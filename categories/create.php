<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';

// Always respond JSON
header('Content-Type: application/json; charset=utf-8');

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
    return $text ?: 'category';
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
  // If project is inside a subfolder (e.g., /myproject), keep it
  $twoUp = dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')) ?: '';
  return rtrim(str_replace('\\', '/', $twoUp), '/');
}
function makeFullUrl(string $rel): ?string {
  $host = $_SERVER['HTTP_HOST'] ?? '';
  if (!$host) return null;
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $base = projectBasePath(); // e.g., /myproject
  $rel = '/' . ltrim($rel, '/'); // ensure leading slash
  return $scheme . '://' . $host . $base . $rel;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']); exit;
}

$pdo = db();
$DEBUG = isset($_GET['debug']);

try {
  if (isMultipart()) {
    // multipart/form-data
    $name = trim($_POST['name'] ?? '');
    if ($name === '') { http_response_code(400); echo json_encode(['error'=>'name is required']); exit; }

    $slug = trim($_POST['slug'] ?? '');
    if ($slug === '') $slug = slugify($name);

    $image_url = trim($_POST['image_url'] ?? ''); // optional URL text
    $is_active = array_key_exists('is_active', $_POST) ? parseBool($_POST['is_active']) : 1;

    $key = uploadKey();
    if ($key) {
      $file = $_FILES[$key];

      // PHP upload error?
      if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode([
          'error' => 'Upload failed',
          'details' => uploadErrorText((int)($file['error'] ?? -1))
        ]); exit;
      }

      if (!is_uploaded_file($file['tmp_name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'No valid uploaded file detected']); exit;
      }

      // Size limit (5MB)
      $maxBytes = 5 * 1024 * 1024;
      if (($file['size'] ?? 0) > $maxBytes) {
        http_response_code(413);
        echo json_encode(['error' => 'File too large (max 5MB)']); exit;
      }

      // Detect MIME
      $mime = null;
      if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']) ?: null;
      } elseif (function_exists('mime_content_type')) {
        $mime = @mime_content_type($file['tmp_name']) ?: null;
      }
      $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
      ];
      if (!$mime || !isset($allowed[$mime])) {
        // As last resort, validate via getimagesize
        $imginfo = @getimagesize($file['tmp_name']);
        if (!$imginfo || !in_array($imginfo['mime'], array_keys($allowed), true)) {
          http_response_code(422);
          echo json_encode(['error' => 'Only JPG, PNG, WEBP, GIF are allowed']); exit;
        }
        $mime = $imginfo['mime'];
      }
      $ext = $allowed[$mime];

      // Prepare upload dir under project root
      $projectRoot = dirname(__DIR__); // project root (where config/ and uploads/ live)
      $uploadDir = $projectRoot . '/uploads/categories';
      ensureDir($uploadDir);

      // Unique file name
      $fname = date('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
      $dest  = $uploadDir . '/' . $fname;

      if (!@move_uploaded_file($file['tmp_name'], $dest)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to move uploaded file (check folder permissions)']); exit;
      }

      @chmod($dest, 0644);

      // Store relative URL (relative to project base)
      $image_url = '/uploads/categories/' . $fname;
    } else {
      // No file: accept external URL if valid, otherwise leave null
      if ($image_url !== '' && !preg_match('~^https?://~i', $image_url)) {
        http_response_code(422);
        echo json_encode(['error' => 'image_url must be a valid http(s) URL']); exit;
      }
    }

    // Insert
    $ins = $pdo->prepare("INSERT INTO categories (name, slug, image_url, is_active) VALUES (:name, :slug, :img, :ia)");
    $ins->execute([
      ':name' => $name,
      ':slug' => $slug,
      ':img'  => ($image_url !== '' ? $image_url : null),
      ':ia'   => $is_active
    ]);

    $id = (int)$pdo->lastInsertId();
    $get = $pdo->prepare("SELECT * FROM categories WHERE id = :id");
    $get->execute([':id' => $id]);
    $row = $get->fetch(PDO::FETCH_ASSOC);

    $full = null;
    if (!empty($row['image_url'])) {
      $full = makeFullUrl($row['image_url']);
    }

    $resp = ['success'=>true, 'data'=>$row, 'image_full_url'=>$full];
    if ($DEBUG) {
      $resp['debug'] = [
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? null,
        'files' => $_FILES,
        'project_base' => projectBasePath(),
        'upload_dir' => $uploadDir ?? null
      ];
    }

    http_response_code(201);
    echo json_encode($resp); exit;

  } else {
    // application/json
    $data = input();

    $name = trim($data['name'] ?? '');
    if ($name === '') { http_response_code(400); echo json_encode(['error'=>'name is required']); exit; }

    $slug = trim($data['slug'] ?? '');
    if ($slug === '') $slug = slugify($name);

    $image_url = trim($data['image_url'] ?? ''); // external URL only in JSON
    if ($image_url !== '' && !preg_match('~^https?://~i', $image_url)) {
      http_response_code(422);
      echo json_encode(['error' => 'image_url must be a valid http(s) URL']); exit;
    }

    $is_active = array_key_exists('is_active', $data) ? (int)!!$data['is_active'] : 1;

    $ins = $pdo->prepare("INSERT INTO categories (name, slug, image_url, is_active) VALUES (:name, :slug, :img, :ia)");
    $ins->execute([
      ':name' => $name,
      ':slug' => $slug,
      ':img'  => ($image_url !== '' ? $image_url : null),
      ':ia'   => $is_active
    ]);

    $id = (int)$pdo->lastInsertId();
    $get = $pdo->prepare("SELECT * FROM categories WHERE id = :id");
    $get->execute([':id' => $id]);
    $row = $get->fetch(PDO::FETCH_ASSOC);

    http_response_code(201);
    echo json_encode(['success'=>true, 'data'=>$row]); exit;
  }

} catch (PDOException $e) {
  if ($e->getCode() === '23000') {
    http_response_code(409);
    echo json_encode(['error' => 'Duplicate slug']); exit;
  }
  http_response_code(500);
  echo json_encode(['error' => 'Database error', 'code' => $e->getCode()]);
} catch (Throwable $t) {
  http_response_code(500);
  echo json_encode(['error' => $t->getMessage()]);
}