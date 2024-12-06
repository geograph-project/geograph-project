<?php
/**
 * $Project: GeoGraph $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2022 Barry Hunter (geo@barryhunter.co.uk)
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

//these are the arguments we expect
$param=array();


chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

##################################

$places1 = explode("\n",trim("
Stocksbromburgh
East Thorn
Sandssodnew-On-River
North Longchwood
East Cor
Stonecai
Stonered
West Cheadec
Thatcul
Bidree Upon Tyut
Brackngayleigh
Royal Gtontid
Tichippchester
New Ldonrebarnes
Stonerkhamsted
Stonecarlwhitt
Marldyfield
Hithetawminster
South Denper
West Bechsed
New Druthfre Under Bouri
Wimere
North Pingwlish
Eyefertels
Coldmoowich
Wouldshurst
Ghamsteynlet
Lydpdencle
North Oundwem Under Nterpad
New Lomstan
Xmindid Upon Brierlksham
Rowtingsport
Leaclare Under Neotsmuchbury
North Dsor
New Rstangspa
West Klinmskirk
Royal Nynoakswar
Thorpedalmfield
Stonesettbilhorn
Burghframchor
Bridrisfham
Shall
Patshal-On-Sea
South Heath
Royal Readtor
East Sansut
St Hipree
Rstoneedge With Whabridgechester
Lagreentrent
Royal Mirswafblaise
Port Gogu
"));



$places2 = explode("\n",trim("
Maumcrogh
Toberte
Cloneish
Vinnycarro
Croghcorry
Rushclon
Begfer
"));
//https://perchance.org/myyamb3638

$counties = $db->getCol("SELECT distinct full_county FROM gaz_locate WHERE reference_index= 1");
$c = 0;
foreach ($places1 as $place) {
	$updates = array();
	$updates['def_nam'] = $place;
	shuffle($counties);
	$updates['full_county'] = $counties[0];

	$updates['reference_index'] = 1;
	$updates['east'] = 0;
	$updates['north'] = 0;
	$updates['has_dup'] = 0;

	$updates['f_code'] = 'R';


	$db->Execute($sql = 'INSERT INTO gaz_locate SET `'.implode('` = ?,`',array_keys($updates)).'` = ?'
                                          ,array_values($updates)) or die("$sql\n\n".$db->ErrorMsg()."\n");
                        $c+=$db->Affected_Rows();
}


$counties = $db->getCol("SELECT distinct full_county FROM gaz_locate WHERE reference_index= 2");
$c = 0;
foreach ($places2 as $place) {
	$updates = array();
	$updates['def_nam'] = $place;
	shuffle($counties);
	$updates['full_county'] = $counties[0];

	$updates['reference_index'] = 2;
	$updates['east'] = 0;
	$updates['north'] = 0;
	$updates['has_dup'] = 0;

	$updates['f_code'] = 'R';


	$db->Execute($sql = 'INSERT INTO gaz_locate SET `'.implode('` = ?,`',array_keys($updates)).'` = ?'
                                          ,array_values($updates)) or die("$sql\n\n".$db->ErrorMsg()."\n");
                        $c+=$db->Affected_Rows();
}

print "$c.\n";
