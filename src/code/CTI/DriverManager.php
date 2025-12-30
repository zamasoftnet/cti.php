<?php
/**
 * ドキュメント変換サーバーに接続するドライバの窓口関数です。
 * 
 * @package CTI
 * @example ../../test/output-stdout.php 結果を標準出力に出力する。
 * @example ../../test/output-file.php 結果をファイルに出力する。
 * @example ../../test/output-resource.php 結果をリソースに出力する。
 * @example ../../test/output-dir.php 結果を複数の画像としてディレクトリに出力する。
 * @example ../../test/output-var.php 結果を変数に出力する。
 * @example ../../test/message-func.php エラー等のメッセージを表示する。
 * @example ../../test/progress-func.php 処理の進行状況を表示する。
 * @example ../../test/reset.php 同じセッションで何度も処理をする。
 * @example ../../test/resolver.php サーバーからの要求に応じてリソースを送る。
 * @example ../../test/output-var.php 結果を変数に出力する。
 * @example ../../test/server-info.php サーバーの情報を取得する。
 * @example ../../test/server-resource.php サーバーから文書にアクセスして変換する。
 */
require_once ('Driver.class.php');

/**
 * 指定されたURIに接続するためのドライバを返します。
 * 
 * @param $uri 接続先アドレス。
 * @return Driver
 */
function cti_get_driver ($uri) {
  return new Driver();
}

/**
 * 指定されたURIに接続し、セッションを返します。
 * 
 * @param $uri 接続先アドレス。
 * @param $options 接続オプション。
 * @return Session
 */
function cti_get_session($uri, $options = null) {
	$driver = cti_get_driver($uri);
	return $driver->get_session($uri, $options);
}

?>