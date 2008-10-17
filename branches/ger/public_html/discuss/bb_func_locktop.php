<?php
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004 Paul Puzyrev, Sergei Larionov. www.minibb.net
*/
if (!defined('INCLUDED776')) die ('Fatal error.');

if(!isset($_GET['chstat'])) die('Fatal error.'); else $topic_status=$_GET['chstat'];

if($tD=db_simpleSelect(0,$Tt,'topic_status, topic_poster, sticky','topic_id','=',$topic)){
if (($tD[1]==$user_id and $tD[2]!=1 and (($topic_status==0 and $userUnlock==1) or $topic_status==1)) OR $logged_admin==1 OR $isMod==1) {
if(updateArray(array('topic_status'),$Tt,'topic_id',$topic)>0) $errorMSG=(($topic_status==1)?$l_topicLocked:$l_topicUnLocked);
else $errorMSG=$l_itseemserror;
$correctErr="<a href=\"{$main_url}/{$indexphp}action=vthread&amp;forum=$forum&amp;topic=$topic\">$l_back</a>";
}
else {
$errorMSG=$l_forbidden; $correctErr=$backErrorLink;
}

}

$title.=$errorMSG;
echo load_header(); echo ParseTpl(makeUp('main_warning')); return;
?>