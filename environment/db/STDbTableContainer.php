<?php

require_once($php_html_description);
require_once($php_htmltag_class);

 
$global_array_all_exist_stdbtableContainers= array();

/**
 * represent a container with only one table inside
 * with navigation lists if needed
 * @author Alexander Kolli
 *
 */
class STDbTableContainer
{
	var $bFirstContainer= false; // ob der Container der erste f�r STDbSiteCreator ist
	var $name;  // Name des Containers mit dem er als Objekt gehandelt wird
				// und f�r den Button wenn kein identifName angegeben wird
	var $get_vars;
	var	$identifName= null; // Name des Containers wenn er als Button angezeigt wird
	var $projectIdentif= null; // Name des Projektes, wenn er nicht gesetzt wird, wird der Projekt-Name aus STSiteCreator genommen
	var $db; // Datenbank-Objekt
	var $tables= array(); // alle OSTDbTable Objekte welche f�r die Auflistung gebraucht werden
	var	$sFirstTableName; //erste Tabelle
	var $sFirstAction= STCHOOSE;  // gesetzte erste Aktion
	var	$actions= array(); // welche Aktion die Tabelle in der ersten auflistung haben soll
	var $bChooseByOneTable= false; // soll auch bei einer Tabelle die Auswahl erscheinen?
	var $aContainer= array(); // alle STDbTableContainer welche zur Auswahl aufgelistet werden
	
	var $aBehindHeadLineButtons= array();
	var $aBehindProjectIdentif= array();
	var $aBehindTableIdentif= array();
	
	var	$bChooseInTable= true; // ob die anderen Tabellen als Button angezeigt werden sollen
	var $bDisplayedTable= false; // ob auch die gerade angezeigte Tabelle als Button angezeigt werden soll
	
	var $aNavigationTables= array(); // Alle Tabellen die im Container zus�tlich in einer STListTable angezeigt werden sollen 
	var	$aNoChoice= array(); // wenn eine Tabelle in der Auswahl nicht angezeigt werden soll
							 // aber wegen Einstellungen gebraucht wurde
							 // kann die Auswahl damit herausgenommen werden
	var	$getParmNavigation= array(); // alle Parameter die bei einem link aus dem NavigationTable
									 // eingestellt werden
	var	$getParmListLinks= array();	// alle Parameter die bei einem link aus dem Main-Table
									// eingestellt werden
		
	function __construct($name, &$container)
	{
		global	$global_array_all_exist_stdbtableContainers;
		
		Tag::echoDebug("container", "create new TableContainer <b>$name</b>");
		Tag::paramCheck($name, 1, "string");
		Tag::paramCheck($container, 2, "STDbTableContainer");
		
		$this->name= $name;
		$this->db= &$container->getDatabase();
		$this->get_vars= new GetHtml();
		//echo "create Container \"$name\"<br />";
		/*$tables= $db->list_tables();
		st_print_r($tables);
		foreach($tables as $table)
		{
			Tag::alert($name==$table, "STDbTableContainer::STDbTableContainer(\"$name\", &".get_class($db).")",
						"can not create container \"$name\" with same name of table");
		}*/
		Tag::alert(isset($global_array_all_exist_stdbtableContainers[$name]),
					"STDbTableContainer::STDbTableContainer(\"$name\", &".get_class($db).")",
					"container \"$name\" already exists");
		$global_array_all_exist_stdbtableContainers[$name]= &$this;
	}
	function navigationTable($table, $pos= null, $classId= "STNavigationTable")
	{
		Tag::paramCheck($table, 1, "string", "STBaseTable");
		
		if(typeof($table, "string"))
			$table= $this->getTable($table);
		$array= array("table"=>$table, "pos"=>$pos, "class"=>$classId);
		$this->aNavigationTables[]= $array;
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
	// deprecated
	function containerChoice($container)
	{
		$this->needContainer($container);
	}
	function getDefinedContainerNames()
	{
		return $this->aContainer;
	}
	function &needContainer($container, $identification= null)
	{
		Tag::paramCheck($container, 1, "STDbTableContainer", "string");
		Tag::paramCheck($identification, 2, "string", "empty(string)", "null");
		
		if(typeof($container, "STDbTableContainer"))
		{
			$containerName= $container->getName();
			// container is a copy, so take it from List for return
			$container= unknown_STDbTableContainer::getContainer($containerName);
		}else
		{
			$containerName= $container;
			$container= unknown_STDbTableContainer::getContainer($containerName);
			if(!$container)
				$container= new unknown_STDbTableContainer($containerName, $this);
		} 
		$this->aContainer[]= $containerName;
		if($identification!==null)
			$container->setIdentification($identification);
		// alex 04/08/2005: brauch ich doch nicht
/*		// alex 04/08/2005:	trage den ParentContainer im hereinkommenden ein
		//					ist dieser bereits ein container darf der ParentContainer
		//					jedoch nur im Container aus der Container-Liste eingetragen werden
		//					da der hereinkommende keine Refferenz auf diesen hat
		$oContainer= &$this->getContainer($container);
		$oContainer->setParentContainer($container);*/
		return $container;
	}
	function isAktContainer()
	{
		$get= new GetHtml();
		$get= $get->getArrayVars();
		$containerName= $get["stget"]["container"];
		if($this->name==$containerName)
			return true;
		if(!$containerName)
		{
			if($this->bFirstContainer)
				return true;
			return null;
		}
		return false;
	}
	function insertByContainerLink($param, $name)
	{
		$this->getParmButton[$name][STINSERT][]= $param;
	}
	function updateByContainerLink($param, $name)
	{
		$this->getParmButton[$name][STUPDATE][]= $param;
	}
	function deleteByContainerLink($param, $name)
	{
		$this->getParmButton[$name][STDELETE][]= $param;
	}
	function insertByMainListLink($param)
	{
		$this->getParmListLinks[STINSERT][]= $param;
	}
	function updateByMainListLink($param)
	{
		$this->getParmListLinks[STUPDATE][]= $param;
	}
	function deleteByMainListLink($param)
	{
		$this->getParmListLinks[STDELETE][]= $param;
	}
	function insertByNavigationLink($param, $name= "STNavigationTable")
	{
		$this->getParmNavigation[$name][STINSERT][]= $param;
	}
	function updateByNavigationLink($param, $name= "STNavigationTable")
	{
		$this->getParmNavigation[$name][STUPDATE][]= $param;
	}
	function deleteByNavigationLink($param, $name= "STNavigationTable")
	{
		$this->getParmNavigation[$name][STDELETE][]= $param;
	}
	function setGlobalObjectContainer($container)
	{
		global	$global_first_objectContainerName;
		
		if(typeof($container, "STDbTableContainer"))
			$container= $container->getName();
		elseif(Tag::isDebug())
		{
			$bExist= false;
			foreach($global_array_all_exist_stdbtableContainers as $name=>$container)
			{
				if($name==$container)
					$bExist= true;
			}
			Tag::alert(!$bExist, "STDbTableContainer::setGlobalObjectContainer()",
														"container ".$container." not exist in list");
		}
		$global_first_objectContainerName= $container;
	}
	function &getContainer($containerName= null)
	{
		global	$HTTP_GET_VARS,
				$global_first_objectContainerName,
				$global_array_all_exist_stdbtableContainers;
			
		if(!$containerName)
		{
			$containerName= $HTTP_GET_VARS["stget"]["container"];
			if(!$containerName)
			{
				$containerName= $global_first_objectContainerName;
				if(Tag::isDebug())
					STCheck::is_warning(!$containerName, "STDbTableContainer::getContainer()",
																"no globaly container set");
				if(!$containerName)
					return $this;
			}
		}	
		foreach($global_array_all_exist_stdbtableContainers as $name=>$container)
		{
			if($name==$containerName)
				return $global_array_all_exist_stdbtableContainers[$name];
		}
		Tag::alert(true, "STDbTableContainer::getContainer()", "container '$containerName' is not set in container-List");
	}
	function &getAllContainer()
	{
		global	$global_array_all_exist_stdbtableContainers;
		
		return $global_array_all_exist_stdbtableContainers;
	}
	function getAllContainerNames()
	{
		global	$global_array_all_exist_stdbtableContainers;
		
		$aRv= array();
		foreach($global_array_all_exist_stdbtableContainers as $name=>$container)
			$aRv[]= $name;
		return $aRv;
	}
	// alex 04/08/2005: brauch ich doch nicht
/*	function setParentContainer($container)
	{
		if(!$this->sParentContainer)
		{
			if(typeof($container, "STDbTableContainer"))
				$container= $container->getName();
			$this->sParentContainer= $container;
		}
	}
	function &getParentContainer()
	{
		if(!$this->sParentContainer)
			return null;
		return $this->getContainer($this->sParentContainer);
	}
	function getParentContainerName()
	{
		return $this->sParentContainer;
	}*/
	function getName()
	{
		return $this->name;
	}
	function setIdentification($name)
	{
		$this->setDisplayName($name);
	}
	function setDisplayName($name)
	{
		$this->identifName= $name;
	}
	function setProjectIdentifier($project)
	{
		Tag::deprecated("STDbTableContainer::setDisplayName($project)", "STDbTableContainer::setProjectIdentifier($project)");
		$this->setProjectDisplayName($project);
	}
	function setProjectDisplayName($project)
	{
		$this->projectIdentif= $project;
	}
	function getProjectIdentifier()
	{
		Tag::deprecated("STDbTableContainer::getProjectDisplayName()", "STDbTableContainer::getProjectIdentifier()");
		return $this->getProjectDisplayName();
	}
	function getProjectDisplayName()
	{
		return $this->projectIdentif;
	}
	function getIdentification()
	{
		Tag::deprecated("STDbTableContainer::getDisplayName()", "STDbTableContainer::getIdentification()");
		return $this->getDisplayName();
	}
	function getDisplayName()
	{
		$name= $this->identifName;
		if(!isset($name))
			$name= $this->name;
		return $name;
	}
	function &getDatabase()
	{
		return $this->db;
	}
	function need(&$object)
	{
		if(typeof($object, "STDbTableContainer"))
			$name= "container_".$name;
		else
			Tag::alert(!typeof($object, "OSTDbTable"), "STDbTabelContainer::need(&object:".get_class($object).")",
									"param must be an object from OSTDbTabel or STDbTableContainer");
		$this->tables[$name]= &$object;
	}
	function &needTable($sTableName)
	{	
		// alex 12/04/2005: �berpr�fe nun zuerst ob die Tabelle schon vorhanden ist	
		//$table= new OSTDbTable($sTableName, $this);
		//$this->tables[$sTableName]= &$table;
		$table= &$this->tables[$sTableName];		
		if(!$table)
		{	// alex 17/05/2005:	die Tabelle wird nun von der �berschriebenen Funktion
			//					getTable() aus dem Datenbank-Objekt erzeugt.
			// alex 08/06/2005: 2. Parameter f�r getTable auf false gesetzt
			//					da sonst wenn das erste mal needTable aufgerufen wird,
			//					bei der funktion getTable aus der Datenbank,
			//					alle Tabellen in die this->tables geladen werden
			$table= $this->db->getTable($sTableName, false);
			$table->abOrigChoice= array();
			$this->tables[$sTableName]= &$table;
			// alex 04/06/2005:	wenn die Datenbank nicht mit ampersand "&" vor new erzeugt wurde
			//					ist die Datenbank in der Variable $this->db nicht die gleiche
			//					zur Sicherheit -> zus�tzlich eintragen
			if(typeof($this, "OSTDatabase"))
			{
				if(!$this->db->tables[$sTableName])
					$this->db->tables[$sTableName]= &$table;
			}
		}
		return $table;
	}
	function &getTable($tableName)
	{
		Tag::paramCheck($tableName, 1, "string");
		$nParams= func_num_args();
		Tag::lastParam(1, $nParams);
		
		$table= null;
		//echo "----------------------------------------------------------------------------------------------------------------------------<br>";
		// alex 08/07/2005: die Tabelle wird nun auch ohne Referenz geholt
		//					�nderungen jetzt ausserhalb m�glich
		//					und die Tabelle ist dann nicht automatisch in $this->tables eingetragen
		//					wenn hier eine Referenz geholt w�rde w�re sie automatisch
		//					bei $this->db->getTable in $this->tables eingetragen
		if(isset($this->tables[$tableName]))
		  $table= &$this->tables[$tableName];
		if($table === null)
		{	// alex 24/05/2005:	// alex 17/05/2005:	die Tabelle wird von der �berschriebenen Funktion
			//					getTable() aus dem Datenbank-Objekt geholt.
			//					nat�rlich ohne Referenz, da sie wom�glich noch ge�ndert wird
			$table= $this->db->getTable($tableName);
		}
		return $table;
	}
	function &getTables($onError= onErrorStop)
	{
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
				$this->tables[$name]= $this->db->getTable($name, $bAllByNone);
		}
		//st_print_r($this->tables, 1);
		return $this->tables;
	}
	function setFirstTable($tableName, $action= STLIST)
	{
		Tag::paramCheck($tableName, 1, "string");
		Tag::paramCheck($action, 2, "check", $action==STLIST||$action==STINSERT||$action==STUPDATE,
										"STLIST", "STINSERT", "STUPDATE");
										
		$this->sFirstTableName= $tableName;
		$this->sFirstAction= $action;
		$this->actions[$tableName]= $action;
	}
	function isDbTable($tableName)
	{
	    //echo __FILE__.__LINE__."<br>";
	    //echo "----------------------------------------------------------------------------------------------------------------------------<br>";
		if($this->getTable($tableName))
			return true;
		return false;
	}
	function getTableName()
	{
		global $HTTP_GET_VARS;
				
		$tableName= $HTTP_GET_VARS["stget"]["table"];		
		if($tableName)
		{
			if(!$this->isDbTable($tableName))
				return null;
			return $tableName;
		}
		$tableName= $this->getFirstTableName();
		return $tableName;
	}
	function setFirstActionOnTable($action, $tableName)
	{
		$this->actions[$tableName]= $action;
	}
	function getActions()
	{
		foreach($this->tables as $table)
		{
			$tableName= $table->getName();
			if(!$this->actions[$tableName])
				$this->actions[$tableName]= STLIST;
		}
		return $this->actions;
	}
	function getFirstTableName()
	{
		$tableName= $this->sFirstTableName;
		if(	!$tableName
			and
			count($this->tables)==1
			and
			!$this->bChooseByOneTable	)
    	{
    		reset($this->tables);
    		$tableName= key($this->tables);
    	}
		return $tableName;
	}
	function getFirstAction($table= null)
	{
		$action= $this->sFirstAction;
		if(	$action==STCHOOSE
			or
			$table				)
		{
			$actions= $this->getActions();
			if($table)
				return $actions[$table];
			if(	count($actions)==1
				and
				!$this->bChooseByOneTable	)
			{
				$action= reset($actions);
			}
		}
		return $action;
	}
	function getAction()
	{
		global $HTTP_GET_VARS;
		
		$action= $HTTP_GET_VARS["stget"]["action"];
		$table= $this->getTableName();
		if(	!$action
			or
			!$table	)// wenn die Tabelle nicht stimmt, stimmt auch die Aktion nicht
			$action= $this->getFirstAction();
		return $action;	
	}
	function chooseAlsoByOneTable()
	{
		$this->bChooseByOneTable= true;
	}
	function addObjBehindHeadLineButtons(&$tag)
	{
		Tag::paramCheck($tag, 1, "Tag", "string");
		
		if(typeof($tag, "STSearchBox"))
			$this->asSearchBox[$tag->categoryName]= "exist";
		$this->aBehindHeadLineButtons[]= &$tag;
	}
	function addBehindHeadLineButtons($tag)
	{
		Tag::paramCheck($tag, 1, "Tag", "string");
		
		if(typeof($tag, "STSearchBox"))
			$this->asSearchBox[$tag->categoryName]= "exist";
		$this->aBehindHeadLineButtons[]= &$tag;
	}
	function addObjBehindProjectIdentif(&$tag)
	{
		Tag::paramCheck($tag, 1, "Tag", "string");
		
		if(typeof($tag, "STSearchBox"))
			$this->asSearchBox[$tag->categoryName]= "exist";
		$this->aBehindProjectIdentif[]= &$tag;
	}
	function addBehindProjectIdentif($tag)
	{
		Tag::paramCheck($tag, 1, "Tag", "string");
		
		if(typeof($tag, "STSearchBox"))
			$this->asSearchBox[$tag->categoryName]= "exist";
		$this->aBehindProjectIdentif[]= &$tag;
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
	function doChoice($table, $choice= true)
	{
		if(typeof($table, "STBaseTable"))
			$table= $table->getName();
		if($choice)
			unset($this->aNoChoice[$table]);
		else
			$this->aNoChoice[$table]= $table;
	}
	function tableChoice($table)
	{
		if(typeof($table, "STBaseTable"))
			$table= $table->getName();
		if($this->aNoChoice[$table])
			return false;
		$tables= $this->getTables();
		if($tables[$table])
			return true;
		return false;
	}
}

?>