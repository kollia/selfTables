<?php

require_once($php_html_description);
require_once($php_htmltag_class);
require_once($_stbasecontainer);
require_once($_stdbtablecreator);
require_once($_stdbtabledescriptions);
require_once($_sttable);
require_once($_stbox);


class STObjectContainer extends STBaseContainer
{
	var $db; // Datenbank-Objekt
	var $headTag;
	var $chooseTitle;
	var $language= "en";
	var $sDefaultCssLink;
	var	$parentContainerName; // name of the parent container
	var	$oGetTables= array(); // all tables which are geted but not needed
	var $tables= array(); // alle STDbTable Objekte welche f�r die Auflistung gebraucht werden
	var	$sFirstTableName; //erste Tabelle
	var $sFirstAction= null; // erste Aktion nicht gesetzt hole von Tabelle
	var $actAction= ""; // current action from this container
	var $actTableName= ""; // actual main table in this container
	var	$actions= array(); // welche Aktion die Tabelle in der ersten auflistung haben soll
	var $bChooseByOneTable= false; // soll auch bei einer Tabelle die Auswahl erscheinen?

	var	$bChooseInTable= true; // ob die anderen Tabellen als Button angezeigt werden sollen
	var $bDisplayedTable= false; // ob auch die gerade angezeigte Tabelle als Button angezeigt werden soll

	var	$aNoChoice= array(); // wenn eine Tabelle in der Auswahl nicht angezeigt werden soll
							 // aber wegen Einstellungen gebraucht wurde
							 // kann die Auswahl damit herausgenommen werden
	var	$oExternSideCreator;
	var $aBehindTableIdentif= array();
	var $oCurrentListTable;
	var $bDisblayedTable= false;

		var	$sInsertAction;
		var	$sUpdateAction;
		var	$sDeleteAction;
		var $sNewEntry;

	function __construct($name, &$container)
	{
		Tag::paramCheck($name, 1, "string");
		Tag::paramCheck($container, 2, "STObjectContainer");

		$this->db= &$container->getDatabase();
		$this->parentContainerName= $container->getName();
		STBaseContainer::__construct($name);
	}
	function setLanguage($lang)
	{
		STCheck::param($lang, 1, "string");
		
		if(	$lang != "en" &&
			$lang != "de"	)
		{
			echo "<b>only follow languages are allowed:</b><br />";
			echo "                   en   -   english<br />";
			echo "                   de   -   german<br />";
			printErrorTrace();
			exit;
		}
		$this->language= $lang;
	}
	function createMessages()
	{
		if($this->language == "de")
		{
			$this->sInsertAction= "einfuegen";
			$this->sUpdateAction= "aktualisieren";
			$this->sDeleteAction= "loeschen";
			$this->sNewEntry= " neuer Eintrag ";
			//$this->msg->setMessageContent("DELETE_QUESTION", "wollen Sie diesen Eintrag wirklich löschen?");
			$this->sDeleteQuestion= "wollen Sie diesen Eintrag wirklich löschen?";
			
		}else // otherwise language have to be english "en"
		{
			$this->sInsertAction= "insert";
			$this->sUpdateAction= "update";
			$this->sDeleteAction= "delete";
			$this->sNewEntry= " new Entry ";
			//$this->msg->setMessageContent("DELETE_QUESTION", "do you want to delete this entry?");
			$this->sDeleteQuestion= "do you want to delete this entry?";
		}
	}
	function describeTables($description)
	{
		// this function is only for container which have spezific tables
		// in the database where the classes be exidet
	}
	function chooseInTable($bChoose)
	{
		Tag::paramCheck($bChoose, 1, "bool");
		$this->bChooseInTable= $bChoose;
	}
	function showDisplayedTableButton($show)
	{
		Tag::paramCheck($show, 1, "bool");
		$this->bDisplayedTable= $show;
	}
	function &getDatabase()
	{
		return $this->db;
	}
	function getDatabaseName()
	{
		return $this->db->getDatabaseName();
	}
	function &needEmptyTable($sTableName)
	{
		STCheck::paramCheck($sTableName, 1, "string");
		
		return $this->needTable($sTableName, /*empty*/true);
	}
	function &needTable($sTableName, $bEmpty= false)
	{
		STCheck::paramCheck($sTableName, 1, "string");
		STCheck::paramCheck($bEmpty, 2, "null", "boolean");

		$this->initContainer();
		$orgTableName= $this->getTableName($sTableName);
    	if($orgTableName)
    	    $sTableName= $orgTableName;
		else
		// not all databases save the tables case sensetive
			$orgTableName= $sTableName;
		STCheck::echoDebug("table", "need table <b>$sTableName</b> in container <b>".$this->getName()."</b>");
		$sTableName= strtolower($sTableName);
		$table= null;
		if(isset($this->tables[$sTableName]))
		  $table= &$this->tables[$sTableName];
		if(isset($this->oGetTables[$sTableName]))
		  $table= &$this->oGetTables[$sTableName];
		
		if(isset($table))
		{
			$this->tables[$sTableName]= &$table;
		}else
		{
		    if(STCheck::isDebug())
		    {
		        $msg= "table <b>$orgTableName</b> do not exist insite current container <b>".$this->getName()."</b>";
		        if(STCheck::isDebug("table"))
		            STCheck::echoDebug("table", $msg);
	            else
	                STCheck::echoDebug("db.statements.table", $msg);
		    }
			// alex 17/05/2005:	die Tabelle wird nun von der �berschriebenen Funktion
			//					getTable() aus dem Datenbank-Objekt erzeugt.
			// alex 08/06/2005: 2. Parameter f�r getTable auf false gesetzt
			//					da sonst wenn das erste mal needTable aufgerufen wird,
			//					bei der funktion getTable aus der Datenbank,
		    //					alle Tabellen in die this->tables geladen werden
			if(!typeof($this, "STDatabase"))
			{
				$container= &STBaseContainer::getContainer($this->parentContainerName);
				$newTable= clone $container->getTable($orgTableName);//, false);
			}else
			    $newTable= &$this->getTable($orgTableName);//, false)
			if(	!isset($newTable) ||
				!is_object($newTable)	)
			{
				if($bEmpty === true)
				{
					$table= new STAliasTable($sTableName);
					$table->container= &$this;
					$this->tables[$sTableName]= $table;
				}else
				    $table= null;
				return $table;
			}
			if($this->getName()===$this->db->getName())
			{// own container is database,
			 // so table is inserted in ->oGetTables
			 // and must only insert in ->tables
			    $this->tables[$sTableName]= &$newTable;
				return $newTable;
			}
			if(  !isset($this->tables[$sTableName]) ||
			    !is_object($this->tables[$sTableName])   )
			{
			    $this->tables[$sTableName]= &$newTable;
			}
			return $newTable;
		}
		return $table;
	}
	function &getTable($tableName= null)//, $bAllByNone= true)
	{
		STCheck::paramCheck($tableName, 1, "string", "null");
		//Tag::paramCheck($bAllByNone, 2, "bool");
		$nParams= func_num_args();
		Tag::lastParam(1, $nParams);

		// method needs initialization properties
		// only if he is not initializised
		// and not in the create or initialize phase
		// to know which tables are defined
		if(	$this->bCreated===null
			or
			(	$this->bCreated===true
				and
				$this->bInitialize===null	)	)
		{
			$this->initContainer();
		}

		if($tableName==null)
		{
		    $tableName= $this->getTableName();
		}
		if(!$tableName)
		{
			Tag::echoDebug("table", "no table to show difined for this container ".get_class($this)."(".$this->getName().")");
			Tag::echoDebug("table", "or it not be showen on the first status");
			$Rv= null;
			return $Rv;
		}

		$orgTableName= $this->getTableName($tableName);
    	if($orgTableName)
    	    $tableName= $orgTableName;
		else
		// not all databases save the tables case sensetive
			$orgTableName= $tableName;
		$tableName= strtolower($tableName);
		// alex 08/07/2005: die Tabelle wird nun auch ohne Referenz geholt
		//					�nderungen jetzt ausserhalb m�glich
		//					und die Tabelle ist dann nicht automatisch in $this->tables eingetragen
		//					wenn hier eine Referenz geholt w�rde w�re sie automatisch
		//					bei $this->db->getTable in $this->tables eingetragen
		$table= &$this->tables[$tableName];
		if(!$table)
		{
			unset($this->tables[$tableName]);
			$table= &$this->oGetTables[$tableName];
		}
		if(!$table)
		{	// alex 24/05/2005:	// alex 17/05/2005:	die Tabelle wird von der �berschriebenen Funktion
			//					getTable() aus dem Datenbank-Objekt geholt.
			//					nat�rlich wird die Tabelle kopiert, da sie wom�glich noch ge�ndert wird
			$container= &STBaseContainer::getContainer($this->parentContainerName);
			$table= clone $container->getTable($orgTableName);//, $bAllByNone);
			if($table)
			{
				$table->abNewChoice= array();
				$table->bSelect= false;
				$table->bTypes= false;
				$table->bIdentifColumns= false;
				unset($table->container);
				$table->container= $this;
				$this->oGetTables[$tableName]= &$table;
			}else
				unset($this->oGetTables[$tableName]);
		}
		Tag::echoDebug("table", "get table <b>$tableName</b> from container <b>".$this->getName()."</b>");
		return $table;
	}
	function &getTables($onError= onErrorStop)
	{
		// method needs initialization properties
		// only if he is not initializised
		// and not in the create or initialize phase
		// to know which tables are defined
		if($this->bCreated===null)
		{
			$this->createContainer();
		}
		if(!count($this->tables))
  		{
			$tableNames= $this->db->list_tables($onError);
			// alex 17/05/2005:	damit die Funktionen nicht im Kreis rennen
			//					habe ich den parameter bAllByNone eingef�hrt,
			//					welcher bei (true) besagt, dass wenn noch keine Tabelle
			//					in der gebrauchten tables Liste ist,
			//					dass sie mit allen aus der Datenbank
			//					vervollst�ndigt wird.
			$bAllByNone= false;
			// alex 17/05/2005:	die Tabellen werden nun von der �berschriebenen Funktion
			//					getTable() aus dem Datenbank-Objekt erzeugt.
			foreach($tableNames as $name)
				$this->tables[$name]= $this->db->getTable($name);//, $bAllByNone);
		}
		//st_print_r($this->tables, 1);
		return $this->tables;
	}
	function setInTableNewColumn($tableName, $columnName, $type)
	{
		$tableName= $this->getTableName($tableName);
		if(isset($this->tables[$tableName]))
		{
			$this->tables[$tableName]->dbColumn($columnName, $type, $len);
		}
	}
	function setInTableColumnNewFlags($tableName, $columnName, $flags)
	{
		$tableName= $this->getTableName($tableName);
		if(isset($this->tables[$tableName]))
		{
			$this->tables[$tableName]->columnFlags($columnName, $flags);
		}
	}
	function setFirstTable($tableName, $action= null)
	{
		Tag::paramCheck($tableName, 1, "string");
		Tag::paramCheck($action, 2, "check", $action===STLIST||$action===STINSERT||$action===STUPDATE||$action===null,
										"STLIST", "STINSERT", "STUPDATE");

		$tableName= $this->getTableName($tableName);
		$this->sFirstTableName= $tableName;
		if($action===null)
		{// take the table from needTable,
		 // because if be set this function before
		 // get an other table, the db container
		 // fetch all from the database if fetched with getTable
			$table= &$this->needTable($tableName);
			$this->sFirstAction= &$table->sFirstAction;
		}else
			$this->sFirstAction= $action;
		$this->actions[$tableName]= $action;
	}
	function haveTable($tableName)
	{
		// method needs initialization properties
		// to know which tables are defined
		$this->createContainer();

		$tableName= $this->getTableName($tableName);
		foreach($this->oGetTables as $table=>$content)
		{
			if(preg_match("/".$table."/i", $tableName))
				return true;
		}
		return false;
	}
	function stgetParams($containerName= null)
	{
		Tag::paramCheck($containerName, 1, "string", "null");

		if($containerName===null)
			$containerName= $this->getName();
		$default= STObjectContainer::getContainer();
		$default= $default->getName();
		$params= new STQueryString();
		$stget= $params->getArrayVars();
		if(isset($stget["stget"]))
			$stget= $stget["stget"];
		else
			$stget= null;
		while($stget)
		{
			if(	(	isset($stget["container"]) &&
					$stget["container"]===$containerName	) ||
				(	!isset($stget["container"]) &&
					$containerName===$default		)			)
			{
				return $stget;
			}
			if(isset($stget["older"]["stget"]))
				$stget= $stget["older"]["stget"];
			else
				$stget= null;
		}
		return null;
	}
	function getTableName($tableName= null)
	{
		STCheck::param($tableName, 0, "string", "null");

		$bStdTab= false;
		if($tableName === null)
		{
			$bStdTab= true;
			if($this->actTableName != "")
				return $this->actTableName;
		}
		if(!$tableName)
		{
			$stget= $this->stgetParams();
			if(	isset($stget["table"]) &&
				is_string($stget["table"])	)
			{
				$tableName= $stget["table"];
			}
		}else
		{

			$description= &STDbTableDescriptions::instance($this->db->getName());
			// getTableName from STDbTableDescriptions
			// return only an different name when before
			// was defined an other tablename
			$orgTableName= $description->getTableName($tableName);
			if(!$orgTableName)
			{// maby the table-name is lower case
				$oTable= &$this->db->oGetTables[$tableName];
				if($oTable)
					$orgTableName= $oTable->getName();
				if(!$orgTableName)
				{
					foreach($this->aExistTables as $sTable=>$content)
					{
						if($tableName===strtolower($sTable))
						{
							$tableName= $sTable;
							break;
						}
					}
					$orgTableName= $this->aExistTables[$tableName]["table"];
				}
			}else
		    	$tableName= $orgTableName;
		}
		if($tableName)
		{
			if($bStdTab)
				$this->actTableName= $tableName;
			return $tableName;
		}
		$tableName= $this->getFirstTableName();
		if(	( !isset($tableName) || !trim($tableName) )
			and
			count($this->tables)==1	)
		{// get tableName from table-object not from key,
		 // because there only lower case
    		$table= &reset($this->tables);
    		$tableName= $table->getName();
		}
		if( ( !isset($tableName) ||
		      !trim($tableName)     ) &&
		    $this->parentContainerName != $this->getName()    )
		{
		    $container= &STBaseContainer::getContainer($this->parentContainerName);
		    $tableName= $container->getTableName();
		}
		if($bStdTab)
			$this->actTableName= $tableName;
		return $tableName;
	}
	function setFirstActionOnTable($action, $tableName)
	{
		$tableName= $this->getTableName($tableName);
		$this->actions[$tableName]= $action;
	}
	function getActions()
	{
		$this->createContainer();
		foreach($this->tables as $table)
		{
			$tableName= $table->getName();
			if(!isset($this->actions[$tableName]))
			{
				$action= $table->getFirstAction();
				if(!isset($action))
				    $action= STLIST;
				$this->actions[$tableName]= $action;
			}
		}
		return $this->actions;
	}
	var $actFirstTable= "";
	function getFirstTableName()
	{
		// method needs initialization properties
		// only if he is not initializised
		// and not in the create or initialize phase
		// to know which tables are defined
		if(	$this->bCreated===null
			or
			(	$this->bCreated===true
				and
				$this->bInitialize===null	)	)
		{
			$this->initContainer();
		}

		$tableName= $this->sFirstTableName;
		if(	$tableName
			and
			$this->askForAcess()	)
		{
			$table= &$this->getTable($tableName);
			$action= $this->sFirstAction;
			if(!$action)
				$action= STLIST;
			if(!$table->hasAccess($action))
			{
				$this->sFirstAction= STCHOOSE;
				$this->sFirstTableName= "";
				$tableName= "";
			}
		}
		if(	!$tableName
			and
			count($this->tables)==1
			and
			!$this->bChooseByOneTable	)
    	{// get tableName from table-object not from key,
		 // because there only lower case
    	    $table= reset($this->tables);
    		if(!$table)
    		{
    			$keys= array_keys($this->tables);
    			$tableName= reset($keys);
    		}else
    		    $tableName= $table->getName();
    	}
		return $tableName;
	}
	function askForAcess()
	{
		if(	STSession::sessionGenerated()
			and
			typeof($this->oExternSideCreator, "STSessionCreator")	)
		{
			return true;
		}
		return false;

	}
	function getFirstAction($tableName= null)
	{
		$tableName= $this->getTableName($tableName);
		$action= $this->sFirstAction;
		if(	!isset($action) ||
			trim($action) == ""		)
		{
		    if(   isset($tableName) &&
		          $tableName != ""        )
		    {
    			$actions= $this->getActions();
    			if(	isset($tableName) &&
    				is_string($tableName) &&
    				trim($tableName) != "" &&
    				isset($actions[$tableName])	)
    			{
    				return $actions[$tableName];
    			}
    			if(	count($actions)==1 &&
    				!$this->bChooseByOneTable	)
    			{
    				$action= reset($actions);
    			}
		    }
		          
		    if(   !isset($action) ||
		          trim($action) == "" )
		    {
		        $table= $this->getTable();
		        $action= $table->getAction();
		        if(   !isset($action) ||
		              trim($action) == "" )
		        {
		            
		        }
		    }
		}
		return $action;
	}
	function getAction()
	{
		if(	isset($this->actAction) &&
			trim($this->actAction) != ""	)
		{
			return $this->actAction;
		}
		$query= new STQueryString();
		$action= $query->getAction();
		$table= $this->getTableName();
		if(	!isset($action) ||
			trim($action) == "" ||
			!isset($table) ||
			trim($table) == ""		)// wenn die Tabelle nicht stimmt, stimmt auch die Aktion nicht
		{
		    $action= $this->getFirstAction();
		}
		if(	$action==STCHOOSE
			and
			count($this->tables)==1	)
		{
			$table= reset($this->tables);
			$action= $this->sFirstTableAction[$table];
			if(!$action)
				$action= STLIST;
		}
		$this->actAction= $action;
		return $action;
	}
	function chooseAlsoByOneTable()
	{
		$this->bChooseByOneTable= true;
	}
	function doChoice($table, $choice= true)
	{
		STCheck::param($table, 0, "string");
		STCheck::param($choice, 1, "boolean");

		if(typeof($table, "STAliasTable"))
			$table= $table->getName();
		else
			$table= $this->getTableName($table);
		if($choice)
			unset($this->aNoChoice[$table]);
		else
			$this->aNoChoice[$table]= $table;
	}
	function tableChoice($table)
	{
		STCheck::param($table, 0, "string", "STAliasTable");

		if(typeof($table, "STAliasTable"))
			$table= $table->getName();
		else
			$table= $this->getTableName($table);

		if(isset($this->aNoChoice[$table]))
			return false;
		$tables= &$this->getTables();
		if($tables[strtolower($table)])
			return true;
		return false;
	}
    function getAddressToContainer($bHtmlArray= false)
    {
    	Tag::paramCheck($bHtmlArray, 1, "boolean", "STQueryString");

    	$bAsObject= false;
    	if(typeof($bHtmlArray, "STQueryString"))
    	{
    		$bAsObject= true;
    		$oGet= &$bHtmlArray;
    	}else
    		$oGet= new STQueryString();

    	$oGet= STBaseContainer::getAddressToContainer($oGet);
		$this->getAddressToNextContainer($oGet);

    	if($bAsObject)
    		return $oGet;
    	if($bHtmlArray)
    		return $oGet->getArrayVars;
    	return $oGet->getStringVars;
    }
	function deleteContainer(&$oGetParam)
	{
		Tag::paramCheck($oGetParam, 1, "STQueryString");
		Tag::echoDebug("containerChoice", "delete container ".$this->getName()." in object STObjectContainer");
		$params= $oGetParam->getArrayVars();
		if(isset($params["stget"]["container"]))
			$container= $params["stget"]["container"];
		else
			$container= null;
		if(!$container)
		{// container is the first,
		 // cannot delete any more^
		 	return false;
		}

		STBaseContainer::deleteContainer($oGetParam);
		$params= $oGetParam->getArrayVars();
		if(isset($params["stget"]["container"]))
			$container= $params["stget"]["container"];
		if(!isset($container))
		{// older container must be the first
			global	$global_first_objectContainerName;

			$container= $global_first_objectContainerName;
		}
		$container= &$this->getContainer($container);
		$result= $container->hasDynamicAccessToOneEntry();
		if($result)
		{
			return $container->deleteContainer($oGetParam);
		}
		return true;

	}
	function getAddressToNextContainer(&$oGetParam)
	{
		$table= &$this->getTable();
		$result= $this->hasDynamicAccessToOneEntry();
		if($result)
		{
			if(!$result["address"])
			{
        		$oGetParam->insert("stget[".$table->getName()."][".$result["column"]."]=".$result["columnEntry"]);
        		$oGetParam->insert("stget[link][from][0][".$table->getName()."]= ".$result["column"]);
        		$oGetParam= $result["container"]->getAddressToContainer($oGetParam);
        	}else
        		$oGetParam= new STQueryString($result["address"]);
	        return true;
        }
		return false;
	}
	function hasDynamicAccessToOneEntry()
	{
		$countTable= $this->getTable();
    	if(isset($countTable->aForward["do"]))
    	{// when table be set to forward by one entry
		 // search before on which column it should be forwarded
    		$showTypes= $countTable->showTypes;
    		$column= $countTable->aForward["column"];
    		if(!$column)
    		{
      			$bFound= false;
      			foreach($showTypes as $alias=>$link)
      			{
      				foreach($link as $type=>$to)
      				{
      					if(preg_match("/link/i", $type))
      					{
      						$bFound= true;
							// variable $alias be set in the first foreach loop
							$field= $countTable->findAliasOrColumn($showTypes["valueColumns"][$alias]);
							$column= $field["column"];
      						break;
      					}
      				}
      				if($bFound)
      					break;
      			}
    		}else
			{
    			$bFound= true;
				$field= $countTable->findColumnOrAlias($column);
				$alias= $field["alias"];
				$column= $showTypes["valueColumns"][$alias];
			}
			if($bFound)
			{// if an column found, look in table
			 // whether is one entry

				// first create all with access cluster
				$accessActions= $countTable->createDynamicAccess();
				if(	$accessActions[STLIST]==1
					and
					(	(	!$accessActions[STADMIN]
        					and
        					!$accessActions[STINSERT]
        					and
        					!$accessActions[STUPDATE]
        					and
        					!$accessActions[STDELETE]	)
						or
						(	!$countTable->bInsert
							and
							!$countTable->bUpdate
							and
							!$countTable->bDelete		)	)	)
				{
        			$countTable->clearSelects();
        			$countTable->clearGetColumns();
        			$countTable->select($column);
    				$countTable->limit(2);
        			$statement= $this->db->getStatement($countTable);
        			$count= $this->db->fetch_single_array($statement);
        			if(count($count)==1)
        			{
						$aRv= array("column"=>$column);
						$aRv["columnEntry"]= $count[0];
            			foreach($showTypes[$alias] as $type=>$to)
            			{
            				if(preg_match("/link/i", $type))
            				{
								if(typeof($to, "STBaseContainer"))
									$aRv["container"]= &$to;
								else
									$aRv["address"]= $to;
								break;
            				}
            			}
						return $aRv;
      				}
				}
  			}else
				Tag::warning(!$bFound, "STObjectContainer::getAddressToNextContainer()", "no correct column to forwarding be set in container "
													.$this->getName()." with table ".$countTable->getName());
    	}

		return null;
	}
	function execute(&$externSideCreator, $onError)
	{
		Tag::paramCheck($externSideCreator, 1, "STSideCreator");

		$this->createMessages();
		$this->initContainer();
		$this->oExternSideCreator= &$externSideCreator;
		$params= new STQueryString();
		$get_vars= $params->getArrayVars();
		if(isset($get_vars["stget"]))
			$get_vars= $get_vars["stget"];
		else
			$get_vars= array();
		if(	!isset($get_vars["action"]) ||
			$get_vars["action"] == ""		)
		{
			$get_vars["action"]= $this->getAction();
		}
		if(	(	!isset($get_vars["table"]) ||
				$get_vars["table"] == "" 		) &&
			$get_vars["action"] != STCHOOSE			)
		{
			$get_vars["table"]= $this->getTableName();
		}
		
		if(	$this->bFirstContainer
			and
			$this->getAddressToNextContainer($params)	)
		{	// when this container is the first Container
			// and it is set the table by one entry to forward
			// by only one entry find in the function getAddressToNextContainer
			// forward the user to the calculated address
			
    		$address= $params->getStringVars();
    		if(Tag::isDebug())
    		{
			echo __file__.__line__."<br>";
			echo "define first container!<br>";
    			$h1= new H1Tag();
    				$h1->add("user reached only one entry in table");
    				$h1->add(br());
    			if($container)
    			{
    				$h1->add("so forward to next container");
    				$h1->add(br());
    			}else
    			{
    				$h1->add("so forward to address: ");
    				$h1->add(br());
    			}
    				$a= new ATag();
    					$a->href($address);
    					$a->add($address);
    				$h1->add($a);
    			$this->add($h1);
    			return "FORWARDTtoADDRESS";
    		}else
    		{
    			header("location: ".$address);
    			exit;
    		}
		}
		STBaseContainer::execute($externSideCreator, $onError);
		$action= $this->getAction();
		if($action == "")
		{
		    $table= $this->getTableName();
		    if(!isset($table) || trim($table) == "")
		        $table= $this->getFirstTableName();
		    $action= $this->getFirstAction();
		}
		if(	isset($get_vars["action"]) &&
			(	$get_vars["action"]==STUPDATE ||
				$get_vars["action"]==STINSERT	)	)
		{
			$result= $this->makeInsertUpdateTags($get_vars);
			
		}elseif(	isset($get_vars["action"]) &&
					$get_vars["action"]==STLIST		)
		{
			$result= $this->makeListTags($get_vars);
			
		}elseif(	isset($get_vars["action"]) &&
					$get_vars["action"]==STDELETE	)
		{
			$result= $this->deleteTableEntry($get_vars);
			
		}else
		{
			$this->makeChooseTags($get_vars);
			$result= "NOERROR";
		}
		return $result;
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

			$this->sHeadTitle= "Auswahlmenue";
    		$this->addAllInSide($chooseTable);
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
			$tableName= "";
			if(	isset($get_vars["table"]) &&
				is_string($get_vars["table"])	)
			{
				$tableName= $get_vars["table"];
			}
			$table= &$this->getTable($tableName);
			if(	$table->oSearchBox
				and
				!isset($this->asSearchBox[$table->oSearchBox->categoryName])
				and
				(	!$table->oSearchBox->bDisplayByButton
					or
					$get_vars["displaySearch"]=="true"			)									)
			{
				$table->oSearchBox->setSqlEffect(MYSQL_ASSOC);
				$table->oSearchBox->execute($table);
				$this->addObjBehindProjectIdentif($table->oSearchBox);
			}
				
			$list= &$this->getListTable($tableName);
			// add all params from Container for ListTable
//			if(is_array($this->getParmListLinks[$navi["class"]]))
//			{
				foreach($this->getParmListLinks as $action=>$do)
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
			if($this->bChooseInTable)
				$div->add($this->getChooseTableTag($get_vars));
			
			if($table->bShowName)
			{
				$tableIdentifier= $table->getDisplayName();
				if($tableIdentifier)
				{
					$h2= new H2Tag();
						$h2->align("center");
						$h2->add($tableIdentifier);
					$div->addObj($h2);
				}else
					$div->add(br());
			}
			$this->aBehindTableIdentif= array_merge(	$this->oExternSideCreator->aBehindTableIdentif,
														$this->aBehindTableIdentif);
			$count= count($this->aBehindTableIdentif);
			
			for($o= 0; $o<$count; $o++)
			{
				//echo "<br />OK<br />";
				//print_r($this->aBehindTableIdentif[$o]);
					$div->addObj($this->aBehindTableIdentif[$o]);
			}

			$PK= $table->getPkColumnName();
			if(!STCheck::warning(	typeof($table, "STDbTable") &&
									$PK === false, "makeListTags", "in table $tableName is no preimery key set"))
			{
				$updateAccess= $table->hasAccess(STUPDATE);
				$deleteAccess= $table->hasAccess(STDELETE);
				if(	(	$table->canUpdate()
						and
						$updateAccess		)
					or
					(	$table->canDelete()
						and
						$deleteAccess		)	)
				{
    				$get= new STQueryString();
					$get->noStgetNr("stget[action]");
					$get->noStgetNr("stget[".$tableName."][".$PK."]");
    				$script= new JavaScriptTag();
    					$function= new jsFunction("selftable_updateDelete", "action", "VALUE");
    						$get->update("stget[action]='+action+'");
    						$get->update("stget[".$tableName."][".$PK."]='+VALUE+'");
    						
							$function->add("bOk= true;");
							$function->add("if(action=='delete')");
							$function->add("    bOk= confirm('".$this->sDeleteQuestion."');");
							$function->add("if(bOk)");
							$location= "    location.href='".$get->getStringVars()."';";
    						$function->add($location);
    				$script->add($function);
					$div->addObj($script);
				}

				if(	$table->canUpdate()
					and
					$updateAccess		)
				{
					$list->select($PK, $this->sUpdateAction);
					$list->link($this->sUpdateAction, "javascript:selftable_updateDelete('update',%VALUE%);");
					//$list->setAsLinkParam($this->sUpdateAction, "id");
					//$list->setParamOnActivate(STUPDATE, "stget[action]=update", $this->sUpdateAction);
				}
				if(	$table->canDelete()
					and
					$deleteAccess		)
				{
					$list->select($PK, $this->sDeleteAction);
					$list->link($this->sDeleteAction, "javascript:selftable_updateDelete('delete',%VALUE%);");
					//$list->setAsLinkParam($this->sDeleteAction, "id");
					//$list->setParamOnActivate(UPDATE, "stget[action]=delete", $this->sDeleteAction);
				}
			}
			$this->setAllMessagesContent(STLIST, $list);

			if(typeof($table, "STDbTable"))
			{
			    $result= $list->execute();
			}else
				$result= "NOERROR";
				
			if(	$table->canInsert() &&
				$table->hasAccess(STINSERT)	&&
				isset($table->columns) &&
				count($table->columns) > 0		)
			{
				$get= new STQueryString();
				$get->update("stget[action]=".STINSERT);
				$params= $get->getStringVars();

				$center= new CenterTag();
					$button= new ButtonTag("newEntry");
						$button->add($this->sNewEntry);
						$button->onClick("javascript:location='".$this->oExternSideCreator->getStartPage().$params."'");
					$center->addObj($button);
					$center->add(br());
			}
			$this->setMainTable($list);
    		$div->addObj($center);
    		if(typeof($table, "STDbTable"))
				$div->addObj($list);
			$this->addAllInSide($div);
			return $result;
		}
	function &getHead($defaultTitle= "unknown")
	{
		Tag::paramCheck($defaultTitle, 1, "string");

		if(isset($this->headTag))
			return $this->headTag;
		$head= &STBaseContainer::getHead($defaultTitle);
		$action= $this->getAction();
		if(	$action!==STINSERT
			and
			$action!==STUPDATE	)
		{
			return $head;
		}
		$table= &$this->getTable();

		if($table->hasTinyMCE())
		{
			$mce= $table->getTinyMCE();
			$head->add("<!-- tinyMCE -->");
			$head->addObj($mce->getExternalScript());

			if($table->tinyMCECount()>1)
			{
				$columns= $table->tinyMCEColumns();
				for($n= 0; $n<count($columns); $n++)
				{
					$mce= $table->getTinyMCE($columns[$n]);
					$head->addObj($mce->getHeadScript("tinyMCE".$n));
				}

			}else
				$head->addObj($mce->getHeadScript());
			$head->add("<!-- /tinyMCE -->");
		}
		return $head;
	}

		function makeInsertUpdateTags($get_vars)
		{	
			$table= &$this->getTable($get_vars["table"]);
			$box= new STBox($this);
			$box->align("center");
			$get= new STQueryString();
			if($this->sFirstAction==$get_vars["action"])
			{// if first action is set to STUPDATE or STINSERT
			 // user can not choose any other actions in this table
			 // so go back to last container


				/*
				 * undocumented because do not know why display this toDo-message
				if(count($this->aContainer))
				{
					echo "file:".__file__." line:".__line__."<br />";
					echo "toDo: first action for this container is ".$get_vars["action"]."<br />";
					echo "      can also choose to other container<br />";
					echo "      nothing is what to do?";
					exit;
				}
				 */
			 	$this->deleteContainer($get);
			}else
			{
				$get->update("stget[action]=".STLIST);
				if($table->sDeleteLimitation=="true")
					$get->delete("stget[".$get_vars["table"]."][".$table->getPkColumnName()."]");
			}
			$getParameter= $get->getStringVars();
			$box->onOKGotoUrl($this->oExternSideCreator->getStartPage().$getParameter);
			if($get_vars["action"]==STINSERT)
			{
				$box->table($table);
				$this->setAllMessagesContent(STINSERT, $box);
				$head= &$this->getHead($this->sNewEntry." in ".$table->getIdentifier());
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
			//$this->addObj($head);
			//$body= new BodyTag();
				$headline= &$this->getHeadline($get_vars);
				$this->addObj($headline);
				$center= new CenterTag();
					$h2= new H2Tag();
						$h2->add($table->getIdentifier());
					$center->addObj($h2);
					$center->add(br());

			if($table->sFirstAction!==STLIST)
			{
				$chooseTable= $this->getChooseTableTag($get_vars);
				$this->addObj($chooseTable);
			}
    		$this->addObj($center);
				$this->addObj($box);
			//$this->addObj($body);
			return $result;
		}
		function getResult()
		{
			return $this->oMainTable->getResult();
		}
		function getInsertID()
		{
			$action= $this->getAction();
			if(	$action!==STINSERT
				or
				$action !==STUPDATE	)
			{
				STCheck::warning(1, "STSideCreator::getResult()",
									"this function is only for action STINSERT and STUPDATE");
				return null;
			}
			return $this->oMainTable->getLastInsertID();
		}
		function deleteTableEntry($get_vars)
		{
			$table= &$this->getTable($get_vars["table"]);
			//$PK= $table->getPkColumnName();
			$box= new STBox($this);
			$box->table($table);
			//$box->where($PK."=".$get_vars["link"]["VALUE"]);
			//$box->onOkGotoUrl($get->getParamString(STDELETE, "stget[link][".$this->sDeleteAction."]"));
			$tableName= $table->getName();
			$get= new STQueryString();
			$stget= $get->getArrayVars();
			$stget= null;
			if(isset($stget["stget"]))
				$stget= $stget["stget"];
			else
				$stget= null;
			if(isset($stget[$tableName]))
			{// is the own limitation from an other container
			 // do not delete this limitation
				$bFromContainer= false;
				$column= key($stget[$tableName]);
				$from= null;
				if(isset($stget["link"]["from"]))
					$from= $stget["link"]["from"]; 
				while($from)
				{
					foreach($from as $content)
					{
						if($content[$tableName]==$column)
						{
							$bFromContainer= true;
							break;
						}
					}
					if($bFromContainer)
						break;
					if(isset($stget["older"]["stget"]))
						$stget= $stget["older"]["stget"];
					else
						$stget= null;
					if(isset($stget["link"]["from"]))
						$from= $stget["link"]["from"];
					else
						$from= null;
				}
				if(!$bFromContainer)
					$get->delete("stget[".$tableName."]");
			}
			$get->update("stget[action]=".STLIST);
			$get->delete("stget[$tableName]");
			if($table->listArrangement == STVERTICAL)
			{// alex02/05/2019:	when container listed only one column vertical,
			 // 				this one do not exist after deletion
			 // 				so jump out from container
				$get->delete("stget[container]");
			}
			$box->msg->onEndGotoUrl($get->getStringVars());
			$this->setAllMessagesContent(STDELETE, $box);
			$result= $box->delete();
			$this->addObj($box);
			return $result;
		}
		function getChooseTableTag($get_vars)
		{
			$action= $this->getAction();
			$aTables= &$this->getTables();
			$aktTableName= $this->getTableName();
			if(isset($this->oExternSideCreator))
			{
    			foreach($aTables as $name=>$table)
    			{
    				// do not ask with $name
    				// because $name only in lower case
    				$tableName= $table->getName();
    				if(	!$table->hasAccess($action, false)
    					or
    					(	$name==$aktTableName
    						and
    						!$this->bDisblayedTable	)
    					or
    					isset($this->oExternSideCreator->aNoChoice[$tableName])		)
    				{
    					$this->aNoChoice[$tableName]= $tableName;
    				}
    			}
			}
			
			$chooseTable= new STChooseTable($this);
			$chooseTable->align("center");
			$chooseTable->setStartPage($this->oExternSideCreator->getStartPage());
			$chooseTable->noChoise($this->aNoChoice);
			$chooseTable->forwardByOne();
    		$nExistTables= $chooseTable->execute();
			if(!$nExistTables)
			{// wenn auf garkeine Tabelle zugegriffen werden kann
			 // erzeuge einen Fehler
				$oTable= &$this->getTable();
				if(	STCheck::isDebug()
					&&
					!$oTable	)
				{
					STCheck::echoDebug(true, "no tables exist, install anyone before, or use STDbSideCreator->install()");
					exit;
				}else
					$oTable->hasAccess($get_vars["action"], true);
			}


			return $chooseTable;
		}
		/*protected*/function &getListTable($tableName)
		{
			if(isset($this->oCurrentListTable))
			{
				$table= &$this->oCurrentListTable->getTable();
				if($table->getName()==$tableName)
					return $this->oCurrentListTable;
			}

			$this->oCurrentListTable= new STTable($this);
			$table= &$this->getTable($tableName);
			$table->getSelectedColumns();	//fals noch keine Spalte gesetzt ist
										//vor dem setzen von "aktualisieren" und "l�schen"
										//alle Spalten aus der Datenbank holen
			
			// create rows for dynamic-clustering
			$checked= $table->createDynamicAccess();
			if(count($checked))
			{
  				if(isset($checked[STADMIN]))
  				{
  				    if($checked[STADMIN])
  					{
  					    $this->oCurrentListTable->callback($this->sUpdateAction, "st_list_table_changing_access", STLIST);
  					    $this->oCurrentListTable->callback($this->sDeleteAction, "st_list_table_changing_access", STLIST);
  					}else
  					{
  					    $table->doUpdate(false);
  						$table->doDelete(false);
  					}
  				}elseif(isset($table->sAcessClusterColumn[STUPDATE]))
  				{
  				    if($checked[STUPDATE])
  					    $this->oCurrentListTable->callback($this->sUpdateAction, "st_list_table_changing_access", STLIST);
  					else
  					    $table->doUpdate(false);

  				}elseif(isset($table->sAcessClusterColumn[STDELETE]))
  				{
  				    if($checked[STDELETE])
  					    $this->oCurrentListTable->callback($this->sDeleteAction, "st_list_table_changing_access", STLIST);
  					else
  					    $table->doDelete(false);
  				}
			}

			// check Access
			$this->setAccessForColumnsInTable($table, $this->oCurrentListTable);
			$this->oCurrentListTable->table($table);
			$this->oCurrentListTable->doContainerManagement($this->oExternSideCreator->bContainerManagement);
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
					{//control wether the link to the container can showen
					    // alex: 26/01/2022
					    // should be defined
					    //$container= $this->getContainer($container->getName());
					    $access= $container->hasContainerAccess();
						$oList->hasAccess($columnName, $access);
					}
				}
			}
		}
		// setzt alle gewuenschten Fehler-Meldungen
		// und Callbacks
		// in den diversen Objekten, welche dann mit
		// execute ausgefuehrt werden.
		//
		// param $table muss ein objekt vom typ STBaseTableBox sein
		/*private*/ function setAllMessagesContent($action, &$oBox)
		{
			$oBox->setLanguage($this->language);
			$table= &$oBox->getTable();
			$tableName= $table->getName();
			if(isset($this->asError[$action][$tableName]))
				$aError= $this->asError[$action][$tableName];
			if(	!isset($aError) &&
				isset($this->asError[$tableName])	)
			{
				$aError= $this->asError[$tableName];
			}
			if(	!isset($aError) &&
				isset($this->asError["all"])	)
			{
				$aError= $this->asError["all"];
			}
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
	function &needContainer($container, $byAction= STLIST, $byTable= STALLDEF)
	{
		Tag::paramCheck($container, 1, "STObjectContainer", "STFrameContainer", "string");
		Tag::paramCheck($byTable, 2, "string");
		Tag::paramCheck($byAction, 3, "string");

		$container= &STBaseContainer::needContainer($container, $byTable, $byAction);
		$containerName= $container->getName();
		if(!is_array($this->aContainer[$containerName]))
			$this->aContainer[$containerName]= array();
		$this->aContainer[$containerName][$byTable][$byAction]= true;
		return $container;
	}
	function &getOlderLinkedTable()
	{
		if($this->bFirstContainer)
		{
			// container be the first,
			// no older linkedTable can be set
			return null;
		}

		//search older container in get-params
		//to set the last table from this container
		$olderContainer= $this->getOlderContainer();
		$vars= $this->getContainerGetParams();
		$tableName= $vars["older"]["stget"]["table"];
		if(!$tableName)
		{// table must be from the older container
		 // the first one
			$tableName= $olderContainer->getTableName();
		}
		$table= &$olderContainer->getTable($tableName);
		return $table;
	}
	// gibt nur einen Cluster zur�ck
	// wenn der Container der Aktuelle ist
	// da diese Methode ein ->getTable aufruft
	// muss, wenn diese Tabelle nicht angezeigt werden soll,
	// ein ->doChoice(<Tabelle>, false) aufgerufen werden.
	function getLinkedCluster($action, $table= null, $pkValue= null)
	{
		Tag::paramCheck($action, 1, "string");
		Tag::paramCheck($table, 2, "STDbTable", "string", "null");
		STCheck::paramCheck($pkValue, 3, "string", "int", "float", "null");

	    if(!STUserSession::sessionGenerated())
			return "";
    	if(!$this->isAktContainer())
    	    return "";

		if($table===null)
		{
			$table= &$this->getOlderLinkedTable();
			if(!$table)
				return "";
			$tableName= $table->getName();
			//echo "container:".$this->getName()."<br />";
			//echo "older table:".$tableName."<br />";
		}elseif(!typeof($table, "STDbTable"))
    	{
			$desc= STDbTableDescriptions::instance();
    	    $tableName= $desc->getTableName($table);
    		$table= $this->getTable($tableName);
    	}else
    	    $tableName= $table->getName();

    	$session= &STUserSession::instance();
    	$aAccess= &$session->getDynamicClusters($table);
		if($pkValue===null)
		{
        	$html= new STQueryString();
        	$vars= $html->getArrayVars();
        	$vars= $vars["stget"][$tableName];
        	$pk= $table->getPkColumnName();
    		if($vars)
    		{
        		$linkedKey= key($vars);
        		$linkedValue= $vars[$linkedKey];
    			Tag::error(!$linkedValue, "STObjectContainer::getLinkedCluster()", "reference '".$linkedKey."' to table ".
    							"'".$tableName."' from older Container, not be set in Url");
    		}else
    			Tag::error(!$linkedValue, "STObjectContainer::getLinkedCluster()", "table "."'".$tableName.
    															"' from older Container, not be set in Url");
		}else
		{
			$linkedKey= $pk;
			$linkedValue= $pkValue;
		}

    	if($linkedKey!=$pk)
    	{
    	    $table->clearSelects();
    		$table->select($pk);
    		$table->where($linkedKey."=".$linkedValue);
    		$statement= $this->db->getStatement($table);
    		$linkedValue= $db->fetch_single($statement);
    	}
		$cluster= $aAccess[$action][$linkedValue];

    	return $cluster;
	}
	static public function install($containerName, $className, $fromContainer= null, $sourceFile= null)
	{
		global	$global_array_exist_stobjectcontainer_with_classname,
				$global_boolean_install_objectContainer;

		STCheck::paramCheck($containerName, 1, "string");
		STCheck::paramCheck($className, 2, "string");
		STCheck::paramCheck($fromContainer, 3, "string", "null");
		STCheck::paramCheck($sourceFile, 3, "string", "null");

		if(!STCheck::isDebug("install"))
			STCheck::echoDebug("container", "container <b>".$containerName."</b> from class <b>".$className."</b> is provided");
		$global_array_exist_stobjectcontainer_with_classname[$containerName]= array(	"class"=>$className,
																						"from"=>$fromContainer,
																						"source"=>$sourceFile	);
		if(!$global_boolean_install_objectContainer)
			return;
		$bfrom= true;
		if($fromContainer==null)
		{
			$bfrom= false;
			$fromContainer= $containerName;
		}
		$container= &STBaseContainer::getContainer($fromContainer);
		if(!$bfrom)
			$newContainer= &$container->getContainer($containerName);
		else
			$newContainer= &$container;
		$newContainer->installContainer();
		return;
	}
	function installContainer()
	{
		$desc= &STDbTableDescriptions::instance($this->db->getDatabaseName());
		$desc->installTables($this->db);
	}
	function getColumnFromTable($tableName, $column)
	{
		//echo "STObjectContainer::getColumnFromTable($tableName, $column)<br />";
		//st_print_r($this->asExistTableNames);
		//st_print_r($this->asTableColumns);
		$newColumn= "";
	    $tableName= $this->getTableName($tableName);
	    if(	isset($this->asTableColumns) &&
	    	isset($this->asTableColumns[$tableName]) &&
	    	isset($this->asTableColumns[$tableName][$column])	)
	    {
	    	//echo "exist columns in table $tableName:";
	    	$newColumn= $this->asTableColumns[$tableName][$column]["column"];
			if(!$newColumn)
			    $newColumn= $column;
	    }
		return $newColumn;
	}
	function addObjBehindTableIdentif(&$tag)
	{
		Tag::paramCheck($tag, 1, "Tag", "string");

		if(typeof($tag, "STSearchBox"))
			$this->asSearchBox[$tag->categoryName]= "exist";
		$this->aBehindTableIdentif[]= &$tag;
	}
	function addBehindTableIdentif($tag)
	{
		Tag::paramCheck($tag, 1, "Tag", "string");

		if(typeof($tag, "STSearchBox"))
			$this->asSearchBox[$tag->categoryName]= "exist";
		$this->aBehindTableIdentif[]= &$tag;
	}
		function hasContainerAccess($makeError= false, $forAllTables= false)
		{
			$access= STBaseContainer::hasContainerAccess($makeError);
			if(!$access)
				return false;

			$this->initContainer();
			if(	is_array($this->tables)
				and
				count($this->tables))
			{
				$action= $this->getAction();
				$bChoose= false;
				$sFirstTableName= null;
				foreach($this->tables as $tableName=>$table)
				{
					if(	$this->tableChoice($table) )
					{
						$bChoose= true;
						if(!$sFirstTableName)
							$sFirstTableName= $tableName;
						STCheck::echoDebug("access", "ask for access in table <b>".$tableName."</b>");
						if(	$this->tables[$tableName]->hasAccess($action)	)
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
					if($makeError)
					{
						Tag::echoDebug("access", "makeError be set, so goto login-mask by table ".$tableName);
						$this->tables[$tableName]->hasAccess($action, true);
					}
					return false;
				}//else
				 //	Tag::echoDebug("access", "no cluster in any table set, so has access <b>end of search</b> with returning true");
			}
			STCheck::echoDebug("access", "no table set in container, so has access <b>end of search</b> with returning true");
			return true;
		}
}

    function st_list_table_changing_access(&$oCallback, $columnName, $row)
		{
		    //$oCallback->echoResult();
				if(!STUserSession::sessionGenerated())
				    return;

				if($columnName=="aktualiseren")
				    $action= STUPDATE;
				else
				    $action= STDELETE;
				$session= &STUserSession::instance();
				//echo "list_table_access:";st_print_r($oCallback->aAcessClusterColumns);
				foreach($oCallback->aAcessClusterColumns as $info)
				{
				    if( $info["action"]==$action
								or
								$info["action"]==STADMIN )
						{
						    $cluster= $oCallback->getValue($info["column"]);
								break;
						}
				}
				//echo $cluster."<br />";
				if(!$cluster)
				    return;
				if(!$session->hasAccess($cluster, null, null, false, $action))
				    $oCallback->setValue("");
		}
		
?>