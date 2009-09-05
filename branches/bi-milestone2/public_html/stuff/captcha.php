<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
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
require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');




$db=NewADOConnection($GLOBALS['DSN']);



if (!empty($_GET['token'])) {
	$ok=false;
	$token=new Token;

	if ($token->parse($_GET['token']))
	{
		if ($token->hasValue("id")) {
			$id = $token->getValue("id");
	
			//iamges should be immutable! So we have permentent urls
			customCacheControl(getlastmod(),$id);	
			customExpiresHeader(3600*24*48,true);
			
			$image = new GridImage;
			$image->loadFromId($id);
			
			if (!empty($CONF['fetch_on_demand'])) {
				//we do this becase gridimage class cant produce a url without creating thumbnail
				function getGeographUrl($gridimage_id,$hash,$size ='small') { 
				       $yz=sprintf("%02d", floor($gridimage_id/1000000)); 
				       $ab=sprintf("%02d", floor(($gridimage_id%1000000)/10000)); 
				       $cd=sprintf("%02d", floor(($gridimage_id%10000)/100)); 
				       $abcdef=sprintf("%06d", $gridimage_id); 
				       $fullpath="/geophotos/$yz/$ab/$cd/{$abcdef}_{$hash}"; 
				       $server =  "http://s".($gridimage_id%4).".geograph.org.uk"; 
				       switch($size) { 
					       case 'full': return "http://{$CONF['fetch_on_demand']}$fullpath.jpg"; break; 
					       case 'med': return "$server{$fullpath}_213x160.jpg"; break; 
					       case 'small': 
					       default: return "$server{$fullpath}_120x120.jpg"; 
				       } 
				} 

				header("Location: ".getGeographUrl($id,$image->_getAntiLeechHash(),'small'));

				exit;
			}
			
			$details = $image->getThumbnail(213,160,2);
			$url = $details['url'];
			header("Content-Type: image/jpeg");
			@readfile('..'.$url);
			exit;
		}
	}
	exit;
} elseif (!empty($_GET['id'])) {
	$token=new Token;
	$token->setValue("id", intval($_GET['id']));

	print "TOKEN: <TT>".$token->getToken()."</TT>";
	exit;
}

init_session();

if (!empty($_GET['image'])) {
	$image = new GridImage;
	$image->loadFromId($_SESSION['id']);
	$details = $image->getThumbnail(213,160,2);
	$url = $details['url'];
	header("Content-Type: image/jpeg");
	@readfile('..'.$url);
	exit;
} 

#$smarty = new GeographPage;


#$smarty->display('_std_begin.tpl');
	

if ($_POST['choice']) {
	if (!$_SESSION['pos']) {
		print "<h2 style=\"background-color:#ff9999\">Nope!</h2>";
	} elseif ($_POST['choice'] == $_SESSION['pos']) {

		print "<h2 style=\"background-color:lightgreen\">Hello Human! Come on inside...</h2>";
	} else {
		print "<h2 style=\"background-color:#ff9999\">Failed...</h2>";
	}
	print "<a href=\"captcha.php?\">Try again</a>";
	
	$_SESSION['pos'] = ''; //prevent retry
	$_SESSION['id'] = ''; 
	
} else {

	$initalrows = $db->getAll("SELECT * FROM category_pair INNER JOIN category_stat ON (cat1 = imageclass) WHERE c > 100 ORDER BY RAND() LIMIT 10");
	$found = 0;
	
	#$initalrows = array(array('cat1' => 'Marker'));
	
	while (!$found && count($initalrows)) {
		$row1 = array_pop($initalrows);
	
		$cat = "'".mysql_real_escape_string($row1['cat1'])."'";
		
		$pairrows = $db->getAll("SELECT * FROM category_pair WHERE (cat1 = $cat OR cat2 = $cat) AND num = 7 LIMIT 30");
		
		if (count($pairrows)) {
			$list = array();
			foreach ($pairrows as $row2) {
				if ($row2['cat1'] == $row1['cat1']) {
					$list[] = mysql_real_escape_string($row2['cat2']);
				} else {
					$list[] = mysql_real_escape_string($row2['cat1']);
				}
			}
			print_r($list);
			$images = $db->getCol("SELECT gridimage_id FROM gridimage_search s WHERE s.imageclass IN ('".implode("','",$list)."') ORDER BY imageclass LIMIT 15");
			
			if (count($images) >= 15) {
				$found = 1;
			}
		}
	}
	
	if (!$found) {
		die("error, please try again later...");
	}
	
	$answers = $db->getCol("SELECT gridimage_id FROM gridimage_search s WHERE imageclass = $cat LIMIT 10");
	$category = $row1['cat1'];
	
	$_SESSION['ids'] = $answers;
	
	
	$ids = array_merge($images,$answers);
	shuffle($ids);
	
	print "<title>Geograph Captcha Test - Proof of concept</title>";
	print "<h2>Geograph Captcha Test - Proof of concept</h2>";
	
	print $html;
	
	print "<p>To continue please select the <b>5</b> images of '<b>".htmlentities($category)."</b>'...</p>";
	
	
	print "<form method=\"post\"><table><tr>";
	$i = 0;
	foreach ($ids as $id) {
		$token=new Token;
		$token->setValue("id", intval($id));
		$t = $token->getToken();
	
		print "<td><img src=\"captcha.php?token=$t&\"><br/>";
		
		print "<input type=checkbox name=choice[] value=\"$t\"/>";
		
		print "</td>";
		
		$i++;
		
		if ($i%5==0) {
			print "</tr><tr>";
		}
		
	}
	print "</tr></table><p><input type=\"submit\"/></p>";
	print "</form>";
}

#$smarty->display('_std_end.tpl');
exit;

?>
