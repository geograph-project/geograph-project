{assign var="page_title" value="New Search Engine"}
{include file="_std_begin.tpl"}

<h2>The Geograph <a href="/search.php">Search Engine</a> - now <i>keyword</i> powered</h2>

<p>We have recently introduced a new search system to Geograph. This is powered by an indexing system designed especially for text searching. This is a drastic change compared to the old search, which, though it could do text searches, was kinda clunky.</p>

<div class="interestBox" style="background-color:pink; color:black; border:2px solid red; padding:10px;">
<img src="/templates/basic/img/icon_alert.gif" alt="Alert" width="25" height="22" align="left" style="margin-right:10px"/>
There is a better introduction to the new search here: <a href="/article/Searching-on-Geograph">Searching on Geograph</a> and <a href="/article/Word-Searching-on-Geograph">Word Searching</a> 
</div>
<br/>

<div class="interestBox" style="padding:10px;border:1px solid yellow; font-size:0.8em; width:300px; float:right">
	However, the legacy search engine was powerful in the options offered on the advanced search form, allowing you to refine the query quite a bit. For this reason the legacy search is still available, but where your search can be fulfilled by the new index you will be automatically redirected to the new search. <br/><br/>

	Hopefully new users finding our search for the first time, should just find the new search works as is, and shouldn't have to worry about the clunky old system. If you are a returning user please take a moment to forget the old search system.<br/><br/>

	We are still refining this integration, and aim to make the transition as smooth as possible, but we welcome feedback!<br/><br/>
	
	
	<h4>I have a question, what should I do?</h4>
	    <p>Please <a title="Contact Us" href="/contact.php">Contact Us</a>{if $enable_forums}, alternatively pop into the <a href="/discuss/">Discussion Forum</a>{/if}.</p>
    
</div>


<p>Enter multiple keywords separated by spaces, in any order:</p>
<ul>
    	<li style="padding:10px;"><tt style="border:1px solid gray;padding:10px">river bridge arch</tt></li>
    	<li style="padding:10px;">To exclude a word put a - in front <tt style="border:1px solid gray;padding:10px">bridge -river</tt></li>
    	<li style="padding:10px;">either/or <tt style="border:1px solid gray;padding:10px"><span style="color:lightgrey;font-size:0.8em">(</span>river OR road<span style="color:lightgrey;font-size:0.8em">)</span> tree</tt> (brackets optional)</li>
</ul>

<p>The new engine can also apply filters just by entering prefixes directly in the search box, below are some examples of supported fields:</p>

<ul>
    	<li style="padding:10px;"><tt style="border:1px solid gray;padding:10px">title:bridge</tt></li>
    	<li style="padding:10px;"><tt style="border:1px solid gray;padding:10px">description:road</tt></li>
    	<li style="padding:10px;"><tt style="border:1px solid gray;padding:10px">category:road</tt></li>
    	<li style="padding:10px;"><tt style="border:1px solid gray;padding:10px">year:2007</tt> (the year the photo was taken)</li>
    	<li style="padding:10px;"><tt style="border:1px solid gray;padding:10px">month:200605</tt> (the month, eg May 2006)</li>
    	<li style="padding:10px;"><tt style="border:1px solid gray;padding:10px">day:20060521</tt> (the day, eg 21st May 2006)</li>
	<li style="padding:10px;"><tt style="border:1px solid gray;padding:10px">myriad:SH</tt> or <tt style="border:1px solid gray;padding:10px">hectad:TQ49</tt> or <tt style="border:1px solid gray;padding:10px">gridref:TQ4192</tt></li>
    	<li style="padding:10px;"><tt style="border:1px solid gray;padding:10px">realname:"Fred Smith"</tt></li>
</ul>

<p>In addition can combine these features and even specify phrases:</p> 
<ul>
	<li style="padding:10px;"><tt style="border:1px solid gray;padding:10px">bridge -comment:road</tt> (negation)</li>
    	<li style="padding:10px;"><tt style="border:1px solid gray;padding:10px">river title:bridge</tt></li>
    	<li style="padding:10px;"><tt style="border:1px solid gray;padding:10px">"road bridge"</tt></li>
    	<li style="padding:10px;"><tt style="border:1px solid gray;padding:10px">road bridge -"suspension bridge"</tt></li>
</ul>  	

<p>Also boolean queries are supported (includes partial support for phrases and fields):</p> 
<ul>
	<li style="padding:10px;"><tt style="border:1px solid gray;padding:10px"> dog | ( cat mouse)</tt></li>
	<li style="padding:10px;"><tt style="border:1px solid gray;padding:10px">(river OR road) AND tree</tt></li>
</ul>  	


<p>All keywords are not case sensitive!</p>

<br/>
<hr/>

<p>You can find the reference for the <a href="/help/search">old query style here</a>.</p>


    

    
{include file="_std_end.tpl"}
