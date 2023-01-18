# CurlBrowser
A PHP class to simplify cURL calls. Emulates an Edge browser user agent and supports cookies and caching.
Automatically downloads the CA extract (cacert.pem) from the cURL website if needed.

## Usage Examples
```
require("CurlBrowser.class.php");

$Browser = new CurlBrowser();

// Pull https://www.google.com
$html = $Browser->Get("https://www.google.com");

// POST a form
$html = $Browser->Post("https://www.yoursite.com/form.php", array(
  "foo" => "bar", 
  "hello" => "world"));
```

## CA Certificates
By default, the caPath property of CurlBrowser is set to https://curl.se/ca/cacert.pem. If CurlBrowser doesn't detect a "cacert.pem" file in the same folder, it will attempt to download that cacert.pem into the same folder. Alternatively, if you already have a CA bundle you want to use, you can set caPath to that path:

```
require("CurlBrowser.class.php");

$Browser = new CurlBrowser();
$Browser->caPath = "/path/to/cacert.pem";

$html = $Browser->Get(...etc...);
```

## Caching
By default, CurlBrowser will try to create a temp folder and store cached versions of GET requests. You can pass "false" as the second parameter to Get() in order to bypass caching.

```
require("CurlBrowser.class.php");

$Browser = new CurlBrowser();

// Pull https://www.google.com
$html = $Browser->Get("https://www.google.com");

// Pull it again (will pull from the cache instead of performing a request)
$html = $Browser->Get("https://www.google.com");

// Pull it again but skip caching
$html = $Browser->Get("https://www.google.com", false);
```

