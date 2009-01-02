<?php
/**
 * $Project: GeoGraph $
 * $Id: editimage.php 5107 2008-12-22 20:02:01Z barry $
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

//you must be logged in to request changes
$USER->mustHavePerm("basic");


$template='cards.tpl';
$cacheid='';

$r = empty($_GET['r'])?5:intval($_GET['r']);
$c = empty($_GET['c'])?2:intval($_GET['c']);
$v = empty($_GET['v'])?1:intval($_GET['v']);

$smarty->assign('r',$r);
$smarty->assign('c',$c);
$smarty->assign('v',$v);

$smarty->display($template,$cacheid);

?>