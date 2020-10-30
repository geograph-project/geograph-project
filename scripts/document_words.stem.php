<?

//these are the arguments we expect
$param=array(
);

chdir(__DIR__);
require "./_scripts.inc.php";


$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

###################################

$w = array();

$recordSet = $db->Execute("select title,extract,titles,tags from content where type = 'document'");
while (!$recordSet->EOF) {
	foreach ($recordSet->fields as $key => $value) {
		foreach(explode(" ",strtolower(trim(preg_replace('/[^\w]+/',' ',$value)))) as $word) {
			@$w[$word]++;
		}
	}
        $recordSet->MoveNext();
}
$recordSet->Close();

###################################

$db->Execute("UPDATE document_words SET uses = 0");

$inserts = 0;
foreach ($w as $word => $count) {
	$updates = "word = ".$db->Quote($word).", uses = $count";
	$db->Execute("INSERT INTO document_words SET $updates  ON DUPLICATE KEY UPDATE $updates") or die(mysql_error());
	$inserts++;
}

print "inserts = $inserts\n";

########################################

$stemmed = 0;

require "3rdparty/PorterStemmer.class.php";

$recordSet = $db->Execute("SELECT id,word FROM document_words WHERE stemcrc = 0");
while (!$recordSet->EOF) {
	$stem = PorterStemmer::Stem($recordSet->fields['word']);
	$stem = $db->Quote($stem);
	$db->Execute("UPDATE document_words SET stem = $stem,stemcrc=CRC32($stem) WHERE id = {$recordSet->fields['id']}");
        $recordSet->MoveNext();
	$stemmed++;
}
$recordSet->Close();


print "Stemmed = $stemmed\n";

