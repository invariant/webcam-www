<?php
#error_reporting(0);
date_default_timezone_set('UTC');
require_once 'database.inc';
require_once 'data.inc';

define('ROOT_DIR', str_replace('view.php', '', __FILE__));
define('ROOT_URL', str_replace('view.php', '', $_SERVER['PHP_SELF']));
define('MOTION_THRESHOLD', 25);



// connect to database
mysql_connect($db_hostname, $db_username, $db_password);
mysql_select_db($db_database);

function nearest_to_time($target_time, $allowed_distance) {
	$result=mysql_query("SELECT `id`, ABS( `time` - '$target_time' ) AS `distance` FROM `pictures` WHERE `status` = 1 ORDER BY `distance` LIMIT 1");
	if ($result) {
		$row=mysql_fetch_assoc($result);
		$dist=$row['distance'];	
		if ($dist<=abs($allowed_distance)) {
			return $row['id'];
		}	
	}
	return 0;
}

function off_num($current_id, $num, $need_motion=false, $return_time=false) {
	$absnum=abs($num);
	$motion_threshold=MOTION_THRESHOLD;
	$motion_sql =$need_motion?"AND `difference` > $motion_threshold":"";
	if ($num<0) {
		$query="SELECT `id`, `time` FROM `pictures` WHERE `id` < $current_id AND `status` = 1 $motion_sql ORDER BY `id` DESC LIMIT $absnum";
	}
	else {
		$query="SELECT `id`, `time` FROM `pictures` WHERE `id` > $current_id AND `status` = 1 $motion_sql ORDER BY `id` LIMIT $absnum";
	}
	$result=mysql_query($query);
	if ($result) {
		$row=mysql_fetch_assoc($result);
		return $return_time?intval($row['time']):intval($row['id']);
	}
	return 0;
}

// echo "<pre>\n";
// var_dump($_SERVER);
// echo "</pre>\n";

$output_format=key_exists('format', $_REQUEST)?$_REQUEST['format']:'html';

// deal with "nearest" request
if (key_exists('nearest', $_REQUEST)) {
	$near_time=intval($_REQUEST['nearest']);
	$id=nearest_to_time($near_time, 86400);
	if ($id>0) {
		header('Location: '.ROOT_URL.$id);
		exit;		
	}
}

$page_data=array();

if (key_exists('picture', $_REQUEST)) {
	$page_data['latest']=false;
	$query="SELECT * FROM `pictures` WHERE `status` > 0 AND `id` = '" . intval($_REQUEST['picture']) . "';";
}
else {
	$page_data['latest']=true;
	$query="SELECT * FROM `pictures` WHERE `status` > 0 ORDER BY `id` DESC;";
}

$result=mysql_query($query);
if ($result) {
	$row=mysql_fetch_assoc($result);
	if ($row) {	
		$page_data['number']=intval($row['id']);		
		$page_data['time']=intval($row['time']);	
		$page_data['difference']=intval($row['difference']);	
		$page_data['brightness']=intval($row['brightness']);			
		$page_data['status']=intval($row['status']);					
	}
}

if (!$page_data['number']) {
	goto end;
}

// generate picture file name
$file="pictures/".gmdate('Y-m-d', $page_data['time'])."/{$page_data['number']}.jpg";
$page_data['image_url']=ROOT_URL.$file;
$page_data['root']=ROOT_URL;
$page_data['have_picture']=file_exists(ROOT_DIR.$file)&&($page_data['status']==1);

// derive more fields from data we have
$page_data['time_string']=$page_data['time']>0?date('D j M Y, H:i:s \G\M\T', $page_data['time']) : 'Unknown';
$page_data['motion']=intval($page_data['difference']>MOTION_THRESHOLD);

// brightness description
if ($page_data['brightness']>=400000) {
	$page_data['brightness_description']="very bright";
}
else if ($page_data['brightness']>=200000) {
	$page_data['brightness_description']="bright";
}
else if ($page_data['brightness']>=100000) {
	$page_data['brightness_description']="fairly bright";
}
else if ($page_data['brightness']>=50000) {
	$page_data['brightness_description']="dim";
}
else {
	$page_data['brightness_description']="dark";
}

$page_data['motion_description']=$page_data['motion']?'motion':'no motion';

$page_data['title']=$page_data['latest']?"Latest Picture":"Picture No. {$page_data['number']}";
$page_data['info']="difference: {$page_data['difference']} ({$page_data['motion_description']}), brightness: {$page_data['brightness']} ({$page_data['brightness_description']})";

$page_data['prev_motion_time']=intval(off_num($page_data['number'], -1, true, true));
$page_data['next_motion_time']=intval(off_num($page_data['number'], 1, true, true));

function make_offset_link($offset) {	
	global $page_data;
	$target=$page_data['time']-$offset;
	$nearest=nearest_to_time($target, $offset/2);
	if ($nearest>0) {
		return "{$page_data['root']}$nearest";	
	}
	return "";
}

function make_id_offset_link($offset, $need_motion=false) {
	global $page_data;
	$tgt=off_num($page_data['number'], $offset, $need_motion);
	if ($tgt>0) {
		return "{$page_data['root']}$tgt";
	}
	return "";
}

function make_latest_link() {
	global $page_data;
	if (!$page_data['latest']) {
		return $page_data['root'];	
	}	
	return "";
}

function make_first_link() {
	global $page_data;
	$first=off_num(0,1);
	if ($first!=$page_data['number']) {
		return "{$page_data['root']}$first";	
	}	
	return "";
}

function echo_link_if($link, $title, $id='', $last)
{
	$elem=strlen($link)>0?"a":'span';
	$style='';
	$spacer=$last?"":"&nbsp;&nbsp;&nbsp;\n";
	$href=strlen($link)>0?"href='$link'":'';	
	if (strlen($id)>0) {
		echo "<$elem id='$id' $style $href>$title</$elem>$spacer";
	}
	else {
		echo "<$elem>$title</$elem>$spacer";
	}
}

function echo_links($titles) {
	global $page_data;
	$count=0;
	foreach ($titles as $key => $title) {
		$link=key_exists($key, $page_data['links'])?$page_data['links'][$key]:'';
		echo_link_if($link, htmlentities($title), $key, ++$count>=sizeof($titles));
	}
}

$page_data['links']=array(
	'first'=>make_first_link(),
	"back24h"=>make_offset_link(3600*24),
	"back4h"=>make_offset_link(3600*4),
	"back1h"=>make_offset_link(3600),
	"back10m"=>make_offset_link(600),
	'back1'=>make_id_offset_link(-1),
	'fwd1'=>make_id_offset_link(1),
	"fwd10m"=>make_offset_link(-600),
	"fwd1h"=>make_offset_link(-3600),
	"fwd4h"=>make_offset_link(-3400*4),
	"fwd24h"=>make_offset_link(-3600*24),
	'latest'=>make_latest_link(),
	'backmotion'=>make_id_offset_link(-1, true),
	'fwdmotion'=>make_id_offset_link(1, true)
);

$time_start=intval($page_data['time']/86400)*86400;
$time_end=$time_start+86400;
$time_data=timeData($time_start, $time_end);

end:
if ($output_format==='json') {
	if ($page_data['number']) {
		$output=json_encode($page_data);
	}
	else {
		$output=json_encode(array('status'=>'not found'));	
		http_response_code(404);		
	}
	header('Content-type: application/json; charset=utf-8');		
	header('Cache-control: max-age=5');			
}
else if ($output_format==='html') {
	if ($page_data['have_picture']) {
		ob_start();
		require ROOT_DIR.'template.html';	
		$output=ob_get_clean();		
		header('Cache-control: max-age=5');		
	}
	else {
		ob_start();
		require ROOT_DIR.'404.php';
		$output=ob_get_clean();
		http_response_code(404);
		header('Cache-control: max-age=0');
	}
	header('Content-type: text/html; charset=utf-8');			
}
echo $output;
?>