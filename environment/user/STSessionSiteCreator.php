<?php

require_once($user_admin);
require_once($table_out);
require_once($_stdatabase);

class STSessionSiteCreator extends STSiteCreator
{
	var $userDb;
	var $nProjectID;
	var	$sProject;
	var	$userManagement;
	var	$aAccessClusters= array();
	var $sUserLoginMask= null;

	function __construct($container= null)
	{
		STCheck::paramCheck($container, 1, "STBaseContainer", "null");

		STSiteCreator::__construct($container);
	}
		function getProjectID()
		{
			if($this->nProjectID===null)
				$this->nProjectID= 1;
			$this->bAskForProject= true;
			return $this->nProjectID;
		}
		function initSession()
		{
		    if(!STSession::sessionGenerated())
				    STSession::init();
    		$this->userManagement= &STSession::instance();
   			$this->userManagement->registerSession();
			if($this->sUserLoginMask)
				$this->userManagement->setUserLoginMask($this->sUserLoginMask);
   		$this->userManagement->verifyLogin();
		}
	function setUserLoginMask($address)
	{
	    global $st_user_login_mask;

		  if($this->userManagement)
			  $this->userManagement->setUserLoginMask($address);
		  else
			    $st_user_login_mask= $address;
	}
		function setUserAccessCluster($cluster)
		{
			$this->aUserAccessCluster[]= $cluster;
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
		function hasAccess($clusters)
		{
        	Tag::alert(!$this->userManagement, "STUserSiteCreator::hasAccess()",
            						"you must invoke before this function initSession()");

			Tag::paramCheck($clusters, 1, "string", "array");

            if(is_array($clusters))
			{
			    $aClusters= $clusters;
			    foreach($clusters as $key=>$value)
				{
				    if(!$value)
					    unset($clusters[$key]);
				}
			}else
            	$clusters= preg_split("/[, ]+/", $clusters);
            $clusters= array_flip($clusters);
            if(count($this->aUserAccessCluster))
            {
            	foreach($this->aUserAccessCluster as $cluster)
                {
                	if($clusters[trim($cluster)]!==null)
                    	return true;
                }
                return false;
            }
            // if no clusters by set
            // developer do not need access-control
            return true;
		}
		/*function hasContainerAccess(&$oContainer)
		{
			Tag::echoDebug("access", "function <b>hasContainerAccess()</b> to container \"".$oContainer->getName()."\" ?");
			// alex 28/10/2005:	take aktual container not from incomming Container
			//					its maybe from the showTypes extraField of an old Container
			$tables= $oContainer->getTables();
			if(	is_array($tables)
				and
				count($tables))
			{
				$bChoose= false;
				foreach($tables as $tableName=>$table)
				{
					if(	$oContainer->tableChoice($table) )
					{
						$bChoose= true;
						if(	$this->hasTableAccess($table, STLIST)	)
						{
							Tag::echoDebug("access", "user has <b>access</b> to table ".$table->getName().
												"(".$table->getDisplayName().") <b>end of search</b>, return true");
							return true;
						}
					}
				}
				if($bChoose)
				{
					Tag::echoDebug("access", "no acccess of any table <b>end of search</b> with returning false");
					return false;
				}
			}
			Tag::echoDebug("access", "no table set in container, so has access <b>end of search</b> with returning true");
			return true;
		}
		function hasTableAccess($table, $action, $gotoLoginMask= false)
		{
			Tag::alert(!$this->userManagement, "STUserSiteCreator::hasTableAccess()",
											"you must invoke before this function initSession()");

			if(!$table)
			{
				Tag::echoDebug("access", "<b>hasTableAccess():</b> table undefined so return false");
				return false;
			}
			Tag::echoDebug("access", "function <b>hasTableAccess()</b> for table ".$table->getName());
			$clusters= $this->getCluster($table, $action);
			if(!$clusters)// no Cluster set, so return true
			{
				Tag::echoDebug("access", "no cluster in table set, so make no access-handling, return true");
				return true;
			}
			foreach($clusters as $key=>$cluster)
			{// delete all empty clusters in array
				if(!$cluster)
					unset($clusters[$key]);
			}
			if(!count($clusters))// no Cluster set, so return truetrue
			{
				Tag::echoDebug("access", "no cluster in table set, so make no access-handling, return true");
				return true;
			}else
			{
				if(Tag::isDebug("access"))
				{
					Tag::echoDebug("access", "follow clusters for table found:");
					st_print_r($clusters);
				}
			}
			$infoString= $table->getAccessInfoString($action);
			$customID= $table->getAccessCustomID($action);
			$access= $this->hasAccess($clusters, $infoString, $customID, $gotoLoginMask, $action);
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
			}
			if($access)
			{
				Tag::echoDebug("access", "user in action $staction has <b>access</b> to table "
										.$table->getName()."(".$table->getDisplayName()
										.") with Clusters '<i>$clusterString</i>'");
				return true;
			}

			Tag::echoDebug("access", "user in action $staction has <b>no access</b> to table "
									.$table->getName()."(".$table->getDisplayName()
									.") with Clusters '<i>$clusterString</i>'");
			if($gotoLoginMask)
			{
				Tag::echoDebug("access", "so goto login-mask");
				$this->userManagement->gotoLoginMask(5);
			}
			Tag::echoDebug("access", "gotoLoginMask not be set, so return false");
			return false;
		}*/
		function getCluster($table, $access)
		{
			$clusters= null;
			$tableName= $table->getName();
			if(isset($this->aAccessClusters[$tableName][$access]))
				$clusters= $this->aAccessClusters[$tableName][$access];
			if(	!isset($clusters) &&
				isset($this->aAccessClusters[$tableName][ACCESS])	)
			{
				$clusters= $this->aAccessClusters[$tableName][ACCESS];
			}
			if(	!isset($clusters) &&
				isset($this->aAccessClusters["-all"][$access])	)
			{
				$clusters= $this->aAccessClusters["-all"][$access];
			}
			if(	!isset($clusters) &&
				isset($this->aAccessClusters["-all"][ACCESS])	)
			{
				$clusters= $this->aAccessClusters["-all"][ACCESS];
			}
			$otherClusters= $table->getAccessCluster($access);
			if($clusters==null)
			    $clusters= array();
			if(!is_array($otherClusters))
			    $otherClusters= array($otherClusters);
			if($otherClusters==null)
			    $otherClusters= array();
			$clusters= array_merge($clusters, $otherClusters);
			return $clusters;
		}
		function accessTable($table, $action= STACCESS, $additionText= "")
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
			$bRv= $this->hasAccess($clusterString, $toAccessInfoString, $nAction, true);
			return $bRv;
		}
		protected function closeUserDbConnection()
		{
			if(STUserSession::sessionGenerated())
			{
				$_instance= &STUserSession::instance();
				$db= &$_instance->getUserDb();
				if(!$aClosed[$db->getName()])
					$db->closeConnection();
			}
		}
		function execute($additionalText= "")
		{
			global $HTTP_GET_VARS;

			$query= new STQueryString();
			$get_vars= $query->getArrayVars("stget");
			$tableName= $this->getTableName();
			$action= $this->getAction();
			if($tableName)
				$table= &$this->tableContainer->getTable($tableName);
			$action= $this->getAction();
			$additionalText= "";

			if(isset($table))
				$this->accessTable($table, $action, $additionalText);

			return STSiteCreator::execute();
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
