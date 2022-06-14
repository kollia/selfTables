<?php

require_once($_stsession);
//require_once($database_selector);
require_once($_stusermanagement_install);

class STUserManagementSession extends STSession
{
	var $database= null;
	var $user;
	var $userID;
	var $bLog;
	var	$userManagementProjectName= "UserManagement";
	var $loggedinGroup= "LOGGED_IN"; // user hat auf die Cluster zugriff,
									// wenn sie mit dieser Gruppe spezifiziert wurden,
									// sobald er sich registriert hat
	var $onlineGroup= "ONLINE";	// user hat immer auf diese Cluster Zugriff
								// wenn sie mit dieser Gruppe spezifiziert wurden.
								// auch wenn er sich gar nicht registriert hat
	var	$allAdminCluster= "allAdmin";	// user has in every project
										// access to all clusters
	var	$sGroupTable= "Group";
	/*var	$sProjectTable= "Project";
	var	$asProjectTableColumns= array();
	var $sClusterTable= "Cluster";
	var	$sPartitionTable= "Partition";
	var	$asClusterTableColumns= array();
	var	$sClusterGroupTable= "ClusterGroup";
	var	$asClusterGroupTableColumns= array();
	var	$asGroupTableColumns= array();
	var	$sGroupTypeTable= "GroupType";
	var	$asGroupTypeTableColumns= array();
	var	$sUserGroupTable= "UserGroup";
	var	$asUserGroupTableColumns= array();
	var	$sUserTable= "User";
	var	$asUserTableColumns= array();
	var	$sLogTable= "Log";
	var	$asLogTableColumns= array();*/

	// all for own Projects
	var $projectAccessTable;
	var $sProjectIDColumn= "ProjectID";
	var $sClusterIDColumn= "ClusterID";
	var $sAuthorisationColumn= "Authorisation";
	var $aProjectAccessCluster= array();

	function __construct(&$Db, $prefix= null, $private= "")
	{
		Tag::alert($private!="selfTables_STUserSession_private_String", "STUserSession::constructor()",
								"class STUserSession is private, choose STUserSession::init(\$Db)");
		STSession::__construct("selfTables_STSession_private_String");
   		$this->database= &$Db;
		$this->bLog= true;

		$this->aSessionVars[]= "ST_USER";
		$this->aSessionVars[]= "ST_USERID";
		$this->aSessionVars[]= "ST_PROJECTID";
		$this->aSessionVars[]= "ST_LOGGED_MESSAGES";

		/*$this->asProjectTableColumns=	 	array(	"ID"=>			array(	"column"=>	"ID"	),
													"Name"=>		array(	"column"=>	"Name",
																			"alias"=>	"Projekt-Name"	),
													"Path"=>		array(	"column"=>	"Path",
																			"alias"=>	"Projekt-Pfad"	),
													"Description"=>	array(	"column"=>	"Description",
																			"alias"=>	"Beschreibung"		),
													"DateCreation"=>array(	"column"=>	"DateCreation",
																			"alias"=>	"Erzeugungs-Datum"	)	);
		$this->asClusterTableColumns=	 	array(	"ID"=>			array(	"column"=>	"ID"			),
													"ProjectID"=>	array(	"column"=>	"ProjectID",
																			"alias"=>	"Projekt"		),
													"Description"=>	array(	"column"=>	"Description",
																			"alias"=>	"Beschreibung"		),
													"identification"=>	array(	"column"=>	"identification",
																				"alias"=>	"Identifikation"		),
													"DateCreation"=>array(	"column"=>	"DateCreation",
																			"alias"=>	"Erzeugungs-Datum"	)	);
		$this->asClusterGroupTableColumns= 	array(	"ID"=>			array(	"column"=>	"ID"	),
													"ClusterID"=>	array(	"column"=>	"ClusterID",
																			"alias"=>	"Cluster"	),
													"GroupID"=>		array(	"column"=>	"GroupID",
																			"alias"=>	"Gruppe"	),
													"Description"=>	array(	"column"=>	"Description",
																			"alias"=>	"Beschreibung"		),
													"DateCreation"=>array(	"column"=>	"DateCreation",
																			"alias"=>	"Erzeugungs-Datum"	)	);
		$this->asGroupTableColumns=		 	array(	"ID"=>			array(	"column"=>	"ID"	),
													"Name"=>		array(	"column"=>	"Name",
																			"alias"=>	"Gruppe"	),
													"DateCreation"=>array(	"column"=>	"DateCreation",
																			"alias"=>	"Erzeugungs-Datum"	)	);
		$this->asGroupTypeTableColumns=	 	array(	"ID"=>			array(	"column"=>	"ID"	),
													"Label"=>		array(	"column"=>	"Label",
																			"alias"=>	"Bezeichnung"	),
													"Description"=>	array(	"column"=>	"Description",
																			"alias"=>	"Beschreibung"		),
													"DateCreation"=>array(	"column"=>	"DateCreation",
																			"alias"=>	"Erzeugungs-Datum"	)	);
		$this->asUserGroupTableColumns=	 	array(	"ID"=>			array(	"column"=>	"ID"	),
													"UserID"=>		array(	"column"=>	"UserID",
																			"alias"=>	"User"	),
													"GroupID"=>		array(	"column"=>	"GroupID",
																			"alias"=>	"Gruppe"	),
													"DateCreation"=>array(	"column"=>	"DateCreation",
																			"alias"=>	"Erzeugungs-Datum"	)	);
		$this->asUserTableColumns=		 	array(	"ID"=>			array(	"column"=>	"ID"	),
													"UserName"=>	array(	"column"=>	"UserName",
																			"alias"=>	"Nickname"	),
													"NrLogin"=>		array(	"column"=>	"NrLogin",
																			"alias"=>	"Eingelogged"	),
													"LastLogin"=>	array(	"column"=>	"LastLogin",
																			"alias"=>	"letzter Login"		),
													"GroupType"=>	array(	"column"=>	"GroupType",
																			"alias"=>	"Log-Gruppe"	),
													"Pwd"=>			array(	"column"=>	"Pwd",
																			"alias"=>	"Paswort"		),
													"DateCreation"=>array(	"column"=>	"DateCreation",
																			"alias"=>	"Erzeugungs-Datum"	)	);
		$this->asLogTableColumns=		 	array(	"ID"=>			array(	"column"=>	"ID"	),
													"UserID"=>		array(	"column"=>	"UserID",
																			"alias"=>	"User"	),
													"ProjectID"=>	array(	"column"=>	"ProjectID",
																			"alias"=>	"Projekt"		),
													"Type"=>		array(	"column"=>	"Typ",
																			"alias"=>	"Log-Type"	),
													"CustomID"=>	array(	"column"=>	"CustomID",
																			"alias"=>	"CustomID"	),
													"Description"=>	array(	"column"=>	"Description",
																			"alias"=>	"Beschreibung"		),
													"DateCreation"=>array(	"column"=>	"DateCreation",
																			"alias"=>	"Erzeugungs-Datum"	)	);
		if($prefix)
			$this->setPrefixToTables($prefix);*/
	}
	function setPrefixToTables($prefix)
	{
		STCheck::param($prefix, 0, "string");

		$desc= &STDbTableDescriptions::instance($this->database->getName());

		$desc->setPrefixToTable($prefix, "Partition");
		$desc->setPrefixToTable($prefix, "Project");
		$desc->setPrefixToTable($prefix, "Cluster");
		$desc->setPrefixToTable($prefix, "ClusterGroup");
		$desc->setPrefixToTable($prefix, "Group");
		$desc->setPrefixToTable($prefix, "UserGroup");
		$desc->setPrefixToTable($prefix, "User");
		$desc->setPrefixToTable($prefix, "GroupType");
		$desc->setPrefixToTable($prefix, "Log");
	}
	function verifyLogin($Project= 1)
	{// method only to check param -> must be set
		Tag::paramCheck($Project, 1, "int", "string");

		STSession::verifyLogin($Project);
	}
	function projectTable($name)
	{
		$this->sProjectTable= $name;
	}
	function getProjectCluster()
	{
		if(!$this->projectCluster)
		{
			$project= $this->database->getTable("Project");
        	$project->clearSelects();
        	$project->clearIdentifColumns();
        	$project->clearGetColumns();
        	$project->select("has_access");
        	$project->select("can_insert");
        	$project->select("can_update");
        	$project->select("can_delete");
			$project->where("ID=".$this->projectID);
			$selector= new STDbSelector($project);
			$selector->execute(STSQL_ASSOC, 1);
			$this->projectCluster= $selector->getRowResult();
		}
		return $this->projectCluster;
	}
	function getDynamicClusters($table)
	{
	    Tag::paramCheck($table, 1, "STDbTable");

	    $clusters= $this->session_vars["ST_USER_DEFINED_VARS"]["dynamic"][$table->getName()];
    	if(!is_array($clusters))
  		{//echo __file__.__line__."<br />";
   		    //st_print_r($this->sAcessClusterColumn,3);
  		    $table->clearSelects();
  		    $table->select($table->getPkColumnName());
  		    foreach($table->sAcessClusterColumn as	$clusterInfo)
  		    {
  		        $table->select($clusterInfo["column"]);
  				$table->andWhere($clusterInfo["column"]." is not null");
  				$table->andWhere($clusterInfo["column"]."!=''");
  		    }
			$selector= new STDbSelector($table);
			$selector->execute();
			$result= $selector->getResult();
			$clusters= array();
  		    foreach($result as $row)
  		    {
  		        foreach($table->sAcessClusterColumn as	$key=>$clusterInfo)
  				    {
  				        $clusters[$clusterInfo["action"]][$row[0]]= $row[$key+1];
  				    }
  		    }
  		    //st_print_r($created,5);
  		    $this->session_vars["ST_USER_DEFINED_VARS"]["dynamic"][$table->getName()]= $clusters;
        }
    	return $clusters;
	}
	function addDynamicCluster($table, $action, $pkValue, $cluster)
	{
	    Tag::paramCheck($table, 1, "STDbTable", "string");
	    Tag::paramCheck($action, 2, "int", "string");
	    Tag::paramCheck($pkValue, 3, "int", "string");
	    Tag::paramCheck($cluster, 4, "string");

		if(typeof($table, "STDbTable"))
		    $table= $table->getName();
		$this->session_vars["ST_USER_DEFINED_VARS"]["dynamic"][$table][$action][$pkValue]= $cluster;
		$this->session_vars["ST_EXIST_CLUSTER"][$cluster]= true;
	}
	function projectColumn($defined, $column, $alias= null)
	{
		Tag::paramCheck($defined, 1, "string");
		Tag::paramCheck($column, 2, "string");
		Tag::paramCheck($alias, 3, "string", "null");
		Tag::alert(!$this->asProjectTableColumns[$defined], $defined." is no defined column in STGalleryContainer");
		Tag::warning($alias&&!$this->asProjectTableColumns[$defined]["alias"], "defined column ".$defined." has no alias name");

		$this->asProjectTableColumns[$defined]["column"]= $column;
		if($alias)
			$this->asProjectTableColumns[$defined]["alias"]= $alias;
	}
	function projectAlias($column, $alias)
	{
		Tag::paramCheck($column, 1, "string");
		Tag::paramCheck($alias, 3, "string");

		$this->projectColumn($column, $column, $alias);
	}
	function clusterTable($name)
	{
		$this->sClusterTable= $name;
	}
	/*function partitionTable($name)
	{
		$this->sPartitionTable= $name;
	}*/
	function clusterColumn($defined, $column, $alias= null)
	{
		Tag::paramCheck($defined, 1, "string");
		Tag::paramCheck($column, 2, "string");
		Tag::paramCheck($alias, 3, "string", "null");
		Tag::alert(!$this->asClusterTableColumns[$defined], $defined." is no defined column in STGalleryContainer");
		Tag::warning($alias&&!$this->asClusterTableColumns[$defined]["alias"], "defined column ".$defined." has no alias name");

		$this->asTableClusterColumns[$defined]["column"]= $column;
		if($alias)
			$this->asClusterTableColumns[$defined]["alias"]= $alias;
	}
	function clusterAlias($column, $alias)
	{
		Tag::paramCheck($column, 1, "string");
		Tag::paramCheck($alias, 3, "string");

		$this->clusterColumn($column, $column, $alias);
	}
	function clusterGroupTable($name)
	{
		$this->sClusterGroupTable= $name;
	}
	function clusterGroupColumn($defined, $column, $alias= null)
	{
		Tag::paramCheck($defined, 1, "string");
		Tag::paramCheck($column, 2, "string");
		Tag::paramCheck($alias, 3, "string", "null");
		Tag::alert(!$this->asClusterGroupTableColumns[$defined], $defined." is no defined column in STGalleryContainer");
		Tag::warning($alias&&!$this->asClusterGroupTableColumns[$defined]["alias"], "defined column ".$defined." has no alias name");

		$this->asClusterGroupTableColumns[$defined]["column"]= $column;
		if($alias)
			$this->asClusterGroupTableColumns[$defined]["alias"]= $alias;
	}
	function clusterGroupAlias($column, $alias)
	{
		Tag::paramCheck($column, 1, "string");
		Tag::paramCheck($alias, 3, "string");

		$this->clusterGroupColumn($column, $column, $alias);
	}
	function groupTable($name)
	{
		$this->sGroupTable= $name;
	}
	function groupColumn($defined, $column, $alias= null)
	{
		Tag::paramCheck($defined, 1, "string");
		Tag::paramCheck($column, 2, "string");
		Tag::paramCheck($alias, 3, "string", "null");
		Tag::alert(!$this->asGroupTableColumns[$defined], $defined." is no defined column in STGalleryContainer");
		Tag::warning($alias&&!$this->asGroupTableColumns[$defined]["alias"], "defined column ".$defined." has no alias name");

		$this->asGroupTableColumns[$defined]["column"]= $column;
		if($alias)
			$this->asGroupTableColumns[$defined]["alias"]= $alias;
	}
	function groupAlias($column, $alias)
	{
		Tag::paramCheck($column, 1, "string");
		Tag::paramCheck($alias, 3, "string");

		$this->groupColumn($column, $column, $alias);
	}
	function userGroupTable($name)
	{
		$this->sUserGroupTable= $name;
	}
	function userGroupColumn($defined, $column, $alias= null)
	{
		Tag::paramCheck($defined, 1, "string");
		Tag::paramCheck($column, 2, "string");
		Tag::paramCheck($alias, 3, "string", "null");
		Tag::alert(!$this->asUserGroupTableColumns[$defined], $defined." is no defined column in STGalleryContainer");
		Tag::warning($alias&&!$this->asUserGroupTableColumns[$defined]["alias"], "defined column ".$defined." has no alias name");

		$this->asUserGroupTableColumns[$defined]["column"]= $column;
		if($alias)
			$this->asUserGroupTableColumns[$defined]["alias"]= $alias;
	}
	function userGroupAlias($column, $alias)
	{
		Tag::paramCheck($column, 1, "string");
		Tag::paramCheck($alias, 3, "string");

		$this->userGroupColumn($column, $column, $alias);
	}
	function userTable($name)
	{
		$this->sUserTable= $name;
	}
	function userColumn($defined, $column, $alias= null)
	{
		Tag::paramCheck($defined, 1, "string");
		Tag::paramCheck($column, 2, "string");
		Tag::paramCheck($alias, 3, "string", "null");
		Tag::alert(!$this->asUserTableColumns[$defined], $defined." is no defined column in STGalleryContainer");
		Tag::warning($alias&&!$this->asUserTableColumns[$defined]["alias"], "defined column ".$defined." has no alias name");

		$this->asUserTableColumns[$defined]["column"]= $column;
		if($alias)
			$this->asUserTableColumns[$defined]["alias"]= $alias;
	}
	function userAlias($column, $alias)
	{
		Tag::paramCheck($column, 1, "string");
		Tag::paramCheck($alias, 3, "string");

		$this->Column($column, $column, $alias);
	}
	function logTable($name)
	{
		$this->sLogTable= $name;
	}
	function logColumn($defined, $column, $alias= null)
	{
		Tag::paramCheck($defined, 1, "string");
		Tag::paramCheck($column, 2, "string");
		Tag::paramCheck($alias, 3, "string", "null");
		Tag::alert(!$this->asLogTableColumns[$defined], $defined." is no defined column in STGalleryContainer");
		Tag::warning($alias&&!$this->asLogTableColumns[$defined]["alias"], "defined column ".$defined." has no alias name");

		$this->asLogTableColumns[$defined]["column"]= $column;
		if($alias)
			$this->asLogTableColumns[$defined]["alias"]= $alias;
	}
	function logAlias($column, $alias)
	{
		Tag::paramCheck($column, 1, "string");
		Tag::paramCheck($alias, 3, "string");

		$this->logColumn($column, $column, $alias);
	}
	function groupTypeTable($name)
	{
		$this->sGroupTypeTable= $name;
	}
	function groupTypeColumn($defined, $column, $alias= null)
	{
		Tag::paramCheck($defined, 1, "string");
		Tag::paramCheck($column, 2, "string");
		Tag::paramCheck($alias, 3, "string", "null");
		Tag::alert(!$this->asGroupTypeTableColumns[$defined], $defined." is no defined column in STGalleryContainer");
		Tag::warning($alias&&!$this->asGroupTypeTableColumns[$defined]["alias"], "defined column ".$defined." has no alias name");

		$this->asGroupTypeTableColumns[$defined]["column"]= $column;
		if($alias)
			$this->asGroupTypeTableColumns[$defined]["alias"]= $alias;
	}
	function groupTypeAlias($column, $alias)
	{
		Tag::paramCheck($column, 1, "string");
		Tag::paramCheck($alias, 3, "string");

		$this->groupTypeColumn($column, $column, $alias);
	}
	public static function init(&$Db, $prefix= null)
	{
		global $global_selftable_session_class_instance;

		Tag::alert(global_sessionGenerated(), "STUserSession::init()",
								"session was already created");
		$global_selftable_session_class_instance[0]= new STUserManagementSession($Db, $prefix, "selfTables_STUserSession_private_String");
	}
	/* fault whether I do not know
	static public function sessionGenerated()
	{
		/**
		 * when this function making problems!
		 * Strict Standards:  Non-static method STSession::sessionGenerated() should not be called statically
		 * there is also a globaly method global_sessionGenerated() which do the same
		 *
		global $global_selftable_session_class_instance;

		if(	isset($global_selftable_session_class_instance[0]) &&
			typeof($global_selftable_session_class_instance[0], "STUserSession")	)
		{
			return true;
		}
		return false;
	}*/
	function &getUserDb()
	{
		return $this->database;
	}
	function makeTableMeans()
	{//return;STCheck::warning(1,"","");
		// take tables from database
		echo "makeTableMeans()<br>";
		showErrorTrace();
		$Project= &$this->database->needTable($this->sProjectTable);
		$Cluster= &$this->database->needTable($this->sClusterTable);
		$ClusterGroup= &$this->database->needTable($this->sClusterGroupTable);
		$Group= &$this->database->needTable($this->sGroupTable);
		$GroupType= &$this->database->needTable($this->sGroupTypeTable);
		$UserGroup= &$this->database->needTable($this->sUserGroupTable);
		$User= &$this->database->needTable($this->sUserTable);
		$Log= &$this->database->needTable($this->sLogTable);

		// set identifColumns in tables
        $Project->identifColumn($this->asProjectTableColumns["Name"]["column"], $this->asProjectTableColumns["Name"]["alias"]);
        $Cluster->identifColumn($this->asClusterTableColumns["ID"]["column"], $this->asClusterTableColumns["ID"]["alias"]);
        $Group->identifColumn($this->asGroupTableColumns["Name"]["column"], $this->asGroupTableColumns["Name"]["alias"]);
        $GroupType->identifColumn($this->asGroupTypeTableColumns["Label"]["column"],
													$this->asGroupTypeTableColumns["Label"]["alias"]);
        $User->identifColumn($this->asUserTableColumns["GroupType"]["column"], $this->asUserTableColumns["GroupType"]["alias"]);
        $User->identifColumn($this->asUserTableColumns["UserName"]["column"], $this->asUserTableColumns["UserName"]["alias"]);

		// set foreign keys in tables
        $Cluster->foreignKey($this->asClusterTableColumns["ProjectID"]["column"], $this->sProjectTable);
        $ClusterGroup->foreignKey($this->asClusterGroupTableColumns["ClusterID"]["column"], $this->sClusterTable);
        $ClusterGroup->foreignKey($this->asClusterGroupTableColumns["GroupID"]["column"], $this->sGroupTable);
        $UserGroup->foreignKey($this->asUserGroupTableColumns["GroupID"]["column"], $this->sGroupTable);
        $UserGroup->foreignKey($this->asUserGroupTableColumns["UserID"]["column"], $this->sUserTable);
        $Log->foreignKey($this->asLogTableColumns["UserID"]["column"], $this->sUserTable);
        $Log->foreignKey($this->asLogTableColumns["ProjectID"]["column"], $this->sProjectTable);

		//$this->database->getTableStructure(true);
	}
	function setLog($bLog)
	{
		$this->bLog= $bLog;
	}
  	function getUserID()
  	{
  		if(isset($this->session_vars["ST_USERID"]))
	  		return $this->session_vars["ST_USERID"];
  		return 0;
  	}
	function getUserName()
	{
  		if(isset($this->session_vars["ST_USER"]))
			return $this->session_vars["ST_USER"];
  		return "";
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
		Tag::deprecated("is died", "STUserSession::hasManagementAccess()");

		if(	isset($this->session_vars["ST_CLUSTER_MEMBERSHIP"][$this->allAdminCluster])
			and
			$this->isLoggedIn())
		{
  			$this->LOG(STACCESS, 0, "user is super-admin");
  			/**/Tag::echoDebug("user", "-&gt; User is Super-Admin &quot;allAdmin&quot; so return TRUE<br />");
  			return true;
  		}
		$anyAccess= false;
		$projectAccess= false;
		if(is_array($this->session_vars["ST_CLUSTER_MEMBERSHIP"]))
		{
			foreach($this->session_vars["ST_CLUSTER_MEMBERSHIP"] as $project)
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
		Tag::deprecated("is died", "STUserSession::ownProjectAccessTable()");

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
	function setUserProject($ProjectName)
	{
		// create table properties for user in database
		//$this->makeTableMeans();
		STSession::setUserProject($ProjectName);

		if($ProjectName==trim("##StartPage"))
		{
			$this->project= "";
			$this->projectID= 0;
			$this->session_vars["ST_PROJECTID"]= 0;
		}else
		{
  			// deffiniere Projekt
  			$project= $this->database->getTable("Project");
			$project->select("ID");
			$project->select("Name");
			$project->select("Path");
			$where= new STDbWhere();
			if(is_numeric($ProjectName))
				$where->andWhere("ID=".$ProjectName);
			else
				$where->andWhere("Name='$ProjectName'");
			$project->where($where);
  			$statement= $this->database->getStatement($project);
			$this->database->query($statement);
			$row= $this->database->fetch_row(STSQL_NUM);
			STCheck::alert(	(!is_array($row) || count($row)==0 ), "STUserSession::setUserProject()",
  										"Project &quote;<b>$ProjectName</b>&quote; is not defined in the database" );
  			$this->projectID= $row[0];
  			$this->project= $row[1];
			if(!$this->startPage)
			{
				/**/Tag::echoDebug("user", "set startPage from database to ".$row[2]);
				$this->startPage= $row[2];
			}
  			$this->session_vars["ST_PROJECTID"]= $this->projectID;
		}
	}
  	function setProperties($ProjectName= "")
  	{
  	    STCheck::paramCheck($ProjectName, 1, "string");// property shouldn*t be an null string, parameter only be defined for STSession::setProperties()
		/**/Tag::echoDebug("user", "entering STUserSession::setProperties ...");

		$this->setUserProject( $ProjectName );
		// alex 09/10/2005:	ST_CLUSTER_MEMBERSHIP soll bei jedem setProperties
		//					aktualisiert werden
		/**/if( Tag::isDebug("user") )
		{
			echo "ST_CLUSTER_MEMBERSHIP - sessionvar not set";
			echo "->start checking properties from scratch..<br />";
		}
		$this->readCluster();

		$clusters= $this->getExistClusters();
		if(!isset($clusters))
		{
			/**/if( Tag::isDebug("user") )
			{
				echo "ST_EXIST_CLUSTER - sessionvar not set";
				echo "->select all exist clusters from database<br />";
			}

			$clusterTable= $this->database->getTable("Cluster");
			$clusterTable->select("ID");
			$clusterTable->select("ProjectID");
			$statement= $this->database->getStatement($clusterTable);
			$aClusters= $this->database->fetch_array($statement, MYSQL_ASSOC);
			//echo "clusters from DB:";st_print_r($aClusters);echo "<br />";
			foreach($aClusters as $row)
				$this->setExistCluster($row["ID"], $row["ProjectID"]);
			/**/if( Tag::isDebug("user") )
			{
				echo "<b>found existing clusters:</b><br /><pre>";
				print_r($this->getExistClusters());
				echo "</pre><br />";
			}

		}

		// set Properties also in STSession
		STSession::setProperties();
	}
	function readCluster()
	{// hole alle Cluster,
	 // zugeh�rig zum Projekt und User
	 // aus der Datenbank
	 	/**/if(Tag::isDebug())
		{
			/**/Tag::echoDebug("user", "<b>entering readCluster..</b>");
	 		$GroupID= $this->selectGroupID($this->loggedinGroup);
			Tag::alert(!$GroupID, "STUserSession::readCluster()", "no ".$this->loggedinGroup." Group in Db-Table ".
																		$this->sGroupTable." exists");
	 		$GroupID= $this->selectGroupID($this->onlineGroup);
			Tag::alert(!$GroupID, "STUserSession::readCluster()", "no ".$this->onlineGroup." Group in Db-Table ".
																		$this->sGroupTable." exists");
		}

		//$statement= "select ID,ProjectID from MUCluster";
		$oCluster= &$this->database->getTable("Cluster");
		$clusterSelector= new STDbSelector($oCluster, STSQL_ASSOC);
		$clusterSelector->select("Cluster", "ID");
		$clusterSelector->getColumn("Cluster", "ProjectID");
		$clusterSelector->execute();
		$aClusters= $clusterSelector->getResult();
		//$statement= "select ".$this->asClusterTableColumns["ID"]["column"].",";
		//$statement.= $this->asClusterTableColumns["ProjectID"]["column"];
		//$statement.= " from ".$this->sClusterTable;
		//$aClusters= $this->database->fetch_array($statement, MYSQL_ASSOC);
		//echo "clusters from DB:";st_print_r($aClusters,2);echo "<br />";
		foreach($aClusters as $row)
			$this->setExistCluster($row["ID"], $row["ProjectID"]);
		/**/if( Tag::isDebug("user") )
		{
			echo "<b>found existing clusters:</b><br /><pre>";
			print_r($this->getExistClusters());
			echo "</pre><br />";
		}

		$oProject= &$this->database->getTable("Project");
		echo __FILE__.__LINE__."<br>selector will be create<br>";//exit;
		if(!typeof($oProject, "STDbSelector"))
		  $projectCluster= new STDbSelector($oProject, STSQL_ASSOC);
		else
		  $projectCluster= &$oProject;
		echo __FILE__.__LINE__."<br>selector created<br>";//exit;
		$projectCluster->distinct();
		$projectCluster->select("Cluster", "ID");
		$projectCluster->select("Project", "Name");
		$projectCluster->select("Cluster", "ProjectID");
		echo __FILE__.__LINE__."<br>";//exit;
		if($this->userID)
		{
			$groupWhere= new STDbWhere("Name='".$this->onlineGroup."'", "Group", "or");
			$groupWhere->orWhere("Name='".$this->loggedinGroup."'");
			$usergroupWhere= new STDbWhere("UserID=".$this->userID, "UserGroup");
			$groupWhere->orWhere($usergroupWhere);
		}else
			$groupWhere= new STDbWhere("Name='".$this->onlineGroup."'", "Group", "and");
		$projectCluster->where($groupWhere);
		echo __FILE__.__LINE__."<br>";//exit;
		//st_print_r($groupWhere, 5);
		
		//$projectWhere= new STDbWhere("ID=0", "Project");
		//$projectCluster->orWhere($projectWhere);
		/*$statement=  "select distinct c.".$this->asClusterTableColumns["ID"]["column"].",";
		$statement.= "p.".$this->asProjectTableColumns["Name"]["column"].",";
		$statement.= "c.".$this->asClusterTableColumns["ProjectID"]["column"]." ";
		$statement.= "from ".$this->sProjectTable." as p ";
		$statement.= "inner join ".$this->sClusterTable." as c on p.ID=c.ProjectID ";
		$statement.= "inner join ".$this->sClusterGroupTable." as cg on c.ID=cg.ClusterID ";
		$statement.= "inner join ".$this->sGroupTable." as g on cg.GroupID=g.ID ";
		$statement.= "left join ".$this->sUserGroupTable." as ug on g.ID=ug.GroupID ";
		$statement.= "where ";
		if($this->userID)
			$statement.= "ug.UserID=".$this->userID." or g.Name='LOGGED_IN' or ";
		$statement.= "g.Name='ONLINE'";
		echo "manuel statement:$statement<br/>";*/
		if(STCheck::isDebug("user"))
		{
			$statement= $projectCluster->getStatement();
			Tag::echoDebug("user", "checking in database for ".$statement);
		}
		$projectCluster->wait= true;
		$projectCluster->execute();
		$aCluster= $projectCluster->getResult();
		//$aCluster= $this->database->fetch_array($statement, MYSQL_ASSOC);
		$this->aCluster= array();
		/*$access["ID"]= 0;
		$access["project"]= "allProjects";
		$access["addUser"]= "N";
		$access["addGroup"]= "N";
		$this->aCluster["LOGGED_IN"]= $access;*/
		foreach($aCluster as $row)
		{// Projekt ID f�r Cluster nur anzeigen,
		 // wenn dieser zugriff auf das UserManagement hat
		 	$this->setMemberCluster($row["ID"], $row["Name"], $row["ProjectID"]);
		}
		/**/if( Tag::isDebug("user") )
		{
			echo "<b>found following cluster Memberships:</b><br /><pre>";
			st_print_r($this->getMemberClusters(), 2);
			if(Tag::isDebug("user.cluster"))
				echo "</pre><br />";
		}
	}
	function selectGroupID($groupname)
	{
		$group= $this->database->getTable("Group");
		$selector= new STDbSelector($group);
		$selector->select("Group", "ID");
		$selector->where("Name='$groupname'");
		$selector->limit(1);
		$selector->execute();
		$ID= $selector->getSingleResult();
		return $ID;
	}
	function writeLog($Type, $customID, $logText)
	{
		$url= $_SERVER["REQUEST_URI"];
		$post= "No";
		if(count($_POST) > 0)
			$post= "Yes";
		if(isset($this->sLogFile))
        {
        	STSession::writeLog($Type, $customID, $logText);
            return;
        }
        $user= $this->userID;
        if($user===null)
        	$user= -1;
        $project= $this->projectID;
        if($project==null)
        	$project= -1;
        $logTable= &$this->database->getTable("Log");
        $inserter= new STDbInserter($logTable);
        $inserter->fillColumn("UserID", $user);
        $inserter->fillColumn("ProjectID", $project);
        $inserter->fillColumn("Typ", $Type);
        $inserter->fillColumn("CustomID", $customID);
        $inserter->fillColumn("Description", $logText);
        $inserter->fillColumn("URL", $url);
        $inserter->fillColumn("post", $post);
        $inserter->fillColumn("DateCreation", "sysdate()");
        $inserter->execute();
        //showErrorTrace();
        //$statement=  "insert into ".$this->sLogTable."(UserID,ProjectID,Type,CustomID,Description,DateCreation) ";
        //$statement.= "values(".$user.",".$project.",".$Type.",".$customID.",'".$logText."',sysdate())";
        //$this->database->fetch($statement);
	}
	function acceptUser($user, $password= null)
	{
		STCheck::paramCheck($user, 1, "string", "int");
		STCheck::paramCheck($password, 2, "string");

		if(Tag::isDebug("user"))
		{
			$pwd= str_repeat("*", strlen($password));
			echo "<b>entering acceptUser(<em>&quot;".$user."&quot;, &quot;".$pwd."&quot;,</em>)</b><br />";
		}

		$userTable= $this->database->getTable("User");
		$userTable->clearSelects();
		$userTable->clearGetColumns();
		$userTable->select("ID");
		$userTable->select("UserName");
		$userTable->select("GroupType");
		if(is_string($user))
			$userTable->where("UserName='".$user."'");
		else
			$userTable->where("ID=".$user);
		$selector= new STDbSelector($userTable);
		$selector->execute();
		$row= $selector->getRowResult();

	  	$ID= $row[0];
		$user= $row[1];
		$this->user= $row[1];
		$this->session_vars["ST_USER"]= $row[1];
		$groupType= $row[2];
		if(is_numeric($row[2]))
		{
			$groupType= $this->database->getTable("GroupType");
			$groupType->clearSelects();
			$groupType->clearGetColumns();
			$groupType->select("GroupType", "Label");
			$groupType->where("ID=".$row[2]);
			$selector= new STDbSelector($groupType);
			$selector->execute();
			$groupType= $selector->getSingleResult();
		}
		if( Tag::isDebug("user") )
		{
			if(isset($ID))
			{
				echo "founded first row with ID:".$row[0]." and Grouptype:'".$row[1]."' in database from table MUUser:<br />";
			}else
				echo "do not found user in Database";
		}
	  	if(	!isset($ID)
	  		or
			$groupType!="custom"	)
	  	{
			Tag::echoDebug("user", "user from type ".$row[1]." so check accepting about ->getFromOtherConnections()");
			$result= $this->getFromOtherConnections($ID, $user, $password, $row[1]);
			//echo "accept is $result";exit;
			return $result;
		}
	  	//kein �berpr�fung �ber LDAP-Server
		if( !$ID )
			 return 1;// kein User mit diesem Namen vorhanden

		$oUserTable= $this->database->getTable("User");
		$userSelector= new STDbSelector($userTable);
		$userSelector->select("User", "ID");
		$userSelector->where("UserName='".$user."'");
		$userSelector->andWhere("Pwd=password('".$password."')");
		$userSelector->execute();
		$ID= $userSelector->getSingleResult();
		//$statement=	 "select ID from ".$this->sUserTable." ";
		//$statement.= "where UserName='$user' and Pwd=password('$password')";
		//$ID= $this->database->fetch_single($statement);
		if(!$ID)
		{
		 	 Tag::echoDebug("user", "do not found user with given password ...");
			 return 2;// Passwort ist falsch
		}
		$this->sGroupType= "custom";
		$this->userID= $ID;
		$this->user= $user;
		//$this->checkForLoggedIn();
		return 0;
	}
	function existsDbCluster($clusterName)
	{
		$cluster= $this->database->getTable("Cluster");
		$selector= new STDbSelector($cluster);
		$selector->clearSelects();
		$selector->count();
		$selector->where($selector->getPkColumnName()."='".$clusterName."'");
		$selector->execute();
		$exists= $selector->getSingleResult();
		if($exists)
			return true;
		return false;

	}
	function getPartitionID($partitionName)
	{
		if(isset($this->nPartition[$partitionName]))
			return $this->nPartition[$partitionName];

		$clusters= $this->getProjectCluster();
		$oPartition= $this->database->getTable("Partition");
		$desc= STDbTableDescriptions::instance();
  		$oPartition->accessBy($clusters[$desc->getColumnName("Project", "has_access")], STLIST);
		$oPartition->clearIdentifColumns();
		$oPartition->identifColumn("Name");
  		//$oPartition->accessBy("STUM-Insert", STINSERT);
  		//$oPartition->accessBy("STUM-Update", STUPDATE);
  		$oPartition->accessBy($clusters[$desc->getColumnName("Project", "can_delete")], STDELETE);
		$selector= new STDbSelector($oPartition);
		$selector->clearSelects();
		$selector->select("Partition", $oPartition->getPkColumnName());
		$selector->where("Name='".$partitionName."'");
		$selector->andWhere("ProjectID=".$this->projectID);
		$selector->execute();
		$id= $selector->getSingleResult();
		if($id)
		{
			$this->nPartition[$partitionName]= $id;
			return $id;
		}
		$oPartition->accessCluster("has_access", "ID", "Berechtigung zum ansehen der Partition \"@\"", true, "Project", $this->projectID);
		//$oPartition->insertCluster("can_insert", "ID", "Berechtigung zum ansehen der Partition \"@\"");
		//$oPartition->updateCluster("can_update", "ID", "Berechtigung zum ansehen der Partition \"@\"");
		$oPartition->deleteCluster("can_delete", "ID", "L�sch-Berechtigung der Partition \"@\"", true, "Project", $this->projectID);
		$inserter= new STDbInserter($oPartition);
		$inserter->fillColumn("Name", $partitionName);
		$inserter->fillColumn("ProjectID", $this->getProjectId());
		$inserter->fillColumn("DateCreation", "sysdate()");
		$error= $inserter->execute();
		if($error)
			return false;
		$last= $inserter->getLastInsertID();
		$this->nPartition[$partitionName]= $last;
		return $last;
	}
	function createCluster($clusterName, $accessInfoString, $sIdentifString= "regular system clusters", $addGroup= true)
	{
		Tag::paramCheck($clusterName, 1, "string");
		Tag::paramCheck($accessInfoString, 2, "string");
		Tag::paramCheck($sIdentifString, 3, "string");
		Tag::paramCheck($addGroup, 4, "bool");

		$this->setExistCluster($clusterName, $this->getProjectID());
		$partitionId= $this->getPartitionID($sIdentifString);
		$oCluster= &$this->database->getTable("Cluster");
		$insert= new STDbInserter($oCluster);
		$insert->fillColumn("ID", $clusterName);
		$insert->fillColumn("ProjectID", $this->projectID);
		$insert->fillColumn("Description", $accessInfoString);
		$insert->fillColumn("identification", $partitionId);
		$insert->fillColumn("DateCreation", "sysdate()");

		//$statement= $this->database->getInsertStatement($this->sClusterTable, $clusterContent);
		//$cluster= $clusterContent["ID"];
		if($insert->execute(noErrorShow)) //$this->database->fetch($statement, noDebugErrorShow))
			return "NOCLUSTERCREATE";
		$this->session_vars["ST_CLUSTER_MEMBERSHIP"][$clusterName]= $this->projectID;

		if(!$addGroup)
			return "NOERROR";
		$groupID= $this->createGroup($clusterName);
		if($groupID==-1)
			return "NOGROUPCREATE";

		if(!$this->joinClusterGroup($clusterName, $groupID))
			return "NOGROUPCONNECTCREATE";

		return "NOERROR";
	}
	function createGroup($groupName, $groupDescription= "")
	{
		$group= $this->database->getTable("Group");
		$inserter= new STDbInserter($group);
		$inserter->fillColumn("Name", $groupName);
		if($groupDescription)
			$inserter->fillColumn("description", $groupDescription);
		$inserter->fillColumn("DateCreation", "sysdate()");
		if($inserter->execute(noErrorShow))
			return -1;
		$groupID= $this->database->getLastInsertedPk();
		return $groupID;
	}
	function joinClusterGroup($clusterName, $group)
	{
		if(STCheck::isDebug())
		{
			$cluster= $this->database->getTable("Cluster");
			$cluster->clearSelects();
			$cluster->clearGetColumns();
			$cluster->count();
			$cluster->where("ID='".$clusterName."'");
			$selector= new STDbSelector($cluster);
			$selector->execute();
			if(STCheck::error(!$selector->getSingleResult(), "STUserSession::joinClusterGroup()", "group ".$group." for join to <b>CLUSTER</b> does not exist"))
				return -1;
		}
		if(is_string($group))
		{
			$grouptable= $this->database->getTable("Group");
			$grouptable->clearSelects();
			$grouptable->select("ID");
			$grouptable->where("Name='".$group."'");
			$selector= new STDbSelector($grouptable);
			$selector->execute();
			$groupId= $selector->getSingleResult();
			if(!$groupId)
			{
				STCheck::error(1, "STUserSession::joinClusterGroup()", "group ".$group." for join to <b>CLUSTER</b> does not exist");
				return -1;
			}
		}else
			$groupId= $group;

		$clusterGroup= $this->database->getTable("ClusterGroup");
		$inserter= new STDbInserter($clusterGroup);
		$inserter->fillColumn("ClusterID", $clusterName);
		$inserter->fillColumn("GroupID", $groupId);
		$inserter->fillColumn("DateCreation", "sysdate()");
		if($inserter->execute(noErrorShow))
			return -1;
		$ID= $this->database->getLastInsertedPk();
		return $ID;
	}
	function joinUserGroup($user, $group)
	{
		STCheck::paramCheck($user, 1, "int", "string");
		STCheck::paramCheck($group, 2, "int", "string");

		if(is_string($user))
		{
			$usertable= $this->database->getTable("User");
			$usertable->clearSelects();
			$usertable->select("ID");
			$usertable->where("UserName='".$user."'");
			$selector= new STDbSelector($usertable);
			$selector->execute();
			$userId= $selector->getSingleResult();
			if(!userId)
			{
				STCheck::error(1, "STUserSession::joinUserGroup()", "user ".$user." for join to <b>GROUP</b> does not exist");
				return -1;
			}
		}else
			$userId= $user;
		if(is_string($group))
		{
			$grouptable= $this->database->getTable("Group");
			$grouptable->clearSelects();
			$grouptable->select("ID");
			$grouptable->where("Name='".$group."'");
			$selector= new STDbSelector($grouptable);
			$selector->execute();
			$groupId= $selector->getSingleResult();
			if(!$groupId)
			{
				STCheck::error(1, "STUserSession::joinUserGroup()", "group ".$group." for join to <b>USER</b> does not exist");
				return -1;
			}
		}else
			$groupId= $group;

		$userGroup= $this->database->getTable("UserGroup");
		$inserter= new STDbInserter($userGroup);
		$inserter->fillColumn("UserID", $userId);
		$inserter->fillColumn("GroupID", $groupId);
		$inserter->fillColumn("DateCreation", "sysdate()");
		if($inserter->execute(noErrorShow))
			return -1;
		$ID= $this->database->getLastInsertedPk();
		return $ID;
	}
	function createAccessCluster($parentCluster, &$cluster, $accessInfoString, $sIdentifString, $addGroup= false)
	{
		Tag::paramCheck($parentCluster, 1, "string");
		Tag::paramCheck($cluster, 2, "string");
		Tag::paramCheck($accessInfoString, 3, "string");
		Tag::paramCheck($sIdentifString, 4, "string");
		Tag::paramCheck($addGroup, 5, "bool");

		$cluster= $parentCluster."_".$cluster;
		return $this->createCluster($cluster, $accessInfoString, $sIdentifString, $addGroup);
	}
	// deleteAccessCluster l�scht keine Gruppen,
	// nur STClusterGroup-eintr�ge
	function deleteAccessCluster($cluster)
	{
		Tag::paramCheck($cluster, 1, "string");

		$clusterGroup= $this->database->getTable($this->sClusterGroupTable);
		$clusterGroup->clearSelects();
		$clusterGroup->select("ID");
		$clusterGroup->select("GroupID");
		$clusterGroup->clearFks();
		$clusterGroup->modifyForeignKey(false);
		$clusterGroup->where($this->asClusterGroupTableColumns["ClusterID"]["column"]."='".$cluster."'");
		$statement= $this->database->getStatement($clusterGroup);
		$clusterGroupResult= $this->database->fetch($statement, noErrorShow);
		if(count($clusterGroupResult))
		{
			$statement= $this->database->getDeleteStatement($clusterGroup);//, $this->asClusterGroupTableColumns["ClusterID"]["column"]."='".$cluster."'");
			$this->database->fetch($statement, noErrorShow);
		}
		$statement= $this->database->getDeleteStatement($this->sClusterTable, $this->asClusterTableColumns["ID"]["column"]."='".$cluster."'");
		if(!$this->database->fetch($statement, noErrorShow))
			return "NOCLUSTERDELETE";
		return "NOERROR";
	}
}

?>