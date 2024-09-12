<?php

/**
 * testing first on command line connection
 * $> openssl s_client -connect <host>:<port>  \
 *                          -cert /<cert-path>/certificate.crt  \
 *                          -key /<cert-path>/certificate.key   \
 *                          -CAfile /<cert-path>/certificate.pem  \
 *                          -showcerts
 * for windows the command binary is openssl.exe
 * afterwards fill certificate options into this file
 * and start with $> php -f ldap_check_commandline.php
 *
 * @author Alexander Kolli
 * @since 12/09/2024 
 */


ldap_set_option(null, LDAP_OPT_DEBUG_LEVEL , 7);


//$ldap = ldap_connect("example.com", 938);
//$ldap = ldap_connect("example.com", 636);
$ldap = ldap_connect("ldaps://example.com:636");
ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
ldap_set_option($ldap, LDAP_OPT_X_TLS_CACERTFILE, '/<path to ca file or bundle>/tls-ca-bundle.pem');
ldap_set_option($ldap, LDAP_OPT_X_TLS_CERTFILE, '/<path to certificate>/public_certificate.crt');
ldap_set_option($ldap, LDAP_OPT_X_TLS_KEYFILE, '/<path to private key>/private.key');

//# Disable certificate verification
//ldap_set_option($ldap, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
//# try also to set 'TLS_REQCERT never' inside ldap.conf
//# if certificates are to weak, you have to do this

//# If ldap_start_tls() is necessary, do not use it by ldap_connect() with ldaps://
//ldap_start_tls($ldap);

$loginName= "<user name>";
$password= "<password>";
$baseDN= "ou=user,dc=example,dc=com";
//$baseDN= "dc=example,dc=com";
$userDN= "cn=$loginName,$baseDN";
$bind = ldap_bind($ldap, $userDN, $password);

echo "\n\n";
echo "LDAP-server was binding with user credentials:\n";
echo "'$userDN'\n";
echo "and password '$password'\n\n";
if ($bind) {
    echo "LDAP bind successful.\n\n";
} else {
    echo "LDAP bind failed: " . ldap_error($ldap)."\n\n";
}