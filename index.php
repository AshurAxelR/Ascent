<?php
const SUMMER_TIME = true;
const START_DATE = '23-07-2022';
const HOLIDAYS = array(); // additional holidays (column indices)
const DAYS = 40;

if($_SERVER['SERVER_NAME']!='localhost' && $_SERVER['SERVER_NAME']!='127.0.0.1') die('Not localhost.');

date_default_timezone_set('UTC');
$now = time() + (SUMMER_TIME ? 3600 : 0);

// demo date is fixed, remove next line
$now = strtotime('06-08-2022');

$start = strtotime(START_DATE);
$day = (int)(($now-$start)/24/60/60) - 1;

const EOL = "\n";
$db = explode(EOL, @file_get_contents('asc.txt'));

$weekend_start_balance = 2;
$opts_json = '[
	{"cap": "Practice piano", "val": 50,
			"rule": "practice piano IN 5 days | OR creativity &ge; 4h TODAY"},
	{"cap": "5h rest limit", "val": 25,
			"rule": "rest+idle &lt; 5h | OR |~ weekend |~ AND rest+idle &lt; 8h"},
	{"cap": "7h rest limit broken", "val": -50,
			"rule": "rest &gt; 7h | AND NOT weekend"},
	{"cap": "3h internet limit", "val": 25,
			"rule": "internet &lt; 3h | OR |~ weekend |~ AND internet &lt; 5h"},
	{"cap": "12am bed time", "val": 50,
			"rule": "go to bed &le; 12am | OR weekend"},
	{"cap": "1am bed time broken", "val": -50,
			"rule": "go to bed &gt; 1am | AND NOT deadline | AND NOT New Years eve"},
	{"cap": "8.30 breakfast time", "val": 50,
			"rule": "breakfast &le; 8.30am | OR |~ weekend |~ AND breakfast &le; 10.30am"},
	{"cap": "Average sleep", "val": 20,
			"rule": "weekly AVG: |~ sleep &ge; 7h |~ AND sleep &le; 8&#0189;h"},
	{"cap": "Eat friut or veg", "val": 50},
	{"cap": "No dirty dishes when leaving kitchen", "val": 30},
	{"cap": "Dirty dishes left overnight", "val": -50},
	{"cap": "Weekly chores", "val": 50,
			"rule": "weekly chores done | OR TODAY chores &ge; 2h"},
	{"cap": "Monthly chores", "val": 50,
			"rule": "monthly chores done | OR TODAY chores &ge; 1h"},
	{"cap": "Excercise", "val": 75,
			"rule": "excercise TODAY | OR walk &ge; 30min TODAY | OR walk &ge; 1h IN 2 days"},
	{"cap": "Weekly total walk &ge; 2h", "val": 25},
	{"cap": "Weekend swap", "val": 0}
]';
$opts = json_decode($opts_json, true);
$weekend_swap_index = count($opts)-1;
$total = 0;
foreach($opts as $opt) {
	if($opt['val']>0) $total += $opt['val'];
}

$save = @$_REQUEST['s'];
if($save) {
	if(strlen($save)!=count($opts)) {
		echo 'Bad request.';
		die();
	}
	$save_day = @$_REQUEST['d'];
	if($save_day==$day && $day==count($db)) {
		$db[] = $save;
	}
	else if($save_day==$day && $day==count($db)-1) {
		$db[count($db)-1] = $save;
	}
	else if($save_day==$day && $day>count($db)) {
		$none = str_repeat('n', count($opts));
		while(count($db)<$day)
			$db[] = $none;
		$db[] = $save;
	}
	else {
		echo 'Bad request.';
		die();
	}
	if(!@file_put_contents('asc.txt', implode(EOL, $db))) {
		echo 'Cannot save data.';
		die();
	}
	echo 'Saved.';
	exit();
}

$color_map = json_decode('[
	[0, "#c40"], [0.6, "#fff"], [0.7, "#efd"], [0.8, "#cea"], [0.9, "#9d7"], [1, "#7c6"], [2, "#5b5"]
]', true);
function value_color($v) {
	global $color_map;
	foreach($color_map as $cm) {
		if($v<$cm[0])
			return $cm[1];
	}
	return false;
}

if(@$_REQUEST['g']) {
	$res = array();
	$days = array();
	for($i=0; $i<DAYS; $i++) {
		$res[$i] = 0;
		$t = $start + $i*24*60*60;
		$w = idate('w', $t);
		$days[$i] = strtoupper(date('j M', $t));
	}
	foreach($opts as $row=>$opt) {
		for($i=0; $i<DAYS; $i++) {
			if($i<count($db) && @substr($db[$i], $row, 1)=='y') {
				$res[$i] += $opt['val'];
			}
		}
	}
?>
<!DOCTYPE html>
<html>
<head>
<title>Asc: Total</title>
<link rel="shortcut icon" href="favicon.png" />
<style><!--
svg text {
	font-family: Verdana, sans;
	fill: #000;
	font-size: 9px;
}
svg .axis {
	fill: none;
	stroke-width: 1;
	stroke: #000;
}
svg .grid {
	fill: none;
	stroke-width: 1;
	stroke: #eee;
}
--></style>
</head>

<body>
<?php

$min = 0;
for($i=0; $i<count($db); $i++) {
	if($res[$i]<$min)
		$min = $res[$i];
}

$Y_SCALE = 0.4;

$gwidth = count($db)*45+15;
$gheight = $total*$Y_SCALE;
echo '<svg width="' . ($gwidth+100) . '" height="' . (($total-$min)*6/10+70) . '">' . PHP_EOL;
echo '<g transform="translate(80 ' . ($gheight+50) . ')">' . PHP_EOL;
echo '<line class="axis" x1="0" y1="0" x2="' . $gwidth . '" y2="0" />' . PHP_EOL;
for($v=0; $v<=$total; $v+=50) {
	$y = -$v*$Y_SCALE;
	if($v!=0)
		echo '<line class="grid" x1="0" y1="' . $y . '" x2="' . $gwidth . '" y2="' . $y . '" />';
	echo '<text x="-10" y="' . ($y+3) . '" text-anchor="end" >' . $v . '</text>' . PHP_EOL;
}

for($i=0; $i<count($db); $i++) {
	$x = $i*45+30;
	$color = value_color($res[$i]/$total);
	$h = $res[$i]*$Y_SCALE;
	$text_style = is_weekend($i) ? ' style="fill:#f77" ' : '';
	if($h>0) {
		echo '<rect style="fill:' . $color . '" x="' . ($x-15) . '" y="' . (-$h) . '" width="30" height="' . $h . '" />';
		echo '<text style="fill:#aaa" x="' . $x . '" y="' . (-$h-5) . '" text-anchor="middle">' . $res[$i] . '</text>';
		echo '<text ' . $text_style . ' x="' . $x . '" y="15" text-anchor="middle">' . $days[$i] . '</text>' . PHP_EOL;
	}
	else if($h<0) {
		echo '<rect style="fill:' . $color . '" x="' . ($x-15) . '" y="0" width="30" height="' . (-$h) . '" />';
		echo '<text style="fill:#c40" x="' . $x . '" y="' . (-$h+12) . '" text-anchor="middle">' . $res[$i] . '</text>';
		echo '<text ' . $text_style . ' x="' . $x . '" y="-5" text-anchor="middle">' . $days[$i] . '</text>' . PHP_EOL;
	}
	else {
		echo '<text ' . $text_style . ' x="' . $x . '" y="15" text-anchor="middle">' . $days[$i] . '</text>' . PHP_EOL;
	}
}

echo '</g></svg>';

?>
</body>
</html>
<?php
	exit();
}

function col_date($i) {
	global $start;
	return $start + $i*24*60*60;
}
function is_weekend($i) {
	if(in_array($i, HOLIDAYS, true)) return true;
	$w = idate('w', col_date($i));
	return ($w==0 || $w==6);
}

$weekend_balance = $weekend_start_balance;
for($i=0; $i<$day; $i++) {
	if($weekend_swap_index>=0 && @substr($db[$i], $weekend_swap_index, 1)=='y')
		$weekend_balance += is_weekend($i) ? 1 : -1;
}

?>
<!DOCTYPE html>
<html>
<head>
<title>Asc</title>
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
table {
	font: inherit;
	border-collapse: collapse;
}
tr {
	height: 20pt;
}
tr.row:hover {
	background-color: #fafafa;
}
td {
	padding: 3px 10px;
	border: 1px solid #ddd;
}
td.d {
	padding: 3px;
	width: 55px;
	min-width: 55px;
	text-align: center;
	cursor: default;
}
td.today {
	background-color: #f8f8f8;
}
td.today:hover {
	background-color: #eeeeee;
}
td.cap {
	white-space: nowrap;
	border-right: none;
	position: relative;
}
td.val {
	text-align: right;
	border-left: none;
	font-weight: bold;
}
td.neg {
	color: #aaa;
}
td.shown {
	background-color: #ddd;
}
tr.head {
	font-size: 8pt;
	color: #777;
}
tr.head td.sun {
	color: #f77;
}
tr.total td {
	border: none;
	vertical-align: top;
}
tr.graph {
	height: 5px;
}
tr.graph td {
	border: none;
}
p {
	margin: 0px;
	padding: 0px;
}
span.h {
	font-size: 14pt;
	font-weight: bold;
}
h3 {
	font-size: 10pt;
	font-weight: bold;
	margin: 5px 0px;
	padding: 0px;
}
ul {
	margin: 5px 20px;
	padding: 0px;
}
div.rulepivot {
	position: absolute;
	z-index: 1;
	top: -10px;
	right: 0px;
}
div.rule {
	color: #000;
	font-size: smaller;
	white-space: nowrap;
	padding: 10px;
	padding-top: 0px;
	padding-bottom: 8px;
	display: none;
	border:  1px solid #777;
	background-color: #fff;
	box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.2);
	position: absolute;
}
div.rulecap {
	color: #999;
	font-size: 10pt;
	background-color: #fafafa;
	padding: 4px 10px;
	margin-left: -10px;
	margin-right: -10px;
	border-bottom: 1px solid #ddd;
	margin-bottom: 6px;
}
div.rulecap b {
	color: #000;
}
div.rulecap.neg b {
	color: #c40;
}
span.hasrule {
	color: #aaa;
}
span.marg {
	color: #ddd;
}
div#overlay {
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	visibility: hidden;
	z-index: 2;
}
--></style>
<script type="text/javascript">
var opts = <?php echo $opts_json; ?>;
var weekendSwapIndex = <?php echo $weekend_swap_index; ?>;
var isWeekend = <?php echo is_weekend($day) ? 'true' : 'false'; ?>;
var total = 0;
var weekendBalance = <?php echo $weekend_balance; ?>;
var ruleShown = -1;

function updTotal() {
	total = 0;
	for(let i=0; i<opts.length; i++) {
		let opt = document.getElementById('s'+i);
		if(opt.innerHTML!='')
			total = total + opts[i].val;
	}
	if(total>0)
		total = '+'+total;
	document.getElementById('total').innerHTML = total;
	
	let wb = weekendBalance;
	if(weekendSwapIndex>=0) {
		let opt = document.getElementById('s'+weekendSwapIndex);
		if(opt.innerHTML!='')
			if(isWeekend)
				wb = wb+1;
			else
				wb = wb-1;
	}
	if(wb>0)
		wb = '+'+wb;
	document.getElementById('wb').innerHTML = wb;
	
	let v = total / <?php echo $total; ?>.0;
	let color;
	<?php
	$first = true;
	foreach($color_map as $cm) {
		if(!$first) echo "\telse ";
		$first = false;
		echo "if(v<{$cm[0]})\n\t\tcolor='{$cm[1]}';\n";
	}
	?>
	document.getElementById('gr').style.backgroundColor = color;
}

function toggle(c, i) {
	if(c.innerHTML!='')
		c.innerHTML = '';
	else if(opts[i].val>=0)
		c.innerHTML = '&#10004;';
	else
		c.innerHTML = '&#10008;';
	document.getElementById('savebutton').style.visibility = 'visible';
	updTotal();
}

function upload(str) {
	let xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			document.getElementById('resp').innerHTML = this.responseText;
			document.getElementById('savebutton').style.visibility = 'hidden';
		}
	};
	xhttp.open("GET", "?d=<?php echo $day; ?>&s="+str, true);
	xhttp.send();
}

function save() {
	let s = '';
	for(let i=0; i<opts.length; i++) {
		let opt = document.getElementById('s'+i);
		if(opt.innerHTML!='')
			s = s + 'y';
		else
			s = s + 'n';
	}
	upload(s);
}

function hideRule() {
	if(ruleShown>=0) {
		document.getElementById('rule'+ruleShown).style.display = 'none';
		document.getElementById('cap'+ruleShown).classList.remove('shown');
		document.getElementById('overlay').style.visibility = 'hidden';
		ruleShown = -1;
	}
}

function showRule(r) {
	ruleShown = r;
	document.getElementById('rule'+ruleShown).style.display = 'block';
	document.getElementById('cap'+ruleShown).classList.add('shown');
	document.getElementById('overlay').style.visibility = 'visible';
}

</script>
</head>
<body onload="updTotal()"><div id="body">

<table>
<?php

function convertRule($rule) {
	$rule = preg_replace('/\s*\|\s*/', '<br/>', $rule);
	$rule = preg_replace('/\s*~\s*/', '<span class="marg">&#x2502;&nbsp;</span>', $rule);
	$kw = array(
		'OR'=>'<b>or</b>',
		'AND'=>'<b>and</b>',
		'NOT'=>'<b>not</b>',
		'IN'=>'<b>within</b>',
		'AVG'=>'<b>average</b>',
		'TOTAL'=>'<b>total</b>',
		'TODAY'=>'<b>today</b>'
	);
	return strtr($rule, $kw);
}

$res = array();
echo '<tr class="head"><td style="border-right:none"></td><td style="border-left:none"></td>';
for($i=0; $i<DAYS; $i++) {
	$res[$i] = 0;
	echo '<td class="d' . (is_weekend($i) ? ' sun' : '') . '">' . strtoupper(date('j\<\b\r\/\>M', col_date($i))) . '</td>';
}
echo '</tr>' . EOL;
foreach($opts as $row=>$opt) {
	echo '<tr class="row">';
	$val = $opt['val'];
	$neg = 'neg ';
	if($val>0) {
		$val = '+' . $val;
		$neg = '';
	}
	$rule = isset($opt['rule']) ? convertRule($opt['rule']) : false;
	echo '<td id="cap' . $row . '" class="' . $neg . 'cap"';
	if($rule) {
		echo ' onclick="showRule(' . $row . ')">' . $opt['cap'] . ' <span class="hasrule">[...]</span>';
		echo '<div class="rulepivot"><div id="rule' . $row . '" class="rule">';
		echo '<div class="' . $neg . 'rulecap"><b>' . $val . '</b> : ' . $opt['cap'] . '</div>';
		echo $rule;
		echo '</div></div>';
	}
	else {
		echo'>' . $opt['cap'];
	}
	echo '</td>';
	echo '<td class="' . $neg . 'val">' . $val . '</td>' . EOL;
	for($i=0; $i<DAYS; $i++) {
		if($i==$day)
			echo '<td id="s' . $row . '" class="d today" onclick="toggle(this, ' . $row . ')">';
		else
			echo '<td class="d">';
		if($i<count($db) && @substr($db[$i], $row, 1)=='y') {
			$res[$i] += $opt['val'];
			echo $opt['val']>=0 ? '&#10004;' : '&#10008;';
		}
		echo '</td>';
	}
	echo '</tr>' . EOL;
}
echo '<tr class="graph"><td></td><td></td>';
for($i=0; $i<DAYS; $i++) {
	$v = $res[$i] / (float) $total;
	$color = value_color($v);
	echo '<td ' . ($i==$day ? 'id="gr" ' : '') . 'style="background-color:' . $color . '"></td>';
}
echo '</tr>';
echo '<tr class="total"><td><a href="?g=1">Total</a></td><td style="text-align:right"><b>+' . $total . '</b></td>';
for($i=0; $i<DAYS; $i++) {
	if($i==$day)
		echo '<td class="d"><span id="total" style="font-weight:bold">0</span><br />
			<input id="savebutton" type="button" value="&#9998;" title="Save" style="font-size:14pt;margin-top:4px;visibility:hidden" onclick="save()" /></td>';
	else if($i<$day)
		echo '<td class="d">' . ($res[$i]>0 ? '+' : '') . $res[$i] . '</td>';
	else
		echo '<td></td>';
}
echo '</tr>' . EOL;
?>
</table>
<hr style="width:200px;border:none;margin:5px 0;padding:0;border-bottom:1px solid #777" />
<p><a href="chores.php">Chores</a> / <a href="baks.php">History</a></p>
<p>Weekends: <span id="wb" style="font-weight:bold"><?php echo ($weekend_balance>0 ? '+' : '') . $weekend_balance; ?></span><br/>
Work+travel over 12h grants extra weekend</p>
<p id="resp"></p>
<div id="overlay" onclick="hideRule()"></div>
</body>
</html>
