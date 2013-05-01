<?php

class S360 {
  
  static $use_ssl = false;
  static $debug   = false;
  static $last_error = '';
  
  private static function _post($url) {
    // create curl resource 
    $ch = curl_init(); 
    
    // set url 
    curl_setopt($ch, CURLOPT_URL, $url); 
    
    //return the transfer as a string 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    
    // $output contains the output string 
    $output = curl_exec($ch); 

    if (S360::$debug) {
      echo "/// URL /////////////////////////////////////////////////////////////////////////////////////////////////////<br/>\n";
      echo $url . "<br/>\n";
      echo "/// DATA ////////////////////////////////////////////////////////////////////////////////////////////////////<br/>\n";
      print_r($output);
      echo "<br/>\n<br/>\n";
    }
    
    if ($output == false) {
      S360::$last_error = curl_error($ch);
    }
    
    // close curl resource to free up system resources 
    curl_close($ch);
    
    return $output;
  }
  
  private static function _do_post($url) {
    if (!function_exists('json_decode')) {
      return array('errorMessage' => 'JSON is not installed', 'errorCode' => '999');
    }
    $output = S360::_post($url);
    if ($output) {
      $output = json_decode($output, true);
    } else {
      $output = S360::$last_error;
    }
    
    return $output;
  }
  
  public static function create_token($username) {
    $salt = "beachfr0nt" . $username;
    return sha1($salt);
  }
  
  private static function _getHost() {
    $host = '360.sorensonmedia.com';
    $filename = '/var/tmp/360host';
    if (file_exists($filename)) {
      $data = file($filename);
      if ($data && $data[0]) {
        $env = trim($data[0]);
        $response = trim(S360::_post('http://www.sorensonmedia.com/internal/apis/360Env.php?env=' . $env));
        
        if ($response != 'invalid option specified') {
          $host = $response;
        }
      }
    }
    return $host;
  }
  
  private static function _getProtocol() {
    if (S360::$use_ssl) {
      $protocol = 'https://';
    } else {
      $protocol = 'http://';
    }
    return $protocol;
  }
  
  public static function do_post($url) {
    return S360::_do_post(S360::_getProtocol() . S360::_getHost() . $url);
  }

}

require_once('s360_account.php');
require_once('s360_asset.php');
require_once('s360_rate_plan.php');
require_once('s360_format_constraint.php')

?>