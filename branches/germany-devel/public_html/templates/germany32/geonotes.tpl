{include file="_std_begin.tpl"}

{* TODO
* move more code to geonotes.js?
* note text: create links ([[AB1234]] or [[12345]] or even https?://.*)
* replace geonotewidth,... hack with something sensible,
   e.g. var initialvalus = { id1 : { 'x1' : ... } ... }
* use a.getAttribute('b') etc. instead of a.b?
* compatibility checks
* implement _GET['note_id'] handling in geonotes.php for editimage.php
* imagemap is only fallback if javascript is not enabled
   => set img.usemap = null inside gn.__initImage(), ignore areas without corresponding
      box and note, there as well as in gn.addNote()
* move everything (besides references to area) from txt/box to area (and finally to something like gn.annotation[id],
  i.e. use something like gn.annotation[id].img instead of document.getElementById("notearea"+id).img
* check if border size has been used correctly in coordinate calculations
* display "unsaved changes" or "unmoderated changes"
* display last error message or "no changed values"
* reset button?
* try to capture all mouse events while dragging, or find out how
  to prevent the browsers from also doing drag and drop...
*}
{if $image}

<h2><a title="Grid Reference {$image->grid_reference}{if $square_count gt 1} :: {$square_count} images{/if}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> : {$image->bigtitle|escape:'html'}</h2>

<div class="{if $image->isLandscape()}photolandscape{else}photoportrait{/if}">
  <div class="img-shadow" id="mainphoto">{$image->getFull(true,"class=\"geonotes\" usemap=\"#notesmap\" id=\"gridimage\"")}
    <map name="notesmap" id="notesmap">
    {foreach item=note from=$notes}
    <area alt="" title="{$note->comment|escape:'html'}" id="notearea{$note->note_id}" nohref="nohref" shape="rect" coords="{$note->x1},{$note->y1},{$note->x2},{$note->y2}"
    geonotewidth="{$note->init_imgwidth}" geonoteheight="{$note->init_imgheight}" geonotex1="{$note->init_x1}" geonotex2="{$note->init_x2}" geonotey1="{$note->init_y1}" geonotey2="{$note->init_y2}" geonotestatus="{$note->status}" geonotependingchanges="{if $note->pendingchanges}1{else}0{/if}"/>
    {/foreach}
    </map>
    {foreach item=note from=$notes}
    <a title="{$note->comment|escape:'html'}" id="notebox{$note->note_id}" href="#" style="left:{$note->x1}px;top:{$note->y1}px;width:{$note->x2-$note->x1+1-2}px;height:{$note->y2-$note->y1+1-2}px;z-index:{$note->z+50}" class="notebox"><span></span></a>
    {/foreach}
    {foreach item=note from=$notes}
    <div id="notetext{$note->note_id}" class="geonote"><p>{$note->html()}</p></div>
    {/foreach}
    <div id="noteboxedit" class="noteboxedit">
      <div id="noteboxeditbg" class="noteboxeditbg"></div>
      <div id="noteboxedit00" class="noteboxbutton"></div>
      <div id="noteboxedit01" class="noteboxbutton"></div>
      <div id="noteboxedit02" class="noteboxbutton"></div>
      <div id="noteboxedit10" class="noteboxbutton"></div>
      <div id="noteboxedit11" class="noteboxbutton"></div>
      <div id="noteboxedit12" class="noteboxbutton"></div>
      <div id="noteboxedit20" class="noteboxbutton"></div>
      <div id="noteboxedit21" class="noteboxbutton"></div>
      <div id="noteboxedit22" class="noteboxbutton"></div>
    </div>
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


<script type="text/javascript">
/* <![CDATA[ */
var curedit = 0;
var dragx1, dragx2, dragy1, dragy2;
var dragmx, dragmy;
var minx, maxx, miny, maxy, minwidth, minheight;
var dragging = -1;
var editbuttons = [];
var imageid = {$image->gridimage_id};
var imgurl = '{$img_url}';
var imgwidth = {$std_width};
var imgheight = {$std_height};
var stdwidth = {$std_width};
var stdheight = {$std_height};
{if $showorig}
{literal}
function setimgsize(large) {
	stopedit(curedit);
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
	function stopedit(id)
	{
		if (!id || id != curedit) {
			return;
		}
		curedit = 0;
		dragging = -1;
		var area = document.getElementById("notearea"+id);
		var edbox = document.getElementById("noteboxedit");
		area.geonotehide = false;
		edbox.style.display = 'none';
	}
	function drawedit(area, edbox, x1, x2, y1, y2)
	{
		var img = area.geoimg;
		var padding = gn.__getPadding(img);
		var dx = padding[0];
		var dy = padding[1];
		if (img.offsetParent) { // try img.x,img.y otherwise?
			dx += img.offsetLeft;
			dy += img.offsetTop;
		}
		dragx1 = x1;
		dragx2 = x2;
		dragy1 = y1;
		dragy2 = y2;
		var borderedbox = 1; // FIXME hard coded
		edbox.style.left = (x1 + dx) + 'px';
		edbox.style.top = (y1 + dy) + 'px';
		edbox.style.width = (x2 - x1 + 1 - 2*borderedbox) + 'px';
		edbox.style.height = (y2 - y1 + 1 - 2*borderedbox) + 'px';
		var bborder = 1; // FIXME hard coded
		var bwidth = 3 + 2*bborder; // FIXME hard coded
		var bheight = 3 + 2*bborder; // FIXME hard coded
		var xarray = [ 0, Math.floor((x2 - x1 + 1 - bwidth)/2),  x2 - x1 - bwidth + 1 ];
		var yarray = [ 0, Math.floor((y2 - y1 + 1 - bheight)/2), y2 - y1 - bheight + 1 ];
		for (var j = 0; j <= 2; ++j) {
			for (var i = 0; i <= 2; ++i) {
				//var ebbut = document.getElementById("noteboxedit"+i+j);
				var ebbut = editbuttons[j*3+i];
				ebbut.style.left = (xarray[i]-bborder) + 'px';
				ebbut.style.top = (yarray[j]-bborder) + 'px';
				ebbut.style.display = 'block';
			}
		}
		edbox.style.display = 'block';
	}
	function startedit(id)
	{
		if (id != curedit) { // refreshes current edit frame otherwise
			stopedit(curedit);
		}
		curedit = id;
		var area = document.getElementById("notearea"+id);
		var edbox = document.getElementById("noteboxedit");
		var box = area.geobox;
		area.geonotehide = true;
		box.style.display = 'none';

		var img = area.geoimg;
		var width = img.width;
		var height = img.height;
		var x1 = Math.floor(area.geonotex1 * width / area.geonotewidth);
		var x2 = Math.floor(area.geonotex2 * width / area.geonotewidth);
		var y1 = Math.floor(area.geonotey1 * height / area.geonoteheight);
		var y2 = Math.floor(area.geonotey2 * height / area.geonoteheight);

		drawedit(area, edbox, x1, x2, y1, y2);
	}
	function toggleedit(id)
	{
		if (id == curedit) {
			stopedit(id);
		} else {
			startedit(id);
		}
	}
	function statuschanged(area)
	{
		var id = area.noteid;
		var form = document.getElementById("note_form_"+id);
		if (area.geonoteunsavedchanges) {
			form.className = "noteformunsaved";
		} else if (area.geonotependingchanges) {
			form.className = "noteformpending";
			// FIXME disable commit button?
		} else {
			form.className = "noteform";
			// FIXME disable commit button?
		}
	}
	function commitNote(id)
	{
		var area = document.getElementById("notearea"+id);
		//var noteid = area.id.substr(8);
		var elcommit = document.getElementById("note_commit_"+id);
		elcommit.disabled = true;
		var elz = document.getElementById("note_z_"+id);
		var valz = parseInt(elz.options[elz.selectedIndex].value);
		var eltxt = document.getElementById("note_comment_"+id);
		var valtxt = eltxt.value;

		var postdata = 'commit=1';
		postdata += '&id=' + encodeURIComponent(area.geonoteid);
		postdata += '&imageid=' + encodeURIComponent(imageid);
		postdata += '&x1=' + encodeURIComponent(area.geonotex1);
		postdata += '&y1=' + encodeURIComponent(area.geonotey1);
		postdata += '&x2=' + encodeURIComponent(area.geonotex2);
		postdata += '&y2=' + encodeURIComponent(area.geonotey2);
		postdata += '&imgwidth=' + encodeURIComponent(area.geonotewidth);
		postdata += '&imgheight=' + encodeURIComponent(area.geonoteheight);
		postdata += '&z=' + encodeURIComponent(valz);
		postdata += '&comment=' + encodeURIComponent(valtxt);
		postdata += '&status=' + area.geonotestatus;

		var url="/geonotes.php";
		var req=getXMLRequestObject();
		var reqTimer = setTimeout(function() {
		       req.abort();
		}, 30000);
		req.onreadystatechange = function()
		{
			if (req.readyState != 4) {
				return;
			}
			clearTimeout(reqTimer);
			req.onreadystatechange = function() {};

			if (req.status != 200) {
				alert("Cannot communicate with server, status " + req.status);
				elcommit.disabled = false;
				return;
			}

			var parts = req.responseText.split(':');
			var renum = /^-?[0-9]+$/;
			if (!renum.test(parts[0])) {
				alert("Unexpected response from server");
				elcommit.disabled = false;
				return;
			}

			var rcode = parseInt(parts[0]);
			if (rcode < 0) {
				alert("Error: Server returned error " + -rcode + " (" + parts[2] + ")");
				elcommit.disabled = false;
				return;
			}

			if (area.geonoteid < 0) {
				//FIXME error if parts.length < 3 || parts[2] not a number
				area.geonoteid = parseInt(parts[2]);
			}

			area.geonotependingchanges = rcode == 1;
			area.geonoteunsavedchanges = false; // FIXME disable editing until this moment?

			elcommit.disabled = false;
			statuschanged(area);
		}
		/*url += '?' + postdata;
		req.open("GET", url, true);
		req.send(null);*/
		req.open("POST", url, true);
		req.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		//req.setRequestHeader("Connection", "close");
		req.send(postdata);
	}
	function updatenotez(id) {
		var area = document.getElementById("notearea"+id);
		var el = document.getElementById("note_z_"+id)
		var val = parseInt(el.options[el.selectedIndex].value);
		area.geonoteunsavedchanges = true;
		var box = area.geobox;
		if (box) {
			box.style.zIndex = val + 50;
		}
		statuschanged(area);
	}
	function updatenotestatus(id) {
		var area = document.getElementById("notearea"+id);
		var el = document.getElementById("note_status_"+id);
		area.geonotestatus = el.options[el.selectedIndex].value;
		area.geonoteunsavedchanges = true;
		statuschanged(area);
	}
	function updatenotecomment(id) {
		var area = document.getElementById("notearea"+id);
		var el = document.getElementById("note_comment_"+id)
		var val = el.value
		area.title = val; // FIXME area.title='' if box exists?
		area.geonoteunsavedchanges = true;
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
		statuschanged(area);
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
		form.id = 'note_form_' + noteid;
		var ele;

		ele = document.createElement('label');
		ele.for = 'note_z_' + noteid;
		ele.appendChild(document.createTextNode('z:'));
		form.appendChild(ele);
		ele = document.createElement('select');
		ele.name = 'note_z_' + noteid;
		ele.id = 'note_z_' + noteid;
		gn.addEvent(ele,"change",function(){updatenotestatus(noteid);});
		//opt = document.createElement('option');...
		//ele.appendChild(opt);
		//ele.options[0] = new Option("awaiting moderation", "pending", false, false);
		for (var i=-10; i<=10; ++i) {
			ele.options[i+10] = new Option(i, i, false, i==0);
		}
		form.appendChild(ele);
		//form.appendChild(document.createElement('br'));
		form.appendChild(document.createTextNode(' | '));

		ele = document.createElement('label');
		ele.for = 'note_status_' + noteid;
		ele.appendChild(document.createTextNode('status:'));
		form.appendChild(ele);
		ele = document.createElement('select');
		ele.name = 'note_status_' + noteid;
		ele.id = 'note_status_' + noteid;
		gn.addEvent(ele,"change",function(){updatenotestatus(noteid);});
		//opt = document.createElement('option');...
		//ele.appendChild(opt);
		//ele.options[0] = new Option("awaiting moderation", "pending", false, false);
		ele.options[0] = new Option("visible", "visible", false, true);
		ele.options[1] = new Option("deleted", "deleted", false, false);
		form.appendChild(ele);
		//form.appendChild(document.createElement('br'));
		form.appendChild(document.createTextNode(' | '));

		ele = document.createElement('input');
		ele.id = 'note_edit_' + noteid;
		ele.type = 'button';
		ele.value = 'edit box';
		gn.addEvent(ele,"click",function(){toggleedit(noteid);});
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
		ele.id = 'note_commit_' + noteid;
		ele.type = 'button';
		ele.value = 'commit';
		gn.addEvent(ele,"click",function(){commitNote(noteid);});
		form.appendChild(ele);

		var forms = document.getElementById('noteforms');
		var addbutton = document.getElementById('addbutton');
		forms.insertBefore(head, addbutton);
		forms.insertBefore(form, addbutton);
		statuschanged(area);
	}
	function startDrag(e, ix, iy) {
		var mpos = gn.__getMousePosition(e); // TODO compare with mapping1.js
		dragmx = mpos[0];
		dragmy = mpos[1];
		dragging = ix+3*iy;
		//alert(ix+","+iy+":"+dragging);
		dragminy = 0;
		dragmaxy = imgheight - 1;
		dragminwidth = Math.ceil(15*imgwidth/stdwidth);
		dragminheight = Math.ceil(15*imgheight/stdheight);
		var edbox = document.getElementById("noteboxedit");
		var dw = edbox.parentNode.clientWidth;
		var dh = edbox.parentNode.clientHeight;
		var sx = edbox.parentNode.scrollLeft; //FIXME portable?
		var sy = edbox.parentNode.scrollTop;  //FIXME portable?
		dragminx = sx;
		dragmaxx = Math.min(sx + dw - 1, imgwidth - 1);

		/*if (edbox.parentNode.setCapture && !edbox.parentNode.addEventListener) {
			edbox.parentNode.setCapture(false);
		}
		if (window.event) {
			e = window.event;
		}
		if (e.stopPropagation) {
			e.stopPropagation();
		} else {
			e.cancelBubble = true;
		}*/
		return false;
	}
	function stopDrag() {
		dragging = -1;
		/*if (document.releaseCapture) {
			document.releaseCapture();
		}*/
	}
	function dragBox(e) {
		if (dragging == -1) {
			return;
		}
		var ix = dragging % 3;
		var iy = (dragging - ix) / 3;
		var mpos = gn.__getMousePosition(e); // TODO compare with mapping1.js

		var dx = mpos[0] - dragmx;
		var dy = mpos[1] - dragmy;

		var newx1 = dragx1;
		var newx2 = dragx2;
		var newy1 = dragy1;
		var newy2 = dragy2;
		if (ix == 1 && iy == 1) {
			newx1 += dx;
			newx2 += dx;
			newy1 += dy;
			newy2 += dy;
		} else {
			if (ix == 0) {
				newx1 += dx;
			} else if (ix == 2) {
				newx2 += dx;
			}
			if (iy == 0) {
				newy1 += dy;
			} else if (iy == 2) {
				newy2 += dy;
			}
		}
		if (   newx1 < dragminx || newx2 > dragmaxx || newx2-newx1+1 < dragminwidth
		    || newy1 < dragminy || newy2 > dragmaxy || newy2-newy1+1 < dragminheight) {
			return;
		}
		dragmx = mpos[0];
		dragmy = mpos[1];
		var area = document.getElementById("notearea"+curedit);
		var edbox = document.getElementById("noteboxedit");
		drawedit(area, edbox, newx1, newx2, newy1, newy2)
		area.geonotex1 = newx1;
		area.geonotex2 = newx2;
		area.geonotey1 = newy1;
		area.geonotey2 = newy2;
		area.geonotewidth = imgwidth;
		area.geonoteheight = imgheight;
		area.geonoteunsavedchanges = true;
		gn.recalcBox(area); // FIXME do that only in stopedit()?
		statuschanged(area);
		/*if (window.event) {
			e = window.event;
		}
		if (e.stopPropagation) {
			e.stopPropagation();
		} else {
			e.cancelBubble = true;
		}*/
		return false;
	}
	function initnoteedit() {
		for (var j = 0; j <= 2; ++j) {
			for (var i = 0; i <= 2; ++i) {
				var ebbut = document.getElementById("noteboxedit"+i+j);
				var ix = i;
				var iy = j;
				editbuttons[j*3+i] = ebbut;
				//gn.addEvent(ebbut,"mousedown",function(ev){startDrag(ev, i, j);return false;}); // this happens all the time... need more coffee
				gn.addEvent(ebbut,"mousedown",function(ix,iy){return function(ev){startDrag(ev, ix, iy);return false;}}(i,j));
			}
		}
		var edbox = document.getElementById("noteboxedit");
		gn.addEvent(edbox.parentNode, "mousemove", dragBox/*, true*/);
		gn.addEvent(document,"mouseup", stopDrag);
		//gn.addEvent(edbox.parentNode, "mouseup",stopDrag);
	}
	gn.addEvent(window,"load",initnoteedit);
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
	<form action="javascript:void(0);" id="note_form_{$note->note_id}" class="{if $note->pendingchanges}noteformpending{else}noteform{/if}">
		<label for="note_z_{$note->note_id}">z:</label>
		<select name="note_z_{$note->note_id}" id="note_z_{$note->note_id}" onchange="updatenotez({$note->note_id});">
		{section name=zloop start=0 loop=21}{* no negative values... *}
			<option value="{$smarty.section.zloop.index-10}"{if $smarty.section.zloop.index-10==$note->z} selected="selected"{/if}>{$smarty.section.zloop.index-10}</option>
		{/section}
		</select> |
		<label for="note_status_{$note->note_id}">status:</label>
		<select name="note_status_{$note->note_id}" id="note_status_{$note->note_id}" onchange="updatenotestatus({$note->note_id});">
			<option value="pending"{if $note->status=='pending'} selected="selected"{/if}>awaiting moderation</option>
			<option value="visible"{if $note->status=='visible'} selected="selected"{/if}>visible</option>
			<option value="deleted"{if $note->status=='deleted'} selected="selected"{/if}>deleted</option>
		</select> |
		<input type="button" value="edit box" id="note_edit_{$note->note_id}" onclick="toggleedit({$note->note_id});" /><br />
		<textarea name="note_comment_{$note->note_id}" id="note_comment_{$note->note_id}" cols="50" rows="10" onchange="updatenotecomment({$note->note_id});">{$note->html(false)}</textarea><br />
		<input type="button" value="commit" id="note_commit_{$note->note_id}" onclick="commitNote({$note->note_id});" />
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
