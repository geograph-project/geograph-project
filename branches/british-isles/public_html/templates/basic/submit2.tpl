{assign var="page_title" value="Submit 2"}
{include file="_std_begin.tpl"}
{literal}<style type="text/css">

.sh {
	border-top:2px solid brown;
	border-left:2px solid brown;
	border-right:2px solid brown;
	padding:10px;
	margin:0px;
	font-size:1.6em;
	font-weight:bold;
	display:block;
	text-decoration:none;
	color:black;
}

.sh span {
	float:left;
	width:20px;
	border:1px solid gray;
	background-color:lightgrey;
	font-weight:bold;
	text-size:1.3em;
	text-align:center;
	margin-right:15px;
}

.sn {
	background-color:pink;
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
function clicker(step,override) {
	var theForm = document.forms['theForm'];
	var name = document.forms['theForm'].elements['selected'].value;
	
	var ele = document.getElementById('sd'+step);
	var ele2 = document.getElementById('se'+step);
	var showing = (ele.style.display == 'block');
	
	if (typeof(override) != 'undefined') {
		showing = !override;
	}
	
	if (showing) {
		ele.style.display = 'none';
		ele2.innerHTML = '+';
	} else {
		ele.style.display = 'block';
		ele2.innerHTML = '-';
		
		
		var loc = '';
		
		if (theForm.elements['grid_reference['+name+']'] && theForm.elements['grid_reference['+name+']'].value != '') {
			loc = loc + "&grid_reference="+escape(theForm.elements['grid_reference['+name+']'].value);
		}
		if (theForm.elements['photographer_gridref['+name+']'] && theForm.elements['photographer_gridref['+name+']'].value != '') {
			loc = loc + "&photographer_gridref="+escape(theForm.elements['photographer_gridref['+name+']'].value);
		}

		
		if (step == 1) {
			//document.getElementById('iframe'+step).src = '/submit2.php?inner&step=1';
		} else if (step == 2) {
			document.getElementById('iframe'+step).src = '/submitmap.php?inner&submit2'+loc;
		} else if (step == 3) {
			document.getElementById('iframe'+step).src = '/puploader.php?inner&submit2&step=2'+loc;
		} else if (step == 4) {
			document.getElementById('iframe'+step).src = '/puploader.php?inner&submit2&step=3'+loc+'&upload_id='+escape(theForm.elements['upload_id['+name+']'].value);
		} else {
			
		}  
		
		
	}
	return false;
}

function doneStep(step) {
	document.getElementById('sh'+step).className = "sh sy";
	clicker(step,false);
}

function updateAttribDivsSetup() {
	document.theForm.pattrib[0].checked = false;

	
}
AttachEvent(window,'load',updateAttribDivsSetup,false);

</script>
{/literal}
{dynamic}
	<h2>Submit version 2 <sup>Alpha</sup></h2> 
	
	<p>Complete the following steps in any order (and continue onto step 2/3 while the photo is still uploading!). Step 2 is optional, can directly enter a grid reference in step 3 if wish. </p>
	
	
<!-- # -->	 
	<a id="sh1" href="#" class="sh sn" onclick="return clicker(1)"><span id="se1">-</span> Step 1 - Upload Photo</a>
	
	<div id="sd1" class="sd" style="display:block">
		<iframe src="/submit2.php?inner&amp;step=1" id="iframe1" width="100%" height="220px" style="border:0"></iframe>
	</div>
<!-- # -->	 
	<a id="sh2" href="#" class="sh sn" onclick="return clicker(2)"><span id="se2">+</span> Step 2 - Find Square on Map</a>
	
	<div id="sd2" class="sd">
		<iframe src="about:blank" id="iframe2" width="100%" height="700px"></iframe>
	</div>
<!-- # -->	 
	<a id="sh3" href="#" class="sh sn" onclick="return clicker(3)"><span id="se3">+</span> Step 3 - Enter Map References</a>
	
	<div id="sd3" class="sd">
		<iframe src="about:blank" id="iframe3" width="100%" height="500px"></iframe>
	</div>
<!-- # -->	 
	<a id="sh4" href="#" class="sh sn" onclick="return clicker(4)"><span id="se4">+</span> Step 4 - Title/Description/Meta</a>
	
	<div id="sd4" class="sd">
		<iframe src="about:blank" id="iframe4" width="100%" height="650px"></iframe>
	</div>
<form action="{$script_name}" name="theForm" method="post">
<!-- # -->	 
	<a id="sh5" href="#" class="sh sn" onclick="return clicker(5)"><span id="se5">+</span> Step 5 - Attribution</a>
	
	<div id="sd5" class="sd">
		<div class="termsbox">
			
			
			<p>{external title="View licence" href="http://creativecommons.org/licenses/by-sa/2.0/" text="Here is the Commons Deed outlining the licence terms" target="_blank"}</p>

			{assign var="credit" value=$user->credit_realname}
			{assign var="credit_default" value=0}
			{include file="_submit_licence.tpl"}


		</div>
	</div>
<!-- # -->	 
	<a id="sh6" href="#" class="sh sn" onclick="return clicker(6)"><span id="se6">+</span> Step 6 - Confirm Licencing and Finish</a>
	
	<div id="sd6" class="sd">
   <div style="width:230px;float:right;position:relative;text-align:center;font-size:0.7em">
   	<a href="http://creativecommons.org/licenses/by-sa/2.0/"><img src="http://{$static_host}/img/cc_deed.jpg" width="226" height="226" alt="Creative Commons Licence Deed"/></a><br/>
   	[ Click to see full Licence Deed ]
   </div>

		<p>
		Because we are an open project we want to ensure our content is licensed
		as openly as possible and so we ask that all images are released under a {external title="Learn more about Creative Commons" href="http://creativecommons.org" text="Creative Commons" target="_blank"}
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
	
		<br/><br/><br/><br/>
	
		<p>If you agree with these terms, click "I agree" and your image will be submitted to Geograph.<br />
		<input type="button" value="Close Window" onclick="location.href='minibrowser:close'"/>
		<input style="background-color:lightgreen; width:200px" type="submit" name="finalise" value="I AGREE &gt;" onclick="{literal}if (checkMultiFormSubmission()) {autoDisable(this); return true} else {return false;}{/literal}"/>
		</p>
	</div>
<!-- # -->	 
	<a id="sh10" href="#" class="sh sn" onclick="return clicker(10)" style="background-color:yellow; font-size:0.9em"><span id="se10">+</span> The Scratch Pad</a>
	
	<div id="sd10" class="sd">
		Dont Edit anything here - its just where we store stuff as you go along. Its only shown for debugging - the final version will have it permentally hidden.
		{assign var="key" value="0"}
		<div><span>Upload ID:</span><input type="text" name="upload_id[{$key}]" value="" size="12" maxlength="12"/> </div>
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

{/dynamic}    
{include file="_std_end.tpl"}
