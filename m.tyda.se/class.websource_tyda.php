<?php

class Websource_Tyda extends Websource {
	function content($word) {
		$output = curl_get($this->curl_handle, 'http://tyda.se/search?form=1&w_lang=&x=0&y=0&w=' . $word);
		
		if ($output === FALSE) return FALSE;
		
		new DOMDocument2XPath($output, $xpath);
		
		$entries = $xpath->query('//table[@class="tyda_content"]');
		
		if ($entries->length) {
			$table = $entries->item(0);
			
			$_dom = new DOMDocument('1.0', 'UTF-8');
			$_dom->xmlStandalone = true;
			$_dom->formatOutput = true;
			
			$_dom->appendChild($_dom->importNode($table, true));
			
			return $_dom->saveHTML();
		}
		return '1';
	}
}