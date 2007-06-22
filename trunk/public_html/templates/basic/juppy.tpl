{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin.tpl"}
{dynamic}
<p>Before you jump down to the interesting bit about where to find JUppy, we'd like you to read the following, 
just so you know what you're letting yourself in for...</p>

<h2>JUppy - the Geograph bulk uploader</h2>

<p>JUppy is an application that installs on your PC. Once installed, it allows you to
prepare image submissions off-line, editing the text and image grid references as many times as you 
like before sending the completed works to the Geograph servers. Because all the information is prepared off-line 
and sent to us in a big chunk, you may find it much quicker when submitting multiple images compared with using 
the web interface. One of our beta testers managed to submit 10 images in 84 seconds! Juppy will also take care of 
resizing images to acceptable Geograph sizes. Alternatively, JUppy's quite happy to let you take care of the resizing.</p>

<p>You can also save a partially completed submission batch and come back later to add more images - great
if you're on an extended trip away from your internet connection with your laptop but want to get all the 
information in whilst it's still fresh in your mind.</p>

<p>To use JUppy, you need a machine with a Sun Java 1.5 (SE5) runtime installed. This is a free download
from <a href="http://java.sun.com/javase/downloads/index.jsp">Sun<a/>. If you already have Java 
installed and it's not the correct one, it will be updated automatically.</p>

<p>JUppy is written in Java so it runs on several different types of machine. It's been extensively tested on 
Windows and Linux machines. It should work with Macs as well although we've not been able to test it. (If you are able to try JUppy out on a Mac, please let us know how you get on.)</p>

<p>JUppy is still under development and we'd be pleased to receive any comments or suggestions for improvements.</p>

<p>Before you can use JUppy, you need to have submitted a minimum of {$imagecount} images
using the web interface. We just want to make sure you fully understand the submission process. {$sadly}</p>

<h2>You'll need to trust us</h2>

<p>Java applications try to be well behaved. Because of this, unless you specifically grant an application like JUppy 
access to your hard drive, it won't be able to write anything. This means that JUppy won't be able to save your 
upload queue or category lists (see below). So, when you run JUppy for the first time, you'll see a 'digital certificate'
window appear. We like to think you know us well enough to tell your system to always trust us. If you don't, your system
will ask you every time you start JUppy.</p>

<h2>Before you use JUppy in anger</h2>
<p>Before you can use JUppy, you will need to log in to the Geograph project once. This sets up the category list for JUppy. Once you've performed this first login, you don't need to connect again until you're ready to send us your images.</p>

<h2>Updating JUppy</h2>

<p>Because JUppy is a Java 'webstart' application, whenever Java detects that your system is on-line, it will check
the Geograph servers to see whether we've updated JUppy. If we have, it will automatically fetch the latest, greatest version and install it automatically. If we haven't changed it or you're off-line, you'll use whatever version is installed.</p>

<h2>...and finally...</h2>

{if $notgood}
  <p style='border: single red 1px; background: pink'><br />{$sadly}<br />&nbsp;</p>
{else}
  <p>{$sadly}</p>
  <p>Thanks for being patient. It's now time to <a href="get-juppy.php">download JUppy...</a></p>  
{/if}


<br style="clear:both"/>
&nbsp;
{/dynamic}
{include file="_std_end.tpl"}
