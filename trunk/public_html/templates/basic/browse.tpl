{assign var="page_title" value="Browse"}
{include file="_std_begin.tpl"}

    <h2>Browse &amp; Submit</h2>
     
<p>We're still working on the browsing features, but you are welcome to
<a href="/submit.php">submit your pictures</a>.   
{if !$user->registered}
	<i>Note that you will be asked to login when you visit the
	submit page - please <a title="Register to create account" href="/register.php">register</a> if you haven't 
	already done so</i>
{/if}
</p>   

<p>You can view a particular grid square below - if the square hasn't been filled yet,
we'll tell you how far away the nearest one is...</p>

<form action="{$script_name}" method="get">
<div>
	<label for="gridsquare">Grid square</label>
	<select id="gridsquare" name="gridsquare">
		{html_options options=$prefixes selected=$gridsquare}
	</select>
	<label for="eastings">E</label>
	<select id="eastings" name="eastings">
		{html_options options=$kmlist selected=$eastings}
	</select>
	<label for="northings">N</label>
	<select id="northings" name="northings">
		{html_options options=$kmlist selected=$northings}
	</select>

		<input type="submit" name="setpos" value="Show &gt;"/>
</div>
</form>


{if $errormsg}
<p>{$errormsg}</p>
{/if}

{if $showresult}

	{if $imagecount}

		<p>We have 
		{if $imagecount eq 1}just one image{else}{$imagecount} images{/if} 
		for {$gridref} - click for larger version</p>
		
		{foreach from=$images item=image}
		
		  <div style="float:left;" class="photo33"><a title="view full size image" href="view.php?id={$image->gridimage_id}">{$image->getThumbnail(213,160)}</a>
		  <div class="caption"><a title="view full size image" href="/view.php?id={$image->gridimage_id}">{$image->title|escape:'html'}</a></div></div>
		  
		  
		{/foreach}
		
		<br style="clear:left;"/>&nbsp;
		

	{else}

		<p>We have no images for {$gridref}, 
		
		{if $nearest_distance}
			the closest occupied grid square is <a title="Jump to {$nearest_gridref}" href="/browse.php?gridref={$nearest_gridref}">{$nearest_gridref}</a> at {$nearest_distance}km away.
		{else}
			and have no pictures for any grid square within 100km either!
		{/if}
		
		</p>

	{/if}
   
   
{/if}

{include file="_std_end.tpl"}
