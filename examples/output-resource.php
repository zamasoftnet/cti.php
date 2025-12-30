<?php
require_once __DIR__ . '/../vendor/autoload.php';

//セッションの開始
$session = cti_get_session('ctip://localhost:8099/',
	array('user' => 'user',
	'password' => 'kappa'));
	
//ファイル出力
@mkdir($dir, 0777, 'out');
$fp = fopen('out/output-resource.pdf', 'w');
$session->set_output_as_resource($fp);

//リソースの送信
$session->start_resource('file:/test.css');
readfile('data/test.css');
$session->end_resource();
	
//文書の送信
$session->start_main('file:/ob.html');
readfile("data/test.html");
$session->end_main();

//セッションの終了
$session->close();

fclose($fp);
?>