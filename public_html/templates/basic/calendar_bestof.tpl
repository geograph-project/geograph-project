{assign var="page_title" value="Geograph calendar for `$year`"}
{include file="_std_begin.tpl"}


<h2>Best Of Calendar for {$year}</h2>

<div style="width:900px">

	<p>Geograph is producing a custom printed calendar, showcasing recent imagry from Contributors of Geograph. See the preview of selected images below. <a href="order.php">Order your calendar</a> today, only &pound;8 per calendar (minimum 2), plus &pound;3 P+P (To UK addresses only)</p>

	<p>The calendar will be in the familiar A3 wall-hanging format, wire-bound across the middle, with a picture above a one month calendar. This will have space for appointments, events, etc. to be inserted on a day by day basis.

	<p>There will be a separate picture for each month plus one picture for the front cover.

	{foreach from=$images key=index item=image}
		<div style="float:left; width:213px; height:220px; text-align:center; margin:5px">
			<div style="height:160px;">
				{$image->getThumbnail(213,160)}
			</div>
			<a href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a><br>
			by <a href="/profile/{$image->user_id}">{$image->realname|escape:'html'}</a>
		</div>
	{/foreach}
</div>

<p style="clear:both;padding-top:20px;">
   <a href="order.php" style="font-size:large;background-color:#000066;color:yellow;padding:10px;border-radius:10px">Order 'Best Of Geograph' Calendar &gt; &gt;</a>
</p>
Or if you are a Geograph contributor, can <a href="/login.php">sign in</a> to make a custom calendar of your own images


{include file="_std_end.tpl"}


