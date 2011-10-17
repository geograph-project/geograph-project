{assign var="page_title" value="Town Editor"}
{dynamic}
{include file="_std_begin.tpl"}

<h2>Town Editor</h2>
<p>Use this page to add/delete/edit towns shown on the maps.
Changed values are highlighted in grey. Use R button to reset value.
{if $dbarr}
Use the G(et) and P(ut) buttons for data transfer from/to the edit line.
{/if}
<b>Please check the box "Test run" below for finding out if the page
works as expected on your browser before doing real changes!</b>
</p>
    
{if $message}
	<div style="border:1px solid red; padding:10px;">{$message}</div>
{/if}
    
   
<div style="background-color:#e0e0e0">
<h3>Towns to display</h3>
<p>This settings are valid for the whole session.<p>
<form method="post">
<label for="townlim">Minimal size: </label>
<input type="text" id="townlim" name="townlim" size="1" value="{$townlim}" />
<br />
<label for="towncids">Community id starting with: </label>
<input type="text" id="towncids" name="towncids" size="10" value="{$towncids}" />
<small>(leading zeroes may be required for some areas; empty string for reset)</small>
<br />
<input type="submit" name="listsettings" value="Set"><br />
</form>
</div>
<div>
	{if $rastermap->enabled}
		<div class="rastermap" style="float:right;  width:350px;position:relative">
		
		<b>{$rastermap->getTitle($gridref)}</b><br/><br/>
		{$rastermap->getImageTag()}<br/>
		<span style="color:gray"><small>{$rastermap->getFootNote()}</small></span>
		 
		</div>
		
		{$rastermap->getScriptTag()}
			{literal}
			<script type="text/javascript">
				function updateMapMarkers() {
					updateMapMarker(document.theForm.grid_reference,false,true);
					updateMapMarker(document.theForm.photographer_gridref,false,true);
				}
				AttachEvent(window,'load',updateMapMarkers,false);
			</script>
			{/literal}
		
	{else} 
		<script type="text/javascript" src="{"/mapping.js"|revision}"></script>
	{/if}
	
 		


<form method="post" action="javascript:void()" name="theForm" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0; border-top:none">
<label for="grid_reference"><b style="color:#0018F8">New Grid Reference</b> </label><br/>
<input type="text" id="grid_reference" name="grid_reference" size="14" value="{$gridref|escape:'html'}" onkeyup="updateMapMarker(this,false,true)" onpaste="updateMapMarker(this,false,true)"/>{if $rastermap->reference_index == 1}<img src="http://{$static_host}/img/icons/circle.png" alt="Marks new position" width="29" height="29" align="middle"/>{else}<img src="http://www.google.com/intl/en_ALL/mapfiles/marker.png" alt="Marks new position" width="20" height="34" align="middle"/>{/if}
<br/>
<span style="font-size:0.6em">
| <a href="javascript:void(mapMarkerToCenter(document.theForm.grid_reference));void(updateMapMarker(document.theForm.grid_reference,false,true));">Marker to Center</a>
| <a href="javascript:void(copyGridRef());">Reset to initial values</a> | 
</span>
</p>

<p>
<label for="photographer_gridref"><b style="color:#002E73">Old Grid Reference</b></label><br/>
<input type="text" id="photographer_gridref" name="photographer_gridref" size="14" value="{$gridref|escape:'html'}" readonly="readonly"/>{if $rastermap->reference_index == 1}<img src="http://{$static_host}/img/icons/viewc--1.png" alt="Marks old position" width="29" height="29" align="middle"/>{else}<img src="http://{$static_host}/img/icons/camicon.png" alt="Marks old position" width="20" height="34" align="middle"/>{/if}

	{literal}
	<script type="text/javascript">
		function copyGridRef() {
			document.theForm.grid_reference.value = document.theForm.photographer_gridref.value;
			updateMapMarker(document.theForm.grid_reference,false,true);
			return false;
		}
	</script>
	{/literal}
</p>
{if !$rastermap->enabled}
{literal}
<script type="text/javascript">

</script>
{/literal}
{/if}
</form>
</div>
{if $haveogdb}
<div>
<form method="post" action="{$script_name}">
<input type="text"   name="findlimit" size="10" value="10000">
<input type="submit" name="findlarge" value="Find large towns in opengeodb"><br />
<input type="submit" name="findsim" value="Find similar towns in opengeodb"><br />
{*<hr />*}
<br />
<textarea name="findlist" cols="50" rows="5"></textarea><br />
<input type="submit" name="findgiven" value="Find given towns in opengeodb">
</form>
</div>
{if $dbarr}
<hr />
<div>
<form method="post" action="javascript:void()" name="editline" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0; border-top:none">
	<table>
	<tr><th></th><th>Name</th><th>Short name</th><th>Size</th><th>Align</th><th>Grid</th><th>East</th><th>North</th><th>Comm. Id</th><th></th></tr>
		<tr>
		<td>
			Edit
		</td>
		<td>
			<input type=text name="newna0" size=30 value="">
		</td>
		<td>
			<input type=text name="newsn0" size=30 value="">
		</td>
		<td>
			<select name="news0" id="news0">
			<option value="1" >1: state level</option>
			<option value="2" >2</option>
			<option value="3" >3: county level</option>
			<option value="4" selected>4</option>
			</select>
		</td>
		<td>
			<select name="newq0" id="newq0">
			<option value="-1" >-1: right/bottom if possible</option>
			<option value="0" selected>0: auto</option>
			<option value="1" >1: right/top</option>
			<option value="2" >2: left/top</option>
			<option value="3" >3: right/bottom</option>
			<option value="4" >4: left/bottom</option>
			</select>
		</td>
		<td>
			<input type=hidden name="oldr0" value="">
			<select name="newr0" id="newr0">
			{foreach item=curriname key=curri from=$ris}
			<option value="{$curri}" >{$curri}: {$curriname}</option>
			{/foreach}
			</select>
		</td>
		<td>
			<input type=hidden name="olde0" value="">
			<input type=text name="newe0" size=10 value="">
		</td>
		<td>
			<input type=hidden name="oldn0" value="">
			<input type=text name="newn0" size=10 value="">
		</td>
		<td>
			<input type=text name="newc0" size=10 value="">
		</td>
		<td>
			<input type=button value="To map" onclick="tomap(this,0)">
			<input type=button value="From map" onclick="frommap(this,0)">
		</td>
		</tr>
	</table>
</form>
</div>
{/if}
{/if}

<hr />
<div>


	<form action="{$script_name}" method="post">
	{*
	<p>Filter: <input type=text name=q value="{$q}"> (seperate words with spaces) </p>
	<hr/>*}

<div style="height:{if $dbarr}20{else}40{/if}em;overflow:scroll;">
	<table>
	<tr><th>Id</th><th>Name</th><th>Short name</th><th>Size</th><th>Align</th><th>Grid</th><th>East</th><th>North</th><th>Comm. Id</th><th></th></tr>
	{foreach key=key item=row from=$arr name=loop}
		<tr>
		<td>
			<input type=hidden name="oldi{$smarty.foreach.loop.iteration}" value="{$row.id}">
			{$row.id}
		</td>
		<td>
			<input type=hidden name="oldna{$smarty.foreach.loop.iteration}" value="{$row.name}">
			<input type=text name="newna{$smarty.foreach.loop.iteration}" size=30 value="{$row.name}" onfocus="onf(this)" onblur="onb(this,{$smarty.foreach.loop.iteration},'na')">
			<input type=button value="R" onclick="oncl(this,{$smarty.foreach.loop.iteration},'na')">
			{if $dbarr}
			<input type=button value="G" onclick="getval(this,{$smarty.foreach.loop.iteration},'na')">
			<input type=button value="P" onclick="putval(this,{$smarty.foreach.loop.iteration},'na')">
			{/if}
		</td>
		<td>
			<input type=hidden name="oldsn{$smarty.foreach.loop.iteration}" value="{$row.short_name}">
			<input type=text name="newsn{$smarty.foreach.loop.iteration}" size=30 value="{$row.short_name}" onfocus="onf(this)" onblur="onb(this,{$smarty.foreach.loop.iteration},'sn')">
			<input type=button value="R" onclick="oncl(this,{$smarty.foreach.loop.iteration},'sn')">
			{if $dbarr}
			<input type=button value="G" onclick="getval(this,{$smarty.foreach.loop.iteration},'sn')">
			<input type=button value="P" onclick="putval(this,{$smarty.foreach.loop.iteration},'sn')">
			{/if}
		</td>
		<td>
			<input type=hidden name="olds{$smarty.foreach.loop.iteration}" value="{$row.s}">
			<select name="news{$smarty.foreach.loop.iteration}" id="news{$smarty.foreach.loop.iteration}" onfocus="onf(this)" onblur="onb(this,{$smarty.foreach.loop.iteration},'s')">
			<option value="1" {if $row.s eq '1'} selected{/if}>1: state level</option>
			<option value="2" {if $row.s eq '2'} selected{/if}>2</option>
			<option value="3" {if $row.s eq '3'} selected{/if}>3: county level</option>
			<option value="4" {if $row.s eq '4'} selected{/if}>4</option>
			</select>
			<input type=button value="R" onclick="oncl(this,{$smarty.foreach.loop.iteration},'s')">
			{if $dbarr}
			<input type=button value="G" onclick="getval(this,{$smarty.foreach.loop.iteration},'s')">
			<input type=button value="P" onclick="putval(this,{$smarty.foreach.loop.iteration},'s')">
			{/if}
		</td>
		<td>
			<input type=hidden name="oldq{$smarty.foreach.loop.iteration}" value="{$row.quad}">
			<select name="newq{$smarty.foreach.loop.iteration}" id="newq{$smarty.foreach.loop.iteration}" onfocus="onf(this)" onblur="onb(this,{$smarty.foreach.loop.iteration},'q')">
			<option value="-1" {if $row.quad < 0} selected{/if}>-1: right/bottom if possible</option>
			<option value="0" {if $row.quad eq '0'} selected{/if}>0: auto</option>
			<option value="1" {if $row.quad eq '1'} selected{/if}>1: right/top</option>
			<option value="2" {if $row.quad eq '2'} selected{/if}>2: left/top</option>
			<option value="3" {if $row.quad eq '3'} selected{/if}>3: right/bottom</option>
			<option value="4" {if $row.quad eq '4'} selected{/if}>4: left/bottom</option>
			</select>
			<input type=button value="R" onclick="oncl(this,{$smarty.foreach.loop.iteration},'q')">
			{if $dbarr}
			<input type=button value="G" onclick="getval(this,{$smarty.foreach.loop.iteration},'q')">
			<input type=button value="P" onclick="putval(this,{$smarty.foreach.loop.iteration},'q')">
			{/if}
		</td>
		<td>
			<input type=hidden name="oldr{$smarty.foreach.loop.iteration}" value="{$row.reference_index}">
			<select name="newr{$smarty.foreach.loop.iteration}" id="newr{$smarty.foreach.loop.iteration}" onfocus="onf(this)" onblur="onb(this,{$smarty.foreach.loop.iteration},'r')">
			{foreach item=curriname key=curri from=$ris}
			<option value="{$curri}" {if $row.reference_index eq $curri} selected{/if}>{$curri}: {$curriname}</option>
			{/foreach}
			</select>
			<input type=button value="R" onclick="oncl(this,{$smarty.foreach.loop.iteration},'r')">
			{if $dbarr}
			<input type=button value="G" onclick="getval(this,{$smarty.foreach.loop.iteration},'r')">
			<input type=button value="P" onclick="putval(this,{$smarty.foreach.loop.iteration},'r')">
			{/if}
		</td>
		<td>
			<input type=hidden name="olde{$smarty.foreach.loop.iteration}" value="{$row.e}">
			<input type=text name="newe{$smarty.foreach.loop.iteration}" size=10 value="{$row.e}" onfocus="onf(this)" onblur="onb(this,{$smarty.foreach.loop.iteration},'e')">
			<input type=button value="R" onclick="oncl(this,{$smarty.foreach.loop.iteration},'e')">
			{if $dbarr}
			<input type=button value="G" onclick="getval(this,{$smarty.foreach.loop.iteration},'e')">
			<input type=button value="P" onclick="putval(this,{$smarty.foreach.loop.iteration},'e')">
			{/if}
		</td>
		<td>
			<input type=hidden name="oldn{$smarty.foreach.loop.iteration}" value="{$row.n}">
			<input type=text name="newn{$smarty.foreach.loop.iteration}" size=10 value="{$row.n}" onfocus="onf(this)" onblur="onb(this,{$smarty.foreach.loop.iteration},'n')">
			<input type=button value="R" onclick="oncl(this,{$smarty.foreach.loop.iteration},'n')">
			{if $dbarr}
			<input type=button value="G" onclick="getval(this,{$smarty.foreach.loop.iteration},'n')">
			<input type=button value="P" onclick="putval(this,{$smarty.foreach.loop.iteration},'n')">
			{/if}
		</td>
		<td>
			<input type=hidden name="oldc{$smarty.foreach.loop.iteration}" value="{$row.community_id}">
			<input type=text name="newc{$smarty.foreach.loop.iteration}" size=10 value="{$row.community_id}" onfocus="onf(this)" onblur="onb(this,{$smarty.foreach.loop.iteration},'c')">
			<input type=button value="R" onclick="oncl(this,{$smarty.foreach.loop.iteration},'c')">
			{if $dbarr}
			<input type=button value="G" onclick="getval(this,{$smarty.foreach.loop.iteration},'c')">
			<input type=button value="P" onclick="putval(this,{$smarty.foreach.loop.iteration},'c')">
			{/if}
		</td>
		<td>
			<input type=hidden name="oldd{$smarty.foreach.loop.iteration}" value="">
			{if $row.id}
			<label for="newd{$smarty.foreach.loop.iteration}">delete</label>
			<input type="checkbox" name="newd{$smarty.foreach.loop.iteration}" value="1" onfocus="onf(this)" onblur="onb(this,{$smarty.foreach.loop.iteration},'d')">
			{else}
			<input type=hidden name="newd{$smarty.foreach.loop.iteration}" value="">
			{/if}
			<input type=button value="To map" onclick="tomap(this,{$smarty.foreach.loop.iteration})">
			<input type=button value="From map" onclick="frommap(this,{$smarty.foreach.loop.iteration})">
			{if $dbarr}
			<input type=button value="G" onclick="getall(this,{$smarty.foreach.loop.iteration})">
			<input type=button value="P" onclick="putall(this,{$smarty.foreach.loop.iteration})">
			{/if}
		</td>
		</tr>
	{/foreach}
	</table>
</div>
	<input type=hidden name=highc value="{$smarty.foreach.loop.total}">
	<label for="dryrun">Test run</label>
	<input type="checkbox" name="dryrun" value="1"><br />
	<input type=submit name=submit value="Commit Changes">
	</form>   
</div>
{if $dbarr}
<hr />
<div style="height:20em;overflow:scroll;">
<form method="post" action="javascript:void()" name="dbarr">
	<table>
	<tr><th></th><th>Name</th><th>Size</th><th>Grid</th><th>East</th><th>North</th><th>Comm. Id</th><th></th></tr>
	{foreach key=key item=row from=$dbarr name=dbloop}
		<tr>
		<td>
			{$key}
		</td>
		<td>
			<input type=hidden name="dbna{$smarty.foreach.dbloop.iteration}" value="{$row.name}">
			{$row.name}
		</td>
		<td>
			{$row.size}
		</td>
		<td>
			{*foreach item=curriname key=curri from=$ris}
			<option value="{$curri}" {if $row.reference_index eq $curri} selected{/if}>{$curri}: {$curriname}</option>
			{/foreach*}
			<input type=hidden name="dbr{$smarty.foreach.dbloop.iteration}" value="{$row.reference_index}">
			{$row.reference_index}: {$ris[$row.reference_index]}
		</td>
		<td>
			<input type=hidden name="dbe{$smarty.foreach.dbloop.iteration}" value="{$row.e}">
			{$row.e}
		</td>
		<td>
			<input type=hidden name="dbn{$smarty.foreach.dbloop.iteration}" value="{$row.n}">
			{$row.n}
		</td>
		<td>
			<input type=hidden name="dbc{$smarty.foreach.dbloop.iteration}" value="{$row.cid}">
			{$row.cid}
		</td>
		<td>
			{*<input type=button value="To map" onclick="dbtomap(this,{$smarty.foreach.dbloop.iteration})">*}
			<input type=button value="To edit" onclick="dbtoedit(this,{$smarty.foreach.dbloop.iteration})">
		</td>
		</tr>
	{/foreach}
	</table>
</form>
</div>
{/if}
   
    
   {literal} 
<script>
var selectedItem;

function onf(that) {
	selectedItem = that;
	//that.style.backgroundColor = 'yellow';
	//that.form.list.selectedIndex = 0;
}

function onb(that,num,suf) {
	selectedItem.style.backgroundColor = (that.form['old'+suf+num].value == that.value)?'':'lightgrey';
	//that.form.list.selectedIndex = 0;

}

function oncl(that,num,suf) {
	that.form['new'+suf+num].value = that.form['old'+suf+num].value;
	that.form['new'+suf+num].style.backgroundColor = '';
}

function dbtoedit(that,num) {
	document.editline.newe0.value = that.form['dbe'+num].value;
	document.editline.newn0.value = that.form['dbn'+num].value;
	document.editline.newc0.value = that.form['dbc'+num].value;
	document.editline.newr0.value = that.form['dbr'+num].value;
	document.editline.newna0.value = that.form['dbna'+num].value;
	document.editline.newsn0.value = that.form['dbna'+num].value;
}

function putval(that,num,suf) {
	document.editline['new'+suf+0].value = that.form['new'+suf+num].value;
}

function getval(that,num,suf) {
	that.form['new'+suf+num].value = document.editline['new'+suf+0].value;
	that.form['new'+suf+num].style.backgroundColor = (that.form['old'+suf+num].value == that.form['new'+suf+num].value)?'':'lightgrey';
}

function putall(that,num,suf) {
	var slist = [ 'na','sn','s','q','r','e','n','c' ];
	//for each (var suf in slist)
	//	putval(that,num,suf);
	for (var idx in slist)
		putval(that,num,slist[idx]);
}

function getall(that,num,suf) {
	var slist = [ 'na','sn','s','q','r','e','n','c' ];
	//for each (var suf in slist)
	//	getval(that,num,suf);
	for (var idx in slist)
		getval(that,num,slist[idx]);
}

function tomap(that,num) {
	var oe = that.form['olde'+num].value;
	var on = that.form['oldn'+num].value;
	var or = that.form['oldr'+num].value;
	var ne = that.form['newe'+num].value;
	var nn = that.form['newn'+num].value;
	var nr = that.form['newr'+num].value;
	if (oe == '' && on == '') {
		oe = ne;
		on = nn;
		or = nr;
	}
	var ogrid;
	var ngrid;
	//alert(""+nr+"_"+or);
	if (or == 1) //FIXME ->function in geotools2.js?
		ogrid=new GT_OSGB();
	else if (or == 2)
		ogrid=new GT_Irish();
	else if (or == 3)
		ogrid=new GT_German32();
	else if (or == 4)
		ogrid=new GT_German33();
	else if (or == 5)
		ogrid=new GT_German31();
	else return;
	if (nr == 1) //FIXME ->function in geotools2.js?
		ngrid=new GT_OSGB();
	else if (nr == 2)
		ngrid=new GT_Irish();
	else if (nr == 3)
		ngrid=new GT_German32();
	else if (nr == 4)
		ngrid=new GT_German33();
	else if (nr == 5)
		ngrid=new GT_German31();
	else return;
	ogrid.setGridCoordinates(oe, on);
	ngrid.setGridCoordinates(ne, nn);
	document.theForm.grid_reference.value = ngrid.getGridRef(5);
	document.theForm.photographer_gridref.value = ogrid.getGridRef(5);
	updateMapMarker(document.theForm.grid_reference,false,true);
	updateMapMarker(document.theForm.photographer_gridref,false,true);
	var wgs = ngrid.getWGS84();
	var point = new GLatLng(wgs.latitude,wgs.longitude);
	map.setCenter(point);
}


function frommap(that,num) {
	var gridref = theForm.grid_reference.value.trim().toUpperCase();
	var grid=new GT_OSGB();
	var ok = false;
	var nr;
	if (grid.parseGridRef(gridref)) {//FIXME ->function in geotools2.js?
		ok = true;
		nr = 1;
	} else {
		grid=new GT_Irish();
		if (grid.parseGridRef(gridref)) {
			ok = true;
			nr = 2;
		} else {
			grid=new GT_German32();
			if (grid.parseGridRef(gridref)) {
				ok = true;
				nr = 3;
			} else {
				grid=new GT_German33();
				if (grid.parseGridRef(gridref)) {
					ok = true;
					nr = 4;
				} else {
					grid=new GT_German31();
					ok = grid.parseGridRef(gridref)
					nr = 5;
				}
			}
		}
	}
	if (!ok)
		return;
	that.form['newe'+num].value = grid.eastings;
	that.form['newn'+num].value = grid.northings;
	that.form['newr'+num].value = nr;
	if (num) {
		that.form['newe'+num].style.backgroundColor = (that.form['olde'+num].value == that.form['newe'+num].value)?'':'lightgrey';
		that.form['newn'+num].style.backgroundColor = (that.form['oldn'+num].value == that.form['newn'+num].value)?'':'lightgrey';
		that.form['newr'+num].style.backgroundColor = (that.form['oldr'+num].value == that.form['newr'+num].value)?'':'lightgrey';
		tomap(that,num);
	}
}

/*function onc(that) {
	selectedItem.value = that.options[that.selectedIndex].value;
	selectedItem.focus();
}*/
</script>
{/literal}

{if $rastermap->enabled}
	{$rastermap->getFooterTag()}
{/if}

{/dynamic}    
{include file="_std_end.tpl"}
