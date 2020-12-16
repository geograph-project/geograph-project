<?php
/**
 * $Project: GeoGraph $
 * $Id: viewps.php 2295 2006-07-05 12:15:49Z barryhunter $
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

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

if (empty($_GET['tab']))
	$_GET['tab'] = 'overview';

$tabs = explode(' ','overview recent stat general');

print "<p>";
foreach ($tabs as $key) {
        if ($_GET['tab'] == $key) {
                print " | <b>$key</b>";
        } else {
                print " | <a href=\"?tab=$key\">$key</a>";
        }
}
print " |</p>";

if ($_GET['tab'] == 'overview') {


$sql = "(select 'unknown' as status, count(distinct gridimage_id) as images, count(distinct substring_index(url,'/',3)) as hosts,
count(distinct url) as urls,count(distinct nullif(archive_url,'')) as saved,count(distinct if(archive_url='' and archive_checked > '2000-00-00',url,null)) as missing
from gridimage_link WHERE parent_link_id = 0 AND next_check < NOW())
UNION
(select IF(HTTP_Status_final!=HTTP_Status,CONCAT(HTTP_Status div 100,'xx->',HTTP_Status_final div 100,'xx'),CONCAT(HTTP_Status_final div 100,'xx')) as status,
count(distinct gridimage_id) as images,count(distinct substring_index(url,'/',3)) as hosts,
count(distinct url) as urls,count(distinct nullif(archive_url,'')) as saved,count(distinct if(archive_url='' and archive_checked > '2000-00-00',url,null)) as missing
FROM gridimage_link WHERE parent_link_id = 0 and next_check > NOW() and next_check < '9999-00-00' AND (HTTP_Status_final > 0 OR HTTP_Status > 599)
GROUP BY HTTP_Status DIV 100,HTTP_Status_final div 100)
";

$t = $c = "";
if (!empty($_GET['user_id'])) {
	$sql = str_replace("gridimage_link ","gridimage_link inner join gridimage_search using (gridimage_id)",$sql);
	$sql = str_replace("WHERE ","WHERE user_id = ".intval($_GET['user_id'])." AND ",$sql);
}

        $title = "Complete overview";
        dump_sql_table($sql,$title);

if (!empty($_GET['debug']))
	print $sql;

} elseif ($_GET['tab'] == 'outstanding') {

	$crit = array();
	$crit['links-2A-find-archives'] = "archive_checked LIKE '0000%' AND next_check < '9999-01-01' AND url NOT like '%geograph.org.uk/%' AND url NOT like '%geograph.ie/%' AND parent_link_id = 0";

	$crit['links-2B-request-archives'] = "archive_url = '' AND archive_checked NOT LIKE '0000%' AND HTTP_Status_final IN (200,304) AND archive_requested < date_sub(NOW(), INTERVAL 90 DAY)";

	$crit['links-2A-find-archives.retry'] = "archive_url = '' AND archive_requested NOT LIKE '0000%' AND next_check < '9999-01-01' AND updated < DATE_SUB(NOW(),interval 24 hour)";


	$crit['links-3-check-links'] = "next_check < now() AND parent_link_id = 0 AND url NOT like 'http://www.geograph.org.uk/%' AND url NOT like 'http://www.geograph.ie/%'";

	$crit['links-3B-check-geograph'] = "next_check < now() AND parent_link_id = 0 AND (url like 'http://www.geograph.org.uk/%' OR url like 'http://www.geograph.ie/%')";

	$crit['links-3-check-links.geograph'] = "next_check < now() AND parent_link_id = 0 AND (url like 'http://www.geograph.org.uk/%' OR url like 'http://www.geograph.ie/%')";


	$crit['links-4-fix-punct-urls'] = "archive_url != '' AND HTTP_Status > 200 AND url not rlike '[[:alpha:][:digit:]/&#]$'
	        AND NOT(url like 'http://www.geograph.org.uk/%' OR url like 'http://www.geograph.ie/%') AND parent_link_id = 0
        	AND next_check < '9999-01-01' AND fix_attempted LIKE '0000%'";

	$crit['links-6-soft404-check'] = "HTTP_Status_final = 200 AND (
            (page_title = SUBSTRING_INDEX(SUBSTRING_INDEX(url,'/',3),'/',-1)) OR
            (page_title = REPLACE('www.','',SUBSTRING_INDEX(SUBSTRING_INDEX(url,'/',3),'/',-1))) OR
            (page_title like '%Not Found%') OR
            (HTTP_Status in (301,302) AND HTTP_Location RLIKE '[[:<:]](404|error)[[:>:]]')
        ) AND parent_link_id = 0 AND next_check < '9999-01-01' AND soft_checked LIKE '0000%'";


	print "<table>";
	print "<tr><th align=left>script</th><th>count</th><th>urls</th><th>images</th>";
	foreach ($crit as $key => $where) {
		print "<tr><th align=left title=\"$where\">$key</th>";
		$row = $db->getRow("SELECT COUNT(*),COUNT(DISTINCT url),COUNT(DISTINCT gridimage_id) FROM gridimage_link WHERE $where");
		print "<td align=right>".implode("</td><td align=right>",$row)."</td>";
		print "</tr>";
	}


} elseif ($_GET['tab'] == 'recent') {
	$crit = "> date_sub(now(),interval 1 day) AND parent_link_id = 0";
	print "<div style='float:left;width:560px'>";
	$sql = "SELECT SUBSTRING(last_checked,1,13) AS hour,count(*) as cnt,count(distinct substring_index(url,'/',3)) as hosts,count(distinct url) as urls,sum(HTTP_Status=0) as `?`,sum(HTTP_Status=200) as `200`,sum(HTTP_Status between 300 and 310) as 3xx,sum(HTTP_Status between 400 and 499) as 4xx,sum(HTTP_Status between 500 and 599) as 5xx,sum(HTTP_Status between 600 and 699) as 6xx
		 FROM gridimage_link WHERE last_checked  $crit GROUP BY SUBSTRING(last_checked,1,13) DESC";
	$title = "Most recent by HTTP Status";
	dump_sql_table($sql,$title);
	print "</div>";

	$cols = "count(*) as cnt,count(distinct url) as urls,sum(archive_url!='') as found,sum(archive_url='') as missing";
        print "<div style='float:left;width:300px'>";
        $sql = "SELECT SUBSTRING(archive_checked,1,13) AS hour, $cols
                 FROM gridimage_link WHERE archive_checked $crit GROUP BY SUBSTRING(archive_checked,1,13) DESC";
        $title = "Most recent archived checked";
        dump_sql_table($sql,$title);
        print "</div>";

        print "<div style='float:left;width:300px'>";
        $sql = "SELECT SUBSTRING(archive_requested,1,10) AS day, $cols
                 FROM gridimage_link WHERE archive_requested > date_sub(now(),interval 10 day) GROUP BY SUBSTRING(archive_requested,1,10) DESC";
        $title = "Most recent requested by day";
        dump_sql_table($sql,$title);
        print "</div>";

        print "<div style='float:left;width:300px'>";
        $sql = "SELECT SUBSTRING(last_found,1,10) AS day, count(*) as cnt,count(distinct url) as urls
                 FROM gridimage_link WHERE last_found > date_sub(now(),interval 10 day) AND parent_link_id = 0 GROUP BY SUBSTRING(last_found,1,10) DESC";
        $title = "Most recent found by day";
        dump_sql_table($sql,$title);
        print "</div>";


	exit;
} elseif ($_GET['tab'] == 'stat') {


	$domains = array("http://www.geograph.org.uk/","https://www.geograph.org.uk/","http://www.geograph.ie/","https://www.geograph.ie/");

        $where = "parent_link_id = 0 AND url NOT like '".implode("%' AND url NOT like '",$domains)."%'";


	$stat = array();
	$stat['TOTAL LINKS'] =
                $db->getOne("select count(distinct url) from gridimage_link where $where AND next_check < '9999-01-01'");

	$stat['still to check'] =
		$db->getOne("select count(distinct url) from gridimage_link where $where AND HTTP_Status = 0 AND next_check < '9999-01-01'");

	$stat['Dead checked recently'] =
		$db->getOne("select count(distinct url) from gridimage_link where $where AND next_check > NOW() AND next_check < '9999-01-01'");
	$stat['... of which apprently ok'] =
		$db->getOne("select count(distinct url) from gridimage_link where $where AND next_check > NOW() AND next_check < '9999-01-01' AND HTTP_Status = 200");
	$stat['... of which redirected but ok'] =
		$db->getOne("select count(distinct url) from gridimage_link where $where AND next_check > NOW() AND next_check < '9999-01-01' AND HTTP_Status between 300 and 310 AND HTTP_Status_final = 200");
	$stat['... of which broken'] =
		$db->getOne("select count(distinct url) from gridimage_link where $where AND next_check > NOW() AND next_check < '9999-01-01' AND HTTP_Status > 400");
	$stat['... of which redirected to error page'] =
		$db->getOne("select count(distinct url) from gridimage_link where $where AND next_check > NOW() AND next_check < '9999-01-01' AND HTTP_Status between 300 and 310 AND HTTP_Status_final != 200");

	$stat['Archive existance checked'] =
		$db->getOne("select count(distinct url) from gridimage_link where $where AND archive_checked not like '0000%'");
	$stat['... of which found to be Archived'] =
		$db->getOne("select count(distinct url) from gridimage_link where $where AND archive_requested like '0000%' and archive_url != ''");
	$stat['... of which archived for now broken links'] =
		$db->getOne("select count(distinct url) from gridimage_link where $where AND next_check > NOW() AND next_check < '9999-01-01' AND HTTP_Status > 400 AND archive_url != ''");
	$stat['... of which was not archived, but now archived by request'] =
		$db->getOne("select count(distinct url) from gridimage_link where $where AND archive_requested not like '0000%' and archive_url != ''");

	print "<pre>";
	print_r($stat);
	exit;
} elseif ($_GET['tab'] == 'general') {

$hour = "> DATE_SUB(NOW(), INTERVAL 1 HOUR)";

$sql = "SELECT COUNT(*),HTTP_Status,SUM(archive_url!='')/COUNT(*)*100
, SUM(last_checked $hour) AS checked
, SUM(archive_url != '' AND archive_checked $hour) AS archived
, SUM(archive_requested $hour) AS requested
FROM gridimage_link
 WHERE parent_link_id = 0 GROUP BY HTTP_Status DIV 100 WITH ROLLUP";
$title = "Grouped by status";
dump_sql_table($sql,$title);

$cols = "gridimage_id,url,HTTP_Status,page_title,last_found,last_checked,archive_url,archive_checked,archive_requested";

$sql = "SELECT $cols FROM gridimage_link WHERE parent_link_id = 0 ORDER BY last_found DESC LIMIT 3";
$title = "Most recent Found";
dump_sql_table($sql,$title);

$sql = "SELECT $cols FROM gridimage_link WHERE parent_link_id = 0 ORDER BY last_checked DESC LIMIT 3";
$title = "Most recent Checks";
dump_sql_table($sql,$title);

$sql = "SELECT $cols FROM gridimage_link WHERE parent_link_id = 0 ORDER BY archive_requested DESC LIMIT 3";
$title = "Most recent Archive Requested";
dump_sql_table($sql,$title);

$sql = "SELECT $cols FROM gridimage_link WHERE parent_link_id = 0 ORDER BY archive_checked DESC LIMIT 3";
$title = "Most recent Archive Checked";
dump_sql_table($sql,$title);


}


function dump_sql_table($sql,$title,$autoorderlimit = false) {
	$result = mysql_query($sql.(($autoorderlimit)?" order by count desc limit 25":'')) or die ("Couldn't select photos : $sql " . mysql_error() . "\n");

	$row = mysql_fetch_array($result,MYSQL_ASSOC);

	print "<H3>$title</H3>";

	print "<TABLE border='1' cellspacing='0' cellpadding='2'><TR>";
	foreach ($row as $key => $value) {
		print "<TH>$key</TH>";
	}
	print "</TR>";
	do {
		print "<TR>";
		$align = "right";
		foreach ($row as $key => $value) {
			if ($key == 'ip' || $key == 'useragent') {
				print "<TD ALIGN=$align><A HREF=\"?$key=".urlencode($value)."\">".htmlentities($value)."</A></TD>";
			} else {
				print "<TD ALIGN=$align>".htmlentities($value)."</TD>";
			}
			$align = "left";
		}
		print "</TR>";
	} while ($row = mysql_fetch_array($result,MYSQL_ASSOC));
	print "</TR></TABLE>";
}

