{assign var="page_title" value="GeoBrowser"}
{include file="_std_begin.tpl"}
{literal}<style type="text/css">
td.caption { font-size:0.7em}
</style>{/literal}
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js" type="text/javascript"></script>
<!--script src="/js/jquery.min.js" type="text/javascript"></script-->
<script src="{"/mapper/geotools2.js"|revision}" type="text/javascript"></script>
<script src="{"/js/geobrowser.js"|revision}" type="text/javascript"></script>
<script src="{"/sorttable.js"|revision}" type="text/javascript"></script>
<script src="{"/js/dragtable.js"|revision}" type="text/javascript"></script>


<h2>Geobrowser - Alpha</h2>

<form action="" onsubmit="return updateGridReference(this)" name="theForm" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;">
	<label for="gridref">Grid Reference</label>
		<input type=text size=8 name="gridref" id="gridref">

	<!--label for="incterms">Include cluster/terms</label> 
		<input type="checkbox" name="incterms" id="incterms"/-->
	
	<input type=submit value="Update &gt;"> <br/>
	
	
	<label for="showrows">Show first</label> 
		<select name="showrows" id="showrows" onchange="reGroup(this.form)">
			<option>1</option>
			<option>2</option>
			<option>3</option>
			<option>5</option>
			<option>10</option>
			<option>20</option>
		</select> 
	<label for="showrows">Image(s)</label> <label for="groupby">for each</label> 
		<select name="groupby" id="groupby" onchange="reGroup(this.form)">
			<option value="">-show all rows-</option>
		</select><br/>	

	<label for="popularity">Calculate Popularity for </label> 
		<select name="popularity" id="popularity" onchange="countPopularity(this.form)">
			<option value="">-</option>
		</select> (useful to sort by Popularity)<br/>	

	<label for="numrows">Maximum Number of images to show</label> 
		<select name="numrows" id="numrows" onchange="reGroup(this.form)">
			<option>2</option>
			<option>5</option>
			<option>10</option>
			<option selected>20</option>
			<option>50</option>
			<option>100</option>
			<option>200</option>
			<option>500</option>
			<option>1000</option>
			<option>2000</option>
			<option>5000</option>
			<option value="100000">all</option>
		</select> <br/>
	
	
</form>
</div>

<p<i>Click</i> a column header to reorder images, <i>Drag</i> a column header to reorder columns</p>
<table id="myTable" class="report sortable draggable" border="1" bordercolor="#dddddd" cellspacing="0" cellpadding="5">
</table>

<div id="info"></div>

<br/><br/>

{include file="_std_end.tpl"}

