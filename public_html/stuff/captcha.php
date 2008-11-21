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
	$idarray = $db->getAssoc("select gridimage_id,imageclass from category_stat order by rand() limit 5");
	
	$ids = array_keys($idarray);

	$id = $ids[2];
	
	$image = new GridImage;

	$image->loadFromId($id);
	$html = $image->getThumbnail(213,160);

	$html = preg_replace("/alt=\".*?\"/",'align="right"',$html);

	$cache = md5(time()); //cache defeat & red hearing ;-)
	$html = preg_replace("/src=\".*?\"/","src=\"?image=$cache\"",$html);
	#print $cache.' '.$id;
	
	//can never be too careful!
	srand(time());
	shuffle($ids);
	
	$position = array_search($id,$ids);
	
	$_SESSION['pos'] = $position;
	$_SESSION['id'] = $id;
	
	print "<title>Geograph Captcha Test - Proof of concept</title>";
	print "<h2>Geograph Captcha Test - Proof of concept</h2>";
	
	print $html;
	
	print "<p><b>To continue please identify the subject of the photo on the right...</b></p>";
	
	
	print "<form method=\"post\"><p>";
	$i = 0;
	foreach ($ids as $id) {
		print "<input type=\"radio\" name=\"choice\" value=\"$i\"/> {$idarray[$id]}<br/>";
		$i++;
	}
	print "<input type=\"submit\"/></p>";
	print "</form>";
}

#$smarty->display('_std_end.tpl');
exit;

?>
