<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/protect_admin_employee.php';

header('Content-Type: application/json; charset=utf-8');

// Helpers
function isMultipart(): bool { $ct=$_SERVER['CONTENT_TYPE']??($_SERVER['HTTP_CONTENT_TYPE']??''); return stripos($ct,'multipart/form-data')!==false || !empty($_FILES); }
function input(): array { $ct=$_SERVER['CONTENT_TYPE']??''; if (stripos($ct,'application/json')!==false){$raw=file_get_contents('php://input');$d=json_decode($raw,true); if(json_last_error()===JSON_ERROR_NONE)return $d?:[];} return $_POST?:[]; }
if (!function_exists('slugify')) { function slugify($t){$t=trim($t);$t=preg_replace('~[^\pL\d]+~u','-',$t);$t=trim($t,'-');$t=strtolower($t);$t=preg_replace('~[^-\w]+~','',$t);return $t?:'brand';} }
function uploadKey(): ?string { foreach(['logo','image','file'] as $k){ if(!empty($_FILES[$k]) && !empty($_FILES[$k]['tmp_name'])) return $k; } return null; }
function ensureDir(string $p): void { if(!is_dir($p)){ if(!@mkdir($p,0775,true) && !is_dir($p)) throw new RuntimeException('Cannot create upload directory: '.$p);} if(!is_writable($p)) throw new RuntimeException('Upload directory not writable: '.$p); }
function deleteOldLocalIfAny(?string $url): void {
  if (!$url) return;
  if (strpos($url, '/uploads/brands/') === 0) {
    $path = dirname(__DIR__) . $url;
    if (is_file($path)) @unlink($path);
  }
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($method, ['POST','PUT','PATCH'], true)) { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }

$pdo = db();
$d = input();
$id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($d['id'] ?? 0);
if ($id <= 0) { http_response_code(400); echo json_encode(['error'=>'id is required']); exit; }

$cur = $pdo->prepare("SELECT * FROM brands WHERE id = :id");
$cur->execute([':id'=>$id]);
$current = $cur->fetch(PDO::FETCH_ASSOC);
if (!$current) { http_response_code(404); echo json_encode(['error'=>'Brand not found']); exit; }

$fields=[]; $p=[':id'=>$id];

// name
if (array_key_exists('name',$d)) {
  $name = trim((string)$d['name']);
  if ($name === '') { http_response_code(422); echo json_encode(['error'=>'name cannot be empty']); exit; }
  $fields[]='name = :name'; $p[':name']=$name;
}

// slug
if (array_key_exists('slug',$d)) {
  $slug = trim((string)$d['slug']);
  if ($slug === '') {
    $ref = $p[':name'] ?? $current['name'];
    $slug = slugify($ref);
  }
  $fields[]='slug = :slug'; $p[':slug']=$slug;
}

// description
if (array_key_exists('description',$d)) {
  $fields[]='description = :desc'; $p[':desc'] = ($d['description'] !== '' ? $d['description'] : null);
}

// is_active
if (array_key_exists('is_active',$d)) {
  $fields[]='is_active = :ia'; $p[':ia'] = (int)!!$d['is_active'];
}

// Logo update (file or URL)
$newLogo = null;
if (isMultipart() && uploadKey()) {
  $key = uploadKey();
  $file = $_FILES[$key];
  if (($file['error'] ?? UPLOAD_ERR_OK)!==UPLOAD_ERR_OK){ http_response_code(400); echo json_encode(['error'=>'Upload failed']); exit; }
  if (!is_uploaded_file($file['tmp_name'])) { http_response_code(400); echo json_encode(['error'=>'No valid uploaded file']); exit; }
  $max=5*1024*1024; if(($file['size']??0)>$max){ http_response_code(413); echo json_encode(['error'=>'File too large (max 5MB)']); exit; }
  $finfo=new finfo(FILEINFO_MIME_TYPE); $mime=$finfo->file($file['tmp_name']);
  $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif','image/svg+xml'=>'svg'];
  if(!isset($allowed[$mime])){ http_response_code(422); echo json_encode(['error'=>'Only JPG, PNG, WEBP, GIF, SVG are allowed']); exit; }
  $ext=$allowed[$mime];

  $root=dirname(__DIR__); $dir=$root.'/uploads/brands'; ensureDir($dir);
  $fname=date('YmdHis').'-'.bin2hex(random_bytes(6)).'.'.$ext; $dest=$dir.'/'.$fname;
  if(!@move_uploaded_file($file['tmp_name'],$dest)){ http_response_code(500); echo json_encode(['error'=>'Failed to move uploaded file']); exit; }
  @chmod($dest,0644);
  $newLogo = '/uploads/brands/'.$fname;
} elseif (array_key_exists('logo_url',$d)) {
  $u = $d['logo_url'];
  if ($u !== null) {
    $u = trim((string)$u);
    if ($u !== '') {
      if (!preg_match('~^https?://~i',$u)) { http_response_code(422); echo json_encode(['error'=>'logo_url must be http(s) URL']); exit; }
      $newLogo = $u;
    }
  }
}

if ($newLogo !== null) {
  deleteOldLocalIfAny($current['logo_url']);
  $fields[]='logo_url = :logo'; $p[':logo']=$newLogo;
}

if (!$fields) { http_response_code(400); echo json_encode(['error'=>'No fields to update']); exit; }

try {
  $u = $pdo->prepare("UPDATE brands SET ".implode(', ', $fields)." WHERE id = :id");
  $u->execute($p);

  $g = $pdo->prepare("SELECT * FROM brands WHERE id = :id");
  $g->execute([':id'=>$id]);
  $row = $g->fetch(PDO::FETCH_ASSOC);

  $full = !empty($row['logo_url']) && strpos($row['logo_url'],'http')!==0 ? (function($r){ $h=$_SERVER['HTTP_HOST']??''; if(!$h)return null; $sch=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http'; $two=dirname(dirname($_SERVER['SCRIPT_NAME']??''))?:''; $b=rtrim(str_replace('\\','/',$two),'/'); return $sch.'://'.$h.$b.'/'.ltrim($r,'/'); })($row['logo_url']) : ($row['logo_url'] ?? null);

  echo json_encode(['success'=>true,'data'=>$row,'logo_full_url'=>$full]);
} catch (PDOException $e) {
  if ($e->getCode()==='23000'){ http_response_code(409); echo json_encode(['error'=>'Duplicate name or slug']); exit; }
  http_response_code(500); echo json_encode(['error'=>'Database error','code'=>$e->getCode()]);
}