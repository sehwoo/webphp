<?php
require 'web.php';

$urls = array(
	'/(.*)' => 'Hello'
);

class Hello {
	function get($page) {
		if (empty($page)) $page = "world";
		echo "Hello {$page}!";
	}
}

run($urls);

?>
