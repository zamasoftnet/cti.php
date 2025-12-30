<?php
require_once ('../code/CTI/DriverManager.php');

//セッションの開始
$session = cti_get_session('ctip://localhost:8099/',
	array('user' => 'user',
	'password' => 'kappa'));
	
//ファイル出力
@mkdir($dir, 0777, 'out');
$session->set_output_as_file('out/reset-1.pdf');

//リソースの送信
$session->start_resource('test.css');
readfile('data/test.css');
$session->end_resource();
	
//文書の送信
$session->start_main('test.html');
readfile("data/test.html");
$session->end_main();

//事前に送って変換
$session->set_output_as_file('out/reset-2.pdf');
$session->start_resource('test.html');
readfile("data/test.html");
$session->end_resource();
$session->transcode('test.html');

//同じ文書を変換
$session->set_output_as_file('out/reset-3.pdf');
$session->transcode('test.html');

//リセットして変換
$session->reset();
$session->set_output_as_file('out/reset-4.pdf');
$session->transcode('test.html');

//再度変換
$session->set_output_as_file('out/reset-5.pdf');
$session->start_main('test.html');
readfile("data/test.html");
$session->end_main();

//セッションの終了
$session->close();
?>