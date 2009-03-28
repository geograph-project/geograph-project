{include file="_std_begin.tpl"}

<form enctype="multipart/form-data" action="{$script_name}" method="post" name="theForm" onsubmit="if (this.imageclass) this.imageclass.disabled=false;" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;">

<p>Thank you very much - your photo has now been added to grid square 
<u">TQ5050</u>.</p>
<p>Your photo has identification number [<u>123456</u>]</p>


</form> 

<br/><br/>

<form enctype="multipart/form-data" action="/submit_competition.php" method="get" name="theForm">


<div class="interestBox" style="border:2px solid black">
	<img src="http://{$static_host}/templates/basic/img/hamster.gif" width="161" height="174" align="right"/>
	<h2><span style="color:#000066">‘Bag the Most Grid Squares’ <br/>
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &amp; ‘Best Photograph’</span></h2>
	
	<p>If you wish to enter the Mapping News competitions, simple enter the provided code into the box below</p>

	<p>Competition Code: <input type="hidden" name="id" value="123456"/> <input type="text" name="code" size="5"/> 
	<input type="submit" value="Go"/>
	</p>
	<hr/>
	<small>Opening Dates: <i>between 1 April 2008 and 30 September 2008</i>, <br/>
	Note: <i>Entry is only open to UK permanent residents aged 18 and under in full time education</i> ({external href="/help/competition_terms" text="full terms" target="_blank"}).</small>
</div>
</form>
<br/><br/>
<p> or <u>Click here to submit a new photo...</u></p>
<br/>
{include file="_std_end.tpl"}

