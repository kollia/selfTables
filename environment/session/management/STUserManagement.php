<?php

//global $_stdbinserter;
require_once($_stdbinserter);
require_once($_stsitecreator);
require_once($_stuserclustergroupmanagement);

function pwdCallback(STCallbackClass &$callbackObject, $columnName, $rownum)
{
    if(!$callbackObject->before)
        return;
        
    //$callbackObject->echoResult();
    $session= STUSerSession::instance();
    $domain= $session->getCustomDomain();
    $table= $callbackObject->getTable();
    $domainField= $table->findAliasOrColumn("domain");
    $domainColumn= $domainField['alias'];
    $domainValue= $callbackObject->getValue($domainColumn);
    
    if($domainValue != $domain['Name'])
        $callbackObject->disabled($columnName);
}
function descriptionCallback(&$callbackObject, $columnName, $rownum)
{
    //$bFirstCall= $callbackObject->echoResult();
    global	$global_selftable_session_class_instance;
    
    $instance= $global_selftable_session_class_instance[0];
    
    {
        $clusterTable= $callbackObject->getTable("Cluster");
        $cluster= new STDbSelector($clusterTable);
        $cluster->select("Project", "Name", "Name");
        $cluster->select("Cluster", "ID", "cluster");
        $cluster->select("Cluster", "Description", "Description");
        $cluster->where("ClusterGroup", "GroupID=".$callbackObject->sqlResult[$rownum]['access descriptions']);
        $cluster->execute();
        $aResult= $cluster->getResult();
    }
    $source=   "<table>";
    foreach($aResult as $row)
    {
        $source.=  "	<tr>";
        $source.=  "		<td>";
        $source.=  "			<b>";
        $source.=  "				[";
        if($row['cluster'] != $instance->allAdminCluster)
            $source.= $row["Name"];
        $source.= "]";
        $source.=  "			</b>";
        $source.=  "		</td>";
        $source.=  "		<td>";
        $source.=  "				".$row["Description"];
        $source.=  "		</td>";
        $source.=  "	</tr>";
    }
    $source.=  "</table>";
    $callbackObject->setValue($source);
    
}

function actionCallback(&$callbackObject, $columnName, $rownum)
{
    $session= STUSerSession::instance();
    $domain= $session->getCustomDomain();
    if( $callbackObject->sqlResult[$rownum]["Group"] == $session->onlineGroup ||
        $callbackObject->sqlResult[$rownum]["Group"] == $session->loggedinGroup ||
        $callbackObject->sqlResult[$rownum]["Domain"] != $domain['ID']              )
    {
        $callbackObject->noUpdate();
        $callbackObject->noDelete();
    }
}

class STUserManagement extends STObjectContainer
{
	function __construct(string $name, STObjectContainer &$container)
	{
		STObjectContainer::__construct($name, $container);
	}
	protected function create()
	{
	    $session= &STUserSession::instance();
	    $this->setDisplayName("Project Management");
	    $this->accessBy($session->usermanagement_Project_AccessCluster, STLIST);
	    $this->accessBy($session->usermanagement_Project_ChangeCluster, STADMIN);
		//$this->needContainer("projects");
	    
	    $domain= $this->getTable("AccessDomain");
	    $domain->identifColumn("Name", "Domain");
	    //$domain->select("ID", "Domain", "Domain);
	    
	    $user= &$this->needTable("User");
	    $user->setDisplayName("User");
	    $user->accessBy($session->usermanagement_User_AccessCluster, STLIST);
	    $user->accessBy($session->usermanagement_User_ChangeCluster, STADMIN);
	       
	    $groups= &$this->needTable("Group");
	    $groups->setDisplayName("Groups");
	    
		$project= &$this->needTable("Project");
		$project->setDisplayName("existing Projects");
		$this->setFirstTable("Project");
	}
	protected function init(string $action, string $table)
	{
	    $session= &STUserSession::instance();
	    $domain= $session->getCustomDomain();
	    
	    $username= "User";
	    $newpass= "new Password";
	    $reppass= "Password repetition";
	    
	    $user= &$this->needTable("User");
	    $user->select("domain", "Domain");
	    $user->preSelect("domain", $domain['ID']);
	    $user->disabled("domain");
	    $user->select("user", "User");
	    $user->select("FullName", "full qualified name");
	    $user->select("email", "Email");
        $user->preSelect("DateCreation", "systemdate()");
		
		$groups= &$this->needTable("Group");
		$groups->select("domain", "Domain");
		$groups->preSelect("domain", $domain['Name']);
		$groups->disabled("domain");
		$groups->preSelect("DateCreation", "sysdate()");
		$groups->select("Name", "Group");
		
		$project= &$this->needTable("Project");
		$project->select("Name", "Project");
		$project->select("Description", "Description");
		$project->select("ID", "ID");
		$project->select("display", "Display");
		$project->align("ID", "center");
		$project->select("Path", "URL");
		$project->preSelect("DateCreation", "sysdate()");
		$project->orderBy("Name");
		
		if($action==STLIST)
		{
		    $user->select("NrLogin", "logged in");
		    $user->select("LastLogin", "last login");
		    $user->orderBy("domain");
		    $user->orderBy("user");
		    $user->setMaxRowSelect(50);
		    
		    $groups->select("ID", "access descriptions");
		    $groups->listCallback("descriptionCallback", "access descriptions");
		    //$groups->listCallback("actionCallback", "update");
		    $groups->listCallback("actionCallback", "delete");
		    $groups->orderBy("domain");
		    $groups->orderBy("Name");
		    $groups->setMaxRowSelect(50);
		    
			$userClusterGroup= $this->getContainer("UserClusterGroupManagement");
		    $project->namedLink("Project", $userClusterGroup);
		}else
		{
			$user->select("Pwd", "Pwd");
			$user->password("Pwd", true);
		    $user->passwordNames($newpass, $reppass);
		    $user->updateCallback("pwdCallback", $newpass);
		    $user->updateCallback("pwdCallback", $reppass);
		    $user->updateCallback("pwdCallback", $username);
		}
	}
	function installContainer()
	{
		$instance= &STSession::instance();
		
		// create custom domain database entry
		$domain= $instance->getCustomDomain();
		
		$overview= $instance->getDbProjectName("ProjectOverview");
		$profile= $instance->getDbProjectName("UserProfile");
		$usermanagement= $instance->getDbProjectName("UserManagement");
		
		$this->createCluster($instance->allAdminCluster, $overview, "access to all exist CLUSTERs in every project", /*addGroup*/false);
		$this->createCluster($instance->profile_ChangeAccessCluster, $profile, "access to own profile data", /*addGroup*/false);
		$this->createCluster($instance->usermanagement_Project_AccessCluster, $usermanagement, "Permission to see all projects inside UserManagement", /*addGroup*/false);
		$this->createCluster($instance->usermanagement_Project_ChangeCluster, $usermanagement, "Permission to create projects inside  UserManagement", /*addGroup*/false);
		$this->createCluster($instance->usermanagement_User_AccessCluster, $usermanagement, "Permission to see all user inside UserManagement", /*addGroup*/false);
		$this->createCluster($instance->usermanagement_User_ChangeCluster, $usermanagement, "Permission to create/modify user inside UserManagement", /*addGroup*/false);
		$this->createCluster($instance->usermanagement_Group_AccessCluster, $usermanagement, "Ability to see all permission groups", /*addGroup*/false);
		$this->createCluster($instance->usermanagement_Group_ChangeCluster, $usermanagement, "Ability to change groups affiliation", /*addGroup*/false);
		$this->createCluster($instance->usermanagement_Cluster_ChangeCluster, $usermanagement, "Ability to create new clusters for a project", /*addGroup*/false);
		$this->createCluster($instance->usermanagement_Log_AccessCluster, $usermanagement, "Permission to see all logged affiliations", /*addGroup*/false);
		
		 
    	$this->createGroup($instance->allAdminGroup, $domain['Name']);
    	$this->createGroup($instance->onlineGroup, $domain['Name']);
    	$this->createGroup($instance->loggedinGroup, $domain['Name']);
    	$this->createGroup($instance->usermanagementAccessGroup, $domain['Name']);
    	$this->createGroup($instance->usermanagementAdminGroup, $domain['Name']);
		
		$this->joinClusterGroup($instance->allAdminCluster, $instance->allAdminGroup);
		$this->joinClusterGroup($instance->profile_ChangeAccessCluster, $instance->loggedinGroup);
    	$this->joinClusterGroup($instance->usermanagement_Project_AccessCluster, $instance->usermanagementAccessGroup);
    	$this->joinClusterGroup($instance->usermanagement_Project_ChangeCluster, $instance->usermanagementAdminGroup);
		$this->joinClusterGroup($instance->usermanagement_Cluster_ChangeCluster, $instance->usermanagementAdminGroup);
    	$this->joinClusterGroup($instance->usermanagement_User_AccessCluster, $instance->usermanagementAccessGroup);
    	$this->joinClusterGroup($instance->usermanagement_User_ChangeCluster, $instance->usermanagementAdminGroup);
		
		
		// select all needed tabels for an join
		// from table-cluster to table-user
		$this->getTable("Cluster");
		$this->getTable("ClusterGroup");

		$user= $this->getTable("User");
		//$user->clearSelects();
		//$user->clearGetColumns();
		//$user->count();
		$selector= new STDbSelector($user);
		$selector->count("User");
		$selector->joinOver("Group");
		$where= new STDbWhere("ID='".$instance->allAdminCluster."'");
		//$where->andWhere("domain=$defaultDomainKey");
		$where->table("Cluster");
		$selector->where($where);
		$selector->execute();
		if(!$selector->getSingleResult())
		{
			$db= &$instance->getUserDb();
			$creator= new STSiteCreator($db);
			$creator->setMainContainer("um_install");
			//STCheck::debug(false);
			$result= $creator->execute();
			if($result=="NOERROR")
			{
				$desc= &STDbTableDescriptions::instance($this->getDatabase()->getDatabaseName());
				$userName= $desc->getColumnName("User", "user");
				$pwd= $desc->getColumnName("User", "Pwd");
				$container= &$creator->getContainer("um_install");
				$sqlResult= $container->getResult();
				$password= $sqlResult[$pwd];
				$preg= array();
				preg_match("/^password\('(.+)'\)$/", $password, $preg);
				$password= $preg[1];
				$userId= $this->db->getLastInsertID();
				$this->joinUserGroup($userId, $instance->allAdminGroup);
				if(!STUserSession::sessionGenerated())
					$instance->registerSession();
				$instance->acceptUser($sqlResult[$userName], $password);
				$instance->setProperties( $overview );
			}
			$creator->display();
			exit;
		}		
		

/*		$created= $this->createCluster("STUM-UserAccess", "Permission to edit own User-Accounts");
		if($created==="NOERROR")// if Cluster is created, make an join to the LOGGED_IN group.
			$this->joinClusterGroup("STUM-UserAccess", "LOGGED_IN");// otherwise maybe the admin has changed this
		$this->createCluster("STUM-UserListAccess", "Permission to see all user");
		$this->createCluster("STUM-UserListAdmin", "Permission to create, change and delete users");	*/
	}
}

?>