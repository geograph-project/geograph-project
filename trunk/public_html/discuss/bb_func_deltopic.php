<?php
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004 Paul Puzyrev, Sergei Larionov. www.minibb.net
*/
if (!defined('INCLUDED776')) die ('Fatal error.');

if ($logged_admin==1 or $isMod==1) {

if($res=db_simpleSelect(0,$Tt,'topic_id','topic_id','>',$topic,'','','forum_id','=',$forum)) $h=$res[0]; else $h=0;
if($h==0) $return=0; else{
$numRows=$countRes;
$rP=$numRows/$viewmaxtopic;
$rPInt=floor($numRows/$viewmaxtopic);
$return=$rPInt;
if($rP==$rPInt) $return-=1;
}

db_delete($Ts,'topic_id','=',$topic);
$topicsDel=db_delete($Tt,'topic_id','=',$topic,'forum_id','=',$forum);
$postsDel=db_delete($Tp,'topic_id','=',$topic,'forum_id','=',$forum);
$postsDel--;
db_forumReplies($forum,$Tp,$Tf);
db_forumTopics($forum,$Tt,$Tf);

//fire event
require_once('geograph/event.class.php');
new Event(EVENT_DELTOPIC, $topic);


if(isset($metaLocation)) { $meta_relocate="{$main_url}/{$indexphp}action=vtopic&forum={$forum}&page={$return}&h={$h}"; echo ParseTpl(makeUp($metaLocation)); exit; } else { header("Location: {$main_url}/{$indexphp}action=vtopic&forum={$forum}&page={$return}&h={$h}"); exit; }

}
else {
$errorMSG=$l_forbidden; $correctErr='';
echo load_header(); echo ParseTpl(makeUp('main_warning')); return;
}
?>