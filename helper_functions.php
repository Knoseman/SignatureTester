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
    $merchant_private_key = openssl_get_privatekey(file_get_contents('merchant_private_key.pem'));
    $plaintext = $method . $uuid . serialize_data($data);
    openssl_sign($plaintext, $signature, $merchant_private_key);
    return base64_encode($signature);
}

function verify($method, $uuid, $data, $signature_from_gluefinance) {
    $gluefinance_public_key = openssl_get_publickey(file_get_contents('gluefinance_public_key.pem'));
    $plaintext = $method . $uuid . serialize_data($data);
    return openssl_verify($plaintext,base64_decode($signature_from_gluefinance),$gluefinance_public_key);
}

function uuid()
{
    $chars = md5(uniqid(true));
    $uuid = substr($chars,0,8) . '-';
    $uuid .= substr($chars,8,4) . '-';
    $uuid .= substr($chars,12,4) . '-';
    $uuid .= substr($chars,16,4) . '-';
    $uuid .= substr($chars,20,12);
    return $uuid;
}

function API($method, $params) {
    $uuid = uuid();
    $signature = sign($method, $uuid, $params);
    $options = array('http' =>
        array(
            'method'  => 'POST',
            'header'  => 'Content-type: application/json',
            'content' => json_encode(array(
                'method' => $method,
                'params' => array(
                    'Signature' => $signature,
                    'UUID' => $uuid,
                    'Data' => $params
                ),
                'version' => 1.1
            ))
        )
    );

    $r = file_get_contents('https://test.gluefinance.com:8443/api/1', false, stream_context_create($options));
error_log(print_r($r,true));
error_log( $r );
    $r = json_decode($r,true);
    if( verify($r['result']['method'], $r['result']['uuid'], $r['result']['data'], $r['result']['signature']) ) return $r;
}

?>
