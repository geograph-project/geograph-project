<html>
<head>
<title>Recent Photos</title>
<link rel="stylesheet" type="text/css" title="Monitor" href="{"/templates/basic/css/basic.css"|revision}" media="screen" />
</head>
<body style="background-color:{$maincontentclass|replace:"content_photo":""}"

{if $maincontentclass eq "content_photowhite"}
	text="#000000"
{/if}
{if $maincontentclass eq "content_photoblack"}
	text="#FFFFFF"
{/if}
{if $maincontentclass eq "content_photogray"}
	text="#CCCCCC"
{/if}


>

 {if $recentcount}
  
  	<h3 {if $overview} style="padding-top:15px; border-top: 2px solid black; margin-top: 15px;"{/if}>Recent Photos</h3>
  	
  	{foreach from=$recent item=image}
  
  	  <div style="text-align:center;padding-bottom:1em;">
  	  <a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}" target="_main">{$image->getThumbnail(120,120)}</a>
  	  
  	  <div>
  	  <a title="view full size image" href="/photo/{$image->gridimage_id}" target="_main">{$image->title|escape:'html'}</a>
  	  by <a title="view user profile" href="{$image->profile_link}" target="_main">{$image->realname}</a>
	  for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}" target="_main">{$image->grid_reference}</a>
	  
	  </div>
  	  
  	  </div>
  	  
  
  	{/foreach}
  
  {/if}

  <a href="/recent.php" target="_self">More...</a>
	
</body>
</html>
