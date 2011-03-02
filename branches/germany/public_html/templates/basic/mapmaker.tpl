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
ri: <input size="3" type="text" name="ri" value="{$ri}"/><br />
scale: <input size="5" type="text" name="scale" value="{$scale}"/><br />
limit: <input size="5" type="text" name="limit" value="{$limit}"/><br />
user: <input size="5" type="text" name="user" value="{$usr}"/><br />
level1: <input size="5" type="text" name="level1" value="{$level1}"/><br />
level2: <input size="5" type="text" name="level2" value="{$level2}"/><br />
cid: <input size="5" type="text" name="cid" value="{$cid}"/><br />
force: <input type="checkbox" name="force" value="1"{if $force} checked{/if}>
geo: <input type="checkbox" name="geo" value="1"{if $geo} checked{/if}>
grid: <input type="checkbox" name="grid" value="1"{if $grid} checked{/if}><br />
black/white: <input type="checkbox" name="bw" value="1"{if $bw} checked{/if}><br />
<input type="submit" name="make" value="Make Map">

</form>

{/dynamic}    
{include file="_std_end.tpl"}
