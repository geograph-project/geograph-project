<?php
/**
 * $Project: GeoGraph $
 * $Id: login.php 6836 2010-09-15 20:14:34Z geograph $
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

$USER->mustHavePerm("basic");

$smarty = new GeographPage;


$referring_page="n/a";
if (isset($_REQUEST['referring_page']))
	$referring_page=$_REQUEST['referring_page'];
elseif (isset($_SERVER['HTTP_REFERER']))
	$referring_page=$_SERVER['HTTP_REFERER'];

	
	
$smarty->assign('referring_page',$referring_page);

$smarty->display('ask.tpl');

	
