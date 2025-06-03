{assign var="page_title" value="First Geograph Search"}
{include file="_std_begin.tpl"}

<h2>Advanced Search Builder</h2>

{if $errormsg}
<p class="error-text"><b>{$errormsg}</b></p> {/* Replaced style="color:red" */}
{/if}

	<div class="tabHolder">
		<a href="/search.php?form=simple" class="tab">Simple search</a>
		<a href="/search.php?form=text" class="tab">Advanced Search</a>
		{dynamic}
		{if $user->registered}
		<a href="/search.php?form=advanced&amp;legacy=true" class="tab"><small>Old advanced</small></a>
		{/if}
		{/dynamic}
		<span class="tabSelected">First Geographs</span>
	</div>
	<div class="interestBox">
		<b>First Geograph Search</b>
	</div>
	<p>The old First Geograph interface is now gone, but much of the functionality is now available in other ways, see below</p>

<div class="content-padding-large"> {/* Replaced style="padding:20px;" */}

<h3>First Geographs</h3>
<p>You can <b>now</b> search specifically for "First Geographs" in the <a href="{$script_name}?form=text">Normal Search</a></b>, just enter <tt>ftf:1</tt> as the last keyword.</p>
<form method="get" action="{$script_name}" class="search-form interestBox">
	<div class="search-form-grid">
		<div class="form-row">
			<div class="form-cell"><label for="q_ftf1">Keywords:</label></div>
			<div class="form-cell"><input type="text" id="q_ftf1" name="q" value="river ftf:1"/></div>
			<div class="form-cell"><input type="submit" value="Find"/></div>
		</div>
	</div>
</form>

<br/>
<h3>Hectad / Myriad references</h3>

<p>also a reminder that the new search can search hectad and myriad references directly:</p>
<form method="get" action="{$script_name}" class="search-form interestBox">
	<div class="search-form-grid">
		<div class="form-row">
			<div class="form-cell"><label for="q_tq74">Example:</label></div>
			<div class="form-cell"><input type="text" id="q_tq74" name="q" value="TQ74 ftf:1"/></div>
			<div class="form-cell"><input type="submit" value="Find"/></div>
		</div>
	</div>
</form>
<p>or</p>
<form method="get" action="{$script_name}" class="search-form interestBox">
	<div class="search-form-grid">
		<div class="form-row">
			<div class="form-cell"><label for="q_hectad_tq74">Example:</label></div>
			<div class="form-cell"><input type="text" id="q_hectad_tq74" name="q" value="hectad:TQ74 ftf:1"/></div>
			<div class="form-cell"><input type="submit" value="Find"/></div>
		</div>
	</div>
</form>

<br/>
<p>... and as they are keywords can combine them. <a href="/article/Word-Searching-on-Geograph" class="about">About</a></p>
<form method="get" action="{$script_name}" class="search-form interestBox">
	<div class="search-form-grid">
		<div class="form-row">
			<div class="form-cell"><label for="q_tq_multi">Example:</label></div>
			<div class="form-cell"><input type="text" id="q_tq_multi" name="q" value="tq74 OR tq73 OR tq64 OR tq64 ftf:1" size="40"/></div>
			<div class="form-cell"><input type="submit" value="Find"/></div>
		</div>
	</div>
</form>
<small>(finds 'First Geographs' in all four hectads)</small>
<br/><br/>
<p>Another:</p>
<form method="get" action="{$script_name}" class="search-form interestBox">
	<div class="search-form-grid">
		<div class="form-row">
			<div class="form-cell"><label for="q_quarry_sh">Example:</label></div>
			<div class="form-cell"><input type="text" id="q_quarry_sh" name="q" value="quarry SH ftf:1"/></div>
			<div class="form-cell"><input type="submit" value="Find"/></div>
		</div>
	</div>
</form>

<br/>
<h3>Numerical Square</h3>
<p>These can be done with the new experimental <a href="/finder/sqim.php">Search by Gridsquare</a> feature...</p>

<form method="get" action="/finder/sqim.php" class="search-form interestBox">
	<div class="search-form-grid">
		<div class="form-row">
			<div class="form-cell"><label for="q_sqim">Example:</label></div>
			<div class="form-cell"><input type="text" id="q_sqim" name="q" value="bridge easting:55 northing:46" size="40"/></div>
			<div class="form-cell"><input type="submit" value="Find"/></div>
			<div class="form-cell">(for <span class="text-muted">XX</span>5546 )</div> {/* Replaced style="color:gray" */}
		</div>
	</div>
</form>

</div>

{include file="_std_end.tpl"}
