<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head><title>Geograph Support :: geograph.org.uk</title>
<link rel="stylesheet" type="text/css" title="Monitor" href="http://s0.geograph.org.uk/templates/basic/css/basic.css" media="screen" />
    <link rel="stylesheet" href="http://s0.geograph.org.uk/support/styles/main.css" media="screen">
    <link rel="stylesheet" href="http://s0.geograph.org.uk/support/styles/colors.css" media="screen">

</head>
<body bgcolor="#ffffff" style="background-color:white;margin:0px">

    <ul id="nav">
         <?                    
         if($thisclient && is_object($thisclient) && $thisclient->isValid()) {?>
         <li><a class="log_out" href="logout.php">Log Out</a></li>
         <li><a class="my_tickets" href="tickets.php">My Tickets</a></li>
         <?}else {?>
         <li><a class="ticket_status" href="tickets.php">Ticket Status</a></li>
         <?}?>
         <li><a class="new_ticket" href="open.php">New Ticket</a></li>
         <li><a class="home" href="index.php">Support Home</a></li>
    </ul>
