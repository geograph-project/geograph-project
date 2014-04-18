{assign var="page_title" value="Bulk Convertor"}
{include file="_std_begin.tpl"}
<script src="{"/sorttable.js"|revision}"></script>
{literal}
<style>
a.add {
	color:green;
}
a.rem {
	color:red;
}
</style>
{/literal}
<h2>Bulk Category --> Context and Tags convertor</h2>


{dynamic}    


{if $imageclass && $field} 
	<h3>Make a moderation</h3>
	<form method=get style="background-color:#eee;padding:10px">
		category: <input type=text name=imageclass value="{$imageclass|escape:'html'}" readonly style="background-color:#eee;"><br>
		type: <input type=text name=field value="{$field|escape:'html'}" readonly style="background-color:#eee;"><br>
		action: <input type=text name=action value="{$action|escape:'html'}" readonly style="background-color:#eee;"><br>

		value: 
		{if $action != 'add'}
			<input type=text name=value value="{$value|escape:'html'}" readonly style="background-color:#eee;"><br>
		
		{elseif $field == 'tag'}
			{literal}
			<input type="text" name="value" size="30" maxlength="94" onkeyup="if (this.value.length > 2) {loadTagSuggestions(this,event);} " autocomplete="off" placeholder="Tag Search" id="tagsearch"><br/>
	                <div style="position:relative;">
        	                <div style="position:absolute;top:0px;left:0px;background-color:lightgrey;margin-left:6px;padding-right:20px;display:none" id="tagParent">
                	                <ul id="taglist">
                        	        </ul>
	                        </div>
        	        </div>
		        <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
			<script>

        function loadTagSuggestions(that,event) {

                var unicode=event.keyCode? event.keyCode : event.charCode;
                if (unicode == 13) {
                        useTag(that.value);
                        return false;
                }

                param = 'q='+encodeURIComponent(that.value);

                $.getJSON("/tags/tags.json.php?"+param+"&callback=?",

                // on search completion, process the results
                function (data) {
                        var div = $('#taglist').empty();
                        $('#tagParent').show();

                        if (data && data.length > 0) {

                                for(var tag_id in data) {
                                        var text = data[tag_id].tag;
                                        if (data[tag_id].prefix && data[tag_id].prefix!='term' && data[tag_id].prefix!='category' && data[tag_id].prefix!='cluster' && data[tag_id].prefix!='wiki') {
                                                text = data[tag_id].prefix+':'+text;
                                        }
                                        text = text.replace(/<[^>]*>/ig, "");
                                        text = text.replace(/['"]+/ig, " ");

                                        div.append("<li><a href=\"javascript:void(useTag('"+encodeURIComponent(text)+"'))\">"+text+"</a></li>");
                                }

                        } else {
                                div.append("<li>Tag not found</li>");
                        }
                });
        }

	function useTag(text) {
		$('#tagsearch').val(text);
		$('#taglist').empty();
                $('#tagParent').hide();
	}
			</script>
			{/literal}
		{else}
			<select name="value">
				<option value=""></option>
				{html_options options=$values selected=$value}
			</select><br/>
		{/if}
		explanation: <input type="text" name="explanation" size=100 maxlength="255"><br>
		 (please explain why making this change)<br>
		<input type=submit name=confirm value="confirm">
	</form>
{else}
	<h3>Moderate Suggestions</h3>
{/if}

{if !$subject}
	<p>This page looks at what categories you have used on your images, and shows the Context(s), Subject and Tags that could be auto added to those images.</p>
{/if}

<p>Click a "X" to remove the particular mapping. Or a "Add" to suggest a new mapping - a label that can be added for all images using the particular category</p>

<a href="?show=1{$extra}">Show all (dont hide checked rows)</a>
<table class="report sortable" id="catlist" style="font-size:8pt;" cellpadding=4>
<thead>
<tr>
	<td>Category</td>
	<td>Images</td>
	<td>Context(s)</td>
	<td>Subject</td>
	<td>Tag(s)</td>
</tr>
<thead>
<tbody>
{foreach from=$suggestions key=imageclass item=row}
	{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
	<tr bgcolor="{$bgcolor}">
		<td><b><a href="/search.php?imageclass={$imageclass|escape:'url'}&amp;do=1{if $user_id}&amp;user_id={$user_id}{/if}">{$imageclass|escape:'html'}</a></b></td>
              	<td align=right><b>{$row.images}</b></td>
		<td class="tags">
			{if $row.context1 && $row.context1!='-bad-' && $row.context1!='forum alerted'}
				<span class=tag>
				<a href="/tagged/top:{$row.context1|escape:'url'}" class="taglink">{$row.context1|escape:'html'}</a>
				<a href="?imageclass={$imageclass|escape:'url'}&amp;field=context&action=remove&amp;value={$row.context1|escape:'url'}{$extra}" class="rem">X</a>
				</span>
			{/if}
			{if $row.context2 && $row.context2!='-bad-' && $row.context2!='forum alerted'}
				<span class=tag>
				<a href="/tagged/top:{$row.context2|escape:'url'}" class="taglink">{$row.context2|escape:'html'}</a>
				<a href="?imageclass={$imageclass|escape:'url'}&amp;field=context&action=remove&amp;value={$row.context2|escape:'url'}{$extra}" class="rem">X</a>
				</span>
			{/if}
			{if $row.context3 && $row.context3!='-bad-' && $row.context3!='forum alerted'}
				<span class=tag>
				<a href="/tagged/top:{$row.context3|escape:'url'}" class="taglink">{$row.context3|escape:'html'}</a>
				<a href="?imageclass={$imageclass|escape:'url'}&amp;field=context&action=remove&amp;value={$row.context3|escape:'url'}{$extra}" class="rem">X</a>
				</span>
			{/if}
			<a href="?imageclass={$imageclass|escape:'url'}&amp;field=context&action=add{$extra}" class="add">Add</a>
		</td>
		<td class="tags">
			{if $row.subject}
				<span class=tag>
				<a href="/tagged/subject:{$row.subject|escape:'url'}" class="taglink">{$row.subject|escape:'html'}</a>
				<a href="?imageclass={$imageclass|escape:'url'}&amp;field=subject&action=remove&amp;value={$row.subject|escape:'url'}{$extra}" class="rem">X</a>
				</span>
			{else}
				<a href="?imageclass={$imageclass|escape:'url'}&amp;field=subject&action=add{$extra}" class="add">Add</a>
			{/if}
		</td>
		<td class="tags">
			{if $row.tags}
				{foreach from=$row.tags item=tag}
					{if $tag ne '-bad-' && $tag|lower ne $imageclass|lower}
	        	                        <span class=tag>
               			                <a href="/tagged/{$tag|escape:'url'}" class="taglink">{$tag|escape:'html'}</a>
						<a href="?imageclass={$imageclass|escape:'url'}&amp;field=tag&action=remove&amp;value={$tag|escape:'url'}{$extra}" class="rem">X</a>
	                        	        </span>
					{/if}
				{/foreach}

			{elseif !$row.subject}
				<span class=tag>
				<a href="/tagged/category:{$imageclass|escape:'url'}" class="taglink">{$imageclass|lower|escape:'html'}</a>
				</span>
			{/if}

			{if $row.canonical && $row.canonical|lower ne $imageclass|lower && $row.canonical ne '-bad-'}
				<span class=tag>
                                <a href="/search.php?canonical={$row.canonical|escape:'url'}&amp;do=1" class="taglink">{$row.canonical|escape:'html'}</a>
				<a href="?imageclass={$imageclass|escape:'url'}&amp;field=canonical&action=remove&amp;value={$row.canonical|escape:'url'}{$extra}" class="rem">X</a>
                                </span>
			{/if}
			<a href="?imageclass={$imageclass|escape:'url'}&amp;field=tag&action=add{$extra}" class="add">Add</a>
		</td>
		<td>
			<a href="?imageclass={$imageclass|escape:'url'}&amp;action=checked{$extra}" class="add">Checked</a>
		</td>
	</tr>
{/foreach}
</tbody>
</table>

{/dynamic}


{include file="_std_end.tpl"}
