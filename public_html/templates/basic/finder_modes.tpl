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

<h2>Full-Text Relevenence Play Area</h2>

<div class="interestBox">This is an experiment to try a couple of 'ranking modes', the current search uses a default and reasonable one, but there are many possiblities. 
<ul>
	<li>So try running a search below and see how alternatives compare. Rate each using the section at the bottom of each box.</li>
	<li>You are only rating how subjectivly good the first few results are for your choosen search.</li>
	<li><small>There is no need to rate every single one, just the particully good or particully bad ones. Pay particular attention to the first 5 columns.</small></li>
</ul>
</div>

<form action="{$script_name}" method="get">
	<p>
		<label for="fq">Free Text Search</label>: <input type="text" name="q" id="fq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
		<input type="submit" value="Search"/>
	</p>

</form>

{dynamic}
{if $inners}
	<div style="width:{math equation="186*c" c=$count_inners}px">
	{foreach from=$inners item=item}
		<div style="position:relative;float:left">
			<iframe src="{$item.url}" width="180" height="800"></iframe>
		</div>
	{/foreach}
	<br style="clear:both"/>
	</div>
{/if}
{/dynamic}


<div style="margin-top:6px; margin-bottom:60px;">
	<img src="/templates/basic/img/icon_alert.gif" alt="Alert" width="18" height="16" align="left" style="margin-right:10px"/>
	<small>For this experiment, please use 2-10 keywords, and avoid all special operators (such as OR, ~, " or -), the only acceptable modifier is = (to disable stemming for that keyword).</small>
</div>


{include file="_std_end.tpl"}
