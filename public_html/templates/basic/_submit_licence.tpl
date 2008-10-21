	<div class="interestBox">
	<p>As part of the licence it's important that the '<i>Original Author</i>' or Photographer is correctly attributed, use this section to apply the appropriate credit to the photographer.</p>
	
	<dl>
		<dt id="dt_self"><input type="radio" name="pattrib" value="self" id="pattrib_self" {if !$credit}checked="checked"{/if} onclick="updateAttribDivs()"/><label for="pattrib_self">I am the photographer</label></dt>
		<dd id="dd_self" style="border:1px solid gray; padding:5px{if $credit};display:none{/if}">Use this option when you as the '<i>Geograph Account Holder</i>', also took the photo.</dd>
		<div style="padding:10px"><i>- or -</i></div>
		<dt id="dt_other"><input type="radio" name="pattrib" value="other" id="pattrib_other" onclick="updateAttribDivs()" {if $credit}checked="checked"{/if}/><label for="pattrib_other">I am not the photographer, and need to apply the appropriate credit for this image</label></dt>
		<dd id="dd_other" style="border:1px solid gray; padding:5px{if !$credit};display:none{/if}">By selecting the above option you certify that you as the '<i>Geograph Account Holder</i>',<br/> act as an authorised '<i>Licensor</i>' ({newwin href="/help/what_is_a_licensor" text="What does this mean?"}) for the photographer named below:
		<br/><br/>
		Photographer Name: <input type="text" name="pattrib_name" value="{$credit|escape:"html"}" size="40"/>
		<br/><br/>
		Note: It's vitally important to be sure you are a valid '<i>Licensor</i>' on behalf of the '<i>Original Author</i>' mentioned here. 
		<br/><br/>
		This option should <b>not</b> be used to republish the work of others already published under a Creative Commons Licence, either elsewhere or on Geograph; such content is not appropriate for Geograph.
		</dd>
	</dl>
	<div style="text-align:right"><input type="checkbox" name="pattrib_default" value="on" id="pattrib_default" {if $credit_default}checked="checked"{/if}/>Make this my new default from now on</div>
	
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