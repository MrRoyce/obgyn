<?php

/** Error reporting */
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);
date_default_timezone_set('America/New_York');

define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');

function send_mail($message) {

	$subject = 'Week Number ' . $message->week;
	
	$headers   = array();
	$headers[] = "MIME-Version: 1.0";
	$headers[] = "Content-type: text/plain; charset=iso-8859-1";
	$headers[] = "From: Sender Name <sender@domain.com>";  // TODO get this
	$headers[] = "Reply-To: Recipient Name <receiver@domain3.com>";  // TODO get this
	$headers[] = "Subject: " . $subject ;
	$headers[] = "X-Mailer: PHP/".phpversion();

	mail($message->to, $subject, $message->email, implode("\r\n", $headers));
}

/** Include PHPExcel */
require_once './Classes/PHPExcel/Reader/Excel5.php';

$objReader = new PHPExcel_Reader_Excel5(); 
$objReader->setReadDataOnly(true); 
$objPHPExcel = $objReader->load("obgyn_mesage.xls");

$i = 0;

foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
	//echo 'Worksheet - ' , $worksheet->getTitle() , EOL;

	foreach ($worksheet->getRowIterator() as $row) {
		
		if ($i) {
			$message = new stdClass;
			
			$cellIterator = $row->getCellIterator();
			$cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
			
			foreach ($cellIterator as $cell) {
				if (!is_null($cell)) {
					//echo '        Cell - ' , $cell->getColumn() , ' - ' , $cell->getCalculatedValue() , EOL;
					switch ($cell->getColumn())
					{
						case 'C':  // week number
							$message->week = $cell->getCalculatedValue();
							break;
							
						case 'D':  // email address
							$message->to = $cell->getCalculatedValue();
							break;
							
						case 'E':  // body of message
							$message->email = $cell->getCalculatedValue();
							break;
						
						default:
							break;
					}
				}
			}
			
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
		} // skip 1st row
		$i++;
	}  // e/o loop to get each row
}
