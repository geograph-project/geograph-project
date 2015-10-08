<?

include __DIR__."/database.inc.php";


$size = 100000;
$begin = 0;
$final = getOne("SELECT MAX(file_id) FROM file");

for($start = $begin; $start < $final; $start+=$size) {
        $end = $start+$size-1;
        $where = "file_id BETWEEN $start AND $end";

	print "$i ";

	queryExecute("insert into thumb_md5 SELECT file_id,size,md5sum,filename,file_modified FROM file WHERE class = 'thumb.jpg' AND $where");
	$rows = mysql_affected_rows();
	print " [$rows]\n";
	$i++;
}

print "\n";

