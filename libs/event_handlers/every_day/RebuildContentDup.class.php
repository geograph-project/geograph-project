<?php
/**
 * $Project: GeoGraph $
 * $Id: RebuildUserStats.class.php 3288 2007-04-20 11:32:27Z barry $
 * 
 * GeoGraph geographic photo archive project
 * http://geograph.sourceforge.net/
 *
 * This file copyright (C) 2005  Barry Hunter (geo@barryhunter.co.uk)
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

/**
*
* @package Geograph
* @author Barry Hunter <geo@barryhunter.co.uk>
* @version $Revision: 3288 $
*/

require_once("geograph/eventhandler.class.php");
require_once('geograph/searchcriteria.class.php');
require_once('geograph/searchengine.class.php');

//filename of class file should correspond to class name, e.g.  myhandler.class.php
class RebuildContentDup extends EventHandler
{
	function processEvent(&$event)
	{
		global $memcache;
		//perform actions

		$db=&$this->_getDB();


                $data = $db->getRow("SHOW TABLE STATUS LIKE 'content_tmp'");

                if (!empty($data['Create_time']) && strtotime($data['Create_time']) > (time() - 60*60*3)) {
                        //if a recent table give up this time. It might still be running.
                        return false;
                }


		$db->Execute("DROP TABLE IF EXISTS `content_tmp`");
		$db->Execute("CREATE TABLE `content_tmp` LIKE `content`");

		//we dont want the auto_increment, otherwise it will populate on the temp table, and mess up 'ON DUPLICATE KEY'
		$db->Execute("ALTER TABLE `content_tmp` CHANGE `content_id` `content_id` INT(10) UNSIGNED NULL, DROP PRIMARY KEY");

#######################
#BLOG
		$db->Execute("

INSERT INTO `content_tmp`
SELECT 
	NULL AS content_id, 
	blog_id AS foreign_id, 
	TRIM(title) AS title, 
	CONCAT('/blog/',blog_id) AS url, 
	user_id AS user_id, 
	gridimage_id, 
	gridsquare_id, 
	'' AS extract, 
	0 AS images, 
	0 AS wordcount, 
	views+views_archive AS views, 
	0 AS titles, 
	0 AS tags, 
	content AS words, 
	'blog' AS source, 
	'info' AS type, 
	updated, 
	created,
        0 as wgs84_lat,
        0 as wgs84_long,
	null as sequence
FROM blog
WHERE approved = 1 AND published < NOW()

		");

#######################
#Category
		$db->Execute("

INSERT INTO `content_tmp`
SELECT 
	NULL AS content_id, 
	CRC32(LOWER(gi.imageclass)) AS foreign_id, 
	TRIM(gi.imageclass) AS title, 
	CONCAT('/search.php?imageclass=',REPLACE(gi.imageclass,' ','+')) AS url, 
	0 AS user_id, 
	gridimage_id, 
	0 AS gridsquare_id, 
	IF(canonical IS NULL,'',CONCAT('Parent: ',canonical)) AS extract, 
	COUNT(*) AS images, 
	0 AS wordcount, 
	0 AS views, 
	0 AS titles, 
	0 AS tags, 
	0 AS words, 
	'category' AS source, 
	'info' AS type, 
	MAX(submitted) AS updated, 
	MIN(submitted) AS created,
        0 as wgs84_lat,
        0 as wgs84_long,
        null as sequence
FROM gridimage_search gi
LEFT JOIN category_canonical USING (imageclass)  
GROUP BY gi.imageclass;

		");

#######################
#CONTEXT (NOT ALL TAGS!)

		$db->Execute("

INSERT INTO `content_tmp`
SELECT 
	NULL AS content_id, 
	gt.tag_id AS foreign_id, 
	TRIM(tag) AS title, 
	CONCAT('/search.php?tag=',REPLACE(IF(prefix!='',CONCAT(prefix,':',tag),tag),' ','+')) AS url, 
	0 AS user_id, 
	gi.gridimage_id, 
	0 AS gridsquare_id, 
	'' AS extract, 
	COUNT(*) AS images, 
	0 AS wordcount, 
	0 AS views, 
	0 AS titles, 
	0 AS tags, 
	0 AS words, 
	'context' AS source, 
	'info' AS type, 
	MAX(gt.created) AS updated, 
	t.created AS created,
        0 as wgs84_lat,
        0 as wgs84_long,
        null as sequence
FROM gridimage_search gi
INNER JOIN gridimage_tag gt USING (gridimage_id)
INNER JOIN tag t USING (tag_id)
WHERE gt.status = 2 AND t.status = 1 AND prefix = 'top'
GROUP BY gt.tag_id;

		");

#######################
#SNIPPET
		$db->Execute("

INSERT INTO `content_tmp`
SELECT 
	NULL AS content_id, 
	s.snippet_id AS foreign_id, 
	TRIM(s.title) AS title, 
	CONCAT('/snippet/',s.snippet_id) AS url, 
	s.user_id, 
	gridimage_id, 
	gridsquare_id, 
	'' AS extract, 
	COUNT(gridimage_id) AS images, 
	0 AS wordcount, 
	0 AS views, 
	0 AS titles, 
	0 AS tags, 
	s.comment AS words, 
	'snippet' AS source, 
	'info' AS type, 
	MAX(gs.created) AS updated, 
	s.created,
        s.wgs84_lat,
        s.wgs84_long,
        null as sequence
FROM snippet s
INNER JOIN gridimage_snippet gs USING (snippet_id)
INNER JOIN gridimage_search gi USING (gridimage_id)
LEFT JOIN gridsquare g ON (g.grid_reference = s.grid_reference)
WHERE s.enabled = 1
GROUP BY s.snippet_id;

		");

#######################
#USER
		$db->Execute("

INSERT INTO `content_tmp`
SELECT 
	NULL AS content_id, 
	u.user_id AS foreign_id, 
	TRIM(u.realname) AS title, 
	CONCAT('/profile/',u.user_id) AS url, 
	0 AS user_id, 
	gridimage_id, 
	0 AS gridsquare_id, 
	IF(nickname='','',CONCAT('Nickname: ',nickname)) AS extract, 
	images, 
	0 AS wordcount, 
	0 AS views, 
	0 AS titles, 
	0 AS tags, 
	'' AS words, 
	'user' AS source, 
	'info' AS type, 
	submitted AS updated, 
	DATE(signup_date) AS created,
        0 as wgs84_lat,
        0 as wgs84_long,
        null as sequence
FROM user u
INNER JOIN user_stat USING (user_id)
INNER JOIN gridimage_search ON (last=gridimage_id);

		");

#  UNIQUE KEY `foreign_id` (`foreign_id`,`source`)

#####################################
# GEO-TRIPS


                $db->Execute("

INSERT INTO `content_tmp`
SELECT
        NULL AS content_id,
        t.id AS foreign_id,
        TRIM(IF(title='',CONCAT(location,' from ',start),title)) AS title,
        CONCAT('/geotrips/',t.id) AS url,
        uid AS user_id,
        img AS gridimage_id,
        0 AS gridsquare_id,
        IF(LENGTH(descr)>500,CONCAT(SUBSTRING(descr,1,500),' ...'),descr) AS extract,
        coalesce(c.count,0) AS images,
        0 AS wordcount,
        0 AS views,
        0 AS titles,
        type AS tags,
        descr AS words,
        'trip' AS source,
        'info' AS type,
        FROM_UNIXTIME(updated) AS updated,
        `date` AS created,
        0 as wgs84_lat,
        0 as wgs84_long,
        null as sequence
FROM geotrips t left join queries_count c on (c.id = t.search)

                ");

#####################################
# FAQ Posts

                $db->Execute("

INSERT INTO `content_tmp`
SELECT
        NULL AS content_id,
        a.answer_id AS foreign_id,
        TRIM(q.question) AS title,
        CONCAT('/faq3.php?a=',a.answer_id,'#',a.answer_id) AS url,
        a.user_id AS user_id,
        0 AS gridimage_id,
        0 AS gridsquare_id,
        target AS extract,
        0 AS images,
        0 AS wordcount,
        0 AS views,
        section AS titles,
        a.tags AS tags,
        a.content AS words,
        'faq' AS source,
        'document' AS type,
        a.updated AS updated,
        a.created AS created,
        0 as wgs84_lat,
        0 as wgs84_long,
        null as sequence
FROM answer_answer a
	INNER JOIN user USING (user_id)
	INNER JOIN answer_question q USING (question_id)
	WHERE a.status = 1 AND q.status = 1
	GROUP BY level ASC,answer_id ASC

                ");

#####################################
# Link entries.

$h = fopen("https://www.geograph.org/links/download_tsv.php",'r');
while ($h && !feof($h)) {
	$bits = explode("\t",trim(fgets($h)));
	##0link_id  1sites  2url  3title  4excerpt  5description  6introduced  7experimental  8category  9developer  10tags  11created  12updated

	$count=0;
	$updates = array();
	$updates['url'] = preg_replace('/^https?:\/\/www\.geograph\.org\.uk/','',$bits[2],-1,$count);
	if ($count != 1)
		continue;

	$updates['foreign_id'] = $bits[0];
	$updates['title'] = $bits[3];
	if (!empty($bits[4])) {
		$updates['extract'] = $bits[4];
		$updates['words'] = $bits[5];
	} else
		$updates['extract'] = $bits[5];
	$updates['tags'] = $bits[8];
		if (!empty($bits[10])) $updates['tags'] .= ', '.$bits[10];
	$updates['source'] = 'link';
	$updates['type'] = 'document';
	$updates['created'] = (!empty($bits[6]) && $bits[6] > '2000-00-00')?$bits[6]:$bits[11];
	$updates['updated'] = $bits[12];

	$db->Execute('INSERT INTO content_tmp SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
}

#####################################
# keep any lat/long coordinates we happen to have.

$db->Execute("
UPDATE `content_tmp` ct INNER JOIN `content` c USING (gridsquare_id)
SET ct.wgs84_lat = c.wgs84_lat, ct.wgs84_long = c.wgs84_long, ct.sequence = c.sequence
WHERE ct.wgs84_lat = 0 AND ct.gridsquare_id > 0
");

#####################################
# copy over the updates

//... works because have UNIQUE KEY(`foreign_id`,`source`),

$db->Execute("
INSERT INTO `content`
SELECT * FROM `content_tmp` ct
ON DUPLICATE KEY UPDATE
	title = ct.title,
	url = ct.url,
	user_id = ct.user_id,
	gridimage_id = ct.gridimage_id,
	gridsquare_id = ct.gridsquare_id,
	extract = ct.extract,
	images = IF(ct.images=0,content.images,ct.images),
	words = ct.words,
	tags = ct.tags,
	type = ct.type,
	views = ct.views,
	updated = ct.updated,
	created = ct.created,
        wgs84_lat = ct.wgs84_lat,
        wgs84_long = ct.wgs84_long;
");

#####################################
# finally delete any gone
# ... use a dynamic list, so will only delete ones we actully update!

$list = $db->getCol("SELECT DISTINCT source FROM `content_tmp`");

$db->Execute("
DELETE `content`.*
FROM `content`
	LEFT JOIN `content_tmp` USING (`foreign_id`,`source`)
WHERE `content`.source IN ('".implode("','",$list)."')
	AND `content_tmp`.`foreign_id` IS NULL;
");

#####################################

		$db->Execute("DROP TABLE `content_tmp`");

		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}

}

