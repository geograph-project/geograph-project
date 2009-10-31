{assign var="page_title" value="Submit no frills"}
{include file="_std_begin.tpl"}

<script type="text/javascript" src="{"/mapper/geotools2.js"|revision}"></script>
<script type="text/javascript" src="{"/js/puploader.js"|revision}"></script>

	<div style="float:right;position:relative">&middot; <a href="/help/submission">Alternative Submission Methods</a> &middot;</div>
	
	<h2>No Frills Submit - for experts only <sup style="color:red">Alpha</sup></h2> 
	
	<p>This submission process is designed to be very quick - and <b>very little validation of input is done - be careful</b>.</p>
	
	<p>In particular, there is no maps, no calculation of view direction, and category entry is a plain text box. The subject grid reference isnt even checked to see if a valid square - only fails after image submission.</p>
	
	<p>Please only use this process, if you double check your grid-references, category, and dates, as its very easy to input invalid data!</p>
	
<form action="/submit2.php?nofrills" name="theForm" method="post">

	
<!-- # -->	 
	<h3>Step 1 - Upload Photo</h3>
	
	<div id="sd1" class="sd" style="display:block">
		<iframe src="/submit2.php?inner&amp;step=1" id="iframe1" width="100%" height="220px" style="border:0"></iframe>
	</div>
	
	<h3>Step 2 - Enter Details</h3>
	
	<div style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;">
	
		{assign var="key" value="0"}
		<input type="hidden" name="upload_id[{$key}]" value="" size="60"/> 
		
		<div><span style="width:120px;display:block;float:left"><b>Subject</b>:</span><input type="text" name="grid_reference[{$key}]" value="" size="12" maxlength="12" onblur="checkGridref(this)"/><span style="color:red" id="msg-grid_reference[{$key}]"></span> (at least 4 figures required)</div>
		
		<div><span style="width:120px;display:block;float:left">Photographer:</span><input type="text" name="photographer_gridref[{$key}]" value="" size="12" maxlength="12" onblur="checkGridref(this)"/><span style="color:red" id="msg-photographer_gridref[{$key}]"></span></div>  
		
		<div><span style="width:120px;display:block;float:left">Use 6 Fig:</span><input type="checkbox" name="use6fig[{$key}]" value="1"/> <label for="use6fig">Only display 6 figure grid reference ({newwin href="/help/map_precision" text="Explanation"})</label></div> 
		
		<div><span style="width:120px;display:block;float:left">View Direction:</span><select id="view_direction" name="view_direction[{$key}]">
			{foreach from=$dirs key=key2 item=value}
				<option value="{$key2}"{if $key2%45!=0} style="color:gray"{/if}>{$value}</option>
			{/foreach}
		</select> (no automatic selection)</div> 
		
		<div><span style="width:120px;display:block;float:left"><b>Title</b>:</span><input type="text" name="title[{$key}]" value="" size="50" maxlength="128"/></div>
		
		<div><span style="width:120px;display:block;float:left">Description:</span><textarea name="comment[{$key}]" cols="60" rows="5" wrap="soft"></textarea></div>  
		
		<div><span style="width:120px;display:block;float:left"><b>Category:</b></span><input type="text" name="imageclass[{$key}]" value="" size="20" maxlength="64"/> (no auto complete beyond what your browser provides)</div>  
		
		<div><span style="width:120px;display:block;float:left"><b>Taken Date</b>:</span><input type="text" name="imagetaken[{$key}]" value="" size="10" maxlength="10"/> (YYYY-MM-DD <b>ONLY</b> - double check its a valid date!)</div>  
	
		<input type="hidden" name="selected" value="{$key}"/>
		
		<p>Shared Descriptions/References
			<a href="#" onclick="return showShared();" id="hideshare">Expand <i>Shared Descriptions</i></a>
			<div id="showshare" style="display:none">
				<iframe src="about:blank" height="400" width="98%" id="shareframe" style="border:2px solid gray">
				</iframe>
				<div><a href="#" onclick="hide_tree('share');return false">- Close <i>Shared Descriptions</I> box</a> -</div>
			</div></p>
			


	</div>	
	
	<h3>Step 3 - Confirm Licencing and Finish</h3>
	
		<div class="termsbox" style="margin:0">
{dynamic}
			{assign var="credit" value=$user->credit_realname}
			{assign var="credit_default" value=0}
			{include file="_submit_licence.tpl"}
{/dynamic}
		</div>
		
	<div id="sd6" class="sd">

		<p>
		Because we are an open project we want to ensure our content is licensed
		as openly as possible and so we ask that all images are released under a <b>Attribution-Share Alike</b> {external title="Learn more about Creative Commons" href="http://creativecommons.org" text="Creative Commons" target="_blank"}
		licence, including accompanying metadata.</p>

		<p>{external title="View licence" href="http://creativecommons.org/licenses/by-sa/2.0/" text="Here is the Commons Deed outlining the licence terms" target="_blank"}</p>
	
		<p>If you agree with these terms, click "I agree" and your image will be stored in the grid square.<br/><br/>
		<input style="background-color:pink; width:200px" type="submit" name="abandon" value="I DO NOT AGREE" onclick="return confirm('Are you sure? The current upload will be discarded!');"/>
		<input style="background-color:lightgreen; width:200px" type="submit" name="finalise" value="I AGREE &gt;" onclick="{literal}if (checkMultiFormSubmission()) {autoDisable(this); return true} else {return false;}{/literal}"/>
		</p>
		<br/><br/>
	</div>
</form>


	<script type="text/javascript">{literal}
	function previewImage() {
		var f1 = document.forms['theForm'];
		var f2 = document.forms['previewForm'];
		
		var name = f1.elements['selected'].value;
		
		for (q=0;q<f2.elements.length;q++) {
			if (f2.elements[q].name && f1.elements[f2.elements[q].name+'['+name+']']) {
				f2.elements[q].value = f1.elements[f2.elements[q].name+'['+name+']'].value;
			}
		}
		if ((f2.elements['title'].value == '') || (f2.elements['upload_id'].value == '') || (f2.elements['grid_reference'].value == '')) {
			alert("Needs Image, Title and Subject Grid Reference before preview can be used"); 
			return false;
		}
		
		window.open('','_preview');//forces a new window rather than tab?
		return true;
	}
	function checkGridref(that) {
		if (that.value.length == 0) {
			document.getElementById("msg-"+that.name).innerHTML = "No Grid Reference";
		}
		
		that.value = that.value.toUpperCase();
		
		var gridref = that.value;
		
		var grid=new GT_OSGB();
		var ok = false;
		if (grid.parseGridRef(gridref)) {
			ok = true;
		} else {
			grid=new GT_Irish();
			ok = grid.parseGridRef(gridref)
		}
		if (ok) {
			document.getElementById("msg-"+that.name).innerHTML = '';
		} else {
			document.getElementById("msg-"+that.name).innerHTML = "Invalid Grid Reference";
		} 
	}
	function doneStep(step) {
		//dummy!
	}
	function clicker(step,override) {
		//dummy!
	}
	function showShared() {
		var f1 = document.forms['theForm'];
		var name = f1.elements['selected'].value;
		
		if (f1.elements['upload_id['+name+']'].value == '') {
			alert("upload the image before entering shared description");
			return false;
		}
		
		if (f1.elements['grid_reference['+name+']'].value == '') {
			alert("enter subject grid_reference before entering shared description");
			return false;
		}
		
		upload_id = f1.elements['upload_id['+name+']'].value;
		grid_reference = f1.elements['grid_reference['+name+']'].value;
		
		show_tree('share'); 
		document.getElementById('shareframe').src='/submit_snippet.php?upload_id='+escape(upload_id)+'&gr='+escape(grid_reference);
		return false;
	}
	
	{/literal}</script>
	<form action="/preview.php" method="post" name="previewForm" target="_preview" style="padding:10px; text-align:center">
	<input type="hidden" name="grid_reference"/>
	<input type="hidden" name="photographer_gridref"/>
	<input type="hidden" name="view_direction"/>
	<input type="hidden" name="use6fig"/>
	<input type="hidden" name="title"/>
	<textarea name="comment" style="display:none"/></textarea>
	<input type="hidden" name="imageclass"/>
	<input type="hidden" name="imageclassother"/>
	<input type="hidden" name="imagetakenDay"/>
	<input type="hidden" name="imagetakenMonth"/>
	<input type="hidden" name="imagetakenYear"/>
	<input type="hidden" name="upload_id"/>
	<input type="submit" value="Preview Submission in a new window" onclick="return previewImage()"/> 
	
	<input type="checkbox" name="spelling"/>Check Spelling
	<sup style="color:red">Experimental!</sup>
	</form>

{include file="_std_end.tpl"}
