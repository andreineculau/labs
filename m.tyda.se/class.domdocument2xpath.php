<?php
/**
 * Implements DOMDocument2XPath class
 * 
 * This is the class that helps parse HTML into DOM trees
 * 
 * @package lib
 * @author	Andrei Neculau <andrei.neculau@gmail.com>, http://www.andreineculau.com
 */

class DOMDocument2XPath extends DOMDocument
{
	/**
	 * Constructor: Creates an instance of DOMDocument2XPath
	 *
	 * @param string $source
	 * @param string $xpath
	 * @param string $version
	 * @param string $encoding
	 */
	function __construct($source = NULL, &$xpath = NULL, $version = '1.0', $encoding = 'utf-8')
	{
		parent::__construct($version, $encoding);
		$this->preserveWhiteSpace = FALSE;
		$this->substituteEntities = TRUE;
		
		if ($source)
		{
			$source = preg_replace('/<head[^>]*>/', '<head><meta http-equiv="Content-Type" content="text/html; charset=' . $this->encoding . '">', $source);
			$source = preg_replace('/>\s+/', '>', $source);
			$source = preg_replace('/\s+</', '<', $source);
			$source = preg_replace('/\s{2,}/', ' ', $source);
			@$this->loadHTML($source);
			$xpath = new DOMXPath($this);
		}
	}
}

function GetContentAsString($node) {    
  $st = "";
  foreach ($node->childNodes as $cnode)
   if ($cnode->nodeType==XML_TEXT_NODE)
     $st .= $cnode->nodeValue;
   else if ($cnode->nodeType==XML_ELEMENT_NODE) {
     $st .= "<" . $cnode->nodeName;
     if ($attribnodes=$cnode->attributes) {
       $st .= " ";
       foreach ($attribnodes as $anode)
         $st .= $anode->nodeName . "='" . 
           $anode->nodeValue . "'";
       }    
     $nodeText = GetContentAsString($cnode);
     if (empty($nodeText) && !$attribnodes)
       $st .= " />";        // unary
     else
       $st .= ">" . $nodeText . "</" . 
         $cnode->nodeName . ">";
     }
  return $st;
  }

?>