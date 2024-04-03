<?php

//global $_stdbinserter;
require_once($_stdbinserter);
require_once($_stsitecreator);
require_once($_stuserclustergroupmanagement);

function disablePasswordCallback(STCallbackClass &$callbackObject, $columnName, $rownum)
{
	//$callbackObject->echoResult();
    if(!$callbackObject->before)
        return;

    $session= STUSerSession::instance();
    $domain= $session->getCustomDomain();
    $table= $callbackObject->getTable();
    $domainField= $table->findAliasOrColumn("domain");
    $domainColumn= $domainField['alias'];
    $domainValue= $callbackObject->getValue($domainColumn);
    
    if($domainValue != $domain['Name'])
        $callbackObject->disabled($columnName);
}
function emailCallback(STCallbackClass &$callbackObject, $columnName, $rownum)
{
	if( $callbackObject->display ||
		!$callbackObject->before	)
	{
		return;
	}
	//$callbackObject->echoResult();
	$send= $callbackObject->getValue("send_EMail");
	if(	isset($send) &&
		$send == "yes"	)
	{
		$db= $callbackObject->getDatabase();
		$oUserTable= $callbackObject->getTable();

		// create user-code
		$pwdField= $oUserTable->searchByColumn("Pwd");
		$code= random_int(10000000, 99999999);
		$callbackObject->setValue($code, $pwdField['alias']);
		$callbackObject->setValue($code, "re_{$pwdField['alias']}");

		// extract column values from post with alias names
		$dbvalues=  $callbackObject->getAllValues();
		$newdbvalue= array();
		foreach($dbvalues as $key=>$value)
		{
			$field= $oUserTable->searchByAlias($key);
			if(!isset($field))
			{// try to remove underline '_' should be a space
				$key= preg_replace("/_/", " ", $key);
				$field= $oUserTable->searchByAlias($key);
			}
			if(isset($field))
				$newdbvalue[$field['column']]= $value;
		}

		// create EMail text
		$selector= new STDbSelector($db);
		$selector->select("Mail", "case");
		$selector->select("Mail", "subject");
		$selector->select("Mail", "html");
		$selector->select("Mail", "text");
		$where= new STDbWhere();
		$where->where("case!='OWN_REGISTRATION'");
		$where->andWhere("case!='ACKNOWLEDGE_INACTIVE'");
		$where->andWhere("case!='ACKNOWLEDGE_ACTIVE'");
		$selector->where($where);
		$selector->execute();
		$repl_content= $selector->getResult();
		$new_replacement= array();
		foreach($repl_content as $row)
		{
			if($row['case'] == "MAIL_REGISTRATION")
			{
				$mail_subject= $row['subject'];
				$mail_text= $row['text'];
				break;
			}else
			{
				if(preg_match("/\./", $row['case']))
				{
					$cases= preg_split("/\./", $row['case']);
					if(	isset($newdbvalue[$cases[1]]) &&
						$newdbvalue[$cases[1]] == $cases[2]	)
					{
						$row['case']= $cases[0];
						$row['text']= preg_replace("/\{subject\}/", $row['subject'], $row['text']);
						$new_replacement[]= $row;
					}
				}else
				{
					$row['text']= preg_replace("/\{subject\}/", $row['subject'], $row['text']);
					$new_replacement[]= $row;
				}
			}
		}
		usermanagement_email_replacement($mail_subject, $new_replacement, $newdbvalue);
		if(preg_match("/\{.*\}/", $mail_subject, $preg))
			STCheck::warning(true, "usermanagement_email_replacement()", "placeholder '{$preg[0]}' from registration EMail-Text does not exist");
		usermanagement_email_replacement($mail_text, $new_replacement, $newdbvalue);
		if(preg_match("/\{.*\}/", $mail_text, $preg))
			STCheck::warning(true, "usermanagement_email_replacement()", "placeholder '{$preg[0]}' from registration EMail-Text does not exist");
		$line= __LINE__;
		return "build email '$mail_subject' by STUserManagement callback on line $line";
	}
}
function usermanagement_email_replacement(string &$string, array $replacement, array $dbreplacement) : int
{
	$nChanged= 0;
	foreach($dbreplacement as $key => $value)
	{
		if(isset($value))
		{
			$string= preg_replace("/\{$key\}/", $value, $string, /*no limit*/-1, $count);
			$nChanged+= $count;
		}
	}
	foreach($replacement as $value)
	{
		if(preg_match("/\{(".$value['case'].")(\.exist)?\}/", $string, $preg))
		{
			$pattern= $preg[1];
			if(isset($preg[2]))
			{ // <case>.exist want to call
				$pattern.= "\\.exist";
				$replace= $value['text'];
				if(preg_match_all("/\{[^{}]+\}/", $replace, $counter))
				{
					$num= usermanagement_email_replacement($replace, $replacement, $dbreplacement);
					if(count($counter) != $num)
						$replace= "";
				}
			}else
				$replace= $value['text'];
			$string= preg_replace("/\{$pattern\}/", $replace, $string, /*no limit*/-1, $count);
			$nChanged+= $count;
		}
	}
	if(preg_match("/\{.*\}/", $string, $preg))
	{ // if filled placeholders but now also placeholder exist do again
	  // but if doesn't filled placeholders but placeholder exist don't do again
		if($nChanged)
			$nChanged+= usermanagement_email_replacement($string, $replacement, $dbreplacement);
	}
	return $nChanged;
}
function checkPasswordCallback(STCallbackClass &$callbackObject, $columnName, $rownum)
{
	//$callbackObject->echoResult();
    if(	$callbackObject->display == true ||
		$callbackObject->before == false	)
	{
        return;
	}
	$send= $callbackObject->getValue("send_EMail");
	if($send == "yes")
		return;
	$pwd= $callbackObject->getValue("Pwd");
	if( $callbackObject->action == STUPDATE &&
		$pwd == ""								)
	{
		// can be "" by update when password not changed
		return;
	}
	$table= new st_tableTag(LI);
		$table->style("background-color:red;");
		$table->add("password have to be longer than 8 digits");
		$table->nextRow();
		$table->add("The password must contain lowercase letters,<br />uppercase letters and numbers");
	
	if(strlen($pwd) < 9)
	{
		$callbackObject->addHtmlContent($table);
		return "password have to be longer than 8 digits";
	}
	if(	preg_match("/[a-z]/", $pwd) &&
		preg_match("/[A-Z]/", $pwd) &&
		preg_match("/[0-9]/", $pwd)	&&
		!preg_match("/^\*/", $pwd)		)
	{
		return;
	}
	$callbackObject->addHtmlContent($table);
	return "The password must contain lowercase letters, uppercase letters and numbers and should not begin with a star ('*')";
}
function descriptionCallback(&$callbackObject, $columnName, $rownum)
{
    //$bFirstCall= $callbackObject->echoResult();
    global	$global_selftable_session_class_instance;
    
    $instance= $global_selftable_session_class_instance[0];
    
    {
        $clusterTable= $callbackObject->getTable("Cluster");
        $cluster= new STDbSelector($clusterTable);
        $cluster->select("Project", "Name", "Name");
        $cluster->select("Cluster", "ID", "cluster");
        $cluster->select("Cluster", "Description", "Description");
        $cluster->where("ClusterGroup", "GroupID=".$callbackObject->sqlResult[$rownum]['access descriptions']);
        $cluster->execute();
        $aResult= $cluster->getResult();
    }
    $source=   "<table>";
    foreach($aResult as $row)
    {
        $source.=  "	<tr>";
        $source.=  "		<td>";
        $source.=  "			<b>";
        $source.=  "				[";
        if($row['cluster'] != $instance->allAdminCluster)
            $source.= $row["Name"];
        $source.= "]";
        $source.=  "			</b>";
        $source.=  "		</td>";
        $source.=  "		<td>";
        $source.=  "				".$row["Description"];
        $source.=  "		</td>";
        $source.=  "	</tr>";
    }
    $source.=  "</table>";
    $callbackObject->setValue($source);
    
}

function actionCallback(&$callbackObject, $columnName, $rownum)
{
    $session= STUSerSession::instance();
    $domain= $session->getCustomDomain();
    if( $callbackObject->sqlResult[$rownum]["Group"] == $session->onlineGroup ||
        $callbackObject->sqlResult[$rownum]["Group"] == $session->loggedinGroup ||
        $callbackObject->sqlResult[$rownum]["Domain"] != $domain['ID']              )
    {
        $callbackObject->noUpdate();
        $callbackObject->noDelete();
    }
}

class STUserManagement extends STObjectContainer
{
	function __construct(string $name, STObjectContainer &$container)
	{
		STObjectContainer::__construct($name, $container);
	}
	protected function create()
	{
	    $session= &STUserSession::instance();
	    $this->setDisplayName("Project Management");
	    $this->accessBy($session->usermanagement_Project_AccessCluster, STLIST);
	    $this->accessBy($session->usermanagement_Project_ChangeCluster, STADMIN);
		//$this->needContainer("projects");
	    
	    $domain= $this->getTable("AccessDomain");
	    $domain->identifColumn("Name", "Domain");
	    //$domain->select("ID", "Domain", "Domain);
	    
		$mail= &$this->needTable("Mail");
		$mail->setDisplayName("registration EMail-Text");
	    $mail->accessBy($session->usermanagement_User_AccessCluster, STLIST);
	    $mail->accessBy($session->usermanagement_User_ChangeCluster, STADMIN);

	    $user= &$this->needTable("User");
	    $user->setDisplayName("User");
	    $user->accessBy($session->usermanagement_User_AccessCluster, STLIST);
	    $user->accessBy($session->usermanagement_User_ChangeCluster, STADMIN);
	       
	    $groups= &$this->needTable("Group");
	    $groups->setDisplayName("Groups");
	    
		$project= &$this->needTable("Project");
		$project->setDisplayName("existing Projects");
		$this->setFirstTable("Project");
	}
	protected function init(string $action, string $table)
	{
	    $session= &STUserSession::instance();
	    $domain= $session->getCustomDomain();
	    
	    $username= "User";
	    $newpass= "new Password";
	    $reppass= "Password repetition";

	    $mail= &$this->needTable("Mail");
		$mail->select("case", "Case");
		$mail->select("description", "Description");
		if($action != STLIST)
		{
			$mail->select("subject", "Subject");
			$mail->select("html", "allow HTML");
			$mail->select("text", "EMail text");
		}

	    $user= &$this->needTable("User");
		$user->select("domain", "Domain");
		$user->preSelect("domain", $domain['ID']);
		$user->disabled("domain");
		$user->select("sex", "Sex");
		$user->pullDownMenuByEnum("sex");
		$user->select("user", "Nickname");
		$user->select("title_prefixed", "Title");
		$user->pullDownMenuByEnum("title_prefixed");
		$user->select("firstname", "first Name");
		$user->select("surname", "Surname");
		$user->select("title_subsequent", "Title subsequent");
		$user->pullDownMenuByEnum("title_subsequent");
		$user->select("email", "Email");
		$user->addColumn("sending", "SET('no', 'yes')", /*NULL*/false);
		if( $action == STINSERT ||
			$action == STUPDATE	)
		{
			$user->select("sending", "send EMail");
		}
		//$user->getColumn("register");
		$user->preSelect("DateCreation", "sysdate()");
		
		$groups= &$this->needTable("Group");
		$groups->select("domain", "Domain");
		$groups->preSelect("domain", $domain['Name']);
		$groups->disabled("domain");
		$groups->preSelect("DateCreation", "sysdate()");
		$groups->select("Name", "Group");
		
		$project= &$this->needTable("Project");
		$project->select("Name", "Project");
		$project->select("Description", "Description");
		if($action == STLIST)
		{
			$project->select("ID", "ID");
			$project->align("ID", "center");
		}
		$project->select("display", "Display");
		$project->preSelect("display", "ENABLED");
		if($action != STLIST)
		{
			$project->select("Target", "Target");
			$project->preSelect("Target", "SELF");
			$project->pullDownMenuByEnum("Target");
		}
		$project->select("Path", "URL");
		$project->preSelect("DateCreation", "sysdate()");
		$project->orderBy("Name");
		
		if($action==STLIST)
		{
		    $user->select("NrLogin", "logged in");
		    $user->select("LastLogin", "last login");
		    $user->orderBy("domain");
		    $user->orderBy("user");
		    $user->setMaxRowSelect(50);
		    
		    $groups->select("ID", "access descriptions");
		    $groups->listCallback("descriptionCallback", "access descriptions");
		    //$groups->listCallback("actionCallback", "update");
		    $groups->listCallback("actionCallback", "delete");
		    $groups->orderBy("domain");
		    $groups->orderBy("Name");
		    $groups->setMaxRowSelect(50);
		    
			$userClusterGroup= $this->getContainer("UserClusterGroupManagement");
		    $project->namedLink("Project", $userClusterGroup);
		}else
		{
			$user->select("Pwd", "Pwd");
			$user->password("Pwd", true);
		    $user->passwordNames($newpass, $reppass);
		    $user->updateCallback("disablePasswordCallback", $newpass);
		    $user->updateCallback("disablePasswordCallback", $reppass);
		    $user->updateCallback("disablePasswordCallback", $username);
			$user->updateCallback("checkPasswordCallback", $newpass);
			$user->insertCallback("checkPasswordCallback", $newpass);
			$user->updateCallback("emailCallback", "sending");
			$user->insertCallback("emailCallback", "sending");
		}
	}
	/**
	 * method to create messages for different languages.<br />
	 * inside class methods (create(), init(), ...) you get messages from <code>$this->getMessageContent(<message id>, <content>, ...)</code><br />
	 * inside this method depending the <code>$language</code> define messages with <code>$this->setMessageContent(<message id>, <message>)</code><br />
	 * see STMessageHandling
	 *
	 * @param string $language current language like 'en', 'de', ...
	 * @param string $nation current nation of language like 'US', 'GB', 'AT'. If not defined, default is 'XXX'
	 */
	protected function createMessages(string $language, string $nation)
	{
		global $global_boolean_installed_objectContainer;

		STObjectContainer::createMessages($language, $nation);

		if(!$this->oExternSideCreator->bDoInstall)
			return;
		// -----------------------------------------------------------------------------------------
		// use this translation strings only if STProjectUserSiteCreator set to install
		if($language == "de")
		{
		    $this->setMessageContent("MAIL-HOST-description", "address of Website");
		    $this->setMessageContent("MAIL-HOST-subject", "");
		    $this->setMessageContent("MAIL-HOST-text", "");
		    $this->setMessageContent("MAIL-ADMINISTRATOR_MAIL-description", "email address from administrator who handles the registration process");
		    $this->setMessageContent("MAIL-ADMINISTRATOR_MAIL-subject", "");
		    $this->setMessageContent("MAIL-ADMINISTRATOR_MAIL-text", "");
			$this->setMessageContent("MAIL-MAIL_CODE-description", "generated numeric code when sending emails");
			$this->setMessageContent("MAIL-MAIL_CODE-subject", "");
			$this->setMessageContent("MAIL-MAIL_CODE-text", "{Pwd}");
			$this->setMessageContent("MAIL-MAIL_MINUTES-description", "how long the created account by own registration should be available");
			$this->setMessageContent("MAIL-MAIL_MINUTES-subject", "5");
			$this->setMessageContent("MAIL-MAIL_MINUTES-text", "{subject} Minuten");
			$this->setMessageContent("MAIL-MAIL_DAYS-description", "how long the created user-code by Admin mail should be available");
			$this->setMessageContent("MAIL-MAIL_DAYS-subject", "5");
			$this->setMessageContent("MAIL-MAIL_DAYS-text", "{subject} Tage");
			$this->setMessageContent("MAIL-SIGNATURE-description", "Signature by all sending emails");
			$this->setMessageContent("MAIL-SIGNATURE-subject", "");
			$this->setMessageContent("MAIL-SIGNATURE-text", "Ihr Software-Team\\n    from {HOST}");
			$this->setMessageContent("MAIL-PREFIXED_TITLE-description", "prefixed title only if exist");
			$this->setMessageContent("MAIL-PREFIXED_TITLE-subject", "");
			$this->setMessageContent("MAIL-PREFIXED_TITLE-text", " {title_prefixed}");
			$this->setMessageContent("MAIL-SUBSEQUENT_TITLE-description", "subsequint title only if exist");
			$this->setMessageContent("MAIL-SUBSEQUENT_TITLE-subject", "");
			$this->setMessageContent("MAIL-SUBSEQUENT_TITLE-text", ", {title_subsequent}");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.FEMALE-description", "Form of address for womans");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.FEMALE-subject", "");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.FEMALE-text", "Sehr geehrte Frau{PREFIXED_TITLE.exist} {surname}{SUBSEQUENT_TITLE.exist}");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.MALE-description", "Form of address for mens");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.MALE-subject", "");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.MALE-text", "Sehr geehrter Herr{PREFIXED_TITLE.exist} {surname}{SUBSEQUENT_TITLE.exist}");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.*GENDER-description", "form of address for gender neutral persons");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.*GENDER-subject", "");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.*GENDER-text", "Sehr geehrte(r/s){PREFIXED_TITLE.exist} {surname}{SUBSEQUENT_TITLE.exist}");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.UNKNOWN-description", "form of address for unknown persons");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.UNKNOWN-subject", "");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.UNKNOWN-text", "Sehr geehrte(r)Frau/Herr {PREFIXED_TITLE.exist} {surname}{SUBSEQUENT_TITLE.exist}");
			$this->setMessageContent("MAIL-OWN_REGISTRATION-description", "registration by user (not inside UserManagement)");
			$this->setMessageContent("MAIL-OWN_REGISTRATION-subject", "Registrierung auf der Web-Seite {HOST}");
			$this->setMessageContent("MAIL-OWN_REGISTRATION-text", "{FORM_ADDRESS}!\\n\\n".
														" Danke das sie sich auf unserer Web-Site {HOST} registrieren wollen.\\n".
														" Sie haben den Benutzer mit dem Namen '{user}' angelegt.\\n".
														" Bitte bestätigen sie ihre Registrierung mit dem Link {HOST}?regmail={pwd}.\\n".
														" Dieser LINK ist {MAIL_MINUTES} gültig.\\n\\n".
														" {SIGNATURE}");
			$this->setMessageContent("MAIL-MAIL_REGISTRATION-description", "registration from ADMIN, hook 'send EMail' by creation/update of User");
			$this->setMessageContent("MAIL-MAIL_REGISTRATION-subject", "Registration on Website {HOST}");
			$this->setMessageContent("MAIL-MAIL_REGISTRATION-text", "{FORM_ADDRESS}!\\n\\n".
														" Bitte registrieren sie sich auf unserer Web-Site {HOST}.\\n".
														" Es wurde für Sie der Benutzer mit dem Namen '{user}' angelegt.\\n".
														" verwenden Sie diesen mit dem user-code \"{MAIL_CODE}\", ohne Anführungszeichen, als Passwort.\\n".
														" Der user-code ist {MAIL_DAYS} gültig.\\n\\n".
														" {SIGNATURE}");
			$this->setMessageContent("MAIL-ACKNOWLEDGE_INACTIVE-text", "{FORM_ADDRESS}!\\n\\n".
														" Vielen Dank, dass sie sich auf unserer Web-Sitee {HOST} registriert haben.\\n".
														" Bitte senden sie eine EMail an ADMINISTRATOR_MAIL\\n".
														" um ihr Konto mit dem Benutzer-Namen '{user}' freizuschalten.\\n\\n".
														" {SIGNATURE}");
			$this->setMessageContent("MAIL-ACKNOWLEDGE_ACTIVE-description", "acknowledge for registration");
			$this->setMessageContent("MAIL-ACKNOWLEDGE_ACTIVE-subject", "Registrierung auf der Web-Seite {HOST}");
			$this->setMessageContent("MAIL-ACKNOWLEDGE_ACTIVE-text", "{FORM_ADDRESS}!\\n\\n".
														" Vielen Dank, dass sie sich auf unserer Web-Sitee {HOST} registriert haben.\\n".
														" Ihr Konto mit dem Benutzer '{user}' wurde frei geschalten.\\n\\n".
														" {SIGNATURE}");
			
		}else // otherwise language have to be english "en"
		{
		    $this->setMessageContent("MAIL-HOST-description", "address of Website");
		    $this->setMessageContent("MAIL-HOST-subject", "");
		    $this->setMessageContent("MAIL-HOST-text", "");
		    $this->setMessageContent("MAIL-ADMINISTRATOR_MAIL-description", "email address from administrator who handles the registration process");
		    $this->setMessageContent("MAIL-ADMINISTRATOR_MAIL-subject", "");
		    $this->setMessageContent("MAIL-ADMINISTRATOR_MAIL-text", "");
			$this->setMessageContent("MAIL-MAIL_CODE-description", "generated numeric code when sending emails");
			$this->setMessageContent("MAIL-MAIL_CODE-subject", "");
			$this->setMessageContent("MAIL-MAIL_CODE-text", "");
			$this->setMessageContent("MAIL-MAIL_MINUTES-description", "how long the created account by own registration should be available");
			$this->setMessageContent("MAIL-MAIL_MINUTES-subject", "5");
			$this->setMessageContent("MAIL-MAIL_MINUTES-text", "{subject} minutes");
			$this->setMessageContent("MAIL-MAIL_DAYS-description", "how long the created user-code by Admin mail should be available");
			$this->setMessageContent("MAIL-MAIL_DAYS-subject", "5");
			$this->setMessageContent("MAIL-MAIL_DAYS-text", "{subject} days");
			$this->setMessageContent("MAIL-SIGNATURE-description", "Signature by all sending emails");
			$this->setMessageContent("MAIL-SIGNATURE-subject", "");
			$this->setMessageContent("MAIL-SIGNATURE-text", "Your Software-Team\\n    from {HOST}");
			$this->setMessageContent("MAIL-PREFIXED_TITLE-description", "prefixed title only if exist");
			$this->setMessageContent("MAIL-PREFIXED_TITLE-subject", "");
			$this->setMessageContent("MAIL-PREFIXED_TITLE-text", " {title_prefixed}");
			$this->setMessageContent("MAIL-SUBSEQUENT_TITLE-description", "subsequint title only if exist");
			$this->setMessageContent("MAIL-SUBSEQUENT_TITLE-subject", "");
			$this->setMessageContent("MAIL-SUBSEQUENT_TITLE-text", ", {title_subsequent}");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.FEMALE-description", "form of address for womans");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.FEMALE-subject", "");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.FEMALE-text", "Dear Mrs.{PREFIXED_TITLE} {surname}{SUBSEQUENT_TITLE}");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.MALE-description", "form of address for mens");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.MALE-subject", "");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.MALE-text", "Dear Mr.{PREFIXED_TITLE} {surname}{SUBSEQUENT_TITLE}");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.*GENDER-description", "form of address for gender neutral persons");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.*GENDER-subject", "");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.*GENDER-text", "Dear{PREFIXED_TITLE} {surname}{SUBSEQUENT_TITLE}");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.UNKNOWN-description", "form of address for unknown persons");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.UNKNOWN-subject", "");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.UNKNOWN-text", "Dear{PREFIXED_TITLE} {surname}{SUBSEQUENT_TITLE}");
			$this->setMessageContent("MAIL-OWN_REGISTRATION-description", "registration by user (not inside UserManagement)");
			$this->setMessageContent("MAIL-OWN_REGISTRATION-subject", "Registration on Website {HOST}");
			$this->setMessageContent("MAIL-OWN_REGISTRATION-text", "{FORM_ADDRESS}!\\n\\n".
														" Thank you that you want to register on our Web-Site {HOST}.\\n".
														" You have created the user '{user}'.\\n".
														" Please activate the Link {HOST}?regmail={pwd} for finishing.\\n".
														" This LINK is only {MAIL_MINUTES} available.\\n\\n".
														" {SIGNATURE}");
			$this->setMessageContent("MAIL-MAIL_REGISTRATION-description", "registration from ADMIN, hook 'send EMail' by creation/update of User");
			$this->setMessageContent("MAIL-MAIL_REGISTRATION-subject", "Registration on Website {HOST}");
			$this->setMessageContent("MAIL-MAIL_REGISTRATION-text", "{FORM_ADDRESS}!\\n\\n".
														" Please register on our Web-Site {HOST}.\\n".
														" It was created for you the user '{user}'.\\n".
														" Use this user account with the user-code \"{MAIL_CODE}\", without quotes, as password.\\n".
														" The user-code is {MAIL_DAYS} available.\\n\\n".
														" {SIGNATURE}");
			$this->setMessageContent("MAIL-ACKNOWLEDGE_INACTIVE-description", "acknowledge before account becoming active");
			$this->setMessageContent("MAIL-ACKNOWLEDGE_INACTIVE-subject", "Registration on {HOST}");
			$this->setMessageContent("MAIL-ACKNOWLEDGE_INACTIVE-text", "{FORM_ADDRESS}!\\n\\n".
														" Thank you for registering on our Web-Site {HOST}.\\n".
														" Please send an email to the ADMINISTRATOR_MAIL\\n".
														" to activate your account with the user '{user}'.\\n\\n".
														" {SIGNATURE}");
			$this->setMessageContent("MAIL-ACKNOWLEDGE_ACTIVE-description", "acknowledge for registration finish");
			$this->setMessageContent("MAIL-ACKNOWLEDGE_ACTIVE-subject", "Registration on {HOST}");
			$this->setMessageContent("MAIL-ACKNOWLEDGE_ACTIVE-text", "{FORM_ADDRESS}!\\n\\n".
														" Great pleasure, you registered on our Web-Sitee {HOST} successfully.\\n".
														" Your account with the user '{user}' is now available.\\n\\n".
														" {SIGNATURE}");
		}
	}
	protected function installContainer()
	{
		$instance= &STSession::instance();
		
		// create custom domain database entry
		$domain= $instance->getCustomDomain();
		
		$overview= $instance->getDbProjectName("ProjectOverview");
		$profile= $instance->getDbProjectName("UserProfile");
		$usermanagement= $instance->getDbProjectName("UserManagement");
		
		$this->createCluster($instance->allAdminCluster, $overview, "access to all exist CLUSTERs in every project", /*addGroup*/false);
		$this->createCluster($instance->profile_ChangeAccessCluster, $profile, "access to own profile data", /*addGroup*/false);
		$this->createCluster($instance->usermanagement_Project_AccessCluster, $usermanagement, "Permission to see all projects inside UserManagement", /*addGroup*/false);
		$this->createCluster($instance->usermanagement_Project_ChangeCluster, $usermanagement, "Permission to create projects inside  UserManagement", /*addGroup*/false);
		$this->createCluster($instance->usermanagement_User_AccessCluster, $usermanagement, "Permission to see all user inside UserManagement", /*addGroup*/false);
		$this->createCluster($instance->usermanagement_User_ChangeCluster, $usermanagement, "Permission to create/modify user inside UserManagement", /*addGroup*/false);
		$this->createCluster($instance->usermanagement_Group_AccessCluster, $usermanagement, "Ability to see all permission groups", /*addGroup*/false);
		$this->createCluster($instance->usermanagement_Group_ChangeCluster, $usermanagement, "Ability to change groups affiliation", /*addGroup*/false);
		$this->createCluster($instance->usermanagement_Cluster_ChangeCluster, $usermanagement, "Ability to create new clusters for a project", /*addGroup*/false);
		$this->createCluster($instance->usermanagement_Log_AccessCluster, $usermanagement, "Permission to see all logged affiliations", /*addGroup*/false);
		
		 
    	$this->createGroup($instance->allAdminGroup, $domain['Name']);
    	$this->createGroup($instance->onlineGroup, $domain['Name']);
    	$this->createGroup($instance->loggedinGroup, $domain['Name']);
    	$this->createGroup($instance->usermanagementAccessGroup, $domain['Name']);
    	$this->createGroup($instance->usermanagementAdminGroup, $domain['Name']);
		
		$this->joinClusterGroup($instance->allAdminCluster, $instance->allAdminGroup);
		$this->joinClusterGroup($instance->profile_ChangeAccessCluster, $instance->loggedinGroup);
    	$this->joinClusterGroup($instance->usermanagement_Project_AccessCluster, $instance->usermanagementAccessGroup);
    	$this->joinClusterGroup($instance->usermanagement_Project_ChangeCluster, $instance->usermanagementAdminGroup);
		$this->joinClusterGroup($instance->usermanagement_Cluster_ChangeCluster, $instance->usermanagementAdminGroup);
    	$this->joinClusterGroup($instance->usermanagement_User_AccessCluster, $instance->usermanagementAccessGroup);
    	$this->joinClusterGroup($instance->usermanagement_User_ChangeCluster, $instance->usermanagementAdminGroup);
		
		

		// select all needed tabels for an join
		// from table-cluster to table-user
		$this->getTable("Cluster");
		$this->getTable("ClusterGroup");

		$user= $this->getTable("User");
		//$user->clearSelects();
		//$user->clearGetColumns();
		//$user->count();
		$selector= new STDbSelector($user);
		$selector->select("User", "email");
		$selector->joinOver("Group");
		$where= new STDbWhere("ID='".$instance->allAdminCluster."'");
		//$where->andWhere("domain=$defaultDomainKey");
		$where->table("Cluster");
		$selector->where($where);
		$selector->execute();
		$admin_email= $selector->getSingleResult();
		if(!isset($admin_email))
		{
			$db= &$instance->getUserDb();
			$creator= new STSiteCreator($db);
			$creator->setMainContainer("um_install");
			//STCheck::debug(false);
			$result= $creator->execute();
			if($result=="NOERROR")
			{
				$desc= &STDbTableDescriptions::instance($this->getDatabase()->getDatabaseName());
				$userName= $desc->getColumnName("User", "user");
				$pwd= $desc->getColumnName("User", "Pwd");
				$container= &$creator->getContainer("um_install");
				$sqlResult= $container->getResult();
				$password= $sqlResult[$pwd];
				$preg= array();
				preg_match("/^password\('(.+)'\)$/", $password, $preg);
				$password= $preg[1];
				$userId= $this->db->getLastInsertID();
				$this->joinUserGroup($userId, $instance->allAdminGroup);
				if(!STUserSession::sessionGenerated())
					$instance->registerSession();
				$instance->acceptUser($sqlResult[$userName], $password);
				$instance->setProperties( $overview );
			}
			$creator->display();
			exit;
		}		
		

		// create hompage-link for mail variable {HOST}
		if(!isset($_SERVER['HTTP_REFERER']))
		{
			if(isset($_SERVER['REQUEST_SCHEME']))
				$sheme= $_SERVER['REQUEST_SCHEME'];
			else
				$sheme= "http";
			$HOST= $sheme."://";
			
			if(isset($_SERVER['SCRIPT_NAME']))
				$HOST.= $_SERVER['SCRIPT_NAME'];
		}else
		{// maybe class used inside PHP_CLI script like xdebug
			$ref= preg_split("$[\\/]$", $_SERVER['HTTP_REFERER']);
			$sheme= reset($ref);
			$HOST= $sheme."//";
			$HOST.= $_SERVER['HTTP_HOST'];
			if(isset($_SERVER['SCRIPT_NAME']))
				$HOST.= $_SERVER['SCRIPT_NAME'];
		}
		// read administrator email address
		if(!isset($admin_email))
			$admin_email= "example@".$_SERVER['HTTP_HOST'];

		$cases= array(	"HOST",
						"ADMINISTRATOR_MAIL",
						"MAIL_CODE",
						"MAIL_MINUTES",
						"MAIL_DAYS",
						"SIGNATURE",
						"PREFIXED_TITLE",
						"SUBSEQUENT_TITLE",
						"FORM_ADDRESS.sex.FEMALE",
						"FORM_ADDRESS.sex.MALE",
						"FORM_ADDRESS.sex.*GENDER",
						"FORM_ADDRESS.sex.UNKNOWN",
						"OWN_REGISTRATION",
						"MAIL_REGISTRATION",
						"ACKNOWLEDGE_INACTIVE",
						"ACKNOWLEDGE_ACTIVE"				);
		$mail= $this->getTable("Mail");
		$select= new STDbSelector($mail);
		$select->allowQueryLimitation(false);
		$select->select("Mail", "case");
		$select->execute(noErrorShow);
		$caseRes= $select->getSingleArrayResult();
		$insert= new STDbInserter($mail);

		foreach($cases as $case)
		{
			if(!in_array($case, $caseRes))
			{
				$insert->fillColumn("case", "'$case'");
				switch($case)
				{
					case "HOST":
						$insert->fillColumn("description", $this->msgBox->getMessageContent("MAIL-HOST-description"));
						$insert->fillColumn("subject", $this->msgBox->getMessageContent("MAIL-HOST-subject"));
						$insert->fillColumn("html", "NO");
						$insert->fillColumn("text", $HOST);
						break;
					case "ADMINISTRATOR_MAIL":
						$insert->fillColumn("description", $this->msgBox->getMessageContent("MAIL-ADMINISTRATOR_MAIL-description"));
						$insert->fillColumn("subject", $this->msgBox->getMessageContent("MAIL-ADMINISTRATOR_MAIL-subject"));
						$insert->fillColumn("html", "NO");
						$insert->fillColumn("text", $admin_email);
						break;
					default:
						$insert->fillColumn("description", $this->msgBox->getMessageContent("MAIL-$case-description"));
						$insert->fillColumn("subject", $this->msgBox->getMessageContent("MAIL-$case-subject"));
						$insert->fillColumn("html", "NO");
						$insert->fillColumn("text", $this->msgBox->getMessageContent("MAIL-$case-text"));
						break;
				}
				$insert->fillNextRow();
			}
		}
		$insert->execute(noErrorShow);
	}
}

?>