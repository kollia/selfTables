<?php

class STClusterGroupManagement extends STObjectContainer
{
	function STClusterGroupManagement($name, &$container)
	{
		Tag::paramCheck($name, 1, "string");
		Tag::paramCheck($container, 2, "STObjectContainer");
		
		STObjectContainer::STObjectContainer($name, $container);
		
	}
	function create()
	{
		$clustergroup= &$this->needTable("ClusterGroup");
		$clustergroup->setDisplayName("Gruppen-Zuweisung zum gew�hlten Cluster");
		$clustergroup->nnTable("Zugriff");
		$clustergroup->select("GroupID");
		$clustergroup->select("DateCreation", "zugeh�rigkeit seit");
		$clustergroup->preSelect("DateCreation", "sysdate()");
		$clustergroup->distinct();
		$clustergroup->changeFormOptions("Speichern");
		$clustergroup->noInsert();
		$clustergroup->noUpdate();
		$clustergroup->noDelete();
		$clustergroup->setMaxRowSelect(20);
		
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
		$cluster= $this->getTable("Cluster");
		$cluster->select("ID");
		$cluster->select("Description");
		$selector= &new OSTDbSelector($cluster);
		$selector->execute();
		$res= $selector->getRowResult();
		$div= &new DivTag();
			$h2= &new H3Tag("Description");
				$h2->add("Zugeh�rigkeit der Gruppen zum Cluster ");
				$span= &new SpanTag("hightlighted");
					$span->add($res[0]);
				$h2->addObj($span);
			$div->addObj($h2);
			$div->add($res[1]);
			$div->align("center");
		$this->addObjBehindProjectIdentif($div);
	}
}
?>