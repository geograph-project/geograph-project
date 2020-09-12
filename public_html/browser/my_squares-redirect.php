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


customNoCacheHeader(); //because we performing a user redirect.


$postfix = "";
if (isset($_GET['map'])) {
	$postfix = "/display=map";
}

if (!empty($_GET['days'])) {
	 $postfix = "/days=".(intval($_GET['days'])).$postfix;
	 //$postfix = "/since=".(time()-60*60*24*intval($_GET['days'])).$postfix;
}

if ($USER->registered && $USER->user_id && $USER->realname) {
	$postfix = "/my_square=".intval($USER->user_id)."/realname+-%22".urlencode($USER->realname)."%22".$postfix."/display=group/group=grid_reference/n=4/gorder=last%20desc/sort=submitted_down";
}

header("Location: /browser/#!$postfix");

