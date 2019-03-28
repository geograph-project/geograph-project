<?php
/**
 * $Project: GeoGraph $
 * $Id: viewps.php 2295 2006-07-05 12:15:49Z barryhunter $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
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

require_once('geograph/global.inc.php');
init_session();

$USER->mustHavePerm("admin");

?>
<html>
<head>
<title>drain tasks</title>
<meta name="robots" content="noindex\" />
</head>
<body style="font-size:10px;font-family:verdana">
<p>
<?

if ($USER->hasPerm("admin")) {
        print " | <a href=\"/statistics/filesystem.php?tab=drain-tasks\">Back to Stats page</a>";
}
print " |</p>";


$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

//$db = GeographDatabaseConnection(false);

$filedb=NewADOConnection($CONF['filesystem_dsn']);

if (empty($_GET['target']))
	$_GET['target'] = 'mount';
if (empty($_GET['clause']))
	$_GET['clause'] = "class = 'thumb.jpg' AND replicas LIKE '%tea%tea%' AND replica_count > replica_target";

if (!empty($_GET['target']))
	$_GET['target'] = trim($_GET['target']);

if (!preg_match('/^[\w%]+$/',$_GET['target']))
	die("grr\n");

//if (!preg_match('/^[\w %\'\$<>=\!\()\.*:\[\]-]+$/',$_GET['clause']))
//	die("grrrrr\n");


$orders = array('NULL','shard ASC','shard DESC','files ASC','files DESC','RAND()','bytes DESC','replica_count ASC','replica_count DESC');
if (empty($_GET['order']) || !in_array($_GET['order'],$orders))
	$_GET['order'] = $orders[0];


$sql = "select clause,target,count(*) tasks,sum(executed < 1) todo,sum(executed > 1) done,SUM(if(executed > 1,files,0)) as files,max(created) from drain_task group by clause order by created";
$title = "Existing";
//print '<div style="height:200px;font-size:0.5em;overflow:auto">';
//dump_sql_table($sql,$title,$filedb);
//print "</div>";

?>
group by shard,class,replicas,replica_count,replica_target,backups,backup_count,backup_target<BR>
<hr>
<form method=get>
Target <input type="text" name="target" value="<? echo htmlentities($_GET['target']); ?>" size=10><input type=submit><BR>
Clause <input type="text" name="clause"  value="<? echo htmlentities($_GET['clause']); ?>" size=120 maxlength=255><br>
<select name="order"><?
foreach ($orders as $order)
	printf('<option value="%s"%s>%s</option>',$order,$_GET['order']==$order?' selected':'',$order);
?>
</select> &nbsp;&nbsp;&nbsp;&nbsp;

New only? <input type=checkbox name="new" <? if (!empty($_GET['new'])) { echo ' checked'; } ?>>
Ignore Dups? <input type=checkbox name="ignore" <? if (!empty($_GET['ignore'])) { echo ' checked'; } ?>>
<?

$sql = "SELECT * FROM file_stat WHERE {$_GET['clause']} AND replica_count > 1 AND replicas LIKE '%{$_GET['target']}%' GROUP BY class,replicas LIMIT 50";
$sql = str_replace('$target',$_GET['target'],$sql);
$sql = str_replace(" AND replicas LIKE '%mount%'","",$sql);
$title = "Examples";
if (!empty($_GET['e']))
	dump_sql_table($sql,$title,$filedb);

$q = $filedb->Quote($_GET['clause']);

$target = $filedb->Quote($_GET['target']);
if ($_GET['target'] == 'mount' || preg_match('/[%_]/',$_GET['target'])) {
	$target = "mount"; //this a column, comes from the join
} elseif ($_GET['target'] == 'ssd') {
	$target = "CONCAT(IF(RAND()>0.5,'tea','cake'),'s',IF(RAND()>0.5,'1','2'))";
} elseif ($_GET['target'] == 'hard') {
	$target = "CONCAT(IF(RAND()>0.5,'tea','cake'),'h',IF(RAND()>0.5,'1','2'))";
}

$sql = "SELECT NULL,shard,SUM(`count`) AS files,SUM(bytes) as bytes,$q AS `clause`,$target AS target,NOW() as created,0 AS `executed`,0 AS defer_until FROM file_stat WHERE {$_GET['clause']} AND replica_count > 1 GROUP BY shard ORDER BY ".$_GET['order'];
if ($target == 'mount' || preg_match('/[%_]/',$_GET['target'])) {
	$sql = str_replace('WHERE ',"INNER JOIN mounts ON (replicas LIKE CONCAT('%',mount,'%')) WHERE ",$sql);

	if (preg_match('/[%_]/',$_GET['target'])) {
		$sql = str_replace('WHERE ','WHERE mount LIKE '.$filedb->Quote($_GET['target']).' AND ',$sql);
	}

	//uncomment this to create the rule on ALL mounts, rather than just the first one in the mounts table!
	//$sql = str_replace('GROUP BY shard','GROUP BY mount,shard',$sql);
} elseif (strlen($target) < 10) {
	$sql = str_replace('$target',$_GET['target'],$sql);
	$sql = str_replace('WHERE ',"WHERE replicas LIKE '%{$_GET['target']}%' AND ",$sql); //drain-task does this automatically, so don't need it in 'clause', but it helps to include to avoid CREATING unnesserially tasks.
}
if (!empty($_GET['new']))
	$sql = str_replace('GROUP BY',"AND shard NOT IN(select distinct shard from drain_task where clause = $q) GROUP BY",$sql);

$title = "INSERT INTO drain_task $sql;";
$sql = str_replace("SELECT ","SELECT SQL_CALC_FOUND_ROWS ",$sql)." LIMIT 10";

if (!empty($_GET['ignore'])) {
	$title = str_replace("INSERT INTO","INSERT IGNORE INTO",  $title);
}

dump_sql_table($sql,$title,$filedb);

print "Total = ".$filedb->getOne("SELECT FOUND_ROWS()");

if (!empty($_GET['Execute']) && $USER->hasPerm("admin")) {
	$_GET['limit'] = empty($_GET['limit'])?999999:intval($_GET['limit']);
	print "<HR>RUNNING ".str_replace(';',' LIMIT '.intval($_GET['limit']),$title);
        $filedb->Execute(str_replace(';',' LIMIT '.intval($_GET['limit']),$title));
        print "<hr>Inserted: ".mysql_affected_rows();
	$e = mysql_error();
	if (!empty($e))
	        print "<hr>Error: ".htmlentities($e);
}

?>
<hr>
Limit <input type=tel name=limit value="<? echo empty($_GET['limit'])?'':intval($_GET['limit']); ?>">
<input type=submit name="Execute" value="Execute">
</form>


<?




function dump_sql_table($sql,$title,$db) {

        $recordSet = &$db->Execute($sql);

	if (!empty($title))
	        print "<hr><H3>$title</H3>";

        print "<TABLE border='1' cellspacing='0' cellpadding='2'><TR>";
	foreach ($recordSet->fields as $key => $value) {
                print "<TH>$key</TH>";
        }
        print "</TR>";

        do {
                print "<TR>";
                foreach ($recordSet->fields as $key => $value) {
                        print "<TD>$value</TD>";
                }
                print "</TR>";

                $recordSet->MoveNext();
        } while (!$recordSet->EOF);

        $recordSet->Close();
        print "</TABLE>";
}

