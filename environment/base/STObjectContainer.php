<?php

require_once($php_html_description);
require_once($_stbasecontainer);
require_once($_stdbtablecreator);
require_once($_stdbtabledescriptions);
require_once($_stchoosebox);
require_once($_stlistbox);
require_once($_stitembox);


abstract class STObjectContainer extends STBaseContainer
{
	var $db; // Datenbank-Objekt
	var $headTag;
	var $chooseTitle;
	var $sDefaultCssLink;
	/**
	 * all tables which are geted to modify
	 * but not needed to display
	 * @var array
	 */
	protected $oGetTables= array();
	/**
	 * all tables which are needed
	 * to display or modify
	 * @var array
	 */
	protected $tables= array();
	var	$sFirstTableName; //erste Tabelle
	var $sFirstAction= null; // erste Aktion nicht gesetzt hole von Tabelle
	var $actAction= ""; // current action from this container
	var $actTableName= ""; // current main table in this container
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

	/**
	 * array with STSearchBox objects
	 * to search inside database
	 * @var array
	 */
	private $asSearchBox= array();
	/**
	 * displayed box
	 * to get result
	 * @var STBaseBox
	 */
	protected $oMainTable;

	function __construct($name, &$container, $bodyClass= "body_content")
	{
	    if(STCheck::isDebug())
	    {
    		STCheck::param($name, 0, "string");
    		STCheck::param($container, 1, "STObjectContainer");
    		STCheck::param($bodyClass, 2, "string");
	    }

		$this->db= &$container->getDatabase();
		STBaseContainer::__construct($name, $container, $bodyClass);
	}
	/**
	 * method to create messages for different languages.<br />
	 * inside class methods (create(), init(), ...) you get messages from <code>$this->getMessageContent(<message id>, <content>, ...)</code><br />
	 * inside this method depending the <code>$language</code> define messages with <code>$this->setMessageContent(<message id>, <message>)</code><br />
	 * see STMessageHandling
	 *
	 * @param string $language current language like 'en', 'de', ...
	 */
	protected function createMessages(string $language)
	{
		if($language == "de")
		{
		    $this->setMessageContent("INSERT", "einfügen");
		    $this->setMessageContent("UPDATE", "aktualisieren");
		    $this->setMessageContent("DELETE", "löschen");
		    $this->setMessageContent("newENTRY", " neuer Eintrag");
		    $this->setMessageContent("DELETE_QUESTION", "wollen Sie diesen Eintrag wirklich löschen?");		    
		    $this->setMessageContent("NOPERMISSION", "Sie haben keine Berechtigung um die Aktion \''+action+'\' durchzuführen!");
			
		}else // otherwise language have to be english "en"
		{
		    $this->setMessageContent("INSERT", "insert");
		    $this->setMessageContent("UPDATE", "update");
		    $this->setMessageContent("DELETE", "delete");
		    $this->setMessageContent("newENTRY", " new Entry");
		    $this->setMessageContent("DELETE_QUESTION", "do you want to delete this entry?");
		    $this->setMessageContent("NOPERMISSION", "you have no permission to '+action+' this entry!");
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
	function &needEmptyTable(string $sTableName)
	{	
		return $this->needTable($sTableName, /*empty*/true);
	}
	/**
	 * create N to N table with checkboxes which connect between fixTable and joinTable
	 * 
	 * @param string $joinTable table which can connect by clicking the checkbox
	 * @param string $nnTable connection table between joinTable and fixTable
	 * @param string $fixTable fix table which should be manifested with where statement 
	 * @return STDbSelector return STDbSelector object with table name from joinTable
	 */
	public function &needNnTable(string $joinTable, string $nnTable, string $fixTable)
	{
	    // not all databases save the tables case sensetive
	    $sTableName= strtolower($this->getTableName($joinTable));
	    if(!isset($this->tables[$sTableName]))
	    {
    	    $table= $this->getTable($joinTable);
    	    $selector= new STDbSelector($table);
    	    $selector->setNnTable($nnTable, $fixTable);
    	    //$selector->joinOver($fixTable);
    	    //$selector->joinOver($nnTable);
    	    $this->needTableObject($selector);
    	    
	    }else
	    {
	        $selector= $this->tables[$sTableName];
	        STCheck::alert(!typeof($selector, "STDbSelector") || !$selector->bIsNnTable, "STObjectContainer::needNnTable()",
	            "the joinTable '$joinTable' (third parameter) was selected before, but not as N to N table");
	    }
	    return $selector;
	}
	public function need(&$object)
	{
		Tag::paramCheck($object, 1, "STBaseTable", "STBaseContainer");

		if(typeof($object, "STBaseContainer"))
			$this->needContainer($object);
		else
			$this->needTableObject($object);
	}
	/**
	 * an extern created table or container which should
	 * be used inside choosebox
	 * 
	 * @param STDbSelector|STBaseContainer $object new table or container which should displayed
	 */
	public function needTableObject(&$object)
	{
		if(typeof($object, "STBaseContainer"))
			$object->oUsedContainerLayer= $this;
	    $orgTableName= $object->getName();
	    // not all databases save the tables case sensetive
	    $sTableName= strtolower($orgTableName);
	    $this->tables[$sTableName]= &$object;
	}
	public function &needTable(string $sTableName, bool $bEmpty= false) : object
	{   
	    $this->initContainer();
	    
	    $orgTableName= $this->getTableName($sTableName);
	    if(isset($orgTableName))
	        $sTableName= $orgTableName;
        else
            $orgTableName= $sTableName;
        STCheck::echoDebug("table", "need table <b>$sTableName</b> in container <b>".$this->getName()."</b>");
        // not all databases save the tables case sensetive
        $sTableName= strtolower($sTableName);
        $table= null;
        if(isset($this->tables[$sTableName]))
            $table= &$this->tables[$sTableName];
        else
        {
            $gettable= $this->getTable($orgTableName, /*container*/null, $bEmpty);
            if(isset($gettable))
            {
                $this->tables[$sTableName]= $gettable;
                $table= &$this->tables[$sTableName];
                $table->abOrigChoice= array();
                //$this->table[$sTableName]= &$table;
            }
        }
        return $table;
	}
	public function needLink(string $name, string $address= null)
	{
		$object= new STBaseTable($name);
		if(isset($address))
			$object->linkTo($address);
		$this->needTableObject($object);
		return $object;
	}
	public function &getTable(string $tableName= null, string $sContainer= null, bool $bEmpty= false)
	{
		$nParams= func_num_args();
		STCheck::lastParam(3, $nParams);
		
		if( $sContainer != null &&
		    $sContainer != $this->name )
		{
		    $container= &STBaseContainer::getContainer($sContainer);
		    return $container->getTable($tableName, $sContainer, $bEmpty);
		}else
		    $container= &$this;
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
		
		// check first right name of table
		if($tableName==null)
		{
		    $orgTableName= $tableName= $container->getTableName();
		}
		if(!$tableName)
		{
			Tag::echoDebug("table", "no table to show difined for this container ".get_class($this)."(".$this->getName().")");
			Tag::echoDebug("table", "or it not be showen on the first status");
			$Rv= null;
			// cannot return null
			// return need an variable
			return $Rv;
		}

		if(!isset($orgTableName))
		  $orgTableName= $container->getTableName($tableName);
    	if($orgTableName)
    	    $tableName= $orgTableName;
		else
		// not all databases save the tables case sensetive
			$orgTableName= $tableName;
		$tableName= strtolower($tableName);
		// ----------------------------------------------------------------------------------------------------
		
		
		// alex 08/07/2005: die Tabelle wird nun auch ohne Referenz geholt
		//					�nderungen jetzt ausserhalb m�glich
		//					und die Tabelle ist dann nicht automatisch in $this->tables eingetragen
		//					wenn hier eine Referenz geholt w�rde w�re sie automatisch
		//					bei $this->db->getTable in $this->tables eingetragen
		if(isset($this->tables[$tableName]))
		{
		    if(STCheck::isDebug("table"))
		    {
    		    $space= STCheck::echoDebug("table", "get <b>used</b> table <b>$tableName</b> from container <b>".$this->getName()."</b>");
    		    st_print_r($this->tables, 1, $space);
		    }
		    $table= &$this->tables[$tableName];
		    
		}else if(isset($this->oGetTables[$tableName]))
		{
		    if(STCheck::isDebug("table"))
		    {
    		    $space= STCheck::echoDebug("table", "get in <b>evidence</b> holded table <b>$tableName</b> from container <b>".$this->getName()."</b>");
    		    //st_print_r($this->oGetTables, 1, $space);
    		    //showBackTrace();
		    }
		    $table= &$this->oGetTables[$tableName];
		    
		}else if( !$bEmpty &&
		          $this->parentContainer != null &&
		          $this->name !== $this->parentContainer->getName()   )
		{	// alex 24/05/2005:	// alex 17/05/2005:	die Tabelle wird von der �berschriebenen Funktion
			//					getTable() aus dem Datenbank-Objekt geholt.
		    //					nat�rlich wird die Tabelle kopiert, da sie wom�glich noch ge�ndert wird
		    $oldTable= $this->parentContainer->getTable($orgTableName);
		    if(STCheck::isDebug("table"))
		    {
		        $msg= "get table <b>$tableName</b> ";
		        $msg.= "from parent container <b>".$this->parentContainer->getName()."</b> ";
		        $msg.= "for container <b>".$this->name."</b> ";
		        $msg.= "and clone to new one";
		        STCheck::echoDebug("table", $msg);
		    }
			$table= clone $oldTable;
			STCheck::alert(!isset($table), "STObjectContainer::getTable()", "cannot clone $oldTable");
			
			$table->abOrigChoice= array();
			unset($table->container);
			$table->container= $this;
			$this->oGetTables[$tableName]= &$table;
			if(STCheck::isDebug())
			{
			    $msg= array();
			    $msg[]= "new not exist table $table filled with own container";
			    $msg[]= "    was cloned from $oldTable";
			    STCheck::echoDebug("table", $msg);
			    STCheck::warning(!isset($table), "STDbSelector::getTable()",
			        "table ".$table->getName()." do not exist inside container ".$container->getName());
			}
		}else
		{
		    if($bEmpty)
		    {
		        $table= new STBaseTable($tableName);
		        Tag::echoDebug("table", "created dummy table ".$table->toString()." inside database container <b>".$this->getName()."</b>");
		    }else
		        $table= &$this->createTable($orgTableName);
		    $this->oGetTables[$tableName]= &$table;
		}
		//showBackTrace();
		return $table;
	}
	/**
	 * create unknown table if container not from STDatabase
	 * 
	 * @param string $tableName name of table which is not used
	 * @return STBaseTable table object
	 */
	function &createTable($tableName)
	{
	    $table= new STBaseTable($tableName);	    
	    Tag::echoDebug("table", "created dummy table ".$table->toString()." inside database container <b>".$this->getName()."</b>");
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
  		    if(!count($this->oGetTables))
  		    {
    			$tableNames= $this->db->list_tables($onError);
    			// alex 17/05/2005:	die Tabellen werden nun von der �berschriebenen Funktion
    			//					getTable() aus dem Datenbank-Objekt erzeugt.
    			foreach($tableNames as $name)
    			{
    			    $keyTableName= strtolower($name); 
    			    $this->oGetTables[$keyTableName]= clone $this->db->getTable($name);//, $bAllByNone);
    			}
  		    }else
  		        return $this->oGetTables; 
		}
		//st_print_r($this->tables, 1);
		return $this->tables;
	}
	function setInTableNewColumn($tableName, $columnName, $type)
	{
		$tableName= strtolower($this->getTableName($tableName));
		if(isset($this->tables[$tableName]))
		{
			$this->tables[$tableName]->dbColumn($columnName, $type, $len);
		}
	}
	function setInTableColumnNewFlags($tableName, $columnName, $flags)
	{
	    $tableName= strtolower($this->getTableName($tableName));
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
	/**
	 * whether table object exist inside container
	 *
	 * @param string $tableName name of table
	 * @return bool whether exist
	 */
	public function hasTable(string $tableName) : bool
	{
	    // method needs initialization properties
	    // to know which tables are defined
	    $this->createContainer();
	    
	    $tableName= strtolower($this->getTableName($tableName));
	    if(isset($this->oGetTables[$tableName]))
	        return true;
	    return false;
	}
	/**
	 * whether table exist inside database
	 *
	 * @param string $tableName name of table
	 * @return bool whether exist
	 */
	public function isDbTable(string $tableName) : bool
	{
		return $this->db->isDbTable($tableName);
	}
	function getTableName(string $tableName= null)
	{
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
			$description= &STDbTableDescriptions::instance($this->db->getDatabaseName());
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
		    isset($this->parentContainerName) &&
		    trim($this->parentContainerName) != "" &&
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
		        if(!isset($table))// all trys to find table be ineffective
		            $action= STLIST;// so return standard action
		        else
		            $action= $table->getFirstAction();
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

		if(typeof($table, "STBaseTable"))
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
		STCheck::param($table, 0, "string", "STBaseTable");

		if(typeof($table, "STBaseTable"))
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
	function deleteQueryContainer(&$oGetParam)
	{
		STCheck::param($oGetParam, 0, "STQueryString");
		STCheck::echoDebug("containerChoice", "delete container ".$this->getName()." in object STObjectContainer");
		$params= $oGetParam->getArrayVars();
		
		if(!isset($params["stget"]["container"]))
		{// container is the first,
		 // cannot delete any more
		 	return false;

		}else
			$container= $params["stget"]["container"];

		STBaseContainer::deleteQueryContainer($oGetParam);
		$params= $oGetParam->getArrayVars();
		if(isset($params["stget"]["container"]))
			$container= $params["stget"]["container"];
		if( !isset($container) ||
		    trim($container) == ""    )
		{// older container must be the first
			global	$global_first_objectContainerName;

			$container= $global_first_objectContainerName;
		}
		$container= &$this->getContainer($container, false);
		if(!$container)
		{
			// cannot find defined container
			// so do not search for dynamic access			
			return true;
		}
		$result= $container->hasDynamicAccessToOneEntry();
		if($result)
		{
			return $container->deleteQueryContainer($oGetParam);
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
			    $oGetParam->setLimitagion("container", $table->getName(), $result['column'], $result["columnEntry"]);
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
				STCheck::is_warning(!$bFound, "STObjectContainer::getAddressToNextContainer()", "no correct column to forwarding be set in container "
													.$this->getName()." with table ".$countTable->getName());
    	}

		return null;
	}
	function execute(&$externSideCreator, $onError)
	{
		Tag::paramCheck($externSideCreator, 1, "STSiteCreator");
		
		$this->createMessages($this->locale['language'], $this->locale['nation']);
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
			showLine();
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
		if(STCheck::isDebug())
		{
    		$displayTable= $this->getTable();
    		if( typeof($displayTable, "STDbSelector") &&
    		    $displayTable->bIsNnTable &&
    		    !$displayTable->bNnTableColumnSelected     )
    		{
    		    $msg= "table '".$displayTable->getName()."' is declared";
    		    $msg.= " as N to N table, but there is no STDbSelector::nnTableCheckboxColumn() defined";
    		    STCheck::alert(true, "STObjectContainer::execute()", $msg);
    		}
		}
		$result= STBaseContainer::execute($externSideCreator, $onError);
		if($result != "NOERROR")
		    return $result;
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
		    $this->makeChooseTags($get_vars);
		return $result;
	}
	function makeChooseTags($get_vars)
	{
		$chooseTable= $this->getChooseTableTag($get_vars);
		// alex 18/05/2005:	die Weiterleitung ist jetzt in den STChooseBox verschoben
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
		if(	isset($table->oSearchBox) &&
			!isset($this->asSearchBox[$table->oSearchBox->categoryName]) &&
			(	!$table->oSearchBox->bDisplayByButton
				or
				$get_vars["displaySearch"]=="true"			)									)
		{
			$table->oSearchBox->setSqlEffect(MYSQL_ASSOC);
			$table->oSearchBox->execute($table);
			$this->addObjBehindProjectIdentif($table->oSearchBox);
		}
		
		$div= new DivTag();
		$headline= &$this->getHeadline($get_vars);
		$div->addObj($headline);
		if($this->bChooseInTable)
		    $div->add($this->getChooseTableTag());
		 
		$result= "NOERROR";
		if($tableName)
		{
			$list= &$this->getListTable($tableName);
			// add all params from Container for ListTable
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
			if( typeof($table, "STDbTable") &&
			    !STCheck::is_warning($PK === false, "makeListTags", "in table $tableName is no preimery key defined"))
			{
				$updateAccess= $table->hasAccess(STUPDATE);
				$deleteAccess= $table->hasAccess(STDELETE);
				$bUpdate= false;
				$bDelete= false;
				if(	$table->canUpdate() &&
					$updateAccess		    )
				{
				    $bUpdate= true;
				}
				if(	$table->canDelete() &&
					$deleteAccess		    )
				{
				    $bDelete= true;
				}
				if( $bUpdate ||
				    $bDelete    )
				{
    				$get= new STQueryString();
					$get->noStgetNr("stget[action]");
					$get->noStgetNr("stget[".$tableName."][".$PK."]");
    				$script= new JavaScriptTag();
    					$function= new jsFunction("selftable_updateDelete", "action", "VALUE");
        					$get->setLimitation("'+action+'", $this->getContainerName(), $tableName, $PK, "'+VALUE+'");
    						
    						$function->add("bOk= true;");
    						$function->add("if(action=='delete')");
						if($bDelete)
						{
							$function->add("    bOk= confirm('".$this->msgBox->getMessageContent("DELETE_QUESTION")."');");							
						}
						if(!$bUpdate)
						    $function->add("else if(action=='update')");
						if( !$bDelete ||
						    !$bUpdate     )
						{
						    $function->add("{");
						    $function->add("bOk= false;");
						    $function->add("alert('".$this->msgBox->getMessageContent("NOPERMISSION")."');");
						    $function->add("}");
						}
							$function->add("if(bOk)");
							$location= "    location.href='".$get->getStringVars()."';";
    						$function->add($location);
    				$script->add($function);
					$div->addObj($script);
				}

				$pkField= $table->getColumnField($PK);
				$value= "%VALUE%";
				if($pkField['type'] == "string")
				    $value= "'$value'";
				if(	$table->canUpdate()
					and
					$updateAccess		)
				{
				    $sUpdateLink= $this->msgBox->getMessageContent("UPDATE");
					$list->updateLine($PK, $sUpdateLink);
					$list->link($sUpdateLink, "javascript:selftable_updateDelete('update',$value);");
				}
				if(	$table->canDelete()
					and
					$deleteAccess		)
				{
				    $sDeleteLink= $this->msgBox->getMessageContent("DELETE");
					$list->deleteLine($PK, $sDeleteLink);
					$list->link($sDeleteLink, "javascript:selftable_updateDelete('delete',$value);");
				}
			}
			$this->setAllMessagesContent(STLIST, $list);
			
			if(typeof($table, "STDbTable"))
			    $result= $list->execute();
			    

			if(	$table->canInsert() &&
				$table->hasAccess(STINSERT)	&&
				isset($table->columns) &&
				count($table->columns) > 0		)
			{
				$get= new STQueryString();
				$get->setLimitation(STINSERT, $this->getContainerName(), $table->getName());
				$params= $get->getStringVars();

				$center= new CenterTag();
					$button= new ButtonTag("newEntry");
						$button->add($this->msgBox->getMessageContent("newENTRY"));
						$button->onClick("javascript:location='".$this->oExternSideCreator->getStartPage().$params."'");
					$center->addObj($button);
					$center->add(br());
			}
			$this->setMainTable($list);
    		$div->addObj($center);
    		if(typeof($table, "STDbTable"))
				$div->addObj($list);
	    }
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
			$box= new STItemBox($this);
			$box->align("center");
			$query= new STQueryString();
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
			 	$this->deleteQueryContainer($query);
			}else
			{
				$query->update("stget[action]=".STLIST);
				if($table->getDeleteLimitationOrder()=="true")
					$query->removeLimitation($get_vars["table"], $table->getPkColumnName());
			}
			$getParameter= $query->getStringVars();
			$box->onOKGotoUrl($this->oExternSideCreator->getStartPage().$getParameter);
			if($get_vars["action"]==STINSERT)
			{
				$box->table($table);
				$this->setAllMessagesContent(STINSERT, $box);
				$head= &$this->getHead($this->msgBox->getMessageContent("newENTRY")." in ".$table->getDisplayName());
				$result= $box->insert();
			}else
			{
				$box->table($table);
				$this->setAllMessagesContent(STUPDATE, $box);
				//st_print_r($table->oWhere);
				//$whereStatement= $this->db->getWhereStatement($table, "t1");
				//echo "statement ".$whereStatement."<br />";
				//$whereStatement= $table->getPkColumnName();
				//$whereStatement.= "=".$get_vars["link"]["VALUE"];
				//$where= new STDbWhere($whereStatement);
				//$box->where($where);
				$head= &$this->getHead("Eintrag aktualisieren in ".$table->getDisplayName());
				$result= $box->update();
			}
			$this->oMainTable= &$box;
			
			$headline= &$this->getHeadline($get_vars);
			$this->addObj($headline);
			$center= new CenterTag();
				$h2= new H2Tag();
					$h2->add($table->getDisplayName());
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
				STCheck::is_warning(1, "STSiteCreator::getResult()",
									"this function is only for action STINSERT and STUPDATE");
				return null;
			}
			return $this->oMainTable->getLastInsertID();
		}
		function deleteTableEntry($get_vars)
		{
			$table= &$this->getTable($get_vars["table"]);
			$table->removeDynamicClusters();
			
			$box= new STItemBox($this);
			$box->table($table);
			
			$query= new STQueryString();
			$query->removeLimitation();
			$query->update("stget[action]=".STLIST);
			if($table->listArrangement == STVERTICAL)
			{// alex02/05/2019:	when container listed only one column vertical,
			 // 				this one do not exist after deletion
			 // 				so jump out from container
				$query->removeContainer();
			}
			$box->msg->onEndGotoUrl($query->getStringVars());
			$this->setAllMessagesContent(STDELETE, $box);
			$result= $box->delete();
			$this->addObj($box);
			return $result;
		}
		public function getNotChoiceTables() : array
		{
			$action= $this->getAction();
			$aTables= &$this->getTables();
			$aktTableName= $this->getTableName();

			$aNoChoice= array();
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
    					$aNoChoice[$tableName]= $tableName;
    				}
    			}
			}
			return $aNoChoice;
		}
		/**
		 * Return a table tag with all tables, which can be choosen.<br>
		 * Only current table can not choosen.
		 * 
		 * @param string|bool $baseURL if set, all links of the tables will be set to this URL
		 * 								and also the query show to an new container
		 * @param string|bool $align alignment of the table
		 * @param bool $bNewContainer if true, the query show to an new container
		 * @return STChooseBox the table tag
		 */
		function getChooseTableTag(string|bool $baseURL= NULL, string|bool $align= "center", bool $bNewContainer= false) : STChooseBox
		{
			if(is_bool($baseURL))
			{
				$bNewContainer= $baseURL;
				if(preg_match("/\.php|htm(l)?/i", $align))
				{
					$baseURL= $align;
					$align= "center";
				}else
					$baseURL= NULL;
			}elseif(is_bool($align))
			{
				$bNewContainer= $align;
				$align= "center";
			}
			$action= $this->getAction();
			$aNoChoice= $this->getNotChoiceTables();
			$this->aNoChoice= array_merge($this->aNoChoice, $aNoChoice);
			if(isset($this->oUsedContainerLayer))
			{
				$fromContainer= $this->oUsedContainerLayer;
				$aNoChoice= $fromContainer->getNotChoiceTables();
				$this->aNoChoice= array_merge($this->aNoChoice, $aNoChoice);
			}else
				$fromContainer= $this;
			$chooseTable= new STChooseBox($fromContainer);
			if(isset($this->oUsedContainerLayer))
				$chooseTable->setCurrentTableName($this->getName());
			if(!isset($baseURL))
			{
				$baseURL= $this->starterPage;
				if(	!$baseURL &&
					isset($this->oExternSideCreator)	)
				{
					$baseURL= $this->oExternSideCreator->getStartPage();
					$this->starterPage= $baseURL;
				}
			}else
				$this->starterPage= $baseURL;
			$chooseTable->align($align);
			$chooseTable->setStartPage($baseURL);
			$chooseTable->noChoise($this->aNoChoice);
			$chooseTable->forwardByOne();
    		$nExistTables= $chooseTable->execute($bNewContainer);
			if(!$nExistTables)
			{// wenn auf garkeine Tabelle zugegriffen werden kann
			 // erzeuge einen Fehler
				$oTable= &$this->getTable();
				if(	STCheck::isDebug()
					&&
					!$oTable	)
				{
					STCheck::echoDebug(true, "no tables exist, install anyone before, or use STDbSiteCreator->install()");
					exit;
				}else
					$oTable->hasAccess($action, true);
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

			$this->oCurrentListTable= new STListBox($this);
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
  					    $this->oCurrentListTable->callback($this->msgBox->getMessageContent("UPDATE"), "st_list_table_changing_access", STLIST);
  					    $this->oCurrentListTable->callback($this->msgBox->getMessageContent("DELETE"), "st_list_table_changing_access", STLIST);
  					}else
  					{
  					    $table->doUpdate(false);
  						$table->doDelete(false);
  					}
  				}elseif(isset($table->sAccessClusterColumn[STUPDATE]))
  				{
  				    if($checked[STUPDATE])
  					    $this->oCurrentListTable->callback($this->msgBox->getMessageContent("UPDATE"), "st_list_table_changing_access", STLIST);
  					else
  					    $table->doUpdate(false);

  				}elseif(isset($table->sAccessClusterColumn[STDELETE]))
  				{
  				    if($checked[STDELETE])
  					    $this->oCurrentListTable->callback($this->msgBox->getMessageContent("DELETE"), "st_list_table_changing_access", STLIST);
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
		// param $table muss ein objekt vom typ STBaseBox sein
		private function setAllMessagesContent($action, &$oBox)
		{		    
		    STCheck::paramCheck($oBox, 2, "STBaseBox");
		    
		    $oBox->setLanguage($this->locale['language'], $this->locale['nation']);
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
		$tableName= $this->getOlderLinkedTableName();
		$table= &$olderContainer->getTable($tableName);
		return $table;
	}
	function getOlderLinkedTableName(int $nTable= 1) : string
	{
		if($this->bFirstContainer)
		{
			// container be the first,
			// no older linkedTable can be set
			return "";
		}

		//search older container in get-params
		//to set the last table from this container
		$query= $this->getContainerGetParams();
		if(	!isset($query['link']['from']) ||
			empty($query['link']['from'])	)
		{
			return "";
		}
		$count= 1;
		$tableName= "";
		foreach(array_reverse($query['link']['from']) as $link)
		{
			if(is_array($link))
			{
				$tableName= array_key_first($link);
				if($nTable == $count)
					break;
				$tableName= ""; // was wrong table
				++$count;
			}
		}
		return $tableName;
	}
	/**
	 * return cluster for the current entry of table.<br />
	 * Only when a session defined and the object
	 * is the current container.
	 * 
	 * @param string $action current action
	 * @return array if array is empty no cluster from any older linked table exist
	 */
	function getLinkedCluster(string $action) : array
	{
	    if(	!STUserSession::sessionGenerated() ||
    		!$this->currentContainer() ||
			$this->bFirstContainer /*side from first container cannot have any linked cluster*/  )
		{
    	    return array();
		}

		$aRv= array();
		$deep= 1;
		$tableName= $this->getOlderLinkedTableName($deep);
		while($tableName)
		{
			$table= $this->getTable($tableName);
			$clusterColumns= $table->getAccessClusterColumns();
			$aRv= array_merge($aRv, $clusterColumns);
			++$deep;
			$tableName= $this->getOlderLinkedTableName($deep);
		}
    	return $aRv;
		
/*    	$pk= $table->getPkColumnName();
    	$session= &STUserSession::instance();
    	$aAccess= $session->getDynamicClusters($table);
		return $aAccess;

		showLine();
		echo "from table:$tableName<br>";
		echo "access clusters:";
		$clusterColumns= $table->getAccessClusterColumns();
		if(!count($clusterColumns))
			return array();
		showLine();
		st_print_r($clusterColumns, 2);

		$query= new STQueryString();
		$value= $query->getLimitation($tableName);
		st_print_r($value);
		if(	isset($value) )
		{
			$limitColumn= key($value);
			$limitValue= $value[$limitColumn];
			echo "limit column:$limitColumn<br>";
			$selector= new STDbSelector($table);
			foreach($clusterColumns as $cluster)
			{
				$selector->select($table->Name, $cluster['column']);
			}
			$selector->where("$limitColumn='$limitValue'");
			$selector->execute();
			$clusterSelect= $selector->getResult();
			showLine();
			st_print_r($clusterSelect);
		}else
			$clusterSelect= array();


		showLine();
		echo "linked key:$linkedKey<br>";
		echo "....... PK:$pk<br>";
    	if($linkedKey!=$pk)
    	{
    	    $selector= new STDbSelector($table);
    	    $selector->select($table->Name, $pk);
    	    $selector->where($linkedKey."=".$linkedValue);
    	    $selector->execute();
    	    $linkedValue= $selector->getSingleResult();
    	}
    	$cluster= $aAccess[$action][$linkedValue];
    	showLine();
    	echo "getLinkedCluster($pkValue) from table {$tableName}";
    	st_print_r($table->show, 2);

    	return $cluster;*/
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
	public function getAccessCluster($action)
	{
	    $clusters= STBaseContainer::getAccessCluster($action);
	    if( isset($this->parentContainer) &&
	        $this->parentContainer != $this    )
	    {
	        $clusters= array_merge($clusters, $this->parentContainer->getAccessCluster($action));
	    }
	    return $clusters;
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
						    $cluster= $oCallback->getValue($info["column"], $row);
								break;
						}
				}
				//echo $cluster."<br />";
				if(!$cluster)
				    return;
				if(!$session->hasAccess($cluster, null, null, $action, false))
				    $oCallback->setValue("");
		}
		
?>