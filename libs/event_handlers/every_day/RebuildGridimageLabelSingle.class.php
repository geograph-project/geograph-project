<?php
/**
 * $Project: GeoGraph $
 * $Id: RebuildUserStats.class.php 3288 2007-04-20 11:32:27Z barry $
 * 
 * GeoGraph geographic photo archive project
 * http://geograph.sourceforge.net/
 *
 * This file copyright (C) 2008  Barry Hunter (geo@barryhunter.co.uk)
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

//filename of class file should correspond to class name, e.g.  myhandler.class.php
class RebuildGridimageLabelSingle extends EventHandler
{
	function processEvent(&$event)
	{
		$db=&$this->_getDB();


		$sql = "select gridimage_id,model,group_concat(label order by score desc limit 1) as label,max(score) as score, max(seq_id) as max_seq_id
		 from gridimage_label where score > 0.1 and model != 'type' and {where} group by gridimage_id, model";

		$create  = "create table gridimage_label_single (primary key(gridimage_id,model),key(model),label varchar(128) not null) ";
		$insert  = "insert  into gridimage_label_single ";
		$replace = "replace into gridimage_label_single ";

			//information_schema, rather than 'describe' etc, is wont error if none!
		$columns = $db->getOne("select count(*) from information_schema.columns where table_schema = database() and table_name = 'gridimage_label_single'");

		$db->Execute('start transaction');

		if ( $columns == 5 ) { //to make easy to regerate table if add a column

			$max = $db->getOne("SELECT MAX(max_seq_id) FROM gridimage_label_single");

			$sql2 = $replace . str_replace('{where}', "seq_id > $max", $sql);
			$this->Execute($sql2);

		} else {
			if ($columns) //existing table, now outdated
				$this->Execute("DROP TABLE gridimage_label_single");


			//cant partition by seq_id, as risks splitting an image in the middle
			$models = $db->getCol("SELECT model FROM dataset where model!=''");

			foreach($models as $idx => $model) {
				$sql2 = ($idx?$insert:$create) . str_replace('{where}', "model = ".$db->Quote($model), $sql);
				$this->Execute($sql2);
			}
		}
		$db->Execute('commit');


		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
}
