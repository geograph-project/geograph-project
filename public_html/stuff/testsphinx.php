<?

//if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'],'66.249.76.') !== FALSE) {
//	header("HTTP/1.1 503 Service Unavailable");
//	exit;
//}

$ABORT_GLOBAL_EARLY = true;

require_once('geograph/global.inc.php');
require_once('3rdparty/facet-functions.php');

if (!defined('SPHINX_INDEX')) {
	if (!empty($_GET['cc'])) {
	        define('SPHINX_INDEX',"content_stemmed");
	} elseif (!empty($_GET['gg'])) {
	        define('SPHINX_INDEX',"germany");
	} elseif (!empty($_GET['is'])) {
        	define('SPHINX_INDEX',"islands");
	} elseif (!empty($_GET['vv'])) {
	        define('SPHINX_INDEX',"viewpoint");
	} else
        	define('SPHINX_INDEX',empty($_GET['recent'])?"sample8":"sample8E,sample8D");
}


###########################################
#initialize query

	$db = mysql_sphinx();


	print "Host: {$CONF['sphinx_host']}<br>";
	print "<pre>";


	$data = getAssoc("SELECT COUNT(*) FROM sample8E");
	print_r($data);

	$data = getAssoc("SHOW STATUS");
	print_r($data);


#end
###########################################


function getAll($query) {
	global $db;
	if (!($result = mysql_query($query, $db))) {
		return FALSE; //SHOW META in sphinx will report the error
	}
	if (!mysql_num_rows($result)) {
		return FALSE;
	}
	$a = array();
	while($row = mysql_fetch_assoc($result)) {
                if (!empty($row['title']))
                        $row['title'] = utf8_encode($row['title']);
                if (!empty($row['realname']))
                        $row['realname'] = utf8_encode($row['realname']);
                if (!empty($row['place']))
                        $row['place'] = utf8_encode($row['place']);
		$a[] = $row;
	}
	return $a;
}
function getAssoc($query) {
	global $db;
	if (!($result = mysql_query($query, $db))) {
		return FALSE; //SHOW META in sphinx will report the error
	}
	if (!mysql_num_rows($result)) {
		return FALSE;
	}
	$a = array();
	$row = mysql_fetch_assoc($result);

	if (count($row) > 2) {
		do {
			$i = array_shift($row);
			$a[$i] = $row;
		} while($row = mysql_fetch_assoc($result));
	} else {
		$row = array_values($row);
		do {
			$a[$row[0]] = $row[1];
		} while($row = mysql_fetch_row($result));
	}
	return $a;
}

