<?php
declare(strict_types=1);
require __DIR__.'/db.php';require __DIR__.'/helpers.php';admin_required();
if($_SERVER['REQUEST_METHOD']!=='POST')json_out(['error'=>'Method not allowed.'],405);csrf_check();$d=input_json();$id=filter_var($d['id']??null,FILTER_VALIDATE_INT);$status=(string)($d['status']??'');if(!$id||!in_array($status,['Shipped','Paid / Pending Delivery'],true))json_out(['error'=>'Invalid order status.'],422);$from=$status==='Shipped'?'Paid / Pending Delivery':'Shipped';$s=$pdo->prepare('UPDATE orders SET status=? WHERE id=? AND status=?');$s->execute([$status,$id,$from]);if(!$s->rowCount())json_out(['error'=>'Order status was not changed.'],409);json_out(['ok'=>true]);
