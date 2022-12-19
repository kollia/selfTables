<?php

global $_stdbinserter;
require_once($_stdbinserter);
require_once($_stsitecreator);

class STUserManagement extends STObjectContainer
{
	function __construct($name, &$container, $bInstall= false)
	{
		Tag::paramCheck($name, 1, "string");
		Tag::paramCheck($container, 2, "STObjectContainer");	
		
		STObjectContainer::__construct($name, $container);
	}
	function create()
	{
	    $this->setDisplayName("UserManagement");
	    $this->accessBy("STUM-UserAccess");
		//$this->needContainer("projects");
	    
	    $domain= $this->getTable("GroupType");
	    $domain->identifColumn("ID", "Domain");
	    //$domain->select("ID", "Domain");
	    
	    $user= &$this->needTable("User");
	    $user->setDisplayName("User");
	    $user->select("GroupType", "Domain");
	    $user->preSelect("GroupType", "custom");
	    $user->disabled("GroupType");
	    $user->select("UserName", "User");
	    $user->select("FullName", "full qualified name");
	    $user->select("EmailAddress", "Email");
	    $user->select("Description");
	    $user->orderBy("GroupType");
	    $user->orderBy("UserName");
	    $user->setMaxRowSelect(50);
	       
	    $groups= &$this->needTable("Group");
	    $groups->setDisplayName("Groups");
	    $groups->select("Name", "Group");
	    $groups->setMaxRowSelect(100);
	    
		$project= &$this->needTable("Project");
		$project->setDisplayName("existing Projects");
		$project->select("Name", "Project");
		$project->select("Description");
		$project->select("Path", "Position");
		$project->orderBy("Name");
		$this->setFirstTable("Project");
	}
	function init()
	{
	    $action= $this->getAction();
		$user= &$this->needTable("User");
		$groups= &$this->needTable("Group");
		$project= &$this->needTable("Project");
		if($action==STLIST)
		{
		    $user->select("NrLogin", "logged in");
		    $user->select("LastLogin", "last login");
		    
		    $groups->select("ID", "Description");
		}else
		{
			$user->select("Pwd");
			$user->password("Pwd", true);
		    $user->passwordNames("new Passwort", "Password repetition");
		}
	}
	function installContainer()
	{
		global $HTTP_SERVER_VARS;

		$instance= &STSession::instance();
    
		$partition= $this->getTable("Partition");
		$partition->clearSelects();
		$partition->clearGetColumns();
		$partition->count();
		$selector= new OSTDbSelector($partition);
		$selector->execute();
		$res= $selector->getSingleResult();

    	$projectName= $instance->userManagementProjectName;
    	$project= $this->getTable("Project");
    	$project->clearSelects();
    	$project->clearIdentifColumns();
    	$project->clearGetColumns();
    	$project->select("ID");
		$project->where("Name='".$projectName."'");
    	$selector= new OSTDbSelector($project);
    	$selector->execute();
    	$userManagementID= $selector->getSingleResult();
    	if($userManagementID)
    		$instance->projectID= $userManagementID;
		else
			$instance->projectID= 1;

		if($res<1)
		{
			if(!$userManagementID)
			{
			    $desc= STDbTableDescriptions::instance($this->database->getDatabaseName());
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
    			$project->accessCluster("has_access", "Name", "Berechtigung zum ansehen des Projektes @");
    			$project->insertCluster("can_insert", "Name", "Berechtigung zum anlegen eines neuen Projektes");
    			$project->updateCluster("can_update", "Name", "�nderungs-Berechtigung im Projekt @");
    			$project->deleteCluster("can_delete", "Name", "L�sch-Berechtigung im Projekt @");
    			$inserter= new STDbInserter($project);
    			$inserter->fillColumn("Name", $projectName);
    			$inserter->fillColumn("Path", $HTTP_SERVER_VARS["SCRIPT_NAME"]);
    			$inserter->fillColumn("description", "Auflistung und �nderung aller Zugriffe im UserManagement");
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
		}
    		$this->createCluster("STUM-Access", "Berechtigung zum Ansehen aller Projekte im UserManagement");
    		$this->createCluster("STUM-Insert", "Berechtigung zum erstellen neuer Projekte im UserManagement");
    		$this->createCluster("STUM-Update", "Berechtigung zum editieren aller Projekte im UserManagement");
    		$this->createCluster("STUM-Delete", "Berechtigung zum l�schen aller Projekte im UserManagement");


		// select all needed tabels for an join
		// from table-cluster to table-user
		$this->getTable("Cluster");
		$this->getTable("ClusterGroup");

		$user= $this->getTable("User");
		$user->clearSelects();
		$user->clearGetColumns();
		$user->count();
		$selector= new OSTDbSelector($user);
		$where= new STDbWhere("ID='".$instance->allAdminCluster."'");
		$where->forTable("Cluster");
		$selector->where($where);
		$statement= $selector->getStatement();
		$selector->execute();
		if(!$selector->getSingleResult())
		{
			$this->createCluster($instance->allAdminCluster, "Zugriff auf alle CLUSTER in jedem Projekt");

			$db= &$instance->getUserDb();
			$creator= new STSiteCreator($db);
			$creator->setMainContainer("um_install");
			$container= &$creator->getContainer("um_install");
			STCheck::debug(false);
			$result= $creator->execute();
			if($result=="NOERROR")
			{
				$desc= &STDbTableDescriptions::instance($this->database->getDatabaseName());
				$userName= $desc->getColumnName("User", "UserName");
				$pwd= $desc->getColumnName("User", "Pwd");
				$sqlResult= $container->getResult();
				$password= $sqlResult[$pwd];
				preg_match("/^password\('(.+)'\)$/", $password, $preg);
				$password= $preg[1];
				$userId= $this->db->getLastInsertID();
				$this->joinUserGroup($userId, $instance->allAdminCluster);
				$instance->registerSession();
				$instance->acceptUser($sqlResult[$userName], $password);
				$instance->setProperties( $userManagementID );
			}
			$creator->display();
			exit;
		}

		$this->createGroup($instance->onlineGroup, "user haben Zugriff auch wenn sie nicht eingeloggt sind");
		$this->createGroup($instance->loggedinGroup, "user hat Zugriff auf diese Gruppe wenn er sich eingeloggt hat");

		$created= $this->createCluster("STUM-UserAccess", "Berechtigung zum editieren des eigenen User-Accounts");
		if($created==="NOERROR")// if Cluster is created, make an join to the LOGGED_IN group.
			$this->joinClusterGroup("STUM-UserAccess", "LOGGED_IN");// otherwise maybe the admin has changed this
		$this->createCluster("STUM-UserListAccess", "Berechtigung zum ansehen aller Benutzer");
		$this->createCluster("STUM-UserListAdmin", "Berechtigung zum �ndern, l�schen und erstellen der Benutzer");
	}
}

?>