<?php
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004 Paul Puzyrev, Sergei Larionov. www.minibb.net
*/
if (!defined('INCLUDED776')) die ('Fatal error.');

	header("HTTP/1.1 503 Service Unavailable");
	$smarty->display('function_disabled.tpl');
	exit;


if(isset($_GET['searchWhere'])) $searchWhere=$_GET['searchWhere'];
elseif(isset($_POST['searchWhere'])) $searchWhere=$_POST['searchWhere'];
else $searchWhere=0;
if(isset($_GET['searchHow'])) $searchHow=$_GET['searchHow'];
elseif(isset($_POST['searchHow'])) $searchHow=$_POST['searchHow'];
else $searchHow=0;
if(isset($_GET['searchFor'])) $searchFor=$_GET['searchFor'];
elseif(isset($_POST['searchFor'])) $searchFor=$_POST['searchFor'];
else $searchFor='';
if(isset($_GET['searchForum'])) $searchForum=$_GET['searchForum'];
elseif(isset($_POST['searchForum'])) $searchForum=$_POST['searchForum'];
else $searchForum=0; 
if(isset($_GET['days'])) $days=$_GET['days'];
elseif(isset($_POST['days'])) $days=$_POST['days'];
else { $days=0; $flag=1; }
if(isset($_GET['eMatch'])) $eMatch=$_GET['eMatch'];
elseif(isset($_POST['eMatch'])) $eMatch=$_POST['eMatch'];
elseif(isset($_GET['exact'])) $exact=$_GET['exact'];

if ((preg_match("/^([A-Z]{1,2})([0-9]{1,2}[A-Z]?) *([0-9])([A-Z]{0,2})$/",strtoupper($searchFor)) || preg_match("/^([a-zA-Z]{1,2}) ?(\d{2,5})[ \.]?(\d{2,5})$/",$searchFor) ) 
	&& ($searchForum==0 || $searchForum==5)) {
	header("Location:http://{$_SERVER['HTTP_HOST']}/discuss/search.php?q={$searchFor}");
	print "<a href=\"http://{$_SERVER['HTTP_HOST']}/discuss/search.php?q={$searchFor}\">View Search Results</a>";
	exit;
}
			

$searchWhere+=0;$searchHow+=0;$searchForum+=0;$word=0;$min=2;$i=0;
$searchFor=textFilter($searchFor,100,$post_word_maxlength,0,1,0,0);
$days=substr($days,0,4)+0;

$sCA=array($Tp.','.$Tt,$Tt,$Tp);
$sTA=array($Tp,$Tt,$Tp);
$sTTA=array('post_time','topic_time','post_time');

if($searchWhere==0) $whereGenAr=array($Tp.'.post_text',$Tt.'.topic_title');
elseif($searchWhere==1) $whereGenAr=array('topic_title','');
elseif($searchWhere==2) $whereGenAr=array($Tp.'.poster_name','');

if((isset($eMatch)&&$eMatch=='on')||(isset($exact)&&$exact)) {$exact=1;$eMatch='checked';} else {$exact=0;$eMatch='';}

if(!isset($clForumsUsers)) $clForumsUsers=array();
$closedForums=getAccess($clForums, $clForumsUsers, $user_id);
$extra=($closedForums!='n'?1:0);

$SHchk=array('','','');
$SHchk[$searchHow]='selected';
$SWchk=array('','','');
$SWchk[$searchWhere]='selected';

$exploded=explode(' ',$searchFor);

if($searchFor==''&&$days<=$defDays&&(!isset($flag) or !$flag)) {$word=1;$exact=1;$searchHow=3;$searchWhere=1;$searchString='';}
elseif($searchFor==''&&$days>$defDays) $warning=$l_search[12].' '.$defDays.' '.$l_days.'.';

$searchWithin=($searchForum>0?'AND '.$sTA[$searchWhere].'.forum_id='.$searchForum:'');

if($searchHow==0){
if(strlen($exploded[0])>$min) $word=1;
$searchString=db_searchMatchGen($whereGenAr[0],$i);
$searchString2=db_searchMatchGen($whereGenAr[1],$i);
for($i=1;$i<sizeof($exploded);$i++){
if(!$word&&strlen($exploded[$i])>$min) $word=1;
if($searchWhere==0){
$searchString.=' AND '.db_searchMatchGen($whereGenAr[0],$i);
$searchString2.=' AND '.db_searchMatchGen($whereGenAr[1],$i);
}
else $searchString.=' AND '.db_searchMatchGen($whereGenAr[0],$i);
}
}
elseif($searchHow==1){
$word=1;
if(strlen($exploded[0])>$min){
$searchString=db_searchMatchGen($whereGenAr[0],$i);
$searchString2=db_searchMatchGen($whereGenAr[1],$i);
for($i=1;$i<sizeof($exploded);$i++){
if($word&&strlen($exploded[$i])<=$min) {$word=0; break;}
if($searchWhere==0){
$searchString.=' OR '.db_searchMatchGen($whereGenAr[0],$i);
$searchString2.=' OR '.db_searchMatchGen($whereGenAr[1],$i);
}
else $searchString.=' OR '.db_searchMatchGen($whereGenAr[0],$i);
}
}
else $word=0;
}
elseif($searchHow!=3){
for ($i=0;$i<sizeof($exploded);$i++){
if (strlen($exploded[$i])>$min) {$word=1; break;}
}
$exploded[$i]=$searchFor;
$searchString=db_searchMatchGen($whereGenAr[0],$i);
$searchString2=db_searchMatchGen($whereGenAr[1],$i);
}
if($searchWhere!=0) unset($searchString2);

if(!$word||strlen($searchFor)>100) $searchResults='<span class=txtSm>'.$l_search[10].'</span>';
else{
if(!isset($searchResults)) $searchResults='';
$i=$viewmaxsearch*$page;
$and=($extra&&!$searchWhere?'AND':'WHERE');
$xtrE=($extra==1?getClForums($closedForums,$and,$sTA[$searchWhere],'forum_id','AND','!='):'');
$and=($extra||!$searchWhere?'AND':'WHERE');
$xtr=$xtrE.getByDay($and,$sTA[$searchWhere],$sTTA[$searchWhere]).db_searchWithin($searchWhere,true);
if(!$searchWhere) $numRows=db_simpleSelect(2,$sCA[$searchWhere],'count(*)',$Tp.'.topic_id','=',"$Tt.topic_id");
else $numRows=db_simpleSelect(2,$sCA[$searchWhere],'count(*)');
$pageNav=pageNav($page,$numRows,"{$main_url}/{$indexphp}action=search&amp;searchFor=$searchFor&amp;searchWhere=$searchWhere&amp;searchHow=$searchHow&amp;searchForum=$searchForum&amp;days=$days&amp;exact=$exact&amp;page=",$viewmaxsearch,false);
$makeLim=makeLim($page,$numRows,$viewmaxsearch);
$xtr=str_replace('WHERE','AND',$xtr);

if($searchWhere==0&&$numRows){
$xtr.=db_searchWithin($searchWhere,false);
$cols=db_simpleSelect(0,$Tp.','.$Tt.','.$Tf,"$Tp.post_id, $Tp.forum_id, $Tp.topic_id, $Tp.post_text, $Tp.post_time, $Tt.topic_title, $Tf.forum_name",'','','','post_id DESC',$makeLim,$Tp.'.topic_id','=',$Tt.'.topic_id',false);
do{
$i++;
$forum=$cols[1];
$topic=$cols[2];
$pageAnchor=db_searchDeSlice(false,$cols[0]);
$searchResults.='<b>'.$i.'. </b><span class=txtSm>'.$l_posted.': '.$cols[4].'</span> - <a href="'.$indexphp.'action=vtopic&amp;forum='.$forum.'&amp;page='.db_searchDeSlice(true,$topic).'"><b>'.$cols[6].'</b></a> <b>&#8212;&#8250;</b> <a href="'.$indexphp.'action=vthread&amp;forum='.$forum.'&amp;topic='.$topic.'">'.$cols[5].'</a><br>'."\n".'
&nbsp;&nbsp;&nbsp; <span class=txtSm><a href="'.$indexphp.'action=vthread&amp;forum='.$forum.'&amp;topic='.$topic.'&amp;page='.$pageAnchor[0].$pageAnchor[1].'">'.substr(strip_tags($cols[3]),0,81).'...</a></span><br><br>'."\n";
}
while($cols=db_simpleSelect(1));
}

elseif($searchWhere==1&&$numRows){
$cols=db_simpleSelect(0,$Tt.','.$Tf,"$Tt.topic_id, $Tt.forum_id, $Tt.topic_title, $Tt.topic_time, $Tf.forum_name",'','','',"$Tt.topic_id DESC",$makeLim,$Tt.'.forum_id','=',$Tf.'.forum_id',false);
do{
$i++;
$forum=$cols[1];
$topic=$cols[2];
$searchResults.='<b>'.$i.'. </b><span class=txtSm>'.$l_posted.': '.$cols[3].'</span> - <a href="'.$indexphp.'action=vtopic&forum='.$forum.'&amp;page='.db_searchDeSlice(TRUE,$cols[0]).'"><b>'.$cols[4].'</b></a> <b>&#8212;&#8250;</b> <a href="'.$indexphp.'action=vthread&amp;forum='.$forum.'&amp;topic='.$cols[0].'">'.$topic.'</a><br><br>'."\n";
}
while($cols=db_simpleSelect(1));
}

elseif($searchWhere==2&&$numRows){
$xtr.=db_searchWithin($searchWhere,false);
$cols=db_simpleSelect(0,$Tp.','.$Tt.','.$Tf,"$Tp.post_id, $Tp.forum_id, $Tp.topic_id, $Tp.post_text, $Tp.post_time, $Tt.topic_title, $Tf.forum_name",'','','','post_id DESC',$makeLim,$Tp.'.topic_id','=',$Tt.'.topic_id',false);
do{
$i++;
$forum=$cols[1];
$topic=$cols[2];
$pageAnchor=db_searchDeSlice(false,$cols[0]);
$searchResults.='<b>'.$i.'. </b><span class=txtSm>'.$l_posted.': '.$cols[4].'</span> - <a href="'.$indexphp.'action=vtopic&amp;forum='.$forum.'&amp;page='.db_searchDeSlice(TRUE,$topic).'"><b>'.$cols[6].'</b></a> <b>&#8212;&#8250;</b> <a href="'.$indexphp.'action=vthread&amp;forum='.$forum.'&amp;topic='.$topic.'">'.$cols[5].'</a><br>'."\n".'&nbsp;&nbsp;&nbsp; <span class=txtSm><a href="'.$indexphp.'action=vthread&amp;forum='.$forum.'&amp;topic='.$topic.'&amp;page='.$pageAnchor[0].$pageAnchor[1].'">'.substr(strip_tags($cols[3]),0,81).'...</a></span><br><br>'."\n";
}
while($cols=db_simpleSelect(1));;
}

}

if($days<=0) $days=$defDays;
$xtr='';
$title=$title.$l_searchSite;

if($searchForum<1){ $st=1; $frm=''; $SFchk=' selected';}
else $frm=$searchForum;
include ($pathToFiles.'bb_func_forums.php');

echo load_header(); echo ParseTpl(makeUp('search'));
?>