<?php
#error_reporting(0);
date_default_timezone_set('UTC');
require_once 'database.inc';

define('ROOT_DIR', str_replace('save_picture.php', '', __FILE__));

$error_message='Unknown error';
$status=500;
$body='';

/*
Generate the license plist for a given name and order number.
*/
function store_picture($data, $time, $difference, $brightness) {
	
	// save the metadatd
	$result=mysql_query("INSERT INTO `pictures` (`time`, `difference`, `brightness`) VALUES ('$time', '$difference', '$brightness');");
	
	// now we have the next picture id, save the file
	if ($result) { 
		$id=mysql_insert_id();

		// make subdir
		$dir=ROOT_DIR.'pictures/'.gmdate('Y-m-d', $time);		
		if (!file_exists($dir)) {
			mkdir($dir, 0777, true);
		}		
		
		// save file
		$file="$dir/$id.jpg";		
		file_put_contents($file, $data);

		// set status field to 1 to indicate complete record
		$result=mysql_query("UPDATE `pictures` SET `status`='1' WHERE id=$id;");
		if ($result) {
			return true;
		}
	}
	return false;
}

// connect to database
$db=mysql_connect($db_hostname, $db_username, $db_password);
if (!$db) {
	$error_message='Unable to connect to MySQL: ' . mysql_error();
	$status=500;
	goto end;
}

// select table
if (!mysql_select_db($db_database)) {
	$error_message='Unable to select database: ' . mysql_error();
	$status=500;
	goto end;
}

$time=$_REQUEST['time'];
if (!(mb_strlen($time)>0)) {
	$time=0;
}

$difference=$_REQUEST['difference'];
if (!(mb_strlen($difference)>0)) {
	$difference=0;
}

$brightness=$_REQUEST['brightness'];
if (!(mb_strlen($brightness)>0)) {
	$brightness=0;
}

$data=$_REQUEST['data'];
if (!(mb_strlen($data)>0)) {
	$status=400;
	$error_message='Invalid or missing "data" parameter.';
	goto end;
}

// generate and store license
if (!store_picture($data, $time, $difference, $brightness)) {
	$status=500;
	$error_message='Failed to store picture: ' . mysql_error();
	goto end;
}	

// success
$status=200;
$error_message='Success';

// set body to error message
$body=$error_message;

// generate the page
end:
header('Content-type: text/plain; charset=utf-8');
if (strlen($error_message)>0) {
	header('X-Error-Message: ' . $error_message);
}
http_response_code($status);
echo $body;

?>