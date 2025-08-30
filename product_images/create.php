<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');

function isMultipart(): bool {
  $ct = $_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? '');
  return stripos($ct,'multipart/form-data')!==false || !empty($_FILES);
}
function input(): array {
  $ct = $_SERVER['CONTENT_TYPE'] ?? '';
  if (stripos($ct,'application/json')!==false) {
    $raw = file_get_contents('php://input');
    $d = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE) return $d ?: [];
  }
  return $_POST ?: [];
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
  $host=$_SERVER['HTTP_HOST']??''; if(!$host)return null;
  $scheme=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http';
  $twoUp=dirname(dirname($_SERVER['SCRIPT_NAME']??''))?:'';
  $base=rtrim(str_replace('\\','/',$twoUp),'/');
  $rel='/'.ltrim($rel,'/');
  return $scheme.'://'.$host.$base.$rel;
}
function nextOrder(PDO $pdo, int $pid): int {
  $q=$pdo->prepare("SELECT COALESCE(MAX(display_order),-10) + 10 FROM product_images WHERE product_id=:pid");
  $q->execute([':pid'=>$pid]);
  return (int)($q->fetchColumn() ?: 0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit;
}

$pdo = db();

try {
  $isMulti = isMultipart();
  if ($isMulti) {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $primary_index = isset($_POST['primary_index']) ? (int)$_POST['primary_index'] : null;
    $start_order = isset($_POST['start_order']) ? (int)$_POST['start_order'] : null;
    $image_url_text = trim((string)($_POST['image_url'] ?? ''));
  } else {
    $d = input();
    $product_id = (int)($d['product_id'] ?? 0);
    $primary_index = isset($d['primary_index']) ? (int)$d['primary_index'] : null;
    $start_order = isset($d['start_order']) ? (int)$d['start_order'] : null;
    $image_url_text = trim((string)($d['image_url'] ?? ''));
  }

  if ($product_id <= 0) { http_response_code(400); echo json_encode(['error'=>'product_id is required']); exit; }

  // verify product exists
  $chk=$pdo->prepare("SELECT id FROM products WHERE id=:id");
  $chk->execute([':id'=>$product_id]);
  if(!$chk->fetch()){ http_response_code(422); echo json_encode(['error'=>'product_id not found']); exit; }

  $inserted = [];

  if ($isMulti && (!empty($_FILES['image']) || !empty($_FILES['images']))) {
    // Collect files (image or images[])
    $files = [];
    if (!empty($_FILES['image']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
      $files[] = $_FILES['image'];
    }
    if (!empty($_FILES['images'])) {
      // normalize multi-files array
      $arr = $_FILES['images'];
      if (is_array($arr['tmp_name'])) {
        for ($i=0; $i<count($arr['tmp_name']); $i++) {
          if (is_uploaded_file($arr['tmp_name'][$i])) {
            $files[] = [
              'name'=>$arr['name'][$i],'type'=>$arr['type'][$i],'tmp_name'=>$arr['tmp_name'][$i],
              'error'=>$arr['error'][$i],'size'=>$arr['size'][$i]
            ];
          }
        }
      } elseif (is_uploaded_file($arr['tmp_name'])) {
        $files[] = $arr;
      }
    }

    if (!$files && $image_url_text==='') {
      http_response_code(400); echo json_encode(['error'=>'Provide image file(s) or image_url']); exit;
    }

    $root = dirname(__DIR__);
    $dir  = $root . '/uploads/products';
    ensureDir($dir);

    $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];

    $order = $start_order ?? nextOrder($pdo, $product_id);

    foreach ($files as $idx=>$file) {
      if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        http_response_code(400); echo json_encode(['error'=>'Upload failed']); exit;
      }
      $max=5*1024*1024; if(($file['size']??0)>$max){ http_response_code(413); echo json_encode(['error'=>'File too large (max 5MB)']); exit; }
      $finfo=new finfo(FILEINFO_MIME_TYPE); $mime=$finfo->file($file['tmp_name']);
      if (!isset($allowed[$mime])) { http_response_code(422); echo json_encode(['error'=>'Only JPG, PNG, WEBP, GIF are allowed']); exit; }
      $ext=$allowed[$mime];
      $fname=date('YmdHis').'-'.bin2hex(random_bytes(6)).'.'.$ext;
      $dest=$dir.'/'.$fname;
      if (!@move_uploaded_file($file['tmp_name'],$dest)) { http_response_code(500); echo json_encode(['error'=>'Failed to move uploaded file']); exit; }
      @chmod($dest,0644);
      $rel='/uploads/products/'.$fname;

      $is_primary = ($primary_index !== null) ? (int)($idx===$primary_index) : 0;
      $ins=$pdo->prepare("INSERT INTO product_images (product_id,image_url,is_primary,display_order) VALUES (:pid,:url,:pr,:ord)");
      $ins->execute([':pid'=>$product_id, ':url'=>$rel, ':pr'=>$is_primary, ':ord'=>$order]);
      $inserted[] = (int)$pdo->lastInsertId();
      $order += 10;
    }

    // If no file but image_url_text provided (single URL)
    if (!$files && $image_url_text !== '') {
      if (!preg_match('~^https?://~i',$image_url_text)) { http_response_code(422); echo json_encode(['error'=>'image_url must be http(s) URL']); exit; }
      $ord = $start_order ?? nextOrder($pdo, $product_id);
      $ins=$pdo->prepare("INSERT INTO product_images (product_id,image_url,is_primary,display_order) VALUES (:pid,:url,0,:ord)");
      $ins->execute([':pid'=>$product_id, ':url'=>$image_url_text, ':ord'=>$ord]);
      $inserted[] = (int)$pdo->lastInsertId();
    }

  } else {
    // JSON: support images[] or single image_url
    $d = input();
    $images = $d['images'] ?? null;
    if ($images && !is_array($images)) {
      http_response_code(422); echo json_encode(['error'=>'images must be an array']); exit;
    }
    if (!$images && empty($d['image_url'])) {
      http_response_code(400); echo json_encode(['error'=>'Provide images[] or image_url']); exit;
    }

    $ord = $start_order ?? nextOrder($pdo, $product_id);

    if ($images) {
      foreach ($images as $img) {
        $url = trim((string)($img['image_url'] ?? ''));
        if (!preg_match('~^https?://~i',$url)) { http_response_code(422); echo json_encode(['error'=>'Each image_url must be http(s) URL']); exit; }
        $is_primary = !empty($img['is_primary']) ? 1 : 0;
        $order = isset($img['display_order']) ? (int)$img['display_order'] : $ord;

        $ins=$pdo->prepare("INSERT INTO product_images (product_id,image_url,is_primary,display_order) VALUES (:pid,:url,:pr,:ord)");
        $ins->execute([':pid'=>$product_id, ':url'=>$url, ':pr'=>$is_primary, ':ord'=>$order]);
        $inserted[] = (int)$pdo->lastInsertId();
        $ord = $order + 10;
      }
    } else {
      $url = trim((string)$d['image_url']);
      if (!preg_match('~^https?://~i',$url)) { http_response_code(422); echo json_encode(['error'=>'image_url must be http(s) URL']); exit; }
      $ins=$pdo->prepare("INSERT INTO product_images (product_id,image_url,is_primary,display_order) VALUES (:pid,:url,0,:ord)");
      $ins->execute([':pid'=>$product_id, ':url'=>$url, ':ord'=>$ord]);
      $inserted[] = (int)$pdo->lastInsertId();
    }
  }

  // Ensure only one primary: if none set yet, set the first image as primary
  $hasPrimary=$pdo->prepare("SELECT 1 FROM product_images WHERE product_id=:pid AND is_primary=1 LIMIT 1");
  $hasPrimary->execute([':pid'=>$product_id]);
  if (!$hasPrimary->fetch()) {
    // set first created image as primary
    $firstId = $inserted[0] ?? null;
    if ($firstId) {
      $pdo->prepare("UPDATE product_images SET is_primary = (id = :id) WHERE product_id = :pid")
          ->execute([':id'=>$firstId, ':pid'=>$product_id]);
    }
  } elseif (!empty($inserted) && $primary_index !== null) {
    // if any primary selected among new ones
    $chosenId = $inserted[$primary_index] ?? null;
    if ($chosenId) {
      $pdo->prepare("UPDATE product_images SET is_primary = (id = :id) WHERE product_id = :pid")
          ->execute([':id'=>$chosenId, ':pid'=>$product_id]);
    }
  }

  // Return list
  $s=$pdo->prepare("SELECT * FROM product_images WHERE product_id=:pid ORDER BY is_primary DESC, display_order ASC, id ASC");
  $s->execute([':pid'=>$product_id]);
  $rows=$s->fetchAll();

  // Add full URL for locals
  foreach ($rows as &$r) {
    if (!empty($r['image_url']) && strpos($r['image_url'],'http')!==0) {
      $r['image_full_url'] = makeFullUrl($r['image_url']);
    }
  }

  http_response_code(201);
  echo json_encode(['success'=>true,'data'=>$rows]);

} catch (RuntimeException $re) {
  http_response_code(500); echo json_encode(['error'=>$re->getMessage()]);
} catch (PDOException $e) {
  http_response_code(500); echo json_encode(['error'=>'Database error','code'=>$e->getCode()]);
} catch (Throwable $t) {
  http_response_code(500); echo json_encode(['error'=>$t->getMessage()]);
}