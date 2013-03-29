<?php

if ($_SERVER['SERVER_ADDR']=='127.0.0.1') {
	require_once('./geograph_snub.inc.php');
} else {
	require_once('geograph/global.inc.php');
}

include('./geotrip_func.php');
$db = GeographDatabaseConnection(false);


header("Content-type: application/octet-stream");
header("Content-Disposition: attachment; filename=\"geotrips.csv\"");

  $trk=$db->getAll("select * from geotrips order by id desc");

  print("TripID,Title,SubTitle,UserID,GridimageID,GridReference,TripDate,Updated,Content,SearchID\n");
  foreach ($trk as $trip) {
    if ($trip['title']) $title=effingq($trip['title']);
    else $title=$trip['location'].' from '.$trip['start'];
    if (strlen($trip['descr'])>500) $subtit=substr(effingq($trip['descr']),0,500).'...';
    else $subtit=effingq($trip['descr']);
    $gr=bbox2gr($trip['bbox']);
    print($trip['id'].',"'.$title.'","'.$subtit.'",'.$trip['uid'].','.$trip['img'].','.$gr.','.$trip['date'].',');
    print($trip['updated'].',"'.effingq($trip['descr']).'",'.$trip['search']."\n");
  }

function effingq($text) {
  return str_replace('"','""',$text);
}



