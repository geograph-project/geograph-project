<?php
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004 Paul Puzyrev, Sergei Larionov. www.minibb.net
*/
if (!defined('INCLUDED776')) die ('Fatal error.');

if (!isset($genEmailDisable) or $genEmailDisable!=1){

$newPasswd=''; $confirmCode='';

$email=(isset($_POST['email'])?$_POST['email']:'');

if ($email==$admin_email) $email='';

if ($step!=1) {
$title.=$l_sub_pass;
echo load_header(); echo ParseTpl(makeUp('tools_send_password')); return;
}
else {

if (!($updId=db_simpleSelect(0,$Tu,$dbUserId,$dbUserSheme['user_email'][1],'=',$email))) {
$title.=$l_emailNotExists;
$errorMSG=$l_emailNotExists;
$correctErr=$backErrorLink;
echo load_header(); echo ParseTpl(makeUp('main_warning')); return;
}
else {

${$dbUserNp}=substr(ereg_replace("[^0-9A-Za-z]", "A", md5(uniqid(rand()))),0,8); $newPasswd=${$dbUserNp};
${$dbUserNk}=substr(md5(uniqid(rand())),0,32); $confirmCode=${$dbUserNk};

$updArr=array($dbUserNp,$dbUserNk);
$fs=updateArray($updArr,$Tu,$dbUserId,$updId[0]);

if ($fs>0) {
$msg=ParseTpl(makeUp('email_user_password'));
$sub=explode('SUBJECT>>', $msg); $sub=explode('<<', $sub[1]); $msg=trim($sub[1]); $sub=$sub[0];
sendMail($email, $sub, $msg, $admin_email, $admin_email);
$title.=$l_emailSent;
$errorMSG=$l_emailSent;
$correctErr='';
echo load_header(); echo ParseTpl(makeUp('main_warning')); return;
}
else {
$title.=$l_itseemserror;
$errorMSG=$l_itseemserror;
$correctErr='';
echo load_header(); echo ParseTpl(makeUp('main_warning')); return;
}

}

}

}
else {
$title.=$l_accessDenied;
$errorMSG=$l_accessDenied;
$correctErr='';
echo load_header(); echo ParseTpl(makeUp('main_warning')); return;
}
?>