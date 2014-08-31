<?php

class ExtendedClass {

	function get_var($var) {
		$var2 = "_$var";
		return ($this->$var)?($this->$var):($this->$var2);
	}

	function get_called_class() {
		return get_class($this);
	}
}

?>