<?php
declare(strict_types=1);
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';

$gateways=[];
if($config['paystack_public_key']!==''&&$config['paystack_secret_key']!==''){
    $gateways[]=['id'=>'paystack','label'=>'Pay by card via Paystack'];
}
if($config['flutterwave_public_key']!==''&&$config['flutterwave_secret_key']!==''&&$config['flutterwave_webhook_hash']!==''){
    $gateways[]=['id'=>'flutterwave','label'=>'Pay by card via Flutterwave'];
}
$default=in_array($config['gateway'],array_column($gateways,'id'),true)?$config['gateway']:($gateways[0]['id']??'');
json_out(['gateways'=>$gateways,'default'=>$default]);