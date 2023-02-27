<?php

global $_stdbinserter;
require_once($_stdbinserter);
require_once($_stsitecreator);

class STUserProfileContainer extends STObjectContainer
{
	function __construct($name, &$container, $bInstall= false)
	{
		Tag::paramCheck($name, 1, "string");
		Tag::paramCheck($container, 2, "STObjectContainer");	
		
		STObjectContainer::__construct($name, $container);
	}
	function create()
	{
		$this->setDisplayName("User Profile");

		$user= &$this->needTable("User");
		$user->setDisplayName("Benutzer-Daten");
		$user->identifColumn("UserName", "user");
		$user->select("UserName", "User");
		$user->select("FullName", "full qualified Name");
		$user->getColumn("GroupType");
		$user->doInsert(false);
		$user->doDelete(false);
		$user->listLayout(STVERTICAL);
		$user->accessBy("LOGGED_IN");
	}
	function init()
	{
		$user= &$this->needTable("User");
		$user->select("EmailAddress", "Email Address");
		$user->select("Description", "Description");
		$action= $this->getAction();
		if($action==STLIST)
		{
		    $user->select("NrLogin", "logged in");
			$user->select("LastLogin", "last login");
		}else
		{
			$user->select("Pwd", "Pwd");
			$user->password("Pwd", true);
			if($this->currentContainer())
				$user->passwordNames("altes Passwort", "neues Passwort", "Passwort wiederholung");
			else
				$user->passwordNames("neues Passwort", "Passwort wiederholung");
		}
		if($this->currentContainer())
		{
			$session= &STUserSession::instance();
			$userID= $session->getUserID();
			if($userID)
				$user->where($user->getPkColumnName()."=".$userID);
		}
	}
}

?>