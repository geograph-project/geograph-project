{assign var="page_title" value="Donate to Geograph Project"}

{include file="_std_begin.tpl"}

<h2>Donate and Support the Geograph Project</h2>


<p>Geograph is a community project - registered as a Charity in England and Wales (No. 1145621) with the UK Charities Commission. Keeping the project running costs money; for the servers, that keep the site online 24 hours a day; for backups, to make sure all the content is safe; and for the administration to keep everything coordinated.</p>

<p>Please consider supporting us:</p>
<style>{literal}
ul.methods {
	border-top:1px solid silver;
        padding-top:10px;
}
ul.methods li {
	padding-bottom:10px;
	border-bottom:1px solid silver;
	margin-bottom:10px;
}
ul.methods li img {
	vertical-align:middle;
}
ul.methods li div {
	position:relative;width:120px;float:left;
	padding:10px;
}
{/literal}</style>
<ul class="methods">
	<li>
		<a href="https://mydonate.bt.com/donation/donate.html?charity=geograph"><img src="https://mydonate.bt.com/images/Donate-Now-button.gif" height=24></a> 
		via <b>{external href="https://mydonate.bt.com/charities/geograph" text="BT MyDonate"}</b> - <small>Preferred</small> <br><br>

		<div>
			<a href="https://mydonate.bt.com/charities/geograph"><img src="https://mydonate.bt.com/images/creditcards.gif" width=110></a>
		</div>		

		Click the link above to go to the Geograph page on MyDonate, and make a donation from there. Via BT, we are charged <b>no fees</b> (unlike Paypal) for receiving donations. You can also apply Gift Aid to increase the value of your donation.
		<br/><br/>

		<small>Note: If you first {external href="https://mydonate.bt.com/login.html?j_action=registration" text="create an account"}, can make a regular monthly donation. Use the dropdown on "Step 1" of the donation process to select 'regular', read more in the {external href="http://www.btplc.com/mydonate/Help/Helpguides/Fordonors/Donor.aspx" text="help"} (click the 'Donations' section). <br/>
		(You can make a one-off donation by credit/debit card without having to register, if you like)</small>
		<br/><br/>
	</li>

	<li>
		via <b>PayPal</b><br>
		<div style="width:250px">
			Single Donation
			<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
				<input type="hidden" name="cmd" value="_donations">
				<input type="hidden" name="business" value="paypal@geograph.org.uk">
				<input type="hidden" name="lc" value="US">
				<input type="hidden" name="item_name" value="Geograph Project">
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
	</li>

	<li>
		See {external text="our page on <b>Give as you Live</b>" href="https://www.giveasyoulive.com/join/geograph-project-limited"}, for how can raise free donations simply by shopping online.
	</li>

	<li>Can also donate via {external href="https://www.cafonline.org/" text="Charities Aid Foundation (CAF)"}</li>

	<li>{mailto address="treasurer@geograph.org.uk" text="Contact the Treasurer" encode="javascript"} if you wish to set up a standing order.</li>
</ul>

<p>Thank you!</p>

<hr/>

<p>In order to make the most of your donation we rely on support from the following organizations:</p>

<table cellspacing=0 cellpadding=10 border=0> 
	<tr>
		<td align="center">
			<a title="Geograph sponsored by Ordnance Survey" href="https://www.ordnancesurvey.co.uk/education/" rel="nofollow"><img src="{$static_host}/img/os-logo-p85.png" width="85" height="67" alt="Ordnance Survey"/></a>
		</td>
		<td>
			{external href="https://www.ordnancesurvey.co.uk/education/" text="Ordnance Survey"}<br/><br/>
			- National Mapping Agency of Great Britain. OS Sponsor Geograph.
		</td>
	</td>
        <tr>
                <td align="center">
			<a href="http://www.livetodot.com/hosting/" rel="nofollow"><img src="{$static_host}/img/livetodot-logo.png" style="padding:10px;" alt="Livetodot Logo"/></a>
		</td>
		<td>
			{external href="http://www.livetodot.com/hosting/" text="Livetodot Hosting"}<br/><br/>
			- Server Co-location services. Home of the Geograph servers.
                </td>
        </td>
        <tr>
                <td align="center">
			<a href="http://www.woc.co.uk/" rel="nofollow"><img src="{$static_host}/img/woc.jpg"/></a>
                </td>
                <td>
 			{external href="http://www.woc.co.uk/" text="World of Computers"}<br/><br/>
			- high quality custom built servers. This site is powered by servers built by WOC. 
        </td>
</table>

<p><small>Want your name here? Get in <a href="/contact.php">Contact</a>!</small></p>

<hr/>

<p align="center" style="font-size:0.9em;"><span class="nowrap"><i>Geograph<sup>&reg</sup> Britain and Ireland</i> is a project by <a href="/article/About-Geograph-page">Geograph Project Limited</a></span>, <span class="nowrap">a Charity Registered in England and Wales, no 1145621</span>. <span class="nowrap">Company no 7473967</span>. The registered office is <span class="nowrap">49 Station Road, Polegate, East Sussex, BN26 6EA.</span> </p>

{include file="_std_end.tpl"}
