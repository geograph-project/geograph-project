<?php
/**
 * $Project: GeoGraph $
 * $Id: conversion.php 5502 2009-05-13 14:18:23Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 BArry Hunter (geo@barryhunter.co.uk)
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

$USER->mustHavePerm("moderator");

$smarty->display('_std_begin.tpl');

$db = GeographDatabaseConnection(true);

	$id = $db->getOne("SELECT MAX(gridimage_id) FROM gridimage")-10000;
	$offset = 0;
	$size = 100;
	if (!empty($_GET['o']))
		$offset = intval($_GET['o']);


	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$list = $db->getAll("select gridimage_id,grid_reference,title,user_id,
			if(gi.realname!='',gi.realname,u.realname) as realname,gi.realname as credit_realname, moderation_status
		from gridimage gi inner join user u using (user_id) INNER JOIN gridsquare gs using (gridsquare_id)
		WHERE gridimage_id > $id AND moderator_id = {$USER->user_id} ORDER BY moderated DESC LIMIT $offset,$size");

        print "<h2>Images recently moderated by you</h2>";
	print "<p><a href=\"?\">Refresh listing</a> (don't use browser refresh function)</p>";

	if (count($list)) {
		if (!function_exists('smarty_modifier_truncate'))
			require_once("smarty/libs/plugins/modifier.truncate.php");

                foreach ($list as $idx => $row) {
                        $image = new GridImage();
                        $image->fastInit($row);
                        $thumbh = 160;
                        $thumbw = 213;
?>
                                <div style="float:left;" class="photo33"><div style="height:<? echo $thumbh; ?>px;vertical-align:middle"><a title="<? echo $image->grid_reference; ?> : <? echo htmlentities($image->title) ?> by <? echo htmlentities($image->realname); ?> - click to view full size image" href="/photo/<? echo $image->gridimage_id; ?>"><? echo $image->getThumbnail($thumbw,$thumbh,false,true); ?></a></div>
                                <div class="caption"><div class="minheightprop" style="height:3.5em"></div><a href="/gridref/<? echo $image->grid_reference; ?>"><? echo $image->grid_reference; ?></a> : <a title="view full size image" href="/photo/<? echo $image->gridimage_id; ?>"><? echo htmlentities(smarty_modifier_truncate($image->title,48,"...")); ?></a><div class="minheightclear"></div></div>
                                <div class="statuscaption"><? echo htmlentities($image->moderation_status); ?></a></div>
                                </div>
<?
                }
		print "<br style=\"clear:both\"/>";

		if (count($list) == $size) {
			$offset+=$size;
			print "<div class=interestBox><a href=?o=$offset>More...</a></div>";
		}

	} else {
		print "Nothing to display. Note: this page only looks at last 10,000 images submitted.";
	}


$smarty->display('_std_end.tpl');

