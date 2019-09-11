=======================================================================
                      GEOGRAPH TOY README
=======================================================================


A) Introduction
-----------------------------------------------------------------------

This is a basic implementation of Geograph Website code. 
It's not a proper user-facing website, just enough code to test the backend services.
We use this internaly to test our servers are capable of running the real Geograph website.


Note: deliberately first implemented using PHP5 functions (like mysql_*) and then upgraded to php7
		https://svn.geograph.org.uk/viewsvn/?do=diff&project=geograph&path=/branches/toy/&rev=9002&oldrev=9001

and using standard POSIX filesystem - so it runs on the current servers. 
... later on, will 'upgrade' this to use a Amazon S3 implementation (for files hosting). 


B) Requirements
-----------------------------------------------------------------------

* Apache Webserver(s)
  * If have multiple servers, will need shared file system (NFS/EFS etc)
* PHP/5.6 (tested as Apache module)
  * PHP Extensions: pcre zlib bz2 iconv mbstring session posix apache2handler gd exif json memcache mysql mysqli mhash apc curl
* Mysql 5.6/5.7 Master server (needs intergrated backup)
  * Note: ONLY_FULL_GROUP_BY must NOT be in sql_mode for now. 
* Manticore, 2.6+/3 (doesn't need to be backed up, data compiled from database server)
  * Note we do need some custom pluings. Source: http://svn.geograph.org.uk/svn/modules/trunk/sphinx/
* Redis server (doesn't need to be backed up, transient non-critical data. But does eed to be highly available)
* Ability to run scripts on schedule
* imagemagick, jpegtran, and exiftool command-line tools
* ability for the webserver to send emails, notably via the php mail() function

C) Ideally
-----------------------------------------------------------------------
* Separate file hosting+serving infrastructure  (best if static files, images etc served from second system, optimized for it)
  * Could be something like Amazon S3 (currently would need POSIX compatiblity layer, like s3fs), or just NFS/EFS etc perhaps with Varnish
* If possible https://github.com/mysqludf/lib_mysqludf_preg insalled on the MySQL servers. 
* MySQL Slave  (so long running queries can run on slave - but can cope with single master)
* Carrot2 DCS  (doesnt need highly available, it's used by overnight cron tasks for example)
* TimeGate Proxy  (doesnt need highly available, it's used by overnight cron tasks for example)


D) Directory Structure
-----------------------------------------------------------------------

libs/conf/
  config file for defining server enviroment

libs/geograph/
  geograph specific classes and library code (GPL)
    
public_html/
  web root - should be served by Apache eg https://toy.geograph.org.uk/

public_html/photos/
  folder that may be served by a separate CDN, doesnt actully need to be 
  hosted locally. eg via separate hostname https://toy-cdn.geograph.org.uk/photos/
  
public_html/templates/basic/
  basic smarty template and supporting files

public_html/templates/basic/compiled/
public_html/templates/basic/cache/
  shared template folders
  If mulitple instances of apache, all need to use common folder,
   eg via a Distributed/Shared FileSystem, NFS, EFS etc. 

schema/
  mysql database schema

scripts/
  code that needs to be run on a schedule (eg once an hour) 
  
  
E) Installation
-----------------------------------------------------------------------
This software tested with PHP 5.6 and PHP 7.2, and was designed to run on
apache webservers using the apache module.

--------------

1. Download the files from repository, if have subversion installed, easiest with a simple checkout command. 

svn checkout https://svn.geograph.org.uk/svn/branches/toy/ geograph_toy/

--------------

2. Configure apache with a virtual host for the geograph site using the 
   file in config/apache-vhost.conf as a guide, e.g.

   <VirtualHost *>
    DocumentRoot <**basedir**>/public_html
    ServerName <**yourdomain**>
    php_value include_path .:<**basedir**>/libs/
    php_value register_globals Off
    
    RewriteEngine on
    RewriteRule /help/(.*) /staticpage.php?page=$1

    ErrorDocument 404 /staticpage.php?page=404
   </VirtualHost>

    the server should be capable of running PHP. see also config/php.ini for typical values needed in php.ini.

--------------
  
3. Create a new mysql database and create a user with all privileges on it. (make a note of these for later) 

      if have a master+slave setup (recommeded!) then suggested to create a second database, that is configured to be excluded from replication. (but optional) 

      see schema/create.txt for example commands to run on command line.

	NOTE: if needbe, Remove ONLY_FULL_GROUP_BY from sql_mode

	SET GLOBAL sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));
 

--------------

4. Initialise the database using schema/database.mysql into the master. (e.g. mysql geograph < database.mysql)

	Also import schema/image_dump.mysql - which is sample dataset to test more advanced queries

	and schema/gridimage_funny.mysql - which is sample dataset of titles with extended non-ascii charactors, to test charset conversions

--------------

5. Setup Manticore (based on Sphinx)  Currently use, Server version: 2.6.2 later versions MAY work

	use config/sphinx.conf as a start. 
	... will need the mysql database credentials. Ideally connect to slave/read-replica

	Note: we use some custom Sphinx Plugins, which will need compiling. 
	Source: http://svn.geograph.org.uk/viewsvn/?do=browse&project=geograph&path=/modules/trunk/sphinx/
	But this repository contains recompiled .so files for use. 

	So will need to copy the plugins/ folder somewhere, as well the sphinxql_state.sql file. The locations of these are defined in the config file. 

	Also need a empty folder for the 'binlog' and a folder to create log files, and the pid file. 

	first build the index with indexer

	/path/to/bin/indexer --config /path/to/sphinx.conf toy

	once setup config file and all other required files, start the deamon

	/path/to/bin/searchd

--------------

6. Setup Redis Daemon. No special setup is required, just need its ip/port for the config, and need one unique DB to use for the test. 

--------------

7. Setup Carrot2 DCS. Its stateless, has no persistnat data. Again make note of its IP/port
	https://project.carrot2.org/download-dcs.html

--------------

8. Setup MemGator. Example config is provided in the cron example (but there are better ways of setting that up!)
	https://github.com/oduwsdl/MemGator
	... also a slightly refined archives.json is included. 

--------------

9. Edit the configuration file with your services details (from steps above!) 
 
	copy libs/conf/example.conf.php to /libs/conf/www.domain.com.conf.php and edit

	where the HTTP hostname is part of filename, code literally does 
	require('conf/'.$_SERVER['HTTP_HOST'].'.conf.php');

--------------

10. Setup cronjobs - use config/crontab as a starting point

--------------

11. Restart apache and attempt to access http://<**yourdomain**>/test.php - 
	this will test your installation and report back on anything that must be fixed

--------------

12. If setting up Amazon S3

	a) Put your AWS creditials in conf file awsAccessKey/awsSecretKey - NOTE: recommended to create a IAM user with S3 read/write access, rather than using root creditials
	b) Create a bucket, using hostname style. eg "toy-photos.geograph.org.uk";
		the bucket should not have 'block public access' set.
	c) setup DNS to point the hostname at S3 (see https://docs.aws.amazon.com/AmazonS3/latest/dev/VirtualHosting.html ) 
		toy-photos.geograph.org.uk CNAME toy-photos.geograph.org.uk.s3.amazonaws.com
	d) use the bucket name in $CONF['awsS3Bucket']
	e) AND in $CONF['STATIC_HOST'];
	f) Note: it normal for accessing URLs to redirect to full hostname intially. I think 24 hours or so
		eg will redirect http://toy-photos.geograph.org.uk.s3-eu-west-1.amazonaws.com/photos/00/000070.jpg
		until Amazon sets up DNS for your cname to point to the right region
	g) Tip: can setup a S3 lifecycle to automatically delete test files after say 10 days. 

--------------

13. If have multiple webserver instances, Smarty requires a 'common' folder to store complied and cached templates file. 

	So need to use a distributed filesystem. Works over NFS on current servers. EFS has been lightly tested. 

	a) Create the EFS filesystem in AWS control panel
	b) mount it in the local filesystem somewhere convenient
	c) create folders for complied and cached folders, and create symlinks to them, for example

		mkdir /mnt/efs/toy-compiled/
		chmod a+w /mnt/efs/toy-compiled/
		rm -Rf public_html/templates/basic/compiled/
		ln -s /mnt/efs/toy-compiled/ public_html/templates/basic/compiled

	d) repeat for cache

--------------

14. Once test.php reports success, you're good to go!


