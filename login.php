<?php 
session_start();
require_once 'vendor/autoload.php';
require_once 'variables.php';
$_SESSION["logged"] = 0;

use Bigcommerce\Api\Client as Bigcommerce;

function postRequest($url, $data, $refer = "", $timeout = 10, $header = [])
{
    $curlObj = curl_init();
    $ssl = stripos($url,'https://') === 0 ? true : false;
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_FOLLOWLOCATION => 1,
        CURLOPT_AUTOREFERER => 1,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)',
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_0,
        CURLOPT_HTTPHEADER => ['Expect:'],
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        CURLOPT_REFERER => $refer
    ];
    if (!empty($header)) {
        $options[CURLOPT_HTTPHEADER] = $header;
    }
    if ($refer) {
        $options[CURLOPT_REFERER] = $refer;
    }
    if ($ssl) {
        //support https
        $options[CURLOPT_SSL_VERIFYHOST] = false;
        $options[CURLOPT_SSL_VERIFYPEER] = false;
    }
    curl_setopt_array($curlObj, $options);
    $returnData = curl_exec($curlObj);
    if (curl_errno($curlObj)) {
        //error message
        $returnData = curl_error($curlObj);
    }
    curl_close($curlObj);
    return $returnData;
}

function login($userEmail, $userPassword) {
    $url = STORE_URL.EXPRESS_LOGIN;
    $fields = array(
        'login_email' => urlencode($userEmail),
        'login_pass' => urlencode($userPassword),
    );

    $postvars = '';
    foreach($fields as $key => $value) {
        $postvars .= $key . "=" . $value . "&";
    }

    $postRes = postRequest($url, $postvars);

    $postRes = substr($postRes, 10, 1);

    $response = Bigcommerce::configure(array(
        'client_id' => CLIENT_ID,
        'auth_token' => ACCESS_TOKEN,
        'store_hash' => STORE_HASH
    ));

    if ($postRes == '1') {
        $filter = ['email' => $userEmail];

        $customer = Bigcommerce::getCustomers($filter);
        // Set session variables
        $_SESSION["customer_id"] = $customer[0]->id;
        $_SESSION["customer_email"] = $_POST['userEmail'];
        $_SESSION["logged"] = 1;

        header('Location:/');
        exit();
    }
}

if (isset($_POST['userEmail']) && isset($_POST['userPassword']) ) {
    login($_POST['userEmail'], $_POST['userPassword']);
}

?>
<!DOCTYPE html>
<html lang="en">
    <?php include 'header.php';?>
    <body>
        <div class="container">
            <!-- Just an image -->
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-heading">
                        <h2 class="text-center mt-5">
                            <a class="" href="/">
                                <img src="/dist/images/logo.jpg" width="auto" height="144" alt="">
                            </a>
                        </h2>
                    </div>
                    <hr />
                    <div class="modal-body">
                        <form action="" role="form" method="post">
                            <div class="form-group">
                                <div class="input-group">
                                    <span class="input-group-addon">
                                    <span class="glyphicon glyphicon-user"></span>
                                    </span>
                                    <input type="email" class="form-control" placeholder="Email" name="userEmail" />
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="input-group">
                                    <span class="input-group-addon">
                                    <span class="glyphicon glyphicon-lock"></span>
                                    </span>
                                    <input type="password" class="form-control" placeholder="Password" name="userPassword" />

                                </div>

                            </div>

                            <div class="form-group text-center">
                                <button type="submit" class="btn btn-success btn-lg">Login</button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>

            <?php include 'footer.php'; ?>
        </div>
    </body>
</html>
