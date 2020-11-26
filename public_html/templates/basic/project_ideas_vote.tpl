{include file="_std_begin.tpl"}
{literal}
<style>
  .connectedSortable {
    border: 1px solid gray;
    width: 360px;
    min-height: 20px;
    list-style-type: none;
    margin: 0;
    padding: 5px 0 0 0;
    margin-right: 10px;
    margin-bottom: 10px;
  }
  .connectedSortable li {
    margin: 0 5px 5px 5px;
    padding: 5px;
    font-size:0.8em;
    width: 340px;
  }
  .sortContainer {
    float: left;
    width: 380px;
  }
</style>
{/literal}

<h2><a href="/project/ideas.php">Geograph ideas</a> :: Voting for idea(s) you support</h2>

<form method=post name="theForm">

<p>Use this page to give points to your favorite idea(s). Drag items to the left panel, and order them to assign points (more detail at the bottom)</p>

{dynamic}

{if $autoadd}
        <p style=background-color:yellow><big>The selected idea, has been added at the top of the left list for you (but not saved yet!). You can now move it suitable location in the list.
                And/or add other ideas you like to the list. Don't forget to save!</big></p>
{/if}

<div id="output">
	<div class="sortContainer">
		<h4>Ideas you like the most</h4>
		<ul class="connectedSortable">
		{foreach from=$ideas item=idea}
			{if $idea.mine > 0}
				<li class="ui-state-default" data-id="{$idea.project_idea_id}" title="{$idea.content|escape:'html'|truncate:250}">{$idea.title|escape:'html'}</li>
			{/if}
                {/foreach}
		</ul>
	</div>
	<div class="sortContainer">
		<h4>All other ideas</h4>
		<ul class="connectedSortable">
		{foreach from=$ideas item=idea}
			{if $idea.mine eq 0}
				<li class="ui-state-default" data-id="{$idea.project_idea_id}" title="{$idea.content|escape:'html'|truncate:250}">{$idea.title|escape:'html'}</li>
			{/if}
                {/foreach}
		</ul>
	</div>


	<br style="clear:both"></div>
{/dynamic}


<hr>

	<input type=button value="Save your selection" onclick="saveResults()"> (votes are not saved until you press this button)
	<textarea name="results" id="results" style="display:none" rows=50 cols=100></textarea>

</form>
<hr>

<b>Instructions</b>
<ol>
	<li>Drag and drop items into the <b>LEFT</b> panel to add <b>your</b> vote for them.
	<li>Drag items to reorder the items in the left panel, to put your <b>favorite items at the TOP</b>.<ul>
		<li>Your top item gets 10 points, the next 5, next 2.5 etc.
		</ul>
	<li>You can add as many ideas as you like to the left panel (if you like them), but the relative vote deminisishs the more you add.
	<li>Don't forget to click the <b>Save button</b>, to record your votes. 
	<li>Can always return to this page later, to <b>change</b> your votes, even moving items back to the right panel. 
</ol>


{literal}
  <link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
  <script src="//code.jquery.com/jquery-1.10.2.js"></script>
  <script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
<script>

$(function() {
                        $( ".connectedSortable" ).sortable({
                                      connectWith: ".connectedSortable"
                                    }).disableSelection();

});

function saveResults() {
	var text = '';
	var errors = 0;
	$(".connectedSortable").each(function(index) {
		$(this).find('li').each(function(index) {
			var that = $(this);
			text = text + that.text() + " | "+that.data('id')+' | '+(that.hasClass('ui-state-highlight')?1:0)+"\n";
		});
		text = text + "\n";
	});
	if (errors > 0) {
		alert("Unable to submit, issues with "+errors+" group(s)");
		return;
	}

	var out = $('#results').show();
	out.val(text);

	//now we validated, can submit!
	document.forms['theForm'].submit();

}

{/literal}

</script>

{include file="_std_end.tpl"}

