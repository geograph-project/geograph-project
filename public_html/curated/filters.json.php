<?php
/**
 * $Project: GeoGraph $
 * $Id: submissions.php 6417 2010-03-04 22:14:53Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2007 Barry Hunter (geo@barryhunter.co.uk)
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

customExpiresHeader(3600*24*5);

$data = array();

if ($_GET['model'] == 'pe') {
	$db = GeographDatabaseConnection(true);
	$data['region'] = $db->getCol("select distinct region from sphinx_placenames");
	$data['country'] = $db->getCol("select distinct country from sphinx_placenames");
	$data['myriad'] = $db->getCol("select prefix from gridprefix where landcount>0");
	$data['largest'] = array(8192,3000,1600,1024,800,641,640,480,320);
}
//too many to list!
$data['user_id'] = true;
$data['gridref'] = true;
$data['taken'] = true;

outputJSON($data);

