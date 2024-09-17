<?php

require_once( $_stusersession );

require_once( "st_pathdef.inc.php" );
require_once( $global_st_plugins['LDAP']['ldap_authentication'] );
require_once( $global_st_plugins['LDAP']['mindtake_ldap'] );

/*	---------------------------------------------------------------
			~~~~~~~~ Log-description for customID (LOGIN/LOGOUT)~~~~~~~~
			
		0	-	successful logged-in/logged-out
        1	-	wrong username
        2	-	wrong password
        3	-	Multiple Usernames found!
        4	-	Unknown error in LDAP authentication!
		5	-	you have no access to this data!
		6	-	Timeout
		7	-	access-error (ACCESS_ERROR)
		8	-	wrong access domain
		---------------------------------------------------------------	*/	

/**
 * abstract UserSession for LDAP-connection
 */
abstract class STLdapUserSession extends STUserSession
{
	var $aExistingGroups= array();
	/**
	 * current DomainAccess identification
	 * @var string
	 */
	var $sGroupType= null;
	

	/**
	 * STLdapUserSession constructor to create an object 
	 * only inside instance method <code>init()</code>
	 * 
	 * @param STObjectContainer $Db object of container or database to instantiate the session
	 * @param string $prefix prefix defined before every database table name
	 * @param string $ldapDomain domain which are used for ldap-connection 
	 */
	protected function __construct(&$Db, string $prefix= null, string $ldapDomain= null)
	{
		global	$_st_set_session_global;
		
		$_st_set_session_global= true;
		$this->sGroupType= $ldapDomain;
		STUserSession::__construct($Db, $prefix);
  	}
	abstract protected function getLDAP_AuthenticationObject() : object;
	protected function getFromOtherConnections(string $user, string $password, string $access_domain= "unknown", array &$userData= null)
	{// authentication check over LDAP-Server
	
		if(!isset($this->sGroupType))
		{
			// Error: class object not prepared to login with an LDAP-user
			return 1; // no user found for other connection
		}
		if(	$access_domain != "unknown" &&
			$this->sGroupType != $access_domain	)
		{
            // Error  1: no user found for other connection
			// Error 10: wrong access domain 
			return STUserSession::getFromOtherConnections($ID, $user, $password, $access_domain);
		}

		$ldapAuthentification= $this->getLDAP_AuthenticationObject();
		$ldapAuthentification->debug_= STCheck::isDebug("user");
    
		if(!isset($userData))
			$userData = array();
        $result= $ldapAuthentification->authenticate( $user, $password , $userData );
		if(Tag::isDebug("user"))
		{
		    Tag::echoDebug("user", "STLdapUserSession::authenticate('".$user."', [password], array(empty) )");
		    $space= Tag::echoDebug("user", "result from back data \$userData");
			st_print_r($userData, 10, $space+35);
		}
		if($result==0)
		{// User wurde Authentifiziert
  			if(Tag::isDebug("user"))
    		{
        	    // Debug -> Beim erfolgreichen login -> nur Daten Anzeigen
				echo "<h1>Login Successfull</h1>";
        	    echo "User's name : <em>".$userData['displayName']."</em><br />";
        	    echo "User's description : <em>".$userData['description']."</em><br />";
				echo "<br />";
        	    //echo "User's FullUserDN : <em>".$userData['FullUserDN']."</em><br />";
        	    //echo "User is member of following groups{<em>";
        	    //print_r($userData['member']);
				//	echo "</em>}<br />";
  			}
			return 0; // login success
		}
  		if( $this->isDebug() )
    	{
			echo "<h1>Login Incorrect</h1>";
			echo "return Error-Code ".$result." (".$this->getErrorString($result, $this->sGroupType).")<br />";
  		}	
		// Fehler !!
			// return 0: No Error User with Password found
            // Error  1: user not found for this other connection
            // Error  2: Wrong Password
            // Error  3: Multiple Usernames found!
            // Error  4: Unknown error in LDAP authentication!
			// Error  5: You have no access to this data. Please try an other user.
			// Error  6: User is inactive.
			// Error  7: Registration time is pass over.
			// Error  8: User in registration-mode, have to define new password.
			// Error  9: Unknown database error occured
			// Error 10: wrong access domain
		
		if($result == 1)
			return STUserSession::getFromOtherConnections($ID, $user, $password, $access_domain);
		return $result;
	}
	public function getErrorString(int $error_nr, string $domain= "unknown")
	{
		if(	$error_nr == 4 &&
			(	$domain == "unknown" ||
				$domain == $this->sGroupType	)	)
		{
			return "Unknown error in LDAP authentication occured!";
		}
		return STUserSession::getErrorString($error_nr, $domain);
	}
	function setProperties($ProjectName= "")
  	{
			global	$MUS_CLUSTER_MEMBERSHIP;
  						
			$this->setUserProject( $ProjectName );
			// deffiniere Gruppen-Array				
			if( !isset( $MUS_CLUSTER_MEMBERSHIP ) 
				and
				$this->sGroupType!="custom"	)
			{
				/**/if( $this->isDebug() ) 
				{
					echo "MUS_CLUSTER_MEMBERSHIP - sessionvar not set";
					echo "->start checking LDAP properties from scratch..<br />";
				}
				// �berpr�fe zuerst ob die ldap-Gruppen einem Projekt zugeordnet sind
				$this->checkLDAPGroups();
				
			}
			STUserSession::setProperties($ProjectName);		
  	}
  	public function hasAccess($authorisationString, $toAccessInfoString= null, $customID= null, $action= STALLDEF, $gotoLoginMask= false)
  	{
  	    if(!is_array($authorisationString))
  	        $authorisation= preg_split("/[, ]+/", $authorisationString, -1, PREG_SPLIT_NO_EMPTY);
  	    else
  	        $authorisation= $authorisationString;
  	    return STUserSession::hasAccess($authorisation, $toAccessInfoString, $customID, $action, $gotoLoginMask);
  	}
		function fillEldapGroups($groups)
		{
			$this->aExistingGroups= $groups;
		}
		function checkLDAPGroups()
		{

			/**/if( $this->isDebug() ){
				echo "entering checkLDAPGroups():<br />";
			}
			$label= $this->database->fetch_single("select Label from MUGroupType where ID='LKHGraz'");
			$aExistingGroups= array();
			foreach($this->aExistingGroups as $group)
			{	//�berpr�fe ob alle LKH-Gruppen existieren
				/**/if( $this->isDebug() ) echo "&nbsp;&nbsp;check if LDAP-Group already stored in MUGroup <em>$label$group</em>:";
				$statement= "select ID from MUGroup where Name='$label$group'";
				$ID= $this->database->fetch_single($statement);
				if(!$ID)
				{
					/**/if( $this->isDebug() ) echo "-&gt; NO -&lg; insert new Group into MUGroup<br />";
					$iStatement=  "insert into MUGroup(Name, DateCreation) ";
					$iStatement.= "values('$label$group',sysdate())";
					$this->database->solution($iStatement);

					$ID= $this->database->fetch_single($statement);
				} /**/else if( $this->isDebug() ) echo "CHECK<br />";
				$aExistingGroups[]= $ID;
			}
			$this->aExistingGroups= $aExistingGroups;
  	/**/if( $this->isDebug() ){ echo "&nbsp;updating all LKHGroups: "; print_r( $this->aExistingGroups ); }

			// �berpr�fe nun �bereinstimmigkeit
			// der LKH-Gruppen vom ELDAP-Server
			// mit der eigenen Datenbank UserManagement

			// erstelle neue LKHGroup wenn noch nicht vorhanden
			/**/if( $this->isDebug() ) echo "&nbsp;check for Membership of Current User in Groups<br />";
			foreach($aExistingGroups as $LKHGroup)
			{
				$statement= "select ID from MUUserGroup where UserID=".$this->userID;
				$statement.= " and GroupID=".$LKHGroup;
				/**/if( $this->isDebug() ) echo "&nbsp;&nbsp;<em>$LKHGroup</em>: ";
      			if(!$this->database->fetch_single($statement))
      			{	
      				$statement= "insert into MUUserGroup values(0,".$this->userID.",$LKHGroup,sysdate())";
      				$this->database->solution($statement);
      				/**/if( $this->isDebug() ) echo $statement."<br />";
      			} /**/else if( $this->isDebug() ) echo "CHECK<br />";
    		}
			// l�sche aus der Datenbank LKH-Gruppen in MUUserGroup
			// wenn der ELDAP-Server keine zugeh�rigkeit mehr anzeigt
			/**/if( $this->isDebug() ) echo "&nbsp;Check for revoked Group memberships:<br />";
			$statement=  "select u.GroupID from MUUserGroup as u, MUGroup as g";
			$statement.= " where u.UserID=".$this->userID;
			$statement.= " and u.GroupID=g.ID and g.Name like '$label%'";
			$aDBLKHGroups= $this->database->fetch_array($statement);
			foreach($aDBLKHGroups as $LKHGroup)
			{
			/**/if( $this->isDebug() )
			    {
			         echo "<em><pre>";
			         st_print_r($LKHGroup);
			         echo "</pre></em>: ";
			    }
    			if(!in_array($LKHGroup["GroupID"], $aExistingGroups))
    			{
    				$statement= "delete from MUUserGroup where UserID=".$this->userID;
    				$statement.= " and GroupID=".$LKHGroup["GroupID"];
    				$this->database->solution($statement);
    				/**/if( $this->isDebug() ) echo $statement."<br />";
    			}/**/else if( $this->isDebug() ) echo "CHECK (still active)<br />";
			}
			if( $this->isDebug() ) echo "Leaving checkLDAPGroups()<br />";
		} 
    function logHimOut($CustomID, $logTXT= "")
  	{
		STUserSession::logHimOut($CustomID, $logTXT);
		$this->aExistingGroups= null;
    }		
}
