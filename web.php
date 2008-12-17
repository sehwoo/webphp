<?php
$_S = &$_SERVER;
$v = array();
$stat = array();

$stat[200] = '200 OK';
$stat[204] = '204 No Content';
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

$url = substr($_S['REQUEST_URI'], strrpos($_S['SCRIPT_NAME'], '/'));
while (substr($url, -1) == '/') $url = substr($url, 0, -1);
if ($url == '') $url = '/';

// $u can be either `/test` or `test`.
function URL($u) {
	global $_S;
	while ($u[0] == '/') $u = substr($u, 1, strlen($u) - 1);
	$u = substr($_S['SCRIPT_NAME'], 0,
		strrpos($_S['SCRIPT_NAME'], '/') + 1) . $u;
	return 'http://'. $_S['SERVER_NAME'].
		(($_S['SERVER_PORT'] == '80')?
		'':':'. $_S['SERVER_PORT']) . $u;
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

function _render($yield, $_l = 'layout') {
	global $v;
	foreach ($v as $_k => $_v) $$_k = $_v;
	if (is_string($_l))
		if (file_exists($f = 'views/'. $_l)) include $f;
		elseif (file_exists($f = 'views/'. $_l .'.php')) include $f;
	else echo $yield;
}

// Render takes the view to be run. $_l is used as layout if exists
// Layouts must be in the `views/` directory.
function render($_f, $_l = 'layout') {
	global $v;
	foreach ($v as $_k => $_v) $$_k = $_v;
	ob_start();
	include 'views/'. trim($_f) .'.php';
	_render(ob_get_clean(), $_l);
}

// Runs the method in the specified controller
function serve($c, $m, $a = array(), $e = TRUE) {
	// $c, $m, $a, $e = controller, GET or POST, matches, eval?
	// $e = TRUE or FALSE. TRUE => evals the statement
	if (($m = strtolower($m)) == 'post') $a[] = '$_POST';
	$s = $c .'::'. $m .'('. join(',', $a) .');';
	if (method_exists($c, $m))
		if ($e == TRUE) eval($s);
		else return $s;
	else r(501, $stat[501]);
}

// This matches the URL and runs the appropriate controller's method
function run($urls) {
	global $url, $_S;

	foreach ($urls as $r => $c) {
		// $r, $c, $m = route, controller, matches
		preg_match('/^'.str_replace('/', '\/', $r).'$/', $url, $m);

		if (count($m) > 0) {
			array_shift($m); // Rid $m[0] (the matching URL)
			foreach ($m as &$i) $i = '"'. $i .'"';
			serve($c, (in_array($_S['REQUEST_METHOD'], array(
				'POST', 'GET', 'DELETE', 'PUT'))?
				$_S['REQUEST_METHOD']:'GET'), $m);
			return;
		} // else it isn't a match so go to the next item in array
	}
	// If the code reaches this point, there was no match
	die('not found');
}

