<?php

require_once('geograph/global.inc.php');

header('Access-Control-Allow-Origin: *');
customExpiresHeader(3600);

if (!empty($_GET['gridref'])) {

	$db = GeographDatabaseConnection(true);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	//todo, acccept user_id, and join user_gridsquare too!

	$query = "select grid_reference,last_stat,g.percent_land,g.imagecount,g.has_geographs,has_recent,Place,County,Country,km_ref from gridsquare g left join sphinx_placenames p using (placename_id)
	 where grid_reference = ".$db->Quote($_GET['gridref']);

	$data = $db->getAll($query);
} else {
	$data = array('error'=>$error);
}

outputJSON($data);
