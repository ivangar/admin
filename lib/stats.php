<?php 
require_once($_SERVER['DOCUMENT_ROOT'] . '/admin/lib/metrics.php');

if (isset($_REQUEST['action'])) {
	$action = $_REQUEST['action'];
	$arg = (isset($_REQUEST['argument'])) ? $_REQUEST['argument'] : NULL;

	switch ($action) {
		case 'tests_data':
			//Instance of forum topic
			$program_results = new Metrics();
			$results = $program_results->Set_Results('AIT_Pre_01');
			fb($results);
			echo "var myTransportedArrayJson = " . json_encode($results);
			break;
		default;
			break;
	}

	exit;
} else {
	return false;
}

?>