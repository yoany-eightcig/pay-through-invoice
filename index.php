<?php 
	session_start();

	require_once 'vendor/autoload.php';
	require_once 'variables.php';

	if (!isset($_SESSION["logged"]) || $_SESSION["logged"] == 0)  {
		header('Location:/login.php');
		exit();
	}

	use Bigcommerce\Api\Client as Bigcommerce;

	Bigcommerce::configure(array(
    	'client_id' => CLIENT_ID,
    	'auth_token' => ACCESS_TOKEN,
    	'store_hash' => STORE_HASH
	));

	$filter = [
		'customer_id' => $_SESSION["customer_id"],
		'sort' => 'date_created:desc',
	];

	$orders = Bigcommerce::getOrders($filter);

?>

<!DOCTYPE html>
<html lang="en">
	<?php include 'header.php';?>
	<body>
		<div class="container">
			
			<?php include 'navbar.php'; ?>

			<div class="d-none alert alert-danger" id="alert" role="alert">
	  			Your order ID was not found.!
			</div>
			<!-- Modal -->
			<div class="modal fade" data-show="true" id="myModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" >
			  	<div class="modal-dialog" role="document">
			    	<div class="modal-content">
			      		<div class="modal-header">
			        		<h5 class="modal-title" id="exampleModalLabel">Enter Order ID</h5>
			        		<button type="button" class="close" data-dismiss="modal" aria-label="Close">
			          			<span aria-hidden="true">&times;</span>
			        		</button>
			      		</div>
					    <div class="modal-body">
					        <div class="form-group">
					          	<label for="orderId">Order ID:</label>
					          	<input type="text" class="form-control" id="orderId" name="orderId" placeholder="ex: 1234567">
					        </div>
					    </div>
			      		<div class="modal-footer">
			        		<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
			        		<button type="button" class="btn btn-primary" id="continue" data-dismiss="modal">Continue</button>
			      		</div>
			    	</div>
			  	</div>
			</div>
			<div id="container_orders">
				<h3>Orders</h3>
				<table class="table ">
				  <thead class="thead-light">
				    <tr>
				      <th scope="col">Order #</th>
				      <th scope="col">Products</th>
				      <th scope="col">Total</th>
				      <th scope="col">Status</th>
				      <th scope="col">Action</th>
				    </tr>
				  </thead>
				  <tbody>
				  	<?php 
				  	 	if ($orders)
				  		foreach ($orders as $order) {
				  		// debug($order);
				  	?>
				    <tr>
				      	<td><?php echo $order->id ?></td>
				      	<td>
				      		<table class="table ">
				      			<thead>
				      				<tr>
				      					<th class="center">#</th>
				      					<th>Item</th>
				      					<th class="center">Qty</th>
				      				</tr>
				      			</thead>
				      			<tbody>
				      				<?php 
				      					$items = 0;
				      					foreach ($order->products as $key => $product) {
				      						$items++;
				      						?>
				      						<tr>
				      							<td class="center"><?=$items?></td>
				      							<td class="left strong"><?=$product->name?></td>
				      						  	<td class="center"><?=$product->quantity?></td>
				      						</tr>
				      						<?php
				      					}
				      				?>
				      			</tbody>
				      		</table>			      		
				      	</td>
				      	<td><?php echo number_format($order->total_inc_tax, 2);?></td>
				      	<td class="center"><?php echo $order->status ?></td>
				      	<td>
				      		<button class="btn btn-primary" id="continue_<?php echo $order->id?>">Load</button>
				      	</td>
				    </tr>
					<script type="text/javascript">
					    jQuery(document).ready(function($){
					        $( "#continue_<?php echo $order->id?>" ).click(function() {
					        	$( "#loading" ).toggleClass( "d-none" );
					        	$( "#container_orders" ).toggleClass( "d-none" );
					        	let orderId = <?php echo $order->id?>;
					        	$.get('/getorderdetail.php', {orderId: orderId}, function (data, textStatus, jqXHR) {
					        		console.log(textStatus);
					        		$("#orderInfo").html(data);
					        		$( "#loading" ).toggleClass( "d-none" );
					        	});
					    	});
					    });
					</script>
				    <?php 
				    	}
				    ?>
				  </tbody>
				</table>		
			</div>
			<div class="loader mx-auto d-none" id="loading"></div>
			<div class ="mt-3" id="orderInfo"></div>
            <?php include 'footer.php'; ?>
		</div>
	</body>
</html>
