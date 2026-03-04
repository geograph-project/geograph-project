{assign var="page_title" value="Donate to Geograph Project"}

{include file="_std_begin.tpl"}

<h2>Supporting Geograph through donations</h2>

<div style="width:80ch;max-width:100%; margin:auto;">
<img src="/img/geograph-logo.svg" alt="Geograph Britain and Ireland" style="width:300px; height:auto; max-width:100%; background:#000066; padding:10px; display: block; margin:auto;">
<p>
Geograph is a community project - registered as a Charity in England and Wales (No. 1145621) with the UK Charities Commission.
</p>
<p>
Keeping the project running costs money; for the servers, that keep the site online 24 hours a day; for backups, to make sure all the content is safe; and for the administration to keep everything coordinated. The Geograph Project receives no public funding. We	are completely reliant on the generosity of people like you to enable us to carry on doing what we're really proud of doing: helping people explore the geography and history of Britain and Ireland.
</p>
<p>
We are very grateful to all Geograph users who donate, whether these are one off donations, irregular donations or regular monthly donations.
</p>

</div>


<div class="tabs-container" style="max-width:80ch; margin:auto;">

{*Supporting Geograph tab*}
<a id="tab1" href="#tab1" class="tab-link">Supporting Geograph</a>
<div class="tab-content">
<h3>Supporting Geograph</h3>
<div class="donate-para">
<h4>Ways to donate</h4>
<p>There are several ways you can consider supporting us. Our preferred method is by paying <a href="#tab2">directly into our bank account</a> as this incurs no fees, therefore ensuring that every penny you donate goes to supporting the Geograph Project. Alternatively, you can donate via the <a href="#tab3">Charities Aid Foundation</a> or via <a href="#tab3">PayPal</a>.</p>
</div>

<div class="donate-para">
<h4>Gift aid</h4>
<p>If you are a UK taxpayer, you can make every £1 you give – or have given – worth 25p more by completing a <a href="#tab4">Gift Aid declaration</a>.</p>
</div>

<div class="donate-para">
<h4>Queries</h4>
<p>If you have any queries or would like any additional help regarding donations, please get in touch with the {mailto address="treasurer@geograph.org.uk" text="Contact the Treasurer" encode="javascript"} who will try to assist.</p>
</div>
</div>

{*Bank transfer tab*}
<a id="tab2" href="#tab2" class="tab-link">Bank transfer</a>
<div class="tab-content">
<h3>Bank transfer</h3>
<div class="donate-para">
Making a donation directly to our bank account incurs no fees so every penny you donate goes to supporting the Geograph Project, and is our preferred route for donations.
</div>
<div class="donate-para">We welcome one off donations and regular donations via standing order into our account via the details below.</div>
<div class="donate-bank-details">
<p><b>Account Name:</b> Geograph Project Limited</p>
<p><b>Sort Code:</b> 20-67 90</p>
<p><b>Account No:</b> 43479838</p>
</div>


<div class="donate-para">
If you are a UK taxpayer, you can make every £1 you give (or have given) worth 25p more by completing a Gift Aid declaration.
</div>
</div>


{*Alternative methods tab*}
<a id="tab3" href="#tab3" class="tab-link">Alternative methods</a>
<div class="tab-content">
<h3>Other donation routes</h3>
<div class="donate-para">We can also accept donations via PayPal and Charities Aid Foundation. However, these routes incur charges, so not all of your contribution will go to supporting Geograph.</div>


<div class="donate-alternative-boxout"><p>If you are a UK taxpayer, you can make every £1 you give (or have given) worth 25p more by completing a Gift Aid declaration.</p><p>If you are donating (or planning to donate) through PayPal, please complete a Gift Aid form via the tab above. For Charities Aid Foundation donations via their platform they will apply for Gift Aid on our behalf (although a small charge is made for this service).</p></div>


<h4>Charities Aid Foundation</h4>
<p>Charities Aid Foundation operate an online donation platform. If you are a UK taxpayer, Gift Aid can be added to your donation via their donation platform.</p>

<div class="donate-caf">
<a href="https://cafdonate.cafonline.org/18714" class=btn onclick="return startDonation(this)" target=_blank>Start Donation</a>
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

<br style="clear:both;"/>

<p>{newwin href="https://cafdonate.cafonline.org/18714" text="or open in a new Window"}</p>

</div>
<h4>PayPal</h4>
<p>You can carry out one time or recurring donations via PayPal using the options below. Alternatively you can open the PayPal donation platform 
{newwin href="https://www.paypal.com/gb/fundraiser/charity/126560" text="in a new window"}.</p>


		<div style="width:250px">
			<h5>Single Donation</h5>
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



		</div>
		<div style="width:250px">
			<h5>Monthly Donation</h5>
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

{*Gift aid tab*}
<a id="tab4" href="#tab4" class="tab-link">Gift aid</a>
<div class="tab-content">
<h3>Gift aid</h3>
<div class="donate-para">
{external text="Gift aid is a UK Government scheme" href="https://www.gov.uk/donating-to-charity/gift-aid"} allowing charities to claim back the basic rate of tax from donations. This means that for every £1 donated by UK tax payers, we can obtain an additional 25p from the government (at no cost to the donor).
</div>

<div class="donate-gift-aid">
For your donations to be eligible for Gift Aid you must pay an amount of income tax and/or capital gains tax for each tax year (6 April one year to 5 April the next) that is at least equal to 
the tax that all charities and Community Amateur Sports Clubs reclaim on your donations in the same tax year.
</div>

<div class="donate-para">If you are donating (or planning to donate) either via our bank account or through PayPal, and you are a UK tax payer, we would ask you to download and complete the form below. Forms should be sent to the {mailto address="treasurer@geograph.org.uk" text="Geograph Project Treasurer" encode="javascript"} to be processed. Charities Aid Foundation will apply for Gift Aid on our behalf for any donations via their platform (although a small charge is made for this service).
</div>

<div class="donate-giftaid-download">
<a href="https://media.geograph.org.uk/files/17d63b1625c816c22647a73e1482372b/GiftAidDeclaration_GPL.pdf">Download the Gift Aid form</a>
</div>
</div>

{*Bequests tab*}
<a id="tab5" href="#tab5" class="tab-link">Bequests</a>
<div class="tab-content">
<h3>Leaving gifts in your will</h3>
<div class="donate-para">
After taking care of your family and friends, please consider leaving a gift to the	Geograph Project in your will. Whatever the amount, big or small, we're extremely grateful for any gift left to the Geograph Project.
</div>
<div class="donate-para">
If you are considering writing or updating your will, it is best to use a qualified	solicitor or will writer. To leave a gift to the Geograph Project, you will need to give your professional adviser our charity name, postal address and registered charity	number along with the amount, or percentage of your estate, that you would like to give us.
</div>

<div class="donate-bank-details">
Geograph Project Ltd
Dept 1706, 43 Owston Road, Carcroft, Doncaster, South Yorkshire, DN6 8DA.
Registered charity in England and Wales (1145621)<br>
</div>
<div class="donate-para">
If you do include the Geograph Project in your will you don't have to tell us and you	can always change your mind later. But we'd be delighted to {mailto address="treasurer@geograph.org.uk" text="hear from you" encode="javascript"} and we will treat any information you share with us in strictest confidence.
</div>
</div>

{*Other tab*}
<a id="tab6" href="#tab6" class="tab-link">Other support</a>
<div class="tab-content">
<h3>Other ways to support</h3>
<div class="donate-para">
<h4>Give as you live</h4>
See {external text="our page on <b>Give as you Live</b>" href="https://www.giveasyoulive.com/join/geograph-project-limited"}, for how can raise free donations simply by shopping online.
</div>
</div>


</div>













<br/><br/>


<hr>


<p>We are also reliant on non-financial support from the following organizations:</p>


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


<hr>

<p align="center" style="font-size:0.9em;"><i class="nowrap">Geograph<sup>&reg;</sup> Britain and Ireland</i> is a project <span 
class="nowrap">by <a href="/article/About-Geograph-page">Geograph Project Limited</a></span>, a Charity <span 
class="nowrap">Registered in England and Wales</span>, no <b>1145621</b>. <span class="nowrap">Company no 7473967</span>.<br>
The registered office is <span class="nowrap">Dept 1706, 43 Owston Road,</span> Carcroft, Doncaster, South Yorkshire. DN6 8DA.</p>

{include file="_std_end.tpl"}
