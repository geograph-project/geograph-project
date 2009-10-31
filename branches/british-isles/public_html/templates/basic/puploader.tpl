{assign var="page_title" value="Geograph Picasa Uploader"}
{include file="_basic_begin.tpl"}
 {literal}<style type="text/css">
 
table.c3 {
	background-color:#000066; font-family:Georgia
}
table.c3 I {
	color: white; font-family: Georgia
}
a.c1 {
	color: white; font-size: 144%
} 
 
#theForm {
	background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;
}
.scrollbox {
	overflow:auto; 
	height:200px; 
	width:100%; 
	border: 1px solid green;
}
.photobox {
	float:left; 
	height:180px; 
	width:180px; 
	border:1px solid green; 
	margin:5px;
	margin-right:0px; 
	text-align:center;
	background-color:lightgrey;
}
.photobox_selected {
	float:left; 
	height:180px; 
	width:180px; 
	border:1px solid green; 
	margin:5px;
	margin-right:0px; 
	text-align:center;
	background-color:yellow;
}
.photobox input,.photobox_selected input,.photobox textarea,.photobox_selected textarea {
	font-size:0.5em;
	border:0px;
	background-color:white;
	padding:0px;
	margin:0px;
}
.photobox .nowrap,.photobox_selected .nowrap {
	font-size:0.4em;
}
.termsbox {
	position:relative; 
	background-color:pink;
	padding:10px;
}

</style>
{/literal}
<script type="text/javascript" src="{"/js/puploader.js"|revision}"></script>
{literal}
<script type="text/javascript">
function selectPhoto(name) {
	var ele = document.forms['theForm'].elements['selected'];
	if (ele.value != '') {
		if (document.getElementById("photo:"+ele.value))
			document.getElementById("photo:"+ele.value).className="photobox";
	}
	ele.value = name;
	if (document.getElementById("photo:"+name))
		document.getElementById("photo:"+name).className="photobox_selected";
	document.getElementById('subIframe').src = "about:blank";
	tabClick('tab','',-1,4);
}

  function startUp() {
  	selectPhoto(document.forms['theForm'].elements['selected'].value);
  }
  AttachEvent(window,'load',startUp,false);

function submitTabClick(tabname,divname,num,count) {
	var theForm = document.forms['theForm'];
	var name = document.forms['theForm'].elements['selected'].value;
	if (num == 2) {
		document.getElementById('subIframe').src = '/submitmap.php?inner&picasa';
	} else {
		url = '/puploader.php?inner';
		
		if (theForm.elements['grid_reference['+name+']'] && theForm.elements['grid_reference['+name+']'].value != '') {
			url = url + "&grid_reference="+escape(theForm.elements['grid_reference['+name+']'].value);
		}
		if (theForm.elements['photographer_gridref['+name+']'] && theForm.elements['photographer_gridref['+name+']'].value != '') {
			url = url + "&photographer_gridref="+escape(theForm.elements['photographer_gridref['+name+']'].value);
		}
		if (num > 2) {
			url = url + "&step="+(num-1);
		} else {
			url = url + "&step="+(num);
		}
		
		document.getElementById('subIframe').src = url;
	}  
	tabClick(tabname,divname,num,count);
}

</script>{/literal}
{dynamic}

<table cellspacing="0" cellpadding="4" width="100%" class="c3">
<tbody>
<tr>
<td>&nbsp;</td>
<td><a href="http://{$http_host}/"><img height="74" src=
"http://{$http_host}/templates/basic/img/logo.gif" width="257" border="0" /></a></td>
<td valign="top" align="center"><a href="http://{$http_host}/" class=
"c1">{$http_host}</a><br />
<i>The Geograph British Isles project aims to collect a geographically
representative<br />
photograph for every square kilometre of the British Isles and you can be part of
it.</i></td>
<td>&nbsp;</td>
</tr>
</tbody>
</table>


<div style="padding:10px">
	<form enctype="multipart/form-data" action="post.php" method="post" name="theForm" id="theForm">
		
		<div style="float:right">Logged in as {$user->realname} / <a href="/logout.php">Logout</a></div>

		<h2>Picasa --&gt; Geograph Uploader v0.68</h2>

		<div style="color:black; background-color:yellow; font-size:0.7em; padding:3px; border: 1px solid orange">Please avoid submitting images with overlaid text or borders; they should be cropped before submission. Thank you for your attention to this matter.<br/><br/>
		You should only submit photos you have taken yourself, or where you can specifically act as a Licensor on behalf of the original author.</div><br/>

		<ol>
			<li>Select each photo in turn, the selected image is shown in yellow</li>
			<li>Fill out the relevent details in available tabs</li>
			<li>Once you have filled out all the details check the terms, and the photos will be sent to Geograph</li>
		</ol>
		<div class="scrollbox">
		{assign var="thumbnail" value="photo:thumbnail"}
		{assign var="imgsrc" value="photo:imgsrc"}
		{foreach from=$pData key=key item=image}
			<div class="photobox" id="photo:{$key}" onclick="selectPhoto('{$key}');">
				<tt>{$image.title}</tt>
				<div style="width:100px; height:100px;">
					<a href="{$image.$imgsrc}?size=640" target="_blank"><img src="{$image.$thumbnail}?size=100"/></a>
				</div>
				<input type="hidden" name="{$image.$imgsrc}?size=640"/>
				<input type="hidden" name="field[{$key}]" value="{$image.$imgsrc}?size=640"/>
				<span class="nowrap">Su:<input type="text" name="grid_reference[{$key}]" value="" size="12" maxlength="12"/></span>  
				<span class="nowrap">Ph:<input type="text" name="photographer_gridref[{$key}]" value="" size="12" maxlength="12"/></span>  
				<span class="nowrap">6f:<input type="text" name="use6fig[{$key}]" value="" size="1" maxlength="2"/></span> 
				<span class="nowrap">Dir:<input type="text" name="view_direction[{$key}]" value="" size="3" maxlength="4"/></span> 
				<span class="nowrap">Ti:<input type="text" name="title[{$key}]" value="" size="20" maxlength="255"/></span>  
				<span class="nowrap">De:<textarea name="comment[{$key}]" cols="30" rows="2" wrap="soft"></textarea></span>  
				<span class="nowrap">Cl:<input type="text" name="imageclass[{$key}]" value="" size="12" maxlength="64"/> <input type="text" name="imageclassother[{$key}]" value="" size="12" maxlength="64"/></span>  
				<span class="nowrap">Da:<input type="text" name="imagetaken[{$key}]" value="" size="10" maxlength="10"/></span>  
			</div>
		{/foreach}

		</div>
		<input type="hidden" name="selected" value="0"/>
		<div class="tabHolder">
			<a class="tab{if $tab == 1}Selected{/if} nowrap" id="tab1" onclick="submitTabClick('tab','',1,4)">1) Enter Grid Reference</a><a 
			   class="tab{if $tab == 2}Selected{/if} nowrap" id="tab2" onclick="submitTabClick('tab','',2,4)">or Draggable Map</a>
			<a class="tab{if $tab == 3}Selected{/if} nowrap" id="tab3" onclick="submitTabClick('tab','',3,4)">2) Map References</a>
			<a class="tab{if $tab == 4}Selected{/if} nowrap" id="tab4" onclick="submitTabClick('tab','',4,4)">3) Title/Description</a>
		</div>
		<iframe id="subIframe" name="subIframe" src="about:blank" width="100%" height="800"></iframe>


		<div class="termsbox">

			<h2>Confirm image rights</h2>

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

			{assign var="credit" value=$user->credit_realname}
			{assign var="credit_default" value=0}
			{include file="_submit_licence.tpl"}

			<p>If you agree with these terms, click "I agree" and your images submitted to Geograph.<br />
			<input type="button" value="Close Window" onclick="location.href='minibrowser:close'"/>
			<input style="background-color:lightgreen; width:200px" type="submit" name="finalise" value="I AGREE &gt;" onclick="{literal}if (checkMultiFormSubmission()) {autoDisable(this); return true} else {return false;}{/literal}"/>
			</p>
		</div>

	</form>
</div>
{/dynamic}

</body>
</html>
