<?php

class STTableDescriptions
{
	var	$aExistTables= array();
	var	$asTableColumns= array();
	
	/*public*/function &instance()
	{
		global	$global_sttabledescriptions_class_instance;
		
		if(!$global_sttabledescriptions_class_instance[0])
		{
			$global_sttabledescriptions_class_instance[0]= new STTableDescriptions();
		}
		return $global_sttabledescriptions_class_instance[0];
	}
	/*public*/function getOrgTableName($name)
	{
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
		if($this->aExistTables[$name])
			$name= $this->aExistTables[$name]["table"];
		else
		{
			if($name===strtolower($name))
			{// incomming name is only in lower case
			 // so ask for orginal tablename
			 // (getOrgTableName looks only for lower case)
			 	$orgName= $this->getOrgTableName($name);
				if($this->aExistTables[$orgName])
					$name= $this->aExistTables[$orgName]["table"];
			}
		}
		return $name;
	}
	/*public*/function getColumnName($table, $column)
	{
		$table= $this->getOrgTableName($table);
		if($this->asTableColumn[$table][$column])
			$column= $this->asTableColumn[$table][$column][$column];
		return $column;
	}
	/*public*/function getColumnContent($table, $column)
	{
		$aRv= null;
		$table= $this->getOrgTableName($table);
		if($this->asTableColumn[$table][$column])
			$aRv= $this->asTableColumn[$table][$column];
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