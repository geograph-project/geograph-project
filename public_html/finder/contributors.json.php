<?php
/**
 * $Project: GeoGraph $
 * $Id: contributors.php 8217 2015-01-05 16:33:42Z geograph $
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

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$callback = isset($_GET['callback']) ? $_GET['callback'] : 'serveCallback';

if (empty($q)) {
    header('Content-Type: application/javascript');
    echo $callback . '({"error": "Query parameter is missing."})';
    exit;
}

$sphinx = new sphinxwrapper($q);
$sphinx->pageSize = 15;
$sphinx->processQuery();
$ids = $sphinx->returnIds(1, 'user');

$results = array();
if (!empty($ids)) {
    $where = "user_id IN(" . join(",", $ids) . ")";
    $db = GeographDatabaseConnection(true);
    $limit = 25;

    $prev_fetch_mode = $ADODB_FETCH_MODE;
    $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
    $rows = $db->getAssoc("
        SELECT user.user_id, nickname, realname, images
        FROM user
        LEFT JOIN user_stat USING (user_id)
        WHERE $where
        LIMIT $limit");
    $ADODB_FETCH_MODE = $prev_fetch_mode;

    foreach ($ids as $id) {
        if (isset($rows[$id])) {
            $results[] = array(
                'user_id' => $id,
                'nickname' => $rows[$id]['nickname'],
                'realname' => $rows[$id]['realname'],
                'images' => $rows[$id]['images'],
            );
        }
    }
}

$output = array(
    'items' => $results,
    'query_info' => $sphinx->query_info,
    'copyright' => 'Geograph Project & contributors',
);

header('Content-Type: application/javascript');
echo $callback . '(' . json_encode($output) . ');';
