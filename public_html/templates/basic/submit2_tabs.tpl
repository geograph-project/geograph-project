{assign var="page_title" value="Submit 2 with Tabs"}
{include file="_std_begin.tpl"}
{literal}<style type="text/css">

.sh {
	font-size:1.2em;
	text-decoration:none;
}

.sh span {
	
}

.sn {
	background-color:#dddddd;
}
.sy {
	background-color:lightgreen;
}

.sd {
	display:none;
}

.termsbox {
	position:relative; 
	padding:10px;
}

#iframe1,#iframe2,#iframe3,#iframe4,#iframe5 {
	border-top:0;
}

</style>
{/literal}
<script type="text/javascript" src="{"/js/puploader.js"|revision}"></script>
{literal}
<script type="text/javascript">

  String.prototype.endsWith = function (pattern) {
    var d = this.length - pattern.length;
    return d >= 0 && this.lastIndexOf(pattern) === d;
  }

function clicker(step,override,shiftKey) {
	var theForm = document.forms['theForm'];
	var name = document.forms['theForm'].elements['selected'].value;
	
	var ele = document.getElementById('sd'+step);
	
	var showing = (ele.style.display == 'block');
	
	if (typeof(override) != 'undefined' && override != null) {
		showing = !override;
	}
	

	var tabname = 'sh';
	var divname = 'sd';
	var steps = new Array(1,2,3,4,9);
	for (var idx=0;idx<steps.length;idx++) {
		q=steps[idx];
		var newname = (step==q)?'tabSelected':'tab';
		document.getElementById(tabname+q).className = document.getElementById(tabname+q).className.replace(/tab\w*/,newname);
		if (divname != '') {
			document.getElementById(divname+q).style.display = (step==q)?'':'none';
		}
	}	
	
	if (showing) {

	
		ele.style.display = 'none';
		
	} else {
		ele.style.display = 'block';
		
		
		var loc = 'inner&submit2&step='+step;
		
		if (theForm.elements['grid_reference['+name+']'] && theForm.elements['grid_reference['+name+']'].value != '') {
			loc = loc + "&grid_reference="+escape(theForm.elements['grid_reference['+name+']'].value);
		}
		
		
		if (step == 1) {
			//we dont reload this - as it could be in progress, and features its own 'start over' link
			//document.getElementById('iframe'+step).src = '/submit2.php?inner&step=1';
		} else if (step == 9) {
			if (document.getElementById('iframe'+step).src.endsWith('/submitmap.php?'+loc) == false)
				   document.getElementById('iframe'+step).src = '/submitmap.php?'+loc;
		} else if (step == 2) {
			if (theForm.elements['service'] && theForm.elements['service'].checked) {
				loc = loc + "&service="+escape(theForm.elements['service'].value);
			}
			//todo - this only NEEDS a 4fig subject GR - the rest is loaded with javascript anyway
			if (document.getElementById('iframe'+step).src.endsWith('/puploader.php?'+loc) == false)
				   document.getElementById('iframe'+step).src = '/puploader.php?'+loc;
		} else if (step == 3) {
			if (theForm.elements['photographer_gridref['+name+']'] && theForm.elements['photographer_gridref['+name+']'].value != '') {
				loc = loc + "&photographer_gridref="+escape(theForm.elements['photographer_gridref['+name+']'].value);
			}
			if (theForm.elements['use_autocomplete'] && theForm.elements['use_autocomplete'].checked) {
				loc = loc + "&use_autocomplete=1";
			}
			loc = loc + '&upload_id='+escape(theForm.elements['upload_id['+name+']'].value);

			if (document.getElementById('iframe'+step).src.endsWith('/puploader.php?'+loc) == false)
				   document.getElementById('iframe'+step).src = '/puploader.php?'+loc;
		} else {
			
		}  
		if (typeof(shiftKey) != 'undefined' && shiftKey) {
			location.hash = "#sh"+step;
		}
	}
	return false;
}

function doneStep(step,dontclose) {
	document.getElementById('sh'+step).className = "tab sh sy";
	if (!dontclose) {
		clicker(step,false);
	}
}
function showPreview(url,width,height,filename) {
	height2=Math.round((148 * height)/width);
	document.getElementById('previewInner').innerHTML = '<img src="'+url+'" width="148" height="'+height2+'" id="imgPreview" onmouseover="this.height='+height+';this.width='+width+'" onmouseout="this.height='+height2+';this.width=148" /><br/>'+filename;
	document.getElementById('hidePreview').style.display='';
}
function scalePreview(scale) {
	var ele = document.getElementById('imgPreview');
	if ((ele.width * scale) <= 1280 && (ele.width * scale) > 10) {
		ele.width = ele.width * scale;
		ele.height = ele.height * scale;
	}
}
function setTakenDate(value) {
	if (document.getElementById('iframe'+3).src.length > 11) {
		top.frames['iframe3'].setTakenDate(value);
	}
}

function saveService(that) {
	if (that.checked) {
		createCookie("MapSrv",'G',10);
	} else {
		eraseCookie("MapSrv");
	}
}

function restoreService() {
	newservice = readCookie('MapSrv');
	if (newservice) {
		document.getElementById('service_google').checked = true;
	}
}
AttachEvent(window,'load',restoreService,false);

function readHash() {
	if (location.hash.length) {
		// If there are any parameters at the end of the URL, they will be in location.search
		// looking something like  "#ll=50,-3&z=10&t=h"

		// skip the first character, we are not interested in the "#"
		var query = location.hash.substring(1);

		var pairs = query.split("&");
		for (var i=0; i<pairs.length; i++) {
			// break each pair at the first "=" to obtain the argname and value
			var pos = pairs[i].indexOf("=");
			var argname = pairs[i].substring(0,pos).toLowerCase();
			var value = pairs[i].substring(pos+1).toUpperCase();

			if (argname == "gridref") {
				var theForm = document.forms['theForm'];
				var name = theForm.elements['selected'].value;
				theForm.elements['grid_reference['+name+']'].value = unescape(value);
				clicker(2,true);
				
				document.getElementById("oldlink").href=document.getElementById("oldlink").href+"&gridreference="+value;
			}
		}
	}
}
AttachEvent(window,'load',readHash,false);
</script>
{/literal}

	<div style="float:right;position:relative;text-align:center">&middot; <a href="/help/submission">View alternative Submission Methods</a> &middot;<br/>&middot; <b><a href="/submit.php?redir=false" id="oldlink">Original Submission Method</a></b> &middot;{if $user->submission_method == 'submit'}<br/><br/><div class="interestBox">Set <b>Version 2</b> as <i>your</i> default<br/> on <a href="/profile.php?edit=1#prefs">Profile Edit page</a></div>{/if}</div>
	
	<h2>Submit Image <sup style="color:gray">v2:Tabs - Beta</sup></h2> 
	
	<noscript>
	<div style="background-color:pink; color:black; border:2px solid red; padding:10px;"> This process requires Javascript! The original <a href="/submit.php">Submission Process</a> should be functional with it.</div>
	</noscript>
	
	
	
	<p>Complete the following steps in any order (and continue onto the following steps while the photo is still uploading!). 
	 A overview map is provided to help locate a square, but is optional, can directly enter a grid reference in step 2 if wish.
	 If possible, the date, and grid-reference(s) are automatically extracted from the submitted image.</p>

	
	<form action="{$script_name}?process" name="theForm" method="post">
{dynamic}
	<p style="background-color:#eeeeee;padding:2px"><b>Options</b>: (close and reopen step to take effect)<br/>
	{if !$user->use_autocomplete}
	<input type="checkbox" name="use_autocomplete" {if $user->use_autocomplete} checked{/if} id="use_autocomplete"/> <label for="use_autocomplete">Use auto-complete text entry for image category selection in Step 3. <a href="/profile.php?edit=1" target="_blank">Change permanently</a></label> <br/>
	{/if}
	<input type="checkbox" name="service" id="service_google" value="Google" onclick="saveService(this)"/> <label for="service_google">Use Google Mapping in Step 2 - even for Great Britain</label></p>

{/dynamic}
	{if !$tab}
		{assign var="tab" value="1"}
	{/if}
<!-- # -->
	<div class="tabHolder">
		<a id="sh1" href="#" class="tab{if $tab == 1}Selected{/if} sh sn" onclick="return clicker(1,null,event.shiftKey)">1. Upload</a>
		<a id="sh9" href="#" class="tab{if $tab == 9}Selected{/if} sh sn" onclick="return clicker(9,null,event.shiftKey)" style="font-size:0.9em">Overview Map</a>
		<a id="sh2" href="#" class="tab{if $tab == 2}Selected{/if} sh sn" onclick="return clicker(2,null,event.shiftKey)">2. Detail Map</a>
		<a id="sh3" href="#" class="tab{if $tab == 3}Selected{/if} sh sn" onclick="return clicker(3,null,event.shiftKey)">3. Description</a>
		<a id="sh4" href="#" class="tab{if $tab == 4}Selected{/if} sh sn" onclick="return clicker(4,null,event.shiftKey)">4. Confirm and Finish</a>
	</div>
	
	<div id="sd1" class="sd" style="display:block">
		<iframe src="/submit2.php?inner&amp;step=1" id="iframe1" width="100%" height="520px" style="border:0"></iframe>
	</div>
<!-- # -->	 
	
	<div id="sd9" class="sd">
		<iframe src="about:blank" id="iframe9" width="100%" height="700px"></iframe>
	</div>
<!-- # -->	 
	
	<div id="sd2" class="sd">
		<iframe src="about:blank" id="iframe2" width="100%" height="500px"></iframe>
	</div>
<!-- # -->	 
	
	<div id="sd3" class="sd">
		<iframe src="about:blank" id="iframe3" name="iframe3" width="100%" height="700px"></iframe>
	</div>
<!-- # -->	 
	
	<div id="sd4" class="sd" style="border:2px solid red; padding:4px;border-top:0">
		<div style="width:230px;float:right;position:relative;text-align:center;font-size:0.7em">
			<a href="http://creativecommons.org/licenses/by-sa/2.0/" target="_blank"><img src="http://{$static_host}/img/cc_deed.jpg" width="226" height="226" alt="Creative Commons Licence Deed"/></a><br/>
			[ Click to see full Licence Deed ]
		</div>

		<p>
		Because we are an open project we want to ensure our content is licensed
		as openly as possible and so we ask that all images are released under a <b>Attribution-Share Alike</b> {external title="Learn more about Creative Commons" href="http://creativecommons.org" text="Creative Commons" target="_blank"}
		licence, including accompanying metadata.</p>

		<p>With a Creative Commons licence, the photographer <b>keeps the copyright</b> but allows 
		people to copy and distribute the work provided they <b>give credit</b>.</p>

		<p>Since we want to ensure we can use your work to fund the running costs of
		this site, and allow us to create montages of grid images, we ask that you
		allow the following</p>

		<ul>
		<li>The right to use the work commercially</li>
		<li>The right to modify the work to create derivative works</li>
		</ul>

		<p>{external title="View licence" href="http://creativecommons.org/licenses/by-sa/2.0/" text="Here is the Commons Deed outlining the licence terms" target="_blank"}</p>
	
		<br style="clear:both"/>

		<div class="termsbox" style="margin:0">
{dynamic}
			{assign var="credit" value=$user->credit_realname}
			{assign var="credit_default" value=0}
			{include file="_submit_licence.tpl"}
{/dynamic}
		</div>
	
		<p align="center"><input type="button" value="Preview Submission in a new window"  onclick="document.getElementById('previewButton').click();"/> 
	
		<p>If you agree with these terms, click "I agree" and your image will be stored in the grid square.<br/><br/>
		<input style="background-color:pink; width:200px" type="submit" name="abandon" value="I DO NOT AGREE" onclick="return confirm('Are you sure? The current upload will be discarded!');"/>
		<input style="background-color:lightgreen; width:200px" type="submit" name="finalise" value="I AGREE &gt;" onclick="{literal}if (checkMultiFormSubmission()) {autoDisable(this); return true} else {return false;}{/literal}"/>
		</p>
		<br/><br/>
	</div>
<!-- # -->
{dynamic}
	{if $is_admin}
	<a id="sh10" href="#" class="sh sn" onclick="return clicker(10,null,event.shiftKey)" style="background-color:yellow; font-size:0.9em"><span id="se10">+</span> The Scratch Pad</a>
	{/if}
{/dynamic}
	
	<div id="sd10" class="sd">
		<p><b>Do not Edit anything here</b> - its just where we store stuff as you go along. Its only shown for debugging - the final version will have it permentally hidden.</p>
		{assign var="key" value="0"}
		<div><span>Upload ID:</span><input type="text" name="upload_id[{$key}]" value="" size="60"/> </div>
		<div><span>Largest Size:</span><input type="text" name="largestsize[{$key}]" value="" size="4"/> </div>
		<div><span>Subject:</span><input type="text" name="grid_reference[{$key}]" value="" size="12" maxlength="12"/> </div>
		<div><span>Photographer:</span><input type="text" name="photographer_gridref[{$key}]" value="" size="12" maxlength="12"/></div>  
		<div><span>use 6 Fig:</span><input type="text" name="use6fig[{$key}]" value="" size="1" maxlength="2"/></div> 
		<div><span>View Direction:</span><input type="text" name="view_direction[{$key}]" value="" size="3" maxlength="4"/></div> 
		<div><span>Title:</span><input type="text" name="title[{$key}]" value="" size="20" maxlength="128"/></div>  
		<div><span>Description:</span><textarea name="comment[{$key}]" cols="30" rows="2" wrap="soft"></textarea></div>  
		<div><span>Category:</span><input type="text" name="imageclass[{$key}]" value="" size="12" maxlength="64"/> <input type="text" name="imageclassother[{$key}]" value="" size="12" maxlength="64"/></div>  
		<div><span>Date:</span><input type="text" name="imagetaken[{$key}]" value="" size="10" maxlength="10"/></div>  
	
		<input type="hidden" name="selected" value="0"/>
	</div>
<!-- # -->	 
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
	{/literal}</script>
	<form action="/preview.php" method="post" name="previewForm" target="_preview" style="padding:10px; text-align:center; border-top:2px solid black;">
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
	<input type="submit" value="Preview Submission in a new window" onclick="return previewImage()" id="previewButton"/> 
	
	<input type="checkbox" name="spelling"/>Check Spelling
	<sup style="color:red">Experimental!</sup>
	</form>

	<div style="position:fixed;left:10px;bottom:10px;display:none;background-color:silver;padding:2px;font-size:0.8em;width:148px" id="hidePreview">
	<div id="previewInner"></div></div>
	
	

{include file="_std_end.tpl"}
