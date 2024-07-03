<?php

require_once($_stdbsession);
//require_once($_sttdate);

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
	 * all groups link to clusters for usermanagement
	 * @var string
	 */
	var $usermanagementAccessGroup= "UM_Access";
	var $usermanagementAdminGroup= "UM_CHANGE";
	var $usermanagementAvailableProfileGroup= "OwnProfile";
	var $profile_ChangeAccessCluster= "PR_ChangeAccess";

	/**
	 * all clusters for usermanagement
	 * @var string
	 */
	var $usermanagement_EMailAccess= "UM_EMailAccess";
	var $usermanagement_EMailModif= "UM_EMailModification";
	var $usermanagement_UserAccess= "UM_UserAccess";
	var $usermanagement_UserModif= "UM_UserModification";
	var $usermanagement_GroupModif= "UM_GroupModification";
	var $usermanagement_LogAccess= "UM_LOGAccess";

	var $usermanagement_ProjectAccess= "UM_ProjectAccess";
	var $usermanagement_ProjectModif= "UM_ProjectModification";
	var $usermanagement_ProjectAssign= "UM_ProjectGroupAssignment";

	var $usermanagement_ClusterAccess= "UM_ClusterAccess";
	var $usermanagement_ClusterModif= "UM_ClusterModification";
	var $usermanagement_ClusterAssign= "UM_ClusterGroupAssignment";

	
	/**
	 * user linked with this cluster
	 * has administration rights
	 * to all projects
	 * @var string
	 */
	var	$allAdminCluster= "allAdmin";
    var $allAdminGroup= "Admin";
	var	$sGroupTable= "Group";

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
	/**
	 * all project names predefined inside STProjectUserSiteCreator
	 * or any extended class
	 * @var array of string
	 */
	private $dbProjects= array();

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
    public function setDbProjectName(string $memberVarName, string $dbProjectName)
    {
        $this->dbProjects[$memberVarName]= array($dbProjectName => 'x');
    }
	public function setDbProjectID(string $dbProjectName, int $dbProjectID)
	{
		$bSet= false;
		foreach($this->dbProjects as &$project)
		{
			$name= array_key_first($project);
			if($name == $dbProjectName)
			{
				$project[$name]= $dbProjectID;
				$bSet= true;
				break;
			}
		}
		if(!$bSet)
			$this->dbProjects[$dbProjectName]= array($dbProjectName => $dbProjectID);
	}
	public function getDbProjectName(string $memberVarName)
    {
		if(isset($this->dbProjects[$memberVarName]))
			$sRv= array_key_first($this->dbProjects[$memberVarName]);
		STCheck::alert(!isset($sRv), "STUserSession::getDbProjectName()", "memberVarName '$memberVarName' for project not set inside STProjectSiteCreator", 1);
        return $sRv;
    }
	public function getDbProjectID(string $dbProjectName)
    {
		$sRv= null;
		foreach($this->dbProjects as $project)
		{
			$name= array_key_first($project);
			if($dbProjectName == $name)
			{
				$sRv= $project[$name];
				if($sRv == "x")
					$sRv= null;
				break;
			}
		}
        return $sRv;
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
		global $_stusermanagement;
	    
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
        $dbTableDescription->column("Project", "ID", "SMALLINT", /*null*/false);
        $dbTableDescription->primaryKey("Project", "ID");
        $dbTableDescription->autoIncrement("Project", "ID");
        $dbTableDescription->column("Project", "Name", "varchar(70)", /*null*/false);
        $dbTableDescription->uniqueKey("Project", "Name", 1);
		$dbTableDescription->column("Project", "sort", "INT", /*null*/false);
        $dbTableDescription->column("Project", "Path", "varchar(255)", /*null*/false);
		$dbTableDescription->column("Project", "Target", "set('BLANK','SELF','TOP')", /*null*/false, /*default*/"SELF");
        $dbTableDescription->column("Project", "display", "set('DISABLED','ENABLED')", /*null*/false, /*default*/"ENABLED");
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
        $dbTableDescription->foreignKey("User", "domain", "AccessDomain");		
        $dbTableDescription->column("User", "user", "varchar(50)", /*null*/false);
        $dbTableDescription->uniqueKey("User", "user", 1);
		$dbTableDescription->column("User", "sex", "set('FEMAIL','MALE','*GENDER','UNKNOWN')", /*null*/false);
		$dbTableDescription->column("User", "title_prefixed", "set('Dr.','MA','Mag','Dipl. Ing.')", /*null*/true);
        $dbTableDescription->column("User", "firstname", "varchar(100)", /*null*/true);
        $dbTableDescription->column("User", "surname", "varchar(100)", /*null*/true);
		$dbTableDescription->column("User", "title_subsequent", "set('PhD','MSc','BSc')", /*null*/true);
        //$dbTableDescription->column("User", "image", "varchar(255)", /*null*/true);
        $dbTableDescription->column("User", "email", "varchar(100)", /*null*/false);
		// password can be null because different registration methods exists
        $dbTableDescription->column("User", "Pwd", "char(50) binary", /*null*/true);
		//$dbTableDescription->column("User", "active", "set('NO', 'YES')", /*null*/false, /*default*/"NO");
		$dbTableDescription->column("User", "register", "set('CREATED', 'SENDMAIL', 'INACTIVE', 'ACTIVE')",  /*null*/true, /*default*/"CREATED");
		$dbTableDescription->column("User", "active", "set('NO', 'YES')", /*null*/false, /*default*/"YES");
		$dbTableDescription->column("User", "regcode", "varchar(100)", /*null*/true);
		$dbTableDescription->column("User", "sendingtime", "DATETIME", /*null*/true);
		$dbTableDescription->column("User", "NrLogin", "INT UNSIGNED", /*null*/true, /*default*/0);
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
        
		$dbTableDescription->table("Mail");
		$dbTableDescription->column("Mail", "ID", "INT", /*null*/false);
        $dbTableDescription->primaryKey("Mail", "ID");
        $dbTableDescription->autoIncrement("Mail", "ID");
		$dbTableDescription->column("Mail", "case", "varchar(50)", /*null*/false);
		$dbTableDescription->uniqueKey("Mail", "case", 1);
		$dbTableDescription->column("Mail", "description", "varchar(300)", /*null*/false);
		$dbTableDescription->column("Mail", "subject", "varchar(200)", /*null*/false);
		$dbTableDescription->column("Mail", "html", "set('NO', 'YES')", /*null*/false, /*default*/"NO");
		$dbTableDescription->uniqueKey("Mail", "html", 1);
		$dbTableDescription->column("Mail", "text", "text", /*null*/false);

        $dbTableDescription->table("Log");
        $dbTableDescription->column("Log", "ID", "BIGINT UNSIGNED", /*null*/false);
        $dbTableDescription->primaryKey("Log", "ID");
        $dbTableDescription->autoIncrement("Log", "ID");
        $dbTableDescription->column("Log", "UserID", "INT", /*null*/false);
        $dbTableDescription->foreignKey("Log", "UserID", "User", 1);
        $dbTableDescription->column("Log", "ProjectID", "TINYINT", /*null*/false);
        $dbTableDescription->foreignKey("Log", "ProjectID", "Project", 2);
        $dbTableDescription->column("Log", "Type", "set('DEBUG','LOGIN','LOGIN_ERROR','LOGOUT','ACCESS', 'ACCESS_ERROR')", /*null*/false);
        $dbTableDescription->column("Log", "CustomID", "varchar(255)");
        $dbTableDescription->column("Log", "Description", "TEXT", /*null*/false);
        $dbTableDescription->column("Log", "DateCreation", "DATETIME", /*null*/false);        
	}	
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
			$selector= new STDbSelector($project);
			$selector->execute(STSQL_ASSOC, 1);
			$this->projectCluster= $selector->getRowResult();
		}
		return $this->projectCluster;
	}
	function getDynamicClusters($table)
	{
	    Tag::paramCheck($table, 1, "STDbTable");

	    $clusters= $this->getSessionVar("ST_USER_DEFINED_VARS", "dynamic", $table->getName());
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
  		    $this->setRecursiveSessionVar($clusters, "ST_USER_DEFINED_VARS", "dynamic", $table->getName());
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
		$this->setRecursiveSessionVar($cluster, "ST_USER_DEFINED_VARS", "dynamic", $table, $action, $pkValue);
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
	    $selector= new STDbSelector($user);
	    $selector->where("User", "ID=".$this->getUserID());
	    $res= $selector->execute();
	    if($res <= 0)
	        return $res;
	    $aRv= $selector->getRowResult($sqlType);
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
	    $selector= new STDbSelector($user);
	    $res= $selector->execute();
	    if($res <= 0)
	        return $res;
        $aRv= $selector->getResult($sqlType);
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
	/**
	 * verify project on session management
	 *  
	 * @param string|integer $project project as Name like in database, or as project ID<br />
	 *                                see UserManagement 'existing projects'
	 * @param string|Tag $login define URL where user can login into system 
	 *                          (address where STUserProjectManagement defined), or direct login mask as Tag objects 
	 * @return bool whether user be logged-in
	 */
	public function verifyLogin($project, $login= "") : bool
	{
	    STCheck::param($project, 0, "int", "string");
	    STCheck::param($login, 1, "string", "empty(string)", "Tag");

	    $this->UserLoginMask= $login;
	    $loggedIn= $this->verifyProject($project);
	    return $loggedIn;
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

		if( $ProjectName == 0 ||
			$ProjectName==trim("##StartPage")	)
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
  	function setProperties(string $ProjectName= "")
  	{
  	    // property shouldn*t be an null string, parameter only be defined for STSession::setProperties()
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
		if( isset($this->userID) &&
		    $this->userID != -1        )
		{// toDo: search why have to set joins before selecting fields from other tables -> what will be wrong when bAddedFkTables set to true
			$projectCluster->leftJoin("UserGroup", "GroupID", "Group");
		}
		$projectCluster->select("Cluster", "ID", "ID");
		$projectCluster->select("Project", "Name", "Name");
		$projectCluster->select("Cluster", "ProjectID", "ProjectID");
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
		{
			// if no user defined
			// user called website was not known
			// so do not need logging, 
			// because don't know who had access
			return;
		}
        $project= $this->projectID;
        if($project===null)
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
		$selector= new STDbSelector($userTable);
		$selector->clearSelects();
		$selector->clearGetColumns();
		$selector->select("User", "ID", "ID");
		$selector->select("User", "user", "UserName");
		$selector->select("User", "active");
		$selector->select("User", "Pwd"); // if Pwd is "" user is in registration mode
		$selector->select("AccessDomain", "Name", "Domain");
		if(is_string($user))
			$selector->where("user='".$user."'");
		else
			$selector->where("ID=".$user);
		if($domain != "")
		{
		    $selector->andWhere("GroupType='$domain'");
		}
		$selector->execute();
		$row= $selector->getResult();

		$ID= -1;
		$groupType= "";
		$user= "";
		$active= "NO";
		$register= null;
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
			$active= $row[$rownr]['active'];
    	  	$groupType= $row[$rownr]['Domain'];
			if( $row[$rownr]['Pwd'] == "")
				$register= true;
			else
				$register= false;
    		
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
			 return 1;// no user with this name exist
		}

		$oWhere= new STDbWhere();
		$oWhere->where("ID='$ID'");
		if($register)
		{
			$timeSelect= new STDbSelector($userTable->getDatabase());
			$timeSelect->select("Mail", "case");
			$timeSelect->select("Mail", "subject");
			$timeSelect->where("case='MAIL_DAYS'");
			$timeSelect->orWhere("case='MAIL_MINUTES'");
			$timeSelect->execute();
			$rows= $timeSelect->getResult();
			$days= 5;
			$minutes= 5;
			foreach($rows as $row)
			{;
				if($row['case'] == "MAIL_MINUTES")
					$minutes= $row['subject'];
				if($row['case'] == "MAIL_DAYS")
					$days= $row['subject'];
			}
			$oWhere->andWhere("regcode='$password'");
			$oWhere->andWhere("DATEDIFF(NOW(), sendingtime) <= $days");
		}else
			$oWhere->andWhere("Pwd=password('".$password."')");

		$userSelector= new STDbSelector($userTable);
		$userSelector->select("User", "ID", "ID");
		$userSelector->select("User", "sendingtime");
		$userSelector->where($oWhere);
		$userSelector->execute();
		$user_row= $userSelector->getRowResult();

		if( !isset($user_row['ID']) ||
			!$user_row['ID'] ||
		    $user_row['ID'] != $ID    )
		{
			if($register)
			{
				STCheck::echoDebug("user", "user $user in registration mode gives wrong user-code or MAIL_DAYS expands");
				return 7;// activation time pass over or wrong code
			}
		 	 Tag::echoDebug("user", "do not found user with given password ...");
			 return 2;// password was wrong
		}
		$this->userID= $ID;
		$this->user= $user;
		if($active == "NO")
		{
			if($register)
			{// user should be in registration mode
				$this->setSessionVar("ST_USER", $user);
				$this->setSessionVar("ST_USERID", $ID);
				$this->setSessionVar("ST_REGISTRATION", true);
				return 8;
			}
			return 6;// user inactive
		}
		$this->setSessionVar("ST_USER", $user);
		$this->setSessionVar("ST_USERID", $ID);
		return 0;// correct login
	}
	public function existsDbCluster(string $clusterName)
	{
		$cluster= $this->container->getTable("Cluster");
		$selector= new STDbSelector($cluster);
		//$selector->clearSelects();
		$selector->count();
		
		$whereObj= new STDbWhere();
		$whereObj->where($selector->getPkColumnName()."='$clusterName'");
		$whereObj->andWhere("ProjectID=".$this->getProjectID());
		$selector->where($whereObj);
		$selector->execute();
		$exists= $selector->getSingleResult();
		if($exists)
			return true;
		return false;
	}
	public function existsDbClusterGroupJoin(string $clusterName, string|int $groupIdName)
	{
	    $clusterWhere= new STDbWhere();
	    $clusterWhere->table("Cluster");
	    $clusterWhere->where("ID='$clusterName'");
		$groupWhere= new STDbWhere();
		if(is_numeric($groupIdName))
		{
			$groupWhere->table("ClusterGroup");
			$groupWhere->orWhere("GroupID='$groupIdName'");
		}else
		{
			$groupWhere->table("Group");
			$groupWhere->orWhere("Name='$groupIdName'");
		}
	    
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
	    
	    $selector= new STDbSelector($group);
	    $selector->clearSelects();
	    $selector->count();
	    
	    $oWhere= new STDbWhere();
	    $oWhere->where("Name='$groupName'");
	    $oWhere->andWhere("domain='$domain'");
	    $oWhere->table("Group");
	    
	    $selector->where($oWhere);
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
		$oPartition= $this->container->getTable("Partition");
		$desc= STDbTableDescriptions::instance($this->database->getDatabaseName());
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
	        if( $domain['ID'] == null )
	        {	            
    	        if($this->createDomain($domain['Name'], $domain['Prefix'], $domain['Description']) == -1)
    	            return null;
    	        $domain= $this->getDomain($this->mainDOMAIN);
	        }
	    }
	    return $domain;
	}
	public function createCluster(string $clusterName, int|string $projectNameID, string $accessInfoString, bool $addGroup= true) : string
	{
		if(is_string($projectNameID))
		{
			$projectID= $this->getDbProjectID($projectNameID);
			if(!isset($projectID))
			{
				$oProject= $this->container->getTable("Project");
				$projectSelector= new STDbSelector($oProject);
				$projectSelector->select("Project", "ID", "ID");
				$projectSelector->where("Project", "Name='$projectNameID'");
				$projectSelector->execute(noErrorShow);
				$projectID= $projectSelector->getSingleResult();
				if(STCheck::is_error(!isset($projectID), "STUserSession::createCluster()", "no project '$projectNameID' stored inside database"))
					return "NOCLUSTERCREATE";
				$this->setDbProjectID($projectNameID, $projectID);
			}
		}else
			$projectID= $projectNameID;
	    if(isset($_SESSION))
	    {
    	    if($this->doClusterExist($clusterName, $projectID))
    	        return "NOCLUSTERCREATE";
	    }elseif($this->existsDbCluster($clusterName))
	        return "NOCLUSTERCREATE";
		$this->setExistCluster($clusterName, $projectID);
		//$partitionId= $this->getPartitionID($sIdentifString);
		$oCluster= &$this->container->getTable("Cluster");
		$insert= new STDbInserter($oCluster);
		$insert->fillColumn("ID", $clusterName);
		$insert->fillColumn("ProjectID", $projectID);
		$insert->fillColumn("Description", $accessInfoString);
		//$insert->fillColumn("identification", $partitionId);
		$insert->fillColumn("DateCreation", "sysdate()");

		//$statement= $this->database->getInsertStatement($this->sClusterTable, $clusterContent);
		//$cluster= $clusterContent["ID"];
		if($insert->execute(noErrorShow)) //$this->database->fetch($statement, noDebugErrorShow))
			return "NOCLUSTERCREATE";
		//$this->setRecursiveSessionVar($this->projectID, "ST_CLUSTER_MEMBERSHIP", $clusterName);

		if(!$addGroup)
			return "NOERROR";
		$groupID= $this->createGroup($clusterName);
		if($groupID==-1)
			return "NOGROUPCREATE";

		if(!$this->joinClusterGroup($clusterName, $groupID))
			return "NOGROUPCONNECTCREATE";

		return "NOERROR";
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
	public function joinClusterGroup(string $clusterName, string|int $groupIdName)
	{
	    // select only whether exist
	    $cluster= new STDbSelector($this->container->getTable("Cluster"));
		$cluster->select("Cluster", "ID", "ID");
		$cluster->where("ID='".$clusterName."'");
		$cluster->execute();
		if(STCheck::is_error($cluster->getErrorId(), "STUserSession::joinClusterGroup()", 
							"cluster '$clusterName' for join to <b>GROUP</b> does not exist", 2))
		    return -1;
		  
		if(!is_numeric($groupIdName))
		{
			$grouptable= new STDbSelector($this->container->getTable("Group"));
			$grouptable->select("Group", "ID", "ID");
			$grouptable->where("Name='".$groupIdName."'");
			$grouptable->execute();
			if(STCheck::is_error($grouptable->getErrorId(), "STUserSession::joinClusterGroup()", 
								"group ".$groupIdName." for join to <b>CLUSTER</b> does not exist", 2))
				return -1;
			$groupId= $grouptable->getSingleResult();
			if(!isset($groupId))
			{
				echo "<br>joinClusterGroup('$clusterName', $groupIdName)<br>";
				echo $grouptable->getErrorString()."<br>";
				echo $grouptable->getStatement()."<br>";
				showBackTrace();
			}
			if(STCheck::is_error(!isset($groupId), "STUserSession::joinClusterGroup()", 
					"cannot join from cluster '$clusterName' to non exist group '$groupIdName'", 2))
				return -1;
		}else
			$groupId= intval($groupIdName);
		
		if($this->existsDbClusterGroupJoin($clusterName, $groupIdName))
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
			$selector= new STDbSelector($usertable);
			$selector->execute();
			$userId= $selector->getSingleResult();
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
			$selector= new STDbSelector($grouptable);
			$selector->execute();
			$groupId= $selector->getSingleResult();
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

		$clusterGroup= $this->container->getTable($this->sClusterGroupTable);
		$clusterGroup->clearSelects();
		$clusterGroup->select("ID", "ID");
		$clusterGroup->select("GroupID", "GroupID");
		$clusterGroup->clearFks();
		$clusterGroup->modifyForeignKey(false);
		$clusterGroup->where($this->asClusterGroupTableColumns["ClusterID"]["column"]."='".$cluster."'");
		$statement= $clusterGroup->getStatement();
		$clusterGroupResult= $this->database->fetch($statement, noErrorShow);
		if(count($clusterGroupResult))
		{
		    $deleter= new STDbDeleter($clusterGroup);
		    $deleter->execute(noErrorShow);
		}
		$deleter= new STDbDeleter($this->sClusterTable);
		$deleter->where($this->asClusterTableColumns["ID"]["column"]."='".$cluster."'");
		if($deleter->execute(noErrorShow) !== 0)
			return "NOCLUSTERDELETE";
		return "NOERROR";
	}
}

?>