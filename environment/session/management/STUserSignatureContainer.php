<?php

require_once $_stuserprofilecontainer;
require_once($_st_registration_text);

class STUserSignatureContainer extends STUserProfileContainer
{
	private $stored_user_data;

	function __construct($name, &$container, $bInstall= false)
	{
		Tag::paramCheck($name, 1, "string");
		Tag::paramCheck($container, 2, "STObjectContainer");	
		
		STObjectContainer::__construct($name, $container);
	}
	function create()
	{
		//STUserProfileContainer::create();
	}
	function init(string $action, string $table)
	{
		// do not need overview images
		$this->bAddContent= false;

		if($this->currentContainer())
		{
			STUserProfileContainer::init($action, $table);

			$user= $this->getTable("User");
			$user->setDisplayName("Stored User Data");
			$user->clearSelects();
			//$user->select("*");
			$user->doUpdate(false);
			$user->clearWhere();
			
			$selector= new STDbSelector($user);
			$selector->execute();
			$dbvalues= $selector->getRowResult();
			$newdbvalue= array();
			foreach($dbvalues as $key=>$value)
			{
				$field= $user->searchByAlias($key);
				if(isset($field))
					$newdbvalue[$field['column']]= $value;
			}
			$dbvalues['regcode']= "do not need";

			$text= get_db_mail_text($user, "PRINT_STORED_USER_DATA", $dbvalues);
			$this->stored_user_data= $text['html'];
		}
	}
	public function execute(&$externSideCreator, $onError)
	{
		$script= new JavaScriptTag();
		$script->add("window.print();");

		STObjectContainer::add($this->stored_user_data);
		STObjectContainer::add($script);
		
		$this->needBackButton(false);
		$res= STUserProfileContainer::execute($externSideCreator, $onError);
		return $res;
	}
}

?>