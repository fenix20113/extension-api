<?php
namespace AppShed\Element\Screen;

class Map extends Screen {
    const TYPE = 'map';
    const TYPE_CLASS = 'map';
    
    protected $zoom = 12;
    protected $scroll = false;
    
    public function getZoom() {
        return $this->zoom;
    }

    public function setZoom($zoom) {
        $this->zoom = $zoom;
    }
    
    /**
	 * Get the html node for this element
     * @param \DOMElement $node
	 * @param \Appshed\XML\DOMDocument $xml
	 * @param \AppShed\HTML\Settings $settings
     * @param \AppShed\Style\CSSDocument $css
     * @param array $javascripts
	 */
    protected function getHTMLNodeBase($node, $xml, $settings, $css, &$javascripts) {
        $node->setAttribute('data-zoom', $this->zoom);
    }
    
    /**
	 *
	 * @param \DOMElement $items
	 * @param \DOMDocument $xml
	 * @param \AppShed\HTML\Settings $settings
     * @param \AppShed\Style\CSSDocument $css
     * @param array $javascripts
	 */
	protected function addHTMLChildren($items, $xml, $settings, $css, &$javascripts) {
		$items->appendChild($itemsInner = $xml->createElement('script', array('type' => 'application/json')));
		
        $settings->pushCurrentScreen($this->getId);
		
        $locs = array();
		$headButtons = array();
		foreach ($this->children as $child) {
            if($child->getHeaderItem()) {
                $headButtons[] = $child;
            }
            else {
                $locs[] = $child->getMarkerObject($xml, $settings);
                $child->getCSS($css, $settings);
                $child->getJavascript($javascripts, $settings);
            }
		
        }
        $itemsInner->appendChild($xml->createTextNode(json_encode($locs)));
		
        $settings->popCurrentScreen();
       
        return $headButtons;
	}
}