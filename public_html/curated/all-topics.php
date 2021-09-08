<?php
/**
 * $Project: GeoGraph $
 * $Id: view_direction_filler.php 8455 2016-12-15 11:09:03Z PeterFacey $
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


$smarty = new GeographPage;

if (isset($_GET['output']) && $_GET['output'] == 'csv') {
        $template='statistics_table_csv.tpl';
        # let the browser know what's coming
        header("Content-type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"".basename($_SERVER['SCRIPT_NAME'],'.php').".csv\"");
} else {
        $template='statistics_table.tpl';
}

$cacheid=basename(__FILE__).filemtime(__FILE__);

$smarty->caching = 2;

if (isset($_GET['refresh']) && $USER->hasPerm('admin'))
	$smarty->clear_cache($template, $cacheid);

if (!$smarty->is_cached($template, $cacheid))
{
	$db = GeographDatabaseConnection(true);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

/*
	$table = $db->getAll("

select l.`group`,l.label,welsh,
	count(distinct g.gridimage_id) as images,
	count(distinct c.user_id) as curators,
	count(distinct substring(grid_reference,1,3 - reference_index)) as myriads,
	count(distinct concat(substring(grid_reference,1,length(grid_reference)-3),substring(grid_reference,length(grid_reference)-1,1)) ) as hectads,
	count(distinct region) as regions,
	count(distinct decade) as decades
from curated_headword l
	left join curated1 c on (c.label = l.label AND c.active>0 and c.score>7 and c.gridimage_id>0)
	left join gridimage_search g using (gridimage_id)
where (length(l.description) > 10 and notes NOT like 'x%') OR c.gridimage_id > 0
group by label

	");
*/

	if (!empty($_GET['other'])) {
	        $table = $db->getAll("

select `group`,s.label,welsh, images,curators,myriads,hectads,regions,decades
from curated1_stat s
	left join curated_headword l on (l.label = s.label and s.group = 'Geography and Geology')
where l.label is null
order by label

	        ");
		$extra['other']=1;
	} else {
		$join = empty($_GET['all'])?'inner':'left';

	        $table = $db->getAll("

select `group`,l.label,welsh,length(description) as description, images,curators,myriads,hectads,regions,decades
from curated_headword l
	$join join curated1_stat using (label)
order by label

	        ");
		@$extra['all']=intval($_GET['all']);
	}


	if ($template=='statistics_table.tpl')
		foreach ($table as $i => $row) {
			//headword table is all geo+geo group
			$link = "/curated/collecter.php?group=".urlencode($row['group'])."&amp;label=".urlencode($row['label']);
			$table[$i]['label'] = "<a href=\"$link\">".htmlentities2($row['label'])."</a>";

			if ($row['images'] > 0) {
				$link = "/curated/sample.php?label=".urlencode($row['label']);
				$table[$i]['images'] = "<a title=\"{$row['images']}\" href=\"$link\">".($row['images'])."</a>";
			}

			if ($row['regions'] > 1) {
				$link = "/curated/sample.php?label=".urlencode($row['label'])."&region=Group+By";
				$table[$i]['regions'] = "<a title=\"{$row['regions']}\" href=\"$link\">".($row['regions'])."</a>";
			}
		}

	$smarty->assign_by_ref('table', $table);
	$smarty->assign_by_ref('extra', $extra);

	$smarty->assign("h2title",'All Curated Topics (with definition)');
	$smarty->assign("total",count($table));

}

$smarty->display($template,$cacheid);

