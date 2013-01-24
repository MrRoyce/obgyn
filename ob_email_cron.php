<?php

/** Error reporting */
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);
date_default_timezone_set('America/New_York');

define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');

// get the configuration information
require_once('./sites/default/settings.php');
$url = parse_url($db_url);

require_once('./functions.php');
// open database connection
$link = mysql_connect($url['host'], $url['user'], $url['pass']);
if (!$link) {
	echo mysql_error() . EOL;
	error_log('ERROR - ob_email_cron.php Could not connect to database: ' . mysql_error());
    die('Could not connect: ' . mysql_error());
}

@mysql_select_db(str_replace('/', '', $url['path'])) or die("ERROR - ob_email_cron.php Unable to select database" . mysql_error());

//read lmorse_drpl1 pcal_content_type_2date_registry table for entries with emails
$sql = mysql_query(
	'SELECT `registry`.`field_computed_week_value`, `registry`.`field_pick_date_email_value`, `node`.`timestamp`'
	. '	FROM `pcal_content_type_2date_registry` AS `registry`,'
	. '	`pcal_node_revisions` AS `node`'
	. ' WHERE `registry`.`field_pick_date_email_value` IS NOT NULL'
	. ' AND `registry`.`field_pick_date_subscribe_check_value` = ' . '"Subscribe"'
	. ' AND `registry`.`nid` = `node`.`nid`'
	. ' AND `registry`.`vid` = `node`.`vid`'
	);
$userinfo = array();

while ($row_user = mysql_fetch_assoc($sql)) {
    $userinfo[] = $row_user;
}
mysql_close($link);

if (count($userinfo) > 0) {

	$seconds_in_week = 604800;
	$seconds_in_day = 86400;
	$days_in_week = 7;
	$weeks_pregnant_40 = 40;
	$now = strtotime(date("M d Y"));
	
	$emails = getEmails();  // get the email templates
	$post_script = '<br /><br />Visit us online at <a href="http://www.obgynspb.com" target="_blank">http://www.obgynspb.com</a> and like us on Facebook at <a href="http://www.facebook.com/obgynspecialists" target="_blank">http://www.facebook.com/obgynspecialists</a>.<br />==============================================<br /><br />To Unsubscribe, just reply to this email with the word Unsubscribe in the subject line.';

	foreach ($userinfo AS $user) {
	
		$message = new stdClass;
		
		// calculate the number of weeks pregnant
		$original_date = floor(date($user['timestamp'])/$seconds_in_week);
		$now_date = floor(time()/$seconds_in_week);
		$prego = $user['field_computed_week_value'];
		$weeks_pregnant = $now_date - $original_date + $prego;
		
		//echo "EMail: {$user['field_pick_date_email_value']}, Number of weeks pregnant: $weeks_pregnant<br />";
		$message->week = $weeks_pregnant;
		$message->to = $user['field_pick_date_email_value'];
		$message->email = 'Week Number ' . $weeks_pregnant . '<br /><br />' . $emails[$weeks_pregnant] . $post_script; // add the postscript to the message
		
		$error = false;
			
		if ((isset($message->week)) AND (isset($message->email)) AND (isset($message->to))) {
			// email the message
			$success = send_mail($message);
			if (!($success)) {
				$error = 'ERROR - Could not send email: ' . print_r($message, TRUE);
			}
		} else {
			$error = 'ERROR - Could not get correct email values: ' . print_r($message, TRUE);
		}
		
		if ($error) {
			echo $error . EOL;
			error_log($error);
		}
		
		unset($message);
	}
}

// send emails if appropriate
