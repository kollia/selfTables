# LDAP connection

first try a correct connection by modify script ldap_check_commandline.php<br />
and start with
```
php -f ldap_check_commandline.php
```
<br /><br />
adapt afterwards LDAPServer_Connection_template.inc.php 
```
cp LDAPServer_Conncetion_template.inc.php LDAPServer_Connection.inc.php
```
and fill with knowing new options

<br /><br /><br /><br />
create new server class for new Session object
```php
<?php

$_ldap= load_pluginModule("LDAP");
require_once( $_ldap['ldap_authentication'] );
require_once( $_ldap['_stldapusersession'] );
require_once( 'LDAPServer_Connection.inc.php' );

class MyLdapSession extends STLdapUserSession
{
	/**
	 * Instantiating session object for LDAP-connection.
	 * 
	 * @param STObjectContainer $Db object of container or database to instantiate the session
	 * @param string $prefix prefix defined before every database table name
	 * @param string $ldapDomain domain which will be used for ldap-connection.<br />
	 *                           If domain not set, method <code>getFromOtherConnectios()</code> 
	 *                           from object STLdapUserSession return always 1 for no user found
	 */
	public static function init(&$Db= null, $prefix= null, string $domain= null)
	{        
		$instance= new MyLdapSession($Db, $prefix, $domain);
		STUserSession::init($instance, $prefix);
	}
	protected function getLDAP_AuthenticationObject() : object
	{
	    $ldapConfig= new LDAPServer_Connection();
		
        $ldapObj = new LDAP_Authentication( $ldapConfig );
		return $ldapObj;
	}
	protected function getFromOtherConnections(string $user, string $password, string $domain= null, array &$userData= null)
	{// authentication check over LDAP-Server
	
		$userData= array(); // by right connection, get back all attributes
		$res= STLdapUserSession::getFromOtherConnections($user, $password, $domain, $userData);
		if($res > 0)// by ERROR return code
			return $res;

        /*
            code to fill user table if user not exist before
            and create new access groups with connection to user,
            only when get from LDAP server
            ...
        */

        return 0;
    }
}
```

<br /><br />
now instad of creating session with <code>STUserSession()</code>
create session with your new LDAP session class <code>MyLdapSession()</code>
before the UserManagement or your other projects
(<b>Tip:</b> if you create own session before <code>STProjectUserSiteCreator()</code>. object do not create new one)
```php
$db= new STDbMariaDb(); // database object for UserManagement
$prefix= "ST"; // prefix for tables inside database$domain= "LDAP"; // domain for witch user and domains should be created,
               // you have to fill this domain name also per hand into AccessDomain table of database
MyLdapSession::init($db, $prefix, $domain);

```

