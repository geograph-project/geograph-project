<?php
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004 Paul Puzyrev, Sergei Larionov. www.minibb.net
*/
if (!defined('INCLUDED776')) die ('Fatal error.');

$confirmCode=(isset($_GET['confirmCode'])?$_GET['confirmCode']:'');

if ($confirmCode=='') {
$title.=$l_forbidden; $errorMSG=$l_forbidden; $correctErr='';
}
elseif($curr=db_simpleSelect(0,$Tu,$dbUserNp,$dbUserNk,'=',$confirmCode)) {
${$dbUserSheme['user_password'][1]}=md5($curr[0]); ${$dbUserNk}=''; ${$dbUserNp}='';
$updArr=array($dbUserSheme['user_password'][1],$dbUserNk,$dbUserNp);
$fs=updateArray($updArr,$Tu,$dbUserNk,$confirmCode);
if ($fs>0) {
$title.=$l_passwdUpdate; $errorMSG=$l_passwdUpdate; $correctErr='';
}
else {
$title.=$l_itseemserror; $errorMSG=$l_itseemserror; $correctErr='';
}
}
echo load_header(); echo ParseTpl(makeUp('main_warning'));
?>