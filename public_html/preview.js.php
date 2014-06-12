<?php
/**
 * $Project: GeoGraph $
 * $Id: staticpage.php 6962 2010-12-09 14:56:48Z geograph $
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


if (!empty($_GET['o'])) {
	$option = $_GET['o'];
} else {
	$default = 'none';
	if (!empty($_GET['d'])) {
		$default = $_GET['d'];
	}
	$option = $USER->getPreference('preview.method',$default,true);
}

customExpiresHeader(3600,true,true);

header("Content-type: text/javascript");

customGZipHandlerStart();

switch($option) {
	case 'preview': readfile('js/preview.js',false); break;
	case 'preview2': readfile('js/preview2.js',false); break;
}

//output nothing - a nice noop!
