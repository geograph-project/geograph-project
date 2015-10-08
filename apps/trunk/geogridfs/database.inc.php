<?

$mounts = '';
foreach (file(__DIR__.'/config.py') as $line)
        if (preg_match("/\s(\w+) = '([\w\/-]+)'/",$line,$m))
                $mounts[$m[1]] = $m[2];

$db =mysql_connect($mounts['hostname'],$mounts['username'],$mounts['password']) or die(mysql_error());
mysql_select_db($mounts['database'],$db) or die(mysql_error());

#####

function dbQuote($in) {
        return "'".mysql_real_escape_string($in)."'";
}

function queryExecute($query,$debug=false) {
        global $db;
	if ($debug) {
		print "#Starting ".date('r')."\n";;
		print "$query\n";
	}
        $result = mysql_query($query, $db) or print('<br>Error queryExecute: '.mysql_error());
	if ($debug) {
		print "#Done ".date('r')." : ".mysql_affected_rows()." Rows\n---------\n";

	}
        return $result;
}

function getOne($query) {
        global $db;
        $result = mysql_query($query, $db) or print("<br>Error getOne [[ $query ]] : ".mysql_error());
        if (mysql_num_rows($result)) {
                return mysql_result($result,0,0);
        } else {
                return FALSE;
        }
}

function getRow($query) {
        global $db;
        $result = mysql_query($query, $db) or print('<br>Error getRow: '.mysql_error());
        if (mysql_num_rows($result)) {
                return mysql_fetch_assoc($result);
        } else {
                return FALSE;
        }
}

function getCol($query) {
        global $db;
        $result = mysql_query($query, $db) or print('<br>Error getColAsKeys: '.mysql_error());
        if (!mysql_num_rows($result)) {
                return FALSE;
        }
        $a = array();
        while($row = mysql_fetch_row($result)) {
                $a[] = $row[0];
        }
        return $a;
}

function getColAsKeys($query) {
        global $db;
        $result = mysql_query($query, $db) or print('<br>Error getColAsKeys: '.mysql_error());
        if (!mysql_num_rows($result)) {
                return FALSE;
        }
        $a = array();
        while($row = mysql_fetch_row($result)) {
                $a[$row[0]] = '';
        }
        return $a;
}


function getAll($query) {
        global $db;
        $result = mysql_query($query, $db) or print('<br>Error getAll: '.mysql_error());
        if (!mysql_num_rows($result)) {
                return FALSE;
        }
        $a = array();
        while($row = mysql_fetch_assoc($result)) {
                $a[] = $row;
        }
        return $a;
}


function getAssoc($query) {
        global $db;
        $result = mysql_query($query, $db) or print('<br>Error getAssoc: '.mysql_error());
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

function print_rp($q) {
        print "<pre style='border:1px solid red; padding:10px; text-align:left; background-color:silver'>";
        print_r($q);
        print "</pre>";
}


