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
	
	protected function _request($url,$data = null)
	{
	  if($this->ch == null)
	  {
		  $this->ch = curl_init();
		}
		$ch = $this->ch;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
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
