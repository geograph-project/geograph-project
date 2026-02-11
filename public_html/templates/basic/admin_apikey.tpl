{assign var="page_title" value="API Key"}
{include file="_std_begin.tpl"}
{dynamic}

<h2>API Key Request</h2>

<ul>
    <li>
        <strong>Small-Scale Needs:</strong> Our APIs are optimized for real-time, small-scale queries such as fetching a few sample images near a specific location for a web display. Signup for a key below.<br><br>
    </li>

    <li>
        <strong>Bulk Data:</strong> For large-scale requirements (thousands of records) or one-time projects, we recommend our pre-compiled data dumps. 
        <ul>
            <li>Access them at <a href="https://data.geograph.org.uk/">data.geograph.org.uk</a>. <em>Note: Maintenance levels vary across datasets.</em></li>

	    <li>Need something even more specific? <a href="https://docs.google.com/forms/d/e/1FAIpQLSdhtlmb7OGM_QrLKtJLbeg1-eVIErCzQrTDJQL7NXJTz45UfA/viewform">Fill out this form</a> to request a custom dataset.</li>
        </ul><br>
    </li>

    <li>
        <strong>AI & Machine Learning:</strong> If you require data with pixel information for tasks like AI classification, check out our specialized datasets:
        <ul>
            <li><a href="https://data.geograph.org.uk/datasets.html">View Image Datasets</a></li>
        </ul><br>
    </li>

    <li>
        <strong>Advanced Research:</strong> We are currently exploring Image Similarity using encoding models like CLIP and Perception Encoder. These pre-computed embeddings are available for classification training or academic research. 
        <ul>
            <li>Some datasets are already available on <a href="https://www.kaggle.com/barrybhunter/datasets">Kaggle</a>. 
            For specific collaboration or access to additional models, please <a href="/contact.php">contact us</a> directly.</li>
        </ul><br>
   </li>
</ul>

<hr>

<p>Use this page to request a key to use one of the Geograph APIs...</p>

 {if $message}
	<div style="border:1px solid red; padding:20px;margin:20px;">{$message}</div> {/if}
	
<form action="{$script_name}" method="post" style="background-color:#eee;padding:20px">
	 <input type="hidden" name="id" value="{$id}">

		<table cellpadding="3" cellspacing="0">
		  <tr>
			 <td><b>your name</b></td>
			 <td><input type="name" required name="name" value="{$arr.name|escape:'html'}"></td>
		  </tr>
		  <tr>
			 <td><b>your email</b></td>
			 <td><input type="email" required name="email" value="{$arr.email|escape:'html'}">
				please enter your email so we may contact you</td>
		  </tr>
		  <tr>
			 <td><b>homepage</b></td>
			 <td><input type="text" name="homepage_url" value="{$arr.homepage_url|escape:'html'}">
				optional - please provide a link to your site/project homepage</td>
		  </tr>
		  <tr>
			 <td><b>type</b></td>
			 <td><select name="type" value="{$arr.type}|escape:'html'">
					<option></option>
					<option>commercial project</option>
					<option>non-profit project</option>
					<option>hobby project</option>
					<option>university coursework</option>
					<option>personal use only</option>
					<option>other</option>
				</select></td>
		  </tr>
		  <tr>
			 <td><b>comments</b></td>
			 <td><textarea name="comments" rows="4" cols="50">{$arr.comments}</textarea><br/>
			 please note what you want to use the key for, and expected traffic levels etc</td>
		  </tr>
		  <tr>
			 <td><b>robot?</b></td>
			 <td>
				<p>We are having problems with lots of bots crawling. Please enter the number of 'g' letters in the word "geograph" in the box below to prove you a human!</p>

				<p>Letters: <input type=number name=number size=3 style="width:50px">
			 </td>
		  <tr>
			 <td>&nbsp;</td>
			 <td><input type="submit" value="Request Key" name="submit"></td>
		  </tr>
	</table></form>

    
{/dynamic}    
{include file="_std_end.tpl"}
