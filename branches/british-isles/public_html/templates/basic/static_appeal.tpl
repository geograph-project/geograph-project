{assign var="page_title" value="Donate to Geograph Project"}

{include file="_std_begin.tpl"}

<h2>Donate and Support the Geograph Project</h2>

<p>Geograph is a community project - registered as a Charity in England and Wales (No. 1145621) with the UK Charities Commission. Keeping the project running costs money; for the servers, that keep the site online 24 hours a day; for backups, to make sure all the content is safe; and for the administration to keep everything coordinated.</p>


<style>{literal}
ul.methods {
        padding-top:10px;
}
ul.methods li {
	padding-bottom:10px;
	margin-bottom:10px;
}
ul.methods li img, input[type=image] {
	vertical-align:middle;
}
ul.methods li div {
	position:relative;width:120px;float:left;
	padding:10px;
}
{/literal}</style>
<ul class="methods">
	<li><b>{external href="https://mydonate.bt.com/charities/geograph" text="BT MyDonate"}</b></b>: - <small>Preferred</small> <br><br>

		<a href="https://mydonate.bt.com/donation/v4/chooseAmount.html?charity=61687&sourcePage=charityPage"><img src="https://mydonate.bt.com/images/Donate-Now-button.gif" height=48 align></a> 

		Via BT MyDonate, we are charged <b>no fees</b> (unlike PayPal) for receiving donations. You can also apply Gift Aid to increase the value of your donation.
	</li>
</ul>

or

<ul class="methods">
	<li>
		<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
				<b>PayPal</b>:

				<input type="hidden" name="cmd" value="_donations">
				<input type="hidden" name="business" value="paypal@geograph.org.uk">
				<input type="hidden" name="lc" value="US">
				<input type="hidden" name="item_name" value="Appeal Donation via {$self_host}">
				<input type="hidden" name="no_note" value="0">
				<input type="hidden" name="currency_code" value="GBP">
				<input type="hidden" name="bn" value="PP-DonationsBF:btn_donateCC_LG.gif:NonHostedGuest">
				<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">

			Click the button to make a one-off donation via Paypal. Can donate without creating a PayPal account.
		</form>	
	</li>
</ul>

or

<ul class="methods">
	<li><b>Facebook</b>:
		Visit our facebook page: {external href="https://www.facebook.com/geograph.org.uk" text="facebook.com/geograph.org.uk"} and click the blue 'Donate' button
	</li>
</ul>


<hr>

<p align="center" style="font-size:0.9em;"><span class="nowrap"><i>Geograph<sup>&reg</sup> Britain and Ireland</i> is a project by <a href="/article/About-Geograph-page">Geograph Project Limited</a></span>, <span class="nowrap">a Charity Registered in England and Wales, no 1145621</span>. <span class="nowrap">Company no 7473967</span>. The registered office is <span class="nowrap">Dept 1706, 43 Owston Road, Carcroft, Doncaster, South Yorkshire. DN6 8DA.</span> </p>

{include file="_std_end.tpl"}
