<?php

class STUserSideCreator extends STSessionSideCreator
{
	var	$sProject;
	var $nProject;
	var	$sUserTablePrefix;
	var	$bDoInstall= false;

	function __construct($projectNameNr, $container= null)
	{
		STCheck::paramCheck($projectNameNr, 1, "string", "int");
		STCheck::paramCheck($container, 2, "STBaseContainer", "null");

		// if projectName is an integer
		// define also sProject with it,
		// because by invoke initSession
		// the function read the name from database
		$this->sProject= $projectNameNr;
		if(is_numeric($projectNameNr))
			$this->nProjectID= $projectNameNr;
		STSessionSideCreator::__construct($container);
	}
	function initSession($userDb= null)
	{
		global	$PHP_SELF;

		Tag::alert(!$userDb&&!$this->db, "STUserSideCreator::initSession()",
									"before invoke initSession() without DB set DB with setMainContainer()");
		if($userDb)
		{
			// alex 11/09/2005:	da die DB keine Referenz ist
			//					diesen aus der liste holen
			$dbName= $userDb->getName();
			$this->userDb= &STDbTableContainer::getContainer($dbName);
		}else
			$this->userDb= &$this->db;

		if(!STSession::sessionGenerated())
			STUserSession::init($this->userDb, $this->sUserTablePrefix);
   		$this->userManagement= &STUserSession::instance();

		if($this->bDoInstall)
		{
			$this->bDoInstall= false;
			STSideCreator::install();
		}
		$this->userManagement->registerSession();
		$project= $this->getProjectID();
		$this->userManagement->verifyLogin($project);
		$project= $this->userManagement->getProjectName();
		//echo "set UserLoginMask ".$this->sUserLoginMask." in UserManagement<br />";
		if($this->sUserLoginMask)
			$this->userManagement->setUserLoginMask($this->sUserLoginMask);

		$identifier= "<h1><em>Projekt</em> <font color='red'>";
		$identifier.= $project."</font></h1>";
		$this->setProjectDisplayName($identifier);

		$descriptions= &STDbTableDescriptions::instance($this->userDb->getName());
		$nrColumn= $descriptions->getColumnName("Query", "ID");
		$pathColumn= $descriptions->getColumnName("Query", "path");
		$queryTable= &$this->userDb->getTable("Query");
		STQueryString::setQueryTable($queryTable, $nrColumn, $pathColumn);
		STQueryString::globaly_noStgetNr(session_name());
		$param= new STQueryString();
		Tag::echoDebug("user", "table for querystring is be set");
		// alex 02/05/2005:	entfernt, da var $startPage ja nicht gesetzt wird
		//$this->setStartPage($this->userManagement->getStartPage());
	}
	function getProjectID()
	{
		if($this->nProjectID===null)
			$this->nProjectID= 2;
		$this->bAskForProject= true;
		return $this->nProjectID;
	}
	function install()
	{
		STCheck::warning(STUserSession::sessionGenerated(), "STUserSideCreator::install()", "invoke this function before initSession()");
		$this->bDoInstall= true;
	}
	function setPrefixForUserTables($prefix)
	{
		Tag::alert($this->userManagement, "STUserSideCreator::setPrefixToTables()",
											"you must invoke this function before initSession()");
		$this->sUserTablePrefix= $prefix;
	}
		function &getUserManagement()
		{
			return $this->userManagement;
		}
		function authorisationBy($authorisation, $forTable= "-all", $access= ACCESS)
		{
			if(!isset($this->aAuthorisation[$forTable]))
				$this->aAuthorisation[$forTable]= array();
			$this->aAuthorisation[$forTable][$access]= $authorisation;
		}
		function accessBy($clusters, $forTable= "-all", $access= STACCESS)
		{
			if(!isset($this->aAccessClusters[$forTable]))
				$this->aAccessClusters[$forTable]= array();
			$this->aAccessClusters[$forTable][$access]= $clusters;
		}
		// alex 06/05/2005:	funktion access ausdokumnentiert
		//					da ja das hasAccess, welches ich heraufholte,
		//					schon existierte.
		/*function access($clusterString, $toAccessInfoString= "", $customID= null)
		{
			return $this->userManagement->hasAccess($clusterString, $toAccessInfoString, $customID, true);
		}*/
		function hasAccess($clusters, $toAccessInfoString= "", $customID= null, $makeError= false, $action= STALLDEF)
		{
			Tag::alert(!$this->userManagement, "STUserSideCreator::hasAccess()",
											"you must invoke before this function initSession()");
			Tag::paramCheck($clusters, 1, "string", "array");
			Tag::paramCheck($toAccessInfoString, 2, "string", "empty(string)", "null");
			Tag::paramCheck($customID, 3, "int", "null");

			if(is_string($clusters))
				return $this->userManagement->hasAccess($clusters, $toAccessInfoString, $customID, $makeError, $action);

			//st_print_r($clusters);
			$bOk= false;
			foreach($clusters as $cluster)
			{
			    if( is_String($cluster)
					    and
							$cluster!==""        )
					{
				      if(!$this->userManagement->hasAccess($cluster, $toAccessInfoString, $customID, $makeError, $action))
					        return false;
				      $bOk= true;
					}
			}
			/*if(!$bOk)
			{// if no cluster in the array
			 // ask for allAdmin, if the $makeError is true
			 // to logout
			    $this->userManagement->hasAccess("allAdmin", $toAccessInfoString, $customID, $makeError);
			}*/
			return true;
		}
}

?>