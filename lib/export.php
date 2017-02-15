<?php 
require_once($_SERVER['DOCUMENT_ROOT'] . '/admin/lib/results.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/admin/lib/evaluations.php');

if (isset($_REQUEST['action'])) {
	$action = $_REQUEST['action'];
	$arg = (isset($_REQUEST['argument'])) ? $_REQUEST['argument'] : NULL;

	switch ($action) {
		case 'exportTests':
			//Instance of forum topic
			$program_results = new Results();
			$program_results->Get_Program_TestIds();
			$program_results->CreateExcelReport();
			break;
		case 'exportEvals':
 			$program_evaluations = new Evaluations();
 			$program_evaluations->Get_Eval_Ids();
 			$program_evaluations->CreateExcelReport();
			break;
		default;
			break;
	}

	exit;
} else {
	return false;
}

?>