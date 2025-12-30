<?php
require_once ('../code/CTI/DriverManager.php');

//セッションの開始
$session = cti_get_session('ctip://localhost:8099/',
	array('user' => 'user',
	'password' => 'kappa'));
	
//出力しない
$session->set_results(new SingleResult(new NullBuilder()));

function progress($length, $read) {
  echo "$read / $length\n";
}
$session->set_progress_func('progress');

//リソースのアクセス許可
$session->property('input.include', 'http://www.w3.org/**');
	
//文書の送信
$session->transcode('http://www.w3.org/TR/xslt');

//セッションの終了
$session->close();
?>