{assign var="page_title" value="Games :: Mark-It!"}
{include file="_std_begin.tpl"}
<script type="text/javascript" src="/games/markit.js?v={$javascript_version}"></script>
	
{literal}
<style type="text/css">
.photo_box {
	float:left;
	position:relative;
	padding: 10px;
	
}

</style>
{/literal}
{dynamic}
{if $game->image} 
  <form enctype="multipart/form-data" action="{$script_name}" method="post" name="theForm" onsubmit="if (this.imageclass) this.imageclass.disabled=false;" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;">

	<h2>Game - Mark-It!</h2>
	
	<p>Drag the circle to mark the subject shown in the image. Get within 100m to win the remaining pineapples, lose one pineapple for every miss. Once you think you are close, click the check button to see how you did! If unsure you can reveal the caption of the image which may or may not make it easier!</p> 
	
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
		<a href="{$game->image->_getFullpath()}" target="gameimag"><img src="{$game->image->getThumbnail(213,160,true)}"></a>
		
		
		<p><label for="grid_reference"><b style="color:#0018F8">Grid Reference</b></label> <input id="grid_reference" type="text" name="grid_reference" value="{$grid_reference|escape:'html'}" size="14" onkeyup="updateMapMarker(this,false)" onmouseup="updateMapMarker(this,false)"/> <input type="button" value="check..." onclick="return game_check(this.form)"/></p>
	
		
		<input type="hidden" name="token" value="{$game->getToken()}"/>
		<fieldset>
			<legend>Pineapples Available <input type="text" name="points" value="{$game->points}" size="1" readonly="readonly"/></legend>
			{section loop=$game->points name="point"}
				<img src="/templates/basic/img/game_point.png" name="pointimg{$smarty.section.point.index}"/>
			{/section}
		</fieldset>
		
	</div>
	<div style="font-size:0.8em;display:none" id="caption">
	 <b>{$game->image->title|escape:'html'}</b><br/>
	 {$game->image->comment|escape:'html'}
	</div>
	<div id="anticaption">
		<input type="button" value="show caption" onclick="return showCaption(this.form)"/> (cost: one pineapple)
	</div>
	
	
	{if $rater} 
		<p>
		Please rate this<br/> particular image:<br/>
		<select name="rate" size="6" onchange="rateUpdate(this)">
			<option value="1">1 - Very Easy</option>
			<option value="2">2 - Easy</option>
			<option value="3">3 - Medium</option>
			<option value="4">4 - Hard</option>
			<option value="5">5 - Very Hard</option>
			<option value="-1">Not Suitable</option>
		</select></p>
		<input type="hidden" name="rater" value="1"/>
	{/if}
	
	<br style="clear:both"/>
	<div style="text-align:right">{if $game->score}Score at beginning of this game: {$game->score}{/if} <input type="submit" name="save" value="save scores &gt;" disabled="disabled"/> or <input type="submit" name="next" value="another &gt; &gt;" disabled="disabled"/></div>
</form>


<!-- Creative Commons Licence -->
<div class="ccmessage"><a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img 
alt="Creative Commons Licence [Some Rights Reserved]" src="http://creativecommons.org/images/public/somerights20.gif" /></a> &nbsp; Image used on this page, &copy; Copyright <a title="View profile" href="{if $game->image->nickname}/user/{$game->image->nickname|escape:'url'}/{else}/profile.php?u={$game->image->user_id}{/if}">{$game->image->realname|escape:'html'}</a> and  
licensed for reuse under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">Creative Commons Licence</a>.</div>
<!-- /Creative Commons Licence -->

{else}
	<p>There are no images available at the moment, thanks for your interest, and please check back tomorrow.</p>
	{if $game->score}<p>Don't forget to <a href="/games/score.php?token={$game->getToken()}">save your score</a>!</a></p>{/if}
{/if}
{/dynamic}

{include file="_std_end.tpl"}
