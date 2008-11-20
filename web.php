<?php

$url = substr($_SERVER['REQUEST_URI'],
	strrpos($_SERVER['SCRIPT_NAME'], '/'));
while(substr($url, -1) == '/') $url = substr($url, 0, -1);
if($url=='') $url='/';

// Prepares string for url regex
function prep_reg($s) {
	return '/^' . str_replace('/', '\/', $s) . '$/';
}

function serve($c, $m, $a = array(), $e = TRUE) {
	// $c, $m, $a, $e = controller, GET or POST, matches, eval?
	// $e = TRUE or FALSE. TRUE => evals the statement
	if (($m = strtolower($m)) == 'post') $a[] = '$_POST';
	$s = $c .'::'.$m.'('. join(',', $a) .');';
	if ($e == TRUE) eval($s);
	return $s;
}

// This matches the URL and runs the appropriate controller's method
function run($urls) {
	global $url;

	foreach ($urls as $r => $c) {
		// $r, $c = route, controller
		$r = prep_reg($r);
		preg_match($r, $url, $m);
		// $m = matches

		if (count($m) > 0) {
			// $m includes the url that it matches
			array_shift($m);
			foreach ($m as &$i) $i = '"'. $i .'"';
			if (count($_POST) == 0) {
				// No POST data. Execute `get`
				serve($c, 'get', $m);
				return '';
			} elseif (count($_POST) > 0) {
				// There is POST data. Execute `post`
				serve($c, 'post', $m);
				return '';
			}
		} // else it isn't a match so go to the next item in array
	}
	// If the code reaches this point, there was no match
	die('not found');
}

?>
