<?php
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004 Paul Puzyrev, Sergei Larionov. www.minibb.net
*/
if (!defined('INCLUDED776')) die ('Fatal error.');

$USERINFO='';

$user=(isset($_GET['user'])?$_GET['user']+0:0);

if(($user_id==1 or $isMod==1) and $user!=1 and isset($_GET['activity']) and ($_GET['activity']==1 or $_GET['activity']==0)){
$activity=$_GET['activity'];
updateArray(array('activity'),$Tu,$dbUserId,$user);
}

if(!isset($l_sendDirect)) $usEmail=''; else $usEmail='<a href="'.$indexphp.'action=senddirect&amp;user='.$user.'">'.$l_sendDirect.'</a>';

$addFieldsGen=array('user_icq','user_website','user_occ','user_from','user_interest');

$addFd='';
$addCustomFd='';
foreach($addFieldsGen as $k=>$v) if(isset($dbUserSheme[$v][1])) $addFd.=','.$dbUserSheme[$v][1]; else $addFd.=',null';
foreach($dbUserSheme as $k=>$v) if(strstr($k,'user_custom')) $addCustomFd.=','.$v[1];

if ($row=db_simpleSelect(0,$Tu,$dbUserAct.','.$dbUserSheme['username'][1].','.$dbUserDate.','.$dbUserSheme['user_viewemail'][1].','.$dbUserSheme['user_email'][1].$addFd.',null'.$addCustomFd,$dbUserId,'=',$user)) {
if ($row[3]!=1) $row[4]=$usEmail; else $row[4]='<a href="mailto:'.$row[4].'">'.$row[4].'</a>';
if ($row[6]!='') $row[6]='<a href="'.$row[6].'" target="_blank">'.$row[6].'</a>'; else $row[6]='';
if(strstr($row[2],'-')) $row[2]=convert_date($row[2]); else $row[2]=convert_date(date('Y-m-d H:i:s',$row[2]));
$usrCell=makeUp('main_user_info_cell');

$infLn=array_search(end($l_usrInfo),$l_usrInfo)+1;
for($i=1; $i<$infLn; $i++){
if (isset($l_usrInfo[$i]) and $row[$i]!='') {
$what=$l_usrInfo[$i]; $whatValue=$row[$i];
$USERINFO.=ParseTpl($usrCell);
}
}

/* Topics count */
if ($lastT=db_simpleSelect(0,$Tt,'count(*)','topic_poster','=',$user)) {
$what=$l_stats_numTopics;
$whatValue=$lastT[0];
$USERINFO.=ParseTpl($usrCell);
}

/* Posts count */
if ($lastT=db_simpleSelect(0,$Tp,'count(*)','poster_id','=',$user)) {
$what=$l_stats_numPosts;
$whatValue=$lastT[0]-$whatValue;
$USERINFO.=ParseTpl($usrCell);
}

/* Last topics */
if(!isset($clForumsUsers)) $clForumsUsers=array();
$closedForums=getAccess($clForums, $clForumsUsers, $user_id);
if ($closedForums!='n') $xtr=getClForums($closedForums,'AND','','forum_id','AND','!='); else $xtr='';

$topicAll=array();
if ($lastT=db_simpleSelect(0,$Tt,'topic_id, forum_id, topic_title','topic_poster','=',$user, 'topic_id desc', $viewmaxtopic)) {
$what=$l_userLastTopics;
$whatValue='<ul class=limbb>';
do {
$topicAll[]=$lastT[0];

if(isset($mod_rewrite) and $mod_rewrite) $urlp="{$main_url}/{$lastT[1]}_{$lastT[0]}_-1.html"; else $urlp="{$indexphp}action=vthread&amp;topic={$lastT[0]}&amp;forum={$lastT[1]}&amp;page=-1";

$whatValue.="<li><a href=\"{$urlp}\">{$lastT[2]}</a>";
}
while ($lastT=db_simpleSelect(1));
$whatValue.='</ul>';
$USERINFO.=ParseTpl($usrCell);
}

/* Last posts */
if(sizeof($topicAll)>0){

$xtr2=getClForums($topicAll,'AND','','topic_id','AND','!=');
$xtr=$xtr.' '.$xtr2;

}//are topics

$topicAll=array();
$num=1;
if($ls=db_simpleSelect(0,$Tp,'topic_id','poster_id','=',$user,'post_id DESC')){
do if(!in_array($ls[0],$topicAll)) { $topicAll[]=$ls[0]; $num++; }
while($ls=db_simpleSelect(1) AND $num<=$viewmaxtopic);
}

$xtr=getClForums($topicAll,'where','','topic_id','OR','=');

if(sizeof($topicAll)>0 and $lastT=db_simpleSelect(0,$Tt,'topic_id, forum_id, topic_title','','','','topic_last_post_id DESC')){
$what=$l_userLastPosts;
$whatValue='<ul class=limbb>';
do {

if(isset($mod_rewrite) and $mod_rewrite) $urlp="{$main_url}/{$lastT[1]}_{$lastT[0]}_-1.html"; else $urlp="{$indexphp}action=vthread&amp;topic={$lastT[0]}&amp;forum={$lastT[1]}&amp;page=-1";

$whatValue.="<li><a href=\"{$urlp}\">{$lastT[2]}</a>";
}
while ($lastT=db_simpleSelect(1));
$whatValue.='</ul>';
$USERINFO.=ParseTpl($usrCell);
}

/* Activities */
$closedForums=getAccess($clForums, $clForumsUsers, $user_id);
if ($closedForums!='n') $xtr=getClForums($closedForums,'AND','','forum_id','AND','!='); else $xtr='';

$what=$l_usrInfo[10];

$forums=array();
$forumIds=array();
if($rw=db_simpleSelect(0,$Tp,'forum_id','poster_id','=',$user)){
do {
if(!isset($forums[$rw[0]])) $forums[$rw[0]]=1; else $forums[$rw[0]]++;
if(!in_array($rw[0],$forumIds)) $forumIds[]=$rw[0];
}
while($rw=db_simpleSelect(1));

asort($forums,SORT_NUMERIC);
$forums=array_reverse($forums,TRUE);

$xtr=getClForums($forumIds,'where','','forum_id','OR','=');
$forumNames=array();
if($rw=db_simpleSelect(0,$Tf,'forum_id,forum_name')){
do $forumNames[$rw[0]]=$rw[1];
while($rw=db_simpleSelect(1));
}

$userID=$user+0;
$key2='';
$whatValue='';
$tpl=makeUp('stats_bar');
if(sizeof($forumNames)>0){
foreach($forums as $k=>$val){
if(!isset($vMax)) $vMax=$val;
$stats_barWidth=round(100*($val/$vMax));

if(isset($mod_rewrite) and $mod_rewrite) { $urlp1="{$main_url}/"; $urlp2="_0.html"; } else { $urlp1="{$indexphp}action=vtopic&amp;forum="; $urlp2=''; }

if($stats_barWidth>$stats_barWidthLim) $key='<a href="'.$urlp1.$k.$urlp2.'">'.$forumNames[$k].'</a>';
else{
$key2='<a href="'.$urlp1.$k.$urlp2.'">'.$forumNames[$k].'</a>';
$key='<a href="'.$urlp1.$k.$urlp2.'">...</a>';
}
$whatValue.=ParseTpl($tpl);
}
}

$USERINFO.=ParseTpl($usrCell);
}//if posts

if(($user_id==1 or $isMod==1) and $user!=1){
$act=$row[0]; $actnew=($act==0?1:0);
$mes1=($act==0?$l_no:$l_yes);
$mes2=($act==0?$l_yes:$l_no);
$what=$l_member; $whatValue="{$mes1} [<a href=\"{$main_url}/{$indexphp}action=userinfo&amp;user={$user}&amp;activity={$actnew}\">{$mes2}</a>]";
$USERINFO.=ParseTpl($usrCell);
}

/* finally */

$userInfo=$l_about.' &ldquo;'.$row[1].'&rdquo;';
$title.=$l_about.' '.$row[1];
$tpl=makeUp('main_user_info'); 

}
else {
$title.=$l_userNotExists; $errorMSG=$l_userNotExists; $correctErr=$backErrorLink;
$tpl=makeUp('main_warning');
}

echo load_header(); echo ParseTpl($tpl); return;
?>