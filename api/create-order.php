<?php

declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['error' => 'Method not allowed.'], 405);
$d = input_json();
$gateway = (string)($d['gateway'] ?? '');
if (!in_array($gateway, ['paystack', 'flutterwave'], true)) json_out(['error' => 'Choose a valid payment method.'], 422);
if ($gateway === 'paystack' && (!$config['paystack_public_key'] || !$config['paystack_secret_key'])) json_out(['error' => 'Paystack is not configured yet.'], 503);
if ($gateway === 'flutterwave' && (!$config['flutterwave_public_key'] || !$config['flutterwave_secret_key'] || !$config['flutterwave_webhook_hash'])) json_out(['error' => 'Flutterwave is not configured yet.'], 503);
$name = trim((string)($d['customer_name'] ?? ''));
$phone = trim((string)($d['phone'] ?? ''));
$email = filter_var(trim((string)($d['email'] ?? '')), FILTER_VALIDATE_EMAIL);
$address = trim((string)($d['address'] ?? ''));
$state = trim((string)($d['state'] ?? ''));
$items = $d['items'] ?? [];
if (mb_strlen($name) < 2 || !valid_phone($phone) || !$email || mb_strlen($address) < 8 || !in_array($state, valid_states(), true) || !is_array($items) || !count($items) || count($items) > 30) json_out(['error' => 'Check your name, Nigerian phone, email, address, state and bag.'], 422);
$lines = [];
$ids = [];
foreach ($items as $i) {
    $id = filter_var($i['id'] ?? null, FILTER_VALIDATE_INT);
    $qty = filter_var($i['qty'] ?? null, FILTER_VALIDATE_INT);
    $size = trim((string)($i['size'] ?? ''));
    if (!$id || !$qty || $qty > 20) json_out(['error' => 'The bag has an invalid item or quantity.'], 422);
    $lines[] = [$id, $size, $qty];
    $ids[] = $id;
}
if (count(array_unique(array_map(fn($x) => $x[0] . '|' . $x[1], $lines))) !== count($lines)) json_out(['error' => 'Remove duplicate size lines from your bag.'], 422);
$ids = array_values(array_unique($ids));
sort($ids);
$pdo->beginTransaction();
try {
    $marks = implode(',', array_fill(0, count($ids), '?'));
    $s = $pdo->prepare("SELECT * FROM products WHERE is_active=1 AND id IN ($marks) ORDER BY id FOR UPDATE");
    $s->execute($ids);
    $products = [];
    foreach ($s as $p) $products[(int)$p['id']] = $p;
    $problems = [];
    $requested = [];
    foreach ($lines as [$rid, $rs, $rq]) {
        $requested[$rid] = ($requested[$rid] ?? 0) + $rq;
    }
    foreach ($requested as $rid => $required) {
        $rp = $products[$rid] ?? null;
        if (!$rp) {
            $problems[] = 'A product is unavailable.';
            continue;
        }
        $available = max(0, (int)$rp['stock'] - active_reserved($pdo, (int)$rid));
        if ($required > $available) $problems[] = $rp['name'] . ' has fewer units available than requested.';
    }
    if ($problems) throw new DomainException(implode(' ', $problems));
    $snap = [];
    $subtotal = 0;
    $problems = [];
    foreach ($lines as [$id, $size, $qty]) {
        $p = $products[$id] ?? null;
        if (!$p) {
            $problems[] = 'A product is unavailable.';
            continue;
        }
        $sizes = array_values(array_filter(array_map('trim', explode(',', $p['sizes']))));
        $available = max(0, (int)$p['stock'] - active_reserved($pdo, $id));
        if ($qty > $available || ($sizes && !in_array($size, $sizes, true)) || (!$sizes && $size !== '')) {
            $problems[] = $p['name'] . ' is no longer available in that size or quantity.';
            continue;
        }
        $subtotal += (float)$p['price'] * $qty;
        $snap[] = [$p, $size, $qty];
    }
    if ($problems) throw new DomainException(implode(' ', $problems));
    $s = $pdo->prepare('SELECT fee FROM delivery_fees WHERE state=?');
    $s->execute([$state]);
    $fee = $s->fetchColumn();
    $fee = $fee === false ? (float)$config['default_delivery_fee'] : (float)$fee;
    $total = $subtotal + $fee;
    $reference = 'CMB-' . bin2hex(random_bytes(12));
    $s = $pdo->prepare("INSERT INTO orders(reference,customer_name,phone,email,address,state,subtotal,delivery_fee,total,gateway,status) VALUES(?,?,?,?,?,?,?,?,?,?, 'Pending Payment')");
    $s->execute([$reference, $name, $phone, $email, $address, $state, $subtotal, $fee, $total, $gateway]);
    $orderId = (int)$pdo->lastInsertId();
    $number = 1000 + $orderId;
    $pdo->prepare('UPDATE orders SET order_number=? WHERE id=?')->execute([$number, $orderId]);
    $s = $pdo->prepare('INSERT INTO order_items(order_id,product_id,product_name,size,qty,unit_price) VALUES(?,?,?,?,?,?)');
    foreach ($snap as [$p, $size, $qty]) $s->execute([$orderId, $p['id'], $p['name'], $size ?: '', $qty, $p['price']]);
    $pdo->commit();
} catch (DomainException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_out(['error' => $e->getMessage()], 409);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('CMB create order: ' . $e->getMessage());
    json_out(['error' => 'We could not create the order. Please try again.'], 500);
}
if ($gateway === 'paystack') {
    if (!$config['paystack_public_key'] || !$config['paystack_secret_key']) json_out(['error' => 'Paystack keys are not configured. Add test keys in api/config.php.'], 503);
    json_out(['reference' => $reference, 'amount' => $total, 'email' => $email, 'gateway' => 'paystack', 'public_key' => $config['paystack_public_key']]);
}
if (!$config['flutterwave_public_key'] || !$config['flutterwave_secret_key']) json_out(['error' => 'Flutterwave keys are not configured in api/config.php.'], 503);
json_out(['reference' => $reference, 'amount' => $total, 'email' => $email, 'gateway' => 'flutterwave', 'public_key' => $config['flutterwave_public_key']]);
