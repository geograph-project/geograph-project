{include file="_std_begin.tpl"}
{dynamic}
{if $image}

<div style="float:right; width:250px" class="{if $image->isLandscape()}photolandscape{else}photoportrait{/if}">
  <div class="img-shadow"><a href="/photo/{$image->gridimage_id}" target="_blank">{$image->getThumbnail(213,160)}</a></div>
  <div class="caption"><b>{$image->title|escape:'html'}</b></div>
  
</div>

<h2>‘Bag the Most Grid Squares’ &amp; <br/> ‘Best Photograph’ entry form</h2>

{if $saved}
	<h2>Submission Complete!</h2>
	<p>Thank you very much - your photo entered for the competition</p>
	<p>Your photo has identification number [<a href="/photo/{$image->gridimage_id}">{$image->gridimage_id}</a>]</p>

	<p><a title="submit another photo" href="/submit.php">Click here to submit a new photo...</a></p>

{else}

<form enctype="multipart/form-data" action="{$script_name}" method="post" name="theForm"  style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;">
<input type="hidden" name="id" value="{$image->gridimage_id}"/>
<input type="hidden" name="code" value="{$code}"/>
<p>By completing this page you are entering the selected picture "{$image->title|escape:'html'}", into the above mentioned competitions.</p>


<p>In addition to the Creative Commons Licence, we ask you to confirm:
<ul>
	<li>That you are the photographer of the above image</li>
</ul>	
 <br style="clear:both"/>
<ul style="margin-top:0px">
	<li>You are submitting the image to Geograph for the purposes of this competition,<br/> and agree to {external  href="http://schools.geograph.org.uk/help/terms" text="website terms of use" target="_blank"} (opens in a new window)<br/><br/></li>

	<li>And have read and agree to the specific competition terms, as detailed below:
	 <div style="width:100%; height:200px; position:relative; border:1px solid black; padding: 3px; overflow:auto; font-size:0.8em; background-color:white">
		{assign var="inline" value="1"}

		{include file="static_competition_terms.tpl"}
	 </div>
	 <div style="text-align:right">{external href="http://`$http_host`/help/competition_terms" text="open in new window" target="_blank"}</div>
	 <br/>
	</li>

</ul>

<p>If you agree with these terms, click "I agree" and your image will be
	entered into the competitions.<br /><br />
	<input style="background-color:pink;" type="button" value="&lt; Back" onclick="window.location.href='/submit.php'"/>
	<input style="background-color:lightgreen; width:200px" type="submit" name="finalise" value="I AGREE &gt;" onclick="autoDisable(this);"/> and <b>Good Luck!</b>
</p>

</form>

 <br style="clear:both"/>
{/if}

{else}

<p class="error" style="color:#990000;font-weight:bold;">Please enter the Competition Code to continue...</p>

<form enctype="multipart/form-data" action="/submit_competition.php" method="get" name="theForm">


<div class="interestBox" style="border:2px solid black">
	<img src="http://{$static_host}/templates/basic/img/hamster.gif" width="161" height="174" align="right"/>
	<h2><span style="color:#000066">‘Bag the Most Grid Squares’ <br/>
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &amp; ‘Best Photograph’</span></h2>
	
	<p>If you wish to enter the Mapping News competitions, simply enter the provided code into the box below</p>

	<p>Competition Code: <input type="hidden" name="id" value="{$id}"/> <input type="text" name="code" size="5"/> 
	<input type="submit" value="Go"/>
	</p>
	<hr/>
	<small>Opening Dates: <i>between 1 April 2008 and 30 September 2008</i>, <br/>
	Note: <i>Entry is only open to UK permanent residents aged 18 and under in full time education</i> ({external href="http://`$http_host`/help/competition_terms" text="full terms" target="_blank"}).</small>
</div>
</form>

{/if}
{/dynamic}
{include file="_std_end.tpl"}

