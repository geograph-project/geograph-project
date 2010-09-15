{assign var="page_title" value="Full-Text Relevenence Play Area"}
{include file="_std_begin.tpl"}
{literal}

  <script type="text/javascript">
  
  function focusBox() {
  	el = document.getElementById('fq');
  	el.focus();
  }
  AttachEvent(window,'load',focusBox,false);
  
  </script>

{/literal}

<h2>Full-Text Relevance Play Area</h2>

<div class="interestBox">This is an experiment to try a couple of 'ranking modes', the current search uses a default and reasonable one, but there are many possiblities. 
<ul>
{dynamic}

	<li>So try running a search below and see how alternatives compare. {if $compare}Optionally rate {else}Rate{/if} each using the section at the bottom of each box.</li>
	{if $compare}<li>Click the 'This one is best' against which column you things represents the best results</li>{/if}
	<li>You are only rating how <b>subjectivly good</b> the <u>first few results</u> are for your choosen search.</li>
	{if !$compare}<li><small>There is no need to rate every single one, just the particully good or particully bad ones. Pay particular attention to the first 5 columns.</small></li>{/if}
{/dynamic}

</ul>
</div>

<form action="{$script_name}" method="get">
	<p>
		<label for="fq">Free Text Search</label>: <input type="text" name="q" id="fq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
		{if $compare}
			<input type="hidden" name="c" value="1"/>
		{/if}
		<input type="submit" value="Search"/>
	</p>

</form>

{dynamic}
{if $inners}
	<div style="width:{math equation="186*c" c=$count_inners}px">
	{foreach from=$inners item=item}
		<div style="position:relative;float:left">
			{if $compare}
				<iframe src="{$item.url}" width="680" height="280"></iframe>
				<form method="get" action="/finder/modes.php">

					<input type="hidden" name="q" value="{$q|escape:'html'}"/>
					{if $compare}
						<input type="hidden" name="c" value="1"/>
					{/if}
					<input type="hidden" name="mode" value="{$item.mode|escape:'html'}"/>
					<input type="hidden" name="modes" value="{$modes|escape:'html'}"/>

					<input type="submit" value="This one is best!"/>
				</form>
				<br/><br/>
			{else}
				<iframe src="{$item.url}" width="180" height="800"></iframe>
			{/if}
		</div>
	{/foreach}
	<br style="clear:both"/>
	</div>
	{if $compare}
		<hr/>
		<form method="get" action="/finder/modes.php">

			<input type="hidden" name="q" value="{$q|escape:'html'}"/>
			{if $compare}
				<input type="hidden" name="c" value="1"/>
			{/if}
			<input type="hidden" name="mode" value="0"/>
			<input type="hidden" name="modes" value="{$modes|escape:'html'}"/>
			<input type="submit" value="Stalemate! neither stand out"/> (shows another pair) 
		</form>
	{/if}
{/if}
{/dynamic}


<div style="margin-top:6px; margin-bottom:60px;">
	<img src="/templates/basic/img/icon_alert.gif" alt="Alert" width="18" height="16" align="left" style="margin-right:10px"/>
	<small>For this experiment, please use 2-10 keywords, and avoid all special operators (such as OR, ~, " or -), the only acceptable modifier is = (to disable stemming for that keyword).</small>
</div>


{include file="_std_end.tpl"}
