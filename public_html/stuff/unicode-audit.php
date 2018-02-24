<?

require_once('geograph/global.inc.php');
init_session();

$db = GeographDatabaseConnection(false);


if (!empty($_GET['q'])) {
	if (strlen($_GET['q']) > 1) {
		$like = "LIKE ".$db->Quote('%'.$_GET['q'].'%'); //its a html entity!
	} else {
		$like = "LIKE BINARY CONCAT('%',CHAR(".ord($_GET['q'])."),'%')";
	}
	$rows = $db->getAll("SELECT * FROM gridimage_funny WHERE title $like OR comment $like LIMIT 100");
	foreach ($rows as $row) {
		print "<a href=/photo/{$row['gridimage_id']}>{$row['gridimage_id']}</a><br>";
		print "<b>".htmlspecialchars2($row['title'])."</b>";
		print "<p>".htmlspecialchars2($row['comment'])."</p>";

		if (!empty($_GET['t'])) {
			print "<b>".htmlentities(iconv('ISO-8859-15','ASCII//TRANSLIT', $row['title']))."</b>";
			print "<p>".htmlentities(iconv('ISO-8859-15','ASCII//TRANSLIT', $row['comment']))."</p>";

		}
		print "<hr>";
	}
	exit;
}

if (!empty($_POST['o'])) {
	foreach ($_POST['o'] as $c => $value) {
		if (!empty($value)) {
	             	$updates = array();
        	        $updates["encoded"] = $c;
			$updates["final"] = $value;
                	$updates["user_id"] = intval($USER->user_id);

	                $db->Execute('INSERT INTO sphinx_latin1_map SET created = NOW(),`'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
        	        //$id = mysql_insert_id();
		}
	}
}

$done = $db->getAssoc("SELECT encoded,final FROM sphinx_latin1_map ORDER BY created");

if (!empty($_GET['done'])) {
	$charset = $ignore = $regexp = array();
	print "<p>(Note, spaces are shown as + so visible)</p>";
	foreach ($done as $c => $final) {
		if (!empty($_GET['i']) && strlen($c) > 3)
			continue;
		print "<div title=\"$c\">";
		print "<big>".htmlspecialchars2(urldecode($c))."</big>";
		print " => ".urlencode($final);
		print "</div>";
		if (!preg_match('/^%[\dA-F]{2}$/',$c) || (strlen($final) > 1 && $final != 'SPACE' && $final != 'IGNORE')) {
			$regexp[$c] = $final;
		} elseif($final == 'SPACE' || $final == ' ') { //ignore
		} elseif($final == 'IGNORE') {
			$ignore[$c]=1;
		} else {
			$charset[$c] = $final;
		}
	}
	print "<hr>";
	$charset_type = "sbcs"; 
	if (!empty($_GET['u']))  $charset_type = "utf-8";
	print "<h3>Generated sphinx.conf mapping for $charset_type</h3>";
	print "<pre>";
	print "charset_type = $charset_type\n";
	if (!empty($regexp)) {
		//TODO, collapse multiple into one (eg &#1071; => r and &#1103; => r COULD be &#(1071|1103); => r
		asort($regexp);
		foreach ($regexp as $c => $final) {
			if ($final == 'SPACE') $final = '.'; //spaces dont work, in regex syntax, so use some other arbitary seperator
			if ($final == 'IGNORE') { $final = '\\x80'; $ignore['%80']=1; } //sphinx wont ignore control chars, so choose an aread undefined in latin1

			if (!empty($_GET['u'])) {
				if (strlen($c) > 3 || ord(urldecode($c)) < 127 ) { //a html entity OR a ascii char!
					$c = str_replace('%','\\x',$c); # \x7F	hex character code (exactly two digits)
				} else {
					//oterwise its a non-ascii, but  iso-8859-1, which needs will have been changed in mysql->sphinx to a UTF8
					$char = latin1_to_utf8(urldecode($c)); //get as a plain char
					$c = '\\x{'.dechex(uniord($char)).'}'; //encode as re2 expresison \x{10FFFF} 
				}
			} else {
				//$c = urldecode($c);
				$c = str_replace('%','\\x',$c); # \x7F	hex character code (exactly two digits)
			}

			$final = str_replace(' ','.',$final);

			print "regexp_filter = ".htmlentities($c)." => $final\n";   //need htmlentities as we ARE replaceing some actual entities, but simple ones
		}
		print "\n\n";
	}

	if (!empty($ignore)) {
		print "ignore_chars = "; $sep = '';
		foreach ($ignore as $c => $dummy) {
			print "$sep".simple_urlencode_to_char($c)."";
			$sep = ", ";
		}
		print "\n\n";
	}
	if (!empty($charset)) {
		print "charset_table = 0..9, A..Z->a..z, _, a..z \\\n\t\t";
		asort($charset);
		$i =1;
		foreach ($charset as $c => $final) {
			print ", ".simple_urlencode_to_char($c)."->".strtolower($final);
			if (!($i%10))
				print " \\\n\t\t";
			$i++;
		}
	}
	print "</pre>";
	exit;
} else {
	print "<p><a href=?done=1>View results so far</a></p>";
}


if (!empty($_GET['descriptions'])) {
	$result = mysql_query("select comment from gridimage_funny WHERE comment IS NOT NULL");

} else {
	$result = mysql_query("select title from gridimage_funny WHERE title IS NOT NULL");
}

$a = array();

while ($row = mysql_fetch_assoc($result)) {
	$value = array_pop($row);
	$encoded = str_replace('+',' ',urlencode($value));

	/* if (preg_match_all('/(%[\dA-F]{2})(\d|%[\dA-F]{2})*'.'/',$encoded,$m)) {
		foreach ($m[0] as $c)
			$a[$c] = $value;
	}*/

	if (preg_match_all('/(%[\dA-F]{2})/',$encoded,$m)) {
		foreach ($m[0] as $c)
			$a[$c] = $value;
	}
	if (preg_match_all('/(%26(%23\d+|\w+)%3B)/',$encoded,$m)) {
                foreach ($m[0] as $c)
                        $a[$c] = $value;
        }

}

//print_r($a);

ksort($a);

print "<form method=post>";
print "<p><b>Acceptable values are 'SPACE', 'IGNORE' and/or a series of one or more: LOWERCASE letters (a-z), numbers (0-9) and underscores (_).</b> (if multiple can include spaces too)</p>";
print "SPACE if should be treated as word sperator. IGNORE if should be treated as never even there. letters should be mapped to mapped to their lowercase equivient. If nothing visible, before the textbox, then probably some sort of seperator, but for now leave the text box blank</p>";

foreach ($a as $c => $title) {
	if (!empty($done[$c]) && empty($_GET['all']))
		continue;
	if (!empty($_GET['i']) && strlen($c) > 3)
		continue;

	print "<big>".htmlspecialchars2(urldecode($c))."</big>";
	print " <input type=text name=\"o[$c]\" value=\"\" size=2 title=\"$c\">";
	print htmlspecialchars2($title);

	print " <a href=?q=$c>more</a>"; //$c is ALREADY urlencoded!
	print "<hr>\n\n";
}

print "<input type=submit>";



//http://php.net/manual/en/function.ord.php

function uniord($u) {
    $k = mb_convert_encoding($u, 'UCS-2LE', 'UTF-8');
    $k1 = ord(substr($k, 0, 1));
    $k2 = ord(substr($k, 1, 1));
    return $k2 * 256 + $k1;
}


	function uniord2($char) {
		//trick from http://php.net/manual/en/function.ord.php#109724
		return hexdec(bin2hex($char));
	}
	function simple_urlencode_to_char($c) {
		if (!empty($_GET['u'])) {
			if (ord(urldecode($c)) < 127) {//plain ascii, so easy
				return str_replace('%','U+',$c);
			} else {
				$char = latin1_to_utf8(urldecode($c));
				return "U+".strtoupper(dechex(uniord2($char)));
			}
		} else {
			//must by a plain sbcs char
			return str_replace('%','U+',$c);
		}
	}

