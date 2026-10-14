<?php

declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['error' => 'Method not allowed.'], 405);
$items = input_json();
if (!is_array($items) || count($items) > 50) json_out(['error' => 'Invalid cart.'], 400);
$ids = [];
foreach ($items as $i) {
    $id = filter_var($i['id'] ?? null, FILTER_VALIDATE_INT);
    if ($id) $ids[] = $id;
}
if (!$ids) json_out(['items' => []]);
$ids = array_values(array_unique($ids));
$marks = implode(',', array_fill(0, count($ids), '?'));
$s = $pdo->prepare("SELECT id,name,category,price,image,sizes,stock FROM products WHERE is_active=1 AND id IN ($marks)");
$s->execute($ids);
$products = [];
foreach ($s as $p) $products[(int)$p['id']] = $p;
$out = [];
foreach ($items as $i) {
    $id = (int)($i['id'] ?? 0);
    $p = $products[$id] ?? null;
    $size = trim((string)($i['size'] ?? ''));
    $qty = max(0, (int)($i['qty'] ?? 0));
    $stock = $p ? max(0, (int)$p['stock'] - active_reserved($pdo, $id)) : 0;
    $sizes = $p ? array_values(array_filter(array_map('trim', explode(',', $p['sizes'])))) : [];
    $valid = $p && $qty > 0 && $qty <= $stock && (!$sizes || in_array($size, $sizes, true));
    $out[] = ['id' => $id, 'name' => $p['name'] ?? null, 'category' => $p['category'] ?? null, 'price' => $p['price'] ?? null, 'image' => $p['image'] ?? null, 'stock' => $stock, 'size' => $size, 'qty' => $valid ? $qty : min($qty, $stock), 'valid' => (bool)$valid];
}
json_out(['items' => $out]);
