{assign var="page_title" value="Searching by Text"}
{include file="_std_begin.tpl"}


<h2>Text <a href="/search.php">Search</a> Options</h2>


<div class="interestBox" style="background-color:pink; color:black; border:2px solid red; padding:10px;">
<img src="http://{$static_host}/templates/basic/img/icon_alert.gif" alt="Alert" width="50" height="44" align="left" style="margin-right:10px"/>
We have recently introduced a new search engine, <a href="/help/search_new">see this page</a> for more details.<br/>
</div>
<br/>



<p><i>By default the Geograph Image Search just searches for the exact text you enter anywhere in the <b>title</b>, matching part words (does not separate into keywords).</i></p>


<p>Use any of the following features to refine your search:</p>

<ul>
	<li><h3 style="background-color:#eeeeee; padding:10px">^Whole Word Matching</h3>
	<p>Enter a word prefixed by caret: ^ to find images with that exact word in the title, example:</p>
	<tt style="border:1px solid gray;padding:10px">^bridge</tt>
	<p>would not match against Bridgewater.</p></li>

    
	<li><h3 style="background-color:#eeeeee; padding:10px">Searching the Title, Description and Category+</h3>
	<p>End your search word with a plus: + to search all three fields, example:</p>
	<tt style="border:1px solid gray;padding:10px">bridge+</tt>
	<p>would match images with bridge in the title, in the comments, <b>or</b> in the 'Road Bridge' category (for example).</p></li>

	
    
	<li><h3 style="background-color:#eeeeee; padding:10px">Advanced Boolean Searches</h3>
	<p>Can use the commands <tt>AND</tt>, <tt>OR</tt>, <tt>NOT</tt>(or -) (case is important, brackets optional), probably easiest to demonstrate with a few examples:</p>
	<ul>
    	<li style="padding:10px;"><tt style="border:1px solid gray;padding:10px">bridge AND river</tt></li>
    	<li style="padding:10px;"><tt style="border:1px solid gray;padding:10px">river OR stream</tt></li>
    	<li style="padding:10px;"><tt style="border:1px solid gray;padding:10px">road AND NOT bridge</tt> or <tt style="border:1px solid gray;padding:10px">road -bridge</tt></li>
    	<li style="padding:10px;"><tt style="border:1px solid gray;padding:10px">road OR motorway OR carriageway</tt></li>
    	<li style="padding:10px;"><tt style="border:1px solid gray;padding:10px">NOT bridge</tt> or <tt style="border:1px solid gray;padding:10px">-bridge</tt></li>
    	<li style="padding:10px;"><tt style="border:1px solid gray;padding:10px">^train AND ^bridge+</tt></li>
    	<li style="padding:10px;"><tt style="border:1px solid gray;padding:10px">road OR motorway+</tt></li>
    	<li style="padding:10px;"><tt style="border:1px solid gray;padding:10px">road+ AND <span style="color:lightgrey">(</span>a OR motorway+<span style="color:lightgrey">)</span></tt> : brackets optional</li>
    	<li style="padding:10px;"><tt style="border:1px solid gray;padding:10px">(river AND crossing) OR bridge</tt> : brackets <b>required</b></li>
    	<li style="padding:10px;"><tt style="border:1px solid gray;padding:10px">wood -^trees+</tt> : the - goes before the ^</li>
    	</ul>
    	<p>* each term can be combined with either or both ^ and + charactors for very flexible searching!</p>
   	</li>
   	  	
</ul> 

    
<a name="question"></a>
<h3>I have a question, what should I do?</h3>
    <p>Please <a title="Contact Us" href="/contact.php">Contact Us</a></p>
    
    
{include file="_std_end.tpl"}
