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
	    $this->needNnTable("Group", "ClusterGroup", "Cluster");
	}
	function init()
	{
	    $cluster= $this->getTable("Cluster");
	    $selector= new STDbSelector($cluster);
	    $selector->select("Cluster", "ID");
	    $selector->select("Cluster", "Description");
	    $selector->execute();
	    $res= $selector->getRowResult();
	    $h2= new H4Tag();
	       $div= new DivTag("Description");
    	       $div->add($res['Description']);
    	       $div->align("center");	           
	       $h2->add($div);
	    $this->addObjBehindProjectIdentif($h2);
	    
	    $where= new STDbWhere();
	    $where->table("ClusterGroup");
	    $where->where("ClusterID='".$res['ID']."'");
	    $where->orWhere("ClusterId is null");
	    
	    $nnTable= $this->needTable("Group");
	    $nnTable->setDisplayName("Group assignment to Cluster ".$res['ID']);
	    $nnTable->select("Group", "domain", "domain");
	    $nnTable->nnTableCheckboxColumn("Affilation");
	    $nnTable->select("Group", "Name", "Group");
	    $nnTable->select("ClusterGroup", "DateCreation", "membership since");
	    $nnTable->preSelect("DateCreation", "sysdate()");// "ClusterGroup", 
	    //$nnTable->where($where);
	    $nnTable->setMaxRowSelect(40);
	    //$nnTable->orderBy("ClusterGroup", "Affilation", /*ASC*/false);
	    $nnTable->orderBy("Group", "domain");
	    $nnTable->orderBy("Group", "Name");
	    
	    $domain= $nnTable->getTable("AccessDomain");
	    $domain->identifColumn("Name", "domain");
	    
	}
}
?>