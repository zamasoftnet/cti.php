<?php
require_once ('../code/CTI/DriverManager.php');

//セッションの開始
$session = cti_get_session('ctip://localhost:8099/',
	array('user' => 'user',
	'password' => 'kappa'));
	
//変数出力
$session->set_output_as_variable($var);

//リソースの送信
$session->start_resource('file:/test.css');
readfile('data/test.css');
$session->end_resource();
	
//文書の送信
$session->start_main('file:/test.html');
readfile("data/test.html");
$session->end_main();

//セッションの終了
$session->close();

//ファイル出力
@mkdir($dir, 0777, 'out');
$fp = fopen('out/output-var.pdf', 'w');
fwrite($fp, $var);
fclose($fp);
?>