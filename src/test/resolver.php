<?php
require_once ('../code/CTI/DriverManager.php');

//セッションの開始
$session = cti_get_session('ctip://localhost:8099/',
	array('user' => 'user',
	'password' => 'kappa'));
	
//ファイル出力
@mkdir($dir, 0777, 'out');
$session->set_output_as_file('out/resolver.pdf');

//リソースの送信
function resolver($uri, $r) {
  if (file_exists($uri)) {
  	$r->start();
  	readfile($uri);
  	$r->end();
  }
}
$session->set_resolver_func('resolver');
	
//文書の送信
$session->start_main('data/test.html');
readfile("data/test.html");
$session->end_main();

//セッションの終了
$session->close();
?>