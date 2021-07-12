<?php

class STGroupGroupManagement extends STObjectContainer
{
	function create()
	{
		$session= &STUserSession::instance();		
		$group= &$this->getTable("Group");
		$group->identifColumn("Name", "Gruppe");
		$group->identifColumn("Description", "Bezeichnung");
		$group->getColumn("ID");
		$group->where("Name!=".$session->onlineGroup);
		$group->andWhere("Name!=".$session->loggedinGroup);
		$group->orderBy("Name");
		
		$groupgroup= &$this->needTable("GroupGroup");
		$groupgroup->setDisplayName("Gruppen-Zuweisung zu den Gruppen");
		$groupgroup->select("Group1ID");
		$groupgroup->select("Group2ID");
		$groupgroup->nnTable("Verbindung");
		$groupgroup->getColumn("Group1ID");
		$groupgroup->select("DateCreation", "Zugeh�rigkeit seit");
		$groupgroup->preSelect("Group1ID", $stget["STGroup"]["ID"]);
		$groupgroup->preSelect("DateCreation", "sysdate()");
		$groupgroup->listCallback("st_usermanagement_groupGroup_selector", "DateCreation");
		$groupgroup->setMaxRowSelect(20);
		$groupgroup->noInsert();
		$groupgroup->noUpdate();
		$groupgroup->noDelete();
		
		$clusterGroup= &$this->getTable("ClusterGroup");
		$clusterGroup->clearGetColumns();
		$clusterGroup->clearSelects();
		$clusterGroup->count();
	}
	function init()
	{
		$query= new STQueryString();
		$stget= $query->getArrayVars("stget");
		
		$group= &$this->getTable("Group");
		$tableName= $group->getName();
		$idName= $group->getColumnName("ID");
		$group->andWhere("ID!=".$stget[$tableName][$idName]);
	}
}

$global_stusermanagement_undergroups= array();

function st_usermanagement_groupGroup_selector(&$oCallback)
{
	global	$global_stusermanagement_undergroups;
	
	$query= new STQueryString();
	$session= &STUserSession::instance();
	$group= $oCallback->getValue("Gruppe");
	$groupID= $oCallback->getValue("ID");
	$groupTable= &$oCallback->container->getTable("Group");
	$fromGroup= $query->getArrayVars("stget[".$groupTable->getName()."][".$groupTable->getColumnName("ID")."]");
	if(	$group===$session->onlineGroup
		or
		$group===$session->loggedinGroup
		or
		$groupID===$fromGroup				)
	{
		$oCallback->skipRow();
		return;
	}
	if(!count($global_stusermanagement_undergroups))
	{
    	$groupGroupTable= $oCallback->container->getTable("GroupGroup");
    	$groupGroupTable->clearSelects();
    	$groupGroupTable->clearGetColumns();
    	$groupGroupTable->clearFks();
		$groupGroupTable->noNnTable();
    	$groupGroupTable->select("Group2ID");
    	$groupGroupTable->where("Group1ID=".$fromGroup);
    	$selector= new OSTDbSelector($groupGroupTable);
    	$selector->execute();
    	$global_stusermanagement_undergroups= $selector->getSingleArrayResult();
		if(!count($global_stusermanagement_undergroups))
			$global_stusermanagement_undergroups= array(0);
	}
	if(in_array($groupID, $global_stusermanagement_undergroups)) 
	{
		$oCallback->skipRow();
		return;
	}
	//$oCallback->echoResult();
	return;
	$groupID= $oCallback->getValue("STGroup@ID");
	$clusterGroup= $oCallback->container->getTable("ClusterGroup");
	$clusterGroup->where("GroupID=".$groupID);
	$selector= new OSTDbSelector($clusterGroup);
	$selector->modifyForeignKey(false);
	$selector->execute();
	$exists= $selector->getSingleResult();
	if($exists)
		$oCallback->skipRow();
}

?>