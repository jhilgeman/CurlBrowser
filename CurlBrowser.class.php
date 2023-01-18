<?php
/* CurlBrowser
 *
 * A class to simplify cURL calls. Emulates an MS Edge browser user agent and 
 * supports cookies for sites that utilize them. Also performs caching of GET
 * calls by default.
 *
 * @author: Jonathan Hilgeman
 * @version: 1.0
 * @license: MIT License
 * @updated: 2023-01-18
 */
class CurlBrowser
{
  public $userAgent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36 Edg/108.0.1462.76";
  public $cookiesFile = null;
  public $proxyURL = null;
  public $saveLastOutput = "tmp/lastresponse.html";
  public $cacheDir = "tmp/curlbrowsercache";
  public $httpHeaders = array();
  public $caPath = "https://curl.se/ca/cacert.pem";
  public $lastResponseWasCached = false;
  private $ch;
  
  public function __construct($UseCookies = true)
  {
    if(is_bool($UseCookies) && $UseCookies)
    {
      $this->cookiesFile = "tmp/cookies.txt";
    }
    elseif(is_string($UseCookies) && ($UseCookies != ""))
    {
      $this->cookiesFile = $UseCookies;
    }

    if($this->cacheDir != null)
    {
      if(!file_exists($this->cacheDir))
      {
        mkdir($this->cacheDir);
      }
    }
    
  }
  
  public function SetCustomHTTPHeaders($arrHeaders)
  {
    $this->httpHeaders = $arrHeaders;
  }
  
  public function SetProxy($proxy)
  {
    $this->proxyURL = $proxy;
  }

  public function Get($url, $cache = true)
  {
    if(file_exists("stop.txt")) { echo "Stopping!"; die(); }
    $this->lastResponseWasCached = false;
    if($cache)
    {
      $cacheFile = $this->cacheDir . "/" . sha1($url);
      if(file_exists($cacheFile)) { $this->lastResponseWasCached = true; return file_get_contents($cacheFile); }
    }
    
    $response = $this->_request($url);
    
    if($cache)
    {
      file_put_contents($cacheFile, $response);
    }
    
    return $response;
  }
  
  public function Post($url,$data = array())
  {
    $this->lastResponseWasCached = false;
    return $this->_request($url,$data);
  }
  
  protected function _request($url,$data = null,$sslVerification = true)
  {
    if($this->ch == null)
    {
      $this->ch = curl_init();
    }
    $ch = $this->ch;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    
    // You can disable certification verification by setting caPath to null
    // but this isn't recommended!
    if(($this->caPath === null) || ($sslVerification === false))
    {
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    }
    // If the caPath is a URL, then check to see if there is a local copy.
    // If there isn't, then temporarily disable SSL verification and download 
    // the bundle. Ideally, you should download the CA bundle with your browser
    // and put it on the server and point caPath to it.
    elseif(substr($this->caPath,0,8) == "https://")
    {
      $local_cacert_pem = __DIR__ . "/cacert.pem";
      if(!file_exists($local_cacert_pem))
      {
        $bundle = $this->_request($this->caPath,null,false);
        if(strpos($bundle,"BEGIN CERTIFICATE") === false)
        {
          throw new \Exception("CurlBrowser Error: caPath is set to a URL but cURL was unable to download and verify the CA bundle. You need to manually download cacert.pem from the cURL website and place it next to the ".__FILE__." file.");
        }
        if(file_put_contents($local_cacert_pem,$bundle) === false)
        {
          throw new \Exception("CurlBrowser Error: Downloaded the CA bundle from {$this->caPath} but could not write to {$local_cacert_pem}. You need to manually download cacert.pem from the cURL website and place it next to the ".__FILE__." file.");
        }
      }
      curl_setopt($ch, CURLOPT_CAINFO, $local_cacert_pem);
      curl_setopt($ch, CURLOPT_URL, $url);
    }
    elseif(file_exists($this->caPath))
    {
      curl_setopt($ch, CURLOPT_CAINFO, $this->caPath);
    }
    else
    {
      throw new \Exception("CurlBrowser Error: caPath is not set to a valid cacert.pem bundle!");
    }
    
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 600);
    curl_setopt($ch, CURLOPT_TIMEOUT, 600);
    if($this->cookiesFile !== null)
    {
      curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookiesFile);
      curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookiesFile);
    }
    curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_ENCODING, "gzip, deflate");
    if($this->proxyURL !== null)
    {
      curl_setopt($ch, CURLOPT_PROXY, $this->proxyURL);
    }
    if(count($this->httpHeaders))
    {
      curl_setopt($ch, CURLOPT_HTTPHEADER, $this->httpHeaders);
    }

    if($data !== null)
    {
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    
    // Run operation
    $result = curl_exec($ch);
    $error = curl_error($ch);
    
    if($result === false)
    {
      throw new Exception($error);
    }
    else
    {
      if(!empty($this->saveLastOutput))
      {
        file_put_contents($this->saveLastOutput,$result);
      }
      return $result;
    }
  }
}
