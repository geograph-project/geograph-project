{assign var="page_title" value="UserSort"}
{include file="_std_begin.tpl"}
{literal}
<style type="text/css">
.photo_box {
	float:left;
	position:relative;
	padding: 10px;
	
}

</style>
{/literal}
{dynamic}
{if count($pairs)} 
	<h2>UserSort - Experimental</h2>
	
	{if $message}
		<p style="color:#990000;font-weight:bold;">{$message}</p>
	{/if}
	
	<p>Below you will be shown pairs of images, you are asked to simply click on the image that, in your opinion, {$v_info.that}. Yes this is very subjective, this is simply an experiment to see how images can be classified by {$v_info.name}.</p>
	
	<p>Please treat this as a bit of fun, and don't spend too long deciding, just go with your gut instinct!</p>
	
	
	{foreach from=$pairs key=i item=row name="rows"}
		<div id="row{$smarty.foreach.rows.iteration}" style="display:none">
		{foreach from=$row key=i item=photo name="cells"}
			<div class="photo_box">
				<a href="#" onclick="return storeVote({$smarty.foreach.rows.iteration},{$photo->gridimage_id},{$photo->other_id})"><img src="{$photo->url}"></a>			
			</div>
		{/foreach}
		<br style="clear:both"/>
		{$smarty.foreach.rows.iteration}/{$pairs_count}
		</div>
		
	{/foreach}
	<br style="clear:both"/>
	<div id="row{$smarty.foreach.rows.iteration+1}" class="interestBox" style="display:none">
		<h4>Thank You</h4>
		<form name="theForm" action="{$script_name}" method="post">
			<input type="hidden" name="v" value="{$v}"/>
			<input type="hidden" name="plus" value=""/>
			<input type="hidden" name="minus" value=""/>
			<p>Click the following button to register your votes on the system:<br/>
			<input type="submit" name="Save" value="Store Votes &gt; &gt;"/></p>
			<p><input type="checkbox" name="more" id="more" checked/> <label for="more">Load another page of results</label></p>
		</form>
	</div>
{else}
	<p>There are no images for you to sort at the moment, thanks for your interest, and please check back tomorrow.</p>
{/if}
{/dynamic}

{literal}
<script type="text/javascript">
function storeVote(row,plus,minus) {
	document.getElementById('row'+row).style.display = 'none';
	document.getElementById('row'+(row+1)).style.display = '';
	f = document.theForm;
	f.plus.value = f.plus.value + plus + ',';
	f.minus.value = f.minus.value + minus + ',';
	return false;
}


function startVotes() {
	ele = document.getElementById('row1');
	if (ele) {
		ele.style.display = '';
	}
}
	

AttachEvent(window,'load',startVotes,false);

</script>
{/literal}
{include file="_std_end.tpl"}
