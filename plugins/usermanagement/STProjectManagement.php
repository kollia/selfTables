<?php

class STProjectManagement extends STObjectContainer
{
	function __construct($name, &$container)
	{
		STCheck::param($name, 0, "string");
		STCheck::param($container, 1, "STObjectContainer");

		STObjectContainer::STObjectContainer($name, $container);
	}
	function create()
	{
		$this->setDisplayName("Administration");
		$this->setFirstTable("Project");

		$project= &$this->needTable("Project");
		$project->setDisplayName("Projekte");
		$project->select("Name", "Projektname");
		$project->namedPkLink("Name", "partition");
		$project->select("description", "Projekt-Beschreibung");
		$project->preSelect("DateCreation", "sysdate()");
		$project->accessBy("STUM-ProjectAccess", STLIST);
		$project->accessBy("STUM-ProjectInsert", STINSERT);
		$project->accessBy("STUM-ProjectUpdate", STUPDATE);
		$project->accessBy("STUM-ProjectDelete", STDELETE);
		$project->needPkInResult("has_access");
		$project->accessCluster("has_access", "ID", "Berechtigung zum ansehen des Projektes @.");
		$project->accessCluster("can_insert", "ID", "Berechtigung zum anlegen eines neue Projektes.");
		$project->accessCluster("can_update", "ID", "Berechtigung zum �ndern des Projektes @.");
		$project->accessCluster("can_delete", "ID", "Berechtigung zum l�schen des Projektes @.");

		$user= &$this->needTable("User");
		$user->setDisplayName("Benutzer-Liste");
		$user->accessBy("STUM-UserListAccess", STLIST);
		$user->accessBy("STUM-UserListAdmin", STADMIN);
		//$user->select("UserName", "Benutzer");
		$user->clearWhere();
	}
	function init()
	{
		$action= $this->getAction();
		$project= &$this->getTable("Project");
		$user= &$this->needTable("User");
		if($action==STLIST)
		{
			$project->select("DateCreation", "Erzeugungs-Datum");

			/*$user->select("LastLogin", "letzter Zugriff");
			$user->select("NrLogin", "besucht");*/
		}else
		{
			$project->select("Path", "Start-Dateipfad");

			/*$user->select("Pwd");
			$user->password("Pwd", true);
			$user->passwordNames("neues Passwort", "Passwort wiederholung");*/

		}
	}
	function installContainer()
	{
		$this->createCluster("STUM-ProjectAccess", "Berechtigung zum ansehen aller Projekte");
		$this->createCluster("STUM-ProjectInsert", "Berechtigung zum erstellen eines Projektes");
		$this->createCluster("STUM-ProjectUpdate", "Berechtigung zum editieren der Projekte");
		$this->createCluster("STUM-ProjectDelete", "Berechtigung zum l�schen der Projekte");
	}
}
?>