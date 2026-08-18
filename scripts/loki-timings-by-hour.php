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

$param = array('debug'=>0, 'stream' => 'stdout', 'limit' => 5000, 'date' => '', 'extra'=>'', 'extra2'=> '+1 day', 'hours'=>0, 'bot'=>1, 'all'=>0, 'save'=>0, 'auto'=>false);

chdir(__DIR__);
require "./_loki-wrapper.inc.php";

if (!empty($param['save']))
	$db = GeographDatabaseConnection(false);


############################################

//TODO!
//should use get_base_query, and we now know how to process the duration inside loki, eg with a 'sum() by' query!

$query = '{job="production/geograph", container="nginx", stream="stdout"} |= "Googlebot" |= " 200 " |= " /photo"';
$slug = "photo"; //for the log table!
$agent = "Googlebot"; //for the log table!


if (!empty($param['auto'])) {

	$self = basename($argv[0]);
        foreach (range(-15,-1) as $offset) { //loki only keeps 16 days
               $d = date('Y-m-d',strtotime($offset.' day'));

		$c = $db->getOne("SELECT id FROM timings_by_hour WHERE hour = '$d 00:00:00' AND agent='$agent'");
		if ($c) continue;

		$cmd = "php $self --date=$d --limit={$param['limit']} --all=1 --save --config={$param['config']}";

		print "$cmd\n";
		if ($param['auto'] > 1)
			passthru($cmd);
	}
	exit;
}

############################################

//function get_timing_groups($query, $grouper = '', $unwrap_field = 'timing', $period = '1h', $fp = null, $start = null, $end = null, $as_array = false) {

$generator = get_timing_groups($query, '', 'timing', '1h', $fp = null, $start, $end);

foreach ($generator as $str) {
	list($group,$time,$value) = $str;

	$hour = date('Y-m-d H:i:s', $time);

	if (!empty($param['date']) && strpos($hour, $param['date']) !== 0)
		continue; //there might ba a request on mightnight, extra2 should perhaps be 23:59:59 not +1 day


	print "$hour($group) = $value\n";

	if (!empty($param['save'])) {
		$updates = array();
                $updates['agent'] = $agent;
		$updates['hour'] = $hour;
		$updates['slug'] = $slug;
                $updates['value'] = $value;
                $rows[] = $updates;
	}
}


############################################

if (!empty($rows)) {
        $str = "INSERT INTO timings_by_hour (`".implode("`,`",array_keys($updates))."`) VALUES "; $sep = "\n";
        foreach ($rows as $row) {
                $str .= $sep.'('.implode(',',array_map('myquote',$row)).')';
                $sep = ",\n";
	}
	$db->Execute($str);
	$affected = $db->Affected_Rows();
	print "$affected saved\n";
}


#######################

function myquote($in) {
        if (is_numeric($in))
                return $in;
        global $db;
        return $db->Quote($in);
}


