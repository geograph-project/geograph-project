{assign var="page_title" value="Blog :: $title"}
{assign var="meta_description" value=$content|truncate:200}
{assign var="rss_url" value="/blog/feed.rss"}
{assign var="extra_meta" value="<link rel=\"canonical\" href=\"http://www.geograph.org.uk/blog/`$blog_id`\" />"}
{include file="_std_begin.tpl"}
{literal}<style type="text/css">
.unable,.unable A  {
	color:gray;
}
</style>{/literal}
<script src="{"/sorttable.js"|revision}"></script>


<div style="float:left; position:relative; padding-right:10px;height:40px"><h3 style="margin-top:0px"><a href="/blog/">Geograph Blog</a> ::</h3></div>

<h2 style="margin-bottom:0px" class="nowrap">{$title|escape:"html"}</h2>
<div>By <a title="View profile" href="/blog/?u={$user_id}">{$realname|escape:'html'}</a></div>


<p style="margin-left:auto;margin-right:auto;width:600px;position:relative;margin-bottom:100px;line-height:2em" id="blogcontent">{$content|nl2br|GeographLinks:true|regex_replace:'/(height|width)="\d+"/':'$1="14"'}</p>

<hr/>
{literal}
<!-- AddThis Button BEGIN -->
<div class="addthis_toolbox addthis_default_style ">
<a class="addthis_button_facebook_like" fb:like:layout="button_count"></a>
<a class="addthis_button_tweet"></a>
<a class="addthis_button_google_plusone" g:plusone:size="medium"></a>
<a class="addthis_counter addthis_pill_style"></a>
</div>
<script type="text/javascript" src="http://s7.addthis.com/js/250/addthis_widget.js#pubid=geograph"></script>
<!-- AddThis Button END -->
{/literal}

{if $gridsquare_id}
<div style="float:right; position:relative; padding:5px; border:1px solid gray; ">
	<div style="width:300px; height:250px;" id="mapCanvas">Loading map...</div>
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
        dsq.src = 'http://' + disqus_shortname + '.disqus.com/embed.js';
        (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(dsq);
    })();
{/literal}</script>
<noscript>Please enable JavaScript to view the <a href="http://disqus.com/?ref_noscript">comments powered by Disqus.</a></noscript>
<a href="http://disqus.com" class="dsq-brlink">blog comments powered by <span class="logo-disqus">Disqus</span></a>




{if $lat && $google_maps_api_key}
	<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key={$google_maps_api_key}" type="text/javascript"></script>

	{literal}
	<script type="text/javascript">
	//<![CDATA[
	var map;

	function onLoad() {
		map = new GMap2(document.getElementById("mapCanvas"));
		map.addMapType(G_PHYSICAL_MAP);
		map.addControl(new GSmallMapControl());
		map.addControl(new GMapTypeControl(true));
		{/literal}
		var center = new GLatLng({$lat},{$long});
		{literal}
		map.setCenter(center, 10,G_PHYSICAL_MAP);

		var themarker = new GMarker(center,{clickable: false});
		map.addOverlay(themarker);
	}

	AttachEvent(window,'load',onLoad,false);
	//]]>
	</script>
{/literal}{/if}


<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js" type="text/javascript"></script>
<script src="/js/jquery.topzindex.min.js" type="text/javascript"></script>
{literal}
	<script>
	$(function() {
		$('#blogcontent img[src$=".jpg"]').each(function (i) {
			var bigimage = $(this).attr("src");
			var position = $(this).position();
			$(this).after("<div class='zoomdiv"+i+"'><img class='bigimg' src='"+bigimage+"'/></div>");
			if (i%2 == 0) {
				$("div.zoomdiv"+i).css({ position:'absolute', left: '630px', top: position.top-30 });

				$(this).after("<div class='linkdiv"+i+"'></div>");
				$("div.linkdiv"+i).css({ position:'absolute', left: position.left, top: position.top+16, width: (630-position.left)+'px', height:'1px', borderBottom:'1px solid silver' });

			} else {
				$("div.zoomdiv"+i).css({ position:'absolute', left: '-150px', top: position.top-60 });

				$(this).after("<div class='linkdiv"+i+"'></div>");
				$("div.linkdiv"+i).css({ position:'absolute', left: '-30px', top: position.top-4, width: (44+position.left)+'px', height:'1px', borderBottom:'1px solid silver' });
			}
			$("div.zoomdiv"+i).mouseenter(function () {
				$(this).topZIndex( { increment: 10 } );
			});
			$(this).mouseenter(function () {
				$("div.zoomdiv"+i).topZIndex( { increment: 10 } );
			});
		});
	});
	</script>
{/literal}

{include file="_std_end.tpl"}

