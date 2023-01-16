<?php

require_once( $_stobjectcontainer );
require_once( $_stquerystring );
require_once( $_stdbselector );
require_once( $_stclustergroupmanagement );

class STUserClusterGroupManagement extends STObjectContainer
{
    private $clusterGroup;
    
	function __construct($name, &$container)
	{
		Tag::paramCheck($name, 1, "string");
		Tag::paramCheck($container, 2, "STObjectContainer");
		
		STObjectContainer::__construct($name, $container);
		$this->clusterGroup= new STClusterGroupManagement("ClusterGroupAssignment", $this->getDatabase());	
	}
	function create()
	{
	    $this->setDisplayName("Cluster-User Management");
	    
	    $cluster= $this->needTable("Cluster");
	    $cluster->setDisplayName("access CLUSTER");
	    $userGroup= $this->needTable("UserGroup");
	    //$userGroup->setDisplayName("");
	    //$clusterGroup= $this->needTable("ClusterGroup");
	    //$clusterGroup->setDisplayName("");
	    $this->setFirstTable("Cluster");
	    return;
	    
		
		$group= &$this->getTable("Group");
		$group->getColumn("ID");
		$group->select("Description", "Gruppen-Bezeichnung");
		$group->orderBy("Name");
		
		/*$cluster= &$this->needTable("Cluster");
		$cluster->select("ID", "Cluster");
		$cluster->select("Description", "Beschreibung");
		
		$group= &$this->needTable("Group");
		$group->setDisplayName("Zugriffs-Gruppen");
		$group->select("Name", "Gruppen-Name");
		$group->select("Description", "Bezeichnung");
		
		$partition= &$this->needTable("Partition");
		$partition->setDisplayName("Gruppenzuordung zu den Cluster");
		$partition->select("Name", "Zugriff f�r");
		$partition->doInsert(false);
		$partition->doUpdate(false);
		$partition->doDelete(false);
		$partition->needPkInResult("has_access");
		$partition->accessCluster("has_access", "ID", "Zugriff zu den \"@\" Berechtigungen");
		
		$this->setFirstTable("Partition");*/
	}
	function init()
	{
	    $action= $this->getAction();
	    $prj= $this->getTable("Project");
	    
	    $query= new STQueryString();	    
	    $projectName= "xxx";
	    $limitation= $query->getLimitation($prj->getName());
	    if(isset($limitation))
	    {
	        $projectName= $limitation['Name'];
	        $prjIDTable= new STDbSelector($prj);
	        $prjIDTable->select("Project", "ID");
	        $prjIDTable->where("Name='$projectName'");
	        $prjIDTable->execute();
	        $projectID= $prjIDTable->getSingleResult();
	        echo __FILE__.__LINE__."<br>";
	        echo "project:$projectName ID:$projectID<br>";
	    }
	    
	    
	    $group= $this->getTable("Group");
	    $group->identifColumn("Name", "Group");
	    
	    $userGroup= $this->needTable("UserGroup");
	    $userGroup->setMaxRowSelect(100);
	  
		$cluster= $this->getTable("Cluster");
		$cluster->select("ID", "Cluster");
		$cluster->namedLink("Cluster", $this->clusterGroup);
		$cluster->select("Description");
		if($action == STLIST)
		{
		    $where= new STDbWhere();
		    $where->table("Project");
		    $where->where("Name='$projectName'");
		    $cluster->where($where);
		}
	}
}
?>