{assign var="page_title" value="Donate to Geograph Project"}

{include file="_std_begin.tpl"}

<h2>Donate and Support the Geograph Project</h2>

<p>Geograph is a community project - registered as a Charity in England and Wales (No. 1145621) with the UK Charities Commission. Keeping the project running costs money; for the servers, that keep the site online 24 hours a day; for backups, to make sure all the content is safe; and for the administration to keep everything coordinated.</p>


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
		<a href="https://mydonate.bt.com/donation/v4/chooseAmount.html?charity=61687&sourcePage=charityPage"><img src="https://mydonate.bt.com/images/Donate-Now-button.gif" height=48></a> 
		via <b>{external href="https://mydonate.bt.com/charities/geograph" text="BT MyDonate"}</b> - <small>Preferred</small> <br><br>

		Click the link above to go to the Geograph page on MyDonate, and make a donation from there. Via BT MyDonate, we are charged <b>no fees</b> (unlike PayPal) for receiving donations. You can also apply Gift Aid to increase the value of your donation.
		<br/><br/>
	</li>

	<li>
		Alternatively we can also accept donations via <b>PayPal</b>:<br>
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

</ul>

<p align="center" style="font-size:0.9em;"><span class="nowrap"><i>Geograph<sup>&reg</sup> Britain and Ireland</i> is a project by <a href="/article/About-Geograph-page">Geograph Project Limited</a></span>, <span class="nowrap">a Charity Registered in England and Wales, no 1145621</span>. <span class="nowrap">Company no 7473967</span>. The registered office is <span class="nowrap">Dept 1706, 43 Owston Road, Carcroft, Doncaster, South Yorkshire. DN6 8DA.</span> </p>

{include file="_std_end.tpl"}
