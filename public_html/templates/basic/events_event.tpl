{assign var="page_title" value="Event :: $title"}
{include file="_std_begin.tpl"}
{literal}<style type="text/css">
.unable,.unable A  {
	color:gray;
}
</style>{/literal}
<script src="{"/sorttable.js"|revision}"></script>

<h2><a href="/events/">Events</a> :: {$title|escape:"html"}</h2>

<div style="float:right; position:relative; padding:5px; border:1px solid gray; ">
	<div style="color:red; background-color:white">Marker only shows grid square, see description for exact location</div><br/>
	<div style="width:500px; height:450px;" id="mapCanvas">Loading map...</div>
</div>

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
			  by <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a>
			  for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>
		</div>
	</div></dd>
{/if}

</dl>

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

	<table class="report sortable" id="events">
	<thead><tr>
		<td style="width:130px;font-size:0.9em" sorted="asc">Updated</td>
		<td>Who</td>
		<td>Message{dynamic}{if $user->registered} <small>(<i>optional</i>, 160 characters max)</small>{/if}{/dynamic}</td>
		<td>Intention</td>
	</tr></thead>
	<tbody>
	{dynamic}

	{if $list}
	{foreach from=$list item=item}
		{if $item.geoevent_attendee_id == $attendee.geoevent_attendee_id && $edit}
			<tr>
				<td style="font-size:0.9em"><b>{$attendee.updated|date_format:"%a, %e %b %Y"|default:"-"}</b></td>
				<td>{$user->realname|escape:"html"}<input type="hidden" name="id" value="{$geoevent_id}"/><input type="hidden" name="attendee" value="{$attendee.geoevent_attendee_id}"/></td>
				<td><input type="text" name="message" value="{$attendee.message|escape:"html"}" size="64" maxlength="160" onkeyup="{literal}if (this.value != this.defaultValue) {this.form.saveBtn.style.display=''; }{/literal}"/></td>
				<td><select name="type" onchange="this.form.saveBtn.style.display='';">
				<option value="">select...</option>
				{html_options options=$types selected=$attendee.type}
				</select> </td>
				<td><input type="submit" value="save" name="saveBtn" style="display:none"/></td>
			</tr>
		{else}
			{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
			<tr bgcolor="{$bgcolor}"{if $item.type == 'unable to attend'} class="unable"{/if}>
				<td sortvalue="{$item.updated}" class="nowrap" style="font-size:0.9em"><b>{$item.updated|date_format:"%a, %e %b %Y"}</b></td>
				<td sortvalue="{$item.realname|escape:"html"}"><a href="/profile/{$item.user_id}">{$item.realname|escape:"html"}</a></td>
				<td>{$item.message|escape:"html"|default:'--None--'}</td>
				<td>{$item.type}</td>
				{if $item.geoevent_attendee_id == $attendee.geoevent_attendee_id}
					<td><a href="{$script_name}?id={$geoevent_id}&amp;edit=1">Edit</a></td>
				{/if}
			</tr>
		{/if}
	{/foreach}
	{else}
		<tr><td colspan="2">- there are no registered attendees -</td></tr>
	{/if}

	{if $user->registered && !$attendee.geoevent_attendee_id}
	<tr>
		<td style="font-size:0.9em">Register Here</td>
		<td>{$user->realname|escape:"html"}<input type="hidden" name="id" value="{$geoevent_id}"/></td>
		<td><input type="text" name="message" value="{$attendee.message|escape:"html"}" size="64" maxlength="160"/></td>
		<td><select name="type">
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
        <script type="text/javascript" src="{"/js/mappingLeaflet.js"|revision}"></script>

	{literal}
	<script type="text/javascript">
	//<![CDATA[
	var map = null ;
	var issubmit = false;


                                        function loadmap() {
						{/literal}
                                                var point = [{$lat},{$long}];
						{literal}
                                                var newtype = readCookie('GMapType');

                                                mapTypeId = firstLetterToType(newtype);

                                                map = L.map('mapCanvas',{attributionControl:false}).setView(point, 8).addControl(
                                                        L.control.attribution({ position: 'bottomright', prefix: ''}) );

                                                setupOSMTiles(map,mapTypeId);

                                                map.on('baselayerchange', function (e) {
                                                        if (e.layer && e.layer.options && e.layer.options.mapLetter) {
                                                                var t = e.layer.options.mapLetter;
                                                                createCookie('GMapType',t,10);
                                                        } else {
                                                                console.log(e);
                                                        }
                                                });

						 createMarker(point);
                                        }
                                        AttachEvent(window,'load',loadmap,false);

	//]]>
	</script>
{/literal}{/if}

{include file="_std_end.tpl"}

