{include file="_basic_begin.tpl"}
{dynamic}

<TABLE cellSpacing=0 cellPadding=4 width="100%" style="background-color:#000066; font-family:Georgia">
  <TBODY>
  <TR>
    <TD>&nbsp;</TD>
    <TD><A href="http://{$http_host}/"><IMG height=74 src="http://{$http_host}/templates/basic/img/logo.gif" width=257 border=0></A></TD>
    <TD vAlign=top align=center><A href="http://{$http_host}/"><font color=#ffffff size=+2>{$http_host}</FONT></A><BR>
       <FONT face=Georgia color=#ffffff><I>The Geograph British Isles project aims to collect a geographically representative<BR> photograph for every square kilometre of the British Isles and you can be part of it.</I></FONT></TD>
    <TD>&nbsp;</TD></TR>
</TABLE>  

<div style="padding:10px">
	<form enctype="multipart/form-data" action="{$script_name}" method="post" name="theForm" onsubmit="if (this.imageclass) this.imageclass.disabled=false;" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;">
		
		<div style="float:right">Logged in as {$user->realname}</div>

		<h2>Geograph --&gt; Uploader v0.1</h2>

		<div style="overflow:auto; height:200px; width:100%; border: 1px solid red">
		{assign var="thumnbail" value="photo:thumbnail"}
		{assign var="imgsrc" value="photo:imgsrc"}
		{foreach from=$pData item=image}
			{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
			<div style="float:left; height:180px; width:180px; border:1px solid green; margin-right:15px; text-align:center; background-color:{$bgcolor}">
				{$image.title}
				<div style="width:100px; height:100px;">
					<img src="{$image.$thumnbail}?size=100"/>
				</div>
				<input type=checkbox name="{$image.$imgsrc}?size=640" checked/> Upload?<br/>
			</div>
		{/foreach}

		</div>

		<iframe src="about:blank" width="500" height="300"></iframe>


		<div style="position:relative; overflow:auto; height:400px; width:100%;">

			<h2>Confirm image rights</h2>

			<p>
			Because we are an open project we want to ensure our content is licensed
			as openly as possible and so we ask that all images are released under a {external title="Learn more about Creative Commons" href="http://creativecommons.org" text="Creative Commons" target="_blank"}
			licence, including accompanying metadata.</p>

			<p>With a Creative Commons licence, the photographer <b>keeps the copyright</b> but allows 
			people to copy and distribute the work provided they <b>give credit</b>.</p>

			<p>Since we want to ensure we can use your work to fund the running costs of
			this site, and allow us to create montages of grid images, we ask that you
			allow the following</p>

			<ul>
			<li>The right to use the work commercially</li>
			<li>The right to modify the work to create derivative works</li>
			</ul>

			<p>{external title="View licence" href="http://creativecommons.org/licenses/by-sa/2.0/" text="Here is the Commons Deed outlining the licence terms" target="_blank"}</p>

			{assign var="credit" value=$user->credit_realname}
			{assign var="credit_default" value=0}
			{include file="_submit_licence.tpl"}

			<p>If you agree with these terms, click "I agree" and your images submitted to Geograph.<br />
			<input type="button" value="Close Window" onclick="location.href='minibrowser:close'"/>
			<input style="background-color:lightgreen; width:200px" type="submit" name="finalise" value="I AGREE &gt;" onclick="autoDisable(this);{if $user->stats.images && $user->stats.images > 100 && $last_imagetaken}autoDisable(this.form.finalise[0]);{/if}"/>
			</p>
		</div>

	</form>
</div>
{/dynamic}

</body>
</html>
