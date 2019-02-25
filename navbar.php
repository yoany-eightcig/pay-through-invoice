<?php
	require_once 'variables.php';
?>

<nav class="navbar navbar-light bg-light">
  	<a class="navbar-brand" href="/">
  		<!-- Just an image -->
    	<img src="/dist/images/logo.jpg" width="auto" height="80px" alt="">
  	</a>
  	<span><?php echo $_SESSION["customer_email"]; ?> | <a href="/logout.php">Sign Out</a> </span>
</nav>
