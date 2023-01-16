<?php

require_once( $_stobjectcontainer );

class STClusterGroupManagement extends STObjectContainer
{
	function __construct($name, &$container)
	{
		Tag::paramCheck($name, 1, "string");
		Tag::paramCheck($container, 2, "STObjectContainer");
		
		STObjectContainer::__construct($name, $container);
		
	}
	function create()
	{
		$clustergroup= &$this->needTable("ClusterGroup");
	}
	function init()
	{
	    $cluster= $this->getTable("Cluster");
	    $cluster->select("ID");
	    $cluster->select("Description");
	    $selector= new STDbSelector($cluster);
	    $selector->execute();
	    $res= $selector->getRowResult();
	    $h2= new H2Tag();
	       $div= new DivTag("Description");
    	       $div->add($res['Description']);
    	       $div->align("center");	           
	       $h2->add($div);
	    $this->addObjBehindProjectIdentif($h2);
	    
	    $clustergroup= &$this->needTable("ClusterGroup");
	    $clustergroup->setDisplayName("Group assignment to Cluster ".$res['ID']);
	    $clustergroup->nnTable("Zugriff");
	    $clustergroup->select("GroupID");
	    $clustergroup->select("DateCreation", "zugehörigkeit seit");
	    $clustergroup->preSelect("DateCreation", "sysdate()");
	    $clustergroup->distinct();
	    $clustergroup->changeFormOptions("Speichern");
	    $clustergroup->noInsert();
	    $clustergroup->noUpdate();
	    $clustergroup->noDelete();
	    $clustergroup->setMaxRowSelect(20);
	    
	    $group= &$this->getTable("Group");
	    $group->getColumn("ID");
	    $group->identifColumn("Name", "Group");
	    $group->select("Description", "Gruppen-Bezeichnung");
	    $group->orderBy("Name");
	    
	    
		
	}
}
?>