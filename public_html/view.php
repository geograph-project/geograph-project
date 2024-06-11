<?php
/**
 * $Project: GeoGraph $
 * $Id: view.php 8912 2019-03-18 10:45:55Z barry $
 *
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Paul Dixon (paul@elphin.com)
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

if (empty($_SERVER['HTTP_USER_AGENT']))
        die("no scraping");

if (strpos($_SERVER['HTTP_USER_AGENT'], 'python-requests')!==FALSE)
        die("no scraping");

require_once('geograph/global.inc.php');

if (isset($_GET['id']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'BingPreview/1.0b')!==FALSE) ) {

	$db = GeographDatabaseConnection(true);

        $row = $db->getRow("select gridimage_id,realname,title,grid_reference from gridimage_search where gridimage_id=".intval($_GET['id']) );

	print "<html><head><title>".htmlentities($row['title'])." by ".htmlentities($row['realname'])."</title>";
	print "</head><body style=\"font-family:georgia;color:white;background-color:#000066\">";

	print "<p><a href=\"http://creativecommons.org/licenses/by-sa/2.0/\" rel=\"licence\">";
	print "<img src=\"http://creativecommons.org/images/public/somerights20.gif\" width=\"200\" height=\"70\" alt=\"cc-by-sa/2.0\" border=\"0\"></a></p>";

	print "<p><big style=\"font-size:4.3em\">Image <b> &copy; ".htmlentities($row['realname'])."</b> and licensed for reuse as per <span style=\"white-space:nowrap;\">cc-by-sa/2.0</span></big></p>";

	print "<a href=\"http://geograph.org.uk/p/{$row['gridimage_id']}\" style=\"color:yellow;font-size:3em\">http://geograph.org.uk/p/{$row['gridimage_id']}</a>";
	print "</body></html>";
	exit;

} elseif ((strpos($_SERVER["REQUEST_URI"],'/photo/') === FALSE && isset($_GET['id'])) || strlen($_GET['id']) !== strlen(intval($_GET['id']))) {
	//keep urls nice and clean - esp. for search engines!
	header("HTTP/1.0 301 Moved Permanently");
	header("Status: 301 Moved Permanently");
	header("Location: ".$CONF['canonical_domain'][1]."/photo/".intval($_GET['id']));
	print "<a href=\"".$CONF['canonical_domain'][1]."/photo/".intval($_GET['id'])."\">View image page</a>";
	exit;
}


require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/mapmosaic.class.php');
require_once('geograph/rastermap.class.php');

if (!empty($_POST['style'])) {
	session_cache_limiter('private_no_expire'); //this is just to override the default no-store that gets added (so user can use backbutton)

	init_session();
	$_GET['style'] = $_POST['style']; //getStyle still uses _GET
	// getStyle is called later down the page

} elseif (isset($_GET['style'])) {
	init_session();
	$USER->getStyle();
	if (isset($_GET['id'])) {
		$_SESSION['setstyle'] = 1;
		header("HTTP/1.0 301 Moved Permanently");
		header("Status: 301 Moved Permanently");
		header("Location: ".$CONF['canonical_domain'][1]."/photo/".intval($_GET['id']));
		exit;
	}
	header("Location: /");
	exit;
} else {
	init_session_or_cache(3600, 0); //cache publically, and privately
}



customGZipHandlerStart();

$smarty = new GeographPage;

$template='view.tpl';

if (!empty($_GET['preview'])) {
	        require_once('geograph/imagelist.class.php');

	$smarty->assign('right_block','_block_recent.tpl');

        //lets find some recent photos
        new RecentImageList($smarty);
}


$cacheid=0;

if ($smarty->caching) {
	$smarty->caching = 2; // lifetime is per cache
	$smarty->cache_lifetime = 3600*3; //3hour cache
}

$image=new GridImage;

if (isset($_GET['id']))
{
	$image->loadFromId(intval($_GET['id']));

	$isowner=($image->user_id==$USER->user_id)?1:0;
	$ismoderator=($USER->hasPerm('moderator')||$USER->hasPerm('director'))?1:0;

	$ab=floor($_GET['id']/10000);

	$cacheid="img$ab|{$_GET['id']}|{$isowner}_{$ismoderator}";

	if (isset($_GET['expand'])) {
		$cacheid .= "E";
		$smarty->assign('expand',1);
		$CONF['global_thumb_limit'] = 4;
	}

	//is the image rejected? - only the owner and administrator should see it
	if ($image->moderation_status=='rejected')
	{
		if ($isowner||$ismoderator)
		{
			//ok, we'll let it lie...
		}
		else
		{
			$db = GeographDatabaseConnection(true);
			if ($row = $db->getRow("select destination,reference_index from gridimage_redirect r inner join gridimage_search gi on (destination = gi.gridimage_id) where r.gridimage_id = ".intval($_GET['id']))) {
				$to = $row['destination'];
		                header("HTTP/1.0 301 Moved Permanently");
                		header("Status: 301 Moved Permanently");
		                header("Location: ".$CONF['canonical_domain'][$row['reference_index']]."/photo/".intval($to));
                		exit;
			}

			//clear the image
			$image=new GridImage;
			$cacheid=0;
			$rejected = true;
		}
	}
}

//do we have a valid image?
if ($image->isValid())
{
	if ($image->grid_square->reference_index == 1
		&& $_SERVER['HTTP_HOST'] == 'www.geograph.ie' &&
			((stripos($_SERVER['HTTP_USER_AGENT'], 'http')!==FALSE) ||
			(stripos($_SERVER['HTTP_USER_AGENT'], 'bot')!==FALSE)) ) {
		header("HTTP/1.0 301 Moved Permanently");
		header("Status: 301 Moved Permanently");
		header("Location: ".$CONF['canonical_domain'][1]."/photo/".intval($_GET['id']));
		exit;
	} elseif ($image->grid_square->reference_index == 2 && $_SERVER['HTTP_HOST'] != 'www.geograph.ie' && $CONF['template']!='archive') {
		$smarty->assign("ireland_prompt",1);
	}

	pageMustBeHTTPS(); //in here so doesnt affect preview - and after all other redirects

	if ($image->grid_square) {
		$image->grid_square->rememberInSession();
	}

	//what style should we use?
	$style = $USER->getStyle();

	//when this image was modified
	$mtime = strtotime($image->upd_timestamp);

	//page is unqiue per user (the profile and links)
	$hash = $cacheid.'.'.$USER->user_id;

	//if they have just just changed the style dont allow sending a 304 :) (of course can still exploit the smarty cache)
	if (!empty($_SESSION['setstyle'])) {
		unset($_SESSION['setstyle']);
	} else {
		//can't use IF_MODIFIED_SINCE for logged in users as has no concept as uniqueness
		customCacheControl($mtime,$hash,($USER->user_id == 0));
	}

	if ($image->title == 'The War Memorial at Winchcombe') {
		$smarty->assign('extra_meta', "<link rel=\"canonical\" href=\"http://{$_SERVER['HTTP_HOST']}/of/title:".urlencode($image->title)."\"/>");
	}


	if (!empty($_SESSION['currentSearch']) && ($idx = array_search($image->gridimage_id,$_SESSION['currentSearch']['r'])) !== FALSE) {
		$s = $_SESSION['currentSearch']; //keep a copy to avoid adding next/prev to the session value
		if ($idx > 0) {
			$s['l'] = $s['r'][$idx-1];
		}
		if ($idx < count($_SESSION['currentSearch']['r'])-1) {
			$s['n'] = $s['r'][$idx+1];
		}
		unset($s['r']);
		$smarty->assign_by_ref('current_search',$s);
	}

	if (appearsToBePerson() && empty($_SESSION['responsive']) && empty($_GET['responsive'])) {
		if (empty($_SESSION['photos'][$image->gridimage_id])) {
			if (empty($db) || $db->readonly)
				$db = GeographDatabaseConnection(false);

			if (empty($db->readonly)) //not used yet, but ultimately we may get to stage that running on a readonly slave.
				$db->Query("INSERT LOW_PRIORITY INTO gridimage_log VALUES({$image->gridimage_id},1,0,0,now()) ON duplicate KEY UPDATE hits=hits+1");

			@$_SESSION['photos'][$image->gridimage_id]++;
		}
	} else {
		$smarty->assign('is_bot',true);
	}

	$ref = @parse_url($_SERVER['HTTP_REFERER']);
	$ref_query = array();
	if (!empty($ref['query'])) {
		parse_str($ref['query'], $ref_query);

		if (strpos($ref['host'],'images.google.') === 0 && !empty($ref_query['prev'])) {
			$ref = @parse_url('http://'.$ref['host'].urldecode($ref_query['prev']));
			parse_str($ref['query'], $ref_query);
		}
	} elseif (!empty($_GET['q'])) {
		$ref = @parse_url($_SERVER['SCRIPT_URI'].'?'.$_SERVER['QUERY_STRING']);
		parse_str($ref['query'], $ref_query);
	}

	if (!empty($CONF['sphinx_host'])
		&& count($ref_query) > 0
		&& ( $intersect = array_intersect(array('q','query','qry','search','su','searchfor','s','qs','p','key','buscar','w'),array_keys($ref_query)) )
		&& ( $key = @array_shift($intersect) )
		&& !is_numeric($ref_query[$key])
		&& ($q = trim(preg_replace('/\b(geograph|photo|photograph|image|picture|site:[\w\.-]+|inurl:[\w\.-]+)s?\b/','',$ref_query[$key] )) )
		&& strlen($q) > 3 ) {

		if (!empty($m[1]) && $m[1] == 'prev' && preg_match('/\b(q|query|qry)=([\w%\+\.\(\)\"\':]+)(\&|$)/',$q,$m)) {
			$q = trim(urldecode($m[2]));
		}

		$smarty->assign("search_keywords",$q);

		$mkey = $image->grid_reference.' '.$q;
		$info = $memcache->name_get('sn',$mkey);

		if (!empty($info)) {
			list($count,$when) = $info;

			$smarty->assign("search_count",$count);
		} else {
			$sphinx = new sphinxwrapper($mkey);

			$sphinx->processQuery();

			$count = $sphinx->countMatches('_images');
			$smarty->assign("search_count",$count);

			//fails quickly if not using memcached!
			$info = array($count,time());
			$memcache->name_set('sn',$mkey,$info,$memcache->compress,$memcache->period_med);
		}
	}

	if ($CONF['template']!='charcoal' && $CONF['template']!='archive') { //this is mainly to exclude schools!
		//temporally patch, so that we can put set the margin, just like related.js WILL!
		$smarty->assign('maincontentclass', 'content_photo'.$style.' photopage');
	} else {
		$smarty->assign('maincontentclass', 'content_photo'.$style);
	}
	$smarty->assign('tile_host', $CONF['TILE_HOST']);

	if (!$smarty->is_cached($template, $cacheid))
	{
		//if ($CONF['template']!='archive') {
			if (!empty($image->db) && !empty($image->db->readonly) && (empty($db) || empty($db->readonly))) {
				//if the image has a readonly connection, we can use, lets do it!
				$db = $image->db;
			} elseif (empty($db)) {
				$db = GeographDatabaseConnection(true);
			}

			$image->hits = $db->getOne("SELECT hits+hits_archive+hits_gallery FROM gridimage_log WHERE gridimage_id = {$image->gridimage_id}");
		//}

		$image->assignToSmarty($smarty);
		$smarty->assign('larger',true);

		$image->loadSnippets();
		$image->loadCollections();
		$image->loadTags(true); //request array format (same as loadSnippets used to do)

		//disable large image for [panorama: ] tagged images, needs to be done here, AFTER loadSnippets()!
		if (!empty($image->tag_prefix_stat['panorama']))
			$smarty->assign('larger',false);

		if ($CONF['template']!='archive' && empty($q) && !empty($db)) {

			if ($same = $db->getOne("SELECT images from gridimage_duplicate where grid_reference = '{$image->grid_reference}' and title = ".$db->Quote($image->title))) {
				//todo, should check duplication_stat as well, if part of that, then link directly there.
				// but ONLY if same_serial = gridimage_duplicate.images (because may be multiple serials, better to link to list.php, as list all contributors?)
				if ($serial = $db->getOne("SELECT serial FROM duplication_stat WHERE gridimage_id = {$image->gridimage_id} AND same_serial = $same")) {
					$url = "/photoset/{$image->grid_reference}/".urlencode($serial); //todo, use getDirectLink?
				} else {
					$url = "/stuff/list.php?title=".urlencode($image->title)."&amp;gridref={$image->grid_reference}";
				}
				$smarty->assign('prompt', "This is 1 of <a href=\"$url\">$same images, with title ".htmlentities($image->title)."</a> in this square");

				if (!empty($image->collections))
					$image->collections[] = array('url'=>$url,'title'=>$image->title." [$same]",'type'=>'Title Cluster');

/*
			} elseif (preg_match('/[^\w]+(\d{1,3})[^\w]$/', $image->title)) {
				$title = preg_replace('/[^\w]+(\d{1,3})[^\w]$/', ' #', $image->title);
				if ($same = $db->getOne("SELECT images from gridimage_duplicate where grid_reference = '{$image->grid_reference}' and title = ".$db->Quote($title))) {
	                                $url = "/stuff/list.php?title=".urlencode($title)."&amp;gridref={$image->grid_reference}";
                                	$smarty->assign('prompt', "This is 1 of <a href=\"$url\">$same images, with title ".htmlentities(preg_replace('/ #$/','',$title))."</a> in this square");
                        	}
*/

			} elseif (!empty($image->prompt)) {
				//the loadCollections sets this, when loading pre-computed title clusters
				$smarty->assign('prompt', $image->prompt);

			} elseif (false && substr_count($image->title,' ') > 1) {
				$words = explode(' ',trim($image->title));
				array_pop($words);
				$title = preg_replace('/[^\w]+$/','',implode(' ',$words))."%";  //the replace removes commas etc from end of words (so 'The Black Horse, Nuthurst', necomes 'The Black Horse')
				//if (($same = $db->getOne("SELECT COUNT(*) AS images FROM gridimage_search where grid_reference = '{$image->grid_reference}' and title LIKE ".$db->Quote($title))) && $same > 1) {

				//should be more effient to do this in manticore/sphinx
				$sph = GeographSphinxConnection('sphinxql',true);
				require_once ( "3rdparty/sphinxapi.php" );
				$query = "@grid_reference {$image->grid_reference} @title \"^".SphinxClient::EscapeString(trim($title,'%')).'"';
				//NOTE: the spaces before SELECT are delibeate, as adodb automatically adds LIMIT 1, which conflicts with sphinxQL OPTION
				if (($same = $sph->getOne("  SELECT COUNT(*) FROM gi_stemmed WHERE MATCH(".$sph->Quote($query).") OPTION ranker=none")) && $same > 1) {

					//the space on the end of the URL param is deliberate!
	                                $url = "/stuff/list.php?title=".urlencode(preg_replace('/%$/',' ',$title))."&amp;gridref={$image->grid_reference}";
					$smarty->assign('prompt', "This is 1 of <a href=\"$url\">$same images, with title starting with ".htmlentities(trim($title,'%'))."</a> in this square");

					if (!empty($image->collections))
						$image->collections[] = array('url'=>$url,'title'=>trim($title,'%')." ... [$same]",'type'=>'Title Cluster');

                        	}
			}
		}

	} else {
		$smarty->assign_by_ref("image",$image); //we dont need the full assignToSmarty
	}

} elseif (!empty($rejected)) {
	header("HTTP/1.0 410 Gone");
	header("Status: 410 Gone");
	$template = "static_404.tpl";
} else {
	header("HTTP/1.0 404 Not Found");
	header("Status: 404 Not Found");
	$template = "static_404.tpl";
}

if (!empty($mobile_browser))
         $smarty->assign("mobile_browser", 1);

$smarty->display($template, $cacheid);


//if (isset($_GET['php_profile']) && class_exists('Profiler',false)) {
//         Profiler::render();
//}


