{assign var="page_title" value="Xmas images"}
{include file="_std_begin.tpl"}


<h2>{$page_title}</h2>

<div>

{assign var="last" value="-1"}
{foreach from=$results item=image}
	{if $last != $image->user_id}
		<h3 style="clear:both">{$image->realname|escape:'html'} 
			{if $image->count > 5}
				<a href="/search.php?user_id={$image->user_id}&amp;taken={$image->imagetaken}&amp;do=1&gridref={$image->grid_reference}&amp;d=3">more...</a>
			{/if}
		</h3>
		{assign var="last" value=$image->user_id}
	{/if}
	  <div style="float:left;position:relative; width:130px; height:130px">
	  <div align="center">
	  <a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="/photo/{$image->gridimage_id}" target="_parent">{$image->getThumbnail(120,120,false,true,$src)}</a></div>
	  </div>

{foreachelse}
	{if $q}
		<p><i>There is no content to display at this time.</i></p>
	{/if}
{/foreach}
	<br style="clear:both">
</div>


<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js" type="text/javascript"></script>
{if $src == 'data-src'}
	<script src="{"/js/lazy.js"|revision}" type="text/javascript"></script>
{/if}
<script src="/preview.js.php?d=preview" type="text/javascript"></script>


{include file="_std_end.tpl"}

