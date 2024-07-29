<?php

require_once($_stdbsession);

class STUserSession extends STDbSession
{
	var $user;
	var $userID;
	var $bLog;
	/**
	 * main domain name for all user
	 * registerd with this usermanagement
	 * @var string
	 */
	private $mainDOMAIN= "custom";
	private $accessDomains= array(   array(  "ID" => -1,
	                                       "Name" => "xxx", // <- will be defined inside constructor
        	                               "Prefix" => "*",
        	                               "Description" => "default domain for all User and Groups which have access over this UserManagement"    )   );
	var	$userManagementProjectName= "UserManagement";
	
	// ATTENTION - group name has to be exist inside SQL Group Table
	/**
	 * user has always access to projects
	 * linked with this group.<br />
	 * (do not need to be logged-in)
	 * @var string
	 */
	var $onlineGroup= "ONLINE";	
	/**
	 * user has access to projects
	 * linked with this group
	 * only if logged-in 
	 * @var string
	 */
	var $loggedinGroup= "LOGGED_IN";
	/**
	 * user has access to all cluster
	 * defined for UserManagement
	 * @var string
	 */
	var $usermanagementAccessGroup= "UM_Access";
	var $usermanagementAdminGroup= "UM_CHANGE";
	/**
	 * user linked with this cluster
	 * is administrator and has
	 * access to all other clusters
	 * and so also to all projects
	 * @var string
	 */
	var $usermanagement_AccessCluster= "UM_ClusterManagementAccess";
	var $usermanagement_ChangeCluster= "UM_ClusterManagementCHANGE";
	var $usermanagement_User_Access= "UM_UserAccess";
	var $usermanagement_User_Change= "UM_UserCHANGE";
	/**
	 * user linked with this cluster
	 * has administration rights
	 * to all projects
	 * @var string
	 */
	var	$allAdminCluster= "allAdmin";
    var $allAdminGroup= "Admin";
	var	$sGroupTable= "Group";
	var $sClusterGroupTable= "ClusterGroup";

	// all for own Projects
	var $projectAccessTable;
	var $sProjectIDColumn= "ProjectID";
	var $sClusterIDColumn= "ClusterID";
	var $sAuthorisationColumn= "Authorisation";
	var $aProjectAccessCluster= array();
	/**
	 * whether defiend database table description	 * 
	 * @var boolean
	 */
	private $bDescriptionDefined= false;

	protected function __construct($Db)
	{
	    $this->accessDomains[0]['Name']= $this->mainDOMAIN;
		STDbSession::__construct($Db);
		STDbSession::storeSessionOnFile(true);
		$this->bLog= true;		

		$this->aSessionVars[]= "ST_USER";
		$this->aSessionVars[]= "ST_USERID";
		$this->aSessionVars[]= "ST_PROJECTID";
		$this->aSessionVars[]= "ST_LOGGED_MESSAGES";
	}
	/**
	 * initial object of session
	 *
	 * @param object $instance should be the database where the session will be stored,
	 *                           or by overloading from an other class it can be the instance from there
	 *                           (default parameter null only defined because parameters hase to be compatible with parent object definition)
	 * @param string $prefix can be the prefix string for tables inside database
	 */
	public static function init(&$instance= null, string $prefix= "")
	{
	    STCheck::param($instance, 0, "STDatabase", "STDbSession");
	    STCheck::param($prefix, 1, "string", "empty(string)");
	    
	    $object= null;
	    if(typeof($instance, "STDatabase"))
	        $object= new STUserSession($instance, $prefix);
	    elseif( typeof($instance, "STDbSession") &&
	            !typeof($instance, "STUserSession")  )
	    {
	        $object= new STUserSession($instance->getDatabase(), $prefix);
	    }else
	        $object= &$instance;	        
	       
	    return STDbSession::init($object, $prefix);
	}
	/**
	 * set new custom domain.<br />
	 * have to set inside the constructor of the overloaded class
	 * 
	 * @param string $name name of the new custom domain
	 * @param string $prefix prefix with which a new cluster be filled
	 * @param string $description description for this domain
	 */
	public function setNewCustomDomain(string $name, string $prefix= null, string $description= null)
	{
	    $this->mainDOMAIN= $name;
	    $domainKey= null;
	    foreach($this->accessDomains as $key => $curDomain)
	        if($curDomain["ID"] == -1)
	        {
	            $domainKey= $key;
	            break;
	        }
	    if(isset($domain))
	    {
    	    if(isset($prefix))
    	        $this->accessDomains[$domainKey]['Prefix']= $prefix;
	        if(isset($description))
	            $this->accessDomains[$domainKey]['Description']= $description;
	    }
	}
	public function getOnlineGroup() : string
	{ return $this->onlineGroup; }
	public function getLoggedinGroup() : string
	{ return $this->loggedinGroup; }
	public function getAllAdminCluster() : string
	{ return $this->allAdminCluster; }
	public function defineDatabaseTableDescriptions($dbTableDescription)
	{
	    global $_stum_installcontainer;
	    
	    STCheck::paramCheck($dbTableDescription, 1, "STDbTableDescriptions");
	    
	    if($this->bDescriptionDefined)
	        return;
	    $this->bDescriptionDefined= true;
	    STDbSession::defineDatabaseTableDescriptions($dbTableDescription);
	        
        $dbTableDescription->table("Query");
        $dbTableDescription->column("Query", "ID", "BIGINT", /*null*/false);
        $dbTableDescription->primaryKey("Query", "ID");
        $dbTableDescription->autoIncrement("Query", "ID");
        $dbTableDescription->column("Query", "path", "TEXT", /*null*/false);
        $dbTableDescription->indexKey("Query", "path", 1, 255);
        
/*        $dbTableDescription->table("Translate");
        $dbTableDescription->column("Translate", "ID", "varchar(50)", false);
        $dbTableDescription->primaryKey("Translate", "ID");
        $dbTableDescription->uniqueKey("Translate", "ID", 1);
        $dbTableDescription->indexKey("Translate", "ID", 1);
        $dbTableDescription->column("Translate", "lang", "char(3)", false);
        $dbTableDescription->uniqueKey("Translate", "lang", 1);
        $dbTableDescription->indexKey("Translate", "lang", 1);
        $dbTableDescription->column("Translate", "translation", "text", false);*/
        
        $dbTableDescription->table("AccessDomain");
        $dbTableDescription->column("AccessDomain", "ID", "TINYINT", /*null*/false);
        $dbTableDescription->primaryKey("AccessDomain", "ID");
        $dbTableDescription->autoIncrement("AccessDomain", "ID");
        $dbTableDescription->column("AccessDomain", "Name", "varchar(10)", /*null*/false);
        $dbTableDescription->column("AccessDomain", "Label", "varchar(30)", /*null*/false);
        $dbTableDescription->column("AccessDomain", "Description", "varchar(255)");
        $dbTableDescription->column("AccessDomain", "DateCreation", "DATETIME", /*null*/false);
        
        $dbTableDescription->table("Project");
        $dbTableDescription->column("Project", "ID", "TINYINT", /*null*/false);
        $dbTableDescription->primaryKey("Project", "ID");
        $dbTableDescription->autoIncrement("Project", "ID");
        $dbTableDescription->column("Project", "Name", "varchar(70)", /*null*/false);
        $dbTableDescription->uniqueKey("Project", "Name", 1);
        $dbTableDescription->column("Project", "Path", "varchar(255)", /*null*/false);
        $dbTableDescription->column("Project", "Description", "text");
        $dbTableDescription->column("Project", "DateCreation", "datetime", /*null*/false);
 /*       $dbTableDescription->column("Project", "has_access", "varchar(255)", false);
        $dbTableDescription->column("Project", "can_insert", "varchar(255)", false);
        $dbTableDescription->column("Project", "can_update", "varchar(255)", false);
        $dbTableDescription->column("Project", "can_delete", "varchar(255)", false);*/
        
/*        $dbTableDescription->table("Partition");
        $dbTableDescription->column("Partition", "ID", "SMALLINT", false);
        $dbTableDescription->primaryKey("Partition", "ID");
        $dbTableDescription->autoIncrement("Partition", "ID");
        $dbTableDescription->column("Partition", "Name", "varchar(100)", false);
        $dbTableDescription->uniqueKey("Partition", "Name", 1);
        $dbTableDescription->column("Partition", "ProjectID", "TINYINT", false);
        $dbTableDescription->foreignKey("Partition", "ProjectID", "Project");
        $dbTableDescription->column("Partition", "has_access", "varchar(255)", false);
        //$dbTableDescription->column("Partition", "can_insert", "varchar(255)", false);
        //$dbTableDescription->column("Partition", "can_update", "varchar(255)", false);
        $dbTableDescription->column("Partition", "can_delete", "varchar(255)", false);
        $dbTableDescription->column("Partition", "DateCreation", "DATETIME", /false);*/
        
        $dbTableDescription->table("User");
        $dbTableDescription->column("User", "ID", "INT", /*null*/false);
        $dbTableDescription->primaryKey("User", "ID");
        $dbTableDescription->autoIncrement("User", "ID");
        $dbTableDescription->column("User", "domain", "TINYINT", /*null*/false);
        //$dbTableDescription->uniqueKey("User", "domain", 1);
        $dbTableDescription->foreignKey("User", "domain", "AccessDomain");
        $dbTableDescription->column("User", "user", "varchar(50)", /*null*/false);
        $dbTableDescription->uniqueKey("User", "user", 1);
        $dbTableDescription->column("User", "FullName", "varchar(100)", /*null*/true);
        $dbTableDescription->column("User", "image", "varchar(255)", /*null*/true);
        $dbTableDescription->column("User", "email", "varchar(100)", /*null*/false);
        $dbTableDescription->column("User", "Pwd", "char(50) binary", /*null*/false);
        $dbTableDescription->column("User", "NrLogin", "INT UNSIGNED");
        $dbTableDescription->column("User", "LastLogin", "DATETIME");
        $dbTableDescription->column("User", "DateCreation", "DATETIME", /*null*/false);
        
        $dbTableDescription->table("Cluster");
        $dbTableDescription->column("Cluster", "ID", "varchar(100)", /*null*/false);
        $dbTableDescription->primaryKey("Cluster", "ID");
        $dbTableDescription->column("Cluster", "ProjectID", "TINYINT", /*null*/false);
        $dbTableDescription->foreignKey("Cluster", "ProjectID", "Project", 1);
        $dbTableDescription->column("Cluster", "Description", "TEXT", /*null*/false);
        //$dbTableDescription->column("Cluster", "identification", "SMALLINT", /*null*/false);
        //$dbTableDescription->foreignKey("Cluster", "identification", "Partition", 2);
        //$dbTableDescription->column("Cluster", "lastDynamicAccess", "set('false', 'true')", true);
        $dbTableDescription->column("Cluster", "DateCreation", "DATETIME", /*null*/false);
        
        $dbTableDescription->table("Group");
        $dbTableDescription->column("Group", "ID", "INT", /*null*/false);
        $dbTableDescription->primaryKey("Group", "ID");
        $dbTableDescription->autoIncrement("Group", "ID");
        $dbTableDescription->column("Group", "domain", "TINYINT", /*null*/false);
        $dbTableDescription->foreignKey("Group", "domain", "AccessDomain");
        $dbTableDescription->column("Group", "Name", "varchar(100)", /*null*/false);
        $dbTableDescription->uniqueKey("Group", "Name", 1);
        //$dbTableDescription->column("Group", "Description", "TEXT");
        $dbTableDescription->column("Group", "DateCreation", "DATETIME", /*null*/false);
        
        $dbTableDescription->table("ClusterGroup");
        $dbTableDescription->column("ClusterGroup", "ID", "INT", /*null*/false);
        $dbTableDescription->primaryKey("ClusterGroup", "ID");
        $dbTableDescription->autoIncrement("ClusterGroup", "ID");
        $dbTableDescription->column("ClusterGroup", "ClusterID", "varchar(100)", /*null*/false);
        $dbTableDescription->foreignKey("ClusterGroup", "ClusterID", "Cluster", 1);
        $dbTableDescription->column("ClusterGroup", "GroupID", "INT", /*null*/false);
        $dbTableDescription->foreignKey("ClusterGroup", "GroupID", "Group", 2);
        $dbTableDescription->column("ClusterGroup", "DateCreation", "DATETIME", /*null*/false);
        
        $dbTableDescription->table("UserGroup");
        $dbTableDescription->column("UserGroup", "ID", "INT", /*null*/false);
        $dbTableDescription->primaryKey("UserGroup", "ID");
        $dbTableDescription->autoIncrement("UserGroup", "ID");
        $dbTableDescription->column("UserGroup", "UserID", "INT", /*null*/false);
        $dbTableDescription->foreignKey("UserGroup", "UserID", "User", 1);
        $dbTableDescription->column("UserGroup", "GroupID", "INT", /*null*/false);
        $dbTableDescription->foreignKey("UserGroup", "GroupID", "Group", 2);
        $dbTableDescription->column("UserGroup", "DateCreation", "DATETIME", /*null*/false);
        
/*        $dbTableDescription->table("GroupGroup");
        $dbTableDescription->column("GroupGroup", "ID", "INT", false);
        $dbTableDescription->primaryKey("GroupGroup", "ID");
        $dbTableDescription->autoIncrement("GroupGroup", "ID");
        $dbTableDescription->column("GroupGroup", "Group1ID", "INT", false);
        $dbTableDescription->column("GroupGroup", "Group2ID", "INT", false);
        $dbTableDescription->foreignKey("GroupGroup", "Group1ID", "Group", 1);
        $dbTableDescription->foreignKey("GroupGroup", "Group2ID", "Group", 2);
        $dbTableDescription->column("GroupGroup", "DateCreation", "DATETIME", false); */
        
        $dbTableDescription->table("Log");
        $dbTableDescription->column("Log", "ID", "BIGINT UNSIGNED", /*null*/false);
        $dbTableDescription->primaryKey("Log", "ID");
        $dbTableDescription->autoIncrement("Log", "ID");
        $dbTableDescription->column("Log", "UserID", "INT", /*null*/false);
        $dbTableDescription->foreignKey("Log", "UserID", "User", 1);
        $dbTableDescription->column("Log", "ProjectID", "TINYINT", /*null*/false);
        $dbTableDescription->foreignKey("Log", "ProjectID", "Project", 2);
        $dbTableDescription->column("Log", "Type", "set('ERROR','LOGIN','LOGOUT','ACCESS')", /*null*/false);
        $dbTableDescription->column("Log", "CustomID", "varchar(255)");
        $dbTableDescription->column("Log", "Description", "TEXT", /*null*/false);
        $dbTableDescription->column("Log", "DateCreation", "DATETIME", /*null*/false);
        
        STObjectContainer::install("um_install", "STUM_InstallContainer", "userDb", $_stum_installcontainer);
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
	function setPrefixToTables($prefix)
	{
		STCheck::param($prefix, 0, "string");

		$desc= &STDbTableDescriptions::instance($this->database->getDatabaseName());
		$desc->setPrefixToTables($prefix);
	}
	function projectTable($name)
	{
		$this->sProjectTable= $name;
	}
	function getProjectCluster()
	{
		if(!$this->projectCluster)
		{
			$project= $this->container->getTable("Project");
        	$project->clearSelects();
        	$project->clearIdentifColumns();
        	$project->clearGetColumns();
        	$project->select("has_access");
        	$project->select("can_insert");
        	$project->select("can_update");
        	$project->select("can_delete");
			$project->where("ID=".$this->projectID);
			$clusterGroupSelector= new STDbSelector($project);
			$clusterGroupSelector->execute(STSQL_ASSOC, 1);
			$this->projectCluster= $clusterGroupSelector->getRowResult();
		}
		return $this->projectCluster;
	}
	/**
	 * Get all dynamic clusters from table
	 * and store it inside session
	 * 
	 * @param STDbTable $table table object from which need
	 * @return array cluster array as follow as showen<br />
	 * 					array[action][pk][column]= cluster
	 * 						action	- allowed permission for cluster
	 * 								  'access', 'admin' or defined
	 * 						pk		- primary key of table entry
	 * 						column	- column name in which cluster stored
	 * 						cluster	- dynamic cluster for permission
	 */
	function getDynamicClusters($table) : array
	{
	    Tag::paramCheck($table, 1, "STDbTable");

		if(!count($table->sAccessClusterColumn))
			return array();
		$clusters= array();
		$store_in_session= false;
		if($store_in_session)
	    	$clusters= $this->getSessionVar("ST_USER_DEFINED_VARS", "dynamic", $table->getName());
    	if(	!$store_in_session ||
			!is_array($clusters)	)
  		{
			$pkColumn= $table->getPkColumnName();
  		    $clusterGroupSelector= new STDbSelector($table);
			$clusterGroupSelector->allowQueryLimitation($table->allowQueryLimitation());
  		    $clusterGroupSelector->select($table->Name, $pkColumn);
  		    foreach($table->sAccessClusterColumn as	$clusterInfo)
  		        $clusterGroupSelector->select($table->Name, $clusterInfo["column"]);
			$clusterGroupSelector->execute();
			//$clusterGroupSelector->displayWrappedStatement();
			$result= $clusterGroupSelector->getResult();
			$clusters= array();
  		    foreach($result as $row)
  		    {
  		        foreach($table->sAccessClusterColumn as	$key=>$clusterInfo)
			    {
					if(trim($row[$clusterInfo["column"]]) == "")
						STCheck::warning(1, "STUserSession::getDynamicClusters()", 
							"inside table {$table->getName()} row of $pkColumn:{$row[$pkColumn]} column {$clusterInfo["column"]} has no cluster");
					else
			        	$clusters[$clusterInfo["action"]][$row[$pkColumn]][$clusterInfo["column"]]= $row[$clusterInfo["column"]];
			    }
  		    }
			if($store_in_session)
  		    	$this->setRecursiveSessionVar($clusters, "ST_USER_DEFINED_VARS", "dynamic", $table->getName());
        }
    	return $clusters;
	}
	function addDynamicCluster(string $cluster)//($table, $action, $pkValue, $permission, $cluster)
	{
/*	    Tag::paramCheck($table, 1, "STDbTable", "string");
	    Tag::paramCheck($action, 2, "int", "string");
	    Tag::paramCheck($pkValue, 3, "int", "string");
	    Tag::paramCheck($cluster, 4, "string");

		if(typeof($table, "STDbTable"))
		    $table= $table->getName();
		$this->setRecursiveSessionVar($cluster, "ST_USER_DEFINED_VARS", "dynamic", $table, $action, $pkValue, $permission);*/
		$this->setRecursiveSessionVar(true, "ST_EXIST_CLUSTER", $cluster);
	}
	function projectColumn($defined, $column, $alias= null)
	{
		Tag::paramCheck($defined, 1, "string");
		Tag::paramCheck($column, 2, "string");
		Tag::paramCheck($alias, 3, "string", "null");
		Tag::alert(!$this->asProjectTableColumns[$defined], $defined." is no defined column in STGalleryContainer");
		STCheck::is_warning($alias&&!$this->asProjectTableColumns[$defined]["alias"], "defined column ".$defined." has no alias name");

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
		STCheck::is_warning($alias&&!$this->asClusterTableColumns[$defined]["alias"], "defined column ".$defined." has no alias name");

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
		STCheck::is_warning($alias&&!$this->asClusterGroupTableColumns[$defined]["alias"], "defined column ".$defined." has no alias name");

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
		STCheck::is_warning($alias&&!$this->asGroupTableColumns[$defined]["alias"], "defined column ".$defined." has no alias name");

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
		STCheck::is_warning($alias&&!$this->asUserGroupTableColumns[$defined]["alias"], "defined column ".$defined." has no alias name");

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
		STCheck::is_warning($alias&&!$this->asUserTableColumns[$defined]["alias"], "defined column ".$defined." has no alias name");

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
		STCheck::is_warning($alias&&!$this->asLogTableColumns[$defined]["alias"], "defined column ".$defined." has no alias name");

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
		STCheck::is_warning($alias&&!$this->asGroupTypeTableColumns[$defined]["alias"], "defined column ".$defined." has no alias name");

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
	function &getUserDb()
	{
		return $this->database;
	}
	function makeTableMeans()
	{
		// take tables from database
		echo "makeTableMeans()<br>";
		showBackTrace();
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
        $User->identifColumn($this->asUserTableColumns["user"]["column"], $this->asUserTableColumns["user"]["alias"]);

		// set foreign keys in tables
        $Cluster->foreignKey($this->asClusterTableColumns["ProjectID"]["column"], $this->sProjectTable);
        $ClusterGroup->foreignKey($this->asClusterGroupTableColumns["ClusterID"]["column"], $this->sClusterTable);
        $ClusterGroup->foreignKey($this->asClusterGroupTableColumns["GroupID"]["column"], $this->sGroupTable);
        $UserGroup->foreignKey($this->asUserGroupTableColumns["GroupID"]["column"], $this->sGroupTable);
        $UserGroup->foreignKey($this->asUserGroupTableColumns["UserID"]["column"], $this->sUserTable);
        $Log->foreignKey($this->asLogTableColumns["UserID"]["column"], $this->sUserTable);
        $Log->foreignKey($this->asLogTableColumns["ProjectID"]["column"], $this->sProjectTable);
	}
	function setLog($bLog)
	{
		$this->bLog= $bLog;
	}
  	public function getUserID()
  	{
  	    $userID= $this->getSessionVar("ST_USERID");
  	    if(!isset($userID))
	  		return 0;
  		return $userID;
  	}
	public function getUserName()
	{
	    $user= $this->getSessionVar("ST_USER");
	    if( !$this->isLoggedIn() &&
	        $this->loginUser != "" )
	    {
	        $user= $this->loginUser;
	    }
	    if(!isset($user))
	        return "";
	    return $user;
	}
	/**
	 * return database entry from current user.<br />
	 * (with maybe modified table from database)
	 * 
	 * @param string $sqlType which keys the database row should have (default: STSQL_ASSOC)
	 * @return array array of database row content, by error -> error nr
	 */
	public function getUserData($sqlType= null)
	{
	    $user= $this->container->getTable("User");
	    $clusterGroupSelector= new STDbSelector($user);
	    $clusterGroupSelector->where("User", "ID=".$this->getUserID());
	    $res= $clusterGroupSelector->execute();
	    if($res <= 0)
	        return $res;
	    $aRv= $clusterGroupSelector->getRowResult($sqlType);
		// search correct name inside database
	    $pwd= $user->searchByColumn("Pwd");
	    unset($aRv[$pwd['column']]);// toDo: maybe better create new function for STdbSelector "->noSelect()"
	                                //       because the selector should take the pre-defined columns from User-table
	    return $aRv;
	}
	/**
	 * return database list from all users.<br />
	 * (with maybe modified table from database)
	 *
	 * @param string $sqlType which keys the database rows should have (default: STSQL_ASSOC)
	 * @return array
	 */
	public function getUserDataList($sqlType= null)
	{
	    $user= $this->container->getTable("User");
	    $clusterGroupSelector= new STDbSelector($user);
	    $res= $clusterGroupSelector->execute();
	    if($res <= 0)
	        return $res;
        $aRv= $clusterGroupSelector->getResult($sqlType);
        $pwd= $user->searchByColumn("Pwd");// search correct name inside database
        foreach($aRv as &$row)
        {// toDo: maybe better create new function for STdbSelector "->noSelect()"
         //       because the selector should take the pre-defined columns from User-table
            unset($aRv[$pwd['column']]);
        }
	    return $aRv;
	}
	public function getProjectID()
	{
		return $this->projectID;
	}
	public function getProjectName()
	{
		return $this->project;
	}
	function hasUserManagementAccess($projectID= null, $addType= addUser)
	{
		Tag::deprecated("is died", "STUserSession::hasManagementAccess()");

		$membership= $this->getSessionVar("ST_CLUSTER_MEMBERSHIP");
		if(	isset($membership[$this->allAdminCluster])
			and
			$this->isLoggedIn())
		{
  			$this->LOG(STACCESS, 0, "user is super-admin");
  			/**/Tag::echoDebug("user", "-&gt; User is Super-Admin &quot;allAdmin&quot; so return TRUE<br />");
  			return true;
  		}
		$anyAccess= false;
		$projectAccess= false;
		if(is_array($membership))
		{
		    foreach($membership as $project)
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
			$this->setSessionVar("ST_PROJECTID", 0);
		}else
		{
  			// deffiniere Projekt
  			$proj= $this->container->getTable("Project");
  			$project= new STDbSelector($proj);
  			$project->select("Project", "ID");
  			$project->select("Project", "Name");
  			$project->select("Project", "Path");
  			$project->allowQueryLimitationByOwn(false);
			$where= new STDbWhere();
			if(is_numeric($ProjectName))
				$where->andWhere("ID=".$ProjectName);
			else
				$where->andWhere("Name='$ProjectName'");
			$project->where($where);
			$project->execute();
			$row= $project->getRowResult();
			if(!is_array($row) || count($row)==0 )
			{
    			STCheck::alert(1, "STUserSession::setUserProject()",
      										"Project <q><b>$ProjectName</b></q>; is not defined in the database", 2 );
    			exit;
			}
  			$this->projectID= $row['ID'];
  			$this->project= $row['Name'];
			if(!$this->startPage)
			{
				/**/Tag::echoDebug("user", "set startPage from database to ".$row['Path']);
			    $this->startPage= $row['Path'];
			}
			$this->setSessionVar("ST_PROJECTID", $this->projectID);
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

			$clusterTable= $this->container->getTable("Cluster");
			$clusterTable->select("ID");
			$clusterTable->select("ProjectID");
			$statement= $clusterTable->getStatement();
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
	var $countReadCluster= 0;
	function readCluster()
	{// hole alle Cluster,
	 // zugeh�rig zum Projekt und User
	 // aus der Datenbank
	 	/**/if(0)//Tag::isDebug())
		{
		    /**/Tag::echoDebug("user", "<b>entering readCluster..</b>");
		    echo __FILE__.__LINE__."<br>";
	 		$GroupID= $this->selectGroupID($this->loggedinGroup);
			Tag::alert(!$GroupID, "STUserSession::readCluster()", "no ".$this->loggedinGroup." Group in Db-Table ".
																		$this->sGroupTable." exists");
	 		$GroupID= $this->selectGroupID($this->onlineGroup);
			Tag::alert(!$GroupID, "STUserSession::readCluster()", "no ".$this->onlineGroup." Group in Db-Table ".
																		$this->sGroupTable." exists");
		}
		
		//$statement= "select ID,ProjectID from MUCluster";
		$oCluster= &$this->container->getTable("Cluster");
		$clusterSelector= new STDbSelector($oCluster, STSQL_ASSOC);
		$clusterSelector->select("Cluster", "ID", "ID");
		$clusterSelector->select("Cluster", "ProjectID", "ProjectID");
		$clusterSelector->execute();
		$aClusters= $clusterSelector->getResult();
		foreach($aClusters as $row)
			$this->setExistCluster($row["ID"], $row["ProjectID"]);
		/**/if( Tag::isDebug("user") )
		{
			$space= STCheck::echoDebug("user", "<b>found existing clusters:</b>");
			st_print_r($this->getExistClusters(), 2, $space);
		}

		$oProject= $this->container->getTable("Project");
		if(!typeof($oProject, "STDbSelector"))
		  $projectCluster= new STDbSelector($oProject, STSQL_ASSOC);
		else
		  $projectCluster= &$oProject;
		$projectCluster->distinct();
		$projectCluster->select("Cluster", "ID");
		$projectCluster->select("Project", "Name");
		$projectCluster->select("Cluster", "ProjectID");
		if( isset($this->userID) &&
		    $this->userID != -1        )
		{//$this->$loggedinGroup;
			$groupWhere= new STDbWhere("Name='".$this->onlineGroup."'", "Group", "or");
			$groupWhere->writeWhereCondition();
			$groupWhere->orWhere("Name='".$this->loggedinGroup."'");
			$usergroupWhere= new STDbWhere("UserID=".$this->userID, "UserGroup");
			$usergroupWhere->writeWhereCondition();
			$groupWhere->orWhere($usergroupWhere);
		}else		    
		    $groupWhere= new STDbWhere("Name='".$this->onlineGroup."'", "Group", "and");
	    if( STCheck::isDebug("user"))
	    {
	        $space= STCheck::echoDebug("user", "make where statement for project table:");
	        st_print_r($groupWhere, 10, $space+30);
	    }
		$projectCluster->where($groupWhere);
		
		
		
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
			STCheck::echoDebug("user", "checking in database for statement:");
			STCheck::echoDebug("user", $statement);
		}
		$projectCluster->execute();
		$aCluster= $projectCluster->getResult();
		if(STCheck::isDebug("user"))
		{
		    $space= STCheck::echoDebug("user", "<b>found follow memberships inside database:</b>");
		    st_print_r($aCluster, 2, 34+$space);
		}
		
		// toDo: clear setting where statement inside STDbSelector $projectCluster
		//       but shouldn't be set inside project table (18.10.2022)
		$oProject->clearWhere();
		
		$this->aCluster= array();
		foreach($aCluster as $row)
		{// Projekt ID f�r Cluster nur anzeigen,
		 // wenn dieser zugriff auf das UserManagement hat
		    if( isset($row['ID']) &&
		        $row['ID'] != null    )
		    {
		 	    $this->setMemberCluster($row["ID"], $row["Name"], $row["ProjectID"]);
		    }
		}
		/**/if( Tag::isDebug("user") )
		{
		    $memberClusters= $this->getMemberClusters();
		    if($memberClusters)
		    {
		        $space= STCheck::echoDebug("user", "<b>found following cluster Memberships set inside SESSION:</b>");
    			st_print_r($memberClusters, 2, $space+34);
		    }else
		        STCheck::echoDebug("user", "<b>no cluster for Memberships be set</b>");
		    if(STCheck::isDebug("session"))
		    {
		        $space= STCheck::echoDebug("session", "follow session variables be set");
		        st_print_r($_SESSION, 5, $space);
				echo "<br>";
				STCheck::echoDebug("session", "testing position");
				showBackTrace();
				echo "<br>";
		    }
		}
	}
	function selectGroupID($groupname)
	{
		$group= $this->constainer->getTable("Group");
		$clusterGroupSelector= new STDbSelector($group);
		$clusterGroupSelector->select("Group", "ID");
		$clusterGroupSelector->where("Name='$groupname'");
		$clusterGroupSelector->limit(1);
		$clusterGroupSelector->execute();
		$ID= $clusterGroupSelector->getSingleResult();
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
        $logTable= &$this->container->getTable("Log");
        $inserter= new STDbInserter($logTable);
        $inserter->fillColumn("UserID", $user);
        $inserter->fillColumn("ProjectID", $project);
        $inserter->fillColumn("Type", $Type);
        $inserter->fillColumn("CustomID", $customID);
        $inserter->fillColumn("Description", $logText);
        $inserter->fillColumn("URL", $url);
        $inserter->fillColumn("post", $post);
        $inserter->fillColumn("DateCreation", "sysdate()");
        $inserter->execute();
        //showBackTrace();
        //$statement=  "insert into ".$this->sLogTable."(UserID,ProjectID,Type,CustomID,Description,DateCreation) ";
        //$statement.= "values(".$user.",".$project.",".$Type.",".$customID.",'".$logText."',sysdate())";
        //$this->database->fetch($statement);
	}
	/**
	 * check wheter authentication with user password is correct
	 * 
	 * @param string|int $user user ID or name from database or LDAP<br />
	 *                         user name can also be a domain with user separated with '\' 
	 * @param string $password password also set like user before
	 * @return int login error code or 0 by correct user/password @see STSession::getLoginError()
	 */
	function acceptUser($user, $password= null) : int
	{
		STCheck::paramCheck($user, 1, "string", "int");
		STCheck::paramCheck($password, 2, "string", "empty(string)");

		if(Tag::isDebug("user"))
		{
			$pwd= str_repeat("*", strlen($password));
			STCheck::echoDebug("user", "<b>entering acceptUser(<em>&quot;".$user."&quot;, &quot;".$pwd."&quot;,</em>)</b>");
		}

		$domain= "";
		if(is_string($user))
		{
		    $preg= preg_split("/\\\\/", $user);
    		if( $preg !== false &&
    	        count($preg) == 2  )
    	    {
                $domain= $preg[0];
                $user= $preg[1];
    	    }
		}
		$userTable= $this->container->getTable("User");
		$clusterGroupSelector= new STDbSelector($userTable);
		$clusterGroupSelector->clearSelects();
		$clusterGroupSelector->clearGetColumns();
		$clusterGroupSelector->select("User", "ID", "ID");
		$clusterGroupSelector->select("User", "user", "UserName");
		$clusterGroupSelector->select("AccessDomain", "Name", "Domain");
		if(is_string($user))
			$clusterGroupSelector->where("user='".$user."'");
		else
			$clusterGroupSelector->where("ID=".$user);
		if($domain != "")
		{
		    $clusterGroupSelector->andWhere("GroupType='$domain'");
		}
		$clusterGroupSelector->execute();
		$row= $clusterGroupSelector->getResult();

		$ID= -1;
		$groupType= "";
		$user= "";
		$existrows= count($row);
		if($existrows > 0 )
		{
		    $rownr= 0;
		    if(count($row) > 1)
		    {
		        if(STCheck::isDebug("user"))
		        {
		            $domains= array();
    		        foreach($row as $line)
    		            $domains[$line['GroupType']]= "used";
    		        $domain= "";
    		        foreach($domains as $dom=>$used)
    		            $domain.= "$dom/";
    		        $domain= substr($domain, 0, strlen($domain)-1);
    		        STCheck::echoDebug("user", "ambiguous user found by follow domains '$domain'");
		        }
		        return 3;
		    }
    	  	$ID= $row[$rownr]['ID'];
    	  	$user= $row[$rownr]['UserName'];
    	  	$groupType= $row[$rownr]['Domain'];
    		
		}
		if( STCheck::isDebug("user") )
		{
		    $msg= "";
			if($existrows > 0)
			{
			    if(count($row) > 1)
			        STCheck::echoDebug("user", "found more than one user '$user', take first with group-type $groupType");
				$msg= "found user '$user' with ID:$ID and Grouptype:'$groupType' inside database table MUUser";
			}else
				$msg= "do not found any user with name '$user' in Database";
			STCheck::echoDebug("user", $msg);
		}
	  	if(	$ID == -1 ||
			$groupType != "custom"	)
	  	{
	  	    if($ID == -1)
	  	    {
	  	        $msg= "user do not exist inside database";
	  	        $type= "unknown";	  	        
	  	    }else
	  	    {
	  	        $msg= "user from type ".$groupType;
	  	        $type= $groupType;
	  	    }
	  	        
	  	    $msg.= ", so check accepting about ->getFromOtherConnections()";
	  	    STCheck::echoDebug("user", $msg);
			$result= $this->getFromOtherConnections($ID, $user, $password, $type);
			if($result === 0)
			{
    			$this->setSessionVar("ST_USER", $user);
    			$this->setSessionVar("ST_USERID", $ID);
			}
			return $result;
		}
	  	//kein Ueberpruefung ueber LDAP-Server
		if( $ID == -1 )
		{
		    STCheck::echoDebug("user", "user do not exist inside database");
			 return 1;// kein User mit diesem Namen vorhanden
		}

		$oWhere= new STDbWhere();
		$oWhere->where("ID='$ID'");
		$oWhere->andWhere("Pwd=password('".$password."')");
		
		//$oUserTable= $this->container->getTable("User");
		$userSelector= new STDbSelector($userTable);
		$userSelector->select("User", "ID", "ID");
		$userSelector->where($oWhere);
		$userSelector->execute();
		$corrID= $userSelector->getSingleResult();
		if( !$corrID ||
		    $corrID != $ID    )
		{
		 	 Tag::echoDebug("user", "do not found user with given password ...");
			 return 2;// Passwort ist falsch
		}
		//$this->sGroupType= "custom";
		$this->userID= $ID;
		$this->user= $user;
		$this->setSessionVar("ST_USER", $user);
		$this->setSessionVar("ST_USERID", $ID);
		//$this->checkForLoggedIn();
		return 0;
	}
	public function existsDbCluster(string $clusterName)
	{
		$cluster= $this->container->getTable("Cluster");
		$clusterGroupSelector= new STDbSelector($cluster);
		//$clusterGroupSelector->clearSelects();
		$clusterGroupSelector->count();
		
		$whereObj= new STDbWhere();
		$whereObj->where($clusterGroupSelector->getPkColumnName()."='$clusterName'");
		$whereObj->andWhere("ProjectID=".$this->getProjectID());
		$clusterGroupSelector->where($whereObj);
		$clusterGroupSelector->execute();
		$exists= $clusterGroupSelector->getSingleResult();
		if($exists)
			return true;
		return false;
	}
	public function existsDbClusterGroupJoin(string $clusterName, string $groupName)
	{
	    $clusterWhere= new STDbWhere();
	    $clusterWhere->table("Cluster");
	    $clusterWhere->where("ID='$clusterName'");
	    $groupWhere= new STDbWhere();
	    $groupWhere->table("Group");
	    $groupWhere->orWhere("Name='$groupName'");
	    
	    $clustergroup= new STDbSelector($this->container->getTable("ClusterGroup"));
	    $clustergroup->count();
	    $clustergroup->where($clusterWhere);
	    $clustergroup->andWhere($groupWhere);
	    $clustergroup->execute();
	    $count= $clustergroup->getSingleResult();
	    if($count > 0)
	        return true;
	    return false;
	}
	public function existsDbGroup(string $groupName, string $domainName)
	{
	    $domain= $this->getDomainID($domainName);
	    $group= $this->container->getTable("Group");
	    
	    $clusterGroupSelector= new STDbSelector($group);
	    $clusterGroupSelector->clearSelects();
	    $clusterGroupSelector->count();
	    
	    $oWhere= new STDbWhere();
	    $oWhere->where("Name='$groupName'");
	    $oWhere->andWhere("domain='$domain'");
	    $oWhere->table("Group");
	    
	    $clusterGroupSelector->where($oWhere);
	    $clusterGroupSelector->execute();
	    $exists= $clusterGroupSelector->getSingleResult();	    
	    if($exists)
	        return true;
	    return false;
	}
	function getPartitionID($partitionName)
	{
		if(isset($this->nPartition[$partitionName]))
			return $this->nPartition[$partitionName];

		$clusters= $this->getProjectCluster();
		$oPartition= $this->container->getTable("Partition");
		$desc= STDbTableDescriptions::instance($this->database->getDatabaseName());
  		$oPartition->accessBy($clusters[$desc->getColumnName("Project", "has_access")], STLIST);
		$oPartition->clearIdentifColumns();
		$oPartition->identifColumn("Name");
  		//$oPartition->accessBy("STUM-Insert", STINSERT);
  		//$oPartition->accessBy("STUM-Update", STUPDATE);
  		$oPartition->accessBy($clusters[$desc->getColumnName("Project", "can_delete")], STDELETE);
		$clusterGroupSelector= new STDbSelector($oPartition);
		$clusterGroupSelector->clearSelects();
		$clusterGroupSelector->select("Partition", $oPartition->getPkColumnName());
		$clusterGroupSelector->where("Name='".$partitionName."'");
		$clusterGroupSelector->andWhere("ProjectID=".$this->projectID);
		$clusterGroupSelector->execute();
		$id= $clusterGroupSelector->getSingleResult();
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
	public function getDomain(string $domainName)
	{
	    foreach($this->accessDomains as $domain)
	    {
	        if($domain['Name'] == $domainName)
	            return $domain;
	    }
	    return null;
	}
	public function getCustomDomain() : array
	{
	    $domain= $this->getDomain($this->mainDOMAIN);
	    STCheck::alert(!isset($domain), "STUserSession::getCustomDomain()", "get from method STUserSession::getDomain() wrong null value");
	    if( $domain['ID'] == -1 )
	    {
	        $table= $this->container->getTable("AccessDomain");
	        $domainTable= new STDbSelector($table);
	        $domainTable->select("AccessDomain", $table->getPKColumnName(), "ID");
	        $domainTable->where("Name='".$this->mainDOMAIN."'");
	        $domainTable->execute(onErrorStop);
	        $domain['ID']= $domainTable->getSingleResult();
	        if( $domain['ID'] == -1 )
	        {	            
    	        if($this->createDomain($domain['Name'], $domain['Prefix'], $domain['Description']) == -1)
    	            return null;
    	        $domain= $this->getDomain($this->mainDOMAIN);
	        }
	    }
	    return $domain;
	}
	public function createCluster(string $clusterName, string $accessInfoString, bool $addGroup= true) : string
	{
	    if(isset($_SESSION))
	    {
    	    if($this->doClusterExist($clusterName, $this->getProjectID()))
    	        return "NOCLUSTERCREATE";
	    }elseif($this->existsDbCluster($clusterName))
	        return "NOCLUSTERCREATE";
		$this->setExistCluster($clusterName, $this->getProjectID());
		//$partitionId= $this->getPartitionID($sIdentifString);
		$oCluster= &$this->container->getTable("Cluster");
		$insert= new STDbInserter($oCluster);
		$insert->fillColumn("ID", $clusterName);
		$insert->fillColumn("ProjectID", $this->projectID);
		$insert->fillColumn("Description", $accessInfoString);
		//$insert->fillColumn("identification", $partitionId);
		$insert->fillColumn("DateCreation", "sysdate()");

		if($insert->execute(onDebugErrorShow))
			return "NOCLUSTERCREATE";
		$this->setRecursiveSessionVar($this->projectID, "ST_CLUSTER_MEMBERSHIP", $clusterName);

		if(!$addGroup)
			return "NOERROR";
		$groupID= $this->createGroup($clusterName);
		if($groupID==-1)
			return "NOGROUPCREATE";

		if(!$this->joinClusterGroup($clusterName, $clusterName))
			return "NOGROUPCONNECTCREATE";

		return "NOERROR";
	}
	/**
	 * remove cluster from database
	 * with all any more unused groups
	 * 
	 * @param string $cluster name of cluster
	 * @param bool|array $withGroup whether should also remove group deamons.
	 * 								can also be an array of clusters to which connection can also be removed,
	 * 								because when group link to an unknown cluster, the group will not be removed
	 * 								also when command to
	 * @return string|array array of error strings or 'NOERROR' as string
	 */
	public function removeCluster(string $cluster, bool|array $withGroup) : string|array
	{
		if(is_array($withGroup))
		{
			$bWithGroup= true;
			
		}else
			$bWithGroup= $withGroup;
		$saRv= $this->removeClusterGroup($cluster, $withGroup);
		
		if(!is_string($saRv)) // ERROR
		{
			foreach($saRv as $error)
			{
				if($error == "NOCLUSTERGROUPREMOVED")
				{
					$saRv[]= "NOCLUSTERREMOVED";
					if($bWithGroup)
					{
						$saRv[]= "NOGROUPREMOVED";
						$saRv[]= "NOUSERGROUPREMOVED";
					}
					return $saRv;
				}
			}
		}

		$oCluster= $this->container->getTable("Cluster");
		$deleter= new STDbDeleter($oCluster);
		$deleter->where("ID='$cluster'");
		if($deleter->execute(noErrorShow) !== 0)
		{
			$saRv[]= "NOCLUSTERREMOVED";
			if($bWithGroup)
			{
				$saRv[]= "NOGROUPREMOVED";
				$saRv[]= "NOUSERGROUPREMOVED";
			}
			return $saRv;
		}else
			$this->removeExistCluster($cluster);
		return $saRv;// saRv can be NOERROR from removeClusterGroup
	}
	public function removeGroup(string|int $group)
	{
		$oGroup= $this->container->getTable("Group");
		if(!is_numeric($group))
		{
			$groupSelector= new STDbSelector($oGroup);
			$groupSelector->select("Group", "ID", "ID");
			$groupSelector->where("Name='$group'");
			$groupSelector->execute(noErrorShow);
			$groupid= $groupSelector->getSingleResult();
		}else
		{
			$groupid= $group;
			$group= null;
		}
		$res= $this->removeUserGroup("group", $groupid);
		if(!is_string($res))
		{ // error occured
			$aRv[]= "NOUSERGROUPREMOVED";
			$aRv[]= "NOGROUPREMOVED";
			return $aRv;
		}
		$deleter= new STDbDeleter($oGroup);
		$deleter->where("ID=$groupid");
		$res= $deleter->execute();
		if($res != 0)
			return array( "NOGROUPREMOVED" );
		return "NOERROR";
	}
	public function removeUserGroup(string $whatdef, int $ID) : string
	{
		$userGroup= $this->container->getTable("UserGroup");
		$deleter= new STDbDeleter($userGroup);
		if($whatdef == "user")
			$deleter->where("UserIDs=$ID");
		else
			$deleter->where("GroupID=$ID");
		$res= $deleter->execute(noErrorShow);
		if($res == 0)
			return "NOERROR";
		return "DELETIONFAULT";
	}
	/**
	 * remove entrys from table ClusterGroup
	 * 
	 * @param string|int $fromClusterGroup	name of cluster or direct group ID.<br>
	 * 										If variable is an cluster remove all entrys from table ClusterGroup
	 * 										which link to this cluster. If variable is an group ID
	 * 										only this ClusterGroup entry will be removed. (variable $withGroups is than not nessasary)
	 * @param bool|array $withGroup whether should also remove group deamons.
	 * 								can also be an array of clusters to which connection can also be removed,
	 * 								because when group link to an unknown cluster, the group will not be removed
	 * 								also when command to
	 * @return string|array array of error strings or 'NOERROR' as string
	 */
	public function removeClusterGroup(string|int $fromClusterGroup, bool|array $withGroups= false) : string|array
	{
		if(is_array($withGroups))
		{
			$bWithGroups= true;
			
		}else
			$bWithGroups= $withGroups;

		$clusterGroup= $this->container->getTable("ClusterGroup");
		$clusterGroupSelector= new STDbSelector($clusterGroup, STSQL_ASSOC, noErrorShow);
		if(	is_string($fromClusterGroup) &&
			!is_numeric($fromClusterGroup)	)
		{
			$aRv= array();
			$clusterGroupSelector->select("ClusterGroup", "ID", "ID");
			$clusterGroupSelector->select("ClusterGroup", "GroupID", "GroupID");
			$clusterGroupSelector->clearFks();
			$clusterGroupSelector->allowQueryLimitation(false);
			$clusterGroupSelector->where("ClusterID='".$fromClusterGroup."'");
			//$statement= $clusterGroupSelector->getStatement();
			$clusterGroupSelector->execute();
			$clusterGroupResult= $clusterGroupSelector->getSingleArrayResult();
			if($bWithGroups)
				$groupResult= $clusterGroupSelector->getSingleArrayResult("GroupID");
			else
				$groupResult= array();
		}else
		{
			$groupResult= array();
			$clusterGroupResult= array();
			$clusterGroupResult[]= $fromClusterGroup;
		}
		if(count($clusterGroupResult))
		{
		    $deleter= new STDbDeleter($clusterGroupSelector, STSQL_ASSOC, noErrorShow);
			$where= new STDbWhere();
			$where->IN("ID", $clusterGroupResult);
			$deleter->where($where);
		    if($deleter->execute() != 0)
			{
				$aRv[]= "NOCLUSTERGROUPREMOVED";
				return $aRv;
			}
		}else
			return array("NOCLUSTERGROUPEXIST");
		foreach($groupResult as $group)
		{
			// all connections from the cluster to the group are severed
			// now check whether the groups also have a connection to an other cluster
			$clusterGroupCheck= new STDbSelector($clusterGroup, STSQL_ASSOC, noErrorShow);
			$clusterGroupCheck->select("ClusterGroup", "ID", "ID");
			$clusterGroupCheck->select("ClusterGroup", "ClusterID");
			$clusterGroupCheck->clearWhere();
			$clusterGroupCheck->where("GroupID=$group");
			//$statement= $clusterGroupCheck->getStatement();
			$clusterGroupCheck->execute();
			$allClusterGroups= $clusterGroupCheck->getResult();
			if(is_array($withGroups))
			{
				$clusterGroupResult= array();
				foreach($allClusterGroups as $existing)
				{
					if(in_array($existing['ClusterID'], $withGroups))
						$this->removeClusterGroup($existing['ID']);
					else
						$clusterGroupResult[]= $existing;
				}
			}else
				$clusterGroupResult= $allClusterGroups;
			if(empty($clusterGroupResult))
			{
				$res= $this->removeGroup($group);
				if($res != "NOERROR")
				{
					if(is_array($res))
						$aRv= array_merge($aRv, $res);
					else
						$aRv[]= $res;
				}
			}else
				$aRv[]= "NOTALLGROUPSREMOVED";
		}
		if(empty($aRv))
			return "NOERROR";
		return $aRv;
	}
	private function getDomainKey(string $domainName)
	{
	    $nRv= -1;
	    foreach($this->accessDomains as $key => $domain)
	    {
	        if($domain['Name'] == $domainName)
	        {
	            $nRv= $key;
	            break;
	        }
	    }
	    return $nRv;
	}
	public function createDomain(string $name, string $prefix, string $description) : int
	{
	    $id= $this->getDomainID($name);
	    if($id == -1)
	    {
	        $domain= $this->getUserDb()->getTable("AccessDomain");
	        $ins= new STDbInserter($domain);
	        $ins->fillColumn("Name", $name);
	        $ins->fillColumn("Label", $prefix);
	        $ins->fillColumn("Description", $description);
	        $ins->fillColumn("DateCreation", "sysdate()");
	        $ins->execute(noErrorShow);
	        $id= $ins->getLastInsertID();
	        if( $ins->getErrorId() != 0 ||
	            $id == null                )
	        {
	            $id= -1;
	        }
	    }
	    return $id;
	}
	public function getDomainID(string $domainName)
	{
	    $nRv= -1;
	    $nKey= $this->getDomainKey($domainName);
	    if($nKey != -1)
	        $nRv= $this->accessDomains[$nKey]['ID'];
	    if($nRv == -1)
	    {
	        $domain= $this->getUserDb()->getTable("AccessDomain");
	        $domainSelector= new STDbSelector($domain);
	        $domainSelector->select("AccessDomain", "ID", "ID");
	        $domainSelector->select("AccessDomain", "Name", "Name");
	        $domainSelector->select("AccessDomain", "Label", "Label");
	        $domainSelector->select("AccessDomain", "Description", "Description");
	        $domainSelector->where("Name='$domainName'");
	        $domainSelector->execute();
	        $res= $domainSelector->getRowResult();
	        if( $res == null &&
	            $domainSelector->getErrorId() == 0 )
	        {
	            $nRv= -1;
	        }else
	        {
	            $nRv= $res['ID'];
	            if($nKey == -1)
	            {
	                $d= array( "ID" => $res['ID'],
	                           "Name" => $res['Name'],
	                           "Label" => $res['Label'],
	                           "Description" => $res['Description']    );
	                $this->accessDomains[]= $d;
	            }else
	                $this->accessDomains[$nKey]['ID']= $res['ID'];
	        }
	    }
	    return $nRv;
	}
	public function createGroup(string $groupName, string $domain= null)
	{
	    if(!isset($domain))
	        $domain= $this->accessDomains[0]['Name'];
	    $domainID= $this->getDomainID($domain);
	    if($this->existsDbGroup($groupName, $domain))
	       return -1; 
        
		$group= $this->container->getTable("Group");
		$inserter= new STDbInserter($group);
		$inserter->fillColumn("Name", $groupName);
		$inserter->fillColumn("domain", $domainID);
		$inserter->fillColumn("DateCreation", "sysdate()");		
		if($inserter->execute())//noErrorShow))
		{
		    echo __FILE__.__LINE__."<br>";
		    echo "errno:".$inserter->getErrorId()."<br>";
		    echo $inserter->getErrorString()."<br>";
			return false;
		}
		$groupID= $this->database->getLastInsertedPk();
		return $groupID;
	}
	public function joinClusterGroup(string $clusterName, string $groupName)
	{
	    // select only whether exist
	    $cluster= new STDbSelector($this->container->getTable("Cluster"));
		$cluster->select("Cluster", "ID", "ID");
		$cluster->where("ID='".$clusterName."'");
		$cluster->execute();
		if(STCheck::is_error($cluster->getErrorId(), "STUserSession::joinClusterGroup()", "cluster ".$clusterName." for join to <b>GROUP</b> does not exist", 2))
		    return -1;
		    
	    $grouptable= new STDbSelector($this->container->getTable("Group"));
	    $grouptable->select("Group", "ID", "ID");
		$grouptable->where("Name='".$groupName."'");
		$grouptable->execute();
		if(STCheck::is_error($grouptable->getErrorId(), "STUserSession::joinClusterGroup()", "group ".$groupName." for join to <b>CLUSTER</b> does not exist", 2))
		    return -1;
	    $groupId= $grouptable->getSingleResult();
		
		if($this->existsDbClusterGroupJoin($clusterName, $groupName))
		    return -1;

		$clusterGroup= $this->container->getTable("ClusterGroup");
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
		STCheck::param($user, 0, "int", "string");
		STCheck::param($group, 1, "int", "string");

		if(is_string($user))
		{
			$usertable= $this->container->getTable("User");
			$usertable->clearSelects();
			$usertable->select("ID", "ID");
			$usertable->where("user='".$user."'");
			$clusterGroupSelector= new STDbSelector($usertable);
			$clusterGroupSelector->execute();
			$userId= $clusterGroupSelector->getSingleResult();
			if(!userId)
			{
				STCheck::is_error(1, "STUserSession::joinUserGroup()", "user ".$user." for join to <b>GROUP</b> does not exist", 1);
				return -1;
			}
		}else
			$userId= $user;
		if(is_string($group))
		{
			$grouptable= $this->container->getTable("Group");
			$grouptable->clearSelects();
			$grouptable->select("ID", "ID");
			$grouptable->where("Name='".$group."'");
			$clusterGroupSelector= new STDbSelector($grouptable);
			$clusterGroupSelector->execute();
			$groupId= $clusterGroupSelector->getSingleResult();
			if(!$groupId)
			{
				STCheck::is_error(1, "STUserSession::joinUserGroup()", "group ".$group." for join to <b>USER</b> does not exist", 1);
				return -1;
			}
		}else
			$groupId= $group;

		$userGroup= $this->container->getTable("UserGroup");
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
		STCheck::param($parentCluster, 0, "string", "null");
		STCheck::param($cluster, 1, "string");
		STCheck::param($accessInfoString, 2, "string");
		STCheck::param($sIdentifString, 3, "string");
		STCheck::param($addGroup, 4, "bool");

		if(	isset($parentCluster) &&
			trim($parentCluster) != ""	)
		{
			$cluster= $parentCluster."_".$cluster;
		}
		return $this->createCluster($cluster, $accessInfoString, $sIdentifString, $addGroup);
	}
	// deleteAccessCluster l�scht keine Gruppen,
	// nur STClusterGroup-eintr�ge
	function deleteAccessCluster($cluster)
	{
		STCheck::param($cluster, 0, "string");

		$clusterGroup= $this->container->getTable($this->sClusterGroupTable);
		$clusterGroup->clearSelects();
		$clusterGroup->select("ID", "ID");
		$clusterGroup->select("GroupID", "GroupID");
		$clusterGroup->clearFks();
		$clusterGroup->allowQueryLimitation(false);
		$clusterGroup->where("ClusterID='".$cluster."'");
		$clusterGroupSelector= new STDbSelector($clusterGroup);
		//$statement= $clusterGroup->getStatement();
		$clusterGroupSelector->execute(noErrorShow);
		//$clusterGroupResult= $this->database->query($statement, noErrorShow);
		$clusterGroupResult= $clusterGroupSelector->getResult();
		if(count($clusterGroupResult))
		{
		    $deleter= new STDbDeleter($clusterGroup);
		    $deleter->execute(noErrorShow);
		}
		$deleter= new STDbDeleter("Cluster");
		$deleter->where($this->asClusterTableColumns["ID"]["column"]."='".$cluster."'");
		if($deleter->execute(noErrorShow) !== 0)
			return "NOCLUSTERDELETE";
		return "NOERROR";
	}
}

?>