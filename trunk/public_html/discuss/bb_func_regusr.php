<?php
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004 Paul Puzyrev, Sergei Larionov. www.minibb.net
*/
if (!defined('INCLUDED776')) die ('Fatal error.');

$warning=''; $editable='';
$actionName='register';

if ($user_id==0){

include($pathToFiles.'bb_func_inslng.php');
if(isset($_POST['user_viewemail'])) $user_viewemail=$_POST['user_viewemail'];
$showemailDown=makeValuedDropDown(array(0=>$l_no,1=>$l_yes),'user_viewemail');
if(isset($_POST['user_sorttopics'])) $user_sorttopics=$_POST['user_sorttopics'];
$sorttopicsDown=makeValuedDropDown(array(0=>$l_newAnswers,1=>$l_newTopics),'user_sorttopics');
if(!isset($_POST['language'])) $language=$lang; else $language=$_POST['language'];
$languageDown=makeValuedDropDown($glang,'language');

if(isset($user_usr) and $step==0) $login=$user_usr;
$userTitle=$l_newUserRegister;

switch($step) {
case 1:
if(isset($closeRegister) and $closeRegister==1) {
$_POST['passwd']=substr(ereg_replace("[^0-9A-Za-z]", "A", md5(uniqid(rand()))),0,8);
$_POST['passwd2']=$_POST['passwd'];
}

require($pathToFiles.'bb_func_usrdat.php');

if($DB=='mysql' or $DB=='pgsql') $case='lower'; elseif($DB=='mssql') $case='lcase';

if (db_simpleSelect(0,$Tu,$dbUserId,$dbUserId,'=',1) and !db_simpleSelect(0,$Tu,$dbUserId,$case."({$dbUserSheme['username'][1]})",'=',strtolower(${$dbUserSheme['username'][1]})) and !db_simpleSelect(0,$Tu,$dbUserId,$dbUserSheme['user_email'][1],'=',${$dbUserSheme['user_email'][1]}) and ${$dbUserSheme['username'][1]}!=$admin_usr and ${$dbUserSheme['user_email'][1]}!=$admin_email) {

$act='reg';
require($pathToFiles.'bb_func_checkusr.php');

if ($correct==0) {
${$dbUserDate}='now()';
${$dbUserSheme['user_password'][1]}=md5(${$dbUserSheme['user_password'][1]});
if(isset($registerInactiveUsers) and $registerInactiveUsers) ${$dbUserAct}=0; else ${$dbUserAct}=1;

$ins=insertArray(array($dbUserSheme['username'][1], $dbUserDate, $dbUserSheme['user_password'][1], $dbUserSheme['user_email'][1], $dbUserSheme['user_icq'][1], $dbUserSheme['user_website'][1], $dbUserSheme['user_occ'][1], $dbUserSheme['user_from'][1], $dbUserSheme['user_interest'][1], $dbUserSheme['user_viewemail'][1], $dbUserSheme['user_sorttopics'][1], $dbUserSheme['language'][1], $dbUserAct, $dbUserSheme['user_custom1'][1], $dbUserSheme['user_custom2'][1], $dbUserSheme['user_custom3'][1]),$Tu);
if ($ins==0) {

if (($emailusers==1 OR (isset($closeRegister) and $closeRegister==1)) and $genEmailDisable!=1){
$emailMsg=ParseTpl(makeUp('email_user_register'));
$sub=explode('SUBJECT>>', $emailMsg); $sub=explode('<<', $sub[1]); $emailMsg=trim($sub[1]); $sub=$sub[0];
sendMail(${$dbUserSheme['user_email'][2]}, $sub, $emailMsg, $admin_email, $admin_email);
}

if ($emailadmin==1 and $genEmailDisable!=1) {
$emailMsg=ParseTpl(makeUp('email_admin_userregister'));
$sub=explode('SUBJECT>>', $emailMsg); $sub=explode('<<', $sub[1]); $emailMsg=trim($sub[1]); $sub=$sub[0];
sendMail($admin_email, $sub, $emailMsg, ${$dbUserSheme['user_email'][2]}, $admin_email);
}

$title.=$l_userRegistered;
$errorMSG=$l_thankYouReg;
$correctErr=$l_goToLogin;
$tpl=makeUp('main_warning');
}
else {
$title.=$l_itseemserror;
$errorMSG=$l_itseemserror;
$correctErr=$backErrorLink;
$tpl=makeUp('main_warning');
}
}
else {
if (!isset($l_userErrors[$correct])) $l_userErrors[$correct]=$l_undefined;
$warning=$l_errorUserData.": <span class=warning>{$l_userErrors[$correct]}</span>";
$title.=$l_errorUserData;
$tpl=makeUp('user_dataform');
if(isset($closeRegister) and $closeRegister==1) $tpl=preg_replace("#<!--PASSWORD-->(.*)<!--/PASSWORD-->#is",'',$tpl);
}
}
else {
$title.=$l_errorUserExists;
$warning=$l_errorUserData.': <span class=warning>'.$l_errorUserExists.'</span>';
$tpl=makeUp('user_dataform');
if(isset($closeRegister) and $closeRegister==1) $tpl=preg_replace("#<!--PASSWORD-->(.*)<!--/PASSWORD-->#is",'',$tpl);
}
echo load_header(); echo ParseTpl($tpl); return;
break;

default:
$title.=$l_newUserRegister;
$tpl=makeUp('user_dataform');
if(isset($closeRegister) and $closeRegister==1) $tpl=preg_replace("#<!--PASSWORD-->(.*)<!--/PASSWORD-->#is",'',$tpl);
echo load_header(); echo ParseTpl($tpl); return;
}

}
else {
$title.=$l_userRegistered;
$errorMSG=$l_userRegistered;
$correctErr=$backErrorLink;
echo load_header(); echo ParseTpl(makeUp('main_warning')); return;
}
?>