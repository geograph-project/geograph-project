{include file="_std_begin.tpl"}
<style>{literal}
.examples li {
	padding-bottom:20px;
}
.examples tt {
	padding-left:30px;
	border:1px solid silver;
	padding:3px;
	margin:3px;
}
</style>{/literal}

 <h2>Create Page</h2>

<form method="post" action="{$script_name}">
<div class="interestBox" style="text-align:center">
What is this page to be about about?<br/>
<input type="text" name="input" size="60" style="font-size:1.2em"/><br/>
<input type="submit">
</div>
</form>

<p><b>In the box above, enter ONE of the following</b>:</p>

<ul class="examples">
	<li>The URL of an article, eg <br/>
		<tt>http://{$http_host}/article/Dumbarton-Cemetery</tt></li>

	<li>The URL of a Shared Description, eg <br/>
		<tt>http://{$http_host}/snippet/6253</tt></li>

	<li>Myriad, Hectad or Gridsquare reference, eg <br/>
                <tt>NT4534</tt> or <tt>TQ74</tt></li>

	<li>Placename, eg, <br/>
                <tt>Limerick</tt> or <tt>Glasgow</tt></li>

	<li>Tag, eg, <br/>
                <tt>Bridge</tt></li>
</ul>
	




{include file="_std_end.tpl"}

