<?php

	const ACCESS_TOKEN  = '1558tglfq9uupxbpeyxneytcgo4rnaz';
	const CLIENT_ID 	= 'iqrday31i72teyghj0bf5vtgnqua9k4';
	const CLIENT_SECRET = '2pdpmj61qwxxro1cwl5r4gx01eeqk6d';
	const STORE_HASH 	= 'dl52lpq';
	
	const USER_NAME = 'pay-through-invoice';
	const API_PATH = 'https://api.bigcommerce.com/stores/'.STORE_HASH.'/v3/';

	// const STORE_URL = 'https://update-price.mybigcommerce.com/';
	const STORE_URL = 'https://www.eightcig.com/';

	const EXPRESS_LOGIN = 'remote.php?w=expressCheckoutLogin';

	const AUTHORIZE_CLIENTKEY = "4d8LEWnMa2FbNx3ahJnu255JNDU2UAHpXqc7xp4XQ6hu2BfQJrY4BjcbwCYYf2b9";
	const AUTHORIZE_API_LOGIN_ID = "7Zd6wz9HaZu4";

	function debug($data) {
		highlight_string("<?php\n\$data =\n" . var_export($data, true) . ";\n?>");
		exit();
	}
?>