{include file="_basic_begin.tpl"}
<style>{literal}
body {overflow:hidden;}
{/literal}</style>
<table cellspacing=0 cellpadding=0 border=0 width="100%" onclick="window.parent.location.href='http://www.geograph.org.uk/photo/{$image->gridimage_id}';" style="cursor:pointer">
	<tr>
		<td valign="top" align="center" width=240 style="padding:3px">
	                {$image->getThumbnail(213,160)}<br><br>
<small><b>&nbsp; &copy; Copyright <a title="View profile" href="http://{$http_host}{$image->profile_link}" target="_blank">{$image->realname|escape:'html'}</a></small>
		</td>
		<td valign="top" width="75%" style="padding:4px;">
  			<div style="{if $image->title|strlen < 40}font-size:2.3em{else}font-weight:bold;font-size:1.5em{/if};margin-bottom:3px;background-color:#eee;padding:1px;">{$image->title|escape:'html'}</div>

{if $overview}
  <div style="float:right; position:relative;margin-right:10px;">
	{include file="_overview.tpl"}
  </div>
{/if}

			<div>in <b>{$image->grid_reference|escape:'html'}</b>, taken on <b>{$image_taken}</b>, by <b>{$image->realname|escape:'html'}</b></div>

			{if 0 && $image->subject_gridref}<div>Location: {$image->subject_gridref}</div>{/if}

			{if $image->tags}<small>Tags: [{$image->tags|replace:'?':'] ['}]</small>{/if}

			{if $image->comment}
				<div style="font-size:0.8em;padding:10px">
					{$image->comment|escape:'html'|nl2br|geographlinks}
				</div>
			{/if}

		</td>
		<td valign="top">

{if $rastermap->enabled}
	{$rastermap->getImageTag($image->subject_gridref)}

	{$rastermap->getScriptTag()}
{else}
	<div class="rastermap" style="width:{$rastermap->width}px;height:{$rastermap->width}px;position:relative">
		Map coming soon...
	</div>
{/if}

		</td>
	</tr>
</table>

{if $rastermap->enabled}
        {$rastermap->getFooterTag()}
{/if}

{include file="_basic_end.tpl"}
