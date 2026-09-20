<?php
namespace CTI;

/**
 * CTIドライバです。
 */
class Driver {
  /**
   * 指定されたURIに接続し、セッションを返します。
   *
   * @param string $uri 接続先URI。
   * @param array|null $options 接続オプション。'user'、'password' のほか、
   *   試験用に 'insecure' => true で証明書の検証を省きます(2.2.1 以降)。
   * @return Session
   */
  public function get_session($uri, $options = null) {
    $host = 'localhost';
    $port = 8099;
    $tls = false;
    if (preg_match_all('/^ctips\:\/\/([^\:\/]+)\:([0-9]+)\/?$/', $uri, $out)) {
      $tls = true;
      $host = $out[1][0];
      $port = $out[2][0];
    }
    else if (preg_match_all('/^ctips:\/\/([^:\/]+)\/?$/', $uri, $out)) {
      $tls = true;
      $host = $out[1][0];
    }
    else if (preg_match_all('/^ctip\:\/\/([^\:\/]+)\:([0-9]+)\/?$/', $uri, $out)) {
      $host = $out[1][0];
      $port = $out[2][0];
    }
    else if (preg_match_all('/^ctip:\/\/([^:\/]+)\/?$/', $uri, $out)) {
      $host = $out[1][0];
    }
    $address = ($tls ? 'tls://' : 'tcp://').$host.':'.$port;
    $ssl = array();
    if ($tls && $options !== null && !empty($options['insecure'])) {
      // 試験用: 証明書の検証とホスト名の照合を省く
      $ssl['verify_peer'] = false;
      $ssl['verify_peer_name'] = false;
    }
    $context = stream_context_create(array('ssl' => $ssl));
    $fp = stream_socket_client($address, $errno, $errmsg, (float)ini_get('default_socket_timeout'),
        STREAM_CLIENT_CONNECT, $context);
    if ($fp === false) {
      throw new \Exception(__FUNCTION__.": stream_socket_client() failed: $errno / $errmsg");
    }
    return new Session($fp, $options);
  }
}
