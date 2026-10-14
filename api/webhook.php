<?php
declare(strict_types=1);
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';

$raw=file_get_contents('php://input');
$event=json_decode($raw,true);
if(!is_array($event))json_out(['error'=>'Invalid webhook payload.'],400);

$paystackReference=(string)($event['data']['reference']??'');
$flutterwaveReference=(string)($event['data']['tx_ref']??$event['data']['reference']??'');
$paystackSignature=$_SERVER['HTTP_X_PAYSTACK_SIGNATURE']??'';
$flutterwaveSignature=$_SERVER['HTTP_VERIF_HASH']??'';

$reference='';
$gateway='';
$order=null;
if($paystackReference!==''&&$paystackSignature!==''){
    $s=$pdo->prepare('SELECT * FROM orders WHERE reference=?');
    $s->execute([$paystackReference]);
    $candidate=$s->fetch();
    if($candidate&&$candidate['gateway']==='paystack'){
        $expected=hash_hmac('sha512',$raw,$config['paystack_secret_key']);
        if(hash_equals($expected,$paystackSignature)){$order=$candidate;$reference=$paystackReference;$gateway='paystack';}
    }
}
if(!$order&&$flutterwaveReference!==''&&$flutterwaveSignature!==''&&$config['flutterwave_webhook_hash']!==''){
    $s=$pdo->prepare('SELECT * FROM orders WHERE reference=?');
    $s->execute([$flutterwaveReference]);
    $candidate=$s->fetch();
    if($candidate&&$candidate['gateway']==='flutterwave'&&hash_equals($config['flutterwave_webhook_hash'],$flutterwaveSignature)){
        $order=$candidate;$reference=$flutterwaveReference;$gateway='flutterwave';
    }
}
if(!$order)json_out(['error'=>'Invalid webhook signature or unknown order.'],401);

$eventName=(string)($event['event']??'');
if(($gateway==='paystack'&&$eventName!=='charge.success')||($gateway==='flutterwave'&&$eventName!=='charge.completed'))json_out(['received'=>true]);

try{
    $providerConfig=$config;
    $providerConfig['gateway']=$gateway;
    $payment=verify_gateway($reference,$providerConfig);
    if(!$payment['ok']||abs($payment['amount']-(float)$order['total'])>0.01)json_out(['received'=>true]);
    $changed=fulfill_order($pdo,$order,$payment['amount']);
    if($changed){
        $s=$pdo->prepare('SELECT * FROM orders WHERE id=?');
        $s->execute([$order['id']]);
        send_mail($pdo,$s->fetch(),$config);
    }
    json_out(['received'=>true]);
}catch(Throwable $e){
    error_log('CMB '.$gateway.' webhook '.$reference.': '.$e->getMessage());
    json_out(['error'=>'Webhook processing failed.'],500);
}