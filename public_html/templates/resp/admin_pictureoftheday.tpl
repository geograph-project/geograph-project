{assign var="page_title" value="Geograph Admin"}
{include file="_std_begin.tpl"}


<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js" type="text/javascript"></script>

{literal}
<style>
#maincontent *{
	box-sizing:border-box;
}

	#dispThumbs {
		position:absolute;
		color:white;
		z-index:1000;
	} 
	#dispThumbs iframe {
		width:550px;
		height:280px;
	}
</style>
<script>
	$(function() {
		var xOffset = 20;
		var yOffset = 20;

		$('a.imagepopup').hover(
			function(e) {
				var m = this.href.match(/\/(\d+)$/);
				$("body").append("<div id='dispThumbs'><iframe src='/frame.php?id="+m[1]+"'></iframe></div>");
			},
			function() {
				$("#dispThumbs").remove();
			}
		).mousemove(function(e){
			$("#dispThumbs")
				.css("top",(e.pageY + xOffset) + "px")
				.css("left",(e.pageX + yOffset) + "px");
		});
	});
</script>



{/literal}

<h2>Picture of the day</h2>

<p>The <a href="/">website homepage</a> prominently displays a picture of the day. Each day an image is selected from the pool of submitted images, which can be managed on this page. Moderators are encouraged to add images to the pool, and to review (vote) for images already added to the potential homepage pool.</p>


{*---------------------------Three col setup-------------------------*}


<div class="threecolsetup">



{*------------------------Nominate New Image----------------------------*}
<div class="threecolumn">
<h3>Nominate New Image</h3>


<p>Use the form below to nominate an image for <i>Picture of the Day</i>. Images can be added to the pool used for pseudo-random selection, or images can be assigned to a specific day. Please keep the image pool well-stocked with images.</p>

<div class="interestBox">Note: the image will be resized and cropped to dimensions of 393 &times; 300 pixels. Please preview photos to check the image appears acceptably when cropped to the central area, especially for portrait images.</div>

<form method="post" action="pictureoftheday.php" style="text-align:center">

<div style="width:100%; text-align: center;">

<div style="display: inline-block; vertical-align: middle; width:250px; max-width:40%; margin:5px;">
<label for="addimage">Image ID:</label>
<input type="text" name="addimage" size="8" id="addimage" value="{$addimage}" style="width: 200px; max-width: 100%"/>
</div>

<div style="display: inline-block; vertical-align: middle; width:250px; max-width:40%; margin:5px;">
<label for="when">Date (optional):</label>
<input type="text" name="when" size="16" id="when" value="{$when}" style="width: 200px; max-width: 100%"/>
</div>

<div style="display: inline-block; vertical-align: middle; width:250px; max-width:40%; margin:5px;">
<input type="button" value="Preview" onclick="window.open('/?potd='+this.form.addimage.value);" style="width: 200px; max-width: 100%" />
</div>

<div style="display: inline-block; vertical-align: middle; width:250px; max-width:40%; margin:5px;">
<input type="submit" name="add" value="Add" style="width: 200px; max-width: 100%"/>
</div>

</div>

{if $error}
<div style="border:1px solid red;background:#ffeeee;padding:5px;margin-top:5px;">{$error}</div>
{/if}
{if $confirm}
<div style="border:1px solid green;background:#eeffee;padding:5px;margin-top:5px;">{$confirm}</div>
{/if}

</form>

<p>Dates should be specified using the <a href="https://www.php.net/manual/en/function.strtotime.php">strtotime</a> format, for example:</p>

	<ul>
	<li>2007-05-29 </li>
	<li>24 Sep</li>
	<li>tomorrow</li>
	<li>this friday</li>
	</ul>

<div class="interestBox">Note: Dates put in as a month and day are interpreted as being in US date format. E.g. 05/11 would be interpreted as the 11th May, rather than the 5th November.</div>

</div>

{*------------------------Image pool----------------------------*}
<div class="threecolumn">
<h3>Image pool</h3>

<p>Images submitted to the pool for <i>Picture of the Day</i> are selected for display using an algorithm, which is partly influenced by the voting scores given to images. Higher rated images are more likely to be selected to be displayed. Please review the images below, and rate each image in the searches, as this helps to improve the selection.</p>

<div class="interestBox">Note: Please do not disclose the URLs for these search links.</div>

<h4>Recently added images</h4>
<p>Images which have been recently submitted to the <i>Picture of the Day</i> pool are displayed in the search below. Please vist and regularly review and rate these images by casting votes.<p>

<ul class="buttonbar">
<div class="buttonbar-dropdown">
  <button>Recently added images &#9660;</button>
  <div class="buttonbar-dropdown-content">
	<a href="/search.php?i=5761957&amp;temp_displayclass=vote">Thumbnails</a>
	<a href="/search.php?i=5761957&amp;temp_displayclass=blackvote">Georiver</a>
  </div>
</div>
</ul>



<h4>Pending image pool</h4>

<p>The full upcoming image pool can be found in the search below. Please continue to cast votes for images in the search, as this influences the selection algorithm.<p>

<ul class="buttonbar">
<div class="buttonbar-dropdown">
  <button>Full image pool &#9660;</button>
  <div class="buttonbar-dropdown-content">
	<a href="/search.php?i=2136521&amp;temp_displayclass=vote">Thumbnails</a>
	<a href="/search.php?i=2136521&amp;temp_displayclass=blackvote">Georiver</a>
  </div>
</div>

</ul>

</div>

{*------------------------Upcoming Allocations----------------------------*}
<div class="threecolumn">
<h3>Upcoming Allocations</h3>


<p>The image selected for <i>Picture of the Day</i> is determined using a pseudo-random algorithm and is subject to change. The list below shows a preview of the upcoming allocations, but it is highly likely the order will reshuffle before the next images is displayed. Images with a specifically assigned day will remain scheduled for that date.

<div class="interestBox">Tip: Hover over an image link to preview a thumbnail.</div>

<table class="report">
{foreach from=$coming_up key=date item=info}
<tr>
<td>{$date}</td>

{if $info.gridimage_id}
	<td><a href="/photo/{$info.gridimage_id}" class="imagepopup">photo {$info.gridimage_id}</a> 
		{if $info.pool}
		 (random from pool)
		{/if}
	</td>
{else}
<td><i>no image</i></td>
{/if}
</tr>

{/foreach}
</table>

</div>

</div>
<br style="clear:both">



{include file="_std_end.tpl"}

