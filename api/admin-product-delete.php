<?php
declare(strict_types=1);
require __DIR__.'/db.php';require __DIR__.'/helpers.php';admin_required();
if($_SERVER['REQUEST_METHOD']!=='POST')json_out(['error'=>'Method not allowed.'],405);csrf_check();$id=filter_var(input_json()['id']??null,FILTER_VALIDATE_INT);if(!$id)json_out(['error'=>'Invalid product.'],422);$s=$pdo->prepare('SELECT image FROM products WHERE id=?');$s->execute([$id]);$p=$s->fetch();if(!$p)json_out(['error'=>'Product not found.'],404);$pdo->prepare('DELETE FROM products WHERE id=?')->execute([$id]);$path=safe_upload_path($p['image']);if($path&&is_file($path))@unlink($path);json_out(['ok'=>true]);
