<?php


class STDbWhere
{
	/**
	 * all content of where clausels in an subarray with key named 'array'
	 * and also the splitet content in an subarray with kea named 'aValues'.<br>
	 * In the 'array' array also can be rekursive new where clausels (STDbWhere).
	 * @private
	 */
    var $array= array();
 	/**
 	 * for wich table the where clausel be
 	 * @private
 	 */
    var	$sForTable= "";
    /**
     * table object
     */
    private $oTable= null;
	/**
	 * if the clausel will be add to an other table,
	 * use this operator
	 * @private
	 */
    var $sOp;
	/**
	 * splitted where claus
	 * @private
	 */
    var $aValues= array();
	/**
	 * database name for which where-caluse is
	 */
    var $sDbName= "";
    private $oDb= null;
    /**
     * whether the statement should written into the 'on' statement if possible, 
     * otherwise by false value only into the 'where' statement
     * @var boolean
     */
    var $bWriteOn= true;
    /**
     * whether where statement 
     * was written for current object
     * @var boolean
     */
    var $bWritten= false;
	/**
	 * create instance of where clausel
	 *
	 * @param string $statment statement is an string with column and value with relational operator ("ID=0")
	 * @param string $tableName for which table the where clausel be
	 * @param string $clauselOp can be the string 'and' or 'or', default if you not set this parameter the operator is 'and'
	 * @public
	 */
	public function __construct($statement= null, $tableName= null, $clauselOp= null)
	{
		STCheck::param($statement, 0, "string", "empty(string)", "null");
		STCheck::param($tableName, 1, "string", "empty(string)", "null");
		STCheck::param($clauselOp, 2, "check", $clauselOp=="and" || $clauselOp=="or" || $clauselOp===null,
														"strings and/or or null");

		if($statement === "")
			$statement= null;
		$this->sOp= $clauselOp;
		if(isset($statement))
		{
			if($this->check($statement))
				$this->array[]= $statement;
			else
				STCheck::is_error(1, "STDbWhere::check()", "where statement isn't correct (where ".$statement.")");
		}
		if(	isset($tableName) &&
			$tableName !== ""		)
		{
			$this->table($tableName);
		}
	}
	public function setDatabase($db)
	{
	    STCheck::param($db, 0, "STDatabase", "STDbTable");
	    
	    if(typeof($db, "STDatabase"))
	    {
	        $this->oDb= $db;
	        $this->sDbName= $db->getDatabaseName();
	    }else if(typeof($db, "STDbTable"))
	    {
	        $this->oDb= $db->getDatabase();
	        $this->table($db);
	        $this->sDbName= $this->oDb->getDatabaseName();
	    }else
	        STCheck::alert(1, "STDbWhere::setDatabase()", "parameter is no Database or Table");
	    STCheck::echoDebug("db.statements.where", "set database '".$this->sDbName."' for where clause");
	}
	function isModified()
	{
		if(count($this->array))
			return true;
		return false;
	}
	function forTable($tableName= null, $overwrite= false)
	{
		STCheck::param($tableName, 0, "string", "STBaseTable", "null");
		STCheck::param($overwrite, 1, "boolean");

		STCheck::deprecated("STDbWhere::table()", "STDbWhere::forTable()");
		//$desc= STDbTableDescriptions::
		return $this->table($tableName, $overwrite);
	}
	/**
	 * define where clause for witch table
	 * 
	 * @param STBaseTable|string|null $table Table name, object or NULL when be later should defined or only want to ask to get the table name return
	 * @param boolean $overwrite whether new defnition should overwrite the old table name if exist
	 * @return string for whitch table where clause is defined
	 */
	function table($table= null, $overwrite= false)
	{
		STCheck::param($table, 0, "string", "STBaseTable", "null");
		STCheck::param($overwrite, 1, "boolean");		

		if(!$table)
			return $this->sForTable;

		if(is_string($table))
		{
			$pos= strpos($table, ".");
			if($pos !== false)
			{
				$dbName= substr($table, 0, $pos);
				$tableName= substr($table, $pos+1);				
			}else
			{
				$tableName= $table;
				$dbName= "";
			}
		}else if(typeof($table, "STDbTable"))
		{
			$tableName= $table->getName();
			$db= $table->getDatabase();
			$dbName= $db->getDatabaseName();
		}else if(typeof($table, "STBaseTable"))
		{
		    $dbName= "";
		    $tableName= $table->getName();
		}else
		{
		    $tableName= "";
		    $dbName= "";
		}

		if( $dbName == "" ||
		    (   $overwrite &&
		        $this->sDbName != ""    )   )
		{	        
		    $dbName= $this->sDbName;
		}
		
		$desc= NULL;
		if($dbName != "")
		{
			$desc= STDbTableDescriptions::instance($dbName);
			if(isset($desc))
				$tableName= $desc->getTableName($tableName);
			else
			    $errorMsg= "cannot read instance of database '$dbName'";
		}else
		    $errorMsg= "no database for STDbWehre object set";
		if($desc == NULL)
		    STCheck::echoDebug("db.statements.where", $errorMsg);

		$overName= "";
		if(	!isset($this->sForTable) ||
			$this->sForTable == "" ||
			$overwrite				   )
		{
			// if the tableName the same as
			// before saved, search only for null-string
			if($tableName!=$this->sForTable)
				$overName= $this->sForTable;
			$this->sForTable= $tableName;
		}

		if(	isset($this->aValues[$overName]) &&
			count($this->aValues[$overName])	)
		{
			$unset[$tableName]= $this->aValues[$overName];
			unset($this->aValues[$overName]);
			$this->addValues($unset);
		}
		//st_print_r($this->aValues,10);
		return $this->sForTable;
	}
		// gibt den Tabellen-Context von dem Objekt zur�ck,
		// auf welches die Suche als erstes trifft.
		function getForTableName()
		{
			return $this->sForTable;
		}
		function getWhereTableNames($where= null)
		{
			Tag::paramCheck($where, 1, "STDbWhere", "array", "string", "null");

			echo __FILE__.__LINE__."<br>";
			echo "getWhereTableNames()<br>";
			exit;
			$needetTables= array();
			if(is_array($where))
			{
				foreach($where as $content)
					$needetTables= array_merge($needetTables, $this->getWhereTableNames($content));
			}elseif(typeof($where, "STDbWhere"))
			{
				$needetTables[]= $where->sForTable;
				$needetTables= array_merge($needetTables, $this->getWhereTableNames($where->array));
			}elseif($where===null)
			{
				$needetTables= $this->getWhereTableNames($this->array);
			}
			return $needetTables;
		}
		function where($statement)
		{
		 	Tag::paramCheck($statement, 1, "STDbWhere", "string");

			if(!$this->check($statement))
			{
				STCheck::is_error(1, "STDbWhere::check()", "where statement isn't correct (where ".$statement.")");
				return false;
			}
			unset($this->array);
		   	$this->array[]= $statement;
			return true;
		}
		function andWhere($statement)
		{
		 	Tag::paramCheck($statement, 1, "STDbWhere", "string", "empty(string)", "null");
		 	
			if(!$this->check($statement))
			{
				if(!is_string($statement))
				{
					echo "<pre> statement:";
					st_print_r($statement, 5);
					echo "</pre>";
					STCheck::is_error(1, "STDbWhere::check()", "where statement isn't correct");
				}else
					STCheck::is_error(1, "STDbWhere::check()", "where statement isn't correct (where ".$statement.")");
				return false;
			}
			if(count($this->array))
				$this->array[]= " and ";
		   	$this->array[]= $statement;
			return true;
		}
		function orWhere($statement)
		{
		 	Tag::paramCheck($statement, 1, "STDbWhere", "string", "empty(string)", "null");
		 	
			if(!$this->check($statement))
			{
				STCheck::is_error(1, "STDbWhere::check()", "where statement isn't correct (where ".$statement.")");
				return false;
			}
			if(count($this->array))
				$this->array[]= " or ";
		   	$this->array[]= $statement;
			return true;
		}
		/*remove*/private function getArray()
		{
			return $this->array;
		}
		public function writeOnCondition()
		{
		    $this->bWriteOn= true;
		}
		public function writeWhereCondition()
		{
		    $this->bWriteOn= false;
		    echo __FILE__.__LINE__."<br>";
		    st_print_r($this,20);
		}
		private function addValues($array)
		{
			foreach($array as $table=>$content)
			{
				foreach($content as $column=>$aValue)
				{
					if(	isset($this->aValues[$table][$column]) &&
						count($this->aValues[$table][$column])		)
					{
						$this->aValues[$table][$column]= array_merge($this->aValues[$table][$column], $aValue);
					}else
						$this->aValues[$table][$column]= $aValue;
				}
			}
			//echo "add:";
			//st_print_r($this->aValues,10);
		}
		private function check($statement)
		{
			STCheck::param($statement, 0, "string", "STDbWhere", "empty(string)", "null");

			if(	$statement == "" ||
				$statement == null	)
			{
				return false;
			}
			elseif(typeof($statement, "STDbWhere"))
			{
				if(count($statement->array)==0)
					return false;
				if($this->sForTable != "")
				{
					$statement->table($this->sForTable);
					if( $statement->sDbName == "" &&
					    $this->sDbName != ""           )
					{
					    $statement->setDatabase($this->sDbName);
					}
				}
				$this->addValues($statement->aValues);
				return true;
			}
			$preg= array();
			preg_match("/^([^=><!]*| +'.*' *)(is +not|is|between|like|not +like|in|not +in|>=|<=|<>|!=|<|>|=)([^=><!]*| *'.*' *)$/i", $statement, $preg);
			//echo "where:";st_print_r($preg);
			if(	!isset($preg[1])
				or
				!isset($preg[3])	)
			{
				return false;
			}
			$preg[1]= trim($preg[1]);
			$preg[2]= trim($preg[2]);
			$preg[3]= trim($preg[3]);
			if($preg[2] == "between")
			{
				$column= $preg[1];
				$preg2= array();
			  //if(preg_match("/[ \t]([^ \t]+|'.*')[ ]+and +([^ \t]+|'.*')[ \t]/i", $preg[3], $preg2))
				if(preg_match(   "/([^ \t]+|'.*')[ \t]+and[ \t]+([^ \t]+|'.*')/i", $preg[3], $preg2))
				{
					$value= array($preg2[1], $preg2[2]);
				}
				//	st_print_r($preg2);
			}else
			{
				if(	is_numeric($preg[1])
					or
					preg_match("/^'.*'$/", $preg[1])
					or
					$preg[1]==="null"					)
				{
					$value= $preg[1];
					$column= $preg[3];
				}else
				{
					$value= $preg[3];
					$column= $preg[1];
				}
				if(preg_match("/^(in|not +in)$/", $preg[2]))
				{
					$value= substr($value, 1, strlen($value)-2);
					$value= preg_split("/,/", $value);
				}
				if($value==="null")
					$value= null;
			}
			$count= 0;
			if(	isset($this->sForTable) &&
				isset($this->aValues[$this->sForTable]) &&
				isset($this->aValues[$this->sForTable][$column])	)
			{
				$count= count($this->aValues[$this->sForTable][$column]);
			}
			$this->aValues[$this->sForTable][$column][$count]["value"]= $value;
			$this->aValues[$this->sForTable][$column][$count]["operator"]= $preg[2];
			$this->aValues[$this->sForTable][$column][$count]["type"]= "value";
			if(	$value!==null
				and
				!is_array($value)
				and
				!is_numeric($value)
				and
				!preg_match("/^'.*'$/", $value)	)
			{
				$this->aValues[$this->sForTable][$column][$count]["type"]= "column";
				if(isset($this->aValues[$this->sForTable][$value]))
					$count= count($this->aValues[$this->sForTable][$value]);
				else
					$count= 0;
				$this->aValues[$this->sForTable][$value][$count]["value"]= $column;
				$this->aValues[$this->sForTable][$value][$count]["operator"]= $preg[2];
				$this->aValues[$this->sForTable][$value][$count]["type"]= "column";
			}
			return true;
		}
		function getSettingValue($column, $table= "") : array
		{
		    if(isset($this->aValues[$table][$column]))
			    return $this->aValues[$table][$column];
		    return array();
		}
		private function createStringContent(string $content, string $aliasName) : string
		{
		    $old_content= $content;
		    //echo $content."<br />";
		    //$content= preg_quote($content); // preg_quote makes before = an backslash
		    //echo $content."<br />";
		    
		    $oTable= $this->oDb->getTable($this->sForTable);
		    //-----------------------------------------------------------
		    $pattern_first=  "([^><=! \\t]*)"; // first is always the column
		    //-----------------------------------------------------------
		    $operators= $this->oDb->getOperatorArray();
		    $pattern_op= "(";
		    foreach($operators as $op)
		    {
		        if(isset($op))
		        {
    		        $str= preg_replace("/[ \\t]+/", "[ \\t]+", $op);
    		        $pattern_op.= "$str|";
		        }
		    }		    
		    $pattern_op= substr($pattern_op, 0, -1).")";
		    //-----------------------------------------------------------
		    $pattern_second= "(.*)"; // second content can be an column or an content
		    //-----------------------------------------------------------
		    $preg= array();
		    if(!preg_match("/^$pattern_first$pattern_op$pattern_second$/i", $content, $preg))
		    {
		        Tag::echoDebug("db.statements.where", "<b>WARNING</b> can not localize '".$old_content."'");
		    }else
		    {
		        if(STCheck::isDebug("db.statements.where"))
		        {
		            $space= STCheck::echoDebug("db.statements.where", "localize statement string '$content' as:");
		            st_print_r($preg, 2, $space);
		        }
		    }
		    
		    $sOrgField1= trim($preg[1]);
		    $operator= trim($preg[2]);
		    $sOrgField2= trim($preg[3]);
		    
		    $content1= $content2= array();
		    $field1= $oTable->validColumnContent($sOrgField1, $content1, /*alias*/true);
		    STCheck::warning(!$field1, "STDbWhere::getStatement()",
		        "incorrect first field of comparison from where statement '$old_content'", /*output*/2);
		    if($content1['keyword'] == "@field")
		    {
		        $sfield1= $content1['content']['column'];
		        if($aliasName)
		            $sfield1= "$aliasName.$sfield1";
		    }else
		        $sfield1= $sOrgField1;
		    $field2= $oTable->validColumnContent($sOrgField2, $content2, /*alias*/true);
		    STCheck::warning(!$field2, "STDbWhere::getStatement()",
		        "incorrect second field of comparison from where statement '$old_content'", /*output*/2);
		    if($content2['keyword'] == "@field")
		    {
		        $sfield2= $content1['content']['column'];
		        if($aliasName)
		            $sfield2= "$aliasName.$sfield2";
		    }else
		        $sfield2= $sOrgField2;
		    
	        $content= $sfield1.$operator.$sfield2;
	        if(STCheck::isDebug("db.statements.where"))
	        {
	            if($content1['keyword'] == "@field")
	                STCheck::echoDebug("db.statements.where", 
	                    "first field '$sOrgField1' become to column('".$sfield1."') with content('".$content."')");
                if($content2['keyword'] == "@field")
                    STCheck::echoDebug("db.statements.where",
                        "second field '$sOrgField2' become to column('".$sfield2."') with content('".$content."')");
	        }
	        return $content;
		}
		public function reset()
		{
		    $this->bWritten= false;
		    foreach($this->array as $content)
		    {
		        if(typeof($content, "STDbWhere"))
		            $content->reset();
		    }
		}   
		public function getStatement(STDbTable $oTable, string $condition, array $aliases= null)
		{
			STCheck::param($condition, 1, "check", $condition=="on"||$condition=="where", "'on' string", "'where' string");

			//echo __FILE__.__LINE__."<br>";
			//echo "incomming aliases:";
			//st_print_r($aliases);
			if(STCheck::isDebug("db.statements.where"))
			{
			    $amsg= array();
			    $blanc= "------------------------------------------------------------------------------";
			    $blanc.= $blanc;
			    STCheck::echoDebug("db.statements.where", $blanc);
			    $bTableContainer= false;
			    if(typeof($oTable, "STDbSelector"))
			        $bTableContainer= true;
				$message= "make where clause for condition <b>$condition</b> in table";
				if($bTableContainer)
				    $message.= "-container";
				$message.= " ".$oTable->toString();
				$amsg[]= $message;
				$message= "";
				if($oTable->modify())
				    $message.= "and has ";
				else
				    $message.= "but has no ";
				$message.= "permission to use query constraints<br>";
				$amsg[]= $message;
				$nIntented= STCheck::echoDebug("db.statements.where", $amsg);				
				st_print_r($this, 10, $nIntented);
			}
			if(!$aliases)
			    $aliases= array();
			$curAlias= "unknown";

			$currentTableName= $oTable->getName();
			$forTableName= $oTable->db->getTableName($this->sForTable);
			$bMakeStatement= true;
			if($this->bWritten == true)
			{
			    $bMakeStatement= false;
			    STCheck::echoDebug("db.statements.where", 
			        "where statement was written for this STDbWhere(<b>$forTableName</b>) object before, so do noting");
			}
			if( $bMakeStatement &&
			    $condition == "on" && // if condition is 'where' can implement by all tables
			    $currentTableName != $forTableName    )
			{
			    $bMakeStatement= false;
			    STCheck::echoDebug("db.statements.where", 
			        "current STDbWhere object is for <b>$forTableName</b> not for <b>$currentTableName</b>");
			}
			if( $bMakeStatement &&
			    $condition == "on" &&
			    $this->bWriteOn == false )
			{
			    $bMakeStatement= false;
			    if(STCheck::isDebug())
			    {
			        if($this->bWriteOn)
			            $condname= "on";
		            else
		                $condname= "where";
		            $amsg[]= "where statment is only for <b>$condname</b> condition";
		            $amsg[]= "do not create any where statement";
		            STCheck::echoDebug("db.statements.where", $amsg);
			    }
			}
			
		    if( !isset($this->sOp)	)
		    {
		        $plusContent= " and ";
		    }else
		        $plusContent= $this->sOp;
		    
	        if(	!isset($forTableName) ||
	            !isset($aliases[$forTableName])	)
	        {
	            $aliasName= $curAlias;
	        }else
	            $aliasName= $aliases[$forTableName];
            if(count($aliases)<=1)
                $aliasName= "";
            if( STCheck::isDebug("db.statements.where") &&
                $bMakeStatement                             )
            {
                $nIntented= STCheck::echoDebug("db.statements.where", "given alias Names:");
                st_print_r($aliases, 1, $nIntented);
                echo "<br />";
                /*			if(!$forTableName)
                 {
                 $flipAlias= array_flip($aliases);
                 $forTableName= $flipAlias[$curAlias];
                 }*/
                Tag::echoDebug("db.statements.where", "alias for current Table is \"".$curAlias."\"");
                Tag::echoDebug("db.statements.where", "need for table ".$forTableName);
                if(count($aliases)>1)
                    Tag::echoDebug("db.statements.where", "now alias is \"".$aliasName."\"");
                else
                    Tag::echoDebug("db.statements.where", "no alias, because it only one alias set, do not need alias for table");
            }
		    
		    $statement= "";
		    foreach($this->array as $content)
		    {
		        if(is_string($content))
		        {
		            // alex 09/05/2005:	im preg_match anfang (^) und Ende ($) eingef�gt
		            //					da or auch in der Spalte zb. Kateg(or)y gefunden wurde
		            //					es d�rfte im content nur die Variablen and, or stehen
		            //					es m�ssen jedoch sehrwohl leerzeichen davor oder danach
		            //					existieren d�rfen
		            if(	!preg_match("/^[ ]*and[ ]*$/", $content) &&
		                !preg_match( "/^[ ]*or[ ]*$/", $content)		)
		            {
		                if($bMakeStatement)
		                {
		                    $newStatement= $this->createStringContent($content, $aliasName);
		                    if($statement != "")
		                        $statement.= $plusContent;
		                    $statement.= $newStatement;
		                }
		            }else
		            { // content is operator
		                $plusContent= $content;
		            }
		        }elseif(typeof($content, "STDbWhere"))
		        {
		            if($this->sDbName != "")
		                $content->setDatabase($this->oDb);
	                STCheck::echoDebug("db.statements.where", "found new STDbWhere object inside array and create rekursive");
	                $newStatement= $content->getStatement($oTable, $condition, $aliases);
	                if(trim($newStatement) != "")
	                {// where statement exist for current Table
	                    if(trim($statement) != "")
	                        $statement.= $plusContent;
	                    $statement.= $newStatement;
	                }
		        }else//if($content)
		        {
		            $space= STCheck::echoDebug("db.statements.where", "where content:");
		            st_print_r($content, 10, $space);
		            STCheck::alert(1, "STDbWhere::getStatement", "content of where-clause is no string, nor is it an object of STDbWhere");
		        }
		    }//foreach($array as $content)
		    if($bMakeStatement)
		        $this->bWritten= true;
		    $statement= $this->addBraces($statement);
		    if(STCheck::isDebug("db.statements.where"))
		    {
		        STCheck::echoDebug("db.statements.where", "result for current table is <b>'$statement'</b>");
		        STCheck::echoDebug("db.statements.where", $blanc);
		    }
		    return $statement;
		}
		/**
		 * add braces before and behind the statement
		 * and consider words of 'and' or 'or' on beginning
		 * 
		 * @param string $statement normaly statement
		 * @return string statement with brackets 
		 */
		private static function addBraces($statement)
		{
		    $ereg= array();
		    $statement= trim($statement);
		    if($statement == "")
		        return "";
		    if( substr($statement, 0, 1) == "(" )  // if the first char is an bracket the last have to be also one
		        //substr($statement, strlen($statement) - 1) == ")" )  // <- so do not need to check
		    {
		        return $statement;
		    }
			if(preg_match("/^(and|or)[ \t]+(.*)$/", $statement, $ereg))
			{
				//st_print_r($ereg);
				$ereg2= trim($ereg[2]);
				if( !substr($ereg2, 0, 1) == "(" )
				    $ereg2= "($ereg2)";
				$statement= "${ereg[1]} $ereg2";
			}else
				$statement= "($statement)";
			return $statement;
		}
	}

?>