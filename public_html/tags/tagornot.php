<?php
/**
 * $Project: GeoGraph $
 * $Id: places.php 5786 2009-09-12 10:18:04Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Paul Dixon (paul@elphin.com)
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
$template = 'tags_tagornot.tpl';
$cacheid = ''; #md5($_GET['tag']);

$USER->mustHavePerm("basic");

#if (!$smarty->is_cached($template, $cacheid)) {
	$db = GeographDatabaseConnection(true);

	$where = '';
	$andwhere = '';

	if (isset($_GET['prefix'])) {

		$andwhere = " AND prefix = ".$db->Quote($_GET['prefix']);
		$smarty->assign('theprefix', $_GET['prefix']);
	}

	if (!empty($_GET['tag'])) {

		if (strpos($_GET['tag'],':') !== FALSE) {
			list($prefix,$_GET['tag']) = explode(':',$_GET['tag'],2);

			$andwhere = " AND prefix = ".$db->Quote($prefix);
			$smarty->assign('theprefix', $prefix);
			$sphinxq = "tags:\"$prefix {$_GET['tag']}\"";
		} elseif (isset($_GET['prefix'])) {
			$sphinxq = "tags:\"{$_GET['prefix']} {$_GET['tag']}\"";
		} else {
			$sphinxq = "tags:\"{$_GET['tag']}\"";
		}

		$row= $db->getRow("SELECT * FROM tag WHERE status = 1 AND tag=".$db->Quote($_GET['tag']).$andwhere);
				
		if (!empty($row)) {
			$smarty->assign('tag', $row);
		}
	}
#}

$smarty->display($template,$cacheid);
