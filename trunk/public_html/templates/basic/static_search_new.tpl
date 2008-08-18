{assign var="page_title" value="Search Engine"}
{include file="_std_begin.tpl"}


<h2>The Geograph <a href="/search.php">Search Engine</a> - part 2</h2>

<p>We have recently introduced a new search system to Geograph. This is powered by a indexing system designed specially for text searching. This is a drastic change compared to the old search, which while could do text searches it was kinda clunky.</p>

<p>However the legacy search engine was powerful in the options offered on the advanced search form, allowing you refine the query quite a bit. For this reason the legacy search is still available, but where your search can be furfullied by the new index you will be automatically redirected to the new search. </p>

<p>Hopefully new users finding our search for the first time, should just find the new search works as is, and shouldnt have to worry about the clunky old system.</p>

<p>We are still refining this intergration, and aim to make the transition as smooth as possible, but we welcome feedback!</p>

<hr/>

<p>One exciting feature of the new engine, is can apply filters just by entering prefixes directly in the search box, below are some examples:</p>


<ul>
    	<li style="padding:10px;"><tt style="border:1px solid gray;padding:10px">title:bridge</tt></li>
    	<li style="padding:10px;"><tt style="border:1px solid gray;padding:10px">river title:bridge</tt></li>
    	<li style="padding:10px;"><tt style="border:1px solid gray;padding:10px">description:road</tt></li>
    	<li style="padding:10px;"><tt style="border:1px solid gray;padding:10px">bridge -comment:road</tt> (negation)</li>
    	<li style="padding:10px;"><tt style="border:1px solid gray;padding:10px">category:road</tt></li>
    	<li style="padding:10px;"><tt style="border:1px solid gray;padding:10px">year:2007</tt> (the year the photo was taken)</li>
    	<li style="padding:10px;"><tt style="border:1px solid gray;padding:10px">month:200605</tt> (the month, eg May 2006)</li>
    	<li style="padding:10px;"><tt style="border:1px solid gray;padding:10px">day:20060521</tt> (the day, eg 21st May 2006)</li>
	<li style="padding:10px;"><tt style="border:1px solid gray;padding:10px">myriad:SH</tt></li>
    	<li style="padding:10px;"><tt style="border:1px solid gray;padding:10px">hectad:TQ49</tt></li>
    	<li style="padding:10px;"><tt style="border:1px solid gray;padding:10px">gridref:TQ4192</tt></li>
    	<li style="padding:10px;"><tt style="border:1px solid gray;padding:10px">realname:Fred realname:Smith</tt></li>
    	<li style="padding:10px;"><tt style="border:1px solid gray;padding:10px">realname:"Fred Smith"</tt></li>
</ul>






<hr/>

<p>You can find the reference for the <a href="/help/search">old query style here</a></p>

<hr/>
    
<a name="question"></a>
<h3>I have a question, what should I do?</h3>
    <p>Please <a title="Contact Us" href="/contact.php">Contact Us</a></p>
    
    
{include file="_std_end.tpl"}
