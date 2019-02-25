<?php 
	require_once 'variables.php';
?>

<!DOCTYPE html>
<html lang="en">
	<?php include 'header.php';?>
	<body>
		<div class="container">
			<!-- Just an image -->
			<nav class="navbar navbar-light bg-light">
			  	<a class="navbar-brand" href="/">
			    	<img src="/dist/images/logo.jpg" width="auto" height="80px" alt="">
			  	</a>
			  	<a href="/logout.php">Log Out</a>
			</nav>
			<div class="alert alert-success" role="alert">
  				Thank you for the purchase made. We hope that you like our product.
			</div>
			<div class="float-left mt-3">
				<a href="/" class="btn btn-primary" role="button" data-reveal-close="">
	                Back
	            </a>
			</div>
			<div class="float-right mt-3">
				<a href="<?php echo STORE_URL; ?>" class="btn btn-secondary button--primary" role="button" data-reveal-close="">
	                Continue Shopping
	            </a>
	        </div>
            <?php include 'footer.php'; ?>
		</div>
	</body>
</html>
