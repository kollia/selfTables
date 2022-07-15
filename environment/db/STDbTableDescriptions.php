<?php

class STDbTableDescriptions
{
	var	$aExistTables= array();
	var	$asTableColumns= array();

	/**
	 * create or fetch an table-description instance
	 * of STDbTableDescription
	 *
	 * @param string dbName name of database in which the tables should be
	 * @return STDbTableDescriptions instance of STDbTableDescription
	 */
	public static function &instance($dbName)
	{
		global	$global_sttabledescriptions_class_instance;

		STCheck::param($dbName, 0, "string");
		
		if(!isset($global_sttabledescriptions_class_instance[$dbName]))
		{
		    //echo __FILE__.__LINE__."<br>";
		    //echo "create new STDbTableDescription()<br>";
			$global_sttabledescriptions_class_instance[$dbName]= new STDbTableDescriptions();
		}
		return $global_sttabledescriptions_class_instance[$dbName];
	}
	public static function getDatabaseName()
	{
		global	$global_sttabledescriptions_class_instance;
		
		if(	!is_array($global_sttabledescriptions_class_instance) ||
			count($global_sttabledescriptions_class_instance) == 0	)
		{
			STCheck::alert("(global selfTable database = NULL) no database be defined");
			exit;
		}
		if(count($global_sttabledescriptions_class_instance) > 0)
		{
			STCheck::warning("more than one database be defined -> take first");
		}
		$dbName= rewind($global_sttabledescriptions_class_instance);
		return $dbName;
	}
	/*public*/function getOrgTableName($name)
	{
		if(isset($this->aExistTables[$name]))
			return $name;
		$lowerCase= strtolower($name);
		foreach($this->aExistTables as $orgName=>$content)
		{
			if($lowerCase===strtolower($content["table"]))
			{
				$name= $orgName;
				break;
			}
		}
		return $name;
	}
	/*public*/function getTableName($name)
	{
		STCheck::param($name, 0, "string");
		
		if(isset($this->aExistTables))
		{
			if(isset($this->aExistTables[$name]))
			{
				$name= $this->aExistTables[$name]["table"];
			}else
			{
				if($name===strtolower($name))
				{// incomming name is only in lower case
				 // so ask for orginal tablename
				 // (getOrgTableName looks only for lower case)
				 	$orgName= $this->getOrgTableName($name);
					if(	isset($this->aExistTable) &&
						isset($this->aExistTables[$orgName])	)
					{	
						$name= $this->aExistTables[$orgName]["table"];
					}
				}
			}
		}
		return $name;
	}
	/**
	 * returns the database name in which the table is given.<br />
	 * If the table is present in more than one defined db,
	 * it will be returning the name of the first db.
	 *
	 * @public
	 * @static
	 * @param string tableName name of the table which should be in the database
	 * @return string name of the database which having the table
	 */
	function getDbNameOfTable($tableName)
	{
		STCheck::param($tableName, 0, "string");

		global $global_sttabledescriptions_class_instance;

		reset($global_sttabledescriptions_class_instance);
		{
			$dbName= key($global_sttabledescriptions_class_instance);
			if($global_sttabledescriptions_class_instance[$dbName]->aExistTables)
				return $dbName;
		}
		while(next($global_sttabledescriptions_class_instance));
		return NULL;
	}
	/*public*/function getColumnName($table, $column)
	{
		STCheck::param($table, 0, "string");
		STCheck::param($column, 1, "string");

		//echo "STDbTableDescription::getColumnName($table, $column)------------------<br>";
		//st_print_r($this->asTableColumns);
		$table= $this->getOrgTableName($table);
		if(!isset($this->asTableColumns[$table]))
			STCheck::echoDebug("description.tables.warning", "found no original table for table '$table' " .
					"inside defined table descriptions, so take asked column name '$column'");
		elseif(!isset($this->asTableColumns[$table][$column]))			
		{
			if(STCheck::isDebug())
			{
				$bfound= false;
				foreach($this->asTableColumns[$table] as $array)
				{
					if($array["column"] == $column)
					{
						$bfound= true;
						break;
					}
				}
				if($bfound)
					STCheck::echoDebug("description.tables.ok", "asked column <b>$column</b> be the same as in database");
				else
					STCheck::echoWarning("description.tables.warning", "cannot find asked column as alias or original name in database");
			}
		}else
		{
			if(STCheck::isDebug())
				STCheck::echoDebug("description.tables.ok", "found selected table-name <b>$column</b> as <b>".$this->asTableColumns[$table][$column]["column"]."</b>");
			$column= $this->asTableColumns[$table][$column]["column"];
		}
		//echo "for original table $table and column $column<br>";
		return $column;
	}
	/*public*/function getColumnContent($table, $column)
	{
		$aRv= null;
		$table= $this->getOrgTableName($table);
		if($this->asTableColumns[$table][$column])
			$aRv= $this->asTableColumns[$table][$column];
		return $aRv;
	}
	/*public*/function getPkColumnName($table)
	{
		$table= $this->getOrgTableName($table);
		$pk= null;
		if(is_array($this->asTableColumns[$table]))
		{
			foreach($this->asTableColumns[$table] as $column=>$content)
			{
				if($content["pk"])
				{
					$pk= $column;
					break;
				}
			}
		}
		return $pk;
	}
	/*protected*/function table($tableName)
	{
	    //if($displayName===null)
		//	    $displayName= $tableName;
	    $this->aExistTables[$tableName]= array(	"table"=>$tableName,
												"installed"=>false	);
	}
	/*public*/function setPrefixToTable($prefix, $tableName)
	{
		$this->aExistTables[$tableName]["table"]= $prefix.$tableName;
	}
	/*public*/function setPrefixToTables($prefix)
	{
		foreach($this->aExistTables as $name=>$table)
			$this->setPrefixToTable($prefix, $name);
	}
	/*protected*/function column($tableName, $column, $type, $null= true)
	{
		STCheck::paramCheck($tableName, 1, "string");
		STCheck::paramCheck($column, 2, "string");
		STCheck::paramCheck($type, 3, "string");
		STCheck::paramCheck($null, 4, "bool");
	    Tag::warning(!$this->aExistTables[$tableName], "STDbTableContainer::setColumnInTable()", "table $tableName does not exist");
		$params= func_num_args();
		STCheck::lastParam(4, $params);

		$type= strtoupper($type);
	    $this->asTableColumns[$tableName][$column]= array(	"column"=>	$column,
															"type"=>	  $type,
															"null"=>		$null	);
	}
	/*public*/function notNull($tableName, $column)
	{
		Tag::warning(!$this->aExistTables[$tableName], "STDbTableContainer::setInTableColumnNotNull()", "table $tableName does not exist");
		Tag::alert(!$this->asTableColumns[$tableName][$column], "STDbTableContainer::setInTableColumnNotNull()", "column $column does not exist in table $tableName");

		$this->asTableColumns[$tableName][$column]["null"]= false;
	}
	/*public*/function primaryKey($tableName, $column, $pk= true)
	{
		Tag::warning(!$this->aExistTables[$tableName], "STDbTableContainer::setInTableColumnNotNull()", "table $tableName does not exist");
		Tag::alert(!$this->asTableColumns[$tableName][$column], "STDbTableContainer::setInTableColumnNotNull()", "column $column does not exist in table $tableName");

		$this->asTableColumns[$tableName][$column]["pk"]= $pk;
	}
	/*public*/function foreignKey($tableName, $column, $toTable, $identif= 1, $toColumn= null, $type= "RESTRICT")
	{
		STCheck::paramCheck($tableName, 1, "string");
		STCheck::paramCheck($column, 2, "string");
		STCheck::paramCheck($toTable, 3, "string");
		STCheck::paramCheck($identif, 4, "string", "int");
		STCheck::paramCheck($toColumn, 5, "string", "null");
		STCheck::paramCheck($type, 6, "string");
		Tag::warning(!$this->aExistTables[$tableName], "STDbTableContainer::setInTableColumnNotNull()", "table $tableName does not exist");
		Tag::alert(!$this->asTableColumns[$tableName][$column], "STDbTableContainer::setInTableColumnNotNull()", "column $column does not exist in table $tableName");

		$type= strtoupper($type);
		$this->asTableColumns[$tableName][$column]["fk"]= array("column"	=>$toColumn,
																"table"		=>$toTable,
																"identif"	=>$identif,
																"type"		=>$type		);
	}
	function getForeignKeys($tableName)
	{
		STCheck::param($tableName, 0, "string");

		$aRv= array();
		$tableName= $this->getOrgTableName($tableName);
		if(	isset($this->aExistTables[$tableName]) &&
			$this->aExistTables[$tableName]	&&
		    isset($this->asTableColumns[$tableName])  )
		{
			foreach($this->asTableColumns[$tableName] as $name=>$column)
			{
				if(	is_array($column)
					and
					isset($column["fk"])	)
				{
					$other= $column["fk"]["column"];
					if($other!=null)
						$other= $this->getColumnName($tableName, $column["fk"]["column"]);
					else
						$other= null;

					$aRv[]= array(	"own"=>		$this->getColumnName($tableName, $name),
									"other"=>	$other,
									"table"=>	$this->getTableName($column["fk"]["table"])					);
				}
			}
		}
		return $aRv;
	}
	/*public*/function uniqueKey($tableName, $column, $unique, $uniqueLength= null)
	{
		STCheck::paramCheck($tableName, 1, "string");
		STCheck::paramCheck($column, 2, "string");
		STCheck::paramCheck($unique, 3, "string", "int");
		STCheck::paramCheck($uniqueLength, 4, "null", "int");
		Tag::warning(!$this->aExistTables[$tableName], "STDbTableContainer::setInTableColumnNotNull()", "table $tableName does not exist");
		Tag::alert(!$this->asTableColumns[$tableName][$column], "STDbTableContainer::setInTableColumnNotNull()", "column $column does not exist in table $tableName");
		STCheck::warning(	(	$this->asTableColumns[$tableName][$column]["type"]==="TEXT"
								or
								$this->asTableColumns[$tableName][$column]["type"]==="BLOB"	)
							and
							!$uniqueLength														, "STTableDescription::uniqueKey()",
										"BLOB column '".$column."' used in key specification without a key length"								);

		$this->asTableColumns[$tableName][$column]["udx"]["name"]= $unique;
		$this->asTableColumns[$tableName][$column]["udx"]["length"]= $uniqueLength;
	}
	/*public*/function indexKey($tableName, $column, $index, $indexLength= null)
	{
		STCheck::paramCheck($tableName, 1, "string");
		STCheck::paramCheck($column, 2, "string");
		STCheck::paramCheck($index, 3, "string", "int");
		STCheck::paramCheck($indexLength, 4, "null", "int");
		Tag::warning(!$this->aExistTables[$tableName], "STDbTableContainer::setInTableColumnNotNull()", "table $tableName does not exist");
		Tag::alert(!$this->asTableColumns[$tableName][$column], "STDbTableContainer::setInTableColumnNotNull()", "column $column does not exist in table $tableName");

		$this->asTableColumns[$tableName][$column]["idx"]["name"]= $index;
		$this->asTableColumns[$tableName][$column]["idx"]["length"]= $indexLength;
	}
	/*public*/function autoIncrement($tableName, $column)
	{
		Tag::warning(!$this->aExistTables[$tableName], "STDbTableContainer::setInTableColumnNotNull()", "table $tableName does not exist");
		Tag::alert(!$this->asTableColumns[$tableName][$column], "STDbTableContainer::setInTableColumnNotNull()", "column $column does not exist in table $tableName");

		$this->asTableColumns[$tableName][$column]["auto_increment"]= true;
	}
	/*public*/function updateTable($defined, $tableName)
	{
	    Tag::error(!$this->aExistTables[$defined], "STDbTableContainer::updateTable()", "table $defined does not exist");

	    $this->aExistTables[$defined]["table"]= $tableName;
	}
	/*public*/function updateColumn($tableName, $definedColumn, $column)
	{
	    Tag::error(!$this->aExistTables[$tableName], "STDbTableContainer::updateColumn()", "table $tableName does not exist");
	    Tag::error(!$this->asTableColumns[$tableName][$definedColumn], "STDbTableContainer::updateColumn()", "column $definedColumn does not exist in table $tableName");

	    $this->asTableColumns[$tableName][$definedColumn]["column"]=	$column;
	}
	function installTables(&$database)
	{
		Tag::paramCheck($database, 1, "STDatabase");

		echo __FILE__.__LINE__."<br>";
		echo "STDbTableDescription::installTables();<br>";
		if(!$this->aExistTables)
			return;
		if(mysqlVersionNeed("4.0.7"))
			$bCreateFks= true;
		else
			$bCreateFks= false;
	    foreach($this->aExistTables as $table=>$defined)
		{
			if(!$defined["installed"])
			{
	        	$oTable= new STDbTableCreator($database, $defined["table"]);
				$oTable->check();
				//st_print_r($this->asTableColumns[$table],10);
				//echo "for table $table:";
				foreach($this->asTableColumns[$table] as $content)
				{//st_print_r($content,10);
				    $oTable->column($content["column"], $content["type"], $content["null"]);
					if($content["auto_increment"])
						$oTable->autoIncrement($content["column"]);
					if($content["idx"])
						$oTable->indexKey($content["column"], $content["idx"]["name"], $content["idx"]["length"]);
					if($content["udx"])
						$oTable->uniqueKey($content["column"], $content["udx"]["name"], $content["udx"]["length"]);
					if($content["pk"])
						$oTable->primaryKey($content["column"]);
					if($content["fk"])
						$oTable->foreignKey($content["column"], $content["fk"]["table"], $content["fk"]["identif"], $content["fk"]["column"], $content["fk"]["type"]);
				}
				$oTable->execute();
				$this->aExistTables[$table]["installed"]= true;
				Tag::echoDebug("install", "table ".$table." was installed");
			}
		}
	}
}

?>