<?php
/**
 * $Project: GeoGraph $
 * $Id: contributors.php 6407 2010-03-03 20:44:37Z barry $
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

require_once('geograph/global.inc.php');
init_session();

if (isset($_GET['mine'])) {
	$_GET['q'] .= " user:user{$USER->user_id} by:{$USER->realname}";
}

if (!empty($_GET['upload_id'])) {

        $gid = crc32($_GET['upload_id'])+4294967296;
        $gid += $USER->user_id * 4294967296;
        $gid = sprintf('%0.0f',$gid);

} elseif (!empty($_REQUEST['gridimage_id'])) {

        $gid = intval($_REQUEST['gridimage_id']);
}

if (!empty($_GET['gr'])) {
	$q=trim($_GET['gr']);

        $square=new GridSquare;
        if (preg_match_all('/\b([a-zA-Z]{1,2}) ?(\d{2,5})(\.\d*|) ?(\d{2,5})(\.*\d*|)\b/',$q,$matches)) {
                $gr = array_pop($matches[0]); //take the last, so that '/near/Grid%20Reference%20in%20C1931/C198310' works!
                $grid_ok=$square->setByFullGridRef($gr,true,true);
		$q = $square->grid_reference;
        }

        //for some unexplainable reason, setByFullGridRef SOMETIMES returns false, and fails to set nateastings - even though allow-zero-percent is set. Fix that...
        if (!$square->nateastings && $square->x && $square->y) {
                require_once('geograph/conversions.class.php');
                $conv = new Conversions;
                list($e,$n,$reference_index) = $conv->internal_to_national($square->x,$square->y);
                $square->nateastings = $e;
                $square->natnorthings = $n;
                $square->reference_index = $reference_index;
                $grid_ok = 1;
        }

        if ($grid_ok) {
		require_once('geograph/conversions.class.php');
                $conv = new Conversions;

                list($lat,$lng) = $conv->national_to_wgs84($square->nateastings,$square->natnorthings,$square->reference_index);

		$lat = deg2rad($lat);
		$lng = deg2rad($lng);
	}

	$sphinx = new sphinxwrapper($q);
	$sphinx->pageSize = $pgsize = 20;

	$pg = (!empty($_GET['page']))?intval(str_replace('/','',$_GET['page'])):0;
	if (empty($pg) || $pg < 1) {$pg = 1;}

	$sphinx->processQuery();

	$sph = GeographSphinxConnection('sphinxql',true);

	$prev_fetch_mode = $ADODB_FETCH_MODE;
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	$where = "match(".$sph->Quote($sphinx->q).")";

	//check what items are already used on the image
	if (!empty($gid)) {
		$db = GeographDatabaseConnection(true);
		$tag_ids = $db->getAssoc("SELECT tag_id as id,tag_id FROM gridimage_tag WHERE status = 2 AND gridimage_id = $gid");
		$snippet_ids = $db->getAssoc("SELECT snippet_id as id,snippet_id FROM gridimage_snippet WHERE gridimage_id = $gid");
	} else {
		$tag_ids = $snippet_ids = array();
	}

	//lookup results
	$results = array();
	$attributes = array('context' ,'subject','tag','snippet');

	foreach ($attributes as $attribute) {
		$rows = $sph->getAll($sql = "
			select id,{$attribute}s,{$attribute}_ids,COUNT(*) as count,GROUPBY() as group,MIN(geodist(wgs84_lat,wgs84_long,$lat,$lng)) as dist
			from sample8
			where $where
			group by {$attribute}_ids
			order by dist asc
			limit {$sphinx->pageSize}");

		foreach ($rows as $idx => $row) {
			$ids = explode(',',$row[$attribute.'_ids']);
			$names = explode('_SEP_',$row[$attribute.'s']);array_shift($names); //the first is always blank!
			$row['label'] = trim($names[array_search($row['group'],$ids)]);

                        if ($attribute == 'tag') {
				//these attributes of the specific image, so should never be copied!
				if (strcasecmp($row['label'],'from:drone') === 0 || preg_match('/^(panorama|hfov|vfov|type|camera):/i',$row['label']))
	                                continue;
				//we know these ids for for very small features, so shouldnt suggest long distance ones!
				if (preg_match('/^milestoneid:/i',$row['label']) && $row['dist'] > 500)
					continue;
			}

			if ($attribute == 'snippet' && !empty($snippet_ids)) {
				$row['used'] = $snippet_ids[$row['group']] ?? false;
			} elseif (!empty($tag_ids)) {
				$row['used'] = $tag_ids[$row['group']] ?? false;
			}

			$dist = round($row['dist']/1000);

			$results[$dist][$attribute][] = $row;
		}
	}

	ksort($results); //only sorting the distance!

	outputJSON($results);

}
