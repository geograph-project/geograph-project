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

if (empty($_POST)) {
	init_session_or_cache(3600*3, 900); //cache publically, and privately
} else {
	init_session();
}

$smarty = new GeographPage;

if (empty($_GET['url']) || preg_match('/[^\w\.\,-]/',$_GET['url'])) {
	header("HTTP/1.0 404 Not Found");
	header("Status: 404 Not Found");
	$smarty->display('static_404.tpl');
	exit;
}

if ($_GET['url'] == 'Shepherd-Neame2') {
	header("HTTP/1.0 301 Moved Permanently");
	header("Status: 301 Moved Permanently");
	header("Location: /article/Shepherd-Neame/2");
	print "<a href=\"/article/Shepherd-Neame/2\">go</a>";
	exit;
}

$isadmin=($USER->hasPerm('moderator') || $USER->hasPerm('director'))?1:0;

$smarty->assign('ismod',$USER->hasPerm('moderator'));

$template = 'article_article.tpl';

if ($_GET['url'] == 'preview') {
	$cacheid = time().md5($_POST['title']);
} else {

	$cacheid = 'articles|'.$_GET['url'].'|'.intval($_GET['page']);
	$cacheid .= '|'.$isadmin;
	$cacheid .= '-'.(isset($_SESSION['article_urls']) && in_array($_GET['url'],$_SESSION['article_urls'])?1:0);
	if (!empty($_GET['epoch']) && preg_match('/^[\w]+$/',$_GET['epoch'])) {
		$cacheid .= "--".$_GET['epoch'];
	} else {
		$_GET['epoch'] = '';
	}
	if (!empty($_GET['inner'])) {
		$cacheid .= "inner";
		$smarty->assign('inner',1);
	}
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

function getUniqueHash($title) {
	static $usedTitles;
	if (empty($usedTitles)) {
		$usedTitles = array();
	}
	$title = str_replace(' ','-',trim(preg_replace('/[^\w \-]+/','',strtolower($title))));
	$i ='';
	while (isset($usedTitles[$title.$i])) {
		$i++;
	}
	$usedTitles[$title.$i] = 1;
	return $title.$i;
}

function get_snippet($snippet,$gridimage_id = 0) {
	global $imageCredits;
	global $db;

	$snippet = intval($snippet);

	if (empty($db))
		$db = GeographDatabaseConnection(true);

	$row = $db->getRow("SELECT s.*,realname FROM snippet s INNER JOIN user u USING (user_id) WHERE snippet_id = $snippet AND enabled = 1");

	if (empty($row)) {
		return "ERROR unable to load shared description $snippet";
	}

	$count = $db->getOne("SELECT count(*) FROM gridimage_snippet WHERE gridimage_id < 4294967296 AND snippet_id = $snippet");

	$html='<div class="photoguide"><small style="background-color:silver;padding:3px"><i>Shared Description</i> used on <a title="view more images" href="/snippet/'.$row['snippet_id'].'">'.($count+0).' images</a></small>';

	$html.='<div style="float:left;width:400px">';

		$html.='<div><b><a title="view more images" href="/snippet/'.$row['snippet_id'].'">';
		$html.=htmlentities2($row['title']).'</a></b> by <a href="/profile/'.$row['user_id'].'">'.htmlentities2($row['realname']).'</a></div>';

		$html .= GeographLinks(nl2br(htmlentities2($row['comment'])));

	$html.='</div>';

	if (empty($gridimage_id)) {
		$gridimage_id = $db->getOne("SELECT gridimage_id FROM gridimage_snippet WHERE gridimage_id < 4294967296 AND snippet_id = $snippet");
	}
	$gridimage_id = intval($gridimage_id);

	if (!empty($gridimage_id)) {
		$image=new GridImage;

		$image->loadFromId($gridimage_id);

		if (!empty($image->gridimage_id) && $image->moderation_status != 'rejected') {

			if (isset($imageCredits[$image->realname])) {
				$imageCredits[$image->realname]++;
			} else {
				$imageCredits[$image->realname]=1;
			}

			$html.='<div style="float:left;width:213px"><br/><br/>';

				$title=$image->grid_reference.' : '.htmlentities2($image->title).' by '.htmlentities2($image->realname);

				$html.='<a title="'.$title.' - click to view full size image" href="/photo/'.$image->gridimage_id.'">';
				$html.=$image->getThumbnail(120,120);
				$html.='</a><div class="caption"><a title="view full size image" href="/photo/'.$image->gridimage_id.'">';
				$html.=htmlentities2($image->title).'</a> by <a href="'.$image->profile_link.'">'.htmlentities2($image->realname).'</a></div>';

			$html.='</div>';

		} else {
			$html .= "image=$gridimage_id";
		}
	}

	$html.='<br style="clear:both"/>';

	if (preg_match('/National Cycle.* (\d+)/i',$row['title'],$m)) {
		$sphinxq = "national cycle|cycleway \"route {$m[1]}\"";
                $sphinx = new sphinxwrapper($sphinxq);
		$count = $sphinx->countMatches('_images');
		if ($count) {
			$html .="<small><i>There are <b>$count images</b> matching (<a href=\"/search.php?text=".urlencode($sphinxq)."\">".htmlentities($sphinxq)."</a>)</i></small>";
		}
	}
	$html.='</div>';

	return $html;
}


function get_tag($tag,$gr,$images = 4) {
	global $imageCredits;
	global $db;

	if (empty($db))
		$db = GeographDatabaseConnection(true);

	if (1) {
		$where = array();
                $where['prefix'] = "prefix = ''";

		$tag2 = preg_replace('/[^\w:]+/',' ',$tag);		

                if (strpos($tag,':') !== FALSE) {
                        list($prefix,$tag) = explode(':',$tag,2);

                        $where['prefix'] = "prefix = ".$db->Quote($prefix);
			$sphinxq = "tags:\"__TAG__ $prefix ".preg_replace('/[^\w]+/',' ',$tag)." __TAG__\"";
                } else {
			$sphinxq = "tags:\"__TAG__ ".preg_replace('/[^\w]+/',' ',$tag)." __TAG__\"";
		}
                $where['tag'] = "tag = ".$db->Quote($tag);

                $row= $db->getRow("SELECT tag_id,prefix,tag,description,canonical FROM tag WHERE status = 1 AND ".implode(' AND ',$where));
	}

	if (empty($row)) {
		return "ERROR unable to load tag [".htmlentities($tag)."]";
	}
	$tag_ids = array($row['tag_id']);	

	if (!empty($row['canonical'])) {
		if ($row2 = $db->getRow("SELECT prefix,tag FROM tag WHERE status = 1 AND tag_id = ".intval($row['canonical']))) {
			$sphinxq = " ($sphinxq) | (\"__TAG__ ".preg_replace('/[^\w]+/',' ',implode(' ',$row2))." __TAG__\")";
			$tag_ids[] = $row['canonical'];
		}
	}

	$count = $db->getOne("SELECT count(*) FROM tag_public WHERE tag_id IN (".implode(',',$tag_ids).")");

	if ($row['prefix']) {
		$tag_url = urlencode($row['prefix'].':'.$row['tag']);
		$tag_html = htmlentities($row['prefix'].':'.$row['tag']);
	} else {
		$tag_url = urlencode($row['tag']);
		$tag_html = htmlentities($row['tag']);
	}

	$html='<div class="interestBox"><small>Tag:</small><b><a href="/tags/?tag='.$tag_url.'">'.$tag_html.'</a></b><small> ('.($count+0).' images)</small></div>';

	if (strlen($images) == 0)
		$images= 4;

	if (!empty($count) && $images) {
		$html.='<div class="interestBox" style="background-color:gray">';

		if (!empty($gr)) {
			$sphinxq = "$gr ($sphinxq)";
		}

		$sphinx = new sphinxwrapper($sphinxq);
		if (!empty($gr)) {
			$sphinx->processQuery();
		}
	        $sphinx->pageSize = $pgsize = intval($images);
		$pg = 1;
                $client = $sphinx->_getClient();

		$number = intval($pgsize/3); if ($number < 2) $number = 2; if ($number > 5) $number = 5; 
		
                        $bits = array();
                        $bits[] = "withinfirstx(atakenyear,$number)";
                        $bits[] = "withinfirstx(takendays,$number)";
                        $bits[] = "withinfirstx(classcrc,$number)";
                        $bits[] = "withinfirstx(scenti,$number)";
                        if (!preg_match('/user_id/',$sphinxq)) {
                                $bits[] = "withinfirstx(auser_id,$number)";
                        }
                        $client->setSelect(implode('+',$bits)." as myint,id as group");
                        $values = array();
                        $values[] = count($bits);
                        #if ($group == 'all')
                        #        $values[] = count($bits)-1;
                        $client->setFilter('myint',$values);
                        $sphinx->sort = 'myint DESC,@id DESC';

                $ids = $sphinx->returnIds($pg,'_images');
		if (!empty($ids)) {

			if (count($ids) > 40) {
				$ids = array_slice($ids,0,40);
			}

			if (!empty($gr)) {
				$html = str_replace('?tag','?photo='.$ids[0].'&amp;tag',$html);
			}

			foreach ($ids as $gridimage_id) {
				$image=new GridImage;
				$image->loadFromId($gridimage_id);

				if (!empty($image->gridimage_id) && $image->moderation_status != 'rejected') {

					if (isset($imageCredits[$image->realname])) {
						$imageCredits[$image->realname]++;
					} else {
						$imageCredits[$image->realname]=1;
					}

					$html.='<div style="float:left;width:130px;height:130px">';

					$title=$image->grid_reference.' : '.htmlentities2($image->title).' by '.htmlentities2($image->realname);

					$html.='<a title="'.$title.' - click to view full size image" href="/photo/'.$image->gridimage_id.'">';
					$html.=$image->getThumbnail(120,120);
					$html.='</a></div>';
				}
			}
			$html.='<br style="clear:both"/></div>';
		}
	}

	return $html;
}




function smarty_function_articletext($input) {
	global $imageCredits,$smarty,$CONF;

	$output = preg_replace('/(^|\n)(-{7,})\n(.*?)\n(-{7,})/es',"article_make_table('\$3')",str_replace("\r",'',$input));

	if ($CONF['CONTENT_HOST'] != $CONF['SELF_HOST']) {
		$output = str_replace($CONF['CONTENT_HOST'],$CONF['SELF_HOST'],$output);
	}

	$output = preg_replace('/\!(\[+)/e','str_repeat("�",strlen("$1"))',$output);

	$output = str_replace(
		array('[b]','[/b]','[big]','[/big]','[small]','[/small]','[i]','[/i]','[h2]','[/h2]','[h3]','[/h3]','[h4]','[/h4]','[tt]','[/tt]','[float]','[/float]','[br/]','[hr/]','[reveal]','[/reveal]'),
		array('<b>','</b>','<big>','</big>','<small>','</small>','<i>','</i>','<h2>','</h2>','<h3>','</h3>','<h4>','</h4>','<tt>','</tt>','<div style="float:left;padding-right:10px;padding-bottom:10px;position:relative">','</div>','<br style="clear:both"/>','<hr align="center" width="75%"/>','<span style="color:white">','</span>'),
		$output);

	$pattern=array(); $replacement=array();

	if ($pages = preg_split("/\n+\s*~{6,}(~[\w \t\{\}\+:-]*)\n+/",$output,-1,PREG_SPLIT_DELIM_CAPTURE)) {

		if (!empty($_GET['page']) && $_GET['page'] == 9999) {
			$pages = array(implode("\n[hr/]\n",$pages));
			$_GET['page'] = 1;
		}

		$numberOfPages = ceil(count($pages)/2);
		$thispage = empty($_GET['page'])?1:intval($_GET['page']);
		$thispage = min($numberOfPages,$thispage);
		$thispage = max(1,$thispage);
		$offset = ($thispage-1)*2;
		if (stripos($pages[$offset],'[singlePageContents]')!==FALSE) {
			$pages[$offset] = str_ireplace('[singlePageContents]','',$pages[$offset]);
			$singlePageContents = $thispage;
		}
	}

	if (preg_match_all('/<h(\d)>([^\n]+?)<\/h(\d)>/',$output,$matches)) {
		$list = array();
		if (count($pages) > 1) {
			foreach ($pages as $idx => $onepage) {
				if (preg_match_all('/<h(\d)>([^\n]+?)<\/h(\d)>/',$onepage,$matches)) {
					$style = ($idx)?' style="border-top:1px solid silver"':'';
					$offset = floor($idx/2)+1;
					$url = ($offset==$thispage)?'':"/article/{$GLOBALS['page']['url']}/$offset";
					foreach ($matches[1] as $i => $level) {
						$hash = getUniqueHash($matches[2][$i]);
						if (empty($singlePageContents) || $singlePageContents == $offset)
							$list[] = "<li class=\"h$level\"$style><a href=\"$url#$hash\">{$matches[2][$i]}</a></li>";
						$pattern[]='/<h('.$level.')>('.preg_quote($matches[2][$i], '/').')<\/h('.$level.')>/';
						$replacement[]='<h$1><a name="'.$hash.'"></a><a name="p'.$i.'"></a>$2</h$3>';
						$style = '';
					}
				}
			}
		} else {
			foreach ($matches[1] as $i => $level) {
				$hash = getUniqueHash($matches[2][$i]);
				$list[] = "<li class=\"h$level\"><a href=\"#$hash\">{$matches[2][$i]}</a></li>";
				$pattern[]='/<h('.$level.')>('.preg_quote($matches[2][$i], '/').')<\/h('.$level.')>/';
				$replacement[]='<h$1><a name="'.$hash.'"></a><a name="p'.$i.'"></a>$2</h$3>';
			}
		}
		$list = implode("\n",$list);
		$smarty->assign("tableContents", $list);
	}

	if (count($pages) > 1) {
		$smarty->assign('page',$thispage);
		$offset = ($thispage-1)*2;
		if ($thispage > 1 && strlen($pages[$offset-1]) > 1) {
			$smarty->assign('pagetitle',substr($pages[$offset-1],1));
		}
		$output = $pages[$offset];
	}

	if (!empty($_GET['test'])) {
		print "<pre>";
		print htmlentities(print_r($pages,1));
		exit;
	}


	$pattern[]='/<\/h(\d)>\n(?!\*)/';
	$replacement[]='</h$1>';


	$pattern[]='/(?<!["\'\[\/\!\w])([STNH]?[A-Z]{1}\d{4,10})(?!["\'\]\/\!\w])/';
	$replacement[]="<a href=\"{$CONF['SELF_HOST']}/gridref/\\1\" target=\"_blank\">\\1</a>";

	$pattern[]='/\[image id=([a-z]*:?\d+) text=([^\]"]+)\]/e';
	$replacement[]="smarty_function_gridimage(array(id => '\$1',extra => '\$2'))";

	$pattern[]='/\[image id=([a-z]*:?\d+)\]/e';
	$replacement[]="smarty_function_gridimage(array(id => '\$1',extra => '{description}'))";


	$pattern[]='/\[snippet id=(\d+)(?: image=(\d+))?\]/e';
	$replacement[]="get_snippet('\$1','\$2')";

	$pattern[]='/\[tag=([^=\]"]+?)(?: gr=(\w{1,2}\d{0,4}))?(?: images=(\d+))?\]/e';
	$replacement[]="get_tag('\$1','\$2','\$3')";

	$pattern[]='/(\!)([STNH]?[A-Z]{1}\d{4,10})(?!["\'\]\/\!\w])/';
	$replacement[]="\\2";

	$pattern[]='/\[img=([^\]" ]+)(| [^\]"]+)\]/';
	$replacement[]='<img src="\1" alt="\2" title="\2"/>';

	$pattern[]='/\[mooflow=(\d+)\]/';
	$replacement[]='<iframe src="/search.php?i=\1&amp;temp_displayclass=mooflow_embed" width="750" height="430"></iframe>';

	$pattern[]='/\[gmap=(\d+)\]/';
	$replacement[]='<iframe src="/search.php?i=\1&amp;temp_displayclass=gmap_embed" width="750" height="430"></iframe>';

	$pattern[]='/\[slidebig=(\d+)\]/';
	$replacement[]='<iframe src="/search.php?i=\1&amp;temp_displayclass=slidebig" width="750" height="750"></iframe>';

	$pattern[]='/\[fade=([\w\/:\.]+)=(\d+)\]/';
	$replacement[]='<iframe src="/stuff/fade.php?1=\1&amp;2=\2" width="670" height="700"></iframe>';

	$pattern[]='/\[youtube=([\w-]+)\]/';
	$replacement[]='<object width="480" height="385"><param name="movie" value="https://www.youtube-nocookie.com/v/\1&hl=en_US&fs=1&rel=0"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="https://www.youtube-nocookie.com/v/\1&hl=en_US&fs=1&rel=0" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="480" height="385"></embed></object>';

	$pattern[]='/\[zoomit=(\w+)\]/';
	$replacement[]='<script src="http://zoom.it/\1.js?width=auto&height=400px"></script>';

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

	$pattern[]="/\[url=http:\/\/getamap\.ordnancesurvey\.co\.uk\/getamap\/frames\.htm.*?gazString=(\w+)\](.+?)\[\/url\]/ie";
	$replacement[]="smarty_function_getamap(array('gridref'=>\"\$1\",'text'=>'\$2'))";

	$pattern[]="/\[url=((f|ht)tp[s]?:\/\/[^<> \n\"]+?)\](.+?)\[\/url\]/ie";
	$replacement[]="smarty_function_external(array('href'=>\"\$1\",'text'=>'\$3','title'=>\"\$1\"))";

	$pattern[]="/\[url=#([\w-]+)\](.+?)\[\/url\]/i";
	$replacement[]='<a href="#\1">\2</a>';


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

	if ($CONF['PROTOCOL'] == 'https://') {
		$pattern[]='/"http:\/\/(s\d|t0|m|media)\.geograph\.org\.uk/';
		$replacement[]='"https://\1.geograph.org.uk';
	}

	$output=preg_replace($pattern, $replacement, $output);

	$output=str_replace('�','[',$output);

if ($GLOBALS['page']['url'] == "About-Geograph-page") {
        $output=preg_replace('/\bGeograph\b(?![^<]*>)/','Geograph<sup style="font-size:8pt;text-decoration:none">&reg;</sup>',$output,4);
}

	if (count($m[0])) {
		$smarty->assign("copyright", '<div class="copyright">Great Britain 1:50 000 Scale Colour Raster Mapping Extracts &copy; Crown copyright Ordnance Survey. All Rights Reserved. Educational licence 100045616.</div>');
	}

	if (count($pages) > 1) {
		$smarty->assign('pagesString', pagesString($thispage,$numberOfPages,"/article/{$GLOBALS['page']['url']}/"));
		if ($numberOfPages > $thispage) {
			if (strlen($pages[$offset+1]) > 1) {
				$smarty->assign('nextString', "<a href=\"/article/{$GLOBALS['page']['url']}/".($thispage+1)."\" rel=\"next\">Next page: <b>".htmlentities2(substr($pages[$offset+1],1))."</b>...</a>");
			} else {
				$smarty->assign('nextString', "<a href=\"/article/{$GLOBALS['page']['url']}/".($thispage+1)."\" rel=\"next\">Continued on next page...</a>");
			}
		}
	}

	if ($GLOBALS['global_thumb_count'] > 100 && $GLOBALS['USER']->user_id == $GLOBALS['page']['user_id']) {
		$smarty->assign('user_warning', "The number of images ({$GLOBALS['global_thumb_count']}) on this page is rather high. The recommended limit is 100. Please consider splitting the content into separate pages");
	}
	return $output;
}

$smarty->register_modifier("articletext", "smarty_function_articletext");

if ($_GET['url'] == 'preview') {

	$smarty->reassignPostedDate('publish_date');
	$_POST['title'] = preg_replace('/[^\w\-\.,:;\' ]+/','',trim($_POST['title']));
	if (empty($_POST['url']) && !empty($_POST['title'])) {
		$_POST['url'] = $_POST['title'];
	}
	$_POST['url'] = preg_replace('/ /','-',trim($_POST['url']));
	$_POST['url'] = preg_replace('/[^\w-]+/','',$_POST['url']);
	
	$gs=new GridSquare();
	if (!empty($_POST['grid_reference'])) {
		if ($gs->setByFullGridRef($_POST['grid_reference'])) {
			$_POST['gridsquare_id'] = $gs->gridsquare_id;
		} else 
			$errors['grid_reference'] = $gs->errormsg;
	}
	
	$_POST['content'] = strip_tags($_POST['content']);
	$_POST['content'] = preg_replace('/[��]/','',$_POST['content']);

	$_POST['extract'] = strip_tags($_POST['extract']);
	$_POST['extract'] = preg_replace('/[��]/','',$_POST['extract']);
	
	if (!empty($_POST['page'])) {
		$_GET['page'] = intval($_POST['page']);
	}
	
	$_POST['title'] = "Preview of: {$_POST['title']} -- NOT SAVED";
	
	$page = $_POST;
	
} else {
	$db = GeographDatabaseConnection(false);

	$page = $db->getRow("
	select article.*,realname,gs.grid_reference,category_name,c.type as ctype
	from article 
		left join user using (user_id)
		left join article_cat c on (article.article_cat_id = c.article_cat_id)
		left join gridsquare gs on (article.gridsquare_id = gs.gridsquare_id)
	where ( (licence != 'none' and approved > 0) 
		or user.user_id = {$USER->user_id}
		or $isadmin )
		and url = ".$db->Quote($_GET['url']).'
	limit 1');
	if (count($page)) {
		$cacheid .= '|'.$page['update_time'];

		if ($page['user_id'] == $USER->user_id) {
			$cacheid .= '|'.$USER->user_id;
		}

		if (!isset($_GET['dontcount']) && @strpos($_SERVER['HTTP_REFERER'],$page['url']) === FALSE && appearsToBePerson()) {
			$db->Execute("UPDATE LOW_PRIORITY article_stat SET views=views+1 WHERE article_id = ".$page['article_id']);
		}

		if (preg_match('/^This content has been moved to\s+(http:\/\/[\w\.\/-]+)\s*$/',$page['content'],$m)) {
		        header("HTTP/1.0 301 Moved Permanently");
		        header("Status: 301 Moved Permanently");
		        header("Location: {$m[1]}");
		        print "<a href=\"{$m[1]}\">go</a>";
			exit;
		}


		//when this page was modified
		$mtime = strtotime($page['update_time']);

		//can't use IF_MODIFIED_SINCE for logged in users as has no concept as uniqueness
		customCacheControl($mtime,$cacheid,($USER->user_id == 0));

		if (preg_match('/\bgeograph\b/i',$page['category_name']) || $page['ctype'] == 'document') {
			$template = 'article_article2.tpl';

			pageMustBeHTTPS();
		}

		if (strlen($page['content']) < 100) {
			header("HTTP/1.1 503 Service Unavailable");
		}
	} else {
		header("HTTP/1.0 404 Not Found");
		header("Status: 404 Not Found");
		$template = 'static_404.tpl';
	}
}

if (!$smarty->is_cached($template, $cacheid))
{
	if (count($page)) {
		#$CONF['global_thumb_limit'] *= 2;
		#$CONF['post_thumb_limit'] *= 2;

		$smarty->assign($page);
		if (!empty($page['extract'])) {
			$smarty->assign('meta_description', "User contributed article about, ".$page['extract']);
		}
		if ($page['url'] != $_GET['url']) {
			$smarty->assign('extra_meta', "<link rel=\"canonical\" href=\"{$CONF['CONTENT_HOST']}/article/{$page['url']}".(empty($_GET['page'])?'':"/".intval($_GET['page']))."\"/>");
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
		if (preg_match('/\bgeograph\b/i',$page['category_name']) || $page['ctype'] == 'document') {
			if (empty($db))
			        $db = GeographDatabaseConnection(true);

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

		if (!empty($page['parent_url'])) {
			if (empty($db))
			        $db = GeographDatabaseConnection(true);

			$parent = $db->getRow("SELECT url AS parent_url,title AS parent_title FROM article WHERE approved > 0 AND url LIKE ".$db->Quote("%".preg_replace("/^.*\//",'',$page['parent_url'])));
			if (!empty($parent) && $parent['parent_url'] != $page['url']) {
				$smarty->assign($parent);
			}
		}
	}
} else {
	$smarty->assign('edit_prompt', $page['edit_prompt']);
	$smarty->assign('approved', $page['approved']);
	$smarty->assign('user_id', $page['user_id']);
	$smarty->assign('url', $page['url']);
}




$smarty->display($template, $cacheid);

