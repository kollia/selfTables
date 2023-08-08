<?php

require_once($_stmessagehandling);
require_once($php_html_description);
require_once($_stdownload);


class STSiteCreator extends HtmlTag
{
		var	$db;
		var	$defaultTitles= array();
		var	$project;
		var	$sFirstTableContainerName;
		var	$tableContainer;
		var	$chooseTable;
		var	$startPage= null;
		var	$sBackButton= "";
		var $sLanguage= "en";
		var	$oMainTable;
		//deprecated
		var	$aNoChoise= array();
		var	$uRequireSites= array();
		var $aNeededVars= array();// Variablen welche auf selbst deffinierter Seite ben�tigt werden
		var	$aBehindTableIdentif= array();
		var	$aBehindProjectIdentif= array();
		var	$aBehindHeadLineButtons= array();
		var	$backButtonAddress;
		var	$bBackButton= true;
		var	$oCurrentListTable;
		var $asError= array();
		var	$aCallbacks= array();
		var $bChooseInTable= true; 	// ob auch, wenn die Tabelle angezeigt wird,
									// eine Auswahl existieren soll
		var	$bContainerManagement= true; // ob die Container in das older verschoben werden soll
		var	$aContainerAdress= array(); // alle link-Adressen der ContainerButtons
		var $logoutButton= null;
		var $aDefaultCssLink= array();
		protected $bUseOnlyDefaultCssLinks= false;

		function __construct($container= null)
		{
			global	$_selftable_first_main_database_name;

			STCheck::paramCheck($container, 1, "STBaseContainer", "STFrameContainer", "null");

			if(STCheck::isDebug())
			{
				global	$_st_page_starttime_;

				STCheck::setPageStartTime();
				STCheck::echoDebug("performance", "creating object of class STSiteCreator ".date("H:i:s")." ".(time()-$_st_page_starttime_));
			}
			HtmlTag::__construct();
			if($container)
			{
				// alex 11/09/2005:	maybe the container is no refference,
				//					so took the container from globaly list
				$containerName= $container->getName();
				$listContainer= &STBaseContainer::getContainer($containerName);
				// alex 11/10/2022: if the container from list not the same as from parameter
				//                  tooke the pareameter container
				if( !isset($listContainer) ||
				    $listContainer->getName() != $container->getName()  )
				{
				    $this->setMainContainer($container);
				}else
				    $this->setMainContainer($listContainer);
				if(!$_selftable_first_main_database_name)
				{
					$db= $container->getDatabase();
					$_selftable_first_main_database_name= $db->getName();
					$this->db= &$db;
				}
			}
		}
		function setLanguage($lang)
		{
			STCheck::param($lang, 1, "string");
			
			if(	$lang != "en" &&
				$lang != "de"	)
			{
				echo "<b>only follow languages be allowed:</b><br />";
				echo "                   en   -   english<br />";
				echo "                   de   -   german<br />";
				printErrorTrace();
				exit;
			}
			$this->sLanguage= $lang;
		}
		function createMessages($onError)
		{	
			$action= $this->getAction();
			$oTable= &$this->tableContainer->getTable();
			$msgHandling= new STMessageHandling(get_class($this), $onError);
			
			if($this->sLanguage == "en")
			{
				if($this->sBackButton == "")
					$this->sBackButton= " back ";
				$msgHandling->setMessageContent("LISTACCESSERROR@", "user has no permission to see table @");
				$msgHandling->setMessageContent("INSERTACCESSERROR@", "user has no permission to fill content into table @");
				$msgHandling->setMessageContent("UPDATEACCESSERROR@", "user has no permission to change content inside table @");
				$msgHandling->setMessageContent("DELETEACCESSERROR@", "user has no permission to delete content from table @");
				
			}elseif($this->sLanguage = "de")
			{
				if($this->sBackButton == "")
					$this->sBackButton= " zurueck ";
				$msgHandling->setMessageContent("LISTACCESSERROR@", "Benutzer hat keine Berechtigung die Tabelle @ anzusehen");
				$msgHandling->setMessageContent("INSERTACCESSERROR@", "Benutzer hat keine Berechtigung in der Tabelle @ neue Eintraege zu erstellen");
				$msgHandling->setMessageContent("UPDATEACCESSERROR@", "Benutzer hat keine Berechtigung Eintraege in der Tabelle @ zu aendern");
				$msgHandling->setMessageContent("DELETEACCESSERROR@", "Benutzer hat keine Berechtigung Eintraege in der Tabelle @ zu loeschen");
			}
			
			if(isset($oTable))
			{
    			if(	$action == STLIST &&
    				!$oTable->hasAccess(STLIST, true)	)
    			{
    				$msgHandling->setMessageId("LISTACCESSERROR@", $oTable->getDisplayName());
    				
    			}elseif(	$action == STDELETE &&
        					(	!$oTable->canDelete() ||
        						!$oTable->hasAccess(STDELETE, true)	)	)
    			{
    				$msgHandling->setMessageId("DELETEACCESSERROR@", $oTable->getDisplayName());
    				
      			}elseif(	$action == STINSERT &&
    						(	!$oTable->canInsert() ||
    							!$oTable->hasAccess(STINSERT, true)	)	)
    			{
    				$msgHandling->setMessageId("INSERTACCESSERROR@", $oTable->getDisplayName());
    				
    			}elseif(	$action == STUPDATE &&
    						(	!$oTable->canUpdate() ||
    							!$oTable->hasAccess(STUPDATE, true)	)	)
    			{
    				$msgHandling->setMessageId("UPDATEACCESSERROR@", $oTable->getDisplayName());
    			}		
			}
			return $msgHandling;
		}
		protected function closeUserDbConnection()
		{
			// Dummy function for STSessionSiteCreator
			// there is eventually defined an extra user database
			// which should also be closed
		}
		function display($bCloseConnection= true)
		{
		    //-------------------------------------------------------------------------------------------------------
		    // alex:  19/10/2022
		    //        since use database session
		    //        do not close connection
		    //        because after finished site,
		    //        STDbSessionHandler try to write session data
		    //        into database
			/*$aClosed= array();
			if($bCloseConnection)
			{
				$containers= &STBaseContainer::getAllContainer();
				foreach($containers as $container)
				{
					if(typeof($container, "STDatabase"))
					{
						$container->closeConnection();
						$aClosed[$container->getName()]= true;
					}
				}
				$this->closeUserDbConnection();
			}*/
			//-------------------------------------------------------------------------------------------------------
			echo "<!DOCTYPE html>";
			if(STCheck::isDebug())
				echo "\n";
			HtmlTag::display();
			if(Tag::isDebug())
			{
				global	$_st_page_starttime_;

				Tag::echoDebug("performance", "END of hole page-display in class STSiteCreator ".date("H:i:s")." needed ".(time()-$_st_page_starttime_)." sec.");
			}
		}
		function doContainerManagement($bManagement)
		{
			$this->bContainerManagement= $bManagement;
		}
		function setMainContainer($container)
		{
			STCheck::paramCheck($container, 1, "STBaseContainer", "string");

			global	$global_first_objectContainerName;

			if(is_string($container))
			{
				$containerName= $container;
				$container= &STBaseContainer::getContainer($containerName);
			}else
				$containerName= $container->getName();

			$container->bFirstContainer= true;

			if($this->sFirstTableContainerName)
			{
				$before= &STBaseContainer::getContainer($this->sFirstTableContainerName);
				$before->bFirstContainer= false;
			}
			$this->sFirstTableContainerName= $containerName;
			$global_first_objectContainerName= $containerName;
			// alex 17/05/2005:	parameter kann jetzt vom Container STObjectContainer sein
			// 					die Datenbank wird nun �ber dieses Objekt geholt
			$this->tableContainer= &$container;
			if(!$this->db)
				$this->db= $container->getDatabase();
		}
		function needVar($name, &$value)
		{
			$this->aNeededVars[$name]= &$value;
		}
		function &getVar($name)
		{
			return $this->aNeededVars[$name];
		}
		function &getDatabase()
		{
			return $this->db;
		}
		function setProjectID($projectID)
		{
			$this->nProjectID= $projectID;
		}
		function getProjectID()
		{
			if($this->nProjectID===null)
				$this->nProjectID= 1;
			$this->bAskForProject= true;
			return $this->nProjectID;
		}
		function setProjectDisplayName($name)
		{
			$this->project= $name;
		}
		function setProjectIdentifier($project)
		{
			Tag::deprecated("STSiteCreator::setProjectDisplayName()", "STSiteCreator::setProjectIdentifier()");
			$this->setProjectDisplayName($project);
		}
		function getProjectIdentifier()
		{
			Tag::deprecated("STSiteCreator::getProjectDisplayName()", "STSiteCreator::getProjectIdentifier()");
			$this->getProjectDisplayName();
		}
		function getProjectDisplayName()
		{
			$project= $this->tableContainer->getProjectDisplayName();
			if($project===null)
				$project= $this->project;
			return $project;
		}
		function chooseTitle($title)
		{
			Tag::deprecated("STSiteCreator::title()", "STSiteCreator::chooseTitle()");
			$this->chooseTitle= $title;
		}
		function setStartPage($file)
		{
			$this->startPage= $file;
		}
		function getStartPage()
		{
			if(!isset($this->startPage))
			{
				global $HTTP_SERVER_VARS;
			
				$this->startPage= $HTTP_SERVER_VARS["SCRIPT_NAME"];
			}
			return $this->startPage;
		}
		function getResult()
		{
			if(!typeof($this->tableContainer, "STObjectContainer"))
			{
				STCheck::is_warning(1, "STSiteCreator::getResult()",
								"this function is only for an STObjectContainer");
				return null;
			}
			return $this->tableContainer->getResult();

		}
		function getInsertID()
		{
			if(!typeof($this->tableContainer, "STObjectContainer"))
			{
				STCheck::is_warning(1, "STSiteCreator::getResult()",
								"this function is only for an STObjectContainer and action STINSERT and STUPDATE");
				return null;
			}
			$action= $this->getAction();
			if(	$action!==STINSERT
				or
				$action !==STUPDATE	)
			{
				STCheck::is_warning(1, "STSiteCreator::getResult()",
									"this function is only for action STINSERT and STUPDATE");
				return null;
			}
			return $this->tableContainer->getInsertID();
		}
	function showLogoutButton($buttonName= "log out", $align= "right", $buttonId= "logoutMainButton")
	{
		$this->logoutButton= array(	"buttonName"=>$buttonName,
									"columnAlign"=>$align,
									"buttonId"=>$buttonId		);
	}
		public function execute($onError= onErrorMessage)
		{
			global	$HTTP_GET_VARS;

			Tag::alert($this->tableContainer==null, "STDbSiteCreator::execute()",
								"befor execute set container in constructor or with ::setMainContainer()");
			
			if(isset($HTTP_GET_VARS["stget"]))
				$get_vars= $HTTP_GET_VARS["stget"];
			if(isset($get_vars["table"]))
				$queryTable= $get_vars["table"];
			if(!isset($get_vars))
				$get_vars= array();
			// alex 01/07/2005:	gibt es in stget einen Container
			//					wechsle vom Haupt-Container zu diesem
			if( isset($get_vars["container"]) &&
			    trim($get_vars["container"]) != ""   )
			{
				$container= &STBaseContainer::getContainer($get_vars["container"]);
				Tag::alert(!isset($container), "STDbSiteCreator::execute()",
												"do not found given container from GET-VARS \""
												.$get_vars["container"]."\" in container-list");
				$this->tableContainer= &$container;
				// bug: 28/12/2009
				//	by STFrameContainer no database exists and so comming thru getDatabase() NULL
				//	if save this without chek on this-db
				//	the container from set table STQueryString in global variable $global_selftables_query_table["table"]
				//	lost the member variable container
				$db= $container->getDatabase();
				if($db !== NULL)
				    $this->db= &$db;//&$container->getDatabase();			
			}
			if(is_array($this->logoutButton))
				$this->tableContainer->showLogoutButton(	$this->logoutButton["buttonName"],
															$this->logoutButton["columnAlign"],
															$this->logoutButton["buttonId"]);

			// alex 18/05/2005:	wenn die Aktion für choose (Auswahl) steht
			//					kontrolliere ob eine Tabelle schon als erstes
			//					aufgelistet werden soll
			$get_vars["action"]= $this->getAction();
			if(	(	!isset($get_vars["table"]) ||
					$get_vars["table"] == ""		) &&
				$get_vars["action"] != STCHOOSE			)
			{
				$get_vars["table"]= $this->getTableName();
			}
			
			if(	isset($get_vars["table"]) &&
				is_string($get_vars["table"]) &&
				isset($this->uRequireSites[$get_vars["table"]]))
			{// wenn gewünscht inkludiere eine weitere Seite
				$siteCreator= &$this;// zugriff auf gegenwärtiges Objekt in der neuen Seite
				require($this->uRequireSites[$get_vars["table"]]["site"]);
			}
			
			if(STCheck::isDebug("container"))
			{
			    echo "<br />";
			    $msg= "execute container ".get_class($this)."(<b>".$this->tableContainer->getName()."</b>)";			    
			    if( isset($this->db) &&
			        $this->db != NULL          )
			    {
			        $msg.= " with database ".get_class($this->db)."(<b>".$this->db->getName()."</b>)";
			    }else
			        $msg.= " with no database";
		        STCheck::echoDebug("container", $msg);
		        //st_print_r()
		        if(	isset($get_vars["table"]) &&
		            $get_vars["table"] != ""		)
		        {
		            $msg= "     on table     <b>".$get_vars["table"]."</b>";
		        }elseif(typeof($this->tableContainer, "STObjectContainer"))
		            $msg= "with no explicit <b>table</b>";
		        else
		            $msg= "with no table";
		        STCheck::echoDebug("container", $msg);		        
		        if(	isset($get_vars["action"]) &&
		            $get_vars["action"] != ""		)
		        {
		            $msg= "    and by action <b>".$get_vars["action"]."</b>";
		        }else
		            $msg= "with unknown action";
	            STCheck::echoDebug("container", $msg);
	            echo "<br />";
			}
			// create first the container where will be set the maintable / other tables
			$this->tableContainer->setLanguage($this->sLanguage);
			//$this->tableContainer->createContainer();
			if(isset($get_vars["download"]))
			{
				$oTable= &$this->tableContainer->getTable();
				$download= new STDownload($this->db, $oTable);
				$download->execute();
			}
			
			$msgHandling= $this->createMessages($onError);
			$result= $msgHandling->getMessageId();
			if($result=="NOERROR")
			{
			    $result= $this->tableContainer->execute($this, $onError);
				$msgHandling->setMessageContent($result);
				$msgHandling->setMessageId($result);
				$endScript= $msgHandling->getMessageEndScript();
				$this->tableContainer->appendObj($endScript);

				if($result!="FORWARDTtoADDRESS")
					$this->addObj($this->tableContainer->getHead("Unknown"));
				$this->addObj($this->tableContainer);
			}else
			{
				$msgHandling->setErrorScript("window.location.back()");
				$body= new BodyTag();
					$body->addObj($msgHandling->getMessageEndScript());
				$this->addObj($body);
			}
			return $result;
		}
		/**
		 * returning head tag with content
		 *
		 * @return Tag head-tag
		 */
		function &getHead()
		{
			$head= &$this->getElementByTagName("head");
			return $head;
		}
		/**
		 * returning body tag with content
		 *
		 * @return Tag body-tag
		 */
		 function &getBody()
		 {
		 	$body= &$this->getElementByTagName("body");
		 	return $body;
		 }
		function addObjBehindProjectIdentif(&$tag)
		{
			$this->aBehindProjectIdentif[]= &$tag;
		}
		function addBehindProjectIdentif($tag)
		{
			$this->aBehindProjectIdentif[]= &$tag;
		}
		function addObjBehindTableIdentif(&$tag)
		{
			$this->aBehindTableIdentif[]= &$tag;
		}
		function addBehindTableIdentif($tag)
		{
			$this->aBehindTableIdentif[]= &$tag;
		}
		function addObjBehindHeadLineButtons(&$tag)
		{
			$this->aBehindHeadLineButtons[]= &$tag;
		}
		function addBehindHeadLineButtons($tag)
		{
			$this->aBehindHeadLineButtons[]= &$tag;
		}
		function setBackButtonValue($name)
		{
			$this->sBackButton= $name;
		}
		/*function setMainTable(&$table, $bAdmin= true)
		{
			if(!typeof($table, "STDbTable", "stdbtablecontainer"))
			{echo get_class($table)."<br />";
				echo "<b>ERROR:</b> first parameter in STUser::setMainTable() ";
				echo "must be an object from class STDbTable or STDbTableContainer";
				exit;
			}if(!is_bool($bAdmin))
			{
				echo "<b>ERROR:</b> second parameter in STUser::setMainTable() must be an boolean";
				exit;
			}
			$this->aMainTable["table"]= &$table;
			$this->aMainTable["admin"]= $bAdmin;
		}*/
		function setMainMenueButtonValue($name)
		{
			$this->sBackButton= $name;
		}
		function setBackButtonAddress($address)
		{
			if($address)
				$this->bBackButton= true;
			else
				$this->bBackButton= false;
			$this->backButtonAddress= $address;
		}
		function getBackButton($get_vars)
		{
			if(!$this->bBackButton)
				return null;


			$get= new STQueryString();
			if($get_vars["action"]!=STLIST)
			{
				$get->update("stget[action]=".STLIST);
				$get->delete("stget[link][VALUE]");
				$backButtonAddress= $get->getStringVars();
			}
			if($this->backButtonAddress)
				$backButtonAddress= $this->backButtonAddress;
			if($backButtonAddress)
			{
  				$table= new TableTag();
  					$table->width("100%");
  					$tr= new RowTag();
  						$td= new ColumnTag(TD);
  							$td->align("right");
  							$button= new ButtonTag("backButton");
  								$button->add($this->sBackButton);
  								$button->onClick("javascript:location='".$backButtonAddress."'");
  							$td->add($button);
  						$tr->add($td);
  					$table->add($tr);
				return $table;
			}
			return null;
		}
		function getContainerAddress($containerName)
		{
			return $this->tableContainer->aContainerAdress[$containerName];
		}
		public function setDefaultCssLink($href, $media= "all", $title= "protokoll default Stylesheet")
		{
		    $this->bUseOnlyDefaultCssLinks= true;
		    $this->setCssLink($href, $media, $title);
		}
		public function setCssLink($href, $media= "all", $title= "protokoll default Stylesheet")
		{
		    if($media == "all")
		        $this->aDefaultCssLink= array();
		    else
		        $this->aDefaultCssLink[$media]= array();
			$this->aDefaultCssLink[$media][]= array( "href"=>	$href,
											         "title"=>	$title	);
		}
		public function addCssLink($href, $media= "all", $title= "protokoll default Stylesheet")
		{
		    $this->aDefaultCssLink[$media][]= array( "href"=>	$href,
		                                             "title"=>	$title	);
		}
		function getCssLinks()
		{
		    $aLinks= array();			
			if($this->tableContainer->needDefaultCssLinks())
			{
				foreach($this->aDefaultCssLink as $media => $mediaLinks)
				{
				    foreach($mediaLinks as $link)
				        $aLinks[]= STQueryString::getCssLink($link["href"], $media, $link["title"]);
				}
			}
			if(!$this->bUseOnlyDefaultCssLinks)
			{
    			$aContainerLinks= $this->tableContainer->getCssLinks();
    			foreach($aContainerLinks as $link)
    			    $aLinks[]= $link;
			}
			return $aLinks;
		}
		// deprecatet wird in STObjectContainer verschoben
		function chooseInTable($bChoose)
		{
			$this->bChooseInTable= $bChoose;
		}
		function setAccessForColumnsInTable(&$oTable, &$oList)
		{
			STCheck::is_warning(1, "", "");
			echo "wrong function access in STSiteCreator<br />";
			echo "function now in STObjectContainer<br />";
			exit;
		}
		function &getMainTable()
		{
			return $this->tableContainer->oMainTable;
		}
		function &getNavigationTable($tableDisplayName= null)
		{
			Tag::alert(!$tableDisplayName&&count($this->tableContainer->aNavigationTables)>1,
							"STDbSiteCreator::getNavigationTable()", "more then one navigation-table in container found");

			if(!$tableDisplayName)
				$tableDisplayName= $this->tableContainer->aNavigationTables[0]["tableName"];
			foreach($this->tableContainer->aNavigationTables as $key=>$content)
			{
				if($content["tableName"]==$tableDisplayName)
					return $this->tableContainer->aNavigationTables[$key]["list"];
			}
			if(Tag::isDebug())
			{
			    $set= "no navigation-table in container set";
					if($tableDisplayName)
			        $set= "table-display-name ".$tableDisplayName." not set in aktual container";
					STCheck::is_warning(1, "STDbSiteCreator::getNavigationTable()", $set);
					//st_print_r($this->tableContainer->aNavigationTables,2);
			}
			return null;
		}
		function noChoise($table)
		{
			if(typeof($table, "MUDbTable"))
				$table= $table->getName();
			$this->aNoChoise[]= $table;
		}
		function onTableRequireSite($tableName, $site)
		{
			$require= array();
			$require["site"]= $site;
			$require["action"]= "require";
			$this->uRequireSites[$tableName]= $require;
		}
		function setMessageContent($action, $table, $error= null, $errorMessage= null)
		{
			if($error===null)
			{echo $table."<br />";
				$error= $action;
				$errorMassage= $table;echo $errorMassage."<br />";
				if(!isset($this->asError["all"]))
					$this->asError["all"]= array();
				$this->asError["all"][$error]= $errorMassage;
				return;
			}
			if($errorMessage===null)
			{
				$errorMessage= $error;
				$error= $table;
				$table= $action;
				if(!isset($this->asError[$table]))
					$this->asError[$table]= array();
				$this->asError[$table][$error]= $errorMessage;
				return;
			}
			$action= strtolower(trim($action));
			if(!isset($this->asError[$action]))
				$this->asError[$action]= array();
			if(!isset($this->asError[$action][$table]))
				$this->asError[$action][$table]= array();
			$this->asError[$action][$table][$error]= $errorMessage;
		}
		// action darf nicht delete sein
		function callback($action, $tableName, $columnName, $callbackFunction= null)
		{
			if(!$callbackFunction)
			{
				$callbackFunction= $columnName;
				$columnName= "mysql_statement";
			}
			if(!isset($this->aCallbacks[$action]))
				$this->aCallbacks[$action]= array();
			if(!isset($this->aCallbacks[$action][$tableName]))
				$this->aCallbacks[$action][$tableName]= array();
			$this->aCallbacks[$action][$tableName][$columnName]= $callbackFunction;
		}
	function &getContainer(string $containerName= null, string $className= null, string $fromContainer= null)
	{
		global	$_selftable_first_main_database_name;

		if(	$containerName
			and
			!$fromContainer
			and
			!STDatabase::existDatabaseClassName($className)	)
		{
			STCheck::alert(	!$_selftable_first_main_database_name, "STSiteCreator::getContainer()",
							"first pulled container must be for an database-object"						);
			if(!$className)
				$className= "STObjectContainer";
			$fromContainer= $_selftable_first_main_database_name;
		}
		if(STCheck::isDebug())
		{
			STCheck::alert($containerName && !STBaseContainer::existContainer($containerName) && !$className, "STSiteCreator::getContainer()",
								"on first call of getContainer() for '$containerName' second parameter must be an defined class-name");
		}
		$newContainer= &STBaseContainer::getContainer($containerName, $className, $fromContainer);
		if(!$this->sFirstTableContainerName)
		{
			$this->tableContainer= $newContainer;
			$this->setMainContainer($newContainer);
		}
		if( isset($newContainer) &&
		    !$_selftable_first_main_database_name )
		{
			$db= $newContainer->getDatabase();
			if(isset($db))
			   $_selftable_first_main_database_name= $db->getName();
		}
		return $newContainer;
	}
	function getContainerName()
	{
		global $HTTP_GET_VARS;

		if(isset($HTTP_GET_VARS["stget"]["container"]))
			$containerName= $HTTP_GET_VARS["stget"]["container"];
		else
			$containerName= $this->tableContainer->getName();
		return $containerName;
	}
	/*function &getOlderContainer()
	{
		global $HTTP_GET_VARS;

		$oldContainerName= $HTTP_GET_VARS["stget"]["older"]["stget"]["container"];
		if(	!$oldContainerName
			and
			$HTTP_GET_VARS["stget"]["container"]
			and
			$this->sFirstTableContainerName!=$HTTP_GET_VARS["stget"]["container"]	)
		{
			$oldContainerName= $this->sFirstTableContainerName;
		}
		$container= &STDbTableContainer::getContainer($oldContainerName);
		return $container;
	}*/
	function getAction()
	{
		$container= &$this->getContainer();
		return $container->getAction();
	}
	function &getTable($tableName= null)
	{
		return $this->getContainer()->getTable($tableName); 
	}
	function getTableName()
	{
		$container= &$this->getContainer();
		$sRv= $container->getTableName();
		return $sRv;
	}
	function getContainerIdentification()
	{
		$this->getContainer();//setzt den $this->tableContainer
		return $this->tableContainer->getIdentification();
	}
	function install()
	{
		global	$global_boolean_install_objectContainer;

		STCheck::debug("install");
		$bInstalled= false;
		$containers= STBaseContainer::getAllContainerNames();
		foreach($containers as $containerName)
		{
			$obj= &STBaseContainer::getContainer($containerName);
			if(typeof($obj, "STObjectContainer"))
			{
			    STCheck::echoDebug("install", "<b>install</b> container ".get_class($obj)."($containerName)");
				$obj->installContainer();
				$bInstalled= true;
				STCheck::echoDebug("install", "container ".get_class($obj)."($containerName) is installed");
			}
		}
		if(!$bInstalled)
			STCheck::echoDebug("install", "no container to install be set");
		$global_boolean_install_objectContainer= true;
	}
}

?>