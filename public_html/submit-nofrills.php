<?php
/**
 * $Project: GeoGraph $
 * $Id: submitmap.php 5953 2009-10-30 17:08:22Z barry $
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

$USER->mustHavePerm("basic");

$USER->getStats();

if ($USER->stats['images'] < 50) {
	die("Currently this page is only available to contributors with over 50 images. <a href=/help/submission>View other submission methods</a>");
}

if (empty($_GET['letmein'])) {
	die("Currently this page is only available by invite - if would like to try it, let us know!");
	exit;

}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
	customExpiresHeader(3600,false,true);
}

$dirs = array (-1 => '');
$jump = 360/16; $jump2 = 360/32;
for($q = 0; $q< 360; $q+=$jump) {
	$s = ($q%90==0)?strtoupper(heading_string($q)):ucwords(heading_string($q));
	$dirs[$q] = sprintf('%s : %03d deg (%03d > %03d)',
		str_pad($s,16,' '),
		$q,
		($q == 0?$q+360-$jump2:$q-$jump2),
		$q+$jump2);
}
$dirs['00'] = $dirs[0];
$smarty->assign_by_ref('dirs', $dirs);
			
$smarty->display('submit-nofrills.tpl',$cacheid);

?>
