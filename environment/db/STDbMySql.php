<?php

//last change 23.11.2004
require_once($_stdatabase);	

	/**
	*	 class STDbMySql
	*	 	   zugriff auf Datenbank
	*
	* @Autor: Alexander Kolli
	* @version: 1.0
	*/
class STDbMySql extends STDatabase
{
	/**
	 * last call of msqli::query
	 */
	private $lastDbResult= NULL;

	/**
	 * field propertys from last result
	 */
	private $field_properties= NULL;

	/**
	 * all tables and fields 
	 * inside this database
	 */
	private $databaseTables= array();

	/**
	 * all type-name whitch column
	 * can have
	 */
	private $existTypes= array();
	private $columnTypes= array();

  /**
	*  Konstruktor für Zugriffs-deffinition
	*
	*/
	function __construct($identifName= "main-menue", $defaultTyp= MYSQL_NUM, $DBtype= "MYSQL")
   	{
		STDatabase::__construct($identifName, $defaultTyp, "MYSQL");
  	}
	/**
	*  Verbindungs-Aufbau zur Datenbank
	*
	*  @param string:$host hostname for connection
	*  @param string:$user username for connection
	*  @param string:$passwd password for connection
	*  @param string:$database database which should be used (optional)
	*/
	function connect($host= null, $user= null, $passwd= null, $database= null)
	{
   		$this->conn= new mysqli($host, $user, $passwd, $database);
		// alex 17/05/2005: obwohl referenze auf innere DB besteht
		//					wird die Connection dort nicht eingetragen.
		//					keine Ahnung warum !!!
		$this->db->conn= $this->conn;
  		if($this->conn->connect_errno)
  		{
			$this->error= true;
  			echo "can not make the connection to host <b>$host</b><br>";
  			echo "with user <b>$user</b><br>";
  			echo "<b>ERROR".$this->conn->connect_errno.": </b>".$this->conn->connect_error;
  			exit;
  		}
		Tag::echoDebug("db.statement", "connect to MySql-database with user $user on host $host in db-container ".$this->getName());
  		$this->host= $host;
  		$this->user= $user;
		$this->dbName= $database;
		if(	isset($database) &&
			$database != null &&
			trim($database) != ""	)
		{
			// if database was defined (same as this case)
			// function do not select new databse
			// only create content of table names
			$this->useDatabase($database);
		}
	}
	/**
	*  close connection to database
	*/
	function closeConnection()
	{
		if(Tag::isDebug())
		{
			Tag::echoDebug("db.statement", "close MySql-connection from database ".$this->dbName." in db-container ".$this->getName());
			if(!$this->conn->close())
				echo $this->conn->error."<br />";
		}else
			$this->conn->close();
	}
	// deprecated
   	function toDatabase($database, $onError= onErrorStop)
	{
		$this->useDatabase($database, $onError);
	}
		/**
		*  wechselt zu der angegebenen Datenbank
		*
		*  @param string:$database Datenbank-Name
		*  @param enum:$onError ob die Methode Fehler anzeigen soll und beendet werden soll.
		*					<br>noErrorShow - Fehler wird nicht angezeigt und Programm nicht beendet
		*					<br>onErrorShow - Fehler wird angezeigt aber Programm nicht beendet
		*  		  			<br>onErrorStop - Fehler wird angezeigt und Programm beendet
		*/
   	function useDatabase($database, $onError= onErrorStop)
   	{
   		STCheck::paramCheck($database, 1, "string");
   		STCheck::paramCheck($onError, 2, "int");
   		
		STCheck::echoDebug("db.statement". "use Mysql Database $database");
		if($database != $this->dbName)
		{
			$this->dbName= $database;
			// alex 17/05/2005: obwohl referenze auf innere DB besteht
			//					wird der DB-Name dort nicht eingetragen.
			//					keine Ahnung warum !!!
			//$this->db->dbName= $this->dbName;
			Tag::echoDebug("db.statement", "use new Database ".$database);
			if(!$this->conn->select_db("$database"))
			{
					$this->error= true;
					if($onError>noErrorShow)
					{
						echo "<br>can not reache database <b>$database</b>,<br>";
						if( $this->conn==null )
							echo "with no connection be set<br>";
						else
						{
							echo "with user <b>$this->user</b> on host <b>$this->host</b><br>";
							echo "<b>ERROR".$this->conn->connect_errno.": </b>".$this->conn->connect_error."<br />";
						}
						if($onError==onErrorStop)
							exit();
					}
			}
		}
		// read all tables in database
		$this->asExistTableNames= $this->fetch_single_array("show tables");
		
		// save also in db on wich must be an reference,
		// do not know why not
		//$this->db->asExistTableNames= $this->asExistTableNames;
		if(	STCheck::isDebug("db.statement")
			or
			STCheck::isDebug("table")		)
		{
			if(STCheck::isDebug("table"))
				$debugString= "table";
			else
				$debugString= "db.statement";
			if($this->asExistTableNames)
			{
				Tag::echoDebug($debugString, "found existing tables in database <b>".$this->dbName."</b>:");
				echo "<b>[</b>".$debugString."<b>]:</b> ";
				st_print_r($this->asExistTableNames, 1, (strlen($debugString)+4));
				echo "<br />";
			}else
				STCheck::echoDebug("db.statement", "WARNING: does not found any table in database <b>".$this->dbName."</b>");
		}else if(	STCheck::isDebug()
					and
					!$this->asExistTableNames	)
		{
			echo "<b>WARNING:</b> does not found any table in database <b>".$this->dbName."</b><br />";
		}
	}
	public function real_escape_string(string $str)
	{
	    $escaped= $this->conn->real_escape_string($str);
	    return $escaped;
	}
	function getLastInsertedPk()
	{
		return $this->insert_id();
	}
	protected function querydb($statement)
	{
		global $_st_page_starttime_;
		
		if(STCheck::isDebug())
		{
		    STCheck::echoDebug("db.statement", $statement);
		    STCheck::echoDebug("db.statement.time", date("H:i:s")." ".(time()-$_st_page_starttime_));
		}
		$this->lastDbResult = $this->conn->query($statement);
		return $this->lastDbResult;
	}
	protected function list_dbtable_fields($TableName)
	{
		if(isset($this->databaseTables[$TableName]))
			return $this->databaseTables[$TableName];
		$fields= array();
		$statement= "SHOW COLUMNS FROM $TableName";
		$res= $this->query($statement);
		$errno= $this->errnodb();
		if($errno > 0)
			return NULL;
		while($row= $this->fetch_row(STSQL_ASSOC))
		{
			$fields[]= $row;
		}
		$this->databaseTables[$TableName]= $fields;
		return $fields;
	}
	public function field_count($dbResult)
	{
		//st_print_r($dbResult,2);
		//echo "field count is ".$dbResult->field_count."<br>";
		return $dbResult->field_count;
	}
	private function getFieldProperties($tableName, $field_offset)
	{
		if(!isset($this->field_properties[$tableName][$field_offset]))
		{
			$this->list_dbtable_fields($tableName);
			if($this->lastDbResult == NULL)
				return NULL;
			$this->field_properties[$tableName][$field_offset]= mysqli_fetch_field_direct($this->lastDbResult, $field_offset);
			if(!is_object($this->field_properties[$tableName][$field_offset]))
				return null;
		}
		return $this->field_properties[$tableName][$field_offset];
	}
	public function field_name($tableName, $field_offset)
	{
		$properties= $this->getFieldProperties($tableName, $field_offset);
		if($properties == NULL)
			return NULL;
		return is_object($properties) ? $properties->name : null;
	}
	public function field_len($tableName, $field_offset)
	{
		$properties= $this->getFieldProperties($tableName, $field_offset);
		if($properties == NULL)
			return NULL;
		return is_object($properties) ? $properties->length : null;
	}
	public function field_NullAllowed($tableName, $field_offset)
	{
		return !$this->has_field_flag($tableName, $field_offset, "NOT_NULL");
	}
	public function field_PrimaryKey($tableName, $field_offset)
	{
		return $this->has_field_flag($tableName, $field_offset, "PRI_KEY");
	}
	public function field_autoIncrement($tableName, $field_offset)
	{
		return $this->has_field_flag($tableName, $field_offset, "AUTO_INCREMENT");
	}
	public function field_UniqueKey($tableName, $field_offset)
	{
		return $this->has_field_flag($tableName, $field_offset, "UNIQUE_KEY");
	}
	public function field_MultipleKey($tableName, $field_offset)
	{
		return $this->has_field_flag($tableName, $field_offset, "MULTIPLE_KEY");
	}
	public function field_enum_field($tableName, $field_offset)
	{
		return $this->has_field_flag($tableName, $field_offset, "ENUM");
	}
	public function getField_EnumArray($tableName, $field_offset)
	{
		if(!isset($this->databaseTables[$tableName]))
			$this->list_dbtable_fields($tableName);

		$flags= array();
		if(!preg_match("/enum\((.+)\)/i", $this->databaseTables[$tableName][$field_offset]["Type"], $flags))
			return null;
		$enums= preg_split("/','/", $flags[1]);
		$enums[0]= substr($enums[0], 1);
		$enums[count($enums)-1]= substr($enums[count($enums)-1], 0, strlen($enums[count($enums)-1])-1);
		return $enums;
	}
	private function has_field_flag($tableName, $field_offset, $flag_name)
	{
		static $flags;

		$properties= $this->getFieldProperties($tableName, $field_offset);
		if($properties == NULL)
			return NULL;
		$flags_num= $properties->flags;

		if (!isset($flags))
		{// create flags-table
			$flags = array();
			$constants = get_defined_constants(true);
			foreach ($constants['mysqli'] as $c => $n)
			{
				if (preg_match('/MYSQLI_(.*)_FLAG$/', $c, $m))
				{	
					if (!array_key_exists($n, $flags)) 
						$flags[$m[1]] = $n;
				}
			}
			if(STCheck::isDebug("show.db.fields"))
			{
				STCheck::echoDebug("show.db.fields", "existing mysql flags on mysqli-client:");
				st_print_r($flags, 2, 20);
				echo "<br />";
			}
		}

		if(!isset($flags[$flag_name]))
			return false;
		if($flags_num & $flags[$flag_name])
			return true;
		return false;
	}
	protected function allowedTypeNames($allowed)
	{
		if (empty($this->columnTypes))
		{
			$constants = get_defined_constants(true);
			foreach ($constants['mysqli'] as $c => $n)
			{
				if (preg_match('/^MYSQLI_TYPE_(.*)/', $c, $m))
					$this->existTypes[$n]= $m[1];
			}
			// all not listet types are not handled inside STItemBox
			// so it should throw an exception when it should be used
			// to know for the developer to handle it!
			$this->columnTypes= array(	"DECIMAL"     => "real",
										"TINY"        => "int",
										"SHORT"       => "int",
										"LONG"        => "int",
										"FLOAT"       => "real",
										"DOUBLE"      => "real",
										"TIMESTAMP"   => "int",
										"LONGLONG"    => "int",
										"INT24"       => "int",
										"DATE"        => "date",
										"TIME"        => "time",
										"DATETIME"    => "datetime",
										"NEWDATE"     => "date",
										"ENUM"        => "enum",
										"SET"         => "enum",
										"TINY_BLOB"   => "string",
										"MEDIUM_BLOB" => "string",
										"LONG_BLOB"   => "string",
										"BLOB"        => "string",
										"VAR_STRING"  => "string",
										"STRING"      => "string",
										"CHAR"        => "string",
										"NEWDECIMAL"  => "real"		  );
			if(STCheck::isDebug("show.db.fields"))
			{
				STCheck::echoDebug("show.db.fields", "existing mysql types on mysqli-client:");
				st_print_r($this->existTypes, 2, 20);
				echo "<br />";
				STCheck::echoDebug("show.db.fields", "allowed database types:");
				st_print_r($allowed, 2, 20);
				echo "<br />";
				STCheck::echoDebug("show.db.fields", "compare Table:");
				st_print_r($this->columnTypes, 2, 20);
				echo "<br />";
			}
		}

	}
	public function field_type($tableName, $field_offset)
	{
		$properties= $this->getFieldProperties($tableName, $field_offset);
		if($properties == NULL)
			return NULL;
		if(!array_key_exists($properties->type, $this->existTypes ))
			throw new Exception("type '$properties->type' does not exist inside existing database list	");
		$type= $this->existTypes[$properties->type];
		if(!array_key_exists($type, $this->columnTypes ))
			throw new Exception("type '$type' does not exist inside known list");
		return $this->columnTypes[$type];
	}
	protected function insert_id()
	{
		return $this->conn->insert_id;
	}
    
	protected function fetchdb_row($type= STSQL_ASSOC, $onError= onErrorStop)
	{
		if(	$type == STSQL_NUM ||
			$type == STSQL_BOTH		)
		{
			$num= $this->lastDbResult->fetch_row();
			if($num == NULL)
				return NULL;
			$row= $num;
		}
		if(	$type == STSQL_ASSOC ||
			$type == STSQL_BOTH		)
		{
			$row= $this->lastDbResult->fetch_assoc();
			if($this->errordb())
			{
			    if($onError == onErrorStop)
			    {
			        STCheck::error(1, "fetchdb_row", $this->errordb());
			        exit;
			    }else
			        STCheck::warning(1, "fetchdb_row", $this->errordb());
			}
			if($row == NULL)
				return NULL;
			if($type == STSQL_BOTH)
				$row= array_merge($row, $num);
		}
		return $row;
	}
	protected function errnodb()
	{
		$ern= $this->conn->connect_errno;
		if(	$ern > 0 ||
			$this->lastDbResult === NULL	)
		{
			return $ern;
		}
		return $this->conn->errno;
	}
	protected function errordb()
	{
		$ern= $this->conn->connect_errno;
		if(	$ern > 0 ||
			$this->lastDbResult === NULL	)
		{
			return $this->conn->connect_error;
		}
		return $this->conn->error;
	}
	function solution($statement, $typ= null, $onError= onErrorStop)
	{
		$typ= $this->getTyp($typ);
	 	return $this->fetch($statement, $typ, $onError);
	}
	function getRegexpOperator()
	{
		return "regexp";
	}
	function getLikeOperator()
	{
		return "like";
	}
	function getIsOperator()
	{
		return "=";
	}
	function getGreaterOperator()
	{
		return ">";
	}
	function getGreaterEqualOperator()
	{
		return ">=";
	}
	function getLowerOperator()
	{
		return "<";
	}
	function getLowerEqualOperator()
	{
		return "<=";
	}
	function getIsNotOperator()
	{
		return "!=";
	}
	function getIsNullOberator()
	{
		return "is";
	}
	function getIsNotNullOperator()
	{
		return "is not";
	}
	function getIntLen()
	{
		return 11;
	}
	function getBigIntLen()
	{
		return 20;
	}
	function getTextLen()
	{
		return 65535;
	}
	function &getDatatypes()
	{
		if($this->datatypes)
		{
			return $this->datatypes;
		}

		$datatypes=	array(	"TINYINT"=>		array(	"type"=>	"int",
													"length"=>	4,
													"range"=>	array(	"u"=>	array(	0,
																						255		),
																		"s"=>	array(	-128,
																						127		)	)	),
							"SMALLINT"=>	array(	"type"=>	"int",
													"length"=>	6,
													"range"=>	array(	"u"=>	array(	0,
																						65535	),
																		"s"=>	array(	-32768,
																						 32767	)	)	),
							"MEDIUMINT"=>	array(	"type"=>	"int",
													"length"=>	9,
													"range"=>	array(	"u"=>	array(	0,
																						16777215	),
																		"s"=>	array(	-8388608,
																						+8388607	)	)	),
							"INT"=>			array(	"type"=>	"int",
													"length"=>	11,
													"range"=>	array(	"u"=>	array(	0,
																						4300000000	),
																		"s"=>	array(	-2147483648,
																						+2147483647	)	)	),
							"INTEGER"=>		array(	"type"=>	"int",
													"length"=>	11,
													"range"=>	array(	"u"=>	array(	0,
																						4300000000	),
																		"s"=>	array(	-2147483648,
																						+2147483647	)	)	),
							"BIGINT"=>		array(	"type"=>	"int",
													"length"=>	20,
													"range"=>	array(	"u"=>	array(	0,
																						2E+64-1		),
																		"s"=>	array(	-2E+63,
																						+2E+63-1	)	)	),
							"FLOAT"=>		array(	"type"=>	"real",
													"length"=>	null,
													"range"=>	array(	"s"=>	array(	-3,402823466E+38,
																						-1,175494351E+38,
																						 0,
																						 0,
																						 1,175494351E+38,
																						 3,402823466E+38	)	)	),
							"DOUBLE"=>		array(	"type"=>	"real",
													"length"=>	null,
													"range"=>	array(	"s"=>	array(	-1,798^308,
																						-2,225^-308,
																						 0,
																						 0,
																						 2,225^-308,
																						 1,798^308		)	)	),
							"REAL"=>		array(	"type"=>	"real",
													"length"=>	null,
													"range"=>	array(	"s"=>	array(	-1,798^308,
																						-2,225^-308,
																						 0,
																						 0,
																						 2,225^-308,
																						 1,798^308		)	)	),
							"DATE"=>		array(	"type"=>	"time",
													"format"=>	"YYYY-MM-DD"	),
							"DATETIME"=>	array(	"type"=>	"time",
													"format"=>	"YYYY-MM-DD hh:mm:ss"	),
							"TIMESTAMP"=>	array(	"type"=>	"time",
													"format"=>	"YYYY-MM-DD hh:mm:ss"	),
							"TIME"=>		array(	"type"=>	"time",
													"format"=>	"hh:mm:ss"	),
							"CHAR"=>		array(	"type"=>	"string",
													"length"=>	255			),
							"VARCHAR"=>		array(	"type"=>	"string",
													"length"=>	255			),
							"BLOB"=>		array(	"type"=>	"string",
													"length"=>	65535		),
							"TEXT"=>		array(	"type"=>	"string",
													"length"=>	65535		),
							"ENUM"=>		array(	"type"=>	"enum",
													"max"=>		65535		),
							"SET"=>			array(	"type"=>	"enum",
													"max"=>		64			)				);
		$this->datatypes= &$datatypes;
		return $datatypes;
	}
	function saveForeignKeys($bMake= null)
	{
		if(is_bool($bMake))
		{
			if($bMake===true)
				$this->bFKsave= mysqlVersionNeed("4.0.7");
			else
				$this->bFKsave= false;

		}elseif(!is_bool($this->bFKsave))
			$this->bFKsave= mysqlVersionNeed("4.0.7");
		return $this->bFKsave;
	}

}

 ?>