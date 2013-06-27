<?php
namespace AppShed\HTML;

class Remote {
    /**
     *
     * @var \AppShed\Element\Root
     */
    protected $root;
    
    /**
     *
     * @var array
     */
    protected $roots = array();

    /**
     * @var string
     */
    protected $requestUrl;
    
    /**
     *
     * @var int
     */
    protected $refreshAfter = 0;

    public function __construct($root) {
        $this->root = $root;
    }
    
    public function addRoot($root) {
        $this->roots[] = $root;
    }
    
    public function setRefreshAfter($refreshAfter) {
        $this->refreshAfter = $refreshAfter;
    }
    
    /**
     * 
     * @return \AppShed\HTML\Settings
     */
    public function getSettings() {
        $settings = new Settings();
        $requestUrl = $this->getRequestUrl();
        $settings->setFetchUrl($requestUrl);
        $settings->setPrefix(sha1($requestUrl));
        $settings->setEmailPreview(isset($_REQUEST['emailPreview']) ? $_REQUEST['emailPreview'] === 'true' : false);
        $settings->setPhonePreview(isset($_REQUEST['telPreview']) ? $_REQUEST['telPreview'] === 'true' : false);
        return $settings;
    }
    
    /**
     * 
     * @param \AppShed\HTML\Settings $settings
     */
    public function getResponseObject($settings = null) {
        if(!$settings) {
            $settings = $this->getSettings();
        }
        
        $xml = self::getNewXMLDocument();
        $this->root->getHTMLNode($xml, $settings);
        foreach ($this->roots as $root) {
            $root->getHTMLNode($xml, $settings);
        }
        
        return array(
            'app' => $settings->getApps(),
            'screen' => $settings->getScreens(),
            'settings' => array(
                'main' => $settings->getPrefix() . $this->root->getId(),
                'maintype' => $this->root instanceof \AppShed\Element\App ? 'app' : 'screen'
            )
        );
    }
    
    /**
     * 
     * @param \AppShed\HTML\Settings $settings
     */
    public function getResponse($settings = null, $header = true, $return = false) {
        if(!$settings) {
            $settings = $this->getSettings();
        }
        $data = $this->getResponseObject($settings);
        
        $data['remote'] = array(
            'url' => $settings->getFetchUrl(),
            'refreshAfter' => $this->refreshAfter
        );
        $data['remote'][$settings->getFetchUrl()] = $data['settings']['main'];
        
        $json = json_encode($data);
        $callback = $this->getCallback();
        if ($header) {
			header('Content-type: application/javascript');
			
			if (isset($_SERVER['HTTP_ORIGIN'])) {
				header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
				header('Access-Control-Allow-Credentials: true');
				header('Access-Control-Max-Age: 86400');	// cache for 1 day
			}
			// Access-Control headers are received during OPTIONS requests
			if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

				if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
					header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

				if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
					header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

				exit(0);
			}
		}
		if ($callback) {
			$ret = "$callback(" . $json . ");";
		}
		else {
			$ret = $json;
		}
        if($return) {
            return $ret;
        }
        else {
            echo $ret;
        }
    }
    
    public function setRequestUrl($url) {
        $this->requestUrl = $url;
    }
    
    protected function getRequestUrl() {
        if($this->requestUrl) {
            return $this->requestUrl;
        }
        else if(isset($_REQUEST['fetchURL'])) {
            return $_REQUEST['fetchURL'];
        }
        else if(isset($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_URI'])) {
            return (isset($_SERVER['HTTPS']) ? 'https' : 'http') . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
        }
        else {
            return "";
        }
    }
    
    protected function getCallback() {
        if(isset($_REQUEST['callback'])) {
            return $_REQUEST['callback'];
        }
        return false;
    }


    protected function getNewXMLDocument() {
		$xml = new \AppShed\XML\DOMDocument('1.0', 'UTF-8');
		$xml->preserveWhiteSpace = false;
		$xml->formatOutput = false;
		return $xml;
	}
}