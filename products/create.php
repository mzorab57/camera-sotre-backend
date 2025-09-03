<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/protect_admin_employee.php';

header('Content-Type: application/json; charset=utf-8');

// Helpers
function isMultipart(): bool {
  $ct = $_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? '');
  return stripos($ct,'multipart/form-data')!==false || !empty($_FILES);
}
function input(): array {
  $ct = $_SERVER['CONTENT_TYPE'] ?? '';
  if (stripos($ct,'application/json')!==false) {
    $raw=file_get_contents('php://input'); $d=json_decode($raw,true);
    if (json_last_error()===JSON_ERROR_NONE) return $d?:[];
  }
  return $_POST?:[];
}
if (!function_exists('slugify')) {
  function slugify($t){$t=trim($t);$t=preg_replace('~[^\pL\d]+~u','-',$t);$t=trim($t,'-');$t=strtolower($t);$t=preg_replace('~[^-\w]+~','',$t);return $t?:'product';}
}
function parseBool($v): int { $s=strtolower(trim((string)$v)); return in_array($s,['1','true','on','yes'],true)?1:0; }
function uploadKey(): ?string { foreach(['image','file'] as $k){ if(!empty($_FILES[$k]) && !empty($_FILES[$k]['tmp_name'])) return $k; } return null; }
function ensureDir(string $path): void {
  if (!is_dir($path)) { if (!@mkdir($path,0775,true) && !is_dir($path)) throw new RuntimeException('Cannot create upload directory: '.$path); }
  if (!is_writable($path)) throw new RuntimeException('Upload directory not writable: '.$path);
}
function makeFullUrl(string $rel): ?string {
  $host=$_SERVER['HTTP_HOST']??''; if(!$host) return null;
  $scheme=(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off')?'https':'http';
  $twoUp=dirname(dirname($_SERVER['SCRIPT_NAME']??''))?:'';
  $base=rtrim(str_replace('\\','/',$twoUp),'/');
  $rel='/'.ltrim($rel,'/');
  return $scheme.'://'.$host.$base.$rel;
}
function parseDecimal($v): ?string {
  if ($v === null) return null;
  $s = str_replace([' ',','], ['',''], (string)$v);
  if ($s==='') return null;
  if (!is_numeric($s)) return null;
  return number_format((float)$s, 2, '.', '');
}

if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }
$pdo = db();

try {
  $isMulti = isMultipart();
  $d = $isMulti ? $_POST : input();

  $subcategory_id = (int)($d['subcategory_id'] ?? 0);
  $name = trim($d['name'] ?? '');
  $model = trim($d['model'] ?? '');
  $slug = trim($d['slug'] ?? '');
  $sku  = trim($d['sku'] ?? '');
  $description = $d['description'] ?? null;
  $short_description = $d['short_description'] ?? null;
  $price = parseDecimal($d['price'] ?? null);
  $discount_price = parseDecimal($d['discount_price'] ?? null);
  $type = trim($d['type'] ?? '');
  $brand = trim($d['brand'] ?? '');
  $is_featured = array_key_exists('is_featured',$d) ? (int)!!$d['is_featured'] : 0;
  $is_active   = array_key_exists('is_active',$d) ? (int)!!$d['is_active']   : 1;
  $meta_title = trim($d['meta_title'] ?? '');
  $meta_description = $d['meta_description'] ?? null;
  $image_url = $isMulti ? trim($_POST['image_url'] ?? '') : trim($d['image_url'] ?? ''); // optional

  if ($subcategory_id<=0 || $name==='' || $type==='' || $price===null) {
    http_response_code(400); echo json_encode(['error'=>'subcategory_id, name, type, price are required']); exit;
  }
  if (!in_array($type, ['videography','photography','both'], true)) {
    http_response_code(422); echo json_encode(['error'=>'Invalid type']); exit;
  }
  if ($discount_price!==null && (float)$discount_price > (float)$price) {
    http_response_code(422); echo json_encode(['error'=>'discount_price cannot be greater than price']); exit;
  }
  if ($slug==='') $slug = slugify($name);

  // verify subcategory exists
  $chk=$pdo->prepare("SELECT id FROM subcategories WHERE id=:id");
  $chk->execute([':id'=>$subcategory_id]);
  if(!$chk->fetch()){ http_response_code(422); echo json_encode(['error'=>'subcategory_id not found']); exit; }

  // Insert product
  $ins = $pdo->prepare("INSERT INTO products
    (subcategory_id,name,model,slug,sku,description,short_description,price,discount_price,type,brand,is_featured,is_active,meta_title,meta_description)
    VALUES (:sid,:name,:model,:slug,:sku,:desc,:short,:price,:discount,:type,:brand,:feat,:act,:mtitle,:mdesc)");
  $ins->execute([
    ':sid'=>$subcategory_id, ':name'=>$name, ':model'=>($model?:null), ':slug'=>$slug, ':sku'=>($sku?:null),
    ':desc'=>$description, ':short'=>$short_description, ':price'=>$price, ':discount'=>$discount_price,
    ':type'=>$type, ':brand'=>($brand?:null), ':feat'=>$is_featured, ':act'=>$is_active,
    ':mtitle'=>($meta_title?:null), ':mdesc'=>$meta_description
  ]);
  $pid = (int)$pdo->lastInsertId();

  // Optional primary image (file or URL) → product_images
  $primaryImageRel = null;
  $key = uploadKey();
  if ($key) {
    $file = $_FILES[$key];
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
      http_response_code(400); echo json_encode(['error'=>'Upload failed']); exit;
    }
    if (!is_uploaded_file($file['tmp_name'])) { http_response_code(400); echo json_encode(['error'=>'No valid uploaded file']); exit; }
    $maxBytes = 5*1024*1024; if (($file['size'] ?? 0) > $maxBytes) { http_response_code(413); echo json_encode(['error'=>'File too large (max 5MB)']); exit; }
    $finfo=new finfo(FILEINFO_MIME_TYPE); $mime=$finfo->file($file['tmp_name']);
    $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
    if (!isset($allowed[$mime])) { http_response_code(422); echo json_encode(['error'=>'Only JPG, PNG, WEBP, GIF are allowed']); exit; }
    $ext=$allowed[$mime];
    $root=dirname(__DIR__); $dir=$root.'/uploads/products'; ensureDir($dir);
    $fname = date('YmdHis').'-'.bin2hex(random_bytes(6)).'.'.$ext;
    $dest = $dir.'/'.$fname;
    if (!@move_uploaded_file($file['tmp_name'],$dest)) { http_response_code(500); echo json_encode(['error'=>'Failed to move uploaded file']); exit; }
    @chmod($dest,0644);
    $primaryImageRel = '/uploads/products/'.$fname;
  } elseif ($image_url!=='') {
    if (!preg_match('~^https?://~i',$image_url)) { http_response_code(422); echo json_encode(['error'=>'image_url must be a valid http(s) URL']); exit; }
    $primaryImageRel = $image_url; // external URL
  }

  if ($primaryImageRel!==null) {
    // next display order
    $q=$pdo->prepare("SELECT COALESCE(MAX(display_order),-10)+10 AS next_order FROM product_images WHERE product_id=:pid");
    $q->execute([':pid'=>$pid]);
    $next = (int)($q->fetchColumn() ?: 0);

    $pi = $pdo->prepare("INSERT INTO product_images (product_id,image_url,is_primary,display_order) VALUES (:pid,:url,1,:ord)");
    $pi->execute([':pid'=>$pid, ':url'=>$primaryImageRel, ':ord'=>$next]);

    $imgId = (int)$pdo->lastInsertId();
    // ensure only one primary
    $pdo->prepare("UPDATE product_images SET is_primary = (id = :id) WHERE product_id = :pid")
        ->execute([':id'=>$imgId, ':pid'=>$pid]);
  }

  // Fetch product + primary image
  $sql = "SELECT p.*,
    (SELECT image_url FROM product_images WHERE product_id=p.id AND is_primary=1 LIMIT 1) AS primary_image_url
    FROM products p WHERE p.id=:id";
  $g=$pdo->prepare($sql); $g->execute([':id'=>$pid]);
  $row=$g->fetch(PDO::FETCH_ASSOC);

  $full=null;
  if (!empty($row['primary_image_url']) && strpos($row['primary_image_url'],'http')!==0) {
    $full = makeFullUrl($row['primary_image_url']);
  } elseif (!empty($row['primary_image_url'])) {
    $full = $row['primary_image_url'];
  }

  http_response_code(201);
  echo json_encode(['success'=>true,'data'=>$row,'primary_image_full_url'=>$full]);

} catch (PDOException $e) {
  if ($e->getCode()==='23000'){ http_response_code(409); echo json_encode(['error'=>'Duplicate slug or SKU']); exit; }
  http_response_code(500); echo json_encode(['error'=>'Database error','code'=>$e->getCode()]);
} catch (Throwable $t) {
  http_response_code(500); echo json_encode(['error'=>$t->getMessage()]);
}