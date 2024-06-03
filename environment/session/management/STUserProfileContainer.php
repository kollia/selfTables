<?php

global $_stdbinserter;
require_once($_stdbinserter);
require_once($_stsitecreator);
require_once $_stbackgroundimagesdbcontainer;
require_once($_st_registration_text);

class STUserProfileContainer extends STBackgroundImagesDbContainer
{
	private $bAdminActivation= false;

	function __construct($name, &$container, $bInstall= false)
	{
		Tag::paramCheck($name, 1, "string");
		Tag::paramCheck($container, 2, "STObjectContainer");	
		
		STObjectContainer::__construct($name, $container);
	}
	function create()
	{
		$session= STUserSession::instance();
		$registration= $session->getSessionVar("ST_REGISTRATION");
			
		$userID= $session->getUserID();
		$user= &$this->needTable("User");
		$user->where($user->getPkColumnName()."=".$userID);
		if($registration)
		{
			// check first whether registration process is finished
			// or active
			$selector= new STDbSelector($user);
			$selector->clearSelects();
			//$selector->where("User", $user->getPkColumnName()."=".$userID);
			//$selector->andWhere("User", "register>='SENDMAIL");
			$selector->execute();
			$result= $selector->getResult();
			if(	isset($result[0]['Pwd']) &&
				$result[0]['Pwd'] != ""		)
			{// registration process is finished	
				//STCheck::debug();
				$newdbvalue= $result[0];
				$session->setSessionVar("ST_REGISTRATION", false);
                $query= $session->getSessionUrlParameter();
                if($query != "")
                    $query= "?doLogout=&user=admin".$query;
				$user->setDisplayName("User Profile");
				//$query= "/";
				$script= new JavascriptTag();
				$script->add("top.location.href= '$query';");
				/**
				 * for this side do not use background images
				 * so add content directly into tag not afterward image creation
				 * set bAddContent to false
				 */
				$this->bAddContent= false;
				$this->addOnBodyEnd($script);
				
				// create EMail text
				if($this->bAdminActivation)
					$acknowledge= "ACKNOWLEDGE_INACTIVE";// need activation from admin
				else
					$acknowledge= "ACKNOWLEDGE_ACTIVE";// no admin activation required, user is directly active
				$mail= get_db_mail_text($user, $acknowledge, $newdbvalue);

				$toEmail= $newdbvalue['email'];		
				$oMail= new STEmail(/*exception*/false);
				if(	!$oMail->init($mail['admin']) ||
					!$oMail->sendmail($toEmail, $mail['subject'], $mail['text'], $mail['html'])	)
				{
					$error= $oMail->getErrorString();
					return $error;
				}
			}else
			{
				$user->setDisplayName("please set new password");
				$user->setFirstAction(STUPDATE);
			}
		}else
			$user->setDisplayName("User Profile");
		$user->accessBy("LOGGED_IN");
	}
	function useAdminActivation()
	{
		return $this->bAdminActivation= true;
	}
	function init(string $action, string $table)
	{
		$session= STUserSession::instance();
		$registration= $session->getSessionVar("ST_REGISTRATION");

		$domain= $this->getTable("AccessDomain");
		$domain->identifColumn("Name", "Domain");

		$user= $this->getTable("User");
		$user->select("domain", "Domain");
		$user->disabled("domain");
		$user->select("user", "Nickname");
		if(!$registration)
		{
			$user->select("title_prefixed", "prefixed Title");
			$user->pullDownMenuByEnum("title_prefixed");
			$user->select("firstname", "Firstname");
			$user->select("surname", "Surname");
			$user->select("title_subsequent", "Title");
			$user->pullDownMenuByEnum("title_subsequent");
			$user->select("sex", "Sex");
			$user->pullDownMenuByEnum("sex");
			$user->select("email", "Email Address");
			//$user->getColumn("GroupType");
			$user->doInsert(false);
			$user->doDelete(false);
			$user->listLayout(STVERTICAL);
		}else
		{
			$user->disabled("user");
			if(!$this->bAdminActivation)
			{// no admin activation required, user is directly active
				$user->preselect("register", "ACTIVE");
				$user->preselect("active", "YES");
			}else{//$user->select("register");$user->pullDownMenuByEnum("register");
				// need activation from admin
				$user->preselect("register", "INACTIVE", STUPDATE);}
		}
			
		if($action==STLIST)
		{
		    $user->select("NrLogin", "logged in");
		}else
		{
			$user->select("Pwd", "Pwd");
			$user->password("Pwd", true);
			if($registration)
				$user->passwordNames("new Password", "Password repetition");
			else
				$user->passwordNames("old Password", "new Password", "Password repetition");
		}
		if($this->currentContainer())
		{
			$userID= $session->getUserID();
			if($userID)
				$user->where($user->getPkColumnName()."=".$userID);
		}
	}
}

?>