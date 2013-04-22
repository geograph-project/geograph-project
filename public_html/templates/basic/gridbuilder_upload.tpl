{include file="_std_begin.tpl"}
{dynamic}

<h2>Grid Building: Upload File</h2>
<p>Please choose the file to upload.</p>


<form enctype="multipart/form-data" method="post" action="gridbuilder.php">

<input id="uploadpng" name="uploadpng" type="file" size="60" /><br />
<input type="hidden" name="MAX_FILE_SIZE" value="8192000" />

<input type="hidden" id="shader_image" name="shader_image" value="{$shader_image}"/>
<input type="hidden" id="shader_x" name="shader_x" value="{$shader_x}"/>
<input type="hidden" id="shader_y" name="shader_y" value="{$shader_y}"/>
<input type="hidden" id="reference_index" name="reference_index" value="{$reference_index}"/>
{if $clearexisting}<input type="hidden" name="clearexisting" id="clearexisting" value="1">{/if}
{if $skipupdategridprefix}<input type="hidden" name="skipupdategridprefix" id="skipupdategridprefix" value="1">{/if}
{if $redrawmaps}<input type="hidden" name="redrawmaps" id="redrawmaps" value="1">{/if}
{if $ignore100}<input type="hidden" name="ignore100" id="ignore100" value="1">{/if}
{if $dryrun}<input type="hidden" name="dryrun" id="dryrun" value="1">{/if}
<input type="hidden" id="minx" name="minx" value="{$minx}"/>
<input type="hidden" id="maxx" name="maxx" value="{$maxx}"/>
<input type="hidden" id="miny" name="miny" value="{$miny}"/>
<input type="hidden" id="maxy" name="maxy" value="{$maxy}"/>
<input type="submit" name="upload" value="Ok"><input type="submit" name="back" value="Back">
</form>
{/dynamic}
{include file="_std_end.tpl"}
