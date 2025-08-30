<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

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
function nextOrder(PDO $pdo, int $pid): int {
  $q = $pdo->prepare("SELECT COALESCE(MAX(display_order), -10) + 10 FROM product_specifications WHERE product_id = :pid");
  $q->execute([':pid' => $pid]);
  return (int)($q->fetchColumn() ?: 0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit;
}

$pdo = db();

try {
  $d = input();

  $product_id = (int)($d['product_id'] ?? 0);
  if ($product_id <= 0) { http_response_code(400); echo json_encode(['error'=>'product_id is required']); exit; }

  // verify product exists
  $chk = $pdo->prepare("SELECT id FROM products WHERE id = :id");
  $chk->execute([':id' => $product_id]);
  if (!$chk->fetch()) { http_response_code(422); echo json_encode(['error'=>'product_id not found']); exit; }

  $insertedIds = [];

  // Bulk via specs[] (JSON) or specs_json (form-data as JSON string)
  $specs = $d['specs'] ?? null;
  if (!$specs && isMultipart() && !empty($_POST['specs_json'])) {
    $specs = json_decode((string)$_POST['specs_json'], true);
    if (json_last_error() !== JSON_ERROR_NONE) $specs = null;
  }

  if ($specs && is_array($specs)) {
    $order = isset($d['start_order']) ? (int)$d['start_order'] : nextOrder($pdo, $product_id);
    $stmt = $pdo->prepare("INSERT INTO product_specifications (product_id, spec_name, spec_value, spec_group, display_order) VALUES (:pid,:name,:val,:grp,:ord)");

    foreach ($specs as $idx => $s) {
      $name = trim((string)($s['spec_name'] ?? ''));
      $value = (string)($s['spec_value'] ?? '');
      $group = isset($s['spec_group']) ? trim((string)$s['spec_group']) : null;
      $ord   = isset($s['display_order']) ? (int)$s['display_order'] : $order;

      if ($name === '' || $value === '') {
        http_response_code(422); echo json_encode(['error'=>"specs[$idx] invalid: spec_name and spec_value are required"]); exit;
      }

      $stmt->execute([
        ':pid'=>$product_id, ':name'=>$name, ':val'=>$value,
        ':grp'=>($group !== '' ? $group : null), ':ord'=>$ord
      ]);
      $insertedIds[] = (int)$pdo->lastInsertId();
      $order = $ord + 10;
    }
  } else {
    // Single spec fields
    $name = trim((string)($d['spec_name'] ?? ''));
    $value = (string)($d['spec_value'] ?? '');
    $group = isset($d['spec_group']) ? trim((string)$d['spec_group']) : null;
    $ord = array_key_exists('display_order', $d) ? (int)$d['display_order'] : nextOrder($pdo, $product_id);

    if ($name === '' || $value === '') {
      http_response_code(400); echo json_encode(['error'=>'spec_name and spec_value are required']); exit;
    }

    $ins = $pdo->prepare("INSERT INTO product_specifications (product_id, spec_name, spec_value, spec_group, display_order) VALUES (:pid,:name,:val,:grp,:ord)");
    $ins->execute([
      ':pid'=>$product_id, ':name'=>$name, ':val'=>$value,
      ':grp'=>($group !== '' ? $group : null), ':ord'=>$ord
    ]);
    $insertedIds[] = (int)$pdo->lastInsertId();
  }

  // Fetch inserted items
  $rows = [];
  if ($insertedIds) {
    $in = implode(',', array_fill(0, count($insertedIds), '?'));
    $s = $pdo->prepare("SELECT * FROM product_specifications WHERE id IN ($in) ORDER BY FIELD(id,$in)");
    $s->execute([...$insertedIds, ...$insertedIds]);
    $rows = $s->fetchAll();
  }

  http_response_code(201);
  echo json_encode(['success'=>true, 'data'=>$rows, 'inserted_ids'=>$insertedIds]);

} catch (PDOException $e) {
  http_response_code(500); echo json_encode(['error'=>'Database error','code'=>$e->getCode()]);
} catch (Throwable $t) {
  http_response_code(500); echo json_encode(['error'=>$t->getMessage()]);
}