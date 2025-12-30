<?php
require_once ('../code/CTI/DriverManager.php');

//セッションの開始
$session = cti_get_session('ctip://localhost:8099/',
	array('user' => 'user',
	'password' => 'kappa'));
	
//ファイル出力
$dir = 'out';
@mkdir($dir, 0777, true);
$session->set_output_as_file('out/output-file.pdf');

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