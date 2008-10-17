<?php
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004 Paul Puzyrev, Sergei Larionov. www.minibb.net
*/
if (!defined('INCLUDED776')) die ('Fatal error.');

$allowForm=($user_id==1 or $isMod==1);
$c1=(in_array($forum,$clForums) and isset($clForumsUsers[$forum]) and !in_array($user_id,$clForumsUsers[$forum]) and !$allowForm);
$c2=(isset($allForumsReg) and $allForumsReg and $user_id==0);
$c3=(isset($poForums) and in_array($forum, $poForums) and !$allowForm);
$c4=(isset($roForums) and in_array($forum, $roForums) and !$allowForm);
$c5=(isset($regUsrForums) and in_array($forum, $regUsrForums) and $user_id==0);

if ($c1 or $c2 or $c3 or $c4 or $c5) {
$errorMSG=$l_forbidden; $correctErr=$backErrorLink;
$title=$title.$l_forbidden;
echo load_header(); echo ParseTpl(makeUp('main_warning')); return;
}

if (!$user_usr) $user_usr=$l_anonymous;

if (trim($_POST['topicTitle'])=='' and trim($_POST['postText'])=='') {
$action='vtopic'; return;
}
elseif (trim($_POST['topicTitle'])==''){
$errorMSG=$l_topiccannotempty; $correctErr=$backErrorLink;
$title.=$l_topiccannotempty;
echo load_header(); echo ParseTpl(makeUp('main_warning')); return;
}
else {
$TT=$_POST['topicTitle'];
$topicTitle=textFilter($_POST['topicTitle'],$topic_max_length,$post_word_maxlength,0,1,0,$user_id);
}

$poster_ip=getIP();

if(db_simpleSelect(0,$Tf,'forum_id','forum_id','=',$forum)) {

if($postRange==0) $antiSpam=0; else {
if($user_id==0) $fields=array('poster_ip',$poster_ip); else $fields=array('poster_id',$user_id);
if($antiSpam=db_simpleSelect(0,$Tp,'count(*)',$fields[0],'=',$fields[1],'','','now()-post_time','<',$postRange)) $antiSpam=$antiSpam[0]; else $antiSpam=1;
}

if ($user_id==1 or $antiSpam==0) {
$topic_title=$topicTitle;
$topic_poster=$user_id;
$topic_poster_name=$user_usr;
$topic_time='now()';
$forum_id=$forum;
$topic_status=0;
$topic_last_post_id=0;
$posts_count=0;
$dll=insertArray(array('topic_title','topic_poster','topic_poster_name','topic_time','forum_id','topic_status','topic_last_post_id','posts_count'),$Tt);
if($dll==0) 
{
	//fire an event
	require_once('geograph/event.class.php');
	new Event(EVENT_NEWTOPIC, $insres);
	
	$topic=$insres;
	db_forumTopics($forum,$Tt,$Tf);
	require($pathToFiles.'bb_func_pthread.php');

}
else {
$errorMSG=$l_mysql_error; $correctErr=$backErrorLink;
$title.=$l_mysql_error;
echo load_header(); echo ParseTpl(makeUp('main_warning')); return;
}
}
else {
$errorMSG=$l_antiSpam; $correctErr=$backErrorLink;
$title.=$l_antiSpam;
echo load_header(); echo ParseTpl(makeUp('main_warning')); return;
}

}
else {
$errorMSG=$l_forbidden; $correctErr=$backErrorLink;
$title.=$l_forbidden;
echo load_header(); echo ParseTpl(makeUp('main_warning')); return;
}
?>