<?php
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004 Paul Puzyrev, Sergei Larionov. wwww.minibb.net
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



        if (!isset($_ENV["OS"]) || strpos($_ENV["OS"],'Windows') === FALSE) {
                $threshold = 3;

                //lets give registered users a bit more leaway!
                if ($USER->registered) {
                        $threshold *= 2;
                }
                //check load average, abort if too high
                $buffer = "0 0 0";
                if (is_readable("/proc/loadavg")) {
                        $f = fopen("/proc/loadavg","r");
                        if ($f)
                        {
                                if (!feof($f)) {
                                        $buffer = fgets($f, 1024);
                                }
                                fclose($f);
                        }
                }
                $loads = explode(" ",$buffer);
                $load=(float)$loads[0];

                if ($load>$threshold)
                {
                        $CONF['disable_discuss_thumbs'] = true;
                }
        }


if (!empty($CONF['disable_discuss_thumbs'])) {
	$gridThumbs = "<h4 style=\"color:red\">During times of heavy load we limit the display of thumbnails in the posts.<br/>Sorry for the loss of this feature.</h4>";
} else {
	if ($gridref || ($forum == 5 && $gridref = $topicName)) {
	
		require_once('geograph/gridimage.class.php');
		require_once('geograph/gridsquare.class.php');

		$smarty = new GeographPage;
		$square=new GridSquare;

		$grid_ok=$square->setGridRef($gridref);

		if ($grid_ok) {
			//find a possible place within 25km
			$smarty->assign('place', $square->findNearestPlace(75000));
			
			if ($square->imagecount)
			{
				//todo use smarty caching
				
				//what style should we use?
				$style = $USER->getStyle();
				$smarty->assign('maincontentclass', 'content_photo'.$style);	
				$smarty->assign('backgroundcolor', $style);	
				
				$images=$square->getImages(false,false,'order by moderation_status+0 desc,seq_no limit 100');
				$smarty->assign_by_ref('images', $images);

			}
			$smarty->assign('gridref', $gridref);	
			$gridThumbs = $smarty->fetch("_discuss_gridref_cell.tpl");
		}
	}
}




$numRows=$topicData[5];

$topicDesc=0;
$topic_reverse='';
$srt='ASC';
if(isset($themeDesc) and in_array($topic,$themeDesc)) {
$topicDesc=1;
$topic_reverse="<img src=\"{$static_url}/img/topic_reverse.gif\" align=middle border=0 alt=\"\">&nbsp;";
$srt='DESC';
}

if($page==-1 and $topicDesc==0) $page=pageChk($page,$numRows,$viewmaxreplys);
elseif($page==-1 and $topicDesc==1) $page=0;

if(isset($mod_rewrite) and $mod_rewrite) $urlp="{$main_url}/{$forum}_{$topic}_"; else $urlp="{$main_url}/{$indexphp}action=vthread&amp;forum=$forum&amp;topic=$topic&amp;dontcount=1&amp;page=";

if (!empty($_GET['l']))
	$urlp = str_replace('dontcount','l=1&amp;dontcount',$urlp);

$pageNav=pageNav($page,$numRows,$urlp,$viewmaxreplys,FALSE);
$makeLim=makeLim($page,$numRows,$viewmaxreplys);

$anchor=1;
$i=1;
$ii=0;

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
if(isset($mod_rewrite) and $mod_rewrite) $viewReg="<a href=\"{$main_url}/user{$cc}.html\">{$ins}</a>"; else $viewReg="<a title=\"View user profile\" href=\"/profile/{$cc}\">$ins</a>";
}
else $viewReg='';

$posterName=$cols[1];
$posterText=$cols[3];
if (($topicDesc && !$postID) || !$topicDesc)
	$postID = $cols[6];

if (preg_match_all('/\[\[(\[?)([a-z]+:)?(\w{0,3} ?\d+ ?\d*)(\]?)\]\]/',$posterText,$g_matches)) {
	global $memcache;
	$mkey = $cols[6].$_SERVER['HTTP_HOST'].((!empty($_GET['l']))?'y':'');
	//fails quickly if not using memcached!
	if ($memtext =& $memcache->name_get('fp',$mkey)) {
		$posterText = $memtext;
	} elseif (empty($CONF['disable_discuss_thumbs'])) {
		$thumb_count = 0;

		if ($topic == 10596) {
			$CONF['post_thumb_limit'] = 50;
		}


		foreach ($g_matches[3] as $g_i => $g_id) {
			$server = $_SERVER['HTTP_HOST'];
			$ext = false;
			$prefix = '';
			if ($g_matches[2][$g_i] == 'de:') {
				$server = 'geo.hlipp.de';
				$ext = true;
				$prefix = 'de:';
			} elseif ($g_matches[2][$g_i] == 'ci:') {
				$server = 'channel-islands.geographs.org';
				$ext = true;
				$prefix = 'ci:';
			}
			if (is_numeric($g_id)) {
				if ($global_thumb_count >= $CONF['global_thumb_limit'] || $thumb_count >= $CONF['post_thumb_limit']) {
					$posterText = preg_replace("/\[?\[\[$prefix$g_id\]\]\]?/","[[<a href=\"http://{$server}/photo/$g_id\">$prefix$g_id</a>]]",$posterText);
				} else {
					if (!isset($g_image)) {
						require_once('geograph/gridimage.class.php');
						require_once('geograph/gridsquare.class.php');
						$g_image=new GridImage;
					}
					if ($ext) {
						$g_ok = $g_image->loadFromServer($server, $g_id);
					} else {
						$g_ok = $g_image->loadFromId($g_id);
					}
					if ($g_ok && $g_image->moderation_status == 'rejected' && (!isset($userRanks[$cc]) || $userRanks[$cc] == 'Member')) {
						if ($g_matches[1][$g_i]) {
							$posterText = str_replace("[[[$prefix$g_id]]]",'<img src="/photos/error120.jpg" width="120" height="90" alt="image no longer available ['.$g_id.']" />',$posterText);
						} else {
							$posterText = preg_replace("/(?<!\[)\[\[$prefix$g_id\]\]/","{<span title=\"[$g_id]\">image no longer available</span>}",$posterText);
						}
					} elseif ($g_ok) {
						$postfix = empty($_GET['l'])?'':"<input value='{$g_matches[0][$g_i]}' ondblclick='this.select()' class='imageselect'/>";
						if ($g_matches[1][$g_i]) {
							$g_img = $g_image->getThumbnail(120,120,false,true);
							#$g_img = preg_replace('/alt="(.*?)"/','alt="'.$g_image->grid_reference.' : \1 by '.$g_image->realname.'"',$g_img);
							$g_title=$g_image->grid_reference.' : '.htmlentities2($g_image->title).' by '.$g_image->realname;
							$posterText = str_replace("[[[$prefix$g_id]]]","<a href=\"http://{$server}/photo/$g_id\" target=\"_blank\" title=\"$g_title\">$g_img</a>".$postfix,$posterText);
						} else {
							$posterText = preg_replace("/(?<!\[)\[\[$prefix$g_id\]\]/","{<a href=\"http://{$server}/photo/$g_id\" target=\"_blank\">{$g_image->grid_reference} : {$g_image->title}</a>}".$postfix,$posterText);
						}
					}
					$global_thumb_count++;
				}
				$thumb_count++;
			} else {
				$posterText = str_replace("[[$prefix$g_id]]","<a href=\"http://{$server}/gridref/".str_replace(' ','+',$g_id)."\" target=\"_blank\">$g_id</a>",$posterText);
			}
		}
		
		//fails quickly if not using memcached!
		$memcache->name_set('fp',$mkey,$posterText,$memcache->compress,$memcache->period_long);
		
	}
}

if (empty($CONF['disable_discuss_thumbs'])) {
	$posterText = preg_replace('/\[image id=(\d+)\]/e',"smarty_function_gridimage(array(id => '\$1',extra => '{description}'))",$posterText,5);
	$posterText = preg_replace('/\[image id=(\d+) text=([^\]]+)\]/e',"smarty_function_gridimage(array(id => '\$1',extra => '\$2'))",$posterText,5);
}

//no external images
// the callback function
$fixExternalImages= <<<FUNC
	if (in_array(\$matches[2],\$GLOBALS['domainWhitelist']))
	{
		//this is fine
		return \$matches[0];
	}
	else
	{
		\$url=\$matches[1].\$matches[2].\$matches[3];
		//no external images allowed
		return "<a title=\"Externally hosted image - caution advised\" href=\"".htmlentities(\$url)."\">".htmlentities(\$url)."</a> (external image) ";	
	}
FUNC;

$domainWhitelist = array(
	'www.geograph.org.uk',
	's0.geograph.org.uk',
	't0.geograph.org.uk',
	'www.nearby.org.uk',
	'www.gravatar.com',
	'www.geograph.co.uk',
	'chart.apis.google.com',
	'geodatastore2.appspot.com',
	'wordle.net',
	'www.wordle.net'
);

$posterText=preg_replace_callback(
             '/<img src="(http:\/\/)([^\/]*)([^"]*)".*?>/is',
             create_function(
	             // single quotes are essential here,
	             // or alternative escape all $ as \$
	             '$matches',
	             $fixExternalImages),
             $posterText);
             

$listPosts.=ParseTpl($tpl);

$i=-$i;
if($ii==0) $ii++;
$anchor++;
}
while($cols=db_simpleSelect(1));


$iVal=intval(($numRows-1)/$viewmaxreplys);
if ($page<$iVal) {
	$listPosts.=ParseTpl(makeUp('main_posts_cell_next'));
}

if ($USER->user_id) {
	$myRes=mysql_query("insert into geobb_lastviewed set topic_id=$topic,user_id={$USER->user_id},last_post_id = $postID on duplicate key update last_post_id = if(last_post_id < $postID,$postID,last_post_id)",$GLOBALS['minibb_link']) or die('<p>'.mysql_error($GLOBALS['minibb_link']).'. Please, try another name or value.');
}

unset($result);unset($countRes);

$l_messageABC=($forum==11)?$l_sub_answer11:$l_sub_answer;
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
if (file_exists("templates/main_post_area_{$forum}.html")) {
	$templatename = "main_post_area_{$forum}";
} else {
	$templatename = "main_post_area";
} 
$mainPostArea=makeUp($templatename);
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

if (!$USER->hasPerm("basic") && file_exists("templates/main_posts_{$forum}_anon.html")) {
	$templatename = "main_posts_{$forum}_anon";
} elseif (file_exists("templates/main_posts_{$forum}.html")) {
	$templatename = "main_posts_{$forum}";
} else {
	$templatename = 'main_posts';
}
echo load_header(); echo ParseTpl(makeUp($templatename));
?>
