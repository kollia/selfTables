<?


require_once( $php_html_description );
require_once( $php_htmltag_class );


/*	-------------------------------------------------------------------
		~~~~~~~~ Logbeschreibung für customID (LOGIN/LOGOUT)~~~~~~~~

			0	-	erfolgreich eingelogt/ausgelogt
        	1	-	falscher Username
        	2	-	falsches Passwort
        	3	-	Multiple Usernames found!
        	4	-	Unknown error in LDAP authentication!
			5	-	Sie haben keinen Zugriff auf diese Daten!
			6	-	Timeout
			7	-	Zugriffs-Fehler (ACCESS_ERROR)
	-------------------------------------------------------------------	*/
function global_sessionGenerated()
{
	global	$global_selftable_session_class_instance;
	
	if(isset($global_selftable_session_class_instance))
	{
		if(isset($global_selftable_session_class_instance[0]))
		{
			return true;
		}
	}
	return false;
}

class STSession
{
	var $allAdminCluster= "allAdmin"; // user hat zugriff auf alle Bereiche
	var $isLoggedIn= false;
    var $sessionName = 'PHPSESSID';
	var $aCluster;
	var $aExistCluster;
	var $startPage;
	var $oLoginMask;
	var $noRegister= false;
	var $aSessionVars= array();
	var	$session_vars;	// hier wird eine Referenz von HTTP_SESSION_VARS gesetzt,
						// oder wenn _st_set_session_global auf true steht
						// werden alle Globalen Session-Variblen aus aSessionVars
						// mit einer Referenz darin gespeichert
	/** 
	 * @var ID of project
	 */
	var $projectID= 0;
	/*
	 * @var name of project
	 */
	var $project= "";
	var $UserLoginMask= null;


	function STSession($private)
  	{
		Tag::alert($private!="selfTables_STSession_private_String", "STSession::constructor()",
								"class STSession is private, choose STSession::init()");
		$this->aCluster= array();
		$this->startPage= "";

		$this->aSessionVars[]= "ST_LOGGED_IN";
		$this->aSessionVars[]= "ST_CLUSTER_MEMBERSHIP";
		$this->aSessionVars[]= "ST_EXIST_CLUSTER";
		$this->aSessionVars[]= "ST_USER_DEFINED_VARS";
  	}
	public static function init()
	{
		global	$global_selftable_session_class_instance;

		if(isset($global_selftable_session_class_instance[0])) {
			Tag::warning($global_selftable_session_class_instance[0], "STSession::init()",
									"session was already created");							}

		$global_selftable_session_class_instance[0]= new STSession("selfTables_STSession_private_String");
	}
	public static function &instance()
	{
		global $global_selftable_session_class_instance;

		Tag::alert(	!isset($global_selftable_session_class_instance[0]), "STSession::instance()",
								"no session created, invoke first STSession::init()");
		return $global_selftable_session_class_instance[0];
	}
	static function sessionGenerated()
	{
		/**
		 * when this function making problems!
		 * Strict Standards:  Non-static method STSession::sessionGenerated() should not be called statically
		 * there is also a globaly method global_sessionGenerated() which do the same
		 */
		global	$global_selftable_session_class_instance;

		if(isset($global_selftable_session_class_instance))
			if(isset($global_selftable_session_class_instance[0]))
				return true;
		return false;
	}
	function setVarInSession($var, $value)
	{
	    $this->session_vars["ST_USER_DEFINED_VARS"]["var"][$var]= $value;
	}
	function &getVarFromSession($var)
	{
	    return $this->session_vars["ST_USER_DEFINED_VARS"]["var"][$var];
	}
	function startPage($url)
	{
		if($url)
			$this->startPage= $url;
	}
	function getStartPage()
	{
		global $client_root;

		return $client_root.$this->startPage;
	}
	function noRegisterForDebug($startPage= "")
	{
		$this->noRegister= true;
		if($startPage!="")
			$this->startPage= $startPage;
	}
	function mustRegister()
	{
		return !$this->noRegister;
	}
  	function registerSession()
  	{
		global	$host,
				$HTTP_COOKIE_VARS,
				$php_session_name,
				$php_session_save_path,
				$_st_set_session_global;

		if($this->noRegister)
			return;
		$client_root= "http://".$host;
		if($php_session_save_path)
			session_save_path($php_session_save_path);
		if($php_session_name)
		{
			$this->sessionName= $php_session_name;
			session_name($php_session_name);
		}else
			$this->sessionName= session_name();

		if(Tag::isDebug())
		{// if defined Debug-Session,
			$bSetSession= @session_start();// warning for cookie set is normaly
		}else
			$bSetSession= session_start();
		session_set_cookie_params( 0, '/', $client_root);		
		register_global_SESSION_VAR($globalVar);
		$this->session_vars= &$globalVar;
		//echo "session will be activated for ".session_cache_expire()." minutes<br>";

		/**/if( Tag::isDebug("user") )
		{
			echo "<b>entering registerSession()</b><br />";
			echo "register session variable ".$this->sessionName." on root '/' from host <b>".$_SERVER["HTTP_HOST"]."</b><br />";
			echo "session_start was";
			if(!$bSetSession)
				echo " <b>not</b>";
			echo " succefully<br />";
			echo "session-ID on <b>var</b> ".$this->sessionName." is ".session_id()."<br />";
			echo "session will be activated for ".session_cache_expire()." minutes<br>";
			echo "<br />";
			echo "cookies set on host <b>".$_SERVER["HTTP_HOST"]."</b><br />";
			var_dump($HTTP_COOKIE_VARS);
			echo "<br />";
		}
		//$this->LOG(STACCESS, 0, $session_txt);
  	}
	function getSessionID()
	{
		return session_id();
	}
	function isLoggedIn()
	{
		if(Tag::isDebug())
		{
			Tag::echoDebug("user", "entering ::isLoggedIn() ...");
			if(	isset($this->session_vars["ST_LOGGED_IN"]) &&
				$this->session_vars["ST_LOGGED_IN"]				)
			{
				$string= "user is Logged, so return true";
			}else
				$string= "user is not logged, so return false";
			Tag::echoDebug("user", $string);
		}
      	if( isset($this->session_vars["ST_LOGGED_IN"]) &&
      		$this->session_vars["ST_LOGGED_IN"] 			)
      	{
	  		return true;
      	}else
	  	  	return false;
	}
	function hasAccess($authorisationString, $toAccessInfoString, $customID= null, $gotoLoginMask= false, $action= STALLDEF)
	{
		STCheck::paramCheck($authorisationString, 1, "string", "array");
		STCheck::paramCheck($toAccessInfoString, 2, "string", "", "null");
		STCheck::paramCheck($customID, 3, "string", "int", "null");
		STCheck::paramCheck($gotoLoginMask, 4, "bool");
		STCheck::paramCheck($action, 5, "string");

		//Tag::alert($action==STALLDEF, "STSession::access()", "asking by action STAlldef");
		if(is_array($authorisationString))
		{
			foreach($authorisationString as $cluster)
			{
				if(!$this->access($cluster, $toAccessInfoString, $customID, $gotoLoginMask, $action))
					return false;
			}
			return true;
		}
		return $this->access($authorisationString, $toAccessInfoString, $customID, $gotoLoginMask, $action);
	}
	function hasProjectAccess($authorisationString, $toAccessInfoString= null, $customID= null)
	{
		Tag::deprecated("is died", "STSession::hasProjectAccess()");
		if($this->noRegister)
		{
			/**/Tag::echoDebug("user", "-&gt; User must not be registered so return TRUE<br />");
			return true;
		}
		if(	isset( $this->session_vars["ST_CLUSTER_MEMBERSHIP"][$this->allAdminCluster])
			and
			$this->isLoggedIn())
		{
  			$this->LOG(STACCESS, $customID, $toAccessInfoString);
  			/**/Tag::echoDebug("user", "-&gt; User is Super-Admin &quot;allAdmin&quot; so return TRUE<br />");
  			return true;
  		}
		if(!count($this->aProjectAccessCluster))
		{
			$sCluster= $this->sClusterIDColumn;
			$sAuthorisation= $this->sAuthorisationColumn;
			$db= $this->projectAccessTable->getDatabase();
			$statement= $db->getStatement($this->projectAccessTable);
			//echo $statement."<br />";
			$result= $db->fetch_array($statement, MYSQL_ASSOC);
			foreach($result as $row)
				$this->aProjectAccessCluster[$row[$sAuthorisation]]= $row[$sCluster];
			//print_r($this->aProjectAccessCluster);echo "<br />";
		}
		$aAccess= split(",", $authorisationString);
		$clusterString= "";
		$bRv= false;
		foreach($aAccess as $autho)
		{
			$autho= trim($autho);
			if($autho!=="")
			{
				$cluster= $aAccess[$autho];
				if($cluster)
				{
					/**/Tag::echoDebug("user", "for project-authorisation ".$autho." need cluster ".$cluster);
					$clusterString.= ",".$cluster;
				}else
				{// $autho k�nnte selebst shon ein Cluster sein
					$clusterString.= ",".$autho;
				}
			}
		}
		$clusterString= substr($clusterString, 1);//echo __FILE__.__LINE__."<br />ask for $clusterString<br />";
		return $this->access($clusterString, $toAccessInfoString, $customID);
	}
	// alex 06/05/2005:	Funktionsname von hasAccess auf access ge�ndert,
	//					da jetzt in hasAccess zwischen access und hasProjectAccess
	//					unterschieden wird
	function access($clusterString, $toAccessInfoString= null, $customID= null, $gotoLoginMask= false, $action= STALLDEF)
	{
		Tag::paramCheck($clusterString, 1, "string");
		Tag::paramCheck($toAccessInfoString, 2, "string", "null");
		Tag::paramCheck($customID, 3, "string", "int", "null");
		Tag::paramCheck($gotoLoginMask, 4, "bool");

		//Tag::alert($action==STALLDEF, "STSession::access()", "asking by action STAlldef");
		if($this->noRegister)
		{
			/**/Tag::echoDebug("user", "-&gt; User must not be registered so return TRUE<br />");
			return true;
		}
		/**/if( Tag::isDebug("user") )
		{
			$sAccess= $toAccessInfoString;
			if($sAccess===NULL)
				$sAccess= "NULL";
			else
				$sAccess= htmlspecialchars("\"".$sAccess."\"");
			$sID= $customID;
			if($sID===NULL)
				$sID= "NULL";
			else
				$sID= htmlspecialchars("\"".$sID."\"");
			if($action==STLIST)
				$staction= "STLIST";
			elseif($action==STUPDATE)
				$staction= "STUPDATE";
			elseif($action==STINSERT)
				$staction= "STINSERT";
			elseif($action==STDELETE)
				$staction= "STDELETE";
			elseif($action==STALLDEF)
				$staction= "STALLDEF";
			elseif($action==STADMIN)
				$staction= "STADMIN";
			echo "<b>[</b>user<b>]:</b> <b>entering hasAccess(<em>&quot;".htmlspecialchars( $clusterString )."&quot;, ".$sAccess.", ".$sID.", ".$staction."</em>)</b><br />";
			echo "<b>[</b>user<b>]:</b> User is currently member of following clusters: ";
			if(is_array($this->session_vars["ST_CLUSTER_MEMBERSHIP"]) && count($this->session_vars["ST_CLUSTER_MEMBERSHIP"]))
			{
				foreach( $this->session_vars["ST_CLUSTER_MEMBERSHIP"] as $myCluster=>$Project )
					echo $myCluster.";";
			}else
				echo "<em>NO CLUSTER</em>";
			echo "<br />";
			echo "<b>[</b>user<b>]:</b> sessionvar ST_LOGGED_IN is ";
			var_dump($this->session_vars["ST_LOGGED_IN"]);echo "<br />";
		}
		// alex 09/10/2005:	User muss nicht eingeloggt sein
		//					um auf Projekte zugriff zu haben
		//					habe Abfrage herausgenommen
		/*if(!$ST_LOGGED_IN)
		{
			$this->gotoLoginMask(0);
			exit;
		}*/
		$clusters= preg_split("/[\s,]/", $clusterString, -1, PREG_SPLIT_NO_EMPTY);
		if(STCheck::isDebug())
		{
			foreach($clusters as $cluster)
			{
				$cluster= trim($cluster);
				STCheck::warning(!isset($this->session_vars["ST_EXIST_CLUSTER"][$cluster]), "STSession::access()",
										"cluster <b>\"</b>".$cluster."<b>\"</b> not exist in database", 1);
				STCheck::warning(	isset($this->session_vars["ST_EXIST_CLUSTER"][$cluster])
									and
									$this->session_vars["ST_EXIST_CLUSTER"][$cluster]!==$this->projectID
									and
									$this->project!=0														, "STSession::access()",
											"cluster <b>\"</b>".$cluster."<b>\"</b> is not for the current project"						);

			}
		}
		if(	isset( $this->session_vars["ST_CLUSTER_MEMBERSHIP"][$this->allAdminCluster])
			and
			$this->isLoggedIn())
		{
  			$this->LOG(STACCESS, $customID, $toAccessInfoString);
  			/**/Tag::echoDebug("user", "-&gt; User is Super-Admin &quot;allAdmin&quot; so return TRUE<br />");
  			return true;
  		}
		foreach($clusters as $cluster)
		{
			if(isset( $this->session_vars["ST_CLUSTER_MEMBERSHIP"][ trim($cluster) ]))
			{
				if($toAccessInfoString)
					$this->LOG(STACCESS, $customID, $toAccessInfoString);
				/**/Tag::echoDebug("user", "-&gt; User is Member of '$cluster' Cluster so return TRUE<br />");
				return true;
			}
		}
		if($action!=STALLDEF)
		{
			Tag::echoDebug("user", "member has no direct access to any clusters and action is $staction, so check for dynamic cluster");

			/*if($action==STUPDATE)
				$action= STADMIN;
			elseif($action==STINSERT)
				$action= STADMIN;
			elseif($action==STDELETE)
				$action= STADMIN;*/
			foreach($clusters as $cluster)
			{
				Tag::echoDebug("access", "look for dynamic access to cluster <b>$cluster</b>");
				if(is_array($this->session_vars["ST_CLUSTER_MEMBERSHIP"]))
				{
					foreach($this->session_vars["ST_CLUSTER_MEMBERSHIP"] as $dynamic_cluster=>$project)
					{
						Tag::echoDebug("access", "with having cluster <b>$dynamic_cluster</b>");
						if($action==STLIST)
						{
							$cl= preg_quote($cluster);
							$dyn_cl= preg_quote($dynamic_cluster);
							//echo "preg_match('/^$cl\_/', $dynamic_cluster)<br />";
							if(	preg_match("/^".$cl."\_/", $dynamic_cluster)
								or
								preg_match("/^".$dyn_cl."\_/", $cluster)	)
							{
            					if($toAccessInfoString)
            						$this->LOG(STACCESS, $customID, $toAccessInfoString);
            					/**/Tag::echoDebug("user", "-&gt; User is Member of '$cluster' with dynamic Cluster '$dynamic_cluster', so return TRUE<br />");
            					return true;
							}
						}else // else for if($action==STLIST)
						{
							$cl= preg_quote($dynamic_cluster);
							//echo "preg_match('/^$cl\_/', $dynamic_cluster)<br />";
							if(preg_match("/^".$cl."\_/", $cluster))
							{
            					if($toAccessInfoString)
            						$this->LOG(STACCESS, $customID, $toAccessInfoString);
            					/**/Tag::echoDebug("user", "-&gt; User is Member of '$cluster' width dynamic Cluster '$dynamic_cluster', so return TRUE<br />");
            					return true;
							}
						} // end of if($action==STLIST)
					}// end of	foreach($this->session_vars["ST_CLUSTER_MEMBERSHIP"])
				}// end of if(is_array($this->session_vars["ST_CLUSTER_MEMBERSHIP"]))
			}// end of foreach($clusters)
		}// end of if($action!=STALLDEF)

		if($toAccessInfoString)
			$this->LOG(STACCESS_ERROR, $customID, $toAccessInfoString);
		if($gotoLoginMask)
		{
			/**/Tag::echoDebug("user", "User is in none of the Specified Clusters so goto LoginMask<br />");
			//$this->logHimOut(7, "ACCESS_ERROR");
			$this->gotoLoginMask(5);
		}
		/**/Tag::echoDebug("user", "User is in none of the Specified Clusters so return FALSE<br />");
		return false;
	}
    function logHimOut($CustomID, $logTXT= "")
  	{

		/**/Tag::echoDebug("user", "clear all Session-Vars");
		$this->setUserProject($this->project);
		$this->LOG(STLOGOUT, $CustomID, $logTXT);
		$this->isLoggedIn= false;
  		$this->userID= null;
		$sGroupType= null;// wird nur beim ersten Login benutzt
		$this->projectID= null;
		$this->aCluster= null;
		//$this->echoSessionVars();
		foreach($this->aSessionVars as $var)
		{
			session_unregister($var);
			$$var= null;
		}
    }
	function echoSessionVars()
	{
		foreach($this->aSessionVars as $var)
			global $$var;

		foreach($this->aSessionVars as $var)
		{
			echo $var.": ";
			var_dump($$var);
			echo "<br />";
		}
	}
	function setExistCluster($cluster, $project= 1)
	{
		$this->session_vars["ST_EXIST_CLUSTER"][$cluster]= $project;
	}
	function setMemberCluster($cluster, $projectName, $projectID= 1)
	{
		$this->session_vars["ST_CLUSTER_MEMBERSHIP"][$cluster]= array("ID"=>$projectID, "project"=>$projectName);
		$this->aCluster[$cluster]= $this->session_vars["ST_CLUSTER_MEMBERSHIP"][$cluster];
	}
	function getExistClusters()
	{
		return $this->session_vars["ST_EXIST_CLUSTER"];
	}
	function getMemberClusters()
	{
		return $this->session_vars["ST_CLUSTER_MEMBERSHIP"];
	}
  	function setProperties()
  	{
		/**/Tag::echoDebug("user", "entering STSession::setProperties ...");
		// define Login-Flag
		$this->session_vars["ST_LOGGED_IN"]= 1;
		$this->aExistCluster= $this->getExistClusters();
  	}
		/*function checkForLoggedIn()
		{
			$userID= $this->userID;

			// hole ID aus der Datenbank f�r Gruppe LOGGED_IN
			$statement= "select ID from MUGroup where Name='LOGGED_IN'";
			$loggedIn= $this->database->fetch_single($statement);
			if(!$loggedIn)
			{
				echo "<b>ERROR:</b> no LOGGED_IN Group exist in table MUGroup<br />";
			}
			// kontrolliere ob dort User eingetragen ist
			$statement= "select ID from MUUserGroup where UserID=$userID and GroupID=$loggedIn";
			if(!$this->database->fetch_single($statement))
			{// wenn nicht eingetragen, dann du es jetzt
				$statement= "insert into MUUserGroup values(0, $userID, $loggedIn, sysdate())";
				$this->database->fetch($statement);
			}
		}*/
	function getFromOtherConnections($foundedID, $user, $password, $groupType)
	{// diese Funktion ist zum �berladen verschiedener �berpr�fungen
	 // user sollte f�r die n�chste session gespeichert werden
	 // und die ID muss in $this->userID eingetragen werden

		// Fehler !!
			// return 0: No Error User with Password found
            // Error  1: Wrong Username
            // Error  2: Wrong Password
            // Error  3: Multiple Usernames found!
            // Error  4: Unknown error in LDAP authentication!
			return 1;
	}
	function verifyLogin($Project= 1)
	{
		global	$HTTP_POST_VARS,
				$HTTP_COOKIE_VARS,
				$HTTP_SERVER_VARS;

		STCheck::paramCheck($Project, 1, "string", "int");

		//$sessionName = $this->sessionName;
		$result = $this->private_verifyLogin( $Project);

		return $result;
	}
	function private_verifyLogin($Project)
	{
		/**/ if( STCheck::isDebug("user") ){ echo "<b>entering verifyLogin( ";print_r( $Project ); echo " ):</b> ..<br />"; }
		global	$HTTP_SERVER_VARS,
				$HTTP_GET_VARS,
				$HTTP_POST_VARS,
				$HTTP_COOKIE_VARS;

		if($this->noRegister)
		{
			/**/ if( STCheck::isDebug("user") ) echo "disabled registration for DEBUGGING purposes<br /><br />";
			return;
		}
		$this->project= $Project;
 		if(	isset($HTTP_POST_VARS[ "doLogout" ])
			or
			isset($HTTP_GET_VARS[ "doLogout" ]))
 		{
 			/**/ if( STCheck::isDebug("user") ) echo "start performing LOGOUT<br />";
 			$this->logHimOut(0);
			$this->gotoLoginMask(0);
			exit;
    	}elseif( isset( $HTTP_GET_VARS[ "timeout" ] ) )
		{
			/**/ if( STCheck::isDebug("user") ) echo "perform (javascript automatic timeout triggered ) LOGOUT<br />";
			$this->logHimOut(6, "TIMEOUT");
			$this->gotoLoginMask(0);
			exit;
		}
		else if( 	isset($HTTP_POST_VARS[ "doLogin" ]) &&
					$HTTP_POST_VARS[ "doLogin" ] == 1 		)
	   	{// wir empfangen gerade ein eingegebenes login
		//Tag::debug("db.statement");
		//Tag::debug("user");
			/**/Tag::echoDebug("user", "receiving new login data, start performing login verification");
			/**/Tag::echoDebug("user", "set ST_USERID and ST_CLUSTER_MEMBERSHIP to NULL");
			$this->session_vars["ST_CLUSTER_MEMBERSHIP"]= null;
			$this->session_vars["ST_USERID"]= null;
    	   	$error= $this->acceptUser($HTTP_POST_VARS["user"], $HTTP_POST_VARS["pwd"]);
      		if(!$error)
			{
				/**/ if( Tag::isDebug("user") )
				{
					echo "....login Successfull, set Project to <em>$Project</em>, ";
					echo "update LastLogin and increase NrLogin counter<br />";
				}


				$this->setProperties( $Project );
				$userTable= $this->database->getTable("User");
				/*$userTable->clearSelects();
				$userTable->clearGetColumns();
				$userTable->select("currentLogin");
				$selector= new OSTDbSelector($userTable);
				$selector->execute();
				$last= $selector->getSingleResult();*/
				$updater= new STDbUpdater($userTable);
				$updater->update("LastLogin", "currentLogin");
				$updater->update("currentLogin", "sysdate()");
				$updater->update("NrLogin", "NrLogin+1");
				$updater->execute();
				/*$statement=  "update ".$this->sUserTable." set LastLogin=sysdate(), NrLogin= NrLogin+1 ";
				$statement.= "where ID=".$this->userID;
				$this->database->fetch($statement);*/
				$this->LOG(STLOGIN, 0);
      			return true;
			}
			/**/ if( Tag::isDebug("user") ) echo "....login FAILED: <em>$error</em><br />";
			$this->setUserProject($Project);
			$user= $this->user;
			if(!isset($user))
				$user= "unknown";
			$this->LOG(STLOGIN_ERROR, $error);
			$this->gotoLoginMask($error);
			exit;
    	}else
    	{
    		/**/ if( Tag::isDebug("user") )
			{
				echo "....no login status change, set properties to Project <em>";
				if(is_numeric($Project))
					echo "Nr. ";
				echo $Project."</em>,<br />";
			}
			$this->setUserProject( $Project );
  			if( isset($this->session_vars["ST_LOGGED_IN"]) &&
  				$this->session_vars["ST_LOGGED_IN"] == 1 		)
  			{
  				/**/ if( Tag::isDebug("user") )
						echo "user logged in return TRUE<br />";
  			 	return true;
  		 	}else
			{
				if(	!isset($this->session_vars["ST_CLUSTER_MEMBERSHIP"]) ||
					!$this->session_vars["ST_CLUSTER_MEMBERSHIP"]			)
				{
					Tag::echoDebug("user", "read Cluster with ONLINE group staus from database");
					$this->readCluster();
					$this->session_vars["ST_CLUSTER_MEMBERSHIP"]= $this->aCluster;
				}else
					$this->aCluster= $this->session_vars["ST_CLUSTER_MEMBERSHIP"];
			}
  		 	/**/ if( Tag::isDebug("user") )
					echo "user not logged in return FALSE<br />";
			return false;
    	}
		echo "end of verifyLogin, ST_LOGGED_IN is ";
		var_dump($this->session_vars["ST_LOGGED_IN"]);echo "<br />";
  	}
	function readCluster()
	{
		// function to overwrite
	}
	function acceptUser($user, $password)
    {
    	if(Tag::isDebug("user"))
        {
        	$pwd= str_repeat("*", strlen($password));
            echo "<b>entering acceptUser(<em>&quot;".$user."&quot;, &quot;".$pwd."&quot;,</em>)</b><br />";
        }
        $this->session_vars["ST_USER"]= $user;
        $this->user= $user;
        if($this->aUsers[$user])
        {
        	$ID= $this->aUsers[$user]["ID"];
            $group= $this->aUsers[$user]["group"];
        }
        if( Tag::isDebug("user") )
        {
        	if(isset($ID))
            {
            	echo "user be set from developer in \$this->aUsers<br />";
			}else
            	echo "user not be set from developer in \$this->aUsers<br />";
		}
        if(	!isset($ID)   )
		{
        	Tag::echoDebug("user", "user not set in class, so check accepting about ->getFromOtherConnections()");

            $result= $this->getFromOtherConnections($ID, $user, $password, $group);
            //echo "accept is $result";exit;
            return $result;
		}
        //kein �berpr�fung �ber LDAP-Server
        if( !$ID )
        	return 1;// kein User mit diesem Namen vorhanden
        if($this->aUsers[$user]["password"]!==$password)
        	return 2;// Passwort ist falsch
        $this->sGroupType= $group;
        $this->userID= $ID;
        $this->user= $user;
        //$this->checkForLoggedIn();
        return 0;
	}
	function allowedUser($user, $password, $ID= null, $groupType= null)
	{
    	if($ID===null)
        {
        	$ID= count($this->aUsers);
        	++$ID;
        }
        $this->aUsers[$user]= array();
        $this->aUsers[$user]["password"]= $password;
        $this->aUsers[$user]["ID"]= $ID;
        if($groupType!==null)
        	$this->aUsers[$user]["group"]= $groupType;
	}
  	function setUserProject($ProjectName)
	{
		/**/Tag::echoDebug("user", "<b>entering setUserProject(</b>$ProjectName<b>)</b>");

		$this->projectID= 0;
		$this->session_vars["ST_PROJECTID"]= 0;
		$this->project= $ProjectName;

		// deffiniere User-Name
		if(isset($this->session_vars["ST_USERID"]))
		{//wenn ST_USERID gesetzt ist, weiss die Klasse
			$this->userID= $this->session_vars["ST_USERID"];//die UserID nicht.
			/**/Tag::echoDebug("user", "set userID from session-var ".$this->session_vars["ST_USERID"]);
		}else// sonst wurde bereits eine Authentifizierung �ber Datenbank/ELDAP gemacht
		{
			if(isset($this->userID))
			{
				/**/Tag::echoDebug("user", "set ST_USERID from database to ".$this->userID);
				$this->session_vars["ST_USERID"]= $this->userID;
			}else
				STCheck::echoDebug("user", "no user ID be set ------------");
		}
		if(isset($this->session_vars["ST_USER"]))//selbiges!!
		{
			STCheck::echoDebug("user", "set user from session-var ".$this->session_vars["ST_USER"]);
			$this->user= $this->session_vars["ST_USER"];
		}else
		{
			if(	isset($this->user) &&
				$this->user != ""		)
			{
				STCheck::echoDebug("user", "set user from session-var ".$this->user);
				$this->session_vars["ST_USER"]= $this->user;
			}else
				STCheck::echoDebug("user", "no user name be set ----------");
		}

	}
	function &getLoginMask($error)
	{
		global $st_user_login_mask;
		global $HTTP_SERVER_VARS;

		STCheck::paramCheck($error, 1, "int");

		$url= $HTTP_SERVER_VARS["SCRIPT_NAME"];
		$html= new HtmlTag();
			$head= new HeadTag();
				$title= new TitleTag();
					$title->add("Login side");
				$head->add($title);
			$html->addObj($head);
			$body= new BodyTag();

		if(	is_string($this->UserLoginMask)
			||
			is_string($st_user_login_mask)	)
		{
			$get= new STQueryString();
			$get->delete("doLogout");
			$get->insert("ERROR=".$error);
			$get->insert("from=".$url);
			$get->noSth("ERROR");
			$get->noSth("from");
			$Address= $st_user_login_mask;
			if(is_string($this->UserLoginMask))
				$Address= $this->UserLoginMask;
			$Address.= $get->getStringVars();
			if(Tag::isDebug() )
			{
				$body->add(br());
				$body->add(br());
				$h1= new H1Tag();
					$h1->add("user would be forwarded to:");
					$h1->add(br());
					$a= new ATag();
						$a->href(addslashes($Address));
						$a->add($Address);
					$h1->addObj($a);
				$body->addObj($h1);
	  		}else
			{
				@header("Location: $Address");

				$body->add(br());
				$body->add(br());
				$h1= new H1Tag();
					$h1->add("Please login at:");
					$a= new ATag();
						$a->href(addslashes($Address));
						$a->add("Startpage");
					$h1->addObj($a);
				$body->addObj($h1);
				$script = new ScriptTag();
					$script->add("top.location.href='".addslashes($Address)."'");
				$body->addObj($script);
			}
			$html->addObj($body);
			return $html;
		}
		if(!typeof($this->UserLoginMask, "HtmlTag"))
		{
				$form = new FormTag();
					$form->action("");
					$form->method("post");
					$table= new st_tableTag();
						$table->border(0);
						$table->width("100%");
						$table->add(" ");
						$table->columnHeight("150");
						$table->nextRow();
						$table->addObj(new SpanTag("dynamic"));
						$table->columnAlign("center");
						$logTable= new st_tableTag("logtable");
							$logTable->border(0);
							$logTable->add("user:");
							$logTable->columnAlign("right");
							$nameInput= new InputTag();
								$nameInput->type("text");
								$nameInput->name("user");
								$nameInput->tabindex(1);
							$logTable->addObj($nameInput);
							$submit= new InputTag();
								$submit->type("submit");
								$submit->value("Login");
								$submit->tabindex(3);
							$logTable->addObj($submit);
							$logTable->nextRow();
							$logTable->add("password:");
							$logTable->columnAlign("right");
							$pwdInput= new InputTag();
								$pwdInput->type("password");
								$pwdInput->name("pwd");
								$pwdInput->tabindex(2);
							$hiddenInput= new InputTag();
								$hiddenInput->type("hidden");
								$hiddenInput->name("doLogin");
								$hiddenInput->value(1);
							$logTable->addObj($pwdInput);
							$logTable->addObj($hiddenInput);
			if(typeof($this, "STUserSession"))
			{
				$typeT= $this->database->getTable("GroupType");
				$typeT->select("Label");
				$typeT->distinct();
				$selector= new OSTDbSelector($typeT);
				$selector->execute();
				$result= $selector->getRowResult();
				if(count($result) > 1)
				{
								$pwdInput->tabindex(3);

							$select= new SelectTag();
								$select->name("grouptype");
								$select->size(1);
								$select->tabindex(2);
					foreach($result as $row)
					{
								$option= new OptionTag();
									$option->add($row);
								$select->add($option);
					}
							$logTable->nextRow();
							$logTable->add("&#160;");
							$logTable->addObj($select);
							$logTable->columnAlign("center");
				}
			}
						$table->addObj($logTable);
						$table->columnAlign("center");
					$form->addObj($table);
				$body->addObj($form);
			$html->addObj($body);
			$this->UserLoginMask= &$html;
		}else
			$html= &$this->UserLoginMask;

		//$html->getTa
		return $html;
	}
	function gotoLoginMask($error= 0)
	{
		global	$HTTP_SERVER_VARS,
				$HTTP_GET_VARS,
				$st_user_login_mask,
				$st_user_navigator_mask;

		$nParam= 0;
		$bFromSet= false;
		$bErrorSet= false;
		$bUserSet= false;
		$loginMask= &$this->getLoginMask($error);
		$loginMask->display();
  		exit();
	}
	function setUserLoginMask($address)
	{
		STCheck::paramCheck($address, 1, "string", "Tags");

		STCheck::echoDebug("user", "set UserLoginMask to ".$address);
		$this->UserLoginMask= $address;
	}
	function &getLogoutButton($ButtonText, $class= null)
  	{
        global $HTTP_SERVER_VARS;
        global $st_user_login_mask;

		$startPage= $this->startPage;
		if(preg_match("/\?/", $startPage))
			$delimiter= "&";
		else
			$delimiter= "?";
		$startPage.= $delimiter;
		$query= $HTTP_SERVER_VARS["QUERY_STRING"];
		//echo "startPage:$startPage<br />query:$query<br />";
		if($query)
			$startPage.= $query."&";
		$startPage.= "doLogout";
		//echo "finishd:$startPage";exit;

		$button= new ButtonTag($class);
			$button->type("button");
			$button->add($ButtonText);
			$button->onClick("javascript:self.location.href='".$startPage."'");

		return $button;
  	}
  	function setLoginAddress($toAddress)
  	{
  		$this->startPage($toAddress);
  	}
	function getLoginAddress($toAddress= null)
	{
        global	$HTTP_SERVER_VARS,
        		$st_user_login_mask;

        //echo "   user login: $toAddress<br>";
        //echo "   main login: $st_user_login_mask<br>";
        //echo "defined login: ".$this->startPage."<br>";
        //echo " script login: ".$HTTP_SERVER_VARS["SCRIPT_NAME"]."<br>";
		$param= new STQueryString();
		if(!$toAddress)
			$toAddress= $this->startPage;
		if(!$toAddress)
			$toAddress= $st_user_login_mask;
		$param->insert("from=".$HTTP_SERVER_VARS["SCRIPT_NAME"]);
		$param->insert("user=".$this->getUserName());
		$param->insert("ERROR=0");
		$address= $toAddress.$param->getStringVars();
		return $address;
	}
	function getLoginButton($ButtonText= "LOGIN", $class= null, $toAddress= null)
	{
		$address= $this->getLoginAddress($toAddress);
		$button= new InputTag($class);
			$button->type("button");
			$button->onClick("javascript:self.location.href='".$address."'");
			$button->value($ButtonText);
		return $button;
	}
	function getTimeoutRoutine($min)
	{
		global $HTTP_SERVER_VARS;

		if($this->noRegister)
			return new PTag();
		$toAddress= $This->startPage;
		if(!isset($toAddress))
			$toAddress= $HTTP_SERVER_VARS["PHP_SELF"];
		if(preg_match("/\?/", $toAddress))
			$toAddress.= "&";
		else
			$toAddress.= "?";
		$min= $min*1000;//= secunden
		$min= $min*60;//= minuten
		$toAddress.= "timeout=1";
		$script= new ScriptTag();
		$script->type("text/javascript");
		$string= "setTimeout(\"window.location='";
		$string.= $this->startPage."?timeout=1'\", $min);";
		$script->add($string);

		return $script;
	}
	function isDebug($value= "user")
	{
		/**///return true;
		return Tag::isDebug($value);
	}
	function LOG($type, $customID= null, $logText= "")
	{
                Tag::paramCheck($type, 1, "check",//$type!=STACCESS,
				($type==STDEBUG||$type==STLOGIN||$type==STLOGIN_ERROR||$type==STLOGOUT||$type==STACCESS||$type==STACCESS_ERROR),
                                "STDEBUG", "STLOGIN", "STLOGIN_ERROR", "STLOGOUT", "STACCESS", "STACCESS_ERROR");
                Tag::paramCheck($customID, 2, "int", "null");
                Tag::paramCheck($logText, 3, "string", "null", "empty(string)");

                if($type != STACCESS)
                {
	                $searchText= $type." ".$customID." ".$logText;
	                if(	!isset($this->session_vars["ST_LOGGED_MESSAGES"]) ||
	                	!is_array($this->session_vars["ST_LOGGED_MESSAGES"])	)
	                {
	                        $this->session_vars["ST_LOGGED_MESSAGES"]= array();
	                }elseif( array_search($searchText, $this->session_vars["ST_LOGGED_MESSAGES"]) ||
	                         !$logText       														)
	                {// diese Seite wurde bereits geloggd
	                 // oder der Log ist nicht n�tig, da kein logText vorhanden
	                        return;
	                }
	                if($type != STDEBUG)
	                	$this->session_vars["ST_LOGGED_MESSAGES"][]= $searchText;
                }
                if($type==STDEBUG)
                        $Typ= "'DEBUG'";
                elseif($type==STLOGIN)
                        $Typ= "'LOGIN'";
                elseif($type==STLOGIN_ERROR)
                        $Typ= "'LOGIN_ERROR'";
                elseif($type==STLOGOUT)
                        $Typ= "'LOGOUT'";
                elseif($type==STACCESS)
                        $Typ= "'ACCESS'";
                elseif($type==STACCESS_ERROR)
                        $Typ= "'ACCESS_ERROR'";
                else
                {
                        echo "<br><div align='center'>unknown user-logtyp <b>($type)</b></div><br>";
                        exit();
                }
                $user= $this->userID;
                if(!isset($user))
                        $user= 0;
                $project= $this->projectID;
                if(!isset($project))
                        $project= 0;
                if(!isset($customID))
                        $customID= "NULL";
                if($logText!="")
                        $logText.= " ";
                $logText.= "(user:".$this->user.", project:".$this->project.")";
                $this->writeLog($Typ, $customID, $logText);
    }
	function writeLog($Typ, $customID, $logText, $url)
	{
    	if(!$this->sLogFile)
        	return;
		// alex 04/01/2006: toDo: logText in File schreiben
	}
}
