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
	 * all aliases defined for current container
	 * 
	 * @var array tuple string
	 */
	private $aAliases= null;
	
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
	protected	$asExistTableNames= array();
	var $lastStatement;
	var $foreignKey= false;
	var	$aFieldArrays= array();
	protected $error= null;
	protected $errno= null;
	var $datePos;
	var $pregDateFormat;
	var $dateDelimiter= ".";
	var $timePos;
	var $pregTimeFormat;
	var	$bOrderDates= true; // shoud be order the date in the sqlResult?
	var $inFields= array();
	var	$bFKsave= null; // make foreign Keys in DB,
						// for MySql it define no innoDB
	var	$sNeedAlias= array(); // hier wird eingetragen welches Alias ben�tigt wurde
	var $aValues= null;
	var $bHasTables= false;


	  /**
		*  Konstruktor f�r Zugriffs-deffinition
		*
		*/
	function __construct($identifName= "main-menue", $defaultTyp= STSQL_ASSOC, $DBtype= "BLINDDB")
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
    	if( STCheck::isDebug("db.statement.insert") ||
    	    STCheck::isDebug("db.statement.update")    )
    	{
    	    STCheck::debug("db.statement.modify");
    	}
    	
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
	public function isDbTable(string $tableName) : bool
	{
        if($this->name === $this->db->getName())
        {
            $tableName= $this->getTableName($tableName);
            if(in_array($tableName, $this->asExistTableNames))
                return true;
        }else
            return $this->db->isDbTable($tableName);
        return false;
	}
	function setTimeFormat($sFormat)
	{
		//$sFormat= strtoupper(trim($sFormat));
		$preg= array();
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
		if( !$date ||
		    !preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})/", $date, $preg)  )
		{
			return false;
		}
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
	*  @param string:$host: Hostname
	*  @param string:$user: Username
	*  @param string:$passwd: Passwort
	*/
	abstract public function connect($host= null, $user= null, $passwd= null, $database= null);
	abstract public function closeConnection();
   	public function database($dbName, $onError= onErrorStop)
   	{   
   	    $this->dbName= $dbName;
   	    STDbTableDescriptions::init($this);
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
	public function query($statement, $onError= onDebugErrorShow)
  	{
		if($this->dbType=="BLINDDB")
			return;
		
		// alex 12/05/2005:	statement kann nun auch
		//					ein objekt von STDbTable sein
		if(typeof($statement, "STDbTable"))
			$statement= $statement->getStatement();
		if(is_String($statement))
		{
		    $bExecuteDb= true;
			if(STCheck::isDebug())
			{
				global	$_st_page_starttime_;
		
				if(!$_st_page_starttime_)
					Tag::setPageStartTime();
				Tag::echoDebug("db.statement.time", date("H:i:s")." ".(time()-$_st_page_starttime_));
				//Tag::echoDebug("db.statement", "in DB:".$this->dbName." conn:".$this->conn."\"");
				
				if(STCheck::isDebug("db.test"))
				{
				    $sessionTable= $this->getTableName("Sessions");
				    $inClassFunction= "db.statement";
				    $preg= null;
				    $res= preg_match("/^[ \t]*([^ \t]+)/", $statement, $preg);
				    if( $res )
				    {
				        $stat= strtolower($preg[1]);
				        if( $stat != "show" &&
				            $stat != "select"   )
				        {
				            $res= preg_match("/$sessionTable/", $statement);
				            if( $res == 0 || // insert update anywhere when table is Session
				                STCheck::isDebug("db.test.session")    ) // elsewhere db.test.session be set
				            {
        				        $bExecuteDb= false;
        				        $inClassFunction= "db.test";
        				        //showBackTrace();
				            }
				        }
				    }
				    STCheck::echoDebug($inClassFunction, "statement: \"".$statement."\" ");
				    if(!$bExecuteDb)
				        STCheck::echoDebug("db.test", "do not execute ".$preg[1]."-statement on database for testing");
				}else
				{
				    $stats= array(  "show", "select", "insert", "values", "update", "delete", "from",
            				        array( "inner join", "left join", "right join" ),
            				        "where", "having", "order", "limit"                  );
				    $aStatement= stTools::getWrappedStatement($stats, $statement);
				    $space= STCheck::echoDebug("db.statement", $aStatement);
				    if(STcheck::isDebug("db.statements.from"))
				        showBackTrace(1);
				}
				STCheck::flog("fetch statement on db with command querydb");
			}
			if($bExecuteDb)
			{
			    $functionName= strtolower(substr(trim($statement), 0, 6));
			    if( $functionName == "delete" ||
			        $functionName == "update"     )
			    {// as security port
			        if(!preg_match("/where/i", $statement))
			        {
			            
			            STCheck::warning(1, "STDatabase::query()", "do not execute $functionName-statement on database, because no where-clause exist");
                        STCheck::echoDebug("db.statement", "wrong statement:$statement");
                        $bExecuteDb= false;
			        }
			    }
			}
			if($bExecuteDb)
			{
			    $this->errno= null;
			    $this->error= null;
			    $res= $this->querydb($statement);
			    if(STcheck::isDebug("db.statements.from"))
			        showBackTrace(1);
			}else
			    $res= array();
		}else// if statement until was an Array,
			return $statement; // statement should be the result
		
		$this->lastStatement= $statement;
    	if( (	$res==null
				or
				!$res	)
			&&
			(	$onError > noErrorShow
				or
				STCheck::isDebug("db.statement")	)	)
    	{
    	    $space= 55;
    	    if( $onError > onErrorMessage ||
    	        STCheck::isDebug()             )
    	    {
        	    if(STCheck::isDebug("db.statement"))
        	        $space= STCheck::echoDebug("db.statement", "database error:");    	    
        	    echo $this->getError(/*with tags*/true, $space);
    			if(phpVersionNeed("4.3.0", "debug_backtrace()"))
    			{
    			    echo "<br>";
    				showBackTrace(1);
    				
    			}
    			if( $onError==onErrorStop )
    			    exit();
    	    }
    	}
  		return $res;
  	}
	function getError(bool $withTags= false, int $space= 0)
	{
		//if($this->isError())	// ??? was soll das?
		//	return "";			// damit der Fehler nur einmal ausgegeben wird?

		$string= "";
		if($withTags)
     		$string=  "<b>";
		if($space > 0)
		    $string.= STCheck::getSpaces($space);		   
		$string.= "MYSQL_ERROR ".$this->errno()." in Statement: \"";
		if($withTags)
			$string.= "</b>";
		$string.= $this->lastStatement;
		if($withTags)
			$string.= "<b>";
		$string.= "\"";
		if($withTags)
		    $string.= "</b><br><b>";
	    if($space > 0)
	        $string.= STCheck::getSpaces($space);		
		$string.= "MySql error message:";
		if($withTags)
			$string.= "</b>";			
		if($this->errno()==0)
		    $string.= " no results";
		else
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
	/**
	 * return version of mysql
	 * 
	 * @return array return array with mayor, minor, revision and exact key
	 */
	abstract public function getServerVersion() : array;
	/**
	 * return database engine as addendum string
	 * which engine should used for creation
	 * 
	 * @return string addendum string with engine
	 */
	abstract public function getAddingEngineString() : string;
	/**
	 * check for the required sql version
	 *
	 * @param string $needVersion string of mayor, minor and revision version, seperatet with a point or hyphen
	 */
	function requiredVersion(string $needVersion)
	{
	    $version= $this->getServerVersion();
	    $needVers= preg_split("/[.,-]/", $needVersion);
	    $bOk= true;
	    $anzA= count($needVers);
	    $set[]= "mayor";
	    $set[]= "minor";
	    $set[]= "revision";
	    $anz= count($set);
	    if($anz>$anzA)
	        $anz= $anzA;
        for($o= 0; $o<$anz; $o++)
        {
            $akt= $version[$set[$o]];
            settype($akt, "integer");
            $need= $needVers[$o];
            settype($need, "integer");
            if($akt<$need)
            {
                $bOk= false;
                break;
            }
            if($akt>$need)
                break;
        }
        return $bOk;
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
		
		if( $this->dbType=="BLINDDB" ||
		    $this->errnodb() > 0  ) // query before had an error
		{
			return array();
		}
		$row= array();
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
 	    if(isset($this->errno))
 	    {
 	        return $this->errno;
 	    }
		$this->errno= $this->errnodb();
		$this->error= $this->errordb();
	    return $this->errno;
  	}
  	function error()
	{
  	    if(isset($this->error))
  	        return $this->error;
		$this->errno();
		return $this->error;
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
		if(typeof($statement, "STBaseTable"))
			$statement= $this->getStatement($statement);
 		$res= $this->query($statement, $onError);
		if(!$res)
			return NULL;
		$orderTyp= $typ;
		if($typ==NUM_STfetchArray)
			$typ= MYSQL_NUM;
		elseif($typ==ASSOC_STfetchArray)
			$typ= MYSQL_ASSOC;
		elseif($typ==BOTH_STfetchArray)
			$typ= MYSQL_BOTH;
		while($row = $this->fetchdb_row($typ, $onError))
 		{
			if(	$orderTyp==NUM_STfetchArray
				or
				$orderTyp==ASSOC_STfetchArray
				or
				$orderTyp==BOTH_STfetchArray	)
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
	 * @param string:$statement: 	kann ein normales SQL-Statment sein,<br>
     *   	 						ein Tabellen-Name<br>oder ein Ergebnis aus einem Statement
     *   	 						(wo jedoch bei einem <code>enum</code> nur <code>enum</code>
     *   	 						 im flag angezeigt wird -> sonst auch der Inhalt des Enums)
	 * @param enum:$onError: 	    ob die Methode Fehler anzeigen soll und beendet werden soll.
     *   	 						<br>noErrorShow - Fehler wird nicht angezeigt und Programm nicht beendet
     *   	 						<br>onErrorShow - Fehler wird angezeigt aber Programm nicht beendet
     *   	   		  			<br>onErrorStop - Fehler wird angezeigt und Programm beendet
	 */
	function describeTable($statement, $onError= onErrorStop)
	{
		if(typeof($statement, "STBaseTable"))
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
			$filedArrayKey= $tableName;
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
			$filedArrayKey= $tableName;
			$statement= "select * from $tableName limit 1";
		}
		//-----------------------------------------------------------------------
		// pre-define list of fields from table
		STCheck::echoDebug("db.statement", "describe field-content read from <b>table(</b>$tableName<b>)</b>");
		$this->list_dbtable_fields($tableName);
		//-----------------------------------------------------------------------
		if(isset($this->aFieldArrays[$filedArrayKey]))
			return $this->aFieldArrays[$filedArrayKey];

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
		$this->aFieldArrays[$filedArrayKey]= $aRv;
		if(STCheck::isDebug("show.db.fields"))
		{
			$space= STCheck::echoDebug("show.db.fields", "produced column-result:");
			if(empty($aRv))
				echo "<strong>ERROR:</strong> no field content!<br />";
			st_print_r($aRv, 5, $space);
		}
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
	abstract public function real_escape_string(string $str);
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
		$result= $this->list_dbtable_fields($TableName, $onError);
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
	public function searchInTableStructure(array $structure, array $needTables)
	{	    
	    $aRv= array();
	    foreach($structure as $table => $reach)
	    {
	        if(array_key_exists($table, $needTables))
	            $aRv['found'][$table]= array();	            
	        else
	            $aRv['access'][]= $table;
	        if(is_array($reach))
	        {
    	        $found= $this->searchInTableStructure($reach, $needTables);
    	        if(count($found) > 0)
    	        {
    	            if(isset($found['access']))
    	            {
    	                if(isset($aRv['access']))
    	                    $aRv['access']= array_merge($aRv['access'], $found['access']);
    	                else
    	                    $aRv['access']= $found['access'];
    	            }
    	        }
	        }
	    }
	    return $aRv;
	}
	public function searchJoinTables(array &$aAliases, array $aSubstitutionTables)
	{
	    $newAliases= array();
	    $substitutionAliases= array();
	    if(count($aSubstitutionTables))
	    {
	        // do not search for substitution tables
	        foreach($aAliases as $table => $alias)
	        {
	            if(!isset($aSubstitutionTables[$table]))
	                $newAliases[$table]= $alias;
	            else
	                $substitutionAliases[$table]= $alias;
	        }
	    }else
	        $newAliases= $aAliases;
	    if(STCheck::isDebug())
	    {
    	    if(STCheck::isDebug("db.table.fk"))
    	        $dbg= "db.table.fk";
    	    else
    	        $dbg= "db.statements.table";
    	    if(STCheck::isDebug($dbg))
    	    {
    	        $space= STCheck::echoDebug($dbg, "search join tables where need also for existing tables:");
    	        st_print_r($aAliases, 2, $space);
    	        echo stTools::getSpaces($space)."structure of tables:<br />";
    	        st_print_r($this->aTableStructure['struct'], 20, $space);
    	    }
	    }
	    $this->searchJoinTablesR($this->aTableStructure['struct'], $newAliases, false);
	    $aAliases= $newAliases;
	    if(count($substitutionAliases))
	    {
	        // implement back substitution tables
	        foreach($substitutionAliases as $table => $alias)
	            $aAliases[$table]= $alias;
	    }
	    if(STCheck::isDebug())
	    {
	        if(STCheck::isDebug($dbg))
	        {
	            $space= STCheck::echoDebug($dbg, "result of joining tables need inside selection-statement:");
	            st_print_r($aAliases, 2, $space);
	        }
	    }
	}
	private function searchJoinTablesR($aTableStructure, &$aAliases, $bNeedBefore) : array
	{
	    $debugFunction= false;
	    $branch= array();
	    if(!is_array($aTableStructure))
	        return array();
	    foreach($aTableStructure as $tableName=>$fks)
	    {
	        if($debugFunction) echo "search for table $tableName<br>";
	        $needTable= false;
	        if(isset($aAliases[$tableName]))
	        {
	            if($debugFunction) echo " <b>need</b> table $tableName inside statement<br>";
	            $needTable= true;
	        }
	        $needBranch= $this->searchJoinTablesR($fks, $aAliases, $needTable);
	        if($debugFunction) echo " for table $tableName need ".count($needBranch)." branche(s)<br>";
	        if(count($aAliases) == count($needBranch))
	        {
	            if($debugFunction) echo " found all tables, need no more!<br />";
	            return $needBranch;
	        }
            if( $needTable ||
                count($needBranch) > 1 )
            {
                $branch[]= $tableName;
                if(!$needTable)
                {
                    $allAliases= $this->getAliasOrder();
                    $aAliases[$tableName]= $allAliases[$tableName];
                }
            }
	    }	    
	    return $branch;
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
		exit;
		if(Tag::isDebug("db.statements.aliases"))
		{
			if(1)//!$bFromIdentifications)
			{
				echo "<b>[</b>db.statements.aliases<b>]</b> need tables:<br /><pre>";
				//print_r($aliasTables,3,24);
				if($oTable->getName()=="MUCluster")
				    showBackTrace();
				echo "</pre><b>[</b>db.statements.aliases<b>]</b>";
				echo " end of function <b>getAliases()</b><br /><br />";
			}
		}
		return $aliasTables;
	}
	private function newUnsearchedTables($bFromAll)
	{
	    STCheck::paramCheck($bFromAll, 1, "bool");
	    
	    if(	isset($this->aTableStructure[STALLDEF]) &&
	        $this->aTableStructure[STALLDEF]["fromAll"]===true	)
	    {// if STALLDEF is false and also bFromAll is false,
	        // do search again, because maybe an new table exists
	        return false;
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
	                $this->isDbTable($tableName)								)
	            {// if an new table founded in tableName list
	                // search for all tables again
	                $bSearch= true;
	                unset($this->aTableStructure);
	                return true;
	            }
	        }
	        if(!$bSearch)
	            return false;
	    }
	}
	function getTableStructure(STContainerTempl $container, bool $bFromAll= true)
	{
	    if( isset($this->aTableStructure["struct"]) &&
	        !$this->newUnsearchedTables($bFromAll)     )
		{
		    return $this->aTableStructure["struct"];
		}
		$this->aTableStructure[STALLDEF]["fromAll"]= $bFromAll;
		$aHaveFks= array();
		

		if(!isset($this->aTableStructure["struct"]))
			$this->aTableStructure["struct"]= array();
		foreach($this->asExistTableNames as $tableName)
		{
    		$bGetTable= true;
    		if(!$bFromAll)
    		{
    			if(!$container->hasTable($tableName))
    			{
    			    STCheck::echoDebug("db.statements.table", "table $tableName do not exist inside container:".$container->getName());
    				$bGetTable= false;
    			}
    		}
    		if($bGetTable)
    		{
    			$fromTable= $container->getTable($tableName);
				// the table-name is lower case
				// so get the real one from the fromTable
				$tableName= $fromTable->getName();
        		$this->aTableStructure["struct"][$tableName]= array();
				$this->aTableStructure[STALLDEF]["in"][$tableName]= true;
				$aNewTableStructure= array();
				if(count($fromTable->aFks))
				{
					$aHaveFks[$tableName]= true;
        			foreach($fromTable->aFks as $fromTableName=>$toColumn)
        				$this->aTableStructure["struct"][$tableName][$fromTableName]= array();
				}else
				    STCheck::echoDebug("db.statments.table", "$tableName has no FK to an other table");
				if(STCheck::isDebug("db.table.fk"))
				{
    			    $space= STCheck::echoDebug("db.table.fk", "Foreign Key structure from tables grow to:");
    			    st_print_r($this->aTableStructure,20,$space);
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
				$debugName= "table";
			else
				$debugName= "db.statement";

			$all= "all";
			if($this->aTableStructure[STALLDEF]["fromAll"]===false)
				$all= "exist";
			$space= STCheck::echoDebug($debugName, "<b>foreign Key</b> structure from <b>$all</b> tables in container ".$container->getName());
			st_print_r($this->aTableStructure['struct'],50, $space);
			echo "<br />";
		}
		
		// create now backjoins
		STCheck::echoDebug("db.table.fk", "create backjoins inside tables from database container ".$this->getName());
		$this->createBackJoins($this->aTableStructure["struct"]);
		return $this->aTableStructure["struct"];
	}
	private function createBackJoins($tableStructure)
	{
	    if(is_array($tableStructure))
	    {
	        if(STCheck::isDebug())
	        {
	            if(STCheck::isDebug("db.table.fk"))
	                $classification= "db.table.fk";
	            else
	                $classification= "table";
	            if(STCheck::isDebug($classification))
	            {
	                $space= STCheck::echoDebug($classification, "structure of given foreigen key tables:");
	                st_print_r($tableStructure, 20, $space);
	            }
	        }
    	    foreach ($tableStructure as $toTableName=>$fks)
    	    {
    	        if(is_array($fks))
    	        {
        	        foreach ($fks as $fromTableName=>$ofks)
        	        {
        	            $fromTable= &$this->getTable($fromTableName);
        	            STCheck::echoDebug("db.table.fk", "set backjoin in table $fromTableName to table $toTableName");
        	            $fromTable->setBackJoin($toTableName);
        	            $ntable= $this->getTable($fromTableName);
        	        }    	        
        	        $this->createBackJoins($fks);
    	        }
    	    }
	    }
	}
	private function searchTableStructure($fromTableName, $rootTableName, $aHaveFks)
	{
		if(!isset($this->aTableStructure["struct"][$fromTableName]))
		{
			unset($this->aTableStructure["struct"][$fromTableName]);
			return;
		}
		$this->aTableStructure[STALLDEF]["in"][$fromTableName]= true;
		$fkTables= $this->aTableStructure["struct"][$fromTableName];
		foreach($fkTables as $toTableName=>$ownTableColumns)
		{
			if(	isset($this->aTableStructure["struct"][$toTableName])
				and
				$toTableName!=$rootTableName
				and
				$toTableName!=$fromTableName				)
			{
				$this->searchTableStructure($toTableName, $rootTableName, $aHaveFks);
				$this->aTableStructure["struct"][$fromTableName][$toTableName]= $this->aTableStructure["struct"][$toTableName];
				unset($this->aTableStructure["struct"][$toTableName]);
			}else
			{
				if(	$this->aTableStructure[STALLDEF]["in"][$toTableName] &&
					!count($this->aTableStructure["struct"][$fromTableName][$toTableName]) &&
					isset($aHaveFks[$toTableName]) &&
					$aHaveFks[$toTableName]														)
				{
					$this->aTableStructure["struct"][$fromTableName][$toTableName]= "before";
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

		echo "findConnectTable(\$structure, \$tableList, \$toConnectTable)<br>";
		STCheck::write($structure, 5);
		STCheck::write($tableList, 5);
		STCheck::write($toConnectTable, 5);
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
	 * 
	 * @param STObjectContainer $container
	 * @param array $aNeededTables
	 * @return array
	 */
	function getFirstSelectTableNames($container, $aNeededTables)
	{
		Tag::paramCheck($container, 1, "STObjectContainer");
		Tag::paramCheck($aNeededTables, 2, "array");

		if(count($aNeededTables)===1)
			return $aNeededTables;
		$aFlipNeeded= array_flip($aNeededTables);
		$structure= $this->getTableStructure($container);
		echo __FILE__.__LINE__."<br>";st_print_r($aNeededTables);
		showBackTrace();
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
    		if($this->wait){echo __FILE__.__LINE__."<br>";st_print_r($reached);}
    		$needetTables= count($aFlipNeeded);
    		foreach($reached as $tableName=>$count)
    		{
    			if($count===$needetTables)
    				return array($tableName);
    		}
    		if($this->wait){echo __FILE__.__LINE__."<br>";}
    		$count= 0;
    		foreach($reached as $key=>$value)
    		{
    			$reached[$key]= $count;
    			++$count;
    		}
    		$aNeededTables= $reached;
    		$before= true;
    		if($this->wait){echo __FILE__.__LINE__."<br>";st_print_r($aNeededTables);}
    		if(count($aNeededTables)<=1)
    		    break;
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
	/**
	 * create aliases order for all tables inside database
	 *
	 * @return array of all tables with aliases
	 */
	function getAliasOrder() : array
	{
	    if(isset($this->aAliases))
	    {
	        return $this->aAliases;
	    }
	    STCheck::echoDebug("db.statements.aliases", "create sql aliases for container '".$this->getName()."'");
	    $this->aAliases= array_flip($this->asExistTableNames);
	    foreach ($this->aAliases as &$nr)
	        $nr= "t".$nr;
	    return $this->aAliases;
	}
	/**
	 * search for all tables who has an foreign key
	 * to table of first parameter and has an exist entry
	 * to this table
	 * 
	 * @param string $tableName name of table to whome other tables should refer
	 * @param STDbWhere $where explicit where-clause to set in all joins
	 * @return boolean|array an array of all tables who refer to first parameter table name or by none (false)
	 */
	function hasFkEntriesToTable($tableName, STDbWhere $where= null)
	{
	    STCheck::param($tableName, 0, "string");
	    
	    $aFkTables= array();
		foreach($this->oGetTables as $fkTable)
		{
		    $fks= $fkTable->getForeignKeys();
		    foreach($fks as $toTable=>$columns)
		    {
		        if($tableName == $toTable)
		            $aFkTables[$fkTable->getName()]= $columns;
		    }
		}
		if(empty($aFkTables))
		    return false;
		
		// alex: 15/03/2023
		// toDo:  check database whether same foreign keys set
		//        and do it over sql Error
		$aRv= array();
		foreach($aFkTables as $fkTableName=>$fks)
		{
		    foreach($fks as $columns)
	        {
	            $table= $this->getTable($fkTableName);
	            $fkSelector= new STDbSelector($table);
	            $fkSelector->joinOver($tableName, STINNERJOIN);
	            $fkSelector->count();
	            if(isset($where))
	                $fkSelector->where($where);
                $fkSelector->execute();
                $res= $fkSelector->getSingleResult();
                if($res)
                    $aRv[]= $fkTableName;
	        }
		}
		if(empty($aFkTables))
		    return false;
		return $aRv;
	}
	abstract protected function getSglJoinName($join);
	public function getSqlJoinStatementLinkName($join)
	{
	    if(STCheck::isDebug())
	        STCheck::param($join, 0, "check",
	            $join==STINNERJOIN||$join==STOUTERJOIN||$join==STLEFTJOIN||$join==STRIGHTJOIN||$join=="inner"||$join=="outer"||$join=="left"||$join=="right", 
	            "STINNERJOIN, STOUTERJOIN, STLEFTJOIN", "STRIGHTJOIN");
        return $this->getSglJoinName($join);
	}
	function createStringForDb(&$string)
	{
	    $keyword= false;
	    if(is_string($string))
	        $keyword= $this->keyword($string);
        if(	!is_numeric($string) &&
            $keyword !== false     )
        {
            $string= "'".$string."'";
            return true;
        }
        return false;
	}
	/**
	 * return an array of all operators
	 * the key inside the array shouldn't changed
	 * if an operator not exist, value should be null
	 *
	 * @return string[] operator array
	 */
	abstract public function getOperatorArray();
	function getRegexpOperator()
	{
	    return $this->getOperatorArray()["regexp"];
	}
	function getLikeOperator()
	{
	    return $this->getOperatorArray()["like"];
	}
	function getIsOperator()
	{
	    return $this->getOperatorArray()["="];
	}
	function getGreaterOperator()
	{
	    return $this->getOperatorArray()[">"];
	}
	function getGreaterEqualOperator()
	{
	    return $this->getOperatorArray()[">="];
	}
	function getLowerOperator()
	{
	    return $this->getOperatorArray()["<"];
	}
	function getLowerEqualOperator()
	{
	    return $this->getOperatorArray()["<="];
	}
	function getIsNotOperator()
	{
	    return $this->getOperatorArray()["!="];
	}
	function getIsNullOberator()
	{
	    return $this->getOperatorArray()["is"];
	}
	function getIsNotNullOperator()
	{
	    return $this->getOperatorArray()["is not"];
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
    function &createTable($tableName)
    {
        $table= null;
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
		if(STCheck::isDebug())
		{
		    $msg= "get table \"$tableName\" from DB <b>".$this->getName()."</b> as original table <b>$orgTableName</b>";
    		STCheck::echoDebug("db.statements.table", $msg);     
		}
		$table= new STDbTable($orgTableName, $this);
		$desc= &STDbTableDescriptions::instance($this->getDatabaseName());
		$aFks= $desc->getForeignKeys($orgTableName);
		foreach($aFks as $fk)
		{
			$fkTable= $fk["table"];
			if($fkTable===$orgTableName)
				$fkTable= $table;
			$table->foreignKey($fk["own"], $fkTable, $fk["other"]);
		}
		$this->oGetTables[strtolower($tableName)]= &$table;
		// alex 12/04/2005: entf. $this->tables[$tableName]= &$table;
		// alex 18/11/2005:	wieder eingef�gt, da sonst alles im kreis l�uft
		//					erkl�rung f�r ausdokumentieren nicht vorhanden
		//$this->tables[$tableName]= &$table;
		if(STCheck::isDebug())
		{
			if(!$table)
				STCheck::echoDebug("table", "table ".$orgTableName." not exist in database");
			else
			    STCheck::echoDebug("table", "created table:".$table->toString());
		}
		return $table;
	}
	function noChoise($table)
	{
		if(typeof($table, "MUDbTable"))
			$table= $table->getName();
		$this->aNoChoice[$table]= $table;
	}
	abstract protected function insert_id();
	abstract protected function getValueKeywords() : array;
	abstract public function getFunctionKeywords() : array;
	abstract public function getFunctionDelimiter() : array;
	abstract public function getFieldDelimiter() : array;
	abstract public function getStringDelimiter() : array;
	abstract protected function getAllColumnKeyword() : string;
	public function getDelimitedString(string $content, string $for, bool $bRegex= false)
	{
	    $sRv= "";
	    $delimiter= array();
	    switch ($for)
	    {
	        case "string":
	            $delimiter= $this->getStringDelimiter();
	            break;
	        case "field":
	            $delimiter= $this->getFieldDelimiter();
	            break;
	        case "function":
	            $delimiter= $this->getFunctionDelimiter();
	            break;
	        default:
	            STCheck::alert(1, "STDatabase::getDelimitedString()", 
	               "second parameter \$for have no correct enum (only string/field/function are allowed)");
	            break;
	    }
	    if($bRegex)
	    {
	        $sRv= "[";
	        foreach($delimiter as $one)
	        {
	            if($one['open']['ESC']['reg-br'])
	                $sRv.= "\\";
	            $sRv.= $one['open']['delimiter'];
	        }
	        $sRv.= "]{$content}[";
	        foreach($delimiter as $one)
	        {
	            if($one['close']['ESC']['reg-br'])
	                $sRv.= "\\";
                $sRv.= $one['close']['delimiter'];
	        }
	        $sRv.= "]";
	        
	    }else
	    {
	        $sRv=  $delimiter[0]['open']['delimiter'];
	        $sRv.= $content;
	        $sRv.= $delimiter[0]['close']['delimiter'];
	    }
	    return $sRv;
	}
	/**
	 * inform whether content of parameter is an keyword
	 *
	 * @param string $column content of column
	 * @return array array of keyword, content, type and len, otherwise false.<br />
	 *                 the keyword is in lower case and have to be const/max/min<br />
	 *                 the content is an array with columns or strings which where seperated with an comma (not shure whether it's a correct name/alias)<br />
	 *                 the type of returned value by execute
	 *                 the len of returned value by execute
	 */
	public function keyword(string $column)
	{
	    $lwStr= strtolower(trim($column));
	    $single= $this->getValueKeywords();
	    $allowed= array_merge($this->getFunctionKeywords(), $single);
	    if(isset($single[$lwStr]))
	    {
	        $keyword= $lwStr;
	        $inherit= array();
	        $begin= 0;
	        $end= strlen($lwStr)-1;
	        $usage= "value";
	    }else
	    {
	        $preg= array();
	        $usage= "function";
	        $delimiter= $this->getFunctionDelimiter();
	        $open= "";
	        if($delimiter[0]['open']['ESC']['regex'])
	            $open.= "\\";
            $open.= $delimiter[0]['open']['delimiter'];
            $close= "";
            if($delimiter[0]['close']['ESC']['regex'])
                $close.= "\\";
            $close.= $delimiter[0]['close']['delimiter'];
            $pattern= "/([^\(\) ]+)[ ]*$open(.*)($close)/";
            //$pattern= "/b"
            if(!preg_match($pattern, trim($column), $preg, PREG_OFFSET_CAPTURE))
                return false;
            $begin= $preg[0][1];
            $keyword= strtolower($preg[1][0]);
            $end= $preg[3][1] - strlen($preg[3][0]) + 1;
            if(!array_key_exists($keyword, $allowed))
                return false;
            $inherit= array();
            $splited= preg_split("/,/", $preg[2][0]);
            $quote_open= false;
            $dquote_open= false;
            $in_str= "";
            // split only outside of quotes
            foreach($splited as $each)
            {
                $in_str.= $each;
                $first= substr(trim($each), 0, 1);
                $last= substr(trim($each), -1, 1);
                if( !$quote_open &&
                    !$dquote_open   )
                {
                    if($first == "'" && $last != "'")
                        $quote_open= true;
                    elseif($first == '"' && $last != '"')
                        $dquote_open= true;
                }elseif($quote_open &&
                        $last == "'"    )
                {
                    $quote_open= false;
                    
                }elseif($dquote_open &&
                    $last == '"'    )
                {
                    $dquote_open= false;
                }
                if( !$quote_open &&
                    !$dquote_open   )
                {
                    $inherit[]= $in_str;
                    $in_str= "";
                }
            }
	    }
	    return array(   "keyword" => $keyword,
	                    "usage" => $usage,
            	        "content" => $inherit,
            	        "type" => $allowed[$keyword]['type'],
            	        "len" => $allowed[$keyword]['len'],
            	        "beginpos" => $begin,
            	        "endpos" => $end,
	                    "needOp" => $allowed[$keyword]['needOp']
	    );
	}
	function getLastInsertID()
	{
		return $this->insert_id();
	}
	abstract protected function saveForeignKeys();
}

 ?>