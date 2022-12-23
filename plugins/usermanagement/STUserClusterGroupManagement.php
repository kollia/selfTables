<?php

require_once( $_stobjectcontainer );
require_once( $_stquerystring );
require_once( $_stdbselector );

class STUserClusterGroupManagement extends STObjectContainer
{
	function __construct($name, &$container)
	{
		Tag::paramCheck($name, 1, "string");
		Tag::paramCheck($container, 2, "STObjectContainer");
		
		STObjectContainer::__construct($name, $container);
		
	}
	function create()
	{
	    STCheck::echoDebug("container", "run creation routine for container ".get_class($this)."(<b>$this->name</b>)");
	    $cluster= $this->needTable("Cluster");
	    $cluster->setDisplayName("access CLUSTER");
	    $userGroup= $this->needTable("UserGroup");
	    //$userGroup->setDisplayName("");
	    $clusterGroup= $this->needTable("ClusterGroup");
	    //$clusterGroup->setDisplayName("");
	    $this->setFirstTable("ClusterGroup");
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
	    
	    $prj= $this->getTable("Project");
	    
	    $query= new STQueryString();	    
	    $limitation= $query->getLimitation($prj->getName());
	    if(isset($limitation))
	    {
	        $projectName= $limitation['Name'];
	    }
	    
	    /*toDo: check selection join incorrect
	    $project= new STDbSelector($prj);
	    $project->select("Cluster", "ID");
	    $project->where("Name=$projectName", "Project");
	    echo __FILE__.__LINE__."<br>";
	    echo $project->getStatement();
	    $project->execute();
	    $clusterIDs= $project->getSingleRowArray();
	    st_print_r($clusterIDs);*/
	    
	    
	    $group= $this->getTable("Group");
	    $group->identifColumn("Name", "Group");
	    
	    $userGroup= $this->needTable("UserGroup");
	    $userGroup->setMaxRowSelect(100);
	    
	    $clustergroup= &$this->needTable("ClusterGroup");
	    $clustergroup->setDisplayName("Gruppen-Zuweisung zum gewählten Cluster");
	    $clustergroup->nnTable("access");
	    $clustergroup->select("GroupID");
	    $clustergroup->select("DateCreation", "zugehörigkeit seit");
	    $clustergroup->preSelect("DateCreation", "sysdate()");
	    $clustergroup->distinct();
	    $clustergroup->changeFormOptions("Speichern");
	    $clustergroup->noInsert();
	    $clustergroup->noUpdate();
	    $clustergroup->noDelete();
	    $clustergroup->setMaxRowSelect(20);
	    
		$cluster= $this->getTable("Cluster");
		$cluster->select("ID");
		$cluster->select("Description");
		$selector= new STDbSelector($cluster);
		$selector->execute();
		$res= $selector->getRowResult();
		$div= new DivTag();
			$h2= new H3Tag("Description");
				$h2->add("Zugehörigkeit der Gruppen zum Cluster ");
				$span= new SpanTag("hightlighted");
					$span->add($res['ID']);
				$h2->addObj($span);
			$div->addObj($h2);
			$div->add($res['Description']);
			$div->align("center");
		$this->addObjBehindProjectIdentif($div);
	}
}
?>