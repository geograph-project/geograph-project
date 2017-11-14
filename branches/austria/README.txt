=======================================================================
                             GEOGRAPH README
=======================================================================

A) Introduction
B) Directory Structure
C) Installation
D) Getting Started
E) Adapting for other countries



A) Introduction
-----------------------------------------------------------------------

GeoGraph is web based project to collect and reference geographically
representative images of every square kilometer of the British Isles, but
the software is being designed to allow it to be adapted for similar
projects in other countries

For more info about the UK GeoGraph project
visit http://www.geograph.org.uk/

The GeoGraph software is licenced using the GNU General Public Licence,
see LICENCE.txt for details.


B) Directory Structure
-----------------------------------------------------------------------

apache/
  example apache vhost configuration for the geograph project

libs/adodb/
  adodb database library (LGPL)
  download the latest release from http://adodb.sourceforge.net
  
libs/geograph/
  geograph specific classes and library code (GPL)
  
libs/smarty/
  Smarty templating library (LGPL)
  download the latest release from http://smarty.php.net
  
public_html/
  web root
  
public_html/templates/basic/
  basic smarty template and supporting files - as other
  site templates are developed, they would go into similar
  directories at this level. All template specific graphics
  and CSS files are stored here also.

schema/
  mysql database schema
  
  
C) Installation
-----------------------------------------------------------------------
This software requires PHP 4.3 or higher, and was designed to run on
apache webservers using the php4 module. Y

1. unpack the files into <basedir>

2. configure apache with a virtual host for the geograph site using the 
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
   
   
   
  <VirtualHost *>

  DocumentRoot <**basedir**>/public_html
    ServerName <**yourdomain**>
    
    #php config
    php_value include_path .:<**basedir**>/libs/
    php_value register_globals Off
    php_value upload_max_filesize 8M
    php_value arg_separator.output &amp;
    php_value session.use_trans_sid  1
    
    #turn off indexes
    <Directory <**basedir**>/public_html>      
        Options -Indexes
    </Directory>
    
    RewriteEngine on
    
    RewriteRule /help/([^/]*) /staticpage.php?page=$1 [qsa]
    RewriteRule /gridref/(.*) /browse.php?gridref=$1 [qsa]
    RewriteRule /api/.* /restapi.php [qsa]
    RewriteRule /reg/([^/]+)/(.*) /register.php?u=$1&confirm=$2 [qsa]
    RewriteRule /photo/([0-9]+) /view.php?id=$1 [qsa]
    RewriteRule /list/([A-Z]{2}) /list.php?square=$1 [qsa]
    RewriteRule /explore/places/([0-9]?)/?(\w*)/?$ /explore/places.php?ri=$1&adm1=$2 [qsa,r]
    RewriteRule /map/(.*) /mapbrowse.php?t=$1 [qsa]

    RewriteRule /user/([^\/]*)/all/? /profile.php?user=$1&all=1
    RewriteRule /user/([^\/]*)/? /profile.php?user=$1

    RewriteRule /feed/recent/?([^/]*) /syndicator.php?format=$1 [qsa]
    RewriteRule /feed/results([0-9]*)/([0-9]+)/?([^/]*)/? /syndicator.php?page=$1&i=$2&format=$3 [qsa]

    RewriteRule /discuss/topic([0-9]+) /discuss/?action=vthread&topic=$1 [qsa,r]
    RewriteRule /discuss/forum([0-9]+) /discuss/?action=vtopic&forum=$1 [qsa,r]
    RewriteRule /discuss/feed/recent/?([^/]*) /discuss/syndicator.php?format=$1 [qsa]
    RewriteRule /discuss/feed/forum([0-9]+)/?([^/]*) /discuss/syndicator.php?forum=$1&format=$2 [qsa]    
    
    
    #rewrite imagemap clicks as regular urls - must do this otherwise
    #php's use_trans_sid will break the urls
    RewriteCond %{QUERY_STRING} (.+)\?([0-9]+),([0-9]+)$
    RewriteRule /mapbrowse.php /mapbrowse.php?x=%2&y=%3&%1 


    ErrorDocument 404 /staticpage.php?page=404

  </VirtualHost>


3. create a new mysql database and create a user with all privileges on it.

4. initialise the database using schema/geograph.mysql 
   (e.g. mysql geograph < geograph.mysql)

5. Copy libs/conf/www.exampledomain.com.conf.php to 
   libs/conf/<**yourdomain**>.conf.php

6. Edit this configuration file with your database credentials. You 
   should also edit other configuration entries such as contact_email, 
   register_confirmation_secret and photo_hashing_secret.

7. Restart apache and attempt to access http://<**yourdomain**>/test.php - 
   this will test your installation and report back on anything that must 
   be fixed

8. Once test.php reports success, you're good to go!


D) Getting Started
-----------------------------------------------------------------------

Register as a user then use the mysql command line to grant that user 
admin privileges, e.g.

  update user set rights='basic,admin' 
  where email='your.email@yourdomain.com';

Now you can log in and access administrative functions of the site. 

One of the first tasks is to initialise the gridsquare table with valid 
gridsquares by using a 1km-per-pixel greyscale PNG. Run the 
admin/gridbuilder.php script to perform this task.

Once that is done, the system is ready to accept photographs.


E) Adapting for other countries
-----------------------------------------------------------------------

The code as supplied is ready for the British Isles, and we're happy to
help make changes to support other geographical areas. At the very least, 
you'll need to do the following:

1. Copy the templates/basic directory to templates/<**country**> and 
   edit the logo graphics and any other British Isles specific text

2. Change the configuration file to specify the new template name, e.g.
   $CONF['template']='<**country**>';

3. Clear the gridprefix table and fill it with the grid square prefixes 
   used in your region

4. Create a 1km-per-pixel greyscale PNG of your region, where white is sea 
   and black is land, and shades of grey represent squares with varying 
   degrees of land present, clear the gridsquare table and run the 
   admin/gridbuilder.php script to initialise the gridsquare table.

