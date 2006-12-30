{dynamic}
{assign var="page_title" value="Edit::$title"}

{include file="_std_begin.tpl"}

{if $error}
	<div><span class="formerror">{$error}</span></div>
{/if}

<form action="/article/edit.php" method="post">
<input type="hidden" name="article_id" value="{$article_id|escape:"html"}"/>
<table><tr><td>
{if $title != 'New Article'}
URL: <br/>
http://{$http_host}/article/<input type="text" name="url" value="{$url|escape:"html"}" maxlength="64" size="40"/><br/>{/if}
Title: <br/>
<input type="text" name="title" value="{$title|escape:"html"}" style="font-size:1.3em" maxlength="64" size="40"/>
</td></tr><tr><td>
<div style="text-align:right">
<select name="licence">
{html_options options=$licences selected=$licence}
</select> by
<a href="/profile.php?u={$user_id}" title="View Geograph Profile for {$realname}">{$realname}</a>
</div> 
</td></tr></table>
<hr>
Content: <br/>
<textarea rows="40" cols="80" name="content">{$content|escape:"html"}</textarea></p>

<div style="padding:5px; border: 1px solid gray; background-color:silver; font-family: monospace; font-size:0.8em">
Quick Reference: 

<h2>[h2]Big Title[/h2]</h2>
<h3>[h3]Sub Title[/h3]</h3>

<p>[b]<b>Bold</b>[/b], [i]<i>Italic</i>[/i] and [big]<big>Big</big>[/big]</p>

<p>* Bulleted List<br/>
* Item Two</p>

</div>

<p>Publish Date:<br/>
{html_select_date prefix="publish_date" time=`$publish_date` start_year="-100" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY" day_value_format="%02d" month_value_format="%m"}</p>

<p>
<input type="reset" name="reset" value="Undo Changes" onclick="return confirm('Are you sure? Changes will be lost!');"/>
<input type="submit" name="submit" value="Save Changes..."/> {if $title == 'New Article'}<br/>(Articles will only show on the site once they have been approved}{/if}</p>
</form>

{include file="_std_end.tpl"}
{/dynamic}