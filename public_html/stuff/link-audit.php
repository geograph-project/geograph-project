<?

require_once('geograph/global.inc.php');
init_session();

$db = GeographDatabaseConnection(false);


if (!empty($_GET['audit'])) {

	if (!empty($_POST['result'])) {
		$db->Execute("UPDATE tmp_link_examples
		SET `result` = ".$db->Quote($_POST['result']).",
		user_id = ".intval($USER->user_id)."
		WHERE gridimage_link_id = ".intval($_POST['gridimage_link_id']));
	}

	$row = $db->getRow("SELECT gridimage_link_id,gridimage_id,url,comment
		FROM gridimage_search inner join tmp_link_examples t using (gridimage_id)
		inner join gridimage_link using (gridimage_link_id,gridimage_id)
		where last_found > upd_timestamp
		and next_check < '9999-00-00'
		AND result = ''
		GROUP BY gridimage_id
		ORDER BY crc32(url) LIMIT 1");

if (empty($row))
	die("no more to check!\n");

print "<table cellspacing=0 cellpadding=3 border=1>";
        print "<tr>";
        print "<td>{$row['gridimage_id']}</td>";
        print "<td style=\"font-family:monospace;max-width:40vw;overflow:hidden;\">".nl2br(htmlentities($row['comment']))."</td>";
        print "<td>".GeographLinks(htmlentities2($row['comment']));
print "</table>";

print "Extracted Link:<ul>";

$bits = explode($row['url'],$row['comment']);
if (count($bits) > 1)
print "<li><tt>".htmlentities(substr2($bits[0],-10))."<span style=\"background-color:yellow;border-bottom:1px solid black\">".htmlentities(storten($row['url']))."</span>".htmlentities(substr2($bits[1],0,10))."</tt><br><br></li>";

print "<li><tt style=background-color:yellow>".htmlentities($row['url'])."</tt></li>";

//print "<li><tt style=background-color:yellow>".urlencode($row['url'])."</tt></li>";
print "</ul>";

	?>
	<form method=post>
		<input type=hidden name=gridimage_link_id value="<? echo $row['gridimage_link_id']; ?>">
		Was the above link extracted ok? Does the highlighted portion stop in the right place?
		<input type=submit name="result" value="Yes">
		<input type=submit name="result" value="No">
		<input type=submit name="result" value="Dont Know"><br>
		In particular, if the extracted link contains brackets, make sure they are meant to be part of hte link, and not punctuation forming the text around the link!
	</form>

	(there may be other links in the example description, ignore them, only asking if <b>this specific link</b> was turned into link correctly!)

	<?
	exit;
}





$limit = 200;
if (!empty($_GET['limit']))
	$limit = intval($_GET['limit']);

$result = mysql_query("SELECT t.gridimage_id,comment,result,t.url
FROM gridimage_search inner join tmp_link_examples t using (gridimage_id)
inner join gridimage_link using (gridimage_link_id,gridimage_id)
where last_found > upd_timestamp
and next_check < '9999-00-00'
GROUP BY gridimage_id
ORDER BY crc32(t.url) LIMIT $limit");

?>
<p>
Here is a pretty much random selection of links from descriptions, showing the various unusual charactors we deal with.
This includes, a selection of charactors following the link. Some of these our current linking code handles, but a select it does not.</p>
<p>Note: does include also a handful of entirely vanilla links that should be linked without issue (for comparsion!)</p>
<?

print "<table cellspacing=0 cellpadding=3 border=1>";
while ($row = mysql_fetch_assoc($result)) {
	if ($row['result'] == 'Yes') {
		print "<tr style=background-color:lightgreen>";
	} elseif ($row['result'] == 'No') {
		print "<tr style=background-color:pink>";
	} else {
		print "<tr>";
	}
	print "<td rowspan=2><a href=\"/photo/{$row['gridimage_id']}\">{$row['gridimage_id']}</a></td>";
	print "<td colspan=2>".htmlentities($row['url'])."</td>";
	print "<tr>";
	print "<td style=\"font-family:monospace;max-width:40vw;overflow:hidden;\">".nl2br(htmlentities($row['comment']))."</td>";
	print "<td>".GeographLinks(htmlentities2($row['comment']));
}

print "</table>";



/*


create table tmp_link_examples select gridimage_link_id,gridimage_id,url,substring(substring_index(comment,url,-1),1,4) as after from gridimage_link inner join gridimage_search using (gridimage_id) where next_check < '2023-01-01' and parent_link_id =0 group by substring(substring_index(comment,url,-1),1,1);

... should add AND comment like concat('%',url,'_%') .. so only finds comments, that DO contain the link and something after it!
... also AND last_found > upd_timestamp (just so dont extract links now deleted!)

alter table tmp_link_examples modify `after` varchar(4) null;

insert into tmp_link_examples select gridimage_link_id,gridimage_id,url,null as after from gridimage_link where url rlike '[^\\w]$' and next_check < '2023-01-01' and gridimage_id > 0 and parent_link_id =0 group by substring(url,-1);

alter table tmp_link_examples add chars varchar(10) default null;

insert into tmp_link_examples select gridimage_link_id,gridimage_id,url,null as after,regexp_replace(url,'[\\w\\.\\/\:+-]','') as chars from gridimage_link where url rlike '[^\\w]$' and next_check < '2023-01-01' and gridimage_id > 0 and parent_link_id =0 and url rlike '[^\\w\\.\\/\:+-]' group by substring(regexp_replace(url,'[\\w\\.\\/\:+-]',''),1,2);


alter table tmp_link_examples add result varchar(30) not null, add user_id int unsigned, add updated timestamp not null on update current_timestamp();


... then can
insert into tmp_link_examples select gridimage_link_id,gridimage_id,url,substring(substring_index(comment,url,-1),1,4) as after,'' as chars,'' as result,0 as user_id,'0000-00-00' as `updated` from gridimage_link inner join gridimage_search using (gridimage_id) where next_check < '2023-01-01' and parent_link_id =0 and comment like concat('%',url,'_%') and last_found > upd_timestamp group by substring(substring_index(comment,url,-1),1,1);

alter ignore table tmp_link_examples add unique(gridimage_link_id);


*/

function storten($in) {
	if (strlen($in) > 50)
		return substr($in,0,10)."...".substr($in,-10);
	return $in;
}

function substr2($in,$offset,$length=null) {
	if (strlen($in) > @max(abs($offset),$length)) {
		//sending null, is NOT the same as omitting it!
		//If length is given and is 0, false or null, an empty string will be returned.

		return @($offset<0?'...':'').substr($in,$offset,$length?$length:strlen($in)).($length>$offset?'...':'');
	}
	return $in;
}
