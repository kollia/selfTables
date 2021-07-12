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
	 * create instance of where clausel
	 *
	 * @param string $statment statement is an string with column and value with relational operator ("ID=0")
	 * @param string $tableName for which table the where clausel be
	 * @param string $clauselOp can be the string 'and' or 'or', default if you not set this parameter the operator is 'and'
	 * @public
	 */
	function STDbWhere($statement= null, $tableName= null, $clauselOp= null)
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
				STCheck::error(1, "STDbWhere::check()", "where statement isn't correct (where ".$statement.")");
		}
		if(	isset($tableName) &&
			$tableName !== ""		)
		{
			$this->table($tableName);
		}
	}
	function isModified()
	{
		if(count($this->array))
			return true;
		return false;
	}
	function forTable($tableName= null, $overwrite= false)
	{
			STCheck::param($tableName, 0, "string", "STAliasTable", "null");
			STCheck::param($overwrite, 1, "boolean");

		return $this->table($tableName, $overwrite);
	}
	function table($table= null, $overwrite= false)
	{
		STCheck::param($table, 0, "string", "STAliasTable", "null");
		STCheck::param($overwrite, 1, "boolean");
		
		if(!$table)
			return $this->sForTable;

		if(is_string($table))
		{
			$dbName= "";
			$pos= strpos($table, ".");
			if($pos !== false)
			{
				$dbName= substr($table, 0, $pos);
				$tableName= substr($table, $pos+1);
				
			}else
				$tableName= $table;
		}else
		{
			$tableName= $table->getName();
			$db= $table->getDatabase();
			$dbName= $db->getName();
		}

		$desc= NULL;
		if($dbName != "")
		{
			$desc= STDbTableDescriptions::instance($dbName);
			if($desc)
				$tableName= $desc->getTableName($tableName);
		}
		if($desc == NULL)
			STCheck::echoDebug("db.where", "cannot read instance of database $dbName");

		$overName= "";
		if(	!$this->sForTable
			or
			$this->sForTable == ""
			or
			$overwrite				)
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
				STCheck::error(1, "STDbWhere::check()", "where statement isn't correct (where ".$statement.")");
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
					STCheck::error(1, "STDbWhere::check()", "where statement isn't correct");
				}else
					STCheck::error(1, "STDbWhere::check()", "where statement isn't correct (where ".$statement.")");
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
				STCheck::error(1, "STDbWhere::check()", "where statement isn't correct (where ".$statement.")");
				return false;
			}
			if(count($this->array))
				$this->array[]= " or ";
		   	$this->array[]= $statement;
			return true;
		}
		function getArray()
		{
			return $this->array;
		}
		function addValues($array)
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
		function check($statement)
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
					$statement->forTable($this->sForTable);
				$this->addValues($statement->aValues);
				return true;
			}
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
		function getSettingValue($column, $table= "")
		{
			return $this->aValues[$table][$column];
		}
		public function getStatement($oTable, $aktAlias, $aliases= null)
		{
			STCheck::param($oTable, 0, "STDbTable");
			STCheck::param($aktAlias, 1, "string", "empty(string)");
			STCheck::param($aliases, 2, "array", "null");

			if(!$aliases)
				$aliases= array();
			
			if(Tag::isDebug())
			{
				$message= "make where clause in table <b>".$oTable->getName()."</b> from container <b>".$oTable->container->getName()."</b>";
				if($aktAlias!=="")
					$message.= " with alias-table ".$aktAlias;
				else
					$message.= " without alias-table";
				$nIntented= STCheck::echoDebug("db.statements.where", $message);
				if(STCheck::isDebug("db.statements.where"))
				{
					for($n= 0; $n < $nIntented; ++$n)
						echo " ";
					echo "                                                    has ";
					if(!$oTable->modify())
						echo "no ";
					echo "permission to use query constraints<br>";
					st_print_r($this, 3);
					if(	$this == null ||
						count($this) == 0	)
					{
						echo "<br />";
					}
				}
			}

			$aktTableName= $oTable->getName();
			$statement= "";
			$plusContent= "";
			if(isset($this->aOtherTableWhere[$aktTableName]))
				$statement= $this->aOtherTableWhere[$aktTableName];
			if($statement != "")
			{
				if(	!isset($this) ||
						!isset($this->sOp)	)
				{
					$plusContent= " and ";
				}else
					$plusContent= $this->sOp;
					unset($this->aOtherTableWhere[$aktTableName]);
			}
			if($this===null)
			{
				if($statement)
				{
					Tag::echoDebug("db.statements.where", "result from other table(s) is '$statement'");
					$statement= "($statement)";
				}else
					Tag::echoDebug("db.statements.where", "no where clausls");
					//echo $statement."<br>";
					return $statement;
			}
			//st_print_r($this);
			$array= $this->getArray();
		
			$sForTable= $this->sForTable;
			if(	!isset($sForTable) ||
					!isset($aliases[$sForTable])	)
			{
				$aliasName= $aktAlias;
				if(!isset($aliases[$sForTable]))
					$sForTable= $aktTableName;
			}else
				$aliasName= $aliases[$sForTable];
			
			// tableOperator only for statements
			// whitch are not in current table
			$tableOperator= $this->sOp;
			if($tableOperator)
				$tableOperator.= " ";
				else
					$tableOperator= "and ";
			
			if(count($aliases)<=1)
				$aliasName= "";
			if(STCheck::isDebug("db.statements.where"))
			{
				$nIntented= STCheck::echoDebug("db.statements.where", "given alias Names:");
				for($n= 0; $n < $nIntented; ++$n)
					echo " ";
				echo "<b>[</b>db.statements.where<b>]</b>                                ";
				st_print_r($aliases, 1, $nIntented + 53);
				echo "<br />";
				if(!$sForTable)
				{
					$flipAlias= array_flip($aliases);
					$sForTable= $flipAlias[$aktAlias];
				}
				Tag::echoDebug("db.statements.where", "alias for current Table is \"".$aktAlias."\"");
				Tag::echoDebug("db.statements.where", "need for table ".$sForTable);
				if(count($aliases)>1)
					Tag::echoDebug("db.statements.where", "now alias is \"".$aliasName."\"");
					else
						Tag::echoDebug("db.statements.where", "no alias, because it only one alias set, do not need alias for table");
			}
			$statementForAkt= "";
			foreach($array as $content)
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
						$old_content= $content;
						//echo $content."<br />";
						//$content= preg_quote($content); // preg_quote makes before = an backslash
						//echo $content."<br />";
						//-----------------------------------------------------------
						$pattern_first=  "([^><=!]*)"; // first is always the column
						//-----------------------------------------------------------
						$pattern_op=  "(>=|<=|<|>|=|<>|!=";
						$pattern_op.= "| +like +| +not +like +";			// operator
						$pattern_op.= "| +regexp +| +not +regexp +";
						$pattern_op.= "| +in| +not +in"; // behind in must not be an space
						$pattern_op.= "| +is +| +not +is +)";
						//-----------------------------------------------------------
						$pattern_second= "(.*)"; // second content can be an column or an content
						//-----------------------------------------------------------
						$preg= array();
						if(!preg_match("/^$pattern_first$pattern_op$pattern_second$/i", $content, $preg))
						{
							Tag::echoDebug("db.statements.where", "<b>WARNING</b> can not localize '".$old_content."'");
						}//st_print_r($preg);
						$preg[1]= trim($preg[1]);
						$preg[2]= trim($preg[2]);
						$preg[3]= trim($preg[3]);
						if(	!is_numeric($preg[1])
								and
								substr($preg[1], 0, 1)!= "'"
								and
								!preg_match("/null/i", $preg[1])
								and
								!preg_match("/now([ ]*)/i", $preg[1])
								and
								!preg_match("/sysdate([ ]*)/i", $preg[1])
								and
								!preg_match("/password([ ]*)/i", $preg[1])	)
						{
							$column= $preg[1];
							$value= $preg[3];
						}else
						{
							$column= $preg[3];
							$value= $preg[1];
						}
						if($aliasName)
							$column= $aliasName.".".$column;
							$content= $column." ".$preg[2]." ".$value;
							STCheck::echoDebug("db.statements.where", $old_content." become to column('".$column."') with content('".$content."')");
							$statement.= $plusContent.$content;
							//echo $statement."<br>";
					}else
					{
						//$plusContent+=  " " + $content;
						$plusContent= $content;
						//echo $plusContent."<br>";
					}
				}elseif(typeof($content, "StDbWhere"))
				{
					$statement.= $plusContent.$content->getStatement($oTable, $aktAlias, $aliases);
					// alex 03/08/2005:	content must be an new object from STDbWhere
/*					$oTable->where($content);
					$newWhere= $this->getWhereStatement($oTable, $aliasName, $aliases);
					if($newWhere)
					{// if the aktual statement is not for the aktual table
						// but the newWhere is from aktual table
						// save it in statementForAkt and not to the aktual statement.
						// the aktual statement will be saved then in $this->sOtherTableWhere
						// and statementForAkt is giving back
						//if($statementForAkt)
						//	$statementForAkt.= $plusContent;
							preg_match("/^((and|or) )?(\()?.*(\))?$/", trim($newWhere), $ereg);
							if(	$ereg[1] != "" &&
									isset($ereg[3]) &&
									$ereg[3] != "("		)
							{
								if($ereg[1] == "and")
									$nOp= 4;
									else
										$nOp= 3;
										$newWhere= $ereg[1]." (".substr($newWhere, $nOp).")";
							}
							$statementForAkt.= $newWhere;
							if($sForTable===$aktTableName)
							{
								$statement.= " ".$newWhere;
								$statementForAkt= "";
							}
					}*/
				}else//if($content)
				{
					STCheck::write("content is no string, nor is it an object of STDbWhere");
					if(typeof("StDbWhere"))
						echo "content is type of StDbWhere<br>";
					echo get_class()."<br>";
			st_print_r($array, 3);
			echo __file__.__line__;
			exit;
				}
			}//foreach($array as $content)
			if(	$sForTable!==$aktTableName
					and
					$statement					)
			{
				if(!preg_match("/^\(.*\)$/", trim($statement)))
					$statement= "(".$statement.")";
					if(isset($this->aOtherTableWhere[$sForTable]))
					{
						//echo "other table ".$this->aOtherTableWhere[$sForTable]." plus ".$statement."<br>";
						$this->aOtherTableWhere[$sForTable].= $tableOperator.$statement;
					}else
						$this->aOtherTableWhere[$sForTable]= $tableOperator.$statement;
						Tag::echoDebug("db.statements.where", "write where-statement '$statement' into buffer for table <b>$sForTable</b>");
						Tag::echoDebug("db.statements.where", "result is '$statementForAkt'");
						//echo "where result:$statement<br>";
						return $this->addBraces($statementForAkt);
			}
	//		if(!preg_match("/^(or|and)/i", $statement))
	//			$statement= $tableOperator.$statement;
			Tag::echoDebug("db.statements.where", "result is '$statement'");
			//echo "where result:$statement<br>";
			return $this->addBraces($statement);
		}
		/**
		 * add braces before and behind the statement
		 * and consider words of 'and' or 'or' on beginning
		 * 
		 * @param $statement normaly statement
		 * @return statement with brackets 
		 */
		private function addBraces($statement)
		{
			if(preg_match("/^(and|or)[ \t]+(.*)$/", trim($statement), $ereg))
			{
				//st_print_r($ereg);
				$statement= "${ereg[1]} (${ereg[2]})";
			}else
				$statement= "($statement)";
			return $statement;
		}
	}

?>