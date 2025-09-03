<?php
//  bo war grtny amary gshty database ka , la gall latest products
require_once __DIR__ . '/../middleware/protect_admin_employee.php';

$pdo = db();

$counts = [];
$counts['products_total'] = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$counts['products_active'] = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE is_active=1")->fetchColumn();
$counts['categories'] = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$counts['subcategories'] = (int)$pdo->query("SELECT COUNT(*) FROM subcategories")->fetchColumn();
$counts['brands'] = (int)$pdo->query("SELECT COUNT(*) FROM brands")->fetchColumn();
$counts['tags'] = (int)$pdo->query("SELECT COUNT(*) FROM tags")->fetchColumn();
$counts['images'] = (int)$pdo->query("SELECT COUNT(*) FROM product_images")->fetchColumn();

$latest = $pdo->query("SELECT id,name,slug,price,brand,created_at
  FROM products ORDER BY created_at DESC LIMIT 10")->fetchAll();

echo json_encode(['success'=>true,'counts'=>$counts,'latest_products'=>$latest]);