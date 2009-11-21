{assign var="page_title" value="Snippets"}
{include file="_basic_begin.tpl"}
{dynamic}
<form method="post" action="#top" style="background-color:#f0f0f0;" name="theForm">
<input type="hidden" name="gridimage_id" value="{$gridimage_id}" />
<input type="hidden" name="gr" value="{$gr|escape:'html'}" />

<div id="showcreate" style="display:none">
	<fieldset>

		<div class="field">
			{if $errors.title}<div class="formerror"><p class="error">{$errors.title}</p>{/if}

			<label for="title"><b>Short Title</b>:</label>
			<input type="text" id="title" name="title" size="30" maxlength="64"/>

			<div class="fieldnotes" style="font-size:0.7em;color:gray">Short title for the object/location being represented</div>

			{if $errors.title}</div>{/if}
		</div>
		
		<div class="field">

			<label for="comment"><b>Description</b>:</label>

			<textarea name="comment" id="comment" rows="10" cols="60"></textarea>

			<div class="fieldnotes" style="font-size:0.7em;color:gray">Remember this shared description may be used on multiple images - so keep it generic.<br/>
			
			TIP: use <span style="color:blue">[[TQ7506]]</span> or <span style="color:blue">[[5463]]</span> to link 
to a Grid Square or another Image.<br/>For a weblink just enter directly like: <span style="color:blue">http://www.example.com</span><br/><br/>
			</div>
		</div>
		
		<div class="field">
			{if $errors.grid_reference}<div class="formerror"><p class="error">{$errors.grid_reference}</p>{/if}

			<label for="grid_reference"><b>Grid Reference</b>:</label>
			<input type="text" id="grid_reference" name="grid_reference" size="10" maxlength="12"/> {if $gr}<small>(<a href="javascript:void(document.theForm.grid_reference.value='{$gr|escape:'url'}');">Use {$gr}</a>)</small>{/if}

			<div class="fieldnotes" style="font-size:0.7em;color:gray">Optional Grid Reference for the feature/location, great if this description describes a specific location. Ideally 6 figure plus.</div>

			{if $errors.grid_reference}</div>{/if}
		</div>
		
		<input type="submit" name="create" value="Create Shared Description"/>
		
		<div class="fieldnotes" style="font-size:0.7em;color:gray">Idea: Even if you leave the description itself blank, a 'shared description' can still be used as a way to link a series of images into 'Collection'.</div>
		
	</fieldset>
</div>

<div class="interestBox" style="font-size:0.8em" id="hidecreate">
	<div style="float:right;text-align:center;position:relative">
		<input type="button" value="Create New Shared Description" onclick="show_tree('create')" style="background-color:lightgreen"/><br/>
		<a href="/snippets.php?gr={$gr|escape:'html'}" target="_blank">Edit nearby Shared Descriptions</a>
	</div>
	&middot; Here you can create descriptions that are common to multiple images.<br/>&middot; These shared descriptions can operate in addition <i>or</i> instead of the main description.{if $used}<br/> &middot; Optional: Reference a shared description by its number eg [1] in the main description.{/if}
	
	{if $gridimage_id < 4294967296}
		<br/>&middot; <b>Changes made here apply immediately and don't go though the change request system.</b>
	{/if}
	<a href="#more"><b>read more...</b></a>
</div>
<div style="font-size:0.7em;color:green;border-top:2px solid gray">&nbsp;Shared Descriptions attached to this image:</div>
{foreach from=$used item=item name=used}
	
	<div style=" border-top: 1px solid gray;margin-left:4px">
		<div style="float:right;position:relative">
			<input type="submit" name="remove[{$item.snippet_id}]" value="Remove" style="background-color:pink"/>
		</div>

		{$smarty.foreach.used.iteration}. 
		<b>{$item.title|escape:'html'}</b><br/>
		<div style="font-size:0.7em">{$item.comment|escape:'html'}</div>

		<br style="clear:both"/>
	</div>

{foreachelse}
	<p style="margin:4px;margin-bottom:10px"><i><b>None</b>. <small>Click 'Create New Shared Description'{if $results}, or a 'Use this Description' button below,{/if} to add a description to this image.</small></i></p>
{/foreach}

<div style="font-size:0.7em;color:green;border-top:2px solid gray">&nbsp;Shared Descriptions available:</div>

<div class="interestBox" style="margin:4px">
Within radius:<small>{if $centisquare}
<span class="nowrap"><input type="radio" name="radius" value="0.1"{if $radius == 0.1} checked{/if}/> centisquare</span> / 
{/if}
<span class="nowrap"><input type="radio" name="radius" value="1" {if $radius == 1 || !$radius} checked{/if}/> gridsquare</span> / 
<span class="nowrap"><input type="radio" name="radius" value="2" {if $radius == 2} checked{/if}/> surrounding gridsquares</span> / 
<span class="nowrap"><input type="radio" name="radius" value="10"{if $radius == 10} checked{/if}/> within 10km</span> /
<span class="nowrap"><input type="radio" name="radius" value="1000"{if $radius == 1000} checked{/if}/> anywhere <sub>(keyword needed below!)</sub></span>  </small><br/>
<label for="fq">Search{if $sphinx} keywords{/if}</label>: <input type="text" name="q" id="fq" size="20"{if $q} value="{$q|escape:'html'}"{/if}/>
{if !$sphinx}
	(single keyword only)
{/if}
 <input type="checkbox" name="onlymine" {if $onlymine} checked{/if}/> Only show my descriptions. 
 
 <input type="submit" value="Update"/>
</div>

{foreach from=$results item=item}
	
	<div style="border-top: 1px solid gray;margin-left:4px">
		<div style="float:right;position:relative">
			<input type="submit" name="add[{$item.snippet_id}]" value="Use this Description" style="background-color:lightgreen"/>
		</div>

		<b>{$item.title|escape:'html'}</b> {if $item.grid_reference != $grid_reference} :: {$item.grid_reference} {/if}{if $item.distance}(Distance {$item.distance}km){/if}<br/>
		<div style="font-size:0.7em">{$item.comment|escape:'html'}</div>

		<br style="clear:both"/>
	</div>

{foreachelse}
	{if $empty}
	<p style="margin:4px"><i>please specify some keywords{if $grid_reference}, or choose a smaller radius{/if}</i></p><br/>
	{else}
	<p style="margin:4px"><i>No shared descriptions found{if $radius == 1 || !$radius} in {$grid_reference}{/if}{if $q}, matching [{$q|escape:'html'}]{/if}, create your own!</i></p><br/>
	{/if}
{/foreach}
{if $query_info}
	<p><i>{$query_info}</i></p>
{/if}
<input type="text" value="" style="display:none"/>
</form>

{/dynamic}
<div class="interestBox" style="font-size:0.8em;border-top:2px solid gray"><a name="more"></a>
&middot; For example can create a generic description for a object or location shown in a photo, and reuse the description on all photos of the object or location.<br/>
&middot; All descriptions are public and shared between contributors, i.e. you can reuse a description created by others, just as they can use yours.<br/>
<b>... read more in our {newwin href="/article/Shared-Descriptions" text="Shared Descriptions Article"}, or <a href="#top">back to top</a></b>
</div>
<div class="interestBox" style="background-color:pink; font-size:0.7em; border-top:2px solid white"><i>For clarification, you are submitting these shared descriptions to Geograph Project directly. Geograph Project then grants any contributor the right to reuse any shared description within their Creative Commons licensed submission. From a practical point of view this allows the contributor the use the description without attribution (as its not Creative Commons licensed).</i></div>

</body>
</html>
