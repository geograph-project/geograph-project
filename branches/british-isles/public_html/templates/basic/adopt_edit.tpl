{assign var="page_title" value="Hectad Adoptions"}
{include file="_std_begin.tpl"}
{dynamic}

{literal}<style type="text/css">
        .black_overlay{
            display: none;
            position: absolute;
            top: 0%;
            left: 0%;
            width: 100%;
            height: 100%;
            background-color: black;
            z-index:1001;
            -moz-opacity: 0.8;
            opacity:.80;
            filter: alpha(opacity=80);
        }
        .white_content {
            display: none;
            position: absolute;
            top: 20%;
            left: 20%;
            width: 60%;
            height: 60%;
            padding: 6px;
            border: 6px solid orange;
            background-color: white;
            z-index:1002;
            overflow: auto;
        }
</style>
<script type="text/javascript">
function getXMLRequestObject()
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

var gsid;
var gx;var gy;
function showbox(gid,x,y) {
	document.getElementById('light').style.display='block';
	document.getElementById('fade').style.display='block';
	if (navigator.userAgent.indexOf("MSIE 6.0") == -1) {
		document.getElementById('light').style.position = 'fixed';
	}
	gsid = gid;
	gx = x;
	gy = y;
	{/literal}
	document.getElementById('iframe').src = "{$script_name}?gsid="+gsid;
	{literal}
	return false;
}
function useimage(id) {
	{/literal}
	var url="{$script_name}?hectad={$hectad}&gsid="+gsid+"&gid="+id+"&gx="+gx+"&gy="+gy;
	{literal}
	//make the request
	var req=getXMLRequestObject();

	//need to exploit function closure
	req.onreadystatechange = function()
	{
		if (req.readyState==4) 
		{
			var divInfo=document.getElementById('cell_'+gy+'_'+gx);
			divInfo.innerHTML=req.responseText;

			//patch the memory leak
			req.onreadystatechange = function() {};
			
			document.getElementById('light').style.display='none';
			document.getElementById('fade').style.display='none';
		}
	}
	req.open("GET", url,true);
	req.send(null)
}
</script>

{/literal}


<h2><a href="/adopt/">Hectad Adoptions</a> - Editing {$hectad}</h2>
 
<div id="light" class="white_content">
<iframe src="about:blank" id="iframe" width="100%" height="95%"></iframe>
<div style="text-align:right;"><a href="javascript:void(0)" onclick="document.getElementById('light').style.display='none';document.getElementById('fade').style.display='none'">Close</a></div>
</div><div id="fade" class="black_overlay"></div>

{if $square->reference_index == 1}
<div style="float:right"><img src="http://{$static_host}/img/links/20/mapper.png" width="20" height="20" alt="browse icon" align="absmiddle"/> <a href="/mapper/?t={$map_token}&amp;gridref_from={$gridref}"><b>Draggable</b></a>, <img src="http://{$static_host}/img/links/20/dragcenti.png" width="20" height="20" alt="dragable centi icon" align="absmiddle"/> <a href="/mapper/?t={$map_token}&amp;gridref_from={$gridref}&amp;centi=1"><b>Centisquares</b></a><sup style="color:red">New!</sup>
</div>
{/if}

<p>Click a thumbnail to view full size. Click the link under the thumbnail in squares with many images to select another representative photo.<small><br/>
As well as choosing a photo that is represetative of the square, the photo should be reasonably representative of the hectad as a whole</small></p>




<table style="background-color:white;font-family:courier;font-size:0.7em" border=0 cellspacing=0 cellpadding=1> <tbody>
{foreach from=$ys name=y item=y}
	<tr>
	{foreach from=$xs name=x item=x}
		{if $grid.$y.$x}{assign var="image" value=$grid.$y.$x}
			<td id="cell_{$y}_{$x}" valign="top">{include file="_adopt_cell.tpl"}</td>
		{else}
			<td>&nbsp;</td>
		{/if}
	{/foreach}
	</tr>
{/foreach}
</tbody>
</table>

{if $stats}
	<p>&nbsp;</p>
	<div class="interestBox">
		<p>To help prevent contributor bias here are some statistics of the contributors on last page load (<a href="?hectad={$hectad}">reload</a>)</p>
		<ol>
		{foreach from=$stats name=x key=n item=c}
			<li value="{$c}">{$n|escape:'html'}</li>
		{/foreach}
		</ol>
	</div>
{/if}

{/dynamic}

{include file="_std_end.tpl"}
