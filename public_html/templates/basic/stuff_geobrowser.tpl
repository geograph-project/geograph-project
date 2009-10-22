{assign var="page_title" value="GeoBrowser"}
{include file="_std_begin.tpl"}
{literal}<style type="text/css">
td.caption { font-size:0.7em}

        .black_overlay{
            display: none;
            position: absolute;
            top: 0%;
            left: 0%;
            width: 100%;
            height: 100%;
            background-color: black;
            z-index:1001;
            -moz-opacity: 0.8;
            opacity:.80;
            filter: alpha(opacity=80);
        }
        .white_content {
            display: none;
            position: absolute;
            top: 10%;
            left: 10%;
            width: 80%;
            height: 80%;
            padding: 6px;
            border: 6px solid orange;
            background-color: white;
            z-index:1002;
            overflow: auto;
        }

</style>{/literal}
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js" type="text/javascript"></script>
<!--script src="/js/jquery.min.js" type="text/javascript"></script-->
<script src="{"/mapper/geotools2.js"|revision}" type="text/javascript"></script>
<script src="{"/js/geobrowser.js"|revision}" type="text/javascript"></script>
<script src="{"/sorttable.js"|revision}" type="text/javascript"></script>
<script src="{"/js/dragtable.js"|revision}" type="text/javascript"></script>


<h2>Geobrowser - Alpha</h2>

<div id="light" class="white_content">
<div id="thumbnails"></div>
<div style="text-align:right;"><a href="javascript:void(0)" onclick="document.getElementById('light').style.display='none';document.getElementById('fade').style.display='none'">Close</a></div>
</div><div id="fade" class="black_overlay"></div>

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

<p style="font-size:0.7em"><nobr>&middot; <i>Click</i> a column header to reorder images</nobr> <nobr>&middot; <i>Drag</i> a column header to reorder columns</nobr> <nobr>
&middot; <i>Hover</i> over Image column to load thumbnail</nobr> </p>

<div id="info"></div>
<table id="myTable" class="report sortable draggable" border="1" bordercolor="#dddddd" cellspacing="0" cellpadding="5">
</table>

<div class="interestBox" style="display:none" id="toolbar">| <a href="javascript:void(viewThumbnails())">View as Thumbnails</a> |</div>

<br/><br/>

{include file="_std_end.tpl"}

