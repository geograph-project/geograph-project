{assign var="page_title" value="Event :: $title"}
{include file="_std_begin.tpl"}
{literal}
<style type="text/css">
#maincontent *{
	box-sizing:border-box;
}
.unable,.unable A  {
	color:gray;
}
</style>{/literal}


<script src="{"/sorttable.js"|revision}"></script>

<h2><a href="/events/">Events</a>: {$title|escape:"html"}</h2>

<div class="twocolsetup">
<div class="twocolumn">
<h3>Details</h3>

<dl class="picinfo">

<dt>Description</dt>
 <dd style="font-size:1.2em">{$description|escape:"html"}</dd>


<dt>When</dt>
 <dd style="font-size:1.2em">{$event_time|date_format:"%a, %e %b %Y"} <small>({$days} days from now)</small></dd>

<dt>Grid Square</dt>
 <dd><a href="/location.php?gridref={$grid_reference}"><img src="{$static_host}/img/geotag_16.png" width="16" height="16" align="absmiddle" alt="geotagged!"/></a> <a title="Grid Reference {$grid_reference}" href="/gridref/{$grid_reference}">{$grid_reference}</a></dd>

{if $url}
<dt>Link</dt>
 <dd style="font-size:1.2em">{external href=$url text="more info" nofollow="1"}</dd>
{/if}

<dt>Event Lister</dt>
 <dd><a title="View profile" href="/profile/{$user_id}">{$realname|escape:'html'}</a></dd>



{if $image}
<dt>Chosen Photo</dt>
 <dd><div class="img-shadow">
		<a href="/photo/{$image->gridimage_id}">{$image->getThumbnail(213,160)}</a>
		 <div style="font-size:0.7em">
			  <a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a>
			  by <a title="view user profile" href="{$image->profile_link}">{$image->realname|escape:'html'}</a>
			  for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>
		</div>
	</div></dd>
{/if}

</dl>

</div>



<div class="twocolumn">
<h3>Map</h3>
	<div style="color:red; background-color:white">Marker only shows grid square, see description for exact location</div><br/>
	<div style="width: 100%; max-width:500px; height:450px;" id="map">Loading map...</div>
</div>
</div>

<br style="clear:both"/>



{if $user->user_id == $user_id || $isadmin}
	<td><a href="/events/edit.php?id={$geoevent_id}">edit</a></td>
{/if}

<br style="clear:both"/>

{dynamic}
{if $stats}
<br/>
<div class="interestBox">Breakdown: |
{foreach from=$stats key=name item=value}
	{$name}: <b>{$value}</b> &nbsp; |  
{/foreach}
</div><br/>
{/if}
{/dynamic}

{if $type == 'signup'}
	<form action="{$script_name}?id={$geoevent_id}" method="post">

	<table class="report width700 sortable" id="events">
	<thead><tr>
		<th sorted="asc">Updated</th>
		<th>Who</th>
		<th>Message{dynamic}{if $user->registered} <small>(<i>optional</i>, 160 characters max)</small>{/if}{/dynamic}</th>
		<th>Intention</th>
	</tr></thead>
	<tbody>
	{dynamic}

	{if $list}
	{foreach from=$list item=item}
		{if $item.geoevent_attendee_id == $attendee.geoevent_attendee_id && $edit}
			<tr>
				<td data-title="Updated"><b>{$attendee.updated|date_format:"%a, %e %b %Y"|default:"-"}</b></td>
				<td data-title="Who">{$user->realname|escape:"html"}<input type="hidden" name="id" value="{$geoevent_id}"/><input type="hidden" name="attendee" value="{$attendee.geoevent_attendee_id}"/></td>
				<td data-title="Message"><input type="text" name="message" value="{$attendee.message|escape:"html"}" size="64" maxlength="160" onkeyup="{literal}if (this.value != this.defaultValue) {this.form.saveBtn.style.display=''; }{/literal}"/></td>
				<td><select name="type" onchange="this.form.saveBtn.style.display='';">
				<option value="">select...</option>
				{html_options options=$types selected=$attendee.type}
				</select> </td>
				<td><input type="submit" value="save" name="saveBtn" style="display:none"/></td>
			</tr>
		{else}
			{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
			<tr bgcolor="{$bgcolor}"{if $item.type == 'unable to attend'} class="unable"{/if}>
				<td data-title="Updated" sortvalue="{$item.updated}" class="nowrap">{$item.updated|date_format:"%a, %e %b %Y"}</td>
				<td data-title="Who" sortvalue="{$item.realname|escape:"html"}"><a href="/profile/{$item.user_id}">{$item.realname|escape:"html"}</a></td>
				<td data-title="Message">{$item.message|escape:"html"|default:'--None--'}</td>
				<td data-title="Intention">{$item.type}</td>
				{if $item.geoevent_attendee_id == $attendee.geoevent_attendee_id}
					<td data-title="Edit"><a href="{$script_name}?id={$geoevent_id}&amp;edit=1">Edit</a></td>
				{/if}
			</tr>
		{/if}
	{/foreach}
	{else}
		<tr><td colspan="2">- there are no registered attendees -</td></tr>
	{/if}

	{if $user->registered && !$attendee.geoevent_attendee_id}
	<tr>
		<td data-title="Sign up"><b>Register Here<b></td>
		<td data-title="Name">{$user->realname|escape:"html"}<input type="hidden" name="id" value="{$geoevent_id}"/></td>
		<td data-title="Message"><input type="text" name="message" value="{$attendee.message|escape:"html"}" style="width: 100%" maxlength="160" placeholder="Enter message"/></td>
		<td data-title="Intention"><select name="type">
		<option value="">select...</option>
		{html_options options=$types selected=$attendee.type}
		</select> </td>
		<td><input type="submit" value="save" name="saveBtn"/></td>
	</tr>
	{/if}

	{/dynamic}
	</tbody>
	<tfoot>

	</tfoot>
	</table>

	</form>
{/if}

{if $lat}
	<link rel="stylesheet" type="text/css" href="https://unpkg.com/leaflet@1.3.1/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.3.1/dist/leaflet.js" type="text/javascript"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.5.0/proj4.js"></script>
	<script type="text/javascript" src="{"/js/Leaflet.MetricGrid.js"|revision}"></script>
        <script type="text/javascript" src="{"/js/mappingLeaflet.js"|revision}"></script>

	<script type="text/javascript">
	//<![CDATA[
	var map = null ;
	var issubmit = false;
	var static_host = '{$static_host}';

	{literal}
                                        function loadmap() {
						{/literal}
                                                var point = [{$lat},{$long}];
						{literal}

                                                setupBaseMap(); //creates the map, but does not initialize a view
                                                map.setView(point, 8);

						createMarker(point);
                                        }
                                        AttachEvent(window,'load',loadmap,false);

	//]]>
	</script>
{/literal}{/if}

{include file="_std_end.tpl"}

