<?php
require_once __DIR__ . '/config/db.php';

$pdo = db();

echo "=== Subcategories Table Structure ===\n";
$stmt = $pdo->query('DESCRIBE subcategories');
while($row = $stmt->fetch()) {
    echo $row['Field'] . ' - ' . $row['Type'] . ' - ' . $row['Null'] . ' - ' . $row['Default'] . "\n";
}

echo "\n=== Sample Subcategories Data ===\n";
$stmt = $pdo->query('SELECT s.*, c.name as category_name FROM subcategories s LEFT JOIN categories c ON s.category_id = c.id LIMIT 5');
while($row = $stmt->fetch()) {
    echo "ID: {$row['id']}, Name: {$row['name']}, Category: {$row['category_name']}, Type: {$row['type']}, Active: {$row['is_active']}\n";
}

echo "\n=== Total Count ===\n";
$stmt = $pdo->query('SELECT COUNT(*) as total FROM subcategories');
$count = $stmt->fetch();
echo "Total subcategories: {$count['total']}\n";
?>