{assign var="page_title" value="Convertor Test"}
{include file="_std_begin.tpl"}

{dynamic}
	 <H3>Easting/Northing &lt;=&gt; WGS84 Lat/Long Conversion Tester</H3> 
	 <FORM ACTION="{$script_name}"> 
		<TABLE CELLPADDING="3" CELLSPACING="0"> 
		  <TR> 
			 <TD><INPUT TYPE="RADIO" NAME="datum" VALUE="osgb36"
				ALT="{if $datum == "osgb36"}" CHECKED="CHECKED{/if}"></TD> 
			 <TD>osgb36</TD>
			 <TD ALIGN="RIGHT">easting:</TD> 
			 <TD><INPUT TYPE="TEXT" NAME="e" SIZE="10" VALUE="{$e}"></TD> 
			 <TD ROWSPAN="2" ALIGN="CENTER"><INPUT TYPE="SUBMIT" NAME="To"
				VALUE="Convert &gt;"><BR><INPUT TYPE="SUBMIT" NAME="From" VALUE="&lt; Convert">
				</TD> 
			 <TD ALIGN="RIGHT">lat</TD> 
			 <TD><INPUT TYPE="TEXT" NAME="lat" SIZE="10" VALUE="{$lat}"></TD> 
		  </TR> 
		  <TR> 
			 <TD><INPUT TYPE="RADIO" NAME="datum" VALUE="irish" ALT="{if $datum == "irish"}" CHECKED="CHECKED{/if}"></TD> 
			 <TD>irish grid</TD>
			 <TD ALIGN="RIGHT">northing:</TD> 
			 <TD><INPUT TYPE="TEXT" NAME="n" SIZE="10" VALUE="{$n}"></TD> 
			 <TD ALIGN="RIGHT">long</TD> 
			 <TD><INPUT TYPE="TEXT" NAME="long" SIZE="10" VALUE="{$long}"></TD> 
		  </TR>
		  <TR> 
			 <TD><INPUT TYPE="RADIO" NAME="datum" VALUE="itm" ALT="{if $datum == "itm"}" CHECKED="CHECKED{/if}"></TD> 
			 <TD>itm</TD>
			 <TD COLSPAN="4">&nbsp;</TD> 
		  </TR>
		  <TR>
			 <TD COLSPAN="2" ALIGN="CENTER"><INPUT TYPE="BUTTON"
				VALUE="Clear"></TD>
			 <TD ALIGN="CENTER" COLSPAN="2"><INPUT TYPE="BUTTON"
				VALUE="Clear"></TD>
			 <TD>&nbsp;</TD>
			 <TD COLSPAN="2" ALIGN="CENTER"><INPUT TYPE="BUTTON"
				VALUE="Clear"></TD>
		  </TR>
		  <TR>
			 <TD COLSPAN="2" ALIGN="RIGHT"><INPUT TYPE="CHECKBOX" VALUE="1"
				NAME="usehermert" ALT="{if $usehermert}" CHECKED="CHECKED{/if}"></TD>
			 <TD COLSPAN="6">Use Hermert Translation for Irish Conversions? (Recommended)</TD>
			 
		  </TR> 
		</TABLE></FORM>
		<p>{$querytime}</p>
{/dynamic}    
{include file="_std_end.tpl"}
