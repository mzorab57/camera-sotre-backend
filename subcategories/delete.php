<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';

function input(): array {
  $ct = $_SERVER['CONTENT_TYPE'] ?? '';
  if (stripos($ct, 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $d = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE) return $d ?: [];
  }
  return $_POST ?: [];
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($method, ['POST','DELETE'], true)) {
  http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit;
}

$in = input();
$id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($in['id'] ?? 0);
if ($id <= 0) { http_response_code(400); echo json_encode(['error'=>'id is required']); exit; }

$restore = isset($_GET['restore']) ? (int)$_GET['restore'] : (int)($in['restore'] ?? 0);
$hard    = isset($_GET['hard']) ? (int)$_GET['hard'] : (int)($in['hard'] ?? 0);

$pdo = db();

try {
  if ($restore) {
    $pdo->prepare("UPDATE subcategories SET is_active = 1 WHERE id = :id")->execute([':id'=>$id]);
    $msg = 'Subcategory restored (is_active=1)';
  } elseif ($hard) {
    try {
      $pdo->prepare("DELETE FROM subcategories WHERE id = :id")->execute([':id'=>$id]);
      $msg = 'Subcategory hard-deleted';
    } catch (PDOException $e) {
      if ($e->getCode() === '23000') {
        http_response_code(409); echo json_encode(['error'=>'Cannot delete: products exist for this subcategory']); exit;
      }
      throw $e;
    }
  } else {
    $pdo->prepare("UPDATE subcategories SET is_active = 0 WHERE id = :id")->execute([':id'=>$id]);
    $msg = 'Subcategory deactivated (is_active=0)';
  }

  $g = $pdo->prepare("SELECT * FROM subcategories WHERE id = :id"); $g->execute([':id'=>$id]);
  $row = $g->fetch();

  echo json_encode(['success'=>true,'message'=>$msg,'data'=>$row]);
} catch (PDOException $e) {
  http_response_code(500); echo json_encode(['error'=>'Database error', 'code'=>$e->getCode()]);
}