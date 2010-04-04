<?php
/**
 * $Project: GeoGraph $
 * $Id: staticpage.php 6514 2010-03-26 21:49:55Z barry $
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

if (isset($_SERVER['REDIRECT_SCRIPT_URL']) && preg_match('/120_(ie|ff).gif$/',$_SERVER['REDIRECT_SCRIPT_URL'])) {
	//you can just go away - Gmaps seem to lookup these urls via GGeoXML for somereason...
	header('Content-Length: 0');
	exit;
}

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;

if (!empty($_SERVER['REQUEST_URI'])) {
	$smarty->assign("url",$_SERVER['REQUEST_URI']);
}

$template = "notreadyyet.tpl";

$smarty->display($template);

