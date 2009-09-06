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
			$ok = $image->loadFromId($id);
			
	if (!$ok || $image->moderation_status=='rejected') {
		//clear the image
		$image=new GridImage;
		header("HTTP/1.0 410 Gone");
		header("Status: 410 Gone");
		$template = "static_404.tpl";
		exit;
	}

			if ($token->hasValue("small")) {
				$details = $image->getThumbnail(120,120,2);
			} else {
				$details = $image->getThumbnail(213,160,2);
			}
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

#	print "TOKEN: <TT>".$token->getToken()."</TT>";

	print "<p>Copy/Paste all this into a forum thread..</p>";
	print "<p><input size=110 value=\"[img]http://www.geograph.org.uk/stuff/captcha.php?token=".$token->getToken()."&amp;/med.jpg[/img]\"></p>";

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

if (isset($_GET['report'])) {
	$o = fopen('/tmp/capfailed','a');
	fwrite($o,"-----------------------------\n");
	fwrite($o,date('r')."\n");
	fwrite($o,"REPORT:".getRemoteIP()."\n");
	fclose($o);
} 

if ($_POST['choice']) {
	if (!$_SESSION['ids']) {
		print "<h2 style=\"background-color:#ff9999\">Nope!</h2>";
	} else {
		foreach ($_POST['choice'] as $t) {
			$token=new Token;
			if ($token->parse($t)) {
				$id = $token->hasValue("id")?$token->getValue("id"):0;
				$sent[$id] = 1;
			}
		}
		
		$found = 0;
		foreach ($_SESSION['ids'] as $id) {
			if (isset($sent[$id])) {
				$found++;
			}
		}
		
		if ($found == 5) {
			print "<h2 style=\"background-color:lightgreen\">Hello Human! Come on inside...</h2>";
		} else {
			print "<h2 style=\"background-color:#ff9999\">Failed...</h2>";
			
			$o = fopen('/tmp/capfailed','a');
			fwrite($o,"-----------------------------\n");
			fwrite($o,date('r')."\n");
			fwrite($o,"IP:".getRemoteIP()."\n");
			fwrite($o,print_r($_SESSION['ids'],1)."\n");
			fwrite($o,print_r($sent,1)."\n");
			fwrite($o,"FOUND=$found\n");
			fwrite($o,print_r($_SESSION['all_ids'],1)."\n");
			fwrite($o,print_r($_POST['choice'],1)."\n\n");
			fclose($o);
			
			print "<p><a href=\"?report=1\">Click here if you think you did fill it out correctly</a> (otherwise we will assume you just playing)</p>";
			
		}
	}
	print "<a href=\"captcha.php?\">Try again</a>";
	
	$_SESSION['ids'] = ''; //prevent retry
	
} else {

	$category = 'Butterfly';
	
	#if (false) {
		$offset = rand(0,500);//todo hardcoded...

		$answers = $db->getCol("SELECT gridimage_id FROM gridimage_search s WHERE imageclass = '$category' AND moderation_status = 'accepted' LIMIT $offset,20");
		shuffle($answers);
		$answers = array_slice($answers,0,5);

		$_SESSION['ids'] = $answers;

		$offset = rand(0,1000000);//todo hardcoded...
		$offset_end = $offset+150;
		$ids = $db->getCol("SELECT gridimage_id FROM gridimage_search s WHERE imageclass != '$category' AND moderation_status = 'geograph' AND gridimage_id BETWEEN $offset AND $offset_end GROUP BY imageclass LIMIT 30");

		shuffle($ids);
		$ids = array_slice($ids,0,20);


		$ids = array_merge($ids,$answers);
		shuffle($ids);
		
		$_SESSION['all_ids'] = $ids;
		
	#	print_r(join(',',$ids));
	#} else {
	#	$ids = array(294197,294183,294156,294150,294195,294193,294152,294158,294141,294182,148018,294213,294216,208125,294173,294211,294165,294189,294246,207561,294204,294168,207539,217819,294202);
	#}
	
	print "<title>Geograph Captcha Test - Proof of concept</title>";
	print "<h2>Geograph Captcha Test - Proof of concept</h2>";
	
	print $html;
	
	print "<p>To continue, please select the <b>5</b> image(s) of '<b>".htmlentities($category)."</b>'... (click an image to select)</p>";
	
	
	print "<form method=\"post\"><table><tr>";
	$i = 0;
	foreach ($ids as $id) {
		$token=new Token;
		$token->setValue("id", intval($id));
		$token->setValue("small", 1);
		$t = $token->getToken();
	
		print "<td id='td$t' align=center><label for=\"$t\"><img src=\"captcha.php?token=$t&\"></label><br/>";
		
		print "<input type=checkbox name=choice[] value=\"$t\" id=\"$t\" onchange='changeBackground(this)'/>";
		
		print "</td>";
		
		$i++;
		
		if ($i%5==0) {
			print "</tr><tr>";
		}
		
	}
	print "</tr></table><p><input type=\"submit\"/></p>";
	print "</form>";
	
	?>
	<script>
		function changeBackground(that) {
			
			document.getElementById('td'+that.id).style.backgroundColor=(that.checked)?'red':'white';
			
		}
	</script>
	
	<?
}

#$smarty->display('_std_end.tpl');
exit;

?>
