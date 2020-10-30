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


chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


$perpage = 1000;

extract($db->GetRow("select min(topic_id) as `min`,max(topic_id) as `max` from geobb_topics"),
	EXTR_PREFIX_INVALID, 'numeric'); //need to cope with row being either Assoc or Both. Can't assume with be Both. But can assume not Num only.

$start=floor($min / $perpage) * $perpage;
if (!$start) $start =1;

print "list($min,$max)=>$start)\n\n";


$start_date = $db->getOne("SELECT date_sub(now(),interval 1 hour)");//yes know can do this in php avoiding db call, but makes sure its right timezone, as well as right format

for ($from=$start; $start<=$max; $start+=$perpage)
{
	$sql = "select post_id,topic_id,post_text from geobb_posts where topic_id between $start and ".($start+$perpage-1)." and (post_text like '%action=vthread%')";

	$recordSet = $db->Execute($sql);

	$bits = array();
	while (!$recordSet->EOF)
	{
		if (preg_match_all('/'.preg_quote($param['config'],'/').'\/discuss\/[\w\.\?&;=]+topic=(\d+)/',$recordSet->fields['post_text'],$matches)) {
			foreach ($matches[1] as $idx => $topic_id) {
				$bits[] = "($topic_id,{$recordSet->fields['topic_id']},{$recordSet->fields['post_id']},NOW(),NOW())";
			}
		}
		$recordSet->MoveNext();
	}
	$recordSet->Close();

	if (count($bits)) {
		$sql = "INSERT INTO geobb_topics_backlink VALUES ".implode(',',$bits)." ON DUPLICATE KEY UPDATE `updated`=NOW()";
		print "Inserting ".count($bits)." Rows...";
		$db->Execute($sql);
	} else {
		print "no images between $start and ".($start+$perpage-1)."\n";
	}

}

##perge old links! the ON DUPLICATE KEY UPDATE makes sure still present links are updated!
$sql = "DELETE FROM geobb_topics_backlink WHERE updated < ".$db->Quote($start_date);
print "$sql\n";
$db->Execute($sql);


