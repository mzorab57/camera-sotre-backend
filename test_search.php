<?php
// Test search functionality
header('Content-Type: application/json');

// Test different search scenarios
$testCases = [
    'search=test',
    'search=category',
    'search=',
    'page=1&limit=5',
    'search=test&page=1&limit=5'
];

foreach ($testCases as $params) {
    echo "\n=== Testing: $params ===\n";
    
    $url = "http://localhost/api/categories/get.php?$params";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Content-Type: application/json'
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        echo "ERROR: Failed to get response\n";
    } else {
        echo "Response: " . $response . "\n";
    }
    
    echo "\n";
}
?>