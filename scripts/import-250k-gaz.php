<?

$param=array('file'=>"ras250_gb/gazetteer/250K_Raster_Gaz_2022.txt");

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

require_once('geograph/conversions.class.php');
$conv = new Conversions;

############################################

$lookup=array();

    $recordSet = $db->Execute("SELECT def_nam, east, north, seq FROM os_gaz_250");
    while (!$recordSet->EOF) {
            $row =& $recordSet->fields;

        $name = latin1_to_utf8($row['def_nam']);
        $lookup["$name*".intval($row['east']/1000)."*".intval($row['north']/1000)] = $row['seq'];

            $recordSet->MoveNext();
    }
    $recordSet->Close();

############################################


chdir("../");

$h = fopen($param['file'],'r');

//$headers = fgetcsv($h); //it has no headers!
//A' Chill*HIGHLAND*126851*805183
//Ab Kettleby*LEICESTERSHIRE COUNTY*472370*323070

$defer = array();
$c=0;
while($h && !feof($h)) {
	$line = explode('*',trim(fgets($h)));
	if (count($line)<4)
		continue;

        $updates = array();
        $updates['def_nam'] = $line[0];
        $updates['full_county'] = $line[1];
	//def_nam_soundex - lets not bother, not used anyway!
        $updates['east'] = $line[2];
        $updates['north'] = $line[3];

	list ($gridref,) = $conv->national_to_gridref($updates['east'], $updates['north'], 4, 1);
	$updates['km_ref'] = $gridref;

	$k = "{$line[0]}*".intval($line[2]/1000)."*".intval($line[3]/1000); //dont use the county as that changes!
	if (!empty($lookup[$k])) {
		$updates['seq'] = $lookup[$k];

		$point = "point_en = GeomFromText('POINT({$updates['east']} {$updates['north']})')";
		$db->Execute('INSERT INTO os_gaz_250_new SET `'.implode('` = ?,`',array_keys($updates)).'` = ? ,'.$point,array_values($updates));
		$c++;
	} else {
		//defer these to end!
		$defer[] = $updates;
	}

	if (!($c%100))
		print "$c. ";
}

print "$c. \n";


//defer until end!
foreach ($defer as $updates) {

	$point = "point_en = GeomFromText('POINT({$updates['east']} {$updates['north']})')";
	$db->Execute('INSERT INTO os_gaz_250_new SET `'.implode('` = ?,`',array_keys($updates)).'` = ? ,'.$point,array_values($updates));
	$c++;

	if (!($c%100))
		print "$c. ";
}

print "$c. \n";


/*
CREATE TABLE `os_gaz_250_new` (
  `seq` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `def_nam` varchar(250) NOT NULL,
  `full_county` varchar(250) NOT NULL,
  `east` mediumint(8) unsigned NOT NULL,
  `north` mediumint(8) unsigned NOT NULL,
  `point_en` point NOT NULL DEFAULT '',
  `km_ref` varchar(6) NOT NULL DEFAULT '',
  PRIMARY KEY (`seq`),
  SPATIAL KEY `point_en` (`point_en`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
*/
