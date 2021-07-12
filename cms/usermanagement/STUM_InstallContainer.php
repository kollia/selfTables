<?php

class STUM_InstallContainer extends STObjectContainer
{
	function create()
	{
		STObjectContainer::create();

		// search for custom in table GroupType
		$groupType= &$this->getTable("GroupType");
		$groupType->select("ID");
		$selector= new OSTDbSelector($groupType);
		$selector->execute();
		$groupTypeId= $selector->getSingleResult();
		if(!$groupTypeId)
		{
			$inserter= new STDbInserter($groupType);
			$inserter->fillColumn("Label", "custom");
			$inserter->fillColumn("DateCreation", "sysdate()");
			$inserter->execute();
			$groupTypeId= $this->db->getLastInsertID();
		}

		$this->setFirstTable("User", STINSERT);
		$user= &$this->needTable("User");
		$user->select("UserName", "Administrator");
		$user->select("Pwd");
		$user->preSelect("UserName", "admin");
		$user->preSelect("GroupType", $groupTypeId);
		$user->preSelect("DateCreation", "sysdate()");
		$user->password("Pwd", true);
		$user->passwordNames("Passwort", "Passwort wiederholung");
	}
}
?>