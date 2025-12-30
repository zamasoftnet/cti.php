<?php
require_once __DIR__ . '/../vendor/autoload.php';

//セッションの開始
$session = cti_get_session('ctip://localhost:8099/',
	array('user' => 'user',
	'password' => 'kappa'));
	
//出力しない
$session->set_results(new SingleResult(new NullBuilder()));

function message($code, $message, $args) {
  echo "$code $message ";
  var_dump($args);
}
$session->set_message_func('message');

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