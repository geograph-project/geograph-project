{assign var="page_title" value="Finder"}
{include file="_std_begin.tpl"}

<div style="position:relative;height:800px;">
	<div class="tabHolder">
		<a class="tabSelected nowrap">Quick Results</a>
		<a class="tab nowrap">Original Search</a>
		<a class="tab nowrap">Image Browser</a>
		<a class="tab nowrap">Browser Map</a>
		<a class="tab nowrap">Grouped Results</a>
		<a class="tab nowrap">Collections</a>
		{if $enable_forums}
			<a class="tab nowrap" id="tab8">Discussions</a>
		{/if}
	</div>
	<form method="get" style="background-color:#ddd;padding:10px;">
		<div style="float:left; width:300px">
			Search For: <input type=search name=q size="30" value="bailey bridge"> <br>
			<label><input type=radio name=type checked>Keywords Match</label>
			<label><input type=radio name=type>Similarity Match</label><br><br>
			<button>Update</button>
		</div>
		<div style="float:left; width:300px">
			And/or Near:
			<input type=search name=loc size="30" placeholder="(enter location)"> <br>
			<br>
			<a href="#">Add Date Filter</a> <a href="#">Add Contributor Filter</a>
		</div>
		<div style="clear:both;text-align:center">
		</div>
	</form>
	<br>
	<div class="tabHolder" style="text-align:right;font-size:0.9em">
		Display: 
		<a class="tab{if !$display || $display == 'small'}Selected{/if} nowrap">Small Thumbs</a>
		<a class="tab{if $display == 'large'}Selected{/if} nowrap">Large Thumbs</a>
		<a class="tab{if $display == 'details'}Selected{/if} nowrap">Details</a>
		<a class="tab{if $display == 'river'}Selected{/if} nowrap">GeoRiver</a>
		<a class="tab{if $display == 'map'}Selected{/if} nowrap">Map</a>
		<a class="nowrap">more...</a>
	</div>
	<div style="background-color:#ddd;padding:32px">
		results here
	</div>
</div>

{include file="_std_end.tpl"}

