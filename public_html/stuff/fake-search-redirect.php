<?

require_once('geograph/global.inc.php');
$db = GeographDatabaseConnection(true);

if (rand(1,2) == 2) {
	$rnd = rand()/getrandmax();
	$query = $db->getOne($sql = "SELECT tagtext FROM tag_stat where rnd > ".$rnd);

	if (strpos($query,':') !== FALSE) {
		if (rand(1,2) == 2)
			//the : prefix seperator will conflicked with field operator!
			$query = str_replace(':',' ',$query);
		else
			$query = "[$query]"; //the 'tag' operator deals with the prefix!
	}
} else {
	$query = $db->getOne($sql = "SELECT Place FROM sphinx_placenames order by rand()"); //LIMIT 1 added by getOne!!
} //esel pick a gridrefernce or a postcode??

if (rand(1,3) !== 1) {
	$url = "/of/".urlencode2($query);
} elseif (rand(1,2) == 2) {
	$url = "/search.php?form=simple&go=1&q=".urlencode($query);
} else {
	$url = "/browser/#!/q=".urlencode($query); //will still end up at 'fake browser'

	$url = str_replace('/browser/#','/browser/_fake-browser.php?_escaped_fragment_=',$url); //when following a redirect this transformation wont be done automatically
}

header("Location: $url");

