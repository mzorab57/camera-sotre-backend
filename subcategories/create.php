<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/protect_admin_employee.php';

// Helpers
function isMultipart(): bool {
  $ct = $_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? '');
  return stripos($ct, 'multipart/form-data') !== false || !empty($_FILES);
}
function input(): array {
  $ct = $_SERVER['CONTENT_TYPE'] ?? '';
  if (stripos($ct, 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $d = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE) return $d ?: [];
  }
  return $_POST ?: [];
}
if (!function_exists('slugify')) {
  function slugify($text) {
    $text = trim($text);
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = trim($text, '-');
    $text = strtolower($text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    return $text ?: 'subcategory';
  }
}
function parseBool($v): int {
  $s = strtolower(trim((string)$v));
  return in_array($s, ['1','true','on','yes'], true) ? 1 : 0;
}
function uploadKey(): ?string {
  foreach (['image','file'] as $k) {
    if (!empty($_FILES[$k]) && !empty($_FILES[$k]['tmp_name'])) return $k;
  }
  return null;
}
function uploadErrorText(int $code): string {
  $map = [
    UPLOAD_ERR_INI_SIZE   => 'exceeds upload_max_filesize',
    UPLOAD_ERR_FORM_SIZE  => 'exceeds MAX_FILE_SIZE',
    UPLOAD_ERR_PARTIAL    => 'partially uploaded',
    UPLOAD_ERR_NO_FILE    => 'no file uploaded',
    UPLOAD_ERR_NO_TMP_DIR => 'missing temp dir',
    UPLOAD_ERR_CANT_WRITE => 'cannot write to disk',
    UPLOAD_ERR_EXTENSION  => 'stopped by extension',
  ];
  return $map[$code] ?? 'unknown upload error';
}
function ensureDir(string $path): void {
  if (!is_dir($path)) {
    if (!@mkdir($path, 0775, true) && !is_dir($path)) {
      throw new RuntimeException('Cannot create upload directory: ' . $path);
    }
  }
  if (!is_writable($path)) {
    throw new RuntimeException('Upload directory not writable: ' . $path);
  }
}
function projectBasePath(): string {
  $twoUp = dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')) ?: '';
  return rtrim(str_replace('\\', '/', $twoUp), '/');
}
function makeFullUrl(string $rel): ?string {
  $host = $_SERVER['HTTP_HOST'] ?? '';
  if (!$host) return null;
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $base = projectBasePath();
  $rel = '/' . ltrim($rel, '/');
  return $scheme . '://' . $host . $base . $rel;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']); exit;
}

$pdo = db();

try {
  $image_url = '';
  if (isMultipart()) {
    $category_id = (int)($_POST['category_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $is_active = array_key_exists('is_active', $_POST) ? parseBool($_POST['is_active']) : 1;
    $image_url = trim($_POST['image_url'] ?? '');
  } else {
    $d = input();
    $category_id = (int)($d['category_id'] ?? 0);
    $name = trim($d['name'] ?? '');
    $type = trim($d['type'] ?? '');
    $slug = trim($d['slug'] ?? '');
    $is_active = array_key_exists('is_active', $d) ? (int)!!$d['is_active'] : 1;
    $image_url = trim($d['image_url'] ?? '');
  }

  if ($category_id <= 0 || $name === '' || $type === '') {
    http_response_code(400); echo json_encode(['error' => 'category_id, name, type are required']); exit;
  }
  if (!in_array($type, ['videography','photography','both'], true)) {
    http_response_code(422); echo json_encode(['error' => 'Invalid type']); exit;
  }
  if ($slug === '') $slug = slugify($name);

  // Verify category exists
  $chk = $pdo->prepare("SELECT id FROM categories WHERE id = :id");
  $chk->execute([':id' => $category_id]);
  if (!$chk->fetch()) {
    http_response_code(422); echo json_encode(['error' => 'category_id not found']); exit;
  }

  // Handle file upload (overrides image_url)
  $key = uploadKey();
  if ($key) {
    $file = $_FILES[$key];
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
      http_response_code(400); echo json_encode(['error' => 'Upload failed', 'details' => uploadErrorText((int)$file['error'])]); exit;
    }
    if (!is_uploaded_file($file['tmp_name'])) {
      http_response_code(400); echo json_encode(['error' => 'No valid uploaded file detected']); exit;
    }
    $maxBytes = 5 * 1024 * 1024;
    if (($file['size'] ?? 0) > $maxBytes) {
      http_response_code(413); echo json_encode(['error' => 'File too large (max 5MB)']); exit;
    }

    $mime = null;
    if (class_exists('finfo')) {
      $finfo = new finfo(FILEINFO_MIME_TYPE);
      $mime = $finfo->file($file['tmp_name']) ?: null;
    } elseif (function_exists('mime_content_type')) {
      $mime = @mime_content_type($file['tmp_name']) ?: null;
    }
    $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
    if (!$mime || !isset($allowed[$mime])) {
      $imginfo = @getimagesize($file['tmp_name']);
      if (!$imginfo || !isset($allowed[$imginfo['mime'] ?? ''])) {
        http_response_code(422); echo json_encode(['error' => 'Only JPG, PNG, WEBP, GIF are allowed']); exit;
      }
      $mime = $imginfo['mime'];
    }
    $ext = $allowed[$mime];

    $projectRoot = dirname(__DIR__);
    $uploadDir = $projectRoot . '/uploads/subcategories';
    ensureDir($uploadDir);

    $fname = date('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
    $dest  = $uploadDir . '/' . $fname;
    if (!@move_uploaded_file($file['tmp_name'], $dest)) {
      http_response_code(500); echo json_encode(['error' => 'Failed to move uploaded file']); exit;
    }
    @chmod($dest, 0644);
    $image_url = '/uploads/subcategories/' . $fname;
  } else {
    if ($image_url !== '' && !preg_match('~^https?://~i', $image_url)) {
      http_response_code(422); echo json_encode(['error' => 'image_url must be a valid http(s) URL']); exit;
    }
  }

  $ins = $pdo->prepare("INSERT INTO subcategories (category_id, name, slug, type, image_url, is_active)
                        VALUES (:cid, :name, :slug, :type, :img, :ia)");
  $ins->execute([
    ':cid' => $category_id, ':name' => $name, ':slug' => $slug,
    ':type' => $type, ':img' => ($image_url !== '' ? $image_url : null), ':ia' => $is_active
  ]);

  $id = (int)$pdo->lastInsertId();
  $get = $pdo->prepare("SELECT * FROM subcategories WHERE id = :id");
  $get->execute([':id' => $id]);
  $row = $get->fetch(PDO::FETCH_ASSOC);

  $full = !empty($row['image_url']) ? makeFullUrl($row['image_url']) : null;

  http_response_code(201);
  echo json_encode(['success'=>true,'data'=>$row,'image_full_url'=>$full]);

} catch (PDOException $e) {
  if ($e->getCode() === '23000') {
    http_response_code(409); echo json_encode(['error' => 'Duplicate slug']); exit;
  }
  http_response_code(500); echo json_encode(['error' => 'Database error', 'code'=>$e->getCode()]);
} catch (Throwable $t) {
  http_response_code(500); echo json_encode(['error' => $t->getMessage()]);
}