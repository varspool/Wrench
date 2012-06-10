<?php

namespace WebSocket\Protocol;

/**
 * SSL helper functions
 */
class Ssl
{
    /**
     * Generates a self signed SSL certificate
     *
     * @param string $file
     * @param string $passphrase
     */
    public static function generateSelfSignedCert($file, $passphrase)
    {

        if(!file_exists($pem_file))
        {
            $dn = array(
                "countryName" => "DE",
                "stateOrProvinceName" => "none",
                "localityName" => "none",
                "organizationName" => "none",
                "organizationalUnitName" => "none",
                "commonName" => "foo.lh",
                "emailAddress" => "baz@foo.lh"
            );
            $privkey = openssl_pkey_new();
            $cert    = openssl_csr_new($dn, $privkey);
            $cert    = openssl_csr_sign($cert, null, $privkey, 365);
            $pem = array();
            openssl_x509_export($cert, $pem[0]);
            openssl_pkey_export($privkey, $pem[1], $pem_passphrase);
            $pem = implode($pem);
            file_put_contents($pem_file, $pem);
        }
    }
}
