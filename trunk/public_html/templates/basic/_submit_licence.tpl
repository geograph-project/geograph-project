	<div class="interestBox">
	<p>As part of the licence it's important that the '<i>Original Author</i>' or Photographer is correctly attributed, use this section to apply the apprieate credit to the photographer.</p>
	
	<dl>
		<dt id="dt_self"><input type="radio" name="pattrib" value="self" id="pattrib_self" {if !$credit}checked="checked"{/if} onclick="updateAttribDivs()"/><label for="pattrib_self">I am the photographer</label></dt>
		<dd id="dd_self" style="border:1px solid gray; padding:5px">Use this option when you as the Geograph Account Holder, also took the photo.</dd>
		<div style="padding:10px"><i>- or -</i></div>
		<dt id="dt_other"><input type="radio" name="pattrib" value="other" id="pattrib_other" onclick="updateAttribDivs()" {if $credit}checked="checked"{/if}/><label for="pattrib_other">I am not the photographer, and wish to apply the appriate credit for this image</label></dt>
		<dd id="dd_other" style="border:1px solid gray; padding:5px">By selecting the above option you certify that you as the Geograph Account Holder, are an authorised '<i>Licensor</i>' (<a href="">What does this mean</a>?) for the photographer, named below:
		<br/><br/>
		Photographer Name: <input type="text" name="pattrib_name" value="{$credit|escape:"html"}"/> <a href="">Choose Previous</a>
		<br/><br/>
		Note: It's very important to check that you are allowed to upload on behalf of the Original Author
		</dd>
	</dl>
	<div style="text-align:right"><input type="checkbox" name="pattrib_default" value="on" id="pattrib_default" {if $credit_default}checked="checked"{/if}/>Make this my new default</div>
	
	{literal}
		<script type="text/javascript">
			function $(id) {
				return document.getElementById(id);
			}
		
			function updateAttribDivs() {
				isself = document.theForm.pattrib[0].checked;
				
				$('dt_self').style.fontWeight = isself?'bold':'';
				$('dd_self').style.display = isself?'':'none';
				$('dt_other').style.fontWeight = isself?'':'bold';
				$('dd_other').style.display = isself?'none':'';
			}
			AttachEvent(window,'load',updateAttribDivs,false);
		</script>
	{/literal}
	
	</div>