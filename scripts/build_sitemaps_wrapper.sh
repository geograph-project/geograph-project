#!/bin/sh
#the following is based on one used on channel-islands.geographs.org - ideally could do with making general. 


sudo -u www-data rm -f /var/www/channel_live/public_html/sitemap/root/sitemap*.xml.gz

sudo -u www-data /usr/bin/php /var/www/channel_live/scripts/build_sitemap.php --dir=/var/www/channel_live

GET http://www.google.com/webmasters/tools/ping?sitemap=http%3A%2F%2Fchannel-islands.geographs.org%2Fsitemap.xml


sudo -u www-data rm -f /var/www/channel_live/public_html/kml/sitemap*.xml.gz

sudo -u www-data /usr/bin/php /var/www/channel_live/scripts/build_kmlsitemap.php --dir=/var/www/channel_live

GET http://www.google.com/webmasters/tools/ping?sitemap=http%3A%2F%2Fkml.channel.geographs.org%2Fkml%2Fsitemap.xml


sudo -u www-data rm -f /var/www/channel_live/public_html/sitemap/sitemap*.xml.gz

sudo -u www-data /usr/bin/php /var/www/channel_live/scripts/build_htmlsitemap.php --dir=/var/www/channel_live

GET http://www.google.com/webmasters/tools/ping?sitemap=http%3A%2F%2Fchannel-islands.geographs.org%2Fsitemap%2Fsitemap.xml

echo ""
echo ""
echo ""
