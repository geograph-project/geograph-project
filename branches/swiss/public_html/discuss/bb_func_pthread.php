<?php
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004 Paul Puzyrev, Sergei Larionov. www.minibb.net
*/
if (!defined('INCLUDED776')) die ('Fatal error.');

$allowForm=($user_id==1 or $isMod==1);
$c1=(in_array($forum,$clForums) and isset($clForumsUsers[$forum]) and !in_array($user_id,$clForumsUsers[$forum]) and !$allowForm);
$c2=(isset($allForumsReg) and $allForumsReg and $user_id==0);
$c4=(isset($roForums) and in_array($forum, $roForums) and !$allowForm);
$c5=(isset($regUsrForums) and in_array($forum, $regUsrForums) and $user_id==0);

if ($c1 or $c2 or $c4 or $c5) {
$errorMSG=$l_forbidden; $correctErr=$backErrorLink;
$title=$title.$l_forbidden;
echo load_header(); echo ParseTpl(makeUp('main_warning')); return;
}

if(!$user_usr) $user_usr=$l_anonymous;
if(!isset($TT)) $TT='';
if($_POST['postText']=='') $postText=$TT; else $postText=trim($_POST['postText']);

//Check if topic is not locked
if($lckt=db_simpleSelect(0,$Tt,'topic_status','topic_id','=',$topic)) $lckt=$lckt[0]; else $lckt=1;
if((((sizeof($regUsrForums)>0 and in_array($forum,$regUsrForums)) OR (isset($allForumsReg) and $allForumsReg)) and $user_id==0) or $lckt==1 or $lckt==8) {
$errorMSG=$l_forbidden; $correctErr=$backErrorLink;
$title=$title.$l_forbidden;
echo load_header(); echo ParseTpl(makeUp('main_warning')); return;
}
else {

if ($postText=='') {
//Insert user into email notifies if allowed
if (isset($emptySubscribe) and $emptySubscribe and $user_id!=0 and isset($_POST['CheckSendMail']) and emailCheckBox()!='' and substr(emailCheckBox(),0,8)!='<!--U-->') {
$ae=db_simpleSelect(0,$Ts,'count(*)','user_id','=',$user_id,'','','topic_id','=',$topic); $ae=$ae[0];
if($ae==0) { $topic_id=$topic; insertArray(array('user_id','topic_id'),$Ts); }
}
return;
}

if(!isset($_POST['disbbcode'])) $disbbcode=FALSE; else $disbbcode=TRUE;
$postText=textFilter($postText,$post_text_maxlength,$post_word_maxlength,1,$disbbcode,1,$user_id);
$poster_ip=getIP();

//Posting query with anti-spam protection
if($row=db_simpleSelect(0,$Tt,'topic_id','forum_id','=',$forum,'','','topic_id','=',$topic)) {

if($postRange==0) $antiSpam=0; else {
if($user_id==0) $fields=array('poster_ip',$poster_ip); else $fields=array('poster_id',$user_id);
if($antiSpam=db_simpleSelect(0,$Tp,'count(*)',$fields[0],'=',$fields[1],'','','now()-post_time','<',$postRange)) $antiSpam=$antiSpam[0]; else $antiSpam=1;
}

if($user_id==1 or $antiSpam==0) {

$forum_id=$forum;
$topic_id=$topic;
$poster_id=$user_id;
$poster_name=$user_usr;
$post_text=$postText;
$post_time='now()';
$post_status=0;

$inss=insertArray(array('forum_id', 'topic_id', 'poster_id', 'poster_name', 'post_text', 'post_time', 'poster_ip', 'post_status'),$Tp);

if($inss==0){
$topic_last_post_id=$insres;
if(updateArray(array('topic_last_post_id'),$Tt,'topic_id',$topic)>0){
db_forumReplies($forum,$Tp,$Tf);
db_topicPosts($topic,$Tt,$Tp);

//fire an event
	require_once('geograph/event.class.php');
	new Event(EVENT_NEWREPLY, $topic_last_post_id);
}

if ($emailusers==1 or (isset($emailadmposts) and $emailadmposts==1)) {
$topicTitle=db_simpleSelect(0,$Tt,'topic_title','topic_id','=',$topic);
$topicTitle=$topicTitle[0];
$postTextSmall=strip_tags(substr(str_replace(array('<br>','&#039;','&quot;','&amp;','&#036;'), array("\r\n","'",'"','&','$'), $postText), 0, 200)).'...';
$msg=ParseTpl(makeUp('email_reply_notify'));
$sub=explode('SUBJECT>>', $msg); $sub=explode('<<', $sub[1]); $msg=trim($sub[1]); $sub=$sub[0];
}

//Email all users about this reply if allowed
if($emailusers==1) {
if($row=db_sendMails(0,$Tu,$Ts)){
do if($row[0]!='') sendMail($row[0], $sub, $msg, $admin_email, $admin_email);
while($row=db_sendMails(1,$Tu,$Ts));
}
}

//Email admin if allowed
if (isset($emailadmposts) and $emailadmposts==1 and $user_id!=1) {
sendMail($admin_email, $sub, $msg, $admin_email, $admin_email);
}

//Insert user into email notifies if allowed
if (isset($_POST['CheckSendMail']) and emailCheckBox()!='' and substr(emailCheckBox(),0,8)!='<!--U-->') {
$ae=db_simpleSelect(0,$Ts,'count(*)','user_id','=',$user_id,'','','topic_id','=',$topic); $ae=$ae[0];
if($ae==0) { $topic_id=$topic; insertArray(array('user_id','topic_id'),$Ts); }
}

}//inserted post successfully

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

if(isset($themeDesc) and in_array($topic,$themeDesc)) $anchor=1;
else{
$totalPosts=db_simpleSelect(0,$Tt,'posts_count','topic_id','=',$topic);
$vmax=$viewmaxreplys;
$anchor=$totalPosts[0];
if ($anchor>$vmax) { $anchor=$totalPosts[0]-((floor($totalPosts[0]/$vmax))*$vmax); if ($anchor==0) $anchor=$vmax;}
}
}
?>