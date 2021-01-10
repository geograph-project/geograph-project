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


require_once('geograph/global.inc.php');

init_session();

$smarty = new GeographPage;

pageMustBeHTTP();

$smarty->assign('page_title','Image Randomizer');
$smarty->display('_std_begin.tpl',md5($_SERVER['PHP_SELF']));

print "<h2><a href=\"/explore/\">Explore</a> :: Image Randomizer</h2>";

print "<p>Can also <a href=\"/search.php?orderby=random&displayclass=black&do=1\">get random image slideshow</a> or just <a href=\"/stuff/browse-random.php\">jump to random square</a></p>";


if ($_SERVER['HTTP_HOST'] == 'staging.geograph.org.uk') {
	print "<p>NOTE: This is a remote application, and is picking images from the live site, despite this page being on staging site</p>";
}

print "<iframe src=\"http://ww2.scenic-tours.co.uk/serve.php?t=WoNlVJvoblNlJL5405o44hahObuu4ZaNVwV\" width=850 height=850></iframe>";

$smarty->display('_std_end.tpl');

