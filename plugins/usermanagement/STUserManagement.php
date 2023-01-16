<?php

//global $_stdbinserter;
require_once($_stdbinserter);
require_once($_stsitecreator);
require_once($_stuserclustergroupmanagement);

function groupListCallback(&$callbackObject, $columnName, $rownum)
{
}

function descriptionCallback(&$callbackObject, $columnName, $rownum)
{//print_r($callbackObject->sqlResult[$rownum]);
    //$callbackObject->echoResult();
    //echo "file:".__file__." line:".__line__."<br />";
    if($callbackObject->getValue() == 1)
    {
        $aResult=	array(	"Name"=>"",
            "Description"=>"Zugriff auf alle Projekte und Untergruppen "	);
        $aResult= array($aResult);// es wird eine Zeile vorget�uscht
    }else
    {
        $clusterTable= $callbackObject->getTable("Cluster");
        $cluster= new STDbSelector($clusterTable);
        $cluster->select("Project", "Name");
        $cluster->select("Cluster", "Description");
        $cluster->where("GroupID=".$callbackObject->sqlResult[$rownum]['access to CLUSTERs'], "ClusterGroup");
        $cluster->execute();
        $aResult= $cluster->getResult();
    }
    $source=   "<table>";
    foreach($aResult as $row)
    {
        $source.=  "	<tr>";
        $source.=  "		<td>";
        $source.=  "			<b>";
        $source.=  "				[".$row["Name"]."]";
        $source.=  "			</b>";
        $source.=  "		</td>";
        $source.=  "		<td>";
        $source.=  "				".$row["Description"];
        $source.=  "		</td>";
        $source.=  "	</tr>";
    }
    $source.=  "</table>";
    $callbackObject->setValue($source);
    
    if( $callbackObject->sqlResult[$rownum]["Group"] == "ONLINE" ||
        $callbackObject->sqlResult[$rownum]["Group"] == "LOGGED_IN" )
    {
        $callbackObject->noUnlinkData("delete");
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
	    $this->setDisplayName("ProjectManagement");
	    $this->accessBy("STUM-UserAccess");
		//$this->needContainer("projects");
	    
	    $domain= $this->getTable("AccessDomain");
	    $domain->identifColumn("Name", "Domain");
	    //$domain->select("ID", "Domain");
	    
	    $user= &$this->needTable("User");
	    $user->setDisplayName("User");
	    // *WARNING* column domain was GroupType
	    $user->select("domain", "Domain");
	    $user->preSelect("domain", "custom");
	    $user->disabled("domain");
	    $user->select("user", "User");
	    $user->select("FullName", "full qualified name");
	    $user->select("email", "Email");
	    //$user->select("Description");
	    $user->orderBy("domain");
	    $user->orderBy("user");
	    $user->setMaxRowSelect(50);
	       
	    $groups= &$this->needTable("Group");
	    $groups->setDisplayName("Groups");
	    
		$project= &$this->needTable("Project");
		$project->setDisplayName("existing Projects");
		$this->setFirstTable("Project");
	}
	function init()
	{
	    $session= &STUserSession::instance();
	    
	    $action= $this->getAction();
		$user= &$this->needTable("User");
		
		$groups= &$this->needTable("Group");
		$groups->select("domain", "Domain");
		$groups->preSelect("domain", $session->mainDOMAIN);
		$groups->disabled("domain");
		$groups->select("Name", "Group");
		
		$project= &$this->needTable("Project");
		$project->select("Name", "Project");
		$project->select("Description");
		$project->select("Path", "Position");
		$project->orderBy("Name");
		
		if($action==STLIST)
		{
		    STCheck::echoDebug("container", "new linked object defined to ".get_class($this)."(<b>$this->name</b>)");
		    $user->select("NrLogin", "logged in");
		    $user->select("LastLogin", "last login");
		    
		    $groups->select("ID", "access to CLUSTERs", "descriptionCallback");
		    $groups->orderBy("domain");
		    $groups->orderBy("Name");
		    $groups->setMaxRowSelect(50);
		    
		    $project->namedLink("Project", $this->userClusterGroup);
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
		$selector= new STDbSelector($partition);
		$selector->execute();
		$res= $selector->getSingleResult();

    	$projectName= $instance->userManagementProjectName;
    	$project= $this->getTable("Project");
    	$project->clearSelects();
    	$project->clearIdentifColumns();
    	$project->clearGetColumns();
    	$project->select("ID");
		$project->where("Name='".$projectName."'");
    	$selector= new STDbSelector($project);
    	$selector->execute();
    	$userManagementID= $selector->getSingleResult();
    	if($userManagementID)
    		$instance->projectID= $userManagementID;
		else
			$instance->projectID= 1;
			
			echo __FILE__.__LINE__."<br>";
			echo "exist partitions:$res<br>";
			st_print_r($userManagementID);
		if($res<1)
		{
			if(!isset($userManagementID))
			{
			    echo __FILE__.__LINE__."<br>";
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
    			$project->accessCluster("has_access", "Name", "Permission to see the project @");
    			$project->insertCluster("can_insert", "Name", "Permission to create a new project");
    			$project->updateCluster("can_update", "Name", "Changing-Permission at project @");
    			$project->deleteCluster("can_delete", "Name", "Deleting-Permission at project @");
    			echo __FILE__.__LINE__."<br>";
    			$inserter= new STDbInserter($project);
    			$inserter->fillColumn("Name", $projectName);
    			$inserter->fillColumn("Path", $HTTP_SERVER_VARS["SCRIPT_NAME"]);
    			$inserter->fillColumn("description", "Listing and changing of all access permissions at project UserManagement");
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
		$this->createCluster("STUM-Access", "Permission to see all projects inside UserManagement");
		$this->createCluster("STUM-Insert", "Permission to create projects inside  UserManagement");
		$this->createCluster("STUM-Update", "Permission to edit projects inside  UserManagement");
		$this->createCluster("STUM-Delete", "Permission to delete projects inside  UserManagement");			


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
			$this->createCluster($instance->allAdminCluster, "access to all exist CLUSTERs in every project");

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

		$this->createGroup($instance->onlineGroup, "user has access also if they not logged-in");
		$this->createGroup($instance->loggedinGroup, "user has access to this group if they be logged-in");

		$created= $this->createCluster("STUM-UserAccess", "Berechtigung zum editieren des eigenen User-Accounts");
		if($created==="NOERROR")// if Cluster is created, make an join to the LOGGED_IN group.
			$this->joinClusterGroup("STUM-UserAccess", "LOGGED_IN");// otherwise maybe the admin has changed this
		$this->createCluster("STUM-UserListAccess", "Berechtigung zum ansehen aller Benutzer");
		$this->createCluster("STUM-UserListAdmin", "Berechtigung zum �ndern, l�schen und erstellen der Benutzer");
	}
}

?>