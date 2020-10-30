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

$result=mysql_query("select COUNT(poster_id) from geobb_posts WHERE poster_id = {$USER->user_id}");
$postcount = mysql_result($result,0);
if (!$result || $postcount === '1' || empty($postcount)) {

unset($USER->db);
ob_start();
print "View: http://www.geograph.org.uk/discuss/index.php?&action=vthread&topic=$topic\n";
print "Reports: http://{$_SERVER['HTTP_HOST']}/admin/discuss_reports.php?topic_id=$topic\n\n";
var_dump(mysql_result($result,0));
print_r($_POST);
print_r($USER);
$con = ob_get_clean();

        $check = file_get_contents($CONF['spam_url']."&ip=".getRemoteIP());

	if (empty($check))
		$check = "[error] ".$http_response_header;

	$con = "CHECK: $check\n\n $con";

	if (strpos($check,'<appears>yes</appears>') !== FALSE) {

		$db = GeographDatabaseConnection(false);

	        $tcols = '`'.implode('`,`',$db->getCol("DESCRIBE geobb_topics")).'`';
	        $pcols = '`'.implode('`,`',$db->getCol("DESCRIBE geobb_posts")).'`';

		$u = array();
		$u['post_id'] = $topic_last_post_id;
		$u['topic_id'] = $topic;
		$u['type'] = 'spam';
		$u['comment'] = $check;
                $u['user_id'] = $USER->user_id;

                $db->Execute('INSERT INTO discuss_report SET created=NOW(),`'.implode('` = ?, `',array_keys($u)).'` = ?',array_values($u));
		$report_id = mysql_insert_id();

                $r = intval($report_id);
		$w = "report_id = $r";
                $i = "$r AS report_id";

                $t = "topic_id = $topic";

		$sqls = array();

		if (empty($_POST['topicTitle'])) {
			$p = "post_id = $topic_last_post_id";

                        $sqls[] = "REPLACE INTO geobb_posts_quar SELECT $pcols,$i FROM geobb_posts WHERE $t AND $p";
                        $sqls[] = "DELETE FROM geobb_posts WHERE $t AND $p";

			$sqls[] = "INSERT INTO discuss_report_log SET $w, user_id = {$USER->user_id}, action = 'delete_post'";

			$sqls[] = "UPDATE geobb_topics SET topic_last_post_id = (SELECT MAX(post_id) FROM geobb_posts AS t1 WHERE $t),posts_count=(SELECT COUNT(*) FROM geobb_posts AS t2 WHERE $t) WHERE $t";

		} else {
	                $sqls[] = "REPLACE INTO geobb_topics_quar SELECT $tcols,$i FROM geobb_topics WHERE $t";
	                $sqls[] = "DELETE FROM geobb_topics WHERE $t";
	                $sqls[] = "REPLACE INTO geobb_posts_quar SELECT $pcols,$i FROM geobb_posts WHERE $t";
        	        $sqls[] = "DELETE FROM geobb_posts WHERE $t";

			$sqls[] = "INSERT INTO discuss_report_log SET $w, user_id = {$USER->user_id}, action = 'delete_thread'";
 		}
                $db->Execute("LOCK TABLES geobb_topics WRITE, geobb_posts WRITE, discuss_report WRITE, geobb_posts AS t1 WRITE, geobb_posts AS t2 WRITE,
                                        geobb_topics_quar WRITE, geobb_posts_quar WRITE, discuss_report_log WRITE");
                foreach ($sqls as $sql) {
                        #print "<pre>$sql</pre>";
                        $db->Execute($sql);
                        #print "Affected: ".$db->Affected_Rows();
                }
                $db->Execute("UNLOCK TABLES");

		$data = "Suspected Spam (Please review the message below, and if it is NOT spam, then use the link above to reinstate the thread. The message has already been deleted by the system):\n\n".print_r($_POST,1);

                $mods=$db->GetCol("select email from user where FIND_IN_SET('forum',rights)>0 AND user_id != 3");

                $subject = "[Geograph Forum Report] for thread #$topic [Automated Report]";
                $body = "http://{$_SERVER['HTTP_HOST']}/admin/discuss_reports.php?topic_id=$topic\n\n";
                $body .= "Time: ".date('r')."\n\n";
                $body .= $data;

                foreach ($mods as $email)
                        mail($email,$subject,$body);

		if(preg_match('/^\w{6} <a href=/',$_POST['postText'])) {
			$db->Execute("UPDATE user SET rights = 'dormant,suspicious' WHERE user_id = {$USER->user_id}");
		}

		$auto_logoff = true;
	} else {
	        mail('geograph@barryhunter.co.uk','[Geograph] FIRST POST!',$con);
	}

}

//fire an event
	require_once('geograph/event.class.php');
	if (empty($auto_logoff))
		new Event(EVENT_NEWREPLY, $topic_last_post_id);
}

if (empty($auto_logoff)) {

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

}//if (empty($auto_logoff))

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
