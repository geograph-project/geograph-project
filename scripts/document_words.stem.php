<?

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


$rows = $db->getAll("SELECT * FROM document_words WHERE stemcrc = 0 LIMIT 1000")  or die("unable to select ".$db->ErrorMsg());


require "libs/3rdparty/PorterStemmer.class.php";

foreach($rows as $row) {

	$stem = PorterStemmer::Stem($row['word']);

	$stem = $db->Quote($stem);

	$sql = "UPDATE document_words SET stem = $stem,stemcrc=CRC32($stem) WHERE id = {$row['id']}";

	$db->Execute($sql);
	$count++;
}

print "$count\n\n";
