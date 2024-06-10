<?php

class STUM_InstallContainer extends STObjectContainer
{
	protected function create()
	{
	    $instance= &STSession::instance();
	    $domain= $instance->getCustomDomain();
	    
		$this->setFirstTable("User", STINSERT);
		$user= &$this->needTable("User");
		$user->setDisplayName("Administrator");
		$user->select("sex", "Sex");
		$user->select("user", "Nickname");
		$user->select("firstname", "first Name");
		$user->select("surname", "Surname");
		$user->select("email", "Email");
		$user->select("Pwd");
		$user->password("Pwd", true);
		$user->passwordNames("Password", "Repeat password");
		$user->preSelect("domain", $domain['ID']);
		$user->preSelect("user", "admin");
		$user->preSelect("surname", "Administrator");
		$user->preSelect("register", "ACTIVE");
		$user->preSelect("active", "YES");
		$user->preSelect("DateCreation", "sysdate()");
	}
	
	/**
	 * method will be called when container has an action
	 * to display as STListBox or STItembox
	 *
	 * @param string $action current action of container STInsert/STUpdate/STList/STDelete
	 * @param string $table which table is in action
	 */
	 protected function init(string $action, string $table)
	 {
		// nothing todo
	 }
}
?>