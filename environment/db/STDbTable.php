<?php

require_once($_stbasetable);

class STDbTable extends STBaseTable
{
	var	$db; // database
    var $container;
	var $aAuto_increment= array(); // um ein Feld mit Autoincrement vor dem eigentlichen Insert zu holen
	var	$password= array(); // all about to set an password in database

    function __construct($Table, $container= null, $onError= onErrorStop)
    {
		Tag::paramCheck($Table, 1, "string", "STBaseTable");
		Tag::paramCheck($container, 2, "STObjectContainer", "string", "null");

		$this->abNewChoice= array();
		$this->bSelect= false;
		$this->bTypes= false;
		$this->bIdentifColumns= false;

		if(typeof($Table, "STBaseTable"))
		{
			$tableName= $Table->Name;
		}else
		{
			$co= &$container;
			if(is_string($co))
			{
				$co= null;
				$co= STBaseContainer::getContainer($container);
			}
			$db= &$co->getDatabase();
			$desc= &STDbTableDescriptions::instance($db->getDatabaseName());
			$tableName= $desc->getTableName($Table);
			$this->onError= $onError;
		}
		if(Tag::isDebug())
		{
			Tag::alert(	!$container
						and
						(	typeof($Table, "string")
							or
							!typeof($Table, "STDbTable")	),
						"STDbTable::constructor()",
						"if first parameter in constructor of <b>STDbTable</b> ".
						"is an table-name(".$tableName."), or object from STBaseTable, ".
						"second parameter must be an object from STObjectContainer"			);
			if(typeof($Table, "string"))
				Tag::echoDebug("table", "create new object for table <b>".$Table."</b>");
			else
				Tag::echoDebug("table", "make copy from table-object <b>".$Table->Name."</b> for an new one");
		}
		$this->created[$tableName]= true;
		if(typeof($Table, "STBaseTable"))
		{
		    $this->copy($Table);
		}//else
		STBaseTable::__construct($Table);
		if($container)
		{
			if(is_string($container))
				$containerName= $container;
			else
				$containerName= $container->getName();
			$this->container= &STBaseContainer::getContainer($containerName);
		}else
			$this->container= &$Table->container;
		$this->db= &$this->container->getDatabase();
		if(typeof($Table, "STBaseTable"))
		{
			$this->columns= $Table->columns;
			return;
		}else
		{
    		Tag::echoDebug("describeTable", "table constructor file:".__file__." line:".__line__);
        	$fieldArray= $this->db->describeTable($tableName, $onError);
    		$this->columns= &$fieldArray;
			$this->error= $this->db->isError();
    		foreach($fieldArray as $field)
      		{
      			if(	preg_match("/pri_key/i", $field["flags"]) ||
				  	preg_match("/primary_key/i", $field["flags"])		)
				{
      				$this->sPKColumn= $field["name"];
				}
				if(preg_match("/multiple_key/i", $field["flags"]))
				{
					$sql=  "select `REFERENCED_TABLE_SCHEMA`, `REFERENCED_TABLE_NAME`, `REFERENCED_COLUMN_NAME` ";
					$sql.= "FROM `information_schema`.`KEY_COLUMN_USAGE` ";
					$sql.= "WHERE `TABLE_SCHEMA`='".$this->db->getDatabaseName()."' ";
					$sql.= " and  `TABLE_NAME` = '$tableName'";
					$sql.= " and  `COLUMN_NAME`='".$field["name"]."'";
					$sql.= " and  `REFERENCED_COLUMN_NAME` is not NULL";
					$this->db->query($sql);
					$result= $this->db->fetch_row(STSQL_NUM);
					
					if(	isset($result) &&
						isset($result[0]) &&
						is_array($result) &&
						count($result) == 3	)
					{
						$this->fk($field["name"], $result[1], $result[2]);
						//echo "result of Fk for $tableName.$name:<br>";
						//st_print_r($this->FK, 2);echo "<br>";
					}
				}
      		}
		}
    }
	function copy($oTable)
	{
		STCheck::param($oTable, 0, "STDbTable");
		
		STBaseTable::copy($oTable);
		// 08/09/2006 alex:	db and container should be change in constructor,
		//					because before changed here and if an other db or container
		//					witch is change again in the constructor,
		//					it will be change all db/container in this session
		// 16/03/2017 alex:	try now with unset before 
		unset($this->db);
		$this->db= &$oTable->db;
		unset($this->container);
		$this->container= $oTable->container;
		$this->onError= $oTable->onError;
		$this->sAcessClusterColumn= $oTable->sAcessClusterColumn;
		$this->password= $oTable->password;
	}
	public function getColumnName($column)
	{
	    STCheck::paramCheck($column, 1, "string", "int");
	    
	    if(!is_int($column))
	    {
    	    $desc= STDbTableDescriptions::instance($this->db->getDatabaseName());
    	    $column= $desc->getColumnName($this->Name, $column);
	    }
	    return STBaseTable::getColumnName($column);
	}
	/**
	 * inform whether content of parameter is an keyword.
	 *
	 * @param string $column content of column
	 * @return array array of keyword, column, type and len, otherwise false.<br />
	 *                 the keyword is in lower case and have to be const/max/min<br />
	 *                 the column is the column inside the keyword (not shure whether it's a correct name/alias)<br />
	 *                 the type of returned value by execute
	 *                 the len of returned value by execute
	 */
	public function sqlKeyword(string $column)
	{
	    return $this->db->keyword($column);
	}
	//function select($tableName, $column, $alias= null, $nextLine= true, $add= false)
	public function select($column, $alias= null, $fillCallback= null, $nextLine= null, $add= false)
	{
		if(STCheck::isDebug())
		{
			Tag::paramCheck($column, 1, "string");
			Tag::paramCheck($alias, 2, "string", "function", "TinyMCE", "bool", "null");
			Tag::paramCheck($fillCallback, 3, "function", "TinyMCE", "bool", "null");
			Tag::paramCheck($nextLine, 4, "bool", "null");
			$nParams= func_num_args();
			Tag::lastParam(4, $nParams);
		}

    	$field= $this->findColumnOrAlias($column);
    	if($field["type"] == "not found")
    	{
			if(STCheck::isDebug())
			{
				STCheck::alert(!$this->columnExist($column), "STBaseTable::selectA()",
											"column '$column not exist in table ".$this->Name.
											"(".$this->getDisplayName().")");
			}else
				echo "column '$column not exist in table ".$this->Name.
											"(".$this->getDisplayName().")";
    	}
		STBaseTable::select($column, $alias, $fillCallback, $nextLine);
	}
    function passwordNames($firstName, $secondName, $thirdName= null)
    {
    	$this->password["names"][]= $firstName;
    	$this->password["names"][]= $secondName;
    	if($thirdName)
    		$this->password["names"][]= $thirdName;
    }
    /**
     * define the column as a password field.<br />
     * The column inside the database should be a not null field or be defined with STDbTable::needValue(<column>, STInsert) from script.
     * But the update action inside this framework is defined as default first as an optional column. Because when user update the table
     * with a password column, the password shouldn't change if the user define no new one.
     *  
     * @param string $fieldName name of column or alias column
     * @param boolean $bEncode whether the password should encoded inside the database
     */
    function password(string $fieldName, bool $bEncode= true)
    {
    	$this->password["column"]= $fieldName;
    	$this->password["encode"]= $bEncode;
    	$this->optional($fieldName, STUPDATE);
    }
	function addAccessClusterColumn($column, $parentCluster, $clusterfColumn, $accessInfoString, $addGroup= true, $action= STALLDEF)
	{
		Tag::paramCheck($column, 1, "string");
		Tag::paramCheck($parentCluster, 2, "string", "null");
		Tag::paramCheck($accessInfoString, 3, "string", "empty(string)");
		Tag::paramCheck($clusterfColumn, 4, "string", "null");
		Tag::paramCheck($addGroup, 5, "boolean");
		Tag::paramCheck($action, 6, "string", "int");
		//Tag::paramCheck($parentCluster, 7, "string", "null");
		Tag::alert(!$this->columnExist($column), "STBaseTable::addAccessClusterColumn()",
											"column $column not exist in table ".$this->Name.
											"(".$this->getDisplayName().")", 1);
		Tag::alert(!$this->columnExist($clusterfColumn), "STBaseTable::addAccessClusterColumn()",
											"column for cluster $clusterfColumn not exist in table ".$this->Name.
											"(".$this->getDisplayName().")", 1);


		$this->getColumn($column);
		if($action==STACCESS)
			$action= STLIST;
		if($accessInfoString=="")
		{
			if($action==STLIST)
			{
				$accessInfoString= "berechtigung zum ansehen";
				$accessInfoString.= " des Eintrages \'@\' in der Tabelle ".$this->getDisplayName();
			}elseif($action==STINSERT)
			{
				$accessInfoString= "Berechtigung zum erstellen neuer Eintr�ge";
				$accessInfoString.= " von '\'@\' in der Tabelle ".$this->getDisplayName();
			}elseif($action==STUPDATE)
			{
				$accessInfoString= "Berechtigung zum �ndern der Eintr�ge";
				$accessInfoString.= " von \'@\' in der Tabelle ".$this->getDisplayName();
			}elseif($action==STDELETE)
			{
				$accessInfoString= "Berechtigung zum l�schen der Eintr�ge";
				$accessInfoString.= " von \'@\' in der Tabelle ".$this->getDisplayName();
			}elseif($action==STADMIN)
			{
				$accessInfoString= "�nderungs-Berechtigung der Eintr�ge";
				$accessInfoString.= " von \'@\' in der Tabelle ".$this->getDisplayName();
			}else
				$accessInfoString= "vom Programmierer nicht definierter Zugriff";
		}else
			$accessInfoString= preg_replace("/'/", $accessInfoString, "\'");


		$this->sAcessClusterColumn[]= array(	"action"=>	$action,
												"column"=>	$column,
												"parent"=>	$parentCluster,
												"cluster"=>	$clusterfColumn,
												"info"=>	$accessInfoString,
												"group"=>	$addGroup			);
	}
	function accessCluster($column, $clusterfColumn, $accessInfoString= "", $addGroup= true, $parentTable= null, $pkValue= null)
	{
		STCheck::paramCheck($column, 1, "string");
		STCheck::paramCheck($clusterfColumn, 2, "string");
		STCheck::paramCheck($accessInfoString, 3, "string", "empty(string)");
		STCheck::paramCheck($addGroup, 4, "boolean");
		STCheck::paramCheck($parentTable, 5, "STBaseTable", "string", "boolean", "null");
		STCheck::paramCheck($pkValue, 6, "string", "int", "float", "boolean", "null");

		if(is_bool($parentTable))
		{
			$addGroup= $parentTable;
			$parentTable= null;
		}
		if(is_bool($pkValue))
		{
			$addGroup= $pkValue;
			$pkValue= null;
		}
		$action= STLIST;
		//st_print_r($this->asAccessIds[$action],2);
		$parentCluster= $this->asAccessIds[$action]["cluster"];
		//echo "action:$action<br />";
		//echo "parentCluster:$parentCluster<br />";
		if($this->container->currentContainer())	// if the container is not aktual
		{										// does not need an parent-cluster,
			if(	!$parentCluster					// because an insert box can not appear
				or
				preg_match("/,/", $parentCluster)	)
			{
				$parentCluster= $this->container->getLinkedCluster($action, $parentTable, $pkValue);
			}
			Tag::alert(!$parentCluster, "STDbTable::accessCluster()", "no parentCluster be set");
		}
		$this->addAccessClusterColumn($column, $parentCluster, $clusterfColumn, $accessInfoString, $addGroup, $action);
		//echo "end of accessCluster in Container ".$this->container->getName()."<br />";
	}
	function insertCluster($column, $clusterfColumn, $accessInfoString= "", $addGroup= true, $parentTable= null, $pkValue= null)
	{
		STCheck::paramCheck($column, 1, "string");
		STCheck::paramCheck($clusterfColumn, 2, "string");
		STCheck::paramCheck($accessInfoString, 3, "string", "empty(string)");
		STCheck::paramCheck($addGroup, 4, "boolean");
		STCheck::paramCheck($parentTable, 5, "STBaseTable", "string", "boolean", "null");
		STCheck::paramCheck($pkValue, 6, "string", "int", "float", "boolean", "null");

		if(is_bool($parentTable))
		{
			$addGroup= $parentTable;
			$parentTable= null;
		}
		if(is_bool($pkValue))
		{
			$addGroup= $pkValue;
			$pkValue= null;
		}
		$action= STINSERT;
		$parentCluster= $this->asAccessIds[$action]["cluster"];
		if($this->container->currentContainer())	// if the container is not aktual
		{										// does not need an parent-cluster,
			if(	!$parentCluster					// because an insert box can not appear
				or
				preg_match("/,/", $parentCluster)	)
			{
				$parentCluster= $this->container->getLinkedCluster($action, $parentTable, $pkValue);
			}
			Tag::alert(!$parentCluster, "STDbTable::accessCluster()", "no parentCluster be set");
		}
		$this->addAccessClusterColumn($column, $parentCluster, $clusterfColumn, $accessInfoString, $addGroup, $action);
	}
	function updateCluster($column, $clusterfColumn, $accessInfoString= "", $addGroup= true, $parentTable= null, $pkValue= null)
	{
		STCheck::paramCheck($column, 1, "string");
		STCheck::paramCheck($clusterfColumn, 2, "string");
		STCheck::paramCheck($accessInfoString, 3, "string", "empty(string)");
		STCheck::paramCheck($addGroup, 4, "boolean");
		STCheck::paramCheck($parentTable, 5, "STBaseTable", "string", "boolean", "null");
		STCheck::paramCheck($pkValue, 6, "string", "int", "float", "boolean", "null");

		if(is_bool($parentTable))
		{
			$addGroup= $parentTable;
			$parentTable= null;
		}
		if(is_bool($pkValue))
		{
			$addGroup= $pkValue;
			$pkValue= null;
		}
		$action= STUPDATE;
		$parentCluster= $this->asAccessIds[$action]["cluster"];
		if($this->container->currentContainer())	// if the container is not aktual
		{										// does not need an parent-cluster,
			if(	!$parentCluster					// because an insert box can not appear
				or
				preg_match("/,/", $parentCluster)	)
			{
				$parentCluster= $this->container->getLinkedCluster($action, $parentTable, $pkValue);
			}
			Tag::alert(!$parentCluster, "STDbTable::accessCluster()", "no parentCluster be set");
		}
		$this->addAccessClusterColumn($column, $parentCluster, $clusterfColumn, $accessInfoString, $addGroup, $action);
	}
	function deleteCluster($column, $clusterfColumn, $accessInfoString= "", $addGroup= true, $parentTable= null, $pkValue= null)
	{
		STCheck::paramCheck($column, 1, "string");
		STCheck::paramCheck($clusterfColumn, 2, "string");
		STCheck::paramCheck($accessInfoString, 3, "string", "empty(string)");
		STCheck::paramCheck($addGroup, 4, "boolean");
		STCheck::paramCheck($parentTable, 5, "STBaseTable", "string", "boolean", "null");
		STCheck::paramCheck($pkValue, 6, "string", "int", "float", "boolean", "null");

		if(is_bool($parentTable))
		{
			$addGroup= $parentTable;
			$parentTable= null;
		}
		if(is_bool($pkValue))
		{
			$addGroup= $pkValue;
			$pkValue= null;
		}
		$action= STDELETE;
		$parentCluster= $this->asAccessIds[$action]["cluster"];
		if($this->container->currentContainer())	// if the container is not aktual
		{										// does not need an parent-cluster,
			if(	!$parentCluster					// because an insert box can not appear
				or
				preg_match("/,/", $parentCluster)	)
			{
				$parentCluster= $this->container->getLinkedCluster($action, $parentTable, $pkValue);
			}
			Tag::alert(!$parentCluster, "STDbTable::accessCluster()", "no parentCluster be set");
		}
		$this->addAccessClusterColumn($column, $parentCluster, $clusterfColumn, $accessInfoString, $addGroup, $action);
	}
	function adminCluster($column, $clusterfColumn, $accessInfoString= "", $addGroup= true, $parentTable= null, $pkValue= null)
	{
		STCheck::paramCheck($column, 1, "string");
		STCheck::paramCheck($clusterfColumn, 2, "string");
		STCheck::paramCheck($accessInfoString, 3, "string", "empty(string)");
		STCheck::paramCheck($addGroup, 4, "boolean");
		STCheck::paramCheck($parentTable, 5, "STBaseTable", "string", "boolean", "null");
		STCheck::paramCheck($pkValue, 6, "string", "int", "float", "boolean", "null");

		if(is_bool($parentTable))
		{
			$addGroup= $parentTable;
			$parentTable= null;
		}
		if(is_bool($pkValue))
		{
			$addGroup= $pkValue;
			$pkValue= null;
		}
		$action= STADMIN;
		$parentCluster= $this->asAccessIds[$action]["cluster"];
		if($this->container->currentContainer())	// if the container is not aktual
		{										// does not need an parent-cluster,
			if(	!$parentCluster					// because an insert box can not appear
				or
				preg_match("/,/", $parentCluster)	)
			{
				$parentCluster= $this->container->getLinkedCluster($action, $parentTable, $pkValue);
			}
			Tag::alert(!$parentCluster, "STDbTable::accessCluster()", "no parentCluster be set");
		}
		$this->addAccessClusterColumn($column, $parentCluster, $clusterfColumn, $accessInfoString, $addGroup, $action);
	}
	function needPkInResult($charColumn, $session= "")
	{
		Tag::paramCheck($charColumn, 1, "string");
		Tag::paramCheck($session, 2, "string", "empty(string)");

		$this->needAutoIncrementColumnInResult($this->getPkColumnName(), $charColumn, $session);
	}
	function needAutoIncrementColumnInResult($column, $charColumn, $session= "")
	{
		Tag::paramCheck($charColumn, 1, "string");
		Tag::paramCheck($column, 2, "string");
		Tag::paramCheck($session, 3, "string", "empty(string)");

		if($session=="")
		{
			Tag::alert(!STSession::sessionGenerated(), "STDbTable::needPkInResult()",
							"if third parameter \$session not defined, an session STSession::init() must be created");
			$_instance= &STSession::instance();
			$session= $_instance->getSessionID();
		}
		$this->aAuto_increment["session"]= $session;
		$this->aAuto_increment["inColumn"]= $charColumn;
		$this->aAuto_increment["PK"]= $column;
	}
	/**
	 * fetch table from database of given or current container
	 * 
	 * @param string $sTableName name of the table
	 * @param string $sContainer container name from which table should fetched
	 * @return NULL
	 */
	public function &getTable(string $sTableName, string $sContainer= null)
	{
	    STCheck::param($sTableName, 0, "string");
	    STCheck::param($sContainer, 1, "string", "null");
	    
	    if( $sContainer != null &&
	        $sContainer != $this->container->getName() )
	    {
	        $container= &STBaseContainer::getContainer($sContainer);
	    }else
	        $container= &$this->container;
		return $container->getTable($sTableName);
	}
	function column($name, $type, $len= null)
	{
		$res= $this->getDbColumnTypeLen($name, $type, $len= null);
		STBaseTable::column($name, $res["type"], $res["length"]);
	}
	function dbColumn($name, $type, $len= null)
	{
		Tag::paramCheck($name, 1, "string");
		Tag::paramCheck($type, 2, "string");
		Tag::paramCheck($len, 3, "int", "null");

		$res= $this->getDbColumnTypeLen($name, $type, $len);
		STBaseTable::dbColumn($name, $res["type"], $res["length"]);
	}
	function getDbColumnTypeLen($name, $type, $len= null)
	{//echo "getDbColumnTypeLen($name, $type, $len= null)<br />";
	    $type= strtolower(trim($type));
      	Tag::alert($type=="string"&&$len===null, "STBaseTable::column()",
													"if column $name is an string, \$len can not be NULL");

		$type= strtoupper($type);
		$datatypes= &$this->db->getDatatypes();
		if(preg_match("/(VAR)?CHAR\(([0-9]+)\)/", $type, $preg))
		{
			$type= $preg[1];
			$type.= "CHAR";
			$len= (int)$preg[2];
		}elseif(preg_match("/(SET|ENUM) *\((.+)\)/", $type, $preg))
		{
			$type= $preg[1];
			$split= preg_split("/[ ,]+/", $preg[2]);
			$len= 0;
			foreach($split as $value)
			{
				$calc= strlen($value)-2;
				if($calc>$len)
					$len= $calc;
			}
		}else
		{
			$len= $datatypes[$type]["length"];
			$type= $datatypes[$type]["type"];
		}
		return array(	"type"=>$type,
						"length"=>$len	);
	}
	protected function fk($ownColumn, &$toTable, $otherColumn= null, $bInnerJoin= null, $where= null)
	{
		Tag::paramCheck($ownColumn, 1, "string");
		Tag::paramCheck($toTable, 2, "STBaseTable", "string");
		Tag::paramCheck($otherColumn, 3, "string", "empty(string)", "null");
		Tag::paramCheck($bInnerJoin, 4, "check", $bInnerJoin===null || $bInnerJoin==="inner" || $bInnerJoin==="left" || $bInnerJoin==="right",
											"null", "inner", "left", "right");
		Tag::paramCheck($where, 5, "string", "empty(String)", "STDbWhere", "null");
		
    	$bOtherDatabase= false;
    	if(typeof($toTable, "STDbTable"))
    	{
    		$toTableName= $toTable->getName();
    		if($toTable->db->getDatabaseName()!=$this->db->getDatabaseName())
    			$bOtherDatabase= true;
    	}else
    	{
    		$toTableName= $this->container->getTableName($toTable);
    		unset($toTable);//Adresse auf dieser Variable l�schen
			// check first whether FK points to own new created table
			// otherwise ther will be an endless loop
			if(strtolower($toTableName) == strtolower($this->getName()))
				$toTable= &$this;
			else
    			$toTable= &$this->db->getTable($toTableName);//, false/*bAllByNone*/);
			Tag::alert(!isset($toTable), "STDbTable::fk()", "second parameter '$toTableName' is no exist table");
    	}
		STBaseTable::fk($ownColumn, $toTable, $otherColumn, $bInnerJoin, $where);
		//st_print_r($this->FK,2);
		if($bOtherDatabase)
		{
			foreach($this->aFks[$toTableName] as $key=>$to)
			{
				if($to["own"]===$ownColumn)
				{
					$this->aFks[$toTableName][$key]["table"]= &$toTable;
					break;
				}
			}
			$this->FK[$toTableName]["table"]= &$toTable;
		}else// wenn keine andere Datenbank,
		{	// Tabelle wieder l�schen, da sie automatisch
			// in STBaseTable gesetzt wird
			// weil keine Datenbank vorhanden ist
			// in der die AliasTable aufgelistet sind
			// (also w�re der FK einzige Referenz)
			unset($this->FK[$toTableName]["table"]);
		}
	}
	function &getDatabase()
	{
		return $this->db;
	}
	function getResult($sqlType= null)
	{
		$selector= new STDbSelector($this);
		$selector->execute($sqlType);
		return $selector->getResult();
	}
	function getRowResult($sqlType= null)
	{
		$selector= new STDbSelector($this);
		$selector->execute($sqlType);
		return $selector->getRowResult();
	}
	function getSingleResult($sqlType= null)
	{
		$selector= new STDbSelector($this);
		$selector->execute($sqlType);
		return $selector->getSingleResult();
	}
	function getStatement(bool $bFromIdentifications= false, bool $withAlias= null)
	{
		if(STCheck::isDebug("db.statements.where"))
		{
			$space= STCheck::echoDebug("db.statements.where", "stored where statement for table ".$this->getName());
			st_print_r($this->oWhere, 5, $space);
		}
		return $this->db->getStatement($this, $bFromIdentifications, $withAlias);
	}
	// alex 24/05/2005:	boolean bMainTable auf die Haupttabelle ge�ndert
	//					da dort wenn sie ein Selector ist noch �berpr�fungen
	//					vorgenommen werden sollen, ob der FK auch ausgef�hrt wird
	/**
	 * create part of sql select statement
	 * 
	 * @param bool $bFirstSelect describes whether the method is called by first time.<br />
	 *                           (testing by main-table is same then own object not possible,
	 *                            because the own object/table can also be an foreignKey-table)
	 * @param STDbTable $oMainTable object of the first table from which all other are drawn
	 * @param array $aTableAlias array of all exist alias for tables exist, which gives back all alias which needed
	 * @param bool $withAlias whether alias should set, by null the method decide it self
	 */
	/*private*/function getSelectStatement(bool $bFirstSelect, $oMainTable, array &$aTableAlias, $withAlias)
	{
	    $aUseAliases= array();
	    $singleStatement= "";
	    $statement= "";
	    if($bFirstSelect)
	        $aNeededColumns= $this->getSelectedColumns();
        else
            $aNeededColumns= $this->getIdentifColumns();
        $aGetColumns= array();
        // put only all getColumns into array aGetColumns
        foreach($this->showTypes as $column=>$extraField)
        {
            if(isset($extraField["get"]))
                $aGetColumns[]= $column;
        }
        // add getColumns to selected show-columns
        // which are in array aNeededColumns
        foreach($aGetColumns as $column)
        {
            //if(!isset($aColumns[$column]))
            {
                $aNewColumn= array();
                $aNewColumn["table"]= $this->getName();
                $aNewColumn["column"]= $column;
                $alias= $column;
                if(!$bFirstSelect)// maybe two getColumns are the same from
                {				// the main-table and sub-table
                    $alias= $this->getName()."@".$alias;
                }
                $aNewColumn["alias"]= $alias;
                $aNeededColumns[]= $aNewColumn;
            }
        }
        
        STCheck::flog("create select statement");
        $this->db->removeNoDbColumns($aNeededColumns, $aTableAlias);
        if(!isset($withAlias))
        {
            $withAlias= false;
            foreach($aNeededColumns as $columns)
            {
                if(!in_array($columns['table'], $aUseAliases))
                    $aUseAliases[$aTableAlias[$columns['table']]]= $columns['table'];
            }
            if(count($aUseAliases) > 1)
                $withAlias= true;
        }
        if(STCheck::isDebug())
        {
            $debugSess= "db.statements.aliases";
            if(STCheck::isDebug("db.statements.select"))
                $debugSess= "db.statements.select";
            if(STCheck::isDebug($debugSess))
            {//st_print_r($this->identification,2);
                //echo "<b>[</b>db.statements.select<b>]:</b> make select statement";
                $dbgstr= "make select statement";
                if(!$bFirstSelect)
                    $dbgstr.= " from identifColumns";
                $dbgstr.= " in ";
                if($bFirstSelect)
                    $dbgstr.= "main-Table ";
                $dbgstr.= get_class($this)."/";
                $tableName= $this->getName();
                $displayName= $this->getDisplayName();
                $dbgstr.= "<b>".$tableName."(</b>".$displayName."<b>)</b>";
                $dbgstr.= " from container:<b>".$this->container->getName()."</b>";
                if(STCheck::isDebug("db.statements.select"))
                {
                    $dbgstr.= " for columns:";
                    $space= STCheck::echoDebug("db.statements.select", $dbgstr);
                    st_print_r($aNeededColumns, 2,$space);
                }else
                    $space= STCheck::echoDebug("db.statements.aliases", $dbgstr);
                STCheck::echoDebug($debugSess, "defined alias from follow list:");
                st_print_r($aTableAlias, 2, $space);
            }
        }
        $aShowTypes= $this->showTypes;
        /*if(typeof($this, "STDbSelector"))
         $isSelector= true;					// alex 24/05/2005:	$isSelector eliminiert,
         else									//					da sie ohnehin nur einmal gebraucht wird
         $isSelector= false;*/				//					und jetzt sowieso �ber den 3. Parameter
        //					abgefragt wird
        $aliasCount= count($aTableAlias);
        foreach($aNeededColumns as $column)
        {// durchlaufe das Array mit allen benoetigten Columns
            
            $columnName= $column["column"];
            if(preg_match("/(.+)\\((.+)\\)/", $columnName, $preg))
            {
                if($preg[2] != "*")
                    $columnName= $preg[1]."(`".$preg[2]."`)";
            }else
                $columnName= "`$columnName`";
            if(isset($column["alias"]))
                $columnAlias= "'".$column["alias"]."'";
            else
                $columnAlias= "'".$column["column"]."'";
            if(STCheck::isDebug())
            {
                $msg= "select ";
                if(isset($column["column"]))
                {
                    $sColumn= $columnName;
                    $msg.= "column <b>$sColumn</b>";
                }else
                {
                    $sColumn= "<b>Undefined Column</b>";
                    $msg.= $sColumn;
                }
                $msg.= " as ";
                if(isset($column["alias"]))
                    $msg.= $column["alias"];
                else
                    $msg.= $sColumn;
                STCheck::echoDebug("db.statements.select", $msg);
            }
            
            if($aliasCount>1)
            {		// wenn die Tabelle null ist, gibts sie nicht im FK
                $fkTableName= $this->getFkTableName($column["column"]);
                if(STCheck::isDebug() && $fkTableName)
                    STCheck::echoDebug("db.statements.select", "from ".get_class($this)." ".$this->getName()." for column ".$column["column"]." is Fk-Table \"".$fkTableName."\"");
                if(	$fkTableName
                    and
                    typeof($oMainTable, "STDbSelector")	)
                {
                    // alex 24/05/2005:	if table is an existing foreign Key
                    //                  and the current/main table an STDbSelector,
                    //                  than check whether a table version exist in the selector.
                    //					Otherwise it shouldn't made any output from the linked table.
                    if(!isset($oMainTable->aoToTables[$fkTableName]))
                        $fkTableName= null;
                }
                if(	!$fkTableName // wenn keine Tabelle im FK ist kann die Spalte nur von der Aktuellen-Tabelle sein
                    or
                    //$isSelector
                    //or
                    isset($aShowTypes[$column["alias"]]))
                {
                    $aliasTable= $aTableAlias[$column["table"]];
                    if(	!$bFirstSelect &&
                        $aliasTable=="t1"	)
                    {
                        $aliasTable= "t".($aliasCount+1);
                        $aTableAlias["self.".$column["table"]]= $aliasTable;
                    }
                    $this->sNeedAlias[$aliasTable]= "need";// Tabellen Alias wird gebraucht
                    $aUseAliases[$aTableAlias[$column['table']]]= $column['table'];
                    $singleStatement.= $columnName;
                    if($this->sqlKeyword($column["column"]) != false)
                        $statement.= $columnName;
                    else
                        $statement.= "`".$aliasTable."`.$columnName";
                    if(	isset($column["alias"])
                        and
                        $column["column"]!=$column["alias"])
                    {
                        $statement.= " as $columnAlias";
                        $singleStatement.= " as $columnAlias";
                    }
                    if(Tag::isDebug())
                    {
                        $debugString=  "<b>insert column</b> ".$aliasTable;
                        $debugString.= ".".$column["column"];
                        if(	isset($column["alias"]) )
                            $debugString.= " as ".$column["alias"];
                            Tag::echoDebug("db.statements.select", $debugString);
                    }
                }else
                {
                    STCheck::echoDebug("db.statements.select", "");
                    STCheck::echoDebug("db.statements.select", "need column from foreign table");
                    //$oOther= $this->FK[$fkTableName]["table"]->container->getTable;
                    $containerName= $this->getFkContainerName($column["column"]);
                    //$container= &STBaseContainer::getContainer($containerName);
                    // 13/06/2008:	alex
                    //				fetch table from own table because
                    //				if own table is an DBSelector maybe only
                    //				in him can be deleted an column or identif-column
                    $oOther= $this->getTable($fkTableName, $containerName);
                    $allAliases= $aTableAlias;
                    $withAlias= true;
                    $fkStatement= $oOther->getSelectStatement(/*firstSelect*/false, $oMainTable, $allAliases, $withAlias);
                    //create new using alaises
                    foreach($allAliases as $tabName=>$a)
                        $aUseAliases[$aTableAlias[$tabName]]= $tabName;
                    Tag::echoDebug("db.statements.select", "FK String is \"".$fkStatement."\"");
                    if(!$fkStatement)
                    {// is no columns selected in the FK-table
                        // delete the last comma
                        $statement= substr($statement, 0, strlen($statement)-1);
                        $singleStatement= substr($singleStatement, 0, strlen($singleStatement)-1);
                    }else
                    {
                        $statement.= $fkStatement;
                        $singleStatement.= $fkStatement;
                    }
                    Tag::echoDebug("db.statements.select", "back in Table <b>".$this->getName()."</b>");
                }
            }else
            {
                $statement.= $columnName;
                $singleStatement.= $columnName;
                if(	isset($column["alias"])
                    and
                    $column["column"]!=$column["alias"])
                {
                    $statement.= " as $columnAlias";
                    $singleStatement.= " as $columnAlias";
                }
            }
            Tag::echoDebug("db.statements.select", "String is \"".$statement."\"");
            Tag::echoDebug("db.statements.select", "String is \"".$singleStatement."\"");
            Tag::echoDebug("db.statements.select", "last char is \"".substr($statement, strlen($statement), -1)."\"");
            if( $statement &&
                substr($statement, strlen($statement), -1) != ','   )
            {
                $statement.= ",";
                $singleStatement.= ",";
            }
            if(STCheck::isDebug("db.statements.aliases"))
            {
                $space= STCheck::echoDebug("db.statements.aliases", "need now follow alias tables:");
                st_print_r($aUseAliases, 2, $space);
            }
        }
        $whereClause= $this->getWhere();
        if( isset($whereClause) &&
            isset($whereClause->aValues)  )
        {
            foreach($whereClause->aValues as $tableName=>$content)
            {
                if( $tableName != "and" &&
                    $tableName != "or"      )
                {
                    $tabName= $this->db->getTableName($tableName);// search for original table name
                    $aUseAliases[$aTableAlias[$tabName]]= $tabName;
                }
            }
        }
        foreach($aTableAlias as $tableName=>$alias)
        {
            if(!in_array($tableName, $aUseAliases))
                unset($aTableAlias[$tableName]);
        }
        if( STCheck::isDebug("db.statements.aliases") )
        {
            $space= STCheck::echoDebug("db.statements.aliases", "need table aliases:");
            st_print_r($aTableAlias, 2, $space);
            //STCheck::echoDebug("user", "all aliases wich need for joins between tables:");
            //st_print_r($aUseAliases, 2, $space+40);
            if( typeof($this, "STDbSelector") &&
                $this->Name == "MUProject"        )
            {
                STCheck::echoDebug("user", "where clause need for select statement");
                st_print_r($whereClause,10, $space);
            }
        }
        if($withAlias == false)
            $statement= $singleStatement;
            $statement= substr($statement, 0, strlen($statement)-1);
            Tag::echoDebug("db.statements.select", "createt String is \"".$statement."\"");
            return $statement;
	}
	// alex 19/04/2005:	alle links in eine Funktion zusammengezogen
	//					und $address darf auch ein STDbTable,
	// 					f�r die Verlinkung auf eine neue Tabelle, sein
	protected function linkA(string $which, string $aliasColumn, $address= null, string $valueColumn= null)
	{
	    if(STCheck::isDebug())
	    {
    	    STCheck::param($which, 0, "string");
    	    STCheck::param($aliasColumn, 1, "string");
    	    STCheck::param($address, 2, "STBaseContainer", "STBaseTable", "string", "null");
    	    STCheck::param($valueColumn, 3, "string", "null");
	    }
	    
	    $tableName= "";
		if(typeof($address, "STDbTable"))
		{// ist die Addresse ein STDbTable
		 // diesen abfangen und in einen Container verpacken
			$tableName= $address->getName();
			
		}elseif($address != null &&
		        !typeof($address, "STBaseContainer")  )
		{// wenn nicht k�nnte die Addresse noch der Name einer
		 // Tabelle sein
		    $tables= $this->db->list_tables();
		    $address= STDbTableDescriptions::instance($this->db->getDatabaseName())->getTableName($address);
    		foreach($tables as $tabName)
    		{// schau ob die Adresse eine Tabelle ist
    			if($address==$tabName)
    			{
					$tableName= $tabName;
    				break;
    			}
    		}
		}
		if($tableName != "")
		{
			$newContainerName= "dbtable ".$tableName;
			$address= new STDbTableContainer($newContainerName, $this->db);
			$address->needTable($tableName);
		}
		STBaseTable::linkA($which, $aliasColumn, $address, $valueColumn);
	}
}

?>