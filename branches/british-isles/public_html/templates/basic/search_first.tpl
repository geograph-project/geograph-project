{assign var="page_title" value="First Geograph Search"}
{include file="_std_begin.tpl"}

<h2>Advanced Search Builder</h2>

{if $errormsg}
<p style="color:red"><b>{$errormsg}</b></p>
{/if}

	<div class="tabHolder">
		<a href="/search.php?form=simple" class="tab">simple search</a>
		<a href="/search.php?form=text" class="tab">Advanced Search</a>
		{dynamic}
		{if $user->registered}
		<a href="/search.php?form=advanced&amp;legacy=true" class="tab"><small>old advanced</small></a>
		{/if}
		{/dynamic}
		<span class="tabSelected">first geographs</span>
	</div>
	<div class="interestBox">
		<b>First Geograph Search</b>
	</div>
	<p>The old first geograph interface is now gone. But much of the functionality is now available in other ways, see below</p>

<div style="padding:20px;">

<h3>First Geographs</h3>
You can <b>now</b> search specifically for "First Geographs" in the <a href="{$script_name}?form=text">Normal Search</a></b>, just enter <tt>ftf:1</tt> as the last keyword.
<form method="get" action="{$script_name}" class="interestBox">
Keywords: <input type="text" name="q" value="river ftf:1"/> <input type="submit" value="Find"/>
</form>

<br/>
<h3>Hectad / Myriad references</h3>

also a reminder that the new search can search hectad and myriad references directly:
<form method="get" action="{$script_name}" class="interestBox">
Example: <input type="text" name="q" value="TQ74 ftf:1"/> <input type="submit" value="Find"/>
</form>
or
<form method="get" action="{$script_name}" class="interestBox">
Example: <input type="text" name="q" value="hectad:TQ74 ftf:1"/> <input type="submit" value="Find"/>
</form>

<br/>
... and as they are keywords can combine them. <a href="/article/Word-Searching-on-Geograph" class="about">About</a>
<form method="get" action="{$script_name}" class="interestBox">
Example: <input type="text" name="q" value="tq74 OR tq73 OR tq64 OR tq64 ftf:1" size="40"/> <input type="submit" value="Find"/> <br/>
</form>
<small>(finds 'first geographs' in all four hectads)</small>
<br/><br/>
Another:
<form method="get" action="{$script_name}" class="interestBox">
Example: <input type="text" name="q" value="quarry SH ftf:1"/> <input type="submit" value="Find"/>
</form>

<br/>
<h3>Numerical Square</h3>
These can be done with the new experimental <a href="/finder/sqim.php">Search by Gridsquare</a> feature...

<form method="get" action="/finder/sqim.php" class="interestBox">
Example: <input type="text" name="q" value="bridge easting:55 northing:46" size="40"/> <input type="submit" value="Find"/> (for <span style="color:gray">XX</span>5546 )
</form>


</div>


{include file="_std_end.tpl"}
