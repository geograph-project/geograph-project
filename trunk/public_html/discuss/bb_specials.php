<?
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004 Paul Puzyrev, Sergei Larionov. www.minibb.net
*/

$clForums=array();
$clForumsUsers[]=array();
$roForums=array();
$poForums=array();
$regUsrForums=array();

$userRanks=array(1=>'Administrator', 2=>'Developer', 3=>'Developer',2407=>'Developer');

if($cols=db_simpleSelect(0,'user','user_id','rights',' LIKE ','%moderator%')) {
do isset($userRanks[$cols[0]]) || ($userRanks[$cols[0]]='Moderator'); while($cols=db_simpleSelect(1));
}

$mods=array();

?>