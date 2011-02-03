<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2011 Barry Hunter (geo@barryhunter.co.uk)
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


header('Content-type: application/json');


customExpiresHeader(3600);


$db = GeographDatabaseConnection(true);

$sql = array();


$sql['columns'] = 'tag.tag,tag.prefix';

if (!empty($_GET['q'])) {
        //TODO invoke sphinx...

        $sql['tables'] = array('tag');

        $sql['wheres'] = array("`tag` LIKE ".$db->Quote($_GET['q'].'%'));

        $sql['order'] = '`tag`';
                
        $sql['limit'] = 100;

        //todo only show tags actully in use? (and/or sort by popularity)
} else {
	die("todo");
	
        $sql['tables'] = array();
        $sql['tables']['t'] = "`tag`";
	$sql['tables']['gt'] = "INNER JOIN `gridimage_tag` USING (tag_id)";
       
        $sql['wheres'] = array('status = 1');


        if (!empty($_GET['user_id'])) {
                $sql['wheres'][] = "t user_id = ".intval($_GET['user_id']);
        }
        if (!empty($_GET['tag'])) {
                if ($row2 = getRow("SELECT tag_id FROM `tag` WHERE `tag` = ".dbQuote($_GET['tag']))) {
                        $sql['tables'][] = "INNER JOIN `link2tag` l2 USING (link_id)";
                        $sql['wheres'][] = "l2.tag_id = ".$row2['tag_id'];
                }
        }


        $sql['group'] = 'tag.tag_id';
}

$query = "SELECT {$sql['columns']}";

if (isset($sql['tables']) && count($sql['tables'])) {
	$query .= " FROM ".join(' ',$sql['tables']);
}
if (isset($sql['wheres']) && count($sql['wheres'])) {
	$query .= " WHERE ".join(' AND ',$sql['wheres']);
}
if (isset($sql['order'])) {
	$query .= " ORDER BY {$sql['order']}";
}
if (isset($sql['limit'])) {
	$query .= " LIMIT {$sql['limit']}";
}

$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
$data = $db->getAll($query);

if (!empty($_GET['callback'])) {
        $callback = preg_replace('/[^\w\.-]+/','',$_GET['callback']);
        echo "{$callback}(";
}

require_once '3rdparty/JSON.php';
$json = new Services_JSON();
print $json->encode($data);

if (!empty($_GET['callback'])) {
        echo ");";
}



