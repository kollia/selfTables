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
    var $userClusterGroup= null;
    
	function __construct($name, &$container, $bInstall= false)
	{
		STCheck::param($name, 0, "string");
		STCheck::param($container, 1, "STObjectContainer");	
		
		STObjectContainer::__construct($name, $container);
		$this->userClusterGroup= new STUserClusterGroupManagement("UserClusterGroupManagement", $this->getDatabase());
	}
	function create()
	{
	    $session= &STUserSession::instance();
	    $this->setDisplayName("Project Management");
	    $this->db->accessBy($session->usermanagement_AccessCluster, STLIST);
	    $this->db->accessBy($session->usermanagement_ChangeCluster, STADMIN);
		//$this->needContainer("projects");
	    
	    $domain= $this->getTable("AccessDomain");
	    $domain->identifColumn("Name", "Domain");
	    //$domain->select("ID", "Domain", "Domain);
	    
	    $user= &$this->needTable("User");
	    $user->setDisplayName("User");
	    $user->accessBy($session->usermanagement_User_Access, STLIST);
	    $user->accessBy($session->usermanagement_User_Change, STADMIN);
	       
	    $groups= &$this->needTable("Group");
	    $groups->setDisplayName("Groups");
	    
		$project= &$this->needTable("Project");
		$project->setDisplayName("existing Projects");
		$this->setFirstTable("Project");
	}
	function init()
	{
	    $action= $this->getAction();
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
		$project->select("ID", "ID");
		$project->select("Name", "Project");
		$project->select("Description", "Description");
		$project->select("Path", "URL");
		$project->preSelect("DateCreation", "systemdate()");
		$project->orderBy("Name");
		//$project->align("ID", "right");
		
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
		    
		    $project->namedLink("Project", $this->userClusterGroup);
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
		global $HTTP_SERVER_VARS;
		
		$instance= &STSession::instance();
		
		// create custom domain database entry
		$domain= $instance->getCustomDomain();
		
    	$projectName= $instance->userManagementProjectName;
    	$project= $this->getTable("Project");
    	$project->clearSelects();
    	$project->clearIdentifColumns();
    	$project->clearGetColumns();
    	$project->select("ID", "ID");
		$project->where("Name='".$projectName."'");
    	$selector= new STDbSelector($project);
    	$selector->execute();
    	$userManagementID= $selector->getSingleResult();
    	if($userManagementID)
    		$instance->projectID= $userManagementID;
		else
			$instance->projectID= 1;

		if(!isset($userManagementID))
		{
		    $desc= STDbTableDescriptions::instance($this->getDatabase()->getDatabaseName());
			// fill project-cluster per hand
			// because no project is inserted
			// and the system do not found what we want
			$instance->projectCluster= array(	$desc->getColumnName("Project", "has_access")=>"STUM-Access_".$projectName,
												$desc->getColumnName("Project", "can_insert")=>"STUM-Insert_".$projectName,
												$desc->getColumnName("Project", "can_update")=>"STUM-Update_".$projectName,
												$desc->getColumnName("Project", "can_delete")=>"STUM-Delete_".$projectName	);
			$project->identifColumn("Name");
    		$project->accessBy("STUM-Access", STLIST);
    		$project->accessBy("STUM-Insert", STINSERT);
    		$project->accessBy("STUM-Update", STUPDATE);
    		$project->accessBy("STUM-Delete", STDELETE);
			$inserter= new STDbInserter($project);
			$inserter->fillColumn("Name", $projectName);
			$inserter->fillColumn("Path", $HTTP_SERVER_VARS["SCRIPT_NAME"]);
			$inserter->fillColumn("Description", "Listing and changing of all access permissions at project UserManagement");
			$inserter->fillColumn("DateCreation", "sysdate()");
			$inserter->execute();

			$userManagementID= $inserter->getLastInsertID();
			if($userManagementID!==1)
			{
				$instance->projectID= $userManagementID;

				$partition= $this->getTable("Partition");
				$updater= new STDbUpdater($partition);
				$updater->update("ProjectID", $userManagementID);
				$updater->execute();

				$cluster= $this->getTable("Cluster");
				$updater= new STDbUpdater($cluster);
				$updater->update("ProjectID", $userManagementID);
				$where= new STDbWhere("ID like 'STUM-Access%'");
				$where->orWhere("ID like 'STUM-Insert%'");
				$where->orWhere("ID like 'STUM-Update%'");
				$where->orWhere("ID like 'STUM-Delete%'");
				$updater->where($where);
				$updater->execute();
			}
		}
		$this->createCluster($instance->usermanagementAccessCluster, "Permission to see all projects inside UserManagement", /*addGroup*/false);
		$this->createCluster($instance->usermanagementChangeCluster, "Permission to create projects inside  UserManagement", /*addGroup*/false);			
		 
    	$this->createGroup($instance->onlineGroup, $domain['Name']);
    	$this->createGroup($instance->loggedinGroup, $domain['Name']);
    	$this->createGroup($instance->usermanagementAccessGroup, $domain['Name']);
    	$this->createGroup($instance->usermanagementAdminGroup, $domain['Name']);
    	$this->createGroup($instance->allAdminGroup, $domain['Name']);
		
    	$this->joinClusterGroup($instance->usermanagementAccessCluster, $instance->usermanagementAccessGroup);
    	$this->joinClusterGroup($instance->usermanagementChangeCluster, $instance->usermanagementAdminGroup);
		
		
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
		    $this->createCluster($instance->allAdminCluster, "access to all exist CLUSTERs in every project");
		    $this->joinClusterGroup($instance->allAdminCluster, $instance->allAdminGroup);

			$db= &$instance->getUserDb();
			$creator= new STSiteCreator($db);
			$creator->setMainContainer("um_install");
			$container= &$creator->getContainer("um_install");
			//STCheck::debug(false);
			$result= $creator->execute();
			if($result=="NOERROR")
			{
				$desc= &STDbTableDescriptions::instance($this->getDatabase()->getDatabaseName());
				$userName= $desc->getColumnName("User", "user");
				$pwd= $desc->getColumnName("User", "Pwd");
				$sqlResult= $container->getResult();
				$password= $sqlResult[$pwd];
				$preg= array();
				preg_match("/^password\('(.+)'\)$/", $password, $preg);
				$password= $preg[1];
				$userId= $this->db->getLastInsertID();
				$this->joinUserGroup($userId, $instance->allAdminGroup);
				$instance->registerSession();
				$instance->acceptUser($sqlResult[$userName], $password);
				$instance->setProperties( $userManagementID );
			}
			$creator->display();
			exit;
		}


		$created= $this->createCluster("STUM-UserAccess", "Permission to edit own User-Accounts");
		if($created==="NOERROR")// if Cluster is created, make an join to the LOGGED_IN group.
			$this->joinClusterGroup("STUM-UserAccess", "LOGGED_IN");// otherwise maybe the admin has changed this
		$this->createCluster("STUM-UserListAccess", "Permission to see all user");
		$this->createCluster("STUM-UserListAdmin", "Permission to create, change and delete users");
	}
}

?>