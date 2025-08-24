<?php
require_once __DIR__ . '/config/db.php';

try {
    $pdo = db();
    echo json_encode(["success" => true, "message" => "Database connected successfully"]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}