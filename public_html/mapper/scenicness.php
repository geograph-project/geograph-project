<?php
/**
 * $Project: GeoGraph $
 * $Id: faq.php 15 2005-02-16 12:23:35Z lordelph $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2007 Barry Hunter (geo@barryhunter.co.uk)
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

if (empty($CONF['forums'])) {
	$smarty = new GeographPage;
        $smarty->display('static_404.tpl');
        exit;
}

init_session();

$smarty = new GeographPage;


	$column = 'avg';
        $columns = array('max','min','avg');

        if (!empty($_GET['column']) && in_array($_GET['column'],$columns)) {
                $column = $_GET['column'];
        }

$template = 'mapper_scenicness.tpl';
$cacheid = $column;

if (!empty($_GET['photos']))
	$template = 'mapper_scenicness_photos.tpl';




if (!$smarty->is_cached($template, $cacheid))
{
	$smarty->assign('google_maps_api3_key',$CONF['google_maps_api3_key']);
	$smarty->assign_by_ref('columns', $columns);
	$smarty->assign_by_ref('column', $column);
}

$smarty->display($template, $cacheid);

