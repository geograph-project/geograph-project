{include file="_basic_begin.tpl"}

{dynamic}
<form enctype="multipart/form-data" action="{$script_name}?{if $submit2}submit2=1{/if}{if $container}&amp;container={$container|escape:'url'}{/if}" method="post" name="theForm" onsubmit="if (this.imageclass) this.imageclass.disabled=false;" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;">
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

		<h4><b>Grid References:</b> (recommended)</h4>
		<p><label for="grid_reference"><b style="color:#0018F8">Primary Photo Subject</b></label> <input id="grid_reference" type="text" name="grid_reference" value="{if $square->natspecified}{$grid_reference|escape:'html'}{/if}" size="14" onkeyup="updateMapMarker(this,false)" onpaste="{literal}that=this;setTimeout(function(){updateMapMarker(that,false);},50){/literal}" onmouseup="updateMapMarker(this,false)" oninput="updateMapMarker(this,false)"/>{if $rastermap->service == 'OSOS'}<a href="javascript:void(nudgeMarker(document.theForm.grid_reference))"><img src="http://{$static_host}/img/icons/circle.png" alt="Marks the Subject" width="29" height="29" align="middle"/></a>{elseif $rastermap->service == 'Google'}<img src="http://www.google.com/intl/en_ALL/mapfiles/marker.png" alt="Marks the Subject" width="20" height="34" align="middle"/>{else}<img src="http://{$static_host}/img/icons/circle.png" alt="Marks the Subject" width="29" height="29" align="middle"/>{/if}</p>

		<p><label for="photographer_gridref"><b style="color:#002E73">Photographer Position</b></label> <input id="photographer_gridref" type="text" name="photographer_gridref" value="{$photographer_gridref|escape:'html'}" size="14" onkeyup="updateMapMarker(this,false)" onpaste="{literal}that=this;setTimeout(function(){updateMapMarker(that,false);},50){/literal}" onmouseup="updateMapMarker(this,false)" oninput="updateMapMarker(this,false)"/>{if $rastermap->service == 'OSOS'}<a href="javascript:void(nudgeMarker(document.theForm.photographer_gridref))"><img src="http://{$static_host}/img/icons/viewc--1.png" alt="Marks the Photographer" width="29" height="29" align="middle"/></a>{elseif $rastermap->service == 'Google'}<img src="http://{$static_host}/img/icons/camicon.png" alt="Marks the Photographer" width="12" height="20" align="middle"/>{else}<img src="http://{$static_host}/img/icons/viewc--1.png" alt="Marks the Photographer" width="29" height="29" align="middle"/>{/if}

		<span style="font-size:0.8em"><br/><a href="javascript:void(document.theForm.photographer_gridref.value = document.theForm.grid_reference.value);void(updateMapMarker(document.theForm.photographer_gridref,false));void(parentUpdateVariables());" style="font-size:0.8em">Copy from Subject</a> {if $rastermap->service == 'Google'}<a href="javascript:void(relocateMapToMarkers());" style="font-size:0.8em">Re-Centre Map</a>{/if} <span id="dist_message" style="padding-left:20px"></span></span>

		{if $rastermap->enabled}
			<br/><br/><input type="checkbox" name="use6fig" id="use6fig" {if $use6fig} checked{/if} value="1" onclick="updateUse6fig(this)"/> <label for="use6fig">Only display 6 figure grid reference</label> <a href="/help/map_precision" title="Explanation" class="about" target="_blank" style="font-size:0.6em">About</a>
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
		{/if}
		</div>

			{literal}
			<script type="text/javascript">
				function updateMapMarkersP() {
					updateMapMarker(document.theForm.grid_reference,false,true);
					updateMapMarker(document.theForm.photographer_gridref,false,true);
					if (document.theForm.view_direction) {
						updateViewDirection();
					}
				}
				//deferred till after setupTheForm
				//AttachEvent(window,'load',updateMapMarkersP,false);


				function nudgeMarker(that) {

					if (that.name == 'photographer_gridref') {
						currentelement = marker2;
						var point = new OpenSpace.MapPoint(eastings2, northings2-3);
					} else {
						currentelement = marker1;
						var point = new OpenSpace.MapPoint(eastings1, northings1-3);
					}

					if ((currentelement == null) && map) {
						currentelement = createMarker(point,null);

						//GEvent.trigger(currentelement,'drag');
					} else {
						map.removeMarker(currentelement);
						currentelement = createMarker(point,that.name == 'photographer_gridref'?picon:null);
					}
				}
			</script>
			{/literal}
		{$rastermap->getScriptTag()}

	{else}
		<script type="text/javascript" src="{"/mapping.js"|revision}"></script>
	{/if}
	<br style="clear:both"/>
	{if $submit2}
		<input type="button" value="Done" onclick="if (checkFormSubmission(this.form,{if $rastermap->enabled}true{else}false{/if}{literal})) { window.parent.doneStep(2);} else {return false;}{/literal}"/>
		<input type="button" value="Next Step &gt;&gt;" onclick="if (checkFormSubmission(this.form,{if $rastermap->enabled}true{else}false{/if}{literal})) { window.parent.doneStep(2); window.parent.clicker(3,true);} else {return false;}{/literal}"/><br/>
		<br><small><a href="{$script_name}?inner&amp;submit2&amp;step=1&amp;grid_reference={$grid_reference}">&lt; Enter a Grid reference</a> or <a href="/submitmap.php?inner&amp;submit2&amp;grid_reference={$grid_reference}{if $container}&amp;container={$container|escape:'url'}{/if}">Back to Map Overview Map</a></small>
	{/if}
	{if $rastermap->enabled}
		{$rastermap->getFooterTag()}
	{/if}
{elseif $step eq 3}

{$status_message}

	{if !$submit2}
	<script type="text/javascript">window.parent.tabClick('tab','',4,4);</script>
	{/if}

	{if $reopenmaptoken}
	<div class="interestBox" style="z-index:0"><a href="/submit_popup.php?t={$reopenmaptoken|escape:'html'}" target="gmappreview" onclick="window.open(this.href,this.target,'width=650,height=500,scrollbars=yes'); return false;">Reopen Map in a popup</a> <small>(and view list of placenames)</small>,
	<a href="/gridref/{$square->grid_reference}" target="_blank">Open {$square->grid_reference} Page</a> <small>(in new window)</small></div>
	{/if}

	<p>Please provide a short title for the image, and any other comments about where
	it was taken or other interesting geographical information. (Open <a href="/help/style" target="_blank" id="styleguidelink">Style Guide</a>)</p>

	<p><label for="title"><b>Title</b></label> {if $error.title}
		<br/><span class="formerror">{$error.title}</span>
		{/if}<br/>
	<input size="50" id="title" name="title" value="{$title|escape:'html'}" disabled spellcheck="true" onblur="checkstyle(this,'title',true);" onkeyup="checkstyle(this,'title',false);"/> <span class="formerror" style="display:none" id="titlestyle">Possible style issue. See Guide above. <span id="titlestylet" style="font-size:0.9em"></span></span></p>
	 {if $place.distance}
	 <p style="font-size:0.7em">Gazetteer info as will appear:<br/> <span style="color:silver;">{place place=$place}</span></p>
	 {/if}

	<p style="clear:both"><label for="comment"><b>Description/Comment</b></label> <span class="formerror" style="display:none" id="commentstyle">Possible style issue. See Guide above. <span id="commentstylet"></span></span><br/>
	<textarea id="comment" name="comment" disabled rows="7" cols="80" spellcheck="true" onblur="checkstyle(this,'comment',true);" onkeyup="checkstyle(this,'comment',false);">{$comment|escape:'html'}</textarea></p>
	<div style="font-size:0.7em">TIP: use <span style="color:blue">[[TQ7506]]</span> to link to a Grid Square or <span style="color:blue">[[54631]]</span> to link to another Image.<br/>
	For a weblink just enter directly like: <span style="color:blue">http://www.example.com</span></div>


{if $submit2}
	{if $upload_id}
		<br/>

		<div class="tabHolder" style="font-size:1em">

			<a class="tabSelected nowrap" id="tab1" onclick="tabClick('tab','div',1,3)">Geographical Context</a>&nbsp;
			<a class="tab nowrap" id="tab2" onclick="tabClick('tab','div',2,3);show_tagging(document.forms['theForm'])">Tags <small>(Optional)</small></a>&nbsp;
			<a class="tab nowrap" id="tab3" onclick="tabClick('tab','div',3,3); document.getElementById('shareframe').src='/submit_snippet.php?upload_id={$upload_id}&gr={$grid_reference|escape:'html'}';">Shared Descriptions <small>(Optional)</small></a>
		</div>

		<div id="div3" class="interestBox" style="display:none">
			<iframe src="about:blank" height="400" width="100%" id="shareframe" style="border:0">
			</iframe>
		</div>

		<div id="div1" class="interestBox">
			<div style="float:right"><a href="/article/Transitioning-Categories-to-Tags" text="Article about new tags and categories" class="about" target="_blank">About Context/Tags</a></div>
			<div style="font-size:0.8em;padding-bottom:5px;">
			&middot; Tick as many Geogaphical Contexts as required.<br/>
			&middot; Hover over name for a description, see also <a href="/tags/primary.php" text="More examples" target="_blank">further details and examples</a><br/>
			&middot; If in doubt about the exact context, simply pick the best match from Natural Environment or Human Habitat.<br/>
			&middot; As we have been adding Context to the previous categories, you could also try <a href="/finder/categories.php" target="_blank">searching by the old category name here</a>.<br/>
			</div>

			{foreach from=$tops key=key item=item}
				<div class="plist">
					<div style="color:black">{$key}</div>
					{foreach from=$item item=row}
						<label for="c-{$row.top|escape:'url'}" title="{$row.description|escape:'html'}" id="l-{$row.top|escape:'url'}">
							<input type="checkbox" name="tags[]" value="top:{$row.top|escape:'html'}" id="c-{$row.top|escape:'url'}" onclick="rehighlight(this,true)"/>
							{$row.top|escape:'html'}
						</label>
					{/foreach}
					<br/>
				</div>
			{/foreach}
			<br style="clear:both"/>
			<small>(<a href="javascript:void(untick_all())">Untick All</a>)</small>

		</div>

		<div id="div2" class="interestBox" style="display:none">
			<div style="float:right"><a href="/article/Tags" title="Article about Tags" class="about" target="_blank">More about Tags</a></div>
			<div style="font-size:0.8em;padding-bottom:5px;">
			&middot; Tags are simple free-form keywords/short phrases used to describe the image.<br/>
			&middot; Please add as many Tags as you need. Tags will help other people find your photo.<br/>
			&middot; Tags should be singular, ie an image of a church should have the tag "church", not "churches"<br/> <small>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(however if a photo is of multiple say fence posts, then the tag "fence post<b>s</b>" should be used).</small><br/>
			&middot; To add a placename as a Tag, please prefix with "place:", eg "place:Croydon" - similarly could use "near:Tring".
			</div>

			<iframe src="about:blank" height="200" width="100%" id="tagframe" style="border:0">
			</iframe>
		</div>

{literal}
<script type="text/javascript">
function show_tagging(form) {
	var query = 'upload_id={/literal}{$upload_id}&gr={$grid_reference|escape:'html'}{literal}&v=3';
	if (form.elements['title'].value.length> 0 )
		query=query+'&title='+encodeURIComponent(form.elements['title'].value);
	for(q=0;q<form.elements['tags[]'].length;q++)
		if (form.elements['tags[]'][q].checked)
			query=query+'&tags[]='+encodeURIComponent(form.elements['tags[]'][q].value);
	if (form.elements['comment'].value.length> 0 )
		query=query+'&comment='+encodeURIComponent(form.elements['comment'].value.substr(0,1500).replace(/[\n\r]/,' '));
	document.getElementById('tagframe').src='/tags/tagger.php?'+query;
}
function rehighlight(that,check) {
	if (check) {
		var name=that.name;
		var ele = that.form.elements[name];
		count=-1; //the current one will already be checked
		for(q=0;q<ele.length;q++)
			if (ele[q].checked)
				count++;
		if (count > 5) {
			if (!confirm("Are you sure you wish to enable '"+that.value.replace(/top:/,'')+"'?\n\n You already have "+count+" ticked items, which is probably plenty!")) {
				that.checked = false;
			}
		}

	}
	var id = that.id.replace(/c-/,'l-');
	document.getElementById(id).style.fontWeight=that.checked?'bold':'normal';
	document.getElementById(id).style.backgroundColor=that.checked?'white':'';
}
</script>{/literal}

	{else}
		<p style="color:red">&middot; Further details can only be set once image has finished uploading,
		<a href="javascript:void(window.parent.clicker(3,false));void(window.parent.clicker(3,true));">close and re-open this step</a> once the image has uploaded.</p>
	{/if}
{/if}



	<p><label><b>Date photo taken</b></label> {if $error.imagetaken}
		<br/><span class="formerror">{$error.imagetaken}</span>
		{/if}<br/>
		{html_select_date prefix="imagetaken" time=$imagetaken start_year="-200" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY"}
		{if $imagetakenmessage}
		    {$imagetakenmessage}
		{/if}

		[ Use
		<input type="button" value="Today's" onclick="setdate('imagetaken','{$today_imagetaken}',this.form);parentUpdateVariables()"/>
		{if $last_imagetaken}
			<input type="button" value="Last Submitted" onclick="setdate('imagetaken','{$last_imagetaken}',this.form);parentUpdateVariables()"/>
		{/if}
		{if $imagetaken != '--' && $imagetaken != '0000-00-00'}
			<input type="button" value="Current" onclick="setdate('imagetaken','{$imagetaken}',this.form);parentUpdateVariables()"/>
		{/if}
		Date ]

		<br/><br/><span style="font-size:0.7em">(please provide as much detail as possible, if you only know the year or month then that's fine)</span></p>

	{if $submit2}
		<div style="position:relative;width:100px;float:right">
			<input type="button" value="Next Step &gt;&gt;" onclick="window.parent.doneStep(3); window.parent.clicker(4,true);"/>
		</div>
		<input type="button" value="Done" onclick="window.parent.doneStep(3);"/>
		<input type="button" value="Next Step &gt;&gt;" onclick="window.parent.doneStep(3); window.parent.clicker(4,true);"/>
	{/if}

{/if}


{/dynamic}
<script type="text/javascript" src="{"/js/puploader.js"|revision}"></script>
{literal}
<script type="text/javascript">
	AttachEvent(window,'load',function() {
		setupTheForm();
		if (typeof updateMapMarkersP == 'function') {
			updateMapMarkersP();
		}
	},false);

	function setTakenDate(value) {
		setdate('imagetaken',value,document.forms['theForm']);
	}
{/literal}

{dynamic}
{if $container}
	{literal}

	function resizeContainer() {
		var FramePageHeight =  document.body.offsetHeight + 10;
		window.parent.document.getElementById('{/literal}{$container|escape:'javascript'}{literal}').style.height=FramePageHeight+'px';
	}

	AttachEvent(window,'load',resizeContainer,false);
	{/literal}
{/if}
{/dynamic}
</script>


</form>
</body>
</html>
