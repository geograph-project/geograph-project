{assign var="page_title" value="Markierte Bilder"}
{assign var="extra_css" value="/templates/germanyde/css/marked.css"}
{include file="_std_begin.tpl"}
{dynamic}
<h2>Markierte Bilder</h2>
<div id="marked" class="marked"><!--
{foreach from=$images item=image key=idx}
	--><div id="drag{$idx}" class="dragitem markeditem" style="width:213px;height:160px" data-imageid="{$image->gridimage_id}"><img src="{$image->getThumbnail(213,160,3)}" alt="" style="padding-left:{$image->last_width/-2+213/2|floor}px;padding-top:{$image->last_height/-2+160/2|floor}px;width:{$image->last_width}px;height:{$image->last_height}px"  title="{$image->title|escape:'html'}"/></div><!--
{/foreach}
	--><div id="gap" class="dragitem markedgap" style="width:213px;height:160px;"></div><!--
	--><div id="dummy" class="dragitem markeddummy" style="width:213px;height:160px;"></div><!--
--></div>
<div><p><input type="button" value="Speichern" onclick="storeMarked();" /> |
{literal}
     <input type="button" value="Speichern & Suche" onclick="if (storeMarked()) {window.open('/search.php?marked=1&amp;displayclass=-','_blank');}" /></p></div>
{/literal}
<!-- FIXME better use floats instead of display:inline-block to make IE7 happy? -->
<h2>Ablage</h2>
<div id="clipboard" class="clipboard"><!--
	--><div id="gapclip" class="dragitem clipboardgap" style="width:213px;height:160px;"></div><!--
	--><div id="dummyclip" class="dragitem clipboarddummy" style="width:213px;height:160px;"></div><!--
--></div>
<script type="text/javascript" src="{"/js/geonotes.js"|revision}"></script>
<script type="text/javascript">
/* <![CDATA[ */
var imagecount = {$imagecount};
{literal}
var dragging = -1;
var dragmpos;
var dragele;
var dragobj;
var dragclip;
var dragelepos;
var draggingstarted = 0;
var imagelist = [];
var threshold = 5;
var eleheight = 160+14;
var elewidth = 213+14;
var gapobj, gapclipobj;
var gapele, gapclipele;
var dummyele, dummyclipele;
var divmarkedele, divclipele;

	function cancelEvent(e) { /* FIXME also used in geonotes.tpl, move to geonotes.js/gn or other common js file */
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
	function storeMarked() {
		var idlist = [];
		for (var ele = gapobj.next; ele != gapobj; ele = ele.next) {
			idlist.push(ele.imageid);
		}
		createCookie('markedImages',idlist.join(),10); // FIXME -> geograph.js; markList(idlist)?
		return idlist.length;
	}
	function initDrag(e, dragdata) {
		dragmpos = gn.getMousePosition(e);
		dragging = dragdata.idx;
		dragobj = dragdata;
		dragele = dragdata.ele;
		dragclip = dragobj.clip;
		dragelepos = gn.getElePosition(dragele);
		draggingstarted = 0;
		cancelEvent(e);
		return false;
	}
	function stopDrag(e) {
		if (dragging == -1) {
			return;
		}
		if (draggingstarted) {
			draggingstarted = 0;
			dragele.style.zIndex = 0;
			dragele.style.left = 0;
			dragele.style.top = 0;
			dummyele.style.display = 'none';
			dummyclipele.style.display = 'none';
			gapclipele.style.display = '';
			gapele.style.display = '';
			gapele.className = 'dragitem markedgap';
			gapclipele.className = 'dragitem clipboardgap';
		}
		dragging = -1;
		cancelEvent(e);
		return false;
	}
	function dragDiv(e) {
		if (dragging == -1) {
			return;
		}
		var mpos = gn.getMousePosition(e);
		var dx = mpos[0] - dragmpos[0];
		var dy = mpos[1] - dragmpos[1];
		if (!draggingstarted) {
			if (dx*dx + dy*dy < threshold*threshold) {
				cancelEvent(e);
				return false;
			}
			draggingstarted = 1;
			dragele.style.zIndex = 1;
			if (dragobj.clip) {
				gapele.className = 'dragitem markedgapactive';
			} else {
				gapclipele.className = 'dragitem clipboardgapactive';
			}
		}
		if (Math.abs(dx) > elewidth/2 || Math.abs(dy) > eleheight/2) {  /* did move out of dragele's bounds */
			for (var i = 0; i < imagelist.length; ++i) {
				var insertobj = imagelist[i];
				if (insertobj.idx == dragobj.idx) {
					continue;
				}
				if (insertobj.idx < 0 && dragobj.clip == insertobj.clip) { /* can't insert before current gap div */
					continue;
				}
				var ep = gn.getElePosition(insertobj.ele); // FIXME store position?
				var dx2 = ep[0] - dragelepos[0];
				var dy2 = ep[1] - dragelepos[1];
				var dx3 = dx - dx2;
				var dy3 = dy - dy2;
				if (Math.abs(dx3) > elewidth/2 || Math.abs(dy3) > eleheight/2) { /* did not move into that element's bounds */
					continue;
				}

				/* move elements */
				if (dragobj.clip) {
					divclipele.removeChild(dragele);
				} else {
					divmarkedele.removeChild(dragele);
				}
				var forward = dragobj.clip == insertobj.clip && (dy2 > 0 || dy2 == 0 && dx2 > 0);
				if (forward && insertobj.idx >= 0) {
					insertobj = insertobj.next;
					if (insertobj == dragobj) {
						insertobj = insertobj.next;
					}
				}
				if (insertobj.clip) {
					divclipele.insertBefore(dragele, insertobj.ele);
				} else {
					divmarkedele.insertBefore(dragele, insertobj.ele);
				}

				/* use dummy and gap elements to keep divs' sizes constant */
				if (dragobj.clip != insertobj.clip) {
					if (dragclip) {
						if (dragobj.clip) {
							gapele.style.display = 'none';
							dummyclipele.style.display = '';
						} else {
							gapele.style.display = '';
							dummyclipele.style.display = 'none';
						}
					} else {
						if (dragobj.clip) {
							gapclipele.style.display = '';
							dummyele.style.display = 'none';
						} else {
							gapclipele.style.display = 'none';
							dummyele.style.display = '';
						}
					}
				}

				/* reflect changes in our data structures */
				/* remove element from list */
				dragobj.next.prev = dragobj.prev;
				dragobj.prev.next = dragobj.next;
				/* insert element into list */
				dragobj.next = insertobj;
				dragobj.prev = insertobj.prev;
				dragobj.prev.next = dragobj;
				insertobj.prev = dragobj;
				/* new element positions */
				dragelepos[0] = ep[0];
				dragelepos[1] = ep[1];
				dragmpos[0] += dx2;
				dragmpos[1] += dy2;
				dx = dx3;
				dy = dy3;
				/* new gap colours? */
				if (insertobj.clip != dragobj.clip) {
					dragobj.clip = insertobj.clip;
					if (dragobj.clip) {
						gapele.className = 'dragitem markedgapactive';
						gapclipele.className = 'dragitem clipboardgap';
						dragele.className = 'dragitem clipboarditem';
					} else {
						gapele.className = 'dragitem markedgap';
						gapclipele.className = 'dragitem clipboardgapactive';
						dragele.className = 'dragitem markeditem';
					}
				}
				break;
			}
		}
		dragele.style.left = dx+'px';
		dragele.style.top = dy+'px';
		cancelEvent(e);
		return false;
	}
	function initMarkedList() {
		divmarkedele = document.getElementById('marked');
		divclipele = document.getElementById('clipboard');
		gapele = document.getElementById('gap');
		gapclipele = document.getElementById('gapclip');
		dummyele = document.getElementById('dummy');
		dummyclipele = document.getElementById('dummyclip');
		dummyele.style.display = 'none';
		dummyclipele.style.display = 'none';
		AttachEvent(document, "mousemove", dragDiv);
		AttachEvent(document, "mouseup", stopDrag);
		gapclipobj = { 'ele' : gapclipele, 'idx' : -2, 'clip' : 1 }
		gapclipobj.next = gapclipobj;
		gapclipobj.prev = gapclipobj;
		imagelist.push(gapclipobj);
		gapobj = { 'ele' : gapele, 'idx' : -1, 'clip' : 0 }
		gapobj.next = gapobj;
		gapobj.prev = gapobj;
		imagelist.push(gapobj);
		var prev = gapobj;
		for (var idx = 0; idx < imagecount; ++idx) {
			var ele = document.getElementById('drag'+idx);
			var eledata = { 'ele' : ele, 'idx' : idx, 'prev' : prev, 'clip' : 0, 'imageid' : ele.getAttribute('data-imageid') }
			prev.next = eledata;
			prev = eledata;
			imagelist.push(eledata);
			AttachEvent(ele,'mousedown',function(dragdata){return function(ev){return initDrag(ev, dragdata);}}(eledata));
		}
		prev.next = gapobj;
		gapobj.prev = prev;
	}
	AttachEvent(window,"load",initMarkedList);
{/literal}
/* ]]> */
</script>

{/dynamic}
{include file="_std_end.tpl"}
