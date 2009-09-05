{dynamic}
{assign var="page_title" value="Edit::$title"}

<script src="{"/geograph.js"|revision}" type="text/javascript"></script>

<link rel="stylesheet" type="text/css" title="Monitor" href="{"/location/dragdrop.css"|revision}" media="screen" />

<script src="{"/location/dragdrop.js"|revision}" type="text/javascript"></script>
<script src="{"/location/edit.js"|revision}" type="text/javascript"></script>
	
{if $error}
	<div><span class="formerror">{$error}</span></div>
{/if}

<div style="position:relative;float:right;height:100%; background-color:#eeeeee">

<form onsubmit="return false;" name="searchForm">
<label for="hq">Image Search:</label>
<input type="text" name="q" id="hq" value="test"/>
<input type="button" value="Find" onclick="performSearch(this.form.q.value);"/>
</form>

<div id="searchResults" style="width:300px;">

	<div class="imageBox" id="imageBoxxxxxx">
		<div style="border:none">search results will show here</div>
	</div>
</div>


</div>


	<div id="insertionMarker">
		<img src="images/marker_top.gif">
		<img src="images/marker_middle.gif" id="insertionMarkerLine">
		<img src="images/marker_bottom.gif">
	</div>
	<div id="dragDropContent">
	</div>
	<div id="debug" style="clear:both">
	</div>
{/dynamic}
