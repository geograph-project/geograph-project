<?php
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004 Paul Puzyrev, Sergei Larionov. www.minibb.net
*/

//use our own authentication first...
require_once('geograph/global.inc.php');
init_session();

if (empty($CONF['forums'])) {
	$smarty = new GeographPage;
        $smarty->display('static_404.tpl');
        exit;	
}

if (isset($CONF['curtail_level']) && $CONF['curtail_level'] > 9 ) {
	header("HTTP/1.1 503 Service Unavailable");
	die("server busy, please try later");
}

if (!isset($_GET['forum']) || $_GET['forum'] != $CONF['forum_gallery'])
	$USER->mustHavePerm("basic");

if (empty($CONF['forums'])) {
	if (!$USER->hasPerm("admin") && $_GET['forum'] == $CONF['forum_gallery']) {
		if (!empty($_GET['topic'])) {
			header("Location: /gallery/__".intval($_GET['topic']));
		} else {
			header("Location: /gallery/");
		}
		exit;
	}
	$USER->mustHavePerm("admin");
}

$noSphinx=empty($CONF['sphinx_host']);
$isModerator=$GLOBALS['USER']->hasPerm('moderator');
$isTicketMod=$GLOBALS['USER']->hasPerm('ticketmod');
$isAdmin=$GLOBALS['USER']->hasPerm('admin');
$isModAdmin=$isMod||$isTicketMod||$isAdmin;

$static_host = $CONF['STATIC_HOST'];
$static_url = "http://{$CONF['STATIC_HOST']}/discuss";

customGZipHandlerStart();

//most of everthing below is standard minibb stuff

$currY=date('Y');

function get_microtime() {
$mtime=explode(' ',microtime());
return $mtime[1]+$mtime[0];
}

$starttime=get_microtime();

$queryStr=(isset($_POST['queryStr'])?rawurlencode(rawurldecode($_POST['queryStr'])):rawurlencode($_SERVER['QUERY_STRING']));

if(isset($logged_admin)) unset($logged_admin);
if(isset($isMod)) unset($isMod);
if(isset($user_id)) unset($user_id);
if(isset($langu)) unset($langu);

define ('INCLUDED776',1);

include ('./setup_options.php');

$indexphp=(!isset($GLOBALS['indexphp'])?'index.php':$GLOBALS['indexphp']);

if($useSessions) 
{ 
	$sessname=ini_get('session.name');
	if($sessname=='') 
		$sessname='PHPSESSID';
	
	//we already did this for the geograph session handling
	//session_start();
	
	if(!isset($$sessname)) 
	{ 
		$indexphp.=SID.'&'; 
		$bb_admin.=SID.'&'; 
	} else { 
		$indexphp.="{$sessname}=".$$sessname.'&'; 
		$bb_admin.="{$sessname}=".$$sessname.'&'; 
	}
}




include ($pathToFiles.'setup_'.$DB.'.php');
include ($pathToFiles.'bb_codes.php');
include ($pathToFiles.'bb_cookie.php');
include ($pathToFiles.'bb_functions.php');
include ($pathToFiles.'bb_specials.php');

/* Main stuff */
$loginError=0;
$title=$sitename.' - ';

$forum=0;
$action='';
$topic=0;

if(isset($_GET['gridref'])) {
	$gridref=$_GET['gridref']; 
	$forum = $CONF['forum_gridsquare'];
	

	$result=mysql_query("SELECT topic_id FROM $Tt WHERE forum_id = $forum AND topic_title = '".mysql_escape_string($gridref)."'",$GLOBALS['minibb_link']);
	$currentgridreftopics = mysql_num_rows($result);
		
	if ($currentgridreftopics == 1) {
		$action = "vthread";
		$topic = mysql_result($result,0);		
	} else {
		$action = "vtopic";
	}
	
} else $gridref='';

if(!isset($user_id)) $user_id=0;
if(isset($_GET['page'])) $page=$_GET['page']; elseif(isset($_POST['page'])) $page=$_POST['page']; else $page=0;
if(isset($_GET['forum'])) $forum=$_GET['forum']; elseif(isset($_POST['forum'])) $forum=$_POST['forum']; 
if(isset($_GET['topic'])) $topic=$_GET['topic']; elseif(isset($_POST['topic'])) $topic=$_POST['topic'];

if (isset($_POST['action'])) $action=$_POST['action']; elseif (isset($_GET['action'])) $action=$_GET['action'];

if ($forum == $CONF['forum_submittedarticles'] || $forum == $CONF['forum_gallery']) {
	$viewmaxreplys=10;
}

$forum+=0;
$user_id+=0;
$topic+=0;
$page+=0;
$user_usr='';

$l_adminpanel_link='';
$reqTxt=0;

/* Predefining variables */
$sortingTopics+=0;

if (isset($_GET['sortBy'])) {
$sortBy=$_GET['sortBy']; $sdef=1;
} else {
$sortBy=$sortingTopics; $sdef=0;
}

if (!($sortBy==1 or $sortBy==0)) $sortBy=$sortingTopics;

if (($action=='deltopic' or $action=='delmsg2' or $action=='movetopic2') and isset($dy) and $dy==2) $action='vthread';

if (isset($_POST['mode']) and $_POST['mode']=='login') require($pathToFiles.'bb_func_login.php');

if ($loginError==0) {

if(isset($_GET['mode']) and $_GET['mode']=='logout') {
if($useSessions) { session_unregister('minimalistBBSession'); $indexphp=preg_replace("#".$sessname."=.+&#",'',$indexphp);}
deleteMyCookie();
if(isset($metaLocation)) { $meta_relocate="{$main_url}/{$indexphp}"; echo ParseTpl(makeUp($metaLocation)); exit; } else { header("Location: {$main_url}/{$indexphp}"); exit; }
}

user_logged_in();
if($user_id!=0 and isset($langu) and $langu=str_replace(array('.','/','\\'),'',$langu) and file_exists($pathToFiles."lang/{$langu}.php")) $lang=$langu;
elseif($user_id==0 and isset($_GET['setlang']) and $setlang=str_replace(array('.','/','\\'),'',$_GET['setlang']) and file_exists($pathToFiles."lang/{$_GET['setlang']}.php")) {$lang=$setlang; $indexphp.='setlang='.$setlang.'&';}

include ($pathToFiles."lang/$lang.php");

if(isset($GLOBALS['user_activity']) and $GLOBALS['user_activity']==0) $forb=1;

else{

if ($user_id!=0) {
if($sdef==1) $user_sort=$sortBy;
$loginLogout=ParseTpl(makeUp('user_logged_in'));
$user_logging=$loginLogout;
}
else {
if (!isset($_GET['forum']) || $_GET['forum'] != $CONF['forum_gallery'])
	$USER->mustHavePerm("admin");

if($sdef==0) $user_sort=$sortingTopics; else $user_sort=$sortBy;
$loginLogout=ParseTpl(makeUp('user_login_form'));
if(!in_array($action,array('registernew','register','sendpass','sendpass2'))) $user_logging=ParseTpl(makeUp('user_login_only_form')); else $user_logging='';
}

if(!isset($user_sort)) $user_sort=0;
if($user_sort==0) { $sortByNew=1; $sortedByT=$l_newAnswers; $sortByT=$l_newTopics; }
else { $sortByNew=0; $sortedByT=$l_newTopics; $sortByT=$l_newAnswers; }

/* Protected forums stuff */
if(isset($_POST['allForums']) and $_POST['allForums']==$protectWholeForumPwd) {
$allForums=md5($protectWholeForumPwd);
if($useSessions and !session_is_registered('allForums')) { session_register('allForums'); $_SESSION['allForums']=$allForums; }
setcookie($cookiename.'allForumsPwd','',(time() - 2592000),$cookiepath,$cookiedomain,$cookiesecure);
setcookie($cookiename.'allForumsPwd', $allForums);
if(isset($metaLocation)) { $meta_relocate="{$main_url}/{$indexphp}{$queryStr}"; echo ParseTpl(makeUp($metaLocation));
exit; } else header("Location: {$main_url}/{$indexphp}{$queryStr}");
}
elseif (!isset($_POST['allForums']) and isset($_COOKIE[$cookiename.'allForumsPwd'])) { $allForums=$_COOKIE[$cookiename.'allForumsPwd']; }
elseif (!isset($_POST['allForums']) and !isset($_COOKIE[$cookiename.'allForumsPwd']) and isset($_SESSION['allForums'])) $allForums=$_SESSION['allForums'];
else $allForums='';

if ($protectWholeForum==1) {
if ($allForums!=md5($protectWholeForumPwd)) {
$title=$sitename." :: ".$l_forumProtected;
echo ParseTpl(makeUp('protect_forums')); exit;
}
}

if(!isset($logged_admin)) $logged_admin=0;

if ($logged_admin==1) {
$l_adminpanel_link='<span class=txtNr><a href="'.$bb_admin.'">'.$l_adminpanel.'</a></span><br>';
}
else $l_adminpanel_link='';

if($action=='vthread' || $action=='pthread'){
$topicData=db_simpleSelect(0,$Tt,'topic_title, topic_status, topic_poster, topic_poster_name, forum_id, posts_count, sticky, topic_views','topic_id','=',$topic);
if($topicData and $topicData[4]!=$forum) $forum=$topicData[4];
if ($forum == $CONF['forum_submittedarticles'] || $forum == $CONF['forum_gallery']) {
	$viewmaxreplys=10;
}
unset($result);unset($countRes);
}

$isMod=($forum!=0 and isset($mods) and isset($mods[$forum]) and in_array($user_id,$mods[$forum]))?1:0;

}//forb

/* Private, archive and post-only forums stuff */
if(!isset($forb)) $forb=0;

if ($user_id!=1 and $forum!=0) {
if (isset($clForums) and in_array($forum, $clForums)) {
if (isset($clForumsUsers[$forum]) and !in_array($user_id,$clForumsUsers[$forum])) $forb=1;
}
if (isset($roForums) and in_array($forum, $roForums) and $isMod!=1) {
if (in_array($action, array('pthread', 'ptopic', 'editmsg', 'editmsg2', 'delmsg', 'delmsg2', 'locktopic', 'unlocktopic', 'deltopic', 'movetopic', 'movetopic2', 'sticky', 'unsticky'))) $forb=1;
}
if (isset($poForums) and in_array($forum, $poForums) and $isMod!=1){
if ($action!='' and !in_array($action,array('vthread', 'vtopic', 'pthread', 'editmsg', 'editmsg2'))) $forb=1;
}
}

if ($forb==1) {
$title.=$l_accessDenied;
echo load_header();
$errorMSG=$l_privateForum; $l_returntoforums=''; $correctErr='';

echo ParseTpl(makeUp('main_warning'));
$l_loadingtime='';

if ($USER->hasPerm("basic")) {
	$user_login = ParseTpl(makeUp('main_footer_loggedin'));
} else {
	$user_login = ParseTpl(makeUp('main_footer_login'));
}
echo ParseTpl(makeUp('main_footer'));
exit;
}
/* End stuff */

/* Banned IPs/IDs stuff */
$thisIp=getIP();
$cen=explode('.', $thisIp);

if(isset($cen[0]) and isset($cen[1]) and isset($cen[2])){ 
$thisIpMask[0]=$cen[0].'.'.$cen[1].'.'.$cen[2].'.+'; 
$thisIpMask[1]=$cen[0].'.'.$cen[1].'.+'; 
} 
else { 
$thisIpMask[0]='0.0.0.+';
$thisIpMask[1]='0.0.0.+';
}

if (db_ipCheck($thisIp,$thisIpMask,$user_id)) {
$title=$sitename." :: ".$l_accessDenied;
echo ParseTpl(makeUp('main_access_denied')); exit;
}

include ($pathToFiles.'bb_plugins.php');

/* Main actions */
$backErrorLink="<a href=\"JavaScript:history.back(-1)\">$l_back</a>";

if($action=='pthread') {if($reqTxt!=1)require($pathToFiles.'bb_func_txt.php');require($pathToFiles.'bb_func_pthread.php');}
elseif($action=='ptopic') {if($reqTxt!=1)require($pathToFiles.'bb_func_txt.php');require($pathToFiles.'bb_func_ptopic.php');}

if($action=='pthread') {
$page=-1;
if (!isset($errorMSG)) {
if(isset($anchor)) $anchor='#'.$anchor; else $anchor='';
if(isset($metaLocation)) {
$meta_relocate="{$main_url}/{$indexphp}action=vthread&forum=$forum&topic=$topic&page=$page{$anchor}"; echo ParseTpl(makeUp($metaLocation)); exit; } else header("Location: {$main_url}/{$indexphp}action=vthread&forum=$forum&topic=$topic&page=$page{$anchor}");
}
}

elseif($action=='vthread') require($pathToFiles.'bb_func_vthread.php');

elseif($action=='vtopic') {
if(isset($redthread) and is_array($redthread) and isset($redthread[$forum])) {
if(isset($metaLocation)) {
$meta_relocate="{$main_url}/{$indexphp}action=vthread&forum=$forum&topic={$redthread[$forum]}"; echo ParseTpl(makeUp($metaLocation)); exit;
} else 
header("Location: {$main_url}/{$indexphp}action=vthread&forum=$forum&topic={$redthread[$forum]}");
}
else require($pathToFiles.'bb_func_vtopic.php');
}

elseif($action=='ptopic') {
$page=0;
if (!isset($errorMSG)) {
if(isset($metaLocation)) {
$meta_relocate="{$main_url}/{$indexphp}action=vthread&forum={$forum}&topic={$topic}"; echo ParseTpl(makeUp($metaLocation)); exit; } else 
header("Location: {$main_url}/{$indexphp}action=vthread&forum=$forum&topic=$topic");
}
}

elseif($action=='vpost') { $pageAnchor=db_searchDeSlice(false,intval($_GET['post'])); header("Location: {$indexphp}action=vthread&forum={$forum}&topic={$topic}&page={$pageAnchor[0]}{$pageAnchor[1]}"); exit;}

elseif($action=='search') {if($reqTxt!=1)require($pathToFiles.'bb_func_txt.php');require($pathToFiles.'bb_func_search.php');}

elseif($action=='wait') {
	if ($user_sort==1) $orderBy='topic_id DESC'; else $orderBy='topic_last_post_id DESC';

	if($cols=db_simpleSelect(0, "$Tt Tt left join geobb_lastviewed Tl on (Tt.topic_id = Tl.topic_id and Tl.user_id = {$USER->user_id})", 'Tt.topic_id, topic_title, topic_poster, topic_poster_name, topic_time, forum_id, posts_count, topic_last_post_id, topic_views, (topic_last_post_id > last_post_id) as isnew, last_post_id','forum_id','!=',strval($CONF['forum_gridsquare']),$orderBy,1,'forum_id','!=',strval($CONF['forum_gallery']))){
		if ($cols[9]) {
			print "<title>Updated Since Last Visit</title>";
			print "<b>Updated Since Last Visit</b>";
			print " - <a href=\"{$main_url}/\">Visit Forum</a>"; 
			exit;
		} elseif (is_null($cols[9])) {
			print "<title>New Thread Since Last Visit</title>";
			print "<b>New Thread Since Last Visit</b>";
			print " - <a href=\"{$main_url}/\">Visit Forum</a>"; 
			exit;
		} 
	}
	print "<title>Geograph - No new Posts</title>";
	$countdown = intval($_GET['countdown']);
	if ($countdown>1) {
		$countdown--;
		print "<META HTTP-EQUIV=\"refresh\" content=\"60;URL={$main_url}/{$indexphp}action=wait&amp;countdown=$countdown\"/>"; 
		print "<b>No new Posts</b> - will check once a minute, this message will change if something is posted.";
	} else {
		print "<b>No new Posts</b> - nothing posted for a while, given up checking. <a href=\"{$main_url}/{$indexphp}action=wait&amp;countdown=100\">Start checking again</a>";
	}
	
	print "<br/><br/><br/><a href=\"/\">homepage</a> | <a href=\"{$main_url}/\">discuss</a>";
	exit;
} elseif($action=='deltopic') require($pathToFiles.'bb_func_deltopic.php');

elseif($action=='locktopic') require($pathToFiles.'bb_func_locktop.php');

elseif($action=='editmsg') {$step=0;require($pathToFiles.'bb_func_editmsg.php');}

elseif($action=='editmsg2') {require($pathToFiles.'bb_func_txt.php');$step=1;require($pathToFiles.'bb_func_editmsg.php');}

elseif($action=='delmsg') {$step=0;require($pathToFiles.'bb_func_delmsg.php');}

elseif($action=='movetopic') {$step=0;require($pathToFiles.'bb_func_movetpc.php');}

elseif($action=='movetopic2') {$step=1;require($pathToFiles.'bb_func_movetpc.php');}

elseif($action=='userinfo')
{
	//require($pathToFiles.'bb_func_usernfo.php');
	$url="http://".$_SERVER['HTTP_HOST']."/profile/".$_GET['user'];
	header("Location: $url");
	exit;
	
}

elseif($action=='sendpass') {$step=0;require($pathToFiles.'bb_func_sendpwd.php');}

elseif($action=='sendpass2') {$step=1;require($pathToFiles.'bb_func_sendpwd.php');}

elseif($action=='confirmpasswd') {if (!isset($confirmCode)) $confirmCode=''; require($pathToFiles.'bb_func_confpwd.php');}

elseif($action=='stats') require($pathToFiles.'bb_func_stats.php');

elseif($action=='manual') require($pathToFiles.'bb_func_man.php');

elseif($action=='registernew' and $enableNewRegistrations) {$step=0;require($pathToFiles.'bb_func_regusr.php');}

elseif($action=='register' and $enableNewRegistrations) {$step=1;require($pathToFiles.'bb_func_regusr.php');}

elseif($action=='prefs' and $enableProfileUpdate) {$step=0;require($pathToFiles.'bb_func_editprf.php');}

elseif($action=='editprefs' and $enableProfileUpdate) {$step=1;require($pathToFiles.'bb_func_editprf.php');}

elseif($action=='unsubscribe') require($pathToFiles.'bb_func_unsub.php');

elseif($action=='subscribe') require($pathToFiles.'bb_func_sub.php');

elseif($action=='sticky') {$status=9;require($pathToFiles.'bb_func_sticky.php');}

elseif($action=='unsticky') {$status=0;require($pathToFiles.'bb_func_sticky.php');}

elseif($action=='viewipuser') {require($pathToFiles.'bb_func_viewip.php');}

elseif($action=='tpl') {
if (isset($tplName) and $tplName!='' and file_exists ($pathToFiles.'templates/'.$tplName.'.html')){
echo load_header(); echo ParseTpl(makeUp($tplName));
}
else {
if(isset($metaLocation)) { $meta_relocate="{$main_url}/{$indexphp}"; echo ParseTpl(makeUp($metaLocation)); exit; } else header("Location: {$main_url}/{$indexphp}");
}
}

elseif($action==''){
if(db_simpleSelect(0,$Tf,'forum_id') and $countRes>0){
if ($viewTopicsIfOnlyOneForum!=1) {
	$showforums = $USER->getForumOption('forums',1);
	
	if (!$showforums) {
		if($cols=db_simpleSelect(0,$Tf,'forum_id, forum_icon')){
			do{
				$fIcon[$cols[0]]=$cols[1];
			}
			while($cols=db_simpleSelect(1));
		}
		$title=$sitename;
		echo load_header();
                print "<div style=\"float:left\"><a href=\"index.php\">Reload</a></div>";
 		print "<div style=\"float:right\"><a href=\"index.php?action=wait&amp;countdown=100\">Watch</a></div>";
		print "<div style=\"text-align:center\">Show <a href=\"index.php?forums=1\">Forum List</a> | View <a href=\"index.php?action=vtopic&amp;forum={$CONF['forum_gridsquare']}\">Recent Grid Square Discussions</a></div>";
	} else {
		require($pathToFiles.'bb_func_vforum.php');
	}
	$save=true;
	if (isset($_GET['latest']) ) {
		$_GET['latest']=min(abs(intval($_GET['latest'])),120);
		if ($_GET['latest'] > 40)
			$save = false;
	}
	$viewlastdiscussions = $USER->getForumOption('latest',$viewlastdiscussions,$save);
	if ($viewlastdiscussions!=0) {
		require($pathToFiles.'bb_func_ldisc.php');
		$listTopics=$list_topics;
		if($list_topics!='') echo ParseTpl(makeUp('main_last_discussions'));
		print "<div style=\"text-align:center\">{$l_show}: ";
		foreach (array(5,10,20,30,40,80,100) as $n) {
			if ($viewlastdiscussions == $n) {
				print "<b>$n</b> ";
			} else {
				print "<a href=\"index.php?latest=$n\">$n</a> ";
			}
		}
		print " {$l_lastDiscussions2}</div>";
	}
}
else require($pathToFiles.'bb_func_vtopic.php');
}
else{
$errorMSG=$l_stillNoForums; $l_returntoforums=''; $correctErr='';
echo load_header(); echo ParseTpl(makeUp('main_warning'));
}
}

}

//Loading footer
$endtime=get_microtime();
$totaltime=sprintf ("%01.3f", ($endtime-$starttime));
if ($USER->hasPerm("basic")) {
	$user_login = ParseTpl(makeUp('main_footer_loggedin'));
} else {
	$user_login = ParseTpl(makeUp('main_footer_login'));
}

if(isset($includeFooter)) include($includeFooter); else echo ParseTpl(makeUp('main_footer'));
?>
