{assign var="page_title" value="Snippets"}
{include file="_std_begin.tpl"}
{dynamic}

<h2>Shared Descriptions</h2>

{if $thankyou && $thankyou == 'saved'} 
	<h3 class="titlebar" style="background-color:lightgreen">Thank you - Changes saved</h3>
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

			<div class="fieldnotes" style="font-size:0.7em">Short title for the object being represented</div>

			{if $errors.title}</div>{/if}
		</div>
		
		<div class="field" style="padding:10px">

			<label for="comment"><b>Description</b>:</label>

			<textarea name="comment" id="comment" rows="10" cols="60">{$comment|escape:'html'}</textarea>

			<div class="fieldnotes" style="font-size:0.7em">Remember this shared description may be used on multiple images - so keep it generic.
			</div>
		</div>
		
		<div class="field" style="padding:10px">
			{if $errors.grid_reference}<div class="formerror"><p class="error">{$errors.grid_reference}</p>{/if}

			<label for="grid_reference"><b>Grid Reference</b>:</label>
			<input type="text" id="grid_reference" name="grid_reference" value="{$grid_reference|escape:'html'}" size="10" maxlength="12"/>

			<div class="fieldnotes" style="font-size:0.7em">Optional Grid Reference for the feature/location. Ideally 6 figure plus.</div>

			{if $errors.grid_reference}</div>{/if}
		</div>
		
		<input type="submit" name="save" value="Save"/> <input type="submit" name="cancel" value="Cancel"/>
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
		<p><i>no shared descriptions found</i></p>
	{/if}
{/foreach}
{if $query_info}
	<p><i>{$query_info}</i></p>
{/if}

</form>

{/if}

{/dynamic}

{include file="_std_end.tpl"}

