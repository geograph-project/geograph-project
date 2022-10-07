<?

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;

//$USER->mustHavePerm("basic");




if (!empty($_GET['compare'])) {
        //hardcoded index names for now!
        $sph = GeographSphinxConnection('sphinxql',true);
        $one = $two = $all = array();
        foreach ($sph->getAll("DESCRIBE gi_stemmed") as $row) {
		if ($row['Type'] == 'field') {
			$one[$row['Field']] = 1;
			@$all[$row['Field']]++;
		}
	}
        foreach ($sph->getAll("DESCRIBE sample8A") as $row) {
		if ($row['Type'] == 'field') {
			$two[$row['Field']] = 1;
			@$all[$row['Field']]++;
		}
	}

	$first = $second = $both = array();
	foreach ($all as $key => $count) {
		if ($count == 1) {
			if (isset($one[$key]) && !isset($two[$key])) $first[] = $key;
			if (isset($two[$key]) && !isset($one[$key])) $second[] = $key;
		} else {
			$both[] = $key;
		}
	}

	print "All: ".implode('|',array_keys($all))."<hr>";
	print "One: ".implode('|',array_keys($one))."<hr>";
	print "Two: ".implode('|',array_keys($two))."<hr>";

	print "First: ".implode('|',$first)."<hr>";
	print "Second: ".implode('|',$second)."<hr>";
	print "Both: ".implode('|',$both)."<hr>";

        exit;
}



if (!preg_match('/^\w+$/',$_GET['index']))
	die("please specify index");

$index = $_GET['index'];

$sph = GeographSphinxConnection('sphinxql',false);

$try = 1;
while ($try < 3) {
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

		$rows = $sph->getAll("DESCRIBE ".$index);

		if (!empty($rows[0]) && !empty($rows[0]['Agent']) && $rows[0]['Type'] == 'local') {
			//in the case of distributed index, sphinx tells us the component indexes, lets instead return result for the compoentn index.
			// Users care about teh fields/attributes available, not how its built by the server
			$rows = $sph->getAll("DESCRIBE ".$rows[0]['Agent']);
		}

	if (!empty($rows)) {
		//$example = $sph->getRow("SELECT * FROM $index WHERE id = 5267473");
		$example = $sph->getRow("SELECT * FROM $index LIMIT 1");
	} else {
		if ($try == 1) {
			//rconnect to tea and try again
			$sph = GeographSphinxConnection('sphinxql',true);
			$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		} else {
			die("unable to find index?");
		}
	}
	$try++;
}

$indexes = array(
	'sample8'=>array('sample6','New Images','Most fully featured index. Conceived for the Browser, but now used by many site features,
			like the Image Facet API, the new Simple Search, and Related Images sidebar'),
	'gi_stemmed'=>array('gridimage','Original Images','Comprehenisive index of Images, made for the original Image search, but used elsewhere'),
	//'sqim'=>array('sqim','Images by Square','Images pre-grouped by GridSquare'),
	'viewpoint'=>array('viewpoint','Simplified Images','Simplified images indexed, exclusively for the "Image Dots" basemap'),

	'document_stemmed'=>array('document','Documentation','Documents from the Information pages, including FAQs'),
	'content_stemmed'=>array('content','Collections','Used for the high level Collections page'),
	'snippet'=>array('snippet','Shared Descriptions','General index for direct serching'),
	'post_stemmed'=>array('post','Discussion Posts','Used for the forum search page'),
	'tags'=>array('tags','Tags','First Generation Tags index, for direct searching by tag'),
	'tagsoup'=>array('tagsoup','Tag Soup','Tags indexs, based on images actully attached to'),
	'category'=>array('category','Categories','Direct search of category labels'),
	'gaz_stemmed'=>array('gaz','Gazetter','General Placename Search index'),
	'gaznew'=>array('gaznew','New Gazetter','Newer more advanced placename index'),
	'user'=>array('user','User/Contributors','General index of usernames'),
	//'vision'=>array('vision','Computer Vision Labels','image index using Google Computer Vision Labels'),
	//'snippet'=>array('',''),

);
print "<h2>Main Sphinx Indexes used by Geograph</h2>";
print "<table>";
foreach ($indexes as $idx => $row) {
	print "<tr>";
	if ($index == $idx) {
		print "<td><b>".($title = $row[1])."</b></td>";
		$file = $row[0];
	} else
		print "<td><a href=?index=$idx>".$row[1]."</a></td>";
	print "<td>".$row[2]."</td>";
}
print "</table>";
?>
<hr>
<p>Only columns marked with 'Field' (and shown in bold) are 'full-text searchable'. These columns are indexed and searchable.
Can use them with field syntax in the keywords search box (eg [<tt>@title words</tt>] or in most [<tt>title: words</tt>]).
(all other columns are stored in index, and can be used by the index for sorting, filtering, grouping. But will depend on the interface for the feature which are usable)</p>
<?

if (!empty($title))
	print "<h3>$title</h3>";

function cmp($a, $b) {
    return strcasecmp($a['Field'], $b['Field']);
}
uasort($rows, "cmp");

$data = array();
$alltypes = array();
foreach ($rows as $row) {
	$data[$row['Field']][$row['Type']] = 1;
	$alltypes[$row['Type']] = 1;
}
print "<table border=1 cellspacing=0 cellpadding=3><tr>";
print "<th>Field";
foreach($alltypes as $type=>$dummy)
	print "<th>$type";
print "<th>Example";
foreach ($data as $field => $types) {
	$style= (isset($types['field']))?' style="font-weight:bold;background-color:#ffffcc"':'';
	print "<tr $style>";
	print "<td>$field";
	foreach($alltypes as $type=>$dummy) {
		if (!empty($types[$type])) {
			print "<td>".htmlentities($type)."</td>";
		} else {
			print "<td></td>";
		}
	}

	if (!empty($example[$field])) {
		print "<td>".htmlentities($example[$field]);
	} else {
		print "<td style=color:gray><i>unable to show example</i>";
	}
	if (!empty($example2[$field])) {
		print "<td>".htmlentities($example2[$field]);
	}
	print "</tr>\n";
}

print "</table>";



