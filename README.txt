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



Directory Structure
===================

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
  
