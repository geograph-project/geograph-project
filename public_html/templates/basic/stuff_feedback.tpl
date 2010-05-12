{assign var="page_title" value="Feedback"}
{include file="_std_begin.tpl"}
{dynamic}
{if $thanks}
	<h3>Thank You</h3>
	<p>Many thanks for your feedback, it's much appreciated.</p>
	
	<div style="position:relative; border-left:4px solid orange; padding:20px;">
		<h2>New feature!</h2>

		<ul>
			<li><a href="/finder/human.php?create">Enlist the help of others to find <b>photographs of what you looking for</b></a>!</li>
		</ul>
	</div>
	
	<p>or <a href="javascript:history.go(-1);">Go Back</a></p>
	
{else}
<h2>Let us know what you think!</h2>

<p>Replies to parts A. and B. have now been totalled up and we are not accepting more answers. However the comment box is still available.</p>
<hr/>

<form method="post" action="{$script_name}">

<hr/>

<p><b>C. Any other comments to add?</b></p>
<textarea name="comments" rows="7" cols="80"></textarea><br/>
{if $user->registered}
<small>(<input type="checkbox" name="nonanon"/> <i>Tick here to include your name with this comment, so we can then reply. Will not be linked with the rest of the questions</i>)</small>
{/if}
<hr/>

<p><b>D. <input type="submit" name="submit" value="Send it in!" style="font-size:1.1em"/></b></p>
</form>
{/if}

{/dynamic}    
{include file="_std_end.tpl"}
