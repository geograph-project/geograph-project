<? if (!empty($_GET['show'])) {
	$str = file_get_contents(__FILE__);
	highlight_string($str);
	exit;
}


require_once('geograph/global.inc.php');
$db = GeographDatabaseConnection(true);


$result = mysql_query("select gridimage_id,title from gridimage_funny where title is not null order by  gridimage_id IN (1339706,320042,47189,495051,519472,97714,1049262,1036631) desc,reverse(gridimage_id) limit 500");

header("Content-Type: text/xml; charset=utf-8");

  echo '<?xml version="1.0" encoding="UTF-8"?>';
  echo '<geograph>';

$default = ini_get("default_charset");
while ($row = mysql_fetch_assoc($result)) {

        switch ($_GET['method']) {
                case 'raw': break; //noop!
                case 'html': $row['title'] = htmlentities($row['title']); break;

                case 'utf_html': $row['title'] = utf8_encode(htmlentities($row['title'])); break;
                case 'html_utf': $row['title'] = htmlentities(utf8_encode($row['title'])); break;

                case 'utf_spc': $row['title'] = utf8_encode(htmlspecialchars($row['title'])); break;
                case 'spc_utf': $row['title'] = htmlspecialchars(utf8_encode($row['title'])); break;

                case 'utf_xml': $row['title'] = utf8_encode(xmlentities($row['title'])); break;
                case 'xml_utf': $row['title'] = xmlentities(utf8_encode($row['title'])); break;

                case 'utf_xml2': $row['title'] = utf8_encode(xml2($row['title'])); break;
                case 'xml2_utf': $row['title'] = xml2(utf8_encode($row['title'])); break;

                case 'iconvdef_xml2': $row['title'] = iconv($default, "utf-8", xml2($row['title'])); break;
                case 'xml2_iconvdef': $row['title'] = xml2(iconv($default, "utf-8", $row['title'])); break;

		case 'geograph': $row['title'] = xmlentities(latin1_to_utf8($row['title'])); break;

                case 'iconv_xml2': $row['title'] = iconv("windows-1252", "utf-8", xml2($row['title'])); break;
                case 'xml2_iconv':
                default: $row['title'] = xml2(iconv("windows-1252", "utf-8", $row['title']));
                        $_GET['method'] = "xml2_iconv";
        }
	echo '<title id="'.$row['gridimage_id'].'">'.$row['title'].'</title>';
	if (!empty($_GET['encode']))
		echo '<encoded>'.str_replace('+',' ',urlencode($row['title'])).'</encoded>';

	print "\n";
}
echo "<!-- method: {$_GET['method']} (from $default) -->";

echo '</geograph>';

function xml2($string) {
	$trans = array('"' =>"&quot;",'&'=>"&amp;","'"=>"&apos;","<"=>"&lt;",">"=>"&gt;");//chr(160)=>"&nbsp;");
	return preg_replace( "/&amp;([A-Za-z]{0,4}\w{2,3};|#[0-9]{2,4};|#x[0-9a-fA-F]{2,4};)/", '&$1' ,str_replace(array_keys($trans),array_values($trans),$string));
}


