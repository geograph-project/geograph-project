<?php
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004 Paul Puzyrev, Sergei Larionov. www.minibb.net
*/
if (!defined('INCLUDED776')) die ('Fatal error.');

if(isset($_GET['days'])) $days=$_GET['days'];
elseif(isset($_POST['days'])) $days=$_POST['days'];
else $days='0000';
if(isset($_GET['lst'])) $lst=$_GET['lst'];
elseif(isset($_POST['lst'])) $lst=$_POST['lst'];
else $lst=0;
if(isset($_GET['top'])) $top=$_GET['top'];
elseif(isset($_POST['top'])) $top=$_POST['top'];
else $top=0;

$days=substr($days,0,4)+0;
if($days<=0) $days=$defDays;

if(!isset($clForumsUsers)) $clForumsUsers=array();
$closedForums=getAccess($clForums, $clForumsUsers, $user_id);
$extra=($closedForums!='n'?1:0);

if (isset($topStats) and in_array($topStats,array(1,2,3,4))) $tKey=$topStats; else $tKey=4;

$stats_barWidth='';$statsOpt='';$list_stats_viewed='';$list_stats_popular='';$list_stats_aUsers='';

if($enableViews) $lstLim=2;
else $lstLim=1;

$lst+=0;$top+=0;$key2='';
if($top+1>$tKey) $top=$tKey-1;
if($lst>$lstLim) $lst=$lstLim;
function fTopa($top){
if($top==0) $topa=5;
elseif($top==1) $topa=10;
elseif($top==2) $topa=20;
else $topa=40;
return $topa;
}

$statsTop=' . ';
for($i=0;$i<$tKey;$i++) $statsTop.=($i<>$top?'<a href="'.$indexphp.'action=stats&amp;top='.$i.'&amp;days='.$days.'&amp;lst='.$lst.'">'.$l_stats_top.' '.fTopa($i).'</a> . ':$l_stats_top.' '.fTopa($i).' . ');
$makeLim=fTopa($top);

$statsOptL=array($l_stats_popular,$l_stats_aUsers,$l_stats_viewed);

for($i=0;$i<=$lstLim;$i++){
if($i<>$lst) $statsOpt.=' / <b><a href="'.$indexphp.'action=stats&amp;top='.$top.'&amp;days='.$days.'&amp;lst='.$i.'">'.$statsOptL[$i].'</a></b>';
else $statsOpt.= ' / <b>'. $statsOptL[$i].'</b>';
}

$tpl=makeUp('stats_bar');

if($lst==0){
$xtr=($extra==1?getClForums($closedForums,'AND',$Tp,'forum_id','AND','!='):'');
$xtr.=getByDay('AND',$Tp,'post_time');
}
elseif($enableViews&&$lst==2){
$xtr=($extra==1?getClForums($closedForums,'AND',$Tt,'forum_id','AND','!='):'');
$xtr.=getByDay('AND',$Tt,'topic_time');
}

if($lst==0&&$cols=db_simpleSelect(0,"$Tt,$Tp","$Tt.topic_id, $Tt.topic_title, $Tt.forum_id, $Tt.posts_count as cnt",'','','','cnt DESC',$makeLim,"$Tt.topic_id",'=',"$Tp.topic_id",false,"$Tp.topic_id")){
do{
if($cols[3]){
$val=$cols[3]-1;
if(!isset($vMax)) $vMax=$val;
if ($vMax!=0) $stats_barWidth=round(100*($val/$vMax));
if($stats_barWidth>$stats_barWidthLim) $key='<a href="'.$indexphp.'action=vthread&amp;forum='.$cols[2].'&amp;topic='.$cols[0].'">'.$cols[1].'</a>';
else{
$key2='<a href="'.$indexphp.'action=vthread&amp;forum='.$cols[2].'&amp;topic='.$cols[0].'">'.$cols[1].'</a>';
$key='<a href="'.$indexphp.'action=vthread&amp;forum='.$cols[2].'&amp;topic='.$cols[0].'">...</a>';
}
$list_stats_popular.=ParseTpl($tpl);
}
else break;
}
while($cols=db_simpleSelect(1));
}

elseif($lst==1&&$cols=db_simpleSelect(0,"$Tu,$Tp","$Tu.{$dbUserId}, $Tu.{$dbUserSheme['username'][1]}, count(*) as cnt","$Tu.{$dbUserId}",'>',1,'cnt DESC',$makeLim,"$Tu.{$dbUserId}",'=',"$Tp.poster_id",true,"$Tp.poster_id")){
do{
if($cols[2]){
$val=$cols[2];
if(!isset($vMax)) $vMax=$val;
$stats_barWidth=round(100*($val/$vMax));
if($stats_barWidth>$stats_barWidthLim) $key='<a href="'.$indexphp.'action=userinfo&amp;user='.$cols[0].'">'.$cols[1].'</a>';
else{
$key2='<a href="'.$indexphp.'action=userinfo&amp;user='.$cols[0].'">'.$cols[1].'</a>';
$key='<a href="'.$indexphp.'action=userinfo&amp;user='.$cols[0].'">...</a>';
}
$list_stats_aUsers.=ParseTpl($tpl);
}
else break;
}
while($cols=db_simpleSelect(1));
}

elseif($enableViews&&$lst==2&&$cols=db_simpleSelect(0,$Tt,'topic_id, topic_views, topic_title, forum_id','','','','topic_views DESC, topic_id DESC',$makeLim,'topic_views','>',0,false)){
do{
if($cols[1]){
if(!isset($vMax)) $vMax=$cols[1];
$val=$cols[1];
$stats_barWidth=round(100*($val/$vMax));
if($stats_barWidth>$stats_barWidthLim) $key='<a href="'.$indexphp.'action=vthread&amp;forum='.$cols[3].'&amp;topic='.$cols[0].'">'.$cols[2].'</a>';
else{
$key2='<a href="'.$indexphp.'action=vthread&amp;forum='.$cols[3].'&amp;topic='.$cols[0].'">'.$cols[2].'</a>';
$key='<a href="'.$indexphp.'action=vthread&amp;forum='.$cols[3].'&amp;topic='.$cols[0].'">...</a>';
}
$list_stats_viewed.=ParseTpl($tpl);
}
else break;
}
while($cols=db_simpleSelect(1));
}

unset($xtr);

$numUsers=db_simpleSelect(2,$Tu,'count(*)');
$numTopics=db_simpleSelect(2,$Tf,'SUM(topics_count)');
$numPosts=db_simpleSelect(2,$Tf,'SUM(posts_count)')-$numTopics;
$adminInf=db_simpleSelect(2,$Tu,$dbUserSheme['username'][1],$dbUserId,'=',1);
$lastRegUsr=db_simpleSelect(0,$Tu,"{$dbUserId}, {$dbUserSheme['username'][1]}",$dbUserId,'>',1,"{$dbUserId} DESC",1);

$title=$title.$l_stats;

echo load_header(); echo ParseTpl(makeUp('stats'));
?>