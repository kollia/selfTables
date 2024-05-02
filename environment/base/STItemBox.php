<?php

require_once($_stbasebox);


/**
*	 src: STItemBox.php
*	 class STItemBox: insert and update Box
*/

class STItemBox extends STBaseBox
{
		var $action;
		var $startTag;
		var $columns;
		/**
		 * sql result from main select
		 * @var array
		 */
		private $aResult= array();
		var $join= array();
		var $ownName;
		var	$lastInsertID= null; // last inerted or updated ID
		var $intersecBez;
		var $intersecFld;
		var $password= null;
		var $passwordNames= array();
		var $pwdEncoded;
		var $uploadFields= array();
		var	$aDropDowns= array(); // extra gesetzte dorpDownSelects
		/**
		 * specific enum fields
		 * @var array
		 */
		private $enumField= array();
		var $aSetAlso= array();
		var $aDisabled= array();
		var	$aPreSelect= array();
		var $OKScript= null;
		var	$sButtonValue;
		var	$aInputSize= array();
		var $joinBuffer= array();
		var	$onlyRadioButtons= array(); // wenn nur zwei Enumns vorhanden sind, trotzdem radio Buttons verwenden
		var $aSelectNames= array();
		/**
		 * aktual inner table position for better design
		 */
		var $innerTabPos= array("pos"=>"begin", "count"=>0);

		function __construct(&$container, $class= "STItemBox")
		{
			Tag::paramCheck($container, 1, "STBaseContainer");
			Tag::paramCheck($class, 2, "string");

			STBaseBox::__construct($container, $class);
			//$this->startTag= $tag;
			$this->columns= "*";
			$this->intersecBez= 0;
			$this->intersecFld= 0;
			$this->setLanguage("en");

		}
		function createMessages()
		{
			STBaseBox::createMessages();
			if($this->locale['language'] == "de")
			{
				$this->aSelectNames["select"]= "&#160;&#160; bitte ausw&auml;hlen &#160;&#160;";
				$this->aSelectNames["null_entry"]= "&#160;&#160; Eintrag kann NULL sein &#160;&#160;";
				$this->aSelectNames["left_select"]= "&#160;&#160; bitte zuerst links w&auml;hlen &#160;&#160;";
				$this->aSelectNames["no_entrys"]= "&#160;&#160; keine Eintr&auml;ge &#160;&#160;";
				
				$this->msg->setMessageContent("BOXDISPLAY", ""); // Box wird am Bildschirm angezeigt
				$this->msg->setMessageContent("AND", "und");
				$this->msg->setMessageContent("NODELETE_FK@", "Dieser Eintrag kann nicht geloescht werden, es verweist darauf ein Eintrag von @");
				$this->msg->setMessageContent("NOROWTODELETE", ""); // es wurde kein Eintrag in der Datenbank zum löschen gefunden
				$this->msg->setMessageContent("DELETEQUESTION", "Wollen sie diesen Eintrag wirklich loeschen?"); // Frage ob der User wirklich löschen will
				$this->msg->setMessageContent("WRONGDATEFORMAT@@", "im Feld \"@\" darf nur das Format @ verwendet werden");
	            $this->msg->setMessageContent("COLUMNNOTFILLED@", "Das Feld \"@\" muss befuellt sein");
	            $this->msg->setMessageContent("NOINTEGER@", "Das Feld \"@\" muss eine ganze Zahl beinhalten");
	            $this->msg->setMessageContent("NOFLOAT@", "Das Feld \"@\" muss eine Zahl beinhalten");
	            $this->msg->setMessageContent("TOLONGSTRING@@", "Die Zeichenlaenge im Feld \"@\" darf maximal @ Zeichen beinhalten");
	            $this->msg->setMessageContent("COLUMNVALUEEXIST@", "Der Inhalt fuer das Feld \"@\" existiert bereits in der Datenbank");
	            $this->msg->setMessageContent("COMBICOLUMNVALUEEXIST@@", "Die selbige Kombination von @ UND @ \\nist in der Datenbank bereits vorhanden");
	            $this->msg->setMessageContent("WRONGOLDPASSWORD", "Das alte Passwort ist nicht korrekt");
	            $this->msg->setMessageContent("PASSWORDNOTSAME", "Das neue Passwort stimmt nicht ueberein");
				$this->msg->setMessageContent("WRONGUPLOADTYPE@@@", "Das File im Feld \"@\" darf nur die Typen @ besitzen (aktueller Type:@)");
	            $this->msg->setMessageContent("TOBIGUPLOADFILE@@@", "Das File im Feld \"@\" darf nicht mehr als @ Byte haben (errechnete Groesse:@)");
	            $this->msg->setMessageContent("WRONGIMAGELENGTH@@@", "Das Bild im Feld \"@\" darf die Abmasse von @/@ Pixel nicht ueberschreiten");
	            $this->msg->setMessageContent("WRONGIMAGEHEIGHT@@", "Das Bild im Feld \"@\" darf die Hoehe von @ Pixel nicht ueberschreiten");
	            $this->msg->setMessageContent("WRONGIMAGEWIDTH@@", "Das Bild im Feld \"@\" darf die Breite von @ Pixel nicht ueberschreiten");
	            $this->msg->setMessageContent("UPLOADERROR@@", "ERROR @: das File @ konnte nicht hochgeladen werden");
				$this->msg->setMessageContent("NOUPDATE", "Es wurde kein Inhalt geaendert,\\nsomit kann auch kein Update durchgefuehrt werden");
				$this->msg->setMessageContent("CALLBACKERROR@", "@");
				$this->msg->setMessageContent("SQLERROR@", "@");
				$this->msg->setMessageContent("NOUPDATEROW@", "in der Tabelle @ ist keine Zeile zum aendern vorhanden");
				$this->msg->setMessageContent("NOCLUSTERCREATE@@", "cluster @ fuer '@', konnte nicht erstellt werden");
				$this->msg->setMessageContent("NODELETE_FK@", "Dieser Eintrag kann nicht gelöscht werden, da noch Refferenze(n) von '@' auf diesen bestehen."); 
				
			}else // language have to be english ('en')
			{
				$this->aSelectNames["select"]= "&#160;&#160; please select &#160;&#160;";
				$this->aSelectNames["null_entry"]= "&#160;&#160; entry can be null &#160;&#160;";
				$this->aSelectNames["left_select"]= "&#160;&#160; select first on left side &#160;&#160;";
				$this->aSelectNames["no_entrys"]= "&#160;&#160; no entrys exist &#160;&#160;";
				
				$this->msg->setMessageContent("BOXDISPLAY", ""); // Box wird am Bildschirm angezeigt
				$this->msg->setMessageContent("AND", "and");
				$this->msg->setMessageContent("NODELETE_FK@", "cannot remove this entry, because entry from @ points to them");
				$this->msg->setMessageContent("NOROWTODELETE", ""); // es wurde kein Eintrag in der Datenbank zum löschen gefunden
				$this->msg->setMessageContent("DELETEQUESTION", "do you want to delete this entry?"); // Frage ob der User wirklich löschen will
				$this->msg->setMessageContent("WRONGDATEFORMAT@@", "inside file \"@\" should be used format @");
	            $this->msg->setMessageContent("COLUMNNOTFILLED@", "the field \"@\" have to be filled");
	            $this->msg->setMessageContent("NOINTEGER@", "inside field \"@\" have to be used integer value");
	            $this->msg->setMessageContent("NOFLOAT@", "inside field \"@\" have to be used an number");
	            $this->msg->setMessageContent("TOLONGSTRING@@", "the character length inside the field \"@\" can have maximal @ signs");
	            $this->msg->setMessageContent("COLUMNVALUEEXIST@", "the content of the field \"@\" was existing in database before");
	            $this->msg->setMessageContent("COMBICOLUMNVALUEEXIST@@", "the same combination from @ and @ \\nexists always in database");
	            $this->msg->setMessageContent("WRONGOLDPASSWORD", "wrong old password");
	            $this->msg->setMessageContent("PASSWORDNOTSAME", "the new passwords are not the same");
				$this->msg->setMessageContent("WRONGUPLOADTYPE@@@", "the file inside the field \"@\" can only have the types @ (actual type:@)");
	            $this->msg->setMessageContent("TOBIGUPLOADFILE@@@", "the file inside the field \"@\" can not have more than @ byte (calculated size:@)");
	            $this->msg->setMessageContent("WRONGIMAGELENGTH@@@", "the picture inside the field \"@\" should'nt be greater than @/@ pixels");
	            $this->msg->setMessageContent("WRONGIMAGEHEIGHT@@", "the picture inside the field \"@\" can not have more height than @ pixels");
	            $this->msg->setMessageContent("WRONGIMAGEWIDTH@@", "the picture inside the field \"@\" can not have more width than @ pixels");
	            $this->msg->setMessageContent("UPLOADERROR@@", "ERROR @: the field @ can not be uploaded");
				$this->msg->setMessageContent("NOUPDATE", "It wasn't change any content");
				$this->msg->setMessageContent("CALLBACKERROR@", "@");
				$this->msg->setMessageContent("SQLERROR@", "@");
				$this->msg->setMessageContent("NOUPDATEROW@", "inside table @ wasn't an row to change");
				$this->msg->setMessageContent("NOCLUSTERCREATE@@", "cannot create cluster @ for '@'");
	
			}
		}
		function inputSize($column, $width, $height= null)
		{
			$this->aInputSize[$column]["width"]= $width;
			if($height)
				$this->aInputSize[$column]["height"]= $height;
		}
		function passwordNames($firstName, $secondName, $thirdName= null)
		{
			$this->passwordNames[]= $firstName;
			$this->passwordNames[]= $secondName;
			if($thirdName)
				$this->passwordNames[]= $thirdName;
		}
		function password($fieldName, $bEncode= true)
		{
			$this->password= $fieldName;
			$this->pwdEncoded= $bEncode;
		}
		function getJoinArray($aJoins, $post)
		{
			global $HTTP_POST_VARS;
			
	 		$aRv= array();
	 		// toDo: use this time only from column names
	 		//       but in the future should also use alias columns
	 		//       when the join is over more tables
	 		$bFromColumnAlias= true;
			$oTable= $this->asDBTable;
			Tag::echoDebug("form.join", "make joins for table ".$oTable->getName());
			$fks= &$oTable->getForeignKeys();
			if(STCheck::isDebug())
			{
    			$bToOwn= false;
    			foreach($fks as $tableName=>$tableObj)
    			{
    			    if($this->asDBTable->Name == $tableName)
    			    {
    			        $bToOwn= true;
    			        break;
    			    }
    			}
    			if($bToOwn)
    			{
    			    echo "<br><br>";
        			showLine();
        			echo "create join-table from {$this->asDBTable->Name} to him self<br>";
        			echo "----------------------------------------------------------<br>";
        			echo "toDo: there query limitations inside where statement<br>";
        			echo "      see debug('form.join')<br>";
        			echo "----------------------------------------------------------<br>";
        			st_print_r($fks,3);
    			}
			}
			if(count($fks))
			{
				foreach($fks as $otherTableName=>$content)
				{
					foreach($content as $joinColumns)
					{
    					Tag::echoDebug("form.join", "to table ".$otherTableName);
    				 	/*$joinTable= &$joinColumns["table"];// table ist angegeben wenn auf eine andere DB referenziert wird
    			 		if(!isset($joinTable))
    						$joinTable= &$this->tableContainer->getTable($otherTableName);*/
						$joinTable= $oTable->getFkTable($joinColumns["own"], false);
						$joinTable->clearIndexSelect();

    					// alex 09/05/2005:	boolean $bInside auf string $sPkInside ge�ndert
    					//					da wenn die Column schon im select war (bInside==true)
    					//					wurde Sie bei der Angabe im rowResult nicht als Alias-Namen
    					//					gesucht. Jetzt steht dieser in $sPkInside
    					$sPkColumn= "unknown";
    					$sPkInside= "unknown";

    					//$fks= $this->asDBTable->getForeignKeyModification();
    					foreach($joinTable->identification as $columns)
    					{
    						if($columns["column"]==$joinColumns["other"])
    						{
    						    $sPkColumn= $columns['column'];
    						    if( $bFromColumnAlias &&
    						        isset($columns['alias'])  )
    						    {
    								$sPkInside= $columns['alias'];
    						    }else
    								$sPkInside= $sPkColumn;
    							break;
    						}
    					}
    					// alex 09/05/2005:	wenn damaliges bInside false war
    					//					wurde und wird die PK Column zum select hinzugef�gt
    					//					nun kommt noch dazu, dass sPkInside welches ja "" ist
    					//					auch den Column-Namen erh�lt, da er unten als Angabe
    					//					f�r den PK im rowResult ben�tigt wird
    					if($sPkInside == "unknown")
    					{// if the column not as identification column defined, add it
    					    $joinTable->identifColumn($joinColumns["other"]);
    						$sPkInside= $joinColumns["other"];
    					}

    					STCheck::echoDebug("form.join", "make join select from table ".$joinTable->getName());
    					$statement= $joinTable->getStatement(/*identification select*/true, $bFromColumnAlias);
    					$result= $joinTable->db->fetch_array($statement, MYSQL_ASSOC, $this->getOnError("SQL"));
    					$this->setSqlError($result, $joinTable->db);
    					if(!isset($aRv[$joinColumns["own"]]))
    						$aRv[$joinColumns["own"]]= array();
    					$tableResult= array();
						if(is_array($result))
						{
    						foreach($result as $row)
    						{
        						$rowResult= array();
        						// alex 09/05/2005: PK definition now from sPkInside
        						$rowResult["PK"]= $row[$sPkInside];
        						$string= "";
        						foreach($row as $columnKey=>$columnValue)
        						{
        							$rowResult["columns"][$columnKey]= $columnValue;
        				//			if(!$sPkInside)
        				/*			{
        								if($columnKey!=$joinColumns["other"])
        									$string.= $columnValue." - ";
        							}else								*/
        							if(	$sPkInside != $columnKey ||
        								count($row) == 1			)
        							{
        								$string.= $columnValue." - ";
        							}
        						}
        						$string= substr($string, 0, strlen($string)-3);
        						//if(trim($string)=="")
        						//	$string= $rowResult["PK"];
        						$rowResult["Name"]= $string;
        						$tableResult[]= $rowResult;
    						}
						}
    					$aRv[$joinColumns["own"]][]= $tableResult;
					}
				}
				foreach($this->aDropDowns as $dropDownColumn)
				{
					$aRv[$dropDownColumn]= array(array());
				}				
				$this->makeJoinCallback($aRv, $post);
				return $aRv;
			}
	 		if( !$aJoins
				or
				!count($aJoins)	)
			{
				return $aRv;
			}

			$action= $this->action;//echo "join: ";
			foreach($aJoins as $joinField=>$join)
			{
				//$count= 0;
				//$aRv[$count]= $Table;
				$joins= preg_split("/[ ]+and[ ]+/", $join);
				foreach($joins as $join)
				{// gehe jeden join getrennt mit "and" durch
				 		$result= preg_split("/[ ]*=[ ]*/", $join);
						$name= "";
						foreach($result as $sides)
						{// im einzelnen join alle zwei Seiten vom "="
  				 		$side= preg_split("/[.]+/", $sides);
							if($action==STINSERT)
								$c= count($side)-1;
							else		// beim Update wird von der Haupttabelle ausgegangen,
								$c= 1;	// beim Insert von hinten, der letzten Tabelle

							$where= "";
							$aList= array();
							while($c>0 && $c<count($side))
    					{// wenn eine Seite mehrere eintr�ge getrennt mit "." hat
							 // bedeutet dies, dass eine andere Tabelle mitangegeben ist
							 // welche immer als erstes steht dann das Feld.
							 // Es k�nnen dabei auch mehrere Tabellen angegeben sein => Tabelle.Feld.Tabelle.Feld ...
								$PK= "";
						 		$table= $side[$c-1];
								Tag::echoDebug("show.db.fields", "get file:".__file__." line:".__line__);
								$aTableFields= $this->getFieldArray();
								reset($aTableFields);
								foreach($aTableFields as $field)
								{
									if(preg_match("/primary_key/", $field["flags"]))
										$PK= $field["name"];
								}
								if( $PK=="" )
								{
								 		echo "<br>Error: no <b>primary Key</b> be set in Table <b>$table</b><br>";
										exit();
								}
						 		$statement= "select distinct $PK PK";
								$Bez= "";
								$syn= preg_split("/[ ]*\|[ ]*/", $side[$c]);
								$syn_anz= count($syn);
								if($syn_anz>1)
								{
									$statement.= ",concat_ws(' &#160;-&#160; '";
                                for($i= 0; $i<$syn_anz; $i++)
                                {// sind mehrere Felder getrennt mit "|" angegeben
    							 // werden diese alle in einer Zeile angegeben
									 	$statement.= ",".$syn[$i];
                                    if($i==0)
    									$Bez= $syn[$i];
                                }
									$statement.= ") Name";
								}else
								{
									$statement.= ",".$side[$c]. " Name";
									$Bez= $side[$c];
								}
                            $statement.= " from $table".$where;
                            $aFields= $this->db->fetch_array($statement, MYSQL_ASSOC, $this->getOnError("SQL"));
								$this->setSqlError($aFields);
								$aFields["Bez"]= $Bez;
								if($action==STUPDATE)
								{
									// ToDo: Auswahl-Selectierung,
									//		 damit im zweiten PopUp bei aktion Update nicht alles angezeigt wird
									//if(isset($post[$Bez]))
										//$where= " where $PK='".$post[$Bez]."'";
								}else
									$where= " where $Bez='".$HTTP_POST_VARS[$Bez]."'";
								$aList[($c-1)/2]= $aFields;

								// beim Update wird von der Haupttabelle ausgegangen,
								// beim Insert von hinten, der letzten Tabelle
								if($action==STINSERT)
									$c-= 2;
								else
									$c+= 2;
  						}
							if(count($side)==1)
							{// wenn nicht Schleife dann Hauptname
  							$name= $side[0];
							}
							$aRv[$name]= $aList;
							//$aRv["POST"]= $HTTP_POST_VARS[$name];
						}
				}
			}

			$this->makeJoinCallback($aRv, $post);
			return $aRv;
		}
		function makeJoinCallback(&$joinArray, $post)
		{
			foreach($joinArray as $name=>$join)
			{
			    $oCallbackClass= new STCallbackClass($this->asDBTable, $join);
			    $oCallbackClass->action= $this->action;
				$oCallbackClass->sqlResult= $post;
				$oCallbackClass->joinResult= $joinArray[$name];
				//echo $name;st_print_r($this->aDisabled);echo "<br />";
				$oCallbackClass->aDisabled= array();
				if( isset($this->aDisabled[$name]) &&
				    $this->aDisabled[$name] == true     )
				{
					$oCallbackClass->disabled($name);
				}else
				    $oCallbackClass->enabled($name);
				$oCallbackClass->before= true;
				$oCallbackClass->MessageId= "MAKEJOIN";
				$result= $this->makeCallback("join", $oCallbackClass, $name, $this->action);
				if($result===true)
				{
					$this->aDisabled[$name]= $oCallbackClass->argument("disabled", $name);
					$joinArray[$name]= $oCallbackClass->joinResult;
				}
			}
		}
    function createColumns()
    {
		if(is_array($this->columns))
			return $this->columns;
		if($this->asDBTable)
		{
			$TableColumns= $this->asDBTable->getSelectedColumns();
			$this->asSelect= array_merge($TableColumns, $this->asSelect);
		}
		if(count($this->asSelect))
		{
			$columns= array();
			foreach($this->asSelect as $aColumn)
			{
				$column= $aColumn["column"];
				$alias= $aColumn["alias"];
				if(!$alias)
					$alias= $column;
				$columns[$column]= $alias;
			}
			$this->columns= $columns;
			return $this->columns;
		}
  		if(	trim($this->columns=="*")
				and
				!count($this->asSelect))
  		{// erstelle Spalten-Array aus Tabelle
				Tag::echoDebug("show.db.fields", "get file:".__file__." line:".__line__);
				$fields= $this->getFieldArray();
  			$columns= array();
  			foreach( $fields as $field )
  				$columns[$field["name"]]= $field["name"];
  		}else
  		{// erstelle Spalten-Array aus Statement
				$sColumns= $this->columns;
				if(count($this->asSelect))
				{
					//$sColumns= $this->getSelect();
					$sColumns= preg_replace("/[ ]+as[ ]+/", " ", $sColumns);
				}
  		 	$fromStat= preg_split("/,/", $sColumns);
				$columns= array();

  				foreach($fromStat as $Nr => $column)
  				{
  						$column= trim($column);
  				 		$names= preg_split("/\s|'([^']+)'/", $column, -1,
										PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
							if( count($names)>1 )
  							$columns[$names[0]]= $names[1];
  						else
  							$columns[$names[0]]= $names[0];
							// Durchschnitt der Feld-Bezeichnungen ausrechnen
							$Len= $this->intersecBez+strlen($names[0]);
							if($this->intersecBez==0)
								$this->intersecBez= $Len;
							else
  							$this->intersecBez= $Len / 2;
  				}
  		}
		$this->columns= $columns;
        return $columns;
    }
    private function findCoumnFieldKey(array $columns)
    {
        $aRv= array();
        $fields= $this->getFieldArray();
        foreach($fields as $key=>$field)
        {
            if(isset($columns[$field['name']]))
                $aRv[$field['name']]= $key;
        }
        return $aRv;
    }
    function orderByColumns($fields, $columns)
    {
    	$count= 0;
    	$newFields= array();
		$set= array();
    	foreach($columns as $column => $name)
        {
			$bfound= false;
        	for($i= 0; $i<count($fields); $i++)
            {
            	if($column==$fields[$i]["name"])
                {
					$bfound= true;
                	$newFields[]= $fields[$i];
					$set[$name]= true;
					break;
                }
            }
			if(!$bfound)
				$newFields[]= array("name"=>$column);
        }
		if($this->asDBTable)
		{
			foreach($this->asDBTable->columns as $field)
			{
				if(	$field['type']==="get" &&				    
					!isset($set[$field['column']])	)
				{
					//$content= $this->asDBTable->getColumnContent($showColumn);
					$content= $field;
					//$content["name"]= $showColumn;// if column is not set in Table;
					$content["type"]= "getColumn";
					$newFields[count($newFields)]= $content;
				}
			}

		}
        return $newFields;
    }
    function upload($columnName, $toPath, $type= null, $byte= 0, $width= 0, $height= 0)
    {
    	$field= array();
    	$field["size"]= $byte;
    	$field["path"]= $toPath;
		if($type)
    		$field["type"]= $type;
    	if($width!=0)
    		$field["width"]= $width;
    	if($height!=0)
    		$field["height"]= $height;
    	$this->uploadFields["$columnName"]= $field;
    }
    /**
     * return previos number for auto_increment column
     * which will be automaticly generated from database
     * 
     * @return int new next number for column content
     */
	function createPreviousSelection()
	{
   		$table= &$this->asDBTable;
   		if(!isset($table))
   			return 0;
   		$tableName= $table->getName();
		if(isset($table->aAuto_increment["value"]))
			return $table->aAuto_increment["value"];

		if(count($table->aAuto_increment))
		{
			// look before wheter an insert with session is made
    		$selectStatement=  "select ".$table->aAuto_increment["PK"]." from ".$tableName;
    		$selectStatement.= " where ".$table->aAuto_increment["inColumn"];
    		$selectStatement.= "='".$table->aAuto_increment["session"]."'";
    		$pk= $this->db->fetch_single($selectStatement);
			if($pk===null)
			{
				// insert one row with session
    			$statement=  "insert into ".$tableName."(".$table->aAuto_increment["inColumn"];
    			$statement.= ") values('".$table->aAuto_increment["session"]."')";
    			$this->db->fetch($statement);
				$this->lastInsertID= $this->db->getLastInsertID();

				// select for PK row with session
    			$pk= $this->db->fetch_single($selectStatement);
			}

    		$table->aAuto_increment["value"]=$pk;
		}else
		{
    		$pk= $this->db->fetch_single("select max(".$this->asDBTable->getPkColumnName().") from $tableName");
			$this->setSqlError($pk);
			$pk++;
			$table->aAuto_increment["value"]=$pk;

		}
		return $pk;
	}
	function makeBox($tableName, $join, $where, $changedPost= NULL)
  	{
        global $HTTP_POST_VARS;
                
        /**
         * whether need javascript function DB_changeBox
         * @var boolean $needChangeBox
         */
		$needChangeBox= false;
		
		if($HTTP_POST_VARS)
		{//st_print_r($HTTP_POST_VARS);
			foreach($this->asDBTable->columns as $field)
			{
				$aEnumns= $this->countingEnums($field);
				if(	$aEnumns[0]==2 &&
					count($aEnumns)==3 &&
					!isset($HTTP_POST_VARS[$field["name"]])	)
				{
					//st_print_r($aEnumns);echo "<br />";
					$HTTP_POST_VARS[$field["name"]]= $aEnumns[1];
				}
			}//st_print_r($HTTP_POST_VARS);
		}
/*		showLine()
		st_print_r($post,5);
		st_print_r($changedPost,5);
		st_print_r($HTTP_POST_VARS,5);*/
		if(isset($changedPost))
		{
		    $post= $changedPost;
		}else
		    $post= $HTTP_POST_VARS;
		        
		// 19/06/2007 alex:	the post-vars must be the second parameter
		//					from array_merge, because from the second
		//					the first will be replaced
		$post= array_merge($this->aSetAlso, $post);
        /*$post= $HTTP_POST_VARS;
		foreach($this->aSetAlso as $key=>$value)
		{
			if(!isset($post[$key]))
				$post[$key]= $value;
		}*/
		
		Tag::echoDebug("show.db.fields", "get file:".__file__." line:".__line__);
  		$fields= $this->getFieldArray();// took fields from database
        $columns= $this->createColumns();// make array from column-name and alias-name inside $statement
        $fields= $this->orderByColumns($fields, $columns);//order the field-namen from database, after the $statement
        
		//create objects of HTML-Tags
		$form= new FormTag();
			$form->name("STForm");
			$form->method("post");
		if(count($this->uploadFields))
		{// wenn ein UploadFeld enthalten ist
			$form->enctype("multipart/form-data");
		}
		
		$display= false;
		if(!isset($post["STBoxes_action"]))
		{
			$display= true;
		    // change field names into column names
		    $newPost= array();
		    foreach($post as $key=>$value)
		    {
		        if(isset($columns[$key]))
		            $newPost[$columns[$key]]= $value;
		    }
		    $post= $newPost;
			// write content without values for every column row
			// into post variable
			foreach($fields as $content)
			    if(!isset($post[$columns[$content["name"]]]))
			        $post[$columns[$content["name"]]]= "";
		}
		$oCallbackClass= new STCallbackClass($this->asDBTable, $post);
		$oCallbackClass->action= $this->action;
		$oCallbackClass->display= true;//$display;
		$oCallbackClass->before= true;
		$oCallbackClass->rownum= 0;
		$oCallbackClass->sqlResult= $post;
		if($this->asDBTable)
		{
		    $this->asDBTable->modifyQueryLimitation();
			$oCallbackClass->where= $this->asDBTable->getWhere();
		}else
			$oCallbackClass->where= $this->where;

        if($this->action==STUPDATE)// && $post["STBoxes_action"]!="workOn_Database")
        {// hole die Felder aus der Datenbank, f�r vorab-Anzeige
		 //$aliasNames= array_flip($columns);
            //echo __FILE__." ".__FUNCTION__." line:".__LINE__."<br>";
    	    STCheck::echoDebug("table", "clone table as <b>[secure]</b> into own table. (maybe table will change afterwards)");
    		$oTable= clone $this->asDBTable;
    		$oTable->container= $this->asDBTable->container;
    		// 02/03/2023 alex:
    		//            for any selection with where statement
    		//            from other table, need foreign keys
    		//$oTable->clearFKs();// ich will die Spalten ohne verweiss auf eine N�chste Tabelle
    		//$oTable->clearSqlAliases();
    		//$oTable->clearAliases();// sowie alle orginal Spalten-Namen
    		$oTable->andWhere($this->where);
    		$where= $oTable->getWhere();
    		Tag::alert(!($where && $where->isModified()), "STItemBox::makeBox()", "no where-clausel defined to display");
    		$statement= $oTable->getStatement();
    		STCheck::echoDebug("db.main.statement", $statement);
			$this->db->query($statement, $this->getOnError("SQL"));
			$result= $this->db->fetch_row(MYSQL_ASSOC, $this->getOnError("SQL"));
			$tablePk= $this->asDBTable->getPkColumnName();
			if(isset($result[$tablePk]))
				$this->lastInsertID= $result[$tablePk];
			$this->setSqlError($result);

			if(is_array($result))
			{
				if($HTTP_POST_VARS)
				{
				    $aJoins= $this->getJoinArray($join, $result);
					// 11/09/2008 alex:	if an value from database not exists in POST_VARS,
					//					define it with null
					foreach($result as $column=>$value)
					{
						if(!isset($post[$column]))
						{
						    $post[$column]= null;
						}
					}
					// 19/06/2007 alex:	the post-values must be the second parameter
        			//					from array_merge, because from the second
        			//					the first will be replaced
					$post= array_merge($result, $post);
				}else
					$post= $result;
				
				$this->aResult= $post;
				$oCallbackClass->sqlResult= $post;
				if(STCheck::isDebug())
				{
					if(STCheck::isDebug("db.main.statement"))
					{
						if(isset($post["STBoxes_action"]))
						{
						    STCheck::echoDebug("db.main.statement", "exist result from db");
							$space= STCheck::echoDebug("db.main.statement", "is new result:");
							st_print_r($post,2, $space);
							echo "<br />";
						}

					}elseif(STCheck::isDebug("db.statement"))
						STCheck::echoDebug("db.statement", "create result for exist values in the db row");
				}
				// for UPDATE need callback for where clausel
				$this->makeCallback($this->action, $oCallbackClass, STUPDATE, 0);
				$impl= $oCallbackClass->getHtmlContent();
				if(isset($impl))
				{
					$rowtag= new RowTag();
						$coltag= new ColumnTag(TD);
							$coltag->addObj($impl);
							$coltag->colspan(5);
							$coltag->align("center");
						$rowtag->addObj($coltag);
					$this->addObj($rowtag);
				}
				//echo "ErrorString:";st_print_r($sErrorString);echo "<br />";
				//echo "callbackResult:";st_print_r($oCallbackClass->sqlResult);
				
				if($this->asDBTable)
					$this->asDBTable->where($oCallbackClass->where);
				else
					$this->where= $oCallbackClass->where;
			
			}
				
				
				
        }

   		$aJoins= $this->getJoinArray($join, $post);//create all content of popup-menues
   		
  		/**
  		 * increasing loop for field count
  		 * @var integer $x
  		 */
  		$x= 0;
  		/**
  		 * if defined a password column
         * the $rePwd loop variable will be increased (either $x)
		 * until the end of passwordNames member array be reached
  		 * @var integer $rePwd
  		 */
		$rePwd= 0;
		$previousSelectionDone= false;
		/**
		 * define true whether currently an inner table be set
		 * $var boolean $bInner
		 */
		$bInner= false;
		/**
		 * inner table for better design.<br />
		 * if inner table is NULL, currently no inner table be set
		 * @var object $innerTable
		 */
		$innerTable= NULL;
		/**
		 * if pop-up menu disabled
		 * value do not come in at the post variable
		 * so define a hidden input with the value
		 * and store inside an diff tag
		 * @var object $hidden
		 */
		$hidden= new DivTag();
		
		if(STCheck::isDebug("itembox.columns"))
		{
		    STCheck::echoDebug("itembox.columns", "loop through all columns of database");
		    $bShowColumns= true;
		}else
		    $bShowColumns= false;
		reset($fields);
		if($bShowColumns)
		{
		    echo "database result:";
		    st_print_r($this->aResult);
		}
		while($x<count($fields))
		{// go throug all fields from database
			
			$field= $fields[$x];		
			$name= $field["name"];
			$column= $columns[$name];
			$postColumn= $this->asDBTable->defineDocumentItemBoxName($column);
			//$postColumn= preg_replace("/[ \t]/", $this->sReplaceSpaceSign, $column);
			if( (   !isset($HTTP_POST_VARS) ||
			        empty($HTTP_POST_VARS)       ) &&
			    isset($post[$column])                    )
			{
			    $columnValue= $post[$column];
			    
			}elseif( isset($HTTP_POST_VARS) &&
			         !empty($HTTP_POST_VARS) &&
			         isset($post[$postColumn])    )
			{
			    $columnValue= $post[$postColumn];
			}else
			    $columnValue= null;
			$searchColumn= $column;
			if($searchColumn == $this->password)
			    $searchColumn= reset($this->passwordNames);
			$bDisabled= false;
			if(isset($this->aDisabled[$searchColumn]))
			{
			    foreach ($this->aDisabled[$searchColumn] as $action=>$bSet)
			    {
			        if( $action == 0 || // 0 means action is STINSERT and STUPDATE but no STLIST
			            $action == $this->action     )
			        {
			            $bDisabled= $bSet;
			            break;
			        }
			    }
			}
			if($bShowColumns){
			    echo "<br /><br />row $x - ";
			    if(isset($columns[$name]))
			        echo $columns[$name]. " <b>[show]</b><br />";
			    else
			        echo "$name<br />";
			    echo "value:$columnValue<br>";
			    echo "field type:".$field['type']."<br />";
			    echo "disabled:";st_print_r($bDisabled);
			    st_print_r($field, 2);
			}
		 	if(	isset($columns[$name]) ||
				$field["type"]=="getColumn")
			{//show only if the field exist inside the statement

				if(	!isset($this->asSelect[$x-1]["nextLine"]) ||
					$this->asSelect[$x-1]["nextLine"] !== false	)
				{// if nextLine from column before is false,
				 // create no new Row
					$tr= new RowTag();
					if($bShowColumns)
					    echo "create column ".$columns[$this->asSelect[$x]["column"]]." inside new row<br>";
					
				}elseif($bShowColumns)
					echo "create column ".$this->asSelect[$x]["column"]." inside same row<br>";
				$td= new ColumnTag(TD);

				$td->add("&#160;&#160;&#160;");
				$tr->add($td);

                if( isset($aJoins[$name]) &&
                    $field['type'] != "getColumn"   )
                {   // if the field also exist in the join array
                    // show a PopUp-Menu
                    $joinAnz= count($aJoins[$name]);
					for($n= $joinAnz-1; $n>=0; $n--)
					{// f�r das Entsprechende Feld im $aJoins[$name]
					 // k�nnen auch mehrere Tabellen f�rs PopUp-Men� sein
					 // -> alle in verkehrter Reihenfolge und in einer Linie anzeigen
					 	$td= new ColumnTag(TD);

						$td->add(br());
                    	$aRows= $aJoins[$name][$n];
                    	$Bez= $columns[$name];
						if($n>0)
							$Bez= $columns[$aRows["Bez"]];
						$selName= $name;
						if($n>0)
							$selName= $aRows["Bez"];

						// description output for PopUp-Menu
						$td->add($Bez);
						$td->add(":");
						$td->add(br());
						$tr->add($td);

						$td= new ColumnTag(TD);
						$td->add(br());

						$select= new SelectTag();
						$select->name($postColumn);
						$select->size(1);
						if($bDisabled)
							$select->disabled();
						if(	(	$joinAnz>1
								and
								($n+1)==$joinAnz	)
							or
							isset($this->asDBTable->aRefreshes[$field["name"]])	)
						{// ist eine dritte Tabelle im Spiel,
						 // wird nur die Ausgabe, bei �nderung
						 // des PopUp-Men�s der dritten Tabelle,
						 // aktualisiert.
							$select->onChange("javascript:DB_changeBox('update')");
							$needChangeBox= true;
						}
						$maxlen= 0;
						$bNotNullField= false;
					 	$option= new OptionTag();
						if(count($aRows)>0)
						{
							if(preg_match("/not_null/", $field["flags"]))
							{
								$bNotNullField= true;
								if($this->action==STINSERT)
									$option->add($this->aSelectNames["select"]);
							}else
								$option->add($this->aSelectNames["null_entry"]);
						}else
						{
							if($joinAnz>1)
								$option->add($this->aSelectNames["left_select"]);
							else
								$option->add($this->aSelectNames["no_entrys"]);
						}
						$option->value("");
						$maxlen= -10;
						$select->add($option);
						$bOneEntry= false;
						if(count($aRows) == 1)
						    $bOneEntry= true;
						$selectedPK= null;
                        foreach($aRows as $row)
                        {// show all options for the select-tag
  							if(!is_array($row))
  								break;
							$option= new OptionTag();
							$option->value($row["PK"]);
            				if( isset($columnValue) &&
            					$columnValue==$row["PK"] ||
            					(	$bOneEntry &&
            						$bNotNullField	)			)
            				{
            					$option->selected();
            					$selectedPK= $row['PK'];
            				}
            				$option->add($row["Name"]);
								$length= strlen($row["Name"]);
								if($length>$maxlen)
									$maxlen= $length;
  							$Len= $this->intersecFld+$length;
  							if($this->intersecFld==0)
  								$this->intersecFld= $Len;
  							else
  								$this->intersecFld= $Len / 2;
								$select->add($option);
                        }

                        if(	$bNotNullField &&
                            $bDisabled == false &&
                        	!isset($this->aSetAlso[$field["name"]]) &&
                            !$this->asDBTable->hasDefinedFlag($field['name'], $this->action, "null")    )
                        {// if field is not null, no pre-select and not disabled
	                        $td->add("*");
	                    }else 
	                        $td->add("&#160;&#160;");
	                    
	                    $td->add($select);
	                    if($bDisabled)
	                    {// if select box is disabled value not set inside POST
	                        $hinput= new InputTag();
	                           $hinput->name($postColumn);
	                           $hinput->type("hidden");
	                           if(!isset($selectedPK))
	                               $selectedPK= $columnValue;
	                               $hinput->value($selectedPK);
	                        $td->add($hinput);
	                    }	                       
						if($this->intersecFld)
							$td->colspan(round($maxlen/$this->intersecFld));
						$tr->add($td);
					}
                }else // no PopUp-Menue end of if( $aJoins[$name] )
                {
						$bSingleEnum= false;
						// Bezeichnungs-Angabe f�r Felder
						$td= new ColumnTag(TD);
						$td->add(br());
						if( isset($field["type"]) &&
							$field["type"] !== "getColumn"	)
						{
							if($this->password==$name)
							{// wenn die Schleife auf ein Passwort Stosst
							 // wird der Name aus dem Array passwordNames genommen.
							 // Je nach dem der wievielte Durchlauf ist!
							 	$sName= $this->passwordNames[$rePwd];
								if(!$sName)
									$sName= $columns[$name];
								$td->add($sName);
							}else
							{
								$td->add($columns[$name]);
							}
							$td->add(":");
							if($field["len"]>3000)
								$td->valign("top");
						}
						
						if( isset($columns[$field["name"]]) &&
						    isset($this->uploadFields[$columns[$field["name"]]]))
						{
							$upload= $this->uploadFields[$columns[$field["name"]]];
							$zusatz= "";
							$zusatzWH= "";
							if($upload["size"]>0)
								$zusatz= $upload["size"]. " byte ";
							$bSetWH= false;
							if($upload["width"]>0)
							{
								$bSetWH= true;
								$zusatz.= $upload["width"];
								$zusatzWH= "Breite";
								if($upload["width"]>0)
								{
									$zusatz.= "/";
									$zusatzWH.= "/";
								}
							}
							if($upload["height"]>0)
							{
								$bSetWH= true;
								$zusatz.= $upload["height"];
								$zusatzWH.= "H&ouml;he";
							}
							if($zusatz!="")
							{
								$zusatz= "( maximal ".$zusatz;
								if($bSetWH)
									$zusatz.= " $zusatzWH";
								$zusatz.= " )";
								$td->add(br());
								$td->add($zusatz);
							}
							//wenn die Gr�sse vom Upload durch den Input-Tag hidden
							//angegeben wird, wird wenn das File zu gross ist
							//dieses nicht mitgeschickt, aber niemand weiss das es mitgeschickt wurde
							/*if($upload["size"]>0)
							{
								$input= new InputTag();
									$input->type("hidden");
									$input->name("MAX_FILE_SIZE");
									$input->value($upload["size"]);
								$td->add($input);
							}*/
						}
						$tr->add($td);

						//Eingabe-, Ausgabe-Feld
						$td= new ColumnTag(TD);
						$td->add(br());
						
					if($field["type"]==="getColumn")
					{
						$input= new InputTag();
							$input->name($postColumn);
							$input->type("hidden");
							$input->value($columnValue);
						$td->add($input);
  					}elseif( preg_match("/auto_increment/", $field["flags"]) )
    				{// wenn das Feld einen auto_increment besitzt,
						// soll dieses Feld nicht zum �ndern angeboten werden
						$zahl= ""; 
  						if($this->action==STINSERT)
  						{
							$zahl= $this->createPreviousSelection();
							$previousSelectionDone= true;
  						}else
  						{
  							if(isset($columnValue))
  								$zahl= $columnValue;
  						}
  						$td->add($zahl);
						$input= new InputTag();
							$input->name($postColumn);
							$input->type("hidden");
							$input->value($zahl);
						$td->add($input);
    				}else // -> changing allowed
  				 	{
  				 		if(isset($field["name"]))
  				 		{
						$aliasName= $this->asDBTable->searchByColumn($field["name"], /* no warning */-1);
							if(isset($aliasName["alias"]))
								$mce= &$this->asDBTable->getTinyMCE($aliasName["alias"]);
  				 		}
							if(	$field["len"]>3000
								or
								isset($mce)			)
							{
								$input= new TextareaTag();
							}else
								$input= new InputTag();
							if($bDisabled)
								$input->disabled();
							if(isset($this->asDBTable->aEvents[$name]))
							{
								foreach($this->asDBTable->aEvents[$name] as $event => $function)
									$input->insertAttribute($event, $function);
							}
							if(preg_match("/enum/", $field["flags"]))
							{//wenn Feld einen Enumbesitzt checkbox od. toDo: radiobutton erzeugen
								$input->name($postColumn);
								$aEnums= $this->countingEnums($field);
								if(	isset($this->enumField[$field["name"]]) &&
									$this->enumField[$field["name"]] == "pull_down"	)
								{
									$input= new SelectTag();
									$input->name($postColumn);
									$input->size(1);
									$nEnums= $aEnums[0];
									if(!isset($this->aSetAlso[$field["name"]]))
									{
										$option= new OptionTag();
										if(!preg_match("/not_null/", $field["flags"]))
										{
											--$nEnums;
											$option->add($this->aSelectNames["null_entry"]);
											if(	$this->action == STUPDATE &&
												$columnValue == null	)
											{
												$option->selected();
											}
										}else
											$option->add($this->aSelectNames["no_entrys"]);
										$option->value("");
										$input->add($option);
									}
									for($n= 1; $n<=$nEnums; $n++)
									{
										$option= new OptionTag();
										$option->add($aEnums[$n]);
										if(	(	$this->action == STINSERT &&
												isset($this->aSetAlso[$field["name"]]) &&
												$this->aSetAlso[$field["name"]] == $aEnums[$n]	) ||
											(	$this->action == STUPDATE &&
												isset($columnValue) &&
												$columnValue == $aEnums[$n]	)			)
										{
											$option->selected();
										} 
										$input->add($option);
									}									
									
								}else if(	$aEnums[0]>2 ||
											(	isset($this->enumField[$field["name"]]) &&
												$this->enumField[$field["name"]] == "radio"	)	)
								{// toDo: mehrere radiobuttons
									$input= new DivTag("radiobuttons");
									$enumCount= count($aEnums)-1;// hierbei darf nicht das Feld aEnums[0] gew�hlt werden,
																 // da das NOT NULL Feld wenn vorhanden mitgerechnet wird
									$aDisabled= $bDisabled;
									for($n= 1; $n<=$enumCount; $n++)
									{
										$radio= new InputTag();
											$radio->type("radio");
											$radio->name($postColumn);
											$radio->value($aEnums[$n]);
											if(isset($this->asDBTable->aRefreshes[$field["name"]]))
                							{
                								$radio->onClick("javascript:DB_changeBox('update')");
                								$needChangeBox= true;
                							}
											if(	isset($columnValue) &&
												$columnValue == $aEnums[$n]	)
											{
												$radio->checked();
											}
											if(	$aDisabled===true or
											    (   is_array($aDisabled) &&
											        isset($aEnums[$n]) &&
											        isset($aDisabled[$aEnums[$n]]) &&
												    $aDisabled[$aEnums[$n]]!==null	)    )
											{
												$radio->disabled();
											}
										$input->add($radio);
										$input->add($aEnums[$n]);
									}
								}else 
								{// for two enum entries with not null field,
								 // show only a checkbox.
								 // The first entry is than the not checked state
								 	$bSingleEnum= true;
									$input->type("checkbox");
									$input->value($aEnums[2]);
									if($columnValue==$aEnums[2])
										$input->checked();
									if(isset($this->asDBTable->aRefreshes[$field["name"]]))
                					{
                						$input->onClick("javascript:DB_changeBox('update')");
                						$needChangeBox= true;
                					}
								}
							}else
							{//normales Eingabefeld

  							if($this->password==$name)
  							{// wenn auf ein Passwort gestossen wird,
  							 // im Array passwordNames ist ein Eintrag oder keiner,
  							 // gibt es nur ein Feld und dessen Name ist der SpaltenName.
  							 // Sind zwei Eintr�ge im Array, hat das erste Feld den Namen
  							 // der Spalte und das zweite mit dem Synonym "re_" vorangesetzt.
  							 // Bei maximal drei Eintr�gen, hat das erste Feld das Synonym "old_"
  							 // das zweite keines und das dritte wieder "re_".
  								$vor= "";
  								if($rePwd==0)
  								{
  									if(count($this->passwordNames)==3)
  										$vor= "old_";
  									$sName= $vor.$field["name"];
  								}elseif($rePwd==1)
  								{
  									if(count($this->passwordNames)==2)
  										$vor= "re_";
  									$sName= $vor.$field["name"];
  								}else
  									$sName= "re_".$field["name"];
  								$input->name($sName);
  								$input->type("password");
  							}elseif(isset($this->uploadFields[$columns[$field["name"]]]))
							{// Feld f�r Upload
									if(isset($columnValue))
									{
											$input->name("old_upload_file_".$field["name"]);
											$input->type("hidden");
											$input->value($columnValue);
										$td->add($input);
										$input= new InputTag();
									}
									$input->name($postColumn);
									$input->type("file");
									$input->accept($this->uploadFields[$field["name"]]["type"]);
							}elseif($field["type"]=="date")
							{// Datums-Feld
									$input->name($postColumn);
									$input->type("date");
									$value= $this->db->makeUserDateFormat($columnValue);
									if(!$value)
										$value= $columnValue;
									if(!$value)
										$value= $this->db->getNullDate();
									$input->value($value);
							}elseif($field["type"]=="time")
							{// Datums-Feld
									$input->name($postColumn);
									$input->type("time");
									$value= $this->db->makeUserTimeFormat($columnValue);
									if(!$value)
										$value= $columnValue;
									if(!$value)
										$value= $this->db->getNullTime();
									$input->value($value);
							}else
  							{// normales Eingabe-Feld
  								$input->name($postColumn);
								if(	$field["len"]<3000
									and
									!isset($mce)		)
								{
  									$input->type("text");
  									if(isset($columnValue))
  										$input->value($columnValue);
								}else
								{
									if(isset($columnValue))
									{
										$value= trim($columnValue);
										$firstChar= substr($columnValue, 0, 1);
										$lastChar= substr($columnValue, strlen($columnValue)-1);
										if($firstChar==" " || $firstChar=="\t")
											$value= " ".$value;
										if($lastChar==" " || $lastChar=="\t")
											$value.= " ";
										$input->add($value);
									}
								}
  							}
							if(isset($this->aInputSize[$field["name"]]))
							{
								$size= $this->aInputSize[$field["name"]]["width"];
							}else
							{
  								$size= $field["len"];
    							if( $size>500 )
    							 	$size= 70;
    							elseif( $size>100 )
    								$size= 60;
    							elseif( $size>50 )
    								$size= 50;
    							elseif( $size>30 )
    								$size= 30;
							}
  							$Len= $this->intersecFld+$size;
  							if($this->intersecFld==0)
  								$this->intersecFld= $Len;
  							else
  								$this->intersecFld= $Len / 2;
    						if(	$field["len"]>3000
								or
								isset($mce)			)
    						{
    							$input->cols($size);
    							if(isset($this->aInputSize[$field["name"]]["height"]))
    								$input->rows($this->aInputSize[$field["name"]]["height"]);
    							else
								{
									if($field["len"]>3000)
    									$input->rows(15);
									else
										$input->rows(2);
								}
    						}else
  								$input->size($size);
							}
							if(	isset($this->asDBTable->aRefreshes[$field["name"]])
								and
								$input->tag=="input")
							{
								$input->onChange("javascript:DB_changeBox('update')");
								$needChangeBox= true;
							}
							if(!$bSingleEnum)
							{							        
								if(	preg_match("/not_null/", $field["flags"]) &&
	                        	    !isset($this->aSetAlso[$field["name"]])	&&
								    !$this->asDBTable->hasDefinedFlag($field['name'], $this->action, "null")    )
								{
									$td->add("*"); // if field is not null and no pre-select set
								}else
									$td->add("&#160;&#160;");
							}
							if(STCheck::isDebug("show.db.fields"))
							{
								$string= "dbname('".$field["name"]."') ";
								$string.= "type:".$field["type"]." ";
								if($field["type"] != "int")
									$string.= "(".$field["len"].") ";
								if($field["flags"] != "")
									$string.= $field["flags"];
								$td->add($string);
							}
							$td->add($input);
							if(preg_match("/datetime/", $field["type"]))
							{// bei einem Datums-Feld auch noch das Format angeben
								$td->add("(".$this->db->getDateFormat()." ".$this->db->getTimeFormat().")");
							}else
							if(preg_match("/date/", $field["type"]))
							{// bei einem Datums-Feld auch noch das Format angeben
								$td->add("(".$this->db->getDateFormat().")");
							}else
							if(preg_match("/time/", $field["type"]))
							{// bei einem Datums-Feld auch noch das Format angeben
								$td->add("(".$this->db->getTimeFormat().")");
							}
    				}

    				// add content (strings or HTML-Tags)
    				// whitch are added after selection of column
    				// inside STBaseTable or STDbTable
    				// with ->addContent()
    				if(isset($field["addContent"][$this->action]))
    					$td->add($field["addContent"][$this->action]);
					$tr->add($td);
					
				}

				$td= new ColumnTag(TD);
  					$td->add("&#160;&#160;");
  				$tr->add($td);	
			} // end of else-part from if( $aJoins[$name] )
				if( $this->password==$name
					and
					$rePwd<(count($this->passwordNames)-1)
					and
					$rePwd<3	)
				{// wenn ein Passwort abgefragt wird
				 // wird die Schleife ohne $x zu erh�hen durchlaufen
				 // und die Variable $rePwd wird um eines erh�ht,
				 // so lange bis die Schleife mit dem selben Spalten-Namen
				 // so oft durchlaufen wurde, wie Namen im Array passwordNames sind.
				 // Jedoch maximal 3 Durchg�nge
					$rePwd++;
				}else
				{
				 	$x++;
					$rePwd= 0;
				}

			// insert Row into form from table
			// or into inner table
			if(isset($this->asDBTable->aInnerTables[$this->innerTabPos["count"]]))
			{
				if($this->innerTabPos["pos"] == "begin")
				{
					if($this->asDBTable->aInnerTables[$this->innerTabPos["count"]]["begin"] == $columns[$name])
					{
						$bInner= true;
						$innerTable= new tableTag();
						$iTC= $this->innerTabPos["count"];
						if(	$this->asSelect[$x-1]["nextLine"]!==false &&
							$this->asDBTable->aInnerTables[$iTC]["begin"] != $this->asDBTable->aInnerTables[$iTC]["end"]	)
						{// if nextLine from column before is false,
						 // do not insert the Row
							$innerTable->add($tr);			
							if(STCheck::isDebug())
								STCheck::echoDebug("show.db.fields", "display field <b>$name</b> inside defined section");
						}
						$this->innerTabPos["pos"]= "end";
					}
				}else
				if($this->innerTabPos["pos"] == "end")
				{
					if(	isset($this->asDBTable->aInnerTables[$this->innerTabPos["count"]]["end"]) &&
						$this->asDBTable->aInnerTables[$this->innerTabPos["count"]]["end"] == $columns[$name]	)
					{
						$bInner= 3;
						$innerTable->add($tr);
						$itr= new RowTag();
							$itd= new ColumnTag(TD);
							$itr->add($itd);
							$itd= new ColumnTag(TD);
								$itd->colspan(2);
							if($this->asDBTable->aInnerTables[$this->innerTabPos["count"]]["fieldset"])
							{
								$set= new FieldsetTag();
								if(is_string($this->asDBTable->aInnerTables[$this->innerTabPos["count"]]["fieldset"]))
								{
									$leg= new LegendTag();
										$leg->add($this->asDBTable->aInnerTables[$this->innerTabPos["count"]]["fieldset"]);
										$set->add($leg);
								}
									$nulltr= new RowTag();
										$nulltd= new ColumnTag(TD);
											$nulltd->add("&#160;");
											$nulltr->add($nulltd);
										$innerTable->add($nulltr);
									$set->add($innerTable);
								$itd->add($set);
							}else
								$itd->add($innerTable);
							$itr->add($itd);
						$form->add($itr);
						$innerTable= NULL;
						$this->innerTabPos["count"]+= 1;
						$this->innerTabPos["pos"]= "begin";

					}elseif($innerTable)
					{
						if($this->asSelect[$x-1]["nextLine"]!==false)
						{// if nextLine from column before is false,
						 // do not insert the Row
							$innerTable->add($tr);
			   				// add content (strings or HTML-Tags)
			   				// whitch are added after selection of column
			   				// inside STBaseTable or STDbTable
							// or ->addBehind()
			   				if(isset($field["addBehind"][$this->action]))
			   				{
								$tr= new RowTag();
									$td= new ColumnTag(TD);
									$td->add("&#160;&#160;");
							   		$td->add($field["addBehind"][$this->action]);
						   		$tr->add($td);
						   		$innerTable->add($tr);
			   				}							
						}
					}

				}
			}
			
			if(	$bInner === false &&
				(	!isset($this->asSelect[$x-1]["nextLine"]) ||
					$this->asSelect[$x-1]["nextLine"] === true	)	)
			{// when entry nextLine from column before exist and is false,
			 // do not insert the row into displaying form				
				if(STCheck::isDebug())
					STCheck::echoDebug("show.db.fields", "display field <b>$name</b>");				
				$form->add($tr);
   				// add content (strings or HTML-Tags)
   				// whitch are added after selection of column
   				// inside STBaseTable or STDbTable
				// or ->addBehind()
   				if(isset($field["addBehind"][$this->action]))
   				{
					$tr= new RowTag();
						$td= new ColumnTag(TD);
						$td->add("&#160;&#160;");
				   		$td->add($field["addBehind"][$this->action]);
			   		$tr->add($td);
			   		$form->add($tr);
   				}
			}
			if($bInner === 3)
				$bInner= false;
		}// end of while($x<count($fields))

		$tr= new RowTag();
		$td= new ColumnTag(TD);
		$td->add("&#160;&#160;");

		$td= new ColumnTag(TD);
		$td->add(br());
		$input= new InputTag();
		$buttonValue= $this->sButtonValue;
		if(!$buttonValue)
			$buttonValue= $this->action;
		$input->value($buttonValue);
		if($needChangeBox)
		{
			$input->type("button");
			$input->onClick("javascript:DB_changeBox('make')");
		}else
			$input->type("submit");
		// 18/06/2006 alex:	do not know why submit button should be disabled
		//					when last field is disabled.
		//					so cancel source-code
		//if(isset($this->aDisabled[$field["name"]]))
		//	$input->disabled();
		$td->add($input);

		$input= new InputTag();
		$input->type("hidden");
		$input->name("STBoxes_action");
		$input->value("make");
		$td->add($input);
		if(	$this->action==STINSERT
			and
			!$previousSelectionDone
			and
			isset($this->asDBTable->aAuto_increment["session"])	)
		{
			$this->createPreviousSelection();
			$input= new InputTag();
				$input->type("hidden");
				$input->name($this->asDBTable->aAuto_increment["PK"]);
				$input->value($this->asDBTable->aAuto_increment["value"]);
				$td->add($input);
		}

		$tr->add($td);
		$form->add($tr);
		$this->add($form);

			if($needChangeBox)
			{// der submit-Button und einige PopUp's gehen nun �ber diese Funktion
				$tr= new RowTag();
					$td= new ColumnTag(TD);
						$script= new JavaScriptTag();
							$DB_changeBox= new jsFunction("DB_changeBox", "action");
						  		$DB_changeBox->add("document.STForm.STBoxes_action.value= action;");
				  				$DB_changeBox->add("document.STForm.submit();");
							$script->add($DB_changeBox);
						$td->add($script);
					$tr->add($td);
				$this->add($tr);

        /*      $this->add("<script type='text/javascript'>\n");
              $this->add("<!---\n");
				  $this->add("function DB_changeBox(action)\n");
				  $this->add("{\n");
				  $this->add("document.".$this->name.".action.value= action;\n");
				  $this->add("document.".$this->name.".submit();\n");
				  $this->add("}\n");
              $this->add("//-->\n");
              $this->add("</script>\n");*/
			}
		$this->msg->setMessageId("BOXDISPLAY");
		$changedPost= $post;
  	}
	function submitButtonValue($value)
	{
		$this->sButtonValue= $value;
	}
	function countingAllEnumns()
	{
		$space= STCheck::echoDebug("show.db.fields", "counting enums:");
		$fields= $this->getFieldArray();//hole fields from database
		$aEnums= array();
		foreach($fields as $field)
		{
			$enum= $this->countingEnums($field);
			if($enum[0] > 0)
				$aEnums[$field['name']]= $enum;
		}
		if(STCheck::isDebug("show.db.fields"))
		    st_print_r($aEnums, 2, $space);
		return $aEnums;
	}
	function getEnums($columnName)
	{
        $tableName= $this->asDBTable->getName();
        if(!isset($this->db->aFieldArrays[$tableName]))
            return array();
        foreach ($this->db->aFieldArrays[$tableName] as $value)
        {
            if( $value['name'] == $columnName &&
                isset($value['enums'])            )
            {
                return $value['enums'];
            }
        }
        return array();
	}
	function countingEnums(array $field)
	{
		if(!isset($field['enums']))
			return array(0);
		$nEnums= count($field['enums']);
		if(!preg_match("/not_null/", $field["flags"]))
			$nEnums++;
		$aRv[]= $nEnums;
		$aRv= array_merge($aRv, $field['enums']);
		return $aRv;
	}
	function columns($columns)
	{
		$this->columns= $columns;
	}
	function join($join)
	{
		$split= preg_split("/=/", $join);
		$this->join[$split[0]]= $join;
	}
    function update($onError= onErrorMessage)
	{
		return $this->execute(STUPDATE, $onError);
	}
  	function insert($onError= onErrorMessage)
	{
		return $this->execute(STINSERT, $onError);
	}
	function execute($action, $onError= onErrorMessage)
	{
		$this->defaultOnError($onError);
		$this->createMessages();
		if(	!count($this->asTable)
			and
			!$this->asDBTable	)
		{
			echo "<br><b>ERROR </b> in object STItemBox<br />";
			echo "es wurde f&uuml;r die deffinierte Box keine Tabelle gesetzt<br>";
			exit;
		}
		STCheck::echoDebug("db.statements.limit", "clear all limits inside table for action '$action'");
		$maxRow= $this->asDBTable->getMaxRowSelect();
		$firstRow= $this->asDBTable->getFirstRowSelect();
		$this->asDBTable->clearIndexSelect();
		$this->asDBTable->clearFirstRowSelect();
		if($action==STUPDATE)
		{
			$this->action= STUPDATE;
		}else
			$this->action= STINSERT;
	// alex: 06/09/2005: parsen in function table verlegt
		// alex 02/09/2005:	vor dem Erzeugen des Formulars
		//					showTypes von der gesezten Tabelle durchparsen
		//					ob ein Feld auf upload gesetzt ist,
		//					dann in uploadFields �bernehmen
		/*if($this->asDBTable)
		{
			foreach($this->asDBTable->showTypes as $column=>$showTypes)
			{
				foreach($showTypes as $extraField=>$array)
				{
					if($extraField=="upload")
						$this->uploadFields[$column]= $array;
				}
			}
		}*/
		$result= null;
		$this->box($this->join, $this->where, $result);
		$tr= new RowTag();
			$td= new ColumnTag(TD);
				$td->add($this->msg->getMessageEndScript());
			$tr->add($td);
		$this->add($tr);
				
		$message= $this->msg->getMessageId();

		$query= new STPostArray();		
		if( $query->exist("STBoxes_action") &&
			$query->getValue("STBoxes_action") == "make")
		{
			$display= false;
		}else
			$display= true;

		$oCallbackClass= new STCallbackClass($this->asDBTable, $this->aResult);
		$oCallbackClass->display= $display;
		$oCallbackClass->before= false;
		$oCallbackClass->rownum= 0;
		$oCallbackClass->MessageId= $message;
		$this->makeCallback($this->action, $oCallbackClass, $this->action, 0);
		
		STCheck::echoDebug("db.statements.limit", "set all old limits inside table from $firstRow to $maxRow");
		if(isset($maxRow))
			$this->asDBTable->setMaxRowSelect($maxRow);
		if(isset($firstRow))
			$this->asDBTable->setFirstRowSelect($firstRow);
		return $message;
	}
	function callback($columnNameAction, $callbackFunction, $action= "SAME AS columnNameAction")
	{
		if($action == "SAME AS columnNameAction")
			$action= $columnNameAction;
		STBaseBox::callback($columnNameAction, $callbackFunction, $action);
	}
	function table($table, $name= null)
	{
		STBaseBox::table($table, $name);
		if(typeof($table, "STBaseTable"))
		{
			// alex 08/09/2005:	onlyRadioButtons aus der Tabelle uebernehmen
			// alex 30/10/2013: take enumField from table as onlyRadioButtons 
			$this->enumField= $table->getSpecificEnumFields();

            // alex 08/08/2005:	f�ge auch callbacks hinzu f�r Selects
            //					die mit popupSelect() deffiniert wurden
			$this->aCallbacks= array_merge($this->aCallbacks, $table->aCallbacks);
			if(is_array($table->showTypes))
			{
				foreach($table->showTypes as $columnName=>$extraFields)
				{
					foreach($extraFields as $extra=>$value)
					{
						if($extra=="dropdown")
						{
            				$field= $table->findAliasOrColumn($columnName);
							$this->aDropDowns[]= $field["column"];
						}elseif($extra=="upload")
                		{	// alex 02/09/2005:	vor dem Erzeugen des Formulars
                			//					showTypes von der gesezten Tabelle durchparsen
                			//					ob ein Feld auf upload gesetzt ist,
                			//					dann in uploadFields �bernehmen
							$this->uploadFields[$columnName]= $value;
						}
						if($extra=="disabled")
						{
							$this->disabled($columnName, true);
						}
					}
				}
			}
			// add all password settings
			if(count($table->password))
			{
				$this->password= $table->password["column"];
				$this->pwdEncoded= $table->password["encode"];
				$this->passwordNames= $table->password["names"];
			}
		}
	}
	function box($join, $where)
	{
        global	$HTTP_POST_VARS,
				$HTTP_POST_FILES;

		$post= new STPostArray();
		$query= new STQueryString();
		$get= $query->getArrayVars();
		
		/**
		 * additional content for insert statement
		 * from foreign keys
		 * @var array $insert_content
		 */
		$insert_content= array();
		
		if( $post->exist("STBoxes_action") &&
			$post->getValue("STBoxes_action") == "make")
		{
			$display= false;
		}else
			$display= true;

		$aSetAlso= $this->asDBTable->aSetAlso;
		if( $this->action==STINSERT &&
		    $display == false  			)
		{
		    // alex 03/08/2005:	set additional all fields
		    //					which incomming by query limitation
		    //					and match with foreign keys
		    //					but be not set actually
		    //					also the foreign key have to be an "inner join"
			if(!is_array($aSetAlso))
				$aSetAlso= array();
			$fks= $this->asDBTable->getForeignKeyModification();
			foreach($fks as $table=>$fields)
			{
			    foreach($fields as $column)
			    {
					if(	!isset($aSetAlso[$column["own"]][STINSERT]) &&
						!isset($aSetAlso[$column["own"]]["All"]) &&
						$column["join"]=="inner" &&
						(   !$this->asDBTable->isSelect($column["own"]) ||
						    $this->asDBTable->isDisabled($column["own"])  )     )
					{
						if(!isset($aSetAlso[$column["own"]]))
						    $aSetAlso[$column["own"]]= array();
						if(!isset($get["stget"]['limit'][$table][$column["other"]]))
						{// own column not exist inside limitation, so select from database
						    $oTable= $this->asDBTable->getTable($table);
						    $selector= new STDbSelector($oTable);
						    $selector->select($table, $column["other"]);
						    foreach($get["stget"]['limit'][$table] as $key=>$value)
						        $selector->andWhere($table, "$key='$value'");
					        $selector->execute();
						    $value= $selector->getSingleResult();
						}else
						    $value= $get["stget"]['limit'][$table][$column["other"]];
						$insert_content[$column["own"]]= $value;
					}
			    }
			}
		}
		
		// alex 07/06/2005: setAlso take over STBaseTable
		if(is_array($aSetAlso))
			foreach($aSetAlso as $column=>$content)
			{
				$alias= $this->asDBTable->defineDocumentItemBoxName($column);
				foreach($content as $action=>$value)
				{
					// alex 01/09/2005:	action kann auch "All" sein
					//					wenn das setAlso in der Tabelle
					//					f�r alle Aktionen gesetzt wurde
					if(	(	$action==$this->action
							or
							$action=="All"			) &&								
						!isset($this->aSetAlso[$alias])		)
					{
						$this->aSetAlso[$alias]= $value;
					}
				}
			}
		///////////////////////////////////////////////////////////////
		$table= $this->asDBTable->getName();

		// 19/06/2007 alex:	the post-vars must be the second parameter
		//					from array_merge, because from the second
		//					the first will be replaced
		$post= array_merge($this->aSetAlso, $HTTP_POST_VARS);

		// 14/02/2012 alex:	for all variables geted with no content,
		// 					set variable to null
		// 					because this variable shouldn't insert
		// 					into database
		//					otherwise, when column be an integer or float
		//					it will be write an empty string into database
		//					and the database change it to the number 0
		//					by the next update of the row, the field isn't
		//					realy empty
		foreach($post as $column=>$content)
		{
			if(	$content === "" &&
				(	!isset($this->password) ||
					(	$column != $this->password &&
						$column != "re_{$this->password}" &&
						$column != "old_{$this->password}"		)	)	)
			{
				unset($post[$column]);
			}
		}
		$bError= false;
        if( isset($post["STBoxes_action"]) &&
        	$post["STBoxes_action"]=="make" 	)
        {
			// checking for two enums
  			$aEnums= $this->countingAllEnumns();
			$aShowen= array();
			if($this->asDBTable)
			{
				foreach($this->asDBTable->show as $field)
					$aShowen[$field["column"]]= true;
			}
  			foreach($aEnums as $column=>$enum)
  			{	// alex 08/11/2005: do only set
				//					if the column will be showen
  				if(	isset($enum[0]) &&
  					$enum[0] === 2 &&
					count($enum) === 3 &&
					isset($aShowen[$column]) &&
					$aShowen[$column] === true &&
  					!isset($post[$column])			)	// if the checkbox not set
				{//STCheck::alert($field['type']=="not found", "STItemBox::box()", "column $column not found inside table {$this->asDBTable->getName()}");	
					$alias= $this->asDBTable->defineDocumentItemBoxName($column);
					if(!isset($post[$alias]))
						$post[$alias]= $enum[1];// set the value to the first entry
  				}
  			}
  			
  			// if fields defined to upload
  			// load now with function loadFiles() to hard disk
            if(!$this->loadFiles($post))
				$bError= true;
			
			if(count($insert_content))
			    $showpost= array_merge($insert_content, $post);
			else
			    $showpost= $post;

			// alle callbacks welche vom Anwender gesetzt wurden durchlaufen
			$oCallbackClass= new STCallbackClass($this->asDBTable, $showpost);
			$oCallbackClass->display= $display;
			$oCallbackClass->before= true;
			$oCallbackClass->rownum= 0;
			$oCallbackClass->MessageId= "PREPARE";
			if($this->asDBTable)
			{
			    $this->asDBTable->modifyQueryLimitation();
				$oCallbackClass->where= $this->asDBTable->getWhere();
			}
			$sErrorString= $this->makeCallback($this->action, $oCallbackClass, $this->action, 0);
			if(is_bool($sErrorString))
			{
				if($this->asDBTable)
				{
					$this->asDBTable->where($oCallbackClass->where);
					$this->asDBTable->andWhere($this->where);
					$where= $this->asDBTable->getWhere();
				}else
					$where= $this->where;
			if($this->asDBTable)
				if($this->action==STUPDATE)
					Tag::alert(!($where && $where->isModified()), "STItemBox::box()",
										"no where-clausel defined for update in database");
			}else
			    $bError= true;

			if(	$this->action==STINSERT )
			{
				// if it is generate an STUserSession
				// and in the STBaseTable are be set columns
				// to create cluster for spezific actions
				// produce this
				$_instance= null;
				if(	STUserSession::sessionGenerated()
					and
					count($this->asDBTable->sAcessClusterColumn))
				{
    				$_instance= &STUserSession::instance();
                    $identification= "";
                    foreach($this->asDBTable->identification as $identifColumn)
                    {
                    	$identif= $showpost[$identifColumn["column"]];
                        $identification.= $identif." - ";
                    }
                    if($identification)
                    	$identification= substr($identification, 0, strlen($identification)-3);

    				$pkValue= $showpost[$this->asDBTable->getPkColumnName()];
    				if(!$pkValue)
    				{
    				    $table= $this->asDBTable;
  						$table->clearSelects();
  						$table->select($table->getPkColumnName());
  						$statement= $this->db->getStatement($table);
  						$pkValue= $this->db->fetch_single($statement);
    				}
					$tableName= $this->asDBTable->getDisplayName();
					//st_print_r($showpost);
   					foreach($this->asDBTable->sAcessClusterColumn as $aColumnCluster)
   					{
   						if(!$showpost[$aColumnCluster["column"]])
   						{
							$infoString= preg_replace("/@/", $identification, $aColumnCluster["info"]);

							$cluster= $showpost[$aColumnCluster["cluster"]];
   							$result= $_instance->createAccessCluster(	$aColumnCluster["parent"],
   																		$cluster,
   																		$infoString,
																		$tableName,
																		$aColumnCluster["group"]	);
   							if($result=="NOCLUSTERCREATE")
   							{
   								$bError= true;
   								$this->msg->setMessageId("NOCLUSTERCREATE@@", $cluster, $aColumnCluster["info"]);
   								break;
   							}elseif($result!="NOERROR")
								    $this->msg->setMessageId($result);
								else
								   $_instance->addDynamicCluster($this->asDBTable, $aColumnCluster["action"], $pkValue, $cluster);

							$aClusters[$aColumnCluster["column"]]= $cluster;
   							$showpost[$aColumnCluster["column"]]= $cluster;
						}
					}
				}
			}
			// check all content of fields
			if( !$bError &&
            	!$this->checkFields($showpost) )
			{
				$bError= true;
			}
			if(!$bError)
            {
                if(	isset($showpost[$this->password]) &&
    			    $this->pwdEncoded &&
					$showpost[$this->password]!==null      )
            	{
            		$showpost[$this->password]= "password('".$showpost[$this->password]."')";
            	}
            	
            	$this->asDBTable->allowQueryLimitationByOwn(true);
            	$this->asDBTable->allowFkQueryLimitation(false);
            	if($this->action==STINSERT)
				{
				    $db_case= null;
					if(	isset($this->asDBTable->aAuto_increment["session"])	)
					{
						$PK= $this->asDBTable->aAuto_increment["PK"];
						$changedValues= $this->getChangedResult($showpost);
						$db_case= new STDbUpdater($this->asDBTable);
						foreach($changedValues as $column => $value)
						    $db_case->update($column, $value);
						$where= new STDbWhere($this->db, $PK."=".$showpost[$PK]);
						$db_case->where($where);
					}else
					{
					    $columns= $this->createColumns();
					    $db_case= new STDbInserter($this->asDBTable);
					    foreach($showpost as $column => $value)
					    {
					        if(isset($value))
					        {
					            $field= $this->asDBTable->findAliasOrColumn($column);
					            if( $field['type'] == "not found" )
					            {
					                foreach($columns as $fieldName=>$columnName)
					                {
					                    $preC= preg_replace("/ /", "_", $columnName);
					                    if($preC == $column)
					                    {
					                        $field['column']= $fieldName;
					                        break;
					                    }
					                }
					            }
					            $db_case->fillColumn($field['column'], $value);
					        }
					    }
					}
            	}else
            	{
            	    $changedValues= $this->getChangedResult($showpost);
				    if(count($changedValues))
				    {
    				    $db_case= new STDbUpdater($this->asDBTable);
    				    foreach($changedValues as $column => $value)
    				        $db_case->update($column, $value);
				    }else
				    {
				        $bError= true;
				        $this->msg->setMessageId("NOUPDATEROW@", $this->asDBTable->getName());
				    }
				}

				if(!$bError)
				{
				    if(0)
				    {
    				    $statement= $db_case->getStatement();
    				    $statement= substr($statement, 0, -10);
    				    echo __FILE__.__LINE__."<br>";
    				    echo "manipulate statement<br>";
    				    echo "new statement:$statement<br>";
    				    $db_case->setStatement($statement);
				    }
				    $res= $db_case->execute($this->getOnError("SQL"));
					if($res != 0)
					{
					    $this->msg->setMessageId("SQLERROR@", $db_case->getErrorString());
            			$this->setSqlError($res);
            			$bError= true;
					}
            	}            	
				if(!$bError)
				{// use post as result from INSERT or UPDATE for some output to user
            		$this->aResult= $showpost;
            		return true;
				}
        	}
			if($bError)
			{
				// alex 04/09/2005:	ein Fehler ist aufgetreten
				//					das bereits upgeloadete File
				//					wenn vorhanden, wieder l�schen
				foreach($this->uploadFields as $upfield=>$entry)
				{
					if(is_file($post[$upfield]))
						unlink($post[$upfield]);
				}
            	// if be created clusters for access
            	// delete them by an ERROR
            	if(	isset($_instance) &&
            		typeof($_instance, "STUserSession") &&
					count($aClusters)						)
            	{
            		foreach($aClusters as $key=>$cluster)
            		{
						$_instance->deleteAccessCluster($cluster);
            		}
            	}
			}
        }
        $changedPost= NULL;
        if(count($post) > 0)
            $changedPost= $post;
  		$this->makeBox($table, $join, $where, $changedPost);
        return !$bError;

	}
	public function getResult() : array
	{
	    return $this->aResult;
	}
		private function getChangedResult(array $post_vars) : array
		{
		    $columns= $this->createColumns();
		    $result= array();
			if(isset($this->sqlResult))
			{
			    foreach ($this->sqlResult as $column => $content)
			    {
			        $alias= preg_replace("/ /", "_", $columns[$column]);
			        if(isset($post_vars[$alias]))
			        {
						if($post_vars[$alias] !== $content)
			            	$result[$column]= $post_vars[$alias];
			        }else
					{// if not set entry maybe was removed
						$result[$column]= null;//$post_vars[$alias];
					}
			    }
			}
			//----------------------------------------------------------
			if(isset($this->sqlResult))
			{
			    foreach ($post_vars as $alias => $content)
			    {					
					$column= array_search($alias, $columns);
					if($column === false)
					{// mostly the underlines are spaces
					 // not implemented one is space but an second an underline
						$_alias= preg_replace("/_/", " ", $alias);
						$column= array_search($_alias, $columns);
					}
					if($column === false)
						$column= $alias;
			        if(!isset($this->sqlResult[$column]))
			        {// column was new set from callback
			            $result[$column]= $content;
			        }
			    }
			}
			return $result;
		}			
		function getLastInsertID()
		{
			return $this->lastInsertID;
		}
		function loadFiles(&$post)
		{
			global $HTTP_POST_FILES;

			if(!count($this->uploadFields))
				return true;
			//$fields= $this->getFieldArray();//hole Felder aus Datenbank
			//st_print_r($this->uploadFields,10);
			$columns= $this->createColumns($this->columns);// erstelle Array aus Spalten-Name und Alias-Name
			$aliases= array_flip($columns);
			foreach($this->uploadFields as $alias=>$file)
			{
				$name= $aliases[$alias];
				$bOk= false;
				if(	isset($HTTP_POST_FILES[$name])
					and
					$HTTP_POST_FILES[$name]["tmp_name"]
					and
					$HTTP_POST_FILES[$name]["tmp_name"]!="none"
					and
					$HTTP_POST_FILES[$name]["tmp_name"]!=""		)
				{//echo $name.": ";st_print_r($HTTP_POST_FILES);
					$upload_file= $HTTP_POST_FILES[$name];
					if($file["type"])
					{
						$pattern= preg_replace("/\//", "\\/", $upload_file["type"]);
						if(!preg_match("/$pattern/i", $file["type"]))
						{// dieser Typ darf nicht hochgeladen werden
							$this->msg->setMessageId("WRONGUPLOADTYPE@@@", $columns[$name], $file["type"], $upload_file["type"]);
							return false;
						}
					}
					if(	$file["size"]
						and
						$upload_file["size"]>$file["size"])
					{// File ist zu gross
						$this->msg->setMessageId("TOBIGUPLOADFILE@@@", $columns[$name], $file["size"], $upload_file["size"]);
						return false;
					}
					if(	$file["width"]!=0
						or
						$file["height"]!=0)
					{
						$imageSize= getimagesize($upload_file['tmp_name']);
						if( (	$file["width"]>0 &&
							 	$file["width"]<$imageSize[0]	)
							or
							(	$file["height"]>0 &&
								$file["height"]<$imageSize[1]	)	)
						{// die Abmasse des Bildes sind zu gross
							if(	$file["width"]==0
								and
								$file["height"]<$imageSize[1]	)
							{
								$this->msg->setMessageId("WRONGIMAGEHEIGHT@@", $columns[$name], $file["height"]);
								return false;
							}
							if(	$file["height"]==0 &&
								$file["width"]<$imageSize[0]	)
							{
								$this->msg->setMessageId("WRONGIMAGEWIDTH@@", $columns[$name], $file["width"]);
								return false;
							}
							$this->msg->setMessageId("WRONGIMAGELENGTH@@@", $columns[$name], $file["width"], $file["height"]);
							return false;
						}
					}
					$fileName= $file["path"];
					if(substr($fileName, strlen($fileName)-1, 1)!="/")
						$fileName.= "/";
					$fileName.= $upload_file["name"];
					if(substr($fileName, 0, 1)=="/")
					{
						$path= $_SERVER["SCRIPT_FILENAME"];
						$path= substr($path, 0, strlen($path)-strlen($_SERVER["SCRIPT_NAME"]));
						$fileName= $path.$fileName;
					}
					$second= 2;
					preg_match("/^(.*)\.([^.].*)$/", $fileName, $preg);
					$file_name= $preg[1];
					$file_ext= $preg[2];
					while(file_exists($fileName))
					{
						if(preg_match("/^(.*)(\[)([0-9]+)\]$/", $file_name, $preg))
						{
							$second= $preg[3];
							$second++;
							$file_name= $preg[1];
						}
						$file_name.= "[$second]";
						$second++;
						$fileName= "$file_name.$file_ext";
					}
					$insert_fileName= $fileName;
					if($path)
						$insert_fileName= substr($fileName, strlen($path));
					if(is_uploaded_file($upload_file['tmp_name']))
					{
						if(move_uploaded_file($upload_file['tmp_name'], $fileName))
						{
							$bOk= true;
						}
					}
					if(	$bOk
						and
						$post["old_upload_file_".$name]	)
					{
						unlink($post["old_upload_file_".$name]);
					}
    				if(	$this->action==STUPDATE
    					and
    					$HTTP_POST_FILES[$$name]["tmp_name"]===""
						and
						$HTTP_POST_FILES[$$name]["error"]==4		)
    				{
    					$bOk= true;
    				}
                	if(!$bOk)
                	{
                		$this->msg->setMessageId("UPLOADERROR@@", $HTTP_POST_FILES[$name]["error"], $upload_file["name"]);
                		return false;
                	}
					$post[$columns[$name]]= $insert_fileName;
				}
			}
			return true;
		}
    function checkFields(&$post)
    {
        STCheck::echoDebug("show.db.fields", "check for correct input:");

		$table= $this->asDBTable;
		if(!$table)
		{
			$table= reset($this->asTable);
		}else
		    $table= $table->getName();
        $columns= $this->createColumns($this->columns);// create array from column-Name und alias-Name
        $fields= $this->getFieldArray();//take fields from database
        $aJoins= $this->getJoinArray(array(), $post);//create all content from popup-menues
        $bFieldDefineSelection= false;
        if($this->action==STUPDATE)
        {// check only the changed fields
			if($this->asDBTable)
				$oTable= &$this->asDBTable;
			else
				$oTable= new STDbTable($table, $this->db);
			if($this->where)
				$oTable->andWhere($this->where);
			$selector= new STDbSelector($oTable);
			// 02/03/2023 alex:
			//            for any selection with where statement
			//            from other table, need foreign keys
			//$selector->clearFKs();
			$statement= $selector->getStatement();
			$this->db->query($statement, $this->getOnError("SQL"));
        	$result= $this->db->fetch_row(MYSQL_ASSOC, $this->getOnError("SQL"));
        	if(STCheck::isDebug("db.statement"))
        	{
        		$space= STCheck::echoDebug("db.statement", "actual result from database for function checkFields");
        		st_print_r($result, 1, $space);
        		//$post= array_merge($result, $post);
        		//st_print_r($post);
        		//st_print_r($fields, 5);
        	}
			if($result===false)
			{
				$this->msg->setMessageId("NOUPDATEROW@", $table);
				return false;
			}
			$bFieldDefineSelection= false;
			if($bFieldDefineSelection)
			{
			    echo "<b>begin definition</b> on line ".(__LINE__-3)."<br />";
			    echo "   by file ".__FILE__."<br />";
			    echo "database select: <b>(\$result)</b><br />";
			    st_print_r($result, 1, 10);
			    echo "definde columns inside table: <b>(->table->show)</b><br />";
			    st_print_r($this->asDBTable->show, 3, 10);
			    echo "exist fields: <b>(\$fields)</b><br />";
			    st_print_r($fields,3, 10);
			    echo "existing joins: <b>(\$aJoins)</b><br />";
			    st_print_r($aJoins, 5, 10);
			    echo "created columns: <b>(\$columns)</b><br />";
			    st_print_r($columns  ,3, 10);
			    echo "inncomming post variable: <b>(\$post)</b><br />" ;
			    st_print_r($post, 5, 10);
			}
			$newResult= array();
			// retype alias columns into columns from database
			foreach($result as $column=>$value)
			{
			    $field= $selector->searchByAlias($column);
			    if(isset($field))
			        $newResult[$field["column"]]= $value;
			}
			$this->sqlResult= $newResult;
			$this->setSqlError($result);
			$newFields= array();
			// add fields which are defined inside database
			// but have no values inside incomming post variable
			// fields are all columns from table inside database
        	foreach($fields as $key => $field)
            {
                $f= $this->asDBTable->findColumnOrAlias($field['name']);
                $alias= preg_replace("/[ \t]/", "_", $f['alias']);
                if($bFieldDefineSelection)
                {
                    $space= STCheck::write("field: ".$field["name"].":$alias");
            	    st_print_r($f, 1, $space);
                }
				
				if(!isset($post[$alias]))
				{
					// if alias not inside post
					// maybe column was disabled
					// and take content from db result
					if(isset($this->aDisabled[$alias]))
					{
						foreach ($this->aDisabled[$alias] as $action=>$bSet)
						{
							if( $action == 0 || // 0 means action is STINSERT and STUPDATE but no STLIST
								$action == $this->action     )
							{
								$post[$alias]= $result[$alias];
								break;
							}
						}
					}
				}
				if(	isset($post[$alias]))
				{
				    if($bFieldDefineSelection)
				        STCheck::write("exist by incomming POST variable");
					if(	isset($result[$f['alias']]) )
					{
					    if($bFieldDefineSelection)
					        STCheck::write("and also inside database select");
						if(	(	$post[$alias]!=$result[$f['alias']] &&
								$f['column']!=$this->password					    )
							or
							(	$f['alias']==$this->password &&
								isset($post[$alias])							)	)
						{
							$newFields[]= $field;
						}
					}else
					{
						$newFields[]= $field;
						if($bFieldDefineSelection)
						    STCheck::write("but not inside database select");
					}
				}elseif(isset($result[$f['alias']]))
				{
				    if($bFieldDefineSelection)
				        STCheck::write("exist only inside database");
					if( $f['alias'] != $this->password ||
					    !$this->asDBTable->hasDefinedFlag($this->password, $this->action, "null") /* <- optional */ )
					{
					    if(isset($aJoins[$f['column']]))
					    {
					        if($bFieldDefineSelection)
					        {
					            STCheck::write("field is disabled and a foreign key join to an other table");
					            STCheck::write("a disabled selection box do not send content over POST");
					            STCheck::write("so datbase should'nt removed");
					        }
					        $post[$f['column']]= $result[$f['alias']];
					        
					    }else
					    {   
					        if($bFieldDefineSelection)
					            STCheck::write("field is disabled, so make no changes inside database");
    					    //$post[$f['column']]= "";
					    }
    					$newFields[]= $field;
					}else
					{

						if($bFieldDefineSelection)
					    	STCheck::write("field is a password, so input from user is optional");
					}
				}else if($bFieldDefineSelection)
				    STCheck::write("do not exist inside database select and not incomming post");
				if($bFieldDefineSelection)
				    echo "<br /><br />";
            }
			$fields= $newFields;
        }
        if($bFieldDefineSelection)
        {
            echo "<hr>";
            echo "<b>check for fields:</b><br />";
        }
        foreach($fields as $field)
        {
            $name= $field["name"];
        	if(isset($columns[$name]))
                $columnName= $columns[$name];
            else
                $columnName= $name;
            //$postColumn= preg_replace("/[ \t]/", $this->sReplaceSpaceSign, $columnName);
			$postColumn= $this->asDBTable->defineDocumentItemBoxName($name);
            if( STCheck::isDebug() &&
                isset($post[$postColumn]) &&
                (   STCheck::isDebug("show.db.fields") ||
                    $bFieldDefineSelection                  )   )
            {
                $msg= "  check field <b>$name</b>";
                if(STCheck::isDebug("show.db.fields"))
                    $space= STCheck::echoDebug("show.db.fields", $msg);
                else
                    $space= STCheck::write($msg);
                $space+= 40;
                st_print_r($field, 2, $space);
                STCheck::echoSpace($space);
                echo "<b>content:</b> \"".$post[$postColumn]."\"<br />";
            }
            $ch= 0;
            if(isset($post[$postColumn]))
            {
                $post[$postColumn]= str_replace("'", "\\'", $post[$postColumn], $ch);
                if( $bFieldDefineSelection &&
                    $ch > 0                     )
                {
                    STCheck::echoSpace($space);
                    echo "<b>changed to:</b>\"".$post[$postColumn]."\"<br>";
                }
            }
			$enum= 0;
			if(preg_match("/enum/", $field["flags"]))
			{
				$aEnum= $this->countingEnums($field);
				$enum= $aEnum[0];
			}
			if(	$field["type"]=="date"
				and
				isset($post[$postColumn])		)
			{
				if($post[$postColumn]==$this->db->getNullDate())
					$post[$postColumn]= null;
				else
				{
					if(	$post[$postColumn]!="now()"
						and
						$post[$postColumn]!="sysdate()"	)
					{
    					$result= $this->db->makeSqlDateFormat(trim($post[$postColumn]));
    					if(!$result)
    					{
    					    $this->msg->setMessageId("WRONGDATEFORMAT@@", $columnName, $this->db->getDateFormat());
    						return false;
    					}
    					$post[$postColumn]= $result;
					}
				}
			}

            if( preg_match("/not_null/", $field["flags"])
                &&
                !preg_match("/auto_increment/", $field["flags"])
				&&
				$enum!=2	)
            {
				// alex 08/06/2005:	the compairson to null or "" (null string)
				//					have to be checkd with 3 equal to signs
				//					because the zero number should be allowed
                if( (   !isset($post[$postColumn]) ||
                        $post[$postColumn]===null ||
                        $post[$postColumn]===""	      ) &&
                    (   !isset($columns[$field['name']]) || // if not set inside $columns, cannot be set inside ->aDisabled
                        !isset($this->aDisabled[$columns[$field['name']]]) ||
                        $this->aDisabled[$columns[$field['name']]][/*rownr*/0] == false ) &&
                    (   $this->action == STINSERT ||  // if action is STUPDATE and it be a password field
                        $this->action == STDELETE ||  // field can be an null string for no update
                        $this->password != $name  ||  // elsewhere password repetition be defined
                        isset($post['re_$name'])        )   )
                { 
					$this->msg->setMessageId("COLUMNNOTFILLED@", $columnName);
                    return false;
                }
            }
			if(isset($post[$postColumn]))
			{
				if(	$post[$postColumn] !== ""
					&&
					$field["type"] == "int"
					&&
					!preg_match("/auto_increment/", $field["flags"])	)
				{
					if(	!is_numeric(trim($post[$postColumn]))
						||
						preg_match("/[.]/", $post[$postColumn])	)
					{
						$this->msg->setMessageId("NOINTEGER@", $columnName);
						return false;
					}
				}elseif($post[$postColumn] !== ""
						&&
						$field["type"] == "real")
				{
					if(!is_numeric(trim($post[$postColumn])))
					{
						$this->msg->setMessageId("NOFLOAT@", $columnName);
						return false;
					}
				}elseif(
						$post[$postColumn] !== ""
						&&
						$field["type"] == "string")
				{
					if(strlen($post[$postColumn]) > $field["len"])
					{
						$this->msg->setMessageId("TOLONGSTRING@@", $columnName, $field["len"]);
						return false;
					}
				}
				if( (	preg_match("/primary_key/", $field["flags"])
						or
						preg_match("/unique_key/", $field["flags"])	)
					and
					!preg_match("/auto_increment/", $field["flags"])		)
				{
					$value= $post[$postColumn];
					if(is_string($value))
						$value= "'$value'";
					$selector= new STDbSelector($this->asDBTable);
					$selector->select($this->asDBTable->getName(), $field['name']);
					$selector->where("$name=$value");
					$selector->execute();
					if(!$selector->getSingleResult())
						$this->setSqlError(null);
					else
					{//echo $postColumn."<br />";
						$this->msg->setMessageId("COLUMNVALUEEXIST@", $columnName);
						return false;
					}
				}
			}
			// alex	07/04/2005:	ereg auf preg_match ge�ndert
			//					und wenn nach dem Wort 'multiple_key'
			//					keine Spalte gefunden wird ist es die aktuelle,
			//					sowie var $aFounded gel�scht (wird nirgends gebraucht)
			if( preg_match("/(multiple_key)\(([^()]*)\)?/", $field["flags"], $ereg))
			{//echo $field["flags"]."<br />";st_print_r($ereg);
				//$aFounded[]= $ereg[1];
				if(!trim($ereg[2]))
					$ereg[2]= $field["name"];
				$aNames= preg_split("/ /", $ereg[2]);
				$where= "";
				foreach($aNames as $unique)
				{
					if($where)
						$where.= " and ";
					$value= $post[$unique];
					if(is_string($value))
						$value= "'$value'";
					$where.= "$unique=$value";
				}
				$statement= "select $name from $table where $where";
				if(!$this->db->fetch_single($statement))
					$this->setSqlError(null);
				else
				{
					$names= array();
					$values= array();
					foreach($aNames as $unique)
					{
						$column= $columns[$unique];
						if($column)
						{
							$names[]= $column;
							$value= $post[$unique];
							if(is_string($value))
								$value= "'$value'";
							$values[]= $value;
						}
					}
					if(count($names)==1)
					{
						$this->msg->setMessageId("COLUMNVALUEEXIST@", $columnName);
						return false;
					}
					$error= "";
					for($n= 0; $n<(count($names)-1); $n++)
					{
						if($error)
							$error.= ", ";
						$error.= $names[$n]."(".$values[$n].")";
					}
					$lastNr= count($names)-1;
					$this->msg->setMessageId("COMBICOLUMNVALUEEXIST@@", $error, $names[$lastNr]."(".$values[$lastNr].")");
					return false;
				}
			}
			if($name==$this->password)
			{// if the field is an password
				// should be made an check whether new password and repitition be the same
				// and also whether the old password was inserted
				// only when action is STUPDATE and the array of passwordNames are three 
				// are three
				if(	$this->action==STUPDATE
					and
					count($this->passwordNames)>=3	)
				{
					showLine();
					st_print_r($this->asDBTable->oWhere,5);
					$oTable= $this->asDBTable;
					$oTable->clearSelects();
					$oTable->count();
					$oTable->andWhere($name."=password('".$post["old_".$name]."')");
					$statement= $this->db->getStatement($this->asDBTable);
					//echo $statement;exit;
					if(!$this->db->fetch_single($statement))
					{
						$this->setSqlError(null);
						$this->msg->setMessageId("WRONGOLDPASSWORD");
						return false;
					}
				}
				if(count($this->passwordNames) >= 2)
				{
					if( !isset($post["re_".$name]) ||
						$post[$postColumn] != $post["re_".$name]    )
					{// if password repetition not be set, password also a null string
						// and for STUPDATE no entry needed
						$this->msg->setMessageId("PASSWORDNOTSAME");
						return false;
					}
				}
			}
			if(isset($post[$postColumn]))
				$newPost[$name]= $post[$postColumn];
        }
        //$post= $newPost;
		//echo __file__.__line__."<br>";
		//echo "end result of checking files to update inside database:"
		//st_print_r($post);
        return true;
    }
		function preSelect($columnName, $value)
		{
			$this->aSetAlso[$columnName]= $value;
		}
		function setAlso($columnName, $value)
		{
			$this->setAlso[$columnName]= $value;
		}
		function disabled($columnName, $aEnums= null)
		{
			$field= $this->asDBTable->findAliasOrColumn($columnName);
			if($aEnums)
			{
				if(!is_array($aEnums))
					$aEnums= array($aEnums);
					if( isset($this->aDisabled[$field["alias"]]) &&
					    count($this->aDisabled[$field["alias"]])      )
				{
				    showLine();
					$this->aDisabled[$field["alias"]]= array_merge($this->aDisabled[$field["alias"]], $aEnums);
					return;
				}
			}else
				$aEnums= true;
			$this->aDisabled[$field["alias"]]= $aEnums;
		}
/*		function onOKMakeScript($script)
		{
			$this->OKScript= $script;
		}
		function onOKGotoUrl($url)
		{
			$script= $this->OKScript;
			if(!$script)
				$script= getJavaScriptTag();
			if(Tag::isDebug())
			{
				$string= "<h3>process was OK -&gt; goto Url:<a href='$url'>".$url."</a></h3>";
				$script->add("document.write(\"$string\")");
			}else
				$script->add("self.location.href='".$url."';");
			$this->OKScript= $script;
		}*/
	    function delete($onError= onErrorMessage)
		{
			$this->createMessages();
			$this->defaultOnError($onError);
			$this->asDBTable->modifyQueryLimitation();
			$del= new STDbDeleter($this->asDBTable);
			if( isset($this->where) &&
			    (   !is_string($this->where) ||
			        trim($this->where) !== "")           )
			{
			    $del->where($this->where);
			}

			$sqlContent= array();
			$oCallbackClass= new STCallbackClass($this->asDBTable, $sqlContent);
			$oCallbackClass->display= false;
			$oCallbackClass->before= true;
			$oCallbackClass->MessageId= "PREPARE";
			$error= $this->makeCallback(STDELETE, $oCallbackClass, STDELETE, 0);
			//echo "ErrorString:";st_print_r($error);
			//echo "callbackResult:";st_print_r($oCallbackClass->sqlResult);
			
			if(!$error)
			{
			    $where= $oCallbackClass->getWhere();
			    if(isset($where))
			        $this->where($where);
			    $error= $del->execute();
			}
			if($error !== 0)
			{
			    // delete all uploadet Files, if exist
			    if(count($this->uploadFields))
			    {
			        foreach($this->uploadFields as $column=>$content)
			        {
			            if(	$result[0][$column]
			                and
			                file_exists($result[0][$column])
			                and
			                !isset($oCallbackClass->aUnlink[$column])	)
			            {
			                unlink($result[0][$column]);
			            }
			        }
			    }
			    if($error == "NODELETE_FK")
			    {
			        $fks= $del->getFkLinkTables();
			        $last= count($fks)-1;
			        $str= "";
			        foreach($fks as $nr=>$table)
			        {
			            if($nr>0 && $nr == $last)
			                $str.= " ".$this->msg->getMessageContent("AND")." ";
			            $str.= "'$table'";
			            if($nr != $last)
			                $str.= ", ";
			        }
			        $this->msg->setMessageId("NODELETE_FK@", $str);
			    }else
			        $this->msg->setMessageId("SQLERROR@", $del->getErrorString());
			    
			        
				$tr= new RowTag();
					$td= new ColumnTag(TD);
						$td->add($this->msg->getMessageEndScript());
					$tr->add($td);
            	$this->add($tr);
				$oCallbackClass->before= false;
				$oCallbackClass->MessageId= $this->msg->getMessageId();
				$error= $this->makeCallback(STDELETE, $oCallbackClass, STDELETE, 0);
				return $this->msg->getMessageId();
			}

			$oCallbackClass->before= false;
			$oCallbackClass->MessageId= $this->msg->getMessageId();
			$error= $this->makeCallback(STDELETE, $oCallbackClass, STDELETE, 0);
			
			$tr= new RowTag();
				$td= new ColumnTag(TD);
					$td->add($this->msg->getMessageEndScript());
				$tr->add($td);
           	$this->add($tr);
			return $this->msg->getMessageId();
		}
}

?>