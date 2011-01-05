{assign var="page_title" value="Choose Hectads"}
{include file="_std_begin.tpl"}

{dynamic}
	<h2><a href="/adopt/">Hectad Adoptions</a> - Choose Hectads to Adopt</h2> 

	<p>Use the following to suggest hectads you would be interested in adopting. The specifics of this scheme have not been worked out yet, but the point of this exercise is to get some realistic data on the potential interest and distribution (and to sort out how to allocate squares).</p>
	
	<p>The general idea will be that the 'adopter' of a hectad will be able to create their own custom mosaic of images for the square - with the aim to objectively select the most representative images. A map of these custom mosaics will then be offered alongside the current 'first' mosaic.</p>
	
	<p>You can add as many hectads as you like, but bear in mind if successful you would be asked to sort though potentially hundreds of photos per hectad. If there are multiple requests per hectad we will use some as yet undecided algorithm to attempt to fairly allocate requests.</p>
	
	<p>Note however there is no commitment at this stage. You will be asked to confirm acceptance later on when we have the system ready.</p>

	<div class="interestBox">
	<form action="{$script_name}" method="post">
	
	<p><b>Hectad List</b>, <small>one hectad reference (eg TQ45) per line, in rough order of priority - with the ones you are most interested in towards the top.</small><br/>
<textarea name="hectads" rows="20" cols="10">
{foreach from=$hectads item=item}
{$item.hectad}
{/foreach}
</textarea>
	<p><input type="submit" name="submit" value="Save"/> {if $saved}Saved {$saved} hectads at {$smarty.now|date_format:"%H:%M"}{/if}</p>
	</form>
	</div>
{/dynamic}

{include file="_std_end.tpl"}
