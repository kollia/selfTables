<?php

require_once( $_stsessionsitecreator);

class STUserSiteCreator extends STSessionSiteCreator
{
	var	$sProject;
	var $nProject;
	var	$sUserTablePrefix;

	function __construct($projectNameNr, $container= null)
	{
		STCheck::param($projectNameNr, 0, "string", "int");
		STCheck::param($container, 1, "STBaseContainer", "null");

		// if projectName is an integer
		// define also sProject with it,
		// because by invoke initSession
		// the function read the name from database
		$this->sProject= $projectNameNr;
		if(is_numeric($projectNameNr))
			$this->nProjectID= $projectNameNr;
		STSessionSiteCreator::__construct($container);
	}
	function initSession($userDb= null)
	{
		Tag::alert(!$userDb&&!$this->db, "STUserSiteCreator::initSession()",
									"before invoke initSession() without DB set DB with setMainContainer()");
		if($userDb)
		{
			// alex 11/09/2005:	Db name lost because object is no refference
			//					so take name from original database
			$dbName= $userDb->getName();
			$this->userDb= &STDbTableContainer::getContainer($dbName);
		}else
			$this->userDb= &$this->db;
			
		$this->userManagement= &STUserSession::instance();
		if(!isset($this->userManagement))
		{
		    $bSessionGenerated= false;
		    Tag::alert(!isset($this->sUserTablePrefix), "STUserSiteCreator::initSession()",
		        "you have to invoke method STUserSiteCreator::setPrefixToTables() before initSession()");
		    STUserSession::init($this->userDb, $this->sUserTablePrefix);
		    
		}else if(!STSession::sessionGenerated())
		    $bSessionGenerated= false;
	    else
	        $bSessionGenerated= true;
	        
        if($this->userManagement->noRegister)
            return;
/*		if($this->bDoInstall)
		{
			$this->bDoInstall= false;
			STSiteCreator::install();
		}*/
		if($bSessionGenerated)
		    return;
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

		$descriptions= &STDbTableDescriptions::instance($this->userDb->getDatabaseName());
		$nrColumn= $descriptions->getColumnName("Query", "ID");
		$pathColumn= $descriptions->getColumnName("Query", "path");
		$queryTable= &$this->userDb->getTable("Query");
		STQueryString::setQueryTable($queryTable, $nrColumn, $pathColumn);
		STQueryString::globaly_noStgetNr(session_name());
		Tag::echoDebug("user", "table for querystring is be set");
	}
	function getProjectID()
	{
		if($this->nProjectID===null)
			$this->nProjectID= 2;
		$this->bAskForProject= true;
		return $this->nProjectID;
	}
/*	function install()
	{
		STCheck::warning(STUserSession::sessionGenerated(), "STUserSiteCreator::install()", "invoke this function before initSession()");
		$this->bDoInstall= true;
	}*/
	function setPrefixForUserTables($prefix)
	{
		Tag::alert($this->userManagement, "STUserSiteCreator::setPrefixToTables()",
											"you must invoke this function before initSession()");
		$this->sUserTablePrefix= $prefix;
	}
		function &getUserManagement()
		{
			return $this->userManagement;
		}
		function hasAccess($clusters, $toAccessInfoString= "", $customID= null, $action= STALLDEF, $makeError= false)
		{
			Tag::alert(!$this->userManagement, "STUserSiteCreator::hasAccess()",
											"you must invoke before this function initSession()");
			Tag::param($clusters, 0, "string", "array");
			Tag::param($toAccessInfoString, 1, "string", "empty(string)", "null");
			Tag::param($customID, 2, "int", "null");

			if(is_string($clusters))
				return $this->userManagement->hasAccess($clusters, $toAccessInfoString, $customID, $action, $makeError);

			foreach($clusters as $cluster)
			{
			    if( is_String($cluster)
					    and
							$cluster!==""        )
					{
				      if(!$this->userManagement->hasAccess($cluster, $toAccessInfoString, $customID, $action, $makeError))
					        return false;
					}
			}
			return true;
		}
		protected function checkPermission()
		{
		    $this->initSession();
		    if(isset($this->aAccessClusters[STALLDEF]))
		    {
		        foreach($this->aAccessClusters[STALLDEF] as $cluster)
		            $this->hasAccess($cluster['cluster'], $cluster['info'], $cluster['customID'], STALLDEF, /*loginByFault*/true);
		    }
		    $action= $this->getAction();
		    if(isset($this->aAccessClusters[$action]))
		    {
		        foreach($this->aAccessClusters[$action] as $cluster)
		            $this->hasAccess($cluster['cluster'], $cluster['info'], $cluster['customID'], STALLDEF, /*loginByFault*/true);
		    }		        
		    $this->tableContainer->checkPermission();
		}
}

?>