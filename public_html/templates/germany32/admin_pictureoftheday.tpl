{assign var="page_title" value="Geograph Admin"}
{include file="_std_begin.tpl"}


<h2>Picture of the day</h2>

<p>There are {$pendingcount} images waiting to be displayed as picture of the day - add
another one to the emergency kitty below - these are used whenever a new day dawns
without a particular image assigned.</p>

<p>NOTE: the image will be cropped to landscape format - so for portrait format photos check that they still work when cropped to the central area.</p>

<form method="post" action="pictureoftheday.php">

<div>
<label for="addimage">Image number</label>
<input type="text" name="addimage" size="8" id="addimage" value="{$addimage}"/>
<input type="button" value="Preview" onclick="window.open('/?potd='+this.form.addimage.value);">
</div>

<div>
<label for="when">When?</label>
<input type="text" name="when" size="16" id="when" value="{$when}"/>

<input type="submit" name="add" value="Add"/>

{if $error}
<div style="border:1px solid red;background:#ffeeee;padding:5px;margin-top:5px;">{$error}</div>
{/if}
{if $confirm}
<div style="border:1px solid green;background:#eeffee;padding:5px;margin-top:5px;">{$confirm}</div>
{/if}


<br>
<p>You can leave this blank to add the image to a pool of images used
when a particular day hasn't been assigned yet, or specify the date
with any <a href="http://www.gnu.org/software/tar/manual/html_node/tar_109.html">strtotime</a> format, e.g.</p>
<ul>
<li>2007-05-29 </li>
<li>24 Sep</li>
<li>tomorrow</li>
<li>this friday</li>
</ul>

</div>


</form>

<h3>Coming up...</h3>

<table>
{foreach from=$coming_up key=date item=info}
<tr>
<td>{$date}</td>

{if $info.gridimage_id}
	<td><a href="/photo/{$info.gridimage_id}">photo {$info.gridimage_id}</a> 
		{if $info.pool}
		 (from pool)
		{/if}
	</td>
{else}
<td>no image</td>
{/if}
</tr>

{/foreach}
</table>
    
{include file="_std_end.tpl"}
