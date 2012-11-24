{include file="_std_begin.tpl"}

{* TODO
* note text: convert https?://.*
* replace data-geonote-width,... hack with something sensible?
   e.g. var initialvalues = { id1 : { 'x1' : ... } ... }
* use a.getAttribute('b') etc. instead of a.b?
* compatibility checks (test if _all_ needed functions are available early, i.e. in init routine)
* test IE compatibility
* "reset" button?
* geonote.php: reverse order of notes (latest note = first)?
* dark text on lighter background?
* css -> geonotes.css?
* if $ticket:
   * display ticket changes also to other users
   * improve table style
   * get rid of status lines
* statusline: style->class
*}
{if $image}

<h2><a title="Grid Reference {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> : {$image->bigtitle|escape:'html'}</h2>

<div class="{if $image->isLandscape()}photolandscape{else}photoportrait{/if}">
  <div class="img-shadow" id="mainphoto"><div class="notecontainer" id="notecontainer">
    {$image->getFull(true,"class=\"geonotes\" usemap=\"#notesmap\" id=\"gridimage\"")}
    <map name="notesmap" id="notesmap">
    {foreach item=note from=$notes}
    <area alt="" title="{$note->comment|escape:'html'}" id="notearea{$note->note_id}" nohref="nohref" shape="rect" coords="{$note->x1},{$note->y1},{$note->x2},{$note->y2}"
    data-geonote-width="{$note->init_imgwidth}" data-geonote-height="{$note->init_imgheight}" data-geonote-x1="{$note->init_x1}" data-geonote-x2="{$note->init_x2}" data-geonote-y1="{$note->init_y1}" data-geonote-y2="{$note->init_y2}" data-geonote-status="{$note->status}" data-geonote-pendingchanges="{if $note->pendingchanges}1{else}0{/if}" />
    {/foreach}
    {foreach item=note from=$oldnotes}
    <area alt="" title="{$note->comment|escape:'html'}" id="noteareaold{$note->note_id}" nohref="nohref" shape="rect" coords="{$note->x1},{$note->y1},{$note->x2},{$note->y2}"
    data-geonote-width="{$note->init_imgwidth}" data-geonote-height="{$note->init_imgheight}" data-geonote-x1="{$note->init_x1}" data-geonote-x2="{$note->init_x2}" data-geonote-y1="{$note->init_y1}" data-geonote-y2="{$note->init_y2}" data-geonote-status="{$note->status}" data-geonote-pendingchanges="{if $note->pendingchanges}1{else}0{/if}" data-geonote-noteclass="old" />
    {/foreach}
    {foreach item=note from=$newnotes}
    <area alt="" title="{$note->comment|escape:'html'}" id="noteareanew{$note->note_id}" nohref="nohref" shape="rect" coords="{$note->x1},{$note->y1},{$note->x2},{$note->y2}"
    data-geonote-width="{$note->init_imgwidth}" data-geonote-height="{$note->init_imgheight}" data-geonote-x1="{$note->init_x1}" data-geonote-x2="{$note->init_x2}" data-geonote-y1="{$note->init_y1}" data-geonote-y2="{$note->init_y2}" data-geonote-status="{$note->status}" data-geonote-pendingchanges="{if $note->pendingchanges}1{else}0{/if}" data-geonote-noteclass="new" />
    {/foreach}
    </map>
    {foreach item=note from=$notes}
    <div id="notebox{$note->note_id}" style="left:{$note->x1}px;top:{$note->y1}px;width:{$note->x2-$note->x1+1}px;height:{$note->y2-$note->y1+1}px;z-index:{$note->z+50}" class="notebox"><span></span></div>
    {/foreach}
    {foreach item=note from=$oldnotes}
    <div id="noteboxold{$note->note_id}" style="left:{$note->x1}px;top:{$note->y1}px;width:{$note->x2-$note->x1+1}px;height:{$note->y2-$note->y1+1}px;z-index:{$note->z+50}" class="noteboxold"><span></span></div>
    {/foreach}
    {foreach item=note from=$newnotes}
    <div id="noteboxnew{$note->note_id}" style="left:{$note->x1}px;top:{$note->y1}px;width:{$note->x2-$note->x1+1}px;height:{$note->y2-$note->y1+1}px;z-index:{$note->z+50}" class="noteboxnew"><span></span></div>
    {/foreach}
    {foreach item=note from=$notes}
    <div id="notetext{$note->note_id}" class="geonote"><p>{$note->comment|escape:'html'|nl2br|geographlinks:false:true:true}</p>
    {if !$ticket}
    <hr /><input id="note_t_edit_{$note->note_id}" type="button" value="edit" onclick="return editNote('{$note->note_id}');"><input id="note_t_delete_{$note->note_id}" type="button" value="delete" onclick="return deleteNote('{$note->note_id}');">
    {/if}
    </div>
    {/foreach}
    {foreach item=note from=$oldnotes}
    <div id="notetextold{$note->note_id}" class="geonoteold"><p>{$note->comment|escape:'html'|nl2br|geographlinks:false:true:true}</p></div>
    {/foreach}
    {foreach item=note from=$newnotes}
    <div id="notetextnew{$note->note_id}" class="geonotenew"><p>{$note->comment|escape:'html'|nl2br|geographlinks:false:true:true}</p></div>
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
    <script type="text/javascript" src="{"/js/geonotes.js"|revision}"></script>
  </div></div>

  <div id="imagetexts" style="display:none">
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

</div>

<!-- Creative Commons Licence -->
<div class="ccmessage"><a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img 
alt="Creative Commons Licence [Some Rights Reserved]" src="http://creativecommons.org/images/public/somerights20.gif" /></a> &nbsp; &copy; Copyright <a title="View profile" href="{$image->profile_link}">{$image->realname|escape:'html'}</a> and  
licensed for reuse under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">Creative Commons Licence</a>.</div>
<!-- /Creative Commons Licence -->


<script type="text/javascript">
/* <![CDATA[ */
var commiterrors = false;
var unsavedchanges = false;
var curedit = 0;
var dragx1, dragx2, dragy1, dragy2;
var dragdx1, dragdx2, dragdy1, dragdy2, dragdxs, dragdys;
var dragmx, dragmy;
var bbwidth, bbheight;
var edboxbordersx, edboxbordersy, edboxborderx, edboxbordery;
var dragging = -1;
var minboxsize = 8; // FIXME hard coded
var editbuttons = [];
var imageid = {$image->gridimage_id};
var imgurl = '{$img_url}';
var imgwidth = {$std_width};
var imgheight = {$std_height};
var stdwidth = {$std_width};
var stdheight = {$std_height};
{if $showorig}
{literal}
function setImgSize(large) {
	stopEdit(curedit);
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
{if $ticket}
	var show_hidden = true;
{literal}
	var notelists = { '' : [], 'new' : [], 'old' : [] }
	var classvisible = { '' : true, 'new' : true, 'old' : true }
	function toggleBoxes(noteclass, hidetext, showtext)
	{
		var button = document.getElementById('toggleclass'+noteclass);
		var visible = !classvisible[noteclass];
		classvisible[noteclass] = visible;
		button.value = visible ? hidetext : showtext;
		for (var i = 0; i < notelists[noteclass].length; ++i) {
			var noteinfo = notelists[noteclass][i];
			noteinfo.hide = !visible;
		}
	}
{/literal}
{else}
	var show_hidden = false;
{/if}
{literal}
	var showtexts = false;
	function toggleTexts(hidemsg, showmsg)
	{
		var button = document.getElementById('toggletexts');
		var div = document.getElementById('imagetexts');
		showtexts = !showtexts;
		if (showtexts) {
			button.value = hidemsg;
			div.style.display = 'block';
		} else {
			button.value = showmsg;
			div.style.display = 'none';
		}
	}
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
	function stopEdit(id)
	{
		if (!id || id != curedit) {
			return;
		}
		var button = document.getElementById('note_edit_' + id);
		button.value = 'edit box';
		curedit = 0;
		dragging = -1;
		var edbox = document.getElementById("noteboxedit");
		gn.notes[id].hide = false;
		edbox.style.display = 'none';
	}
	function drawEdit(id, edbox, x1, x2, y1, y2)
	{
		var imageinfo = gn.notes[id].imageinfo;
		var img = imageinfo.img;
		var dx = imageinfo.paddborderoffsetx;
		var dy = imageinfo.paddborderoffsety;

		edbox.style.left = (x1 + dx) + 'px';
		edbox.style.top = (y1 + dy) + 'px';
		edbox.style.width = (x2 - x1 + 1 - edboxbordersx) + 'px';
		edbox.style.height = (y2 - y1 + 1 - edboxbordersy) + 'px';
		var xarray = [ -bbwidth +1, Math.floor((x2 - x1 + 1 - bbwidth)/2),  x2 - x1 ];
		var yarray = [ -bbheight+1, Math.floor((y2 - y1 + 1 - bbheight)/2), y2 - y1 ];
		for (var j = 0; j <= 2; ++j) {
			for (var i = 0; i <= 2; ++i) {
				var ebbut = editbuttons[j*3+i];
				ebbut.style.left = (xarray[i]-edboxborderx) + 'px';
				ebbut.style.top = (yarray[j]-edboxbordery) + 'px';
				ebbut.style.display = 'block';
			}
		}
		edbox.style.display = 'block';
	}
	function startEdit(id)
	{
		if (id != curedit) { // refreshes current edit frame otherwise
			stopEdit(curedit);
		}
		curedit = id;
		var button = document.getElementById('note_edit_' + id);
		button.value = 'stop editing';
		var noteinfo = gn.notes[id];
		var box = noteinfo.box;
		noteinfo.hide = true;
		box.style.display = 'none';

		var img = noteinfo.img;
		var width = img.width;
		var height = img.height;
		var x1 = Math.floor(noteinfo.x1 * width / noteinfo.width);
		var x2 = Math.floor(noteinfo.x2 * width / noteinfo.width);
		var y1 = Math.floor(noteinfo.y1 * height / noteinfo.height);
		var y2 = Math.floor(noteinfo.y2 * height / noteinfo.height);
		var minwidth = Math.ceil(minboxsize*imgwidth/stdwidth);
		var minheight = Math.ceil(minboxsize*imgheight/stdheight);
		var dx = x2 - x1 + 1 - minwidth;
		if (dx < 0) {
			x2 -= dx;
			if (x2 >= width) {
				x1 = width - minwidth;
				x2 = width - 1;
			}
		}
		var dy = y2 - y1 + 1 - minheight;
		if (dy < 0) {
			y2 -= dy;
			if (y2 >= height) {
				y1 = height - minheight;
				y2 = height - 1;
			}
		}

		var edbox = document.getElementById("noteboxedit");
		drawEdit(id, edbox, x1, x2, y1, y2);
	}
	function toggleEdit(id)
	{
		if (id == curedit) {
			stopEdit(id);
		} else {
			startEdit(id);
		}
	}
	function updateStatusLine()
	{
		var msg = '';
		if (unsavedchanges) {
			msg = 'There are unsaved changes.';
		}
		if (commiterrors) {
			if (msg != '') {
				msg += ' | ';
			}
			msg += 'The server reported errors when saving the changes.';
		}
		var statusline = document.getElementById("statusline");
		if (statusline.firstChild)
			statusline.replaceChild(document.createTextNode(msg), statusline.firstChild);
		else
			statusline.appendChild(document.createTextNode(msg));
	}
	function statusChanged(id, globalstatus)
	{
		var form = document.getElementById("note_form_"+id);
		var noteinfo = gn.notes[id];
		var msg = '';
		unsavedchanges |= noteinfo.unsavedchanges;
		if (noteinfo.unsavedchanges) {
			form.className = "noteformunsaved";
			msg = "There are unsaved changes.";
			// FIXME enable commit button?
		} else if (noteinfo.pendingchanges) {
			form.className = "noteformpending";
			msg = "There are unmoderated changes.";
			// FIXME disable commit button?
		} else {
			msg = '';
			form.className = "noteform";
			// FIXME disable commit button?
		}
		if (noteinfo.lasterror !== '') {
			if (msg != '') {
				msg += ' | '
			}
			msg += noteinfo.lasterror;
		}
		var statusline = document.getElementById("statusline_"+id);
		if (statusline.firstChild)
			statusline.replaceChild(document.createTextNode(msg), statusline.firstChild);
		else
			statusline.appendChild(document.createTextNode(msg));
		if (globalstatus) {
			updateStatusLine();
		}
	}
	var internalids = { };
	function parseResponseText(responseText) {
		if (!/^(-?[0-9]+:-?[1-9][0-9]*(:[^#]*)?#)*(-?[0-9]+:-?[1-9][0-9]*(:[^#]*)?)$/.test(responseText)) {
			return [];
		}
		var result = [];
		var responses = responseText.split('#');
		for (var i = 0; i < responses.length; ++i) {
			var parts = responses[i].split(':');
			rcode = parseInt(parts[0]);
			parts[0] = rcode;
			var id = parts[1];
			if (id in internalids) {
				id = internalids[id];
			}
			if (!(id in gn.notes)) {
				return [];
			}
			var noteinfo = gn.notes[id];
			parts[1] = noteinfo;
			if (rcode < 0) {
				if (parts.length < 3) {
					parts[2] = '';
				}
			} else if (noteinfo.servernoteid < 0) {
				if (rcode > 1 || parts.length < 3 || !/^[1-9][0-9]*$/.test(parts[2])) {
					return [];
				}
			} else if (rcode > 2) {
				return [];
			}
			result[result.length] = parts;
		}
		return result;
	}
	function handleResponse(parts) {
		var rcode = parts[0];
		var noteinfo = parts[1];

		if (rcode < 0) {
			noteinfo.lasterror = "Error: Server returned error " + -rcode + " (" + parts[2] + ")";
		} else {
			noteinfo.lasterror = /*rcode == 2 ? "There was nothing to change." :*/ "";
			if (noteinfo.servernoteid < 0) {
				noteinfo.servernoteid = parts[2];
				internalids[noteinfo.servernoteid] = noteinfo.noteid;
			}
			noteinfo.pendingchanges = rcode == 1;
			noteinfo.unsavedchanges = false;
		}
		statusChanged(noteinfo.noteid, false);
		return rcode < 0;
	}
	function commitUnsavedNotes(ids)
	{
		var postdata = 'imageid=' + imageid;
		var numnotes = 0;
		var allids = gn.images[0].notes;

		for (var i = 0; i < ids.length; ++i) {
			var id = ids[i];
			var noteinfo = gn.notes[id];
			if (!noteinfo.unsavedchanges) {
				continue;
			}
			++numnotes;
			var suffix = numnotes != 1 ? '_' + numnotes : '';
			var elz = document.getElementById("note_z_"+id);
			var valz = parseInt(elz.options[elz.selectedIndex].value);
			var eltxt = document.getElementById("note_comment_"+id);
			var valtxt = eltxt.value;

			postdata += '&id' + suffix + '=' + encodeURIComponent(noteinfo.servernoteid);
			postdata += '&x1' + suffix + '=' + encodeURIComponent(noteinfo.x1);
			postdata += '&y1' + suffix + '=' + encodeURIComponent(noteinfo.y1);
			postdata += '&x2' + suffix + '=' + encodeURIComponent(noteinfo.x2);
			postdata += '&y2' + suffix + '=' + encodeURIComponent(noteinfo.y2);
			postdata += '&imgwidth' + suffix + '=' + encodeURIComponent(noteinfo.width);
			postdata += '&imgheight' + suffix + '=' + encodeURIComponent(noteinfo.height);
			postdata += '&z' + suffix + '=' + encodeURIComponent(valz);
			postdata += '&comment' + suffix + '=' + encodeURIComponent(valtxt);
			postdata += '&status' + suffix + '=' + noteinfo.status;
		}

		if (!numnotes) {
			return;
		}
		postdata += '&commit=' + numnotes;
		var elticketnote = document.getElementById("ticketnote");
		postdata += '&ticketnote=' + encodeURIComponent(elticketnote.value);
{/literal}
{if $ismoderator && !$isowner}
		var elimmediate = document.getElementById("immediate");
		postdata += '&immediate=' + (elimmediate.checked ? '1' : '0');
{else}
		postdata += '&immediate=0';
{/if}
{literal}
		//alert(postdata);// FIXME remove

		for (var i = 0; i < allids.length; ++i) {
			var id = allids[i];
			var noteinfo = gn.notes[id];
			var elcommit = document.getElementById("note_commit_"+id);
			elcommit.disabled = true;
			/* FIXME disable every control? */
		}
		var elcommit = document.getElementById("commit_all");
		elcommit.disabled = true;

		var url="/geonotes.php";
		var req=getXMLRequestObject();
		var reqTimer = setTimeout(function() {
		       req.abort();
		}, 30000);
		req.onreadystatechange = function() {
			if (req.readyState != 4) {
				return;
			}
			clearTimeout(reqTimer);
			req.onreadystatechange = function() {};
			commiterrors = true;

			if (req.status != 200) {
				alert("Cannot communicate with server, status " + req.status);
			} else {
				var responseText = req.responseText;
				//alert(responseText);// FIXME remove
				if (/^-[1-9][0-9]*:0:[^#]*$/.test(responseText)) { /* general error */
					var parts = responseText.split(':');
					var rcode = parseInt(parts[0]);
					alert("Error: Server returned error " + -rcode + " (" + parts[2] + ")");
				} else {
					var responses = parseResponseText(responseText);
					if (responses.length == 0) {
						alert("Unexpected response from server");
					} else {
						commiterrors = false;
						for (var i = 0; i < responses.length; ++i) {
							commiterrors |= handleResponse(responses[i]);
						}
						if (commiterrors) {
							alert("The server reported errors when saving the changes.");
						}
					}
				}
			}
			unsavedchanges = false;
			var elcommit = document.getElementById("commit_all");
			elcommit.disabled = false;
			for (var i = 0; i < allids.length; ++i) {
				var id = allids[i];
				var noteinfo = gn.notes[id];
				unsavedchanges |= noteinfo.unsavedchanges;
				var elcommit = document.getElementById("note_commit_"+id);
				elcommit.disabled = false;
				/* FIXME enable controls after disabling them at the beginning of commitUnsavedNotes */
			}
			updateStatusLine();
		}
		req.open("POST", url, true);
		req.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		//req.setRequestHeader("Connection", "close");
		req.send(postdata);
	}
	function updateNoteZ(id) {
		var noteinfo = gn.notes[id];
		var el = document.getElementById("note_z_"+id)
		var val = parseInt(el.options[el.selectedIndex].value);
		noteinfo.unsavedchanges = true;
		var box = noteinfo.box;
		box.style.zIndex = val + 50;
		statusChanged(id, true);
	}
	function updateNoteStatus(id) {
		var noteinfo = gn.notes[id];
		var el = document.getElementById("note_status_"+id);
		noteinfo.status = el.options[el.selectedIndex].value;
		noteinfo.unsavedchanges = true;
		statusChanged(id, true);
	}
	function handleLinks(node, str) {
		// TODO we probably should introduce something like [[:url:href|text]] and [[:url:href]] which would become <a href="href">text</a> or <a href="href">Link</a>
		//      would make parsing easier, no assumptions about probable urls needed... could easily introduce [[:whatever:...]] using the same code...

		/*
		  See GeographLinks() in functions.php:
		  Find
		       /(?<!["\'>F=])(https?:\/\/[\w\.-]+\.\w{2,}\/?[\w\~\-\.\?\,=\'\/\\\+&%\$#\(\)\;\:]*)(?<!\.)(?!["\'])/
		       /(?<![\/F\.])(www\.[\w\.-]+\.\w{2,}\/?[\w\~\-\.\?\,=\'\/\\\+&%\$#\(\)\;\:]*)(?<!\.)(?!["\'])/
		       ( prepend http:// in second case )
		  and create
		       <span class="nowrap"><a title="URL" rel="nofollow" href="URL" target="_blank">Link</a><img class="externallink" alt="External link" title="External link - opens in a new window" src="/img/external.png" width="10" height="10"/></span>

		*/
		node.appendChild(document.createTextNode(str)); //TODO
	}
	function makeText(node, line) {
		var re = /(.*?)(\[\[[A-Za-z]{0,3}[0-9]+\]\])|(.+)/g;
		var match;
		while (match = re.exec(line)) {
			if (typeof(match[2]) !== "undefined"
			    && match[2] !== '' /* FIXME should work around IE bug, but can't test it */ ) {
				if (match[1].length) {
					handleLinks(node, match[1]);
				}
				var link = document.createElement('a');
				var linkdest = match[2].substr(2, match[2].length - 4);
				link.href =  (/\d/.test(linkdest.charAt(0)) ? '/photo/' : '/gridref/') + linkdest;
				link.appendChild(document.createTextNode(match[2]));
				node.appendChild(link);
			} else {
				handleLinks(node, match[3]);
			}
		}
	}
	function updateNoteComment(id) {
		var noteinfo = gn.notes[id];
		var txt = noteinfo.note;
		var el = document.getElementById("note_comment_"+id)
		var val = el.value
		noteinfo.unsavedchanges = true;
		var p = document.createElement('p');
		var nlval = val.replace('\r\n', '\n');
		var lines = nlval.split('\n');
		for (var i = 0; i < lines.length-1; i++) {
			makeText(p, lines[i]);
			p.appendChild(document.createElement('br'));
		}
		makeText(p, lines[lines.length-1]);

		txt.replaceChild(p, txt.firstChild);

		/* reset box size */
		gn.initBoxWidth(txt);
		statusChanged(id, true);
	}
	var newnotes = 0;
	function addNote() {
		++newnotes;
		var noteid = -newnotes;
		var img = document.getElementById('gridimage');
		var imageindex = gn.findImage(img);
		if (imageindex < 0)
			return;
			gn.images[imageindex]
		var imageinfo = gn.images[imageindex];

		var dw = img.parentNode.parentNode.clientWidth;
		var dh = img.parentNode.parentNode.clientHeight;
		var sx = img.parentNode.parentNode.scrollLeft;
		var sy = img.parentNode.parentNode.scrollTop;
		var ix = imageinfo.paddborderoffsetx;
		var iy = imageinfo.paddborderoffsety;
		var sxi = sx - ix;
		var syi = sy - iy;

		var width = Math.ceil(minboxsize*imgwidth/stdwidth);
		var height = Math.ceil(minboxsize*imgheight/stdheight);

		var minx = Math.max(sxi, 0);
		var miny = Math.max(syi, 0);
		var maxx = Math.min(sxi + dw - 1, img.width - 1);
		var maxy = Math.min(syi + dh - 1, img.height - 1);

		var x1 = Math.floor((minx + maxx - width) / 2);
		var y1 = Math.floor((miny + maxy - height) / 2);
		var x2 = x1 + width - 1;
		var y2 = y1 + height - 1;

		var area = document.createElement('area');
		area.id = "notearea" + noteid;
		area.shape = 'rect';
		area.noHref = true;

		var box = document.createElement('div');
		box.id = "notebox" + noteid;
		box.className = 'notebox';
		box.style.zIndex = 0 + 50;
		box.appendChild(document.createElement('span'));

		var txt = document.createElement('div');
		txt.id = "notetext" + noteid;
		txt.className = 'geonote';
		txt.appendChild(document.createElement('p'));

		var ele;

		ele = document.createElement('hr');
		txt.appendChild(ele);
		ele = document.createElement('input');
		ele.id = 'note_t_edit_' + noteid;
		ele.type = 'button';
		ele.value = 'edit';
		AttachEvent(ele, "click", function() { return editNote(noteid); } );
		txt.appendChild(ele);
		ele = document.createElement('input');
		ele.id = 'note_t_delete_' + noteid;
		ele.type = 'button';
		ele.value = 'delete';
		AttachEvent(ele, "click", function() { return deleteNote(noteid); } );
		txt.appendChild(ele);

		var nmap = document.getElementById('notesmap');
		var pdiv = document.getElementById('notecontainer');

		gn.addNote(area, box, txt, nmap, pdiv, pdiv, x1, y1, x2, y2, img, 'visible', true, noteid);

		var head = document.createElement('p');
		ele = document.createElement('b');
		ele.appendChild(document.createTextNode('New annotation #'+-noteid));
		head.appendChild(ele);
		ele = document.createElement('span');
		ele.id = 'statusline_' + noteid;
		ele.style.paddingLeft = '2em';
		head.appendChild(ele);

		var formp = document.createElement('p');

		ele = document.createElement('label');
		ele.for = 'note_z_' + noteid;
		ele.appendChild(document.createTextNode('z:'));
		formp.appendChild(ele);
		ele = document.createElement('select');
		ele.name = 'note_z_' + noteid;
		ele.id = 'note_z_' + noteid;
		AttachEvent(ele,"change",function(){updateNoteStatus(noteid);});
		for (var i=-10; i<=10; ++i) {
			ele.options[i+10] = new Option(i, i, false, i==0);
		}
		formp.appendChild(ele);
		formp.appendChild(document.createTextNode(' | '));

		ele = document.createElement('label');
		ele.for = 'note_status_' + noteid;
		ele.appendChild(document.createTextNode('status:'));
		formp.appendChild(ele);
		ele = document.createElement('select');
		ele.name = 'note_status_' + noteid;
		ele.id = 'note_status_' + noteid;
		AttachEvent(ele,"change",function(){updateNoteStatus(noteid);});
		//ele.options[0] = new Option("awaiting moderation", "pending", false, false);
		ele.options[0] = new Option("visible", "visible", false, true);
		ele.options[1] = new Option("deleted", "deleted", false, false);
		formp.appendChild(ele);
		formp.appendChild(document.createTextNode(' | '));

		ele = document.createElement('input');
		ele.id = 'note_edit_' + noteid;
		ele.type = 'button';
		ele.value = 'edit box';
		AttachEvent(ele,"click",function(){toggleEdit(noteid);});
		formp.appendChild(ele);
		formp.appendChild(document.createElement('br'));

		ele = document.createElement('textarea');
		ele.rows = 10;
		ele.cols = 50;
		ele.id = 'note_comment_' + noteid;
		AttachEvent(ele,"change",function(){updateNoteComment(noteid);});
		formp.appendChild(ele);
		formp.appendChild(document.createElement('br'));

		ele = document.createElement('input');
		ele.id = 'note_commit_' + noteid;
		ele.type = 'button';
		ele.value = 'save';
		AttachEvent(ele,"click",function(){commitUnsavedNotes([noteid]);});
		formp.appendChild(ele);

		var form = document.createElement('form');
		form.id = 'note_form_' + noteid;
		form.appendChild(formp);

		var forms = document.getElementById('noteforms');
		var addbutton = document.getElementById('addbutton');
		forms.appendChild(head);
		forms.appendChild(form);

		statusChanged(noteid, true);

		if (form.scrollIntoView) {
			form.scrollIntoView(true);
		}
		startEdit(noteid);
	}
	function cancelEvent(e) {
		if (window.event) {
			e = window.event;
		}
		/*if (e.stopPropagation) {
			e.stopPropagation();
		} else {
			e.cancelBubble = true;
		}*/
		if (e.preventDefault) {
			e.preventDefault();
		} else {
			e.returnValue = false;
		}
	}
	function deleteNote(noteid) {
		form = document.getElementById('note_form_' + noteid);
		if (form.scrollIntoView) {
			form.scrollIntoView(true);
		}
		gn.hideNoteText();

		var noteinfo = gn.notes[noteid];
		var el = document.getElementById("note_status_"+noteid);
		for (var i = 0; i < el.options.length; ++i) {
			if (el.options[i].value == 'deleted') {
				el.selectedIndex = i;
				break;
			}
		}
		noteinfo.status = el.options[el.selectedIndex].value;
		noteinfo.unsavedchanges = true;
		statusChanged(noteid, true);

		return false;
	}
	function editNote(noteid) {
		form = document.getElementById('note_form_' + noteid);
		if (form.scrollIntoView) {
			form.scrollIntoView(true);
		}
		gn.hideNoteText();
		startEdit(noteid);

		return false;
	}
	function startDrag(e, ix, iy) {
		var mpos = gn.getMousePosition(e); // TODO compare with mapping1.js
		var noteinfo = gn.notes[curedit];
		var img = noteinfo.img;
		var width = img.width;
		var height = img.height;
		dragx1 = Math.floor(noteinfo.x1 * width / noteinfo.width);
		dragx2 = Math.floor(noteinfo.x2 * width / noteinfo.width);
		dragy1 = Math.floor(noteinfo.y1 * height / noteinfo.height);
		dragy2 = Math.floor(noteinfo.y2 * height / noteinfo.height);
		dragmx = mpos[0];
		dragmy = mpos[1];
		dragging = ix+3*iy;
		var dragminx = 0;
		var dragmaxx = imgwidth - 1;
		var dragminy = 0;
		var dragmaxy = imgheight - 1;
		var dragminwidth = Math.ceil(minboxsize*imgwidth/stdwidth);
		var dragminheight = Math.ceil(minboxsize*imgheight/stdheight);
		var dx = dragx2 - dragx1 + 1 - dragminwidth;
		if (dx < 0) {
			dragx2 -= dx;
			if (dragx2 >= width) {
				dragx1 = width - dragminwidth;
				dragx2 = width - 1;
			}
		}
		var dy = dragy2 - dragy1 + 1 - dragminheight;
		if (dy < 0) {
			dragy2 -= dy;
			if (dragy2 >= height) {
				dragy1 = height - dragminheight;
				dragy2 = height - 1;
			}
		}

		dragdx2 = dragmaxx - dragx2;
		dragdx1 = dragminx - dragx1;
		dragdy2 = dragmaxy - dragy2;
		dragdy1 = dragminy - dragy1;
		dragdxs = (dragx2 - dragx1 + 1) - dragminwidth;
		dragdys = (dragy2 - dragy1 + 1) - dragminheight;

		cancelEvent(e);
		return false;
	}
	function stopDrag() {
		if (dragging == -1) {
			return;
		}
		gn.recalcBox(curedit); // FIXME do that only in stopEdit()?
		statusChanged(curedit, true);
		dragging = -1;
	}
	function dragBox(e) {
		if (dragging == -1) {
			return;
		}
		var ix = dragging % 3;
		var iy = (dragging - ix) / 3;
		var mpos = gn.getMousePosition(e); // TODO compare with mapping1.js

		var dx = mpos[0] - dragmx;
		var dy = mpos[1] - dragmy;

		var newx1 = dragx1;
		var newx2 = dragx2;
		var newy1 = dragy1;
		var newy2 = dragy2;
		if (ix == 1 && iy == 1) {
			dx = Math.max(dx, dragdx1);
			dx = Math.min(dx, dragdx2);
			dy = Math.max(dy, dragdy1);
			dy = Math.min(dy, dragdy2);
			newx1 += dx;
			newx2 += dx;
			newy1 += dy;
			newy2 += dy;
		} else {
			if (ix == 0) {
				dx = Math.max(dx, dragdx1);
				dx = Math.min(dx, +dragdxs);
				newx1 += dx;
			} else if (ix == 2) {
				dx = Math.max(dx, -dragdxs);
				dx = Math.min(dx, dragdx2);
				newx2 += dx;
			}
			if (iy == 0) {
				dy = Math.max(dy, dragdy1);
				dy = Math.min(dy, +dragdys);
				newy1 += dy;
			} else if (iy == 2) {
				dy = Math.max(dy, -dragdys);
				dy = Math.min(dy, dragdy2);
				newy2 += dy;
			}
		}
		var noteinfo = gn.notes[curedit];
		var edbox = document.getElementById("noteboxedit");
		drawEdit(curedit, edbox, newx1, newx2, newy1, newy2)
		noteinfo.x1 = newx1;
		noteinfo.x2 = newx2;
		noteinfo.y1 = newy1;
		noteinfo.y2 = newy2;
		noteinfo.width = imgwidth;
		noteinfo.height = imgheight;
		noteinfo.unsavedchanges = true;
		cancelEvent(e);
		return false;
	}
	function initNoteEdit() {
		if (typeof gn === 'undefined') {
			return;
		}
		gn.show_hidden = show_hidden;
		if (!gn.images.length) {
			gn.init();
			if (!gn.images.length) {
				return;
			}
		}
		// TODO compatibility checks for features not tested by gn.init() go here

{/literal}
{if $ticket}
{literal}
		var allids = gn.images[0].notes;
		for (var i = 0; i < allids.length; ++i) {
			var id = allids[i];
			var noteinfo = gn.notes[id];
			var nl = notelists[noteinfo.noteclass];
			nl[nl.length] = noteinfo;
		}
{/literal}
{/if}
{literal}
		updateStatusLine(); /* removes "JavaScript required" */

		var img = document.getElementById('gridimage');
		var edbox = document.getElementById('noteboxedit');
		var editbuttons0 = document.getElementById('noteboxedit00');

		var innersize = gn.getStyleXY(editbuttons0, 'width', 'height');
		var borderlt  = gn.getStyleXY(editbuttons0, 'border-left-width', 'border-top-width');
		var borderrb  = gn.getStyleXY(editbuttons0, 'border-right-width', 'border-bottom-width');
		bbwidth = borderlt[0] + borderrb[0] + innersize[0];
		bbheight = borderlt[1] + borderrb[1] + innersize[1];

		borderlt  = gn.getStyleXY(edbox, 'border-left-width', 'border-top-width');
		borderrb  = gn.getStyleXY(edbox, 'border-right-width', 'border-bottom-width');
		edboxborderx = borderlt[0];
		edboxbordery = borderlt[1];
		edboxbordersx = edboxborderx + borderrb[0];
		edboxbordersy = edboxbordery + borderrb[1];
		//alert([bbwidth, bbheight, edboxborderx, edboxbordery, edboxbordersx, edboxbordersy].join(', '));

		for (var j = 0; j <= 2; ++j) {
			for (var i = 0; i <= 2; ++i) {
				var ebbut = document.getElementById("noteboxedit"+i+j);
				var ix = i;
				var iy = j;
				editbuttons[j*3+i] = ebbut;
				AttachEvent(ebbut,"mousedown",function(ix,iy){return function(ev){return startDrag(ev, ix, iy);}}(i,j));
			}
		}
		AttachEvent(document, "mousemove", dragBox);
		AttachEvent(document, "mouseup", stopDrag);
	}
	AttachEvent(window,"load",initNoteEdit);
{/literal}
/* ]]> */
</script>
<div>
	<form action="javascript:void(0);">
	<p>
{if $showorig}
		<label for="imgsize">Image size:</label>
		<select name="imgsize" id="imgsize" onchange="setImgSize(this.options[this.selectedIndex].value=='original');">
			<option value="default" selected="selected">{$std_width}x{$std_height} (default)</option>
			<option value="original">{$original_width}x{$original_height}</option>
		</select> |
{/if}
		<input id="toggletexts" type="button" value="Show description" onclick="toggleTexts('Hide description','Show description');" /> |
		{if $ticket}
		<input id="toggleclassold" type="button" value="Hide old" onclick="toggleBoxes('old', 'Hide old', 'Show old');" /> |
		<input id="toggleclassnew" type="button" value="Hide new" onclick="toggleBoxes('new', 'Hide new', 'Show new');" /> |
		<input id="toggleclass"    type="button" value="Hide unaffected notes" onclick="toggleBoxes('', 'Hide unaffected notes', 'Show unaffected notes');" /> |
		{else}
		<input type="button" value="Add annotation" onclick="addNote();" /> |
		<label for="ticketnote">Optional ticket description:</label>
		<textarea id="ticketnote" name="ticketnote" cols="20" rows="1"></textarea> |
{if $ismoderator && !$isowner}
		<label for="immediate">Apply immediately:</label>
		<input type="checkbox" id="immediate" name="immediate" /> |
{/if}
		<input type="button" value="Save all" id="commit_all" onclick="commitUnsavedNotes(gn.images[0].notes);" /> |
		{/if}
		<a href="/photo/{$image->gridimage_id}" target="_blank">Open photo page in new window.</a>
		<span id="statusline" style="padding-left:2em">JavaScript required</span>
	</p>
	</form>
</div>
<div id="noteforms" class="noteforms">
{if $ticket}
	<table>
	<tr><th class="oldnote">Old</th><th class="newnote">New</th></tr>
	{foreach item=oldnote from=$oldnotes name=oldloop}
	{assign var=noteidx value=$smarty.foreach.oldloop.index}
	{assign var=newnote value=$newnotes.$noteidx}
	<tr><td colspan="2"><b>Annotation #{$oldnote->note_id}</b><span id="statusline_{$oldnote->note_id}" style="padding-left:2em"></span><span id="statusline_{$newnote->note_id}" style="padding-left:2em"></span></td></tr>
	<tr><td>
		<form action="javascript:void(0);" id="note_form_{$oldnote->note_id}" class="noteformold">
		<label for="note_z_{$oldnote->note_id}">z:</label>
		<input type="text" name="note_z_{$oldnote->note_id}" id="note_z_{$oldnote->note_id}" value="{$oldnote->z}" readonly="readonly" {if $oldnote->z != $newnote->z}class="oldnote"{/if} /> |
		<label for="note_status_{$oldnote->note_id}">status:</label>
		<input type="text" name="note_status_{$oldnote->note_id}" id="note_status_{$oldnote->note_id}" value="{if $oldnote->status=='pending'}awaiting moderation{else}{$oldnote->status}{/if}" readonly="readonly" {if $oldnote->status != $newnote->status}class="oldnote"{/if} /><br />
		<textarea name="note_comment_{$oldnote->note_id}" id="note_comment_{$oldnote->note_id}" cols="50" rows="10" readonly="readonly" {if $oldnote->comment != $newnote->comment}class="oldnote"{/if} >{$oldnote->comment|escape:'html'}</textarea>
		</form>
	</td><td>
		<form action="javascript:void(0);" id="note_form_{$newnote->note_id}" class="noteformnew">
		<label for="note_z_{$newnote->note_id}">z:</label>
		<input type="text" name="note_z_{$newnote->note_id}" id="note_z_{$newnote->note_id}" value="{$newnote->z}" readonly="readonly" {if $oldnote->z != $newnote->z}class="newnote"{/if} /> |
		<label for="note_status_{$newnote->note_id}">status:</label>
		<input type="text" name="note_status_{$newnote->note_id}" id="note_status_{$newnote->note_id}" value="{if $newnote->status=='pending'}awaiting moderation{else}{$newnote->status}{/if}" readonly="readonly" {if $newnote->status != $oldnote->status}class="newnote"{/if} /><br />
		<textarea name="note_comment_{$newnote->note_id}" id="note_comment_{$newnote->note_id}" cols="50" rows="10" readonly="readonly" {if $oldnote->comment != $newnote->comment}class="newnote"{/if} >{$newnote->comment|escape:'html'}</textarea>
		</form>
	</td></tr>
	{/foreach}
	</table>
{else}
    {foreach item=note from=$notes}
	<p><b>Annotation #{$note->note_id}</b><span id="statusline_{$note->note_id}" style="padding-left:2em">{if $note->pendingchanges}There are unmoderated changes.{/if}</span></p>
	<form action="javascript:void(0);" id="note_form_{$note->note_id}" class="{if $note->pendingchanges}noteformpending{else}noteform{/if}">
	<p>
		<label for="note_z_{$note->note_id}">z:</label>
		<select name="note_z_{$note->note_id}" id="note_z_{$note->note_id}" onchange="updateNoteZ({$note->note_id});">
		{section name=zloop start=0 loop=21}{* no negative values... *}
			<option value="{$smarty.section.zloop.index-10}"{if $smarty.section.zloop.index-10==$note->z} selected="selected"{/if}>{$smarty.section.zloop.index-10}</option>
		{/section}
		</select> |
		<label for="note_status_{$note->note_id}">status:</label>
		<select name="note_status_{$note->note_id}" id="note_status_{$note->note_id}" onchange="updateNoteStatus({$note->note_id});">
			<option value="pending"{if $note->status=='pending'} selected="selected"{/if}>awaiting moderation</option>
			<option value="visible"{if $note->status=='visible'} selected="selected"{/if}>visible</option>
			<option value="deleted"{if $note->status=='deleted'} selected="selected"{/if}>deleted</option>
		</select> |
		<input type="button" value="edit box" id="note_edit_{$note->note_id}" onclick="toggleEdit({$note->note_id});" /><br />
		<textarea name="note_comment_{$note->note_id}" id="note_comment_{$note->note_id}" cols="50" rows="10" onchange="updateNoteComment({$note->note_id});">{$note->comment|escape:'html'}</textarea><br />
		<input type="button" value="save" id="note_commit_{$note->note_id}" onclick="commitUnsavedNotes([{$note->note_id}]);" />
	</p>
	</form>
    {/foreach}
{/if}
</div>
{else}
<h2>Sorry, image not available</h2>
<p>The image you requested is not available. This maybe due to software error, or possibly because
the image was rejected after submission - please <a title="Contact Us" href="/contact.php">contact us</a>
if you have queries</p>
{/if}

{include file="_std_end.tpl"}
