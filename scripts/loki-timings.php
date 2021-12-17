<?php
/**
 * $Project: GeoGraph $
 *
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2021 Barry Hunter (geo@barryhunter.co.uk)
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

$param = array('debug'=>0, 'stream' => 'stdout', 'limit' => 5000, 'date' => '', 'diff'=>'hour','hours'=>0);

chdir(__DIR__);
require "./_loki-wrapper.inc.php";

############################################

$query = '{job="production/geograph", container="nginx"} |= "Googlebot" |= " 200 "';

$generator = getlogs($query, $fp = null, $param['limit'], $start, $end);

$stat = array();
foreach ($generator as $str) {
	if (preg_match('/"GET \/(\w+[\w\.-]+)?.*" 200 .*" (\d[\.\d]*) /',$str,$m)) {
		if (preg_match('/sitemap.*\.gz/',$m[1]))
			$m[1] = "sitemap...gz";
		@$stat[$m[1]][] = $m[2];
	}
}

foreach ($stat as $slug => $data) {
	$sum = array_sum($data);
	$min = min($data);
	$max = max($data);
	$cnt = count($data);

	printf("%40s  %6.3f  %6.3f  %6.3f   (%5d)\n", $slug, $min, $sum/$cnt, $max, $cnt);
}

