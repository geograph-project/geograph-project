<?php
/**
 * $Project: GeoGraph $
 * $Id: recreate_maps.php 2996 2007-01-20 21:39:07Z barry $
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


//these are the arguments we expect
$param=array();


chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

//insert a FAKE log (just so we can plot on a graph ;)
$db->Execute("INSERT INTO event_log SET
        event_id = 0,
        logtime = NOW(),
        verbosity = 'trace',
        log = 'running event_handlers/every_day/".basename($argv[0])."',
        pid = 33");

############################################

$status = $db->getAssoc("SHOW TABLE STATUS LIKE 'sphinx_%'");

if (isset($status['sphinx_tags']) && !empty($status['sphinx_tags']['Update_time'])) {

	$sqls = array();

	fwrite(STDERR,date('H:i:s ')."Building DELTA...\n");

	$crit = $status['sphinx_tags']['Update_time'];
	$sqls[] = "CREATE TEMPORARY TABLE sph_delta_ids (primary key (gridimage_id)) ENGINE=MyISAM".
		" SELECT gridimage_id FROM gridimage_search WHERE upd_timestamp >= '$crit'";
	 
	$sqls[] = "insert ignore into sph_delta_ids select distinct gridimage_id FROM gridimage_tag WHERE updated >= '$crit' and gridimage_id < 4294967296";

	foreach ($sqls as $sql) {
		fwrite(STDERR,date('H:i:s ')." $sql\n\n");
		$db->Execute($sql);
	}

        $minmax = $db->getRow("SELECT MIN(gridimage_id) as min,MAX(gridimage_id) as max FROM sph_delta_ids");

	if (empty($minmax['min']))
		die("No updated rows\n");
} else {
	die("No sphinx_tags, or unable to get its updated time, probably use createsphinx-new.php create table from scratch!\n");
}

#####################################################

if (isset($status['sphinx_tags'])) {

	#####################################################

	$db->Execute("DROP TABLE IF EXISTS sphinx_tags_tmp");

	$sqls = array();

	fwrite(STDERR,date('H:i:s ')."Building Tags...\n");

	$sql = "
			SELECT gridimage_id,
				GROUP_CONCAT(DISTINCT IF(prefix='top',tag,NULL) ORDER BY tag_id SEPARATOR ';') AS contexts,
				GROUP_CONCAT(DISTINCT IF(prefix='top',tag_id,NULL) ORDER BY tag_id SEPARATOR ',') AS context_ids,
				GROUP_CONCAT(DISTINCT IF(prefix='subject',tag,NULL) ORDER BY tag_id SEPARATOR ';') AS subjects,
				GROUP_CONCAT(DISTINCT IF(prefix='subject',tag_id,NULL) ORDER BY tag_id SEPARATOR ',') AS subject_ids,
				GROUP_CONCAT(DISTINCT IF(prefix='type',tag,NULL) ORDER BY tag_id SEPARATOR ';') AS types,
				GROUP_CONCAT(DISTINCT IF(prefix='type',tag_id,NULL) ORDER BY tag_id SEPARATOR ',') AS type_ids,
				GROUP_CONCAT(DISTINCT IF(prefix='top' OR prefix='bucket' OR prefix='type' OR prefix='subject',NULL,tagtext) ORDER BY final_id SEPARATOR ';') AS tags,
				GROUP_CONCAT(DISTINCT IF(prefix='top' OR prefix='bucket' OR prefix='type' OR prefix='subject',NULL,final_id) ORDER BY final_id SEPARATOR ',') AS tag_ids
			FROM gridimage_tag gt INNER JOIN tag t USING (tag_id) INNER JOIN tag_stat USING (tag_id)
			INNER JOIN sph_delta_ids USING (gridimage_id)
			WHERE gt.status = 2 and t.status = 1 AND __between__
			GROUP BY gridimage_id ORDER BY NULL";

        for($q=$minmax['min'];$q<$minmax['max'];$q+=100000) {
		$between = "gridimage_id BETWEEN ".($q)." AND ".($q+99999);
		$sqls[] = (count($sqls)?"INSERT INTO sphinx_tags_tmp ":"CREATE TABLE sphinx_tags_tmp (gridimage_id INT UNSIGNED) ENGINE=MyISAM").
			str_replace('__between__',$between, $sql);
	}

	$sqls[] = "ALTER TABLE sphinx_tags_tmp ADD PRIMARY KEY(gridimage_id)";

	foreach ($sqls as $sql) {
		fwrite(STDERR,date('H:i:s ')." $sql\n\n");
		$db->Execute($sql);
	}

	#####################################################

	$sqls = array();

        fwrite(STDERR,date('H:i:s ')."Building Fake Subject Tags...\n");

	//look up columns in table dynamiclly, although we know the list from the above query
	$columns = $db->getAssoc("DESCRIBE sphinx_tags");

	$cols = array();
	foreach ($columns as $column => $data) {
		if ($column == 'gridimage_id') {
			$cols[] = 'gridimage_id';
		} elseif ($column == 'subjects') {
			$cols[] = "GROUP_CONCAT(DISTINCT IF(prefix='subject',tag,NULL) ORDER BY tag_id SEPARATOR ';') AS subjects";
		} elseif ($column == 'subject_ids') {
			$cols[] = "GROUP_CONCAT(DISTINCT IF(prefix='subject',tag_id,NULL) ORDER BY tag_id SEPARATOR ',') AS subject_ids";
		} elseif (is_null($data['Default'])) {
			$cols[] = "NULL as $column";
		} else {
			$cols[] = $db->Quote($data['Default'])." as $column";
		}
	}

	//ignore, so just add to images without tags. or maybe could add ON DUPLICATE UPDATE subjects = ...
	$sql = "INSERT IGNORE INTO sphinx_tags_tmp
		SELECT ".implode(", ",$cols)."
		FROM gridimage_search gi
 		 INNER JOIN sph_delta_ids USING (gridimage_id)
		 INNER join category_mapping c USING (imageclass)
		 INNER join tag on (prefix='subject' AND tag = subject)
		WHERE gi.tags = '' AND gi.imageclass!=''
		AND __between__
		GROUP BY gridimage_id ORDER BY NULL";


        for($q=$minmax['min'];$q<$minmax['max'];$q+=100000) {
		$between = "gridimage_id BETWEEN ".($q)." AND ".($q+99999);
                $sqls[] = str_replace('__between__',$between, $sql);
        }

	foreach ($sqls as $sql) {
		fwrite(STDERR,date('H:i:s ')." $sql\n\n");
		$db->Execute($sql);
	}

	#####################################################

	$db->Execute("REPLACE INTO sphinx_tags SELECT * FROM sphinx_tags_tmp");
}

#####################################################

if (isset($status['sphinx_terms'])) {

	$db->Execute("DROP TABLE IF EXISTS sphinx_terms_tmp");

	#####################################################
	## note while gridimage_group is not CURRENTLY being updated, and term/wiki are effectively static,
	##   ... gridimage_snippet is being updated
	##  a TODO would be to add 'insert into sph_delta_ids select gridimage_id from gridiamge_snippet where > $crit
	## and even gridimage_group as well, once that starts updating! (as could create new cluster term, without updating the image itself!)
	#####################################################

	$sqls = array();

	fwrite(STDERR,date('H:i:s ')."Building Terms...\n");

	$sql = "
			SELECT m.gridimage_id,
				GROUP_CONCAT(DISTINCT label ORDER BY CRC32(label) SEPARATOR ';') AS groups,
				GROUP_CONCAT(DISTINCT CRC32(label) ORDER BY CRC32(label) SEPARATOR ',') AS group_ids,
				GROUP_CONCAT(DISTINCT term ORDER BY CRC32(term) SEPARATOR ';') AS terms,
				GROUP_CONCAT(DISTINCT CRC32(term) ORDER BY CRC32(term) SEPARATOR ',') AS term_ids,
				GROUP_CONCAT(DISTINCT s.title ORDER BY snippet_id SEPARATOR ';') AS snippets,
				GROUP_CONCAT(DISTINCT snippet_id ORDER BY snippet_id SEPARATOR ',') AS snippet_ids,
				GROUP_CONCAT(DISTINCT REPLACE(w.tag,'_',' ') ORDER BY CRC32(w.tag) SEPARATOR ';') AS wikis,
				GROUP_CONCAT(DISTINCT CRC32(w.tag) ORDER BY CRC32(w.tag) SEPARATOR ',') AS wiki_ids
			FROM gridimage_search m
			INNER JOIN sph_delta_ids USING (gridimage_id)
				LEFT JOIN gridimage_group g ON (g.gridimage_id = m.gridimage_id AND label NOT LIKE '%other%')
				LEFT JOIN gridimage_term t ON (t.gridimage_id = m.gridimage_id)
				LEFT JOIN gridimage_snippet gs ON (gs.gridimage_id = m.gridimage_id) LEFT JOIN snippet s USING (snippet_id)
				LEFT JOIN gridimage_wiki w ON (w.gridimage_id = m.gridimage_id)
			WHERE m.__between__
			GROUP BY gridimage_id ORDER BY NULL";

        for($q=$minmax['min'];$q<$minmax['max'];$q+=100000) {
		$between = "gridimage_id BETWEEN ".($q)." AND ".($q+99999);
                $sqls[] = (count($sqls)?"INSERT INTO sphinx_terms_tmp ":"CREATE TABLE sphinx_terms_tmp (gridimage_id INT UNSIGNED) ENGINE=MyISAM").
                         str_replace('__between__',$between, $sql);
        }

	$sqls[] = "DELETE FROM sphinx_terms_tmp WHERE groups IS NULL AND terms IS NULL AND snippets IS NULL AND wikis IS NULL";

	$sqls[] = "ALTER TABLE sphinx_terms_tmp ADD PRIMARY KEY(gridimage_id)";

	foreach ($sqls as $sql) {
		fwrite(STDERR,date('H:i:s ')." $sql\n\n");
		$db->Execute($sql);
	}

	#####################################################

	$db->Execute("REPLACE INTO sphinx_terms SELECT * FROM sphinx_terms_tmp");
}

#####################################################

fwrite(STDERR,date('H:i:s ')."DONE!\n");


