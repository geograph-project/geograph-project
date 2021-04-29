<?php
/**
 * $Project: GeoGraph $
 * $Id: places.php 6407 2010-03-03 20:44:37Z barry $
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

customExpiresHeader(360000);

if (!empty($_GET['q'])) {
	$q=trim($_GET['q']);

        $sphinx = new sphinxwrapper($q);

	$sph = GeographSphinxConnection('sphinxql',true);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	$Q = $sph->Quote('"'.$sphinx->q.'"/1');
	$data['rows'] = $sph->getAll("select * from abgaz where match($Q) option field_weights = (full_name=10,hcounty=5)");
	$data['meta'] = $sph->getAll("show meta");

	if (!empty( $data['rows'])) {
		$conv = new Conversions();

		foreach ( $data['rows'] as $idx => $row) {
			$row['reference_index'] = 1; //all gb for now!
			list($lat,$lng) = $conv->national_to_wgs84($row['e'],$row['n'],$row['reference_index']);
			$data['rows'][$idx]['lat'] = $lat;
			$data['rows'][$idx]['lng'] = $lng;
		}
	}

} else {
	$data = "No query!";
}


outputJSON($data);

