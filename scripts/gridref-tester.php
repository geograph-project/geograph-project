<?

$param = array('id'=>false);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$conv = new Conversions();

$square = new GridSquare();

############################################

//$data = $db->getAll("SELECT * FROM location_sample GROUP BY reference_index,nateastings div 100000, natnorthings div 100000");
$data = $db->getAll("SELECT * FROM location_sample");



$c = 0;
	foreach ($data as $row) {
		$e = $row['nateastings'];
		$n = $row['natnorthings'];
		$gr_length=4;
		$reference_index = $row['reference_index'];
		$e = $row['nateastings'];
		list($gridref,) = $conv->national_to_gridref($e,$n,$gr_length,$reference_index,false);

		//can also test the nearbyGridref implementation!
		$gr = $square->nearbyGridref(0,0,$row['grid_reference']);

		print "{$row['grid_reference']} == $gridref == $gr\n";

		if ($row['grid_reference'] != $gridref || $row['grid_reference'] != $gr) {
			print_r($row);
			exit;
		}

		$c++;
	}

print "$c.\n";


/*

create table location_sample select gridimage_id,nateastings,natnorthings,grid_reference,reference_index from gridimage inner join gridimage_search using (gridimage_id) where nateastings > 0 group by reference_index,nateastings div 10000, natnorthings div 10000;

//NOTE, gridimage.nateastings is unsigned, so rockall fails eastings is stored as 0!

insert into location_sample select gridimage_id,nateastings,natnorthings,grid_reference,reference_index from gridimage_search inner join gridimage using (gridimage_id) where grid_reference like 'MC%' limit 4;

alter table location_sample modify nateastings mediumint not null;

update location_sample set nateastings = -296500 where grid_reference LIKE 'MC%' and nateastings = 0;

*/
