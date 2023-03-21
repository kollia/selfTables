<?php

require_once( $_stobjectcontainer );

class STClusterGroupAssignment extends STObjectContainer
{
	function __construct($name, &$container)
	{
		Tag::paramCheck($name, 1, "string");
		Tag::paramCheck($container, 2, "STObjectContainer");
		
		STObjectContainer::__construct($name, $container);
		
	}
	function create()
	{
	    $table= $this->needNnTable("Group", "ClusterGroup", "Cluster");
	}
	function init()
	{
	    $cluster= $this->getTable("Cluster");
	    $selector= new STDbSelector($cluster);
	    $selector->select("Cluster", "ID");
	    $selector->select("Cluster", "Description");
	    $selector->execute();
	    $res= $selector->getRowResult();   
	    if(!empty($res))
	    {
    	    $h2= new H4Tag();
    	       $div= new DivTag("Description");
        	       $div->add($res['Description']);
        	       $div->align("center");	           
    	       $h2->add($div);
    	    $this->addObjBehindProjectIdentif($h2);
	    }
	    
	    $buttonText= "Group assignment to Cluster ";
	    if(isset($res['ID']))
	        $buttonText.= $res['ID'];
        $nnTable= $this->needTable("Group");
	    $nnTable->setDisplayName($buttonText);
	    $nnTable->select("Group", "domain", "domain");
	    $nnTable->nnTableCheckboxColumn("Affilation");
	    $nnTable->select("Group", "Name", "Group");
	    $nnTable->select("ClusterGroup", "DateCreation", "membership since");
	    $nnTable->preSelect("DateCreation", "sysdate()");
	    $nnTable->setMaxRowSelect(40);
	    $nnTable->orderBy("Group", "domain");
	    $nnTable->orderBy("Group", "Name");
	    
	    $domain= $nnTable->getTable("AccessDomain");
	    $domain->identifColumn("Name", "domain");
	    
	}
}
?>