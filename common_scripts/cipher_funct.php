<?php
//==============================================================================

define('CIPHER_METHOD', 'aes-128-ctr');

//==============================================================================

function encrypt_with_password($plain_text,$password)
{
    $key = md5($password);
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(CIPHER_METHOD));
    $encrypted_content = openssl_encrypt($plain_text, CIPHER_METHOD, $key, 0, $iv).'::'.bin2hex($iv);
    return base64_encode($encrypted_content);
}

//==============================================================================

function decrypt_with_password($cipher_text,$password)
{
    $cipher_text = base64_decode($cipher_text);
    $pos = strpos($cipher_text,'::');
    if ($pos === false) {
        //return openssl_decrypt($encrypted_content, CIPHER_METHOD, $key);
        return "*** Invalid Cipher Text ***";
    }
    else {
        $encrypted_content = substr($cipher_text,0,$pos);
        $key = md5($password);
        $iv = hex2bin(substr($cipher_text,$pos+2));
        return openssl_decrypt($encrypted_content, CIPHER_METHOD, $key, 0, $iv);
    }
}

//==============================================================================
