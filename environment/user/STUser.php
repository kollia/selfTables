<?php


require_once( $php_html_description );
require_once( $php_htmltag_class );


/*	---------------------------------------------------------------
		~~~~~~~~ Logbeschreibung f�r customID (LOGIN/LOGOUT)~~~~~~~~
		
		0	-	erfolgreich eingelogt/ausgelogt
        1	-	falscher Username
        2	-	falsches Passwort
        3	-	Multiple Usernames found!
        4	-	Unknown error in LDAP authentication!
		5	-	Sie haben keinen Zugriff auf diese Daten!
		6	-	Timeout
		7	-	Zugriffs-Fehler (ACCESS_ERROR)
		---------------------------------------------------------------	*/

class STUser
{
	var $allAdminCluster= "allAdmin";
	var $database= null;
	var $isLoggedIn= false;
	var $user;
	var $userID;
    var $sessionName = 'PHPSESSID';
	var $sGroupType= null;// wird nur beim ersten Login benutzt
	var $project;
	var $projectID;
	var $aCluster;
	var $aExistCluster;
	var $aGroups;
	var $startPage;
	var $bLog;
	var $noRegister= false;
	var $aSessionVars= array();
	
	// all for own Projects
	var $projectAccessTable;
	var $sProjectIDColumn= "ProjectID";
	var $sClusterIDColumn= "ClusterID";
	var $sAuthorisationColumn= "Authorisation";
	var $aProjectAccessCluster= array();
  
	function __construct(&$Db)
  	{
		$this->aCluster= array();
   		$this->database= &$Db;
		$this->startPage= "";
		$this->bLog= true;
			
		$this->aSessionVars[]= "ST_LOGGED_IN";
		$this->aSessionVars[]= "ST_USER";
		$this->aSessionVars[]= "ST_USERID";
		$this->aSessionVars[]= "ST_PROJECTID";
		$this->aSessionVars[]= "ST_CLUSTER_MEMBERSHIP";
		$this->aSessionVars[]= "ST_EXIST_CLUSTER";
		$this->aSessionVars[]= "ST_LOGGED_MESSAGES";
  	}
	function &getUserDb()
	{
		return $this->database;
	}
	function startPage($url)
	{
		if($url)
		{
			$this->startPage= $url;
		}
	}
	function getStartPage()
	{
		global $client_root;
		
		return $client_root.$this->startPage;
	}
	function makeTableMeans()
	{
		//$db= &$this->database;
		$Project= &$this->database->needTable("MUProject");
			$Project->identifColumn("Name", "Projekt");
		$Cluster= &$this->database->needTable("MUCluster");
			$Cluster->identifColumn("ID", "Cluster");
			$Cluster->foreignKey("ProjectID", "MUProject");
		$ClusterGroup= &$this->database->needTable("MUClusterGroup");
			$ClusterGroup->foreignKey("ClusterID", "MUCluster");
			$ClusterGroup->foreignKey("GroupID", "MUGroup");
		$Group= &$this->database->needTable("MUGroup");
			$Group->identifColumn("Name", "Gruppe");
		$GroupType= &$this->database->needTable("MUGroupType");
			$GroupType->identifColumn("Label", "Type");
		$UserGroup= &$this->database->needTable("MUUserGroup");
			$UserGroup->foreignKey("GroupID", "MUGroup");
			$UserGroup->foreignKey("UserID", "MUUser");
		$User= &$this->database->needTable("MUUser");
			$User->identifColumn("GroupType");
			$User->identifColumn("UserName", "User");
			$User->identifColumn("FullName", "Name");
		$Log= &$this->database->needTable("MULog");
			$Log->foreignKey("UserID", "MUUser");
			$Log->foreignKey("ProjectID", "MUProject");
	}
	function setLog($bLog)
	{
		$this->bLog= $bLog;
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
				$_SERVER,
				$php_session_name,
				$php_session_save_path;
		
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
		
		if(Tag::isDebug())// if defined Debug-Session,
			$bSetSession= @session_start();// warning for cookie set is normaly
		else
			$bSetSession= session_start();
		session_set_cookie_params( 0, '/', $client_root);
		$_SESSION['ST_LOGGED_IN']= false;
		$_SESSION['ST_USER']= "";
		$_SESSION['ST_USERID']= -1;
		$_SESSION['ST_PROJECTID']= -1;
		$_SESSION['ST_CLUSTER_MEMBERSHIP']= null;
		$_SESSION['ST_EXIST_CLUSTER']= null;
		$_SESSION['ST_LOGGED_MESSAGES'] = "";
			
		/**/if( Tag::isDebug("user") )
		{
			echo "<b>entering registerSession()</b><br />";
			echo "register session variable ".$this->sessionName." on root '/' from host <b>".$client_root."</b><br />";
			echo "session_start was"; 
			if(!$bSetSession)
				echo " <b>not</b>";
			echo " succefully<br />";
			echo "session-ID on <b>var</b> ".$this->sessionName." is ".session_id()."<br />";
			echo "<br />";
			echo "cookies set on host <b>".$_SERVER["HTTP_HOST"]."</b><br />";
			var_dump($HTTP_COOKIE_VARS);
			echo "<br />";
		}
  	}  
	function getSessionID()
	{
		return session_id();
	}
  	function getUserID()
  	{
		return $_SESSION['ST_USERID'];
  	}
	function isLoggedIn()
	{
		if(Tag::isDebug())
		{
			Tag::echoDebug("user", "entering ::isLoggedIn() ...");
			if($_SESSION['ST_LOGGED_IN'])
				$string= "user is Logged, so return true";
			else
				$string= "user is not logged, so return false";
			Tag::echoDebug("user", $string);
		} 
      	if( $_SESSION['ST_LOGGED_IN'] )
	  		return true;		  
	  	else
	  	  	return false;
	}
	function getUserName()
	{	
		return $_SESSION['ST_USER'];
	}
	function getProjectID()
	{
		return $this->projectID;
	}
	function getProjectName()
	{
		return $this->project;
	}
	function hasUserManagementAccess($projectID= null, $addType= addUser)
	{
		/**/if( Tag::isDebug("user") ) {
			echo "Entering hasUserManagementAccess checking for Groups <em>".htmlspecialchars( $groupString )."</em>, <br />";
			echo "User is currently member of following clusters: ";
			if(is_array($_SESSION['ST_CLUSTER_MEMBERSHIP']) && count($_SESSION['ST_CLUSTER_MEMBERSHIP']))
				foreach( $_SESSION['ST_CLUSTER_MEMBERSHIP'] as $myCluster=>$Project )
					echo $myCluster.";";
			echo ",<br />";
		}
		if(	isset($_SESSION['ST_CLUSTER_MEMBERSHIP'][$this->allAdminCluster])
			and
			$this->isLoggedIn())
		{
  			$this->LOG(STACCESS, $customID, $toAccessInfoString);
  			/**/Tag::echoDebug("user", "-&gt; User is Super-Admin &quot;allAdmin&quot; so return TRUE<br />");
  			return true;
  		}
		$anyAccess= false;
		$projectAccess= false;
		if(is_array($_SESSION['ST_CLUSTER_MEMBERSHIP']))
		{
			foreach($_SESSION['ST_CLUSTER_MEMBERSHIP'] as $project)
			{
				if(	$project["addUser"]=="Y"
					or
					$project["addGroup"]=="Y")
				{
					$anyAccess= true;
					if(	$project["ID"]==$projectID
						and 
						$project[$addType]=="Y")
					{
						$projectAccess= true;
						break;
					}
				}
			}
		}
		if($projectID==null)
		{
			if($anyAccess)
			{
			/**/if( Tag::isDebug("user") ) 
			/**/	echo "-&gt; User has access on one or more than one UserManagement Projects, return TRUE<br />";
				return true;
			}
			return false;
		}
		if($projectAccess)
		{
			/**/if( Tag::isDebug("user") ) 
			/**/	echo "-&gt; User has access on UserManagementis in one of the Specified Clusters return TRUE<br />";
			return true;
		}
		return false;
	}
	function ownProjectAccessTable($projectId, $table)
	{
		if(	$projectId===null
			or
			$projectId===""	)
		{
			echo "<b>Error: </b> first parameter in STUser::ownProjectAccessTable() must be a true value";
			exit;
		}
		if(!typeof($table, "ostdbtable"))
		{
			echo "<b>Error: </b> given Table in STUser::ownProjectAccessTable() must be an OSTDbTable()";
			exit;
		}
		$table->where($this->sProjectIDColumn."=".$projectId);
		$table->clearSelects();
		$table->select($this->sProjectIDColumn);
		$table->select($this->sClusterIDColumn);
		$table->select($this->sAuthorisationColumn);
		$this->projectAccessTable= &$table;
	}
	function hasAccess($authorisationString, $toAccessInfoString= null, $customID= null, $gotoLoginMask= false)
	{
		if($this->projectAccessTable) // wenn eine Tabelle gesetzt wurde �ber projektspezifische Abfrage
			return $this->hasProjectAccess($authorisationString, $toAccessInfoString, $customID);
		return $this->access($authorisationString, $toAccessInfoString, $customID, $gotoLoginMask);
	}
	function hasProjectAccess($authorisationString, $toAccessInfoString= null, $customID= null)
	{		
		if($this->noRegister)
		{
			/**/Tag::echoDebug("user", "-&gt; User must not be registered so return TRUE<br />");
			return true;
		}
		if(	isset( $_SESSION['ST_CLUSTER_MEMBERSHIP'][$this->allAdminCluster])
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
		$aAccess= preg_split("/,/", $authorisationString);
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
	function access($clusterString, $toAccessInfoString= null, $customID= null, $gotoLoginMask= false)
	{	
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
			echo "<b>[</b>user<b>]:</b> <b>entering hasAccess(<em>&quot;".htmlspecialchars( $clusterString )."&quot;, ".$sAccess.", ".$sID.",</em>)</b><br />";
			echo "<b>[</b>user<b>]:</b> User is currently member of following clusters: ";
			if(is_array($_SESSION['ST_CLUSTER_MEMBERSHIP']) && count($_SESSION['ST_CLUSTER_MEMBERSHIP']))
			{
				foreach( $_SESSION['ST_CLUSTER_MEMBERSHIP'] as $myCluster=>$Project )
					echo $myCluster.";";
			}else
				echo "<em>NO CLUSTER</em>";
			echo "<br />";
			echo "<b>[</b>user<b>]:</b> sessionvar ST_LOGGED_IN is ";
			var_dump($_SESSION['ST_LOGGED_IN']);echo "<br />";
		}
		// alex 09/10/2005:	User muss nicht eingeloggt sein
		//					um auf Projekte zugriff zu haben
		//					habe Abfrage herausgenommen
		/*if(!$_SESSION['ST_LOGGED_IN'])
		{
			$this->gotoLoginMask(0);
			exit;
		}*/
		if( !is_String($clusterString)
			or
			(	$toAccessInfoString!==null
				and
				!is_String($toAccessInfoString) )	)
		{
			echo "<br><div align='center'>die ersten beiden Parameter von ->hasAccess";
			echo "(<b>(".gettype($clusterString).")'".$clusterString."'</b>, ";
			echo "'<b>(".gettype($toAccessInfoString).")'".$toAccessInfoString."'</b>)";
			echo " m&uuml;ssen ein String sein!</div><br>";
			exit();
		}
		if( $customID!==null
			and
			!is_Numeric($customID))
		{
			echo "<br><div align='center'>der dritte Parameter";
			echo "('<b>$customID</b>') von ->hasAccess()";
			echo " muss, wenn vorhanden, einr Zahl sein!</div><br>";
			exit();
		}
		$clusters= preg_split("/[\s,]/", $clusterString, -1, PREG_SPLIT_NO_EMPTY);
		foreach($clusters as $cluster)
		{
			$cluster= trim($cluster);
			if(	!isset($_SESSION['ST_EXIST_CLUSTER'][$cluster]))
			{echo "exist clusters";print_r($_SESSION['ST_EXIST_CLUSTER']);
				echo "<br /><b>ERROR:</b> Cluster <b>\"</b>".$cluster."<b>\"</b> not exist in database";
				exit;
			}elseif($_SESSION['ST_EXIST_CLUSTER'][$cluster]!==$this->projectID
					and
					$this->project!=0	)
			{
				echo "<br />you <b>little Idiot!</b><br />";
				echo "the cluster <b>\"</b>".$cluster."<b>\"</b> is not set for this project";
				exit();
			}
		}
		if(	isset( $_SESSION['ST_CLUSTER_MEMBERSHIP'][$this->allAdminCluster])
			and
			$this->isLoggedIn())
		{
  			$this->LOG(STACCESS, $customID, $toAccessInfoString);
  			/**/Tag::echoDebug("user", "-&gt; User is Super-Admin &quot;allAdmin&quot; so return TRUE<br />");
  			return true;
  		}
		foreach($clusters as $cluster)
		{
			if(isset( $_SESSION['ST_CLUSTER_MEMBERSHIP'][ trim($cluster) ]))
			{
				if($toAccessInfoString)
					$this->LOG(STACCESS, $customID, $toAccessInfoString);
				/**/Tag::echoDebug("user", "-&gt; User is Member of '$cluster' Cluster so return TRUE<br />");
				return true;
			}
		}
		
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
		$this->aGroups= null;
		//$this->echoSessionVars();
		foreach($this->aSessionVars as $var)
			unset($_SESSION[$var]);
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
		function setUserProject($ProjectName)
		{	
			/**/Tag::echoDebug("user", "<b>entering setUserProject(</b>$ProjectName<b>)</b>");
			// deffiniere User-Name
			if(isset($_SESSION['ST_USERID']))
			{//wenn ST_USERID gesetzt ist, weiss die Klasse
				$this->userID= $_SESSION['ST_USERID'];//die UserID nicht.
				/**/Tag::echoDebug("user", "set userID from session-var to ".$_SESSION['ST_USERID']);
			}else// sonst wurde bereits eine Authentifizierung �ber Datenbank/ELDAP gemacht
			{
				/**/Tag::echoDebug("user", "set ST_USERID from database to ".$this->userID);
				$_SESSION['ST_USERID']= $this->userID;
			}
			if(isset($_SESSION['ST_USER']))//selbiges!!
				$this->user= $_SESSION['ST_USER'];
			else
				$_SESSION['ST_USER']= $this->user;
			
			if($ProjectName==trim("##StartPage"))
			{
				$this->projectID= 0;
				$_SESSION['ST_PROJECTID']= 0;
			}else
			{
  			// deffiniere Projekt
  			$statement= "select ID,Name,Path from MUProject where ";
				if(is_numeric($ProjectName))
					$statement.= "ID=".$ProjectName;
				else
					$statement.= "Name='$ProjectName'";
				$this->database->query($statement);
				$row= $this->database->fetch_row(STSQL_NUM);
  			if( !isset($row)
					or
					!$row
					or
					$row==""
					or
					count($row)==0)
  			{
  				echo "<br><center>Projekt <b>$ProjectName</b> ist in der Datenbank nicht angelegt</center><br>";
  				exit;
  			}
  			$this->projectID= $row[0];
  			$this->project= $row[1];
				if(!$this->startPage)
				{
					/**/Tag::echoDebug("user", "set startPage from database to ".$row[2]);
					$this->startPage= $row[2];
				}
				$_SESSION['ST_PROJECTID']= $this->projectID;
			}
		}
  	function setProperties($ProjectName)
  	{
  			/**/Tag::echoDebug("user", "entering setProperties..");
			// define Login-Flag
			$_SESSION['ST_LOGGED_IN']= 1;
				
			$this->setUserProject( $ProjectName );
			// alex 09/10/2005:	ST_CLUSTER_MEMBERSHIP soll bei jedem setProperties
			//					aktualisiert werden
			// deffiniere Gruppen-Array				
			//if( !isset( $_SESSION['ST_CLUSTER_MEMBERSHIP'] ) )
			//{
				/**/if( Tag::isDebug("user") ) 
				{
					echo "ST_CLUSTER_MEMBERSHIP - sessionvar not set";
					echo "->start checking properties from scratch..<br />";
				}
				$this->readCluster();
				$_SESSION['ST_CLUSTER_MEMBERSHIP']= $this->aCluster;
				
			//}else
			//{// sonst use the session-variable / Session-Cookie
			//	/**/Tag::echoDebug("user", "ST_CLUSTER_MEMBERSHIP - found in Session<br />");
			//	$this->aCluster= $_SESSION['ST_CLUSTER_MEMBERSHIP'];				
			//}	
			if(!isset($_SESSION['ST_EXIST_CLUSTER']))
			{
				/**/if( Tag::isDebug("user") ) 
				{
					echo "ST_EXIST_CLUSTER - sessionvar not set";
					echo "->select all exist clusters from database<br />";
				}
				
				$statement= "select ID,ProjectID from MUCluster";
				$aClusters= $this->database->fetch_array($statement, MYSQL_ASSOC);
				$this->aExistCluster= array();
				foreach($aClusters as $row)
					$this->aExistCluster[$row["ID"]]= $row["ProjectID"];
				$_SESSION['ST_EXIST_CLUSTER']= $this->aExistCluster;
				/**/if( Tag::isDebug("user") )
				{
					echo "<b>found existing clusters:</b><br /><pre>";
					print_r($_SESSION['ST_EXIST_CLUSTER']);
					echo "</pre><br />";
				}
				
			}else
			{// else use the session-variable / Session-Cookie
				/**/Tag::echoDebug("user", "ST_EXIST_CLUSTER - found in Session<br />");
				$this->aExistCluster= $_SESSION['ST_EXIST_CLUSTER'];				
			}			
  	}
		function readCluster()
		{// hole alle Cluster, 
		 // zugeh�rig zum Projekt und User
		 // aus der Datenbank
		 	/**/if(Tag::isDebug())
			{
		 		$logged_inGroupID= $this->selectGroupID("LOGGED_IN");
				/**/Tag::echoDebug("user", "entering readCluster..");
				if(!$logged_inGroupID)
				{
					echo "<br /><b>ERROR:</b> no LOGGED_IN Group in Db-Table MUGroup exists";
					exit;
				}
			}
			
				$statement= "select ID,ProjectID from MUCluster";
				$aClusters= $this->database->fetch_array($statement, MYSQL_ASSOC);
				$this->aExistCluster= array();
				foreach($aClusters as $row)
					$this->aExistCluster[$row["ID"]]= $row["ProjectID"];
				$_SESSION['ST_EXIST_CLUSTER']= $this->aExistCluster;
				/**/if( Tag::isDebug("user") )
				{
					echo "<b>found existing clusters:</b><br /><pre>";
					print_r($_SESSION['ST_EXIST_CLUSTER']);
					echo "</pre><br />";
				}
				
			$statement=  "select c.ID,p.Name,c.ProjectID,c.addUser,c.addGroup from MUProject as p ";
			$statement.= "inner join MUCluster as c on p.ID=c.ProjectID ";
			$statement.= "inner join MUClusterGroup as cg on c.ID=cg.ClusterID ";
			$statement.= "inner join MUGroup as g on cg.GroupID=g.ID ";
			$statement.= "left join MUUserGroup as ug on g.ID=ug.GroupID ";
			$statement.= "where ";
			if($this->userID)
				$statement.= "ug.UserID=".$this->userID." or g.Name='LOGGED_IN' or ";
			$statement.= "g.Name='ONLINE'";
			
			/**/Tag::echoDebug("user", "checking in database for<pre>$statement</pre>");
			$aCluster= $this->database->fetch_array($statement, MYSQL_ASSOC);
			$this->aCluster= array();
			/*$access["ID"]= 0;
			$access["project"]= "allProjects";
			$access["addUser"]= "N";
			$access["addGroup"]= "N";
			$this->aCluster["LOGGED_IN"]= $access;*/
			foreach($aCluster as $row)
			{// Projekt ID f�r Cluster nur anzeigen,
			 // wenn dieser zugriff auf das UserManagement hat
				$access["ID"]= $row["ProjectID"];
				$access["project"]= $row["Name"];
				$access["addUser"]= $row["addUser"];
				$access["addGroup"]= $row["addGroup"];
				$this->aCluster[$row["ID"]]= $access;
			}
			/**/if( Tag::isDebug("user") )
			{
				echo "<b>found following cluster Memberships:</b><br /><pre>";
				print_r( $this->aCluster );
				if(Tag::isDebug("user.cluster"))
				echo "</pre><br />";
			}
			
			
		}
		function selectGroupID($group)
		{
			$statement= "select ID from MUGroup where Name='$group'";
			$ID= $this->database->fetch_single($statement);
			return $ID;
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
		function getFromOtherConnections($foundedID, $user, $password)
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
		function acceptUser($user, $password)
		{
			if(Tag::isDebug("user"))
			{
				$pwd= str_repeat("*", strlen($password));
				echo "<b>entering acceptUser(<em>&quot;".$user."&quot;, &quot;".$pwd."&quot;,</em>)</b><br />";
			}
			$_SESSION['ST_USER']= $user;
			$this->user= $user;
	 		$statement= "select ID, GroupType from MUUser where UserName='$user'";// Pwd=password('$password')";
			$this->database->query($statement);
	 	  	$row= $this->database->fetch_row(STSQL_NUM);
		  	$ID= $row[0];
			if( Tag::isDebug("user") )
			{
				if(isset($ID))
				{
					echo "founded first row in database from table MUUser:<br />";
					print_r($row);
					echo "<br />";
				}else
					echo "do not found user in Database";
			}
		  	if(	!isset($ID)
		  		or
				$row[1]!="custom"	)
		  	{
				$result= $this->getFromOtherConnections($ID, $user, $password);
				//echo "accept is $result";exit;
				return $result;
			}
		  	//kein �berpr�fung �ber LDAP-Server
			if( !$ID )
				return 1;// kein User mit diesem Namen vorhanden		
			$statement=	 "select ID from MUUser ";
			$statement.= "where UserName='$user' and Pwd=password('$password')";
			$ID= $this->database->fetch_single($statement);
			if(!$ID)
				return 2;// Passwort ist falsch
			$this->sGroupType= "custom";
			$this->userID= $ID;
			$this->user= $user;
			//$this->checkForLoggedIn();
			return 0;
		}
		function verifyLogin($Project)
		{
			global	$HTTP_POST_VARS, 
					$HTTP_COOKIE_VARS, 
					$HTTP_SERVER_VARS;
			
			$sessionName = $this->sessionName;
			$result = $this->private_verifyLogin( $Project);
			
			//echo "verifyLogin";exit;
			if( isset($HTTP_POST_VARS[ "doLogin" ]) 
				and
				!isset($HTTP_COOKIE_VARS[$sessionName])
				and
				!isset($_COOKIE[$sessionName])	)
			{
				if( isset( $sessionName ) ){
					
					$popupWindowAddress=  "http://".$HTTP_SERVER_VARS[ 'HTTP_HOST' ];
					$popupWindowAddress.= "/sessionStart.php?".$sessionName."=".urlencode( $sessionName );
					if( Tag::isDebug("user") ) 
					{
						echo "NO COOKIE SET BUT PHPSESS ID FOUND !! [$sessionName]<br />";
						echo "Will open Popup: $popupWindowAddress <br />";
					}
					
					?><script>
						popupWindowAddress= '<?=$popupWindowAddress?>';
						windowParams= 'width=50,height=50,resizable=no,toolbars=no,hotkeys=no';
						mySessionStarter = window.open(popupWindowAddress, '',  windowParams);
						window.focus();
					</script><?
				} else {
					if( Tag::isDebug("user") ) echo "NO PHPSESS ID FOUND !! [$$sessionName]<br />";
				}
			}
			return $result;
		}
		function private_verifyLogin($Project)
		{
			/**/ if( Tag::isDebug("user") ){ echo "<b>entering verifyLogin( ";print_r( $Project ); echo " ):</b> .."; }
			global	$HTTP_SERVER_VARS,
					$HTTP_GET_VARS,
					$HTTP_POST_VARS,
					$HTTP_COOKIE_VARS;				

			if($this->noRegister)
			{
				/**/ if( Tag::isDebug("user") ) echo "disabled registration for DEBUGGING purposes<br /><br />";
				return;
			}
			$this->project= $Project;
  		if(	isset($HTTP_POST_VARS[ "doLogout" ])
				or
				isset($HTTP_GET_VARS[ "doLogout" ]))
  		{		
  			/**/ if( Tag::isDebug("user") ) echo "start performing LOGOUT<br />";
  			$this->logHimOut(0);
				$this->gotoLoginMask(0);
				exit;
    	} else if( isset( $HTTP_GET_VARS[ "timeout" ] ) )
			{
				/**/ if( Tag::isDebug("user") ) echo "perform (javascript automatic timeout triggered ) LOGOUT<br />";
				$this->logHimOut(6, "TIMEOUT");
				$this->gotoLoginMask(0);
				exit;
			}
			else if( $HTTP_POST_VARS[ "doLogin" ] == 1 )
    	{// wir empfangen gerade ein eingegebenes login
			//Tag::debug("db.statement");
			//Tag::debug("user");
				/**/Tag::echoDebug("user", "receiving new login data, start performing login verification");
				/**/Tag::echoDebug("user", "set ST_USERID and ST_CLUSTER_MEMBERSHIP to NULL");
				$_SESSION['ST_CLUSTER_MEMBERSHIP']= null;
				$_SESSION['ST_USERID']= null;
        	$error= $this->acceptUser($HTTP_POST_VARS["user"], $HTTP_POST_VARS["pwd"]);
      		if(!$error)
				{
					/**/ if( Tag::isDebug("user") ) 
					{
						echo "....login Successfull, set Project to <em>$Project</em>, ";
						echo "update LastLogin and increase NrLogin counter<br />";
					}
						
					
					$this->setProperties( $Project );
					$statement=  "update MUUser set LastLogin=sysdate(), NrLogin= NrLogin+1 ";
					$statement.= "where ID=".$this->userID;
					$this->database->solution($statement);
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
  			if( $_SESSION['ST_LOGGED_IN'] == 1 )
  			{
  				/**/ if( Tag::isDebug("user") ) 
						echo "user logged in return TRUE<br />";
  			 	return true;
  		 	}else
			{
				if(!$_SESSION['ST_CLUSTER_MEMBERSHIP'])
				{
					Tag::echoDebug("user", "read Cluster vor ONLINE staus from database");
					$this->readCluster();
					$_SESSION['ST_CLUSTER_MEMBERSHIP']= $this->aCluster;
				}else
					$this->aCluster= $_SESSION['ST_CLUSTER_MEMBERSHIP'];
			}
  		 	/**/ if( Tag::isDebug("user") ) 
					echo "user not logged in return FALSE<br />";				
				return false;    	 
    	}
			echo "end of verifyLogin, ST_LOGGED_IN is ";
			var_dump($_SESSION['ST_LOGGED_IN']);echo "<br />";
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
			$html= new GetHtml();
		// alex 11/05/2005: wenn ein Login vom login.php herein kommt
		//     und das Login fehlerhaft ist, soll der To Parameter
		//     nicht zur�ckgeschickt werden, sowie der from Parameter
		//     nicht show.php sein soll sondern der To Parameter
		$from= $HTTP_SERVER_VARS["SCRIPT_NAME"];
		//echo "preg_match(\"/".$nav."$/\", $from)<br />";
		/*if( preg_match("/".$nav."$/", $from)
			and
			isset($HTTP_GET_VARS["To"])  )
		{
			$Address= $html->getParamString(STDELETE, "To");
			$from= $HTTP_GET_VARS["To"];
		}*/
		$Address= $html->getParamString(STDELETE, "doLogout");
		$Address= $html->getParamString(STINSERT, "ERROR=".$error);
		$Address= $html->getParamString(STINSERT, "from=".$from);
		$Address= $st_user_login_mask.$Address;
		if(Tag::isDebug() )
			{
  			echo "<br /><br /><h1>User would be forwarded to:<br /><a href=\"$Address\">$Address</a>";
  		}else
			{ 
				@header("Location: $Address");
				echo "<br /><br /><h1>Please login at: <a href=\"$Address\">Startpage</a>";
				echo "<script>top.location.href='".addslashes($Address)."';</script>";
			}
  		exit();
		}
		function getLogoutButton($ButtonText, $class= null)
  	{
        global $HTTP_SERVER_VARS;
        global $st_user_login_mask;
        
			$startPage= $this->startPage;
			$query= $_SERVER["QUERY_STRING"];
			if($query)
				$query= "?".$query."&";
			else
				$query= "?";
			$query.= "doLogout";
			
			$input= new InputTag($class);
				$input->type("button");
				$input->value($ButtonText);
				$input->onClick("javascript:self.location.href='".$query."'");
			
			return $input;
  	}
	function getLoginAddress($toAddress= null)
	{
        global	$HTTP_SERVER_VARS,
        		$st_user_login_mask;
		
		$param= new GetHtml();
		if(!$toAddress)
			$toAddress= $HTTP_SERVER_VARS["SCRIPT_NAME"];
		$param->insert("from=".$toAddress);
		$param->insert("user=".$this->getUserName());
		$param->insert("ERROR=0");
		$address= $st_user_login_mask.$param->getStringVars();
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
		function LOG($type, $customID= null, $logText= "")
		{
			Tag::paramCheck($type, 1, "check",//$type!=STACCESS,
					($type==STDEBUG||$type==STLOGIN||$type==STLOGIN_ERROR||$type==STLOGOUT||$type==STACCESS||$type==STACCESS_ERROR),
					"STDEBUG", "STLOGIN", "STLOGIN_ERROR", "STLOGOUT", "STACCESS", "STACCESS_ERROR");
			Tag::paramCheck($customID, 2, "int", "null");
			Tag::paramCheck($logText, 3, "string", "null", "empty(string)");
			$this->writeLog($type, $customID, $logText);
		}
		function writeLog($type, $customID, $logText)
		{
			$searchText= $type." ".$customID." ".$logText;
			if(!isset($_SESSION['ST_LOGGED_MESSAGES']))
				$_SESSION['ST_LOGGED_MESSAGES']= array();
			elseif(	array_search($searchText, $_SESSION['ST_LOGGED_MESSAGES'])
					or
					!$logText	)
			{// diese Seite wurde bereits geloggd
			 // oder der Log ist nicht n�tig, da kein logText vorhanden
				return;
			}
			$_SESSION['ST_LOGGED_MESSAGES'][]= $searchText;
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
			$statement=  "insert into MULog values(0,sysdate(),";
			$statement.= "$user,$project,$Typ,$customID,'$logText')";
			$this->database->solution($statement);
		}
		function isDebug($value= "user")
		{
			/**///return true;
			return Tag::isDebug($value);
		}	
}
