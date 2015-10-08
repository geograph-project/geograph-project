<?

include __DIR__."/database.inc.php";

queryExecute("insert into thumb_md5 SELECT file_id,size,md5sum,filename,file_modified FROM file WHERE class = 'thumb.jpg' AND file_id > (select max(file_id) from thumb_md5)");
#Query OK, 59 rows affected (12.41 sec)
#Records: 59  Duplicates: 0  Warnings: 0

queryExecute("INSERT IGNORE INTO thumb_dup SELECT md5sum,COUNT(*) cnt,'new',0,NOW(),NOW() FROM thumb_md5 GROUP BY md5sum HAVING cnt > 1 ORDER BY NULL");
#Query OK, 0 rows affected (16.80 sec)
#Records: 11228  Duplicates: 11228  Warnings: 0


