<?
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004 Paul Puzyrev, Sergei Larionov. www.minibb.net
*/

$DB=$CONF['db_driver'];

$DBhost=$CONF['db_connect'];
$DBname=$CONF['db_db'];
$DBusr=$CONF['db_user'];
$DBpwd=$CONF['db_pwd'];

$Tf='geobb_forums';
$Tp='geobb_posts';
$Tt='geobb_topics';
$Tu='geobb_users';
$Ts='geobb_send_mails';
$Tb='geobb_banned';

$admin_usr=$CONF['minibb_admin_user'];
$admin_pwd=$CONF['minibb_admin_pwd'];
$admin_email=$CONF['minibb_admin_email'];

if (isset($REVISIONS)) {
	foreach ($REVISIONS as $key => $value) {
		$GLOBALS['revision_'.str_replace(array('/','.'),'',$key)] = ".v$value";
	}
}
$GLOBALS['http_host'] = $_SERVER['HTTP_HOST'];

$bb_admin='bb_admin.php?';

$lang='eng';
$skin='default';
$main_url='http://'.$_SERVER['HTTP_HOST'].'/discuss';
$sitename='Discuss';
$emailadmin=0;
$emailusers=1;
$userRegName='_A-Za-z0-9 ';
$l_sepr='<span style="color:#006699">&nbsp;-&nbsp;</span>';

$post_text_maxlength=32640;
$post_word_maxlength=70;
$topic_max_length=100;
$viewmaxtopic=30;
$viewlastdiscussions=30;
$viewmaxreplys=30;
$viewmaxsearch=30;
$viewpagelim=500;
$viewTopicsIfOnlyOneForum=0;

$protectWholeForum=0;
$protectWholeForumPwd='pwd';

$postRange=20;

$dateFormat='j F Y H:i:s';

$cookiedomain='';
$cookiename='geographbb';
$cookiepath='';
$cookiesecure=FALSE;
$cookie_expires=108000;
$cookie_renew=1800;
$cookielang_exp=2592000;

/* New options for miniBB 1.1 */

$disallowNames=array('Anonymous', 'Fuck', 'Shit');
//$disallowNamesIndex=array('admin'); // 2.0 RC1f

/* New options for miniBB 1.2 */
$sortingTopics=0;
$topStats=4;
$genEmailDisable=0;

/* New options for miniBB 1.3 */
$defDays=60;
$userUnlock=1;

/* New options for miniBB 1.5 */
$emailadmposts=0;
$useredit=86400;

/* New options for miniBB 1.6 */
//$metaLocation='go';
//$closeRegister=1;
//$timeDiff=21600;

/* New options for miniBB 1.7 */
$stats_barWidthLim='31';

/* New options for miniBB 2.0 */

$dbUserSheme=array(
'username'=>array(1,'username','login'),
'user_password'=>array(3,'user_password','passwd'),
'user_email'=>array(4,'user_email','email'),
'user_icq'=>array(5,'user_icq','icq'),
'user_website'=>array(6,'user_website','website'),
'user_occ'=>array(7,'user_occ','occupation'),
'user_from'=>array(8,'user_from','from'),
'user_interest'=>array(9,'user_interest','interest'),
'user_viewemail'=>array(10,'user_viewemail','user_viewemail'),
'user_sorttopics'=>array(11,'user_sorttopics','user_sorttopics'),
'language'=>array(14,'language','language'),
'user_custom1'=>array(16,'user_custom1','user_custom1'),
'user_custom2'=>array(17,'user_custom2','user_custom2'),
'user_custom3'=>array(18,'user_custom3','user_custom3')
);
$dbUserId='user_id';
$dbUserDate='user_regdate'; $dbUserDateKey=2;
$dbUserAct='activity';
$dbUserNp='user_newpasswd';
$dbUserNk='user_newpwdkey';

$enableNewRegistrations=true;
$enableProfileUpdate=true;

$indexphp='index.php?';
$useSessions=true;
$usersEditTopicTitle=true;
$pathToFiles='./';
//$includeHeader='header.php';
//$includeFooter='footer.php';
//$emptySubscribe=TRUE;
//$allForumsReg=TRUE;
//$registerInactiveUsers=TRUE;
//$mod_rewrite=TRUE;
$enableViews=true;

?>
