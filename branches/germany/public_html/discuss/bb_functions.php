<?php
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004 Paul Puzyrev, Sergei Larionov. www.minibb.net
*/
$version='2.0 RC2a';

//--------------->
function makeUp($name,$addDir='') {
if($addDir=='') $addDir=$GLOBALS['pathToFiles'].$GLOBALS['templateDir'].'/';
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
$qs=array();/* ugly code... */
$qv=array();
$ex=explode ('{?',$tpl);
for ($i=0; $i<=sizeof($ex); $i++){
	if (!empty($ex[$i]) and substr_count($ex[$i],'?}')>0) {
		$yy=explode('?}',$ex[$i]);
		if (substr_count($yy[0],'|')>0) {
			$xx=explode('|',$yy[0]);
			if ($xx[0][0] == '!') {
				$res=true;
				$vn=substr($xx[0],1);
			} else {
				$res=false;
				$vn=$xx[0];
			}
			$to=$xx[1];
			if (substr_count($vn,'[')>0) {
				$clr=explode ('[',$vn); $sp=$clr[1]+0; $clr=$clr[0];
				if (!in_array($clr,$qs)) {$qs[]=$clr; }
					if(isset($GLOBALS[$clr][$sp])&&$GLOBALS[$clr][$sp]) $res=!$res;
			}
			else { if(!in_array($vn, $qv)) {$qv[]=$vn; }
				if(isset($GLOBALS[$vn])&&$GLOBALS[$vn]) $res=!$res;
			}
			if (!$res) $to = '';
			$tpl=str_replace('{?'.$yy[0].'?}', $to, $tpl);
		}
	}
}
$qs=array();
$qv=array();
$ex=explode ('{$',$tpl);
for ($i=0; $i<=sizeof($ex); $i++){
if (!empty($ex[$i]) and substr_count($ex[$i],'}')>0) {
$xx=explode('}',$ex[$i]);
if (substr_count($xx[0],'[')>0) {
$clr=explode ('[',$xx[0]); $sp=$clr[1]+0; $clr=$clr[0];
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
function getIP(){
$ip1=getenv('REMOTE_ADDR');$ip2=getenv('HTTP_X_FORWARDED_FOR');
if ($ip2!='' and ip2long($ip2)!=-1) $finalIP=$ip2; else $finalIP=$ip1;
$finalIP=substr($finalIP,0,15);
return $finalIP;
}

//--------------->
function convert_date($dateR){
$engMon=array('January','February','March','April','May','June','July','August','September','October','November','December',' ');
$months=explode (':', $GLOBALS['l_months']);
$months[]='&nbsp;';
if(isset($GLOBALS['timeDiff']) and $GLOBALS['timeDiff']!=0) $dateR=date($GLOBALS['dateFormat'],strtotime($dateR)+$GLOBALS['timeDiff']);
else $dateR=date($GLOBALS['dateFormat'],strtotime($dateR));
$dateR=str_replace($engMon,$months,$dateR);
return $dateR;
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
function pageNav($page,$numRows,$url,$viewMax,$navCell){
$pageNav=$GLOBALS['l_pages'].':';
if(isset($GLOBALS['mod_rewrite']) and $GLOBALS['mod_rewrite'] and ($GLOBALS['action']=='vtopic' or $GLOBALS['action']=='vthread' or $GLOBALS['action']=='')) $mr='.html'; else $mr='';
$page=pageChk($page,$numRows,$viewMax);
$iVal=intval(($numRows-1)/$viewMax);
if($iVal>$GLOBALS['viewpagelim']){
$iVal=$GLOBALS['viewpagelim'];
if($GLOBALS['viewpagelim']>=1) $iVal-=1;
}
if($numRows>0&&$iVal>0&&$numRows<>$viewMax){
$end=$iVal;
if(!$navCell || true) $start=0; else $start=1;
if($page>0&&!$navCell) $pageNav.=' <a href="'.$url.($page-1).$mr.'" class="pageNav">&lt;&lt; '.$GLOBALS['l_prev'].'</a>';
if($navCell&&$end>4){ $end=3;$pageNav.=' '; }
elseif($page<9&&$end>9){ $end=9;$pageNav.=' '; }
elseif($page>=9&&$end>9){
$start=intval($page/9)*9-1;$end=$start+10;
if($end>$iVal) $end=$iVal;
$pageNav.=' <a href="'.$url.'0'.$mr.'" class="pageNav">1</a> ...';
}
else $pageNav.=' ';
for($i=$start;$i<=$end;$i++){
if($i==$page&&!$navCell) $pageNav.=' <b class="pageNav pageNavSelected">'.($i+1).'</b> ';
else {
	$pageNav.=' <a href="'.$url.$i.$mr.'" class="pageNav" title="'.$GLOBALS['l_page'].' '.($i+1).'">'.($i+1).'</a> ';
	if ($i>=$end&&$i==$iVal)
		$pageNav.=' - <a href="'.$url.$i.$mr.'" class="pageNav" title="'.$GLOBALS['l_page'].' '.($i+1).'">'.$GLOBALS['l_lastpage'].'</a> ';
	}
}
if((($navCell&&$iVal>4)||($iVal>9&&$start<=$iVal-10))){
if($navCell&&$iVal<6); else $pageNav.='..';
for($n=$iVal-1;$n<=$iVal;$n++){
if($n>=$i) {
	$pageNav.=' <a href="'.$url.$n.$mr.'" class="pageNav" title="'.$GLOBALS['l_page'].' '.($n+1).'">'.($n+1).'</a> ';
	if ($n==$iVal)
		$pageNav.=' - <a href="'.$url.$n.$mr.'" class="pageNav" title="'.$GLOBALS['l_page'].' '.($n+1).'">'.$GLOBALS['l_lastpage'].'</a> ';
}
}
}
if($page<$iVal&&!$navCell) $pageNav.=' <a href="'.$url.($page+1).$mr.'" class="pageNav">'.$GLOBALS['l_next'].' &gt;&gt;</a>';
return $pageNav;
}
}

//---------------------->
function sendMail($email, $subject, $msg, $from_email, $errors_email) {
// Function sends mail with return-path (if incorrect email TO specifed. Reply-To: and Errors-To: need contain equal addresses!
if (!isset($GLOBALS['genEmailDisable']) or $GLOBALS['genEmailDisable']!=1){
$msg=str_replace("\r\n", "\n", $msg);
$php_version=phpversion();
$from_email="From: $from_email\nReply-To: $errors_email\nErrors-To: $errors_email\nX-Mailer: PHP ver. $php_version";
mail($email, $subject, $msg, $from_email, "-f mail@hlipp.de"); //FIXME env from
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

?>
