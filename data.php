<?php
error_reporting(0);
date_default_timezone_set('UTC');
require_once 'database.inc';
require_once 'data.inc';

// connect to database
mysql_connect($db_hostname, $db_username, $db_password);
mysql_select_db($db_database);

// echo "<pre>\n";
// var_dump($_SERVER);
// echo "</pre>\n";

$start=key_exists('start', $_REQUEST)?$_REQUEST['start']:0;
$end=key_exists('end', $_REQUEST)?$_REQUEST['end']:0;
$day=key_exists('day', $_REQUEST)?$_REQUEST['day']:0;

if ($start<=0 && $end<=0) {
	// use today
	$time=time();
	$start=(intval($time/86400)-$day)*86400;
	$end=$start+86400;	
}

header('Content-type: application/json; charset=utf-8');
header('Cache-control: max-age=60');
echo json_encode(timeData($start, $end));
?>