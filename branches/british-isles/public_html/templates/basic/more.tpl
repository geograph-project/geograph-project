{dynamic}
{assign var="page_title" value="Download Image"}
{include file="_std_begin.tpl"}

<a name="top"></a>
{if $user->user_id == $image->user_id}
<div style="float:right;position:relative"><a href="/resubmit.php?id={$image->gridimage_id}">Upload a even larger version</a></div>
{/if}

<div style="float:left; position:relative; padding-right:10px;"><h2><a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img 
alt="Creative Commons Licence [Some Rights Reserved]" src="{$static_host}/img/somerights20.gif" align="top" /></a> <a href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> : </h2></div>

<h2 style="margin-bottom:0px" class="nowrap"><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a></h2>
<div>by <a title="View profile" href="{$self_host}{$image->profile_link}">{$image->realname|escape:'html'}</a></div>

<br style="clear:both;"/>

<div class="photoguide" style=" ">
	<div style="float:left;width:213px">
		<a title="view full size image" href="/photo/{$image->gridimage_id}">
		{$image->getThumbnail(213,160)}
		</a>
	</div>
	<div style="float:left;padding-left:20px; width:400px;">
		<div class="caption"><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a> for <a href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a></div>
		<span style="font-size:0.7em">{$image->comment|escape:'html'|nl2br|geographlinks|default:"<tt>no description for this image</tt>"}</span><br/>
		<br/>
		<small><b>&nbsp; &copy; Copyright <a title="View profile" href="{$self_host}{$image->profile_link}">{$image->realname|escape:'html'}</a> and  
		licensed for reuse under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">Creative Commons Licence</a></b></small>
	</div>
	
	<br style="clear:both"/>
</div>


<div style="padding:20px">

<h2>The following sizes of images are available for download:</h2><br/>
<div class="interestBox">
        <img src="/templates/basic/img/icon_alert.gif" alt="Alert" width="18" height="16" align="left" style="margin-right:10px"/>


Note: all sizes are <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">Creative Commons Licensed</a>, and any reuse needs to credit <a title="View profile" href="{$self_host}{$image->profile_link}">{$image->realname|escape:'html'}</a>.

Also our <a href="/reuse.php?id={$image->gridimage_id}">Reuse</a> page will tell you more about how to credit images.
</div>

{assign var="size" value=$image->_getFullSize()}

{assign var="preview_url" value=$image->_getFullpath(true,true)}

{assign var="preview_width" value=$size.0}
{assign var="preview_height" value=$size.1}
{if $image->original_width}
	{assign var="original_width" value=$image->original_width}
	{assign var="original_height" value=$image->original_height}
	{if $image->original_width > 4000}
		{math equation="o/320" o=$original_width assign="ratio"}
	{else}
		{math equation="o/180" o=$original_width assign="ratio"}
	{/if}

       {if $ratio > 10}
                 {math equation="o/640" o=$original_width assign="ratio"}
       {/if}

	<p>Click a thumbnail to download the JPEG file...</p>

	<p>&middot; The 'Stamped' version which includes the Create Commons reference and Contributor attribution, written onto the image. Making it easy to comply with the creative commons licence. <a href="/stamp.php?id={$image->gridimage_id}">More Stamping options...</a></p>

{else}
	{assign var="ratio" value=1}
{/if}

			<table style="font-weight:bold;text-align:center" cellspacing="0" border="1" bordercolor="#cccccc" cellpadding="0">

				<tr>
				
					<td valign="top"><div class="interestBox">{$preview_width} x {$preview_height}</div><br/>
					<a href="{$tile_host}/stamp.php?id={$image->gridimage_id}&title=on&gravity=SouthEast&hash={$image->_getAntiLeechHash()}&download=1">Stamped</a>
					</td>
				
				{if $image->altUrl != "/photos/error.jpg"}
					{if $original_width>$original_height}
						{assign var="resized_width" value=640}
						{math assign="resized_height" equation="round(dw*sh/sw)" dw=$resized_width sh=$original_height sw=$original_width}
					{else}
						{assign var="resized_height" value=640}
						{math assign="resized_width" equation="round(dh*sw/sh)" dh=$resized_height sh=$original_height sw=$original_width}
					{/if}
					
					<td valign="top"><div class="interestBox">{$resized_width} x {$resized_height}</div><br/>
					<a href="{$tile_host}/stamp.php?id={$image->gridimage_id}&title=on&gravity=SouthEast&hash={$image->_getAntiLeechHash()}&large=640&download=1">Stamped</a><br><br>
					</td>
				{/if}
				
				{if $original_width > 800 || $original_height > 800}

					{if $original_width>$original_height}
						{assign var="resized_width" value=800}
						{math assign="resized_height" equation="round(dw*sh/sw)" dw=$resized_width sh=$original_height sw=$original_width}
					{else}
						{assign var="resized_height" value=800}
						{math assign="resized_width" equation="round(dh*sw/sh)" dh=$resized_height sh=$original_height sw=$original_width}
					{/if}

					<td valign="top"><div class="interestBox">{$resized_width} x {$resized_height}</div><br/>
					<a href="{$tile_host}/stamp.php?id={$image->gridimage_id}&title=on&gravity=SouthEast&hash={$image->_getAntiLeechHash()}&large=800&download=1">Stamped</a><br><br>
					</td>
				{/if}
				
				{if $original_width > 1024 || $original_height > 1024}

					{if $original_width>$original_height}
						{assign var="resized_width" value=1024}
						{math assign="resized_height" equation="round(dw*sh/sw)" dw=$resized_width sh=$original_height sw=$original_width}
					{else}
						{assign var="resized_height" value=1024}
						{math assign="resized_width" equation="round(dh*sw/sh)" dh=$resized_height sh=$original_height sw=$original_width}
					{/if}

					<td valign="top"><div class="interestBox">{$resized_width} x {$resized_height}</div><br/>
					<a href="{$tile_host}/stamp.php?id={$image->gridimage_id}&title=on&gravity=SouthEast&hash={$image->_getAntiLeechHash()}&large=1024&pointsize=18&download=1">Stamped</a><br><br>
					</td>
				{/if}
				


					<td valign="top"><div class="interestBox">{$original_width} x {$original_height}</div><br/>
					<a href="{$tile_host}/stamp.php?id={$image->gridimage_id}&title=on&gravity=SouthEast&hash={$image->_getAntiLeechHash()}&large=1&pointsize=24&download=1">Stamped Version</a><br><br>
					</td>

				</tr>


				<tr>
				
					<td valign="top"><div class="interestBox">{$preview_width} x {$preview_height}</div><br/>
					<a href="/reuse.php?id={$image->gridimage_id}&amp;download={$image->_getAntiLeechHash()}"><img src="{$preview_url}" width="{$preview_width/$ratio}" height="{$preview_height/$ratio}"/></a><br/><br/>
					<small>as shown on<br/> photo page</small>
					</td>
				
				{if $image->altUrl != "/photos/error.jpg"}
					{assign var="preview_url" value=$image->altUrl}
					
					{if $original_width>$original_height}
						{assign var="resized_width" value=640}
						{math assign="resized_height" equation="round(dw*sh/sw)" dw=$resized_width sh=$original_height sw=$original_width}
					{else}
						{assign var="resized_height" value=640}
						{math assign="resized_width" equation="round(dh*sw/sh)" dh=$resized_height sh=$original_height sw=$original_width}
					{/if}
					
					<td valign="top"><div class="interestBox">{$resized_width} x {$resized_height}</div><br/>
					<a href="/reuse.php?id={$image->gridimage_id}&amp;download={$image->_getAntiLeechHash()}&amp;size=640"><img src="{$preview_url}" width="{$resized_width/$ratio}" height="{$resized_height/$ratio}"/></a>
					</td>
				{/if}
				
				{if $original_width > 800 || $original_height > 800}
					
					{if $original_width>$original_height}
						{assign var="resized_width" value=800}
						{math assign="resized_height" equation="round(dw*sh/sw)" dw=$resized_width sh=$original_height sw=$original_width}
					{else}
						{assign var="resized_height" value=800}
						{math assign="resized_width" equation="round(dh*sw/sh)" dh=$resized_height sh=$original_height sw=$original_width}
					{/if}
					
					<td valign="top"><div class="interestBox">{$resized_width} x {$resized_height}</div><br/>
					<a href="/reuse.php?id={$image->gridimage_id}&amp;download={$image->_getAntiLeechHash()}&amp;size=800"><img src="{$preview_url}" width="{$resized_width/$ratio}" height="{$resized_height/$ratio}"/></a>
					</td>
				{/if}
				
				{if $original_width > 1024 || $original_height > 1024}
					
					{if $original_width>$original_height}
						{assign var="resized_width" value=1024}
						{math assign="resized_height" equation="round(dw*sh/sw)" dw=$resized_width sh=$original_height sw=$original_width}
					{else}
						{assign var="resized_height" value=1024}
						{math assign="resized_width" equation="round(dh*sw/sh)" dh=$resized_height sh=$original_height sw=$original_width}
					{/if}
					
					<td valign="top"><div class="interestBox">{$resized_width} x {$resized_height}</div><br/>
					<a href="/reuse.php?id={$image->gridimage_id}&amp;download={$image->_getAntiLeechHash()}&amp;size=1024"><img src="{$preview_url}" width="{$resized_width/$ratio}" height="{$resized_height/$ratio}"/></a>
					</td>
				{/if}
				

					<td valign="top"><div class="interestBox">{$original_width} x {$original_height}</div><br/>
					<a href="/reuse.php?id={$image->gridimage_id}&amp;download={$image->_getAntiLeechHash()}&amp;size=original"><img src="{$preview_url}" width="{$original_width/$ratio}" height="{$original_height/$ratio}"/></a>
					
					{if $image->originalSize}
						<br/><br/><div class="interestBox">Filesize: {$image->originalSize|thousends} bytes</div>
					{/if}
					</td>

				</tr>

			</table>
			<p>Preview{if $original_width > 640 || $original_height > 640}s{/if} shown at <b>{math equation="round(100/r)" r=$ratio}</b>% of actual size{if $ratio ne 1} - NOT representative of the final quality{/if}.</p>		


<br/><br/><hr/><br/>
Return to <a href="/photo/{$image->gridimage_id}">photo page</a> or find <a href="/reuse.php?id={$image->gridimage_id}">more ways to use image</a>
</div>

{/dynamic}
{include file="_std_end.tpl"}
