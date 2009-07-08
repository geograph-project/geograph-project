{assign var="page_title" value="Great Britain Map"}
{include file="_std_begin.tpl"}

<table>
	<tbody>
		<tr>
			<td>
<iframe src="/mapper/?inner&amp;t={$token}{if $extra}&amp;{$extra}{/if}" width="700" height="900" frameborder="0"></iframe>
			</td>
			<td>
<iframe src="/mapper/blank.html?v2" width="210" height="900" frameborder="1" name="browseframe"></iframe>
			</td>
		</tr>
	</tbody>
</table>

<hr/>
<p><b>Centisquare Depth Key</b>: <img src="/img/depthkey.png" width="400" height="20"/><br/>
To enable centisquare layer click <img src="/mapper/img/layer-switcher-maximize.png" width="18" height="18"/> in top right of map, and select "centisquare coverage" in popup.<br/>
<small>Click [+] above map several times to increase overlay opacity.</small><br/><br/>
<small>NOTE: The centisquare coverage layer only includes images classified as 'Geograph', plotted with a 6 figure grid-reference or above.</small></p>



{include file="_std_end.tpl"}
