<?php
session_start();

require_once 'vendor/autoload.php';
require_once 'variables.php';

use Bigcommerce\Api\Client as Bigcommerce;

Bigcommerce::configure(array(
    'client_id' => CLIENT_ID,
    'auth_token' => ACCESS_TOKEN,
    'store_hash' => STORE_HASH
));

Bigcommerce::verifyPeer(false);

$order_id = null;
if (isset($_GET['orderId'])) {
	$order_id = $_GET['orderId'];
}

$order = Bigcommerce::getOrder($order_id);
$customer = Bigcommerce::getCustomer($order->customer_id);

$error = null;
$error_message = '';

if (!$order) {
	$error = Bigcommerce::getLastError();
	$error_message = 'Order #'.$order_id.' was not found.';

} else if ($_SESSION['customer_email'] != $customer->email) {
	$error = true;
	$error_message = 'This order does not belong to you, please try again.';	
}

?>

<?php if (!$error) {?>
	<script type="text/javascript" src="https://js.authorize.net/v3/AcceptUI.js" charset="utf-8"> </script>

	<div class="card">
	<div class="card-header"> Invoice
		<strong><?php echo $order->id?></strong> 
	  	<span class="float-right"> <strong>Status:</strong> <?php echo $order->status?></span>
	</div>
	<div class="card-body">
		<div class="row mb-4">
			<div class="col-sm-6">
				<h6 class="mb-3">From:</h6>
				<div>
					<strong>EightCig LLC</strong>
				</div>
				<div>3010 E Alexander Rd </div>
				<div>Suite 1002, North Las Vegas, NV</div>
				<div>Email: info@eightcig.com</div>
				<div>Phone: 702-415-5263</div>
			</div>

			<div class="col-sm-6">
				<h6 class="mb-3">To:</h6>
				<div>
					<strong><?php echo $order->billing_address->first_name.' '.$order->billing_address->last_name?></strong>
				</div>
				<div><?php echo $order->billing_address->street_1?></div>
				<div><?php echo $order->billing_address->street_2.', '.$order->billing_address->city.', '.$order->billing_address->state.', '.$order->billing_address->zip.', '.$order->billing_address->country?></div>
				<div><?php echo $order->billing_address->street_1?></div>
				<div>Email: <?php echo $order->billing_address->email?></div>
			</div>
		</div>

		<div class="table-responsive-sm">
			<table class="table table-striped">
				<thead>
					<tr>
						<th class="center">#</th>
						<th>Item</th>
						<th class="right">Unit Cost</th>
						<th class="center">Qty</th>
						<th class="right">Total</th>
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
								<td class="right"><?=number_format($product->base_total, 2);?></td>
							  	<td class="center"><?=$product->quantity?></td>
								<td class="right"><?=number_format($product->total_inc_tax, 2);?></td>
							</tr>
							<?php
						}
					?>
				</tbody>
			</table>
		</div>
		<div class="row">
			<div class="col-lg-4 col-sm-5">

			</div>

			<div class="col-lg-4 col-sm-5 ml-auto">
				<table class="table table-clear">
					<tbody>
						<tr>
							<td class="left">
								<strong>Subtotal</strong>
							</td>
							<td class="right"><?php echo number_format($order->subtotal_inc_tax, 2);?></td>
						</tr>
						<tr>
							<td class="left">
								<strong>Coupon</strong>
							</td>
							<td class="right"><?php echo number_format($order->coupon_discount, 2);?></td>
						</tr>
						<tr>
							<td class="left">
								<strong>Total</strong>
							</td>
							<td class="right">
								<strong><?php echo number_format($order->total_inc_tax, 2);?></strong>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<div class="float-left mt-3">
	<button class="btn btn-primary" id="back">Back</button>
</div>

<?php 
if ($order->status == 'Awaiting Payment') {
?>

<div class="float-right mt-3">
    <form id="paymentForm"
        method="POST"
        action="processingpayment.php">
        <input type="hidden" name="dataOrderId" id="dataOrderId" value="<?php echo $order_id; ?>" />
        <input type="hidden" name="dataAmount" id="dataAmount" value="<?php echo number_format($order->total_inc_tax, 2);?>" />
        <input type="hidden" name="dataValue" id="dataValue" />
        <input type="hidden" name="dataDescriptor" id="dataDescriptor" />
        <button type="button"
            class="AcceptUI btn btn-success"
            data-billingAddressOptions='{"show":true, "required":false}' 
            data-apiLoginID="<?php echo AUTHORIZE_API_LOGIN_ID;?>" 
            data-clientKey="<?php echo AUTHORIZE_CLIENTKEY;?>"
            data-acceptUIFormBtnTxt="Submit" 
            data-acceptUIFormHeaderTxt="Card Information" 
            data-responseHandler="responseHandler">Pay Now
        </button>
    </form>
</div>
<?php }?>

<script type="text/javascript">
	$( "#back" ).click(function() {
		location.reload();
	});

    function responseHandler(response) {
        if (response.messages.resultCode === "Error") {
            var i = 0;
            while (i < response.messages.message.length) {
                console.log(
                    response.messages.message[i].code + ": " +
                    response.messages.message[i].text
                );
                i = i + 1;
            }
        } else {
            paymentFormUpdate(response.opaqueData);
        }
    }

    function paymentFormUpdate(opaqueData) {
        document.getElementById("dataDescriptor").value = opaqueData.dataDescriptor;
        document.getElementById("dataValue").value = opaqueData.dataValue;
        $( "#loading" ).toggleClass( "d-none" );

        // If using your own form to collect the sensitive data from the customer,
        // blank out the fields before submitting them to your server.
        // document.getElementById("cardNumber").value = "";
        // document.getElementById("expMonth").value = "";
        // document.getElementById("expYear").value = "";
        // document.getElementById("cardCode").value = "";

        document.getElementById("paymentForm").submit();
    }
</script>
<?php } else {?>
<div>
	<div class="alert alert-danger" role="alert">
	  	<strong>Error:</strong> <?php echo $error_message; ?>
	</div>	
</div>
<?php }?>
