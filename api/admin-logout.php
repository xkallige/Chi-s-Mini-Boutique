<?php
declare(strict_types=1);
require __DIR__.'/db.php';require __DIR__.'/helpers.php';
if($_SERVER['REQUEST_METHOD']!=='POST')json_out(['error'=>'Method not allowed.'],405);admin_required();csrf_check();$_SESSION=[];if(ini_get('session.use_cookies')){$p=session_get_cookie_params();setcookie(session_name(),'',time()-42000,$p['path'],$p['domain'],$p['secure'],$p['httponly']);}session_destroy();json_out(['ok'=>true]);
