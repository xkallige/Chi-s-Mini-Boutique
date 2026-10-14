<?php
declare(strict_types=1);
require __DIR__.'/db.php';require __DIR__.'/helpers.php';
json_out(['authenticated'=>!empty($_SESSION['admin']),'csrf'=>csrf_token()]);
