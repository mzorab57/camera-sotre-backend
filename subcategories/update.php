<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/protect_admin_employee.php';

// Helpers (same as create)
function isMultipart(): bool { $ct=$_SERVER['CONTENT_TYPE']??($_SERVER['HTTP_CONTENT_TYPE']??''); return stripos($ct,'multipart/form-data')!==false || !empty($_FILES); }
function input(): array { $ct=$_SERVER['CONTENT_TYPE']??''; if (stripos($ct,'application/json')!==false){$raw=file_get_contents('php://input');$d=json_decode($raw,true); if(json_last_error()===JSON_ERROR_NONE)return $d?:[];} return $_POST?:[]; }
if (!function_exists('slugify')) { function slugify($t){$t=trim($t);$t=preg_replace('~[^\pL\d]+~u','-',$t);$t=trim($t,'-');$t=strtolower($t);$t=preg_replace('~[^-\w]+~','',$t);return $t?:'subcategory';} }
function parseBool($v): int { $s=strtolower(trim((string)$v)); return in_array($s,['1','true','on','yes'],true)?1:0; }
function uploadKey(): ?string { foreach(['image','file'] as $k){ if(!empty($_FILES[$k]) && !empty($_FILES[$k]['tmp_name'])) return $k; } return null; }
function uploadErrorText(int $c): string { $m=[1=>'exceeds upload_max_filesize',2=>'exceeds MAX_FILE_SIZE',3=>'partially uploaded',4=>'no file uploaded',6=>'missing temp dir',7=>'cannot write to disk',8=>'stopped by extension']; return $m[$c]??'unknown'; }
function ensureDir(string $p): void { if(!is_dir($p)){ if(!@mkdir($p,0775,true) && !is_dir($p)) throw new RuntimeException('Cannot create upload directory: '.$p);} if(!is_writable($p)) throw new RuntimeException('Upload directory not writable: '.$p); }
function makeFullUrl(string $rel): ?string { $host=$_SERVER['HTTP_HOST']??''; if(!$host)return null; $scheme=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http'; $twoUp=dirname(dirname($_SERVER['SCRIPT_NAME']??''))?:''; $base=rtrim(str_replace('\\','/',$twoUp),'/'); $rel='/'.ltrim($rel,'/'); return $scheme.'://'.$host.$base.$rel; }
function deleteOldLocalIfAny(?string $old): void {
  if (!$old) return;
  if (strpos($old, '/uploads/subcategories/') === 0) {
    $fullPath = dirname(__DIR__) . $old;
    if (is_file($fullPath)) @unlink($fullPath);
  }
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($method, ['POST','PUT','PATCH'], true)) {
  http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit;
}

$pdo = db();
$in = input();
$id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($in['id'] ?? 0);
if ($id <= 0) { http_response_code(400); echo json_encode(['error'=>'id is required']); exit; }

// Load current
$cur = $pdo->prepare("SELECT * FROM subcategories WHERE id = :id");
$cur->execute([':id'=>$id]);
$current = $cur->fetch(PDO::FETCH_ASSOC);
if (!$current) { http_response_code(404); echo json_encode(['error'=>'Subcategory not found']); exit; }

$fields=[]; $p=[':id'=>$id];

// category_id
if (array_key_exists('category_id', $in)) {
  $cid = (int)$in['category_id'];
  if ($cid <= 0) { http_response_code(422); echo json_encode(['error'=>'Invalid category_id']); exit; }
  $chk = $pdo->prepare("SELECT id FROM categories WHERE id = :id"); $chk->execute([':id'=>$cid]);
  if (!$chk->fetch()) { http_response_code(422); echo json_encode(['error'=>'category_id not found']); exit; }
  $fields[]='category_id = :cid'; $p[':cid']=$cid;
}

// name
if (array_key_exists('name', $in)) {
  $name = trim((string)$in['name']);
  if ($name === '') { http_response_code(422); echo json_encode(['error'=>'name cannot be empty']); exit; }
  $fields[]='name = :name'; $p[':name']=$name;
}

// slug
if (array_key_exists('slug', $in)) {
  $slug = trim((string)$in['slug']);
  if ($slug === '') {
    $ref = $p[':name'] ?? $current['name'];
    $slug = slugify($ref);
  }
  $fields[]='slug = :slug'; $p[':slug']=$slug;
}

// type
if (array_key_exists('type', $in)) {
  $type = trim((string)$in['type']);
  if (!in_array($type, ['videography','photography','both'], true)) {
    http_response_code(422); echo json_encode(['error'=>'Invalid type']); exit;
  }
  $fields[]='type = :type'; $p[':type']=$type;
}

// is_active
if (array_key_exists('is_active', $in)) {
  $fields[]='is_active = :ia'; $p[':ia'] = (int)!!$in['is_active'];
}

// image (upload or URL)
$newImage = null;

// file upload
$key = uploadKey();
if ($key) {
  $file = $_FILES[$key];
  if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
    http_response_code(400); echo json_encode(['error'=>'Upload failed', 'details'=>uploadErrorText((int)$file['error'])]); exit;
  }
  if (!is_uploaded_file($file['tmp_name'])) {
    http_response_code(400); echo json_encode(['error'=>'No valid uploaded file detected']); exit;
  }
  $maxBytes = 5*1024*1024;
  if (($file['size'] ?? 0) > $maxBytes) { http_response_code(413); echo json_encode(['error'=>'File too large (max 5MB)']); exit; }

  $mime = null;
  if (class_exists('finfo')) { $finfo=new finfo(FILEINFO_MIME_TYPE); $mime=$finfo->file($file['tmp_name']) ?: null; }
  elseif (function_exists('mime_content_type')) { $mime=@mime_content_type($file['tmp_name']) ?: null; }
  $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
  if (!$mime || !isset($allowed[$mime])) {
    $imginfo=@getimagesize($file['tmp_name']);
    if (!$imginfo || !isset($allowed[$imginfo['mime'] ?? ''])) {
      http_response_code(422); echo json_encode(['error'=>'Only JPG, PNG, WEBP, GIF are allowed']); exit;
    }
    $mime = $imginfo['mime'];
  }
  $ext=$allowed[$mime];

  $root = dirname(__DIR__);
  $uploadDir = $root . '/uploads/subcategories';
  ensureDir($uploadDir);

  $fname = date('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
  $dest = $uploadDir . '/' . $fname;
  if (!@move_uploaded_file($file['tmp_name'], $dest)) {
    http_response_code(500); echo json_encode(['error'=>'Failed to move uploaded file']); exit;
  }
  @chmod($dest, 0644);
  $newImage = '/uploads/subcategories/' . $fname;
} elseif (array_key_exists('image_url', $in)) {
  $provided = $in['image_url'];
  if ($provided !== null) {
    $provided = trim((string)$provided);
    if ($provided !== '') {
      if (!preg_match('~^https?://~i', $provided)) { http_response_code(422); echo json_encode(['error'=>'image_url must be a valid http(s) URL']); exit; }
      $newImage = $provided;
    }
  }
}

if ($newImage !== null) {
  deleteOldLocalIfAny($current['image_url']);
  $fields[]='image_url = :img'; $p[':img']=$newImage;
}

if (!$fields) {
  http_response_code(400); echo json_encode(['error'=>'No fields to update']); exit;
}

try {
  $u = $pdo->prepare("UPDATE subcategories SET ".implode(', ', $fields)." WHERE id = :id");
  $u->execute($p);

  $g = $pdo->prepare("SELECT * FROM subcategories WHERE id = :id"); $g->execute([':id'=>$id]);
  $row = $g->fetch(PDO::FETCH_ASSOC);

  $full = !empty($row['image_url']) ? makeFullUrl($row['image_url']) : null;

  echo json_encode(['success'=>true,'data'=>$row,'image_full_url'=>$full]);
} catch (PDOException $e) {
  if ($e->getCode()==='23000') { http_response_code(409); echo json_encode(['error'=>'Duplicate slug']); exit; }
  http_response_code(500); echo json_encode(['error'=>'Database error', 'code'=>$e->getCode()]);
}