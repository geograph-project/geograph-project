{assign var="page_title" value="Donate to Geograph Project"}

{include file="_std_begin.tpl"}

<h2>Donate and Support the Geograph Project</h2>


<p>Geograph is a community project - registered as a Charity in England and Wales (No. 1145621) with the UK Charities Commission. Keeping the project running costs money; for the servers, that keep the site online 24 hours a day; for backups, to make sure all the content is safe; and for the administration to keep everything coordinated.</p>

<p>Please consider supporting us:</p>
<style>{literal}
ul.methods {
	--border-top:1px solid silver;
        padding-top:10px;
}
ul.methods li {
	padding-bottom:10px;
	--border-bottom:1px solid silver;
	margin-bottom:20px;
}
ul.methods li img {
	vertical-align:middle;
}
ul.methods li div div {
	position:relative;width:170px;float:left;
	padding:10px;
}
a.btn {
	background-color:#000066;
	padding:10px;
	border-radius:10px;
	margin:4px;
	color:white;
	text-decoration:none;
	white-space:nowrap;
}
{/literal}</style>
<ul class="methods">
	<li><p>
		<b>Charities Aid Foundation</b> is our preferred donation processor.</p><p><a href="https://cafdonate.cafonline.org/18714" class=btn onclick="return startDonation(this)" target=_blank>Start Donation</a></p><p>or {newwin href="https://cafdonate.cafonline.org/18714" text="Open in a new Window"}</p>

		<iframe id="IframeDonate" name="IframeDonate" frameborder="0" scrolling="no" data-src="https://cafdonate.cafonline.org//Widget/18714?fix=0" width="460px" height="600px" style="padding: 0px; margin: 0px; border:2px solid #e4e4fc; overflow: hidden; width: 460px; height: 600px; display:none"></iframe>
		<script>{literal}
		function startDonation(that) {
			if (window.outerWidth && window.outerWidth < 600) {
				//if a small window, open directly
				return true;
			}
			var iframe = document.getElementById('IframeDonate');
			if (iframe.dataset)
				iframe.src = iframe.dataset.src;
			else
				iframe.src = iframe.getAttribute('data-src');
			iframe.style.display='';
			that.style.display = 'none';
			return false;
		}
		{/literal}</script>


	</li>

	<li>    Alternatively can accept direct donations via <b>{newwin href="https://www.paypal.com/gb/fundraiser/charity/126560" text="PayPal Giving Fund"}</b>

		or <a href="javascript:void(show_tree('ppay'));" id="hideppay">pay via paypal directly</a>

		<div id="showppay" style="display:none">

		<div style="width:250px">
			Single Donation
			<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
				<input type="hidden" name="cmd" value="_donations">
				<input type="hidden" name="business" value="paypal@geograph.org.uk">
				<input type="hidden" name="lc" value="US">
				{dynamic}<input type="hidden" name="item_name" value="Donation via {$self_host} id:{$hid}">{/dynamic}
				<input type="hidden" name="no_note" value="0">
				<input type="hidden" name="currency_code" value="GBP">
				<input type="hidden" name="bn" value="PP-DonationsBF:btn_donateCC_LG.gif:NonHostedGuest">
				<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
			</form>	
			<small>Click the button above to make a one-off donation via Paypal. Can donate without creating a Paypal account.</small>
		</div>
		<div style="width:80px">
			- or -
		</div>
		<div style="width:250px">
			Monthly Donation
			<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
				<input type="hidden" name="cmd" value="_xclick-subscriptions">
				<input type="hidden" name="business" value="paypal@geograph.org.uk">
				<input type="hidden" name="lc" value="US">
				<input type="hidden" name="no_note" value="1">
				<input type="hidden" name="src" value="1">
				<input type="hidden" name="currency_code" value="GBP">
				<input type="hidden" name="bn" value="PP-SubscriptionsBF:btn_subscribeCC_LG.gif:NonHostedGuest">
				<input type="hidden" name="on0" value="">
				<select name="os0">
					<option value="Option 1">Option 1 : &pound;3.00GBP - monthly</option>
					<option value="Option 2">Option 2 : &pound;5.00GBP - monthly</option>
					<option value="Option 3">Option 3 : &pound;10.00GBP - monthly</option>
					<option value="Option 4">Option 4 : &pound;25.00GBP - monthly</option>
				</select>
				<input type="hidden" name="currency_code" value="GBP">

				<input type="hidden" name="option_select0" value="Option 1">
				<input type="hidden" name="option_amount0" value="3.00">
				<input type="hidden" name="option_period0" value="M">
				<input type="hidden" name="option_frequency0" value="1">

				<input type="hidden" name="option_select1" value="Option 2">
				<input type="hidden" name="option_amount1" value="5.00">
				<input type="hidden" name="option_period1" value="M">
				<input type="hidden" name="option_frequency1" value="1">

				<input type="hidden" name="option_select2" value="Option 3">
				<input type="hidden" name="option_amount2" value="10.00">
				<input type="hidden" name="option_period2" value="M">
				<input type="hidden" name="option_frequency2" value="1">

				<input type="hidden" name="option_select3" value="Option 4">
				<input type="hidden" name="option_amount3" value="25.00">
				<input type="hidden" name="option_period3" value="M">
				<input type="hidden" name="option_frequency3" value="1">

				<input type="hidden" name="option_index" value="0">
				<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
			</form>
			<small>Click Donate to make a regular recurring donation. You can cancel at any time via the Paypal website. PayPal account required.</small>
		</div>
		<br style="clear:both"/>
		</div>
	</li>

	<li>
		See {external text="our page on <b>Give as you Live</b>" href="https://www.giveasyoulive.com/join/geograph-project-limited"}, for how can raise free donations simply by shopping online.
	</li>

	<li>
		Or can setup <b>{external href="https://smile.amazon.co.uk/gp/chpf/about/ref=smi_se_rspo_laas_aas" text="Amazon Smile"}</b> and Amazon will donate to a charity of your choice for every purchase you make. 
			{external href="https://smile.amazon.co.uk/gp/chpf/homepage?q=Geograph&orig=%2F&ie=UTF-8" text="Geograph"} is registered there. 
	</li>

	<li>{mailto address="treasurer@geograph.org.uk" text="Contact the Treasurer" encode="javascript"} if you wish to set up a <b>standing order</b>.</li>

	<li>Also can a leave a <b>gift in your will</b>. <a href="javascript:void(show_tree('gift'));" id="hidegift">Read More...</a>

                <div id="showgift" style="display:none;padding-left:20px"><br><br>
			After taking care of your family and friends, please consider leaving a gift to the
			Geograph Project in your will. The Geograph Project receives no public funding. We
			are completely reliant on the generosity of people like you to enable us to carry on
			doing what we're really proud of doing: helping people explore the geography and
			history of Britain and Ireland. You don't have to be wealthy to leave a gift in your will.
			Whatever the amount, big or small, we're extremely grateful for any gift left to the
			Geograph Project.<br>
			<br>
			Leaving a gift in your will is simple and easy to do<br>
			<br>
			If you are considering writing or updating your will, it is best to use a qualified
			solicitor or will writer. To leave a gift to the Geograph Project, you just need to give
			your professional adviser our charity name, postal address and registered charity
			number along with the amount, or percentage of your estate, that you would like to
			give us. Our details are:<br>
			<br>
			Geograph Project Ltd<br>
			Dept 1706, 43 Owston Road, Carcroft, Doncaster, South Yorkshire, DN6 8DA.<br>
			<br>
			Registered charity in England and Wales (1145621)<br>
			<br>
			If you do include the Geograph Project in your will you don't have to tell us and you
			can always change your mind later. But we'd be delighted to {mailto address="treasurer@geograph.org.uk" text="hear from you" encode="javascript"} and we
			will treat any information you share with us in strictest confidence.
		</div>

</ul>

<p>Thank you!</p>

<hr/>

<p>In order to make the most of your donation we rely on support from the following organizations:</p>


<div style="display: inline-block; text-align:center; padding:20px">
<a title="Geograph sponsored by Ordnance Survey" href="https://www.ordnancesurvey.co.uk/education/" rel="nofollow"><img src="{$static_host}/img/os-logo-p85.png" width="85" height="67" alt="Ordnance Survey"/></a>
<br/><br/>
{external href="https://www.ordnancesurvey.co.uk/education/" text="Ordnance Survey"}
<br/><br/>
National Mapping Agency of Great Britain.
<br/>
OS Sponsor Geograph.
</div>

<div style="display: inline-block; text-align:center; padding:20px">
<a href="https://www.tiger-computing.co.uk/" rel="nofollow"><img src="{$static_host}/img/tiger-logo-tl.png" width="200"/></a>
<br/><br/>
{external href="https://www.tiger-computing.co.uk/" text="Tiger Computing"}
<br/><br/>
Linux Support Services.
<br/>
Tiger keep our servers running in tip-top condition.
</div>

<br style="clear:both"/>




<p><small>Want your name here? Get in <a href="/contact.php">Contact</a>!</small></p>

<br/>
<hr/>

<p align="center" style="font-size:0.9em;"><i class="nowrap">Geograph<sup>&reg</sup> Britain and Ireland</i> is a project <span 
class="nowrap">by <a href="/article/About-Geograph-page">Geograph Project Limited</a></span>, a Charity <span 
class="nowrap">Registered in England and Wales</span>, no <b>1145621</b>. <span class="nowrap">Company no 7473967</span>.<br>
The registered office is <span class="nowrap">Dept 1706, 43 Owston Road,</span> Carcroft, Doncaster, South Yorkshire. DN6 8DA.</p>

{include file="_std_end.tpl"}
