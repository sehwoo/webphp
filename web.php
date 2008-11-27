<?php
define('SCRIPT', $_SERVER['SCRIPT_NAME']);
define('REQUEST', $_SERVER['REQUEST_URI']);
define('PORT', $_SERVER['SERVER_PORT']);
$v = array();

$stat = array();
$stat[200] = '200 OK';
$stat[301] = '301 Moved Permanently';
$stat[302] = '302 Found';
$stat[304] = '304 Not Modified';
$stat[307] = '307 Temporary Redirect';
$stat[400] = '400 Bad Request';
$stat[401] = '401 Authorization Required';
$stat[403] = '403 Forbidden';
$stat[404] = '404 Not Found';
$stat[410] = '410 Gone';
$stat[500] = '500 Internal Server Error';
$stat[501] = '501 Method Not Implemented';

$url = substr(REQUEST, strrpos(SCRIPT, '/'));
while (substr($url, -1) == '/') $url = substr($url, 0, -1);
if ($url == '') $url = '/';

// $u can be either `/test` or `test`.
function URL($u) {
	while ($u[0] == '/') $u = substr($u, 1, strlen($u) - 1);
	$u = substr(SCRIPT, 0, strrpos(SCRIPT, '/') + 1) . $u;
	return 'http://'. $_SERVER['SERVER_NAME'].
		((PORT == '80')? '':':'.PORT).$u;
}

function r($s, $b, $h) {
	global $stat;
	header('HTTP/1.1 '. $stat[$s]);
	if (!empty($h))
		if (is_string($h)) header($h);
		elseif (is_array($h)) foreach ($h as $i) header($i);
	echo $b;
}

function redirect($l) {
	r(301, '', 'Location: '. URL($l));
}

// Render takes the view to be run. $_l is used as layout if exists
// Layouts must be in the `views/` directory.
function render($_f, $_l = 'layout') {
	global $v;
	ob_start();
	foreach ($v as $_k => $_v) $$_k = $_v;
	include 'views/'. trim($_f) .'.php';
	$yield = ob_get_clean();

	if (is_string($_l))
		if (file_exists($f = 'views/'. $_l)) include $f;
	else echo $yield;
}

// Prepares string for url regex
function prep_reg($s) {
	return '/^'. str_replace('/', '\/', $s) .'$/';
}

// Runs the method in the specified controller
function serve($c, $m, $a = array(), $e = TRUE) {
	// $c, $m, $a, $e = controller, GET or POST, matches, eval?
	// $e = TRUE or FALSE. TRUE => evals the statement
	if (($m = strtolower($m)) == 'post') $a[] = '$_POST';
	$s = $c .'::'. $m .'('. join(',', $a) .');';
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
			// $m includes the url that it matches, we dislike
			array_shift($m);
			foreach ($m as &$i) $i = '"'. $i .'"';
			serve($c, (count($_POST) == 0)? 'get':'post', $m);
			return '';
		} // else it isn't a match so go to the next item in array
	}
	// If the code reaches this point, there was no match
	die('not found');
}

?>
