{include file="_std_begin.tpl"}
{dynamic}

<h2>Map Maker</h2>
<p>This is a very basic tool for exploring the grid database.

Enter internal coordinates and away you go! The defaults cover
every known square in the database
</p>


<form method="post" action="mapmaker.php">

x1: <input size="3" type="text" name="x1" value="{$x1}"/><br />
y1: <input size="3" type="text" name="y1" value="{$y1}"/><br />
x2: <input size="3" type="text" name="x2" value="{$x2}"/><br />
y2: <input size="3" type="text" name="y2" value="{$y2}"/><br />

<input type="submit" name="make" value="Make Map">

</form>

{/dynamic}    
{include file="_std_end.tpl"}
