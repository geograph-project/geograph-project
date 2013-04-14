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

$template='switch_tagger.tpl';

$smarty = new GeographPage;

//you must be logged in to submit images
$USER->mustHavePerm("basic");

if (isset($_GET['new'])) {
	$USER->setPreference('tags.tagger_new',intval($_GET['new']),true);
}

$smarty->assign('new',$USER->getPreference('tags.tagger_new','0',true));

$smarty->display($template);

