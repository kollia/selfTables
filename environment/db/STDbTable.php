<?php

require_once($_stpostarray);
require_once($_stbasetable);

class STDbTable extends STBaseTable
{
    private $onError;
    private $aOtherTableWhere= array();
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
    public $container= null;
    /**
     * count of current entrys inside table
     * @var integer
     */
    protected $entryCount= -1;
	var $aAuto_increment= array(); // um ein Feld mit Autoincrement vor dem eigentlichen Insert zu holen
	var	$password= array(); // all about to set an password in database
	/**
	 * array of statement parts,
	 * the reason is to create not everey time the same statement
	 * @var array
	 */
	protected $aStatement= array();

	protected function createFirstOwnTable($Table)
	{
	    STCheck::param($Table, 0, "STDbTable", "string");
	    
	    if(typeof($Table, "string"))
	    {
	        Tag::echoDebug("describeTable", "table constructor file:".__file__." line:".__line__);
	        $fieldArray= $this->db->describeTable($Table, $this->onError);
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
	                $sql.= " and  `TABLE_NAME` = '$Table'";
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
	    }else
	    {
	        $this->copy($Table);
	        $this->columns= $Table->columns;
	    }
	}
    public function __construct($Table, $container= null, $onError= onErrorStop)
    {
        if(typeof($this, "STDbSelector"))
		    STCheck::paramCheck($Table, 1, "string", "STBaseTable", "null");
        else
            STCheck::paramCheck($Table, 1, "string", "STBaseTable");
		STCheck::paramCheck($container, 2, "STObjectContainer", "string", "null");

		$this->abOrigChoice= array();
		$this->onError= $onError;
		
		if(isset($container))
		{
		    if(is_string($container))
		        $this->container= &STBaseContainer::getContainer($container);
	        else
	            $this->container= $container;
	        $this->db= &$this->container->getDatabase();
	        
		}elseif(isset($Table) && typeof($Table, "STObjectContainer"))
		{
		    $this->container= &$Table->container;
		    $this->db= &$this->container->getDatabase();
		}else
		    $this->container= null;
		
		if(typeof($Table, "STBaseTable"))
		{
			$tableName= $Table->Name;
		}elseif(isset($this->container) && typeof($Table, "string"))
		{
		    if(!isset($this->db))
		        $this->db= &$this->container->getDatabase();
		    $desc= &STDbTableDescriptions::instance($this->db->getDatabaseName());
		    $tableName= $desc->getTableName($Table);
		}else
		    $tableName= "UNKNOWN";
		
		STBaseTable::__construct($Table);
		if(Tag::isDebug())
		{
			STCheck::alert(	!$container
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
			elseif(typeof($Table, "STBaseTable"))
				Tag::echoDebug("table", "make copy from table-object <b>".$Table->Name."</b> for an new one");
			else // own table should be a STDbSelector container
			    STCheck::echoDebug("table", "create an empty STDbSelector object");
		}
		if(isset($Table))
		    $this->createFirstOwnTable($Table);
    }
    public function __clone()
    {
        STBaseTable::__clone();
        STCheck::echoDebug("table", "clone STDbTable::content ".$this->Name.":".$this->ID);
        //unset($this->container);
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
		$this->sAccessClusterColumn= $oTable->sAccessClusterColumn;
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
	 * return count of entrys inside table
	 * 
	 * @return integer count of entrys
	 */
	public function getTableEntrys()
	{
	    if($this->entryCount > -1)
	        return $this->entryCount;
	    	    
	    $cTab= new STDbSelector($this);
	    $cTab->count();
	    //$countStatement= $cTab->getStatement();
	    $cTab->execute();
	    $nRv= $cTab->getSingleResult();
	    if(!isset($nRv))
	        $nRv= 0;
	    $this->entryCount= $nRv;
	    return $nRv;
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
	/**
	 * add column for selection
	 *
	 * @param string $column column name from database
	 * @param string $alias column name should appear from select
	 * @param string $fillCallback if set, make callback before and after select for given column
	 * @param boolean $nextLine if <code>false</code> by <code>STItemBox</code> do not show column inside new line
	 * @param boolean $add whether new column should be added to exist original selection
	 * @param boolean $addGet if set, adding differ between select- and get-columns, otherwise parameter <code>$add</code> is for both
	 */
	public function select(string $column, $alias= null, $fillCallback= null, $nextLine= null, bool $add= false, bool $addGet= null)
	{
		if(STCheck::isDebug())
		{
			STCheck::param($alias, 1, "string", "function", "TinyMCE", "bool", "null");
			STCheck::param($fillCallback, 2, "function", "TinyMCE", "bool", "null");
			STCheck::param($nextLine, 3, "bool", "null");
			$nParams= func_num_args();
			STCheck::lastParam(6, $nParams);
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
		STBaseTable::select($column, $alias, $fillCallback, $nextLine, $add, $addGet);
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
		if(STCheck::isDebug())
		{
			STCheck::param($column, 0, "string");
			STCheck::param($parentCluster, 0, "string", "array", "null");
			STCheck::param($clusterfColumn, 2, "string", "null");
			STCheck::param($accessInfoString, 3, "string", "empty(string)");
			STCheck::param($addGroup, 4, "boolean");
			STCheck::param($action, 5, "string", "int");
			//Tag::paramCheck($parentCluster, 7, "string", "null");
			Tag::alert(!$this->columnExist($column), "STBaseTable::addAccessClusterColumn()",
												"column $column not exist in table ".$this->Name.
												"(".$this->getDisplayName().")", 1);
		/*	Tag::alert(!$this->columnExist($clusterfColumn), "STBaseTable::addAccessClusterColumn()",
												"column for cluster $clusterfColumn not exist in table ".$this->Name.
												"(".$this->getDisplayName().")", 1);*/
		}


		if(!isset($clusterfColumn))
		{
			$selector= new STDbSelector($this);
			$selector->select($this->Name, $column, $column);
			$selector->allowQueryLimitation(true);
			$statement= $selector->getStatement();
			$selector->execute();
			$clusterfColumn= $selector->getSingleResult();
			$bCreateCluster= false;
		}else
			$bCreateCluster= true;

		$this->aSetAlso[$column][STINSERT]= $clusterfColumn;
		$res= "NOCLUSTERCREATE";		
		if( $bCreateCluster &&
			STUserSession::sessionGenerated()	)
		{
			$session= STUserSession::instance();
			$res= $session->createCluster($clusterfColumn, $accessInfoString, $addGroup);
			if(	$addGroup &&
				$res == "NOERROR"	)
			{
				foreach($parentCluster as $parent)
				{
					if($parent['action'] == STLIST)
					{
						// the created group inside session->createCluster
						// will be the same as the cluster-name
						$groupName= $clusterfColumn;
						$session->joinClusterGroup($parent['cluster'], $groupName);
					}
				}
			}
		}
		return $res;
	}
	/**
	 * pre-define an access cluster for every row,
	 * where the row only be shown if the user has the cluster.<br />
	 *  beschreibe für welche Zugriffs Berechtigung der Cluster
	 * @param string $column column or alias name where the cluster should be stored
	 * @param string $prefix the prefix can be a column name or alias name of a column,
	 *                         which is enclosed in curly brackets; any characters can be chosen outside the brackets.
	 *                         as example: 'AccessCountry_{c:<column-name>}:'.
	 *                         The prefix will be followd with a count from table entrys.
	 * @param string $accessInfoString the string which should be written inside logging table
	 * @param boolean $addGroup whether by creating the cluster, also a group for it should be created
	 */
	public function accessCluster(string $column, string $prefix= "", string $accessInfoString= "", $addGroup= true)
	{
	    if(STCheck::isDebug())
	    {
    		STCheck::param($column, 0, "string");
    		STCheck::param($prefix, 0, "string");
    		STCheck::param($accessInfoString, 2, "string", "empty(string)");
    		STCheck::param($addGroup, 43, "boolean");
	    }
        $this->cluster(STLIST, $column, $prefix, $accessInfoString, $addGroup);
	}
	/**
	 * pre-define an admin cluster for every row,
	 * where the user can insert/update or delete content inside the linked tabel
	 * if the user has the cluster.<br />
	 * @param string $column column or alias name where the cluster should be stored
	 * @param string $prefix the prefix can be a column name or alias name of a column,
	 *                         which is enclosed in curly brackets; any characters can be chosen outside the brackets.
	 *                         as example: 'AccessCountry_{c:<column-name>}:'.
	 *                         The prefix will be followd with a count from table entrys.
	 * @param string $accessInfoString the string which be used as Description for cluster
	 *                                  and will be always written inside logging table.<br />
	 *                                  The string can be cantain an @ which will be replaced with all
	 *                                  defined identif-columns
	 * @param boolean $addGroup whether by creating the cluster, also a group for it should be created
	 */
	public function adminCluster(string $column, string $prefix= "", string $accessInfoString= "", $addGroup= true)
	{
	    if(STCheck::isDebug())
	    {
	        STCheck::param($column, 0, "string");
	        STCheck::param($prefix, 1, "string");
	        STCheck::param($accessInfoString, 2, "string", "empty(string)");
	        STCheck::param($addGroup, 3, "boolean");
	    }
	    $this->cluster(STADMIN, $column, $prefix, $accessInfoString, $addGroup);
	}
	/**
	 * pre-define a cluster for every row,
	 * where the row only be shown if the user has the cluster.<br />
	 *  beschreibe für welche Zugriffs Berechtigung der Cluster 
	 * @param string $access describe for which permission the cluster should have access.<br />
	 *                       If this method will be used, by first calling the variable have to be STLIST
	 *                       to see the new inserted entry in the STListBox. If you want that the user
	 *                       have permission to change anything inside the linked table, fill this variable
	 *                       with STADMIN. Than the user in the upper linked STListBox have access to insert,
	 *                       update or delete new entrys. Also allowed all other entrys by this variable, where
	 *                       the developer have to handle the cluster permission by his own. He can get the
	 *                       permissions inside the upper container with the methode <code>getLinkedCluster()</code> 
	 * @param string $column column or alias name where the cluster should be stored
	 * @param string $prefix the prefix can be a column name or alias name of a column, 
	 *                         which is enclosed in curly brackets; any characters can be chosen outside the brackets.
	 *                         as example: 'AccessCountry_{c:<column-name>}:'.
	 *                         The prefix will be followd with a count from table entrys.
	 * @param string $accessInfoString the string which should be written inside logging table
	 * @param boolean $addGroup whether by creating the cluster, also a group for it should be created
	 */
	public function cluster(string $access, string $column, string $prefix, string $accessInfoString= "", $addGroup= true)
	{
		$action= $this->container->getAction();
		$field= $this->findColumnOrAlias($column);
		if($action == STLIST)
			$this->getColumn($field['column']);
		
		$this->sAccessClusterColumn[]= array(	"action"=>	$access,
												"column"=>	$field['column'],
												"prefix"=>	$prefix,
												"info"=>	$accessInfoString,
												"group"=>	$addGroup			);
		//$this->addAccessClusterColumn($field['column'], $parentCluster, $clusterfColumn, $accessInfoString, $addGroup, $access);
	}
	public function createDynamicClusters()
	{
		foreach($this->sAccessClusterColumn as &$cluster)
		{
			$access= $cluster['action'];
			$column= $cluster['column'];
			$prefix= $cluster['prefix'];
			$accessInfoString= $cluster['info'];
			$addGroup= $cluster['group'];

			$action= $this->container->getAction();
			if($action == STINSERT)
			{
				if(isset($this->asAccessIds[$action]["cluster"]))
					$parentCluster= $this->asAccessIds[$action]["cluster"];
				else
					$parentCluster= "";
				if($this->container->currentContainer())// if container is not the current one,
				{										// does not need an parent-cluster,
					if(!$parentCluster)					// because an insert box can not appear
					{
						if(!isset($parentTable))
							$parentTable= $this;
						$parentCluster= $this->container->getLinkedCluster($action);
					}else
					{ 
						if(preg_match("/,/", $parentCluster))
							$parentCluster= preg_split("/,/", $parentCluster);
						else 
							$parentCluster= array($parentCluster);
					}
				}
				//Tag::alert(!$parentCluster, "STDbTable::accessCluster()", "no parentCluster be set");
				$clusterfColumn= $this->createDynamicClusterString($access, $prefix, $column, $accessInfoString);
				if(	isset($clusterfColumn) &&
					trim($clusterfColumn) != ""	)
				{
					$cluster['cluster']= $clusterfColumn;
					$cluster['info']= $accessInfoString;
				}
					
				if($parentCluster == "")
					$parentCluster= null;//no parent cluster exist
				$res= $this->addAccessClusterColumn($column, $parentCluster, $clusterfColumn, $accessInfoString, $addGroup, $access);
				$cluster['creation']= $res;
			}else
				STCheck::alert(true, "STDbTable::createDynamicClusters()", "action $action not allowed for creating dynamic clusters");
		}
	}
	public function removeDynamicClusters()
	{
		$query= new STQueryString();
		$stget= $query->getUrlParamValue("stget");
		foreach($this->sAccessClusterColumn as $field)
		{
			if(	STUserSession::sessionGenerated() &&
				isset($stget['limit'][$this->Name])	)
			{
				$selector= new STDbSelector($this);
				$selector->select($this->Name, $field['column'], $field['column']);
				foreach($stget['limit'][$this->Name] as $col=>$val)
				{
					$sWhere= "$col=";
					if(is_numeric($val))
						$sWhere.= "$val";
					else
						$sWhere.= "'$val'";
					$selector->where($this->Name, $sWhere);
				}
				if(!$selector->execute())
					return;
				$cluster= $selector->getSingleResult();

				$toClustersAlso= array();
				$parentClusters= $this->container->getLinkedCluster(STDELETE);
				foreach($parentClusters as $parent)
				{
					if($parent['action'] == STLIST)
						$toClustersAlso[]= $parent['cluster'];
				}
				$session= STUserSession::instance();
				$session->removeCluster($cluster, $toClustersAlso);
			}
		}
	}
	public function currentTable()
	{
		$get= new STQueryString();
		$get= $get->getArrayVars();
		$tableName= "";
		if(isset($get["stget"]["table"]))
			$tableName= $get["stget"]["table"];
		else
			$tableName= $this->container->getTableName();
		if($this->Name==$tableName)
			return true;
		return false;
	}
	public function getAccessClusterColumns()
	{
		return $this->sAccessClusterColumn;
	}
	protected function createDynamicClusterString(string $access, string $prefix, string $column, string &$accessInfoString= "")
	{
		$preg= $this->splitClusterString($prefix);
		if(count($preg))
		{
			$post= new STPostArray();
			$field= $this->findAliasOrColumn($preg[2][0]);
			$begin= substr($prefix, 0, $preg[0][1]);
			if($post->exist($field['alias']))
				$columnContent= $post->getValue($field['alias']);
			elseif($post->exist($field['column']))
				$columnContent= $post->getValue($field['column']);
			else
				$columnContent= "unknownColumnContent";
			$end= substr($prefix, $preg[2][1]+strlen($preg[2][0])+1);
			$sRv= $begin.$columnContent.$end;			
		}else
			$sRv= $prefix;
			
		//toDo: implement only when field not unique
		$field= $this->getColumnField($field['column']);
		$tableEntrys= $this->getTableEntrys();
		// toDo: if field type is unique, do not implement tableEntrys
		$sRv.= "[".($tableEntrys+1)."]";
		switch($access)
		{
			case STLIST:
				$sRv.= "access";
				break;
			case STADMIN:
				$sRv.= "admin";
				break;
			default:
				$sRv.= $access;
				break;
		}
		

		if($accessInfoString=="")
		{
			if($access==STLIST)
			{
				$accessInfoString= "permission to see entry '@' ";
				$accessInfoString.= "in table ".$this->getDisplayName();
			}elseif($access==STINSERT)
			{
				$accessInfoString= "permission to create new entry";
				$accessInfoString.= " of '@' in table ".$this->getDisplayName();
			}elseif($access==STUPDATE)
			{
				$accessInfoString= "permission to change entry";
				$accessInfoString.= " of '@' in table ".$this->getDisplayName();
			}elseif($access==STDELETE)
			{
				$accessInfoString= "permission to delete entry";
				$accessInfoString.= " of '@' in table ".$this->getDisplayName();
			}elseif($access==STADMIN)
			{
				$accessInfoString= "changing-permission for entrys";
				$accessInfoString.= " of '@' in table ".$this->getDisplayName();
			}else
				$accessInfoString= "permission to '$access' entry '$columnContent' inside ".$this->getDisplayName();
		}
		$accessInfoString= preg_replace("/@/", $columnContent, $accessInfoString);	
		
		return $sRv;

	}
	private function splitClusterString(string $cluster)
	{
	    $preg= array();
	    if(preg_match("/([\\\\]*)\{([^\\\\}]*)\}/", $cluster, $preg, PREG_OFFSET_CAPTURE))
	    {
	        if(strlen($preg[1][0]) % 2)
	        {// maybe the wantet string is "...\\{-{<column>}-}"
	            $newBeging= $preg[2][1];
	            $cluster= substr($cluster, $newBeging);
	            $preg= $this->splitClusterString($cluster);
	            if(count($preg))
	            {
	                foreach($preg as &$area)
	                    $area[1]+= $newBeging;
	            }
	        }
	    }
	    return $preg;
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
		
		st_print_r($this->aAuto_increment);
		showBackTrace();
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
	public function joinOver($table, string $join= STINNERJOIN)
	{
	    // if parameter defined with string and it was given an STDbTable, __toString() will be given
	    STCheck::param($table, 0, "string");
	    STCheck::param($join, 1, "check", $join==STINNERJOIN||$join==STLEFTJOIN||$join==STRIGHTJOIN, "STINNERJOIN, STLEFTJOIN or STRIGHTJOIN");
	    
	    $table= $this->db->getTableName($table);
	    STBaseTable::joinOver($table, $join);
	}
	public function noJoinOver($table)
	{
	    // if parameter defined with string and it was given an STDbTable, __toString() will be given
	    STCheck::param($table, 0, "string");
	    
	    $table= $this->db->getTableName($table);
	    STBaseTable::noJoinOver($table);
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
		    unset($this->aFks[$toTableName]["table"]);
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
    	        STCheck::echoDebug("db.statement", "create $nr. statement for <b>select</b> inside table ".$this->toString());
    	        echo "<hr />";
    	        //STCheck::info(1, "STDbTable::getStatement()", "called STDbTable::<b>getStatement()</b> method from:", 1);
	        }
	        if(STCheck::isDebug("db.statement.from"))
	            {showBackTrace(1);echo "<br />";}
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
	    $statement= $this->getStatementA($bFromIdentifications);
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
    	    $bwhere= false;
    	    $border= false;
    	    foreach($arr as $clause)
    	    {
    	        if(strtolower(substr($clause, 0, 6)) == "select")
    	            $this->aStatement['select']= $clause;
                elseif(strtolower(substr($clause, 0, 4)) == "from")
                    $this->aStatement['table']= $clause;
                elseif(strtolower(substr($clause, 0, 5)) == "where")
                {
                    $this->aStatement['where']= $clause;
                    $bwhere= true;
                }elseif(strtolower(substr($clause, 0, 5)) == "order")
                {
                    $this->aStatement['order']= $clause;
                    $border= true;
                }
    	    }
    	    if(!$bwhere)
    	        $this->aStatement['where']= false;
    	    if(!$border)
    	        $this->aStatement['order']= false;
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
	/**
	 * display sql statement string splitted by some keyords
	 * for better human reading
	 */
	public function displayWrappedStatement()
	{
	    $array= $this->getWrappedStatement();
	    foreach($array as $row)
	        echo "$row<br />";
	}
	/**
	 * get sql statement string splitted by some keyords
	 * for better human reading
	 * 
	 *  @return array Array of sql strings
	 */
	public function getWrappedStatement()
	{
	    $stats= array(  "show", "select", "update", "delete", "from",
            	        array( "inner join", "left join", "right join" ),
            	        "where", "having", "order", "limit"                  );
	            
	    return stTools::getWrappedStatement($stats, $this->getStatement());
	}
	private function getStatementA(bool $bFromIdentifications= false)
	{
	    if(STCheck::isDebug())
	    {
	        STCheck::param($bFromIdentifications, 0, "bool");
	        
	        $msg= "create sql statement from table ";
	        $msg.= $this->toString();
	        $msg.= " inside container <b>".$this->container->getName()."</b>";
	        STCheck::echoDebug("db.statements", $msg);
	    }
	    
	    $this->modifyQueryLimitation();
	    $aliasTables= array();
	    //STCheck::write("search for aliases");
	    $aliasTables= $this->getAliasOrder();
	    // search for tables which should also joined
	    $joinTables= array();
	    $joins= $this->getAlsoJoinOverTables();
	    if(count($joins) > 0)
	    {
	        foreach($joins as $table=>$join)
	            $joinTables[$table]= $aliasTables[$table];
	    }
	    if(STCheck::isDebug("db.statements.alias"))
	    {
	        $space= STCheck::echoDebug("db.statements.alias", "follow alias tables can be used:");
	        st_print_r($aliasTables, 1, $space);
	    }
	    // create statement
	    $bMainTable= !$bFromIdentifications;// wenn der erste ->getSlectStatement() Aufruf nicht für
	    // die Haupttabelle getätigt wird, werden nur die Tabellen Identificatoren genommen
	    $mainTable= $bMainTable;
	    if($mainTable)
	        $mainTable= $this;	// alex 24/05/2005:	nur wenn der erste Aufruf für Haupttabelle getätigt wird
                    	        //					muss zur kontrolle bei einem STDbSelector
                    	        //					die Haupttabelle als dritter Parameter mitgegeben werden
	        
	    $aSubstitutionTables= array();
	    $statement= $this->getSelectStatement($aliasTables, $aSubstitutionTables, $bFromIdentifications);
        // implement tables which are joined from user
        if(count($joinTables))
            $aliasTables= array_merge($aliasTables, $joinTables);
        $whereAliases= $this->getWhereAliases();
        if(count($whereAliases))
            $aliasTables= array_merge($aliasTables, $whereAliases);
        if(STCheck::isDebug("db.statements"))
        {
            $space= STCheck::echoDebug("db.statements", "need follow tables inside select-statement");
            st_print_r($aliasTables, 1, $space);
            STCheck::echoDebug("db.statements", "need follow <b>select</b> statement: $statement");
        }
        $this->newWhereCreation($aliasTables, $aSubstitutionTables);        
        $tableStatement= $this->getTableStatement($aliasTables, $aSubstitutionTables);
        STCheck::echoDebug("db.statements", "need follow aditional <b>table</b> statement: $tableStatement");
        $statement.= " $tableStatement";
        
        // create $bufferWhere to copy the original
        // behind the function getWhereStatement()
        // back into the table
        // problems by php version 4.0.6:
        // first parameter in function is no reference
        // but it comes back the changed values
        $bufferWhere= $this->oWhere;
        $whereStatement= $this->getWhereStatement("where", $aliasTables, $aSubstitutionTables);
        if(STCheck::isDebug("db.statements"))
        {
            if(trim($whereStatement) == "")
                $msg= "do not need a <b>where</b> statement";
                else
                    $msg= "need follow <b>where</b> statement: $whereStatement";
                    STCheck::echoDebug("db.statements", $msg);
        }
        $this->oWhere= $bufferWhere;
        if($whereStatement)
        {
            $ereg= array();
            preg_match("/^(and|or)/i", $whereStatement, $ereg);
            if(isset($ereg[1]))
            {
                if($ereg[1] == "and")
                    $nOp= 4;
                else
                    $nOp= 3;
                $whereStatement= substr($whereStatement, $nOp);
            }
            $statement.= " $whereStatement";
        }
        
        // Order Statement hinzufügen wenn vorhanden
        if(	!isset($this->bOrder) ||
            $this->bOrder == true		)
        {
            $orderStat= $this->getOrderStatement($aliasTables);
            $orderStat= trim($orderStat);
            if(	$orderStat !== "" &&
                $orderStat != "ASC" &&
                $orderStat != "DESC"		)
            {
                $statement.= " order by $orderStat";
                STCheck::echoDebug("db.statements", "need follow <b>order</b> statement: order by $orderStat");
            }else
                STCheck::echoDebug("db.statements", "do not need an <b>order</b> statement");
        }
        $limitStat= $this->getLimitStatement(false);
        if($limitStat)
        {
            $statement.= $limitStat;
            STCheck::echoDebug("db.statements", "<b>limit</b> result with: $limitStat");
        }else
            STCheck::echoDebug("db.statements", "do not need a <b>limit</b> statement");
        if(count($this->aOtherTableWhere))
        {
            STCheck::is_warning(1, "STDatabase::getStatement()", "does not reach all where-statements:");
            if(Tag::isDebug())
            {
                echo "<b>do not make the follow where-clausels:</b>";
                st_print_r($this->aOtherTableWhere);
                echo "-------------------------------------------------------<br />\n";
            }
            $this->aOtherTableWhere= array();
        }
        if(STCheck::isDebug())
        {
            $stats= array(  "show", "select", "update", "delete", "from",
                array( "inner join", "left join", "right join" ),
                "where", "having", "order", "limit"                  );
            STCheck::echoDebug("db.statements", "<b>finisched <i>select</i> statement</b>:");
            $aStatement= stTools::getWrappedStatement($stats, $statement);
            STCheck::echoDebug("db.statements", $aStatement);
        }
        return $statement;
	}
	// alex 24/05/2005:	boolean bMainTable auf die Haupttabelle ge�ndert
	//					da dort wenn sie ein Selector ist noch �berpr�fungen
	//					vorgenommen werden sollen, ob der FK auch ausgef�hrt wird
	/**
	 * create part of sql select statement
	 * 
	 * @param array $aTableAlias array of all exist alias for tables exist, which gives back all alias which needed
	 * @param array $aSubstitutionTables array of correct table names which are point to him self or an used table
	 * @param bool $bUseIdentifications whether should use for the first select only the identification columns
	 */
	private function getSelectStatement(array &$aTableAlias, array &$aSubstitutionTables, bool $bUseIdentifications)
	{
	    if(isset($this->aStatement['select']))
	    {
	        if(STCheck::isDebug("db.statements.select"))
	        {
	            $msg[]= "take predefined select statement";
	            $msg[]= "\"".$this->aStatement['select']."\"";
	            STCheck::echoDebug("db.statements.select", $msg);
	        }
	        if(isset($this->aStatement['selectAlias']))
	            $aTableAlias= $this->aStatement['selectAlias'];
	        else
	            $aTableAlias= array();
	        return $this->aStatement['select'];
	    }
	    $statement= "select ";
	    if($this->isDistinct())
	        $statement.= "distinct ";
	    $aliasTable= $aTableAlias[$this->getName()];
	    $statement.= $this->getSelectStatementA(!$bUseIdentifications, $this, $aliasTable, $aTableAlias, $aSubstitutionTables);
	    $this->aStatement['select']= $statement;
	    $this->aStatement['selectAlias']= $aTableAlias;
	    return $statement;
	}
	/**
	 * create part of sql select statement
	 *
	 * @param bool $bFirstSelect describes whether the method is called by first time.<br />
	 *                           (testing by main-table is same then own object not possible,
	 *                            because the own object/table can also be an foreignKey-table)
	 * @param STDbTable $oMainTable object of the first table from which all other are drawn
	 * @param string $sUseAliasForOwnTable alias name for current table.<br />necassary for table point to him self
	 * @param array $aTableAlias array of all exist alias for tables exist, which gives back all alias which needed
	 * @param array $aSubstitutionTables array of correct table names which are point to him self or an used table
	 */
	private function getSelectStatementA(bool $bFirstSelect, STDbTable $oMainTable, string $sUseAliasForOwnTable, array &$aTableAlias, array &$aSubstitutionTables)
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
        //$aShowTypes= $this->showTypes;
        $aliasCount= count($aTableAlias);
        $columnNr= 0;
        foreach($aNeededColumns as $column)
        {// loop the array for all exist columns            
            $columnName= $column["column"];
            STCheck::echoDebug("db.statements.select", "run for ".++$columnNr.". column $columnName inside table '{$this->getName()}'");
            
            $preg= array();
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
            if(STCheck::isDebug("db.statements.select"))
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
                echo "\n<br />";
                $space= STCheck::echoDebug("db.statements.select", $msg);
                st_print_r($column, 1, $space);
            }
            
            if($aliasCount>1)
            {
                $fkTableName= null;
                if( (   !typeof($oMainTable, "STDbSelector") &&
                        isset($column['type']) && // <- otherwise field is PK for update or delete inside STListBox
                        $column['type'] == "select"             ) ||
                    (   typeof($oMainTable, "STDbSelector") &&
                        (   !isset($oMainTable->abOrigChoice["select"]) ||
                            $oMainTable->abOrigChoice["select"] == "true"   )   )    )
                {
                    // alex 24/05/2005:	if table is an existing foreign Key
                    //                  and the current/main table an STDbSelector,
                    //                  than check whether a table version exist in the selector.
                    //					Otherwise it shouldn't made any output from the linked table.
                    // alex 24/02/2023: now search only for foreign keys when inside an STDbSelector container
                    //                  no extra choice was made
                    $fkTableName= $this->getFkTableName($column["column"]);
                    if(STCheck::isDebug() && isset($fkTableName))
                    {
                        STCheck::echoDebug("db.statements.select", "from ".get_class($this)." ".$this->getName()." for column ".$column["column"]." is Fk-Table \"".$fkTableName."\"");
                    }
                }
                if(	!$fkTableName ) // if no FK table exist, the column can only be from the current table
                {
                    if($column['table'] == $this->getName())
                    {
                        $aliasTable= $sUseAliasForOwnTable;
                    }else
                        $aliasTable= $aTableAlias[$column["table"]];
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
                    $newAlias= null;
                    STCheck::echoDebug("db.statements.select", "");
                    STCheck::echoDebug("db.statements.select", "need column from foreign table");
                    if( $fkTableName == $this->getName() ||
                        array_search($fkTableName, $aUseAliases) !== false  )
                    {
                        $subTableName= $fkTableName."_sub";
                        $count= 0;
                        do{
                            $count++;
                            $newName= "$subTableName$count";
                        }while( array_search($newName, $aTableAlias) !== false ||
                            array_search($newName, $aUseAliases) !== false      );
                        $subTableName= $newName;
                        $newAlias= "t".count($aTableAlias);
                        $aTableAlias[$subTableName]= $newAlias;
                        $aUseAliases[$newAlias]= $subTableName;
                        $aSubstitutionTables[$subTableName]= $fkTableName;
                    }
                    //$oOther= $this->FK[$fkTableName]["table"]->container->getTable;
                    $containerName= $this->getFkContainerName($column["column"]);
                    //$container= &STBaseContainer::getContainer($containerName);
                    // 13/06/2008:	alex
                    //				fetch table from own table because
                    //				if own table is an DBSelector maybe only
                    //				in him can be deleted an column or identif-column
                    $oOther= $this->getTable($fkTableName, $containerName);
                    $allAliases= $aTableAlias;
                    if(isset($newAlias))
                        $aliasTable= $newAlias;
                    else
                        $aliasTable= $aTableAlias[$fkTableName];
                    $fkStatement= $oOther->getSelectStatementA(/*firstSelect*/false, $oMainTable, $aliasTable, $allAliases, $aSubstitutionTables);
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
        if( $oMainTable == $this &&
            (count($aTableAlias) + count($aSubstitutionTables)) <= 1    )
        {
            $statement= $singleStatement;
        }
        $statement= substr($statement, 0, strlen($statement)-1);
        STCheck::echoDebug("db.statements.select", "createt statement is \"".$statement."\"");
        return $statement;
	}
	private function getTableStatement(array &$aTableAlias, array $aSubstitutionTables)
	{
	    if(isset($this->aStatement['table']))
	    {
	        if(STCheck::isDebug("db.statements.table"))
	        {
	            $msg[]= "take predefined select statement";
	            $msg[]= "\"".$this->aStatement['table']."\"";
	            STCheck::echoDebug("db.statements.table", $msg);
	        }
	        if(isset($this->aStatement['tableAlias']))
	            $aTableAlias= $this->aStatement['tableAlias'];
	        else
	            $aTableAlias= array();
	        return $this->aStatement['table'];
	    }
	    $statement= "from ".$this->Name;
	    if(count($aTableAlias) <= 1)
	    {
	        $this->aStatement['table']= $statement;
	        $this->aStatement['tableAlias']= $aTableAlias;
	        return $statement;
	    }
	    $maked= array();
	    $maked[$this->Name]= "finished";
	    $statement.= " as ".$aTableAlias[$this->Name]." ";
	    $statement.= $this->getTableStatementA($this, $aTableAlias, $maked, $aSubstitutionTables, /*first access*/true);
	    $this->aStatement['table']= $statement;
	    $this->aStatement['tableAlias']= $aTableAlias;
	    return $statement;
	}
	private function getTableStatementA(STDbTable $oMainTable, array &$aTableAlias, array &$maked, array $aSubstitutionTables, bool $bFirstAccess= false)
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
    	    $this->db->searchJoinTables($aTableAlias, $aSubstitutionTables);
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
    			//$sTableName= $oTable->getName();
    			$bNeedFkColumn= false;
    			foreach($aNeededColumns as $aColumn)
    			{
    				if(	$join["own"]==$aColumn["column"]
    					and
    					!isset($maked[$table])				)
    				{// Query whether the FK column is required
    					$bNeedFkColumn= true;
    					break;
    				}
    			}
    			$sSubstitutionTable= "";
    			$sMakeSubstitutionAlias= "";
    			if(	$bNeedFkColumn===false &&
    				isset($aTableAlias[$table]) &&
    				(   !isset($maked[$table]) ||
    				    (   in_array($table, $aSubstitutionTables) &&
    				        !isset($maked[array_search($table, $aSubstitutionTables)])   )   )  )
    			{// wenn die FK Spalte nicht in den ben�tigten Spalten ist,
    			 // das objekt aber vom Typ STDbSelector ist (wobei die FK-Spalten nicht aufgelistet werden)
    			 // und die Tabelle in den Aliases ist,
    			 // wird sie doch f�r den join ben�tigt
    			    if( isset($maked[$table]) &&
    			        in_array($table, $aSubstitutionTables)   )
    			    {
    			        $sSubstitutionTable= array_search($table, $aSubstitutionTables);
    			        $sMakeSubstitutionAlias= $aTableAlias[$sSubstitutionTable];
    			    }
    			 	$bNeedFkColumn= true;
    			}
    			if(STCheck::isDebug("db.statements"))
    			{
    			    if(STCheck::isDebug("db.statements.table"))
    			        $debugtype= "db.statements.table";
    			    else
    			        $debugtype= "db.statements.where";
    				if($bNeedFkColumn===false)
    				{
    					$debugString= "do not need foreign key from column ".$join["own"]." to table ".$table." for statement";
    					STCheck::echoDebug($debugtype, $debugString);
    					if(isset($maked[$table]))
    					    STCheck::echoDebug($debugtype, "join was inserted before");
    				}elseif(!isset($aTableAlias[$table]))
    				    STCheck::echoDebug($debugtype, "no such table $table for column ".$join["own"]." in createt Alias-Array");
    				else
    				{
    				    $asSubstitution= "";
    				    if($sSubstitutionTable != "")
    				        $asSubstitution= "as $sSubstitutionTable substitution ";
    				    STCheck::echoDebug($debugtype, "need foreign key from column ".$join["own"]." to table $table $asSubstitution".
    												"for select statement from container ".$oTable->container->getName());
    				}
    			}
    			if(	$bNeedFkColumn &&
    				isset($aTableAlias[$table])	)
    			{// add foreign key statement inside on-clause with join type (inner/left/right join)
    			    if(isset($this->aJoinOverTables[$table]))
    			        $joinArt= $this->db->getSqlJoinStatementLinkName($this->aJoinOverTables[$table]);
			        else
			            $joinArt= $this->db->getSqlJoinStatementLinkName($join["join"]);
			        $statement.= " $joinArt";
    
    				$database= null;
    				if(isset($aTableAlias["db.$table"]))
    					$database= $aTableAlias["db.$table"];
    				if($database) 	// wenn im aTableAlias Array eine Datenbank angegeben ist, die fremde DB
    				{				// von dieser auch im statement angeben
    					Tag::echoDebug("db.statements.table", "table is for database ".$database);
    					$database.= ".";
    				}
    
    
    				if($sMakeSubstitutionAlias != "")				// wenn der join innerhalb der gleichen Tabelle ist
    				{												// darf der AliasName nicht der gleiche sein
    				    $sTableAlias= $sMakeSubstitutionAlias;    	// (zb. t1.parentID=t1.ID) weil die eigene Tabelle
    					                               				// nochmals im Join angegeben werden muss
    																// also zb. t1.parentID=t5.ID
    				}else
    					$sTableAlias= $aTableAlias[$table];
    
    				if(Tag::isDebug())
    				{
    					Tag::echoDebug("db.statements.table", "make ".$joinArt." join to table ".$database.$table.
    												" with alias-name ".$sTableAlias);
    				}
    				$where= $ownTableAlias.".".$join["own"];
    				$where.= "=".$sTableAlias.".".$join["other"];
      				$statement.= " join ".$database.$table." as ";
      				if($sMakeSubstitutionAlias != "")
      				    $statement.= $sMakeSubstitutionAlias;
      				else
      				    $statement.= $sTableAlias;
      				$statement.= " on ".$where;
      				if(STCheck::isDebug())
      				{
      				    STCheck::echoDebug("db.statements.table", "make ".$joinArt." join to table ".$database.$table.
      				        " with alias-name ".$sTableAlias);
      				    STCheck::echoDebug("db.statements.where", "add foreign key in on-clause \"$where\"");
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
      				if($sSubstitutionTable == "")
      				{// do not add query limitation by a substitution table point to him self
      				    // toDo: how is possible to add where clauses for tables point to him self and need this anybody  
      				    $statement.= $fromTable->addJoinLimitationByQuery($aTableAlias);
      				    $whereStatement= $fromTable->getWhereStatement("on", $fromTable, $aTableAlias, $aSubstitutionTables);
      				    if($whereStatement)
      				    {
      				        if(!preg_match("/^[ \t]*and/", $whereStatement))
      				            $whereStatement= "and $whereStatement";
      				            $statement.= " ".$whereStatement;
      				            STCheck::echoDebug("db.statements.table", "get on condition '$whereStatement' from table '".$fromTable->getName()."(".$fromTable->ID.")'");
      				    }
      				}
    				if($oMainTable->getName() != $fromTable->getName())
    				{
    				    $whereStatement= $oMainTable->getWhereStatement("on", $fromTable, $aTableAlias, $aSubstitutionTables);
        				if($whereStatement)
        				{
        				    if(!preg_match("/^[ \t]*and/", $whereStatement))
        				        $whereStatement= "and $whereStatement";
        				        $statement.= " ".$whereStatement;
        				        STCheck::echoDebug("db.statements.table", "get on condition '$whereStatement' from main table '".$oMainTable->getName()."(".$oMainTable->ID.")'");
        				}
    				}
    
    				if($sSubstitutionTable != "")
    				    $makedTable= $sSubstitutionTable;
				    else
				        $makedTable= $table;
				    if(!isset($maked[$makedTable]))
    				{// debug-Versuch f�r komplikationen -> bitte auch member-Variable counter am Funktionsanfang aktivieren
    				 //			if($this->counter==4){
    				 //				echo "$statement OK".$this->counter;exit;}else $this->counter++;
    
    				    $maked[$makedTable]= "finished";
    					$statement.= $fromTable->getTableStatementA($oMainTable, $aTableAlias, $maked, $aSubstitutionTables);
    					Tag::echoDebug("db.statements.table", "back in table <b>".$oTable->getName()."</b>");
    				}
    			}// end of if(	$bNeedFkColumn && isset($aTableAlias[$table])	)
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
				    if(isset($this->aJoinOverTables[$sBackTableName]))
				        $joinArt= $this->db->getSqlJoinStatementLinkName($this->aJoinOverTables[$sBackTableName]);
			        else
			            $joinArt= $this->db->getSqlJoinStatementLinkName($join["join"]);
                    $statement.= " ".$joinArt." join ".$database.$sBackTableName." as ".$sTableAlias;
                    $statement.= " on ".$ownTableAlias.".".$join["other"];
                    $statement.= "=".$sTableAlias.".".$join["own"];
					if(Tag::isDebug())
					{
						Tag::echoDebug("db.statements.table", "make ".$joinArt." join to table ".$database.$sBackTableName.
													" with alias-name ".$sTableAlias);
					}
					$statement.= $BackTable->addJoinLimitationByQuery($aTableAlias);
					$statement.= $oBackTable->getTableStatementA($oMainTable, $aTableAlias, $maked, $aSubstitutionTables);
					$whereStatement= $BackTable->getWhereStatement("on", $BackTable, $aTableAlias, $aSubstitutionTables);
					if($whereStatement)
					{
					    if(!preg_match("/^[ \t]*and/", $whereStatement))
					        $whereStatement= "and $whereStatement";
					        $statement.= " ".$whereStatement;
					        STCheck::echoDebug("db.statements.table", "get on condition '$whereStatement' from table '".$fromTable->getName()."(".$fromTable->ID.")'");
					}
					if($oMainTable->getName() != $sBackTableName)
					{
					    $whereStatement= $oMainTable->getWhereStatement("on", $BackTable, $aTableAlias, $aSubstitutionTables);
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
		    count($maked) < (count($aTableAlias) - count($aSubstitutionTables))   )
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
		        showLine();
		        echo "statement:$statement<br>";
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
            		    showBackTrace();
            		    echo "<br /><br /><br />";
            		    exit;
    		        }
		        }
		    }
		    if(STCheck::isDebug())
		    {
		        $nr= STCheck::getIncreaseNr("db.statement");
		        $msg= "do not join to all alias tables see STCheck::debug('db.statements.table', $nr)";
		        STCheck::alert(count($maked) < count($aTableAlias), "STDbTable::getTableStatementA()", $msg, 2);
		    }
		}
		STCheck::echoDebug("db.statements.table", "TableStatement - Result from table '".$oTable->getName()."'= '$statement'");
		return $statement;
	}
	private function getWhereAliases() : array
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
	/*private*/function newWhereCreation(array $aliases= null, array $aSubstitutionTables= null)
	{
	    $oWhere= $this->getWhere();
	    if(isset($oWhere))
	        $oWhere->reset();
	    if(isset($aliases))
	    {
    	    foreach($aliases as $tableName=>$alias)
    	    {
    	        if(isset($aSubstitutionTables[$tableName]))
    	            $searchTableName= $aSubstitutionTables[$tableName];
    	        else
    	            $searchTableName= $tableName;
    	        $table= $this->getTable($searchTableName);
    	        $table->newWhereCreation();
    	    }
	    }
	}
	/**
	 * create where statement
	 * (also called from some other worker objects (like STDbUpdater)
	 * 
	 * @param string $condition for which clause ('where' or 'on')
	 * @param STDbTable|array $from if set codition as 'on' this parameter have to be set
	 * @param array $aliases array of alias names
	 * @param array $aSubstitutionTables array of correct table names which are point to him self or an used table
	 */
	public function getWhereStatement(string $condition, $from= null, array $aliases= null, array $aSubstitutionTables= null)
	{
	    $bSetFromAlias= false;
	    if( !isset($from) ||
	        typeof($from, "array") )
	    {
	        if(typeof($from, "array"))
	        {
	            $bSetFromAlias= true;
	        }
	        $aSubstitutionTables= $aliases;
	        $aliases= $from;
	        if($condition == "on")
	            STCheck::param($from, 1, "STDbTable");
	        $from= $this;
	    }
	    if(STCheck::isDebug())
	    {
	        STCheck::param($from, 1, "array", "STDbTable");
	        STCheck::param($condition, 0, "check", 
	            $condition=="on"||$condition=="where"||$condition=="insert"||$condition=="update", 
	            "'on', 'where', 'insert'", "'update' strings");
            if(isset($aliases))
                STCheck::param($aSubstitutionTables, 3, "array");
	           
            if(STCheck::isDebug("db.statements.where"))
            {
                $space= STCheck::echoDebug("db.statements.where", "stored where statement for table ".$this->getName());
                st_print_r($this->oWhere, 5, $space);
            }
	    }
	    if(isset($this->aStatement[$condition]))
	    {
	        if(STCheck::isDebug("db.statements.where"))
	        {
	            $msg[]= "take predefined where statement";
	            if($condition == "where")
	                $msg[]= "\"".$this->aStatement['where']."\"";
	            else
	                $msg[]= "\"".$this->aStatement['where'][$from->getName()]."\"";
	            STCheck::echoDebug("db.statements.where", $msg);
	        }
	        if($condition == "where")
	            return $this->aStatement['where'];
            $tabName= $from->getName();
	        if(isset($this->aStatement[$condition][$tabName]))
	            return $this->aStatement[$condition][$tabName];
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
	    $aMade= array();
	    $statement= "";
	    $ostwhere= $this->getWhere();
	    if(typeof($ostwhere, "STDbWhere"))
	    {
	        STCheck::echoDebug("db.statements.where", "execute for own table <b>".$this->Name."</b>");
	        $res= $ostwhere->getStatement($from, $condition, $aliases);
	        $statement= $res['str'];
	        $aMade[]= $this->getName();
	    }
	    if(is_array($aliases))
	    {
	        foreach($aliases as $tableName=>$alias)
	        {
	            if( !in_array($tableName, $aMade) &&
	                !isset($aSubstitutionTables[$tableName])   )
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
	                        $res= $ostwhere->getStatement($table, $condition, $aliases);
	                        $whereStatement= $res['str'];
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
	    if( $condition == "where" &&
	        trim($statement) !== ""    )
	    {
	        $statement= "where $statement";
	        $this->aStatement['where']= $statement;
	        $this->aStatement['whereAlias']= $aliases;
	    }
	    if($bSetFromAlias)
	        $from= $aliases;
	    return $statement;
	}
	protected function getOrderStatement(&$aTableAlias, $tableName= null, $bIsOrdered= false)
	{
	    if(isset($this->aStatement['order']))
	    {
	        if(STCheck::isDebug("db.statements.order"))
	        {
	            $msg[]= "take predefined order statement";
	            $msg[]= "\"".$this->aStatement['order']."\"";
	            STCheck::echoDebug("db.statements.order", $msg);
	        }
	        if(isset($this->aStatement['orderAlias']))
	            $aTableAlias= array_merge($aTableAlias, $this->aStatement['orderAlias']);
	        return $this->aStatement['order'];
	    }
	    $statement= "";
	    if(	$tableName===null
	        or
	        $this->Name===$tableName	)
	    {
	        
	        $oTable= &$this;
	        $aNeededColumns= $oTable->getSelectedColumns();
	        //if tableName is null
	        $tableName= $this->Name;
	    }else
	    {
	        $oTable= &$this->getTable($tableName);
	        $aNeededColumns= $oTable->getIdentifColumns();
	    }
	    if(!$oTable->asOrder)
	    {
	        return "";
	    }
	    $bAlias= false;
	    if(count($aTableAlias)>1)
	        $bAlias= true;
        foreach($oTable->asOrder as $sortArray)
        {
            $alias= "";
            if($bAlias)
            {
                $alias= $aTableAlias[$sortArray['table']];
                $alias.= ".";
            }
            $statement.= $alias.$sortArray['column']." ".$sortArray['sort'];
            $statement.= ",";
            if($bIsOrdered)
            {
                return $statement;
            }
        }
        foreach($aNeededColumns as $columnContent)
        {
            $fkTableName= $oTable->getFkTableName($columnContent["column"]);
            if(	isset($fkTableName) &&
                $this->Name != $fkTableName	)
            {
                //echo __FILE__.__LINE__."<br>";
                //echo "getOrderStatement($aTableAlias, $fkTableName, $bIsOrdered)<br>";
                $order= $this->getOrderStatement($aTableAlias, $fkTableName, $bIsOrdered);
                if($order)
                {
                    if($bIsOrdered)
                        return $order;
                        $statement.= $order.",";
                }
            }
        }
        $statement= substr($statement, 0, strlen($statement)-1);
        $tableName= $this->getName();
        $query= new STQueryString();
        $queryArr= $query->getArrayVars();
        if(isset($queryArr["stget"]["sort"][$tableName]))
        {
            $query_statement= "";
            foreach($queryArr["stget"]["sort"][$tableName] as $column)
            {
                //preg_match("/^([^_]+)_(ASC|DESC)$/i", $column, $inherit);
                preg_match("/^(.+)_(ASC|DESC)$/i", $column, $inherit);
                $field= $this->searchByAlias($inherit[1]);
                if( isset($field["column"]) )
                {
                    $aliasTable= "";
                    if(count($aTableAlias) > 1)
                        $aliasTable= $aTableAlias[$field['table']].".";
                        $query_statement.= $aliasTable.$field["column"]." ".$inherit[2].",";
                }elseif(STCheck::isDebug())
                {
                    if( isset($queryArr["stget"]["action"]) &&
                        $queryArr["stget"]["action"] != STINSERT &&
                        $queryArr["stget"]["action"] != STUPDATE &&
                        $queryArr["stget"]["action"] != STDELETE    )
                    {
                        $message= "column alias('".$inherit[1]."') from query string not found inside table '$tableName'";
                        STCheck::write($message, 1);
                        $message= "maybe not reach table definition for current container or action";
                        STCheck::write($message, 1);
                    }
                }
            }
            if(strlen($query_statement) > 0)
                $query_statement= substr($query_statement, 0, strlen($query_statement)-1);
            if($statement != "")
                $statement= $query_statement.",".$statement;
            else
                $statement= $query_statement;
        }
        if(trim($statement) != "")
        {
            $this->aStatement['order']= $statement;
            $this->aStatement['orderAlias']= $aTableAlias;
        }
        return $statement;
	}
	private function getLimitStatement($bInWhere)
	{
	    if($bInWhere)
	    {
	        STCheck::echoDebug("db.statements.limit", "do not use limit statement if where statement exist");
	        return "";
	    }
	    $maxRows= $this->getMaxRowSelect();
	    if($maxRows)
	    {
	        $tableName= $this->getName();
	        $from= $this->getFirstRowSelect();
	        if(!$from)
	            $from= 0;
            STCheck::echoDebug("db.statements.limit", "first row for selection in table '$tableName' is set to $from");
            STCheck::echoDebug("db.statements.limit", "$maxRows maximal rows be set in table '$tableName'");
	            
	    }elseif(isset($this->limitRows))
	    {
	        $from= $this->limitRows["start"];
	        $maxRows= $this->limitRows["limit"];
	    }else
	        return "";
	        
        $where= " limit ".$from.", ".$maxRows;
        STCheck::echoDebug("db.statements.limit", "add limit statement '$where'");
        return $where;
	}
	/**
     * allow modification by every table has an limit in the query string
     * or an foreign key table limit points to own table with also limitation from query
     * 
     * @param bool $bModify whether should modification enabled or disabled
	 * @return array content of fk and own limitatin by query
	 */
	public function allowQueryLimitation(bool|array $modify= null) : array
	{
		if(!isset($modify))
		{
			$bfk= null;
			$bown= null;
		}elseif(is_bool($modify))
		{
			$bfk= $modify;
			$bown= $modify;
		}else
		{
			if(STCheck::isDebug())
			{
				$bOk= true;
				if(!isset($modify['fk']))
					$bOk= false;
				elseif(!isset($modify['own']))
					$bOk= false;
				STCheck::param($modify, 0, "check", $bOk, "array of modify need key 'fk' and 'own'");
			}
			$bfk= $modify['fk'];
			$bown= $modify['own'];
		}
		$aRv= array();
        $aRv['fk']= $this->allowFkQueryLimitation($bfk);
        $aRv['own']= $this->allowQueryLimitationByOwn($bown);
		return $aRv;
	}
	public function allowFkQueryLimitation($bModify= null)
	{
		if(!isset($bModify))
			return $this->bModifyFk;
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
		return $this->bModifyFk;
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
	public function where($stwhere, string $operator= "")
	{
	    STCheck::param($stwhere, 0, "STDbWhere", "string", "empty(string)", "null");
	    STCheck::param($operator, 1, "check", 
	        $operator === "" ||
	        $operator == "and" ||
	        $operator == "or", "null string", "and", "or" );
	    
	    STBaseTable::where($stwhere, $operator);
	    if(isset($this->oWhere))
	        $this->oWhere->setDatabase($this->db, /*overwrite*/false);
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
	 * or contain a keyword from SQL
	 *
	 * @param string|int|float $content string to check
	 * @param array|boolean|null $abCorrect can be an empty array where the correct column inside by return, or the next boolean parameter
	 * @param boolean $alias whether column can also be an alias name (default:false) 
	 * @param array|boolean|null $aKeyword if defined, do not make own check as keyword from content. (bool can only be false, no keyword exist)
	 * @return boolean true if the column parameter is valid
	 */
	public function validColumnContent($content, &$abCorrect= null, bool $bAlias= false, $aKeyword= null) : bool
	{
	    STCheck::param(trim($content), 0, "string");
	    STCheck::param($abCorrect, 1, "array", "bool", "null");
	    STCheck::param($aKeyword, 3, "array", "bool", "null");
	    
	    $db= $this->getDatabase();
	    if(!isset($aKeyword))
            $aKeyword= $db->keyword($content);
        if($aKeyword != false)
        {
            $valid= true;
            $allColumn= $db->getAllColumnKeyword();
            foreach($aKeyword['content'] as $column)
            {
                if( $column != $allColumn &&
                    !STBaseTable::validColumnContent($column, $abCorrect, $bAlias) )
                {
                    $valid= false;
                    break;
                }
            }
            if($valid)
            {
                if(typeof($abCorrect, "array"))
                {
                    $abCorrect['keyword']= "@{$aKeyword['usage']}";
                    $abCorrect['content']= $aKeyword;
                    $abCorrect['type']= "keyword";
                }
                return true;
            }
        }
        $valid= STBaseTable::validColumnContent($content, $abCorrect, $bAlias);
        if(!$valid)
        {
            $content= trim($content);
            $operators= $db->getOperatorArray();
            foreach($operators as $operator)
            {
                if($content == $operator['str'])
                {
                    $abCorrect['keyword']= "@operator";
                    $abCorrect['content']= $abCorrect['content']['column'];
                    return true;
                }
            }
        }
        return $valid;
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
	    
		if(	$address != null &&
			is_string($address)	)
		{// if address only an string, 
		 // test whether string name can be a table from database
			$bTable= false;
		    $tables= $this->db->list_tables();
		    $address= STDbTableDescriptions::instance($this->db->getDatabaseName())->getTableName($address);
    		foreach($tables as $tabName)
    		{// schau ob die Adresse eine Tabelle ist
    			if($address==$tabName)
    			{
					$bTable= true;
					$tableName= $tabName;
    				break;
    			}
    		}
			if($bTable)
				$address= $this->container->getTable($tableName);
		}
		if(typeof($address, "STDbTable"))
		{// is address a STDbTable
		 // pack table into Container
		    $tabName= $address->getName();
		    $newContainerName= "dbtable ".$tabName;
		    $address= new STDbTableContainer($newContainerName, $this->db);
		    $address->needTable($tabName);
			
		}
		STBaseTable::linkA($which, $tableName, $aliasColumn, $address, $valueColumn);
	}
}

?>