<?php
/**
 * $Project: GeoGraph $
 * $Id: faq.php 15 2005-02-16 12:23:35Z lordelph $
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

$USER->mustHavePerm('basic');



$template = 'article_builder_place.tpl';
$cacheid = '';

	

if (isset($_POST) && isset($_POST['submit'])) {
	$errors = array();
	
	
	$_POST['title'] = preg_replace('/[^\w\-\.,:;\' ]+/','',trim($_POST['title']));
	
	$_POST['locality'] = preg_replace('/[^\w\-\.,:;\' ]+/','',trim($_POST['locality']));
	
	$_POST['extract'] = "Short article about {$_POST['title']} in {$_POST['locality']}";
	
	if (!empty($_POST['locality'])) {
		$_POST['title'] .= ", ".$_POST['locality'];
	}	
	
	if (empty($_POST['url']) && !empty($_POST['title'])) {
		$_POST['url'] = $_POST['title'];
	}
	$_POST['url'] = preg_replace('/ /','-',trim($_POST['url']));
	$_POST['url'] = preg_replace('/[^\w-]+/','',$_POST['url']);
	
	if ($_POST['title'] == "New Article")
		$errors['title'] = "Please give a meaningful title";
	
	$gs=new GridSquare();
	if (!empty($_POST['grid_reference'])) {
		if ($gs->setByFullGridRef($_POST['grid_reference'])) {
			$_POST['gridsquare_id'] = $gs->gridsquare_id;
		} else 
			$errors['grid_reference'] = $gs->errormsg;
	}
	
	//the most basic protection
	$_POST['content'] = strip_tags($_POST['content']);
	$c = preg_replace('/[“”]/','',$_POST['content']);

	################################
	# the 'magic'
	
	if ($_POST['title']) {
	
		if ($_POST['gridsquare_id']) {

			$c .= "\n\n[h2]Location Map[/h2]\n\n";

			$conv = new Conversions;
			list($gr,$len) = $conv->national_to_gridref(
				$gs->getNatEastings(),
				$gs->getNatNorthings(),
				max(4,$gs->natgrlen),
				$gs->reference_index,false);

			$c .= "[map {$gr}]\n";
			$c .= "[url=http://{$_SERVER['HTTP_HOST']}/gridref/$gr/links]More Links for {$gs->grid_reference}[/url]\n\n";
		}

		foreach (range(1,2) as $i) {
			if (!empty($_POST['title'.$i])) {
				$title = preg_replace('/[^\w\-\.,:;\' ]+/','',trim($_POST['title'.$i]));
				$c .= "\n\n[h2]{$title}[/h2]";
			}
			if (!empty($_POST['ids'.$i])) {
				$ids = trim(preg_replace('/[^\d]+/',' ',$_POST['ids'.$i]));
				if (!empty($ids)) {
					$c .= "\n\n";
					$ids = explode(' ',$ids);
					if ($_POST['thumbs'.$i] == 'large') {
						$c .= "[image id=".implode("]\n\n[image id=",$ids)."]";
					} else {
						$c .= "[[[".implode("]]] [[[",$ids)."]]]";
					}
				}
			}
		}
		$links = array();
		if (!empty($_POST['related'])) {
			$lines = preg_split("/\n+/",trim(str_replace("\r",'',$_POST['related'])));
			if (!empty($lines)) {
				foreach ($lines as $line) {
					list($link,$name) = explode(' ',$line,2);
					if (empty($link))
						continue;
					if (empty($name)) {
						$name = $link;
					}
					$links[] = "* [url=$link]{$name}[/url]";
				}
			}
		}
		if (!empty($_POST['locality'])) {
			$link = "http://www.google.co.uk/search?q=".urlencode($_POST['title']);
			$name = "Google Web search for ".$_POST['title'];
			$links[] = "* [url=$link]{$name}[/url]";
		}	
		if (!empty($_POST['q'])) {
			$gr2 = preg_replace('/([A-Z])/e', '"%".bin2hex(\'\\1\')', $gs->grid_reference);

			$link = "http://{$_SERVER['HTTP_HOST']}/search.php?grid_reference={$gr2}&searchtext=".urlencode($_POST['q'])."&distance=2&do=1";
			$name = "Auto-updating Search for related images";
			$links[] = "* [url=$link]{$name}[/url]";
		}
		if (!empty($links)) {
			$c .= "\n\n[h2]Further Reading[/h2]\n";
			$c .= implode("\n",$links);
		}	

		$_POST['content'] = strip_tags($c);
	}

	################################
	
	$db = GeographDatabaseConnection(false);
	
	$updates = array();
	
	$keys = array('url','title','content','extract','gridsquare_id');
	
	
	foreach ($keys as $key) {
		if ($page[$key] != $_POST[$key]) {
			$updates[] = "`$key` = ".$db->Quote($_POST[$key]); 
			$smarty->assign($key, $_POST[$key]);
			if ($key == 'url' || $key = 'title') {
				$sql = "select count(*) from article where `$key` = ".$db->Quote($_POST[$key]);

				if ($db->getOne($sql)) 
					$errors[$key] = "(".$db->Quote($_POST[$key]).') is already in use';				
			}
		} elseif (empty($_POST[$key]) && $key == 'title') 
			$errors[$key] = "missing required info";		
	}
	if (isset($_POST['edit_prompt'])) {
		$key = 'edit_prompt';
		$updates[] = "`$key` = ".$db->Quote($_POST[$key]); 
		$smarty->assign($key, $_POST[$key]);
	}
	
	if (!count($updates)) {
		$smarty->assign('error', "No Changes to Save");
		$errors[1] =1;
	}
	
		$updates[] = "`article_cat_id` = 2";
		$updates[] = "publish_date = NOW()";

		$updates[] = "`user_id` = {$USER->user_id}";
		$updates[] = "`create_time` = NOW()";
		$sql = "INSERT INTO article SET ".implode(',',$updates);
	
	
	if (!count($errors) && count($updates)) {
		
		$db->Execute($sql);
		
		$_REQUEST['article_id'] = $db->Insert_ID();
				
		//require_once('geograph/event.class.php');
		//new Event("article_updated", $_REQUEST['article_id']);

		//and back it up
		$sql = "INSERT INTO article_revisions SELECT *,NULL,{$USER->user_id} FROM article WHERE article_id = ".$db->Quote($_REQUEST['article_id']);
		$db->Execute($sql);

		print "<h3>Your article has been created</h3>";
		
		print "<p>Title: ".$_POST['title']."</p>";
		
		$url = "http://{$_SERVER['HTTP_HOST']}/article/{$_POST['url']}";
		
		print "<p>Link: <a href=\"$url\">$url</a></p>";
		
		print "<p>Note: <b>The article is not yet published.</b> To publish the article you need to edit it and assign a licence to the work - you should at the same time check the article is to your satifaction.</p>";
		print "<p>Once a licence has set, the article will be reviewed by site moderators and then made public</p>";
		
		print "<p><a href=\"edit.php?page={$_POST['url']}\">Edit the Article now</a></p>";
		
		print "<p><a href=\"./\">Return to Homepage</a></p>";
		
		
		flush();

		exit;
	} else {
		if ($errors[1] != 1)
			$smarty->assign('error', "Please see messages below...");
		$smarty->assign_by_ref('errors',$errors);
	}
} 



$smarty->display($template, $cacheid);


