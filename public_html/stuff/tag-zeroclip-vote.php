<?php
/**
 * $Project: GeoGraph $
 *
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2024 BArry Hunter (geo@barryhunter.co.uk)
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

$smarty->assign('page_title','Tag Zero-shot CLIP Rating');

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$tagInfo = $db->getRow("select tag_id,tag from tag where prefix = 'subject' and status = 1 and canonical = 0 order by rand() limit 1");
$smarty->assign('notes', "Checking that the images depict a <b>primary subject</b> of [".htmlentities($tagInfo['tag'])."]");

$smarty->assign('tagInfo', $tagInfo);

$smarty->display('stuff_tag-zeroclip-vote.tpl');
