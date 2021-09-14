<?php
/**
 * $Project: GeoGraph $
 * $Id: glossary.php 2960 2007-01-15 14:33:27Z barry $
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


if (isset($_GET['mine'])) {
	require_once('geograph/global.inc.php');
	init_session();

	$smarty = new GeographPage;
	$USER->mustHavePerm("basic");

	$_GET['@user'] = "user".$USER->user_id;
	unset($_GET['mine']);
}


$postfix = "";
foreach ($_GET as $key => $value) {
	if (is_array($value))
		$value = implode(' ',$value);
	if (preg_match('/@(\w+)/',$key,$m)) {
		//attribute filters uese a different format
		$postfix .= "/".urlencode($m[1])."+".urlencode("\"$value\"");
	} else
		$postfix .= "/".urlencode($key)."=".urlencode($value);
}

header("Location: /browser/#!$postfix");


