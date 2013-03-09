<?php
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004 Paul Puzyrev, Sergei Larionov. www.minibb.net
*/
define ('INCLUDED776',1);

//use our own authentication first...
require_once('geograph/global.inc.php');
init_session();
$USER->mustHavePerm("admin");

$noSphinx=empty($CONF['sphinx_host']);
$isModerator=$GLOBALS['USER']->hasPerm('moderator');
$isTicketMod=$GLOBALS['USER']->hasPerm('ticketmod');
$isAdmin=$GLOBALS['USER']->hasPerm('admin');
$isModAdmin=$isMod||$isTicketMod||$isAdmin;

$static_host = $CONF['STATIC_HOST']; 

function get_microtime() {
$mtime=explode(' ',microtime());
return $mtime[1]+$mtime[0];
}

$starttime=get_microtime();

if(isset($logged_admin)) unset($logged_admin);
if(isset($isMod)) unset($isMod);
if(isset($user_id)) unset($user_id);
if(isset($langu)) unset($langu);

include ('./setup_options.php');

if(!isset($GLOBALS['indexphp'])) $indexphp='index.php?'; else $indexphp=$GLOBALS['indexphp'];

if($useSessions) {
$oldBbAdmin=$bb_admin;
session_start();
if(!isset($PHPSESSID)) { $sessstr='?'.SID; $indexphp.=SID.'&'; $bb_admin.=SID.'&'; }
else { $indexphp.='PHPSESSID='.$PHPSESSID.'&'; $bb_admin.='PHPSESSID='.$PHPSESSID.'&';}
}

include ($pathToFiles."setup_$DB.php");
include ($pathToFiles.'bb_cookie.php');
include ($pathToFiles."bb_functions.php");
include ($pathToFiles."lang/$lang.php");

if(isset($_POST['mode'])) $mode=$_POST['mode']; elseif(isset($_GET['mode'])) $mode=$_GET['mode']; else $mode='';
if(isset($_POST['action'])) $action=$_POST['action']; elseif(isset($_GET['action'])) $action=$_GET['action']; else $action='';

$l_adminpanel_link='';
$warning='';

$adminPanel=1;

//-----
function get_template_forum_orders($resultVal, $count, $forumID, $l_mysql_error) {
// Get forumorder options
$forumorder='';
for ($i=0; $i<=$count; $i++) {
$a=$i+1;
$forumorder.="<option value=\"".$a."\"";
if ($forumID==$resultVal["forum_id"][$i]) $forumorder.=" selected";
$forumorder.=">".$a."</option>";
}
return $forumorder;
}

//-----
function getForumIcons() {
$iconList='';
if($handle=@opendir($GLOBALS['pathToFiles'].'img/forum_icons')) {
$ss=0;
while (($file=readdir($handle))!==false) {
if ($file != '.' && $file != '..' and (substr(strtolower($file),-3)=='gif' OR substr(strtolower($file),-3)=='jpg' OR substr(strtolower($file),-4)=='jpeg')) {
$iconList.="<a href=\"JavaScript:paste_strinL('{$file}')\" onMouseOver=\"window.status='{$GLOBALS['l_forumIcon']}: {$file}'; return true\"><img src=\"{$GLOBALS['static_url']}/img/forum_icons/{$file}\" border=0 alt=\"{$file}\"></a>&nbsp;&nbsp;";
$ss++;
if ($ss==5) {
$ss=0;
$iconList.="<br>\n";
}
}
}
closedir($handle);
if ($iconList=='') $iconList=$GLOBALS['l_accessDenied'];
}
else $iconList=$GLOBALS['l_accessDenied'];
return $iconList;
}

//-----
function get_forums_fast_preview ($resultVal, $count, $l_mysql_error) {
// Get forums fast order preview in admin panel
$fast='';
for ($i=0; $i<=$count; $i++) {
$fast.="<img src=\"{$GLOBALS['main_url']}/img/forum_icons/".$resultVal["forum_icon"][$i]."\" width=16 height=16 border=0 alt=\"Forum icon\">&nbsp;<b><a href={$GLOBALS['bb_admin']}action=editforum2&amp;forumID=".$resultVal["forum_id"][$i].">".$resultVal["forum_name"][$i]."</a></b> [ORDER: ".$resultVal["forum_order"][$i]."] - <i><span class=txtSm>".$resultVal["forum_desc"][$i]."</span></i><br>";
}
if ($count<1 and $GLOBALS['viewTopicsIfOnlyOneForum']==1) $fast.="<br>".$GLOBALS['l_topicsWillBeDisplayed'];
return $fast;
}

//-----

switch ($mode) {
case 'logout':
if($useSessions) { session_unregister('minimalistBBSession'); session_unset(); session_destroy(); $bb_admin=$oldBbAdmin; }
deleteMyCookie();
if(isset($metaLocation)) { $meta_relocate="{$main_url}/{$bb_admin}"; echo ParseTpl(makeUp($metaLocation));
exit; } else header("Location: {$main_url}/$bb_admin");

case 'login':
if ($mode=='login') {
if (isset($_POST['adminusr']) and $_POST['adminusr']==$admin_usr and isset($_POST['adminpwd']) and $_POST['adminpwd']==$admin_pwd) {

$cook=$admin_usr.'|'.md5($admin_pwd).'|'.$cookieexptime;
if($useSessions) { if(!session_is_registered('minimalistBBSession')) session_register('minimalistBBSession'); $_SESSION['minimalistBBSession']=$cook; } 
deleteMyCookie();
setMyCookie($admin_usr,$admin_pwd,$cookieexptime);
if(isset($metaLocation)) { $meta_relocate="{$main_url}/{$bb_admin}"; echo ParseTpl(makeUp($metaLocation));
exit; } else header("Location: {$main_url}/$bb_admin");
}
else {
$warning=$l_incorrect_login;
}
} // if mode=login, for preventing login checkout

default:

$user_id=0;
user_logged_in();
if(isset($langu) and file_exists($pathToFiles."lang/{$langu}.php")) $lang=$langu;
include ($pathToFiles."lang/$lang.php");

if($logged_admin==1){

$l_adminpanel_link="<p><a href=\"$bb_admin\">".$l_adminpanel."</a></p>";

switch ($action) {
case 'addforum1':
if (!isset($_POST['forumicon'])) $forumicon='default.gif';
if (!isset($_POST['forumname'])) $forumname='';
if (!isset($_POST['forumdesc'])) $forumdesc='';
$iconList=getForumIcons();
$text2=ParseTpl(makeUp('admin_addforum1'));
break;

case 'addforum2':
$iconList=getForumIcons();
$forumname=(isset($_POST['forumname'])?$_POST['forumname']:'');
$forumdesc=(isset($_POST['forumdesc'])?$_POST['forumdesc']:'');
$forumicon=(isset($_POST['forumicon'])?$_POST['forumicon']:'');

if($forumname!='') {

if($forumicon=='') $forumicon='default.gif';

if (file_exists($pathToFiles."img/forum_icons/{$forumicon}")) {

$forumname=trim(str_replace(array("'",'"'),array('&#039;','&quot;'),$forumname));
$forumdesc=trim(str_replace(array("'",'"'),array('&#039;','&quot;'),$forumdesc));

$forum_name=$forumname; $forum_desc=$forumdesc; $forum_icon=$forumicon; $topics_count=0; $posts_count=0;
if($mx=db_simpleSelect(0,$Tf,'forum_order','','','','forum_order DESC',1)) $forum_order=$mx[0]+1; else $forum_order=0;

$er=insertArray(array('forum_name','forum_desc','forum_icon','forum_order','topics_count','posts_count'),$Tf);

if ($er==0) $warning=$l_forum_added; else $warning=$l_itseemserror;
$text2=ParseTpl(makeUp('admin_panel'));
}
else {
$warning=$l_error_addforumicon."'".$forumicon."'";
$text2=ParseTpl(makeUp('admin_addforum1'));
}
}
else {
$warning=$l_error_addforum;
$text2=ParseTpl(makeUp('admin_addforum1'));
}
break;

case 'editforum1':
$forums_to_edit='';

if ($row=db_simpleSelect(0,$Tf,'forum_id, forum_name','','','','forum_order')) {
do $forums_to_edit.="<option value=\"{$row[0]}\">{$row[1]}</option>";
while ($row=db_simpleSelect(1));
$text2=ParseTpl(makeUp('admin_editforum1'));
}
else {
$warning=$l_noforums;
$text2=ParseTpl(makeUp('admin_panel'));
}
break;

case 'editforum2':
if(isset($_POST['forumID'])) $forumID=$_POST['forumID']; elseif(isset($_GET['forumID'])) $forumID=$_GET['forumID']; else $forumID=0;
if ($forumID!=0) {
if ($row=db_simpleSelect(0,$Tf,'forum_id, forum_name, forum_desc, forum_order, forum_icon','','','','forum_order')) {
$a=0;
do {
$resultVal['forum_name'][$a]=$row[1];
$resultVal['forum_desc'][$a]=$row[2];
$resultVal['forum_order'][$a]=$row[3];
$resultVal['forum_id'][$a]=$row[0];
$resultVal['forum_icon'][$a]=$row[4];
$a++;
}
while($row=db_simpleSelect(1));

$forumorder=get_template_forum_orders($resultVal, $a-1, $forumID, $l_mysql_error);
$forumsPreview=get_forums_fast_preview($resultVal, $a-1, $l_mysql_error);
unset($resultVal);
}

if ($row=db_simpleSelect(0,$Tf,'forum_name, forum_desc, forum_icon','forum_id','=',$forumID)) {

$forumname=$row[0];
$forumdesc=$row[1];
$forumicon=$row[2];
$iconList=getForumIcons();

$text2=ParseTpl(makeUp('admin_editforum2'));
}
else {
$warning=$l_noforums;
$text2=ParseTpl(makeUp('admin_panel'));
}
}
else {
$warning=$l_noforums;
$text2=ParseTpl(makeUp('admin_panel'));
}
break;

case 'editforum3':
$forumname=(isset($_POST['forumname'])?$_POST['forumname']:'');
$forumdesc=(isset($_POST['forumdesc'])?$_POST['forumdesc']:'');
$forumicon=(isset($_POST['forumicon'])?$_POST['forumicon']:'');
$forum_order=(isset($_POST['forum_order'])?$_POST['forum_order']:0);
$forumID=(isset($_POST['forumID'])?$_POST['forumID']:0);

$forumname=trim(str_replace(array("'",'"','\&quot;','\&#039;'),array('&#039;','&quot;','&quot;','&#039;'),$forumname));
$forumdesc=trim(str_replace(array("'",'"','\&quot;','\&#039;'),array('&#039;','&quot;','&quot;','&#039;'),$forumdesc));

if (!isset($_POST['deleteforum'])) {

if ($forumname!='') {
if ($forumicon=='') $forumicon='default.gif';

if (!file_exists($pathToFiles."img/forum_icons/{$forumicon}")) {
$warning=$l_error_addforumicon."'".$forumicon."'";
}
else {

$forum_name=$forumname; $forum_desc=$forumdesc; $forum_icon=$forumicon;
$fs=updateArray(array('forum_name','forum_desc','forum_icon','forum_order'),$Tf,'forum_id',$forumID);

if($fs>0) $warning=$l_forumUpdated; else $warning=$l_prefsNotUpdated;
}
} // if forum name is set
else {
$warning=$l_error_addforum;
}
if ($row=db_simpleSelect(0,$Tf,'forum_id, forum_name, forum_desc, forum_order, forum_icon','','','','forum_order')) {
$a=0;
do {
$resultVal['forum_name'][$a]=$row[1];
$resultVal['forum_desc'][$a]=$row[2];
$resultVal['forum_order'][$a]=$row[3];
$resultVal['forum_id'][$a]=$row[0];
$resultVal['forum_icon'][$a]=$row[4];
if ($row[0]==$forumID) { $forumname=$row[1]; $forumdesc=$row[2]; }
$a++;
}
while($row=db_simpleSelect(1));

$forumorder=get_template_forum_orders($resultVal, $a-1, $forumID, $l_mysql_error);
$forumsPreview=get_forums_fast_preview($resultVal, $a-1, $l_mysql_error);
unset($resultVal);

$iconList=getForumIcons();

}
$text2=ParseTpl(makeUp('admin_editforum2'));
}
else {

$aff=0;

if($rrr=db_simpleSelect(0,"$Tt,$Ts","$Tt.topic_id","$Tt.forum_id",'=',$forumID,'','',"$Tt.topic_id",'=',"$Ts.topic_id")){
$ord='';
do $ord.="topic_id={$rrr[0]} or "; while($rrr=db_simpleSelect(1));
$ord=substr($ord,0,strlen($ord)-4);
$aff+=db_delete($Ts,$ord,'','');
}

$aff+=db_delete($Tf,'forum_id','=',$forumID);
$aff+=db_delete($Tt,'forum_id','=',$forumID);
$aff+=db_delete($Tp,'forum_id','=',$forumID);

if ($aff>0) $warning=$l_forumdeleted." (\"$forumname\") - $l_del $aff $l_rows"; else $warning=$l_itseemserror;
$text2=ParseTpl(makeUp('admin_panel'));
}
break;

case 'removeuser1':
$userID=(isset($_GET['userID'])?$_GET['userID']:'');
$text2=ParseTpl(makeUp('admin_removeuser1'));
break;

case 'removeuser2':
$userID=(isset($_POST['userID'])?$_POST['userID']:0);

if ($userID==0 or !db_simpleSelect(0,$Tu,$dbUserId,$dbUserId,'=',$userID) or $userID==1 or $userID==0) $warning=$l_cantDeleteUser;
else {
if (db_delete($Tu,$dbUserId,'=',$userID)) $warning=$l_userDeleted." (".$userID.")"; else $warning=$l_userNotDeleted." (".$userID.")";

/*Delete from sendMails*/
db_delete($Ts,$dbUserId,'=',$userID);

if (isset($_POST['removemessages'])) {
//set_time_limit(0);

$aff=0;
/*Deleting user messages from posts and topics table. Topics - delete also all associated posts*/
if($rrr=db_simpleSelect(0,$Tt,'topic_id','topic_poster','=',$userID)){
$ord='';
do $ord.="topic_id={$rrr[0]} or "; while($rrr=db_simpleSelect(1));
$ord=substr($ord,0,strlen($ord)-4);
$aff+=db_delete($Tp,$ord,'','');
$aff+=db_delete($Tt,$ord,'','');
}

/* Posts only */
if($rrr=db_simpleSelect(0,$Tp,'DISTINCT topic_id','poster_id','=',$userID)){
do{
$topic_id=$rrr[0];
$aff+=db_delete($Tp,'topic_id','=',$topic_id,'poster_id','=',$userID);
db_topicPosts($topic_id,$Tt,$Tp);
$RES1=$result;
$CNT1=$countRes;
if($lp=db_simpleSelect(0,$Tp,'post_id','topic_id','=',$topic_id,'post_id DESC',1)){
$topic_last_post_id=$lp[0];
$fs=updateArray(array('topic_last_post_id'),$Tt,'topic_id',$topic_id);
$aff+=$fs;
}
$result=$RES1;
$countRes=$CNT1;
}
while($rrr=db_simpleSelect(1));
}

/* Update forums posts, topics amount */
if($res=db_simpleSelect(0,$Tf,'forum_id')){
do{
db_forumReplies($res[0],$Tp,$Tf);
db_forumTopics($res[0],$Tt,$Tf);
}
while($res=db_simpleSelect(1));
}

if ($aff>0) $warning.="<br>".$l_userMsgsDeleted; else $warning.="<br>".$l_userMsgsNotDeleted;
}
else {
/*Make user posts as anonymous*/
$aff=0;
$poster_id=0; $topic_poster=0;
$aff+=updateArray(array('poster_id'),$Tp,'poster_id',$userID);
$aff+=updateArray(array('topic_poster'),$Tt,'topic_poster',$userID);
if ($aff>0) $warning.="<br>".$l_userUpdated0; else $warning.="<br>".$l_userNotUpdated0;
}

}

$text2=ParseTpl(makeUp('admin_panel'));
break;

case 'delsendmails1':
if (!isset($_POST['warning'])) $warning='';
if (!isset($_POST['delemail'])) $delemail='';
$text2=ParseTpl(makeUp('admin_sendmails1'));
break;

case 'delsendmails2':
$delemail=(isset($_POST['delemail'])?$_POST['delemail']:'');

if($delemail!='' and $rw=db_simpleSelect(0,$Tu,$dbUserId,$dbUserSheme['user_email'][1],'=',$delemail)) {
$fs=db_delete($Ts,'user_id','=',$rw[0]);
$row=$delemail;
}
elseif($delemail=='') {
$fs=db_delete($Ts);
$row='ALL';
}
else {
$warning=$l_emailNotExists;
$text2=ParseTpl(makeUp('admin_sendmails1'));
break;
}

$warning=$l_completed." ($row)";
$text2=ParseTpl(makeUp('admin_panel'));
break;

case 'restoreData':
${$dbUserSheme['username'][1]}=$admin_usr;
${$dbUserSheme['user_password'][1]}=md5($admin_pwd);
${$dbUserSheme['user_email'][1]}=$admin_email;
${$dbUserDate}='now()';
$fields=array($dbUserSheme['username'][1],$dbUserSheme['user_password'][1],$dbUserSheme['user_email'][1]);
if($res=db_simpleSelect(0,$Tu,$dbUserId,$dbUserId,'=',1)) {$ins=1; $fs=updateArray($fields,$Tu,$dbUserId,1); }
else {$fields[]=$dbUserDate; $fields[]=$dbUserId; ${$dbUserId}=1; $ins=0; $fs=insertArray($fields,$Tu); }
if (($fs>0 and $ins==1) OR ($fs==0 and $ins==0)) $warning=$l_prefsUpdated; else $warning=$l_prefsNotUpdated;
$text2=ParseTpl(makeUp('admin_panel'));
break;

case 'banUsr1':
$warning='';
$banip=(isset($_GET['banip'])?$_GET['banip']:'');
$text2=ParseTpl(makeUp('admin_banusr1'));
break;

case 'banUsr2':
$warning='';
$banip=(isset($_POST['banip'])?$_POST['banip']:'');
if (preg_match("/^[0-9.+]+$/", $banip) and trim($banip)!=0) {
$thisIp=$banip; $thisIpMask=array($banip,$banip);
if(db_ipCheck($thisIp,$thisIpMask,$user_id)) $warning=$l_IpExists;
else{
$fs=insertArray(array('banip'),$Tb);
$warning=($fs==0?$l_IpBanned:$l_mysql_error);
}
$text2=makeUp('admin_panel');
}
else{
$warning=$l_incorrectIp;
$text2=makeUp('admin_banusr1');
}
$text2=ParseTpl($text2);
break;

case 'deleteban1':
$warning='';
$banipID='';
$bannedIPs='';
if ($banned=db_simpleSelect(0,$Tb,'id,banip','','','','banip')) {
do $bannedIPs.='<input type=checkbox name=banip['.$banned[0].']>&nbsp;&nbsp;'.$banned[1]."<br>\n";
while($banned=db_simpleSelect(1));
$text2=makeUp('admin_deleteban1');
}
else {
$warning=$l_noBans;
$text2=makeUp('admin_panel');
}
$text2=ParseTpl($text2);
break;

case 'deleteban2':
$banip=(isset($_POST['banip'])?$_POST['banip']:array());
$i=0;
$row=0;
if (sizeof($banip)>0) {
while (list($key)=each($banip)) {
$delban[$i]=$key;
$i++;
}
$xtr=getClForums($delban,'','','id','or','=');
$row=db_delete($Tb,$xtr);
}
$warning=$l_completed.' ('.$row.')';
$text2=ParseTpl(makeUp('admin_panel'));
break;

case 'exportemails':
if (db_simpleSelect(0,$Tu,$dbUserId,$dbUserId,'!=',1)) { $text2=makeUp('admin_export_emails'); }
else { $warning=$l_accessDenied; $text2=makeUp('admin_panel'); }
$text2=ParseTpl($text2);
break;

case 'exportemails2':
if ($row=db_simpleSelect(0,$Tu,$dbUserSheme['username'][1].','.$dbUserSheme['user_email'][1],$dbUserId,'!=',1,$dbUserId) and isset($_POST['expEmail'])) {
$cont='';
do {
$cont.=$row[1];
if (isset($_POST['expLogin'])) {
if ($_POST['separate']=='comma') $sep=','; else $sep=chr(9);
$cont.=$sep.$row[0];
}
if ($_POST['screen']==1) $cont.='<br>'; else $cont.="\n";
}
while ($row=db_simpleSelect(1));

if ($_POST['screen']==0) {
header("Content-Type: DUMP/unknown");
header("Content-Disposition: attachment; filename=".str_replace(' ', '_', $sitename)."_emails.txt");
}
echo $cont; exit;
}
$text2=ParseTpl(makeUp('admin_panel'));
break;

case 'searchusers':
$searchus='id';
$whatDropDown=makeValuedDropDown(array('id'=>'ID','login'=>$l_sub_name,'email'=>$l_email,'inactive'=>$l_inactiveUsers,'registr'=>$l_haventReg),'searchus');
$warning='';
$text2=ParseTpl(makeUp('admin_searchusers'));
break;

case 'searchusers2':

if(isset($_GET['delsel']) and isset($_GET['delus']) and sizeof($_GET['delus'])>0) {
$newarr=array();
foreach($_GET['delus'] as $dl) if($dl!=1) $newarr[]=$dl;
$xtr=getClForums($newarr,'','',$dbUserId,'or','=');
$row=db_delete($Tu,$xtr);
$row=db_delete($Ts,$xtr);
}

$tR=makeUp('admin_searchusersres');
if(isset($_GET['searchus'])) $searchus=$_GET['searchus']; elseif(isset($_POST['searchus'])) $searchus=$_POST['searchus']; else $searchus='';
if(isset($_GET['whatus'])) $whatus=$_GET['whatus']; elseif(isset($_POST['whatus'])) $whatus=$_POST['whatus']; else $whatus='';
$page=(isset($_GET['page'])?$_GET['page']:0);

$whatDropDown=makeValuedDropDown(array('id'=>'ID','login'=>$l_sub_name,'email'=>$l_email,'inactive'=>$l_inactiveUsers,'registr'=>$l_haventReg),'searchus');

if($whatus=='' and $searchus!='inactive' and $searchus!='registr' and $num=db_simpleSelect(0,$Tu,'count(*)')){
/* All users */
$num=$num[0];
$makeLim=makeLim($page,$num,$viewmaxsearch);
$pageNav=pageNav($page,$num,"{$bb_admin}action=searchusers2&amp;whatus=&amp;page=",$viewmaxsearch,FALSE);

if ($row=db_simpleSelect(0,$Tu,$dbUserId.','.$dbUserSheme['username'][1].','.$dbUserDate.','.$dbUserSheme['user_password'][1].','.$dbUserSheme['user_email'][1],'','','',$dbUserId,$makeLim)){
$Results='';
do {
$RES1=$result;
$CNT1=$countRes;
if ($lRepl=db_simpleSelect(0,$Tp,'post_time','poster_id','=',$row[0],'post_id DESC',1)) $lReplies=convert_date($lRepl[0]); else $lReplies='<u>???</u>';
$Rest=$tR;
$rDate=convert_date($row[2]);
$Results.=ParseTpl($Rest);
$result=$RES1;
$countRes=$CNT1;
}
while ($row=db_simpleSelect(1));
}
$warning=$l_recordsFound.' '.$num;
}

elseif ($searchus=='inactive'){
/* Determine all inactive users, who haven't posted ANYTHING */
$makeLim='';
$num=0;
if ($num=db_inactiveUsers(0,'count(*)')) $num=$num[0];
$makeLim=makeLim($page,$num,$viewmaxsearch);
$pageNav=pageNav($page,$num,"{$bb_admin}action=searchusers2&amp;whatus={$whatus}&amp;searchus=inactive&amp;page=",$viewmaxsearch,FALSE);

if ($row=db_inactiveUsers(0,'*')){
$Results='';
$tot=0;
do {
$delCheckBox="<input type=checkbox name=delus[] value={$row[0]}>&nbsp;";
$Rest=$tR;
$lReplies='---';
$rDate=convert_date($row[2]);
$Results.=ParseTpl($Rest);
$tot++;
}
while($row=db_inactiveUsers(1));
$Results.=<<<out
<script language="JavaScript">
<!--
function turnAllLayers(sw){
var el=document.searchForm.elements;
var len=el.length;
for(var i=0;i<len;i++){
if (el[i].name.substring(0,5)=='delus'){el[i].checked=sw}
}
}
//-->
</script>
<input type=submit name=delsel value="{$l_delete}" class=inputButton>
<input type=button value="+" onClick="turnAllLayers(true);" class=inputButton>
<input type=button value="-" onClick="turnAllLayers(false);" class=inputButton>
out;
}
$warning=$l_recordsFound.' '.$num;
}

elseif ($searchus=='email' OR $searchus=='login'){
$tot=0;
$whatx=($searchus=='email'?$dbUserSheme['user_email'][1]:$dbUserSheme['username'][1]);
if($row=db_simpleSelect(0,$Tu,$dbUserId.','.$dbUserSheme['username'][1].','.$dbUserDate.','.$dbUserSheme['user_password'][1].','.$dbUserSheme['user_email'][1],$whatx,'=',$whatus)){
$Results='';
do {
$user=$row[0];
$RES1=$result;
$CNT1=$countRes;
if ($lRepl=db_simpleSelect(0,$Tp,'post_time','poster_id','=',$row[0],'post_id DESC',1)) $lReplies=convert_date($lRepl[0]); else $lReplies='<u>???</u>';
$Rest=$tR;
$rDate=convert_date($row[2]);
$Results.=ParseTpl($Rest);
$tot++;
$result=$RES1;
$countRes=$CNT1;
}
while ($row=db_simpleSelect(1));
}
$warning=$l_recordsFound.' '.$tot;
}

elseif ($searchus=='registr') {
$num=0;
if (!preg_match("/^[12][019][0-9][0-9]-[01][0-9]-[0123][0-9]$/", $whatus)) $warning=$l_wrongData;
else{
$less=$whatus.' 00:00:00';
if($row=db_deadUsers(0,$less)){
$num=$countRes;
$makeLim=makeLim($page,$num,$viewmaxsearch);
$pageNav=pageNav($page,$num,"{$bb_admin}action=searchusers2&amp;whatus={$whatus}&amp;searchus=registr&amp;page=",$viewmaxsearch,FALSE);
$Results='';
$row=db_deadUsers(0,$less);
do{
$Rest=$tR;
$rDate=convert_date($row[2]);
$lReplies=$row[5];
$Results.=ParseTpl($Rest);
}
while($row=db_deadUsers(1,$less));
}
$warning=$l_recordsFound.' '.$num;
}
}

else{
$tot=0;
if($row=db_simpleSelect(0,$Tu,$dbUserId.','.$dbUserSheme['username'][1].','.$dbUserDate.','.$dbUserSheme['user_password'][1].','.$dbUserSheme['user_email'][1],$dbUserId,'=',$whatus)){
$Results=makeUp('admin_searchusersres');
$rDate=convert_date($row[2]);
if ($lRepl=db_simpleSelect(0,$Tp,'post_time','poster_id','=',$row[0],'post_id DESC',1)) $lReplies=convert_date($lRepl[0]); else $lReplies='<u>???</u>';
$tot++;
$Results=ParseTpl($Results);
}
$warning=$l_recordsFound.' '.$tot;
}
$text2=ParseTpl(makeUp('admin_searchusers'));
break;

case 'viewsubs':
$topic=(isset($_GET['topic'])?$_GET['topic']:0);
$text2='';
if($tt=db_simpleSelect(0,$Tt,'topic_title','topic_id','=',$topic)){
$topicTitle=$tt[0];
$listSubs='';

if ($row=db_simpleSelect(0,"$Ts,$Tu","$Ts.id,$Ts.user_id,$Tu.{$dbUserSheme['username'][1]},$Tu.{$dbUserSheme['user_email'][1]}",'topic_id','=',$topic,'','',"$Ts.user_id",'=',"$Tu.$dbUserId")){
$listSubs="<form action=\"$bb_admin\" method=post class=formStyle><input type=hidden name=action value=\"viewsubs2\">
<input type=hidden name=topic value=\"$topic\">";
do {
$listSubs.="<br><input type=checkbox name=selsub[] value={$row[0]}><span class=txtSm><a href=\"{$main_url}/{$indexphp}action=userinfo&user={$row[1]}\">{$row[2]}</a> (<a href=\"mailto:{$row[3]}\">{$row[3]}</a>)</span>\n";
}
while ($row=db_simpleSelect(1));
$listSubs.="<p><input type=submit value=\"$l_deletePost\" class=inputButton></form>\n";
}

$text2=ParseTpl(makeUp('admin_viewsubs'));
}
break;

case 'viewsubs2':
$fs=0;
if(isset($_POST['selsub']) and sizeof($_POST['selsub'])>0){
$xtr=getClForums($_POST['selsub'],'','','id','or','=');
$fs=db_delete($Ts,$xtr);
}
$errorMSG=$l_subscriptions.': '.$l_del.' '.$fs.' '.$l_rows;
$correctErr="<a href=\"{$bb_admin}action=viewsubs&topic={$_POST['topic']}\">$l_back</a>";
$text2=ParseTpl(makeUp('main_warning'));
break;

default:
$warning='';
$text2=ParseTpl(makeUp('admin_panel'));
} // end of switch
}
else {
if (!$warning) $warning=$l_enter_admin_login;
$text2=ParseTpl(makeUp('admin_login'));
}

} // end of switch

echo load_header();
echo $text2;

$endtime=get_microtime();
$totaltime=sprintf ("%01.3f", ($endtime-$starttime));
$currY=date('Y');
if(isset($includeFooter)) include($includeFooter); else echo ParseTpl(makeUp('main_footer'));
?>
