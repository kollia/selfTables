<?php

$_email= load_pluginModule("email");
$_editors= load_pluginModule("editors");
require_once($_stdbinserter);
require_once($_stsitecreator);
require_once($_stuserclustergroupmanagement);
require_once($_email['_stemail']);
require_once($_editors['_tinymce']);
require_once($_st_registration_text);

global $__global_defined_password_callback_function;
$__global_defined_password_callback_function= "usermanagement_passwordCheckCallback";
global $__email_text_cases;
$__email_text_cases= array(	"HOST",
							"HOST_NAME",
							"HOST_ADDRESS",
							"ADMINISTRATION_NAME",
							"ADMINISTRATION_MAIL",
							"MAIL_CODE",
							"MAIL_MINUTES",
							"MAIL_DAYS",
							"REMOVE_MONTH",
							"SIGNATURE",
							"PREFIXED_TITLE",
							"SUBSEQUENT_TITLE",
							"FORM_ADDRESS.sex.FEMALE",
							"FORM_ADDRESS.sex.MALE",
							"FORM_ADDRESS.sex.*GENDER",
							"FORM_ADDRESS.sex.UNKNOWN",
							"STORED_USER_DATA",
							"PRINT_STORED_USER_DATA",
							"OWN_REGISTRATION",
							"MAIL_REGISTRATION",
							"ACKNOWLEDGE_INACTIVE",
							"WEBSITE_ACKNOWLEDGE_INACTIVE",
							"ACKNOWLEDGE_ACTIVE"				);


function mail_allowNewCaseCallback(STCallbackClass &$callbackObject, $columnName, $rownum)
{
	if(	$callbackObject->display ||
		$callbackObject->before == false )
	{
		return;
	}
	$case= $callbackObject->getValue("Case");
	if(preg_match("/[^_A-ZÜÖÄ]+/", $case))
		return "for column 'Case' only big letters and underscores allowed";
}
function mail_allowRemovingCallback(STCallbackClass &$callbackObject, $columnName, $rownum)
{
	if($callbackObject->display)
	{
		global $__email_text_cases;

		$case= $callbackObject->getValue("Case");
		if($case == "MAIL_CODE")
		{
			$callbackObject->noUpdate();
			$callbackObject->noDelete();
		}elseif(in_array($case, $__email_text_cases))
		{
			$callbackObject->noDelete();
		}
	}
}
function mail_disableColumnCallback(STCallbackClass &$callbackObject, $columnName, $rownum)
{
	if($callbackObject->display)
	{
		global $__email_text_cases;

		$case= $callbackObject->getValue("case");
		if(in_array($case, $__email_text_cases))
		{
			$callbackObject->table->disabled("Case");
			$field= $callbackObject->table->findColumnOrAlias("subject");
			if($columnName == $field['alias'])
			{
				if(	$case != "MAIL_MINUTES" &&
					$case != "MAIL_DAYS" &&
					$case != "OWN_REGISTRATION" &&
					$case != "MAIL_REGISTRATION" &&
					$case != "ACKNOWLEDGE_INACTIVE" &&
					$case != "ACKNOWLEDGE_ACTIVE"		)
				{
					$callbackObject->disabled($columnName);
				}
			}else
				$callbackObject->disabled($columnName);
				$callbackObject->disabled("HTML");
		}
	}
}
function emailCallback(STCallbackClass &$callbackObject, $columnName, $rownum)
{
	//$callbackObject->echoResult();
	if($callbackObject->display)
		return;

	//$callbackObject->echoResult();
	$action= $callbackObject->getAction();
	$send= $callbackObject->getValue("sending");
	if($callbackObject->before)
	{// BEFORE any changing of database -------------------------------------------------------------------------------
		if($action == STINSERT)
		{// flip ACTIVE handle, because by insert ask whether should create a Dummy user
		 // and it should revert for active state
			$active= $callbackObject->getValue("active");
			if(	isset($active) && // active is dummy user
				$active == "YES"	)
			{// write dummy user
				$callbackObject->setValue("NO", "active");
			}else
			{
				if(	isset($send) &&
					$send == "yes"	)
				{// write inactive user for registration (sending EMail)
					$callbackObject->setValue("NO", "active");

				}else// write active user with password
					$callbackObject->setValue("YES", "active");
			}			
		}
		if(	isset($send) &&
			$send == "yes"	)
		{
			// create user-code
			$code= random_int(1000000000, 9999999999);
			$callbackObject->setValue("SENDMAIL", "register");
			$callbackObject->setValue($code, "regcode");
			$callbackObject->setValue("now()", "sendingtime");
		}
		return;
	}
	// AFTER changing of database -------------------------------------------------------------------------------------
	if(	isset($send) &&
		$send == "yes"	)
	{
		$oUserTable= $callbackObject->getTable();

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
		$newdbvalue['regcode']= $callbackObject->getValue("regcode");

		// create EMail text
		$mail= get_db_mail_text($callbackObject->table, "MAIL_REGISTRATION", $newdbvalue);

		$toEmail= $callbackObject->getValue("email");		
		$oMail= new STEmail(/*exception*/false);
		if(	!$oMail->init($mail['admin']) ||
			!$oMail->sendmail($toEmail, $mail['subject'], $mail['text'], $mail['html'])	)
		{
			$error= $oMail->getErrorString();
			return $error;
		}
		$action= $callbackObject->getAction();
		if($action == STUPDATE)
		{
			// clear password if exist
			$table= $callbackObject->getTable("User");
			$table->allowQueryLimitation(true);
			$remover= new STDbUpdater($table);
			$remover->update("Pwd", "");
			$remover->execute();
		}
	}
}
/**
 * Callback function to disable or enable field columns inside User table.
 * 
 * @param STCallbackClass $callbackObject 
 */
function disableUserFieldsCallback(STCallbackClass &$callbackObject, $columnName, $rownum)
{
	if(!$callbackObject->display)
		return;
	
	$action= $callbackObject->getAction();
	if($action != STUPDATE)
		return;
	//$column= $callbackObject->setColumnToUnderlinedAliasIfNecessary("active", /*underline*/false);
	$field= $callbackObject->table->searchByColumn("active");
	if(	$columnName == $field['alias'] &&
		$callbackObject->getValue("Pwd") == ""	)
	{
		$callbackObject->disabled($columnName);
	}
}
function disablePasswordCallback(STCallbackClass &$callbackObject, $columnName, $rownum)
{
	//$callbackObject->echoResult();
    if(!$callbackObject->before)
        return;

    $session= STUSerSession::instance();
    $domain= $session->getCustomDomain();
    //$table= $callbackObject->getTable();
    $domainValue= $callbackObject->getValue("domain");
    
    if(	(	is_string($domainValue) &&
			$domainValue != $domain['Name']	) ||
		(	is_numeric($domainValue) &&
			$domainValue != $domain['ID']	)	)
	{
        $callbackObject->disabled($columnName);
	}
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
    /**
     * struct of genaral registration properties.<br />
     * outsideRegistration - whether an user can also register by him self from outside<br />
     * adminActivation - whether the administrator need to activate after registration the user
     * dummyUser - whether is allowed to create an dummy user how have no password and cannot login
     * @var array
     */
    private $registrationProperties= array( 'outsideRegistration' =>    true,
                                            'adminActivation' =>        false,
                                            'dummyUser' =>              true    );

	function __construct(string $name, STObjectContainer &$container)
	{
		STObjectContainer::__construct($name, $container);
	}
    /**
     * Administrator need to have activate user after registration
     */
    public function needAdminActivation()
    {
        $this->registrationProperties['adminActivation']= true;
        $this->registrationProperties['dummyUser']= false;
    }
    /**
     * It should be not allowed to create an dummy user
     * how have no password and cannot login
     * 
     * @param bool $bAllow whether should allow an dummy user
     */
    public function allowDummyUser(bool $bAllow= true)
    {
        $this->registrationProperties['dummyUser']= $bAllow;
    }
	protected function create()
	{//STCheck::debug("db.statement.from", 48);STCheck::debug("db.statements.where");
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

	    $mail= &$this->getTable("Mail");
		if($table == $mail->getName())
		{
			$mail->select("case", "Case");
			$mail->select("html", "HTML");
			$mail->align("html", "center");
			$mail->select("description", "Description");
			if($action != STLIST)
			{
				//STCheck::debug();
				//$tinyMCE= new TinyMCE();
				//$tinyMCE->elements("mcetext");
				$mail->select("subject", "Subject");
				$mail->select("html", "allow HTML");
				$mail->select("text", "Text");//, $tinyMCE);
				$mail->insertCallback("mail_allowNewCaseCallback", "case");
				$mail->updateCallback("mail_disableColumnCallback", "case");
				$mail->updateCallback("mail_disableColumnCallback", "subject");
				$mail->updateCallback("mail_disableColumnCallback", "html");
				$mail->updateCallback("mail_disableColumnCallback", "description");
			}else
				$mail->listCallback("mail_allowRemovingCallback", "case");
		}
		
	    $user= &$this->needTable("User");
		if($table == $user->getName())
		{
			$user->select("domain", "Domain");
			$user->preSelect("domain", $domain['ID']);
			$user->disabled("domain");
			$user->select("sex", "Sex");
			$user->pullDownMenuByEnum("sex");
			$user->select("user", "Nickname");
			if($action == STLIST)
				$user->select("active", "active");
			$user->select("title_prefixed", "Title");
			$user->pullDownMenuByEnum("title_prefixed");
			$user->select("firstname", "first Name");
			$user->select("surname", "Surname");
			$user->select("title_subsequent", "Title subsequent");
			$user->pullDownMenuByEnum("title_subsequent");
			$user->select("email", "Email");	
			if( $action == STINSERT ||
				$action == STUPDATE	)
			{
				if($action == STINSERT)
				{
					if($this->registrationProperties['dummyUser'])
						$user->select("active", "Dummy user");
					$user->preSelect("active", "NO");
				}else
					$user->select("active", "active User");	
				$user->addColumn("sending", "SET('no', 'yes')", /*NULL*/false);
				$user->select("sending", "send EMail");

				$func= new jsFunction("disableFieldsOnClick", "action", "change");
				$activeColumn= $user->defineDocumentItemBoxName("active");
				$sendingColumn= $user->defineDocumentItemBoxName("sending");
				$pwdColumn= $user->defineDocumentItemBoxName("Pwd");
				$re_pwdColumn= $user->defineDocumentItemBoxName("re_Pwd");
				$globalset= <<<EOT
					activeDisabled= document.getElementsByName('$activeColumn')[0].disabled;
					activeChecked= document.getElementsByName('$activeColumn')[0].checked;
EOT;
				$disableContent= <<<EOT
					active= document.getElementsByName('$activeColumn')[0];
					sending= document.getElementsByName('$sendingColumn')[0];
					pwd= document.getElementsByName('$pwdColumn')[0];
					re_pwd= document.getElementsByName('$re_pwdColumn')[0];
					
					if(active.checked && change=='active')
					{
						if(action == "insert")
						{
							pwd.value= "";
							re_pwd.value= "";
						}else // action == "update"
							return;
						
						// disable sending with password
						sending.checked= false;
						sending.disabled= true;
						pwd.disabled= true;
						re_pwd.disabled= true;
					}
					if(sending.checked && change=='sending')
					{
						// disable active with password
						active.checked= false;
						active.disabled= true;
						pwd.disabled= true;
						re_pwd.disabled= true;
						pwd.value= "";
						re_pwd.value= "";
					}
					if(	!active.checked &&
						!sending.checked	)
					{
						// enable all fields
						if(change != "active")
							active.checked= activeChecked;
						if(!activeDisabled)
							active.disabled= false;
						sending.disabled= false;
						pwd.disabled= false;
						re_pwd.disabled= false;
					}

EOT;				
					$func->add($disableContent);
				$script= new JavascriptTag();
					$script->add($globalset);
					$script->add($func);
				$this->addOnBodyEnd($script);
				
				//if($action == STINSERT)
				$user->onChange("active", "disableFieldsOnClick('$action', 'active')");
				$user->onChange("sending", "disableFieldsOnClick('$action', 'sending')");
				
				$user->select("Pwd", "Pwd");
				$user->password("Pwd", true);
				$user->optional("Pwd");
				//$oldpass= "old password";
				//$user->passwordNames($oldpass, $newpass, $reppass);	
				$user->passwordNames($newpass, $reppass);		
				$user->updateCallback("disablePasswordCallback", $newpass);
				$user->updateCallback("disablePasswordCallback", $reppass);
				$user->updateCallback("disablePasswordCallback", $username);
				$user->updateCallback("usermanagement_main_passwordCheckCallback", $newpass);
				$user->insertCallback("usermanagement_main_passwordCheckCallback", $newpass);
				$user->updateCallback("emailCallback", "sending");
				$user->insertCallback("emailCallback", "sending");
				$user->updateCallback("disableUserFieldsCallback", "active");
				
			}elseif($action==STLIST)
			{
				//$user->select("NrLogin", "logged in");
				//$user->select("LastLogin", "last login");
				$user->orderBy("domain");
				$user->orderBy("user");
				$user->setMaxRowSelect(50);
			}
			//$user->getColumn("register");
			$user->preSelect("DateCreation", "sysdate()");
		}
		
		$groups= &$this->getTable("Group");
		if($table == $groups->getName())
		{
			$groups->select("domain", "Domain");
			$groups->preSelect("domain", $domain['Name']);
			$groups->disabled("domain");
			$groups->preSelect("DateCreation", "sysdate()");
			$groups->select("Name", "Group");
			if($action==STLIST)
			{
				$groups->select("ID", "access descriptions");
				$groups->listCallback("descriptionCallback", "access descriptions");
				//$groups->listCallback("actionCallback", "update");
				$groups->listCallback("actionCallback", "delete");
				$groups->orderBy("domain");
				$groups->orderBy("Name");
				$groups->setMaxRowSelect(50);
			}
		}
		
		$project= &$this->getTable("Project");
		if($table == $project->getName())
		{
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
				$userClusterGroup= $this->getContainer("UserClusterGroupManagement");
				$project->namedLink("Project", $userClusterGroup);
			}
		}
	}
	/**
	 * Define new callback function
	 * to check whether password is correct.<br />
	 * New function need to have follow parameters:<br />
	 * <callback function>(bool $display, string $onCase, string $password) : bool|string|Tag<br />
	 * - $display	- if true only for display error when $password no null string<br />
	 * - $onCase	- 'insert' or 'update' if password check from usermanagement<br />
	 *				  'registration' if the password check will be done from outside<br />
	 * - $password	- current inserted password from user
	 * 
	 * @param string $functionName name of new function
	 */
	public function setNewPasswordCallback(string $functionName)
	{
		global $__global_defined_password_callback_function;

		$__global_defined_password_callback_function= $functionName;
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
		    $this->setMessageContent("MAIL-HOST_NAME-description", "name of Website");
		    $this->setMessageContent("MAIL-HOST_NAME-subject", "");
		    $this->setMessageContent("MAIL-HOST_NAME-text", "");
		    $this->setMessageContent("MAIL-HOST_ADDRESS-description", "address of Website");
		    $this->setMessageContent("MAIL-HOST_ADDRESS-subject", "");
		    $this->setMessageContent("MAIL-HOST_ADDRESS-text", "");
		    $this->setMessageContent("MAIL-HOST-description", "displayed Website");
		    $this->setMessageContent("MAIL-HOST-subject", "");
		    $this->setMessageContent("MAIL-HOST-text", "{HOST_ADDRESS}");
		    $this->setMessageContent("MAIL-HOST-HTML_text", "<a href='{HOST_ADDRESS}'>{HOST_NAME}</a>");
		    $this->setMessageContent("MAIL-ADMINISTRATION_NAME-description", "name of administrator who handles the registration process");
		    $this->setMessageContent("MAIL-ADMINISTRATION_NAME-subject", "");
		    $this->setMessageContent("MAIL-ADMINISTRATION_NAME-text", "Administration");
		    $this->setMessageContent("MAIL-ADMINISTRATION_MAIL-description", "email address from administrator who handles the registration process");
		    $this->setMessageContent("MAIL-ADMINISTRATION_MAIL-subject", "");
		    $this->setMessageContent("MAIL-ADMINISTRATION_MAIL-text", "");
			$this->setMessageContent("MAIL-ADMINISTRATION_MAIL-HTML_text", "<a href='mailto:{ADMINISTRATION_MAIL}'>{ADMINISTRATION_NAME}</a>");
			$this->setMessageContent("MAIL-MAIL_CODE-description", "generated numeric code when sending emails");
			$this->setMessageContent("MAIL-MAIL_CODE-subject", "");
			$this->setMessageContent("MAIL-MAIL_CODE-text", "{Pwd}");
			$this->setMessageContent("MAIL-MAIL_MINUTES-description", "how long the created account by own registration should be available");
			$this->setMessageContent("MAIL-MAIL_MINUTES-subject", "5");
			$this->setMessageContent("MAIL-MAIL_MINUTES-text", "{subject} Minuten");
			$this->setMessageContent("MAIL-MAIL_DAYS-description", "how long the created user-code by Admin mail should be available");
			$this->setMessageContent("MAIL-MAIL_DAYS-subject", "5");
			$this->setMessageContent("MAIL-MAIL_DAYS-text", "{subject} Tage");
			$this->setMessageContent("MAIL-REMOVE_MONTH-description", "after how much month user-data will be removed when registration process not be finished");
			$this->setMessageContent("MAIL-REMOVE_MONTH-subject", "1");
			$this->setMessageContent("MAIL-REMOVE_MONTH-text", "einem Monat");
			$this->setMessageContent("MAIL-SIGNATURE-description", "Signature by all sending emails");
			$this->setMessageContent("MAIL-SIGNATURE-subject", "");
			$this->setMessageContent("MAIL-SIGNATURE-text", "Ihr Software-Team\\n    von {HOST}");
			$this->setMessageContent("MAIL-PREFIXED_TITLE-description", "prefixed title only if exist");
			$this->setMessageContent("MAIL-PREFIXED_TITLE-subject", "");
			$this->setMessageContent("MAIL-PREFIXED_TITLE-text", " {title_prefixed}");
			$this->setMessageContent("MAIL-SUBSEQUENT_TITLE-description", "subsequint title only if exist");
			$this->setMessageContent("MAIL-SUBSEQUENT_TITLE-subject", "");
			$this->setMessageContent("MAIL-SUBSEQUENT_TITLE-text", ", {title_subsequent}");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.FEMALE-description", "Form of address for womans");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.FEMALE-subject", "");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.FEMALE-text", "Sehr geehrte Frau {PREFIXED_TITLE.exist} {surname}{SUBSEQUENT_TITLE.exist}");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.MALE-description", "Form of address for mens");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.MALE-subject", "");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.MALE-text", "Sehr geehrter Herr {PREFIXED_TITLE.exist} {surname}{SUBSEQUENT_TITLE.exist}");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.*GENDER-description", "form of address for gender neutral persons");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.*GENDER-subject", "");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.*GENDER-text", "Sehr geehrte(r/s) {PREFIXED_TITLE.exist} {surname}{SUBSEQUENT_TITLE.exist}");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.UNKNOWN-description", "form of address for unknown persons");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.UNKNOWN-subject", "");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.UNKNOWN-text", "Sehr geehrte(r)Frau/Herr {PREFIXED_TITLE.exist} {surname}{SUBSEQUENT_TITLE.exist}");
			
			$this->setMessageContent("MAIL-STORED_USER_DATA-description", "all user specific data stored on website");
			$this->setMessageContent("MAIL-STORED_USER_DATA-subject", "");
			$content= <<<EOT

	Geschlecht:        {sex}
	Titel:             {title_prefixed}
	Vorname:           {firstname}
	Nachname:          {surname}
	angehängter Titel: {title_subsequent}
	EMail:             {email}
EOT;
			$this->setMessageContent("MAIL-STORED_USER_DATA-text", $content);
			$html_content= <<<EOT
<table style='font-family: "Times New Roman", Garamond, serif; font-size: smaller;'>
	<tr>
		<td width="30"></td>
		<td bgcolor='gray' style='padding: 1mm;'>
		User:
		</td>
		<td bgcolor='lightgray' style='padding: 1mm;'>
			{user}
		</td>
	</tr>
	<tr>
		<td ></td>
		<td bgcolor='gray' style='padding: 1mm;'>
			Titel:
		</td>
		<td bgcolor='lightgray' style='padding: 1mm;'>
			{title_prefixed}
		</td>
	</tr>
	<tr>
		<td ></td>
		<td bgcolor='gray' style='padding: 1mm;'>
			Vorname:
		</td>
		<td bgcolor='lightgray' style='padding: 1mm;'>
			{firstname}
		</td>
	</tr>
	<tr>
		<td ></td>
		<td bgcolor='gray' style='padding: 1mm;'>
			Nachname:
		</td>
		<td bgcolor='lightgray' style='padding: 1mm;'>
			{surname}
		</td>
	</tr>
	<tr>
		<td ></td>
		<td bgcolor='gray' style='padding: 1mm;'>
			Titel:
		</td>
		<td bgcolor='lightgray' style='padding: 1mm;'>
			{title_subsequent}
		</td>
	</tr>
	<tr>
		<td width="30"></td>
		<td bgcolor='gray' style='padding: 1mm;'>
		Geschlecht:
		</td>
		<td bgcolor='lightgray' style='padding: 1mm;'>
			{sex}
		</td>
	</tr>
	<tr>
		<td ></td>
		<td bgcolor='gray' style='padding: 1mm;'>
			EMail:
		</td>
		<td bgcolor='lightgray' style='padding: 1mm;'>
			{email}
		</td>
	</tr>
</table>
EOT;
			$this->setMessageContent("MAIL-STORED_USER_DATA-HTML_text", $html_content);

			$this->setMessageContent("MAIL-PRINT_STORED_USER_DATA-description", "Printing sheet for all stored user data on website");
			$this->setMessageContent("MAIL-PRINT_STORED_USER_DATA-subject", "");
			$this->setMessageContent("MAIL-PRINT_STORED_USER_DATA-HTML_text", "Folgende User-Daten sind innerhalb unserer Webseite {HOST} gespeichert:<br />\\n".
														"<br />\\n".
														" {STORED_USER_DATA}");

			$this->setMessageContent("MAIL-OWN_REGISTRATION-description", "registration by user (not inside UserManagement)");
			$this->setMessageContent("MAIL-OWN_REGISTRATION-subject", "Registrierung auf der Web-Seite {HOST_NAME}");
			$this->setMessageContent("MAIL-OWN_REGISTRATION-text", "{FORM_ADDRESS}!\\n\\n".
														" Danke das sie sich auf unserer Web-Site {HOST} registrieren wollen.\\n".
														" Sie haben den Benutzer mit dem Namen '{user}' angelegt.\\n".
														" Bitte bestätigen sie ihre Registrierung mit dem Link {HOST}?regmail={pwd}.\\n".
														" Dieser LINK ist {MAIL_MINUTES} gültig.\\n\\n".
														" {SIGNATURE}");
			$this->setMessageContent("MAIL-MAIL_REGISTRATION-description", "registration from ADMIN, hook 'send EMail' by creation/update of User");
			$this->setMessageContent("MAIL-MAIL_REGISTRATION-subject", "Registration on Website {HOST_NAME}");
			$this->setMessageContent("MAIL-MAIL_REGISTRATION-text", "{FORM_ADDRESS}!\\n\\n".
														" Bitte registrieren sie sich auf unserer Web-Site {HOST}.\\n".
														" Es wurde für Sie der Benutzer mit dem Namen '{user}' angelegt.\\n".
														" verwenden Sie diesen mit dem user-code \"{MAIL_CODE}\", ohne Anführungszeichen, als Passwort.\\n".
														" Der user-code ist {MAIL_DAYS} gültig.\\n\\n".
														" Folgende Daten sind von Ihnen gespeichert:".
														" {STORED_USER_DATA}\\n\\n".
														" {SIGNATURE}");
			$this->setMessageContent("MAIL-WEBSITE_ACKNOWLEDGE_INACTIVE-description", "acknowledge of registration seen inside website while accoutn is inactive");
			$this->setMessageContent("MAIL-WEBSITE_ACKNOWLEDGE_INACTIVE-subject", "");
			$this->setMessageContent("MAIL-WEBSITE_ACKNOWLEDGE_INACTIVE-HTML_text", "{FORM_ADDRESS}!<br /><br />\\n".
														" Vielen Dank, dass sie sich auf unserer Webseite {HOST} registriert haben.<br />\\n".
														" Bitte senden sie eine EMail an {ADMINISTRATION_MAIL}<br />\\n".
														" um ihr Konto mit folgenden Benutzer-Daten freizuschalten.<br /><br />\\n".
														" {STORED_USER_DATA}<br /><br />\\n".
														" sollten Sie die Speicherung ihrer Daten nicht wünschen brauchen sie nichts weiter zu tun als keine EMail zu senden.<br />\\n".
														" Alle ihre Daten werden dann nach {REMOVE_MONTH} automatisch gelöscht.<br /><br />\\n".
														" {SIGNATURE}");
			$this->setMessageContent("MAIL-ACKNOWLEDGE_INACTIVE-description", "EMAIL acknowledge before account becoming active");
			$this->setMessageContent("MAIL-ACKNOWLEDGE_INACTIVE-subject", "Registration auf {HOST_NAME}");
			$this->setMessageContent("MAIL-ACKNOWLEDGE_INACTIVE-text", "{FORM_ADDRESS}!\\n\\n".
														" Vielen Dank, dass sie sich auf unserer Webseite {HOST} registriert haben.\\n".
														" Bitte senden sie eine EMail an {ADMINISTRATION_MAIL}\\n".
														" um ihr Konto mit folgenden Benutzer-Daten freizuschalten.\\n\\n".
														" {STORED_USER_DATA}\\n\\n".
														" sollten Sie die Speicherung ihrer Daten nicht wünschen brauchen sie nichts weiter zu tun als keine EMail zu senden.\\n".
														" Alle ihre Daten werden dann nach {REMOVE_MONTH} automatisch gelöscht.\\n\\n".
														" {SIGNATURE}");
			$this->setMessageContent("MAIL-ACKNOWLEDGE_ACTIVE-description", "acknowledge for registration");
			$this->setMessageContent("MAIL-ACKNOWLEDGE_ACTIVE-subject", "Registrierung auf der Webseite {HOST_NAME}");
			$this->setMessageContent("MAIL-ACKNOWLEDGE_ACTIVE-text", "{FORM_ADDRESS}!\\n\\n".
														" Vielen Dank, dass sie sich auf unserer Webseite {HOST} registriert haben.\\n".
														" Ihr Konto mit dem Benutzer '{user}' wurde frei geschalten.\\n\\n".
														" {SIGNATURE}");
			
		}else // otherwise language have to be english "en"
		{
		    $this->setMessageContent("MAIL-HOST_NAME-description", "name of Website");
		    $this->setMessageContent("MAIL-HOST_NAME-subject", "");
		    $this->setMessageContent("MAIL-HOST_NAME-text", "");
		    $this->setMessageContent("MAIL-HOST_ADDRESS-description", "address of Website");
		    $this->setMessageContent("MAIL-HOST_ADDRESS-subject", "");
		    $this->setMessageContent("MAIL-HOST_ADDRESS-text", "");
		    $this->setMessageContent("MAIL-HOST-description", "displayed Website");
		    $this->setMessageContent("MAIL-HOST-subject", "");
		    $this->setMessageContent("MAIL-HOST-text", "{HOST_ADDRESS}");
		    $this->setMessageContent("MAIL-HOST-HTML_text", "<a href='{HOST_ADDRESS}'>{HOST_NAME}</a>");
		    $this->setMessageContent("MAIL-ADMINISTRATION_NAME-description", "name of administrator who handles the registration process");
		    $this->setMessageContent("MAIL-ADMINISTRATION_NAME-subject", "");
		    $this->setMessageContent("MAIL-ADMINISTRATION_NAME-text", "Administration");
		    $this->setMessageContent("MAIL-ADMINISTRATION_MAIL-description", "email address from administrator who handles the registration process");
		    $this->setMessageContent("MAIL-ADMINISTRATION_MAIL-subject", "");
		    $this->setMessageContent("MAIL-ADMINISTRATION-description", "displayed administration who handles the registration process");
		    $this->setMessageContent("MAIL-ADMINISTRATION-subject", "");
		    $this->setMessageContent("MAIL-ADMINISTRATION-text", "Administration (mailto:{ADMINISTRATION_MAIL})");
			$this->setMessageContent("MAIL-ADMINISTRATION-HTML_text", "<a href='mailto:{ADMINISTRATION_MAIL}'>{ADMINISTRATION_NAME}</a>");
			$this->setMessageContent("MAIL-MAIL_CODE-description", "generated numeric code when sending emails");
			$this->setMessageContent("MAIL-MAIL_CODE-subject", "");
			$this->setMessageContent("MAIL-MAIL_CODE-text", "{Pwd}");
			$this->setMessageContent("MAIL-MAIL_MINUTES-description", "how long the created account by own registration should be available");
			$this->setMessageContent("MAIL-MAIL_MINUTES-subject", "5");
			$this->setMessageContent("MAIL-MAIL_MINUTES-text", "{subject} minutes");
			$this->setMessageContent("MAIL-MAIL_DAYS-description", "how long the created user-code by Admin mail should be available");
			$this->setMessageContent("MAIL-MAIL_DAYS-subject", "5");
			$this->setMessageContent("MAIL-MAIL_DAYS-text", "{subject} days");
			$this->setMessageContent("MAIL-REMOVE_MONTH-description", "after how much month user-data will be removed when registration process not be finished");
			$this->setMessageContent("MAIL-REMOVE_MONTH-subject", "1");
			$this->setMessageContent("MAIL-REMOVE_MONTH-text", "one month");
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
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.FEMALE-text", "Dear Mrs.{PREFIXED_TITLE.exist} {surname}{SUBSEQUENT_TITLE.exist}");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.MALE-description", "form of address for mens");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.MALE-subject", "");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.MALE-text", "Dear Mr.{PREFIXED_TITLE.exist} {surname}{SUBSEQUENT_TITLE.exist}");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.*GENDER-description", "form of address for gender neutral persons");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.*GENDER-subject", "");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.*GENDER-text", "Dear {PREFIXED_TITLE.exist} {surname}{SUBSEQUENT_TITLE.exist}");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.UNKNOWN-description", "form of address for unknown persons");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.UNKNOWN-subject", "");
			$this->setMessageContent("MAIL-FORM_ADDRESS.sex.UNKNOWN-text", "Dear {PREFIXED_TITLE.exist} {surname}{SUBSEQUENT_TITLE.exist}");
			
			$this->setMessageContent("MAIL-STORED_USER_DATA-description", "all user specific data stored on website");
			$this->setMessageContent("MAIL-STORED_USER_DATA-subject", "");
			$content= <<<EOT

	Sex:              {sex}
	prefixed title:   {title_prefixed}
	first name:       {firstname}
	surname:          {surname}
	subsequent title: {title_subsequent}
	EMail:            {email}
EOT;
			$this->setMessageContent("MAIL-STORED_USER_DATA-text", $content);
			$html_content= <<<EOT
<table style='font-family: "Times New Roman", Garamond, serif; font-size: smaller;'>
	<tr>
		<td width="30"></td>
		<td bgcolor='gray' style='padding: 1mm;'>
			User:
		</td>
		<td bgcolor='lightgray' style='padding: 1mm;'>
			{user}
		</td>
	</tr>
	<tr>
		<td ></td>
		<td bgcolor='gray' style='padding: 1mm;'>
			prefixed title:
		</td>
		<td bgcolor='lightgray' style='padding: 1mm;'>
			{title_prefixed}
		</td>
	</tr>
	<tr>
		<td ></td>
		<td bgcolor='gray' style='padding: 1mm;'>
			first name:
		</td>
		<td bgcolor='lightgray' style='padding: 1mm;'>
			{firstname}
		</td>
	</tr>
	<tr>
		<td ></td>
		<td bgcolor='gray' style='padding: 1mm;'>
			surname:
		</td>
		<td bgcolor='lightgray' style='padding: 1mm;'>
			{surname}
		</td>
	</tr>
	<tr>
		<td ></td>
		<td bgcolor='gray' style='padding: 1mm;'>
			subsequent title:
		</td>
		<td bgcolor='lightgray' style='padding: 1mm;'>
			{title_subsequent}
		</td>
	</tr>
	<tr>
		<td width="30"></td>
		<td bgcolor='gray' style='padding: 1mm;'>
			Sex:
		</td>
		<td bgcolor='lightgray' style='padding: 1mm;'>
			{sex}
		</td>
	</tr>
	<tr>
		<td ></td>
		<td bgcolor='gray' style='padding: 1mm;'>
			EMail:
		</td>
		<td bgcolor='lightgray' style='padding: 1mm;'>
			{email}
		</td>
	</tr>
</table>
EOT;
			$this->setMessageContent("MAIL-STORED_USER_DATA-HTML_text", $html_content);

			$this->setMessageContent("MAIL-PRINT_STORED_USER_DATA-description", "Printing sheet for all stored user data on website");
			$this->setMessageContent("MAIL-PRINT_STORED_USER_DATA-subject", "");
			$this->setMessageContent("MAIL-PRINT_STORED_USER_DATA-HTML_text", "Follow user-data stored of our website {HOST}:<br />\\n".
														"<br />\\n".
														" {STORED_USER_DATA}");

			$this->setMessageContent("MAIL-OWN_REGISTRATION-description", "registration by user (not inside UserManagement)");
			$this->setMessageContent("MAIL-OWN_REGISTRATION-subject", "Registration on Website {HOST_NAME}");
			$this->setMessageContent("MAIL-OWN_REGISTRATION-text", "{FORM_ADDRESS}!\\n\\n".
														" Thank you that you want to register on our Web-Site {HOST}.\\n".
														" You have created the user '{user}'.\\n".
														" Please activate the Link {HOST}?regmail={pwd} for finishing.\\n".
														" This LINK is only {MAIL_MINUTES} available.\\n\\n".
														" {SIGNATURE}");
			$this->setMessageContent("MAIL-MAIL_REGISTRATION-description", "registration from ADMIN, hook 'send EMail' by creation/update of User");
			$this->setMessageContent("MAIL-MAIL_REGISTRATION-subject", "Registration on Website {HOST_NAME}");
			$this->setMessageContent("MAIL-MAIL_REGISTRATION-text", "{FORM_ADDRESS}!\\n\\n".
														" Please register on our Web-Site {HOST}.\\n".
														" It was created for you the user '{user}'.\\n".
														" Use this user account with the user-code \"{MAIL_CODE}\", without quotes, as password.\\n".
														" The user-code is {MAIL_DAYS} available.\\n\\n".
														" Follow data stored from you:\\n".
														" {STORED_USER_DATA}\\n\\n".
														" {SIGNATURE}");
			$this->setMessageContent("MAIL-ACKNOWLEDGE_INACTIVE-description", "acknowledge before account becoming active");
			$this->setMessageContent("MAIL-ACKNOWLEDGE_INACTIVE-subject", "Registration on {HOST_NAME}");
			$this->setMessageContent("MAIL-ACKNOWLEDGE_INACTIVE-text", "{FORM_ADDRESS}!\\n\\n".
														" Thank you for registering on our Web-Site {HOST}.\\n".
														" Please send an email to the {ADMINISTRATION_MAIL}\\n".
														" to activate your account with the follow data'.\\n\\n".
														" {STORED_USER_DATA}\\n\\n".
														" if you don't want that, do not send any email back and all your data will be removed after {REMOVE_MONTH}.\\n\\n".
														" {SIGNATURE}");
			$this->setMessageContent("MAIL-WEBSITE_ACKNOWLEDGE_INACTIVE-description", "acknowledge of registration seen inside website while accoutn is inactive");
			$this->setMessageContent("MAIL-WEBSITE_ACKNOWLEDGE_INACTIVE-subject", "");
			$this->setMessageContent("MAIL-WEBSITE_ACKNOWLEDGE_INACTIVE-HTML_text", "{FORM_ADDRESS}!<br /><br />\\n".
														" Thank you for registering on our Web-Site {HOST}.<br />\\n".
														" Please send an email to the {ADMINISTRATION_MAIL}<br />\\n".
														" to activate your account with the follow data'.<br /><br />\\n".
														" {STORED_USER_DATA}<br /><br />\\n".
														" if you don't want that, do not send any email back and all your data will be removed after {REMOVE_MONTH}.<br /><br />\\n".
														" {SIGNATURE}");
			$this->setMessageContent("MAIL-ACKNOWLEDGE_ACTIVE-description", "acknowledge for registration finish");
			$this->setMessageContent("MAIL-ACKNOWLEDGE_ACTIVE-subject", "Registration on {HOST_NAME}");
			$this->setMessageContent("MAIL-ACKNOWLEDGE_ACTIVE-text", "{FORM_ADDRESS}!\\n\\n".
														" Great pleasure, you registered on our Web-Sitee {HOST} successfully.\\n".
														" Your account with the user '{user}' is now available.\\n\\n".
														" {SIGNATURE}");
		}
	}
	protected function installContainer()
	{
		global $__email_text_cases;

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
		$selector->allowQueryLimitation(false);
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
				$container= &$creator->getContainer("um_install");
				$table= $container->getTable("User");
				$userName= $table->findColumnOrAlias("user")['alias'];
				$pwd= $table->findColumnOrAlias("Pwd")['alias'];
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
			$HOST_ADDRESS= $sheme."://";
			
			if(isset($_SERVER['SCRIPT_NAME']))
			{
				$HOST_NAME= $_SERVER['SCRIPT_NAME'];
				$HOST_ADDRESS.= $HOST_NAME;
			}
		}else
		{// maybe class used inside PHP_CLI script like xdebug
			$ref= preg_split("$[\\/]$", $_SERVER['HTTP_REFERER']);
			$sheme= reset($ref);
			$HOST_ADDRESS= $sheme."//";
			$HOST_NAME= $_SERVER['HTTP_HOST'];
			if(isset($_SERVER['SCRIPT_NAME']))
			{
				$HOST_NAME.= $_SERVER['SCRIPT_NAME'];
				$HOST_ADDRESS.= $HOST_NAME;
			}
		}
		// read administrator email address
		if(!isset($admin_email))
			$admin_email= "example@".$_SERVER['HTTP_HOST'];

		$mail= $this->getTable("Mail");
		$select= new STDbSelector($mail);
		$select->allowQueryLimitation(false);
		$select->select("Mail", "case");
		$select->execute(noErrorShow);
		$caseRes= $select->getSingleArrayResult();
		$insert= new STDbInserter($mail);

		foreach($__email_text_cases as $case)
		{
			if(!in_array($case, $caseRes))
			{
				$description= $this->msgBox->getMessageContent("MAIL-$case-description");
				$subject= $this->msgBox->getMessageContent("MAIL-$case-subject");
				switch($case)
				{
					case "HOST_NAME":
						$text= $HOST_NAME;
						break;
					case "HOST_ADDRESS":
						$text= $HOST_ADDRESS;
						break;
					case "ADMINISTRATION_NAME":
						$text= "Administrator";
						break;
					case "ADMINISTRATION_MAIL":
						$text= $admin_email;
						break;
					default:
						$text= $this->msgBox->getMessageContent("MAIL-$case-text");
						break;
				}
				if($text != "")
				{// placeholder for plain text
					$insert->fillColumn("case", "'$case'");
					$insert->fillColumn("description", $description);
					$insert->fillColumn("subject", $subject);
					$insert->fillColumn("html", "NO");
					$insert->fillColumn("text", $text);
					$insert->fillNextRow();
				}
				
				$text= $this->msgBox->getMessageContent("MAIL-$case-HTML_text");
				if($text != "")
				{// placeholder for HTML text
					$insert->fillColumn("case", "'$case'");
					$insert->fillColumn("description", $description);
					$insert->fillColumn("subject", $subject);
					$insert->fillColumn("html", "YES");
					$insert->fillColumn("text", $text);
					$insert->fillNextRow();	
				}
			}
		}
		$insert->execute(noErrorShow);
	}
}

?>