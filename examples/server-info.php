<?php
require_once __DIR__ . '/../vendor/autoload.php';

//セッションの開始
$session = cti_get_session('ctip://localhost:8099/',
	array('user' => 'user',
	'password' => 'kappa'));

echo $session->get_server_info('http://www.cssj.jp/ns/ctip/version');

//セッションの終了
$session->close();
?>