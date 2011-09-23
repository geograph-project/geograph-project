<?

$db = mysql_connect('cream','geograph','m4pp3r') or die("unable to connect ".mysql_error());
mysql_select_db("geograph_live",$db)  or die("unable to use ".mysql_error());;


$result = mysql_query("SELECT * FROM document_words WHERE stemcrc = 0 LIMIT 1000")  or die("unable to select ".mysql_error());


require "libs/3rdparty/PorterStemmer.class.php";

while($row = mysql_fetch_assoc($result)) {

	$stem = PorterStemmer::Stem($row['word']);

	$stem = mysql_real_escape_string($stem);

	$sql = "UPDATE document_words SET stem = '$stem',stemcrc=CRC32('$stem') WHERE id = {$row['id']}";

	mysql_query($sql);
	$count++;
}

print "$count\n\n";
