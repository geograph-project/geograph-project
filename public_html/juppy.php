<?php 

header('Content-type: application/x-java-jnlp-file');
echo '<?xml version="1.0" encoding="utf8"?>' 


/********************************************************

This script delivers the jnlp file to start juppy. It's
coded as a script to remove the need to change it as it moves
between servers

*********************************************************/
?>

<jnlp spec="1.0+" href="juppy.php" 
<?php
	echo 'codebase="http://' . $_SERVER['SERVER_NAME'] . '">';
?>

 <information>
  <title>JUppy Geograph uploader</title>
  <vendor>Those nice Geograph people</vendor> 
  <homepage href="http://www.geograph.org.uk" />
  <description kind="one-line">
   JUppy - Java batch uploader for geograph project
  </description>
  <description kind="short">
   JUppy is the Geograph project batch uploader. This permits off-line
   compilation of an image queue which can be sent to the Geograph
   project when a connection is available.
  </description>
  <offline-allowed />
 </information>

 <security>
  <!-- <all-permissions /> -->
  <!-- ...which means the jar has to be signed -->
 </security>

 <resources>
  <j2se version="1.4" />
  <jar href="JUploader.jar" />
 </resources>

 <application-desc>
  <argument><?php echo $_SERVER['SERVER_NAME'];?></argument>
 </application-desc>

</jnlp>

