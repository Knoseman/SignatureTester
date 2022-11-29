<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" style="height: 100%;">
<body>
<?php
function serialize_data($object) {
    $serialized = '';
    if( is_array($object) ) {
        ksort($object); //Sort keys
        foreach($object as $key => $value) {
            if(is_numeric($key)) $serialized .= serialize_data($value); //Array
            else $serialized .= $key . serialize_data($value); //Hash
        }
    } else return $object; //Scalar
    return $serialized;
}


function sign($method, $uuid, $data) {
    $merchant_private_key = openssl_get_privatekey(file_get_contents('keys/merchant_private_key.pem'));
    $plaintext = $method . $uuid . serialize_data($data);
    openssl_sign($plaintext, $signature, $merchant_private_key);
    return base64_encode($signature);
}

	if( $_POST['serialize'] ) {
		$data = json_decode($_POST['serialize'],true);
		if( is_null($data) ) echo 'ERROR_INVALID_JSON';
		else echo htmlentities(serialize_data($data));
	} else if( $_POST['sign'] ) {
		echo 'Plaintext:<hr />';
		echo htmlentities($_POST['method'] . $_POST['uuid'] . $_POST['sign']);
		echo '<hr />Signature:<hr />';
		echo sign($_POST['method'], $_POST['uuid'], $_POST['sign']);
	}
?>
</body>
</html>
