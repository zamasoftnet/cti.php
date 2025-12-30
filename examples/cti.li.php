<?php
require_once ('../code/CTI/DriverManager.php');

//セッションの開始
$session = cti_get_session('ctip://free.cssj.jp:8499/',
	array('user' => 'user',
	'password' => 'kappa',
	'transcoder' => 'li.cti.pdftools.PDFTools'));
	
//ファイル出力
$dir = 'out';
@mkdir($dir, 0777, true);
$session->set_output_as_file('out/cti.li.pdf');

$session->property('output.impose', '4up');
$session->property('output.paper-width', '297mm');
$session->property('output.paper-height', '420mm');
$session->property('output.cutting-margin', '3mm');
$session->property('output.title', 'test');

//文書の送信
$session->start_main();
readfile('data/test.pdf');
$session->end_main();

//セッションの終了
$session->close();
?>