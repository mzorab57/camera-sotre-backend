<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';

// Helpers
function isMultipart(): bool {
  $ct = $_SERVER['CONTENT_TYPE'] ?? '';
  return stripos($ct, 'multipart/form-data') !== false;
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
    $text = trim((string)$text);
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = trim($text, '-');
    $text = strtolower($text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    return $text ?: 'category';
  }
}
function toBoolInt($v): int {
  if (is_bool($v)) return $v ? 1 : 0;
  if (is_numeric($v)) return ((int)$v) ? 1 : 0;
  if (is_string($v)) {
    $s = strtolower(trim($v));
    return in_array($s, ['1','true','on','yes'], true) ? 1 : 0;
  }
  return 0;
}
function deleteOldLocalIfAny(?string $old): void {
  if (!$old) return;
  if (strpos($old, '/uploads/categories/') === 0) {
    $fullPath = dirname(__DIR__) . $old; // relative → absolute
    if (is_file($fullPath)) @unlink($fullPath);
  }
}

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST','PUT','PATCH'], true)) {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']); exit;
}

$pdo = db();
$data = input();
$id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($data['id'] ?? 0);
if ($id <= 0) {
  http_response_code(400);
  echo json_encode(['error' => 'id is required']); exit;
}

// Load current record
$curStmt = $pdo->prepare("SELECT * FROM categories WHERE id = :id");
$curStmt->execute([':id' => $id]);
$current = $curStmt->fetch();
if (!$current) {
  http_response_code(404);
  echo json_encode(['error' => 'Category not found']); exit;
}

$fields = [];
$params = [':id' => $id];

// name
if (array_key_exists('name', $data)) {
  $name = trim((string)$data['name']);
  if ($name === '') {
    http_response_code(422);
    echo json_encode(['error' => 'name cannot be empty']); exit;
  }
  $fields[] = 'name = :name';
  $params[':name'] = $name;
}

// slug
if (array_key_exists('slug', $data)) {
  $slug = trim((string)$data['slug']);
  if ($slug === '') {
    // اگر slug بەتاڵە، از نو بسازە بە بنەمای name نوێ/کۆن
    $refName = $params[':name'] ?? $current['name'];
    $slug = slugify($refName);
  }
  $fields[] = 'slug = :slug';
  $params[':slug'] = $slug;
}

// is_active
if (array_key_exists('is_active', $data)) {
  $fields[] = 'is_active = :ia';
  $params[':ia'] = toBoolInt($data['is_active']);
}

// Image update (two ways)
$newImageUrl = null;

try {
  if (isMultipart() && !empty($_FILES['image']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
    // File upload
    $file = $_FILES['image'];

    // Max 5MB
    $maxBytes = 5 * 1024 * 1024;
    if ($file['size'] > $maxBytes) {
      http_response_code(413);
      echo json_encode(['error' => 'File too large (max 5MB)']); exit;
    }

    // Validate MIME
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
    if (!isset($allowed[$mime])) {
      http_response_code(422);
      echo json_encode(['error' => 'Only JPG, PNG, WEBP, GIF are allowed']); exit;
    }
    $ext = $allowed[$mime];

    // Prepare upload dir
    $root = dirname(__DIR__);
    $uploadDir = $root . '/uploads/categories';
    if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0775, true); }

    // Unique filename
    $fname = time() . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
    $dest  = $uploadDir . '/' . $fname;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
      http_response_code(500);
      echo json_encode(['error' => 'Failed to move uploaded file']); exit;
    }

    $newImageUrl = '/uploads/categories/' . $fname;
  } elseif (array_key_exists('image_url', $data)) {
    // External URL
    $provided = $data['image_url'];
    if ($provided === null) {
      // null → no change, یا دەتوانیت بە میل خۆتەوە null بکەیت بۆ پاککردن
    } else {
      $provided = trim((string)$provided);
      if ($provided !== '') {
        if (!preg_match('~^https?://~i', $provided)) {
          http_response_code(422);
          echo json_encode(['error' => 'image_url must be a valid http(s) URL']); exit;
        }
        $newImageUrl = $provided;
      }
      // اگر empty string هات → هیچ گۆڕانکاری لە image_url مەکە
    }
  }

  // اگر وێنە گۆڕا → هەیەڵی set و پاککردنی کۆنی ناوخۆیی
  if ($newImageUrl !== null) {
    // Delete old local file if it was local
    deleteOldLocalIfAny($current['image_url']);
    $fields[] = 'image_url = :img';
    $params[':img'] = $newImageUrl;
  }

  if (!$fields) {
    http_response_code(400);
    echo json_encode(['error' => 'No fields to update']); exit;
  }

  // Update
  $sql = "UPDATE categories SET " . implode(', ', $fields) . " WHERE id = :id";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);

  // Fetch updated
  $get = $pdo->prepare("SELECT * FROM categories WHERE id = :id");
  $get->execute([':id' => $id]);
  $row = $get->fetch();

  // Build full URL if local upload
  $full = null;
  if (!empty($row['image_url'])) {
    if (preg_match('~^https?://~i', $row['image_url'])) {
      $full = $row['image_url'];
    } elseif (strpos($row['image_url'], '/uploads/') === 0) {
      $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
      $host = $_SERVER['HTTP_HOST'] ?? '';
      if ($host) $full = $scheme . '://' . $host . $row['image_url'];
    }
  }

  header('Location: /categories/get.php?id=' . $row['id']);
  http_response_code(200);
  echo json_encode(['success' => true, 'data' => $row, 'image_full_url' => $full]);

} catch (PDOException $e) {
  if ($e->getCode() === '23000') {
    http_response_code(409);
    echo json_encode(['error' => 'Duplicate slug']); exit;
  }
  http_response_code(500);
  echo json_encode(['error' => 'Database error']);
} catch (Throwable $t) {
  http_response_code(500);
  echo json_encode(['error' => 'Internal error']);
}