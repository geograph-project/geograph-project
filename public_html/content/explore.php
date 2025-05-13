<?php
/**
 * $Project: GeoGraph $
 * $Id: conversion.php 5502 2009-05-13 14:18:23Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 BArry Hunter (geo@barryhunter.co.uk)
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

//customExpiresHeader(3600,false,true);

	$smarty->assign('responsive',true);
	$smarty->display('_std_begin.tpl');

	$db = GeographDatabaseConnection(true);
	//$sph = GeographSphinxConnection('sphinxql',true);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$links = array('/explore/quick.php'=>'Topics','/finder/recent.php'=>'Recent',
	'/stuff/viewgaz4.php' => 'Great Britain','/stuff/viewgaz3.php' => 'Ireland', '/stuff/viewgaz5.php' => 'Isle of Man', '/search.php'=>'Search', '/mapper/combined.php'=>'Map',
	'/browser/' => 'Advanced Browser','/content/explore.php'=>'Collections');

print '<div class="tabHolder" style="max-width:940px">';
foreach ($links as $link => $name) {
	if (basename($link) == 'viewgaz4.php')
		print " Places in: ";
        if (basename($link) == basename($_SERVER['PHP_SELF'])) {
                if (!empty($_GET)) { //having the link is useful to return to "homepage"
                        print "<a class=tabSelected href=$link>$name</a> ";
                } else {
                        print "<a class=tabSelected>$name</a> ";
                }
        } else {
                print "<a class=tab href=$link>$name</a> ";
        }
}
print '</div>';

	print '<div class="interestBox">';
        print "<h2>Explore Collections</h2>";
	print "</div>";

	print "<p>These list focuses on collections with images in Ireland, they may not be specifically about Ireland locations</p>";

#######################################

	$where = array();
	$where[] = "ri2 > 0";
	$where[] = "s.source != 'themed'";

	$order = "source,category_name,title";
	if (!empty($_GET['alpha']))
		$order = "title,images desc";

	$where = implode(' and ',$where);
	$list = $db->getAll("select s.*,c.images as cimags,c.title,c.url,if(c.title like '%tweet%','Tweeted Selection',category_name) as category_name,realname
	 from content_stat_by_grid s
	 inner join content c using (content_id)
	 left join article on(article_id = s.foreign_id) left join article_cat using (article_cat_id)
	 left join user on (user.user_id = c.user_id)
	 where $where order by $order");

#######################################

	if (!empty($list)) {
		print "<br>";
		print '<div class="tabHolder" style="max-width:940px">';
		print " Sort by: ";
		$links = array(
			smarty_function_linktoself(array('name'=>'alpha','value'=>0)) =>'Category',
			smarty_function_linktoself(array('name'=>'alpha','value'=>1)) =>'Alpha',
		);
		foreach ($links as $link => $name) {
			list(,$query) = explode('?',$link);
		        if ($query == htmlentities($_SERVER['QUERY_STRING'])) {
	                        print "<a class=tabSelected>$name</a> ";
		        } else {
		                print "<a class=tab href=$link>$name</a> ";
		        }
		}
		print '</div>';

		print "<div class=interestBox>Collections</div>";

#######################################

		print "<div style=\"columns: auto 22em\">";
		if (!empty($_GET['alpha'])) {
			$alpha = '';
			foreach ($list as $row) {
				$letter = strtolower( substr($row['title'],0,1));
if (empty($letter))
	continue;
				if ($alpha != $letter) {
	                                if ($alpha) print "</ol></div>";

        	                        $alpha = $letter;
                	                print "<div style=\"break-inside: avoid;\">"; //to keep the name with the list!
  //                      	      print "<h4>$alpha</h4>";
                                	print "<ol style=\"padding-left:6em\">";
	                        }

				$count = ($row['cimags'])?$row['cimags']:$row['images'];
				print "<li value=\"$count\">";
				$link = $row['url'];
				$value = htmlentities(html_entity_decode($row['title'])); //the title may be already encoded, but seems saver to decode
				print "<a href=\"$link\">$value</a>";
				print "</li>";
			}
                        if ($alpha) print "</ol></div>";

#######################################

		} else {
			$last = null;
			foreach ($list as $row) {
				$cat = $row['category_name'] ?? $row['source'];

				if ($last != $cat) {
	                                if ($last) print "</ol></div>";

        	                        $last = $cat;
					if (strpos($cat,'None of ') ===0)
						$cat = 'Misc';
                	                print "<div style=\"break-inside: avoid;\">"; //to keep the name with the list!
	  				print "<h4>$cat</h4>";
                                	print "<ol style=\"padding-left:6em\">";
	                        }

				$count = ($row['cimags'])?$row['cimags']:$row['images'];
				print "<li value=\"$count\">";
				$link = $row['url'];
				$value = htmlentities(html_entity_decode($row['title'])); //the title may be already encoded, but seems saver to decode
				print "<a href=\"$link\">$value</a>";
				print "</li>";
			}
                        if ($last) print "</ol></div>";
		}
		print "</div>";
	}

#######################################

$smarty->display('_std_end.tpl');

