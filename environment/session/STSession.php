<?php

require_once( $php_html_description );
require_once( $_stquerystring );


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
	var $loginSite= "";
	var $oLoginMask;
	/**
	 * whether session was generated
	 * and registered
	 * @var boolean $sessionRegistered
	 */
	private $sessionRegistered= false;
	var $noRegister= false;
	var $aSessionVars= array();
	/** 
	 * @var ID of project
	 */
	var $projectID= 0;
	/*
	 * @var name of project
	 */
	var $project= "";
	var $UserLoginMask= null;
	/**
	 * user currently try to logged in
	 * otherwise (no login) an null terminated string
	 * @var string
	 */
	var $loginUser= "";
	var $loginError= 0;


	protected function __construct()
  	{
		$this->aCluster= array();
		$this->startPage= "";
		
		$this->aSessionVars[]= "ST_LOGGED_IN";
		$this->aSessionVars[]= "ST_CLUSTER_MEMBERSHIP";
		$this->aSessionVars[]= "ST_EXIST_CLUSTER";
		$this->aSessionVars[]= "ST_USER_DEFINED_VARS";
  	}
  	/**
  	 * initial object of session
  	 * 
  	 * @param object $instance should be the database where the session will be stored, null,
  	 *                           or by overloading from an other class it can be the instance from there
  	 * @param string $prefix do not need, only defined because the init method need the same like the overloaded
  	 */
  	public static function init(&$instance= null, string $prefix= "")
	{
		global	$global_selftable_session_class_instance;
        
		STCheck::param($instance, 0, "STSession", "null");
		STCheck::alert(isset($global_selftable_session_class_instance[0]), 
		    "STSession::init()", "an session was defined before, cannot define two sessions");
		if(!typeof($instance, "STSession"))
		{
    		$global_selftable_session_class_instance[0]= new STSession();
		}else
		    $global_selftable_session_class_instance[0]= &$instance;
        return $global_selftable_session_class_instance[0];
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

		if(isset($global_selftable_session_class_instance[0]))
		    return $global_selftable_session_class_instance[0]->sessionRegistered;
		return false;
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
	/**
	 * do not make register process for debugging
	 */
	public function noRegister()
	{
		$this->noRegister= true;
	}
	/**
	 * whether object need registration or login
	 * 
	 * @return boolean wheter need registration
	 */
	public function needRegister() : bool
	{
		return !$this->noRegister;
	}
	protected function session_storage_place()
	{
	    global $php_session_save_path;
	    
	    if($php_session_save_path)
	        session_save_path($php_session_save_path);
	    if(STCheck::isDebug("session"))
	    {
	        $path= session_save_path();
	        STCheck::echoDebug("session", "Save session content on '$path'");
	    }
	}
  	function registerSession()
  	{
		global	$host,
				$HTTP_COOKIE_VARS,
				$php_session_name;

		if($this->noRegister)
			return;
		if(STCheck::warning(STSession::sessionGenerated(), "STSession::registerSession()", "cannot register session twice"))
		    return;
		STCheck::echoDebug("session", "register session");
		// save session on default harddisk position
		// or other places
		$this->session_storage_place();
		
		if($php_session_name)
		{
			$this->sessionName= $php_session_name;
			session_name($php_session_name);
		}else
			$this->sessionName= session_name();
		
		if(!isset($_SESSION))
		{
		    // WARNING: since my last test php version 8.1.2
		    //          if third parameter of session_set_cookie_params() is value null, not a null String "",
		    //          session_start() do not work
		    //          and otherwise also when correct (set only 2 parameters) time changing not works ???
		    session_set_cookie_params( 60*5, '/');		    
    		$bSetSession= session_start();
    		STCheck::end_outputBuffer();
    		$this->sessionRegistered= true;
		}else
		{
		    $bSetSession= false;
		    STCheck::echoDebug("session", "session was set before, do not start session again");
		}
		
	    if( STCheck::isDebug("user") ||
	        STCheck::isDebug("session")   )
		{
		    $debug= "user";
		    if(STCheck::isDebug("session"))
		        $debug= "session";
		    $space= STCheck::echoDebug($debug, "now <b>registerSession()</b>");
		    STCheck::echoDebug($debug, "register session variable ".$this->sessionName." on root '/' from host $host<b>".$_SERVER["HTTP_HOST"]."</b>");
			$msg= "session_start was";
			if(!$bSetSession)
				$msg.= " <b>not</b>";
			$msg.= " succefully";
			STCheck::echoDebug($debug, $msg);
			STCheck::echoDebug($debug, "session-ID on <b>var</b> ".$this->sessionName." is ".session_id());
			STCheck::echoDebug($debug, "session will be activated for ".session_cache_expire()." minutes");
			echo "<br />";
			STCheck::echoDebug($debug, "cookies set on host <b>".$_SERVER["HTTP_HOST"]."</b>");
			st_print_r($HTTP_COOKIE_VARS, 5, $space);
			STCheck::echoDebug($debug, "session variables are:");
		    st_print_r($_SESSION, 5, $space);
		    if($_SESSION == null)
		        echo "<br /><br />";
		}
  	}
	function getSessionID()
	{
		return session_id();
	}
	/**
	 * allow session variables defined inside URL<br />
	 * Enabling this setting prevents attacks involved passing session ids in URLs.
	 */
	public static function allowUrlSession()
	{
	    STCheck::echoDebug("session", "Enabling to allow session variables set also in <b>URL</b>");
	    ini_set("session.use_only_cookies", "0");
	}
	function isLoggedIn()
	{
	    $loggedin= false;
	    if( isset($_SESSION) &&
	        is_array($_SESSION) &&
	        isset($_SESSION["ST_LOGGED_IN"]) &&
	        $_SESSION["ST_LOGGED_IN"] 			)
	    {
	        $loggedin= true;
	    }
	    if(STCheck::isDebug("user"))
		{
		    STCheck::echoDebug("user", "entering ::isLoggedIn() ...");
			if(	$loggedin )
			{
				$string= "user is Logged, so return true";
			}else
				$string= "user is not logged, so return false";
			STCheck::echoDebug("user", $string);
		}
		return $loggedin;
	}
	public function needAccess($authorisationString, $toAccessInfoString, $customID= null)
	{
	    // method hasAccess() do not exit if gotoLoginMask is true
	    $this->hasAccess($authorisationString, $toAccessInfoString, $customID, /*gotoLoginMask*/true);
	}
	/**
	 * ask session whether have user currently access to specific CLUSTERs
	 * 
	 * @param string $authorisationString string or array of clusters defined inside usermanagement
	 * @param string $toAccessInfoString authorisation string logged by access into database
	 * @param string $customID ID saved also into database if need for different statistics
	 * @param enum $action access only for specified action (STINSERT/STUPDATE/STDELETE or STALLDEF)
	 * @param boolean $gotoLoginMask goto login mask if access not given, otherwise return false
	 * @return boolean return true when access given, otherwise by no access and variable <code>$gotoLoginMask = false</code> return false
	 */
	public function hasAccess($authorisationString, $toAccessInfoString= null, $customID= null, $action= STALLDEF, $gotoLoginMask= false)
	{
		STCheck::paramCheck($authorisationString, 1, "string", "array");
		STCheck::paramCheck($toAccessInfoString, 2, "string", "", "null");
		STCheck::paramCheck($customID, 3, "string", "int", "null");
		STCheck::paramCheck($action, 4, "bool", "string");
		STCheck::paramCheck($gotoLoginMask, 5, "bool");

		if(is_bool($action))
		{
		    $gotoLoginMask= $action;
		    $action= STALLDEF;
		}
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
		    if(STCheck::isDebug())
		    {
		        if(STCheck::isDebug("user"))
    		        $dbg_str= "user";
		        else
		            $dbg_str= "access";
    		    
		        STCheck::echoDebug($dbg_str, "-&gt; User do not have to be registered so return TRUE<br />");
		    }
			return true;
		}
		$cluster_membership= $this->getSessionVar("ST_CLUSTER_MEMBERSHIP");
		if(	isset( $cluster_membership[$this->allAdminCluster])
			and
			$this->isLoggedIn())
		{
		    if(STCheck::isDebug())
		    {
		        if(STCheck::isDebug("user"))
		            $dbg_str= "user";
	            else
	                $dbg_str= "access";
	                
                STCheck::echoDebug($dbg_str, "user has correct access to '$toAccessInfoString'");
		    }
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
		$aAccess= preg_split("/,/", $authorisationString);
		$clusterString= "";
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
		$cluster_membership= $this->getSessionVar("ST_CLUSTER_MEMBERSHIP");
		$staction= "unknown action";
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
			STCheck::echoDebug("user", "entering hasAccess(<b><em>&quot;".htmlspecialchars( $clusterString )."&quot;</b>, ".$sAccess.", ".$sID.", ".$staction."</em>)");
		}
		// alex 09/10/2005:	User muss nicht eingeloggt sein
		//					um auf Projekte zugriff zu haben
		//					habe Abfrage herausgenommen
		/*if(!$ST_LOGGED_IN)
		{
			$this->gotoLoginMask(0);
			exit;
		}*/
		$logincluster= false;
		$clusters= preg_split("/[\s,]/", $clusterString, -1, PREG_SPLIT_NO_EMPTY);
		if(typeof($this, "STUserSession"))
		{
		    // if searching for ONLINE-Cluster, ONLINE exist only as group,
		    // but user always online, so return true
		    if(in_array($this->getOnlineGroup(), $clusters))
		        return true;
		    // if searching for LOGGED_IN-Cluster, LOGGED_IN exist only as group,
		    // but if user is logged-in return true
		    if(in_array($this->getLoggedinGroup(), $clusters))
		    {
		        $logincluster= true;
		        if($this->isLoggedIn == true)
		            return true;
		    }
		}
		if(STCheck::isDebug())
		{
		    $cluster_exist= $this->getSessionVar("ST_EXIST_CLUSTER");
			foreach($clusters as $cluster)
			{
				$cluster= trim($cluster);
				STCheck::warning(!isset($cluster_exist[$cluster]) && !$logincluster, "STSession::access()",
										"cluster <b>\"</b>".$cluster."<b>\"</b> not exist in database", 1);
			}
		}
		if(	isset( $cluster_membership[$this->allAdminCluster])
			and
			$this->isLoggedIn())
		{
  			$this->LOG(STACCESS, $customID, $toAccessInfoString);
  			/**/Tag::echoDebug("user", "-&gt; User is Super-Admin &quot;allAdmin&quot; so return TRUE<br />");
  			return true;
  		}
		foreach($clusters as $cluster)
		{
		    if(isset( $cluster_membership[ trim($cluster) ]))
			{
				if($toAccessInfoString)
					$this->LOG(STACCESS, $customID, $toAccessInfoString);
				/**/Tag::echoDebug("user", "-&gt; User is Member of '$cluster' Cluster so return <b>TRUE</b>");
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
				if( isset($cluster_membership["ST_CLUSTER_MEMBERSHIP"]) &&
				    is_array($cluster_membership["ST_CLUSTER_MEMBERSHIP"]))
				{
				    foreach($cluster_membership as $dynamic_cluster=>$project)
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
					}// end of	foreach($cluster_membership["ST_CLUSTER_MEMBERSHIP"])
				}// end of if(is_array($cluster_membership["ST_CLUSTER_MEMBERSHIP"]))
			}// end of foreach($clusters)
		}// end of if($action!=STALLDEF)

		if($toAccessInfoString)
			$this->LOG(STACCESS_ERROR, $customID, $toAccessInfoString);
		if($gotoLoginMask)
		{
		    if(STCheck::isDebug())
		    {
		        $dbg= "user";
		        if(STCheck::isDebug("access"))
		            $dbg= "access";
		        elseif(STCheck::isDebug("session"))
		            $dbg= "session";
		        $msg= array();
		        $msg[]= "User is in none of the Specified Clusters";
		        $msg[]= "    <b>'$clusterString'</b>";
		        $msg[]= ",so goto LoginMask<br />";
			    STCheck::echoDebug($dbg, $msg);
		    }
			//$this->logHimOut(7, "ACCESS_ERROR");
			$this->gotoLoginMask(5);
		}
		/**/Tag::echoDebug("user", "User is in none of the Specified Clusters so return <b>FALSE</b>");
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
		$vars= array();
		foreach($this->aSessionVars as $var)
			$vars[]= $var;
		foreach($vars as $var)
			unset($this->aSessionVars[$var]);
		session_unset();
    }
	function echoSessionVars()
	{
		foreach($this->aSessionVars as $var)
		{
			echo $var.": ";
			var_dump($_SESSION[$var]);
			echo "<br />";
		}
	}
	public function getSessionVar($var1, $var2= null, $var3= null, $var4= null, $var5= null)
	{
	    if(isset($var5))
	    {
	        if(!isset($_SESSION[$var1][$var2][$var3][$var4][$var5]))
	            return null;
	        return $_SESSION[$var1][$var2][$var3][$var4][$var5];
	        
	    }else if(isset($var4))
	    {
	        if(!isset($_SESSION[$var1][$var2][$var3][$var4]))
	            return null;
	        return $_SESSION[$var1][$var2][$var3][$var4];
	        
	    }else if(isset($var3))
	    {
	        if(!isset($_SESSION[$var1][$var2][$var3]))
	            return null;
	        return $_SESSION[$var1][$var2][$var3];
	        
	    }else if(isset($var2))
	    {
	        if(!isset($_SESSION[$var1][$var2]))
	            return null;
	        return $_SESSION[$var1][$var2];	        
	    }
    	if(!isset($_SESSION[$var1]))
    	    return null;
    	return $_SESSION[$var1];
	}
	public function setSessionVar($var, $value)
	{
	    $_SESSION[$var]= $value;
	}
	public function setRecursiveSessionVar($value, $var1, $var2= null, $var3= null, $var4= null, $var5= null)
	{
	    if(isset($var5))
	    {
	        $_SESSION[$var1][$var2][$var3][$var4][$var5]= $value;
	        
	    }else if(isset($var4))
	    {
	        $_SESSION[$var1][$var2][$var3][$var4]= $value;
	        
	    }else if(isset($var3))
	    {
	        $_SESSION[$var1][$var2][$var3]= $value;
	        
	    }else if(isset($var2))
	    {
	        $_SESSION[$var1][$var2]= $value;
	        
	    }else
	    {
	        $_SESSION[$var1]= $value;	        
	    }
	}
	public function isSetRecursiveSessionVar($value, $var1, $var2= null, $var3= null, $var4= null, $var5= null) : bool
	{
	    if(isset($var5))
	    {
	        if($_SESSION[$var1][$var2][$var3][$var4][$var5] == $value)
	            return true;
	        
	    }else if(isset($var4))
	    {
	        if($_SESSION[$var1][$var2][$var3][$var4] == $value)
	            return true;
	        
	    }else if(isset($var3))
	    {
	        if($_SESSION[$var1][$var2][$var3] == $value)
	            return true;
	        
	    }else if(isset($var2))
	    {
	        if($_SESSION[$var1][$var2] == $value)
	            return true;
	        
	    }else
	    {
	        if($_SESSION[$var1] == $value)
	            return true;
	    }
	    return false;
	}
	public function addSessionVar($var, $value)
	{
	    if( !isset($_SESSION[$var]) ||
	        !is_array($_SESSION[$var]) )
	    {
	        $_SESSION[$var]= array();
	    }
	    $_SESSION[$var][]= $value;
	}
	public function addRecursiveSessionVar($value, $var1, $var2= null, $var3= null, $var4= null, $var5= null)
	{
	    if(isset($var5))
	    {
	        if( !isset($_SESSION[$var1][$var2][$var3][$var4][$var5]) ||
	            !is_array($_SESSION[$var1][$var2][$var3][$var4][$var5]) )
	        {
	            $_SESSION[$var1][$var2][$var3][$var4][$var5]= array();
	        }
	        $_SESSION[$var1][$var2][$var3][$var4][$var5][]= $value;
	        
	    }else if(isset($var4))
	    {
	        if( !isset($_SESSION[$var1][$var2][$var3][$var4]) ||
	            !is_array($_SESSION[$var1][$var2][$var3][$var4]) )
	        {
	            $_SESSION[$var1][$var2][$var3][$var4]= array();
	        }
	        $_SESSION[$var1][$var2][$var3][$var4][]= $value;
	        
	    }else if(isset($var3))
	    {
	        if( !isset($_SESSION[$var1][$var2][$var3]) ||
	            !is_array($_SESSION[$var1][$var2][$var3]) )
	        {
	            $_SESSION[$var1][$var2][$var3]= array();
	        }
	        $_SESSION[$var1][$var2][$var3][]= $value;
	        
	    }else if(isset($var2))
	    {
	        if( !isset($_SESSION[$var1][$var2]) ||
	            !is_array($_SESSION[$var1][$var2]) )
	        {
	            $_SESSION[$var1][$var2]= array();
	        }
	        $_SESSION[$var1][$var2][]= $value;
	        
	    }else
	    {
	        if( !isset($_SESSION[$var1]) ||
	            !is_array($_SESSION[$var1]) )
	        {
	            $_SESSION[$var1]= array();
	        }
	        $_SESSION[$var1][]= $value;
	    }
	}
	public function isSetRecursiveArraySessionVar($value, $var1, $var2= null, $var3= null, $var4= null, $var5= null) : bool
	{
	    if(isset($var5))
	    {
	        if( isset($_SESSION[$var1][$var2][$var3][$var4][$var5]) &&
	            is_array($_SESSION[$var1][$var2][$var3][$var4][$var5]) &&
	            in_array($value, $_SESSION[$var1][$var2][$var3][$var4][$var5])     )
	            return true;
	            
	    }else if(isset($var4))
	    {
	        if( isset($_SESSION[$var1][$var2][$var3][$var4]) &&
	            is_array($_SESSION[$var1][$var2][$var3][$var4]) &&
	            in_array($value, $_SESSION[$var1][$var2][$var3][$var4])     )
	            return true;
	            
	    }else if(isset($var3))
	    {
	        if( isset($_SESSION[$var1][$var2][$var3]) &&
	            is_array($_SESSION[$var1][$var2][$var3]) &&
	            in_array($value, $_SESSION[$var1][$var2][$var3])     )
	            return true;
	            
	    }else if(isset($var2))
	    {
	        if( isset($_SESSION[$var1][$var2]) &&
	            is_array($_SESSION[$var1][$var2]) &&
	            in_array($value, $_SESSION[$var1][$var2])     )
	            return true;
	            
	    }else
	    {
	        if( isset($_SESSION[$var1]) &&
	            is_array($_SESSION[$var1]) &&
	            in_array($value, $_SESSION[$var1])     )
	            return true;
	    }
	    return false;
	}
	function setExistCluster($cluster, $project)
	{
	    $this->setRecursiveSessionVar($project, "ST_EXIST_CLUSTER", $cluster);
	}
	function doClusterExist($cluster, $project) : bool
	{
	    return $this->isSetRecursiveSessionVar($project, "ST_EXIST_CLUSTER", $cluster);
	}
	function setMemberCluster($cluster, $projectName, $projectID)
	{
	    if( !isset($cluster) ||
	        !is_string($cluster) ||
	        trim($cluster) == ""   )
	    {
	        if(STCheck::isDebug("user"))
	        {
    	        STCheck::echoDebug("user", "cannot write null cluster into SESSION");
    	        showBackTrace();
	        }
	        return;
	    }
	    $memberCluster= array("ID"=>$projectID, "project"=>$projectName);
	    $this->setRecursiveSessionVar($memberCluster, "ST_CLUSTER_MEMBERSHIP", $cluster);
		$this->aCluster[$cluster]= $memberCluster;
	}
	function getExistClusters()
	{
	    return $this->getSessionVar("ST_EXIST_CLUSTER");
	}
	function getMemberClusters()
	{
	    return $this->getSessionVar("ST_CLUSTER_MEMBERSHIP");
	}
	function setProperties($ProjectName= "")
  	{
		/**/Tag::echoDebug("user", "entering STSession::setProperties ...");
		// define Login-Flag
  	    $this->setSessionVar("ST_LOGGED_IN", 1);
		$this->aExistCluster= $this->getExistClusters();
  	}
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
	/**
	 * verify project on session management
	 *  
	 * @param string|integer $project project as Name like in database, or as project ID<br />
	 *                                see UserManagement 'existing projects'
	 * @param string|Tag $login define URL where user can login into system 
	 *                          (address where STUserProjectManagement defined), or direct login mask as Tag objects 
	 * @return bool whether user be logged-in
	 */
	public function verifyLogin($project, $login) : bool
	{
	    STCheck::param($project, 0, "int", "string");
	    STCheck::param($login, 1, "string", "Tag");

	    $this->UserLoginMask= $login;
	    $loggedIn= $this->verifyProject($project);
	    return $loggedIn;
	}
	/**
	 * return error number by fault login, or elsewher 0.<br />
	 * <table>
	 *   <tr>
	 *     <td>
	 *       0
	 *     </td>
	 *       No Error defined.
	 *     <td>
	 *     </td>
	 *   </tr>
	 *   <tr>
	 *     <td>
	 *       1
	 *     </td>
	 *     <td>
	 *       This user name has no access.
	 *     </td>
	 *   </tr>
	 *   <tr>
	 *     <td>
	 *       2
	 *     </td>
	 *     <td>
	 *       Password is incorrect.
	 *     </td>
	 *   </tr>
	 *   <tr>
	 *     <td>
	 *       3
	 *     </td>
	 *     <td>
	 *       Multiple UserName in found!<br />
	 *       Please use also a domain separated with a backslash '\'
	 *     </td>
	 *   </tr>
	 *   <tr>
	 *     <td>
	 *       4
	 *     </td>
	 *     <td>
	 *       Unknown error in LDAP authentication!
	 *     </td>
	 *   </tr>
	 *   <tr>
	 *     <td>
	 *       5
	 *     </td>
	 *     <td>
	 *       You have no access to this data. Please try an other user.
	 *     </td>
	 *   </tr>
	 * </table>
	 * 
	 * @return login error number
	 */
	public  function getLoginError()
	{
	    return $this->loginError;
	}
	public function verifyProject(string $Project) : bool
	{
		global	$HTTP_GET_VARS,
				$HTTP_POST_VARS;
			

		if(!$this->sessionGenerated())
		    $this->registerSession();
		STCheck::echoDebug("user", "entering verifyLogin( <b>".print_r( $Project, /*return str*/true ). "</b> ): ...");
		if($this->noRegister)
		{
			STCheck::echoDebug("user", "disabled registration for DEBUGGING purposes");
			return false;
		}
		$this->project= $Project;
 		if(	isset($HTTP_POST_VARS[ "doLogout" ])
			or
			isset($HTTP_GET_VARS[ "doLogout" ]))
 		{
 			STCheck::echoDebug("user", "start performing LOGOUT");
 			$this->logHimOut(0);
 			if($this->loginSite != "")
 			{
 			    if(STCheck::isDebug())
 			    {
 			        echo "<h1>got login site <a href='".$this->loginSite."'>";
 			        echo $this->loginSite."</a></h1>";
 			        exit;
 			    }
 			    $this->gotoLoginMask(0);
 			    exit;
 			}
    	}elseif( isset( $HTTP_GET_VARS[ "timeout" ] ) )
		{
			/**/ if( STCheck::isDebug("user") ) echo "perform (javascript automatic timeout triggered ) LOGOUT<br />";
		    $this->logHimOut(6, "TIMEOUT");
/*		    if($this->loginSite != "")
		    {
		        if(STCheck::isDebug())
		        {
		            echo "<h1>got login site <a href='".$this->loginSite."'>";
		            echo $this->loginSite."</a></h1>";
		            exit;
		        }
		        $this->gotoLoginMask(0);
		        exit;
		    }*/
		}
		else if( 	isset($HTTP_POST_VARS[ "doLogin" ]) &&
					$HTTP_POST_VARS[ "doLogin" ] == 1 		)
	   	{// wir empfangen gerade ein eingegebenes login
		//Tag::debug("db.statement");
		//Tag::debug("user");
			/**/STCheck::echoDebug("user", "receiving new login data, start performing login verification");
			/**/STCheck::echoDebug("user", "set ST_USERID and ST_CLUSTER_MEMBERSHIP to NULL");
			$this->setSessionVar("ST_CLUSTER_MEMBERSHIP", null);
			$this->setSessionVar("ST_USERID", null);
			$this->loginUser= $HTTP_POST_VARS["user"];
			$error= $this->acceptUser($HTTP_POST_VARS["user"], $HTTP_POST_VARS["pwd"]);
			$this->loginError= $error;
      		if(!$error)
			{
				/**/ if( Tag::isDebug("user") )
				{
				    $msg= "....login Successfull, set Project to <em>$Project</em>, ";
				    $msg.= "update LastLogin and increase NrLogin counter";
					STCheck::echoDebug("user", $msg);
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
				$updater->update("LastLogin", "sysdate()");
				$updater->update("NrLogin", "NrLogin+1");
				$updater->where("ID=".$this->userID);
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
			//return false;
			//$this->gotoLoginMask($error);
			//exit;
    	}//else
    	{
    		/**/ if( STCheck::isDebug("user") )
			{
				$msg= "....no login status change, set properties to Project <em>";
				if(is_numeric($Project))
					$msg.= "Nr. ";
				$msg.= $Project."</em>,<br />";
				STCheck::echoDebug("user", $msg);
			}
			$this->setUserProject( $Project );
			$loggedin= $this->getSessionVar("ST_LOGGED_IN");
  			if( isset($loggedin) &&
  				$loggedin == 1 		)
  			{
  				/**/ STCheck::echoDebug("user", "user <b>logged in</b> return <b>TRUE</b>");
  			 	return true;
  		 	}else
			{
			    $cluster_membership= $this->getSessionVar("ST_CLUSTER_MEMBERSHIP");
			    if(	!isset($cluster_membership) ||
			        !is_array($cluster_membership) ||
			        !count($cluster_membership)       )
				{
					Tag::echoDebug("user", "read Cluster with ONLINE group staus from database");
					$this->readCluster();
					$this->setSessionVar("ST_CLUSTER_MEMBERSHIP", $this->aCluster);
				}else
				    $this->aCluster= $cluster_membership;
			}
  		 	/**/ STCheck::echoDebug("user", "user <b>not logged in</b> return <b>FALSE</b>");
			return false;
    	}
		STCheck::echoDebug("user", "end of verifyLogin, ST_LOGGED_IN is:");
		echo "end of verifyLogin, ST_LOGGED_IN is ";
		var_dump($this->getSessionVar("ST_LOGGED_IN"));echo "<br />";
		return false;
  	}
	function readCluster()
	{
		// function to overwrite
	}
	/**
	 * check wheter authentication with user password is correct
	 * 
	 * @param string $user user name set befor with STSession::allowedUser() method 
	 * @param string $password password also set like user before
	 * @return int login error code or 0 by correct user/password @see STSession::getLoginError()
	 */
	function acceptUser($user, $password) : int
    {
    	if(Tag::isDebug("user"))
        {
        	$pwd= getPlaceholdPassword($password);
            STCheck::echoDebug("user", "<b>entering acceptUser(<em>&quot;".$user."&quot;, &quot;".$pwd."&quot;,</em>)</b>");
        }
        $this->setSessionVar("ST_USER", $user);
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
		$this->setSessionVar("ST_PROJECTID", 0);
		$this->project= $ProjectName;

		// deffiniere User-Name
		$userID= $this->getSessionVar("ST_USERID");
		if(isset($userID))
		{//wenn ST_USERID gesetzt ist, weiss die Klasse
			$this->userID= $userID;//die UserID nicht.
			/**/Tag::echoDebug("user", "set userID from session-var ".$userID);
		}else// sonst wurde bereits eine Authentifizierung �ber Datenbank/ELDAP gemacht
		{
			if(isset($this->userID))
			{
				/**/Tag::echoDebug("user", "set ST_USERID from database to ".$this->userID);
				$this->setSessionVar("ST_USERID", $this->userID);
			}else
				STCheck::echoDebug("user", "no user ID be set ------------");
		}
		$user= $this->getSessionVar("ST_USER");
		if(isset($user))//selbiges!!
		{
			STCheck::echoDebug("user", "set user from session-var ".$this->getSessionVar("ST_USER"));
			$this->user= $user;
		}else
		{
			if(	isset($this->user) &&
				$this->user != ""		)
			{
				STCheck::echoDebug("user", "set user from session-var ".$this->user);
				$this->setSessionVar("ST_USER", $this->user);
			}else
				STCheck::echoDebug("user", "no user name be set ----------");
		}

	}
	function &getLoginMask($error)
	{
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

		if(	is_string($this->UserLoginMask) ) // should be an URL to the login-page
		{
			$query= new STQueryString();
			$query->delete("doLogout");
			$query->update("ERROR=".$error);
			$query->update("ProjectID=".$this->projectID);
			$Address= $this->UserLoginMask;
			$Address.= $query->getStringVars();
			if(Tag::isDebug() )
			{
				$body->add(br());
				$body->add(br());
				$h1= new H1Tag();
					$h1->add("user would be forwarded to:");
					$h1->add(br());
					$a= new ATag();
						$a->href(addslashes($Address));
						$a->target("_top");
						$a->add($Address);
					$h1->addObj($a);
				$body->addObj($h1);
	  		}else
			{
			    // Window-target not works ???
			    /*@header('Window-target:_top');
				@header("Location: $Address");
				exit;*/

				$body->add(br());
				$body->add(br());
				$h1= new H1Tag();
					$h1->add("Please login at:");
					$a= new ATag();
						$a->href(addslashes($Address));
						$a->target("_top");
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
						$table->add(new SpanTag("dynamic"));
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
				$typeT= $this->database->getTable("AccessDomain");
				$typeT->select("Label");
				$typeT->distinct();
				$selector= new STDbSelector($typeT);
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
	function &getLogoutButton($ButtonText, $class= "button")
  	{
        global $HTTP_SERVER_VARS;
        global $st_user_login_mask;

        $query= new STQueryString();
        $query->delete("show");
        $query->delete("ProjectID");
        $query->update("doLogout");
        if($this->isLoggedIn())
            $query->update("user=".$this->getUserName());
            
        $curUrl= $this->startPage.$query->getStringVars();
        STCheck::echoDebug("user", "set url for logout button to '$curUrl'");

		$button= new ButtonTag($class);
			$button->type("button");
			$button->add($ButtonText);
			$button->onClick("javascript:top.location.href='".$curUrl."'");

		return $button;
  	}
  	function setLoginAddress($toAddress)
  	{
  	    $this->loginSite= $toAddress;
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
	                $loggedmsg= $this->getSessionVar("ST_LOGGED_MESSAGES");
	                if(	!isset($loggedmsg) ||
	                	!is_array($loggedmsg)	)
	                {
	                        $this->setSessionVar("ST_LOGGED_MESSAGES", array());
	                }elseif( array_search($searchText, $loggedmsg) ||
	                         !$logText       														)
	                {// diese Seite wurde bereits geloggd
	                 // oder der Log ist nicht n�tig, da kein logText vorhanden
	                        return;
	                }
	                if($type != STDEBUG)
	                	$this->addSessionVar("ST_LOGGED_MESSAGES", $searchText);
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
    function writeLog($Type, $customID, $logText)
	{
    	if(!$this->sLogFile)
        	return;
		// alex 04/01/2006: toDo: logText in File schreiben
	}
}
