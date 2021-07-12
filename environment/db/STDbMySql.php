<?

//last change 23.11.2004
require_once($php_tools_class);
//require_once($database_selector);
//require_once($stdbtablecontainer);
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
	*  Konstruktor f�r Zugriffs-deffinition
	*
	*/
	function STDbMySql($identifName= "main-menue", $defaultTyp= MYSQL_NUM, $DBtype= "MYSQL")
   	{
		STDatabase::STDatabase($identifName, $defaultTyp, "MYSQL");
  	}
	/**
	*  Verbindungs-Aufbau zur Datenbank
	*
	*  @param $host: Hostname
	*  @param $user: Username
	*  @param $passwd: Passwort
	*/
	function connect($host= null, $user= null, $passwd= null)
	{
   		$this->conn= @mysql_connect($host, $user, $passwd);
		// alex 17/05/2005: obwohl referenze auf innere DB besteht
		//					wird die Connection dort nicht eingetragen.
		//					keine Ahnung warum !!!
		$this->db->conn= $this->conn;
  		if(!$this->conn)
  		{
				$this->error= true;
  			echo "can not make the connection to host <b>$host</b><br>";
  			echo "with user <b>$user</b><br>";
  			echo "<b>ERROR".@mysql_errno($this->conn).": </b>".@mysql_error($this->conn);
  			exit;
  		}
		Tag::echoDebug("db.statement", "connect to MySql-database with user $user on host $host in db-container ".$this->getName());
  		$this->host= $host;
  		$this->user= $user;
	}
	/**
	*  close connection to database
	*/
	function closeConnection()
	{
		if(Tag::isDebug())
		{
			Tag::echoDebug("db.statement", "close MySql-connection from database ".$this->dbName." in db-container ".$this->getName());
			mysql_close($this->conn);
		}else
			@mysql_close($this->conn);
	}
	// deprecated
   	function toDatabase($database, $onError= onErrorStop)
	{
		$this->useDatabase($database, $onError);
	}
		/**
		*  wechselt zu der angegebenen Datenbank
		*
		*  @param $database: Datenbank-Name
		*  @param $onError: ob die Methode Fehler anzeigen soll und beendet werden soll.
		*					<br>noErrorShow - Fehler wird nicht angezeigt und Programm nicht beendet
		*					<br>onErrorShow - Fehler wird angezeigt aber Programm nicht beendet
		*  		  			<br>onErrorStop - Fehler wird angezeigt und Programm beendet
		*/
   	function useDatabase($database, $onError= onErrorStop)
   	{
   		STCheck::paramCheck($database, 1, "string");
   		STCheck::paramCheck($onError, 2, "int");
   		
		STCheck::echoDebug("db.statement". "use Mysql Database $database");
		$this->dbName= $database;
		// alex 17/05/2005: obwohl referenze auf innere DB besteht
		//					wird der DB-Name dort nicht eingetragen.
		//					keine Ahnung warum !!!
		$this->db->dbName= $this->dbName;
		Tag::echoDebug("db.statement", "use Database ".$database);
  		if(!mysql_select_db("$database", $this->conn))
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
        			echo "<b>ERROR".mysql_errno($this->conn).": </b>".mysql_error($this->conn);
					}
					if($onError==onErrorStop)
    				exit();
				}
  		}
		// read all tables in database
		$this->asExistTableNames= $this->fetch_single_array("show tables");
		// save also in db on wich must be an reference,
		// do not know why not
		$this->db->asExistTableNames= $this->asExistTableNames;
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
	function getLastInsertedPk()
	{
		return mysql_insert_id($this->conn);
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