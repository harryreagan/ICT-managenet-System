<?php
// In a real production environment, this key should be stored in an environment variable or a secure file outside the web root.
// For this setup, we'll define a constant here, but treat it with caution.
define('ENCRYPTION_KEY', 'your-secure-encryption-key-change-this');
define('ENCRYPTION_METHOD', 'AES-256-CBC');

function encryptData($data)
{
    $ivLength = openssl_cipher_iv_length(ENCRYPTION_METHOD);
    $iv = openssl_random_pseudo_bytes($ivLength);
    $encrypted = openssl_encrypt($data, ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, $iv);
    return base64_encode($encrypted . '::' . $iv);
}

function decryptData($data)
{
    if (!$data)
        return '';
    list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
    return openssl_decrypt($encrypted_data, ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, $iv);
}
?>