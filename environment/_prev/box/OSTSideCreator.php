<?php

require_once($_ostusersession);

class OSTSideCreator extends STUserSideCreator
{
	var	$sTablePrefix;

	function OSTSideCreator($project, $container= null, $bInstall= false)
	{
		STCheck::paramCheck($project, 1, "string", "int");
		STCheck::paramCheck($container, 2, "STObjectContainer", "string", "boolean", "null");
		STCheck::param($bInstall, 2, "boolean");

		if(is_bool($container))
		{
			$bInstall= $container;
			$container= null;
		}
		STUserSideCreator::STUserSideCreator($project);
		$this->init($project, $container, $bInstall);
	}
	function init($project, $container, $bInstall)
	{
		global	$DBin_UserHost,
				$DBin_UserUser,
				$DBin_UserPwd,
				$DBin_UserDatabase,
				$_selftable_first_main_database_name;

		if(	!$this->db
			||
			$this->db->getDatabaseName() != $DBin_UserDatabase)
		{
			$userDb= new OSTDatabase("UserManagement");
			$userDb->connect($DBin_UserHost, $DBin_UserUser, $DBin_UserPwd);
			$userDb->useDatabase($DBin_UserDatabase);
			if(!$_selftable_first_main_database_name)
				$_selftable_first_main_database_name= $userDb->getName();
			if(!$this->db)
				$this->db= &$userDb;
		}else
			$userDb= &$this->db;

		$userDb->getName();
		$desc= &STDbTableDescriptions::instance($userDb->getName());
		$desc->setPrefixToTables("MU");
		$this->setProjectID($project);

		if($container != null)
		{
			if(is_string($container))
			{
				$mainContainerName= $container;

				$container= &STBaseContainer::getContainer($mainContainerName);
			}
			$this->setMainContainer($container->getName());
		}
		if($bInstall)
			$this->install();
		$this->initSession();
		
		if($container != null)
			$this->setMainContainer($container->getName());

		$project= $this->userManagement->getProjectName();
		$identifier= "<h1><em>Projekt</em> <font color='red'>";
		$identifier.= $project."</font></h1>";
		$this->setProjectDisplayName($identifier);
	}
}

?>