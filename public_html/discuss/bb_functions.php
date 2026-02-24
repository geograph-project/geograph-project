<?php
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004 Paul Puzyrev, Sergei Larionov. www.minibb.net
*/
$version='2.0 RC2a';

//--------------->
function makeUp($name,$addDir='') {
if($addDir=='') $addDir=$GLOBALS['pathToFiles'].'templates/';
if (substr($name,0,5)=='email') $ext='txt'; else $ext='html';
if (file_exists($addDir."{$name}.{$ext}")) { 
$tpl='';
$fd=fopen ($addDir."{$name}.{$ext}",'r');
while(!feof($fd)) $tpl.=fgets($fd,1024);
fclose ($fd);
}
else die ("TEMPLATE NOT FOUND: $name");
return $tpl;
}

//--------------->
function ParseTpl($tpl){
$qs=array();
$qv=array();
$ex=explode ('{$',$tpl);
for ($i=0; $i<=sizeof($ex); $i++){
if (!empty($ex[$i]) and substr_count($ex[$i],'}')>0) {
$xx=explode('}',$ex[$i]);
if (substr_count($xx[0],'[')>0) {
$clr=explode ('[',$xx[0]); $sp=intval($clr[1]); $clr=$clr[0];
if (!in_array($clr,$qs)) {$qs[]=$clr; }
if(isset($GLOBALS[$clr][$sp])) $to=$GLOBALS[$clr][$sp]; else $to='';
}
else { if(!in_array($xx[0], $qv)) {$qv[]=$xx[0]; }
if(isset($GLOBALS[$xx[0]])) $to=$GLOBALS[$xx[0]]; else $to='';
}
$tpl=str_replace('{$'.$xx[0].'}', $to, $tpl);
}
}
return $tpl;
}

//--------------->
function load_header() {
//we need to load this template separately, because we load page title
if(!isset($GLOBALS['adminPanel'])) $GLOBALS['adminPanel']=0;

if(strlen($GLOBALS['action'])>0||$GLOBALS['adminPanel']==1) {$f=1; $GLOBALS['l_menu'][0]=" <a href=\"{$GLOBALS['main_url']}/{$GLOBALS['indexphp']}\">{$GLOBALS['l_menu'][0]}</a> ";} else {$f=0;$GLOBALS['l_menu'][0]='';}

if($GLOBALS['action']!='stats') $GLOBALS['l_menu'][3]=($f==1?$GLOBALS['l_sepr']:'')." <a href=\"{$GLOBALS['main_url']}/{$GLOBALS['indexphp']}action=stats\">{$GLOBALS['l_menu'][3]}</a> "; else $GLOBALS['l_menu'][3]='';

if($GLOBALS['viewTopicsIfOnlyOneForum']==1 and $GLOBALS['action']=='') $GLOBALS['l_menu'][7]="{$GLOBALS['l_sepr']} <a href=\"#newtopic\">{$GLOBALS['l_menu'][7]}</a> ";

if(isset($GLOBALS['nTop'])&&$GLOBALS['nTop']==1){
if($GLOBALS['action']=='vtopic') $GLOBALS['l_menu'][7]="{$GLOBALS['l_sepr']} <a href=\"#newtopic\">{$GLOBALS['l_menu'][7]}</a> ";
elseif($GLOBALS['action']=='vthread') $GLOBALS['l_menu'][7]="{$GLOBALS['l_sepr']} <a href=\"#newreply\">{$GLOBALS['l_reply']}</a> ";
}
else $GLOBALS['l_menu'][7]='';

if($GLOBALS['action']!='search') $GLOBALS['l_menu'][1]="{$GLOBALS['l_sepr']} <a href=\"{$GLOBALS['main_url']}/{$GLOBALS['indexphp']}action=search\">{$GLOBALS['l_menu'][1]}</a> "; else $GLOBALS['l_menu'][1]='';

if($GLOBALS['action']!='registernew' and $GLOBALS['user_id']==0 and $GLOBALS['adminPanel']!=1 and $GLOBALS['enableNewRegistrations']) $GLOBALS['l_menu'][2]="{$GLOBALS['l_sepr']} <a href=\"{$GLOBALS['main_url']}/{$GLOBALS['indexphp']}action=registernew\">{$GLOBALS['l_menu'][2]}</a> "; else $GLOBALS['l_menu'][2]='';

if($GLOBALS['action']!='manual') $GLOBALS['l_menu'][4]="{$GLOBALS['l_sepr']} <a href=\"{$GLOBALS['main_url']}/{$GLOBALS['indexphp']}action=manual\">{$GLOBALS['l_menu'][4]}</a> "; else $GLOBALS['l_menu'][4]='';
if($GLOBALS['action']!='prefs'&&$GLOBALS['user_id']!=0 and $GLOBALS['enableProfileUpdate']) $GLOBALS['l_menu'][5]="{$GLOBALS['l_sepr']} <a href=\"{$GLOBALS['main_url']}/{$GLOBALS['indexphp']}action=prefs\">{$GLOBALS['l_menu'][5]}</a> "; else $GLOBALS['l_menu'][5]='';

if($GLOBALS['user_id']!=0) $GLOBALS['l_menu'][6]="{$GLOBALS['l_sepr']} <a href=\"{$GLOBALS['main_url']}/{$GLOBALS['indexphp']}mode=logout\">{$GLOBALS['l_menu'][6]}</a> "; else $GLOBALS['l_menu'][6]='';

if (!isset($GLOBALS['title']) or $GLOBALS['title']=='') $GLOBALS['title']=$GLOBALS['sitename'];
if(isset($GLOBALS['includeHeader'])) { include($GLOBALS['includeHeader']); return; }
return ParseTpl(makeUp('main_header'));
}

//--------------->
function getAccess($clForums, $clForumsUsers, $user_id){
$forb=array();
$acc='n';
if ($user_id!=1 and sizeof($clForums)>0){
foreach($clForums as $f){
if (isset($clForumsUsers[$f]) and !in_array($user_id, $clForumsUsers[$f])){
$forb[]=$f; $acc='m';
}
}
}
if ($acc=='m') return $forb; else return $acc;
}

//--------------->
function getIP() {
	if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
		$cf_ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
		if (filter_var($cf_ip, FILTER_VALIDATE_IP)) return $cf_ip;
	}
	if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
		$forward_ip = trim($ips[0]);
		if (filter_var($forward_ip, FILTER_VALIDATE_IP)) return $forward_ip;
	}
	$remote_ip = $_SERVER['REMOTE_ADDR'] ?? '';
	if (filter_var($remote_ip, FILTER_VALIDATE_IP)) return $remote_ip;
	return "0";
}

//--------------->
function convert_date($dateR, $truncate = false){
	$dateFormat = $GLOBALS['dateFormat'];
	$timeR = strtotime($dateR);
	if ($truncate && $timeR < time()-60*60*24*365)
		$dateFormat = "F Y";
	elseif ($truncate && $timeR < time()-60*60*72)
		$dateFormat = "j F Y";
	$engMon=array('January','February','March','April','May','June','July','August','September','October','November','December',' ');
	$months=explode(':', $GLOBALS['l_months']);
	$months[]='&nbsp;';
	if(isset($GLOBALS['timeDiff']) and $GLOBALS['timeDiff']!=0) $timeR = $timeR+$GLOBALS['timeDiff'];
	$dateR=date($dateFormat,$timeR);
	return str_replace($engMon,$months,$dateR);
}

//--------------->
function pageChk($page,$numRows,$viewMax){
	if($numRows>0 and ($page>0 or $page==-1)){
		$max=$numRows/$viewMax;
		if(intval($max)==$max) $max=intval($max)-1; else $max=intval($max);
		if ($page==-1) return $max;
		elseif($page>$max) return $max;
		else return $page;
	}
	else return 0;
}

//--------------->
// $page = current page
// $numRows = total rows
// $url = the base URL (page number gets appended
// $viewMax = items per page
// $navCell = true means its cell based, which shows 3 rather than 9 to start, and also hides Prev/Last
function pageNav($page,$numRows,$url,$viewMax,$navCell){
	$pageNav=''; // Initialize as empty
	if(isset($GLOBALS['mod_rewrite']) and $GLOBALS['mod_rewrite'] and ($GLOBALS['action']=='vtopic' or $GLOBALS['action']=='vthread' or $GLOBALS['action']=='')) $mr='.html'; else $mr='';
	$page=pageChk($page,$numRows,$viewMax);
	$iVal=intval(($numRows-1)/$viewMax); //last page (0-indexed)

	// If only one page or no pages, return empty string
	if($iVal <= 0) {
		return '';
	}

	// Cap $iVal by viewpagelim
	if(isset($GLOBALS['viewpagelim']) && $iVal > $GLOBALS['viewpagelim']){
		$iVal = $GLOBALS['viewpagelim'];
		if($GLOBALS['viewpagelim'] >= 1) $iVal -= 1; // Adjust if viewpagelim is count based
	}

	// If, after potential capping, there's only one page, return empty
	if($iVal <= 0) {
		return '';
	}

	$pageNav='Pages:';

	// "Prev" link
	if($page > 0 && !$navCell) {
		$pageNav.=' <a href="'.$url.($page-1).$mr.'" class="pageNav">&lt;&lt; Prev</a>';
	}

	// "First" page link (Page 1)
	if($page > 0) { // Only show if not on the first page
		$pageNav.=' <a href="'.$url.'0'.$mr.'" class="pageNav" title="Page 1">1</a>';
	} else { // On first page
		if (!$navCell) $pageNav.=' <b class="pageNav pageNavSelected">1</b>';
		else $pageNav.=' <a href="'.$url.'0'.$mr.'" class="pageNav" title="Page 1">1</a>';
	}

	// Ellipsis before previous page link
	// Show if page before current ($page-1) is not page 1 (index 0) and not page 2 (index 1)
	if($page - 1 > 1) {
		$pageNav.=' ...';
	}

	// Page link before current (page $page, 0-indexed)
	// Show if current page is page 2 (index 1) or greater, AND this page ($page-1) is not the first page (0)
	if ($page - 1 > 0 && $page < 1000) {
		$pageNav.=' <a href="'.$url.($page-1).$mr.'" class="pageNav" title="Page '.$page.'">'.$page.'</a>';
	}

	// Current page (page $page+1, 0-indexed)
	// Display current page if it's NOT the first page AND NOT the last page
	// (because first and last are handled by the specific "First" and "Last" page link sections)
	if ($page > 0 && $page < $iVal) {
		if (!$navCell) $pageNav.=' <b class="pageNav pageNavSelected">'.($page+1).'</b>';
		else $pageNav.=' <a href="'.$url.$page.$mr.'" class="pageNav" title="Page '.($page+1).'">'.($page+1).'</a>';
	}

	// Page link after current (page $page+2, 0-indexed)
	// Show if current page is $iVal-2 or less, AND this page ($page+1) is not the last page ($iVal)
	if ($page + 1 < $iVal && $page < 1000) {
		$pageNav.=' <a href="'.$url.($page+1).$mr.'" class="pageNav" title="Page '.($page+2).'">'.($page+2).'</a>';
	}

	// Ellipsis after next page link
	// Show if page after current ($page+1) is less than $iVal-1 (i.e., there's at least one page between $page+1 and $iVal)
	if ($page + 1 < $iVal - 1) {
		$pageNav.=' ...';
	}

	// "Last" page link (Page $iVal+1)
	// Show if current page is not the last page.
	// If it IS the last page, it's handled by the "else" in the "First" page section (modified to be current page == $iVal)
	if ($page < $iVal) {
		$pageNav.=' <a href="'.$url.$iVal.$mr.'" class="pageNav" title="Page '.($iVal+1).'">'.($iVal+1).'</a>';
	} else { // Current page is $iVal (last page)
		// This 'else' complements the '$page > 0' for the first page link.
		// If on last page, it should be bolded if !$navCell
		if (!$navCell) $pageNav.=' <b class="pageNav pageNavSelected">'.($iVal+1).'</b>';
		// If $navCell is true, it was already rendered as a link by the ($page < $iVal) block,
		// but we need to ensure it's rendered if the ($page < $iVal) was false.
		// The first page logic already handles $page==0. This handles $page==$iVal.
		// The $page > 0 && $page < $iVal handles pages in between.
		// So, if $page == $iVal and $navCell is true, it needs to be a link here.
		else $pageNav.=' <a href="'.$url.$iVal.$mr.'" class="pageNav" title="Page '.($iVal+1).'">'.($iVal+1).'</a>';
	}

	// "Next" link
	if($page < $iVal && !$navCell) {
		$pageNav.=' <a href="'.$url.($page+1).$mr.'" class="pageNav">Next &gt;&gt;</a>';
	}

	return $pageNav;
}

//---------------------->
function sendMail($email, $subject, $msg, $from_email, $errors_email) {
// Function sends mail with return-path (if incorrect email TO specifed. Reply-To: and Errors-To: need contain equal addresses!
if (!isset($GLOBALS['genEmailDisable']) or $GLOBALS['genEmailDisable']!=1){
$msg=str_replace("\r\n", "\n", $msg);
$php_version=phpversion();
$from_email="From: $from_email\nReply-To: $errors_email\nErrors-To: $errors_email\nX-Mailer: PHP ver. $php_version";
mail_wrapper($email, $subject, $msg, $from_email);
}
}

//---------------------->
function emailCheckBox() {

$checkEmail='';
if($GLOBALS['genEmailDisable']!=1){

$isInDb=db_simpleSelect(0,$GLOBALS['Ts'],'count(*)','topic_id','=',$GLOBALS['topic'],'','','user_id','=',$GLOBALS['user_id']);
if($isInDb[0]>0) $isInDb=TRUE; else $isInDb=FALSE;

$true0=($GLOBALS['emailusers']==1);
$true1=($GLOBALS['user_id']!=0);
$true2=($GLOBALS['action']=='vtopic' or $GLOBALS['action'] == 'vthread' or $GLOBALS['action']=='ptopic' or $GLOBALS['action']=='pthread');
$true3a=($GLOBALS['user_id']==1 and (!isset($GLOBALS['emailadmposts']) or $GLOBALS['emailadmposts']==0) and !$isInDb);
$true3b=($GLOBALS['user_id']!=1 and !$isInDb);
$true3=($true3a or $true3b);

if ($true0 and $true1 and $true2 and $true3) {
$checkEmail="<input type=\"checkbox\" id=\"CheckSendMail\" name=\"CheckSendMail\"><label for=\"CheckSendMail\">{$GLOBALS['l_emailNotify']}</label>";
if ($GLOBALS['topic']) { $checkEmail.=" <a title=\"{$GLOBALS['l_subscribe']}\" href=\"{$GLOBALS['indexphp']}action=subscribe&amp;topic={$GLOBALS['topic']}&amp;usrid={$GLOBALS['user_id']}\">{$GLOBALS['l_subscribe']}</a>";
}
}
elseif($isInDb) $checkEmail="<!--U-->{$GLOBALS['l_unsubscribeinfo']}<a title=\"{$GLOBALS['l_unsubscribe']}\" href=\"{$GLOBALS['indexphp']}action=unsubscribe&amp;topic={$GLOBALS['topic']}&amp;usrid={$GLOBALS['user_id']}\">{$GLOBALS['l_unsubscribe']}</a>";
}
return $checkEmail;
}

//---------------------->
function makeValuedDropDown($listArray,$selectName){
$out='';
if(isset($GLOBALS[$selectName])) $curVal=$GLOBALS[$selectName]; else $curVal='';
foreach($listArray as $key=>$val){
if($curVal==$key) $sel=' selected'; else $sel='';
$out.="<option {$sel} value=\"$key\">$val</option>\n";
}
return "<select name=$selectName class=textForm>$out</select>";
}

