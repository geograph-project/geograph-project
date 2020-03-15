<?php
/**
 * $Project: GeoGraph $
 * $Id: contributors.php 6407 2010-03-03 20:44:37Z barry $
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

$_GET['lang'] = 'cy'; //always want this! Do this right now, so that template is setup in global.inc!

require_once('geograph/global.inc.php');
init_session();


$smarty = new GeographPage;
$template = 'finder_welsh.tpl';
$cacheid = '';


        if (!$smarty->is_cached($template, $cacheid)) {

                $db = GeographDatabaseConnection(true);
                        $prev_fetch_mode = $ADODB_FETCH_MODE;
                        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

                $list = $db->getAll("SELECT grouping_cy,top,top_cy,description_cy from category_primary order by grouping desc,top_cy");
                $smarty->assign_by_ref('context',$list);
        }


$smarty->display($template,$cacheid);

