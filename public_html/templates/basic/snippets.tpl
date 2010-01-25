{assign var="page_title" value="Snippets"}
{include file="_std_begin.tpl"}
{dynamic}

<h2>Shared Descriptions</h2>

{if $thankyou && $thankyou == 'saved'} 
	<div style="background-color:lightgreen;padding:10px">
	<h3 class="titlebar" style="margin:0">Thank you - Changes saved</h3>
	{if $id} 
		<a href="/snippet/{$id}">View the Shared Description</a>
	{/if}
	</div>
{/if}

{if $edit}
<form method="post">
<input type="hidden" name="snippet_id" value="{$snippet_id|escape:'html'}"/>

<div>
	<h3>Editing {$title|escape:'html'}</h3>
	<div style="color:gray;margin-bottom:10px;margin-top:0">Used on {$images|thousends} images {if $yours && $images != $yours}(of which are {$yours|thousends} yours){/if}</div>
		
	<fieldset style="background-color:#f0f0f0;">

		<div class="field" style="padding:10px">
			{if $errors.title}<div class="formerror"><p class="error">{$errors.title}</p>{/if}

			<label for="title"><b>Short Title</b>:</label>
			<input type="text" id="title" name="title" value="{$title|escape:'html'}" size="30" maxlength="64"/>

			<div class="fieldnotes" style="font-size:0.7em;color:gray">Short title for the object/location being represented</div>

			{if $errors.title}</div>{/if}
		</div>
		
		<div class="field" style="padding:10px">

			<label for="comment"><b>Description</b>:</label>

			<textarea name="comment" id="comment" rows="10" cols="60">{$comment|escape:'html'}</textarea>

			<div class="fieldnotes" style="font-size:0.7em;color:gray">Remember this shared description may be used on multiple images - so keep it generic.<br/>
			
			TIP: use <span style="color:blue">[[TQ7506]]</span> or <span style="color:blue">[[5463]]</span> to link 
to a Grid Square or another Image.<br/>For a weblink just enter directly like: <span style="color:blue">http://www.example.com</span><br/><br/>
			</div>
		</div>
		
		<div class="field" style="padding:10px">
			{if $errors.grid_reference}<div class="formerror"><p class="error">{$errors.grid_reference}</p>{/if}

			<label for="grid_reference"><b>Grid Reference</b>:</label>
			<input type="text" id="grid_reference" name="grid_reference" value="{$grid_reference|escape:'html'}" size="10" maxlength="12"/>
			 or <input type="checkbox" name="nogr" value="1" id="nogr" {if $wgs84_lat == 0 && $wgs84_long == 0} checked{/if}/><label for="nogr">Don't attach a location to this description</label>
			
			<div class="fieldnotes" style="font-size:0.7em;color:gray">Optional Grid Reference for the feature/location, great if this description describes a specific location. Ideally 6 figure plus.</div>

			{if $errors.grid_reference}</div>{/if}
		</div>
		
		<input type="submit" name="save" value="Save"/> <input type="submit" name="cancel" value="Cancel"/>
		
		<div class="fieldnotes" style="font-size:0.7em;color:gray">Idea: Even if you leave the description itself blank, a 'shared description' can still be used as a way to link a series of images into 'Collection'.</div>
		
	</fieldset>
</div>
</form>

{else}

<p>
	Here you can manage descriptions that are common to multiple images, create new descriptions during image submission, or on the 'Change Image Details' page for your own images. For example a generic description for a object shown in a photo, and reuse the description on all photos of the object. All descriptions are public and shared between contributors, i.e. you can reuse a description created by others, just as they can use yours.
</p>


<form method="get" action="{$script_name}">

<div class="interestBox">
<b>Shared Description Search</b><br/>

{if $sphinx}
	<label for="fq">Search keywords</label>: 
{else}
	<label for="fq">Search <u>keyword<</u></label>: 
{/if}
<input type="text" name="q" id="fq" size="20"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>

<input type="submit" value="Find"/><br/>
<label for="gr">Grid Reference</label>: 
<input type="text" name="gr" id="gr" value="{$gr|escape:'html'}" size="12" maxlength="12"/>{if $is_mod} &nbsp;&nbsp; (<input type="checkbox" name="onlymine" {if $onlymine} checked{/if}/> Only show my descriptions. Moderators can edit all descriptions){/if}<br/>

<label for="gr">Radius</label>: 
{if $centisquare}
<small><input type="radio" name="radius" value="0.1"{if $radius == 0.1} checked{/if}/> Centisquare / 
{/if}
<input type="radio" name="radius" value="1" {if $radius == 1 || !$radius} checked{/if}/> Gridsquare  / 
<input type="radio" name="radius" value="2" {if $radius == 2} checked{/if}/> including surrounding gridsquares / 
<input type="radio" name="radius" value="10"{if $radius == 10} checked{/if}/> within 10km </small><br/>

</div>

{foreach from=$results item=item}
	
	<div style="border-top: 1px solid gray">
		<div style="float:right;position:relative">
			<input type="submit" name="edit[{$item.snippet_id}]" value="Edit"/>
			<input type="submit" name="delete[{$item.snippet_id}]" value="Delete"/>
		</div>

		<b><a href="/snippet.php?id={$item.snippet_id}" class="text">{$item.title|escape:'html'|default:'Untitled'}</a></b> {if $item.grid_reference != $grid_reference} :: {$item.grid_reference} {/if}{if $item.distance}(Distance {$item.distance}km){/if}<br/>
		<div style="font-size:0.7em">{$item.comment|escape:'html'}</div>
		<div style="font-size:0.7em;color:gray;margin-left:10px;">
		
		{if $user->user_id != $item.user_id} 		
			By <a href="/profile/{$item.user_id}">{$item.realname|escape:'html'}</a>. 
		{/if}
		
		Used on {$item.images|thousends} images {if $item.images != $item.yours}(of which {$item.yours|thousends} are yours){/if}</div>
		
		<br style="clear:both"/>
	</div>

{foreachelse}
	{if $gr || $q}
		<p><i>no shared descriptions found (NOTE: it can take 10 minutes for new descriptions to be found)</i></p>
	{/if}
{/foreach}
{if $query_info}
	<p><i>{$query_info}</i></p>
{/if}

</form>

{/if}

{/dynamic}

{include file="_std_end.tpl"}

