=======================================================================
                             GEOGRAPH README
=======================================================================

A) History
B) Introduction
C) Directory Structure
D) Installation
E) Getting Started
F) Adapting for other countries


A) History
-----------------------------------------------------------------------
v0.10 - 17 Mar 2005 - first working beta, basically a CVS snapshot with 
                      code still in development removed.


B) Introduction
-----------------------------------------------------------------------

GeoGraph is web based project to collect and reference geographically
representative images of every square kilometer of the British Isles, but
the software is being designed to allow it to be adapted for similar
projects in other countries

For more info about the GeoGraph software project, 
visit http://geograph.sourceforge.net/

For more info about the UK GeoGraph project
visit http://www.geograph.co.uk/

The GeoGraph software is licenced using the GNU General Public Licence,
see LICENCE.txt for details.


C) Directory Structure
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
  
  
D) Installation
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


E) Getting Started
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


F) Adapting for other countries
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

