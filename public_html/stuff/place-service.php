<?php
/**
 * $Project: GeoGraph $
 * $Id: conversion.php 2960 2007-01-15 14:33:27Z barry $
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

header("Content-Type: text/plain");



$q = isset($_GET['query'])?$_GET['query']:'';

$q = preg_replace('/ OR /',' | ',$q);

$q = preg_replace('/[^\w~\|-]+/',' ',trim(strtolower($q)));




if (empty($q)) {
	die('no query');
}

preg_match('/.*?(\w)/',$q,$m);
$q1 = $m[1];

$q2 = str_replace('~','_',$q);
$q2 = str_replace('|','.',$q2);

$encoding = getEncoding();
if ($encoding) {
	header ('Content-Encoding: '.$encoding);
}
header ('Vary: Accept-Encoding');



	$nocache = $r = '';



	if ($r) {
		//Handle Error

	} else {
		//text query

		// --------------
		$cl = GeographSphinxConnection('client');

		$mode = SPH_MATCH_ALL;
		if (strpos($q,'~') === 0) {
			$q = preg_replace('/^\~/','',$q);
			if (substr_count($q,' ') > 1) //over 2 words
				$mode = SPH_MATCH_ANY;
		} elseif (strpos($q,'-') !== FALSE || strpos($q,'|') !== FALSE) {
			$mode = SPH_MATCH_EXTENDED;
		}
		$index = "gaz";
		//$cl->SetWeights ( array ( 100, 1 ) );
		$cl->SetMatchMode ( $mode );
		$cl->SetSortMode ( SPH_SORT_EXTENDED, "score ASC, @relevance DESC" );
		$cl->SetLimits(0,20);
		$res = $cl->Query ( $q, $CONF['sphinx_prefix'].$index );

		// --------------

		if (!empty($_GET['debug'])) {
			print_r($res);
		}

		if ( $res===false )
		{
			print "\tQuery failed: -- please try again later.\n";
			exit;
		} else
		{
			if ( $cl->GetLastWarning() )
				print "\nWARNING: " . $cl->GetLastWarning() . "\n\n";

			$query_info = "Query '$q' retrieved ".count($res['matches'])." of $res[total_found] matches in $res[time] sec.\n";
		}

		if (is_array($res["matches"]) ) {

			$ids = array_keys($res["matches"]);

			$where = "id IN(".join(",",$ids).")";

			$sql = "SELECT gr,name,localities,id
			FROM placename_index
			WHERE $where
			LIMIT 60";
		} else {
			$r = "\t--none--";
		}
	}

	if ($sql) {
		$db=GeographDatabaseConnection(true);

		$result = $db->Execute($sql) or die ("Couldn't select query : $sql " . $db->ErrorMsg() . "\n");
		$r = '';
		if (!$result->EOF) {
			$rows = array();
			do {
				$row = $result->GetRowAssoc(false);
				$rows[$row['id']] = $row;
				$result->MoveNext();
			} while (!$result->EOF);
			foreach ($ids as $id) {
				$row = $rows[$id];

				$lines = wordwrap($row['localities'],50,"\n");
				$row['localities'] = preg_replace("/\n.*/s",'',$lines);

				$r .= join("\t",array_values($row))."\n";
			}
			$r .="\t$query_info\t\t\tGreat Britain results (c) Crown copyright Ordnance Survey. All Rights Reserved. 100045616";
		} else {
			$r = "\t--none--";
		}
	}

	if ($r) {
		if ($encoding) {
			$r = gzencode($r, 9,  ($encoding == 'gzip') ? FORCE_GZIP : FORCE_DEFLATE);
		}

		customExpiresHeader(3600*24*24,true);

		header('Content-length: '.strlen($r));

		print $r;
	}

