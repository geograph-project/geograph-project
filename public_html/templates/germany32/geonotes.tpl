{include file="_std_begin.tpl"}

{* TODO
* move more code to geonotes.js
* display note while editing
* display also pending notes (->php!)
* status for notes (probably different colors):
     unsaved changes
     saved, but pending (i.e. awaiting moderation)
     saved
* commit: prevent double posts
* implement real controls (i.e. dragging frame etc.)
* note text: create links ([[AB1234]] or [[12345]] or even https?://.*)
* replace geonotewidth,... hack with something sensible,
   e.g. var initialvalus = { id1 : { 'x1' : ... } ... }
* use a.getAttribute('b') etc. instead of a.b
* compatibility checks
* edit editimage.php accordingly
*}
{if $image}

<h2><a title="Grid Reference {$image->grid_reference}{if $square_count gt 1} :: {$square_count} images{/if}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> : {$image->bigtitle|escape:'html'}</h2>

<div class="{if $image->isLandscape()}photolandscape{else}photoportrait{/if}">
  <div class="img-shadow" id="mainphoto">{$image->getFull(true,"class=\"geonotes\" usemap=\"#notesmap\" id=\"gridimage\"")}
    <map name="notesmap" id="notesmap">
    {foreach item=note from=$notes}
    <area alt="" title="{$note->comment|escape:'html'}" id="notearea{$note->note_id}" nohref="nohref" shape="rect" coords="{$note->x1},{$note->y1},{$note->x2},{$note->y2}"
    geonotewidth="{$note->init_imgwidth}" geonoteheight="{$note->init_imgheight}" geonotex1="{$note->init_x1}" geonotex2="{$note->init_x2}" geonotey1="{$note->init_y1}" geonotey2="{$note->init_y2}" geonotestatus="{$note->status}" geonotependingchanges="{if $note->pendingchanges}1{else}0{/if}" />
    {/foreach}
    </map>
    {foreach item=note from=$notes}
    <a title="{$note->comment|escape:'html'}" id="notebox{$note->note_id}" href="#" style="left:{$note->x1}px;top:{$note->y1}px;width:{$note->x2-$note->x1+1}px;height:{$note->y2-$note->y1+1}px;z-index:{$note->z+50}" class="notebox"><span></span></a>
    {/foreach}
    {foreach item=note from=$notes}
    <div id="notetext{$note->note_id}" class="geonote"><p>{$note->html()}</p></div>
    {/foreach}
    <script type="text/javascript" src="/js/geonotes.js"></script>
  </div>

  {if $image->comment1 neq '' && $image->comment2 neq '' && $image->comment1 neq $image->comment2}
     {if $image->title1 eq ''}
       <div class="caption"><b>{$image->title2|escape:'html'}</b></div>
       <div class="caption">{$image->comment2|escape:'html'|nl2br|geographlinks|hidekeywords}</div>
       <hr style="width:3em" />
       <div class="caption">{$image->comment1|escape:'html'|nl2br|geographlinks|hidekeywords}</div>
     {else}
       <div class="caption"><b>{$image->title1|escape:'html'}</b></div>
       <div class="caption">{$image->comment1|escape:'html'|nl2br|geographlinks|hidekeywords}</div>
       <hr style="width:3em" />
       {if $image->title2 neq ''}
       <div class="caption"><b>{$image->title2|escape:'html'}</b></div>
       {/if}
       <div class="caption">{$image->comment2|escape:'html'|nl2br|geographlinks|hidekeywords}</div>
     {/if}
  {else}
     {if $image->title1 neq ''}
       {if $image->title2 neq '' && $image->title2 neq $image->title1 }
       <div class="caption"><b>{$image->title1|escape:'html'} ({$image->title2|escape:'html'})</b></div>
       {else}
       <div class="caption"><b>{$image->title1|escape:'html'}</b></div>
       {/if}
     {else}
       <div class="caption"><b>{$image->title2|escape:'html'}</b></div>
     {/if}
     {if $image->comment1 neq ''}
       <div class="caption">{$image->comment1|escape:'html'|nl2br|geographlinks|hidekeywords}</div>
     {elseif $image->comment2 neq ''}
       <div class="caption">{$image->comment2|escape:'html'|nl2br|geographlinks|hidekeywords}</div>
     {/if}
  {/if}

</div>

<!-- Creative Commons Licence -->
<div class="ccmessage"><a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img 
alt="Creative Commons Licence [Some Rights Reserved]" src="http://creativecommons.org/images/public/somerights20.gif" /></a> &nbsp; &copy; Copyright <a title="View profile" href="{$image->profile_link}">{$image->realname|escape:'html'}</a> and  
licensed for reuse under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">Creative Commons Licence</a>.</div>
<!-- /Creative Commons Licence -->


{*<div style="text-align:center;margin-top:3px" class="interestBox" id="styleLinks"></div>
<script type="text/javascript">
/* <![CDATA[ */
{literal}
function addStyleLinks() {
{/literal}
	document.getElementById('styleLinks').innerHTML = 'Background for photo viewing: {if $maincontentclass eq "content_photowhite"}<b>white</b>{else}<a hr'+'ef="/photo/{$image->gridimage_id}?style=white" rel="nofollow" class="robots-nofollow robots-noindex">White</a>{/if}/{if $maincontentclass eq "content_photoblack"}<b>black</b>{else}<a hr'+'ef="/photo/{$image->gridimage_id}?style=black" rel="nofollow" class="robots-nofollow robots-noindex">Black</a>{/if}/{if $maincontentclass eq "content_photogray"}<b>grey</b>{else}<a hr'+'ef="/photo/{$image->gridimage_id}?style=gray" rel="nofollow" class="robots-nofollow robots-noindex">Grey</a>{/if}';
{literal}
}
 AttachEvent(window,'load',addStyleLinks,false);


function redrawMainImage() {
	el = document.getElementById('mainphoto');
	el.style.display = 'none';
	el.style.display = '';
}
 /*AttachEvent(window,'load',redrawMainImage,false);
 AttachEvent(window,'load',showMarkedImages,false);*/
  
{/literal}
/* ]]> */
</script>
*}

<script type="text/javascript">
/* <![CDATA[ */
var imageid = {$image->gridimage_id};
var imgurl = '{$img_url}';
var imgwidth = {$std_width};
var imgheight = {$std_height};
var stdwidth = {$std_width};
var stdheight = {$std_height};
{if $showorig}
{literal}
function setimgsize(large) {
	if (large) {
{/literal}
		imgurl = '{$orig_url}';
		imgwidth = {$original_width};
		imgheight = {$original_height};
{literal}
	} else {
{/literal}
		imgurl = '{$img_url}';
		imgwidth = {$std_width};
		imgheight = {$std_height};
{literal}
	}
	//alert(imgurl+', '+imgwidth+'x'+imgheight);
	el = document.getElementById('gridimage');
	el.src = imgurl;
	el.width = imgwidth;
	el.height = imgheight;
	gn.recalcBoxes(el);
}
{/literal}
{/if}
{literal}
	function getXMLRequestObject() // stolen from admin/moderation.js
	{
		var xmlhttp=false;
			
		/*@cc_on @*/
		/*@if (@_jscript_version >= 5)
		// JScript gives us Conditional compilation, we can cope with old IE versions.
		// and security blocked creation of the objects.
		 try {
		  xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
		 } catch (e) {
		  try {
		   xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		  } catch (E) {
		   xmlhttp = false;
		  }
		 }
		@end @*/
		if (!xmlhttp && typeof XMLHttpRequest!='undefined') {
		  xmlhttp = new XMLHttpRequest();
		}

		return xmlhttp;
	}

	function commitNote(id) { // FIXME
	//function commitNote(area) { // FIXME
		var area = document.getElementById("notearea"+id);
		//var noteid = area.id.substr(8);
		var elx1 = document.getElementById("note_x1_"+id);
		var elx2 = document.getElementById("note_x2_"+id);
		var ely1 = document.getElementById("note_y1_"+id);
		var ely2 = document.getElementById("note_y2_"+id);
		var elz = document.getElementById("note_z_"+id);
		var eliw = document.getElementById("note_imgwidth_"+id);
		var elih = document.getElementById("note_imgheight_"+id);
		var eltxt = document.getElementById("note_comment_"+id);
		var eldel = document.getElementById("note_del_"+id);
		var valx1 = parseInt(elx1.value);
		var valx2 = parseInt(elx2.value);
		var valy1 = parseInt(ely1.value);
		var valy2 = parseInt(ely2.value);
		var valz = parseInt(elz.value);
		var valiw = eliw.value;
		var valih = elih.value;
		var valtxt = eltxt.value;
		var valdel = eldel.checked;

		// FIXME check if there are really changed values
		// FIXME check if comment contains non wihitespace characters
		// FIXME check if numbers are valid?

		// FIXME disable editing and submit button until we receive a response (or timeout?)
		// FIXME for new entries, receive new noteid and use that noteid in future commitNote calls
		var postnoteid = area.geonoteid;
		var postdata = 'commit=1'
		postdata += '&id=' + encodeURIComponent(postnoteid);
		postdata += '&imageid=' + encodeURIComponent(imageid);
		postdata += '&x1=' + encodeURIComponent(valx1);
		postdata += '&y1=' + encodeURIComponent(valy1);
		postdata += '&x2=' + encodeURIComponent(valx2);
		postdata += '&y2=' + encodeURIComponent(valy2);
		postdata += '&imgwidth=' + encodeURIComponent(valiw);
		postdata += '&z=' + encodeURIComponent(valz);
		postdata += '&imgheight=' + encodeURIComponent(valih);
		postdata += '&comment=' + encodeURIComponent(valtxt);
		postdata += '&status=' + (valdel?'deleted':'visible');

		var url="/geonotes.php";
		var req=getXMLRequestObject();
		var reqTimer = setTimeout(function() {
		       req.abort();
		       // FIXME ...
		}, 30000);
		req.onreadystatechange = function()
		{
			if (req.readyState!=4) {
				return;
			}
			clearTimeout(reqTimer);

			//patch the memory leak
			req.onreadystatechange = function() {};

			if (req.status != 200) {
				alert("Cannot communicate with server, status " + req.status);
				return;
			}

			var parts = req.responseText.split(':');
			var renum = /^-?[0-9]+$/;
			if (!renum.test(parts[0])) {
				alert("Unexpected response from server");
				return;
			}

			var rcode = parseInt(parts[0]);
			if (rcode < 0) {
				alert("Error: Server returned error " + -rcode + " (" + parts[2] + ")");
				return;
			}

			//FIXME  set area.geonoteid if created new note
		}
		/*url += '?commit=1&' + postdata;
		req.open("GET", url,true);
		req.send(null);*/
		req.open("POST", url,true);
		req.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		//req.setRequestHeader("Content-length", postdata.length);
		//req.setRequestHeader("Connection", "close");
		req.send(postdata);
	}

	function updatenotepos(id, item) {
		var area = document.getElementById("notearea"+id);
		var elx1 = document.getElementById("note_x1_"+id);
		var elx2 = document.getElementById("note_x2_"+id);
		var ely1 = document.getElementById("note_y1_"+id);
		var ely2 = document.getElementById("note_y2_"+id);
		var elcur = document.getElementById("note_"+item+"_"+id);
		var eliw = document.getElementById("note_imgwidth_"+id);
		var elih = document.getElementById("note_imgheight_"+id);
		var valx1 = parseInt(elx1.value);
		var valx2 = parseInt(elx2.value);
		var valy1 = parseInt(ely1.value);
		var valy2 = parseInt(ely2.value);
		var valcur = parseInt(elcur.value);
		var valiw = eliw.value;
		var valih = elih.value;
		if (valiw != imgwidth) {
			if (elx1 != elcur) valx1 = Math.floor(valx1 * imgwidth / valiw);
			if (elx2 != elcur) valx2 = Math.floor(valx2 * imgwidth / valiw);
			valiw = imgwidth;
		}
		if (valih != imgheight) {
			if (ely1 != elcur) valy1 = Math.floor(valy1 * imgheight / valih);
			if (ely2 != elcur) valy2 = Math.floor(valy2 * imgheight / valih);
			valih = imgheight;
		}
		if (Math.floor((valy2-valy1+1)*stdheight/imgheight) < 15) {
			valy2 = valy1 + Math.ceil(15*imgheight/stdheight) - 1;
		} else if (valy2-valy1 >= imgheight) {
			valy2 = valy1 + imgheight - 1;
		}
		if (Math.floor((valx2-valx1+1)*stdwidth/imgwidth) < 15) {
			valx2 = valx1 + Math.ceil(15*imgwidth/stdwidth) - 1;
		} else if (valx2-valx1 >= imgwidth) {
			valx2 = valx1 + imgwidth - 1;
		}
		if (valy1 < 0) {
			valy2 -= valy1;
			valy1 = 0;
		} else if (valy2 >= imgheight) {
			var delta = valy2 - imgheight + 1;
			valy1 -= delta;
			valy2 -= delta;
		}
		if (valx1 < 0) {
			valx2 -= valx1;
			valx1 = 0;
		} else if (valx2 >= imgwidth) {
			var delta = valx2 - imgwidth + 1;
			valx1 -= delta;
			valx2 -= delta;
		}
		eliw.value = valiw;
		elih.value = valih;
		elx1.value = valx1;
		elx2.value = valx2;
		ely1.value = valy1;
		ely2.value = valy2;
		area.geonotex1 = valx1;
		area.geonotex2 = valx2;
		area.geonotey1 = valy1;
		area.geonotey2 = valy2;
		area.geonotewidth = valiw;
		area.geonoteheight = valih;
		gn.recalcBox(area);
	}
	function updatenotez(id) {
		var area = document.getElementById("notearea"+id);
		var el = document.getElementById("note_z_"+id)
		var val = parseInt(el.value);
		if (val < -10) {
			val = -10;
		} else if (val > 10) {
			val = 10;
		}
		el.value = val;
		var box = area.geobox;
		if (box) {
			box.style.zIndex = val + 50;
		}
	}
	function updatenotedel(id) {
		var area = document.getElementById("notearea"+id);
		var el = document.getElementById("note_del_"+id);
		// FIXME evaluate el.checked
	}
	function updatenotecomment(id) {
		var area = document.getElementById("notearea"+id);
		var el = document.getElementById("note_comment_"+id)
		var val = el.value
		area.title = val; // FIXME area.title='' if box exists?
		var box = area.geobox;
		if (box) {
			txt = box.geonote
			if (txt) {
				box.title = '';
				/*while (txt.hasChildNodes()) {
					txt.removeChild(txt.lastChild);
				}*/
				// <p> + val + </p>
				var p = document.createElement('p');
				//p.appendChild(document.createTextNode(val)); // FIXME create links? line breaks -> <br >, ...
				var nlval = val.replace('\r\n', '\n');
				var lines = nlval.split('\n');
				for (var i = 0; i < lines.length-1; i++) {
					p.appendChild(document.createTextNode(lines[i]));
					p.appendChild(document.createElement('br'));
				}
				p.appendChild(document.createTextNode(lines[lines.length-1]));

				//txt.appendChild(p);
				txt.replaceChild(p, txt.lastChild);

				/* reset box size */
				gn.initBoxWidth(txt);
			} else {
				box.title = val;
			}
		}
	}
	function addinput(form, idprefix, noteid, labelstr, size, value, changehandler, readonly) {
		ele = document.createElement('label');
		ele.for = 'note_' + idprefix + '_' + noteid;
		ele.appendChild(document.createTextNode(labelstr));
		form.appendChild(ele);
		ele = document.createElement('input');
		ele.type = 'text';
		ele.size = size;
		ele.name = 'note_' + idprefix + '_' + noteid;
		ele.id = 'note_' + idprefix + '_' + noteid;
		ele.value = value;
		ele.readOnly = readonly;
		if (changehandler !== null) {
			gn.addEvent(ele,"change",changehandler);
		}
		form.appendChild(ele);
		form.appendChild(document.createElement('br'));
	}
	var newnotes = 0;
	function addNote() {
		++newnotes;
		var noteid = -newnotes;
		var img = document.getElementById('gridimage');

		var area = document.createElement('area');
		area.id = "notearea" + noteid;
		var x2 = Math.ceil(15*imgwidth/stdwidth)-1;
		var y2 = Math.ceil(15*imgheight/stdheight)-1;
		area.shape = 'rect';
		area.noHref = true;

		var box = document.createElement('a');
		box.id = "notebox" + noteid;
		box.className = 'notebox';
		box.href = '#';
		box.style.zIndex = 0 + 50;
		box.appendChild(document.createElement('span'));

		var txt = document.createElement('div');
		txt.id = "notetext" + noteid;
		txt.className = 'geonote';
		txt.appendChild(document.createElement('p'));

		var nmap = document.getElementById('notesmap');
		var pdiv = document.getElementById('mainphoto');

		gn.addNote(area, box, txt, nmap, pdiv, pdiv, 0, 0, x2, y2, img, 'visible', true, noteid);

		var head = document.createElement('h4');
		head.appendChild(document.createTextNode('New annotation #'+-noteid));
		var form = document.createElement('form');
		var ele;

		addinput(form, 'x1', noteid, 'x1:', 10, area.geonotex1, function(){updatenotepos(noteid, 'x1');}, false);
		addinput(form, 'y1', noteid, 'y1:', 10, area.geonotey1, function(){updatenotepos(noteid, 'y1');}, false);
		addinput(form, 'x2', noteid, 'x2:', 10, area.geonotex2, function(){updatenotepos(noteid, 'x2');}, false);
		addinput(form, 'y2', noteid, 'y2:', 10, area.geonotey2, function(){updatenotepos(noteid, 'y2');}, false);
		addinput(form, 'imgwidth', noteid, 'reference width:', 10, imgwidth, null, true);
		addinput(form, 'imgheight', noteid, 'reference height:', 10, imgheight, null, true);
		addinput(form, 'z', noteid, 'z:', 10, 0, function(){updatenotez(noteid);}, false);

		ele = document.createElement('label');
		ele.for = 'note_del_' + noteid;
		ele.appendChild(document.createTextNode('delete:'));
		form.appendChild(ele);
		ele = document.createElement('input');
		ele.type = 'checkbox';
		ele.checked = false;
		ele.name = 'note_del_' + noteid;
		ele.id = 'note_del_' + noteid;
		ele.value = "1";
		gn.addEvent(ele,"change",function(){updatenotedel(noteid);});
		form.appendChild(ele);
		form.appendChild(document.createElement('br'));

		ele = document.createElement('textarea');
		ele.rows = 10;
		ele.cols = 50;
		ele.id = 'note_comment_' + noteid;
		gn.addEvent(ele,"change",function(){updatenotecomment(noteid);});
		form.appendChild(ele);
		form.appendChild(document.createElement('br'));

		ele = document.createElement('input');
		ele.type = 'button';
		ele.value = 'commit';
		gn.addEvent(ele,"click",function(){commitNote(noteid);});
		form.appendChild(ele);

		var forms = document.getElementById('noteforms');
		var addbutton = document.getElementById('addbutton');
		forms.insertBefore(head, addbutton);
		forms.insertBefore(form, addbutton);
	}
{/literal}
/* ]]> */
</script>
<div>
{if $showorig}
	<form action="javascript:void(0);">
		<label for="imgsize">Image size:</label>
		<select name="imgsize" id="imgsize" onchange="setimgsize(this.options[this.selectedIndex].value=='original');">
			<option value="default" selected="selected">{$std_width}x{$std_height} (default)</option>
			<option value="original">{$original_width}x{$original_height}</option>
		</select>
	</form>
{/if}
</div>
<div id="noteforms" style="max-height:50ex;overflow:auto;background-color:#eeeeee">
    {foreach item=note from=$notes}
	<h4>Annotation #{$note->note_id}</h4>
	<form action="javascript:void(0);" id="note_form_{$note->note_id}">
		<label for="note_x1_{$note->note_id}">x1:</label><input type="text" size="10" name="note_x1_{$note->note_id}" id="note_x1_{$note->note_id}" value="{$note->init_x1}" onchange="updatenotepos({$note->note_id}, 'x1');" /><br />
		<label for="note_y1_{$note->note_id}">y1:</label><input type="text" size="10" name="note_y1_{$note->note_id}" id="note_y1_{$note->note_id}" value="{$note->init_y1}" onchange="updatenotepos({$note->note_id}, 'y1');" /><br />
		<label for="note_x2_{$note->note_id}">x2:</label><input type="text" size="10" name="note_x2_{$note->note_id}" id="note_x2_{$note->note_id}" value="{$note->init_x2}" onchange="updatenotepos({$note->note_id}, 'x2');" /><br />
		<label for="note_y2_{$note->note_id}">y2:</label><input type="text" size="10" name="note_y2_{$note->note_id}" id="note_y2_{$note->note_id}" value="{$note->init_y2}" onchange="updatenotepos({$note->note_id}, 'y2');" /><br />
		<label for="note_imgwidth_{$note->note_id}">reference width:</label><input type="text" size="10" name="note_imgwidth_{$note->note_id}" id="note_imgwidth_{$note->note_id}" value="{$note->init_imgwidth}" readonly="readonly" /><br />
		<label for="note_imgheight_{$note->note_id}">reference height:</label><input type="text" size="10" name="note_imgheight_{$note->note_id}" id="note_imgheight_{$note->note_id}" value="{$note->init_imgheight}" readonly="readonly" /><br />
		<label for="note_z_{$note->note_id}">z:</label><input type="text" size="10" name="note_z_{$note->note_id}" id="note_z_{$note->note_id}" value="{$note->z}" onchange="updatenotez({$note->note_id});" /><br />
		<label for="note_del_{$note->note_id}">delete:</label><input type="checkbox" name="note_del_{$note->note_id}" id="note_del_{$note->note_id}" value="1" {if $note->status=='deleted'}checked="checked" {/if}onchange="updatenotedel({$note->note_id});" /><br />
		<textarea name="note_comment_{$note->note_id}" id="note_comment_{$note->note_id}" cols="50" rows="10" onchange="updatenotecomment({$note->note_id});">{$note->html(false)}</textarea><br />
		<input type="button" value="commit" onclick="commitNote({$note->note_id});" />
	</form>
    {/foreach}
     <form action="javascript:void(0);" id="addbutton">
          <input type="button" value="Add annotation" onclick="addNote();" />
     </form>
</div>
{else}
<h2>Sorry, image not available</h2>
<p>The image you requested is not available. This maybe due to software error, or possibly because
the image was rejected after submission - please <a title="Contact Us" href="/contact.php">contact us</a>
if you have queries</p>
{/if}

{include file="_std_end.tpl"}
