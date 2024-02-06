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