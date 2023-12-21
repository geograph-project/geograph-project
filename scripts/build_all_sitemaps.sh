#!/bin/sh

############

#/usr/bin/php $BASE_DIR/scripts/build_sitemap.php
/usr/bin/php $BASE_DIR/scripts/build_sitemap.php --normal=0 --images=1 --secret=$CONF_SITEMAP_SECRET --per=10000 --start=620

/usr/bin/php $BASE_DIR/scripts/build_usersitemap.php

/usr/bin/php $BASE_DIR/scripts/build_contentsitemap.php

/usr/bin/php $BASE_DIR/scripts/build_snippetsitemap.php

/usr/bin/php $BASE_DIR/scripts/build_photosetsitemap.php --ri=1

if test "$CLI_HTTP_HOST" = 'www.geograph.org.uk'; then
	#/usr/bin/php $BASE_DIR/scripts/build_sitemap.php  --config=www.geograph.ie --ri=2 --suffix=.ie
	/usr/bin/php $BASE_DIR/scripts/build_sitemap.php  --config=www.geograph.ie --ri=2 --suffix=.ie --normal=0 --images=1 --secret=$CONF_SITEMAP_SECRET --per=10000 --start=30

	/usr/bin/php $BASE_DIR/scripts/build_usersitemap.ie.php  --config=www.geograph.ie

	/usr/bin/php $BASE_DIR/scripts/build_snippetsitemap.ie.php  --config=www.geograph.ie

	/usr/bin/php $BASE_DIR/scripts/build_photosetsitemap.php  --config=www.geograph.ie --ri=2 --suffix=.ie
fi

#this pings ALL the sitemaps rebuilt above
/usr/bin/php $BASE_DIR/scripts/ping-sitemaps.php --execute

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
