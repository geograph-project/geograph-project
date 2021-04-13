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


	$smarty->display('_std_begin.tpl');

?>
<h2>Tags</h2>

<?

if (!empty($_GET['run'])) {
	$USER->mustHavePerm("admin");

	$db = GeographDatabaseConnection(false);

} else {
	$db = GeographDatabaseConnection(true);
}
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$dedup = null;
$patterns = $replacements = array();

if (empty($_GET['fix'])) $_GET['fix']=0; //avoid notice

#######################################

if ($_GET['fix'] == 1) {

	print "<h3>Proposed fix #1. Remove space before a lone 's'</h3>";
	print "<p>...to fix where an apostrophe was replaced by space during tag creation, Note: will only replace LOWER case 's' - so not replacing S in initials.</p>";

	$where = "tag like '% s%' and tag rlike binary ' s[[:>:]]'"; //like in theory slightly more effient so works as quick filter

	$dedup = "/(\w+) s\b/";
}

#not in the if() because want them to ALL to run!

	$patterns[] = "/ s\b/";
	$replacements[] = "s";

#######################################

if ($_GET['fix'] == 2) {

	print "<h3>Proposed fix #2. Remove dot(s) after 'st' word.</h3>";

	$where = "tag like '%st.%' and tag rlike '[[:<:]]st\\.'";

	$dedup = "/\bst\.+( *\w+)/i";
}

	$patterns[] = "/\b(st)\.+ */i";
	$replacements[] = "$1 ";

#######################################

if ($_GET['fix'] == 202) {

        print "<h3>Proposed fix #202. Remove dot(s) after Rd, Ave, Dr, Tce/Terr words.</h3>";

        $where = "tag like '%.%' and tag rlike '[[:<:]](rd|ave|dr|tce|terr)\\\\.'";

        $dedup = "/\b(?:rd|ave|dr|tce|terr)\.+( *\w+)/i";
}

        $patterns[] = "/\b(rd|ave|dr|tce|terr)\.+ */i";
        $replacements[] = "$1 ";


#######################################

if ($_GET['fix'] == 3) {

        print "<h3>Proposed fix #3. Remove dot at end of tag</h3>";
	print "<p>Note, does not remove, if part of abbreivation, eg wont touch [Stockport County F.C.]</p>";
        $where = "tag like '%.'";
}

        $patterns[] = "/(?<!\.\w)\.$/i";
        $replacements[] = "";

#######################################

if ($_GET['fix'] == 4) {

	print "<h3>Proposed fix #4. Merge 'next' and 'next to' prefix to 'near'.</h3>";
	print "<p>suspect 'next' is just a accidental shortening of 'next to', and 'next to'/'near' seems unnesseriy complication</p>";

	$where = "prefix IN ('next','next to')";

	//$dedup = "/\bst\.+( *\w+)/i";
}

	$patterns[] = "/next( to)?:(to )?(the )?/";
	$replacements[] = "near:";

#######################################

if ($_GET['fix'] == 5) {

        print "<h3>Proposed fix #5. Expand 'rd' to 'Road'</h3>";
        $where = "tag like '%rd%' and tag rlike '[[:<:]]Rd[[:>:]]'";
}

        $patterns[] = "/\b[Rr]d\b(\.?)/";
        $replacements[] = "Road$1";

#######################################

if ($_GET['fix'] == 6) {

        print "<h3>Proposed fix #6. Remove unsupported control charactors (some have snuck though!)</h3>";
        $where = "tag rlike '[|;,\\\\]+' AND prefix != 'top' AND prefix != 'wiki'"; //top prefix is a special case, allows commas etc
}

        $patterns[] = "/[|;,\\\\]+/";
        $replacements[] = " ";

#######################################

if ($_GET['fix'] == 7) {

        print "<h3>Proposed fix #7. Change emptpy tag to 'blank'</h3>";
        $where = "tag = ''";
}

        $patterns[] = "/^([\w ]+:)?\s*$/";
        $replacements[] = '${1}blank';

#######################################

if ($_GET['fix'] == 8) {

	print "<h3>Proposed fix #4. change nr. to a prefix</h3>";

	$where = "tag like 'nr. %'";
}

	$patterns[] = "/^nr. /";
	$replacements[] = "near:";

#######################################

if ($_GET['fix'] == 9) {

	print "<h3>Fix Milestone ID Prefix</h3>";

	$where = "tag rlike binary '^[[:upper:]]{2,4}[ .-][[:alpha:]][[:alpha:][:digit:]]+\.?$'";
	$where .= " AND prefix IN ('','milestone society national id')";
	$where .= " AND gt.user_id = 124913";

	$patterns[] = "/^([\w ]+:)?(.*)$/";
	$replacements[] = "milestoneid:$2";

	$patterns[] = "/\.+$/";
	$replacements[] = "";
}

#######################################
	// just to tidy up any fixes.

        $patterns[] = "/[ _]+/";
        $replacements[] = " ";

#######################################
// can test out with query. ALL fixes will be run against matching tags!

if (!empty($_GET['query'])) {
	$where = "tag LIKE ".$db->Quote($_GET['query']);
}

if (!empty($_GET['nodep']))
	$dedup = null;

#######################################

if (!empty($where)) {

	$tags = $db->getAll($sql = "
	select tag.tag_id,prefix,tag,tag.status,count(gridimage_id) images
	from tag left join gridimage_tag gt on (gt.tag_id = tag.tag_id and gt.status > 0)
	where $where
	group by tag.tag_id
	having status=1 OR images>0
	limit 1000"); 

	if (!empty($tags)) {

	print "<h4>Example tags (only some shown)</h4>";
	$done = array();
	print "<ol class=examples>"; //so can see spaces in tags!
	$bg = array('#eee','white');
	$i =0;
	foreach ($tags as $row) {

		if ($dedup && preg_match($dedup,$row['tag'],$m)) {
			if (isset($done[$m[1]]))
				continue;
			$done[$m[1]]=1;
		}

		if (isset($done[$row['tag']])) //because have dupes due to prefixes!
			continue;
		$done[$row['tag']]=1;

		if (!empty($row['prefix']))
			$row['tag'] = $row['prefix'].":".$row['tag'];

		$new = trim(preg_replace($patterns,$replacements,$row['tag']));
		if ($new == $row['tag']) //in some rare cases the $where finds stiff not fixed!
			continue;


		if ($i<=200)
			$c = $db->getOne("SELECT `count` FROM tag_stat WHERE tagtext = ".$db->Quote($new))+0;

		$color = $bg[$i%2];
		print "<li style=background-color:$color>[<tt>".htmlentities($row['tag'])."</tt>] from {$row['images']} images ".($c?"merged with":"becomes")."<br>";

		print "[<b><tt>".htmlentities($new)."</tt></b>]";


		if (isset($c)) {
			print " (already used on $c images)";
			unset($c);
		}


		if (!empty($_GET['sql'])) {
			$sqls = array();
			//NOTE, this DOES duplicate process_tag_typos!
				//doing it simplified here, because taht has to deal with reports and creating tickets!
			$tag_id1 = $row['tag_id'];
			if ($row['images'] == 0) {
				$sqls[] = "UPDATE tag SET status = 0 WHERE tag_id = $tag_id1";
			} else {
				$values = tagToSQL($new);
				$row2 = $db->getRow("SELECT tag_id,status FROM tag WHERE ".implode(' AND ',$values));

				if (!empty($row2) && ($tag_id2 = $row2['tag_id'])) {
					$sqls[] = "UPDATE tag SET status = 0, canonical=$tag_id2 WHERE tag_id = $tag_id1";
					if (!$row2['status'])
						$sqls[] = "UPDATE tag SET status = 1 WHERE tag_id = $tag_id2";

					$sqls[] = "UPDATE IGNORE gridimage_tag SET tag_id = $tag_id2 WHERE tag_id = $tag_id1";
					$sqls[] = "UPDATE gridimage_tag SET status=0 WHERE tag_id = $tag_id1"; //any left after first update, failed due to duplicate key issues. Can now delete the duplicate
				} else {
					///$sqls[] = "INSERT INTO tag SET created =

					//actully just lets update the tag!
					$values[] = "status = 1";
					$sqls[] = "UPDATE tag SET ".implode(', ',$values)." WHERE tag_id = $tag_id1";

					//.. but need to make sure scripts/update_tags.php notices the tag changed!
					$sqls[] = "UPDATE gridimage_tag SET updated=NOW() WHERE tag_id = $tag_id1 AND status = 2";
				}
			}
			if (!empty($sqls)) {
				print "<pre>".implode(";\n",$sqls).";</pre>";
				if (!empty($_GET['run'])) {
					foreach ($sqls as $sql) {
						$db->Execute($sql) or die($db->ErrorMsg());
					}
					if ($i > $_GET['run'])
						exit;
				}
			}
		}

		$i++;
	}
	print "</ol>";

	$c = count($tags);
	if ($c == 1000) {
		print "There appear to be <b>at least 1000</b> affected tags";
	} else {
		print "There appear to be $c affected tags";
	}

	} else {
		 print "There appear to be zero affected tags";
	}
}

?>
<style>
ol.examples li{
	margin-bottom:6px;
	color:gray;
}
ol.examples tt {
	white-space:pre;
	color:black;
}
</style>
<?

	$smarty->display('_std_end.tpl');
	exit;




function tagToSQL($tag,$sep = null) {
	global $db;

	$list = array();

	$bits = explode(':',$tag,2);
        if (count($bits) > 1) {
		$list[] = "prefix = ".$db->Quote(strtolower(trim($bits[0])));
		$list[] = "tag = ".$db->Quote(trim($bits[1]));
        } else {
		$list[] = "prefix = ''";
		$list[] = "tag = ".$db->Quote(trim($bits[0]));
        }
	if (!empty($sep))
		return implode($sep,$list);
	return $list;
}
