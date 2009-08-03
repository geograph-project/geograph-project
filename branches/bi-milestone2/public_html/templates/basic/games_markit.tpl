{assign var="page_title" value="Games :: Mark-It!"}
{include file="_std_begin.tpl"}
<script type="text/javascript" src="{"/games/markit.js"|revision}"></script>
	
{literal}
<style type="text/css">
.photo_box {
	float:left;
	position:relative;
	padding: 10px;
	
}

</style>
{/literal}

<h2><a href="/games/">Geograph Games</a> :: Mark-It!</h2>
	
{dynamic}
{if $game->image} 

	{if !$rater}
	<div style="position:relative; float:right; width:60px; height:{$game->batchsize*32}px; border:1px solid red">
		{section loop=$game->batchsize name="batch"}
			{if $smarty.section.batch.index+1 > $game->batchsize-$game->games}
				<div style="width:60px;height:30px; border:1px solid gray; background-color:green; color:white; text-align:center">Done</div>
			{else} 
				<div style="width:60px;height:30px; border:1px solid gray"></div>
			{/if}
		{/section}
	</div>
	{/if}

  <form enctype="multipart/form-data" action="{$script_name}" method="post" name="theForm" onsubmit="if (this.imageclass) this.imageclass.disabled=false;" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;">

	
	<p>Drag the circle to mark the subject shown in the image. Get within 100m to win the remaining tokens, lose one token for every miss. Once you think you are close, click the check button to see how you did! If unsure you can reveal the caption of the image which may or may not make it easier!</p> 
	
	{if $message}
		<p style="color:#990000;font-weight:bold;">{$message}</p>
	{/if}

	<div id="responce"></div>
		
{if $game->rastermap->enabled}
	<div class="rastermap" style="width:{$game->rastermap->width}px;position:relative">
	{$game->rastermap->getImageTag()}
	
	</div>

	{$game->rastermap->getScriptTag()}
{else}
	<div class="rastermap" style="width:{$game->rastermap->width}px;height:{$game->rastermap->width}px;position:relative">
		Map Coming Soon...
	
	</div>
{/if}
	
	

	<div class="photo_box">
		<p><a href="{$game->image->_getFullpath(true,true)}" target="gameimag"><img src="{$game->image->getThumbnail(213,160,true)}"></a>
		<small><small><br/>Click thumbnail to view full size</small></small></p>
		
		
		<p><label for="grid_reference"><b style="color:#0018F8">Grid Reference</b></label> <input id="grid_reference" type="text" name="grid_reference" value="{$grid_reference|escape:'html'}" size="14" onkeyup="updateMapMarker(this,false)" onmouseup="updateMapMarker(this,false)" style="display:none"/> <input type="text" name="grid_reference_display" value="{$grid_reference|escape:'html'}" readonly="readonly" disabled="disabled"/> <input type="button" value="check..." onclick="return game_check(this.form)"/></p>
	
		
		<input type="hidden" name="token" value="{$game->getToken()}"/>
		<fieldset>
			<legend>Hamster Tokens Available <input type="text" name="points" value="{$game->points}" size="1" readonly="readonly"/></legend>
			{section loop=$game->points name="point"}
				<img src="http://{$static_host}/templates/basic/img/hamster-icon.gif" name="pointimg{$smarty.section.point.index}"/>
			{/section}
		</fieldset>
		
	</div>
	<div style="font-size:0.8em;display:none" id="caption">
	 <b>{$game->image->title|escape:'html'}</b><br/>
	 {$game->image->comment|escape:'html'}
	</div>
	<div id="anticaption">
		<input type="button" value="show caption" onclick="return showCaption(this.form)"/> (cost: one token)
	</div>
	
	
	{if $rater} 
		<p>
		Please rate this<br/> particular image:<br/>
		<select name="rate" size="7" onchange="rateUpdate(this)">
			<option value="1">1 - Very Easy</option>
			<option value="2">2 - Easy</option>
			<option value="3">3 - Medium</option>
			<option value="4">4 - Hard</option>
			<option value="5">5 - Very Hard</option>
			<option value="-1">Not Suitable</option>
			<option value="-2">Doubt Position Specified</option>
		</select></p>
		<input type="hidden" name="rater" value="1"/>
	{/if}
	
	<br style="clear:both"/>
	<div style="text-align:right">{if $game->score}Score at beginning of this game: {$game->score}, with {$game->games} games played{/if} 
		{if $rater}
			<input type="submit" name="save" value="save scores &gt;" disabled="disabled"/>
			or <input type="submit" name="next" value="another &gt; &gt;" disabled="disabled"/>
		{elseif $game->games == $game->batchsize-1}
			<input type="submit" name="save" value="save scores &gt;" disabled="disabled"/>
		{else}
			<input type="submit" name="next" value="next &gt; &gt;" disabled="disabled"/>
		{/if}
	</div>
</form>


<!-- Creative Commons Licence -->
<div class="ccmessage"><a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img 
alt="Creative Commons Licence [Some Rights Reserved]" src="http://creativecommons.org/images/public/somerights20.gif" /></a> &nbsp; Image used on this page, &copy; Copyright <a title="View profile" href="{$game->image->profile_link}">{$game->image->realname|escape:'html'}</a> and  
licensed for reuse under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">Creative Commons Licence</a>.</div>
<!-- /Creative Commons Licence -->

{else}
	<p>There are no images available available in the current set.</p>
	{if $game->score}<p>Don't forget to <a href="/games/score.php">save your score</a>!</a></p>{/if}
	
	{if $rater}
		<p>You can also try <a href="/games/markit.php?rater=1&amp;autoload">loading a new set</a>.</p>
	{/if}
{/if}
{/dynamic}

{include file="_std_end.tpl"}
