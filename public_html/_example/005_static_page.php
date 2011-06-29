<?php
/**
 * $Project: GeoGraph $
 * $Id: swarm.php 6077 2009-11-12 22:38:51Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2009 Barry Hunter (geo@barryhunter.co.uk)
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

//disallow public access - remove this on a real page
if (empty($CONF['fetch_on_demand'])) {
	die("this is an example page for developers - not for public access");
}

init_session();
$smarty = new GeographPage;

//setup the template
$template='_example_005_static_page.tpl';	
$cacheid = '';

//do we need to do the heavy lifting, or is there a cached page available?
if (!$smarty->is_cached($template, $cacheid)) {

	//save any data that will be used to render the page
	$smarty->assign("now",date('r'));
} 

//display the template
$smarty->display($template, $cacheid);

