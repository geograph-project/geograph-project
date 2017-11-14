	<div class="interestBox">
	<p>Wegen der Lizenz ist es unabdingbar, dass der '<i>Original Author</i>' oder Fotograf korrekt angegeben ist. In diesem Abschnitt kann der Fotograf entsprechend
	genannt werden.</p>
	
	<dl>
		<dt id="dt_self"><input type="radio" name="pattrib" value="self" id="pattrib_self" {if !$credit}checked="checked"{/if} onclick="updateAttribDivs()"/><label for="pattrib_self">Ich bin der Fotograf</label></dt>
		<dd id="dd_self" style="border:1px solid gray; padding:5px{if $credit};display:none{/if}">Diese Option ist zu benutzen, wenn der einreichende '<i>Geograph-Account-Inhaber</i>' das Bild aufgenommen hat.</dd>
		<div style="padding:10px"><i>- oder -</i></div>
		<dt id="dt_other"><input type="radio" name="pattrib" value="other" id="pattrib_other" onclick="updateAttribDivs()" {if $credit}checked="checked"{/if}/><label for="pattrib_other">Ich bin nicht der Fotograf und muss den Urheber des Bilds angeben</label></dt>
		<dd id="dd_other" style="border:1px solid gray; padding:5px{if !$credit};display:none{/if}">Mit dieser Option bestätig der '<i>Geograph-Account-Inhaber</i>', dass er von folgendem Fotografen autorisiert wurde, das Bild unter dieser Lizenz zu veröffentlichen ({newwin href="/help/what_is_a_licensor" text="Was heißt das?"}):
		<br/><br/>
		Name des Fotografen: <input type="text" name="pattrib_name" value="{$credit|escape:"html"}" size="40"/>
		<br/><br/>
		Hinweis: Es muss sichergestellt sein, dass das Bild für den obengenannten Fotografen veröffentlicht werden darf! 
		<br/><br/>
		Diese Option soll <b>nicht</b> benutzt werden um Bilder wiederzuveröffentlichen, die andere unter einer Creative-Commons-Lizenz veröffentlicht haben, sei es auf Geograph oder anderswo; solche Bilder sind für Geograph nicht geeignet.
		</dd>
	</dl>
	<div style="text-align:right"><input type="checkbox" name="pattrib_default" value="on" id="pattrib_default" {if $credit_default}checked="checked"{/if}/>Als Standardeinstellung übernehmen</div>
	
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
