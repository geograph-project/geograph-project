<?php
/**
 * $Project: GeoGraph $
 * $Id$
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

require_once('geograph/global.inc.php');

############################################
# few methods to quickly execute and die

if (isset($_GET['id']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'http://geourl.org/bot')!==FALSE) ) {
	//die as quickly as possible with the minimum html (with the approval of geourl owner)
	$db = NewADOConnection($GLOBALS['DSN']);

	$row =& $db->getRow("select gridimage_id,wgs84_lat,wgs84_long,title,grid_reference from gridimage_search where gridimage_id=".intval($_GET['id']) );

	if ($row['wgs84_lat']) {
		$title = htmlentities($row['title']."::".$row['grid_reference']);

		print "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\"/>\n";
		print "<title>$title</title>\n";
		print "<meta name=\"ICBM\" content=\"{$row['wgs84_lat']}, {$row['wgs84_long']}\"/>\n";
		print "<meta name=\"DC.title\" content=\"Geograph::$title\"/>\n";
		print "<a href=\"http://{$_SERVER['HTTP_HOST']}/photo/{$row['gridimage_id']}\">View image page</a>";
	} elseif ($row['gridimage_id']) {
		header("HTTP/1.0 500 Server Error");
		header("Status: 500 Server Error");
		print "<title>Lat/Long not available, try again later</title>";
		print "<a href=\"http://{$_SERVER['HTTP_HOST']}/photo/{$row['gridimage_id']}\">View image page</a>";
	} else {
		header("HTTP/1.0 404 Not Found");
		header("Status: 404 Not Found");
		print "<title>Image no longer available</title>";
	}
	exit;
} elseif ((strpos($_SERVER["REQUEST_URI"],'/photo/') === FALSE && isset($_GET['id'])) || strlen($_GET['id']) !== strlen(intval($_GET['id']))) {
	//keep urls nice and clean - esp. for search engines!
	header("HTTP/1.0 301 Moved Permanently");
	header("Status: 301 Moved Permanently");
	header("Location: /photo/".intval($_GET['id']));
	print "<a href=\"http://{$_SERVER['HTTP_HOST']}/photo/".intval($_GET['id'])."\">View image page</a>";
	exit;
}

############################################
# normal setup

require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/mapmosaic.class.php');
require_once('geograph/rastermap.class.php');

init_session();

if (isset($_GET['style'])) {
	$USER->getStyle();
	if (isset($_GET['id'])) {
		header("HTTP/1.0 301 Moved Permanently");
		header("Status: 301 Moved Permanently");
		header("Location: /photo/".intval($_GET['id']));
		exit;
	}
	header("Location: /");
	exit;
}

customGZipHandlerStart();

$smarty = new GeographPage;

$template='view.tpl';

$cacheid=0;

if ($smarty->caching) {
	$smarty->caching = 2; // lifetime is per cache
	$smarty->cache_lifetime = 3600*3; //3hour cache
}

############################################
# load the image

$image=new GridImage;

if (isset($_GET['id']))
{
	$image->loadFromId(intval($_GET['id']));
	$isowner=($image->user_id==$USER->user_id)?1:0;
	$ismoderator=$USER->hasPerm('moderator')?1:0;

	$ab=floor($_GET['id']/10000);

	$cacheid="img$ab|{$_GET['id']}|{$isowner}_{$ismoderator}";

	//is the image rejected? - only the owner and administrator should see it
	if ($image->moderation_status=='rejected')
	{
		if ($isowner||$ismoderator)
		{
			//ok, we'll let it lie...
		}
		else
		{
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
	############################################
	# setup caching headers
	
	//what style should we use?
	$style = $USER->getStyle();
	$cacheid.=$style;


	//when this image was modified
	$mtime = strtotime($image->upd_timestamp);

	//page is unqiue per user (the profile and links)
	$hash = $cacheid.'.'.$USER->user_id;

	//can't use IF_MODIFIED_SINCE for logged in users as has no concept as uniqueness
	customCacheControl($mtime,$hash,($USER->user_id == 0));

	############################################
	# run the query from referrer locally and prompt the user...

	if ( (stripos($_SERVER['HTTP_USER_AGENT'], 'http')===FALSE) &&
	    (stripos($_SERVER['HTTP_USER_AGENT'], 'PHP')===FALSE) &&
	    (stripos($_SERVER['HTTP_USER_AGENT'], 'bot')===FALSE) &&
	    empty($_SESSION['photos'][$image->gridimage_id]) )
	{
		$db=NewADOConnection($GLOBALS['DSN']);
		
		$db->Query("INSERT LOW_PRIORITY INTO gridimage_log VALUES({$image->gridimage_id},1,0,now()) ON duplicate KEY UPDATE hits=hits+1");
		
	}
	@$_SESSION['photos'][$image->gridimage_id]++;

	$ref = @parse_url($_SERVER['HTTP_REFERER']);
	if (!empty($ref['query'])) {
		$ref_query = array();
		parse_str($ref['query'], $ref_query);
		
		if (strpos($ref['host'],'images.google.') === 0 && !empty($ref_query['prev'])) {
			$ref = @parse_url('http://'.$ref['host'].urldecode($ref_query['prev']));
			parse_str($ref['query'], $ref_query);
		}
	}

	if (!empty($CONF['sphinx_host']) 
		&& count($ref_query) > 0
		&& ( $intersect = array_intersect(array('q','query','qry','search','su','searchfor','s','qs','p','key','buscar','w'),array_keys($ref_query)) )
		&& ( $key = @array_shift($intersect) )
		&& !is_numeric($ref_query[$key])
		&& ($q = trim(preg_replace('/\b(geograph|photo|image|picture|site:[\w\.-]+|inurl:[\w\.-]+)s?\b/','',$ref_query[$key] )) )
		&& strlen($q) > 3 ) {

		$smarty->assign("search_keywords",$q);
		
		$mkey = $image->grid_reference.' '.$q;
		$info =& $memcache->name_get('sn',$mkey);
		
		if (!empty($info)) {
			list($count,$when) = $info;
			
			$smarty->assign("search_count",$count);
			
			$smarty->assign_by_ref("image",$image); //we dont need the full assignToSmarty
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

	############################################
	# render the page...

	if (!$smarty->is_cached($template, $cacheid))
	{
		$smarty->assign('maincontentclass', 'content_photo'.$style);

		if (empty($db)) {
			$db=NewADOConnection($GLOBALS['DSN']);
		}
		
		$image->hits = $db->getOne("SELECT hits+hits_archive FROM gridimage_log WHERE gridimage_id = {$image->gridimage_id}");

		$image->assignToSmarty($smarty);
		
		############################################
		# lookup some related images.... 
		//todo : lookup images from the image description, and maybe related images from articles/galleries and themed topics?

		if (!empty($CONF['sphinx_host']) && $CONF['template']=='basic') {
			$words = $db->getCol("SELECT result FROM at_home_result WHERE gridimage_id = {$image->gridimage_id}");
			if (empty($col)) {
				$words = array();
			}
			
			if (preg_match('/\s*\(?\s*\d+\s*\)?\s*$/',$image->title)) {
				$words+=explode(" ",preg_replace('/\s*\(?\s*\d+\s*\)?\s*$/','',$image->title));
			}
			
			if (empty($col) && strlen($image->comment) > 50) { //should have plenty of words...
				//strip any links. 
				$q = preg_replace('/(?<!["\'>F=])(https?:\/\/[\w\.-]+\.\w{2,}\/?[\w\~\-\.\?\,=\'\/\\\+&%\$#\(\)\;\:]*)(?<!\.)(?!["\'])/'," ",$image->comment);
				$q = preg_replace('/(?<![\/F\.])(www\.[\w\.-]+\.\w{2,}\/?[\w\~\-\.\?\,=\'\/\\\+&%\$#\(\)\;\:]*)(?<!\.)(?!["\'])/'," ",$q);
				
				$q = preg_replace('/\b\w{1,3}\b/',' ',$q);
				$q = preg_replace('/ [^\w]+ /',' ',$q);
				
				$lines = explode("\n",wordwrap($q,200,"\n",0));
				$words+=explode(" ",$lines[0]);
			}
			
			if (count($words) < 3 &&  strpos($image->imagetaken,'-00') === FALSE) {
				$words[] = str_replace('-','',$image->imagetaken);
			}
			
			if (count($words) < 3) {
				$words+=explode(" ",$image->realname." ".$image->imageclass." ".$image->title);
			}
			if (count($words) < 3) {
				$words+=explode(" ",$image->title);
			}
			$q = str_replace(array('(',')','|','&'),' ',implode(' ',$words));
			$q = "~".trim(preg_replace('/\s+/',' ',$q));

			print_r($q);
			$mkey = $image->grid_reference.' '.md5($q);
			$ids =& $memcache->name_get('sn',$mkey);
			if (empty($ids)) {
				$pg = 1; //todo - maybe allow pagin?
				$sphinx = new sphinxwrapper($image->grid_reference.' '.$q);

				$sphinx->processQuery();

				$ids = $sphinx->returnIds($pg,'_images');
				print "<hr>";
			print_r($ids);	
				if (empty($ids)) {
					//remove the terms and just show any nearby images. 
					$sphinx = new sphinxwrapper($image->grid_reference.' ');
					$sphinx->processQuery();
					$ids = $sphinx->returnIds($pg,'_images');
				}
				
				$memcache->name_set('sn',$mkey,$ids,$memcache->compress,$memcache->period_long);
			}
			print "<br>";
			print_r($sphinx->qclean);
			if (!empty($ids) && ($id = array_search($image->gridimage_id,$ids)) !== FALSE) {
				unset($ids[$id]);//remove self
			}
	
			if (!empty($ids) && count($ids)) {
				$smarty->assign('maincontentclass', 'content_photo'.$style.'" style="margin:4px 210px 0px 170px;');
				$smarty->assign("right_block","_block_related.tpl");
				require_once('geograph/imagelist.class.php');
				
				$images=new ImageList();
				
				if (0 && count($ids) > 10) {
					//try a crossection
					$table = "tmp_".md5(uniqid());
					$id_list = join(",",$ids);
					
					$db->Execute("CREATE TEMPORARY TABLE $table ENGINE HEAP SELECT gridimage_id,title,grid_reference,user_id,realname,imageclass FROM gridimage_search WHERE gridimage_id IN($id_list) ORDER BY FIELD(gridimage_id,$id_list)");
					$db->Execute("ALTER IGNORE TABLE $table ADD UNIQUE (user_id),ADD UNIQUE (imageclass)");
					$sql = "SELECT * FROM $table LIMIT 20";
					$images->_getImagesBySql($sql);
				} else {
					$images->getImagesByIdList($ids);
				}
				$images->assignSmarty($smarty, 'related');
				print count($images->images);
				if (!empty($sphinx->qclean)) {
					$smarty->assign("related_keywords",$sphinx->qclean);
				}
			}
		}
	}
} elseif (!empty($rejected)) {
	header("HTTP/1.0 410 Gone");
	header("Status: 410 Gone");
} else {
	header("HTTP/1.0 404 Not Found");
	header("Status: 404 Not Found");
}

function smarty_function_hidekeywords($input) {
	return preg_replace('/(^|[\n\r\s]+)(Keywords?[\s:][^\n\r>]+)$/','<span class="keywords">$2</span>',$input);
}
$smarty->register_modifier("hidekeywords", "smarty_function_hidekeywords");

$smarty->display($template, $cacheid);


?>