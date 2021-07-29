<?php

require_once($_staliastable);

class STDbTable extends STAliasTable
{
	var	$db; // database
    var $container;
	var $aAuto_increment= array(); // um ein Feld mit Autoincrement vor dem eigentlichen Insert zu holen
	var	$password= array(); // all about to set an password in database

    function __construct($Table, $container= null, $onError= onErrorStop)
    {
		Tag::paramCheck($Table, 1, "string", "STAliasTable");
		Tag::paramCheck($container, 2, "STObjectContainer", "string", "null");

		if(typeof($Table, "STAliasTable"))
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
			$desc= &STDbTableDescriptions::instance($db->getName());
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
						"is an table-name(".$tableName."), or object from STAliasTable, ".
						"second parameter must be an object from STObjectContainer"			);
			if(typeof($Table, "string"))
				Tag::echoDebug("table", "create new object for table <b>".$Table."</b>");
			else
				Tag::echoDebug("table", "make copy from table-object <b>".$Table->Name."</b> for an new one");
		}
		$this->created[$tableName]= true;
		if(typeof($Table, "STAliasTable"))
			$this->copy($Table);
		else
			STAliasTable::STAliasTable($Table);
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
		if(typeof($Table, "STAliasTable"))
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
      			if(preg_match("/primary_key/", $field["flags"]))
      				$this->sPKColumn= $field["name"];
				if(preg_match("/multiple_key/", $field["flags"]))
				{
					$sql=  "select `REFERENCED_TABLE_SCHEMA`, `REFERENCED_TABLE_NAME`, `REFERENCED_COLUMN_NAME` ";
					$sql.= "FROM `information_schema`.`KEY_COLUMN_USAGE` ";
					$sql.= "WHERE `TABLE_SCHEMA`='".$this->db->getDatabaseName()."' ";
					$sql.= " and  `TABLE_NAME` = '$tableName'";
					$sql.= " and  `COLUMN_NAME`='".$field["name"]."'";
					$sql.= " and  `REFERENCED_COLUMN_NAME` is not NULL";
					$result= $this->db->fetch_row($sql);
					
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
		
		STAliasTable::copy($oTable);
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
	function select($column, $alias= null, $fillCallback= null, $nextLine= null)
	{
    	Tag::paramCheck($column, 1, "string");
		Tag::paramCheck($alias, 2, "string", "function", "TinyMCE", "bool", "null");
		Tag::paramCheck($fillCallback, 3, "function", "TinyMCE", "bool", "null");
		Tag::paramCheck($nextLine, 4, "bool", "null");
    	$nParams= func_num_args();
    	Tag::lastParam(4, $nParams);

    	$field= $this->findColumnOrAlias($column);
    	if($field["type"] == "not found")
    	{
			if(STCheck::isDebug())
			{
				STCheck::alert(!$this->columnExist($column), "STAliasTable::selectA()",
											"column '$column not exist in table ".$this->Name.
											"(".$this->getDisplayName().")");
			}else
				echo "column '$column not exist in table ".$this->Name.
											"(".$this->getDisplayName().")";
    	}
		STAliasTable::select($column, $alias, $fillCallback, $nextLine);
	}
    function passwordNames($firstName, $secondName, $thirdName= null)
    {
    	$this->password["names"][]= $firstName;
    	$this->password["names"][]= $secondName;
    	if($thirdName)
    		$this->password["names"][]= $thirdName;
    }
    function password($fieldName, $bEncode= true)
    {
    	$this->password["column"]= $fieldName;
    	$this->password["encode"]= $bEncode;
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
		Tag::alert(!$this->columnExist($column), "STAliasTable::addAccessClusterColumn()",
											"column $column not exist in table ".$this->Name.
											"(".$this->getDisplayName().")", 1);
		Tag::alert(!$this->columnExist($clusterfColumn), "STAliasTable::addAccessClusterColumn()",
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
		STCheck::paramCheck($parentTable, 5, "STAliasTable", "string", "boolean", "null");
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
		if($this->container->isAktContainer())	// if the container is not aktual
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
		STCheck::paramCheck($parentTable, 5, "STAliasTable", "string", "boolean", "null");
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
		if($this->container->isAktContainer())	// if the container is not aktual
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
		STCheck::paramCheck($parentTable, 5, "STAliasTable", "string", "boolean", "null");
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
		if($this->container->isAktContainer())	// if the container is not aktual
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
		STCheck::paramCheck($parentTable, 5, "STAliasTable", "string", "boolean", "null");
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
		if($this->container->isAktContainer())	// if the container is not aktual
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
		STCheck::paramCheck($parentTable, 5, "STAliasTable", "string", "boolean", "null");
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
		if($this->container->isAktContainer())	// if the container is not aktual
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
	function &getTable($sTableName)
	{
		return $this->container->getTable($sTableName);
	}
	function column($name, $type, $len= null)
	{
		$res= $this->getDbColumnTypeLen($name, $type, $len= null);
		STAliasTable::column($name, $res["type"], $res["length"]);
	}
	function dbColumn($name, $type, $len= null)
	{
		Tag::paramCheck($name, 1, "string");
		Tag::paramCheck($type, 2, "string");
		Tag::paramCheck($len, 3, "int", "null");

		$res= $this->getDbColumnTypeLen($name, $type, $len);
		STAliasTable::dbColumn($name, $res["type"], $res["length"]);
	}
	function getDbColumnTypeLen($name, $type, $len= null)
	{//echo "getDbColumnTypeLen($name, $type, $len= null)<br />";
	    $type= strtolower(trim($type));
      	Tag::alert($type=="string"&&$len===null, "STAliasTable::column()",
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
	/*protected*/function fk($ownColumn, &$toTable, $otherColumn= null, $bInnerJoin= null, $where= null)
	{
		Tag::paramCheck($ownColumn, 1, "string");
		Tag::paramCheck($toTable, 2, "STAliasTable", "string");
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
    		$toTable= &$this->db->getTable($toTableName);//, false/*bAllByNone*/);
			Tag::alert(!isset($toTable), "STDbTable::fk()", "second parameter '$toTableName' is no exist table");
    	}
		STAliasTable::fk($ownColumn, $toTable, $otherColumn, $bInnerJoin, $where);
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
			// in STAliasTable gesetzt wird
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
	function getStatement($bFromIdentifications= false, $withAlias= true)
	{
		if(STCheck::isDebug("db.statements.where"))
		{
			echo "STDbTable:<br>";
			st_print_r($this->oWhere);
		}
		return $this->db->getStatement($this, $bFromIdentifications, $withAlias);
	}
	// alex 19/04/2005:	alle links in eine Funktion zusammengezogen
	//					und $address darf auch ein STDbTable,
	// 					f�r die Verlinkung auf eine neue Tabelle, sein
	/*protected*/function linkA($which, $aliasColumn, $address, $valueColumn= null)
	{
		$tables= $this->db->list_tables();
		if(typeof($address, "STDbTable"))
		{// ist die Addresse ein STDbTable
		 // diesen abfangen und in einen Container verpacken
			$tableName= $address->getName();
		}else
		{// wenn nicht k�nnte die Addresse noch der Name einer
		 // Tabelle sein
			$bFound= false;
    		foreach($tables as $tableName)
    		{// schau ob die Adresse eine Tabelle ist
    			if($address==$tableName)
    			{
					$bFound= true;
    				break;
    			}
    		}
			if(!$bFound)
				$tableName= null;
		}
		if($tableName)
		{
			$newContainerName= "dbtable ".$tableName;
			$address= new STDbTableContainer($newContainerName, $this->db);
			$address->needTable($tableName);
		}
		STAliasTable::linkA($which, $aliasColumn, $address, $valueColumn);
	}
}

?>