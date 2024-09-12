<?php 

/************************************************************************
 * 
 * LDAP Server - Autentification  / Object Wrapper
 * For username + password authentification with an LDAP server.
 *
 * Prerequisite for this class is an class for LDAP communication which
 * has already opened a connection to an ldap server, or 
 * has the username/password stored in it (because LDAP_Authentification class
 * will implicitely open a connection.
 *
 * @author	benjamin.zotter@mindtake.com
 * @created 	2004-06-01
 *
 * example of usage:
 * <pre>
 *	?><form action="<?=$PHP_SELF ?>" method="POST" >
 *		<input type="hidden" name="loginForm[submitted]" value="1" />
 *		Login Username: 
 *		<input type="text" name="loginForm[username]" value="<?=htmlspecialchars( $loginForm['username'] )?>" />
 *		<br />
 *		Login Password:
 *		<input type="password" name="loginForm[password]" value="<?=htmlspecialchars( $loginForm['password'] )?>" />
 *		<br />
 *		<input type="submit" value="Login&nbsp;&gt;&gt;" />
 *	</form><?
 *	if( $loginForm['submitted'] ){
 *		
 *		$ldapAuthentification = new LDAP_Authentification( $ldap );
 *		//--if you want to now what the class(es) do: enable debugging:
 *		$ldap->debug_ = true;
 *		$ldap->showWarnings_ = true;
 *		$ldapAuthentification->debug_ = true;	
 *		$userData = array();
 *		switch( $ldapAuthentification->authenticate( $loginForm['username'], $loginForm['password'] , $userData ) ){
 *		case 0:
 *			echo "<h1>Login Successfull</h1>";
 *			echo "User's name : <em>".$userData['FullName']."</em><br />";
 *			echo "User's description : <em>".$userData['Description']."</em><br />";
 *			echo "User's FullUserDN : <em>".$userData['FullUserDN']."</em><br />";
 *			echo "User is member of following groups{<em>";
 *			foreach( $userData['InGroups'] as $group){
 *				echo ", $group";
 *			} echo "</em>}";
 *			break;
 *
 *		case 1:
 *			echo "<h1>Error: Wrong Username</h1>";
 *			break;	
 *		case 2:
 *			echo "<h1>Error: Wrong Password</h1>";
 *			break;		
 *		case 3:
 *			echo "<h1>Error: Multiple Usernames found!</h1>";
 *			break;		
 *		default:
 *			echo "<h1>Error: Unknown error in LDAP authentication!</h1>";
 *			break;		
 *
 *		}
 *
 *		echo "Test will end now, good by!";
 *	}		
 *
 * </pre>
 *
**/

/**
 * php7 ldap connect and bind via TLS
 * @see https://stackoverflow.com/questions/51615665/php7-ldap-connect-and-bind-via-tls
 * 
 * @example
 *  $ldap="localhost";
 *  $port=636;
 *  $usr="CN=admin";
 *  $pwd="pwd123";
 *
 *  $ds=ldap_connect("$ldap", $port); 
 *  $ldapbind=false;
 *  // for debugging
 *  ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 9);
 *  if(ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3))
 *      if(ldap_set_option($ds, LDAP_OPT_X_TLS_REQUIRE_CERT, 0))
 *          if(ldap_set_option($ds, LDAP_OPT_REFERRALS, 0))
 *              if(ldap_start_tls($ds))
 *                  $ldapbind = @ldap_bind($ds, $usr, $pwd);   
 *  ldap_close($ds);
 *
 *  if(!$ldapbind)
 *          echo "BIND ERROR!\n";
 *  else
 *          echo "BIND OK!\n";
 *          
 * @author stackoverflow
 *
 */
class LDAP_Authentication {
	var $ldapServer;
	var $debug_= false;
	//--------------------------------------------------------------------
	function __construct( &$ldapServer )
	{
		//echo "create LDAP_Authentification<br />";
		$this->ldapServer = &$ldapServer;
	}
	//--------------------------------------------------------------------
	function extractNameFromDisplayName( $displayName ){
		preg_match("/^([0-9]*,)?(.*)/", $displayName, $preg);
		return $preg[2];		
	}	
	//--------------------------------------------------------------------
	function authenticate( $username, $password, &$userData ){

		if( isset( $userData ) ) 
			$userData = array();

		//if( $this->ldapServer->initialize_connection() ) echo "<br /><b>Successfull Connect!</b>";
			

		if(STCheck::isDebug())
		{
			$this->ldapServer->showWarnings_ = true;
			if( Tag::isDebug("user") )
				$this->ldapServer->debug_ = true;
		}

		if( !$this->ldapServer->bind() ){
			$this->ldapServer->reportFatalError(
				"Could not connect to LDAP Server, please try again later",
				"In LDAP_Authentification class, method authenticate()"
			);
		}
		if( Tag::isDebug("user") )
		{
			$msg= array();
			$msg[]= "->authenticate():";
			$msg[]= " null binding with standard user was OK";
			$msg[]= " ... searching now for username:$username with password";
			STCheck::echoDebug("user", $msg);
		}
		$this->ldapServer->search( "(&(sAMAccountName=".$username.")(objectClass=user))" );		

		
		if( $this->ldapServer->rowCount() < 1 )
		{
			if( Tag::isDebug("user") )
			{ 
				echo "<br />ERROR: no such User (with Username: ";
				echo htmlspecialchars( $username )." )";
			}
			return 1;
		}elseif( $this->ldapServer->rowCount() > 1 )
		{
			if( Tag::isDebug("user") )
			{
				echo "<br />ERROR: Username ambiguous (".$this->ldapServer->rowCount();
				echo "with Usernames like: %".htmlspecialchars( $username )."% )";
			}
			return 3;
		}else
		{
			if( Tag::isDebug("user") ) 
				echo "<br />Found Username: will try to use users password:<br />";
			$this->ldapServer->next_record();
			$foundData= $this->ldapServer->getAllUserData();

			if( Tag::isDebug("user") ) 
				echo "will unbind now:";
			$this->ldapServer->unbind();
			$this->ldapServer->disconnect();
			if( Tag::isDebug("user") ) 
				echo "will try to bind with provided password :<br />";
			if( !isset( $password ) OR trim( $password ) == '' )
			{
				if( Tag::isDebug("user") )
					echo "<br />empty password received ";
				/*if($this->ldapServer->bindAnonymously())
				{ ToDo: alle vorhandenen UserNamen k�nnen Anonym gebunden werden
						funktion muss noch f�r Kinderrad erweitert werden
						dass diese mit keinem Passwort einsteigen k�nnen
					echo "so do binding Anonymously<br />";
					return 0;
				}else*/
				if( isset( $userData ) AND is_array( $userData ) )
				{					
					$userData = array(
                				'Error' => 2, 'ErroMsg' => 'Wrong Password'	);
				}
				echo "so do not accept password without eventrying to really bind<br />";
            	return 2;
			}else
			{
				if( $this->ldapServer->bind( "", $password ) )
				{
					if( Tag::isDebug("user") )
						echo "<br /><b>Successfull Bind!</b>";

					if( isset( $userData ) AND is_array( $userData ) )
					{
						$userData = $foundData;
					}
					return 0;
				}else
				{
				    if(STCheck::isDebug("user"))
				    {
				       echo "<br /><br />";
					   STCheck::echoDebug("user", "<b>unsuccessfull bind!</b>");
				    }
					if( isset( $userData ) AND is_array( $userData ) )
					{
						$userData = array(
							'Error' => 2, 'ErroMsg' => 'Wrong Password'	);
					}
					return 2;
				}
			}
		}
		//return -1;
	}




}
	
?>
