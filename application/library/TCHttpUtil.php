<?php
/**
 * @name TCApiControllerBase
 * @author liujianchun
 */
class TCHttpUtil {
  const DEFAULT_HEADERS = [
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
    'Accept-Charset: GB2312,utf-8;q=0.7,*;q=0.7',
    'Accept-Encoding: gzip,deflate',
    'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3381.0 Safari/537.36',
  ];

  public static function getHost($url) {
    $url = parse_url($url);

    return $url['host'];
  }

  public static function curlGet($url, $headers = array(), &$response_headers = null, $follow = true, $timeout = 5) {
    TCPerformanceTracer::start("http");
    if(!$headers) $headers = self::DEFAULT_HEADERS;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_ENCODING, '');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $follow ? 1 : 0);

    //解决curl长时间调用会造成的内存的泄露
    curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    self::setRequestProxy($ch, $url);

    $r = curl_exec($ch);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header_str = trim(substr($r, 0, $header_size));
    $response_headers = [];
    foreach(explode("\n", $header_str) as $line) {
      if(preg_match('/^([^:]+):(.*)$/', $line, $matches)) {
        $response_headers[$matches[1]] = trim($matches[2]);
      }
    }
    curl_close($ch);
    TCPerformanceTracer::end(__METHOD__, __FILE__, __LINE__);

    return substr($r, $header_size);
  }

  private static function setRequestProxy($ch, $url) {
    if(PHP_OS === 'Darwin' || PHP_OS === 'WINNT') {
      $use_proxy = false;
      $host = self::getHost($url);
      if(strpos($host, 'admob.com') !== false) $use_proxy = true;
      if(strpos($host, 'googleapis.com') !== false) $use_proxy = true;
      if($use_proxy) {
        // 需要翻墙，使用本地 shadowsocks 代理
        curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 0);
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5_HOSTNAME);
        curl_setopt($ch, CURLOPT_PROXY, "127.0.0.1");
        curl_setopt($ch, CURLOPT_PROXYPORT, "1080");
      }
    }
  }

  public static function curlPost($url, $data = array(), $headers = array(), &$response_headers = null, $follow = true, $timeout = 5) {
    TCPerformanceTracer::start('http');
    $data = (is_array($data)) ? http_build_query($data) : $data;
    if(!$headers) $headers = self::DEFAULT_HEADERS;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_ENCODING, '');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $follow ? 1 : 0);

    //解决curl长时间调用会造成的内存的泄露
    curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    self::setRequestProxy($ch, $url);

    $r = curl_exec($ch);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header_str = trim(substr($r, 0, $header_size));
    $response_headers = [];
    foreach(explode("\n", $header_str) as $line) {
      if(preg_match('/^([^:]+):(.*)$/', $line, $matches)) {
        $response_headers[$matches[1]] = trim($matches[2]);
      }
    }
    curl_close($ch);
    TCPerformanceTracer::end(__METHOD__, __FILE__, __LINE__);

    return substr($r, $header_size);
  }
}
