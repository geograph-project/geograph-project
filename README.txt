=======================================================================
                      GEOGRAPH TOY README
=======================================================================


A) Introduction
-----------------------------------------------------------------------

This is a basic implementation of Geograph Website code. It doesn't contain any real functions, 
just enough code to test the backend services


B) Requirements
-----------------------------------------------------------------------

* Apache with PHP/5.6
  * ideally: separate file hosting infrastructure
* Mysql 5+
* Manticore, 2.6+
* Redis
* Carrot2 DCS
* TimeGate Proxy
* Ability to run scripts on schedule


C) Directory Structure
-----------------------------------------------------------------------

libs/conf/
  config file for defining server enviroment

libs/geograph/
  geograph specific classes and library code (GPL)
  
libs/smarty/
  Smarty templating library (LGPL)
  
public_html/
  web root - should be served by Apache eg https://toy.geograph.org.uk/

public_html/photos/
  folder that may be served by a separate CDN, doesnt actully need to be 
  hosted locally. eg via separate hostname https://toy-cdn.geograph.org.uk/photos/
  
public_html/templates/basic/
  basic smarty template and supporting files - as other
  site templates are developed, they would go into similar
  directories at this level. All template-specific graphics
  and CSS files are stored here also.

public_html/templates/basic/compiled/
public_html/templates/basic/cache/
  shared template folders
  If mulitple instances of apache, all need to use common folder,
   eg via a Distributed FileSystem

schema/
  mysql database schema

scripts/
  code that needs to be run on a schedule (eg once an hour) 
  
  
D) Installation
-----------------------------------------------------------------------
This software requires PHP 5.6 , and was designed to run on
apache webservers using the apache module.

1. Unpack the files into <basedir>

2. Configure apache with a virtual host for the geograph site using the 
   file in apache/geograph.conf as a guide, e.g.

   <VirtualHost *>
    DocumentRoot <**basedir**>/public_html
    ServerName <**yourdomain**>
    php_value include_path .:<**basedir**>/libs/
    php_value register_globals Off
    
    RewriteEngine on
    RewriteRule /help/(.*) /staticpage.php?page=$1

    ErrorDocument 404 /staticpage.php?page=404
   </VirtualHost>
  
3. Create a new mysql database and create a user with all privileges on it.

4. Initialise the database using schema/database.mysql 
   (e.g. mysql geograph < database.mysql)

5. Setup other backend services manticore Redis etc. 

6. Edit the configuration file with your database credentials. You 
   should also edit other configuration entries such as contact_email. 

7. Restart apache and attempt to access http://<**yourdomain**>/test.php - 
   this will test your installation and report back on anything that must 
   be fixed

8. Once test.php reports success, you're good to go!


