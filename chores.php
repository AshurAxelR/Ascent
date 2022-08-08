<?php
const SUMMER_TIME = true;

if($_SERVER['SERVER_NAME']!='localhost' && $_SERVER['SERVER_NAME']!='127.0.0.1') die('Not localhost.');

date_default_timezone_set('UTC');
$now = time() + (SUMMER_TIME ? 3600 : 0);

// demo date is fixed, remove next line
$now = strtotime('06-08-2022');

$chores_json = '[
	{"cat": "Short term", "list": [
		{"cap": "Take out rubbish", "sd": 3, "d": 3, "w": 5, "t": "06-08-2022"},
		{"cap": "Dust bedroom", "sd": 4, "d": 4, "w": 5, "t": "06-08-2022"},
		{"cap": "Vac living room carpet", "sd": 4, "d": 5, "w": 5, "t": "03-08-2022"},
		{"cap": "Shave", "d": 3, "w": 10, "t": "01-08-2022"}
	]},
	{"cat": "Weekly", "list": [
		{"cap": "Weekly laundry", "sd": 6, "d": 7, "w": 20, "t": "05-08-2022"},
		{"cap": "Kitchen counters and shelves", "d": 6, "w": 5, "t": "31-07-2022"},
		{"cap": "Water plants", "sd": 7, "d": 9, "w": 5, "t": "04-08-2022"}
	]},
	{"cat": "Monthly", "list": [
		{"cap": "Clean tiles and spray mould", "d": 45, "w": 20, "t": "28-07-2022"},
		{"cap": "Backup files", "d": 45, "w": 20, "t": "06-07-2022"},
		{"cap": "Meter readings", "d": 30, "w": 15, "t": "18-07-2022"},
		{"cap": "Water filter", "d": 60, "w": 20, "t": "23-07-2022"}
	]},
	{"cat": "Other", "list": [
		{"cap": "Clean windows", "d": 120, "w": 25, "t": "07-05-2022"},
		{"cap": "Living room behind sofa", "sd": 105, "d": 120, "w": 15, "t": "19-06-2022"}
	]}
]';
$chores = json_decode($chores_json, true);

?>
<!DOCTYPE html>
<html>
<head>
<title>Asc: Chores</title>
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
}
tr.head {
	font-weight: bold;
}
tr.head td {
	padding-top: 15px;
	padding-bottom: 5px;
	border: none;
}
td.bar {
	padding: 0px;
}
td.fr {
	border: 1px solid #bbb;
}
tr:hover td.fr {
	background-color: #f5f5f5;
}
span.tip {
	color: #ccc;
	visibility: hidden;
}
tr:hover span.tip {
	visibility: visible;
}
div.td {
	position: relative;
	width: 200px;
	height: 20pt;
	background-color: #cea;
}
div.out {
	background-color: #fdc;
	color: #c40;
}
div.last {
	background-color: #ffd;
}
div.td div {
	position: absolute;
	top: 0;
	left: 0;
}
div.td div.text {
	width: 200px;
	height: 20pt;
	line-height: 20pt;
	text-align: center;
	box-sizing: border-box;
}
div.td div.bar {
	width: 0;
	height: 20pt;
	background-color: #efd;
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

div#list {
	float:left;
}
div#list td.cap {
	padding-right: 20px;
	border-right: none;
}
div#list td.due {
	text-align: right;
	border-left: none;
}
div#list td.blank {
	text-align: center;
	color:#ccc;
}

.weekend {
	color: #f77;
}

div#cal {
	float:right;
	margin-left:20px;
}
div#cal td.date {
	padding-right: 20px;
	border-right: none;
	white-space: nowrap;
}
div#cal td.wtotal {
	color: #999;
	text-align: right;
	border-left: none;
}
div#cal td.done {
	background-color: #efd;
}
div#cal td.none {
	background-color: #f7f7f7;
	color: #ccc;
}
div#cal td.out {
	background-color: #fdc;
	color: #c40;
}
div#cal td.rep {
	color: #999;
}
div#cal td.chk {
	width: 20px;
	text-align:center;
}
div#cal td.cap {
	padding-right: 40px;
	border-right: none;
}
div#cal span.done {
	font-weight: bold;
}
div#cal td.w {
	padding-right: 20px;
	border-left: none;
	text-align: right;
	color: #999;
}
div#cal td.d {
	border-right: none;
	border-left: none;
	text-align: right;
}
div#cal td.cat {
	padding-right: 40px;
	border-left: none;
	border-right: none;
	color: #999;
}
--></style>
</head>
<body>

<div id="list" style="float:left"><table>
<?php
foreach($chores as $chore) {
	echo '<tr class="head"><td colspan="5">' . $chore['cat'] . '</td></tr>';
	foreach($chore['list'] as $c) {
		echo '<tr><td class="fr cap">' . $c['cap'];
		echo '</td><td class="fr due">';
		$d = @$c['d']>0 ? $c['d'] : 1;
		if(@$c['t']) {
			$warn = '&#9888;&#xfe0f;';
			$t = strtotime($c['t']);
			$days = (int)(($now-$t)/24/60/60);
			$date = date('j M', $t);
			if($days==0)
				$date = '<b>'  . $date . '</b>';
			$sd = @$c['sd'] ? $c['sd'] : $d-((int)($d/5)+1);
			if($days<$sd)
				$warn = '';
			if($days<$d) {
				$cls = '';
				$bar = '<div class="bar" style="width:' . ($days*100/$d) . '%"></div>';
			}
			else {
				$bar = '';
				$cls = $days==$d ? ' last' : ' out';
			}
			echo $date . '</td><td class="fr bar"><div class="td' . $cls . '">' . $bar . '<div class="text">' . $days . ' / ' . $d . '</div></div>';
			echo '</td><td>' . $warn . '</td><td><span class="tip">' . date('j M, D', $t+$d*24*60*60) . '</span></td></tr>';
		}
		else {
			echo '</td><td class="blank">(' . $d . ')';
			echo '</td><td></td><td></td></tr>';
		}
	}
}
?>
</table></div>
<div id="cal"><table>
<tr class="head"><td colspan="5">Today</td></tr>
<?php

function offs_date($d) {
	global $now;
	return $now + $d*24*60*60;
}
function is_weekend($d) {
	$w = idate('w', offs_date($d));
	return ($w==0 || $w==6);
}

function cmp_day_chores($a, $b) {
	$am = $a['m'];
	if($am>1) $am = 1;
	$bm = $b['m'];
	if($bm>1) $bm = 1;
	
	$res = $am <=> $bm;
	if($res==0)
		$res = $a['i'] <=> $b['i'];
	return $res;
}

for($d=0; $d<10; $d++) {
	$day_chores = array();
	$wtotal = 0;
	$i = 0;
	foreach($chores as $chore) {
		foreach($chore['list'] as $c) {
			if(@$c['t']) {
				$t = strtotime($c['t']);
				$days = (int)(($now-$t)/24/60/60);
				$loop = @$c['d']>0 ? $c['d'] : 1;
				$dc = array('i'=>$i, 'chore'=>$c, 'cat'=>$chore['cat'], 'm'=>$loop-$days);
				if($days>$loop) {
					if($d==0) {
						$dc['m'] = $loop-$days;
						$day_chores[] = $dc;
						if(isset($c['w']))
							$wtotal += $c['w'];
					}
				}
				else if((($days+$d)%$loop)==0) {
					$dc['m'] = (int)(($days+$d)/$loop);
					$day_chores[] = $dc;
					if($dc['m']>0 && isset($c['w']))
						$wtotal += $c['w'];
				}
			}
			$i++;
		}
	}
	usort($day_chores, 'cmp_day_chores');
	
	$rowspan = (count($day_chores)>1) ? ('rowspan="' . count($day_chores) . '" ') : '';
	echo '<tr><td ' . $rowspan . 'class="fr date ' . (is_weekend($d) ? ' weekend' : '') . '">' . date('j M, D', offs_date($d)) . '</td>';
	$wtotal = ($wtotal>0) ? ($wtotal . ' min') : '';
	echo '<td ' . $rowspan . 'class="fr wtotal">' . $wtotal . '</td>';
	if(count($day_chores)==0) {
		echo '<td class="fr none chk">&#10004;</td><td colspan="4" class="fr none">No chores today</td>';
	}
	else {
		$first = true;
		foreach($day_chores as $dc) {
			if(!$first) echo '</tr><tr>';
			$mark = '&nbsp;';
			$cap = $dc['chore']['cap'];
			if($dc['m']==0) {
				$mark = '&#10004;';
				$style = 'done';
				$cap = '<span class="done">' . $cap . '</done>';
			}
			else if($dc['m']<0) {
				$mark = '&#10008;';
				$style = 'out';
				$cap .= ' (' . (-$dc['m']) . ')';
			}
			else if($dc['m']>1) {
				$style = 'rep';
			}
			else {
				$style = '';
			}
			$style = 'fr ' . $style;
			echo '<td class="' . $style . ' chk">' . $mark . '</td>';
			echo '<td class="' . $style . ' cap">' . $cap . '</td>';
			echo '<td class="' . $style . ' d">' . $dc['chore']['d'] . '</td>';
			echo '<td class="' . $style . ' cat">' . $dc['cat'] . '</td>';
			echo '<td class="' . $style . ' w">' . (isset($dc['chore']['w']) ? ($dc['chore']['w'] . ' min') : '') . '</td>';
			$first = false;
		}
	}
	echo '</tr>';
	if($d==0)
		echo '<tr class="head"><td colspan="5">Calendar</td></tr>';
}
?>
</table>
</body>
</html>
