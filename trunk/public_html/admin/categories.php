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
init_session();

$USER->mustHavePerm("admin");

$smarty = new GeographPage;

$db = NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');  
#$db->debug = true;
$smarty->assign('page_title','Category Consolidation');
$smarty->display('_std_begin.tpl');

?><h2>Category Consolidation</h2>
<p>Use this page to correct and consolidate the user submitted image 'categories'. 
Use each text box to rename the categories.
You can merge multiple categories by setting them all to the new name.
Changed values are highlighted in gray. </p><?

	
	if ($_POST['submit']) {
		print "<p>Making the following changes:</p>";
		//get some counts just for showing the old number
		$arr = $db->GetAssoc("select imageclass,count(*) from gridimage ".
			"group by imageclass");
		
		ksort($arr);
		for ($c = 1; $c < $_POST['highc']; $c++) {
			if ($_POST['old'.$c] != $_POST['new'.$c] && !$skip[$_POST['old'.$c]]) {
				$isanother = false;
				//check if this is actully a swap?
				for($d = $c+1; $d < $_POST['highc']; $d++) {
					if ($_POST['old'.$c] == $_POST['new'.$d] &&
						$_POST['old'.$d] == $_POST['new'.$c]) {
						$isanother = true;
					}
				}
				if ($isanother) {
					//change one to a temp value
					print "<p>Updating '<i>".$_POST['old'.$c]."</i>'[".$arr[$_POST['old'.$c]]."] to '<b>-".$_POST['new'.$c]."</b>'.</p>";
					$sql = "UPDATE gridimage SET `imageclass` = '-".$_POST['new'.$c]."' WHERE `imageclass` = '".$_POST['old'.$c]."'";
					$db->Execute($sql);	
						//do the backwards swap
						print "<p>Updating '<i>".$_POST['new'.$c]."</i>'[".$arr[$_POST['new'.$c]]."] to '<b>".$_POST['old'.$c]."</b>'.</p>";
						$sql = "UPDATE gridimage SET `imageclass` = '".$_POST['old'.$c]."' WHERE `imageclass` = '".$_POST['new'.$c]."'";
						$db->Execute($sql);	
					//correct the temp value
					print "<p>Updating '<i>-".$_POST['new'.$c]."</i>'[".$arr[$_POST['old'.$c]]."] to '<b>".$_POST['new'.$c]."</b>'.</p>";
					$sql = "UPDATE gridimage SET `imageclass` = '".$_POST['new'.$c]."' WHERE `imageclass` = '-".$_POST['new'.$c]."'";
					$db->Execute($sql);	
					//we already have done the swap so dont want it to happen on the next iteration
					$skip[$_POST['new'.$c]]++;
				} else {
					print "<p>Updating '<i>".$_POST['old'.$c]."</i>'[".$arr[$_POST['old'.$c]]."] to '<b>".$_POST['new'.$c]."</b>'.</p>";
					$sql = "UPDATE gridimage SET `imageclass` = '".$_POST['new'.$c]."' WHERE `imageclass` = '".$_POST['old'.$c]."'";
					$db->Execute($sql);	
				}
			}

		}
		print "<p>All values updated</p>";
		print "<hr>";
	}
	$arr = $db->GetAssoc("select imageclass,count(*) from gridimage ".
			"group by imageclass");
	
	foreach(array('Urban Landscape',
		'Urban Landmark',
		'Open Countryside',
		'Farmland',
		'Woodland',
		'Water Bodies - Lakes and Rivers',
		'Mountains',
		'Marshland',
		'Coastline/Beaches') as $val) {
			if(!isset($arr[$val])) 
				$arr[$val]=0;
	}
	
	ksort($arr);

	

	print "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">";

	
	print "<p align=center>Change selected Box to <select name=\"list\" onchange=\"onc(this)\">";
	print "<option></option>";
	foreach ($arr as $val => $count) {
		print "<option value=\"$val\">$val [$count]</option>";
	}
	print "</select></p>";
	
	print "<table>";
	print "<tr><th>Old Value</th><th>Count</th><th>New Value</th></tr>";
	$c = 1; 
	foreach ($arr as $val => $count) {
		print "<tr>";
		print "<td>".($val?$val:'<i>-blank-</i>')."</td>";
		print "<td align=right><b>$count</b></td>";
		if ($count > 0) {
			print "<td><input type=hidden name=\"old$c\" value=\"$val\">";
			print "<input type=text name=\"new$c\" size=45 value=\"$val\" onfocus=\"onf(this)\" onblur=\"onb(this,$c)\">";
			print "<input type=button value=\"Reset\" onclick=\"oncl(this,$c)\"></td>";
		}
		print "</tr>";
		$c++;
	}
	print "</table>";
	print "<input type=hidden name=highc value=\"$c\">";
	print "<input type=submit name=submit value=\"Commit Changes\">";
	print "</form>";

?>
<script>
var selectedItem;

function onf(that) {
	selectedItem = that;
	that.style.backgroundColor = 'yellow';
	that.form.list.selectedIndex = 0;
}

function onb(that,num) {
	selectedItem.style.backgroundColor = (that.form['old'+num].value == that.value)?'':'lightgrey';
	that.form.list.selectedIndex = 0;

}

function oncl(that,num) {
	that.form['new'+num].value = that.form['old'+num].value;
	that.form['new'+num].style.backgroundColor = '';

}

function onc(that) {
	selectedItem.value = that.options[that.selectedIndex].value;
	selectedItem.focus();
}
</script>
<p>Warning: Be careful using this page to swap categories, 
it can cope will two way swap, but three ways swaps will probably get confused</p>
<?

$smarty->display('_std_end.tpl');


	
?>
