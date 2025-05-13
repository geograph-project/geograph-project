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

	//$db = GeographDatabaseConnection(true);
	$sph = GeographSphinxConnection('sphinxql',true);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$links = array('/explore/quick.php'=>'Topics','/finder/recent.php'=>'Recent',
	'/stuff/viewgaz4.php' => 'Great Britain','/stuff/viewgaz3.php' => 'Ireland', '/stuff/viewgaz5.php' => 'Isle of Man', '/search.php'=>'Search', '/mapper/combined.php'=>'Map',
	'/browser/' => 'Advanced Browser');

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
        print "<h2>Explore Images</h2>";
	print "</div>";

$lists = array(
	'subjects' => array('title'=>'Subjects',            'column'=>'subjects','group'=>'subject_ids'),
	'contexts' => array('title'=>'Geographical Context','column'=>'contexts','group'=>'context_ids'),
	'tags' =>     array('title'=>'Tags',                'column'=>'tags','group'=>'tag_ids'),
	'types' =>     array('title'=>'Types',              'column'=>'types','group'=>'type_ids'),
	'countries' => array('title'=>'Countries',          'column'=>'country','group'=>'country'),
	'user' =>      array('title'=>'Photographer',       'column'=>'realname','group'=>'user_id'),
	'landcover' => array('title'=>'Landcover',          'column'=>'landcover','group'=>'landcover'),
);

$where = $match = $browser = array();

#######################################

	print "<form style=\"background-color:#eee;padding:10px\">\n\n";

#######################################

	print "List: <select name=\"list\" onchange=\"this.form.submit()\">";
	print "<option></option>";
	foreach ($lists as $name => $data) {
		printf('<option value="%s"%s>%s</option>', $name, ($name == $_GET['list']??'')?' selected':'', $data['title']);
	}
	print "</select>\n\n";

#######################################

	if (!empty($lists[$_GET['list']])) {
		$name = $_GET['list'];
		$s = $lists[$_GET['list']];

		$order = 'count DESC';

		$list = get_list_mva($where,$match,$s['group'],$s['column'],$order);
		if (!empty($_GET['alpha'])) {
			//alas can't sort in the quyery, as wont work with MVA, plus we want the top 1000 sorted
			function cmp($a, $b) {
				return strcasecmp($a['groupby'],$b['groupby']);
			}

                        uasort($list, 'cmp');
		}

		print "{$s['title']}: <select name=\"$name\" onchange=\"this.form.submit()\">";
		print "<option></option>";
		foreach ($list as $row) {
			$value = htmlentities($row[$s['column']]);
			if (empty($value))
				continue;
			printf('<option value="%s"%s>%s [%d images]</option>'."\n", $value, ($row[$s['column']] == $_GET[$name]??'')?' selected':'', $value, $row['count']);
			if ($row[$s['column']] == $_GET[$name]??'') {
				$match[] = "@$name ".$row[$s['column']];

				//https://www.geograph.org.uk/browser/#!/contexts+%22Housing%2C+Dwellings%22
				$browser[] = urlencode2("$name \"".$row[$s['column']]."\"");
			}
		}
		print "</select>\n\n";
	}

#######################################

	if (true) {
		$countries = get_list($where,$match,'country','country ASC');

		print "Country: <select name=\"country\" onchange=\"this.form.submit()\">";
		print "<option></option>";
		foreach ($countries as $row) {
			$value = htmlentities($row['country']);
			printf('<option value="%s"%s>%s [%d images]</option>'."\n", $value, ($row['country'] == $_GET['country']??'')?' selected':'', $value, $row['count']);
			if ($row['country'] == $_GET['country']??'') {
				$match[] = "@country ".$row['country'];
				$browser[] = urlencode2("country \"".$row['country']."\"");
			}
		}
		print "</select>\n\n";
	}

	if (!empty($_GET['country'])) {
		$counties = get_list($where,$match,'county','county ASC');

		print "County: <select name=\"county\" onchange=\"this.form.submit()\">";
		print "<option></option>";
		foreach ($counties as $row) {
			$value = htmlentities($row['county']);
			printf('<option value="%s"%s>%s [%d images]</option>'."\n", $value, ($row['county'] == $_GET['county']??'')?' selected':'', $value, $row['count']);
			if ($row['county'] == $_GET['county']??'') {
				$match[] = "@county ".$row['county'];
				$browser[] = urlencode2("county \"".$row['county']."\"");
			}
		}
		print "</select>\n\n";
	}

	print "</form>";

#######################################

	if (!empty($where) || !empty($match)) {

		if (!empty($match))
			$where[] = "MATCH(".$sph->Quote(implode(' ',$match)).")";

//		print_r($where);

		$thumbw = 213;
                $thumbh = 160;
		$pgsize = 30;
		$pg = 1;

			$sql = "SELECT id,title,realname,user_id,takendays,tags,grid_reference,hash FROM sample8 WHERE ".implode(' and ',$where);
			if ($_GET['sort']??'' == 'recent')
				$sql .= " ORDER BY id DESC";
                        if ($pg > 1)
                                $sql .= sprintf(" LIMIT %d,%d", ($pg -1)*$pgsize, $pgsize);
                        else
                                $sql .= " LIMIT $pgsize";

		$imagelist = new ImageList();
		$imagelist->_setSph($sph);

		$count = $imagelist->getImagesBySphinxQL($sql, true);

		print "<br><br>";

		$links = array('/explore/quick.php'=>'Preview',
		smarty_function_linktoself(array('name'=>'sort','value'=>'recent')) => 'Recent',
		'/search.php' => 'Search',
		'/browser/#!/'.implode('/',$browser)."/display=map" => 'Map',
		'/browser/#!/'.implode('/',$browser) => 'Advanced Browser');

		print '<div class="tabHolder" style="max-width:940px">';
		foreach ($links as $link => $name) {
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
		/* -- not implemented!
		print " Sort by: ";
		$links = array(
			smarty_function_linktoself(array('name'=>'alpha','value'=>0)) =>'Images',
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
		*/
		print '</div>';

#######################################

		print "<div class=interestBox>$count of {$imagelist->resultCount} results...</div>";

		print "<div style=\"columns: auto 213px; text-align:center;\">";
		foreach ($imagelist->images as $i => $image) {
			?>
 <a title="<? echo $image->grid_reference; ?> : <? echo htmlentities($image->title) ?> by <? echo htmlentities($image->realname); ?> - click to view full size image" href="/photo/<? echo $image->gridimage_id; ?>"><? echo $image->getThumbnail($thumbw,$thumbh,false,true,'loading=lazy src'); ?></a>
			<?
		}
		print "</div>";

		$keys = array_keys($links);
		$brower = array_pop($keys);

		if ($imagelist->resultCount > 10) {
		?>
			<br><br>
			<form style=\"background-color:#eee;padding:10px\">
				Search <b>within</b> these images:
				Keywords: <input type=search>
				Near: <input type=search>
				<input type=submit disabled value="Search..."><br>
				(not functional - for now goto <a href="<? echo  $brower; ?>">Browser</a> and filter there)
			</form>
			<br><br>
		<?
		}

#######################################

	} elseif (!empty($list)) {

		print '<div class="tabHolder" style="max-width:940px">';
		print " Sort by: ";
		$links = array(
			smarty_function_linktoself(array('name'=>'alpha','value'=>0)) =>'Images',
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

		print "<div class=interestBox>{$s['title']}</div>";

#######################################

		$name = $_GET['list'];

		print "<div style=\"columns: auto 18em\">";
		if (!empty($_GET['alpha'])) {
			$alpha = '';
			foreach ($list as $row) {
				$letter = strtolower( substr($row[$s['column']],0,1));
if (empty($letter))
	continue;
				if ($alpha != $letter) {
	                                if ($alpha) print "</ol></div>";

        	                        $alpha = $letter;
                	                print "<div style=\"break-inside: avoid;\">"; //to keep the name with the list!
  //                      	      print "<h4>$alpha</h4>";
                                	print "<ol style=\"padding-left:6em\">";
	                        }

				print "<li value=\"{$row['count']}\">";
				$link = smarty_function_linktoself(array('name'=>$name,'value'=>$row[$s['column']]));
				$value = htmlentities($row[$s['column']]);
				print "<a href=\"$link\" style=text-decoration:none>$value</a>";
				print "</li>";
			}
                        if ($alpha) print "</ol></div>";

#######################################

		} else {
			print "<ol style=\"padding-left:6em\">";
			foreach ($list as $row) {
				print "<li value=\"{$row['count']}\">";
				$link = smarty_function_linktoself(array('name'=>$name,'value'=>$row[$s['column']]));
				$value = htmlentities($row[$s['column']]);
				print "<a href=\"$link\">$value</a>";
				print "</li>";
			}
			print "</ol>";
		}
		print "</div>";
	}

#######################################

$smarty->display('_std_end.tpl');




function get_list_mva($where,$match,$group,$column,$order = 'count DESC') {
	global $sph;

	if (!empty($match))
		$where[] = "MATCH(".$sph->Quote(implode(' ',$match)).")";
	elseif (empty($where))
		$where[] = "MATCH('@country Ireland')"; //somethign!

	$where = implode(' AND ',$where);

//print $where;

	$list = $sph->getAll("SELECT GROUPBY() as groupby,{$group},{$column},COUNT(*) AS count FROM sample8 WHERE $where GROUP BY {$group} ORDER BY $order LIMIT 1000");
	if (!empty($list) && preg_match('/_ids$/',$group)) {
		foreach ($list as &$row) {
			$ids = explode(',',$row[$group]);
	                $names = explode('_SEP_',$row[$column]);array_shift($names); //the first is always blank!
			$row[$column] = trim($names[array_search($row['groupby'],$ids)]);
			$row['groupby'] = $row[$column]; //we ALSO set this, just to allow easy resorting of array
		}
	}
	unset($row);
	return $list;
}

function get_list($where,$match,$column,$order = 'count DESC') {
	global $sph;

	if (empty($where) && empty($match))
		$match[] = 'Ireland Geograph';// just to have somehting?

	if (!empty($match))
		$where[] = "MATCH(".$sph->Quote(implode(' ',$match)).")";


	$where = implode(' AND ',$where);

//print "SELECT $column,COUNT(*) as count FROM sample8 WHERE $where GROUP BY $column ORDER BY $order";
		//this is simple query as not using MVAs
	$list = $sph->getAll("SELECT $column,COUNT(*) as count FROM sample8 WHERE $where GROUP BY $column ORDER BY $order");

	return $list;
}
