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

$sph = GeographSphinxConnection('sphinxql',true);

$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

?>
<h2>BiGrams</h2>
<div class=interestBox>
<form method=get>
Keyword: <input type=search name=q value="<? echo @htmlentities($_GET['q']); ?>" required><input type=submit><br>
Minimum Images: <input type=number name=min min=0 step=1 value="<? echo  intval(@$_GET['min']); ?>" size=5 style=text-align:right> (optional)

</form>
</div>
<?

if (!empty($_GET['q'])) {
	$q = preg_replace('/[^\w]+/','',$_GET['q']);
	if (strlen($q)<3) {
		print "Query too short";
	} else {
		$sql = "call keywords(".$sph->Quote("*$q*").",'bigrams',1)";
		$re = "/\b".preg_quote($q,'/')."\b/";
		$min = intval(@$_GET['min']);

		$recordSet = $sph->Execute($sql);
		if ($recordSet->RecordCount() === 0) {
			print "No matches";
		} else {
			print "<script src=\"".smarty_modifier_revision("/sorttable.js")."\"></script>";

		        print "<TABLE border='1' cellspacing='0' cellpadding='2' class=\"report sortable\" id=\"list\"><THEAD><TR>";
       		        print "<TH>Phrase</TH>";
       		        print "<TH>Images</TH>";
       		        print "<TH>Hits</TH>";
       		        print "<TH>Query</TH>";
		        print "</TR></THEAD><TBODY>";

			$c=0;
			while (!$recordSet->EOF) {
			        $r = $recordSet->fields;
				//| qpos | tokenized  | normalized   | docs  | hits  |

				if ($r['hits'] > $min
				&& preg_match($re,$r['normalized'])) { //because using * to get part phrase matches, actully getting part word matches too!
					print "<TR>";
				    	print "<td>".htmlentities($r['normalized'])."</TD>";
					print "<TD ALIGN=right>".$r['docs']."</TD>";
					print "<TD ALIGN=right>".$r['hits']."</TD>";
			    		print "<td><a href=\"/search.php?searchtext=".urlencode('"'.$r['normalized'].'"')."&amp;do=1\">Search Images</a></TD>";
			                print "</TR>";
					$c++;
				}

			        $recordSet->MoveNext();
			}
			$recordSet->Close();

			print "</TBODY></TABLE>";
			print "Found $c Phrases";
		}
	}
}


$smarty->display('_std_end.tpl');

