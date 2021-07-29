<?php

class STPartitionManagement extends STObjectContainer
{
	function __construct($name, &$container)
	{
		Tag::paramCheck($name, 1, "string");
		Tag::paramCheck($container, 2, "STObjectContainer");
		
		STObjectContainer::STObjectContainer($name, $container);
		
		//     setInTableColumn($tableName, $column, $type, $null= true, $pk= false, $fkToTable= null, $unikIndex= 0)
		
		
		
	}
	function create()
	{
		$this->setDisplayName("Unterteilung nach Partitionen");
		
		$cluster= &$this->needTable("Cluster");
		$cluster->setDisplayName("Zugriffs-Cluster");
		$cluster->select("ID", "Cluster");
		$cluster->select("identification", "Partition");
		$cluster->select("Description", "Beschreibung");
		$cluster->foreignKey("identification", "Partition");
		$cluster->where("ID not like '%\_%'");
		$cluster->preSelect("DateCreation", "sysdate()");
		
		$groupgroup= &$this->getContainer("groupgroup");
		$group= &$this->needTable("Group");
		$group->setDisplayName("Zugriffs-Gruppen");
		$group->select("Name", "Gruppen-Name");
		$group->select("Description", "Bezeichnung");
		$group->select("ID", "Gruppen-Zuweisung");
		$group->link("ID", $groupgroup);
		$group->where("Name not like '%\_%'");
		$group->preSelect("DateCreation", "sysdate()");
		$group->listCallback("st_usermanagement_groupJoins", "Gruppen-Zuweisung");
				
		$partition= &$this->needTable("Partition");
		$partition->setDisplayName("Zugriffs-Einteilung nach Partitionen");
		$partition->identifColumn("Name", "Partition");
		$partition->select("Name", "Zugriff f�r");
		$partition->namedPkLink("Name", "usergroup");
		$partition->doInsert(false);
		$partition->doUpdate(false);
		//$partition->doDelete(false);
		$partition->needPkInResult("has_access");
		$partition->accessCluster("has_access", "ID", "Zugriff zu den \"@\" Berechtigungen");
		//$partition->tdAttribute("align", "center");
		
		$this->setFirstTable("Partition");
	}
	function init()
	{
		$cluster= &$this->needTable("Cluster");
		$group= &$this->needTable("Group");
		
		$action= $this->getAction();
		if($Action==STLIST)
		{
			$cluster->select("DateCreation", "erzeugt seit");
		
			$group->select("DateCreation", "erzeugt seit");
		}
	}
}

function st_usermanagement_groupJoins(&$oCallback)
{
	//$oCallback->echoResult();
	$container= &STBaseContainer::getContainer("groupgroup");
	$id= $oCallback->getValue("Gruppen-Zuweisung");
	$clusterGroup= &$container->getTable("ClusterGroup");
	$clusterGroup->clearSelects();
	$clusterGroup->clearGetColumns();
	$clusterGroup->count();
	$clusterGroup->where("GroupID=".$id);
	$selector= new OSTDbSelector($clusterGroup);
	$selector->execute();
	$exist= $selector->getSingleResult();
	//echo "gruppe ".$oCallback->getValue($group)." has container ".$exist."<br />";
	if($exist)
		$oCallback->setValue(null, "Gruppen-Zuweisung");
}

?>