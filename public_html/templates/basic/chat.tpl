{assign var="page_title" value="Chat"}
{include file="_std_begin.tpl"}
{dynamic}
{if $show_applet}

<applet code=IRCApplet.class archive="irc.jar,pixx.jar" width=640 height=400>
<param name="CABINETS" value="irc.cab,securedirc.cab,pixx.cab">


<param name="nick" value="{$nickname}">
<param name="alternatenick" value="{$nickname}_">
<param name="fullname" value="{$realname}">


<param name="host" value="irc.freenode.net">
<param name="command1" value="/join #geograph">
<param name="authorizedjoinlist" value="none+#geograph">

<param name="gui" value="pixx">

<param name="quitmessage" value="Bye bye">
<param name="useinfo" value="true">

<param name="pixx:showchanlist" value="false">
<param name="pixx:showabout" value="false">
<param name="pixx:showhelp" value="false">
<param name="pixx:showconnect" value="false">

<param name="pixx:color5" value="009900">
<param name="pixx:color6" value="000066">

<param name="style:bitmapsmileys" value="true">
<param name="style:smiley1" value=":) img/sourire.gif">
<param name="style:smiley2" value=":-) img/sourire.gif">
<param name="style:smiley3" value=":-D img/content.gif">
<param name="style:smiley4" value=":d img/content.gif">
<param name="style:smiley5" value=":-O img/OH-2.gif">
<param name="style:smiley6" value=":o img/OH-1.gif">
<param name="style:smiley7" value=":-P img/langue.gif">
<param name="style:smiley8" value=":p img/langue.gif">
<param name="style:smiley9" value=";-) img/clin-oeuil.gif">
<param name="style:smiley10" value=";) img/clin-oeuil.gif">
<param name="style:smiley11" value=":-( img/triste.gif">
<param name="style:smiley12" value=":( img/triste.gif">
<param name="style:smiley13" value=":-| img/OH-3.gif">
<param name="style:smiley14" value=":| img/OH-3.gif">
<param name="style:smiley15" value=":'( img/pleure.gif">
<param name="style:smiley16" value=":$ img/rouge.gif">
<param name="style:smiley17" value=":-$ img/rouge.gif">
<param name="style:smiley18" value="(H) img/cool.gif">
<param name="style:smiley19" value="(h) img/cool.gif">
<param name="style:smiley20" value=":-@ img/enerve1.gif">
<param name="style:smiley21" value=":@ img/enerve2.gif">
<param name="style:smiley22" value=":-S img/roll-eyes.gif">
<param name="style:smiley23" value=":s img/roll-eyes.gif">
<param name="style:backgroundimage" value="true">
<param name="style:backgroundimage1" value="all all 0 geograph.gif">
<param name="style:sourcefontrule1" value="all all Serif 12">
<param name="style:floatingasl" value="true">

<param name="pixx:timestamp" value="true">
<param name="pixx:highlight" value="true">
<param name="pixx:highlightnick" value="true">
<param name="pixx:nickfield" value="true">
<param name="pixx:styleselector" value="true">
<param name="pixx:setfontonstyle" value="true">

</applet>

<style type="text/css">{literal}
#crib img  {
	border-right:1px solid gray; 
	padding-right:5px;
}
{/literal}</style>

<p>Cribsheet:</p><ul id="crib">
<li><span style="background-color:lightgreen; border:1px solid black; padding: 2px">
:) <img src="/chat/img/sourire.gif"/>&nbsp;&nbsp;
:d <img src="/chat/img/content.gif"/>&nbsp;&nbsp;
:o <img src="/chat/img/OH-1.gif"/>&nbsp;&nbsp;
:p <img src="/chat/img/langue.gif"/>&nbsp;&nbsp;
;) <img src="/chat/img/clin-oeuil.gif"/>&nbsp;&nbsp;
:( <img src="/chat/img/triste.gif"/>&nbsp;&nbsp;
:| <img src="/chat/img/OH-3.gif"/>&nbsp;&nbsp;
:'( <img src="/chat/img/pleure.gif"/>&nbsp;&nbsp;
:$ <img src="/chat/img/rouge.gif"/>&nbsp;&nbsp;
(H) <img src="/chat/img/cool.gif"/>&nbsp;&nbsp;
:@ <img src="/chat/img/enerve2.gif"/>&nbsp;&nbsp;
:s <img src="/chat/img/roll-eyes.gif" style="border-right:none"/>
</span> <small><small><br/>Just type the characters and it will be turned into the image</small><br/><br/></small></li>

<li><tt style="background-color:lightgreen; border:1px solid black; padding: 2px"><b>/me</b> rolls eyes in bewilderment</tt>
shows as <tt style="color:purple; border: 1px solid gray; padding:2px">*{$nickname} rolls eyes in bewilderment</tt><br/><br/></li>
<li><tt style="background-color:lightgreen; border:1px solid black; padding: 2px"><b>/quit</b> needs sleep now</tt>
allows you to leave gracefully with the message, helping to show users you intended to leave!
</li>
</ul>

  {literal}
  <script language="JavaScript" type="text/javascript">
  
  function removeSiteLinks() {
  	el = document.getElementById('nav_block');
  	el.innerHTML = '<a accesskey="1" title="Home Page" href="/">Home</a><small style="font-size:0.7em"><br/><br/>(<b>will close Chat session</b>)</small>';
  	el = document.getElementById('search_block');
  	el.style.display='none';
  	el = document.getElementById('header').getElementsByTagName('h1');
  	el[0].style.width='1px';
  }
  AttachEvent(window,'load',removeSiteLinks,false);
  
  </script>
  {/literal}

{else}

<h2>Geograph Chat (beta)</h2>

<p>We've set up an IRC chat channel where you can hang out and 
natter to your fellow Geographers.</p>

<p>There are two ways to connect</p>

<h3>1. Use your browser</h3>
<p>We've set up a Java applet which makes joining the channel fairly straightforward
for anyone unfamiliar with IRC. To use the applet, simply enter your desired 
nickname below and press "Join".</p>

<form method="get" action="/chat/index.php" target="newchat">
<label for="nickname">Nickname</label>
<input type="text" name="nickname" id="nickname" value="{$nickname}">
<input type="submit" name="join" value="Join"> (opens in a new window)
</form>

<h3>2. Use an IRC client</h3>
<p>If you are familiar with IRC, then simply connect to <tt>irc.freenode.net</tt> and join 
<tt><a href="irc://irc.freenode.net/%23geograph">#geograph</a></tt></p>

<br/><br/>
<div style="background:#a8e60d;padding:15px;">
<h3 style="margin-top:0px"><img src="/templates/basic/img/icon_alert.gif" alt="Alert" width="50" height="44" align="left" style="margin-right:10px"/> Important Notes</h3>

<p>While logged in you might see an automated bot present in the geograph channel with the name "GeographBot". This is there so we can get an idea on the number of users in the channel to report on the website. To do this it records the last communication from each user, it does not log the whole conversation. In time it might have additional functionality like being able to answer questions about images etc</p>

<p>The channel is hosted by <a href="http://freenode.net">Freenode</a>, which 
provides discussion facilities for the Free and Open Source Software communities, 
for not-for-profit organizations and for related communities and organizations.</p>

<p>You use the channel <b>at your own risk</b> - we can't guarantee a member
of the team or appointed "operator" will be available on the channel to police it
at all times</p>

<p>The applet we've provided is the open source <a href="http://www.pjirc.com/main.php">PJIRC</a>
and is entirely unsupported. If it doesn't work for you, try an IRC client like 
<a href="http://www.mirc.com">mIrc (shareware)</a> or <a href="http://www.hydrairc.com">Hydra (free)</a>.
</p>

</div>

{/if}

{/dynamic}
{include file="_std_end.tpl"}

