<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

function isMultipart(): bool { $ct=$_SERVER['CONTENT_TYPE']??($_SERVER['HTTP_CONTENT_TYPE']??''); return stripos($ct,'multipart/form-data')!==false || !empty($_FILES); }
function input(): array { $ct=$_SERVER['CONTENT_TYPE']??''; if (stripos($ct,'application/json')!==false){$raw=file_get_contents('php://input');$d=json_decode($raw,true); if(json_last_error()===JSON_ERROR_NONE)return $d?:[];} return $_POST?:[]; }
if (!function_exists('slugify')) { function slugify($t){$t=trim($t);$t=preg_replace('~[^\pL\d]+~u','-',$t);$t=trim($t,'-');$t=strtolower($t);$t=preg_replace('~[^-\w]+~','',$t);return $t?:'product';} }
function parseDecimal($v): ?string { if($v===null)return null; $s=str_replace([' ',','],['',''],(string)$v); if($s==='')return null; if(!is_numeric($s))return null; return number_format((float)$s,2,'.',''); }
function ensureDir(string $p): void { if(!is_dir($p)){ if(!@mkdir($p,0775,true) && !is_dir($p)) throw new RuntimeException('Cannot create upload directory: '.$p);} if(!is_writable($p)) throw new RuntimeException('Upload directory not writable: '.$p); }

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($method, ['POST','PUT','PATCH'], true)) { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }

$pdo = db();
$d = input();
$id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($d['id'] ?? 0);
if ($id<=0) { http_response_code(400); echo json_encode(['error'=>'id is required']); exit; }

// Load product
$cur=$pdo->prepare("SELECT * FROM products WHERE id=:id"); $cur->execute([':id'=>$id]); $current=$cur->fetch();
if(!$current){ http_response_code(404); echo json_encode(['error'=>'Product not found']); exit; }

$fields=[]; $p=[':id'=>$id];

// subcategory_id
if (array_key_exists('subcategory_id',$d)) {
  $sid=(int)$d['subcategory_id']; if($sid<=0){http_response_code(422);echo json_encode(['error'=>'Invalid subcategory_id']);exit;}
  $chk=$pdo->prepare("SELECT id FROM subcategories WHERE id=:id"); $chk->execute([':id'=>$sid]); if(!$chk->fetch()){ http_response_code(422); echo json_encode(['error'=>'subcategory_id not found']); exit; }
  $fields[]='subcategory_id=:sid'; $p[':sid']=$sid;
}

// name
if (array_key_exists('name',$d)) {
  $name=trim((string)$d['name']); if($name===''){http_response_code(422);echo json_encode(['error'=>'name cannot be empty']);exit;}
  $fields[]='name=:name'; $p[':name']=$name;
}

// model
if (array_key_exists('model',$d)) { $fields[]='model=:model'; $p[':model']=trim((string)$d['model'])?:null; }

// slug
if (array_key_exists('slug',$d)) {
  $slug = trim((string)$d['slug']);
  if ($slug==='') { $ref = $p[':name'] ?? $current['name']; $slug = slugify($ref); }
  $fields[]='slug=:slug'; $p[':slug']=$slug;
}

// sku
if (array_key_exists('sku',$d)) { $fields[]='sku=:sku'; $p[':sku']=trim((string)$d['sku'])?:null; }

// descriptions
if (array_key_exists('description',$d)) { $fields[]='description=:desc'; $p[':desc']=$d['description']; }
if (array_key_exists('short_description',$d)) { $fields[]='short_description=:short'; $p[':short']=trim((string)$d['short_description'])?:null; }

// price + discount
if (array_key_exists('price',$d)) {
  $price=parseDecimal($d['price']); if($price===null){http_response_code(422);echo json_encode(['error'=>'Invalid price']);exit;}
  $fields[]='price=:price'; $p[':price']=$price;
}
if (array_key_exists('discount_price',$d)) {
  $discount=parseDecimal($d['discount_price']); $fields[]='discount_price=:discount'; $p[':discount']=$discount;
}

// type
if (array_key_exists('type',$d)) {
  $type=trim((string)$d['type']); if(!in_array($type,['videography','photography','both'],true)){http_response_code(422);echo json_encode(['error'=>'Invalid type']);exit;}
  $fields[]='type=:type'; $p[':type']=$type;
}

// brand, featured, active
if (array_key_exists('brand',$d)) { $fields[]='brand=:brand'; $p[':brand']=trim((string)$d['brand'])?:null; }
if (array_key_exists('is_featured',$d)) { $fields[]='is_featured=:feat'; $p[':feat']=(int)!!$d['is_featured']; }
if (array_key_exists('is_active',$d))   { $fields[]='is_active=:act';   $p[':act']=(int)!!$d['is_active']; }

// meta
if (array_key_exists('meta_title',$d)) { $fields[]='meta_title=:mtitle'; $p[':mtitle']=trim((string)$d['meta_title'])?:null; }
if (array_key_exists('meta_description',$d)) { $fields[]='meta_description=:mdesc'; $p[':mdesc']=$d['meta_description']; }

if (!$fields && !isMultipart() && !array_key_exists('image_url',$d)) {
  http_response_code(400); echo json_encode(['error'=>'No fields to update']); exit;
}

// Optional primary image update (file or URL via image_url)
$primaryRel = null;
if (isMultipart() && !empty($_FILES['image']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
  $file=$_FILES['image'];
  if (($file['error']??UPLOAD_ERR_OK)!==UPLOAD_ERR_OK){ http_response_code(400); echo json_encode(['error'=>'Upload failed']); exit; }
  $max=5*1024*1024; if(($file['size']??0)>$max){ http_response_code(413); echo json_encode(['error'=>'File too large (max 5MB)']); exit; }
  $finfo=new finfo(FILEINFO_MIME_TYPE); $mime=$finfo->file($file['tmp_name']); $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
  if(!isset($allowed[$mime])){ http_response_code(422); echo json_encode(['error'=>'Only JPG, PNG, WEBP, GIF are allowed']); exit; }
  $ext=$allowed[$mime];
  $root=dirname(__DIR__); $dir=$root.'/uploads/products'; ensureDir($dir);
  $fname=date('YmdHis').'-'.bin2hex(random_bytes(6)).'.'.$ext; $dest=$dir.'/'.$fname;
  if(!@move_uploaded_file($file['tmp_name'],$dest)){ http_response_code(500); echo json_encode(['error'=>'Failed to move uploaded file']); exit; }
  @chmod($dest,0644);
  $primaryRel = '/uploads/products/'.$fname;
} elseif (array_key_exists('image_url',$d)) {
  $u = trim((string)$d['image_url']);
  if ($u!=='') {
    if (!preg_match('~^https?://~i',$u)) { http_response_code(422); echo json_encode(['error'=>'image_url must be a valid http(s) URL']); exit; }
    $primaryRel = $u;
  }
}

// Do update if any fields
if ($fields) {
  try {
    $u=$pdo->prepare("UPDATE products SET ".implode(', ',$fields)." WHERE id=:id");
    $u->execute($p);
  } catch (PDOException $e) {
    if ($e->getCode()==='23000'){ http_response_code(409); echo json_encode(['error'=>'Duplicate slug or SKU']); exit; }
    throw $e;
  }
}

// If primary image provided → insert and set as primary
if ($primaryRel!==null) {
  $q=$pdo->prepare("SELECT COALESCE(MAX(display_order),-10)+10 FROM product_images WHERE product_id=:pid");
  $q->execute([':pid'=>$id]); $next=(int)($q->fetchColumn() ?: 0);

  $pi=$pdo->prepare("INSERT INTO product_images (product_id,image_url,is_primary,display_order) VALUES (:pid,:url,1,:ord)");
  $pi->execute([':pid'=>$id, ':url'=>$primaryRel, ':ord'=>$next]);
  $imgId=(int)$pdo->lastInsertId();

  $pdo->prepare("UPDATE product_images SET is_primary = (id = :id) WHERE product_id = :pid")
      ->execute([':id'=>$imgId, ':pid'=>$id]);
}

// Return
$g=$pdo->prepare("SELECT p.*,
 (SELECT image_url FROM product_images WHERE product_id=p.id AND is_primary=1 LIMIT 1) AS primary_image_url
 FROM products p WHERE p.id=:id");
$g->execute([':id'=>$id]); $row=$g->fetch();
echo json_encode(['success'=>true,'data'=>$row]);