<?php
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004 Paul Puzyrev, Sergei Larionov. www.minibb.net
*/
if (!defined('INCLUDED776')) die ('Fatal error.');

$listPosts=''; $deleteTopic='';

/*** CHECK ***/
if($topicData and $topicData[4]==$forum){
$topicName=$topicData[0]; if ($topicName=='') $topicName=$l_emptyTopic;
$topicStatus=$topicData[1];
$topicSticky=$topicData[6];
$topicPoster=$topicData[2];
$topicPosterName=$topicData[3];
$topic_views=$topicData[7]+1;
}
else {
$errorMSG=$l_topicnotexists; $correctErr=$backErrorLink;
$title=$title.$l_topicnotexists;
echo load_header(); echo ParseTpl(makeUp('main_warning')); return;
}

if(!$row=db_simpleSelect(0,$Tf,'forum_name, forum_icon, topics_count, posts_count','forum_id','=',$forum)){
$errorMSG=$l_forumnotexists; $correctErr=$backErrorLink;
$title=$title.$l_forumnotexists;
echo load_header(); echo ParseTpl(makeUp('main_warning')); return;
}
unset($result);unset($countRes);

$forumName=$row[0]; $forumIcon=$row[1];

/* actual */

if ($gridref || ($forum == 5 && $gridref = $topicName)) {
	
	
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');

	$smarty = new GeographPage;
	$square=new GridSquare;

	$grid_ok=$square->setGridRef($gridref);
	
	if ($grid_ok) {
		if ($square->imagecount)
		{
			$images=$square->getImages();
			$smarty->assign_by_ref('images', $images);

			$gridThumbs = $smarty->fetch("_discuss_gridref_cell.tpl");
		}
	}
}
if (isset($CONF['disable_discuss_thumbs'])) {
	$gridThumbs .= "<h4 style=\"color:red\">During times of heavy load we limit the display of thumbnails in the posts.<br/>Sorry for the loss of this feature.</h4>";
}




$numRows=$topicData[5];

$topicDesc=0;
$topic_reverse='';
if(isset($themeDesc) and in_array($topic,$themeDesc)) {
$topicDesc=1;
$topic_reverse="<img src=\"{$main_url}/img/topic_reverse.gif\" align=middle border=0 alt=\"\">&nbsp;";
}

if($page==-1 and $topicDesc==0) $page=pageChk($page,$numRows,$viewmaxreplys);
elseif($page==-1 and $topicDesc==1) $page=0;

if(isset($mod_rewrite) and $mod_rewrite) $urlp="{$main_url}/{$forum}_{$topic}_"; else $urlp="{$main_url}/{$indexphp}action=vthread&amp;forum=$forum&amp;topic=$topic&amp;dontcount=1&amp;page=";

$pageNav=pageNav($page,$numRows,$urlp,$viewmaxreplys,FALSE);
$makeLim=makeLim($page,$numRows,$viewmaxreplys);

$anchor=1;
$i=1;
$ii=0;

if(isset($themeDesc) and in_array($topic,$themeDesc)) $srt='DESC'; else $srt='ASC';

if($cols=db_simpleSelect(0,$Tp,'poster_id, poster_name, post_time, post_text, poster_ip, post_status, post_id','topic_id','=',$topic,'post_id '.$srt,$makeLim)){

if(!isset($_GET['dontcount']) and isset($enableViews) and $enableViews) updateArray(array('topic_views'),$Tt,'topic_id',$topic);

$tpl=makeUp('main_posts_cell');

do{
if($i>0) $bg='tbCel1'; else $bg='tbCel2';

$postDate=convert_date($cols[2]);

$allowedEdit="<a href=\"{$main_url}/{$indexphp}action=editmsg&amp;topic=$topic&amp;forum=$forum&amp;post={$cols[6]}&amp;page=$page&amp;anchor=$anchor\">$l_edit</a>";

if ($logged_admin==1 or $isMod==1) { 
$viewIP=' '.$l_sepr.' IP: '.'<a href="'.$indexphp.'action=viewipuser&amp;postip='.$cols[4].'">'.$cols[4].'</a>';
if(($ii==0 and $page==0 and $topicDesc==0) or ($topicDesc==1 and $numRows==$viewmaxreplys*$page+$i+1))$deleteM='';
else $deleteM=<<<out
<a href="JavaScript:confirmDelete({$cols[6]},0)" onMouseOver="window.status='{$l_deletePost}'; return true;" onMouseOut="window.status=''; return true;">$l_deletePost</a>
out;
$allowed=$allowedEdit." ".$deleteM;
} 
else {
$cols[4]='';
if ($user_id==$cols[0] and $user_id !=0 and $cols[5]!=2 and $cols[5]!=3) {
$allowed=$allowedEdit;
}
else {
$allowed='';
}
}

# post_status: 0-clear (available for edit), 1-edited by author, 2-edited by admin (available only for admin), 3 - edited by mod
if ($cols[5]==0) {
$editedBy='';
}
else {
$editedBy=" $l_sepr $l_editedBy";
if($cols[5]==2) $we="<a href=\"{$main_url}/{$indexphp}action=userinfo&amp;user=1\">{$l_admin}</a>";
elseif($cols[5]==1) $we=$cols[1];
elseif($cols[5]==3) $we="<a href=\"{$main_url}/{$indexphp}action=stats#mods\">{$l_moderator}</a>";
else $we='N/A';
$editedBy.=$we;
}

if ($cols[0]!=0) {
$cc=$cols[0];
if (isset($userRanks[$cc])) $ins=$userRanks[$cc];
elseif (isset($mods[$forum]) and is_array($mods[$forum]) and in_array($cc,$mods[$forum])) $ins=$l_moderator;
else { $ins=($cc==1?$l_admin:$l_member); }
if(isset($mod_rewrite) and $mod_rewrite) $viewReg="<a href=\"{$main_url}/user{$cc}.html\">{$ins}</a>"; else $viewReg="<a title=\"View user profile\" href=\"{$main_url}/{$indexphp}action=userinfo&amp;user={$cc}\">$ins</a>";
}
else $viewReg='';

$posterName=$cols[1];
$posterText=$cols[3];


if (!isset($CONF['disable_discuss_thumbs']) && preg_match_all("/\[\[(\[?)(\w*\d+)(\]?)\]\]/",$posterText,$g_matches)) {
	foreach ($g_matches[2] as $i => $g_id) {
		if (is_numeric($g_id)) {
			if (!isset($g_image)) {
				require_once('geograph/gridimage.class.php');
				require_once('geograph/gridsquare.class.php');
				$g_image=new GridImage;
			}
			$ok = $g_image->loadFromId($g_id);
			if ($g_image->moderation_status == 'rejected' && !isset($userRanks[$cc]))
				$ok = false;
			if ($ok) {
				if ($g_matches[1][$i]) {
					$g_img = $g_image->getThumbnail(120,120,false,true);
					#$g_img = preg_replace('/alt="(.*?)"/','alt="'.$g_image->grid_reference.' : \1 by '.$g_image->realname.'"',$g_img);
					$g_title=$g_image->grid_reference.' : '.htmlentities($g_image->title).' by '.$g_image->realname;
					$posterText = str_replace("[[[$g_id]]]","<a href=\"http://{$_SERVER['HTTP_HOST']}/photo/$g_id\" target=\"_blank\" title=\"$g_title\">$g_img</a>",$posterText);
				} else {
					$posterText = str_replace("[[$g_id]]","{<a href=\"http://{$_SERVER['HTTP_HOST']}/photo/$g_id\" target=\"_blank\">{$g_image->grid_reference} : {$g_image->title}</a>}",$posterText);
				}
			}			
		} else {
			$posterText = str_replace("[[$g_id]]","<a href=\"http://{$_SERVER['HTTP_HOST']}/gridref/$g_id\" target=\"_blank\">$g_id</a>",$posterText);
		}
	}
}

//no external images
// the callback function
$fixExternalImages= <<<FUNC
	if (($matches[2] == 'www.geograph.org.uk') || ($matches[2] == 'www.geograph.co.uk'))
	{
		//this is fine
		return $matches[0];
	}
	else
	{
		$url=$matches[1].$matches[2].$matches[3];
		//no external images allowed
		return "<a href=\"".htmlentities($url)."\">".htmlentities($url)."</a>";	
	}
FUNC;

$posterText=preg_replace_callback(
             '/<img src="(http:\/\/)([^\/]*)([^"]*)"/i',
             create_function(
	             // single quotes are essential here,
	             // or alternative escape all $ as \$
	             '$matches',
	             'return strtolower($matches[0]);'),
             $posterText);
             

$listPosts.=ParseTpl($tpl);

$i=-$i;
if($ii==0) $ii++;
$anchor++;
}
while($cols=db_simpleSelect(1));
unset($result);unset($countRes);

$l_messageABC=$l_sub_answer;
if ($topicStatus!=1) {
$emailCheckBox=emailCheckBox();

$allowForm=($user_id==1 or $isMod==1);
$c1=(in_array($forum,$clForums) and isset($clForumsUsers[$forum]) and !in_array($user_id,$clForumsUsers[$forum]) and !$allowForm);
$c2=(isset($allForumsReg) and $allForumsReg and $user_id==0);
$c4=(isset($roForums) and in_array($forum, $roForums) and !($user_id==1 or $isMod==1));

if ($c1 or $c2 or $c4){
$mainPostForm='';$mainPostArea='';
$nTop=0;
$listPosts=str_replace('getQuotation();','',$listPosts);
}else{
$mainPostForm=ParseTpl(makeUp('main_post_form'));
$mainPostArea=makeUp('main_post_area');
$nTop=1;
}
}
else {
$mainPostArea=makeUp('main_post_closed');
$listPosts=str_replace('getQuotation();','',$listPosts);
}
$mainPostArea=ParseTpl($mainPostArea);

if ($logged_admin==1 or $isMod==1) {

$deleteTopic="$l_sepr <a href=\"JavaScript:confirmDelete({$topic},1)\" onMouseOver=\"window.status='{$l_deleteTopic}'; return true;\" onMouseOut=\"window.status=''; return true;\">$l_deleteTopic</a>";

$moveTopic="$l_sepr <a href=\"{$main_url}/{$indexphp}action=movetopic&amp;forum=$forum&amp;topic=$topic&amp;page=$page\">$l_moveTopic</a>";

if ($topicStatus==0) { $chstat=1; $cT=$l_closeTopic; }
else { $chstat=0; $cT=$l_unlockTopic; }
$closeTopic="<a href=\"{$main_url}/{$indexphp}action=locktopic&amp;forum=$forum&amp;topic=$topic&amp;chstat=$chstat\">$cT</a>";

if ($topicSticky==0) { $chstat=1; $cT=$l_makeSticky; }
else { $chstat=0; $cT=$l_makeUnsticky; }
$stickyTopic="$l_sepr <a href=\"{$main_url}/{$indexphp}action=unsticky&amp;forum=$forum&amp;topic=$topic&amp;chstat=$chstat\">$cT</a>";

$extra=1;
if ($logged_admin==1 and $cnt=db_simpleSelect(0,$Ts,'count(*)','topic_id','=',$topic) and $cnt[0]>0) $subsTopic="$l_sepr <a href=\"{$bb_admin}action=viewsubs&amp;topic=$topic\">$l_subscriptions</a>"; else $subsTopic='';
}

elseif (($user_id==$topicPoster and $user_id!=0 and $user_id!=1) and $topicSticky!=1) {
if ($topicStatus==0) $closeTopic="<a href=\"{$main_url}/{$indexphp}action=locktopic&amp;forum=$forum&amp;topic=$topic&amp;chstat=1\">$l_closeTopic</a>";
elseif($topicStatus==1 and $userUnlock==1) $closeTopic="<a href=\"{$main_url}/{$indexphp}action=locktopic&amp;forum=$forum&amp;topic=$topic&amp;chstat=0\">$l_unlockTopic</a>";
else $closeTopic='';
}

$title=$title.$topicName;

}//if posts

$st=0; $frm=$forum;
$l_chooseForum = 'Jump To Forum';
include ($pathToFiles.'bb_func_forums.php');

if(isset($mod_rewrite) and $mod_rewrite) $linkToForums="{$main_url}/{$forum}_0.html"; else $linkToForums="{$main_url}/{$indexphp}action=vtopic&amp;forum={$forum}";

echo load_header(); echo ParseTpl(makeUp('main_posts'));
?>