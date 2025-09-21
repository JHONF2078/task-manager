<?php
$passphrase = 'miapp';
$dir = __DIR__.'/../config/jwt';
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}
$config = [
    'private_key_bits' => 4096,
    'private_key_type' => OPENSSL_KEYTYPE_RSA,
];
$privateKey = openssl_pkey_new($config);
if ($privateKey === false) {
    fwrite(STDERR, "No se pudo generar la clave privada\n");
    exit(1);
}
if (!openssl_pkey_export($privateKey, $privateKeyPem, $passphrase)) {
    fwrite(STDERR, "No se pudo exportar la clave privada\n");
    exit(1);
}
$details = openssl_pkey_get_details($privateKey);
if ($details === false) {
    fwrite(STDERR, "No se pudo obtener la clave pública\n");
    exit(1);
}
$publicKeyPem = $details['key'];
file_put_contents($dir.'/private.pem', $privateKeyPem);
file_put_contents($dir.'/public.pem', $publicKeyPem);
chmod($dir.'/private.pem', 0600);
chmod($dir.'/public.pem', 0644);
echo "Claves JWT regeneradas correctamente con passphrase 'miapp'\n";

