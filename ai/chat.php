<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents('php://input');
$body = json_decode($raw, true) ?? [];
$messages = $body['messages'] ?? [];
$sessionStart = !empty($body['session_start']);

// ١. بەخێرهاتن لە سەرەتای چات
if ($sessionStart || empty($messages)) {
    echo json_encode([
        'success' => true,
        'reply' => 'سڵاو! بەخێربێیت بۆ Adnan Shop 📷 من AI ئامێرەکەتم — پسپۆڕی کامێرا و ئامێرەکان. چۆن دەتوانم یارمەتیت بدەم؟'
    ]);
    exit;
}

// ٢. دۆزینەوەی دوایین نامەی یوزەر
$lastUserQuery = "";
foreach (array_reverse($messages) as $m) {
    if (($m['role'] ?? '') === 'user') {
        $lastUserQuery = trim($m['content']);
        break;
    }
}

$productsCtx = [];
$allProducts  = [];
$brandSummary = [];

try {
    $pdo = db();
    if ($pdo) {

        $baseSql = "
            SELECT p.id, p.slug, p.name, p.brand, p.model, p.price, p.description,
                   c.name  AS category,
                   s.name  AS subcategory,
                   pi.image_url
            FROM products p
            LEFT JOIN subcategories s  ON p.subcategory_id = s.id
            LEFT JOIN categories    c  ON s.category_id    = c.id
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE p.is_active = 1
        ";

        // ---- هەموو بەرهەمەکان بۆ brand summary ----
        $allStmt    = $pdo->query($baseSql . " ORDER BY p.brand, p.name");
        $allProducts = $allStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($allProducts as $p) {
            $b = $p['brand'] ?? 'Unknown';
            $brandSummary[$b] = ($brandSummary[$b] ?? 0) + 1;
        }
        ksort($brandSummary);

        // ---- بەرهەمە پەیوەندیدارەکان بۆ query ----
        $isCompare = preg_match('/(vs\.?|versus|بەراورد|compare|مقارنة)/ui', $lastUserQuery);

        if ($isCompare) {
            $parts = preg_split('/(vs\.?|versus|بەراورد|compare|مقارنة)/ui', $lastUserQuery);
            $q1 = '%' . trim($parts[0] ?? '') . '%';
            $q2 = '%' . trim($parts[1] ?? '') . '%';
            $stmt = $pdo->prepare($baseSql . "
                AND (
                    (p.name LIKE ? OR p.brand LIKE ? OR p.model LIKE ?)
                    OR
                    (p.name LIKE ? OR p.brand LIKE ? OR p.model LIKE ?)
                )
                LIMIT 2
            ");
            $stmt->execute([$q1,$q1,$q1, $q2,$q2,$q2]);
            $productsCtx = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } else {
            $q = "%$lastUserQuery%";
            $stmt = $pdo->prepare($baseSql . "
                AND (
                    p.name        LIKE ? OR
                    p.brand       LIKE ? OR
                    p.model       LIKE ? OR
                    p.description LIKE ? OR
                    c.name        LIKE ? OR
                    s.name        LIKE ?
                )
                ORDER BY
                    CASE WHEN p.brand LIKE ? THEN 0
                         WHEN p.name  LIKE ? THEN 1
                         ELSE 2 END,
                    p.price ASC
            ");
            $stmt->execute([$q,$q,$q,$q,$q,$q, $q,$q]);
            $productsCtx = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // ئەگەر هیچ نەدۆزرایەوە → هەموو بەرهەمەکان بدە بۆ AI
        if (empty($productsCtx)) {
            $productsCtx = $allProducts; // ← هەموو بەرهەمەکان، نەک RAND() LIMIT 6
        }
    }
} catch (Throwable $e) {
    $productsCtx = [];
    $allProducts  = [];
}

// ---- خلاصەی brand ----
$totalCount = array_sum($brandSummary);
$brandText  = implode(', ', array_map(
    fn($b, $c) => "$b ($c بەرهەم)",
    array_keys($brandSummary),
    $brandSummary
));

// ---- وێنەی URL چاككردن ----
$imageBase = 'https://adnanshops.com';
foreach ($productsCtx as &$p) {
    if (!empty($p['image_url']) && str_starts_with($p['image_url'], '/')) {
        $p['image_url'] = $imageBase . $p['image_url'];
    }
    $slug = $p['slug'] ?? strtolower(preg_replace('/\s+/', '-', $p['name'] ?? ''));
    $p['details_url'] = "https://adnanshops.com/details/" . rawurlencode($slug);
}
unset($p);

// ٣. System Prompt
$productJson = json_encode($productsCtx, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

$systemInstructions = <<<PROMPT
You are **"Adnan Shop AI Expert"** — a professional, friendly, and knowledgeable camera & equipment advisor for **Adnan Shop**, a camera store in Sulaymaniyah, Iraq.

---

## 🗄️ YOUR FULL PRODUCT KNOWLEDGE

The store has **{$totalCount} active products** from these brands:
{$brandText}

### Products matching the current user query (use these for your answer):
{$productJson}

> If a user asks about a brand or category not in the matched products above, you still know the store carries products from: {$brandText}. Tell them what's available and guide them to explore.

---

## 🌐 LANGUAGE RULES
- Always reply in the **exact same language** the user writes in.
  - Kurdish Sorani → reply in Kurdish Sorani (natural, friendly dialect)
  - Arabic → reply in Arabic
  - English → reply in English
- Never mix languages in one reply.

---

## 📦 PRODUCT REPLY RULES

1. **Always use real data only** — never invent specs, prices, or product names.
2. **Show image** when `image_url` is available:
   `![Product Name](image_url)`
3. **Add a clickable details link** using `details_url`: e.g. `[بینینی زانیاریی زیاتر](${details_url})`
4. **Price** always in USD with $ symbol: e.g. `$1,299.00`
5. **For each product**, include:
   - 📷 Name & Model (bold)
   - 💰 Price
   - ✅ 2–4 key features from description
   - 🖼️ Image (if available)


---

## 📊 COMPARISON RULES
When user uses: vs / بەراورد / compare / مقارنة

- Create a **Markdown table** with these columns:
  | تایبەتمەندی | [Product A] | [Product B] |
  |---|---|---|
- Include rows: Price, Megapixels, Video, Autofocus, Best for
- After the table, add a **Recommendation** paragraph: which is better for video, photo, beginners, or professionals.
- Show both product images.

---

## 💡 SMART RECOMMENDATION RULES
- If user mentions **budget**: recommend products within that range, sorted by value.
- If user mentions **use case** (video, photography, vlog, studio, wildlife): recommend the best fit.
- If user is **unclear**: ask ONE clarifying question:
  > "بۆ چ مەبەستێک دەتەوێ؟ ویدیۆ، وێنەگرتن، یان هەردووکیان؟" (translated to their language)
- **Upsell naturally**: if recommending a camera body, suggest a relevant lens or accessory.

---

## ❌ NEVER DO THIS
- Never repeat the welcome greeting mid-conversation.
- Never say "I don't have database access" — you have full product data above.
- Never invent products not in the data.
- Never show raw image URLs as plain text — always use `![name](url)` syntax.
- Never show raw Markdown as escaped text — render it properly.
- Never leave prices blank — if price is 0 or null, say "تکایە پەیوەندی بکە بۆ نرخ" (contact for price).

---

## ✅ TONE & STYLE
- Friendly, confident, helpful — like a real camera shop expert.
- Use emojis sparingly for clarity (📷 💰 ✅ 🛒).
- Keep replies **focused and scannable** — use bullet points and headers.
- Never be overly long — give the user what they need, then offer to go deeper.
PROMPT;

// ٤. Gemini API
$apiKey   = 'AIzaSyBEyoWmXnqWe9fCCZt2oqxtodQ9d88Lk-U';
$model    = 'gemini-3.1-flash-lite-preview';
$apiUrl   = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

// چاتەکە بۆ Gemini format بکە
$geminiContents = [];

// system instructions وەک یەکەم نامەی user
$geminiContents[] = [
    'role'  => 'user',
    'parts' => [['text' => "SYSTEM INSTRUCTIONS (follow strictly):\n\n" . $systemInstructions]]
];
$geminiContents[] = [
    'role'  => 'model',
    'parts' => [['text' => 'Understood. I am Adnan Shop AI Expert. I will follow all instructions strictly and answer based on the product data provided.']]
];

// مێژووی چات
foreach ($messages as $msg) {
    $role = ($msg['role'] === 'assistant') ? 'model' : 'user';
    $geminiContents[] = [
        'role'  => $role,
        'parts' => [['text' => $msg['content']]]
    ];
}

$payload = [
    'contents'         => $geminiContents,
    'generationConfig' => [
        'temperature'     => 0.65,
        'maxOutputTokens' => 1200,
        'topP'            => 0.9,
    ]
];

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT        => 25,
]);

$response   = curl_exec($ch);
$httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError  = curl_error($ch);
curl_close($ch);

if ($httpStatus === 200) {
    $result = json_decode($response, true);
    $reply  = $result['candidates'][0]['content']['parts'][0]['text']
              ?? 'ببوورە، وەڵامەکە بەردەست نییە. تکایە دووبارە هەوڵ بدەرەوە.';
    echo json_encode(['success' => true, 'reply' => $reply], JSON_UNESCAPED_UNICODE);
} else {
    $errMsg = $curlError ?: "HTTP $httpStatus";
    echo json_encode([
        'success' => false,
        'reply'   => "ببوورە، کێشەیەک هەیە لە پەیوەندی بە سیستەمەکەوە. ($errMsg)"
    ], JSON_UNESCAPED_UNICODE);
}

{/* 

  <?php
declare(strict_types=1);

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents('php://input');
$body = json_decode($raw, true) ?? [];
$messages = $body['messages'] ?? [];
$sessionStart = !empty($body['session_start']);

// ١. بەخێرهاتن لە سەرەتای چات
if ($sessionStart || empty($messages)) {
    echo json_encode([
        'success' => true,
        'reply' => 'سڵاو! بەخێربێیت بۆ Adnan Shop 📷 من AI ئامێرەکەتم — پسپۆڕی کامێرا و ئامێرەکان. چۆن دەتوانم یارمەتیت بدەم؟'
    ]);
    exit;
}

// ٢. دۆزینەوەی دوایین نامەی یوزەر
$lastUserQuery = "";
foreach (array_reverse($messages) as $m) {
    if (($m['role'] ?? '') === 'user') {
        $lastUserQuery = trim($m['content']);
        break;
    }
}

$productsCtx = [];
$allProducts  = [];
$brandSummary = [];

try {
    $pdo = db();
    if ($pdo) {

        $baseSql = "
            SELECT p.id, p.slug, p.name, p.brand, p.model, p.price, p.description,
                   c.name  AS category,
                   s.name  AS subcategory,
                   pi.image_url
            FROM products p
            LEFT JOIN subcategories s  ON p.subcategory_id = s.id
            LEFT JOIN categories    c  ON s.category_id    = c.id
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE p.is_active = 1
        ";

        // ---- هەموو بەرهەمەکان بۆ brand summary ----
        $allStmt    = $pdo->query($baseSql . " ORDER BY p.brand, p.name");
        $allProducts = $allStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($allProducts as $p) {
            $b = $p['brand'] ?? 'Unknown';
            $brandSummary[$b] = ($brandSummary[$b] ?? 0) + 1;
        }
        ksort($brandSummary);

        // ---- بەرهەمە پەیوەندیدارەکان بۆ query ----
        $isCompare = preg_match('/(vs\.?|versus|بەراورد|compare|مقارنة)/ui', $lastUserQuery);

        if ($isCompare) {
            $parts = preg_split('/(vs\.?|versus|بەراورد|compare|مقارنة)/ui', $lastUserQuery);
            $q1 = '%' . trim($parts[0] ?? '') . '%';
            $q2 = '%' . trim($parts[1] ?? '') . '%';
            $stmt = $pdo->prepare($baseSql . "
                AND (
                    (p.name LIKE ? OR p.brand LIKE ? OR p.model LIKE ?)
                    OR
                    (p.name LIKE ? OR p.brand LIKE ? OR p.model LIKE ?)
                )
                LIMIT 2
            ");
            $stmt->execute([$q1,$q1,$q1, $q2,$q2,$q2]);
            $productsCtx = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } else {
            $q = "%$lastUserQuery%";
            $stmt = $pdo->prepare($baseSql . "
                AND (
                    p.name        LIKE ? OR
                    p.brand       LIKE ? OR
                    p.model       LIKE ? OR
                    p.description LIKE ? OR
                    c.name        LIKE ? OR
                    s.name        LIKE ?
                )
                ORDER BY
                    CASE WHEN p.brand LIKE ? THEN 0
                         WHEN p.name  LIKE ? THEN 1
                         ELSE 2 END,
                    p.price ASC
            ");
            $stmt->execute([$q,$q,$q,$q,$q,$q, $q,$q]);
            $productsCtx = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // ئەگەر هیچ نەدۆزرایەوە → هەموو بەرهەمەکان بدە بۆ AI
       if (empty($productsCtx)) {
    $productsCtx = array_slice($allProducts, 0, 5); // تەنها 5 بەرهەم
}
    }
} catch (Throwable $e) {
    $productsCtx = [];
    $allProducts  = [];
}

// ---- خلاصەی brand ----
$totalCount = array_sum($brandSummary);
$brandText  = implode(', ', array_map(
    fn($b, $c) => "$b ($c بەرهەم)",
    array_keys($brandSummary),
    $brandSummary
));

// ---- وێنەی URL چاككردن ----
$imageBase = 'https://adnanshops.com';
foreach ($productsCtx as &$p) {
    if (!empty($p['image_url']) && str_starts_with($p['image_url'], '/')) {
        $p['image_url'] = $imageBase . $p['image_url'];
    }
    $slug = $p['slug'] ?? strtolower(preg_replace('/\s+/', '-', $p['name'] ?? ''));
    $p['details_url'] = "https://adnanshops.com/details/" . rawurlencode($slug);
}
unset($p);

// ٣. System Prompt
$productJson = json_encode($productsCtx, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

$systemInstructions = <<<PROMPT
You are **"Adnan Shop AI Expert"** — a professional, friendly, and knowledgeable camera & equipment advisor for **Adnan Shop**, a camera store in Sulaymaniyah, Iraq.

---

## 🗄️ YOUR FULL PRODUCT KNOWLEDGE

The store has **{$totalCount} active products** from these brands:
{$brandText}

### Products matching the current user query (use these for your answer):
{$productJson}

> If a user asks about a brand or category not in the matched products above, you still know the store carries products from: {$brandText}. Tell them what's available and guide them to explore.

---

## 🌐 LANGUAGE RULES
- Always reply in the **exact same language** the user writes in.
  - Kurdish Sorani → reply in Kurdish Sorani (natural, friendly dialect)
  - Arabic → reply in Arabic
  - English → reply in English
- Never mix languages in one reply.

---

## 📦 PRODUCT REPLY RULES

1. **Always use real data only** — never invent specs, prices, or product names.
2. **Show image** when `image_url` is available:
   `![Product Name](image_url)`
3. **Add a clickable details link** using `details_url`: e.g. `[بینینی زانیاریی زیاتر](${details_url})`
4. **Price** always in USD with $ symbol: e.g. `$1,299.00`
5. **For each product**, include:
   - 📷 Name & Model (bold)
   - 💰 Price
   - ✅ 2–4 key features from description
   - 🖼️ Image (if available)


---

## 📊 COMPARISON RULES
When user uses: vs / بەراورد / compare / مقارنة

- Create a **Markdown table** with these columns:
  | تایبەتمەندی | [Product A] | [Product B] |
  |---|---|---|
- Include rows: Price, Megapixels, Video, Autofocus, Best for
- After the table, add a **Recommendation** paragraph: which is better for video, photo, beginners, or professionals.
- Show both product images.

---

## 💡 SMART RECOMMENDATION RULES
- If user mentions **budget**: recommend products within that range, sorted by value.
- If user mentions **use case** (video, photography, vlog, studio, wildlife): recommend the best fit.
- If user is **unclear**: ask ONE clarifying question:
  > "بۆ چ مەبەستێک دەتەوێ؟ ویدیۆ، وێنەگرتن، یان هەردووکیان؟" (translated to their language)
- **Upsell naturally**: if recommending a camera body, suggest a relevant lens or accessory.

---

## ❌ NEVER DO THIS
- Never repeat the welcome greeting mid-conversation.
- Never say "I don't have database access" — you have full product data above.
- Never invent products not in the data.
- Never show raw image URLs as plain text — always use `![name](url)` syntax.
- Never show raw Markdown as escaped text — render it properly.
- Never leave prices blank — if price is 0 or null, say "تکایە پەیوەندی بکە بۆ نرخ" (contact for price).

---

## ✅ TONE & STYLE
- Friendly, confident, helpful — like a real camera shop expert.
- Use emojis sparingly for clarity (📷 💰 ✅ 🛒).
- Keep replies **focused and scannable** — use bullet points and headers.
- Never be overly long — give the user what they need, then offer to go deeper.
PROMPT;

// ٤. Gemini API
$apiKey   = 'AIzaSyBEyoWmXnqWe9fCCZt2oqxtodQ9d88Lk-U';
$model    = 'gemini-2.5-flash';
$apiUrl   = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

// چاتەکە بۆ Gemini format بکە
$geminiContents = [];

// system instructions وەک یەکەم نامەی user
$geminiContents[] = [
    'role'  => 'user',
    'parts' => [['text' => "SYSTEM INSTRUCTIONS (follow strictly):\n\n" . $systemInstructions]]
];
$geminiContents[] = [
    'role'  => 'model',
    'parts' => [['text' => 'Understood. I am Adnan Shop AI Expert. I will follow all instructions strictly and answer based on the product data provided.']]
];

// مێژووی چات
foreach ($messages as $msg) {
    $role = ($msg['role'] === 'assistant') ? 'model' : 'user';
    $geminiContents[] = [
        'role'  => $role,
        'parts' => [['text' => $msg['content']]]
    ];
}

$payload = [
    'contents'         => $geminiContents,
    'generationConfig' => [
        'temperature'     => 0.65,
        'maxOutputTokens' => 500,
        'topP'            => 0.9,
    ]
];

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT        => 25,
]);

$response   = curl_exec($ch);
$httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError  = curl_error($ch);
curl_close($ch);

if ($httpStatus === 200) {
    $result = json_decode($response, true);
    $reply  = $result['candidates'][0]['content']['parts'][0]['text']
              ?? 'ببوورە، وەڵامەکە بەردەست نییە. تکایە دووبارە هەوڵ بدەرەوە.';
    echo json_encode(['success' => true, 'reply' => $reply], JSON_UNESCAPED_UNICODE);
} else {
    $errMsg = $curlError ?: "HTTP $httpStatus";
    echo json_encode([
        'success' => false,
        'reply'   => "ببوورە، کێشەیەک هەیە لە پەیوەندی بە سیستەمەکەوە. ($errMsg)"
    ], JSON_UNESCAPED_UNICODE);
}



*/}