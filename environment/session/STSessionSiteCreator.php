<?php

require_once($_stdatabase);
//require_once($_stusersession);
require_once($_stsitecreator);

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
		/**
		 * user will be forward to login page
		 * if he has no access to cluster
		 *
		 * @param string $cluster access cluster name
		 * @param string $action action can be set to:<br />
		 *                         <table>
		 *                             <tr>
		 *                                 <td>
		 *                                     STALLDEF
		 *                                 </td><td>-</td>
		 *                                     for all actions (default)
		 *                                 <td>
		 *                                 </td>
		 *                             </tr>
		 *                             <tr>
		 *                                 <td>
		 *                                     STLIST
		 *                                 </td><td>-</td>
		 *                                 <td>
		 *                                     show only the content of table
		 *                                 </td>
		 *                             </tr>
		 *                             <tr>
		 *                                 <td>
		 *                                     STINSERT
		 *                                 </td><td>-</td>
		 *                                 <td>
		 *                                     insert somthing into table
		 *                                 </td>
		 *                             </tr>
		 *                             <tr>
		 *                                 <td>
		 *                                     STUPDATE
		 *                                 </td><td>-</td>
		 *                                 <td>
		 *                                     update row of table
		 *                                 </td>
		 *                             </tr>
		 *                             <tr>
		 *                                 <td>
		 *                                     STDELETE
		 *                                 </td><td>-</td>
		 *                                 <td>
		 *                                     delete row of table
		 *                                 </td>
		 *                             </tr>
		 *                         </table>
		 *                       if no action be set, only the third description string,
		 *                       action will be also the default (STALLDEF)
		 * @param string $sInfoString description of cluster for logging table
		 * @param int $customID custom id for logging table if need
		 */
		public function accessBy(string $cluster, $action= STALLDEF, $sInfoString= "", int $customID= null)
		{
		    STCheck::param($action, 1, "string", "int");
		    STCheck::param($action, 2, "string", "int");
		    
		    if( $action != STALLDEF &&
		        $action != STLIST &&
		        $action != STINSERT &&
		        $action != STUPDATE &&
		        $action != STDELETE    )
		    {
		        $sInfoString= $action;
		        $action= STALLDEF;
		    }
		    if(is_integer($sInfoString))
		    {
		        $customID= $sInfoString;
		        $sInfoString= "";
		    }
		    
		    if(!$sInfoString)
		        $sInfoString= "acces to container ".$this->getDisplayName()."(".$this->name.")";
	        $this->aAccessClusters[$action][]= array(	"cluster"	=>	$cluster,
                                    		            "action"    =>  $action,
                                    		            "info"		=>	$sInfoString,
                                    		            "customID"	=>	$customID		);
		}
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
			if(STSession::sessionGenerated())
			{
				$_instance= &STSession::instance();
				$db= &$_instance->getUserDb();
				//$db->closeConnection();
			}
		}
		function execute($additionalText= "")
		{
			global $__global_finished_SiteCreator_result;
			
		    $tableName= $this->getTableName();
		    if($tableName)
		        $table= &$this->tableContainer->getTable($tableName);
		    
			if( isset($tableName) &&
			    trim($tableName) != ""   )
			{
    			$action= $this->getAction();
    			if(trim($additionalText) == "")
    			    $additionalText= "user has access to table $tableName on container ".$this->getContainer()->getName();
    
    			if(isset($table))
    				$this->accessTable($table, $action, $additionalText);
			}
			
			// check access to SideCreator, Container, and Tables
			$this->checkPermission();
			$result= STSiteCreator::execute();
			if(STCheck::isDebug("test"))
				$__global_finished_SiteCreator_result= $result;
			return $result;
		}
		/**
		 * whether object need registration or login
		 *
		 * @return boolean wheter need registration
		 */
		public function needRegister() : bool
		{
			return $this->userManagement->needRegister();
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
