<?php
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004 Paul Puzyrev, Sergei Larionov. www.minibb.net
*/
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<HEAD><title>miniBB installation</title>
<LINK href="./bb_default_style.css" type="text/css" rel="STYLESHEET">
</HEAD>
<body>

<?
include ('./setup_options.php');
include ($pathToFiles."setup_$DB.php");
if(!isset($GLOBALS['indexphp'])) $indexphp='index.php?'; else $indexphp=$GLOBALS['indexphp'];
$step=(isset($_GET['step'])?$_GET['step']:'');

$namesArray=array('minibbtable_forums','minibbtable_posts','minibbtable_topics','minibbtable_users','minibbtable_send_mails','minibbtable_banned',"\r\n","\n");
$replNamesArray=array($Tf,$Tp,$Tt,$Tu,$Ts,$Tb,'','');

if (!file_exists("./_install_$DB.sql")) echo "<p>Installation file is missing. Please, check your directory for _install_$DB.sql file!";

else {

switch ($step) {
case 'install':

$errors=0;
$warn='';
$buffer='';

$fd=fopen ($pathToFiles."_install_{$DB}.sql", 'r');
while (!feof($fd)) {
$buffer.=fgets($fd,1024);
if(substr_count($buffer,';')>0) {
$buffer=str_replace($namesArray,$replNamesArray,$buffer);
preg_match("#CREATE TABLE (.+?) \(#i",$buffer,$arr);
$tName=$arr[1];
mysql_query($buffer);
if (mysql_error()) {
$errors++;
$warn.="<div>Creating table {$tName} failed... (".mysql_error().")</div>";
}
else $warn.="<div>Table {$tName} successfully created...</div>";
$buffer='';
}
}

if ($errors==0) {
mysql_query("INSERT INTO $Tu ({$dbUserId}, {$dbUserSheme['username'][1]}, {$dbUserSheme['user_password'][1]}, {$dbUserSheme['user_email'][1]}, {$dbUserSheme['user_viewemail'][1]}, {$dbUserDate}) values (1, '$admin_usr', '".md5($admin_pwd)."', '$admin_email', 0, now())");

if (!mysql_error()) $warn.="<p>Admin data successfully added...</div>";

$warn.="
<p>All tables successfully created! Now you can:
<li><p>Continue with miniBB options (see setup_options.php file)
<li><p><a href=\"{$main_url}/{$bb_admin}action=addforum1\">Create forums</a>
<li><p><a href=\"{$main_url}/{$bb_admin}\">Go to admin panel</a>...
<p>...<a href=\"{$main_url}/{$indexphp}\">and use your miniBB right now!</a> :)
<p><b>Don't forget to DELETE the _install.php file as well as the _install_$DB.sql file from your miniBB directory!
<p>DO IT RIGHT NOW!!!
";
}
else {
$warn.="
<p>There were problems via setup! Possible reasons:
<li><p>It's not allowed for your DB-account to create tables;
<li><p>Login/password for the database 's incorrect;
<li><p>You haven't created the database entered in the setup_options.php file (possibly, you need to do it manually use DB console or admin panel like phpMyAdmin, for ex.);
<li><p>Tables are already created and you can directly <a href=\"{$main_url}/{$indexphp}\">go to forums now</a>.
<p>Please, refer to our manual for more questions, check your setup files, or manually create all DB tables.
<p><b>Don't forget to DELETE the _install.php file as well as the _install_$DB.sql file from your miniBB directory!
<p>DO IT RIGHT NOW!!!
";
}

echo $warn;
break;

default:
echo '
<p>Welcome to miniBB setup!
<p>Before installing, copying or modifying miniBB, please, read the <strong><a href=\"COPYING\">License agreement.</a></strong>
<p>Be sure you have correctly setup your "setup_options.php" file <b>first</b>! Refer to <a href="./templates/manual_eng.html">manual</a> if you are having problems.
<p>If you\'re having <b>really BIG</b> problems with this installation, note that we offer a few paid support plans (basic board installation on your server is only $10). To get paid support, use <a href="http://www.minibb.net/contact.html">this form</a> to contact us. Describe the problem you\'re having, and we\'ll setup the board for you shortly. We\'re also offering other types of <a href="http://www.minibb.net/realsupp.html">paid support</a>. For payments and donations, please go to <a href="http://www.minibb.net">our site</a> and click on "PayPal Donate" banner.
<p>It takes only 1 step to create all necessary database tables. You must have necessary database user privileges for that.  <p><a href="_install.php?step=install">Continue setup</a>&gt;&gt;&gt;';
}

}
?>
</body>
</html>