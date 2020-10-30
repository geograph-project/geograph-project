#!/bin/sh

############

/usr/bin/php $BASE_DIR/scripts/build_sitemap.php
###/usr/bin/php $BASE_DIR/scripts/build_sitemap.php --normal=0 --images=1 --secret=$CONF_SITEMAP_SECRET --per=10000

/usr/bin/php $BASE_DIR/scripts/build_usersitemap.php

/usr/bin/php $BASE_DIR/scripts/build_contentsitemap.php

/usr/bin/php $BASE_DIR/scripts/build_snippetsitemap.php

if test "$CLI_HTTP_HOST" = 'www.geograph.org.uk'; then
	/usr/bin/php $BASE_DIR/scripts/build_sitemap.php  --config=www.geograph.ie --ri=2 --suffix=.ie
	###/usr/bin/php $BASE_DIR/scripts/build_sitemap.php  --config=www.geograph.ie --ri=2 --suffix=.ie --normal=0 --images=1 --secret=$CONF_SITEMAP_SECRET --per=10000

	/usr/bin/php $BASE_DIR/scripts/build_usersitemap.ie.php  --config=www.geograph.ie
fi

#this pings ALL the sitemaps rebuilt above
/usr/bin/php $BASE_DIR/scripts/ping-sitemaps.php execute

############

/usr/bin/php $BASE_DIR/scripts/build_userhtml.php

############

#these dont update that often

#rm -f $BASE_DIR/public_html/kml/sitemap*.xml.gz
#/usr/bin/php $BASE_DIR/scripts/build_kmlsitemap.php

############

#rm -f $BASE_DIR/public_html/sitemap/sitemap*.xml.gz
#/usr/bin/php $BASE_DIR/scripts/build_htmlsitemap.php

############

echo ""
echo ""
