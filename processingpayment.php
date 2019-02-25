<?php

require 'vendor/autoload.php';
require 'variables.php';

use net\authorize\api\contract\v1\MerchantAuthenticationType;
use net\authorize\api\contract\v1\OpaqueDataType;
use net\authorize\api\contract\v1\PaymentType;
use net\authorize\api\contract\v1\OrderType;
use net\authorize\api\contract\v1\CustomerAddressType;
use net\authorize\api\contract\v1\CustomerDataType;
use net\authorize\api\contract\v1\SettingType;
use net\authorize\api\contract\v1\UserFieldType;
use net\authorize\api\contract\v1\TransactionRequestType;
use net\authorize\api\contract\v1\CreateTransactionRequest;
use net\authorize\api\controller\CreateTransactionController;
use net\authorize\api\constants\ANetEnvironment;
use net\authorize\api\contract\v1\LineItemType;

use Bigcommerce\Api\Client as Bigcommerce;

Bigcommerce::configure(array(
    'client_id' => CLIENT_ID,
    'auth_token' => ACCESS_TOKEN,
    'store_hash' => STORE_HASH
));

Bigcommerce::verifyPeer(false);

define('AUTHORIZENET_LOG_FILE', 'phplog');

//merchant credentials
const MERCHANT_LOGIN_ID = '7Zd6wz9HaZu4';
const MERCHANT_TRANSACTION_KEY = '4847KTQdym2N29xM';

const RESPONSE_OK = 'Ok';

function createAnAcceptPaymentTransaction($amount)
{
    //Get order details
    $order_id = null;
    if (isset($_POST['dataOrderId'])) {
        $order_id = $_POST['dataOrderId'];
    }

    $bigcommerce_order = Bigcommerce::getOrder($order_id);
    $bigcommerce_customer = Bigcommerce::getCustomer($bigcommerce_order->customer_id);

    $error = null;
    if (!$bigcommerce_order) {
        $error = Bigcommerce::getLastError();
        echo 'Your order ID was not found.';
    }

    /* Create a merchantAuthenticationType object with authentication details
       retrieved from the constants file */
    $merchantAuthentication = new MerchantAuthenticationType();
    $merchantAuthentication->setName(MERCHANT_LOGIN_ID);
    $merchantAuthentication->setTransactionKey(MERCHANT_TRANSACTION_KEY);

    // Set the transaction's refId
    $refId = 'ref'.time();

    // Create the payment object for a payment nonce
    $opaqueData = new OpaqueDataType();
    $opaqueData->setDataDescriptor($_POST['dataDescriptor']);
    $opaqueData->setDataValue($_POST['dataValue']);

    // Add the payment data to a paymentType object
    $paymentOne = new PaymentType();
    $paymentOne->setOpaqueData($opaqueData);

    // Create order information
    $order = new OrderType();
    $order->setInvoiceNumber($bigcommerce_order->id);
    $order->setDescription($bigcommerce_order->products[0]->name);
    $order->setDiscountAmount($bigcommerce_order->discount_amount);


    // Set the customer's Bill To address
    $customerAddress = new CustomerAddressType();
    $customerAddress->setFirstName($bigcommerce_order->billing_address->first_name);
    $customerAddress->setLastName($bigcommerce_order->billing_address->last_name);
    $customerAddress->setAddress($bigcommerce_order->billing_address->street_1.', '.$bigcommerce_order->billing_address->street_2);
    $customerAddress->setCity($bigcommerce_order->billing_address->city);
    $customerAddress->setState($bigcommerce_order->billing_address->state);
    $customerAddress->setZip($bigcommerce_order->billing_address->zip);
    $customerAddress->setCountry($bigcommerce_order->billing_address->country);

    // Set the customer's identifying information
    $customerData = new CustomerDataType();
    $customerData->setType('individual');
    $customerData->setId($bigcommerce_customer->id);
    $customerData->setEmail($bigcommerce_customer->email);

    // Add values for transaction settings
    $duplicateWindowSetting = new SettingType();
    $duplicateWindowSetting->setSettingName('duplicateWindow');
    $duplicateWindowSetting->setSettingValue('60');

    // Add some merchant defined fields. These fields won't be stored with the transaction,
    // but will be echoed back in the response.
    $merchantDefinedField1 = new UserFieldType();
    $merchantDefinedField1->setName('customerLoyaltyNum');
    $merchantDefinedField1->setValue('1128836273');

    $merchantDefinedField2 = new UserFieldType();
    $merchantDefinedField2->setName('favoriteColor');
    $merchantDefinedField2->setValue('blue');

    // Create a TransactionRequestType object and add the previous objects to it
    $transactionRequestType = new TransactionRequestType();
    $transactionRequestType->setTransactionType('authCaptureTransaction');
    $transactionRequestType->setAmount($amount);
    $transactionRequestType->setOrder($order);
    $transactionRequestType->setPayment($paymentOne);
    $transactionRequestType->setBillTo($customerAddress);
    $transactionRequestType->setCustomer($customerData);

    foreach ($bigcommerce_order->products as $key => $product) {
        $lineItem = new LineItemType();
        $lineItem->setItemId($product->id);
        $lineItem->setName($product->name);
        $lineItem->setUnitPrice(number_format($product->base_price, 2));
        $lineItem->setQuantity($product->quantity);
        $transactionRequestType->addToLineItems($lineItem);
    }

    // $transactionRequestType->addToTransactionSettings($duplicateWindowSetting);
    // $transactionRequestType->addToUserFields($merchantDefinedField1);
    // $transactionRequestType->addToUserFields($merchantDefinedField2);

    // Assemble the complete transaction request
    $request = new CreateTransactionRequest();
    $request->setMerchantAuthentication($merchantAuthentication);
    $request->setRefId($refId);
    $request->setTransactionRequest($transactionRequestType);

    // Create the controller and get the response
    $controller = new CreateTransactionController($request);
    $response = $controller->executeWithApiResponse(ANetEnvironment::PRODUCTION);

    if ($response != null) {
        // Check to see if the API request was successfully received and acted upon
        if ($response->getMessages()->getResultCode() == 'Ok') {
            // Since the API request was successful, look for a transaction response
            // and parse it to display the results of authorizing the card
            $tresponse = $response->getTransactionResponse();

            if ($tresponse != null && $tresponse->getMessages() != null) {
                echo ' Successfully created transaction with Transaction ID: '.$tresponse->getTransId()."\n";
                echo ' Transaction Response Code: '.$tresponse->getResponseCode()."\n";
                echo ' Message Code: '.$tresponse->getMessages()[0]->getCode()."\n";
                echo ' Auth Code: '.$tresponse->getAuthCode()."\n";
                echo ' Description: '.$tresponse->getMessages()[0]->getDescription()."\n";

                $orderStatuses = Bigcommerce::getOrderStatuses();

                foreach ($orderStatuses as $key => $status) {
                    if ( $status->name == "Awaiting Fulfillment") {
                        $bigcommerce_order->status_id = $status->id;
                        $bigcommerce_order->status = $status->name;
                        $bigcommerce_order->update();
                    }
                }

                header('Location: thankyou.php');
                die();
            } else {
                echo "Transaction Failed \n";
                if ($tresponse->getErrors() != null) {
                    echo ' Error Code  : '.$tresponse->getErrors()[0]->getErrorCode()."\n";
                    echo ' Error Message : '.$tresponse->getErrors()[0]->getErrorText()."\n";
                }
            }
            // Or, print errors if the API request wasn't successful
        } else {
            echo "Transaction Failed \n";
            $tresponse = $response->getTransactionResponse();

            if ($tresponse != null && $tresponse->getErrors() != null) {
                echo ' Error Code  : '.$tresponse->getErrors()[0]->getErrorCode()."\n";
                echo ' Error Message : '.$tresponse->getErrors()[0]->getErrorText()."\n";
            } else {
                echo ' Error Code  : '.$response->getMessages()->getMessage()[0]->getCode()."\n";
                echo ' Error Message : '.$response->getMessages()->getMessage()[0]->getText()."\n";
            }
        }
    } else {
        echo  "No response returned \n";
    }

    return $response;
}

if (isset($_POST['dataAmount'])) {
    createAnAcceptPaymentTransaction($_POST['dataAmount']);
}
