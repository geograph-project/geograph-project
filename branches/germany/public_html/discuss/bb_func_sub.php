<?php
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004 Paul Puzyrev, Sergei Larionov. www.minibb.net
*/
if (!defined('INCLUDED776')) die ('Fatal error.');

$usrid=(isset($_GET['usrid'])?$_GET['usrid']:0);

if ($topic!=0 and $usrid!=0 and $usrid==$user_id and !($ids=db_simpleSelect(0,$Ts,'id','topic_id','=',$topic,'','','user_id','=',$user_id))) {
$topicU=$topic;

$user_id=$usrid;
$topic_id=$topic; insertArray(array('user_id','topic_id'),$Ts);


$errorMSG=$l_completed; $title.=$l_completed;


}
else {
$title.=$l_accessDenied; $errorMSG=$l_accessDenied;
}

$correctErr='';
echo load_header(); echo ParseTpl(makeUp('main_warning')); return;
?>