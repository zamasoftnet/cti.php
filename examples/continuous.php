<?php
require_once ('../code/CTI/DriverManager.php');

//セッションの開始
$session = cti_get_session('ctip://localhost:8099/',
	array('user' => 'user',
	'password' => 'kappa'));

//リソースの送信
$session->start_resource('file:/test.css');
readfile('data/test.css');
$session->end_resource();

$session->set_continuous(TRUE);
	
//文書の送信
$session->start_main('.');
readfile("data/test.html");
$session->end_main();
	
//文書の送信
$session->start_main('.');
readfile("data/test.html");
$session->end_main();

$session->join();

//セッションの終了
$session->close();
?>