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

 customExpiresHeader(3600,false,true);

	$smarty->display('_std_begin.tpl');
	
	$db = GeographDatabaseConnection(true);

	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


	$date = date('____-m-d');


        print "<h2>This day in ... </h2>";



if (!isset($_GET['gr']) && !empty($_SESSION['photos'])) {

        $ids = array_reverse(array_keys($_SESSION['photos']));
        while(count($ids) > 30) {
                array_pop($ids);
        }
        if ($ids) {
		if ($row = $db->getRow("SELECT AVG(x) x, AVG(y) y FROM gridimage_search WHERE gridimage_id IN (".implode(',',$ids).")")) {
			$x = floor($row['x']);
			$y = floor($row['y']);
			$_GET['gr'] = $db->getOne("SELECT grid_reference FROM gridsquare WHERE x = $x AND y=$y")."*";
		}
        }
}

$mkey = '2'.@md5($_GET['gr']).$date;

$str =& $memcache->name_get('thisday',$mkey);

if (!empty($str)) {
	print $str;
} else {
	ob_start();

?>
<form method="get">
Optionally give preference to images in <input type="text" name="gr" value="<? echo @htmlentities($_GET['gr']); ?>" size="6" maxlength="6"/> <input type=submit value="Update"/><br/>
<small>(enter a grid-reference like SH5064 or TQ75 or NT)</small>
</form>

<?

                        $thumbh = 120;
                        $thumbw = 120;

$years = $db->getAll("select count(*) c,gridimage_id,imagetaken from gridimage_search where imagetaken like '$date' group by substring(imagetaken,1,4) desc");

	if (count($years)) {
		
		if (!empty($_GET['gr'])) {
			if (preg_match('/([A-Za-z]{1,2})(\d*)(\d*)(\d*)(\d*)/',$_GET['gr'],$m)) {
				$gr = array();
				$gr[] = $m[1];
				if (!empty($m[4])) {
					$gr[] = $m[1].$m[2].$m[4];
					$gr[] = $m[0];
				} elseif (!empty($m[2])) {
					$gr[] = $m[1].$m[2].$m[3];
				}
				if (count($gr) > 1) {
					$gr = '('.implode('|',$gr).')';
				} else {
					$gr = implode(' ',$gr);
				}
			}
		}

		foreach ($years as $year) {
			print "<div class=\"interestBox\" style=\"padding:0;margin-bottom:10px\"><h3>";
			print $y = substr($year['imagetaken'],0,4);

			$q = "@takenday ".date($y.'md');

			if (!empty($gr)) {
				$q = "($gr $q) | $q";
			}

			if ($year['c'] > 5) {
				print " <small>[{$year['c']} <a href=\"/search.php?searchtext=".urlencode($q)."&amp;do=1\">images</a>]</small>";				
			}
			print "</h3></div>";

			$imagelist=new ImageList;
			$imagelist->getImagesBySphinx($q,5,1);

			if (!empty($imagelist->images)) {
		        	foreach ($imagelist->images as $idx => $image) {


?>
	  <div style="float:left;position:relative; width:130px; height:130px">
	  <div align="center">
	  <a title="<? echo $image->grid_reference; ?> : <? echo htmlentities($image->title) ?> by <? echo htmlentities($image->realname); ?> - click to view full size image" href="/photo/<? echo $image->gridimage_id; ?>"><? echo $image->getThumbnail($thumbw,$thumbh,false,true); ?></a></div>
	  </div>
<?
				}
			}
			print "<br style=\"clear:both\"/>";
                }
	} else {
		print "nothing to display";
	}

	$str = ob_get_flush();

	$memcache->name_set('thisday',$mkey,$str,$memcache->compress,$memcache->period_long);
} 

	
	$smarty->display('_std_end.tpl');
	exit;

