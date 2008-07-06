<?php
  // Example to illustrate the use of Net_Traceroute
  // $Id: example1.php,v 1.1 2004/02/14 23:08:42 neufeind Exp $

  require_once "Net/Traceroute.php";
  $traceroute = Net_Traceroute::factory();
  if(PEAR::isError($traceroute)) {
    echo $traceroute->getMessage();
  } else {
    $traceroute->setArgs(array('numeric' => NULL));
    var_dump($traceroute->traceroute('pear.php.net'));
  }
?>