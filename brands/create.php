<?php
// brands/create.php
declare(strict_types=1);

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/protect_admin_employee.php';

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
    return $text ?: 'brand';
  }
}
function uploadKey(): ?string {
  foreach (['logo','image','file'] as $k) {
    if (!empty($_FILES[$k]) && !empty($_FILES[$k]['tmp_name'])) return $k;
  }
  return null;
}
function ensureDir(string $path): void {
  if (!is_dir($path)) {
    if (!@mkdir($path, 0775, true) && !is_dir($path)) {
      throw new RuntimeException('Cannot create upload directory: ' . $path);
    }
  }
  if (!is_writable($path)) throw new RuntimeException('Upload directory not writable: ' . $path);
}
function makeFullUrl(string $rel): ?string {
  $host = $_SERVER['HTTP_HOST'] ?? '';
  if (!$host) return null;
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $twoUp = dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')) ?: '';
  $base = rtrim(str_replace('\\', '/', $twoUp), '/');
  $rel = '/' . ltrim($rel, '/');
  return $scheme . '://' . $host . $base . $rel;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit;
}

$pdo = db();

try {
  $isMulti = isMultipart();
  $d = $isMulti ? $_POST : input();

  $name = trim($d['name'] ?? '');
  if ($name === '') { http_response_code(400); echo json_encode(['error'=>'name is required']); exit; }

  $slug = trim($d['slug'] ?? '');
  if ($slug === '') $slug = slugify($name);

  $description = $d['description'] ?? null;
  $is_active = array_key_exists('is_active', $d) ? (int)!!$d['is_active'] : 1;
  $logo_url = $isMulti ? trim((string)($_POST['logo_url'] ?? '')) : trim((string)($d['logo_url'] ?? ''));

  // Handle logo file upload (overrides logo_url)
  $key = uploadKey();
  if ($key) {
    $file = $_FILES[$key];
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
      http_response_code(400); echo json_encode(['error'=>'Upload failed']); exit;
    }
    if (!is_uploaded_file($file['tmp_name'])) { http_response_code(400); echo json_encode(['error'=>'No valid uploaded file']); exit; }
    $max = 5 * 1024 * 1024;
    if (($file['size'] ?? 0) > $max) { http_response_code(413); echo json_encode(['error'=>'File too large (max 5MB)']); exit; }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif','image/svg+xml'=>'svg'];
    if (!isset($allowed[$mime])) { http_response_code(422); echo json_encode(['error'=>'Only JPG, PNG, WEBP, GIF, SVG are allowed']); exit; }
    $ext = $allowed[$mime];

    $root = dirname(__DIR__);
    $dir  = $root . '/uploads/brands';
    ensureDir($dir);

    $fname = date('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
    $dest  = $dir . '/' . $fname;
    if (!@move_uploaded_file($file['tmp_name'], $dest)) { http_response_code(500); echo json_encode(['error'=>'Failed to move uploaded file']); exit; }
    @chmod($dest, 0644);
    $logo_url = '/uploads/brands/' . $fname;
  } else {
    // Only URL mode
    if ($logo_url !== '' && !preg_match('~^https?://~i', $logo_url)) {
      http_response_code(422); echo json_encode(['error'=>'logo_url must be http(s) URL']); exit;
    }
  }

  $ins = $pdo->prepare("INSERT INTO brands (name, slug, logo_url, description, is_active)
                        VALUES (:name,:slug,:logo,:desc,:ia)");
  $ins->execute([
    ':name'=>$name, ':slug'=>$slug,
    ':logo'=>($logo_url !== '' ? $logo_url : null),
    ':desc'=>$description, ':ia'=>$is_active
  ]);

  $id = (int)$pdo->lastInsertId();
  $g = $pdo->prepare("SELECT * FROM brands WHERE id = :id");
  $g->execute([':id'=>$id]);
  $row = $g->fetch(PDO::FETCH_ASSOC);

  $full = !empty($row['logo_url']) && strpos($row['logo_url'],'http')!==0 ? makeFullUrl($row['logo_url']) : ($row['logo_url'] ?? null);

  http_response_code(201);
  echo json_encode(['success'=>true,'data'=>$row,'logo_full_url'=>$full]);

} catch (PDOException $e) {
  if ($e->getCode()==='23000'){ http_response_code(409); echo json_encode(['error'=>'Duplicate name or slug']); exit; }
  http_response_code(500); echo json_encode(['error'=>'Database error','code'=>$e->getCode()]);
} catch (Throwable $t) {
  http_response_code(500); echo json_encode(['error'=>$t->getMessage()]);
}