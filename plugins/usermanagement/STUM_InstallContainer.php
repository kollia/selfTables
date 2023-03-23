<?php

class STUM_InstallContainer extends STObjectContainer
{
	function create()
	{
	    $instance= &STSession::instance();
	    $domain= $instance->getCustomDomain();
	    
		$this->setFirstTable("User", STINSERT);
		$user= &$this->needTable("User");
		$user->setDisplayName("Administrator");
		$user->select("user", "Nickname");
		$user->select("FullName", "Full name");
		$user->select("email", "Email");
		$user->select("Pwd");
		$user->preSelect("domain", $domain['ID']);
		$user->preSelect("user", "admin");
		$user->preSelect("FullName", "Administrator");
		$user->preSelect("DateCreation", "sysdate()");
		$user->password("Pwd", true);
		$user->passwordNames("Password", "Repeat password");
	}
}
?>