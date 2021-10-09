<?php

//last change 23.11.2004
require_once($_stdbtable);
require_once($_stobjectcontainer);
require_once($_stdbtabledescriptions);

/**
*	 src: STDatabase.php
*	 class STDatabase: zugriff auf Datenbank
*/

/**
* Abstract class for access to any databases
*
* @abstract
* @author Alexander Kolli
* @version 1.0
*/
abstract class STDatabase extends STObjectContainer
{
/**
*  string type of the database
* @abstract
* @access private
* @var string
*/
	var $dbType= "defined DB";
/**
*  defined type in which format the result from the database will be showen
*
* <table>
*	<tr>
*		<th align='left' colspan='2'>
*			existing types:
*		</th>
*	</tr>
*	<tr>
*		<td>
*			<b>STSQL_NUM</b>
*		</td>
*		<td>
*			- showes the Fields in the row array with 1, 2, 3, ...
*		</td>
*	</tr>
*	<tr>
*		<td>
*			<b>STSQL_ASSOC</b>
*		</td>
*		<td>
*			- the Fields are showen as key from the select-statement
*		</td>
*	</tr>
*	<tr>
*		<td>
*			<b>STSQL_BOTH</b>
*		</td>
*		<td>
*			- both defined given -> the number and also the named-key
*		</td>
*	</tr>
*
* @access private
* @var integer
*/
	var $defaultTyp;
/**
*  an string which define the host-name or -address on which the database is running
*
* @access private
* @var string
*/
  	var $host= null;
/**
*  an string which define the user-name to access the database
*
* @access private
* @var string
*/
  	var $user= null;
/**
*  an string which define to which database will be connect
*
* @access private
* @var string
*/
  	var $dbName= "";
/**
*  contains all exist name of tables in the choosed database
*
* @access private
* @var array string
*/
	var	$tableNames;
/**
*  contains the structer of tables with his foreign keys
*
* @access private
* @var array string
*/
	var $aTableStructure= array();

	/**
	 * all type-name whitch column
	 * can have
	 */
	private $allowedTypes=	array(	"int",
									"real",
									"string",
									"time",
									"date",
									"datetime",
									"enum"		);
	/*
		all exist types		array(	"decimal",
									"tiny",
									"short",
									"long",
									"float",
									"double",
									"null",
									"timestamp",
									"longlong",
									"int24",
									"date",
									"time",
									"datetime",
									"year",
									"newdate",
									"enum",
									"set",
									"tiny_blob",
									"medium_blob",
									"long_blob",
									"blob",
									"var_string",
									"string",
									"char",
									"interval",
									"geometry",
									"json",
									"newdecimal",
									"bit"      		);*/

/**
*  contains all tablenames in the database to which was curently conected
*
* @access private
* @var array string
*/
	var	$asExistTableNames= array();
	var $aOtherTableWhere= array();
	var $lastStatement;
	var $foreignKey= false;
	var	$aFieldArrays= array();
	var $error;
	var $datePos;
	var $pregDateFormat;
	var $dateDelimiter= ".";
	var $timePos;
	var $pregTimeFormat;
	var	$bOrderDates= true; // shoud be order the date in the sqlResult?
	var $inFields= array();
	var	$bFirstSelectStatement;	// f�r funktion getSelectStatement()
								// ob sie von getStatement aufgerufen wurde,
								// oder in einer rekursiven Schleife l�uft
	var	$bFKsave= null; // make foreign Keys in DB,
						// for MySql it define no innoDB
	var	$sNeedAlias= array(); // hier wird eingetragen welches Alias ben�tigt wurde
	var $aValues= null;
	var $bHasTables= false;


	  /**
		*  Konstruktor f�r Zugriffs-deffinition
		*
		*/
  	function __construct($identifName= "main-menue", $defaultTyp= MYSQL_NUM, $DBtype= "BLINDDB")
   	{
		$this->defaultTyp= $defaultTyp;
		$this->error= false;
		$this->dbType= strtoupper($DBtype);
		$this->datePos= array("YYYY", "MM", "DD");
		$this->pregDateFormat= "/^([0-9]{2,4})[., :-]([0-9]{1,2})[., :-]([0-9]{1,2})$/";
		$this->timePos= array("", "HH", ":", "MM", ":", "SS", "");
		$this->pregTimeFormat= "/([0-9]{1,2})[., :-]([0-9]{1,2})([., :-]([0-9]{1,2}))?/";
    	// alex 17/05/2005:	class is now extend from STObjectcontainer
    	//					and must give at second parameter an container
    	STObjectContainer::__construct($identifName, $this);
  	}
	static function existDatabaseClassName($className)
	{
		if($className==="STDbMySql")
			return true;
		return false;
	}
	function getDatabaseType()
	{
		return $this->dbType;
	}
	function getTyp($typ= null)
	{
		if(	isset($typ) &&
			$this->isSqlTyp($typ)	)
		{
			return $typ;
		}
		return $this->defaultTyp;
	}
	function isTable($tableName)
	{
		Tag::paramCheck($tableName, 1, "string");

		foreach($this->asExistTableNames as $sTableName)
		{
			if(preg_match("/^".$sTableName."$/i", $tableName))
				return true;
		}
		return false;
	}
	function hasTables()
	{
		return $this->bHasTables;
	}
	function setTimeFormat($sFormat)
	{
		//$sFormat= strtoupper(trim($sFormat));
		$res= preg_match("/^([^HMS]*)([HMS]{1,2})(([^HMS]+)([HMS]{1,2})(([^HMS]+)([HMS]{1,2}))?)?(.*)$/", $sFormat, $preg);
		if(STCheck::isDebug())
			STCheck::alert(!$res, "STDatabase::setTimeFormat()", "wrong timeformat '$sFormat'");

		$delimiter= "([^0-9]+)";
		$pregTimeFormat= "/^";
		if($preg[1] !== "")
			$pregTimeFormat.= "([^0-9]+)";
		$this->timePos= array();
		for($i= 1; $i<=9; $i++)
		{
			if(	$preg[$i] !== ""
				&& $i!=3 && $i!=6)
			{

				$this->timePos[]= $preg[$i];
				if($i!=1 && $i!=4 && $i!=7 && $i!=9)
				{
					$pregTimeFormat.= "([0-9]{1";
					if(strlen($preg[$i]) > 1)
						$pregTimeFormat.= ",2";
					$pregTimeFormat.= "})";
	  				if(preg_match("/S/", $preg[$i]))
						$pregTimeFormat.= "([0-9]{1,2})";
				}else
					$pregTimeFormat.= $delimiter;
			}
		}
		$this->pregTimeFormat= $pregTimeFormat."$/";
	}
	function setDateFormat($sFormat)
	{
		$sFormat= strtoupper(trim($sFormat));
		Tag::alert(!preg_match("/^([YMD]{2,4})([., :-])([YMD]{2,4})[., :-]([YMD]{2,4})$/", $sFormat, $preg),
						"STDatabase::setDateFormat()", "wrong dateformat");

		$this->datePos= array();
		$pregDateFormat= "/^";
		for($i= 1; $i<=4; $i++)
		{
			if($i==2)
				$this->dateDelimiter= $preg[2];
			else
			{
				$p= $preg[$i];
				$this->datePos[]= $p;
  				if(preg_match("/Y/", $p))
					$pregDateFormat.= "([0-9]{2,4})";
				else
					$pregDateFormat.= "([0-9]{1,2})";
				$pregDateFormat.= "[ .,:-]";
			}
		}
		$this->pregDateFormat= substr($pregDateFormat, 0, strlen($pregDateFormat)-7)."$/";
	}
	function makeUserTimeFormat($dbtime)
	{//echo "preg_match(\"/^([0-9]{2}):([0-9]{2}):([0-9]{2})$/\", $dbtime, $preg)<br />";
		if(!preg_match("/^([0-9]{2}):([0-9]{2}):([0-9]{2})$/", $dbtime, $preg))
			return false;

		$sRv= "";
		foreach($this->timePos as $pos)
		{
			if(preg_match("/H/", $pos))
			{
				if(strlen($pos) === 1)
				{
					if(substr($preg[1], 0, 1) === "0")
						$preg[1]= substr($preg[1], 1);
				}
				$sRv.= $preg[1];
			}elseif(preg_match("/M/", $pos))
			{
				$sRv.= $preg[2];
			}elseif(preg_match("/S/", $pos))
			{
				$sRv.= $preg[3];
			}else
				$sRv.= $pos;
		}
		return $sRv;
	}
	function makeUserDateFormat($date)
	{
		if(!preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})/", $date, $preg))
			return false;
		$sRv= "";
		for($i= 0; $i<3; $i++)
		{
			$p= $this->datePos[$i];
			if(preg_match("/Y/", $p))
			{
				$d= $preg[1];
				if(strlen($p)<4)
					$d= substr($d, 2, 2);
			}elseif(preg_match("/M/", $p))
			{
				$d= $preg[2];
				if(strlen($p)<2 && substr($d, 0, 1)=="0")
					$d= substr($d, 1, 1);
			}elseif(preg_match("/D/", $p))
			{
				$d= $preg[3];
				if(strlen($p)<2 && substr($d, 0, 1)=="0")
					$d= substr($d, 1, 1);
			}
			$sRv.= $d.$this->dateDelimiter;
		}
		$sRv= substr($sRv, 0, strlen($sRv)-strlen($this->dateDelimiter));
		return $sRv;
	}
	function makeSqlDateFormat($date)
	{//echo "preg_match(\"".$this->pregDateFormat."\", \"$date\", Array())<br />";
    	if(!preg_match($this->pregDateFormat, $date, $preg))
    		return false;
    	$format= array_flip($this->datePos);
    	if(isset($format["YYYY"]))
    		$n= $format["YYYY"];
    	else
    		$n= $format["YY"];
    	$sRv= $preg[$n+1]."-";
    	if(isset($format["MM"]))
    		$n= $format["MM"];
    	else
    		$n= $format["M"];
    	$sRv.= $preg[$n+1]."-";
    	if(isset($format["DD"]))
    		$n= $format["DD"];
    	else
    		$n= $format["D"];
    	$sRv.= $preg[$n+1];
    	return $sRv;
    }
	function makeSqlTimeFormat($time)
	{
		echo "file:".__file__." line:".__line__."<br />";
		echo "makeSqlTimeFormat() programming is'nt findished'";
		exit;
	}
	function getSqlDateFromTimestamp($timestamp= null)
	{
		if($timestamp===null)
			$timestamp= time();
		$sqlDate= date("Y-m-d", $timestamp);
		return $sqlDate;
	}
	function getNullDate()
    {
    	$sRv= "";
      	foreach($this->datePos as $value)
      	{
      		for($n= 0; $n<strlen($value); $n++)
      			$sRv.= "0";
      		$sRv.= $this->dateDelimiter;
      	}
      	$sRv= substr($sRv, 0, strlen($sRv)-strlen($this->dateDelimiter));
      	return $sRv;
	}
	function getNullTime()
	{
		$sRv= "";
		foreach($this->timePos as $i=>$value)
		{
			if($i==0 || $i==2 || $i==4)
			{
				$sRv.= "0";
				if(strlen($value)==2)
					$sRv.= "0";
			}else
				$sRv.= $value;
		}
		return $sRv;
	}
	function getNullDateTime()
	{
		$date= $this->getNullDate();
		$time= $this->getNullTime();
		return $date." ".$time;
	}
	function getDateFormat()
	{
		$format= "";
		foreach($this->datePos as $content)
    	{
    		$format.= $content;
    		$format.= $this->dateDelimiter;
    	}
		$format= substr($format, 0, strlen($format)-1);
		return $format;
	}
	function getTimeFormat()
	{
		$sRv= "";
		foreach($this->timePos as $value)
			$sRv.= $value;
		return $sRv;
	}
	function makeTimestampFromSqlDateFormat($date)
	{
		$timestamp= strtotime($date);
		if($timestamp<0)
			return null;
		return $timestamp;
		/*if(preg_match("/^[0-9]{4}[0-9]{2}[0-9]{2}$/", $date, $preg))
  		{
   			$time= $preg[3]." ".$preg[2]." ".$preg[1];
   			$timestamp= strtotime($time);
   			if($timestamp<0)
    			return null;
			return $timestamp;
		}
  		return null;*/
	}
	function makeTimestampFromUserDateFormat($date)
	{
		$sqlDate= $this->makeSqlDateFormat($date);
		return $this->makeTimestampFromSqlDateFormat($sqlDate);
	}


	/**
	*  Verbindungs-Aufbau zur Datenbank
	*
	*  @param $host: Hostname
	*  @param $user: Username
	*  @param $passwd: Passwort
	*/
	abstract public function connect($host= null, $user= null, $passwd= null, $database= null);
	abstract public function closeConnection();
   	function useDatabase($database, $onError= onErrorStop)
   	{
   		$this->bHasTables= false;
		$this->dbName= $database;
		if(Tag::isDebug())
			Tag::warning(1, "STDatabase::useDatabase()", "function not overloaded from class ".get_class($this));
	}
	function getDatabaseName()
	{
		return $this->dbName;
	}
	function getAllTableNames()
	{
		return $this->asExistTableNames;
	}
	function getConnection()
	{
		return $this->conn;
	}
	abstract protected function querydb($statement);
  	public function fetch($statement, $onError= onErrorStop)
	{
		STCheck::deprecated("STDatabase::query()", "STDatabase::fetch()");
		return $this->query($statement, $onError);
	}
	public function query($statement, $onError= onErrorStop)
  	{
		global $HTML_CLASS_DEBUG_CONTENT;
		global $g_first_scanDescribe;
		
		if($this->dbType=="BLINDDB")
			return;
		// alex 12/05/2005:	statement kann nun auch
		//					ein objekt von STDbTable sein
		if(typeof($statement, "STDbTable"))
			$statement= $this->getStatement($statement);
		if(is_String($statement))
		{
			if(STCheck::isDebug())
			{
				global	$_st_page_starttime_;
		
				if(!$_st_page_starttime_)
					Tag::setPageStartTime();
				Tag::echoDebug("db.statement.time", date("H:i:s")." ".(time()-$_st_page_starttime_));
				//Tag::echoDebug("db.statement", "in DB:".$this->dbName." conn:".$this->conn."\"");
				Tag::echoDebug("db.statement", " \"".$statement."\"");			
				STCheck::flog("fetch statement on db with command mysql_query");
			}
			$res= $this->querydb($statement);
		}else// wenn statement schon ein Array, wird dieses sogleich
			return $statement; // als Ergebnis zur�ckgegeben
		
		$this->lastStatement= $res;
    	if( (	$res==null
				or
				!$res	)
			&&
			(	$onError > noErrorShow
				or
				STCheck::isDebug("db.statement")	)	)
  		{
			echo $this->getError(true);
			if(phpVersionNeed("4.3.0", "debug_backtrace()"))
				showErrorTrace(1);
			if( $onError==onErrorStop )
			 	 exit();
  		}
  		return $res;
  	}
	function getError($withTags= false)
	{
		//if($this->isError())	// ??? was soll das?
		//	return "";			// damit der Fehler nur einmal ausgegeben wird?

		if($this->errno()==0)
			return "kein Ergebnis vorhanden";
		$string= "";
		if($withTags)
     		$string=  "\n<b>";
		$string.= "MYSQL_ERROR ".$this->errno()." in Statement: \"";
		if($withTags)
			$string.= "</b>";
		$string.= $this->lastStatement;
		if($withTags)
			$string.= "<b>";
		$string.= "\"\n";
		if($withTags)
			$string.= "</b><br><b>";
		$string.= "MySql:";
		if($withTags)
			$string.= "</b>";
     	$string.=  " ".$this->error();
		if($withTags)
			$string.= "<br />\n";
	  	return $string;

	}
	protected function isSqlTyp($typ)
	{
		if(	$typ == STSQL_NUM ||
			$typ == STSQL_ASSOC ||
			$typ == STSQL_BOTH		)
		{
			return true;
		}
		return false;
	}
	abstract protected function fetchdb_row($typ);
	/*
	 * 2021/07/29 alex: change function from error() to is_error() for php8 compatibility
	 * 					with STDatabase class where an error function
	 * 					be with no parameters
	 */
	function fetch_row($typ= STSQL_ASSOC, $onError= onErrorStop)
	{
		STCheck::paramCheck($typ, 1, "check", $typ==STSQL_ASSOC || $typ==STSQL_NUM || $typ==STSQL_BOTH, $typ==STBLINDDB,
														"STSQL_ASSOC", "STSQL_NUM", "STSQL_BOTH", "STBLINDDB");
		STCheck::paramCheck($onError, 2, "check", $onError==noErrorShow || $onError==onErrorShow || $onError==onErrorStop,
														"noErrorShow", "onErrorShow", "onErrorStop");
		if($this->dbType=="BLINDDB")
			return;
		$row= array();
		$type= $this->getTyp($typ);
		$row= $this->fetchdb_row($typ);
		if($row)
			$row= $this->orderDate("row", $row, "", $onError);
 		return $row;
	}
	function orderDates($bOrder)
	{// this function is only to do not order dates for the STDbSelector
	 // because it order self and the STDatabase object must not ask the
	 // database for the fields again
		$this->bOrderDates= $bOrder;
	}
	// type can be also an array of fields
	function orderDate($type, $array, $statement= "", $onError= onErrorStop)
	{
		STCheck::paramCheck($type, 1, "array", "string");
		STCheck::paramCheck($array, 2, "array");
		STCheck::paramCheck($statement, 3, "string", "empty(string)");
		STCheck::paramCheck($onError, 4, "check", $onError==noErrorShow || $onError==onErrorShow || $onError==onErrorStop,
														"noErrorShow", "onErrorShow", "onErrorStop");
		
		if(!$this->bOrderDates)
			return $array;

		if(	!count($array)
			or
			preg_match("/describe|show tables/i", $statement)	)
		{
			return $array;
		}

		if(	isset($this->inFields[$statement]) &&
			count($this->inFields[$statement])==0 &&
			$type !== "row"							)
		{
			if(is_array($type))
			{
				$fields= $type;
				$type= "array";
			}else
			{
				$fields= $this->describeTable($statement, $onError);
			}
			$date= false;
			$bInsert= false;
 			foreach($fields as $key=>$column)
 			{
 				if($column["type"]=="date")
				{
					$this->inFields[$statement][]= array("type"=>"date", "nr"=>$key, "name"=>$column["name"]);
					$bInsert= true;
				}
 				if($column["type"]=="time")
				{
					$this->inFields[$statement][]= array("type"=>"time", "nr"=>$key, "name"=>$column["name"]);
					$bInsert= true;
				}
 			}
			if($bInsert==false)
				return $array;
			$array= $this->orderDate($type, $array, $statement, $onError);
			$inFields= array();
			return $array;
		}
		if(	is_string($type) &&
			trim(strtolower($type))=="array"	)
		{
			$aRv= array();
			foreach($array as $row)
				$aRv[]= $this->orderDate("row", $row, $statement, $onError);
			return $aRv;
		}
		if(	isset($this->inFields[$statement]) &&
			is_array($this->inFields[$statement])	)
		{
			foreach($this->inFields[$statement] as $Nr)
			{
				if($Nr["type"] == "date")
				{
					$cont= "nr";
					$date= $array[$Nr[$cont]];
					if(!isset($date))
					{
						$cont= "name";
						$date= $array[$Nr[$cont]];
					}
					$date= $this->makeUserDateFormat($date);
					$array[$Nr[$cont]]= $date;

				}elseif($Nr["type"] == "time")
				{
					$cont= "nr";
					$date= $array[$Nr[$cont]];
					if(!isset($date))
					{
						$cont= "name";
						$date= $array[$Nr[$cont]];
					}
					$date= $this->makeUserTimeFormat($date);
					$array[$Nr[$cont]]= $date;
				}
			}
		}
		return $array;
	}
 	function fetch_single($statement, $onError= onErrorStop)
 	{
		STCheck::paramCheck($statement, 1, "string");
		STCheck::paramCheck($onError, 2, "check", $onError==noErrorShow || $onError==onErrorShow || $onError==onErrorStop,
														"noErrorShow", "onErrorShow", "onErrorStop");
		if($this->dbType=="BLINDDB")
			return;
		$this->query($statement, $onError);
	  	$row= $this->fetch_row(STSQL_NUM, $onError);
		$Rv= null;
		if($row)
			$Rv= reset($row);
		return $Rv;
 	}
	abstract protected function errnodb();
 	function errno()
 	{
		$ern= $this->errnodb();
		if($ern > 0)
			$this->error= true;
 	 	return $ern;
  	}
  	function error()
	{
		$ern= $this->errnodb();
		if($ern > 0)
			$this->error= true;
		return $this->errordb();
  	}
	function fetch_single_array($statement, $onError= onErrorStop)
	{
		if($this->dbType=="BLINDDB")
			return;
		if(is_Array($statement))
			return $statement;
		$aRv= array();
		$array= $this->fetch_array($statement, MYSQL_NUM, $onError);
		foreach($array as $single)
			$aRv[]= $single[0];
		return $aRv;
	}
	function fetch_array($statement, $typ= STSQL_ASSOC, $onError= onErrorStop)
	{
		if($this->dbType=="BLINDDB")
			return;
		$typ= $this->getTyp($typ);

		if(is_Array($statement))
			return $statement;
 	 	$count= 0;
 	 	$Array= array();
		if(typeof($statement, "STAliasTable"))
			$statement= $this->getStatement($statement);
 		$res= $this->fetch($statement, $onError);
		if(!$res)
			return NULL;
		$orderTyp= $typ;
		if($typ==NUM_OSTfetchArray)
			$typ= MYSQL_NUM;
		elseif($typ==ASSOC_OSTfetchArray)
			$typ= MYSQL_ASSOC;
		elseif($typ==BOTH_OSTfetchArray)
			$typ= MYSQL_BOTH;
 		while($row = $this->fetchdb_row($typ))
 		{
			if(	$orderTyp==NUM_OSTfetchArray
				or
				$orderTyp==ASSOC_OSTfetchArray
				or
				$orderTyp==BOTH_OSTfetchArray	)
			{// Array wird zum suchen umsortiert
				foreach($row as $key => $value)
					$Array[$key][$count]= $value;
			}else
			  	$Array[$count]= $row;
			$count++;
 		}
		if(!preg_match("/show +tables/i", $statement))
			$Array= $this->orderDate("array", $Array, $statement, $onError);
 		return $Array;
 	}
	/**
	 *  liefert Tabellen-Information �ber einzelne Tabellen
	 *
	 * @param $statement: 	kann ein normales SQL-Statment sein,<br>
	 *						ein Tabellen-Name<br>oder ein Ergebnis aus einem Statement
	 *						(wo jedoch bei einem <code>enum</code> nur <code>enum</code>
	 *						 im flag angezeigt wird -> sonst auch der Inhalt des Enums)
	 *  @param $onError: 	ob die Methode Fehler anzeigen soll und beendet werden soll.
	 *						<br>noErrorShow - Fehler wird nicht angezeigt und Programm nicht beendet
	 *						<br>onErrorShow - Fehler wird angezeigt aber Programm nicht beendet
	 *  		  			<br>onErrorStop - Fehler wird angezeigt und Programm beendet
	 */
	function describeTable($statement, $onError= onErrorStop)
	{
		if(typeof($statement, "STAliasTable"))
		{
			return $statement->columns;
		}
		
		
		if(!is_String($statement))
			$result= $statement;// $statement ist bereits eine Datenbank-Abfrage
		elseif(isset($this->aFieldArrays[$statement]))
		{
			return $this->aFieldArrays[$statement];
		}
		elseif(preg_match("/ from ([^ ]*)/i", $statement, $preg))
		{// statement should be a correct query
			$tableName= $preg[1];
 	 		if(!preg_match("/limit/i", $statement))
 				$statement.= " limit 1";
			STCheck::echoDebug("db.statement", "describe field-content read from a <b>statement(</b>$statement<b>)</b>");
			echo "get name:$name<br>";
			echo "<br>".__FILE__.__LINE__."<br>";
			echo "toDo: describeTable can also called from an statement<br>";
			echo "      so differ between this two states!";
			exit;
		}else
		{// statement should only be a table name
			$tableName= $statement;
			$statement= "select * from $tableName limit 1";
			STCheck::echoDebug("db.statement", "describe field-content read from <b>table(</b>$tableName<b>)</b>");
		//-----------------------------------------------------------------------
		echo "<br>".__FILE__.__LINE__."<br>";
		echo "list db table fields<br>";
		st_print_r($this->list_dbtable_fields($tableName), 2);
		//-----------------------------------------------------------------------
		}

		$aRv= array();
		if(!isset($result))
			$result= $this->query($statement, $onError);
		if(!$result)
			return $aRv;
		$columns= $this->field_count($result);
		$this->allowedTypeNames($this->allowedTypes);
		for ($n= 0; $n<$columns; $n++)
		{
			$name=  $this->field_name($tableName, $n);
			$type=  $this->field_type($tableName, $n);
			$len=   $this->field_len($tableName, $n);
			$flags= "";
			if(!$this->field_NullAllowed($tableName, $n))
				$flags.= "not_null ";
			if($this->field_PrimaryKey($tableName, $n))
				$flags.= "primary_key ";
			if($this->field_UniqueKey($tableName, $n))
				$flags.= "unique_key ";
			if($this->field_MultipleKey($tableName, $n))
				$flags.= "multiple_key ";
			if($this->field_autoIncrement($tableName, $n))
				$flags.= "auto_increment ";
			$enums= $this->getField_EnumArray($tableName, $n);
			if(	is_array($enums) &&
				count($enums) > 0	)
			{
				$flags.= "enum ";
			}
			$aRv[$n]= array("name"=>$name, "flags"=>$flags, "type"=>$type, "len"=>$len);
			if(	is_array($enums) &&
				count($enums) > 0	)
			{
				$aRv[$n]['enums']= $enums;
			}
		}
		$this->aFieldArrays[$statement]= $aRv;
		if(STCheck::isDebug("show.db.fields"))
		{
			STCheck::echoDebug("show.db.fields", "produced column-result:");
			if(!empty($aRv))
				echo "<strong>ERROR:</strong> no field content!<br />";
			st_print_r($aRv, 5, 20);
		}
		echo "<br>";
		showErrorTrace();
		return $aRv;
 	}
	abstract public function getField_EnumArray($tableName, $field_offset);
	abstract public function field_UniqueKey($tableName, $field_offset);
	abstract public function field_MultipleKey($tableName, $field_offset);
	abstract public function field_NullAllowed($tableName, $field_offset);
	abstract public function field_PrimaryKey($tableName, $field_offset);
	abstract public function field_autoIncrement($tableName, $field_offset);
	abstract public function field_count($dbResult);
	abstract protected function allowedTypeNames($allowed);
	function setInTableNewColumn($tableName, $columnName, $type)
	{
		$objs= &STBaseContainer::getAllContainer();
		foreach($objs as $containerName=>$obj)
		{
			if(	$containerName!==$this->name
				and
				!typeof($obj, "STDatabase")	)
			{// if the container is an other database
			 // the container can not have this table
			 // because if do, the other database recognice also this
				$obj->setInTableNewColumn($tableName, $columnName, $type);
			}
		}
		STObjectContainer::setInTableNewColumn($tableName, $columnName, $type);
	}
	function setInTableColumnNewFlags($tableName, $columnName, $flags)
	{
		$objs= &STBaseContainer::getAllContainer();
		foreach($objs as $containerName=>$obj)
		{
			if(	$containerName!==$this->name
				and
				!typeof($obj, "STDatabase")	)
			{// if the container is an other database
			 // the container can not have this table
			 // because if do, the other database recognice also this
				$obj->setInTableColumnNewFlags($tableName, $columnName, $flags);
			}
		}
		STObjectContainer::setInTableColumnNewFlags($tableName, $columnName, $flags);
	}
	function make_insertString($table, $post_vars= null)
	{
		Tag::deprecated("STDatabase::getInsertStatemant($table, $post_vars)", "STDatabase::make_insertString($table, $post_vars");
		return $this->getInsertStatement($table, $post_vars);
	}
	function getInsertStatement($table, $values= null)
	{
		$key_string= "";
		$value_string= "";
		$result= $this->make_sql_values($table, $values);
		$fields= $this->read_inFields($table, "type");
		$flags= $this->read_inFields($table, "flags");
		if(typeof($table, "STDbTable"))
			$table= $table->getName();

        foreach($result as $key => $value)
		{
			$auto_increment= preg_match("/auto_increment/i", $flags[$key]);
			if(	$key_string!=""
				and
				!$auto_increment	)
			{
				$key_string.= ",";
				$value_string.= ",";
			}
			if(!$auto_increment)
    	   		$key_string.= $key;
			if(	$fields[$key]!="int"
				and
				!preg_match("/now()/i", $value) &&
				!preg_match("/sysdate()/i", $value) &&
				!preg_match("/password(.*)/i", $value)	)
			{
				$value= "'".$value."'";
			}
			if(	$fields[$key]=="int"
				and
				(	$value===null
					or
					$value===""	)	)
			{
				$value= "null";
			}
			if(!$auto_increment)
    	   		$value_string.= $value;
		}
        $sql="INSERT INTO $table($key_string) VALUES($value_string)";
		return $sql;
	}
	function make_updateString($table, $where= "", $post_vars= null)
	{
		// toDo: delete function
		STCheck::deprecated("STDatabase::getUpdateStatement($table, $post_vars)", "STDatabase::make_insertString($table, $post_vars");
		return $this->getUpdateStatement($table, $where, $post_vars);
	}
	function getUpdateStatement($table, $where= "", $values= null)
	{
		Tag::paramCheck($table, 1, "STAliasTable", "string");
		Tag::paramCheck($where, 2, "STDbWhere", "string", "empty(string)", "null");

		$update_string= "";
		if(typeof($table, "STAliasTable"))
			$tableName= $table->getName();
		else
			$tableName= $table;
		$result= $this->make_sql_values($tableName, $values);
		if(!count($result))
			return null;
		$fields= $this->read_inFields($table, "type");
        foreach($result as $key => $value)
		{
			if($update_string!="")
				$update_string.= ", ";
			if(	$fields[$key]!="int"
				and
				!preg_match("/now\(\)/i", $value) &&
				!preg_match("/sysdate\(\)/i", $value) &&
				!preg_match("/password\(.*\)/i", $value))
			{
				if($value===null)
					$value= "null";
				else
					$value= "'".$value."'";
			}
			if(	$fields[$key]=="int"
				and
				(	$value===null
					or
					$value===""	)	)
			{
				$value= "null";
			}
        	$update_string.= $key."=".$value;
		}
        $sql="UPDATE $tableName set $update_string";

		if(is_string($table))
			$table= $this->getTable($table);
		if($where)
		{
			// alex 03/08/2005:	gib where-class in Tabelle
			//$oTable= new STDbTable($table, $this);
			//$bModify= $oTable->modify();
			//$oTable->modifyForeignKey(false);
			$table->where($where);
			//$oTable->modifyForeignKey($bModify);
		}
		$where= $this->getWhereStatement($table, "");
		if($where!="")
		{
			if(preg_match("/^(and|or)/i", $where, $ereg))
			{
				if($ereg[1] == "and")
					$where= substr($where, 4);
				else
					$where= substr($where, 3);
			}
			$sql.= " where $where";
		}
		STCheck::echoDebug("db.main.statement", $sql);
		return $sql;
	}
	function read_inFields($table, $type)
	{
		$fields= $this->describeTable($table);
		$count= 0;
		$aRv= array();
		foreach($fields as $field)
		{
			$aRv[$count]= $field[$type];
			$aRv[$field["name"]]= $field[$type];
			$count++;
		}
		return $aRv;
	}
	function make_sql_values($table, $post_vars)
	{
		if(!$post_vars)
			return array();
		$fields= $this->describeTable($table);//hole Felder aus Datenbank

		$aRv= array();
		foreach($fields as $field)
		{
			$name= $field["name"];
			if(array_key_exists($name, $post_vars))
       			$aRv[$name]= $post_vars[$name];
		}
		return $aRv;
	}
	function list_tables($onError= onErrorStop)
	{
		if($this->dbType=="BLINDDB")
			return array();
		$tables= $this->fetch_single_array("show tables from ".$this->dbName, $onError);
		$this->tableNames= $tables;
		return $tables;
	}
	abstract protected function list_dbtable_fields($TableName);
	function list_fields($TableName, $onError= onErrorStop)
	{
		Tag::paramCheck($TableName, 1, "string");
		if($this->dbType=="BLINDDB")
			return;
		Tag::echoDebug("db.statement", "list_fields in DB ".$this->dbName." from Table ".$TableName);
		if(preg_match("/([^\.]*)\.(.*)/", $TableName, $preg))
		{
			$dbName= $preg[1];
			$TableName= $preg[2];
		}else
			$dbName= $this->dbName;
		$result= $this->list_dbtable_fields($TableName);
		if( !$result
			&&
			$onError > noErrorShow )
 		{
			echo "can not read fields in table <b>".$TableName."</b> from database <b>";
			echo $this->dbName."</b>,<br>";
 			echo "<b>ERROR".$this->errno().":</b><br>";
 			echo "<b>MySql:</b> ".$this->error();
			if($onError==onErrorStop)
				exit();
		 	return null;
 		}
		return $result;
	}
	function withSelector($selector, $limit)
	{
		$columns= $selector->getColumns();
		foreach($columns as $column)
		{
			if(isset($statement))
				$statement.= ",";
			$statement.= $column;
		}
		$statement= "select ".$statement;
		$statement.= " from ".$selector->getName();
		if(isset($limit))
			$statement.= " limit ".$limit;
		$aRv= $this->fetch_array($statement, $selector->getSelectTyp(), $selector->getOnErrorTyp());
		return $aRv;
	}
	function isError()
	{
		return $this->error;
	}
    function getForeignKeyModification($oTable)
	{
    	$stget= new STQueryString();
		$stget= $stget->getArrayVars();
		if(isset($stget["stget"]))
			$stget= $stget["stget"];
		else
			$stget= array();

		$aRv= array();
		$fks= $oTable->getFKs();
		foreach($fks as $table=>$aColumnType)
    	{
    		if(isset($stget[$table]))//[$aColumnType["other"]]
				$aRv[$table]= $aColumnType;

		}
		return $aRv;
	}
    function foreignKeyModification(&$oTable)
    {
		Tag::paramCheck($oTable, 1, "STDbTable");

    	if(!$oTable->modify())
    	{
    		STCheck::echoDebug("db.statements.where", "do not need foreign Key modification".
    														" inside table ".$oTable->getName().
    														" from container ".$oTable->container->getName());
    		return;
    	}
    	STCheck::echoDebug("db.statements.where", "need foreign Key modification".
    													" inside table ".$oTable->getName().
    													" from container ".$oTable->container->getName());
    	
    	$stget= new STQueryString();
		$stget= $stget->getArrayVars();
		if(	isset($stget["stget"])	)
		{
			$stget= $stget["stget"];
		}else
			$stget= array();
		$nIntented= Tag::echoDebug("db.statements.where", "modify foreign Keys in table ".$oTable->getName());
    	$where= new STDbWhere();
    	$fks= $this->getForeignKeyModification($oTable);
		if(Tag::isDebug("db.statements.where"))
		{
			for($n= 0; $n < $nIntented; ++$n)
				echo " ";
			echo "<b>[</b>db.statements.where<b>]</b> need foreign Keys from quiry: ";
			st_print_r($fks, 2, 55);
			//if(!count($fks))
				echo "<br />";
		}
    	foreach($fks as $table=>$aColumnType)
    	{
      		$limitation= $stget[$table];
      		foreach($limitation as $column=>$value)
      		{
    			if($aColumnType["other"]==$column)
    			{
      				$clausel= $aColumnType["own"]."=";
      				if(!is_numeric($value))
      					$value= "'".$value."'";
      				$clausel.= $value;

      				$where->andWhere($clausel);
    			}else
				{
					$clausel= $column."=";
      				if(!is_numeric($value))
      					$value.= "'".$value."'";
      				$clausel.= $value;
					$iWhere= new STDbWhere($clausel);
					$iWhere->forTable($table);
					$where->andWhere($iWhere);
				}
				Tag::echoDebug("db.statements.where", "set where $clausel from FK");
      		}
    	}
		if($oTable->bLimitOwn)
		{
    		$tableName= $oTable->getName();
    		if(	isset($stget[$tableName]) &&
    			is_array($stget[$tableName])	)
    		{
    			foreach($stget[$tableName] as $column=>$value)
    			{
					if($oTable->haveColumn($column))
					{
    					if(!is_numeric($value))
    						$value= "'".$value."'";
						Tag::echoDebug("db.statements.where", "set where $column=$value for own table");
    					$where->andWhere($column."=".$value);
					}
    			}
    		}
		}
    	if($where->isModified())
    	{
    		$iWhere= $oTable->getWhere();
    		if($iWhere)
    			$where->andWhere($iWhere);
    		$oTable->where($where);
    	}
		// da die Tabelle kein zweites mal Modifiziert werden soll
		// setze bModifyFk in der Tabelle auf false
		$oTable->allowQueryLimitation(false);
		Tag::echoDebug("db.statements.where", "end of foreignKeyModification for table ".$oTable->getName());
    }
	function getWhereStatement($oTable, $aktAlias, $aliases= null)
	{		
		STCheck::paramCheck($oTable, 1, "STDbTable");

		$statement= "";
		$ostwhere= $oTable->getWhere();
		if(typeof($ostwhere, "STDbWhere"))
			$statement= $ostwhere->getStatement($oTable, $aktAlias, $aliases);
		return $statement;
	}
	function isSqlSelectKeyWord($column, $needInherit= false)
	{
		if(!preg_match("/^(now\(|sysdate\(|password\(|count\(|max\(|min\()(.*)(\))$/i", $column, $preg))
			return false;
		if(!$needInherit)
			return (true);
		$split= preg_split("/[ ,]/", $preg[2]);
		$inherit= array();
		foreach($split as $column)
		{
			if($column)
				$inherit[]= $column;
		}
		$keyWord= substr($preg[1], 0, strlen($preg[1])-1);
		$aRv= array("keyWord"=>$keyWord, "inherit"=>$inherit);
		return $aRv;
	}
	/**
	 * remove columns if they are not in the database table
	 */
	function removeNoDbColumns(&$aNeededColumns, $aAliases)
	{
		$nParams= func_num_args();
		STCheck::lastParam(2, $nParams);

		/*$exist= array();
		// set into array variable $exist all exists column
		foreach($oTable->columns as $content)
		{
			if($content["db"]!="alias")
			{
				$column= $content["name"];
				$exist[$column]= true;
			}
		}*/
		if(count($aAliases)>1)
			$bNeedAlias= true;
		else
			$bNeedAlias= false;
		$needetTables= array();
		foreach($aNeededColumns as $nr=>$content)
		{
			$column= $content["column"];
			$inherit= $this->isSqlSelectKeyWord($column, true);
			if($inherit)
			{
				$columnString= "";
				foreach($inherit["inherit"] as $col)
				{
					if(	$col=="distinct"
						or
						$col=="*")
					{
						$columnString.= $col;
						if($col!="*")
							$columnString.= " ";
					}else
					{
						if(!$needetTables[$content["table"]])
							$needetTables[$content["table"]]= &$this->getTable($content["table"]);
						if($needetTables[$content["table"]]->columnExist($col))
						{// if exists name in table
							if($bNeedAlias)
								$columnString.= $aAliases[$content["table"]].".";
							$columnString.= $col.",";
						}else
						{
							if(preg_match("/^([^.]+)\.([^.]+)$/", $col, $preg))
							{
								$table= &$this->getTable($preg[1]);
								if(	$table
									and
									$table->columnExist($preg[2])	)
								{
									$columnString.= $aAliases[$preg[1]].".";
									$columnString.= $preg[2].",";
								}
								unset($table);
							}
						}
					}
				}
				if($column!="*")
					$columnString= substr($columnString, 0, strlen($columnString)-1);
				if($columnString=="")
					$columnString= "*";
				$aNeededColumns[$nr]["column"]= $inherit["keyWord"]."(".$columnString.")";
			}else
			{				
				if(!isset($needetTables[$content["table"]]))
					$needetTables[$content["table"]]= &$this->getTable($content["table"]);
				if( !$needetTables[$content["table"]]->columnExist($column)
					and
					$column!="*"	)
				{
					// alex 19/09/2005:	f�ge statdessen eine null column ein
					$aNeededColumns[$nr]["column"]= "(null)";
				}
			}
		}
	}
	// alex 24/05/2005:	boolean bMainTable auf die Haupttabelle ge�ndert
	//					da dort wenn sie ein Selector ist noch �berpr�fungen
	//					vorgenommen werden sollen, ob der FK auch ausgef�hrt wird
	/*private*/function getSelectStatement(&$oTable, &$aTableAlias, $oMainTable, $withAlias)
	{															// wenn kein Alias-Name herein kommt,
		$bMainTable= false;		
		$statement= "";								// muss es die Haupttabelle sein
		if(	$this->bFirstSelectStatement	// alex 27/05/2005:	wenn die Haupttabelle hereinkommt
			and								//					ist es jedoch auch m�glich dass es ein FK
			$oMainTable!==false				)//					von der eigenen Tabelle ist
		{									// alex 24/05/2005:	es kommt maximal false herein
											// wenn die Funktion sowiso nicht f�r die Haupttabelle ist
			if($oTable->getName()==$oMainTable->getName())
				$bMainTable= true;
		}
		if($bMainTable)
		{
			$aNeededColumns= $oTable->getSelectedColumns();
//		echo __file__.__line__."<br>";
			//echo "get from selected columns (show)<br>";
		}else
		{
			$aNeededColumns= $oTable->getIdentifColumns();
//		echo __file__.__line__."<br>";
			//echo "get from identif columns<br>";
		}
			// alex 29/07/2005:	durchsuche ob auch eine column
			//					nur zur Verarbeitung gebraucht wird.
			//					Nur wenn oTable nicht vom OSTDbSelector ist
			//if(!typeof($oTable, "OSTDbSelector"))
			//{
/*		echo __file__.__line__."<br>";
		echo "_Test:";
		st_print_r($aNeededColumns,2);*/	
		$aColumns= array();
		$aGetColumns= array();
		// put only all getColumns into array aGetColumns
		foreach($oTable->showTypes as $column=>$extraField)
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
				$aNewColumn["table"]= $oTable->getName();
				$aNewColumn["column"]= $column;
				$alias= $column;
				if(!$bMainTable)// maybe two getColumns are the same from
				{				// the main-table and sub-table
					$alias= $oTable->getName()."@".$alias;
				}
				$aNewColumn["alias"]= $alias;
				$aNeededColumns[]= $aNewColumn;
			}
		}

		STCheck::flog("create select statement");
		$this->removeNoDbColumns($aNeededColumns, $aTableAlias);
		if(Tag::isDebug("db.statements.select"))
		{//st_print_r($oTable->identification,2);
			echo "<b>[</b>db.statements.select<b>]:</b> make select statement";
			if(!$bMainTable)
				echo " from identifColumns";
			echo " in ";
			if($bMainTable)
				echo "main-Table ";
			echo get_class($oTable)."/";
			$tableName= $oTable->getName();
			$displayName= $oTable->getDisplayName();
			echo "<b>".$tableName."(</b>".$displayName."<b>)</b>";
			echo " from container:<b>".$oTable->container->getName()."</b>";
			echo " for columns:<pre>";
			st_print_r($aNeededColumns, 2);
			echo "</pre>";

			/*$msg= "<b>[</b>db.statements.select<b>]:</b> make Select Statement";
			if(!$bMainTable)
				$msg+= " from identifColumns";
			$msg+= " in ";
			if($bMainTable)
				$msg+= "main-Table ";
			$msg+= get_class($oTable)."/";
			$tableName= $oTable->getName();
			$displayName= $oTable->getDisplayName();
			$msg+= "<b>".$tableName."(</b>".$displayName."<b>)</b>";
			$msg+= " from container:<b>".$oTable->container->getName()."</b>";
			$msg+= " for columns:<pre>";
			//STCheck::echoDebug("db.statement.select", $msg);
			echo msg;*/
		}
		$aShowTypes= $oTable->showTypes;
		/*if(typeof($oTable, "OSTDbSelector"))
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
			$columnAlias= "'".$column["alias"]."'";
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
				$table= $oTable->getFkTableName($column["column"]);
				if($table)
					STCheck::echoDebug("db.statements.select", "from ".get_class($oTable)." ".$oTable->getName()." for column ".$columnname." is Fk-Table \"".$table."\"");
				if(	$table
					and
					typeof($oMainTable, "OSTDbSelector")	)
				{
					// alex 24/05/2005:	wenn die Tabelle im FK existiert
					//					und die Aktuelle Tabelle ein Selector ist,
					//					dann ueberpruefe noch ob sie auch im Selector existiert.
					//					sonst soll keine Ausgabe der verknuepften Tabelle ausgegeben werden
					if(!$oMainTable->getTable($table))
						$table= null;
				}
				if(	!$table // wenn keine Tabelle im FK ist kann die Spalte nur von der Aktuellen-Tabelle sein
					or
					//$isSelector
					//or
					isset($aShowTypes[$column["alias"]]))
				{
					$aliasTable= $aTableAlias[$column["table"]];
					if(	!$this->bFirstSelectStatement
						and
						$aliasTable=="t1"	)
					{
						$aliasTable= "t".($aliasCount+1);
						$aTableAlias["self.".$column["table"]]= $aliasTable;
					}
					$this->sNeedAlias[$aliasTable]= "need";// Tabellen Alias wird gebraucht
					if($this->isSqlSelectKeyWord($column["column"]))
						$statement.= $columnName;
					else
						$statement.= "`".$aliasTable."`.$columnName";
   					if(	isset($column["alias"])
						and
						$column["column"]!=$column["alias"]
						and
						$withAlias							)
   					{
						$statement.= " as $columnAlias";
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
					Tag::echoDebug("db.statements.select", "");//Bitte nicht l�schen
					//$oOther= $oTable->FK[$table]["table"]->container->getTable;
					$containerName= $oTable->getFkContainerName($column["column"]);
					$container= $this->getContainer($containerName);
					/*if(!$oOther)// wenn $oOther deffiniert ist, ist die Tabelle von einer anderen Datenbank
					{*/
				/*		if(typeof($oMainTable, "OSTDbSelector"))
						{
							$oOther= $oMainTable->getTable($table);
						}else
						{
							$oOther= $container->getTable($table);
						}
					}else
					{
						if(typeof($oMainTable, "OSTDbSelector"))
						{// if the table from FK in the OSTDbSelector:$oMainTable
						 // take it from this one
							$other=  $oMainTable->getTable($table);
							if($other)
								$oOther= $other;
						}else
						{// take table from database, not from FK
							$fktableName= $oOther->getName();
							$container= &$this->getContainer();
							if($oOther->db->dbName!=$container->db->dbName)
								$container= $oOther->db;
							$oOther= $container->getTable($fktableName);
						}
					}*/
					// 13/06/2008:	alex
					//				fetch table from own table because
					//				if own table is an DBSelector maype only
					//				in him can be deleted an column or identif-column
					$oOther= $oTable->getTable($table);
					$bFirstSelectStatement= $this->bFirstSelectStatement;
					$this->bFirstSelectStatement= false;
					$fkStatement= $this->getSelectStatement($oOther, $aTableAlias, $oMainTable, $withAlias);
					if(!$fkStatement)
					{// is no columns selected in the FK-table
					 // delete the last comma
						$statement= substr($statement, 0, strlen($statement)-1);
					}else
						$statement.= $fkStatement;
					$this->bFirstSelectStatement= $bFirstSelectStatement; // ob im ersten aufruf, zur�cksetzen
					Tag::echoDebug("db.statements.select", "back in Table <b>".$oTable->getName()."</b>");
				}
			}else
			{

				$statement.= $columnName;
				if(	isset($column["alias"])
					and
					$column["column"]!=$column["alias"]
					and
					$withAlias							)
				{
					$statement.= " as $columnAlias";
				}
			}
			if($statement)
				$statement.= ",";
		}
		$statement= substr($statement, 0, strlen($statement)-1);
		Tag::echoDebug("db.statements.select", "createt String is \"".$statement."\"");
		return $statement;
	}
	//var $counter= 1;
	function getTableStatement($oMainTable, $tableName, &$aTableAlias, &$maked, $bMainTable)
	{
		$statement= "";
		if($oMainTable->getName()!==$tableName)
			$oTable= &$oMainTable->getTable($tableName);
		else
			$oTable= &$oMainTable;
		if($bMainTable)
			$aNeededColumns= $oTable->getSelectedColumns();
		else
			$aNeededColumns= $oTable->getIdentifColumns();
		if(Tag::isDebug("db.statements.table"))
		{
			echo "<b>[</b>db.statements.table<b>]</b> needed columns for table ";
			echo $oTable->getName()."<br /><pre>";
			st_print_r($aNeededColumns, 2);
			echo "</pre><br />";
		}
		$ownTableAlias= $aTableAlias[$oTable->getName()];
		$mainWhereStatement= "";
		if($bMainTable)
		{// need the where-clausl for the second on-clausl
			$mainWhereStatement= $this->getWhereStatement($oTable, $ownTableAlias, $aTableAlias);
			//echo "mainTableWhereStatement:$mainWhereStatement<br />";
			if(trim($mainWhereStatement))
			{
				preg_match("/^(and|or) (\\\\())?/i", $mainWhereStatement, $ereg);
				if($ereg != "(")
				{
					if($ereg[1] == "and")
						$mainWhereStatement= "and (".substr($mainWhereStatement, 4).")";
					else
						$mainWhereStatement= "or (".substr($mainWhereStatement, 3).")";
				}
			}
			//echo "mainWhere:$mainWhereStatement<br />";
		}

	$fk= $oTable->getForeignKeys();
	foreach($fk as $table=>$content)
	{
		foreach($content as $join)
		{
			$sTableName= $oTable->getName();
			$bNeedColumn= false;
			foreach($aNeededColumns as $aColumn)
			{
				if(	$join["own"]==$aColumn["column"]
					and
					!isset($maked[$table])				)
				{// Abfrage ob die Spalten mit FK auch ben�tigt werden
					$bNeedColumn= true;
					break;
				}
			}
			if(	$bNeedColumn===false
				and
				isset($aTableAlias[$table])
				and
				!isset($maked[$table])
				and
				typeof($oTable, "OSTDbSelector"))
			{// wenn die FK Spalte nicht in den ben�tigten Spalten ist,
			 // das objekt aber vom Typ OSTDbSelector ist (wobei die FK-Spalten nicht aufgelistet werden)
			 // und die Tabelle in den Aliases ist,
			 // wird sie doch f�r den join ben�tigt
			 	$bNeedColumn= true;
			}
			if(Tag::isDebug("db.statements.table"))
			{
				if($bNeedColumn===false)
				{
					$debugString= "do not need foreign key from column ".$join["own"]." to table ".$table." for statement";
					Tag::echoDebug("db.statements.table", $debugString);
					if(isset($maked[$table]))
						Tag::echoDebug("db.statements.table", "join was inserted before");
				}elseif(!isset($aTableAlias[$table]))
					Tag::echoDebug("db.statements.table", "no such table $table for column ".$join["own"]." in createt Alias-Array");
				else
				{
					Tag::echoDebug("db.statements.table", "need foreign key from column ".$join["own"]." to table ".$table.
												" for select statement from container ".$oTable->container->getName());
				}
			}
			if(	$bNeedColumn
				and
				isset($aTableAlias[$table])	)
			{
 				if($join["join"]=="outer")
 					$statement.= " left";
				else
					$statement.= " ".$join["join"];

				$database= null;
				if(isset($aTableAlias["db.$table"]))
					$database= $aTableAlias["db.$table"];
				if($database) 	// wenn im aTableAlias Array eine Datenbank angegeben ist, die fremde DB
				{				// von dieser auch im statement angeben
					Tag::echoDebug("db.statements.table", "table is for database ".$database);
					$database.= ".";
				}


				if(isset($maked[$table]))						// wenn der join innerhalb der gleichen Tabelle ist
				{												// darf der AliasName nicht der gleiche sein
					$sTableAlias= $aTableAlias["self.".$table];	// (zb. t1.parentID=t1.ID) weil die eigene Tabelle
					if(!isset($sTableAlias))					// nochmals im Join angegeben werden muss
					{											// also zb. t1.parentID=t5.ID
						$sTableAlias= "t".(count($aTableAlias)+1);
						$aTableAlias["self.".$table]= $sTableAlias;
					}
				}else
					$sTableAlias= $aTableAlias[$table];


				if(Tag::isDebug())
				{
					$joinArt= $join["join"];
					if($joinArt==="outer")
						$joinArt= "left";
					Tag::echoDebug("db.statements.table", "make ".$joinArt." join to table ".$database.$table.
												" with alias-name ".$sTableAlias);
				}
  				$statement.= " join ".$database.$table." as ".$sTableAlias;
  				$statement.= " on ".$ownTableAlias.".".$join["own"];
  				$statement.= "=".$sTableAlias.".".$join["other"];
				//echo "Statement:".$statement."<br />";
				$fromTable= $join["table"];// wenn die Tabelle von einer anderen DB kommt, steht sie im $join vom foreach des $oTable->FK
				if(	$fromTable->container->db->getName() == $oMainTable->container->db->getName() &&
					$fromTable->container->getName() != $oMainTable->container->getName()			)
				{
					$fromTable= $oMainTable->container->getTable($fromTable->getName());
				}
				if(!$fromTable)// sonst muss sie erst aus der aktuellen DB geholt werden
					$fromTable= $oTable->getTable($table);
				//echo "table:".$fromTable->getName()."<br />";
				//st_print_r($fromTable->oWhere,10);
				$whereStatement= $this->getWhereStatement($fromTable, $sTableAlias, $aTableAlias);
				if($mainWhereStatement)
				{
					//if($whereStatement)
					//	$whereStatement= " and ";
					$whereStatement.= $mainWhereStatement;
					$mainWhereStatement= "";
				}
				//echo "where:".$whereStatement."<br />";
				if($whereStatement)
					$statement.= " ".$whereStatement;

				if(!isset($maked[$table]))
				{// debug-Versuch f�r komplikationen -> bitte auch member-Variable counter am Funktionsanfang aktivieren
				 //			if($this->counter==4){
				 //				echo "$statement OK".$this->counter;exit;}else $this->counter++;

					$maked[$table]= "finished";
					$statement.= $this->getTableStatement($oMainTable, $table, $aTableAlias, $maked, false);
					Tag::echoDebug("db.statements.table", "back in table <b>".$oTable->getName()."</b>");
 					/*if(isset($join["table"]))
 					{// take table from database, not from join
						$jointableName= $join["table"]->getName();
						$container= $this->getContainer();
							if($join["table"]->db->dbName!=$container->db->dbName)
								$container= $join["table"]->db;
						$nextTable= $container->getTable($jointableName);
 						$statement.= $this->getTableStatement($oMainTable, $nextTable, $aTableAlias, $maked, false);
 					}else
					{// take table from database, not from database
						$container= &$this->getContainer();
 						$nextTable= $container->getTable($table);
						$statement.= $this->getTableStatement($nextTable, $aTableAlias, $maked, false);
					}*/
				}
			}// end of if(	$bNeedColumn && isset($aTableAlias[$table])	)
   		}//end of foreach($content)
	}// end of foreach($fk)

		//look for tables which have an BackJoin
		foreach($oTable->aBackJoin as $sBackTableName)
		{
			if(!isset($maked[$sBackTableName]))
			{
				$maked[$sBackTableName]= "finished";
    			$BackTable= &$oTable->getTable($sBackTableName);
    			Tag::echoDebug("db.statements.table", "need backward from table $tableName to table $sBackTableName from container ".$BackTable->container->getName());
    			$sTableAlias= $aTableAlias[$sBackTableName];
    			$dbName= $BackTable->db->dbName;
    			$database= "";
    			$fks= $BackTable->getForeignKeys();
    			$join= $fks[$tableName][0];
				Tag::warning(!$join, "STDatabase::getStatement()", "no foreign key be set from backward table $sBackTableName to table $tableName");
    			if($join)
    			{
    				if($dbName!==$this->dbName)
    					$database= $dbName.".";
					$joinArt= $join["join"];
					if($joinArt==="outer")
						$joinArt= "right";
                    $statement.= " ".$joinArt." join ".$database.$sBackTableName." as ".$sTableAlias;
                    $statement.= " on ".$ownTableAlias.".".$join["other"];
                    $statement.= "=".$sTableAlias.".".$join["own"];
					if(Tag::isDebug())
					{
						Tag::echoDebug("db.statements.table", "make ".$joinArt." join to table ".$database.$sBackTableName.
													" with alias-name ".$sTableAlias);
					}
                    //$fromTable= $join["table"];// wenn die Tabelle von einer anderen DB kommt, steht sie im $join vom foreach des $oTable->FK
                    //if(!$fromTable)// sonst muss sie erst aus der aktuellen DB geholt werden
                    //$fromTable= $oTable->getTable($table);
                    //echo "table:".$fromTable->getName()."<br />";
                    //st_print_r($fromTable->oWhere,10);
                    $whereStatement= $this->getWhereStatement($BackTable, $sTableAlias, $aTableAlias);
                    if($mainWhereStatement)
                    {
                    	//if($whereStatement)
                    	//	$whereStatement= " and ";
                    	$whereStatement.= $mainWhereStatement;
                    	$mainWhereStatement= "";
                    }
                    //echo "where:".$whereStatement."<br />";
                	if($whereStatement)
                		$statement.= " ".$whereStatement;
    				$statement.= $this->getTableStatement($oMainTable, $sBackTableName, $aTableAlias, $maked, false);
    			}// end of if(!Tag::warning(!$join))
				unset($BackTable);
			}// end of if($join)
		}// end of foreach($oTable->aBackJoin
		return $statement;
	}
	function getReachedTables($table, $reached= null)
	{
		Tag::paramCheck($table, 1, "STDbTable");
		Tag::paramCheck($reached, 2, "array", "null");

		if(!$reached)
			$reached= array();
		$tableName= $table->getName();
		$reached[$tableName]= true;
		$fks= &$table->getForeignKeys();
		foreach($fks as $tableName=>$content)
		{
			if(!isset($reached[$tableName]))
			{
				$fkTable= $table->getTable($tableName);
				$reached= $this->getReachedTables($fkTable, $reached);
			}
		}
		return $reached;
	}
	function getUnreachedAliases($aliases, $table)
	{
		$tableName= $table->getName();
		unset($aliases[$tableName]);

		$fks= &$table->getForeignKeys();
		foreach($fks as $tableName=>$content)
		{
			if(isset($aliases[$tableName]))
			{
				$fkTable= $table->getTable($tableName);
				$aliases= $this->getUnreachedAliases($aliases, $fkTable);
			}
		}
		return $aliases;
	}
	function getAliases($oTable, $bFromIdentifications= false)
	{
		//$container= &$this->getContainer();
		$sMainTableName= $oTable->getName();
		$aliasTables= array();
		$aliasTables[$sMainTableName]= "t1";
		$count= 2;
		if($bFromIdentifications)
		{
			$showList= $oTable->getIdentifColumns();
			if(Tag::isDebug("db.statements.aliases"))
			{
				Tag::echoDebug("db.statements.aliases", "need columns from table ".$sMainTableName." (->getIdentifColumns) where container is ".$oTable->container->getName());
				st_print_r($showList, 2);
			}
		}else
		{
			$showList= $oTable->getSelectedColumns();
			if(Tag::isDebug("db.statements.aliases"))
			{
				Tag::echoDebug("db.statements.aliases", "need columns from maintable ".$sMainTableName." (->getSelectedColumns) where container is ".$oTable->container->getName());
				st_print_r($showList, 2);
			}
		}
		foreach($showList as $column)
		{//z�hle wieviel Tabellen ben�tigt werden

			Tag::echoDebug("db.statements.aliases", "need column ".$column["column"]);

			//$table= $oTable->getFkTableName($column["column"]);
			//echo "foreignKey Table is $table<br />";
			//if(!$table)
			$table= $column["table"];

			if(!isset($aliasTables[$table]))
			{
				$aliasTables[$table]= "t".count($aliasTables);
			}
			$otherTable= $oTable->getFkTable($column["column"]);
			if($otherTable)
			{
				$fktableName= $otherTable->getName();
				Tag::echoDebug("db.statements.aliases", "column ".$column["column"]." in container ".$otherTable->container->getName().", have an foreign key to table $fktableName");
				/*if(isset($oTable->FK[$table]["table"]))
				{// table in other database
					//$otherTable= $oTable->FK[$table]["table"]->db->getTable($table);
					//if($oTable->FK[$table]["table"]->db->getName()!==
					$otherTable= $oTable->getFkTable($column["column"]);
					echo "newTable containerName:".$otherTable->container->getName()."<br />";
					//$otherTable= $oTable->FK[$table]["table"]->container->getTable($oTable->FK[$table]["table"]->getName());
				}else
				{
					$otherTable= $this->getTable($table);
				}
				// take table from container, not from FK
				$fktableName= $otherTable->getName();
				if($otherTable->db->dbName!=$container->db->dbName)
					$container= $otherTable->db;
				//$otherTable= $container->getTable($fktableName);*/

				if(!isset($aliasTables[$fktableName]))
				{
  					if( !isset($aliasTables["db.".$otherTable->getName()])
  						and
  						$otherTable->db->getDatabaseName()!=$oTable->db->getDatabaseName()	)
  					{
  						$aliasTables["db.".$otherTable->getName()]= $otherTable->db->getDatabaseName();
  					}
					$otherAliasTables= $oTable->db->getAliases($otherTable, true);
					//if(Tag::isDebug("db.statements.aliases"))
					//	if($otherAliasTable)
					STCheck::echoDebug("db.statements.aliases", "be back in table ".$sMainTableName);
					STCheck::flog("search t[x] alias for table $column");
  					foreach($otherAliasTables as $aliasTable=>$value)
  					{
    					if(!preg_match("/^db\./", $aliasTable))
    					{
							if(!isset($aliasTables[$aliasTable]))
							{
    							$aliasTables[$aliasTable]= "t".$count;
    							$count++;
							}
    					}else
						{
							if(!isset($aliasTables[$aliasTable]))
   							$aliasTables[$aliasTable]= $otherAliasTables[$aliasTable];
						}
  					}
				}
			}
		}
		if(!$bFromIdentifications)
			$this->searchAliasesInWhere($oTable, $aliasTables);
		if(Tag::isDebug("db.statements.aliases"))
		{
			if(1)//!$bFromIdentifications)
			{
				echo "<b>[</b>db.statements.aliases<b>]</b> need tables:<br /><pre>";
				print_r($aliasTables);
				echo "</pre><b>[</b>db.statements.aliases<b>]</b>";
				echo " end of function <b>getAliases()</b><br /><br />";
			}
		}
		return $aliasTables;
	}
	function getTableStructure($container, $bFromAll= false)
	{
		Tag::paramCheck($container, 1, "STObjectContainer");
		Tag::paramCheck($bFromAll, 2, "bool");

		if(	isset($this->aTableStructure[STALLDEF]) &&
			$this->aTableStructure[STALLDEF]["fromAll"]===true	)
		{// if STALLDEF is false and also bFromAll is false,
		 // do search again, because maybe an new table exists
			return $this->aTableStructure["struct"];
		}
		if(	$bFromAll===false &&
			(	!isset($this->aTableStructure[STALLDEF]) ||
				$this->aTableStructure[STALLDEF]["fromAll"]===false	)	)
		{
			$bSearch= false;
    		foreach($this->asExistTableNames as $tableName)
    		{
				if(	!isset($this->aTableStructure[STALLDEF]["in"][$tableName])
					and
					$this->haveTable($tableName)								)
				{// if an new table founded in tableName list
				 // search again
					$bSearch= true;
					// search for all tables again
					unset($this->aTableStructure);
					break;
				}
    		}
			if(!$bSearch)
				return $this->aTableStructure["struct"];
		}
		$this->aTableStructure[STALLDEF]["fromAll"]= $bFromAll;
		$aktDeepRef= array();
		$aHaveFks= array();
		$deep= 0;

		if(!isset($this->aTableStructure["struct"]))
			$this->aTableStructure["struct"]= array();
		foreach($this->asExistTableNames as $tableName)
		{
    		$bGetTable= true;
    		if(!$bFromAll)
    		{
    			if(!$container->haveTable($tableName))
    				$bGetTable= false;
    		}
    		if($bGetTable)
    		{
    			$fromTable= $container->getTable($tableName);
				// the table-name is lower case
				// so get the real one from the fromTable
				$tableName= $fromTable->getName();
        		$this->aTableStructure["struct"][$tableName]= array();
				$this->aTableStructure[STALLDEF]["in"][$tableName]= true;
				if(count($fromTable->aFks))
				{
					$aHaveFks[$tableName]= true;
        			foreach($fromTable->FK as $fromTableName=>$toColumn)
        				$this->aTableStructure["struct"][$tableName][$fromTableName]= array();
				}
    		}
		}
		foreach($this->aTableStructure["struct"] as $tableName=>$fkTables)
		{
			if($tableName!=STALLDEF)
				$this->searchTableStructure($tableName, $tableName, $aHaveFks);
		}
		if(	Tag::isDebug("db.statement")
			or
			Tag::isDebug("table")		)
		{
			if(Tag::isDebug("table"))
			{
				$nlength= 9;
				$debugName= "table";
			}else
			{
				$nlength= 16;
				$debugName= "db.statement";
			}

			$all= "all";
			if($this->aTableStructure[STALLDEF]["fromAll"]===false)
				$all= "exist";
			Tag::echoDebug($debugName, "<b>foreign Key</b> structure from <b>$all</b> tables in container ".$container->getName());
			echo "<b>[</b>$debugName<b>]</b>: ";
			st_print_r($this->aTableStructure["struct"],50, $nlength);
			echo "<br />";
		}
		return $this->aTableStructure["struct"];
	}
	function searchTableStructure($fromTableName, $rootTableName, $aHaveFks)
	{
		if(!isset($this->aTableStructure["struct"][$fromTableName]))
		{
			unset($this->aTableStructure["struct"][$fromTableName]);
			return;
		}
		$this->aTableStructure[STALLDEF]["in"][$fromTableName]= true;
		$fkTables= $this->aTableStructure["struct"][$fromTableName];
		foreach($fkTables as $inTableName=>$inFkTables)
		{
			if(	isset($this->aTableStructure["struct"][$inTableName])
				and
				$inTableName!=$rootTableName
				and
				$inTableName!=$fromTableName				)
			{
				$this->searchTableStructure($inTableName, $rootTableName, $aHaveFks);
				$this->aTableStructure["struct"][$fromTableName][$inTableName]= $this->aTableStructure["struct"][$inTableName];
				unset($this->aTableStructure["struct"][$inTableName]);
			}else
			{
				if(	$this->aTableStructure[STALLDEF]["in"][$inTableName] &&
					!count($this->aTableStructure["struct"][$fromTableName][$inTableName]) &&
					isset($aHaveFks[$inTableName]) &&
					$aHaveFks[$inTableName]														)
				{
					$this->aTableStructure["struct"][$fromTableName][$inTableName]= "before";
				}
			}
		}
	}
	// search for all tables in array beforeNeeded
	// how much tables they are reached in array aNeededTables
	// return an array with the same keys like beforeNeeded
	// and the value as number how much reached = array( [tablename]=>[number], ... )
	function getTableReachResults($structure, $aNeededTables, $beforeNeeded, $before)
	{
		STCheck::paramCheck($structure, 1, "array");
		STCheck::paramCheck($aNeededTables, 2, "array");
		STCheck::paramCheck($aNeededTables, 2, "check", (preg_match("/^t[0-9]+$/", key($aNeededTables))>0), "key(t*)=>value(tableName)");
		STCheck::paramCheck($beforeNeeded, 3, "array");
		STCheck::paramCheck($beforeNeeded, 3, "check", (preg_match("/^t?[0-9]+$/", current($beforeNeeded))>0), "key(tableName)=>value([t]*)");
		STCheck::paramCheck($before, 4, "bool");

		$aNeededTables2= $aNeededTables;
		$aRv= array();
		foreach($beforeNeeded as $tableName=>$content)
		{
			$resTableName= $tableName;
			if($before)
			{
				$struct= $this->getTableStructFromStructBefore($tableName, $structure);
				if($struct)
				{
					$resTableName= key($struct);
				}else
					$resTableName= "";
			}else
			{// search in first time the
				$struct= $this->getTableStructFromStruct($tableName, $structure);
			}
			if($resTableName)
			{
				$nReached= 0;
    			foreach($aNeededTables2 as $table)
    			{
    				$result= $this->getTableStructFromStruct($table, $struct, $structure);
    				if(	$result!==null	)
    				{
    					++$nReached;
    				}
    			}
    			$aRv[$resTableName]= $nReached;
			}
		}
		return $aRv;
	}
	/**
	 * returning the tablename from before table which can reach the first table in the tableList
	 *
	 * @param array $structure structure from foreign key connection of all tables in database
	 * @param array $tableList search connection from an table to this one,
	 * 							or if parameter is an string of one table name to this one
	 * @param string $toConnectTable variable give back to which the returned Table should connect.<br />
	 * 								 Variable can also be NULL
	 * @return string table name which reach all other
	 */
	function findConnectTable($structure, $tableList, &$toConnectTable)
	{
		STCheck::param($structure, 0, "array");
		STCheck::param($tableList, 1, "array", "string");
		STCheck::param($toConnectTable, 2, "string", "empty(string)", "null");

		echo "findConnectTable(\$structure, \$tableList, $\$toConnectTable)<br>";
		STCheck::write($structure, 5);
		STCheck::write($tableList, 5);
		if(is_string($tableList))
			$tableList= array($tableList=>true);
		if(	!count($structure)
			or
			!count($tableList)	)
		{
			STCheck::write("return -NULL-");
			return null;
		}
		if(is_array($structure))
		{
			foreach($structure as $tableName=>$fks)
			{
				foreach($fks as $fksTableName=>$other)
				{
					// if tableName is in the reached table
					// returne the tableName
					if(isset($tableList[$fksTableName]))
					{
						$toConnectTable= $fksTableName;
						STCheck::write("return $tableName");
						return $tableName;
					}
					$founded= $this->findConnectTable($fks, $tableList, $toConnectTable);
					if($founded)
					{
						STCheck::write("return $founded");
						return $founded;
					}
				}
			}
		}
		STCheck::write("return -NULL-");
		return null;
	}
	/**
	 * returning an single array with an tablename which reach all other tables from $aNeededTables
	 */
	function getFirstSelectTableNames($container, $aNeededTables)
	{
		Tag::paramCheck($container, 1, "STObjectContainer");
		Tag::paramCheck($aNeededTables, 2, "array");

		if(count($aNeededTables)===1)
			return $aNeededTables;
		$aFlipNeeded= array_flip($aNeededTables);
		//foreach($aNeedetTables as $tableName=>$content)
		//	$aNeedetTables[$tableName]= false;
		//$aFalseNeedet= $aNeedetTables;

		$structure= $this->getTableStructure($container);
		$sFirstStructTable= key($structure);

		$before= false;
		//$sFirstStructTable;
		// TODO: known bug: $sFuirstStructTable
		//				but if set right it makes troubles
		while(	count($aNeededTables)>1
				or
				$sFirstStructTable!==key($aNeededTables)	)
		{
    		$reached= $this->getTableReachResults($structure, $aFlipNeeded, $aNeededTables, $before);
    		$needetTables= count($aFlipNeeded);
    		foreach($reached as $tableName=>$count)
    		{
    			if($count===$needetTables)
    				return array($tableName);
    		}
    		$count= 0;
    		foreach($reached as $key=>$value)
    		{
    			$reached[$key]= $count;
    			++$count;
    		}
    		$aNeededTables= $reached;
			$before= true;
		}
		return array();
	}
		/**
		 * returning an recursive struct of tables
		 * where the table in the first array reach the given one
		 *
		 * @param string $aTableName name of the table
		 * @param array $structure reachable recursive struct of all tables in database
		 * @return array recursive array of the first table in array is an group table
		 */
		function getTableStructFromStructBefore($sTableName, $struct)
		{
			STCheck::param($sTableName, 0, "string");
			STCheck::param($struct, 1, "array");

			//echo "hole structure:";
			//st_print_r($struct, 10);
			//echo "search table struct from an table before ".$sTableName."<br>";
			foreach($struct as $tableName=>$content)
			{
				foreach($content as $table=>$content2)
				{
    				if($table==$sTableName)
    				{
    					//st_print_r($content,10);
    					$aRv= array($tableName=>$content);
    					//echo "return struct:";
    					//st_print_r($aRv, 10);
    					return $aRv;
					}
				}
				if(is_array($content))
				{
					$aRv= $this->getTableStructFromStructBefore($sTableName, $content);
					if($aRv!==null)
					{
    					//echo "return struct:";
    					//st_print_r($aRv, 10);
						return $aRv;
					}
				}
			}
			//echo "return null<br>";
			return null;
		}
		/**
		 * returning an recursive struct of tables where the first table
		 * raeach the given table name
		 *
		 * @param string $aTableName name of the table
		 * @param array $structure reachable recursive struct of all tables in database
		 * @return array recursive array of the first table in array is an group table
		 */
		function getStructureTableGroup($sTableName, $structure)
		{
			STCheck::param($sTableName, 0, "string");
			STCheck::param($structure, 1, "array");

			$count= 0;
			$created= $structure;
			while($created)
			{
				$lastStruct= $created;
				$created= $this->getTableStructFromStructBefore($sTableName, $structure);
				if($created)
					$sTableName= key($created);
				else
					if($count === 0) // if the incomming table name is self an grouptable
					{				 // returning only the struct of this table
									 // not the hole incomming structure
						if(isset($lastStruct[$sTableName]))							
							$aRv= array($sTableName=>$lastStruct[$sTableName]);
						else
							$aRv= array($sTableName=>array());
						return  $aRv;
					}
				++$count;
			}
			return $lastStruct;
		}
		/**
		 * returning an recursive struct of tables bounded with foreign keys
		 * where the first table is the given table name
		 *
		 * @param string $aTableName name of the table
		 * @param array $structure reachable recursive struct of all tables in database
		 * @return array recursive array of the first table in array is an group table
		 */
		function getTableStructFromStruct($sTableName, $struct)
		{
			if(!is_array($struct))
				return null;
			foreach($struct as $table=>$content)
			{
				if($table==$sTableName)
				{
					if(!is_array($content))
					{// if content is "before" begin again on the start
						return $content;
					}
					// otherwise returning founded table
					return array($table=>$content);
				}elseif(is_array($content))
				{
					$aRv= $this->getTableStructFromStruct($sTableName, $content);
					if($aRv!==null)
						return $aRv;
				}
			}
			return null;
		}
	function searchAliasesInWhere($oTable, &$aliasTables)
	{//echo "founded aliases:";st_print_r($aliasTables);
		Tag::echoDebug("db.statements.aliases", "search in where clausel");
		$where= $oTable->getWhere();
		//echo __file__.__line__;
		//st_print_r($where,10);
		//$newAliases= $this->getNewTables($where, $aliasTables);
		if(isset($where))
		{
			if(!isset($where->aValues))
				return;
			foreach($where->aValues as $tabName=>$content)
			{
				if(!isset($aliasTables[$tabName]))
				{
					$aliasTables[$tabName]= "t".(count($aliasTables)+1);}
			}
		}
		return;
	}
	/*function getNewTables($where, $aliasTables)
	{
		$newAliases= array();
		if(typeof($where, "STDbWhere"))
		{
			$tableName= $where->array["sForTable"];
			if(	$tableName
				and
				!isset($aliasTables[$tableName])	)
			{
				$newAliases[]= $tableName;
			}
			$new= $this->getNewTables($where->array, $aliasTables);
			if($new)
				$newAliases= array_merge($newAliases, $new);
		}elseif(is_array($where))
		{
			foreach($where as $content)
			{
				$new= $this->getNewTables($content, $aliasTables);
				if($new)
					$newAliases= array_merge($newAliases, $new);
			}
		}
		return $newAliases;
	}*/
	// alex 25/05/2005:	funktion nach STDbTable verschoben
	//					und in getFKTableName($fromColumn) umbenannt
	//					geh�rt ja auch dort hin
	/*function getTableFromFK($columnName, $aFK)
	{
		foreach($aFK as $table=>$columns)
		{
			if($columnName==$columns["own"])
				return $table;
		}
		return null;
	}*/
	function getLimitStatement($oTable, $bInWhere)
	{
		if($bInWhere)
		{
			STCheck::echoDebug("db.statements.limit", "do not use limit statement if where statement exist");
			return "";
		}
		$maxRows= $oTable->getMaxRowSelect();
		if($maxRows)
		{
			$params= new STQueryString();
			$HTTP_GET_VARS= $params->getArrayVars();
			$tableName= $oTable->getName();
			$from= $oTable->getFirstRowSelect();
			
/*			alex 07/04/2021
 *			set selection first row from url parameter
 *			into OSTTable->execute()
 			
  			if(	$from==0
				and
				isset($HTTP_GET_VARS["stget"]["firstrow"][$tableName])	)
			{
				$from= $HTTP_GET_VARS["stget"]["firstrow"][$tableName];
				$oTable->limit($from, $maxRows);
			}*/
			if(!$from)
				$from= 0;
			STCheck::echoDebug("db.statements.limit", "first row for selection in table '$tableName' is set to $from");
			STCheck::echoDebug("db.statements.limit", "$maxRows maximal rows be set in table '$tableName'");
			
		}elseif(isset($oTable->limitRows))
		{
			$from= $oTable->limitRows["start"];
			$maxRows= $oTable->limitRows["limit"];
		}else
			return "";
		
		$where= " limit ".$from.", ".$maxRows;
		STCheck::echoDebug("db.statements.limit", "add limit statement '$where'");
		return $where;
	}
	/**
	 * create aliases for tables and search also for unreached tables
	 * to set maybe an back-join
	 *
	 * @param $aliases empty array which get all tables with aliases
	 * @param $oTable object of current table
	 * @param $bFromIdentifications need aliases from identification columns
	 * @return boolean false if not found any alias, otherwise true
	 */
	function createAliases(&$aliases, &$oTable, $bFromIdentifications)
	{
		STCheck::echoDebug("db.statements.aliases", "create sql aliases for table ".$oTable->getName());
		if(!$oTable->createAliases($aliases, $oTable, $bFromIdentifications))
			return false;

		$unreach= $this->db->getUnreachedAliases($aliases, $oTable);
		if(count($unreach))
		{
			if(STCheck::isDebug())
			{
				$debugString= "<b>do not reache table";
				if(count($unreach)>1)
					$debugString.= "s";
				$debugString.= " ";
				foreach($unreach as $tableName=>$alias)
					$debugString.= $tableName.", ";
				$debugString= substr($debugString, 0, strlen($debugString)-2);
				STCheck::echoDebug("db.statements.aliases", $debugString."</b>");
			}
			$first= $this->db->getFirstSelectTableNames($oTable->container, $aliases);
			if(count($first))
			{// found an table before, which reach all other
			 // this should be the normaly thing
			 // but sometimes it needs also more back-joins
			 // and the way is the else complicated way
				$firstTableName= current($first);
				$currentTableName= $oTable->getName();
				$structure= $this->db->getTableStructure($oTable->container);
				STCheck::echoDebug("db.statements.aliases", "found the table ".$firstTableName." before ".$currentTableName." which reach all others");
				STCheck::echoDebug("db.statements.aliases", "and make backjoins to this one");
				// set BackJoin flags to the first table
				while($firstTableName!==$currentTableName)
				{
					$before= $this->db->getTableStructFromStructBefore($currentTableName, $structure);
					$beforeTable= key($before);
					$table= &$oTable->getTable($currentTableName);

					$table->setBackJoin($beforeTable);
					if(!isset($aliases[$beforeTable]))
						$aliases[$beforeTable]= "t".(count($aliases)+1);
					unset($table);
					STCheck::echoDebug("db.statements.aliases", "set back-join from ".$currentTableName." to ".$beforeTable.
											" in container ".get_class($oTable->container)."(".$oTable->container->getName().")");
					$currentTableName= $beforeTable;
				}
			}else
			{// no table found which reach all other

				STCheck::echoDebug("db.statements.aliases", "found no table before which reach all others");
				//look for all tables how will be reach
				$newConnect= array();
				$structure= $this->getTableStructure($oTable->container);
				$tableStruct= $this->getTableStructFromStruct($oTable->getName(), $structure);

				reset($tableStruct);
				$reach= $this->getReachedTables($oTable->getTable(key($tableStruct)));
				STCheck::write("reached tables:");
				STCheck::write($reach);
				do{//while($structureTable)

					// for all unreached tables search an connection
					// to the reached tables
					$nUnreached= 0;
					$nReached= 0;
					$nOldUnreached= count($unreach);
					$nOldReached= count($reach);
					STCheck::write("scroll throug \$unreach");
					STCheck::write($unreach);
					while(count($unreach))
					{
						reset($unreach);
						$unreachTable= key($unreach);
						if(	!isset($newConnect[$unreachTable]) ||
							!$newConnect[$unreachTable]				)
						{
							$tableStruct= $this->getStructureTableGroup($unreachTable, $structure);
							$toConnectTable= "";
							// toDo: funciton findConnectTable use always the first connect table
							//		 in the $raeach array, but maybe the table to which need an back-join
							//		 isn't in this array or not on the first place'
							$connect= $this->findConnectTable($tableStruct, $reach, $toConnectTable);
							if($connect)
							{
        						$oConnect= &$oTable->getTable($toConnectTable);
        						$connectTableName= key($tableStruct);
        						STCheck::echoDebug("db.statements.aliases", "set back-join from ".$toConnectTable." in the structure-tablegroup, to ".
														$connect." in container ".$oConnect->container->getName());
        						$oConnect->setBackJoin($connect);
      							$newConnect[$toConnectTable]= true;
      							$reach[$connect]= true;
        						//unset the variable for the next
        						//getTable reference in the foreach-loop
        						unset($oConnect);
        						unset($unreach[$connect]);
        						//$oConnect= $oTable->getTable($beforeTableName);
        						//$oConnect->noColumnSelects();
        						//unset($oConnect);
								if(!isset($aliases[$connect]))
	        						$aliases[$connect]= "t".(count($aliases)+1);
        						if(!isset($aliases[$connectTableName]))
	        						$aliases[$connectTableName]= "t".(count($aliases)+1);
							}// end of if($connect)
						}// end of if(!$newConnect[unreachTable])

						// break loop if not found an new table
						$nUnreached= count($unreach);
						$nReached= count($reach);
						if(	$nUnreached == $nOldUnreached
							&&
							$nReached == $nOldReached		)
						{
							break;
						}
						$nOldReached= $nReached;
						$nOldUnreached= $nUnreached;
					}// end of foreach($unreach)

					reset($tableStruct);
					$groupTableName= key($tableStruct);
					$newReach= $this->getReachedTables($oTable->getTable($groupTableName));

					foreach($newReach as $newTable=>$exist)
					{
						if(isset($unreach[$newTable]))
						{
							unset($unreach[$newTable]);
							$reach[$newTable]= true;
						}
					}
					//$unreach= $this->db->getUnreachedAliases($aliases, $oTable->getTable($groupTableName));

				}while(count($unreach));//($structureTable);

    			if(count($unreach)>count($newConnect))
    			{
    				$unreachTable= "";
    				foreach($unreach as $table=>$alias)
    				{
    					if(!isset($newConnect[$table]))
    						$unreachTable.= $table.", ";
    				}
    				$unreachTable= substr($unreachTable, 0, strlen($unreachTable)-2);
        			echo "<br />";
        			STCheck::error(1, "STDatabase::createAliases()", "<b>Fatal ERROR:</b> found no connection from reached tables to table(s) ".$unreachTable);
        			echo "\n";
        			echo "<b>reached tables:</b>";
        			echo "<pre>";
        			st_print_r($reach);
    				if(!count($reach))
    					echo "<br />";
        			echo "<b>table-structure from selected tables with existing foreign keys</b>";
        			st_print_r($structure,50);
    				if(!count($structure))
    					echo "<br />";
    				$structure= $this->getTableStructure($oTable->container, true/*bForAll*/);
    				echo "\n<b>table-sturcure from all foreign key settings in the hole project:</b>";
    				st_print_r($structure,50);
        			echo "</pre>";
        			exit;
    			}
			}
		}// end of if(count($unreach))

		$oTable->aAliases= $aliases;
		if(Tag::isDebug("db.statements.aliases"))
		{
				echo "<b>[</b>db.statements.aliases<b>]</b> need tables:<br /><pre>";
				print_r($aliases);
				echo "</pre><b>[</b>db.statements.aliases<b>]</b>";
				echo " end of function <b>createAliases()</b><br /><br />";
		}
		return true;
	}
	function getStatement($oTable, $bFromIdentifications= false, $withAlias= true)
	{
		STCheck::param($oTable, 0, "STDbTable");
		STCheck::param($bFromIdentifications, 1, "bool");
		STCheck::param($withAlias, 2, "bool");
		
		STCheck::echoDebug("db.statements", "create sql statement from table <b>".
											$oTable->getName()."</b> inside container <b>".
											$oTable->container->getName()."</b>");
		$this->foreignKeyModification($oTable);
		$aliasTables= array();
		//STCheck::write("search for aliases");
		$this->createAliases($aliasTables, $oTable, $bFromIdentifications);
		//$aliasTables= $this->getAliases($oTable, $bFromIdentifications);
		//st_print_r($aliasTables);
		// Statement zusammen bauen
		$statement= "select ";
		$bMainTable= !$bFromIdentifications;// wenn der erste ->getSlectStatement() Aufruf nicht für
											// die Haupttabelle getätigt wird, werden nur die Tabellen Identificatoren genommen
		$mainTable= $bMainTable;
		if($mainTable)
			$mainTable= $oTable;	// alex 24/05/2005:	nur wenn der erste Aufruf für Haupttabelle getätigt wird
									//					muss zur kontrolle bei einem OSTDbSelector
									//					die Haupttabelle als dritter Parameter mitgegeben werden
		$tableName= $oTable->getName();
		$this->bFirstSelectStatement= true;
		if($oTable->isDistinct())
			$statement.= "distinct ";
		$statement.= $this->getSelectStatement($oTable, $aliasTables, $mainTable, $withAlias);
		$statement.= " from ".$tableName;
		if(count($aliasTables)>1)
		{
			$maked= array();
			$maked[$tableName]= "finished";
			$statement.= " as t1".$this->getTableStatement($oTable, $tableName, $aliasTables, $maked, /*first access*/true);
		}else
		{
				// create $bufferWhere to copy the original
				// behind the function getWhereStatement()
				// back into the table
				// problems by php version 4.0.6:
				// first parameter in function is no reference
				// but it comes back the changed values
				$bufferWhere= $oTable->oWhere;
				$whereStatement= $this->getWhereStatement($oTable, "t1", $aliasTables);
				$oTable->oWhere= $bufferWhere;
				if($whereStatement)
				{
					preg_match("/^(and|or)/i", $whereStatement, $ereg);
					if(isset($ereg[1]))
					{
						if($ereg[1] == "and")
							$nOp= 4;
						else
							$nOp= 3;
						$whereStatement= substr($whereStatement, $nOp);
					}
					$statement.= " where $whereStatement";
				}
		}
		// Order Statement hinzufügen wenn vorhanden
		if(	!isset($oTable->bOrder) ||
			$oTable->bOrder == true		)
		{
			$orderStat= $oTable->getOrderStatement($aliasTables);
			if(	trim($orderStat) !== "" &&
				trim($orderStat) != "ASC" &&
				trim($orderStat) != "DESC"		)
			{
				$statement.= " order by $orderStat";
			}
		}
		$limitStat= $this->getLimitStatement($oTable, false);
		if($limitStat)
			$statement.= $limitStat;
		if(count($this->aOtherTableWhere))
		{
			Tag::warning(1, "STDatabase::getStatement()", "does not reach all where-statements:");
			if(Tag::isDebug())
			{
				echo "<b>do not make the follow where-clausels:</b>";
				st_print_r($this->aOtherTableWhere);
				echo "-------------------------------------------------------<br />\n";
			}
			$this->aOtherTableWhere= array();
		}
		return $statement;
	}
	function getDeleteStatement($table, $where= null)
	{
		if(!$where)
			$where= new STDbWhere();
		if(is_string($table))
		{
			$tableName= $table;
			$container= &$this->getContainer();
			$table= $container->getTable($table);
		}else
			$tableName= $table->getName();
		if($where)
			$table->andWhere($where);
		$whereStatement= $this->getWhereStatement($table, "");
		$statement= "delete from ".$tableName;
		preg_match("/^(and|or)/i", $whereStatement, $ereg);
		if(count($ereg) != 0)
		{
			if(	isset($ereg[1]) &&
				$ereg[1] == "and"	)
			{
				$nOp= 4;
			}else
				$nOp= 3;
			$whereStatement= substr($whereStatement, $nOp);
		}
		$statement.= " where $whereStatement";
		return $statement;
	}
	// gibt true zur�ck wenn kein andere Tabelle auf diese verweist,
	// false wenn kein Eintrag zum l�chen vorhanden ist
	// und sonst den Tabellen-Namen
	function isNoFkToTable($oTable, $where= null)
	{
		Tag::alert(!typeof($oTable, "STAliasTable"), "STDatabase::isNoFkToTable()",
									"first parameter must be an object from STAliasTable");
		$oTable->clearSelects();
		$oTable->clearGetColumns();
		$oTable->clearFKs();
		$oTable->bIsNnTable= false;
		$tableName= $oTable->getName();
		if($where)
			$oTable->andWhere($where);

		$selector= new OSTDbSelector($oTable, STSQL_ASSOC);
		//$statement= $selector->getStatement();
		//echo $statement."<br />";
		$selector->execute();
		$result= $selector->getRowResult();
		if(!$result)
			return false;

		if(is_array($this->tables))
    		foreach($this->tables as $table)
    		{
				$fkTable= $oTable->getTable($table->getName());
				$fk= $fkTable->getForeignKeys();
    			if(is_array($fk))
				{
        			foreach($fk as $inTable=>$to)
        			{
        				if($inTable==$tableName)
        				{
							foreach($to as $column)
							{
								$fkTable->clearSelects();
								$fkTable->clearGetColumns();
								$fkTable->count();
								$is= $result[$column["other"]];
								if(!is_numeric($is))
									$is= "'".$is."'";
								$fkTable->where($column["own"]."=".$is);
								$selector= new OSTDbSelector($fkTable);
								//$statement= $selector->getStatement();
								//echo $statement."<br />";
								$selector->execute();
								$exists= $selector->getSingleResult();
								//echo "found foreign keys from table ".$fkTable->getName()."<br />";
								//echo "to table $inTable<br />";
								//st_print_r($exists);echo "<br />";
        						if($exists)
								{
									//echo "with exist entrys<br />";
        							return $table->getName();
								}//else
								 //	echo "but it have no entrys to the table<br />";exit;
							}
        				}
        			}
				}
    		}
		return true;
	}
	function createStringForDb(&$string)
	{
		if(	!is_numeric($string)
			and
			!preg_match("/^now\([ ]*\)/i", $string)
			and
			!preg_match("/^sysdate\([ ]*\)/i", $string)
			and
			!preg_match("/^password\(.*\)/i", $string)	)
		{
			$string= "'".$string."'";
			return true;
		}
		return false;
	}
	function getDatabaseByName($dbName)
	{
		$containers= STObjectContainer::getAllContainer();
		foreach($containers as $container)
		{
			$db= $container->getDatabase();
			if($this->dbName==$dbName)
				return $db;
		}
		return null;
	}
	function &getDatabase()
	{
		// alex 23/05/2005:	da PHP trotz Refferenze die Datenbank in $this->db
		//					nicht aktualiesiert, muss diese Funktion �berladen werden
		return $this;
	}
	function &getTable($tableName= "")//, $bAllByNone= false)
	{
		Tag::paramCheck($tableName, 1, "string", "empty(string)", "null");
		$nParams= func_num_args();
		STCheck::lastParam(1, $nParams);
		Tag::alert($this->dbType=="BLINDDB", "STDatabase::getTable() ::needTable()", "can not read any Table from STDatabase BLINDDB");

		$table= null;
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

		if(!$tableName)
		{
			$tableName= $this->getTableName();
			$orgTableName= $this->getTableName($tableName);
		}else
		{
			// not all databases save the tables case sensetive
			$orgTableName= $this->getTableName($tableName);
		}
		if(!$orgTableName)
		{
			Tag::echoDebug("table", "no table('$orgTableName') to show difined for this database ".get_class($this)."(".$this->getName().")");
			Tag::echoDebug("table", "or it not be showen on the first status");
			return $table;
		}
/*		$this->aCount[$tableName][$this->getName()]+= 1;
		if($this->aCount[$tableName][$this->getName()] > 20)
		{
			echo "__________________________________________________________________________________________________<br>";
			echo "ask rekursive for table $tableName<br>";
			showErrorTrace();
			exit();
		}*/
		Tag::echoDebug("table", "get table <b>$tableName</b> from DB <b>".$this->getName()."</b>");
		$tableName= strtolower($tableName);
		// alex 12/04/2005: mit getTable werden nun alle Tabellen erstellt
		//					wenn zuvor keine Tabelle mit needTable erzeugt wurde
		// alex 17/05/2005: (ausser wenn $bAllByNone false ist)
		// alex 12/04/2005: es kann jedoch auch eine Tabelle die normaler weise nicht gebraucht wird
		//					damit geholt werden. Sie wird jedoch damit, nicht in die Liste der gebrauchten
		//					aufgenommen
		//if($bAllByNone)
		//	$this->getTables();
		$table= &$this->tables[$tableName];
		if(!$table)
		{
			unset($this->tables[$tableName]);	// beim Abfragen auf Reference von tableName
												// wird ein Eintrag mit null-Value erstellt
			$table= &$this->oGetTables[$tableName];
		}
		if(	!$table
			and
			$this->isTable($tableName)	)
		{
			$table= new STDbTable($orgTableName, $this);
			$desc= &STDbTableDescriptions::instance($this->getName());
			$aFks= $desc->getForeignKeys($orgTableName);
			foreach($aFks as $fk)
			{
				$fkTable= $fk["table"];
				if($fkTable===$orgTableName)
					$fkTable= $table;
				$table->foreignKey($fk["own"], $fkTable, $fk["other"]);
			}
			$this->oGetTables[$tableName]= &$table;
			// alex 12/04/2005: entf. $this->tables[$tableName]= &$table;
			// alex 18/11/2005:	wieder eingef�gt, da sonst alles im kreis l�uft
			//					erkl�rung f�r ausdokumentieren nicht vorhanden
			//$this->tables[$tableName]= &$table;
		}
		if(STCheck::isDebug())
		{
			if(!$table)
				STCheck::echoDebug("table", "table ".$orgTableName." not exist in database");
		}
		return $table;
	}
/*	public function &getTable($tableName)
	{
		$table= STObjectContainer::getTable($tableName);
		if(typeof($table, "STAliasTable"))
			return $table;
		$orgTableName= $this->getTableName($tableName);
		
		// otherwise create an new table-object with the new container
		$table= new STDbTable($orgTableName, $this);
		return $table;
	}*/
	//deprecated wurde als doChoice in STObjectContainer verschoben
	function noChoise($table)
	{
		if(typeof($table, "MUDbTable"))
			$table= $table->getName();
		$this->aNoChoice[$table]= $table;
	}
	abstract protected function insert_id();
	function getLastInsertID()
	{
		return $this->insert_id();
	}
	function saveForeignKeys()
	{
		STCheck::error(1, "STDatabase::saveForeignKeys()", "function is not overloaded!");
		return false;
	}
}

 ?>