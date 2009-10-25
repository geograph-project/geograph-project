{assign var="page_title" value="Snippets"}
{include file="_basic_begin.tpl"}
{dynamic}
<form method="post" style="background-color:#f0f0f0;">
<input type="hidden" name="gridimage_id" value="{$gridimage_id}" />
<input type="hidden" name="gr" value="{$gr|escape:'html'}" />

<div id="showcreate" style="display:none">
	<fieldset>

		<div class="field">
			{if $errors.title}<div class="formerror"><p class="error">{$errors.title}</p>{/if}

			<label for="title"><b>Short Title</b>:</label>
			<input type="text" id="title" name="title" size="30" maxlength="64"/>

			<div class="fieldnotes" style="font-size:0.7em">Short title for the object being represented</div>

			{if $errors.title}</div>{/if}
		</div>
		
		<div class="field">

			<label for="comment"><b>Description</b>:</label>

			<textarea name="comment" id="comment" rows="10" cols="60"></textarea>

			<div class="fieldnotes" style="font-size:0.7em">Remember this shared description may be used on multiple images - so keep it generic.
			</div>
		</div>
		
		<div class="field">
			{if $errors.grid_reference}<div class="formerror"><p class="error">{$errors.grid_reference}</p>{/if}

			<label for="grid_reference"><b>Grid Reference</b>:</label>
			<input type="text" id="grid_reference" name="grid_reference" size="10" maxlength="12"/>

			<div class="fieldnotes" style="font-size:0.7em">Optional Grid Reference for the feature/location. Ideally 6 figure plus.</div>

			{if $errors.grid_reference}</div>{/if}
		</div>
		
		<input type="submit" name="create" value="Create Shared Description"/>
	</fieldset>
</div>

<div class="interestBox" style="font-size:0.8em" id="hidecreate">
	<div style="float:right">
		<input type="button" value="Create New Shared Description" onclick="show_tree('create')"/><br/>
		<a href="/snippets.php?gr={$gr|escape:'html'}" target="_blank">Edit your Shared Descriptions</a>
	</div>
	Here you can create descriptions that are common to multiple images. For example can create a generic description for a object shown in a photo, and reuse the description on all photos of the object. All descriptions are public and shared between contributors, i.e. you can reuse a description created by others, just as they can use yours.
</div>

{foreach from=$used item=item name=used}
	
	<div style=" border-top: 1px solid gray">
		<div style="float:right">
			<input type="submit" name="remove[{$item.snippet_id}]" value="remove"/>
		</div>

		{$smarty.foreach.used.iteration}. 
		<b>{$item.title|escape:'html'}</b><br/>
		<div style="font-size:0.7em">{$item.comment|escape:'html'}</div>

		<br style="clear:both"/>
	</div>

{foreachelse}
	<p><i>nothing has been attached to this image yet</i></p>
{/foreach}


<div class="interestBox">

<b>View nearby descriptions</b> &nbsp;
<label for="fq">Search</label>: <input type="text" name="q" id="fq" size="20"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
{if !$sphinx}
	(single keyword only)
{/if}
<input type="submit" value="Find"/><br/>
{if $centisquare}
<small><input type="radio" name="radius" value="0.1"{if $radius == 0.1} checked{/if}/> This centisquare / 
{/if}
<input type="radio" name="radius" value="1" {if $radius == 1 || !$radius} checked{/if}/> This gridsquare  / 
<input type="radio" name="radius" value="2" {if $radius == 2} checked{/if}/> including surrounding gridsquares / 
<input type="radio" name="radius" value="10"{if $radius == 10} checked{/if}/> within 10km </small>
</div>

{foreach from=$results item=item}
	
	<div style="border-top: 1px solid gray">
		<div style="float:right">
			<input type="submit" name="add[{$item.snippet_id}]" value="use this"/>
		</div>

		<b>{$item.title|escape:'html'}</b> {if $item.grid_reference != $grid_reference} :: {$item.grid_reference} {/if}{if $item.distance}(Distance {$item.distance}km){/if}<br/>
		<div style="font-size:0.7em">{$item.comment|escape:'html'}</div>

		<br style="clear:both"/>
	</div>

{foreachelse}
	<p><i>no shared descriptions found</i></p>
{/foreach}
{if $query_info}
	<p><i>{$query_info}</i></p>
{/if}

</form>

{/dynamic}

</body>
</html>
