<?php
require_once __DIR__ . '/../vendor/autoload.php';

//セッションの開始
$session = cti_get_session('ctip://localhost:8099/',
	array('user' => 'user',
	'password' => 'kappa'));
	
//ファイル出力
$dir = 'out';
@mkdir($dir, 0777, true);
$session->set_output_as_file('out/output-file.pdf');

// 高さの設定
$session->property("output.page-height", "100mm");

// ページ数制限
$session->property("output.page-limit", "3");

// 途中まで出力する
$session->property("output.page-limit.abort", "normal");

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
?>