<?

/**
 * $Project: GeoGraph $
 * $Id: glossary.php 2960 2007-01-15 14:33:27Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2008 Barry Hunter (geo@barryhunter.co.uk)
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

if (empty($_SERVER['HTTP_USER_AGENT']))
        die("no scraping");

require_once('geograph/global.inc.php');
//init_session_or_cache(3600*3, 900); //cache publically, and privately


$smarty = new GeographPage;
pageMustBeHTTPS();

customGZipHandlerStart();

##customExpiresHeader(600,false,true);


$db = GeographDatabaseConnection(true);
$mkey = md5($_SERVER['QUERY_STRING']);


if (!empty($_GET['q'])) {
    if ($_GET['q'] == 'os') {
        $where = " AND (tags REGEXP ".$db->Quote("[[:<:]]os[[:>:]]").")";
    } else {
	$q=$db->Quote("%".trim($_GET['q'])."%");
        $where = " AND (content LIKE $q OR title LIKE $q OR tags LIKE $q)";
    }
} elseif (!empty($_GET['id'])) {
    $where = " AND answer_id = ".intval($_GET['id']);
} else {
    $where = '';
}

if (isset($_GET['l'])) {
    $where .= " AND level = ".intval($_GET['l']);
}


$by = 'target+0';
if (!empty($_GET['by']) && preg_match('/^\w+$/',$_GET['by']) && preg_match('/section|user_id|level|wiki|realname/',$_GET['by'])) {
    $by = $_GET['by'];
}
if ($by == 'level') {
    $by = "level asc,answer_id asc";
}

$data = $db->getAssoc("SELECT a.*,realname,question
FROM answer_answer a INNER JOIN user USING (user_id) INNER JOIN answer_question q USING (question_id)
WHERE a.status = 1 AND q.status = 1 $where
GROUP BY $by,level ASC,answer_id ASC LIMIT 5000");

#############################################################################################

header("Content-Type: text/plain");

    foreach ($data as $idx => $row) {

	print "# ".htmlentities($row['title'])."\n";
	print "=======\n\n";

	print htmlentities2($row['content'])."\n";

	if (count($data) > 1)
		print "\n************\n\n";
    }

