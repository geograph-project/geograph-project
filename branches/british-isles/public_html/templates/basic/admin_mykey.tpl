{assign var="page_title" value="API Key"}
{include file="_std_begin.tpl"}
<h2>Geograph Account Intergration</h2>



{dynamic}
{if $apikey}
	<hr style="color:red"/>
	<h3 align="center" style="color:red">Confidential</h3>
	<hr style="color:red"/>
	<blockquote>
		<p>Your API Key: <b><tt>{$apikey}</tt></b> (keep this <b>secret</b>)</p>
	
		<p>Your Access Key: <b><tt>{$access}</tt></b> ('public' version of your API key - used in urls etc)</p>

		<p>Your Shared Magic: <b><tt>{$shared}</tt></b> (keep this <b>secret</b> - used to authenticate tokens)</p>
	</blockquote>
	<h4>Geograph Account Authentication</h4>
	<p>This is the only method supported currently, contact us for more</p>
	
	<ol>
		<li><b>User visits your Application</b></li>
		<li><i>You generate a link to Geograph so user can login</i> (encoded to prove your identity - php code below)</li>
		<li><b>User clicks link, and logs into Geograph</b></li>
		<li>Geograph generates a link back to your App with the users identity (encoded to prove our identity)</li>
		<li><b>If user agrees they click the link and return</b></li>
		<li><i>Your script decodes the data returned to retrieve the user identity</i> (php code below)</li>
		<li>You can continue with App certain that visitor is a Geograph Account Holder</li>
	</ol>
	
	<blockquote>
		<hr/>
		<p>PHP Code Snippet in Login Page (eg <tt>login.php</tt>): (already has your credentials embedded)</p>

		<pre>{highlight}
<?php
#obtain from http://svn.geograph.org.uk/svn/trunk/libs/geograph/token.class.php
require_once('token.class.php');

$login_url = '{$self_host}/auth.php?a={$access}';

$token=new Token;
$token->magic = '{$shared}';
$token->setValue("action", 'authenticate');
$token->setValue("callback", "http://domain.com/callback.php"); //full-path to callback.php on your server
$login_url .= '&amp;t='.$token->getToken();

print "<a href=\"$login_url\">Login via Geograph</a>";

?>
		{/highlight}</pre>
		<hr/>

		<p>Complete PHP Code for your Callback Page (<tt>callback.php</tt>):</p>

		<pre>{highlight}
<?php
#obtain from http://svn.geograph.org.uk/svn/trunk/libs/geograph/token.class.php
require_once('token.class.php');

$token=new Token;
$token->magic = '{$shared}';

{literal}if (isset($_GET['t']) && $token->parse($_GET['t']) && $token->hasValue('k') && $token->getValue('k') == '{/literal}{$apikey}{literal}') {
	if ($token->hasValue('user_id') && $token->getValue('user_id') != '' ) {
		#if you get back a user_id you can be certain that they logged in on that account
		
		$user_id=$token->getValue('user_id');
		$realname=$token->getValue('realname');
		$nickname=$token->getValue('nickname');
		
		//store these in a session to continue to have access to them in further pages

		session_start();
		$_SESSION['user_id'] = $user_id;
		$_SESSION['realname'] = $realname;

		header("Location: /app/"); #the main page of your app
		
	} else {
		die("login failed");
	}
} else {
	die("invalid callback");
}{/literal}

?>
		{/highlight}</pre>
		<hr/>
                <p>PHP Code Snippet for general pages (where the user needs to be logged in)</p>

                <pre>{highlight}{literal}
<?php

session_start();

if (empty($_SESSION['user_id'])) {
	header("Location: ./login.php"); //the page that contains the above code to login via geograph.
	exit;
}

print "<h3>Hello ".htmlentities($_SESSION['realname']).", welcome to our new application</h3>";

?>
                {/literal}{/highlight}</pre>



	</blockquote>
	<hr style="color:red"/>
	<h3 align="center" style="color:red">Confidential</h3>
	<hr style="color:red"/>
{else}
  	<form method="get" action="{$script_name}">
  		<p>Please enter your apikey to continue:</p>
  		<input type="text" name="apikey"/>
  		<input type="Submit" value="Go"/>
  	</form>


	<p>If you dont yet have a API key, apply for <a href='/contact.php'>here</a>. we will send you one as soon as possible (we soon hope to have a self-service method!)</p>
{/if}


{/dynamic}
{include file="_std_end.tpl"}
