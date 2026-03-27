<?php
declare(strict_types=1);

// --- لێرە ئاگاداربە ---
// ئەگەر cors.php ناوەڕۆکەکەی تەنها Header-ی ڕێگەپێدان بێت ئاساییە
// بەڵام ئەگەر Content-Type: application/json تێدایە، دەبێت لێرە لایبەیت.
require_once __DIR__ . '/../config/cors.php';

$path = $_GET['path'] ?? '';
if ($path === '') { 
    header('Content-Type: text/plain');
    http_response_code(400); 
    echo 'path is required'; 
    exit; 
}

// پاککردنەوەی پاثەکە
$rel = '/' . ltrim($path, '/');
// ئەگەر وشەی /api/ تێدابوو لایبەرە چونکە ئێمە لەناو فۆڵدەری باکێندین
if (strpos($rel, '/api/') === 0) {
    $rel = substr($rel, 4);
}

// تەنها ڕێگە بە بوخچەی uploads بدە
if (strpos($rel, '/uploads/') !== 0) { 
    header('Content-Type: text/plain');
    http_response_code(403); 
    echo 'forbidden'; 
    exit; 
}

$root = dirname(__DIR__); // ڕەگی باکێند
$full = realpath($root . $rel);

if ($full === false || !is_file($full)) {
    header('Content-Type: text/plain');
    http_response_code(404); 
    echo 'not found'; 
    exit;
}

// دۆزینەوەی جۆری وێنە (Mime Type)
$mime = null;
if (function_exists('mime_content_type')) { 
    $mime = @mime_content_type($full); 
}
if (!$mime && class_exists('finfo')) { 
    $f = new finfo(FILEINFO_MIME_TYPE); 
    $mime = @$f->file($full); 
}
if (!$mime && function_exists('getimagesize')) { 
    $i = @getimagesize($full); 
    $mime = $i['mime'] ?? null; 
}

if (!$mime) {
    $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
    $map = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml'
    ];
    $mime = $map[$ext] ?? 'application/octet-stream';
}

// --- زۆر گرنگ ---
// سڕینەوەی هەر Header-ێکی پێشوو کە Content-Type بێت بۆ ئەوەی وێنەکە تێک نەچێت
header_remove('Content-Type');

header('Content-Type: ' . $mime);
header('Cache-Control: public, max-age=2592000'); // 30 ڕۆژ
header('Content-Length: ' . filesize($full));

// دڵنیابوونەوە لەوەی هیچ Buffer-ێک نییە کە وێنەکە تێک بدات
if (ob_get_length()) ob_clean();
flush();

readfile($full);
exit;