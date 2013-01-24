<?php

// get the configuration information
require_once('./sites/default/settings.php');
$url = parse_url($db_url);

//echo '<pre>'; print_r($url); echo '</pre>';
// open database connection
$link = mysql_connect($url['host'], $url['user'], $url['pass']);
if (!$link) {
    die('Could not connect: ' . mysql_error());
}

@mysql_select_db(str_replace('/', '', $url['path'])) or die( "Unable to select database");
//echo 'Connected successfully';
//read lmorse_drpl1 pcal_content_type_2date_registry table for entries with emails
$sql = mysql_query("SELECT `field_pick_date_value`, `field_pick_date2_value`, `field_pick_date_choice_value`, `field_pick_date_email_value`, `field_pick_date_subscribe_check_value` FROM `pcal_content_type_2date_registry` WHERE `field_pick_date_email_value` IS NOT NULL AND `field_pick_date_subscribe_check_value` =  'Subscribe'");
$userinfo = array();

while ($row_user = mysql_fetch_assoc($sql)) {
    $userinfo[] = $row_user;
}
//echo '<pre>'; print_r($userinfo); echo '</pre>';

if (count($userinfo) > 0) {

	$seconds_in_week = 604800;
	$weeks_pregnant_39 = 40;
	$now = strtotime("now");

	foreach ($userinfo AS $user) {
	
		//echo $user['field_pick_date_choice_value'] . '<br />';
		// calculate the number of weeks pregnant
		switch ($user['field_pick_date_choice_value'])
		{
			case 'dday':
				$date = substr($user['field_pick_date_value'], 0, 10);
				$dday_time = strtotime($date);
				$weeks_passed = ($dday_time - $now);
				$weeks_till_dday = ($weeks_passed > 0) ? floor($weeks_passed / $seconds_in_week) : $weeks_pregnant_39 ;
				$weeks_pregnant = ($weeks_till_dday < $weeks_pregnant_39) ? $weeks_pregnant_39 - $weeks_till_dday : $weeks_pregnant_39;
				break;
				
			case 'mensday':
				$date = substr($user['field_pick_date2_value'], 0, 10);
				$mensday_time = strtotime($user['field_pick_date2_value']);
				$weeks_passed = ($now - $mensday_time);
				$weeks_pregnant = ($weeks_passed > 0) ? floor($weeks_passed / $seconds_in_week) : $weeks_pregnant_39 ;
				break;
				
			default:
				break;
		}
		
		
		echo "EMail: {$user['field_pick_date_email_value']}, Selection:  {$user['field_pick_date_choice_value']}, Date: $date , Number of weeks pregnant: $weeks_pregnant<br />";
	}
}


// send emails if appropriate
mysql_close($link);