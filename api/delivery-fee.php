<?php

declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';
$state = trim((string)($_GET['state'] ?? ''));
if ($state !== '' && !in_array($state, valid_states(), true)) json_out(['error' => 'Choose a valid Nigerian state.'], 422);
if ($state === '') json_out(['state' => null, 'fee' => (float)$config['default_delivery_fee']]);
$s = $pdo->prepare('SELECT fee FROM delivery_fees WHERE state=?');
$s->execute([$state]);
$fee = $s->fetchColumn();
json_out(['state' => $state, 'fee' => $fee === false ? (float)$config['default_delivery_fee'] : (float)$fee]);
