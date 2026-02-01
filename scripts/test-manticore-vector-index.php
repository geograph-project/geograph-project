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
$param=array('id'=>3438350, 'type'=>'image',
	'nearest' => false, 'limit' => 10);


chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(false);

$CONF['manticorert_host'] = "manticorert-worker-svc.dev.svc.cluster.local"; //test instance!


##################################

if ($param['nearest']) {
	$rt = GeographSphinxConnection('manticorert',true);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	//donmt need to fetch label!
	$sql = "SELECT id, embeddings FROM label_embedding WHERE nearest_image IS NULL AND length(embeddings) = 2048 LIMIT {$param['limit']}"; //length selects CLIP!
	$rows = $db->getAll($sql);
	$affected = 0;
	foreach ($rows as $row) {
		$updates = array();
		print "{$row['id']}: ";

                $list = unpack('g*', $row['embeddings']);
                $value = "(".implode(', ',$list).")";

	        $vector = "image_vector";

                $sql = "select id, knn_dist() as k from gridimage_embedding where knn($vector, 10, $value) limit 1";
		$results = $rt->getRow($sql);
		if (!empty($results['k']))
			$updates['nearest_image'] = $results['k'];

                $vector = "title_vector";

                $sql = "select id, title, knn_dist() as k from gridimage_embedding where knn($vector, 10, $value) limit 1";
		$results = $rt->getRow($sql);
		if (!empty($results['k'])) 
			$updates['nearest_title'] = $results['k'];
		if ($results['k'] < 0.0000001) {
			print " Title Zero with image {$results['id']} ";
		}
//		print_r($updates);
		if (!empty($updates)) {
			$sql = "UPDATE label_embedding SET updated=updated, `".implode('` = ?,`',array_keys($updates))."` = ? WHERE id = {$row['id']}";
			$db->Execute($sql, array_values($updates)) or die("$sql\n".$db->ErrorMsg()."\n\n");
			 $affected += $db->Affected_Rows();
		} else {
			print_r($row['id']);
			exit;
		}
	}
	print count($rows)." = $affected\n";
	exit;
}

##################################

$row = $db->getRow("SELECT * FROM gridimage_embedding WHERE gridimage_id = {$param['id']} and type = '{$param['type']}'");

//Code provided by Gemini!

// --- Decode the binary blob to a PHP array of floats ---
// Since it was a NumPy float32 array (4 bytes per float),
// and assuming typical little-endian byte order (like x86/x64 systems where NumPy is common).
// 'g' specifies IEEE 754 single-precision float, little-endian.
// To unpack 512 floats, we use 'g512'.
$phpFloatArray = unpack("g*", $row['embeddings']);

// unpack returns an associative array with 1-based numeric keys (1, 2, 3...).
// If you need a 0-indexed numerically indexed array, you can use array_values().
$phpFloatArray = array_values($phpFloatArray);

echo "Embedding successfully retrieved and decoded for image_id: " . $row['gridimage_id'] . "\n";
echo "Number of dimensions (floats): " . count($phpFloatArray) . "\n";
echo "First 10 values of the decoded embedding:\n";
print_r(array_slice($phpFloatArray, 0, 10));

$outofrange =0;
foreach ($phpFloatArray as $number) {
	if ($number > 1 || $number < -1) {
		$outofrange++;
		print "$number\n";
	}

}
print "outofrange: $outofrange\n";

