<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2006 Barry Hunter (geo@barryhunter.co.uk)
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;

if (empty($_GET['page']) || preg_match('/[^\w\.\,-]/',$_GET['page'])) {
	header("HTTP/1.0 404 Not Found");
	header("Status: 404 Not Found");
	$smarty->display('static_404.tpl');
	exit;
}

$isadmin=$USER->hasPerm('moderator')?1:0;

$template = 'article_article.tpl';
$cacheid = 'articles|'.$_GET['page'];
$cacheid .= '|'.$isadmin;
$cacheid .= '-'.(isset($_SESSION['article_urls']) && in_array($_GET['page'],$_SESSION['article_urls'])?1:0);
if (!empty($_GET['epoch']) && preg_match('/^[\w]+$/',$_GET['epoch'])) {
	$cacheid .= "--".$_GET['epoch'];
} else {
	$_GET['epoch'] = '';
}


function article_make_table($input) {
	static $idcounter=1;
	$rows = explode("\n",stripslashes($input));
	
	if (strpos($rows[0],'*') === 0) {
		$GLOBALS['smarty']->assign("include_sorttable",1);
		$output = '<table class="report sortable" id="table'.($idcounter++).'" border="1" bordercolor="#dddddd" cellspacing="0" cellpadding="5">';
	} else {
		$output = '<table class="report" id="table'.($idcounter++).'">';
	}
	$c = 1;
	foreach ($rows as $row) {
		$head = 0;
		if (strpos($row,'*') === 0) {
			$row = preg_replace('/^\*/','',$row);
			$output .= "<thead>";
			$head = 1;
		} elseif ($c ==1) {
			$output .= "<tbody>";
		}
		$output .= "<tr>";
		
		$row = preg_replace('/^\| | \|$/','',$row);
		$cells = explode(' | ',$row);
		
		foreach ($cells as $cell) {
			$output .= "<td>$cell</td>";
		}
		
		$output .= "</tr>";
		if ($head) {
			$output .= "</thead>";
			$output .= "<tbody>";
		}
		$c++;
	}
	return $output."</tbody></table>";
}

function smarty_function_articletext($input) {
	global $imageCredits,$smarty,$CONF;
	
	$output = preg_replace('/(^|\n)(-{7,})\n(.*?)\n(-{7,})/es',"article_make_table('\$3')",str_replace("\r",'',$input));

	if ($CONF['CONTENT_HOST'] != $_SERVER['HTTP_HOST']) {
		$output = str_replace($CONF['CONTENT_HOST'],$_SERVER['HTTP_HOST'],$output);
	}

	$output = preg_replace('/\!(\[+)/e','str_repeat("¬",strlen("$1"))',$output);

	$output = str_replace(
		array('[b]','[/b]','[big]','[/big]','[small]','[/small]','[i]','[/i]','[h2]','[/h2]','[h3]','[/h3]','[h4]','[/h4]','[tt]','[/tt]','[float]','[/float]','[br/]','[hr/]','[reveal]','[/reveal]'),
		array('<b>','</b>','<big>','</big>','<small>','</small>','<i>','</i>','<h2>','</h2>','<h3>','</h3>','<h4>','</h4>','<tt>','</tt>','<div style="float:left;padding-right:10px;padding-bottom:10px;position:relative">','</div>','<br style="clear:both"/>','<hr align="center" width="75%"/>','<span style="color:white">','</span>'),
		$output);

	$pattern=array(); $replacement=array();
	
	if (preg_match_all('/<h(\d)>([^\n]+?)<\/h(\d)>/',$output,$matches)) {
		$list = array();
		foreach ($matches[1] as $i => $level) {
			$list[] = "<li class=\"h$level\"><a href=\"#p$i\">{$matches[2][$i]}</a></li>";
			$pattern[]='/<h('.$level.')>('.preg_quote($matches[2][$i], '/').')<\/h('.$level.')>/';
			$replacement[]='<h$1><a name="p'.$i.'"></a>$2</h$3>';
		}
		$list = implode("\n",$list);
		$smarty->assign("tableContents", $list);
	}
	
	$pattern[]='/<\/h(\d)>\n(?!\*)/';
	$replacement[]='</h$1>';


	$pattern[]='/(?<!["\'\[\/\!\w])([STNH]?[A-Z]{1}\d{4,10})(?!["\'\]\/\!\w])/';
	$replacement[]="<a href=\"http://{$_SERVER['HTTP_HOST']}/gridref/\\1\" target=\"_blank\">\\1</a>";

	$pattern[]='/\[image id=(\d+) text=([^\]]+)\]/e';
	$replacement[]="smarty_function_gridimage(array(id => '\$1',extra => '\$2'))";


	$pattern[]='/(\!)([STNH]?[A-Z]{1}\d{4,10})(?!["\'\]\/\!\w])/';
	$replacement[]="\\2";

	$pattern[]='/\[img=([^\] ]+)(| [^\]]+)\]/';
	$replacement[]='<img src="\1" alt="\2" title="\2"/>';

	$pattern[]='/\[mooflow=(\d+)\]/';
	$replacement[]='<iframe src="/search.php?i=\1&amp;temp_displayclass=mooflow_embed" width="750" height="430"></iframe>';

	$pattern[]='/\n\* ?([^\n]+)(\n{2})?/e';
	$replacement[]="'<ul style=\"margin-bottom:0px;margin-top:0px\"><li>'.stripslashes('\$1').'</li></ul>'.('$2'?'\n':'')";
	$pattern[]='/<\/ul>\n?<ul style=\"margin-bottom:0px;margin-top:0px\">/';
	$replacement[]='';

	//fix a bug where double spacing on a previous match would swallow the newline needed for the next
	$pattern[]='/\n\n(<\w{1,3}>)\#/';
	$replacement[]="\n\$1#";
	
	$pattern[]='/\n\n\#/';
	$replacement[]="\n\r\n\$1#";
	
	$pattern[]='/\n(<\w{1,3}>)?\#([\w]{1,2})? ([^\n]+)(<\/\w{1,3}>)?(\n{2})?/e';
	$replacement[]="'<ol style=\"margin-bottom:0px;'.('\$1'?'':'margin-top:0px').'\"'.('\$2'?' start=\"\$2\"':'').'><li>\$1\$3\$4</li></ol>'.('\$5'?'\n':'')";
	$pattern[]='/<\/ol>\n?<ol style=\"margin-bottom:0px;margin-top:0px\">/';
	$replacement[]='';


	$pattern[]="/\[url[=]?\](.+?)\[\/url\]/i";
	$replacement[]='\1';

	$pattern[]="/\[url=((f|ht)tp[s]?:\/\/[^<> \n]+?)\](.+?)\[\/url\]/ie";
	$replacement[]="smarty_function_external(array('href'=>\"\$1\",'text'=>'\$3','title'=>\"\$1\"))";


	$pattern[]="/\n/";
	$replacement[]="<br/>\n";

	$output=preg_replace($pattern, $replacement, $output);
	
	$output = GeographLinks($output,true);
	
	$pattern=array(); $replacement=array();
	
	if (preg_match_all('/\[(small|)map *([STNH]?[A-Z]{1}[ \.]*\d{2,5}[ \.]*\d{2,5})( \w+|)\]/',$output,$m)) {
		foreach ($m[0] as $i => $full) {
			//lets add an rastermap too
			$square = new Gridsquare;
			$square->setByFullGridRef($m[2][$i],true);
			$square->grid_reference_full = 	$m[2][$i];
			if (!empty($_GET['epoch'])) {
				$rastermap = new RasterMap($square,false,true,false,$_GET['epoch']);
			} elseif (!empty($m[3][$i])) {
				$rastermap = new RasterMap($square,false,true,false,trim($m[3][$i]));
			} else {
				$rastermap = new RasterMap($square,false);
			}
			if ($rastermap->service == 'OS50k') {
				if ($m[1][$i]) {
					$rastermap->service = 'OS50k-small';
					$rastermap->width = 125;
				}
				
				$pattern[] = "/".preg_quote($full, '/')."/";
				$replacement[] = $rastermap->getImageTag();
				
			}
		}
	}
	
	if (count($imageCredits)) {
		arsort($imageCredits);

		$imageCreditsStr = implode(', ',array_keys($imageCredits));

		$imageCreditsStr = preg_replace('/, ([^,]+)$/',' and $1',$imageCreditsStr);

		$smarty->assign("imageCredits", $imageCreditsStr);
		
		$pattern[]="/\[imageCredits\]/i";
		$replacement[]=$imageCreditsStr;
	}
	
	$output=preg_replace($pattern, $replacement, $output);
	
	$output=str_replace('¬','[',$output);
	
	if (count($m[0])) {
		$smarty->assign("copyright", '<div class="copyright">Great Britain 1:50 000 Scale Colour Raster Mapping Extracts &copy; Crown copyright Ordnance Survey. All Rights Reserved. Educational licence 100045616.</div>');
	}
	
	return $output;
}

$smarty->register_modifier("articletext", "smarty_function_articletext");

$db = GeographDatabaseConnection(false);

$page = $db->getRow("
select article.*,realname,gs.grid_reference,category_name
from article 
	left join user using (user_id)
	left join article_cat c on (article.article_cat_id = c.article_cat_id)
	left join gridsquare gs on (article.gridsquare_id = gs.gridsquare_id)
where ( (licence != 'none' and approved > 0) 
	or user.user_id = {$USER->user_id}
	or $isadmin )
	and url = ".$db->Quote($_GET['page']).'
limit 1');
if (count($page)) {
	$cacheid .= '|'.$page['update_time'];
	
	if ($page['user_id'] == $USER->user_id) {
		$cacheid .= '|'.$USER->user_id;
	}

	if (!isset($_GET['dontcount']) && $CONF['template']!='archive' && @strpos($_SERVER['HTTP_REFERER'],$page['url']) === FALSE) {
		$db->Execute("UPDATE LOW_PRIORITY article_stat SET views=views+1 WHERE article_id = ".$page['article_id']);
	}
	
	//when this page was modified
	$mtime = strtotime($page['update_time']);
	
	//can't use IF_MODIFIED_SINCE for logged in users as has no concept as uniqueness
	customCacheControl($mtime,$cacheid,($USER->user_id == 0));

} else {
	header("HTTP/1.0 404 Not Found");
	header("Status: 404 Not Found");
	$template = 'static_404.tpl';
}

if (!$smarty->is_cached($template, $cacheid))
{
	if (count($page)) {
		$CONF['global_thumb_limit'] *= 2;
		$CONF['post_thumb_limit'] *= 2;
		
		$smarty->assign($page);
		if (!empty($page['extract'])) {
			$smarty->assign('meta_description', "User contributed article about, ".$page['extract']);
		}
		
		if (!empty($page['gridsquare_id'])) {
			$square=new GridSquare;
			$square->loadFromId($page['gridsquare_id']);
			$smarty->assign('grid_reference', $square->grid_reference);
			
			require_once('geograph/conversions.class.php');
			$conv = new Conversions;
		
			list($lat,$long) = $conv->gridsquare_to_wgs84($square);
			$smarty->assign('lat', $lat);
			$smarty->assign('long', $long);
		}
		if (preg_match('/\bgeograph\b/i',$page['category_name'])) {
			$db->Execute("set @last=0");
			$users = $db->getAll("select realname,modifier,if(approved = @last,1,least(@last := approved,0)) as same 
			from article_revisions 
			left join user on (article_revisions.modifier = user.user_id)
			where article_id = {$page['article_id']} order by article_revision_id");
			$arr = array();
			foreach ($users as $idx => $row) {
				if ($row['same'] == 1 && $row['modifier'] != $page['user_id'] && !isset($arr[$row['modifier']])) {
					$arr[$row['modifier']] = "<a href=\"/profile/{$row['modifier']}\">".htmlentities2($row['realname'])."</a>";
				}
			}
			$str = preg_replace('/, ([^\,]*?)$/',' and $1',implode(', ',$arr));
			$smarty->assign('moreCredits',$str);
		}
	} 
} else {
	$smarty->assign('user_id', $page['user_id']);
	$smarty->assign('url', $page['url']);
}




$smarty->display($template, $cacheid);

	
?>
