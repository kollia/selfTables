<?php

require_once($base_site_creator);
require_once($user_admin);
require_once($table_out);
require_once($_stdatabase);

class OSTDbSiteCreator extends STDbSiteCreator
{
		var $userDb;
		var	$userManagement;
		var	$aAccessClusters= array();
		
		function OSTDbSiteCreator($project, $container= null, $registerSession= true)
		{
			Tag::paramCheck($project, 1, "string", "int");
			Tag::paramCheck($container, 2, "STDbTableContainer", "null");
			Tag::paramCheck($registerSession, 3, "bool");
		
			STDbSiteCreator::STDbSiteCreator($container);
			OSTDbSiteCreator::init($project, $registerSession);
		}		
		function init($project, $registerSession)
		{
			global	$PHP_SELF,
					$USERCLASS,
					$DBin_UserHost,
					$DBin_UserUser,
					$DBin_UserPwd,
					$DBin_UserDatabase;
					
					
			if($registerSession)
			{
    			$this->userDb= new OSTDatabase("UserManagement");
    			$this->userDb->connect($DBin_UserHost, $DBin_UserUser, $DBin_UserPwd);
    			$this->userDb->toDatabase($DBin_UserDatabase);
    			
    			$this->userManagement= new $USERCLASS($this->userDb);
    			
    			// alex 02/05/2005:	entfernt, da var $startPage ja nicht gesetzt wird
    			//if(isset($startPage))	
    			//	$this->userManagement->startPage($startPage);
    			$this->userManagement->registerSession();
    			$this->userManagement->verifyLogin($project);
				$project= $this->userManagement->getProjectName();
			}else
			{
    			$this->userDb= new STDatabase("UserManagement");
				$this->userManagement= new $USERCLASS($this->userDb);
				$this->userManagement->noRegisterForDebug($PHP_SELF);
			}
			
			$identifier= "<h1><em>Projekt</em> <font color='red'>";
			$identifier.= $project."</font></h1>";
			$this->setProjectIdentifier($identifier);
			
			// alex 02/05/2005:	entfernt, da var $startPage ja nicht gesetzt wird
			//$this->setStartPage($this->userManagement->getStartPage());
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
		function hasAccess($clusters, $toAccessInfoString= "", $customID= null, $makeError= false)
		{
			Tag::paramCheck($clusters, 1, "string", "array");
			Tag::paramCheck($toAccessInfoString, 2, "string", "empty(string)", "null");
			Tag::paramCheck($customID, 3, "int", "null");
			
			if(is_string($clusters))
				return $this->userManagement->hasAccess($clusters, $toAccessInfoString, $customID, $makeError);
			foreach($clusters as $cluster)
			{
				if(!$this->userManagement->hasAccess($cluster, $toAccessInfoString, $customID, $makeError))
					return false;
			}
			return true;
		}
		function hasTableAccess($table, $action, $gotoLoginMask= false)
		{		
			if(!$table)
				return false;
			$clusters= $this->getCluster($table, $action);
			if(!$clusters)// no Cluster set, so return true
				return true;
			$infoString= $table->getAccessInfoString($action);
			$customID= $table->getAccessCustomID($action);
			$access= $this->hasAccess($clusters, $infoString, $customID, $gotoLoginMask);
			if(	!$access
				and
				$gotoLoginMask	)
			{
				$this->userManagement->gotoLoginMask(5);
			}
			if(Tag::isDebug())
			{
				if($action==STLIST)
					$staction= "STLIST";
				elseif($action==STUPDATE)
					$staction= "STUPDATE";
				elseif($action==STINSERT)
					$staction= "STINSERT";
				elseif($action==STDELETE)
					$staction= "STDELETE";
				$clusterString= "";
				foreach($clusters as $cluster)
					$clusterString.= $cluster.", ";
				$clusterString= substr($clusterString, 0, strlen($clusterString)-2);
				if($access)
					Tag::echoDebug("access", "user with action $staction has <b>access</b> to table "
										.$table->getName()."(".$table->getDisplayName()
										.") with Clusters <i>$clusterString</i>");
				else
					Tag::echoDebug("access", "user with action $staction has <b>no access</b> to table "
										.$table->getName()."(".$table->getDisplayName()
										.") with Clusters <i>$clusterString</i>");
			}
			return $access;
		}
		function hasContainerAccess(&$oContainer)
		{
			Tag::echoDebug("access", "<b>hasContainerAccess</b> to container \"".$oContainer->getName()."\" ?");
			// alex 28/10/2005:	take aktual container not from incomming Container
			//					its maybe from the showTypes extraField of an old Container
			$tables= $oContainer->getTables();
			if(is_array($tables))
				foreach($tables as $tableName=>$table)
				{
					if(	$oContainer->tableChoice($table) )
					{	
						if(	$this->hasTableAccess($table, STLIST)	)
						{
							Tag::echoDebug("access", "<b>access</b> to table ".$table->getName().
												"(".$table->getDisplayName().") <b>end of search</b>");
							return true;
						}
					}
				}
			Tag::echoDebug("access", "no acccess <b>end of search</b>");
			return false;
		}
		function getCluster($table, $access)
		{
			$tableName= $table->getName();
			$clusters= $this->aAccessClusters[$tableName][$access];
			if(!isset($clusters))
				$clusters= $this->aAccessClusters[$tableName][ACCESS];
			if(!isset($clusters))
				$clusters= $this->aAccessClusters["-all"][$access];
			if(!isset($clusters))
				$clusters= $this->aAccessClusters["-all"][ACCESS];
			$clusters= array_merge($clusters, $table->getAccessCluster($access));
			return $clusters;
		}
		function accessTable($table, $action= ACCESS, $additionText= "")
		{
			$clusters= $this->getCluster($table, $action);
			if(!isset($clusterString))
				return true;
			if(	$action==0	)
			{
				$nAction= 0;
				$sAction= "access to";
			}elseif($action==STINSERT
					or
					preg_match("/insert/i", $action)	)
			{
				$nAction= 1;
				$sAction= "insert in";
			}elseif($action==STUPDATE
					or
					preg_match("/update/i", $action)	)
			{
				$nAction= 2;
				$sAction= "update";
			}elseif($action==STDELETE
					or
					preg_match("/delete/i", $action)	)
			{
				$nAction= 1;
				$sAction= "delete from";
			}
			$toAccessInfoString= $sAction." table ".$table->getName()." ".$additionText;
			return $this->hasAccess($clusterString, $toAccessInfoString, $nAction, true);
		}
		function execute($additionalText= "")
		{
			global $HTTP_GET_VARS;
			
			$get_vars= $HTTP_GET_VARS["stget"];
		//	if(isset($get_vars))
		//	{
				$tableName= $this->getTableName();
				$action= $this->getAction();
				if($tableName)
					$table= &$this->tableContainer->getTable($tableName);
				$action= $this->getAction();
				$links= $get_vars["link"];
				$additionalText= "";
			
		/*		if($action==STLIST)
					$action= 0;
				elseif($action==$this->sInsertAction)
					$action= INSERT;
				elseif($action==$this->sUpdateAction)
					$action= UPDATE;
				if(	$action==0
					and
					isset($links))
				{
					$what= $links[$this->sDeleteAction];
					if(isset($what))
					{
						$action= STDELETE;
						$additionalText= "where PK is ".$what;
					}
				}*/
				if(isset($table))
					$this->accessTable($table, $action, $additionalText);
		//	}
				
			return STDbSiteCreator::execute();
		}
		/*function hasTableAccess($forTable, $toAccessInfoString= "", $customID= null, $logout= false)
		{
			$clusterString= $this->aAccessClusters[$forTable];
			echo "OK<br />";
			if(!isset($clusterString))
				$clusterString= $this->aAccessClusters["-all"];
			return $this->userManagement->hasAccess($clusterString, $toAccessInfoString, $customID, $logout);
		}*/
		function noRegisterForDebug($defaultStartPage)// muss angegeben werden, da er den Projekt-Pfad nicht 
													  // aus der UserManagement-Datenbank holt
		{
			$this->userManagement->noRegisterForDebug($defaultStartPage);
		}
		function &getUserDb()
		{
			return $this->userDb;
		}
		function getUserID()
		{
			return $this->userManagement->getUserID();
		}
}

?>