<?php
declare(strict_types=1);
require __DIR__.'/db.php';require __DIR__.'/helpers.php';
$admin=isset($_GET['admin']);if($admin)admin_required();
$category=trim((string)($_GET['category']??''));$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
if($id){$sql='SELECT id,name,category,description,price,image,sizes,stock,is_active FROM products WHERE id=?'.($admin?'':' AND is_active=1');$s=$pdo->prepare($sql);$s->execute([$id]);$p=$s->fetch();json_out(['product'=>$p?:null]);}
$sql='SELECT id,name,category,description,price,image,sizes,stock,is_active FROM products'.($admin?'':' WHERE is_active=1');$args=[];if($category!==''){$sql.=($admin?' WHERE ':' AND ').'category=?';$args[]=$category;}$sql.=' ORDER BY created_at DESC,id DESC';$s=$pdo->prepare($sql);$s->execute($args);$products=$s->fetchAll();$categories=$pdo->query('SELECT DISTINCT category FROM products WHERE is_active=1 AND category<>\'\' ORDER BY category')->fetchAll(PDO::FETCH_COLUMN);json_out(['products'=>$products,'categories'=>$categories]);
