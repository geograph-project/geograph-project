{assign var="page_title" value="Geograph Image"}
{include file="_basic_begin.tpl"}


<div class="photoguide" style="margin-left:auto;margin-right:auto; width:470px">
	<div style="float:left;width:213px">
		<a title="view full size image" href="/photo/{$image->gridimage_id}" target="_blank">
		{$image->getThumbnail(213,160)}
		</a><div class="caption"><a title="view full size image" href="/photo/{$image->gridimage_id}" target="_blank">{$image->title|escape:'html'}</a> for <a href="/gridref/{$image->grid_reference}" target="_blank">{$image->grid_reference}</a></div>
	</div>
	<div style="float:left;padding-left:20px; width:200px;">
		<span style="font-size:0.7em">{$image->comment|escape:'html'|nl2br|geographlinks}</span><br/>
		<br/>
		<small><b>&nbsp; &copy; Copyright <a title="View profile" href="{$self_host}{$image->profile_link}" target="_blank">{$image->realname|escape:'html'}</a> and  
		licensed for reuse under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap" target="_blank">Creative Commons Licence</a></b></small>
	</div>
	
	<br style="clear:both"/>
</div>
{if $external}
	<div style="font-size:0.7em;width:470px;text-align:right;margin-left:auto;margin-right:auto;color:silver;margin-top:-4px">
		Image provided by <a href="{$self_host}" target="_blank" style="color:gray;">{$http_host}</a>
	</div>
{/if}

</body>
</html>
