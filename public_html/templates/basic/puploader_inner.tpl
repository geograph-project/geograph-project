{include file="_basic_begin.tpl"}
{dynamic}

<form enctype="multipart/form-data" action="{$script_name}{if $submit2}?submit2=1{/if}" method="post" name="theForm" onsubmit="if (this.imageclass) this.imageclass.disabled=false;" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;">
	<input type="hidden" name="inner" value="1"/>

	{if $errormsg}
	<p style="color:#990000;font-weight:bold;">{$errormsg}</p>
	{/if}

{if $step eq 1}	
	{if !$submit2}
	<script type="text/javascript">window.parent.tabClick('tab','',1,4);</script>
	{/if}
	<p>Begin by choosing the grid square you wish to submit.</p>

	<p><b>Note:</b> this should be the location of the primary <i>subject</i> of the photo, if you wish you can specify a photographer location in the next step.</p>

	<p><label for="grid_reference">Enter the grid reference 
	(<u title="e.g. TQ4364 or TQ 43 64">4</u>,
	<u title="e.g. TQ435646 or TQ 435 646">6</u>,
	<u title="e.g. TQ43526467 or TQ 4352 6467">8</u> or 
	<u title="e.g. TQ4352364673 or TQ 43523 64673">10</u> figure) for the subject grid square</label><br /><br />
	{if $grid_reference}<small><small>(<a href="javascript:void(document.getElementById('grid_reference').value = '');">clear</a>)<br/></small></small>{/if}
	<input id="grid_reference" type="text" name="grid_reference" value="{$grid_reference|escape:'html'}" size="14"/><small class="navButtons"><small><a href="javascript:doMove('grid_reference',-1,0);">W</a></small><sup><a href="javascript:doMove('grid_reference',0,1);">N</a></sup><sub><a href="javascript:doMove('grid_reference',0,-1);">S</a></sub><small><a href="javascript:doMove('grid_reference',1,0);">E</a></small></small>
	&nbsp;&nbsp;&nbsp;
	<input type="submit" name="setpos" value="Next &gt;"/> {if $picnik_api_key}or <input type="submit" name="picnik" value="Upload via Picnik &gt;"/>{/if}
	</p>
	{if $service}
		<input type="hidden" name="service" value="{$service|escape:'html'}"/>
	{/if}
	<script type="text/javascript" src="{"/mapping1.js"|revision}"></script>
	<script type="text/javascript" src="{"/mapper/geotools2.js"|revision}"></script>
{elseif $step eq 2}
	{if !$submit2}
	<script type="text/javascript">window.parent.tabClick('tab','',3,4);</script>
	{/if}
	{if $rastermap->enabled}
		<div style="float:left;width:50%;position:relative">
	{else}
		<div>
	{/if}
	
		{if $reference_index == 2} 
		{external href="http://www.multimap.com/maps/?zoom=15&countryCode=GB&lat=`$lat`&lon=`$long`&dp=904|#map=`$lat`,`$long`|15|4&dp=925&bd=useful_information||United%20Kingdom" text="multimap.com" title="multimap includes 1:50,000 mapping for Northern Ireland" target="_blank"} includes 1:50,000 mapping for Northern Ireland.
		{/if}

		<h4><b>Grid References:</b> (recommended) {$grid_reference|escape:'html'}</h4>
		<p><label for="grid_reference"><b style="color:#0018F8">Primary Photo Subject</b></label> <input id="grid_reference" type="text" name="grid_reference" value="{if $square->natspecified}{$grid_reference|escape:'html'}{/if}" size="14" onkeyup="updateMapMarker(this,false)"/>{if $rastermap->reference_index == 1}<img src="http://{$static_host}/img/icons/circle.png" alt="Marks the Subject" width="29" height="29" align="middle"/>{else}<img src="http://www.google.com/intl/en_ALL/mapfiles/marker.png" alt="Marks the Subject" width="20" height="34" align="middle"/>{/if}</p>

		<p><label for="photographer_gridref"><b style="color:#002E73">Photographer Position</b></label> <input id="photographer_gridref" type="text" name="photographer_gridref" value="{$photographer_gridref|escape:'html'}" size="14" onkeyup="updateMapMarker(this,false)"/>{if $rastermap->reference_index == 1}<img src="http://{$static_host}/img/icons/viewc--1.png" alt="Marks the Photographer" width="29" height="29" align="middle"/>{else}<img src="http://{$static_host}/img/icons/camicon.png" alt="Marks the Photographer" width="12" height="20" align="middle"/>{/if}

		<span style="font-size:0.8em"><br/><a href="javascript:void(document.theForm.photographer_gridref.value = document.theForm.grid_reference.value);void(updateMapMarker(document.theForm.photographer_gridref,false));" style="font-size:0.8em">Copy from Subject</a></span>

		{if $rastermap->enabled}
			<br/><br/><input type="checkbox" name="use6fig" id="use6fig" {if $use6fig} checked{/if} value="1" onclick="updateUse6fig(this)"/> <label for="use6fig">Only display 6 figure grid reference (<a href="/help/map_precision" target="_blank">Explanation</a>)</label>
		{/if}
		</p>

		<p><label for="view_direction"><b>View Direction</b></label> <small>(photographer facing)</small><br/>
		<select id="view_direction" name="view_direction" style="font-family:monospace" onchange="updateCamIcon(this);">
			{foreach from=$dirs key=key item=value}
				<option value="{$key}"{if $key%45!=0} style="color:gray"{/if}{if $key==$view_direction} selected="selected"{/if}>{$value}</option>
			{/foreach}
		</select></p>
	</div>

	{if $rastermap->enabled}
		<div class="rastermap" style="width:45%;position:relative">
		{$rastermap->getImageTag()}<br/>
		<b>{$rastermap->getTitle($gridref)}</b><br/>
		<span style="color:gray"><small>{$rastermap->getFootNote()}</small></span>
		{if $rastermap->service == 'Google'}
			<a href="#" onclick="this.style.display='none';document.getElementById('map').style.width = '100%';document.getElementById('map').style.height = '400px';map.checkResize();return false">Enlarge Map</a>
		{/if}{*FIXME move to FootNote?*}
		
		</div>

			{literal}
			<script type="text/javascript">
				function updateMapMarkers() {
					updateMapMarker(document.theForm.grid_reference,false,true);
					updateMapMarker(document.theForm.photographer_gridref,false,true);
					if (document.theForm.view_direction) {
						updateViewDirection();
					}
				}
				//deferred till after setupTheForm
				//AttachEvent(window,'load',updateMapMarkers,false);
			</script>
			{/literal}
		{$rastermap->getScriptTag()}
		
	{else} 
		<script type="text/javascript" src="{"/mapping.js"|revision}"></script>
	{/if}
	<br style="clear:both"/>
	{if $submit2}
		<input type="button" value="Done" onclick="if (checkFormSubmission(this.form,{if $rastermap->enabled}true{else}false{/if}{literal})) { window.parent.doneStep(2);} else {return false;}{/literal}"/>
		<input type="button" value="Next Step &gt;&gt;" onclick="if (checkFormSubmission(this.form,{if $rastermap->enabled}true{else}false{/if}{literal})) { window.parent.doneStep(2); window.parent.clicker(3,true);} else {return false;}{/literal}"/>
	{/if}
	{if $rastermap->enabled}
		{$rastermap->getFooterTag()}
	{/if}
{elseif $step eq 3}
	{if !$submit2}
	<script type="text/javascript">window.parent.tabClick('tab','',4,4);</script>
	{/if}
	
	{if $reopenmaptoken}
	<div class="interestBox" style="z-index:0"><a href="/submit_popup.php?t={$reopenmaptoken|escape:'html'}" target="gmappreview" onclick="window.open(this.href,this.target,'width=650,height=500,scrollbars=yes'); return false;">Reopen Map in a popup</a> <small>(and view list of placenames)</small>
	<a href="/gridref/{$square->grid_reference}" target="_blank">Open {$square->grid_reference} Page</a> <small>(in new window)</small></div>
	{/if}
	
	<p>Please provide a short title for the image, and any other comments about where
	it was taken or other interesting geographical information. (Open <a href="/help/style" target="_blank" id="styleguidelink">Style Guide</a>)</p>

	<p><label for="title"><b>Title</b></label> {if $error.title}
		<br/><span class="formerror">{$error.title}</span>
		{/if}<br/>
	<input size="50" id="title" name="title" value="{$title|escape:'html'}" spellcheck="true" onblur="checkstyle(this,'title',true);" onkeyup="checkstyle(this,'title',false);"/> <span class="formerror" style="display:none" id="titlestyle">Possible style issue. See Guide above. <span id="titlestylet" style="font-size:0.9em"></span></span></p>
	 {if $place.distance}
	 <p style="font-size:0.7em">Gazetteer info as will appear:<br/> <span style="color:silver;">{place place=$place}</span></p>
	 {/if}

	<p style="clear:both"><label for="comment"><b>Comment</b></label> <span class="formerror" style="display:none" id="commentstyle">Possible style issue. See Guide above. <span id="commentstylet"></span></span><br/>
	<textarea id="comment" name="comment" rows="7" cols="80" spellcheck="true" onblur="checkstyle(this,'comment',true);" onkeyup="checkstyle(this,'comment',false);">{$comment|escape:'html'}</textarea></p>
	<div style="font-size:0.7em">TIP: use <span style="color:blue">[[TQ7506]]</span> or <span style="color:blue">[[5463]]</span> to link 
	to a Grid Square or another Image.<br/>For a weblink just enter directly like: <span style="color:blue">http://www.example.com</span></div>


	{literal}
	<script type="text/javascript">
	<!--
	//rest loaded in geograph.js
	function mouseOverImageClass() {
		if (!hasloaded) {
			setTimeout("prePopulateImageclass2()",100);
		}
		hasloaded = true;
	}

	function prePopulateImageclass2() {
		var sel=document.getElementById('imageclass');
		sel.disabled = false;
		var oldText = sel.options[0].text;
		sel.options[0].text = "please wait...";

		populateImageclass();

		hasloaded = true;
		sel.options[0].text = oldText;
		if (document.getElementById('imageclass_enable_button'))
			document.getElementById('imageclass_enable_button').disabled = true;
	}
	AttachEvent(window,'load',onChangeImageclass,false);
	//-->
	</script>
	{/literal}

	<p><label for="imageclass"><b>Primary geographical category</b></label> {if $error.imageclass}
		<br/><span class="formerror">{$error.imageclass}</span>
		{/if}<br />	
		<select id="imageclass" name="imageclass" onchange="onChangeImageclass()" onfocus="prePopulateImageclass()" onmouseover="mouseOverImageClass()" style="width:300px">
			<option value="">--please select feature--</option>
			{if $imageclass}
				<option value="{$imageclass}" selected="selected">{$imageclass}</option>
			{/if}
			<option value="Other">Other...</option>
		</select>

		<span id="otherblock">
		<label for="imageclassother">Please specify </label> 
		<input size="32" id="imageclassother" name="imageclassother" value="{$imageclassother|escape:'html'}" maxlength="32" spellcheck="true"/>
		</span></p>




	<p><label><b>Date photo taken</b></label> {if $error.imagetaken}
		<br/><span class="formerror">{$error.imagetaken}</span>
		{/if}<br/>
		{html_select_date prefix="imagetaken" time=$imagetaken start_year="-200" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY"}
		{if $imagetakenmessage}
		    {$imagetakenmessage}
		{/if}

		[ Use 
		<input type="button" value="Today's" onclick="setdate('imagetaken','{$today_imagetaken}',this.form);parentUpdateVariables()" class="accept"/>
		{if $last_imagetaken}
			<input type="button" value="Last Submitted" onclick="setdate('imagetaken','{$last_imagetaken}',this.form);parentUpdateVariables()" class="accept"/>
		{/if}
		{if $imagetaken != '--' && $imagetaken != '0000-00-00'}
			<input type="button" value="Current" onclick="setdate('imagetaken','{$imagetaken}',this.form);parentUpdateVariables()" class="accept"/>
		{/if}
		Date ]

		<br/><br/><span style="font-size:0.7em">(please provide as much detail as possible, if you only know the year or month then that's fine)</span></p>

	{if $submit2}
		<input type="button" value="Done" onclick="window.parent.doneStep(3);"/>
		<input type="button" value="Next Step &gt;&gt;" onclick="window.parent.doneStep(3); window.parent.clicker(4,true);"/>
	{/if}

	<script type="text/javascript" src="/categories.js.php"></script>
	<script type="text/javascript" src="/categories.js.php?full=1&amp;u={$user->user_id}"></script>

{/if}


{/dynamic}
<script type="text/javascript" src="{"/js/puploader.js"|revision}"></script>
{literal}
<script type="text/javascript">
	AttachEvent(window,'load',function() { 
		setupTheForm(); 
		if (typeof updateMapMarkers == 'function') { 
			updateMapMarkers(); 
		}
	},false);
	
	function setTakenDate(value) {
		setdate('imagetaken',value,document.forms['theForm']);
	}
</script>
{/literal}

</form>
</body>
</html>
