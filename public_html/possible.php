<?php
/**
 * $Project: GeoGraph $
 * $Id: faq.php 15 2005-02-16 12:23:35Z lordelph $
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

pageMustBeHTTPS();

$smarty = new GeographPage;
$smarty->caching = 0; //explicitly disable caching!

$db = GeographDatabaseConnection(true);
$items = $db->getAll("SELECT * FROM possible WHERE enabled = 1 and LENGTH(content)>10");

foreach ($items as $idx => &$item) {
	$item['title'] = str_replace(array('[',']'),array('<b>','</b>'), htmlentities2($item['title']));

	$text = str_replace("\r",'',htmlentities2($item['content']));

	$text = preg_replace('/^# (.+)$/m', '<ol><li>$1</li></ol>', $text);
	$text = preg_replace('/<\/ol>\n<ol>/', "\n", $text);

	$text = preg_replace('/^\* (.+)$/m', '<ul><li>$1</li></ul>', $text);
	$text = preg_replace('/<\/ul>\n<ul>/', "\n", $text);

	$text = preg_replace('/\*\*(.+?)\*\*/', "<b>$1</b>", $text);
	$text = preg_replace('/\*(.+?)\*/', "<i>$1</i>", $text);

$text = preg_replace(
    '/\[([^\]]+)\]\((https?:\/\/[^\s\)]+)\)/i',
    '<a href="$2" target=_blank>$1</a>',
    $text
);

	$text = nl2br($text);
	$text = str_replace('</li><br />','</li>',$text);

	$item['content'] = $text;
}
unset($item);

$smarty->assign_by_ref('items',$items);

$smarty->display('possible.tpl');
