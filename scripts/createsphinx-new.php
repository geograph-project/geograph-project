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
$param=array(
        'rebuild'=>false, //force rebuild tags/terms - false only creates if not exist
        'schema'=>false, //show the schema used to create a new sphinx index.
	'view'=>false,  //create a actual database view!
);


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

if (!empty($param['rebuild'])) {
        $sqls = array();

        fwrite(STDERR,date('H:i:s ')."Dropping tables\n");
	$sqls[] = "DROP TABLE IF EXISTS sphinx_tags";
	$sqls[] = "DROP TABLE IF EXISTS sphinx_terms";

        foreach ($sqls as $sql) {
                fwrite(STDERR,date('H:i:s ')." $sql\n\n");
                $db->Execute($sql);
        }
}

#####################################################

if (!$db->getOne("SHOW TABLES LIKE 'sphinx_tags'")) {
	$sqls = array();

	fwrite(STDERR,date('H:i:s ')."Building Tags...\n");

	$sqls[] = "DROP TABLE IF EXISTS sphinx_tags";
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
			WHERE gt.status = 2 and t.status = 1 AND __between__
			GROUP BY gridimage_id";

	$count = $db->getOne("SELECT MAX(gridimage_id) FROM gridimage_search");

	for($q=0;$q<$count;$q+=100000) {
		$between = "gridimage_id BETWEEN ".($q+1)." AND ".($q+100000);
		$sqls[] = ($q?"INSERT INTO sphinx_tags ":"CREATE TABLE sphinx_tags (gridimage_id INT UNSIGNED PRIMARY KEY)").
			str_replace('__between__',$between, $sql);
	}

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
	$sql = "INSERT IGNORE INTO sphinx_tags
		SELECT ".implode(", ",$cols)."
		FROM gridimage_search gi
		 INNER join category_mapping c USING (imageclass)
		 INNER join tag on (prefix='subject' AND tag = subject)
		WHERE gi.tags = '' AND gi.imageclass!=''
		AND __between__
		GROUP BY gridimage_id";

	        $count = $db->getOne("SELECT MAX(gridimage_id) FROM gridimage_search where imageclass != '' and tags = ''");  //in theory long term will be few without so can exclude them

        for($q=0;$q<$count;$q+=100000) {
                $between = "gridimage_id BETWEEN ".($q+1)." AND ".($q+100000);
                $sqls[] = str_replace('__between__',$between, $sql);
        }

	foreach ($sqls as $sql) {
		fwrite(STDERR,date('H:i:s ')." $sql\n\n");
		$db->Execute($sql);
	}
}

#####################################################

if (!$db->getOne("SHOW TABLES LIKE 'sphinx_terms'")) {
	$sqls = array();

	fwrite(STDERR,date('H:i:s ')."Building Terms...\n");

	$sqls[] = "DROP TABLE IF EXISTS sphinx_terms";
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
				LEFT JOIN gridimage_group g ON (g.gridimage_id = m.gridimage_id AND label NOT LIKE '%other%')
				LEFT JOIN gridimage_term t ON (t.gridimage_id = m.gridimage_id)
				LEFT JOIN gridimage_snippet gs ON (gs.gridimage_id = m.gridimage_id) LEFT JOIN snippet s USING (snippet_id)
				LEFT JOIN gridimage_wiki w ON (w.gridimage_id = m.gridimage_id)
			WHERE m.__between__
			GROUP BY gridimage_id";

        $count = $db->getOne("SELECT MAX(gridimage_id) FROM gridimage_search");

        for($q=0;$q<$count;$q+=50000) {
                $between = "gridimage_id BETWEEN ".($q+1)." AND ".($q+50000);
                $sqls[] = ($q?"INSERT INTO sphinx_terms ":"CREATE TABLE sphinx_terms (gridimage_id INT UNSIGNED PRIMARY KEY)").
                         str_replace('__between__',$between, $sql);
        }

	$sqls[] = "DELETE FROM sphinx_terms WHERE groups IS NULL AND terms IS NULL AND snippets IS NULL AND wikis IS NULL";

	foreach ($sqls as $sql) {
		fwrite(STDERR,date('H:i:s ')." $sql\n\n");
		$db->Execute($sql);
	}
}

#####################################################

if (!$db->getOne("SHOW TABLES LIKE 'sphinx_placenames'")) {

	fwrite(STDERR,date('H:i:s ')."Building Placename Table...\n");

	$sqls = array();
	$sqls[] = "DROP TABLE IF EXISTS sphinx_placenames";

	$sqls[] = "
	CREATE TABLE sphinx_placenames (placename_id INT UNSIGNED PRIMARY KEY)
		select distinct
			os_gaz.seq+1000000 as placename_id,
			IF(has_dup,CONCAT(def_nam,'/',km_ref),def_nam) as Place,
			IF(full_county!='',full_county,'Unknown') as County,
			IF(loc_country.name!='',loc_country.name,'Unknown') as Country,
			has_dup, km_ref, 1 as reference_index
		from os_gaz
			left join os_gaz_county on (os_gaz.co_code = os_gaz_county.co_code)
			left join loc_country on (country = loc_country.code)
	UNION
		select distinct
			id as placename_id,
			full_name as Place,
			IF(loc_adm1.name!='',loc_adm1.name,'Unknown') as County,
			IF(loc_country.name!='',loc_country.name,'Unknown') as Country,
			has_dup, CONCAT(',',e,',',n) AS km_ref, 2 as reference_index
		from loc_placenames
			left join loc_adm1 on (loc_placenames.adm1 = loc_adm1.adm1 and loc_adm1.country = loc_placenames.country)
			left join loc_country on (loc_placenames.country = loc_country.code)
		where
			loc_placenames.reference_index = 2
		";
		//NOTE for loc_placenames, the km_ref is added to Place in query below, because dont have km_ref, yet!

	foreach ($sqls as $sql) {
		fwrite(STDERR,date('H:i:s ')." $sql\n\n");
		$db->Execute($sql);
	}

	############

	fwrite(STDERR,date('H:i:s ')."Adding Grid References...\n");

	$sql = "SELECT placename_id,has_dup,km_ref,reference_index FROM sphinx_placenames WHERE km_ref LIKE ',%'";

	$recordSet = $db->Execute($sql);

	require_once('geograph/conversions.class.php');
	$conv = new Conversions;

	while (!$recordSet->EOF)
	{
		$r =& $recordSet->fields;

		list($d,$e,$n) = explode(',',$r['km_ref']);
		list ($gridref,) = $conv->national_to_gridref($e,$n,4,$r['reference_index']);

		if (strlen($gridref) != 5) {
			print "FAILED[{$r['placename_id']}] => ($d,$e,$n)($gridref,)\n";
		} else {

			if ($r['has_dup']) {
				$sql = "UPDATE sphinx_placenames SET km_ref = '$gridref',Place = CONCAT(Place,'/','$gridref') WHERE placename_id = {$r['placename_id']}";
			} else {
				$sql = "UPDATE sphinx_placenames SET km_ref = '$gridref' WHERE placename_id = {$r['placename_id']}";
			}
			$db->Execute($sql);
		}

		$recordSet->MoveNext();
	}

	$recordSet->Close();

	#############

	$sqls = array();

	$sqls[] = "create temporary table sphinx_placename_stat (primary key(placename_id)) ".
			"select placename_id,count(distinct gridsquare_id) as squares,sum(imagecount) as images, sum(has_geographs) as has_geographs from gridsquare group by placename_id";
	$sqls[] = "alter table sphinx_placenames add squares mediumint unsigned default null, add images int unsigned default null, add has_geographs int unsigned default null, add index(Place)";
	$sqls[] = "update sphinx_placenames p inner join sphinx_placename_stat s using (placename_id) set p.squares = s.squares, p.images = s.images, p.has_geographs = s.has_geographs";

        foreach ($sqls as $sql) {
                fwrite(STDERR,date('H:i:s ')." $sql\n\n");
                $db->Execute($sql);
        }
	#############
}

#####################################################

if (empty($param['schema']) && empty($param['view'])) {
        fwrite(STDERR,date('H:i:s ')."ALL DONE\n");
	exit();
}

$sql = "
SELECT 
	gi.gridimage_id AS id, 
	UNIX_TIMESTAMP(gi.submitted) AS submitted, 
	TO_DAYS(REPLACE(gi.imagetaken,'-00','-01')) AS takendays,
	REPLACE(gi.imagetaken,'-','') AS takenday, 
	REPLACE(substring(gi.imagetaken,1,7),'-','') AS takenmonth, 
	substring(gi.imagetaken,1,4) AS takenyear,
        CONCAT(substring(gi.imagetaken,1,3),'tt') AS decade, 
      coalesce(MONTHNAME(gi.imagetaken),'Unknown') AS monthname, 
        gi.user_id, CONCAT('user',gi.user_id) as user, 
	gi.realname, 
	gi.title, 
	gi.comment,
	SUBSTRING(gi.grid_reference,1,LENGTH(gi.grid_reference)-4) AS myriad,
	CONCAT(SUBSTRING(gi.grid_reference,1,LENGTH(gi.grid_reference)-3),SUBSTRING(gi.grid_reference,LENGTH(gi.grid_reference)-1,1)) AS hectad,
	gi.imageclass, 
	gi.grid_reference,
      g2.natgrlen,
        (gi.reference_index * 1000000000 + IF(g2.natgrlen+0 <= 3,(g2.nateastings DIV 100) * 100000 + (g2.natnorthings DIV 100),0)) AS scenti,
	RADIANS(wgs84_lat) AS wgs84_lat,
	RADIANS(wgs84_long) AS wgs84_long,
      gi.x, gi.y, 
      CONCAT('ftf',gi.ftf) AS ftf,
	CONCAT('_SEP_ ',REPLACE(contexts,';',' _SEP_ '),' _SEP_') AS contexts, context_ids,
	CONCAT('_SEP_ ',REPLACE(subjects,';',' _SEP_ '),' _SEP_') AS subjects, subject_ids,
	CONCAT('_SEP_ ',coalesce(REPLACE(types,';',' _SEP_ '), IF(gi.moderation_status='accepted','Supplemental','Geograph')) ,' _SEP_') AS types,
		    coalesce(type_ids, IF(gi.moderation_status='accepted',195749,172412) ) as type_ids,
	CONCAT('_SEP_ ',REPLACE(t.tags,  ';',' _SEP_ '),' _SEP_') AS tags,     t.tag_ids,
	CONCAT('_SEP_ ',REPLACE(groups,  ';',' _SEP_ '),' _SEP_') AS groups,   group_ids,
	CONCAT('_SEP_ ',REPLACE(terms,   ';',' _SEP_ '),' _SEP_') AS terms,    term_ids,
	CONCAT('_SEP_ ',REPLACE(snippets,';',' _SEP_ '),' _SEP_') AS snippets, snippet_ids,
	CONCAT('_SEP_ ',REPLACE(wikis,   ';',' _SEP_ '),' _SEP_') AS wikis,    wiki_ids,
	IF(gi.moderation_status='accepted','supplemental',gi.moderation_status) AS status,
        RADIANS(vlat) AS vlat,
        RADIANS(vlong) AS vlong,
        g2.viewpoint_grlen as vgrlen,
	(gi.reference_index * 1000000 + (viewpoint_northings DIV 1000) * 1000 + viewpoint_eastings DIV 1000) AS viewsquare,
        IF(natnorthings>0 AND viewpoint_eastings>0,
                pow(2,floor(log2(SQRT(
                        pow(cast(nateastings as signed)-cast(viewpoint_eastings as signed),2)
                        +pow(cast(natnorthings as signed)-cast(viewpoint_northings as signed),2)
                ))))
                ,'Unknown') AS distance,
	IF(view_direction=-1,'Unknown',view_direction) AS direction,
	IF(ABS(cast(width as signed)-cast(height as signed)) <= 60,'square',IF(width>height,IF(width>(height*2),'panorama','landscape'),'portrait')) AS format,
	gs.placename_id, 
	sequence, 
        coalesce(score,130) AS score, 
        coalesce(baysian*100,300) AS baysian, 
        coalesce(Place,'Unknown') as place,
        coalesce(County,'Unknown') as county,
        coalesce(Country,'Unknown') as country,
        SUBSTRING(MD5(CONCAT(gi.gridimage_id,gi.user_id,'{$CONF['photo_hashing_secret']}')),1,8) AS hash, 
      concat( if(greatest(original_width,original_height) >= 3000,'3000 ',''), if(greatest(original_width,original_height) >= 1600,'1600 ',''), if(greatest(original_width,original_height) >= 1024,'1024 ',''), if(greatest(original_width,original_height) >= 800,'800 ',''), if(greatest(original_width,original_height) >= 640,'641 ','') ) AS larger, 
      greatest(original_width,original_height) as original, 
      width, height, 
      coalesce(l.name,'Unknown') AS landcover, 
      gs.percent_land AS land 
FROM gridimage_search gi
	INNER JOIN gridimage g2 USING (gridimage_id)
	INNER JOIN gridimage_size USING (gridimage_id)
	INNER JOIN gridsquare gs USING (gridsquare_id)
	LEFT JOIN sphinx_placenames p ON (p.placename_id = gs.placename_id)
	LEFT JOIN sphinx_tags t ON  (gi.gridimage_id = t.gridimage_id)
	LEFT JOIN sphinx_terms c ON (gi.gridimage_id = c.gridimage_id)
      LEFT JOIN landcover l USING (landcover_id)
";
//BE WEARY OF ADDING GROUP BY TO THIS QUERY, AS THE SCHEMA BELOW USING LIMIT 1 WILL STRUGGLE.


####################################################

if (!empty($param['view'])) {
	if ($param['view'] > 1) {
		$db->Execute("DROP VIEW IF EXISTS sphinx_view");
	}

	fwrite(STDERR,date('H:i:s ')."Creating view...\n");

	$sql = "CREATE VIEW sphinx_view AS $sql";

	$db->Execute($sql);

	exit;
}

####################################################

fwrite(STDERR,date('H:i:s ')."Getting Schema...\n");

$result = $db->Execute("$sql LIMIT 1") or die($db->ErrorMsg());

print "######################################\n";

print "sql_query = ".str_replace("\n","\\\n",trim(str_replace("\r",'',$sql)))."\n\n";

print "######################################\n";
print "# Attributes...\n\n";

$fields = $result->fieldCount();
for ($i=1; $i < $fields; $i++) {
	$r = $result->FetchField($i);
	$name  = $r->name;
		//the numberic is mysqli - todo, maybe swithc to MetaType?
	switch ($r->type) {
		case 'string': case 253:
		case 'blob':   case 252:
			if ($name == 'comment') {
				//leave a simple field
			} elseif (preg_match('/_ids$/',$name)) {
				print "sql_attr_multi		= uint $name from field\n";
			} else {
				print "sql_field_string	= $name\n";
			}
			break;
		case 'int':   case 3:
			if ($name == 'submitted') {
				print "sql_field_timestamp	= $name\n";
			} else {
				//todo - set bits based on $len
				print "sql_attr_uint		= $name\n";
			}
			break;
		case 'real':  case 246:
			print "sql_attr_float		= $name\n";
			break;
	}
}


fwrite(STDERR,date('H:i:s ')."DONE!\n");

##################################################################################################################
exit;
##################################################################################################################



//TODO the below is UNTESTED ! (but should be roughly what is required!)

fwrite(STDERR,date('H:i:s ')."Running materialized view...\n");

$sql = "CREATE TABLE sphinx_view $sql";

$db->Execute($sql);

$sql = "SELECT gridimage_id,tags,groups,terms,snippets,wikis FROM sphinx_view";

fwrite(STDERR,date('H:i:s ')."Running main query...\n");

$recordSet = Execute($sql) or die($db->ErrorMsg());

fwrite(STDERR,date('H:i:s ')."Starting building values...\n");

$crcs = array();

while (!$recordSet->EOF) {
	$row =& $recordSet->fields;

	$values = array();
	$value_ids = array();
	foreach (array('tags','groups','terms','snippets','wikis') as $key) {
		if (!empty($row[$key])) {
			$bits = explode(';',$row[$key]);
			foreach ($bits as $bit)
				if (!preg_match('/^(top|bucket|subject):/',$bit)) {
					$kstr = strtolower($bit);
					$values[$kstr] = $bit;
					if (empty($crcs[$kstr]))
						$crcs[$kstr] = sprintf("%u", crc32($bit));
					$value_ids[$kstr] = $crcs[$kstr];
				}
		}
	}
	if (!empty($values)) {
		uksort($values,'sort_by_ids');
		asort($value_ids); //dont need to sort $value_ids becauase sphinx will do it anyway.

		$sql = "UPDATE ... SET value_ids = '".implode(',',$value_ids)."',values = ".$db->Quote(implode(';',$values))." WHERE gridimage_id = {$row['gridimage_id']}";
		$db->Execute($sql);
	}

	if ($c%1000 == 0) {
		fwrite(STDERR,date('H:i:s ')."Written $c... ".memory_get_usage()."\n");
	//	usleep(500);
	}
	$c++;
	$recordSet->MoveNext();
}
$recordSet->Close();

$sql = "SELECT * FROM sphinx_view";

print "######################################\n";

print "sql_query = ".str_replace("\n","\\\n",str_replace("\r",'',$sql))."\n\n";

fwrite(STDERR,date('H:i:s ')."DONE!\n");


