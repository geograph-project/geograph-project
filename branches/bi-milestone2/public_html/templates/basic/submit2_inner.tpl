{include file="_basic_begin.tpl"}

<form enctype="multipart/form-data" action="{$script_name}" method="post" name="theForm" onsubmit="if (this.imageclass) this.imageclass.disabled=false;" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;">
	<input type="hidden" name="inner" value="1"/>
{dynamic}

	{if $errormsg}
	<p style="color:#990000;font-weight:bold;">{$errormsg}</p>
	{/if}

{if $step eq 1}	
	

	
	<div><label for="jpeg_exif">Select Image file to upload - recommend resizing to 640px on longest side</label> <br/>	
	<input id="jpeg_exif" name="jpeg_exif" type="file" size="60"/>
	<input type="hidden" name="MAX_FILE_SIZE" value="8192000" /></div>
	<div>
	<input type="submit" name="sendfile" value="Send File &gt;" style="margin-left:140px"/> (while file is sending can continue on the steps below)<br/>
	</div>
	</form>
	<br/>
	<div><b><i>Optionally</i> upload an image with Locational information attached</b><br/>
	<ul>
		<li>GPS-EXIF tags based on WGS84 Lat/Long</li>
		<li>Subject grid-reference from the name of the file (eg "<tt>photo-<b style="padding:1px">TQ435646</b>A.jpg</tt>")</li>
		<li>Subject grid-reference in EXIF Comment tag</li>
	</ul></div>
	
	<form>
{elseif $step eq 2}


{/if}


{/dynamic}
{literal}
<script type="text/javascript">
	function parentUpdateVariables() {
		var thatForm = window.parent.document.forms['theForm'];
		var name = thatForm.elements['selected'].value;
		var theForm = document.forms['theForm'];
		if (name != '') {
			for(q=0;q<theForm.elements.length;q++) {
				var ele = theForm.elements[q];
				if (thatForm.elements[ele.name+'['+name+']']) {
					//we dont need to check for select as IE does pupulate .value
					if (ele.tagName.toLowerCase() == 'input' && ele.type.toLowerCase() == 'checkbox') {
						if (ele.checked) 
							thatForm.elements[ele.name+'['+name+']'].value = ele.value;
					} else {
						thatForm.elements[ele.name+'['+name+']'].value = ele.value;
					}
				}
			}
			if (theForm.elements['imagetakenDay'] && thatForm.elements['imagetaken['+name+']']) {
				thatForm.elements['imagetaken['+name+']'].value = theForm.elements['imagetakenYear'].value + '-' + theForm.elements['imagetakenMonth'].value + '-' + theForm.elements['imagetakenDay'].value
			}
		
		}
	}
	
	function setupTheForm() {
		var thatForm = window.parent.document.forms['theForm'];
		var name = thatForm.elements['selected'].value;
		var theForm = document.forms['theForm'];
		for(q=0;q<theForm.elements.length;q++) {
			var ele = theForm.elements[q];
			if (thatForm.elements[ele.name+'['+name+']']) {
				if (ele.tagName.toLowerCase() == 'select') {
					for(w=0;w<ele.options.length;w++)
						if (ele.options[w].value == thatForm.elements[ele.name+'['+name+']'].value)
							ele.selectedIndex = w;

					AttachEvent(ele,'change',parentUpdateVariables,false);
					
					if (ele.name == 'imageclass') {
						onChangeImageclass();
					}
					
				} else if (ele.tagName.toLowerCase() == 'input' && ele.type.toLowerCase() == 'checkbox') {
					if (thatForm.elements[ele.name+'['+name+']'].value != '')
						ele.checked = true;
					AttachEvent(ele,'click',parentUpdateVariables,false);
				} else if (ele.tagName.toLowerCase() == 'input' && ele.type.toLowerCase() == 'input') {
					AttachEvent(ele,'click',parentUpdateVariables,false);
				} else {
					ele.value = thatForm.elements[ele.name+'['+name+']'].value;
					AttachEvent(ele,'mouseup',parentUpdateVariables,false);
					AttachEvent(ele,'keyup',parentUpdateVariables,false);
				}
			}
		}
		if (theForm.elements['imagetakenDay'] && thatForm.elements['imagetaken['+name+']']) {
			setdate('imagetaken',thatForm.elements['imagetaken['+name+']'].value,theForm);
			AttachEvent(theForm.elements['imagetakenDay'],'change',parentUpdateVariables,false);
			AttachEvent(theForm.elements['imagetakenMonth'],'change',parentUpdateVariables,false);
			AttachEvent(theForm.elements['imagetakenYear'],'change',parentUpdateVariables,false);
		}
		AttachEvent(ele.form,'submit',parentUpdateVariables,false);
	}
	AttachEvent(window,'load',function() { setTimeout("setupTheForm()",100); },false);
</script>
{/literal}

</form>
</body>
</html>