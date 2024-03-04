<?php

class STDbTableDescriptions
{
    protected $db;
    protected $dbName;
    protected $aExistTables= array();
    protected $asTableColumns= array();
	/**
	 * Whether has database only lower case table names."<br />
	 * If set to 0, table names are stored as specified and comparisons are case-sensitive.
	 * If set to 1, table names are stored in lowercase on disk and comparisons are not case-sensitive.
	 * If set to 2, table names are stored as given but compared in lowercase. 
	 * This option also applies to database names and table aliases.
	 * @var int
	 */
	private $nLowerCaseTableNames= null;

	protected function __construct(object &$db)
	{
	    $this->db= &$db;
	    $this->dbName= $db->getDatabaseName();
		$this->nLowerCaseTableNames= $db->hasLowerCaseTableNames();
	}
	/**
	 * create or fetch an table-description instance
	 * of STDbTableDescription
	 *
	 * @param STDatabase db database object where are the original tables
	 * @return STDbTableDescriptions instance of STDbTableDescription
	 */
	public static function &init($db)
	{
		global	$global_sttabledescriptions_class_instance;
	    
	    STCheck::param($db, 0, "STDatabase");
	    
	    $dbName= $db->getDatabaseName();
	    if(!isset($global_sttabledescriptions_class_instance[$dbName]))
	    {
	        STCheck::echoDebug("db.descriptions", "create table descriptions for database $dbName");
	        $global_sttabledescriptions_class_instance[$dbName]= new STDbTableDescriptions($db);
	    }
	    return $global_sttabledescriptions_class_instance[$dbName];
	}
	/**
	 * fetch an table-description instance
	 * of STDbTableDescription
	 *
	 * @param string dbName name of database in which the original tables are stored
	 * @return STDbTableDescriptions instance of STDbTableDescription
	 */
	public static function &instance($dbName)
	{
		global	$global_sttabledescriptions_class_instance;

		STCheck::param($dbName, 0, "string");
		
		if(isset($global_sttabledescriptions_class_instance[$dbName]))
		    return $global_sttabledescriptions_class_instance[$dbName];
		
		if(STCheck::isDebug())
		    showBackTrace();
		STCheck::alert(true, "STDbTableDescription::instance()", "no instance of <b>$dbName</b> STDbTableDescription be created");
		return null;
	}
	public function getDatabaseName()
	{
		return $this->dbName;
	}
	/*public*/function getOrgTableName(string $name)
	{
		if($this->nLowerCaseTableNames == 1)
			$name= strtolower($name);
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
	/*public*/function getTableName(string $name)
	{
		STCheck::param($name, 0, "string");
		if($this->nLowerCaseTableNames == 1)
			$name= strtolower($name);
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
	 * @param string tableName name of the table which should be in the database
	 * @return string name of the database which having the table
	 */
	public static function getDbNameOfTable($tableName)
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
	public function getColumnName(string $table, string $column, int $warnFuncOutput= 0)
	{
	    //echo __FILE__.__LINE__."<br>";
	    //echo "STDbTableDescription::getColumnName($table, $column)<br>";
	    //st_print_r($this->asTableColumns[$orgTable], 3);
		$dbKeyword= $this->db->keyword($column);
		if($dbKeyword)
		{
		    if( count($dbKeyword['content']) == 1 &&
		        trim($dbKeyword['content'][0]) == "*"    )
		    {// if column is a keyword with joker for all columns return incomming column
		        return $column;
		    }
		    $sRv= $dbKeyword['keyword']."(";
		    foreach($dbKeyword['content'] as $column)
		        $sRv.= $this->getColumnName($table, $column).",";
		    $sRv= substr($sRv, 0, strlen($sRv)-1).")";
		    return $sRv;
		}
		$orgTable= $this->getOrgTableName($table);
		if(!isset($this->asTableColumns[$orgTable]))
			STCheck::echoDebug("description.tables.warning", "found no original table for name '$orgTable' " .
					"inside defined table descriptions, so take asked column name '$column'");
		elseif(!isset($this->asTableColumns[$orgTable][$column]))			
		{
			if(STCheck::isDebug())
			{
				$bfound= false;
				
				foreach($this->asTableColumns[$orgTable] as $array)
				{
				    //echo "found ".$array["column"]."<br>";
					if($array["column"] == $column)
					{
						$bfound= true;
						break;
					}
				}
				if( !$bfound &&
				    isset($this->db->oGetTables[ strtolower($table) ])  )
				{
				    $columns= $this->db->oGetTables[ strtolower($table) ]->columns;
				    foreach($columns as $orgColumn)
				    {
				        if($column == $orgColumn['name'])
				        {
				            $bfound= true;
				            break;
				        }
				    }
				}
				if(!$bfound)
				{
				    if($warnFuncOutput>-1)
				    {
				        $warnFuncOutput++;				    
				        STCheck::is_warning(true, "STDbTableDescriptor::getColumnName()", "cannot find column '$column' inside any pre-defined TableDescriptions or inside database as table $orgTable", $warnFuncOutput);
				    }
				}else
					STCheck::echoDebug("description.tables.ok", "asked column <b>$column</b> be the same as in database");
			}
		}else
		{
			if(STCheck::isDebug())
				STCheck::echoDebug("description.tables.ok", "found selected table-name <b>$column</b> as <b>".$this->asTableColumns[$orgTable][$column]["column"]."</b>");
			$column= $this->asTableColumns[$orgTable][$column]["column"];	
		}
		//echo "for original table $orgTable and column $column<br>";
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
	/*protected*/function table(string $tableName)
	{
	    STCheck::echoDebug("db.descriptions", "  define description for table '$tableName' inside '".$this->dbName."' database");
		if($this->nLowerCaseTableNames == 1)
			$tableName= strtolower($tableName);
	    $this->aExistTables[$tableName]= array(	"table"=>$tableName,
												"installed"=>false	);
	}
	public function setPrefixToTable(string $prefix, string $tableName)
	{
		if($this->nLowerCaseTableNames == 1)
		{
			$tableName= strtolower($tableName);
			$prefix= strtolower($prefix);
		}
	    STCheck::echoDebug("db.descriptions", "  set prefix '$prefix' for table '$tableName' inside '".$this->dbName."' database");
	    STCheck::is_alert(!isset($this->aExistTables[$tableName]), "STDbTableDescription::setPrefixToTable()", "table name '$tableName' does not exist");
	    	    
	    if( !isset($this->aExistTables[$tableName]['addPrefix']) ||
	        $this->aExistTables[$tableName]['addPrefix'] == true   )
	    {
    	    $old= $this->aExistTables[$tableName]["table"];
    		$this->aExistTables[$tableName]["table"]= $prefix.$old;
	    }
	}
	public function setPrefixToTables(string $prefix)
	{
		foreach($this->aExistTables as $name=>$table)
		    $this->setPrefixToTable($prefix, $name);
	}
	/*protected*/function column(string $tableName, string $column, string $type, bool $null= true)
	{
		if($this->nLowerCaseTableNames == 1)
			$tableName= strtolower($tableName);
	    STCheck::is_warning(!$this->aExistTables[$tableName], "STDbTableContainer::column()", "table $tableName does not exist");
		$params= func_num_args();
		STCheck::lastParam(4, $params);

		$type= strtoupper($type);
	    $this->asTableColumns[$tableName][$column]= array(	"column"=>	$column,
															"type"=>	  $type,
															"null"=>		$null	);
	}
	/*public*/function notNull(string $tableName, string $column)
	{
		if($this->nLowerCaseTableNames == 1)
			$tableName= strtolower($tableName);
		STCheck::is_warning(!$this->aExistTables[$tableName], "STDbTableContainer::notNull()", "table $tableName does not exist");
		Tag::alert(!$this->asTableColumns[$tableName][$column], "STDbTableContainer::notNull()", "column $column does not exist in table $tableName");

		$this->asTableColumns[$tableName][$column]["null"]= false;
	}
	/*public*/function primaryKey(string $tableName, string $column, bool $pk= true)
	{
		if($this->nLowerCaseTableNames == 1)
			$tableName= strtolower($tableName);
		STCheck::is_warning(!$this->aExistTables[$tableName], "STDbTableContainer::primaryKey()", "table $tableName does not exist");
		Tag::alert(!$this->asTableColumns[$tableName][$column], "STDbTableContainer::primaryKey()", "column $column does not exist in table $tableName");

		$this->asTableColumns[$tableName][$column]["pk"]= $pk;
	}
	/*public*/function foreignKey(string $tableName, string $column, string $toTable, string|int $identif= 1,
										string|int $toColumn= null, string $type= "RESTRICT")
	{
		if($this->nLowerCaseTableNames == 1)
		{
			$tableName= strtolower($tableName);
			$toTable= strtolower($toTable);
		}
		STCheck::is_warning(!$this->aExistTables[$tableName], "STDbTableContainer::foreignKey()", "table $tableName does not exist");
		Tag::alert(!$this->asTableColumns[$tableName][$column], "STDbTableContainer::foreignKey()", "column $column does not exist in table $tableName");

		$type= strtoupper($type);
		$this->asTableColumns[$tableName][$column]["fk"]= array("column"	=>$toColumn,
																"table"		=>$toTable,
																"identif"	=>$identif,
																"type"		=>$type		);
	}
	function getForeignKeys(string $tableName)
	{
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
	/*public*/function uniqueKey(string $tableName, string $column, $unique, $uniqueLength= null)
	{
		if($this->nLowerCaseTableNames == 1)
			$tableName= strtolower($tableName);
		STCheck::paramCheck($unique, 3, "string", "int");
		STCheck::paramCheck($uniqueLength, 4, "null", "int");
		STCheck::is_warning(!$this->aExistTables[$tableName], "STDbTableContainer::setInTableColumnNotNull()", "table $tableName does not exist");
		Tag::alert(!$this->asTableColumns[$tableName][$column], "STDbTableContainer::setInTableColumnNotNull()", "column $column does not exist in table $tableName");
		STCheck::is_warning(	(	$this->asTableColumns[$tableName][$column]["type"]==="TEXT"
								or
								$this->asTableColumns[$tableName][$column]["type"]==="BLOB"	)
							and
							!$uniqueLength														, "STTableDescription::uniqueKey()",
										"BLOB column '".$column."' used in key specification without a key length"								);

		$this->asTableColumns[$tableName][$column]["udx"]["name"]= $unique;
		$this->asTableColumns[$tableName][$column]["udx"]["length"]= $uniqueLength;
	}
	/*public*/function indexKey(string $tableName, string $column, $index, $indexLength= null)
	{
		if($this->nLowerCaseTableNames == 1)
			$tableName= strtolower($tableName);
		STCheck::paramCheck($index, 3, "string", "int");
		STCheck::paramCheck($indexLength, 4, "null", "int");
		STCheck::is_warning(!$this->aExistTables[$tableName], "STDbTableContainer::setInTableColumnNotNull()", "table $tableName does not exist");
		Tag::alert(!$this->asTableColumns[$tableName][$column], "STDbTableContainer::setInTableColumnNotNull()", "column $column does not exist in table $tableName");

		$this->asTableColumns[$tableName][$column]["idx"]["name"]= $index;
		$this->asTableColumns[$tableName][$column]["idx"]["length"]= $indexLength;
	}
	public function autoIncrement(string $tableName, string $column)
	{
		if($this->nLowerCaseTableNames == 1)
			$tableName= strtolower($tableName);
		STCheck::is_warning(!$this->aExistTables[$tableName], "STDbTableContainer::setInTableColumnNotNull()", "table $tableName does not exist");
		Tag::alert(!$this->asTableColumns[$tableName][$column], "STDbTableContainer::setInTableColumnNotNull()", "column $column does not exist in table $tableName");

		$this->asTableColumns[$tableName][$column]["auto_increment"]= true;
	}
	/**
	 * define new table name for an existing container where tables
	 * defined inside database
	 * 
	 * @param string $defined defined table name in previous container
	 * @param string $tableName new name of table
	 * @param boolean $addPrefix whether prefix definition should also generated on this new table (default: false)
	 */
	public function updateTable(string $defined, string $tableName, bool $addPrefix= false)
	{
	    STCheck::is_error(!$this->aExistTables[$defined], "STDbTableContainer::updateTable()", "table $defined does not exist");

		if($this->nLowerCaseTableNames == 1)
			$tableName= strtolower($tableName);
	    $this->aExistTables[$defined]["table"]= $tableName;
	    $this->aExistTables[$defined]['addPrefix']= $addPrefix;
	}
	/**
	 * define new column name inside a table existing in a container
	 * where tables defined inside database
	 * 
	 * @param string $tableName defined table name in previous container
	 * @param string $definedColumn defined column name in previous container
	 * @param string $column new column name
	 */
	public function updateColumn(string $tableName, string $definedColumn, string $column)
	{
		if($this->nLowerCaseTableNames == 1)
			$tableName= strtolower($tableName);
	    STCheck::is_error(!isset($this->aExistTables[$tableName]), "STDbTableContainer::updateColumn()", "table $tableName does not exist");
	    STCheck::is_error(!isset($this->asTableColumns[$tableName][$definedColumn]), "STDbTableContainer::updateColumn()", "column $definedColumn does not exist in table $tableName");

	    $this->asTableColumns[$tableName][$definedColumn]["column"]= $column;
	}
	function installTables(&$database)
	{
		Tag::paramCheck($database, 1, "STDatabase");

		if(!$this->aExistTables)
			return;
		if($database->requiredVersion("4.0.7"))
		    $bCreateFks= true;
		else
		    $bCreateFks= false;
	    foreach($this->aExistTables as $table=>$defined)
		{
			if(!$defined["installed"])
			{
			    foreach($this->asTableColumns[$table] as $column)
			    {
			        if(isset($column['fk']['table']))
			        {
			            $fkTable= $column['fk']['table'];
			            $ownTableName= $this->aExistTables[$table]['table'];
			            $trigger= !isset($this->aExistTables[$fkTable]);
			            $firstMessage= "table $ownTableName should point to table ";
			            $lastMessage= ", inside method ::defineDatabaseTableDescriptions() of container";
			            $message= "$firstMessage $fkTable, but no table with this name defined for creation";
			            $message.= $lastMessage;
		                STCheck::alert($trigger, "STDbTableDescription::installTable()", $message);
		                $trigger= ( !isset($this->aExistTables[$fkTable]['installed']) || 
		                              $this->aExistTables[$fkTable]['installed'] == false );
		                $foreignTableName= $this->aExistTables[$fkTable]['table'];
		                $message= "$firstMessage $foreignTableName, define this foreign table before $ownTableName for creation";
		                $message.= $lastMessage;
		                STCheck::alert($trigger, "STDbTableDescription::installTable()", $message);
			        }
			    }
	        	$oTable= new STDbTableCreator($database, $defined["table"]);
	        	$oTable->check();
	        	//echo __FILE__.__LINE__."<br>";
				//st_print_r($this->asTableColumns[$table],10);
				//echo "for table $table:";
				foreach($this->asTableColumns[$table] as $content)
				{
				    $oTable->column($content["column"], $content["type"], $content["null"]);
				    if(isset($content["auto_increment"]))
						$oTable->autoIncrement($content["column"]);
					if(isset($content["idx"]))
						$oTable->indexKey($content["column"], $content["idx"]["name"], $content["idx"]["length"]);
					if(isset($content["udx"]))
						$oTable->uniqueKey($content["column"], $content["udx"]["name"], $content["udx"]["length"]);
					if(isset($content["pk"]))
						$oTable->primaryKey($content["column"]);
					if(isset($content["fk"]))
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