{include file="_std_begin.tpl"}



<h2>Finder</h2>

{if !$inner}

	<form action="{$script_name}" method="get">
		<div class="interestBox">
			<label for="fg">Grid Ref</label>: <input type="text" name="g" id="fg" size="6"{if $gridref} value="{$gridref|escape:'html'}"{/if}/>
			<label for="fq">Optional Keywords</label>: <input type="text" name="q" id="fq" size="40"{if $q} value="{$q|escape:'html'}"{/if}/>
			<input type="submit" value="Search"/>
		</div>
	</form>
	<br/><br/>

{/if}


{if $results}


	{foreach from=$results item=image}
	  <div style="float:left;position:relative; width:130px; height:130px">
	  <div align="center">
	  <a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120,false,true)}</a>
	  {if $image->less}
	  	<small>[less]</small>
	  {elseif $image->more}
	  	<small>[more]</small>
	  {elseif $image->terms}
	  	<small>[<a href="{$script_name}?{$extra|escape:'html'}&amp;more={$image->gridimage_id}">More</a>]
	  	[<a href="{$script_name}?{$extra|escape:'html'}&amp;less={$image->gridimage_id}">Less</a>]</small>
	  {/if}
	  </div>
	  </div>
	{foreachelse}
		<p><i>no images to display{if $images}, this could be because still pending and/or recently rejected{/if}</i></p>
	{/foreach}
	<br style="clear:both"/>
	<br/>
{/if}

{if $query_info}
	<p><i>{$query_info|escape:'html'}</i></p>
{/if}
<br/>
{include file="_std_end.tpl"}
