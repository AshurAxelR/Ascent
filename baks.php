<!DOCTYPE html>
<html>
<head>
<title>Asc: History</title>
<link rel="shortcut icon" href="favicon.png" />
<style><!--
body {
	font-family: Verdana, sans;
	background-color: #fdfdfd;
	color: #000;
	font-size: 10pt;
	margin: 40px;
	padding: 0px;
}
a {
	color: #39d;
	text-decoration: none;
}
a:hover {
	color: #5bf;
	text-decoration: underline;
}
td {
	text-align: right;
	padding-right: 20px;
}
tr.ny td {
	padding-top: 10px;
}
--></style>
</head>
<body><div id="body">
<table>
<?php

$prev_year = false;
for($d=1; ; $d++) {
	$dir = 'bak/' . $d;
	if(!file_exists($dir))
		break;
		
	$file = $dir . '/index.php';
	if(!file_exists($file)) {
		$file = $dir . '/asc.php';
		if(!file_exists($file))
			continue;
	}
	
	$code = file_get_contents($file, false, null, 0, 500);
	if(preg_match('/(?:(?:\$start\s*\=\s*strtotime\()|(?:\START_DATE\s*\=\s*))\'([0-9\-]+)\'/', $code, $m)) {
		$date = strtotime($m[1]);
		$year = date('Y', $date);
		echo '<tr' . ($year!=$prev_year ? ' class="ny"' : '') . '><td>' . $d . '</td>';
		echo '<td><a href="' . $file . '">' . strtoupper(date('j M', $date)) . ' <b>' . $year . '</b></a></td>';
		echo '<td><a href="' . $file . '?g=1">Total</a></td></tr>' . PHP_EOL;
		$prev_year = $year;
	}
}

?>
</table>
</div>
</body>
</html>
