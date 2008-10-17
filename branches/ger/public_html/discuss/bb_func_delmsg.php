<?php
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004 Paul Puzyrev, Sergei Larionov. www.minibb.net
*/
if (!defined('INCLUDED776')) die ('Fatal error.');

if(isset($_POST['post'])) $post=$_POST['post']; elseif(isset($_GET['post'])) $post=$_GET['post']; else $post=0;
$first=db_simpleSelect(0,$Tp,'post_id','topic_id','=',$topic,'post_id ASC',1); $first=$first[0];

if(($logged_admin==1 or $isMod==1) and $first!=$post) {

if(db_delete($Tp,'post_id','=',$post) and $pp=db_simpleSelect(0,$Tp,'post_id','topic_id','=',$topic,'post_id DESC',1)){
$topic_last_post_id=$pp[0];
updateArray(array('topic_last_post_id'),$Tt,'topic_id',$topic);
db_forumReplies($forum,$Tp,$Tf);
db_topicPosts($topic,$Tt,$Tp);

if(isset($metaLocation)) { $meta_relocate="{$main_url}/{$indexphp}action=vthread&forum={$forum}&topic={$topic}&page={$page}"; echo ParseTpl(makeUp($metaLocation)); exit; } else { header("Location: {$main_url}/{$indexphp}action=vthread&forum={$forum}&topic={$topic}&page={$page}"); exit; }

}
else {
$errorMSG=$l_itseemserror; $correctErr=$backErrorLink;
}

}
else {
$errorMSG=$l_forbidden; $correctErr=$backErrorLink;
}

$title.=$errorMSG;
echo load_header(); echo ParseTpl(makeUp('main_warning')); return;
?>