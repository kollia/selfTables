<?php

require_once($_stbasetable);

class STDbTable extends STBaseTable
{
    /**
     * current database from where table
     * @var object
     */
	public $db;
	/**
	 * current container in which
	 * the table is located
	 * @var object
	 */
    public $container;
	var $aAuto_increment= array(); // um ein Feld mit Autoincrement vor dem eigentlichen Insert zu holen
	var	$password= array(); // all about to set an password in database
	/**
	 * array of statement parts,
	 * the reason is to create not everey time the same statement
	 * @var array
	 */
	protected $aStatement= array();

    function __construct($Table, $container= null, $onError= onErrorStop)
    {
		Tag::paramCheck($Table, 1, "string", "STBaseTable");
		Tag::paramCheck($container, 2, "STObjectContainer", "string", "null");

		$this->abNewChoice= array();

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
		if(isset($container))
		{
		    if(is_string($container))
		        $this->container= &STBaseContainer::getContainer($container);
	        else
	            $this->container= $container;            
		}else
		    $this->container= &$Table->container;
		
		STBaseTable::__construct($Table);
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
		}
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
    public function __clone()
    {
        STBaseTable::__clone();
        STCheck::echoDebug("table", "clone STDbTable::content ".$this->Name.":".$this->ID);
        unset($this->container);
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
		$this->aStatement= $oTable->aStatement;
	}
	public function toString(bool $htmlTags= true) : string
	{
	    $str= "";
	    if(!typeof($this, "STDbSelector"))
	    {
	        if(isset($this->container))
    	        $str= get_class($this->container);
	        else
	            $str= "\"noDef-container\"";
	        $str.= "(";
    	    if($htmlTags) $str.= "<b>";
    	    if(isset($this->container))
    	        $str.= $this->container->getName();
    	    if($htmlTags) $str.= "</b>";
    	    $str.= ")->";
	    }
	    $str.= STBaseTable::toString();
	    return $str;
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
	public function select(string $column, $alias= null, $fillCallback= null, $nextLine= null, $add= false)
	{
		if(STCheck::isDebug())
		{
			STCheck::param($alias, 1, "string", "function", "TinyMCE", "bool", "null");
			STCheck::param($fillCallback, 2, "function", "TinyMCE", "bool", "null");
			STCheck::param($nextLine, 3, "bool", "null");
			$nParams= func_num_args();
			STCheck::lastParam(4, $nParams);
		}

    	$field= $this->findColumnOrAlias($column);
    	if($field["type"] == "not found")
    	{
			if(STCheck::isDebug())
			{
				STCheck::alert(!$this->validColumnContent($column), "STBaseTable::selectA()",
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
	/**
	 * make statement with join over also this given table
	 * {@inheritDoc}
	 * @see STBaseTable::joinOver()
	 */
	public function joinOver(string $table)
	{
	    $table= $this->db->getTableName($table);
	    STBaseTable::joinOver($table);
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
	public function getStatement(bool $bFromIdentifications= false)
	{
	    $nr= STCheck::increase("db.statement");
	    if(STCheck::isDebug())
	    {
	        if(STCheck::isDebug("db.statement"))
	        {
    	        echo "<br /><br />";
    	        echo "<hr color='black'/>";
    	        STCheck::echoDebug("db.statement", "create $nr. statement for table ".$this->toString());
    	        echo "<hr />";
    	        //STCheck::info(1, "STDbTable::getStatement()", "called STDbTable::<b>getStatement()</b> method from:", 1);
	        }
	    }
		if(isset($this->aStatement['full']))
		{
		    if(STCheck::isDebug("db.statements"))
		    {
		        $arr[]= "use pre-defined full statement in ".get_class($this)."(<b>".$this->Name."</b>[".$this->ID."]):";
		        $arr[]= $this->aStatement['full'];
		        STCheck::echoDebug("db.statements", $arr);
		    }
		    return $this->aStatement['full'];
		}
	    $statement= $this->db->getStatement($this, $bFromIdentifications);
	    $this->aStatement['full']= $statement;
	    if(STCheck::isDebug("db.statements"))
	    {
	        $space= STCheck::echoDebug("db.statements", "stored full statement:");
	        st_print_r($this->aStatement, 2, $space);
	        echo "<hr />";
	    }
	    return $statement;
	}
	public function setStatement(string $statement)
	{
	    if(STCheck::isDebug("db.statement"))
	    {
	        $msg[]= "set statement from outside of ".get_class($this)."(<b>".$this->Name."</b>[".$this->ID."]):";
	        $msg[]= $statement;
	        STCheck::echoDebug("db.statement", $msg);
	    }
	    $stats= array(  "select", "from", "where", "order" );	    
	    $arr= stTools::getWrappedStatement($stats, $statement);
	    if( isset($arr) &&
	        is_array($arr) &&
	        count($arr)        )
	    {
	        $this->aStatement= array();
    	    $this->aStatement['full']= $statement;
    	    foreach($arr as $clause)
    	    {
    	        if(strtolower(substr($clause, 0, 6)) == "select")
    	            $this->aStatement['select']= $clause;
                elseif(strtolower(substr($clause, 0, 4)) == "from")
                    $this->aStatement['table']= $clause;
                elseif(strtolower(substr($clause, 0, 5)) == "where")
                    $this->aStatement['where']= $clause;
                elseif(strtolower(substr($clause, 0, 5)) == "order")
                    $this->aStatement['order']= $clause;
    	    }    
	    }else
	        STCheck::is_warning(1, "STDbTable::setStatement()", "cannot read correct statement:$statement");
	}
	public function getResultCountStatement()
	{
	    if(!isset($this->aStatement['table']))
	        $this->getStatement();
        $statement= "select count(*) ";
        if(isset($this->aStatement['table']))
            $statement.= $this->aStatement['table'];
        if(isset($this->aStatement['where']))
            $statement.= " ".$this->aStatement['where'];
        return $statement;
	}
	public function displayWrappedStatement()
	{
	    $array= $this->getWrappedStatement();
	    foreach($array as $row)
	        echo "$row<br />";
	}
	public function getWrappedStatement()
	{
	    $stats= array(  "show", "select", "update", "delete", "from",
            	        array( "inner join", "left join", "right join" ),
            	        "where", "having", "order", "limit"                  );
	            
	    return stTools::getWrappedStatement($stats, $this->getStatement());
	}
	// alex 24/05/2005:	boolean bMainTable auf die Haupttabelle ge�ndert
	//					da dort wenn sie ein Selector ist noch �berpr�fungen
	//					vorgenommen werden sollen, ob der FK auch ausgef�hrt wird
	/**
	 * create part of sql select statement
	 * 
	 * @param array $aTableAlias array of all exist alias for tables exist, which gives back all alias which needed
	 * @param bool $bUseIdentifications whether should use for the first select only the identification columns
	 */
	/*private*/function getSelectStatement(array &$aTableAlias, bool $bUseIdentifications)
	{
	    if(isset($this->aStatement['select']))
	        return $this->aStatement['select'];
	    $statement= "select ";
	    if($this->isDistinct())
	        $statement.= "distinct ";
	    $statement.= $this->getSelectStatementA(!$bUseIdentifications, $this, $aTableAlias);
	    $this->aStatement['select']= $statement;
	    return $statement;
	}
	/**
	 * create part of sql select statement
	 *
	 * @param bool $bFirstSelect describes whether the method is called by first time.<br />
	 *                           (testing by main-table is same then own object not possible,
	 *                            because the own object/table can also be an foreignKey-table)
	 * @param STDbTable $oMainTable object of the first table from which all other are drawn
	 * @param array $aTableAlias array of all exist alias for tables exist, which gives back all alias which needed
	 */
	private function getSelectStatementA(bool $bFirstSelect, STDbTable $oMainTable, array &$aTableAlias)
	{
	    $aUseAliases= array();
	    $singleStatement= "";
	    $statement= "";
	    if($bFirstSelect)
	        $aNeededColumns= $this->getSelectedColumns();
        else
            $aNeededColumns= $this->getIdentifColumns();
        
        STCheck::flog("create select statement");
        $this->removeNoDbColumns($aNeededColumns, $aTableAlias);
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
                $msg.= " as $columnAlias";
                STCheck::echoDebug("db.statements.select", $msg);
            }
            
            if($aliasCount>1)
            {
                $fkTableName= null;
                if( typeof($oMainTable, "STDbSelector") &&
                    (   !isset($oMainTable->abNewChoice["select"]) ||
                        $oMainTable->abNewChoice["select"] == "true"   )   )
                {
                    // alex 24/05/2005:	if table is an existing foreign Key
                    //                  and the current/main table an STDbSelector,
                    //                  than check whether a table version exist in the selector.
                    //					Otherwise it shouldn't made any output from the linked table.
                    // alex 24/02/2023: now search only for foreign keys when inside an STDbSelector container
                    //                  no extra choice was made
                    $fkTableName= $this->getFkTableName($column["column"]);
                    if(STCheck::isDebug() && $fkTableName)
                        STCheck::echoDebug("db.statements.select", "from ".get_class($this)." ".$this->getName()." for column ".$column["column"]." is Fk-Table \"".$fkTableName."\"");
                }
                if(	!$fkTableName ||// if no FK table exist, the column can only be from the current table
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
                    $fkStatement= $oOther->getSelectStatementA(/*firstSelect*/false, $oMainTable, $allAliases);
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
            STCheck::echoDebug("db.statements.select", "String is \"".$singleStatement."\"");
            STCheck::echoDebug("db.statements.select", "String is \"".$statement."\"");
            //echo __FILE__.__LINE__."<br>";
            //echo "last char from statement is \">>".substr($statement, strlen($statement), -1)."<<\"";
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
        if( !empty($aTableAlias) &&
            isset($whereClause) &&
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
        if(STCheck::isDebug("db.statements.aliases"))
        {
            $space= STCheck::echoDebug("db.statements.aliases", "need follow tables inside select-statement");
            st_print_r($aTableAlias, 1, $space);
        }
        if(count($aTableAlias) <= 1)
            $statement= $singleStatement;
        $statement= substr($statement, 0, strlen($statement)-1);
        Tag::echoDebug("db.statements.select", "createt statement is \"".$statement."\"");
        return $statement;
	}
	public function getTableStatement(array &$aTableAlias)
	{
	    if(isset($this->aStatement['table']))
	        return $this->aStatement['table'];
	    $maked= array();
	    $maked[$this->Name]= "finished";
	    $statement= "from ".$this->Name." as ".$aTableAlias[$this->Name]." ";
	    $statement.= $this->getTableStatementA($this, $aTableAlias, $maked, /*first access*/true);
	    $this->aStatement['table']= $statement;
	    return $statement;
	}
	private function getTableStatementA(STDbTable $oMainTable, array &$aTableAlias, array &$maked, bool $bFirstAccess= false)
	{
	    if(STCheck::isDebug())
	    {
	        $sMessage= "make table statement from table $this->Name which is";
            if(!$bFirstAccess)
                $sMessage.= " <b>not</b>";
            $sMessage.= " the main table";
	        STCheck::echoDebug("db.statements.table", $sMessage);
	    }
		$statement= "";
		$tableStructure= $this->db->getTableStructure($this->container);
		if($oMainTable->getName()!==$this->Name)
			$oTable= $this;
		else
			$oTable= &$oMainTable;
		if($bFirstAccess)
			$aNeededColumns= $oTable->getSelectedColumns();
		else
			$aNeededColumns= $oTable->getIdentifColumns();
		if( STCheck::isDebug("db.statements.table") &&
		    $bFirstAccess /*columns only interrest by Maintable*/   )
		{
		    $space= STCheck::echoDebug("db.statements.table", "needed columns for table ".$oTable->getName());
			echo "<pre>";
			st_print_r($aNeededColumns, 2, $space);
			STCheck::echoDebug("db.statements.table", "from table alias:");
			st_print_r($aTableAlias, 1, $space);
			echo "</pre><br />";
		}
		$ownTableAlias= $aTableAlias[$oTable->getName()];
		
    	if($bFirstAccess)
    	   $this->db->searchJoinTables($aTableAlias);
    	$fk= $oTable->getForeignKeys();	
    	if( STCheck::isDebug("db.statements.table") )
        {
        	$exist= count($fk);
        	if($exist > 0)
        	    $msg= "found ";
        	else
        	    $msg= "do not need ";
        	$msg.= "foreign Keys for table ".get_class($oTable).":'".$oTable->getName()."' with ID:".$oTable->ID;
        	$space= STCheck::echoDebug("db.statements.table", $msg);    	
        	if($exist > 0)
        	   st_print_r($fk,3,$space);
        }
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
    			if(	$bNeedColumn===false &&
    				isset($aTableAlias[$table]) &&
    				!isset($maked[$table])          )
    			{// wenn die FK Spalte nicht in den ben�tigten Spalten ist,
    			 // das objekt aber vom Typ STDbSelector ist (wobei die FK-Spalten nicht aufgelistet werden)
    			 // und die Tabelle in den Aliases ist,
    			 // wird sie doch f�r den join ben�tigt
    			 	$bNeedColumn= true;
    			}
    			if(STCheck::isDebug("db.statements"))
    			{
    			    if(STCheck::isDebug("db.statements.table"))
    			        $debugtype= "db.statements.table";
    			    else
    			        $debugtype= "db.statements.where";
    				if($bNeedColumn===false)
    				{
    					$debugString= "do not need foreign key from column ".$join["own"]." to table ".$table." for statement";
    					STCheck::echoDebug("db.statements.table", $debugString);
    					if(isset($maked[$table]))
    					    STCheck::echoDebug("db.statements.table", "join was inserted before");
    				}elseif(!isset($aTableAlias[$table]))
    				    STCheck::echoDebug("db.statements.table", "no such table $table for column ".$join["own"]." in createt Alias-Array");
    				else
    				{
    				    STCheck::echoDebug("db.statements.table", "need foreign key from column ".$join["own"]." to table ".$table.
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
    				$where= $ownTableAlias.".".$join["own"];
    				$where.= "=".$sTableAlias.".".$join["other"];
      				$statement.= " join ".$database.$table." as ".$sTableAlias;
      				$statement.= " on ".$where;
      				if(STCheck::isDebug())
      				{
      				    STCheck::echoDebug("db.statements.table", "make ".$joinArt." join to table ".$database.$table.
      				        " with alias-name ".$sTableAlias);
      				    STCheck::echoDebug("db.statements.where", "add foreign in on-clause \"$where\"");
      				}
      				
      				if(0)
      				{
          				$fromTable= $join["table"];// if the table comes from an other DB, table exist in $join from foreach of $oTable->FK
        				if(	$fromTable->container->db->getName() == $oMainTable->container->db->getName() &&
        					$fromTable->container->getName() != $oMainTable->container->getName()			)
        				{// take the correct table from container
        				    $fromOwnTable= $oMainTable->getTable($join['table']->Name);    				    
        					$fromTable= $oMainTable->container->getTable($fromTable->getName());
        					echo __FILE__.__LINE__."<br>";
        					echo "own getTable: ".$fromOwnTable->toString()." but use ".$fromTable->toString()."<br>";
        				}
        				if(!$fromTable)// otherwise it should select from the current container(-table)
        				    $fromTable= $oTable->getTable($join['table']->Name);
      				}else
      				    $fromTable= $oTable->getTable($join['table']->Name);
    			    $statement.= $fromTable->addJoinLimitationByQuery($aTableAlias);
    				$whereStatement= $fromTable->getWhereStatement("on", $fromTable, $aTableAlias);
    				if($whereStatement)
    				{
    				    if(!preg_match("/^[ \t]*and/", $whereStatement))
    				        $whereStatement= "and $whereStatement";
    					$statement.= " ".$whereStatement;
    					STCheck::echoDebug("db.statements.table", "get on condition '$whereStatement' from table '".$fromTable->getName()."(".$fromTable->ID.")'");
    				}
    				if($oMainTable->getName() != $fromTable->getName())
    				{
        				$whereStatement= $oMainTable->getWhereStatement("on", $fromTable, $aTableAlias);
        				if($whereStatement)
        				{
        				    if(!preg_match("/^[ \t]*and/", $whereStatement))
        				        $whereStatement= "and $whereStatement";
        				        $statement.= " ".$whereStatement;
        				        STCheck::echoDebug("db.statements.table", "get on condition '$whereStatement' from main table '".$oMainTable->getName()."(".$oMainTable->ID.")'");
        				}
    				}
    
    				if(!isset($maked[$table]))
    				{// debug-Versuch f�r komplikationen -> bitte auch member-Variable counter am Funktionsanfang aktivieren
    				 //			if($this->counter==4){
    				 //				echo "$statement OK".$this->counter;exit;}else $this->counter++;
    
    					$maked[$table]= "finished";
    					$statement.= $fromTable->getTableStatementA($oMainTable, $aTableAlias, $maked);
    					Tag::echoDebug("db.statements.table", "back in table <b>".$oTable->getName()."</b>");
    				}
    			}// end of if(	$bNeedColumn && isset($aTableAlias[$table])	)
       		}//end of foreach($content)
    	}// end of foreach($fk)
    
    	
    	if( STCheck::isDebug("db.statements.table") )
    	{
    	    $exist= count($oTable->aBackJoin);
    	    if($exist > 0)
    	        $msg= "found ";
    	    else
    	        $msg= "no ";
            $msg.= "foreign Keys (BackJoin's) ";
            if($exist == 0)
                $msg.= "found ";
            $msg.= "to own table '".$oTable->getName()."' with ID:".$oTable->ID." ";
            if($exist > 0)
                $msg.= "from follow tables:";
            $space= STCheck::echoDebug("db.statements.table", $msg);
            if($exist > 0)
                st_print_r($oTable->aBackJoin,3,$space);
    	}
    	// make no backjoin for follow tables
    	$noJoinTables= $oMainTable->getNotJoinOverTables();
    	foreach($noJoinTables as $noTable)
    	{
    	    if(isset($aTableAlias[$noTable]))
    	        unset($aTableAlias[$noTable]);
    	}
		//look for tables which have an BackJoin
		$ownDatabaseName= $this->db->getDatabaseName();
		foreach($oTable->aBackJoin as $sBackTableName)
		{
		    if( isset($aTableAlias[$sBackTableName]) && // need table inside statement
			    !isset($maked[$sBackTableName])          ) // and was not done before
			{
				$maked[$sBackTableName]= "finished";
    			$BackTable= &$oTable->getTable($sBackTableName);
    			Tag::echoDebug("db.statements.table", "need backward from table $this->Name to table $sBackTableName from container ".$BackTable->container->getName());
    			$sTableAlias= $aTableAlias[$sBackTableName];
    			$dbName= $BackTable->db->dbName;
    			$database= "";
    			$fks= $BackTable->getForeignKeys();
    			$join= $fks[$this->Name][0];
				STCheck::is_warning(!$join, "STDatabase::getStatement()", "no foreign key be set from backward table $sBackTableName to table $this->Name");
    			if($join)
    			{
    			    $oBackTable= $oTable->getTable($sBackTableName);
    				if($dbName!==$ownDatabaseName)
    					$database= $dbName.".";
					$joinArt= $join["join"];
					if($joinArt==="outer")
						$joinArt= "left";
                    $statement.= " ".$joinArt." join ".$database.$sBackTableName." as ".$sTableAlias;
                    $statement.= " on ".$ownTableAlias.".".$join["other"];
                    $statement.= "=".$sTableAlias.".".$join["own"];
					if(Tag::isDebug())
					{
						Tag::echoDebug("db.statements.table", "make ".$joinArt." join to table ".$database.$sBackTableName.
													" with alias-name ".$sTableAlias);
					}
					$statement.= $BackTable->addJoinLimitationByQuery($aTableAlias);
					$statement.= $oBackTable->getTableStatementA($oMainTable, $aTableAlias, $maked);
					$whereStatement= $BackTable->getWhereStatement("on", $BackTable, $aTableAlias);
					if($whereStatement)
					{
					    if(!preg_match("/^[ \t]*and/", $whereStatement))
					        $whereStatement= "and $whereStatement";
					        $statement.= " ".$whereStatement;
					        STCheck::echoDebug("db.statements.table", "get on condition '$whereStatement' from table '".$fromTable->getName()."(".$fromTable->ID.")'");
					}
					if($oMainTable->getName() != $sBackTableName)
					{
					    $whereStatement= $oMainTable->getWhereStatement("on", $BackTable, $aTableAlias);
					    if($whereStatement)
					    {
					        if(!preg_match("/^[ \t]*and/", $whereStatement))
					            $whereStatement= "and $whereStatement";
					            $statement.= " ".$whereStatement;
					            STCheck::echoDebug("db.statements.table", "get on condition '$whereStatement' from main table '".$oMainTable->getName()."(".$oMainTable->ID.")'");
					    }
					}
    			}// end of if(!STCheck::is_warning(!$join))
				unset($BackTable);
			}// end of if($join)
		}// end of foreach($oTable->aBackJoin)
		    
		if( $bFirstAccess &&
		    count($maked) < count($aTableAlias)   )
		{
		    if(STCheck::isDebug("db.statements.table"))
		    {
		        $space= STCheck::echoDebug("db.statements.table", "need select for tables:");
		        st_print_r($aTableAlias, 2, $space);
		        STCheck::echoDebug("db.statements.table", "<b>but have now made only for</b>");
		        st_print_r($maked, 2, $space);
		        $space= STCheck::echoDebug("db.statements.table", "structure of tables are");
		        st_print_r($tableStructure, 20, $space);
		    }
		    $accessTable= array();
		    $accessFoundTable= array();
		    foreach($tableStructure as $table => $reach)
		    {
		        $found= array();
		        if(!is_array($reach))
		        {// reach = 'before'
		            if(array_key_exists($table, $aTableAlias))
		                $found['found'][$table]= array();
		            else
		                $found= array();
		        }else
		            $found= $this->db->searchInTableStructure($reach, $aTableAlias);
		        if(count($found))
		        {
    		        $space= STCheck::echoDebug("db.statements.table", "found follow structure from table <b>$table</b>");
    		        if(isset($found['found']))
    		            $accessFoundTable[$table]= $found;
    		        else
    		            $accessTable[$table]= $found['access'];
    	            if(STCheck::isDebug("db.statements.table"))
    	                st_print_r($found, 20, $space);
		        }
		    }
		    if(STCheck::isDebug("db.statements.table"))
		    {
		        STCheck::echoDebug("db.statements.table", "tables has access to other tables:");
		        st_print_r($accessTable, 20, $space);
		        STCheck::echoDebug("db.statements.table", "table found needed tables:");
		        st_print_r($accessFoundTable, 20, $space);
		    }
		    echo __FILE__.__LINE__."<br>";
		    echo "accessFoundTable:";
		    st_print_r($accessFoundTable,20);
		    $foundAccessOver= array();
		    foreach($accessFoundTable as $firstTable => $reachFirstTable)
		    {
		        foreach($accessFoundTable as $secondTable => $reachSecondTable)
		        {
		            if( $firstTable != $secondTable &&
		                $reachFirstTable['found'] != $reachSecondTable['found'] &&
		                isset($reachFirstTable['access']) &&
		                (   !array_key_exists($firstTable, $maked) ||
		                    !array_key_exists($secondTable, $maked)   )   )
		            {
		                foreach($reachFirstTable['access'] as $foundTable)
		                {
		                    if( isset($reachSecondTable['access']) &&
		                        in_array($foundTable, $reachSecondTable['access']))
		                    {
		                        $foundFirst= array_key_first($reachFirstTable['found']);
		                        $foundSecond= array_key_first($reachSecondTable['found']);
		                        $ofStr= "$foundSecond $foundFirst";
		                        if( !isset($foundAccessOver[$ofStr]) ||
		                            !in_array($foundTable, $foundAccessOver[$ofStr])  )
		                        {
		                            if(!isset($foundAccessOver[$ofStr]))
    		                            $toStr= $foundFirst ." ". $foundSecond;
		                            else
		                                $toStr= $ofStr;
    		                        $foundAccessOver[$toStr][]= $foundTable;
    		                        if(STCheck::isDebug("db.statements.table"))
    		                        {
    		                            $msg= "found connection from table <b>$firstTable</b>($foundFirst) ";
    		                            $msg.= "to tables <b>$secondTable</b>($foundSecond) ";
    		                            $msg.= " over table <b>$foundTable</b>";
    		                            STCheck::echoDebug("db.statements.table", $msg);
    		                        }
		                        }
		                    }
		                }
		            }
		        }
		    }
		    if(STCheck::isDebug())
		    {
		        $bTableDebug= STCheck::isDebug("db.statements.table");
		        STCheck::debug("db.statements.table");
		        if(count($foundAccessOver) > 0)
		        {
		            if(!$bTableDebug)
		            {
		                echo "<br />";
		                $space= STCheck::echoDebug("db.statements.table", "structure of tables are");
		                st_print_r($tableStructure, 20, $space);
		            }
    		        echo "<br /><br /><br />";
    		        $foundtables= false;
    		        $ambiguous= false;
    		        foreach($foundAccessOver as $conns)
    		        {
    		            if( is_array($conns) &&
    		                count($conns) > 0     )
    		            {
    		                $foundtables= true;
    		                if(count($conns) > 1)
    		                {
    		                    $ambiguous= true;
    		                    break;
    		                }
    		            }
    		        }
    		        if($foundtables)
    		        {
        		        $xtable= "table as follow";
        		        if($ambiguous)
        		            $msg= "found ambiguous connection over follow tables, please choose the best one";
        		        else
        		        {
        		            $msg= "found connection over table <b>$this->Name</b>, for better performance,";
        		            $res= reset($foundAccessOver);
        		            $xtable= reset($res);
        		        }
            		    STCheck::echoDebug("db.statements.table", $msg);
            		    $space= STCheck::echoDebug("db.statements.table", "implement ".get_class($oMainTable)."(<b>$this->Name</b>)->joinOver<b>(</b>&lt;$xtable&gt;<b>)</b>");
            		    if($ambiguous)
            		        st_print_r($foundAccessOver, 2, $space);
            		    echo "<br />";
            		    echo "$statement<br>";
            		    echo "---------------------------------------------------------------------------------------------------------";
            		    echo "---------------------------------------------------------------------------------------------------------<br />";
            		    showErrorTrace();
            		    echo "<br /><br /><br />";
            		    exit;
    		        }
		        }
		    }
		    STCheck::alert(count($maked) < count($aTableAlias), "STDbTable::getTableStatementA()", "do not join to all alias tables see STCheck::debug('db.statements.table')");
		}
		STCheck::echoDebug("db.statements.table", "TableStatement - Result from table '".$oTable->getName()."'= '$statement'");
		return $statement;
	}
	/*private*/function getWhereAliases() : array
	{
	    $aRv= array();
	    $aliasTables= $this->db->getAliasOrder();
	    $ostwhere= $this->getWhere();
	    $desc= null;
	    if(isset($ostwhere))
	    {
    	    foreach($ostwhere->aValues as $table=>$content)
    	    {
    	        if(!isset($aliasTables[$table]))
    	        {
    	            if(!isset($desc))
    	                $desc= STDbTableDescriptions::instance($this->db->getDatabaseName());
    	            $table= $desc->getTableName($table);
    	        }
    	        $aRv[$table]= $aliasTables[$table];
    	    }
	    }
	    if( STCheck::isDebug("db.statements.where") &&
	        count($aRv)                                )
	    {
	        $msg= "need additional table alias from ";
	        $msg.= get_class($this)."(<b>".$this->Name."</b>[".$this->ID."])";
	        $msg.= " where statement";
    	    $space= STCheck::echoDebug("db.statements.where", $msg);
    	    st_print_r($aRv, 3, $space);
	    }
	    return $aRv;
	}
	/*private*/function newWhereCreation(array $aliases= null)
	{
	    $oWhere= $this->getWhere();
	    if(isset($oWhere))
	        $oWhere->reset();
	    if(isset($aliases))
	    {
    	    foreach($aliases as $tableName=>$alias)
    	    {
    	        $table= $this->getTable($tableName);
    	        $table->newWhereCreation();
    	    }
	    }
	}
	/*private*/function getWhereStatement(string $condition, STDbTable|array $from= null, array $aliases= null)
	{
	    if(STCheck::isDebug())
	    {
	        STCheck::param($condition, 0, "check", 
	            $condition=="on"||$condition=="where"||$condition=="insert"||$condition=="update", 
	            "'on', 'where', 'insert'", "'update' strings");
	        if($condition == "on")
	           STCheck::param($from, 1, "STDbTable");
	           
            if(STCheck::isDebug("db.statements.where"))
            {
                $space= STCheck::echoDebug("db.statements.where", "stored where statement for table ".$this->getName());
                st_print_r($this->oWhere, 5, $space);
            }
	    }
	    if(isset($this->aStatement[$condition]))
	    {
	        if($condition == "where")
	        {
	            return $this->aStatement['where'];
	        }
            $tabName= $from->getName();
	        if(isset($this->aStatement[$condition][$tabName]))
	        {
	            return $this->aStatement[$condition][$tabName];
	        }
	    }
	    
	    if( STCheck::isDebug("db.statements.where") )
	    {
	        if(typeof($from, "STDbTable"))
	            $fromTable= $from;
	        else
	            $fromTable= $this;
	        $msg= "  excute where-statement outgoing from table ";
	        $msg.= get_class($this)."(<b>".$this->Name."</b>[".$this->ID."])";
	        $msg.= " on condition <b>$condition</b> for table ";
	        $msg.= get_class($fromTable)."(<b>".$fromTable->Name."</b>[".$fromTable->ID."])";
	        $blanc= "************************************************************************";
	        $blanc.= $blanc;
	        $blanc= "<b>$blanc</b>";
	        $amsg[]= $blanc;
	        $amsg[]= $msg;
	        $amsg[]= $blanc;
	        echo "<br />";
	        STCheck::echoDebug("db.statements.where", $amsg);
	        $space= STCheck::echoDebug("db.statements.where", "for given aliases:");
	        st_print_r($aliases, 1, $space);
	        if(!isset($aliases))
	            echo "<br />";
	    }
	    if( !isset($from) ||
	        typeof($from, "array") )
	    {
	        $aliases= $from;
	        $from= $this;
	    }
	    $aMade= array();
	    $statement= "";
	    $ostwhere= $this->getWhere();
	    if(typeof($ostwhere, "STDbWhere"))
	    {
	        STCheck::echoDebug("db.statements.where", "execute for own table <b>".$this->Name."</b>");
	        $statement= $ostwhere->getStatement($from, $condition, $aliases);
	        $aMade[]= $this->getName();
	    }
	    if(is_array($aliases))
	    {
	        foreach($aliases as $tableName=>$alias)
	        {
	            if(!in_array($tableName, $aMade))
	            {
	                $table= $this->getTable($tableName);
	                if(typeof($table, "STAlaisTable"))
	                {
	                    $ostwhere= $table->getWhere();
	                    //echo "table:".$fromTable->getName()."<br />";
	                    //st_print_r($fromTable->oWhere,10);
	                    if(typeof($ostwhere, "STDbWhere"))
	                    {
	                        STCheck::echoDebug("db.statements.where", "execute for joind table <b>".$table->Name."</b>");
	                        $whereStatement= $ostwhere->getStatement($table, $condition, $aliases);
	                        if($whereStatement)
	                        {
	                            if(!preg_match("/^[ \t]*where/", $whereStatement))
	                                $whereStatement= "and $whereStatement";
	                            $statement.= " ".$whereStatement;
	                        }
	                        $aMade[]= $tableName;
	                    }
	                }
	            }
	        }
	    }
	    if( STCheck::isDebug("db.statements.where") )
	    {
	        $amsg= array();
	        if(trim($statement) != "")
	            $message= "created where statement for ";
	        else
	            $message= "for object ";
            $message.= get_class($this)."(<b>".$this->getName()."</b>)";
            $message.= " from container <b>".$this->container->getName()."</b>";
            if(trim($statement) == "")
	            $message.= " was no where statementd created";
	        if(trim($statement) != "")
	            $amsg[]= "     <b>$condition:</b> '$statement'";
            $amsg[]= $message;
            $amsg[]= $blanc;
            STCheck::echoDebug("db.statements.where", $amsg);
	    }
	    if($condition == "where")	        
	        $this->aStatement['where']= $statement;
	    return $statement;
	}
	/**
     * allow modification by every table has an limit in the query string
     * or an foreign key table limit points to own table with also limitation from query
     * 
     * @param bool $bModify whether should modification enabled or disabled (default:enable[true])
	 */
	public function allowQueryLimitation($bModify= true)
	{
        $this->allowFkQueryLimitation($bModify);
        $this->allowQueryLimitationByOwn($bModify);
	}
	public function allowFkQueryLimitation($bModify= true)
	{
	    if(STCheck::isDebug("db.statements.where"))
	    {
	        if($bModify)
	            $do= "allow";
	            else
	                $do= "disable";
	                $do.= " query limitation inside ".$this->toString();
	                STCheck::echoDebug("db.statements.where", $do);
	                STCheck::info(1, "STDbTable::allowQueryLimitation()", $do, 2);
	    }
	    $this->bModifyFk= $bModify;
	    if($this->bModifiedByQuery)
	        $this->resetQueryLimitation("fk", $bModify);
	}
	/**
     * reset modification of query limitation
     * 
     * @param string $which can be 'fk' or 'own'
     * @param bool whether can modify or not
	 */
	protected function resetQueryLimitation(string $which, bool $bModify)
	{
	    if(isset($this->oWhere))
	    {
	        $found= $this->oWhere->resetQueryLimitation($which, $bModify);
	        if( $bModify &&
	            !$found    )
	        {
	            $this->bModifiedByQuery= false;
	        }
	    }
	}
	public function modify()
	{
	    if( $this->bModifyFk ||
	        $this->bLimitOwn   )
	    {
	        return true;
	    }
	    return false;
	}
	public function where($stwhere, $operator= "")
	{
	    STCheck::param($stwhere, 0, "STDbWhere", "string", "empty(string)", "null");
	    STCheck::param($operator, 1, "check", 
	        $operator === "" ||
	        $operator == "and" ||
	        $operator == "or", "null string", "and", "or" );
	    
	    if( typeof($stwhere, "STDbWhere") &&
	        $stwhere->sDbName == ""            )
	    {
	        $stwhere->setDatabase($this->db);
	    }
	    STBaseTable::where($stwhere, $operator);
	}
	/**
	 * remove columns if they are not in the database table
	 *
	 * @param array $aNeededColumns all column names in an array
	 * @param array $aAliases all predefined alias names for tables
	 */
	protected function removeNoDbColumns(array &$aNeededColumns, array $aAliases)
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
            $inherit= $this->db->keyword($column);
            if($inherit)
            {
                $columnString= "";
                foreach($inherit["content"] as $col)
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
                        if($needetTables[$content["table"]]->validColumnContent($col))
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
                                    $table->validColumnContent($preg[2])	)
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
                $aNeededColumns[$nr]["column"]= $inherit["keyword"]."(".$columnString.")";
            }else
            {
                if(!isset($needetTables[$content["table"]]))
                    $needetTables[$content["table"]]= &$this->getTable($content["table"]);
                if( !$needetTables[$content["table"]]->validColumnContent($column)
                    and
                    $column!="*"	)
                {
                    // alex 19/09/2005:	rather insert a null column
                    $aNeededColumns[$nr]["column"]= "(null)";
                }
            }
        }
	}
	/**
	 * check whether given name is a valid column.<br />
	 * The column can also be a quoted string,
	 * or contain a keyword from database
	 *
	 * {@inheritDoc}
	 * @see STBaseTable::validColumnContent()
	 */
	public function validColumnContent($content, &$abCorrect= null, bool $bAlias= false) : bool
	{
        $field= $this->getDatabase()->keyword($content);
        if($field != false)
        {
            foreach($field['columns'] as $column)
            {
                if( $column != "*" &&
                    !STBaseTable::validColumnContent($column, $abCorrect, $bAlias) )
                {
                    return false;
                }
            }
            if(typeof($abCorrect, "array"))
                $abCorrect= $field;
            return true;
        }
        return STBaseTable::validColumnContent($content, $abCorrect, $bAlias);
	}
	// alex 19/04/2005:	alle links in eine Funktion zusammengezogen
	//					und $address darf auch ein STDbTable,
	// 					f�r die Verlinkung auf eine neue Tabelle, sein
	/**
	 * specify a column to select from database and whether how to display
	 * 
	 * {@inheritDoc}
	 * @see STBaseTable::linkA()
	 */
	protected function linkA(string $which, string $tableName, array $aliasColumn, $address= null, string $valueColumn= null)
	{
	    if(STCheck::isDebug())
	    {
    	    STCheck::param($which, 0, "string");
    	    STCheck::param($tableName, 1, "string");
    	    STCheck::param($address, 3, "STBaseContainer", "STBaseTable", "string", "null");
    	    STCheck::param($valueColumn, 4, "string", "null");
	    }
	    
		if(typeof($address, "STDbTable"))
		{// ist die Addresse ein STDbTable
		 // diesen abfangen und in einen Container verpacken
		    $tabName= $address->getName();
		    $newContainerName= "dbtable ".$tabName;
		    $address= new STDbTableContainer($newContainerName, $this->db);
		    $address->needTable($tabName);
			
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
		STBaseTable::linkA($which, $tableName, $aliasColumn, $address, $valueColumn);
	}
}

?>