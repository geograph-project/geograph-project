{assign var="page_title" value="Great Britain Map"}
{include file="_std_begin.tpl"}

<table>
	<tbody>
		<tr>
			<td>
<iframe src="/mapper/?inner&amp;t={$token}{if $extra}&amp;{$extra}{/if}" width="700" height="900" frameborder="0"></iframe>
			</td>
			<td>
<iframe src="/mapper/blank.html" width="210" height="900" frameborder="1" name="browseframe"></iframe>
			</td>
		</tr>
	</tbody>
</table>

<hr/>
<p><b>Centisquare Depth Key</b>: <img src="http://{$static_host}/img/depthkey.png" width="400" height="20" style="position:absolute;clip:rect(0px,400px,20px,40px);"/><br/><br/>
To enable centisquare layer click <img src="/mapper/img/layer-switcher-maximize.png" width="14" height="14"/> in top right of map, and select "Centisquare Coverage" in popup.<br/>
<small>Click [+] above map several times to increase overlay opacity.</small><br/><br/>
<small>NOTE: The centisquare coverage layer only includes images classified as 'Geograph', plotted with a 6 figure grid-reference or above.</small></p>



{include file="_std_end.tpl"}
