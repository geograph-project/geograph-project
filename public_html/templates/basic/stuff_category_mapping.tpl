{assign var="page_title" value="Bulk Convertor"}
{include file="_std_begin.tpl"}
<script src="{"/sorttable.js"|revision}"></script>

<h2>Bulk Category --> Context and Tags convertor</h2>


{dynamic}    


<h3>Preview Suggestions</h3>
<div class="interestBox" style="background-color:pink">NOTE: This table/form is NOT functional. Just showing suggestions for the moment. </div>

{if !$subject}
	<p>This page looks at what categories you have used on your images, and shows the Context(s), Subject and Tags that could be auto added to those images.</p>
{/if}

<form>
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
{foreach from=$suggestions item=row}
	{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
	<tr bgcolor="{$bgcolor}">
		<td><b>{$row.imageclass|escape:'html'}</b></td>
              	<td align=right><b>{$row.images}</b></td>
		<td class="tags">
			{if $row.context1 && $row.context1!='-bad-' && $row.context1!='forum alerted'}
				<span class=tag>
				<a href="/tagged/top:{$row.context1|escape:'url'}" class="taglink">{$row.context1|escape:'html'}</a>
				</span>
			{/if}
			{if $row.context2 && $row.context2!='-bad-' && $row.context2!='forum alerted'}
				<span class=tag>
				<a href="/tagged/top:{$row.context2|escape:'url'}" class="taglink">{$row.context2|escape:'html'}</a>
				</span>
			{/if}
			{if $row.context3 && $row.context3!='-bad-' && $row.context3!='forum alerted'}
				<span class=tag>
				<a href="/tagged/top:{$row.context3|escape:'url'}" class="taglink">{$row.context3|escape:'html'}</a>
				</span>
			{/if}
		</td>
		<td class="tags">
			{if $row.canonical}
				<span class=tag>
				subject:<a href="/tagged/subject:{$row.canonical|escape:'url'}" class="taglink">{$row.canonical|escape:'html'}</a>
				</span>
			{/if}
		</td>
		<td class="tags">
			{if $row.tags}
                                <span class=tag>
                                <a href="/tagged/{$row.tags|escape:'url'}" class="taglink">{$row.tags|escape:'html'}</a>
                                </span>

			{elseif !$row.subject || $row.subject|lower ne $row.imageclass|lower}
				<span class=tag>
				<a href="/tagged/category:{$row.imageclass|escape:'url'}" class="taglink">{$row.imageclass|lower|escape:'html'}</a>
				</span>
			{/if}
		</td>
	</tr>
{/foreach}
</tbody>
</table>
<input type="submit" value="Submit changes" disabled/> (Not yet functional)
</form>

{/dynamic}


{include file="_std_end.tpl"}
