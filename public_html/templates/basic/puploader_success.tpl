{assign var="page_title" value="Upload Results"}
{include file="_std_begin.tpl"}
{literal}<style type="text/css">


</style>{/literal}
{dynamic}
<h2>Image Upload <sub>at {$smarty.now|date_format:"%Y-%m-%d %H:%M:%S"}</sub></h2>
<p>Here is the results of the latest upload:</p>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>

<div class="interestBox" style="background-color:lightgrey">
<dl class="picinfo">
{foreach from=$status key=key item=result}
	<dt>{$filenames.$key|escape:"html"}</dt>
	{if strpos($result,'ok:') === 0}
		<dd>Your photo has identification number [<a href="/photo/{$result|replace:'ok:':''}">{$result|replace:'ok:':''}</a>]</dd>
<div id="points{$result|replace:'ok:':''}"></div>

{literal}
<script type="text/javascript">
$(function(){
	var imageid = {/literal}{$result|replace:'ok:':''}{literal};
        $.getJSON("/stuff/points.json.php?id="+imageid,null,function(data) {
                if (data && data.error) {

                } else if (data && data.length> 0) {
                        $('#points'+imageid).html("<b>Provisional Point(s) for this image:</b><ul></ul>(these are not confirmed, it may change when the image is moderated");
                        $.each(data,function(index,item) {
                                $('#points'+imageid+' ul').append('<li>'+item+'</li>');
                        });
                }
        });
});
</script>
{/literal}
	{else}
		<dd style="background-color:red">{$result|escape:"html"}</dd>
	{/if}
{/foreach}
</dl>
</div>


{if $nofrills}
	<p><a href="/submit-nofrills.php?letmein=1{if $grid_reference}#gridref={$grid_reference|escape:"url"}{/if}">Submit another image</a></p>
{elseif $submit2}
	{if $display == 'tabs'}
	<p><a href="/submit2.php?display=tabs{if $grid_reference}#gridref={$grid_reference|escape:"url"}{/if}">Submit another image (Tabs)</a>

	(<a href="/submit2.php{if $grid_reference}#gridref={$grid_reference|escape:"url"}{/if}">None Tabs based</a>) 
	{else}
	<p><a href="/submit2.php{if $grid_reference}#gridref={$grid_reference|escape:"url"}{/if}">Submit another image</a>

	(<a href="/submit2.php?display=tabs{if $grid_reference}#gridref={$grid_reference|escape:"url"}{/if}">Tabs based</a>) 
	{/if}
	(<a href="/submit2.php?multi=true{if $grid_reference}#gridref={$grid_reference|escape:"url"}{/if}">Continue via Multi-Upload</a>)</p>
{/if}
<ul>
<li><a href="/submissions.php" rel="nofollow">Edit My Recent Submissions</a></li>
</ul>

{/dynamic}


<br/><hr/><br/>

{if $news}
	<b>Latest News</b>
	<ol>
	{foreach from=$news item=newsitem}
		<li>{if $newsitem.days < 4}<b>{/if}<a href="/discuss/index.php?action=vthread&amp;topic={$newsitem.topic_id}" title="{$newsitem.post_text|escape:'html'}">{$newsitem.topic_title}</a></b> <small>{$newsitem.topic_time|date_format:"%a, %e %b"} ({$newsitem.days} days ago)</small></li>
	{/foreach}
	</ul>

	<br/><hr/><br/>
{/if}

<p><i>All images assigned an ID number have been successfully uploaded. Please do not resubmit those images, only the failures.</i></p>

{include file="_std_end.tpl"}
