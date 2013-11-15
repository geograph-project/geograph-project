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
class RebuildContentDupDelta extends EventHandler
{
	function processEvent(&$event)
	{
		//perform actions

		$db=&$this->_getDB();

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
	created 
FROM blog
WHERE approved = 1 AND published < NOW()
AND updated > DATE_SUB(NOW(),INTERVAL 2 HOUR)
		");

#######################
#SNIPPET
		$db->Execute("
INSERT INTO `content_tmp`
SELECT 
	NULL AS content_id, 
	s.snippet_id AS foreign_id, 
	TRIM(title) AS title, 
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
	comment AS words, 
	'snippet' AS source, 
	'info' AS type, 
	MAX(gs.created) AS updated, 
	s.created 
FROM snippet s
INNER JOIN gridimage_snippet gs ON (s.snippet_id = gs.snippet_id AND gridimage_id < 4294967296)
LEFT JOIN gridsquare g USING (grid_reference)
WHERE s.updated > DATE_SUB(NOW(),INTERVAL 2 HOUR) AND s.enabled = 1
GROUP BY s.snippet_id
ORDER BY NULL
		");

#####################################
# GEO-TRIPS


if (true) {
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
        `date` AS created
FROM geotrips t left join queries_count c on (c.id = t.search)
WHERE t.updated > UNIX_TIMESTAMP(DATE_SUB(NOW(),INTERVAL 2 HOUR))
                ");
}

#####################################

#Tidy up....

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
	type = ct.type,
	views = ct.views,
	updated = ct.updated,
	created = ct.created
		");


		$db->Execute("DROP TABLE `content_tmp`");

		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
}
