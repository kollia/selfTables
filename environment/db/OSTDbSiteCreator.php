<?php

require_once($choose_table);
require_once($mysql_database);
require_once($database_tables);
require_once($insert_update);
require_once($database_where_clausel);
require_once($php_html_description);
require_once($_stdownload);


class STDbSiteCreator extends HtmlTag
{
		var	$db;
		var	$chooseTitle;
		var	$project;
		var	$sFirstTableContainerName;
		var	$tableContainer;
		var	$chooseTable;
		var	$startPage;
		var	$sBackButton= " zurueck ";
		var	$oMainTable;
		var	$sInsertAction;
		var	$sUpdateAction;
		var	$sDeleteAction;
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
		
		function __construct($container= null)
		{			
			Tag::paramCheck($container, 1, "STDbTableContainer", "null");
			HtmlTag::__construct();
			if($container)
			{ 
				// alex 11/09/2005:	da der container keine Referenz ist
				//					diesen aus der liste holen
				$containerName= $container->getName();
				$container= &STDbTableContainer::getContainer($containerName);
				$this->setMainContainer($container);
			}			
			$this->sInsertAction= "insert";
			$this->sUpdateAction= "aktualisieren";
			$this->sDeleteAction= "l�schen";
		}
		function doContainerManagement($bManagement)
		{
			$this->bContainerManagement= $bManagement;
		}
		function setMainContainer(&$container)
		{
			global	$global_first_objectContainerName;
					
			Tag::paramCheck($container, 1, "STDbTableContainer");

			$container->bFirstContainer= true;
			$containerName= $container->getName(); 			
			$this->sFirstTableContainerName= $containerName;
			$global_first_objectContainerName= $containerName;
			// alex 17/05/2005:	parameter kann jetzt vom Container STDbTableContainer sein
			// 					die Datenbank wird nun �ber dieses Objekt geholt
			$this->tableContainer= &$container;
			$this->db= &$container->getDatabase();
			$identif= $container->getIdentification();
			$this->aMainTable["button"]= $identif;
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
		function setProjectIdentifier($project)
		{
			$this->project= $project;
		}
		function setProjectDisplayName($project)
		{
			$this->project= $project;
		}
		function getProjectIdentifier()
		{
			$project= $this->tableContainer->getProjectIdentifier();
			if($project===null)
				$project= $this->project;
			return $project;
		}
		function chooseTitle($title)
		{
			$this->chooseTitle= $title;
		}
		function setStartPage($file)
		{
			$this->startPage= $file;
		}
		function getStartPage()
		{
			if(!$this->startPage)
			{
				global $HTTP_SERVER_VARS;
				$this->startPage= $HTTP_SERVER_VARS["SCRIPT_NAME"];
			}
			return $this->startPage;
		}
		function execute()
		{
			global	$HTTP_GET_VARS,
					$HTTP_POST_VARS,
					$HTTP_SERVER_VARS;
		
			Tag::alert($this->tableContainer==null, "STDbSiteCreator::execute()", 
								"befor execute set container in constructor or with ::setMainContainer()");
			$get_vars= $HTTP_GET_VARS["stget"];
			if(!$get_vars)
				$get_vars= array();
			// alex 01/07/2005:	gibt es in stget einen Container
			//					wechsle vom Haupt-Container zu diesem
			if(isset($get_vars["container"]))
			{
				// alex 02/08/2005:	erstelle den BackButton-Namen
				$this->aMainTable["button"]= $this->sBackButton;
				
				$container= &$this->getOlderContainer();
				if(isset($container))
					$this->aMainTable["button"]= $container->getIdentification();
				else			
					$this->aMainTable["button"]= $this->tableContainer->getIdentification();
							
				$container= &STDbTableContainer::getContainer($get_vars["container"]);
				Tag::alert(!isset($container), "STDbSiteCreator::execute()", 
												"do not found given container from GET-VARS \"" 			
												.$get_vars["container"]."\" in container-list");
				$this->tableContainer= &$container;
				$this->db= &$container->getDatabase();
			}
			// alex 18/05/2005:	wenn die Aktion f�r choose (Auswahl) steht
			//					kontrolliere ob eine Tabelle schon als erstes
			//					aufgelistet werden soll
			$get_vars["action"]= $this->getAction();
			if(	!$get_vars["table"]
				and
				$get_vars["action"]!=STCHOOSE	)
			{
				$get_vars["table"]= $this->getTableName();
			}
			/*if(	!$get_vars["action"]
				or
				$get_vars["action"]==STCHOOSE
				or
				!$get_vars["table"]				)
			{
				$actions= $this->tableContainer->getActions();
				if(is_array($actions))// Aktion kann auch STCHOOSE sein
				{
					// alex 02/08/2005:	action und table nur aktualisieren
					//					wenn sie noch nicht gesetzt
					if(	$get_vars["table"]
						and
						$actions[$get_vars["table"]]	)
					{
						$get_vars["action"]= $actions[$get_vars["table"]];
					}else
					{
        				foreach($actions as $actTable=>$action)
        				{// nimm den ersten Eintrag						
        					if(!$get_vars["action"])
        						$get_vars["action"]= $action["action"];
       						$get_vars["table"]= $actTable;
       						break;
        				}
					}
				}
			}*/
			if(isset($this->uRequireSites[$get_vars["table"]]))
			{// wenn gew�nscht inkludiere eine weitere Seite
				$siteCreator= &$this;// zugriff auf gegenw�rtiges Objekt in der neuen Seite
				require($this->uRequireSites[$get_vars["table"]]["site"]);
			}
			
			$oTable= null;
			if($get_vars["table"])
			{
				$oTable= &$this->tableContainer->getTable($get_vars["table"]);
				if(isset($HTTP_GET_VARS["stget"]["download"]))
				{
					$download= new STDownload($this->db, $oTable);
					$download->execute();
				}
			}

			
			if(	$get_vars["action"]==STLIST
				and
				$this->hasTableAccess($oTable, STLIST, true))
			{
				$result= $this->makeListTags($get_vars);
			}elseif($get_vars["action"]==STDELETE
					and
					$oTable->canDelete()
					and
					$this->hasTableAccess($oTable, STLIST, true)	)
			{
				$result= $this->deleteTableEntry($get_vars);
				
			}elseif(	(	$get_vars["action"]==STINSERT
							and
							$oTable->canInsert()
							and
							$this->hasTableAccess($oTable, STLIST, true)	)
						or
						(	$get_vars["action"]==STUPDATE
							and
							$oTable->canUpdate()
							and
							$this->hasTableAccess($oTable, STLIST, true)	)	)
			{
				$result= $this->makeInsertUpdateTags($get_vars);
				
			}else
			{
				// alex 17/05/2005: mainTable wieder gel�scht,
				//					da ich jetzt alles global �ber 
				//					Container mache
				/*	// alex 11/05/2005:	wenn ein MainTable bestimmt wurde
					//					soll dieser als erstes angezeigt werden
				if(isset($this->aMainTable["table"]))
					$result= $this->makeListTags($get_vars);
				else*/
				$this->makeChooseTags($get_vars);
				$result= "NOERROR";
			}
			return $result;
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
		function &getHead($defaultTitle)
		{
  		$head= new HeadTag();
  			$titleString= $this->chooseTitle;
  			if(!$titleString)
  				$titleString= $defaultTitle;
  			$title= new TitleTag($titleString);
  			$head->add($title);
				$head->add($this->getCssLink());
			$this->headTag= &$head;
			return $head;
		}
		function setBackButtonValue($name)
		{
			$this->sBackButton= $name;
		}
		/*function setMainTable(&$table, $bAdmin= true)
		{
			if(!typeof($table, "ostdbtable", "stdbtablecontainer"))
			{echo get_class($table)."<br />";
				echo "<b>ERROR:</b> first parameter in STUser::setMainTable() ";
				echo "must be an object from class OSTDbTable or STDbTableContainer";
				exit;
			}if(!is_bool($bAdmin))
			{
				echo "<b>ERROR:</b> second parameter in STUser::setMainTable() must be an boolean";
				exit;
			}
			$this->aMainTable["table"]= &$table;
			$this->aMainTable["admin"]= $bAdmin;
		}*/
		function forwardByOneMainTableEntry($toColumn= true)
		{		
			$bForward= true;	
			if(is_bool($toColumn))
			{
				$bForward= $toColumn;
				$toColumn= null;
			}
				
			$this->aMainTable["forward"]= $bForward; 
			$this->aMainTable["column"]= $toColumn;
		}
		function setMainMenueButtonValue($name)
		{
			$this->aMainTable["button"]= $name;
		}
		function setBackButtonAddress($address)
		{
			if($address)
				$this->bBackButton= true;
			else
				$this->bBackButton= false;
			$this->backButtonAddress= $address;
		}
		function hasTableAccess($table, $access, $makeError= false)
		{// funktion zum �berschreiben
		 // wenn nicht �berall ein Zugriff gew�nscht wird
		 	return true;
		}
		function hasContainerAccess($oContainer)
		{// funktion zum �berschreiben
		 // wenn nicht �berall ein Zugriff gew�nscht wird
		 	return true;
		}
		function getBackButton($get_vars)
		{
			if(!$this->bBackButton)
				return null;
					
				
			$get= new GetHtml();
			if($get_vars["action"]!=STLIST)
			{
				$get->getParamString(STUPDATE, "stget[action]=".STLIST);
				$get->getParamString(STDELETE, "stget[link][VALUE]");
				$backButtonAddress= $get->getParamString();
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
  								$button->add($sBackButtonName);
  								$button->onClick("javascript:location='".$backAddress."'");
  							$td->add($button);
  						$tr->add($td);
  					$table->add($tr);
				return $table;
			}
			return null;
		}
		function makeHeadlineButtons(&$divTag, $get_vars)
		{	
			global $HTTP_GET_VARS;
			
			// alex 16/06/2005: definition des BackButtons an den Anfang der Funktion gezogen,
			//					da er bei der Parameter-Auswahl noch ver�ndert wird
    		if($this->bChooseInTable)
    			$sBackButtonName= $this->aMainTable["button"];
    		else
    			$sBackButtonName= $this->sBackButton;

			if(	(	$this->sFirstTableContainerName!=$this->tableContainer->getName()
					and
					$this->bContainerManagement											)
				or
				$this->backButtonAddress
				or
				$get_vars["action"]==STINSERT
				or
				$get_vars["action"]==STUPDATE											)
			{// erzeuge BackButton	
	/*			if(	
					or
					$this->sFirstTableContainerName!=$this->tableContainer->getName()	)
				{*/
					if(!$this->backButtonAddress)
					{
						$get= new GetHtml();
						if(	$get_vars["action"]==STLIST
							or
							$get_vars["action"]==STCHOOSE	)
						{
							// alex 16/06/2005: wenn der action-Parameter auf list steht
							//					kann es auch noch ein Update sein
							//					und der Backbutton soll nicht auf Die Hauptauswahl verweisen
							/*if(isset($get_vars["link"][$this->sUpdateAction]))
							{
								$get->getParamString(STDELETE, "stget[link][".$this->sUpdateAction."]");
								$sBackButtonName= $this->sBackButton;
							}else
							{*/	
								$this->deleteContainer($get);
								$get->getParamString(STDELETE, "stget[firstrow][".$get_vars["table"]."]");
								
							//}
						}/*elseif($get_vars["action"]=="mainlist")
						{
							$get->getParamString(STUPDATE, "stget[action]=mainchoose");
						}*/elseif($get_vars["action"]==STINSERT
								or
								$get_vars["action"]==STUPDATE )
						{
							if($this->tableContainer->sFirstAction==$get_vars["action"])
							{
								if(count($this->tableContainer->aContainer))
								{
									echo "file:".__file__." line:".__line__."<br />";
									echo "toDo: first action for this container is ".$get_vars["action"]."<br />";
									echo "      can also choose to other container<br />";
									echo "      no is what to do?";
									exit;
								}
								$this->deleteContainer($get);
							}else
							{
								$get->update("stget[action]=".STLIST);
								//st_print_r($get->getArrayVars(),10);
								$table= $this->tableContainer->getTable($get_vars["table"]);
								$get->delete("stget[".$get_vars["table"]."][".$table->getPkColumnName()."]");
								//st_print_r($get->getArrayVars(),10);
								$sBackButtonName= $this->sBackButton; 
								//$get->getParamString(STUPDATE, "stget[table]=".$get_vars["table"];
							}
						}elseif($get_vars["action"]==STDELETE)
						{
							$get->getParamString(STUPDATE, "stget[action]=".STCHOOSE);
							$get->getParamString(STDELETE, "stget[table]");
							$get->getParamString(STDELETE, "stget[value]");
						}/*elseif($get_vars["action"]==STCHOOSE)
						{
    						$get->getParamString(STDELETE, "stget[action]");//=".STCHOOSE);
    						$get->getParamString(STDELETE, "stget[table]");
    						$get->getParamString(STDELETE, "stget[firstrow][".$get_vars["table"]."]");
    						$get->getParamString(STDELETE, "stget[container]");
						}*/
						
						
						$sBackButtonContainerName= $HTTP_GET_VARS["stget"]["older"]["stget"]["container"];
						if(!$sBackButtonContainerName)
							$sBackButtonContainerName= $this->sFirstTableContainerName;
							
						$this->addParamsByButton($get, sBackButtonContainerName);
						$backAddress= $this->starterPage.$get->getParamString();
					}else
						$backAddress= $this->backButtonAddress;
				
					if($this->bBackButton)
					{	
    					$backButton= new ButtonTag("backButton");
    						$backButton->add($sBackButtonName);
    						$backButton->onClick("javascript:location='".$backAddress."'");
					}
				//}
			}
			
			$containerButtons= array();
			if(	count($this->tableContainer->aContainer)
				and
				(	$get_vars["action"]==STLIST
					or
					$get_vars["action"]==STCHOOSE	)	)
			{
					$bNeededBackButton= false;
					foreach($this->tableContainer->aContainer as $containerName)
					{
						if($containerName!=$sBackButtonContainerName)
						{
    						$get= new GetHtml();
							$this->addParamsByButton($get, $containerName);
							// is button for an back-container
							$older= $HTTP_GET_VARS["stget"]["older"];
							$olderButtons= 1;
							$isBackButton= false;
							while($older)
							{
								if($older["stget"]["container"])
								{
									++$olderButtons;
									if($older["stget"]["container"]==$containerName)
									{
										$isBackButton= true;
										break;
									}
								}else
									break;
								$older= $older["stget"]["older"];
							}
							
							if($containerName==$this->sFirstTableContainerName)
							{
								$isBackButton= true;
								++$olderButtons;// now delete all container in get-vars
							}
							if($isBackButton)
							{
								for($n=$olderButtons; $n>1; $n--)
								{	
									$get_vars= $get->getArrayVars();				
									$from= $get_vars["stget"]["link"]["from"];
									if(is_array($from))
										foreach($from as $delete)
											$get->getParamString(STDELETE, "stget[".$delete."]");
									$aktParams= $get->getArrayVars();
									if(is_array($aktParams["stget"]["link"]["from"]))
									{
										$links= $aktParams["stget"]["link"]["from"];
										foreach($links as $do)
										{
											foreach($do as $tableName=>$column)
												$get->delete("stget[".$tableName."][".$column."]");
										}
									}
									$get->delete("stget[link][from]");
									$get->delete("stget[table]");
    								$get->delete("stget[action]");
    								$get->delete("stget[container]");
								}
							}else
							{
								if($containerName!=$this->tableContainer->getName())
								{
									if($this->bContainerManagement)
										$make= STINSERT;
									else
										$make= STUPDATE;
    								$get->make($make, "stget[table]=");
    								$get->make($make, "stget[action]=");
    								$get->make($make, "stget[container]=".$containerName);
									$get->make($make, "stget[link][from]=");
								}else
								{
									if(is_array($HTTP_GET_VARS["stget"]["link"]["from"]))
									{
										$linkParams= $HTTP_GET_VARS["stget"]["link"]["from"];
										foreach($linkParams as $from)
										{
											$get->delete("stget[".$from."]");
										}
										$get->delete("stget[link][from]");
									}
								}
							}
    						$sButtonAddress= $get->getParamString();
    						$oContainer= STDbTableContainer::getContainer($containerName);
    						$sButtonName= $oContainer->getIdentification();
							if($this->hasContainerAccess($oContainer))
							{
								$button= new ButtonTag("backButton");
									$button->add($sButtonName);
									$button->onClick("javascript:location='".$sButtonAddress."'");
								$containerButtons[]= $button;
							}
						}else
						{
							$containerButtons[]= $backButton;
							$bNeededBackButton= true;
						}
					}
			}
			// f�ge die buttons geordnet in die Tabelle
    		$table= new TableTag();
    			$table->width("100%");
			if(!$bNeededBackButton)
			{
    			$tr= new RowTag();
    				$td= new ColumnTag(TD);
    					$td->align("right");
    					$td->add($backButton);
    				$tr->add($td);
    			$table->add($tr);
			}
			foreach($containerButtons as $button)
			{
    			$tr= new RowTag();
    				$td= new ColumnTag(TD);
    					$td->align("right");
    					$td->add($button);
    				$tr->add($td);
    			$table->add($tr);
			}
			$divTag->addObj($table);
			
			$this->aBehindHeadLineButtons= array_merge(	$this->aBehindHeadLineButtons,
														$this->tableContainer->aBehindHeadLineButtons);
			$anz= count($this->aBehindHeadLineButtons);
			if($anz)
			{
				for($n= 0; $n<$anz; $n++)
				{
					$tag= &$this->aBehindHeadLineButtons[$n];
					$divTag->addObj($tag);
				}
			}
		}
		function addParamsByButton(&$oParam, $name)
		{
			$getParams= $this->tableContainer->getParmButton[$name];
			if(isset($getParams))
			{
				if(isset($getParams[STINSERT]))
					foreach($getParams[STINSERT] as $param)
						$oParam->insert($param);
				if(isset($getParams[STUPDATE]))
					foreach($getParams[STUPDATE] as $param)
						$oParam->update($param);
				if(isset($getParams[STDELETE]))
					foreach($getParams[STDELETE] as $param)					
						$oParam->delete($param);
			}
		}
		function getCssLink()
		{
			return OSTBaseTableBox::getCssLink();
		}
		function &getHeadline($get_vars)
		{
			$div= new DivTag();
			$this->makeHeadlineButtons($div, $get_vars);
			$project= $this->getProjectIdentifier();
    		if($project)
    		{
      			$div->add(br());
      			$div->add(br());
      			$div->add(br());
      			$div->add(br());
    			$center= new CenterTag();
    				$center->add($project);
    			$div->add($center);
    		}
        	$this->aBehindProjectIdentif= array_merge(	$this->aBehindProjectIdentif,
        												$this->tableContainer->aBehindProjectIdentif);
        	$anz= count($this->aBehindProjectIdentif);
        	if($anz)
        	{
        		for($n= 0; $n<$anz; $n++)
        		{
        			$tag= &$this->aBehindProjectIdentif[$n];
        			$div->addObj($tag);
        		}
        	}
  			$div->add(br());
  			$div->add(br());
			return $div;
		}
		// deprecatet wird in STDbTableContainer verschoben
		function chooseInTable($bChoose)
		{
			$this->bChooseInTable= $bChoose;
		}
		function getChooseTableTag($get_vars)
		{
			$aTables= &$this->tableContainer->getTables();
			$aktTableName= $this->getTableName();
			foreach($aTables as $name=>$table)
			{
				if(	!$this->hasTableAccess($table, STLIST, false)
					or
					(	$name==$aktTableName
						and
						!$this->tableContainer->bDisblayedTable	)
					or
					isset($this->tableContainer->aNoChoice[$name])		)
				{
					$this->aNoChoice[$name]= $name;
				}
			}
			
			$chooseTable= new STChooseTable($this->tableContainer);
			$chooseTable->align("center");
			$chooseTable->setStartPage($this->getStartPage());
			$chooseTable->noChoise($this->aNoChoice);
			$chooseTable->forwardByOne();
    		$nExistTables= $chooseTable->execute();
			if(!$nExistTables)
			{// wenn auf garkeine Tabelle zugegriffen werden kann
			 // erzeuge einen Fehler
				$oTable= reset($aTables);
				$this->hasTableAccess($oTable, STLIST, true);
			}
				
				
			return $chooseTable;
		}
		function makeChooseTags($get_vars)
		{
			$chooseTable= $this->getChooseTableTag($get_vars);
			// alex 18/05/2005:	die Weiterleitung ist jetzt in den STChooseTable verschoben
			//					und muss von aussen angegeben werden			
			/*{
				$Address= $chooseTable->getFirstButtonAddress();
				$Address.= "&stget[onlyone]=true";
				if(Tag::isDebug() )
				{
  					echo "<br /><br /><h1>User would be forwarded to:<br /><a href=\"$Address\">$Address</a>";
					exit;
  				}else
				{
					@header("Location: $Address");
					echo "<br /><br /><h1>Please login at: <a href=\"$Address\">Startpage</a>";
					echo "<script>top.location.href='".addslashes($Address)."';</script>";
					exit;
				}
			}*/
			
			$head= &$this->getHead("Auswahlmenue");
			$this->addObj($head);
			$body= new BodyTag();
				$headline= &$this->getHeadline($get_vars);
				$body->addObj($headline);
    		$body->addObj($chooseTable);
			$this->addObj($body);
		}
		function &getListTable($tableName)
		{
			if($this->oCurrentListTable)
			{
				$table= &$this->oCurrentListTable->getTable();
				if($table->getName()==$tableName)
					return $this->oCurrentListTable;
			}
			
			$table= &$this->tableContainer->getTable($tableName);
			$table->getAllColumns();	//fals noch keine Spalte gesetzt ist
										//vor dem setzen von "aktualisieren" und "l�schen"
										//alle Spalten aus der Datenbank holen
			$this->oCurrentListTable= new OSTTable($this->tableContainer);
			// check Access
 			$this->setAccessForColumnsInTable($table, $this->oCurrentListTable);
			$this->oCurrentListTable->table($table);
			/*if(	$table->bShowColumnsNoAccess
				or
				Tag::isDebug()				)*/
			$this->oCurrentListTable->doContainerManagement($this->bContainerManagement);
			$this->oCurrentListTable->align("center");
			return $this->oCurrentListTable;	
		}
		function setAccessForColumnsInTable(&$oTable, &$oList)
		{
			Tag::echoDebug("access", "<b>setAccessForColumnsInTable</b> \"".$oTable->getName()."\"");
			foreach($oTable->showTypes as $columnName=>$extra)
			{
				foreach($extra as $extraField=>$container)
				{
					if(preg_match("/container.+link/", $extraField))
					{
						$access= $this->hasContainerAccess($container);
						$oList->hasAccess($columnName, $access);
					}
				}
			}
		}
		function makeListTags($get_vars)
		{
			// alex 11/05/2005:	wenn action von $get_vars (param stget[action])
			//					nur choose ist, wurde vom User ein MainTable bestimmt
			/*if($get_vars["action"]==STCHOOSE)
			{
				$table= &$this->aMainTable["table"];
				$tableName= $table->getName();
			}else
			{*/
				$tableName= $get_vars["table"];
				$table= &$this->tableContainer->getTable($tableName);
				if(	$table->oSearchBox
					and
					!isset($this->tableContainer->asSearchBox[$table->oSearchBox->categoryName])	
					and
					(	!$table->oSearchBox->bDisplayByButton
						or
						$get_vars["displaySearch"]=="true"			)									)
				{
					$table->oSearchBox->execute();
					$this->tableContainer->addObjBehindProjectIdentif($table->oSearchBox);
				}
			//}
			
			$list= &$this->getListTable($tableName);
			// add all params from Container for ListTable
//			if(is_array($this->tableContainer->getParmListLinks[$navi["class"]]))
//			{
				foreach($this->tableContainer->getParmListLinks as $action=>$do)
				{
					foreach($do as $param)
					{
						if($action==STINSERT)
							$list->insertParam($param);
						elseif($action==STUPDATE)
							$list->updateParam($param);
						elseif($action==STDELETE)
							$list->deleteParam($param);
					}
				}
//			}			
			
			
			$div= new DivTag();					
			$PK= $table->getPkColumnName();
			if($PK===false)
			{
				Tag::warning("in table $tableName is no preimery key set");
			}else
			{
				if(	(	$table->canUpdate()
						and
						$this->hasTableAccess($table, STUPDATE)	)
					or
					(	$table->canDelete()	
						and
						$this->hasTableAccess($table, STDELETE)	)		)
				{
				    showErrorTrace();
    				$get= new GetHtml();
    				$script= new JavaScriptTag();
    					$function= new jsFunction("selftable_updateDelete", "action", "VALUE");
    						$get->update("stget[action]='+action+'");
    						$get->update("stget[limit][".$tableName."][".$PK."]='+VALUE+'");
							//$get->update("stget[link][from][0][".$tableName."]=".$PK);
							$function->add("bOk= true;");
							$function->add("if(action=='delete')");
							$function->add("    bOk= confirm('wollen Sie diesen Eintrag wirklich l�schen?');");
							$function->add("if(bOk)");
    						$function->add("    location.href='".$get->getParamString()."';");
    				$script->add($function);
					$div->addObj($script);
				}
				
				if(	$table->canUpdate()
					and
					$this->hasTableAccess($table, STUPDATE))
				{
					$list->select($PK, $this->sUpdateAction);
					$list->link($this->sUpdateAction, "javascript:selftable_updateDelete('update',%VALUE%);");
					//$list->setAsLinkParam($this->sUpdateAction, "id");
					//$list->setParamOnActivate(STUPDATE, "stget[action]=update", $this->sUpdateAction);
				}
				if($table->canDelete()
					and
					$this->hasTableAccess($table, STDELETE))
				{
					$list->select($PK, $this->sDeleteAction);				
					$list->link($this->sDeleteAction, "javascript:selftable_updateDelete('delete',%VALUE%);");
					//$list->setAsLinkParam($this->sDeleteAction, "id");
					//$list->setParamOnActivate(UPDATE, "stget[action]=delete", $this->sDeleteAction);
				}
			} 	
			
			$get= new GetHtml();
			// alex 02/09/2005:	wenn gel�scht wird,
			//					wird jetzt die Funktion "deleteTableEntry()" aufgerufen
/*			if(isset($get_vars["link"][$this->sDeleteAction]))
			{
				$table= &$this->tableContainer->getTable($get_vars["table"]);
				$box= new OSTBox($this->tableContainer);
				$box->table($table);
				$box->where($PK."=".$get_vars["link"][$this->sDeleteAction]);
				$box->onOkGotoUrl($get->getParamString(STDELETE, "stget[link][".$this->sDeleteAction."]"));
				$this->setAllMessagesContent(STDELETE, $box);
				$result= $box->delete();
				$this->addObj($box);
			}
			if(	!isset($get_vars["link"][$this->sDeleteAction])
				or	
				isset($result)										)*/
//			{	// alex 14/06/2005:	setAllMessagesContent kurz vor das execute verschoben,
				//					da ich auch ein setAllMessagesContent f�r OSTBox eingef�gt habe
				$this->setAllMessagesContent(STLIST, $list);
				$result= $list->execute();
//				if(!isset($result))
//					$result= $result2;
//			}
			$params= $get->getParamString(STUPDATE, "stget[action]=".STINSERT);
			
			if($get_vars["action"]==STCHOOSE)
			{
				$exist= !$this->aMainTable["admin"];
			}else
				$exist= !$table->canInsert();
			if(	$table->canInsert()
				and
				$this->hasTableAccess($table, STINSERT)	)
			{
					$center= new CenterTag();
					$button= new ButtonTag("newEntry");
						$button->add($list->sNewEntry);
						$button->onClick("javascript:location='".$this->getStartPage().$params."'");
					$center->addObj($button);
					$center->add(br());
			}
    		$div->addObj($center);
			$div->addObj($list);
			$this->oMainTable= &$list;
			$this->addAllInSide($get_vars, $div, "Auflistung ".$table->getIdentifier());
			return $result;
		}
		function addAllInSide($get_vars, &$createdTags, $titel)
		{
			$tableName= $get_vars["table"];
			$table= &$this->tableContainer->getTable($tableName);
				
			$head= &$this->getHead($titel);
			$head->add($script);
			$this->addObj($head);
			$body= new BodyTag();
  			$headline= &$this->getHeadline($get_vars);
			$body->addObj($headline);
			
			if(	$this->bChooseInTable
				and
				$this->tableContainer->bChooseInTable	)
			{
				$body->add($this->getChooseTableTag($get_vars));
			}
			
			if($table->bShowName)
			{
				$tableIdentifier= $table->getIdentifier();
				if($tableIdentifier)
				{
					$div= new DivTag();
						$div->align("center");
						$h2= new H2Tag();
							$h2->add($tableIdentifier);
						$div->addObj($h2);
					$body->addObj($div);
				}
			}
			$this->aBehindTableIdentif= array_merge(	$this->aBehindTableIdentif,
														$this->tableContainer->aBehindTableIdentif);
			$count= count($this->aBehindTableIdentif);
			for($o= 0; $o<$count; $o++)
			{
				//echo "<br />OK<br />";
				//print_r($this->aBehindTableIdentif[$o]);
					$body->addObj($this->aBehindTableIdentif[$o]);
			}
					
			// navigationTables mit null-pos einbinden
			// sowie die anderen abz�hlen
			/////////////////////////////////////////////////////////////////
			$bNeedNavis= false;
			$nNeedTopNavis= 0;
			$nNeedRightNavis= 0;
			$nNeedBottomNavis= 0;
			$nNeedLeftNavis= 0;
			foreach($this->tableContainer->aNavigationTables as $key=>$navi)
			{
				
				$list= new OSTTable($this->tableContainer->db, $navi["class"]);
				$this->setAccessForColumnsInTable($navi["table"], $list);
				$list->table($navi["table"]);
				$list->doContainerManagement($this->bContainerManagement);
				if(is_array($this->tableContainer->getParmNavigation[$navi["class"]]))
				{
					foreach($this->tableContainer->getParmNavigation[$navi["class"]] as $action=>$do)
					{
						foreach($do as $param)
						{
							if($action==STINSERT)
								$list->insertParam($param);
							elseif($action==STUPDATE)
								$list->updateParam($param);
							elseif($action==STDELETE)
								$list->deleteParam($param);
						}
					}
				}	
				$list->execute();
				$this->tableContainer->aNavigationTables[$key]["tabelName"]= $navi["table"]->getDisplayName();
				$this->tableContainer->aNavigationTables[$key]["list"]= &$list;
				if($navi["pos"]!==null)
				{
					if($navi["pos"]==STTOP)
						++$nNeedTopNavis;
					elseif($navi["pos"]==STRIGHT)
						++$nNeedRightNavis;
					elseif($navi["pos"]==STBOTTOM)
						++$nNeedBottomNavis;
					elseif($navi["pos"]==STLEFT)
						++$nNeedLeftNavis;
					$bNeedNavis= true;
				}else
					$body->add($list);
			}
			/////////////////////////////////////////////////////////////////
			
			if($bNeedNavis)
			{
				$table= new TableTag();
					$table->width("100%");
				if($nNeedTopNavis)
				{
					$ttr= new RowTag();
						$ttd= new ColumnTag(TD);
					foreach($this->tableContainer->aNavigationTables as $key=>$navi)
					{
						if($navi["pos"]==STTOP)
							$ttd->addObj($this->tableContainer->aNavigationTables[$key]["list"]);
					}
						$ttr->addObj($ttd);
					$table->addObj($ttr);
				}
					$mtr= new RowTag();
				if($nNeedLeftNavis)
				{
						$ltd= new ColumnTag(TD);
					foreach($this->tableContainer->aNavigationTables as $key=>$navi)
					{
						if($navi["pos"]==STLEFT)
							$ltd->addObj($this->tableContainer->aNavigationTables[$key]["list"]);
					}
						$mtr->addObj($ltd);
				}
						$mtd= new ColumnTag(TD);
							$mtd->addObj($createdTags);
						$mtr->addObj($mtd);
				if($nNeedRightNavis)
				{
						$rtd= new ColumnTag(TD);
					foreach($this->tableContainer->aNavigationTables as $key=>$navi)
					{
						if($navi["pos"]==STRIGHT)
							$rtd->addObj($this->tableContainer->aNavigationTables[$key]["list"]);
					}
						$mtr->addObj($rtd);
				}
					$table->addObj($mtr);
					
				if($nNeedBottomNavis)
				{
					$btr= new RowTag();
						$btd= new ColumnTag(TD);
					foreach($this->tableContainer->aNavigationTables as $key=>$navi)
					{
						if($navi["pos"]==STBOTTOM)
							$btd->addObj($this->tableContainer->aNavigationTables[$key]["list"]);
					}
						$btr->addObj($btd);
					$table->addObj($btr);
				}
				$body->addObj($table);
			}else
				$body->addObj($createdTags);
			$this->addObj($body);
		}
		function &getMainTable()
		{
			return $this->oMainTable;
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
			Tag::alert(1, "STDbSiteCreator::getNavigationTable()", 
							"table-display-name ".$tableDisplayName." not set in aktual container");
			st_print_r($this->tableContainer->aNavigationTables,2);
			$b= new BTag();
			$b->add("NO TABLE SET");
			return $b;
		}
		function deleteTableEntry($get_vars)
		{
			$table= &$this->tableContainer->getTable($get_vars["table"]);
			$PK= $table->getPkColumnName();
			$box= new OSTBox($this->tableContainer);
			$box->table($table);
			$box->where($PK."=".$get_vars["link"]["VALUE"]);
			//$box->onOkGotoUrl($get->getParamString(STDELETE, "stget[link][".$this->sDeleteAction."]"));
			$get= new GetHtml();
			$get->getParamString(STDELETE, "stget[link][VALUE]");
			$get->getParamString(STUPDATE, "stget[action]=".STLIST);
			$box->msg->onEndGotoUrl($get->getParamString());
			$this->setAllMessagesContent(STDELETE, $box);
			$result= $box->delete();
			$this->addObj($box);
			return $result;
		}
		function deleteContainer(&$oGetParam)
		{
			$oGetParam->delete("stget[table]");
			$oGetParam->delete("stget[action]");
			$oGetParam->delete("stget[container]");
			$vars= $oGetParam->getArrayVars();
			$fromLink= $vars["stget"]["link"]["from"];
			if(is_array($fromLink))
			{
				foreach($fromLink as $link)
				{
					if(is_array($link))
						foreach($link as $table=>$column)
							$oGetParam->delete("stget[".$table."][".$column."]");
				}
			}
			$oGetParam->delete("stget[link]");
		}
		function makeInsertUpdateTags($get_vars)
		{
			$table= &$this->tableContainer->getTable($get_vars["table"]);
			$box= new OSTBox($this->tableContainer);
			$box->align("center");
			$get= new GetHtml();
			if($this->tableContainer->sFirstAction==$get_vars["action"])
			{// if first action is set to STUPDATE or STINSERT
			 // user can not choose any other actions in this table
			 // so go back to last container
				if(count($this->tableContainer->aContainer))
				{
					echo "file:".__file__." line:".__line__."<br />";
					echo "toDo: first action for this container is ".$get_vars["action"]."<br />";
					echo "      can also choose to other container<br />";
					echo "      no is what to do?";
					exit;
				}
			 	$this->deleteContainer($get);
			}else
			{
				$get->update("stget[action]=".STLIST);
				$get->delete("stget[".$get_vars["table"]."][".$table->getPkColumnName()."]");
			}
			$getParameter= $get->getStringVars();		
			$box->onOKGotoUrl($this->getStartPage().$getParameter);
			if($get_vars["action"]==STINSERT)
			{
				$box->table($table);
				$this->setAllMessagesContent(STINSERT, $box);
				$head= &$this->getHead("neuer Eintrag in ".$table->getIdentifier());
				$result= $box->insert();
			}else			
			{
				$box->table($table);
				$this->setAllMessagesContent(STUPDATE, $box);
				$this->db->foreignKeyModification($table);
				//st_print_r($table->oWhere);
				//$whereStatement= $this->db->getWhereStatement($table, "t1");
				//echo "statement ".$whereStatement."<br />";
				//$whereStatement= $table->getPkColumnName();
				//$whereStatement.= "=".$get_vars["link"]["VALUE"];
				//$where= new STDbWhere($whereStatement);
				//$box->where($where);
				$head= &$this->getHead("Eintrag aktualisieren in ".$table->getIdentifier());
				$result= $box->update();
			}				
				
			
			$this->oMainTable= &$box;
			$this->addObj($head);
			$body= new BodyTag();
				$headline= &$this->getHeadline($get_vars);
				$body->addObj($headline);
				$center= new CenterTag();
					$h2= new H2Tag();
						$h2->add($table->getIdentifier());
					$center->addObj($h2);
					$center->add(br());
    		$body->addObj($center);
				$body->addObj($box);
			$this->addObj($body);
			return $result;
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
			if(!isset($this->aCallbacks[$action][$table]))
				$this->aCallbacks[$action][$tableName]= array();
			$this->aCallbacks[$action][$tableName][$columnName]= $callbackFunction;
		}
		// setzt alle gew�nschten Fehler-Meldungen
		// und Callbacks
		// in den divirsen Objekten, welche dann mit 
		// execute ausgef�hrt werden.
		//
		// param $table muss ein objekt vom typ OSTBaseTableBox sein
		/*private*/ function setAllMessagesContent($action, &$oBox)
		{
			$table= &$oBox->getTable();
			$tableName= $table->getName();
			$aError= $this->asError[$action][$tableName];
			if(!isset($aError))
				$aError= $this->asError[$tableName];
			if(!isset($aError))
				$aError= $this->asError["all"];
			if(isset($aError))
			{// erzeuge neue vom user deffinierte Fehler-Meldungen
				foreach($aError as $error=>$message)
				{
					$oBox->setMessageContent($error, $message);
				}
			}
			// setze Callback
			if(!isset($this->aCallbacks))
				return;
			if(!isset($this->aCallbacks[$action]))
				return;
			if(!isset($this->aCallbacks[$action][$tableName]))
				return;
			foreach($this->aCallbacks[$action][$tableName] as $columnName=>$function)
				$oBox->callback($columnName, $function);
		}
	function &getContainer()
	{
		global $HTTP_GET_VARS;
	
		$newContainerName= $HTTP_GET_VARS["stget"]["container"]; 
		if(	$newContainerName
			and
			$newContainerName!=$this->tableContainer->getName())
		{
			$this->tableContainer= &STDbTableContainer::getContainer($newContainerName);
		}
		return $this->tableContainer;
	}
	function getContainerName()
	{
		global $HTTP_GET_VARS;
	
		$containerName= $HTTP_GET_VARS["stget"]["container"];
		if(!$containerName)
			$containerName= $this->tableContainer->getName();
		return $containerName;
	}
	function &getOlderContainer()
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
	}
	function getAction()
	{
		$container= &$this->getContainer();
		return $container->getAction();
	}
	function getTableName()
	{
		$container= &$this->getContainer();
		return $container->getTableName();
	} 	
	function getContainerIdentification()
	{
		$this->getContainer();//setzt den $this->tableContainer
		return $this->tableContainer->getIdentification();
	}
}

?>