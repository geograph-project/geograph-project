<?php
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004 Paul Puzyrev, Sergei Larionov. www.minibb.net
*/
if (!defined('INCLUDED776')) die ('Fatal error.');

//can edit articles in forum 6&11 for 5 years 
//nice hack huh? :(
if ($_REQUEST['forum']==6 || $_REQUEST['forum']==11)
	$useredit=86400*365*5;

if(isset($_GET['post'])) $post=$_GET['post']; elseif(isset($_POST['post'])) $post=$_POST['post']; else $post=0;
if(isset($_GET['anchor'])) $anchor=$_GET['anchor']; elseif(isset($_POST['anchor'])) $anchor=$_POST['anchor']; else $anchor=0;

if ((isset($_COOKIE[$cookiename.'Update']) or (isset($_SESSION[$cookiename.'Update']) and $_SESSION[$cookiename.'Update']>time())) and !($user_id==1 or $isMod==1)) {
$errorMSG=$l_antiSpam; $correctErr=$backErrorLink;
$title.=$l_antiSpam;
echo load_header(); echo ParseTpl(makeUp('main_warning')); return;
}

/* Check for: topic&post exist, user time or admin, user allowed or admin */
$userAllow=db_simpleSelect(0,$Tp,'poster_id,post_status','post_id','=',$post);
$whoEdited=(integer) $userAllow[1];
$userAllow=$userAllow[0];

if ($user_id!=0 and $row=db_simpleSelect(0,"$Tp,$Tt","$Tp.post_text, $Tt.topic_title, $Tp.post_time","$Tp.post_id",'=',$post,'','',"$Tp.topic_id",'=',"$Tt.topic_id") and (!isset($useredit) or $useredit==0 or $useredit>(strtotime(date('Y-m-d H:i:s'))-strtotime($row[2])) or $user_id==1 or $isMod==1) and ($userAllow==$user_id or $user_id==1 or $isMod==1) ) {

if ($step!=1 and $step!=0) $step=0;

if (($whoEdited==2 or $whoEdited==3) and !($logged_admin==1 or $isMod==1)) {
$errorMSG=$l_onlyAdminCanEdit; $correctErr=$backErrorLink;
$title.=$l_onlyAdminCanEdit;
echo load_header(); echo ParseTpl(makeUp('main_warning')); return;
}
else {

/*First post?*/
if($frt=db_simpleSelect(0,$Tp,'post_id','topic_id','=',$topic,'post_id',1) and $frt[0]==$post and (($logged_admin==1 or $isMod==1) OR (isset($usersEditTopicTitle) and $usersEditTopicTitle))) $first=TRUE; else $first=FALSE;

if($step==1) {
$errorMSG='';

if (strlen(trim($_POST['postText']))==0 or (isset($_POST['postTopic']) and strlen(trim($_POST['postTopic']))==0)) {
$title.=$l_emptyPost; $errorMSG=$l_emptyPost; $correctErr=$backErrorLink;
}
else {

if(($user_id==1 or $isMod==1) and (isset($_POST['fEdit']) and strlen($_POST['fEdit'])>0)) $fEdit=1; else $fEdit=0;

//Update topic title if admin is logged, if it is first post
if ($first) {
$postTopic=(isset($_POST['postTopic'])?$_POST['postTopic']:'');
$topic_title=textFilter($postTopic,$topic_max_length,$post_word_maxlength,0,1,0,$logged_admin);
$fif=updateArray(array('topic_title'),$Tt,'topic_id',$topic);
if($fif!=0) $errorMSG.=$l_topicTitleUpdated.'<br>';
}

if(($user_id==1 or $isMod==1) and $fEdit==1){
if ($logged_admin==1) $post_status=2;
elseif ($isMod==1) $post_status=3;
}
elseif(($user_id==1 or $isMod==1) and $fEdit==0) $post_status=0;
else $post_status=1;

if (!isset($_POST['disbbcode'])) $disbbcode=FALSE; else $disbbcode=TRUE;
$post_text=textFilter($_POST['postText'],$post_text_maxlength,$post_word_maxlength,1,$disbbcode,1,$logged_admin);
$fif=updateArray(array('post_text','post_status'),$Tp,'post_id',$post);

if ($fif!=0) $errorMSG.=$l_topicTextUpdated."<br>"; 

$title.=$l_editPost;
$correctErr="<a href=\"{$main_url}/{$indexphp}action=vthread&amp;forum=$forum&amp;topic=$topic&amp;page={$page}#{$anchor}\">$l_back</a>";
}

//fire an event
	require_once('geograph/event.class.php');
	new Event('topic_edit', $post);
	
//delete it now as users expect their edit to show up!
	global $memcache;
	$mkey = $post.$_SERVER['HTTP_HOST'];
	$memcache->name_delete('fp',$mkey);
	$mkey = $post."www.geograph.org.uk";
	$memcache->name_delete('fp',$mkey);
	$mkey = $post."www.geograph.ie";
	$memcache->name_delete('fp',$mkey);
	

if ($user_id!=1 and $postRange!=0) {
if($useSessions and !session_is_registered($cookiename.'Update')) { session_register($cookiename.'Update'); $_SESSION[$cookiename.'Update']=time()+$postRange;}
setcookie($cookiename.'Update','',(time() - 2592000),$cookiepath,$cookiedomain,$cookiesecure);
setcookie($cookiename.'Update', 1, time()+$postRange, $cookiepath, $cookiedomain, $cookiesecure); 
}

echo load_header(); echo ParseTpl(makeUp('main_warning')); return;
}

else{
//default edit

$postText=deCodeBB($row[0]);
$postTopic=$row[1];

$l_messageABC=$l_editPost;

if ($first) {
$mainPostForm=ParseTpl(makeUp('tools_edit_topic_title'));
} else $mainPostForm='';

if($user_id==1 or $isMod==1) {
if($whoEdited==2 or $whoEdited==3) $ch='checked'; else $ch='';
$emailCheckBox='<input type=checkbox name=fEdit '.$ch.'> <s>'.$l_edit.'</s>';
} else $emailCheckBox='';
$mainPostForm.=ParseTpl(makeUp('main_post_form'));
$title.=$l_editPost;
echo load_header(); echo ParseTpl(makeUp('tools_edit_post'));
}

}

}
else {
$errorMSG=$l_accessDenied; $correctErr=$backErrorLink;
$title.=$l_accessDenied;
echo load_header(); echo ParseTpl(makeUp('main_warning')); return;
}

?>
