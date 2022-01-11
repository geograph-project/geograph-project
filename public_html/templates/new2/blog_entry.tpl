{assign var="page_title" value="Blog :: $title"}
{assign var="rss_url" value="/blog/feed.rss"}
{include file="_std_begin.tpl"}
{if $blog_id == 272}
	<div style="position:absolute; top:0; left:0; width:100%; height:100%;	background-image:url('https://pbs.twimg.com/media/DHNj1dcXoAAtkiX.jpg'); background-size:cover; opacity:0.1; z-index:-1000"></div>
{/if}
{literal}<style type="text/css">
.unable,.unable A  {
	color:gray;
}
#maincontent {
	position:relative;
}
</style>{/literal}

<breadcrumb>
	<a href="/blog/">Blog Entries</a> &gt; 
	by <a title="View profile" href="/blog/?u={$user_id}">{$realname|escape:'html'}</a> &gt;
</breadcrumb>

<h2 class="nowrap">{$title|escape:"html"}</h2>


<p style="margin-left:auto;margin-right:auto;width:600px;font-size:15px;line-height:24px">{$content|nl2br|GeographLinks:true}</p>

<hr/>
{literal}
<!-- AddThis Button BEGIN -->
<div class="addthis_toolbox addthis_default_style ">
<a class="addthis_button_facebook_like" fb:like:layout="button_count"></a>
<a class="addthis_button_tweet"></a>
<a class="addthis_button_google_plusone" g:plusone:size="medium"></a>
<a class="addthis_counter addthis_pill_style"></a>
</div>
<script type="text/javascript" src="https://s7.addthis.com/js/300/addthis_widget.js#pubid=geograph"></script>
<!-- AddThis Button END -->
{/literal}

{if $gridsquare_id && $lat}
<div style="float:right; position:relative; padding:5px; border:1px solid gray; ">
	<div style="width:450px; height:400px;" id="mapCanvas">Loading map...</div>
	<div style="color:red; background-color:white">Marker only shows grid square</div><br/>
</div>
{/if}

<dl class="picinfo">



<dt>When</dt>
 <dd style="font-size:1.2em">{$published|date_format:"%a, %e %b %Y at %H:%M"}</dd>

{if $gridsquare_id}
<dt>Grid Square</dt>
 <dd><a href="/location.php?gridref={$grid_reference}"><img src="{$static_host}/img/geotag_16.png" width="16" height="16" align="absmiddle" alt="geotagged!"/></a> <a title="Grid Reference {$grid_reference}" href="/gridref/{$grid_reference}">{$grid_reference}</a></dd>
{/if}




{if $image}
<dt>Chosen Photo</dt>
 <dd><div class="img-shadow">
		<a href="/photo/{$image->gridimage_id}">{$image->getThumbnail(213,160)}</a>
		 <div style="font-size:0.7em">
			  <a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a>
			  by <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a>
			  for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>
		</div>
	</div></dd>
{/if}

</dl>

{if $user->user_id == $user_id || $isadmin}
	<p style="clear:both"><a href="/blog/edit.php?id={$blog_id}">Edit this entry</a></p>
{/if}

<br style="clear:both"/>


<div id="disqus_thread"></div>
<script type="text/javascript">{literal}
    /* * * CONFIGURATION VARIABLES: EDIT BEFORE PASTING INTO YOUR WEBPAGE * * */
    var disqus_shortname = 'geograph'; // required: replace example with your forum shortname

    // The following are highly recommended additional parameters. Remove the slashes in front to use.{/literal}
    var disqus_identifier = '{$blog_id}';
    var disqus_url = '{$self_host}/blog/entry.php?id={$blog_id}';

{literal}
    /* * * DON'T EDIT BELOW THIS LINE * * */
    (function() {
        var dsq = document.createElement('script'); dsq.type = 'text/javascript'; dsq.async = true;
        dsq.src = 'https://' + disqus_shortname + '.disqus.com/embed.js';
        (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(dsq);
    })();
{/literal}</script>
<noscript>Please enable JavaScript to view the <a href="http://disqus.com/?ref_noscript">comments powered by Disqus.</a></noscript>
<a href="http://disqus.com" class="dsq-brlink">blog comments powered by <span class="logo-disqus">Disqus</span></a>




{if $lat}
	<link rel="stylesheet" type="text/css" href="https://unpkg.com/leaflet@1.3.1/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.3.1/dist/leaflet.js" type="text/javascript"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.5.0/proj4.js"></script>
	<script type="text/javascript" src="{"/js/Leaflet.MetricGrid.js"|revision}"></script>
        <script type="text/javascript" src="{"/js/mappingLeaflet.js"|revision}"></script>

	<script type="text/javascript">
	//<![CDATA[
	var map = null ;
	var issubmit = false;
	var static_host = '{$static_host}';

	{literal}
                                        function loadmap() {
						{/literal}
                                                var point = [{$lat},{$long}];
						{literal}
                                                var newtype = readCookie('GMapType');

                                                mapTypeId = firstLetterToType(newtype);

                                                map = L.map('mapCanvas',{attributionControl:false}).setView(point, 8).addControl(
                                                        L.control.attribution({ position: 'bottomright', prefix: ''}) );

                                                setupOSMTiles(map,mapTypeId);

                                                map.on('baselayerchange', function (e) {
                                                        if (e.layer && e.layer.options && e.layer.options.mapLetter) {
                                                                var t = e.layer.options.mapLetter;
                                                                createCookie('GMapType',t,10);
                                                        } else {
                                                                console.log(e);
                                                        }
                                                });

						 createMarker(point);
                                        }
                                        AttachEvent(window,'load',loadmap,false);

	//]]>
	</script>
{/literal}{/if}

{include file="_std_end.tpl"}

