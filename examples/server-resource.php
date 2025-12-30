<?php
require_once __DIR__ . '/../vendor/autoload.php';

//セッションの開始
$session = cti_get_session('ctip://localhost:8099/',
	array('user' => 'user',
	'password' => 'kappa'));
	
//ファイル出力
$dir = 'out';
@mkdir($dir, 0777, true);
$session->set_output_as_file('out/server-resource.pdf');

//リソースのアクセス許可
$session->property('input.include', 'http://copper-pdf.com/**');
	
//文書の送信
$session->transcode('http://copper-pdf.com/');

//セッションの終了
$session->close();
?>