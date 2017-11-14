<?php
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004 Paul Puzyrev, Sergei Larionov. www.minibb.net
*/

$clForums=array();
$clForumsUsers[]=array();
$roForums=array();
$poForums=array();
$regUsrForums=array();

$userRanks=array();

if($cols=db_simpleSelect(0,'user','user_id,rights,role','role != \'\' OR rights LIKE \'%admin%\' OR rights',' LIKE ','%moderator%')) {
	do {
		if ($cols[2])
			$userRanks[$cols[0]]=$cols[2];
		elseif (strpos($cols[1],'admin') !== FALSE)
			$userRanks[$cols[0]]='Developer';
		elseif (strpos($cols[1],'moderator') !== FALSE)
			$userRanks[$cols[0]]='Moderator';
	} while($cols=db_simpleSelect(1));
}

$mods=array();

$themeDesc = array(3798);

?>
