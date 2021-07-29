<?php

require_once($php_html_description);
require_once($_stdbtabledescriptions);
require_once($_stdbinserter);

  /**
   *  gibt ein verkn�pfungs-Tag auf eine Cascade Sylesheet aus
   *
	 *  @param: $FileName	Cascade Sylesheet-Datei mit Pfadangabe
	 *  @param: $title	Titel des Stylesheets, muss nicht angegeben werden
   *
	 *  @Autor: Alexander Kolli
   */
	 	function getCssLink($FileName, $title= null)
		{
			return STQueryString::getCssLink($FileName, $title);
		}
  /**
   *  gibt ein JavaScript-Statement im HTML, innerhalb des Script-Tags, aus
   *
	 *  @param: $src	der Dateiname einer javascript-Datei, <br>
	 *				wird dieser nicht angegeben, kann auch bei der Datei<br>
	 *				welche man erh&auml;lt mit -&gt;add -&gt; javascript eingef&uuml;gt werden
   *
	 *  @Autor: Alexander Kolli
   */
		function getJavaScriptTag($src= null)
		{
			return STQueryString::getJavaScriptTag($src);
		}

// --------------------------------------------------------------------------------------------------
// --------------------------------------------------------------------------------------------------
// ------------------  Ausf�hrungs-Klasse  ----------------------------------------------------------

class STQueryString
{
		var $param_vars;
		var $aNoSth;

		function __construct($param_vars= null)
		{
			global	$HTTP_GET_VARS;

			STCheck::paramCheck($param_vars, 1, "string", "array", "null");
			if(is_string($param_vars))
			{// alex 03/05/2005:	es darf nun bei einem String
			 //					auch die Adresse selbst mitgegeben werden
				if(preg_match("/\?/", $param_vars))
				{
					$param_vars= $this->createParamString($param_vars);
				}
			}
			$this->param_vars= $param_vars;
			if($this->param_vars===null)
				$this->resetParams();
		}
		function setQueryTable($table, $nrColumn, $pathColumn)
		{
			global	$global_selftables_query_table;

			$table->clearSelects();
			$table->select($nrColumn);
			$table->select($pathColumn);
			$global_selftables_query_table["table"]= $table;
			$global_selftables_query_table["nrColumn"]= $nrColumn;
			$global_selftables_query_table["pathColumn"]=  $pathColumn;
		}
		function isSetQueryTable()
		{
			global	$global_selftables_query_table;

			if(isset($global_selftables_query_table["table"]))
				return true;
			return false;
		}
		function make($type, $WithOrWithout)
		{
			if($type==STINSERT)
				$this->insert($WithOrWithout);
			elseif($type==STUPDATE)
				$this->update($WithOrWithout);
			elseif($type==STDELETE)
				$this->delete($WithOrWithout);
		}
		function setMaxQueryLength($nLength)
		{
			global	$_st_max_query_length;

			$_st_max_query_length= $nLength;
		}
		function setMaxDebugQueryLength($nLength)
		{
			global	$_st_max_debug_query_length;

			$_st_max_debug_query_length= $nLength;
		}
		function allowQueryDebugging($eclipse= false)
		{//echo "function allowQueryDebugging()<br />";
			global	$HTTP_GET_VARS;

        	STQueryString::noSth("debug");
        	if($eclipse)
        		STQueryString::noSth("DBGSESSID");
        	if(isset($HTTP_GET_VARS["debug"]))
        	{
				global	$_st_max_debug_query_length;

				STQueryString::setMaxQueryLength($_st_max_debug_query_length);
        		$debug= $HTTP_GET_VARS["debug"];
				//st_print_r($debug);
        		if(is_array($debug))
        		{
					$bSet= false;
        			foreach($debug as $entry)
        			{
        				STCheck::debug($entry);
						$bSet= true;
        			}
					if(!$bSet)
						STCheck::debug(true);
        		}else
        		{
        			if(!$debug)
        				$debug= true;
        			STCheck::debug($debug);
        		}
        		//Tag::debug("db.statement");
        		//Tag::debug("searchBoxResult");
        	}
		}
		function getUrlVars()
		{
			return $this->getStringVars(/*encode*/true);
		}
		function getStringVars($bEncode= false)
		{
			global	$global_selftables_query_table,
					$global_selftables_do_not_allow_sth,
					$_st_max_query_length;

			$param_vars= $this->param_vars;
			if($_st_max_query_length>0)
				$sRv= $this->createParamString($param_vars, $bEncode);
			else
				$sRv= "create always";

			if(	isset($param_vars) &&
				isset($global_selftables_query_table["table"]) &&
				strlen($sRv) > $_st_max_query_length				)
			{
    			// extract all variables which are set with noSth
    			if(	is_array($this->aNoSth) &&
    				is_array($global_selftables_do_not_allow_sth)	)
    			{
    				$aNoSth= array_merge($this->aNoSth, $global_selftables_do_not_allow_sth);
    				
    			}elseif(is_array($this->aNoSth))
    				$aNoSth= $this->aNoSth;
    			elseif(is_array($global_selftables_do_not_allow_sth))
    				$aNoSth= $global_selftables_do_not_allow_sth;
    			if(isset($aNoSth))
    			{
    				$param= new STQueryString($param_vars);
    				foreach($aNoSth as $queryString)
    				{
    					$param->delete($queryString);
    				}
    				$param_vars= $param->getArrayVars();
    			}
    			$sRv= $this->createParamString($param_vars, $bEncode);
				$selector= new OSTDbSelector($global_selftables_query_table["table"]);
				$selector->modifyForeignKey(false);
				$selector->where($global_selftables_query_table["pathColumn"]."='".$sRv."'");
				$selector->execute();
				$res= $selector->getRowResult();
				if($res)
				{
					$id= $res[0];
				}else
				{
					$inserter= new STDbInserter($global_selftables_query_table["table"]);
					$inserter->fillColumn($global_selftables_query_table["pathColumn"], $sRv);
					$inserter->execute();
					$id= $global_selftables_query_table["table"]->db->getLastInsertID();
				}
				if($id)
				{
					$param= array();
					$param["stget"]["nr"]= $id;
    				foreach($aNoSth as $queryString)
    				{
    					
						$value= $this->getArrayVars($queryString);
						if($value!==false)
						{
    						$null= new STQueryString(array());
    						$null->insert($queryString."=");
    						$query= $null->getArrayVars();
    						$key= key($query);	
    						$queryPos= &$query;
    						while(is_array($queryPos[$key]))
    						{
    							$queryPos2= &$queryPos[$key];
    							unset($queryPos);
    							$queryPos= &$queryPos2;
    							unset($queryPos2);
    							$key= key($queryPos);
    						}
    						$queryPos[$key]= $value;
							unset($queryPos);
    						$this->check_arrayValue_byMerge($param, $query);
						}
    				}
					$sRv= $this->createParamString($param, $bEncode);
				}
			}elseif($_st_max_query_length<1)
			{
				$sRv= $this->createParamString($param_vars, $bEncode);
			}
			return $sRv;
		}
		/**
		 * wether query parameter shouldn't calculated
		 * inside stget number from database.<br />
		 * Setting globaly for all STQueryString objects.
		 * 
		 * @param string $queryString query parameter
		 */
		function globaly_noStgetNr($queryString)
		{
			global	$global_selftables_do_not_allow_sth;

			$global_selftables_do_not_allow_sth[]= $queryString;
		}
		/**
		 * wether query parameter shouldn't calculated
		 * inside stget number from database
		 * 
		 * @param string $queryString query parameter
		 */
		function noStgetNr($queryString)
		{
			$this->aNoSth[]= $queryString;
		}
		function getActTableName()
		{
			$sRv= "";
			if(isset($this->param_vars["stget"]["table"]))
				$sRv= $this->param_vars["stget"]["table"];
			return $sRv;
		}
		function getArrayVars($var= null/*, ...*/)
		{
			$param_vars= $this->param_vars;
			$args= func_get_args();
			if(	isset($args) &&
				is_array($args)	&&
				count($args) > 0	)
			{
				if(preg_match("/[\[\]]/", $args[0], $preg))
				{
					STCheck::warning(isset($args[1]), "STQueryString::getArrayVars()", "if first parameter is 'variable[variable]' function can not have more than one parameter");
					$args= preg_split("/[\[\]]/", $args[0]);
				}
				foreach($args as $arg)
				{
					if(	isset($arg) &&
						$arg !== "" &&
						is_array($param_vars)		)
					{
						if(array_key_exists($arg, $param_vars))
						{
							$param_vars= $param_vars[$arg];
						}else
						{
							$param_vars= false;
							break;
						}
					}
				}
			}elseif($param_vars===null)
				$param_vars= false;
			return $param_vars;
		}
		function insert($paramValue, $bAddLink= false)
		{
			$param_vars= &$this->param_vars;
			$array= $this->splitParamValue($paramValue);
			$update= $this->updateA($array, $param_vars);
			while($update)
			{
				if(!isset($param_vars["stget"]["older"]))
					$param_vars["stget"]["older"]= array();
				$param_vars= &$param_vars["stget"]["older"];
				$update= $this->updateA($update, $param_vars);
			}
		}
		/**
		 * check whether an parameter be defined
		 * in the query url
		 * 
		 * @param string $param for which parameter search to check 
		 * @param mixed  $value check also whether exist parameter has this value 
		 */
		function defined($param, $value= NULL)
		{
			$array= $this->splitParamValue($param);
			$param_vars= $this->param_vars;
			foreach($array["params"] as $param)
			{
				if(!isset($param_vars[$param]))
					return false;
				$param_vars= $param_vars[$param];
			}
			if(	isset($value) &&
				$param_vars != $value	)
			{
				return false;
			}
			return true;
		}
		private function getArrayValue(/*array*/$param_array, /*array*/$aQuery)
		{
			foreach($param_array as $last_parameter)
			{
				if(!isset($aQuery[$last_parameter]))
					return null;
				$aQuery= $aQuery[$last_parameter];
			}
			return $aQuery;
		}
		private function takeDown(/*array*/$param_array, /*array*/&$aQuery)
		{
			$newValue= null;
			$currentValue= $this->getArrayValue($param_array, $aQuery);
			
			if($currentValue === null)
				return null;// parameters are not set
			
			// current parameter array exist inside query
			if(isset($aQuery["stget"]["older"]))
			{
				$newValue= $this->takeDown($param_array, $aQuery["stget"]["older"]);
				if($newValue !== null)
				{
					if(	is_array($aQuery["stget"]["older"]) &&
						empty($aQuery["stget"]["older"])		)
					{
						$this->deleteA(array("stget", "older"), $aQuery);
					}
					$updateValue= $this->getArrayValue($param_array, $newValue);
					$this->updateA(array("params"=>$param_array, "value"=>$updateValue, $aQuery), $aQuery);
					$newValue= $this->createParameterArray($param_array, $currentValue);
				}
				return $newValue;
			}
			$newQuery= $this->createParameterArray($param_array, $currentValue);
			$this->deleteA($param_array, $aQuery);
			return $newQuery;
		}
		private function createParameterArray($param_array, $value)
		{
			$n= count($param_array);
			$c= 0;
			$aRv= array();
			$new= &$aRv;
			foreach($param_array as $parameter)
			{
				$new[$parameter]= array();
				if($n != $c)
					$new= &$new[$parameter];
				++$c;
			}
			$new= $value;
			return $aRv;	
			
		}
		public function delete($param)
		{
			$array= $this->splitParamValue($param);
			if($this->takeDown($array["params"], $this->param_vars) === null)
				$this->deleteA($array["params"], $this->param_vars);
		}
		private function deleteA($param_array, /*array*/&$aQuery)
		{
			$firstVar= reset($param_array);
			$key= key($param_array);
			$nextQueryEntry= $aQuery;

			if(!isset($aQuery[$firstVar]))
				return;
			if(count($param_array) === 1)
			{
				unset($aQuery[$firstVar]);
				return;
			}
			unset($param_array[$key]);
			$this->deleteA($param_array, $aQuery[$firstVar]);
			if(	is_array($aQuery[$firstVar]) &&
				empty($aQuery[$firstVar])			)
			{
				unset($aQuery[$firstVar]);
			}
		}
		public function update($paramValue)
		{
			$array= $this->splitParamValue($paramValue);
			$this->updateA($array, $this->param_vars);
		}
		private function updateA($array, &$param_vars)
		{
			$params= $array["params"];
			$value= $array["value"];
			$pos= &$param_vars;
			$count= count($params);
			$bUpdate= false;
			for($c= 0; $c<$count; $c++)
			{
				if(!isset($pos[$params[$c]]))
				{
					$pos[$params[$c]]= array();
					$pos= &$pos[$params[$c]];
					$bUpdate= false;
				}else
				{
					if(is_array($pos[$params[$c]]))
					{
						$array["value"]= $pos[$params[$c]];
						$pos= &$pos[$params[$c]];
						$bUpdate= true;
					}else
					{
						//$array[]= $params[$c];
						//$array2= array();
						//$array2["params"]= $array;
						$array["value"]= $pos[$params[$c]];
						//$array= $array2;
						$pos= &$pos[$params[$c]];
						$pos= array();
						$bUpdate= true;
					}
				}
			}
			$pos= $value;
			if($bUpdate)
				return $array;
			return null;
		}
		function deleteOlderByCase($nLevel)
		{
			if($nLevel===null)
				return;
			$aktContainer= STBaseContainer::getContainer();
			while(  $aktContainer->getContainerLevel()!==$nLevel
			        and
					isset($this->param_vars["stget"]["older"]["container"])		)
			{
			    $this->delete("stget[table]");
			    $this->delete("stget[container]");
			    $this->delete("stget[action]");
				if(isset($this->param_vars["stget"]["link"]["from"]))
				{
					if(is_array($this->param_vars["stget"]["link"]["from"]))
					{
				    	foreach($this->param_vars["stget"]["link"]["from"] as $from)
						{
				    	    foreach($from as $table=>$column)
							{
						        $this->delete("stget[".$table."][".$column."]");
							}
						}
					}
					$this->delete("stget[link][from]");
				}
				$containerName= &$this->param_vars["stget"]["container"];
				$aktContainer= STBaseContainer::getContainer($containerName);
			}
		}
		function getValue($valueName)
		{
			if(isset($this->param_vars[$valueName]))
				return $this->param_vars[$valueName];
			return NULL;
		}
		function getColumns($tableName= null)
		{
			$aRv= NULL;
			if(!$tableName)
			{
				if(isset($this->param_vars["stget"]))
				{
					$aRv= $this->param_vars["stget"];
					foreach($aRv as $table=>$content)
					{
						if(preg_match("/^(table|action|container|link|older)$/", $table))
							unset($aRv[$table]);
					}
				}
				
			}else if(isset($this->param_vars["stget"][$tableName]))
				$aRv= $this->param_vars["stget"][$tableName];
			return $aRv;
		}
		function getLimitation($tableName)
		{
			STCheck::paramCheck($tableName, 1, "string");

			$description= &STDbTableDescriptions::instance();
			$tableName= $description->getTableName($tableName);
			return $this->param_vars["stget"][$tableName];
		}
		function insertColumn($table, $column, $value)
		{
			$this->insert("stget[$table][$column]=$value", true);
		}
		function updateColumn($table, $column, $value)
		{
			$this->update("stget[$table][$column]=$value", true);
		}
		function deleteColumn($table, $column, $value)
		{
			$this->delete("stget[$table][$column]");
		}
		function splitParamValue($paramValue)
		{
			$split= preg_split("/=/", $paramValue);
			$param= $split[0];
			$value= "";
			if(count($split) > 1)
				$value= $split[1];
			$split= preg_split("/[".preg_quote("[]")."]/", $param);
			$array= $value;
			$count= count($split);
			$array= array();
			foreach($split as $content)
			{
				if($content!=="")
					$array[]= $content;
			}
			$aRv= array();
			$aRv["params"]= $array;
			$aRv["value"]= $value;
			return $aRv;
		}
		function createArray($paramValue, &$aParams)
		{
			$params= preg_split("/\?/", $paramValue);
			if(count($params)==1)
				$params= $params[0];
			else
				$params= $params[1];
			$params= preg_split("/&/", $params);
			foreach($params as $param)
			{
				if($param)
					STQueryString::createArrayA($param, $aParams);
			}

		}
		function createArrayA($paramValue, &$aParams)
		{
			$array= STQueryString::splitParamValue($paramValue);
			$split= $array["params"];
			$value= $array["value"];
			$pos= &$aParams;
			$count= count($split);
			foreach($split as $key=>$param)
			{
				if(!isset($pos[$param]))
				{
					if($count!=($key+1))
					{
						$pos[$param]= array();
					}else
						$pos[$param]= $value;
				}
				$pos= &$pos[$param];
			}
		}
		function createParamString($param_vars, $bEncode)
		{
			if(!$param_vars)
				return "";
			$string= "";
			foreach($param_vars as $key=>$value) //"?wer=du&old[d]=23&old[d][v]=1"
			{
				if($string)
					$string.= "&";
				$firstString= $key;
				if(is_array($value))
				{
					$string.= $this->createArrayString($firstString, $value);
				}else
				{
					$string.= $firstString."=";
					if($bEncode)
						$string.= urlencode($value);
					else
						$string.= $value;
				}
			}
			return "?$string";
		}
		function createArrayString($key, $paramArray)
		{
			$sRv= "";
			Tag::echoDebug("stget.createParamString.Array", $key);
			foreach($paramArray as $pKey=>$pValue)
			{
				if($sRv)
					$sRv.= "&";
				$firstString= $key."[".$pKey."]";
				if(is_array($pValue))
					$sRv.= $this->createArrayString($firstString, $pValue);
				else
				{
					$sRv.= $firstString."=".$pValue;
					Tag::echoDebug("stget.createParamString.Array", $sRv);
				}
			}
			return $sRv;
		}
		function check_arrayValue_byMerge(&$dbVars, $getVars)
		{
			foreach($getVars as $key=>$value)
			{
				if(array_key_exists($key, $dbVars))
				{
					if(	is_array($dbVars[$key])
						and
						is_array($getVars[$key])	)
					{
						$this->check_arrayValue_byMerge($dbVars[$key], $getVars[$key]);
					}elseif(!is_array($dbVars[$key]))
					{// in dbVars is no array, so getVars alwas right
						$dbVars[$key]= $value;
					}//otherwise value in dbVars is ok
				}else
					$dbVars[$key]= $value;
			}
		}
		function resetParams()
		{
			global	$HTTP_GET_VARS,
					$HTTP_COOKIE_VARS,
					$global_selftables_query_table;

			if(	isset($global_selftables_query_table["table"]) &&
				isset($HTTP_GET_VARS["stget"]["nr"])				)
			{
				$nr= $HTTP_GET_VARS["stget"]["nr"];
				$selector= new OSTDbSelector($global_selftables_query_table["table"]);
				// by modify foreign key STQueryString is also needed
				// and so runs in an loop
				$selector->modifyForeignKey(false);
				$selector->where($global_selftables_query_table["nrColumn"]."=".$nr);
				$selector->execute();
				$res= $selector->getRowResult();
				if($res)
				{
					$param_vars= array();
					$this->createArray($res[1], $param_vars);

					// can not use array_merge
					// because if an value in param_vars is an array
					// and in HTTP_GET_VARS isn't,
					// the array-value will be delete
					$this->check_arrayValue_byMerge($param_vars, $HTTP_GET_VARS);
					$HTTP_GET_VARS= $param_vars;

				}else
					STCheck::warning(1, "STQueryString::resetParams()", "stget number ".$nr." not be set in table STQuery -> maybe table content be deleted before");
				unset($HTTP_GET_VARS["stget"]["nr"]);
			}
			$session_name= session_name();
			$this->param_vars= $HTTP_GET_VARS;
			$session_id= session_id();
			if($session_id)
			{
				if(	!isset($HTTP_COOKIE_VARS[$session_name]) ||
					$HTTP_COOKIE_VARS[$session_name] != $session_id	)
				{
					$this->update($session_name."=".$session_id);
					
				}else
					$this->delete($session_name);
			}
		}
		function &getHiddenParamTags($without= null)
		{
			global $HTTP_SERVER_VARS;

			Tag::deprecated("STQueryString::getHiddenParams()", "GetHtml::getHiddenParamTags()");
			if($without)
			{// zerlege Variable $without, f�r das array $aWithout,
			 // in einzelne Variablen
				$split= preg_split("/[, ]/", $without);
				$aWithout= array();
				foreach($split as $param)
				{
					if(trim($param))
						$aWithout[$param]= $param;
				}
			}

			// decodiere den gegebenen query-string
			// und zerlege ihn f�r das array $split in einzelne Variablen
			$string= rawurldecode($HTTP_SERVER_VARS["QUERY_STRING"]);
			return STQueryString::createHiddenParamTags($string, $aWithout);
		}
		function &getHiddenParams()
		{
			$getString= $this->getStringVars();
			return STQueryString::createHiddenParamTags($getString);
		}
		function &createHiddenParamTags($paramString, $aWithout= array())
		{
			if(substr($paramString, 0, 1)=="?")
				$paramString= substr($paramString, 1);
			$split= preg_split("/\&/", $paramString);

			// erstelle nun einen div-Tag
			// indem alle Variablen des query-strings stehen
			// ausser die welche in der var $without stehen
			$div= new DivTag();
			if(trim($paramString)=="")
				return $div;

			$pattern[]= "/\[/";
			$pattern[]= "/\]/";
			$replace[]= "\[";
			$replace[]= "\]";
			foreach($split as $param)
			{
				$KeyValue= preg_split("/=/", $param);
				$KeyValue[0]= trim($KeyValue[0]);
				$inside= false;

				foreach($aWithout as $var)
				{
					$string= preg_replace($pattern, $replace, $var);
					if(preg_match("/^".$string.".*$/", $KeyValue[0]))
					{
						$inside= true;
						break;
					}
				}
				if(!$inside)
				{
					$input= new InputTag();
						$input->type("hidden");
						$input->name($KeyValue[0]);
						$input->value($KeyValue[1]);
					$div->add($input);
				}
			}
			return $div;
		}
/*		function getParamString($type= null, $WithOrWithout= null)
		{
			$this->makeAction(func_get_args());
			// alex 03/05/2005:	zuerst nimm alle ? und & weg
			//					und dann kommt wieder ein Fragezeichen drann
			//					damit kein & auf ein ? folgt
			while(	$this->param_vars!=""
					and
					(	substr($this->param_vars, 0, 1)=="?"
						or
						substr($this->param_vars, 0, 1)=="&"	)	)
			{
				$this->param_vars= substr($this->param_vars, 1);
			}
			if($this->param_vars!="")
				$this->param_vars= "?".$this->param_vars;
			return $this->param_vars;
		}*/
		function makeAction($args)
		{
			$argCount= count($args);
			for($o= 0; $o<$argCount; $o= $o+2)
			{
				if(Tag::isDebug())
				{
					$debugString= "make <b>";
					if($args[$o]==STINSERT)
						$debugString.= "INSERT";
					elseif($args[$o]==STUPDATE)
						$debugString.= "UPDATE";
					elseif($args[$o]==STDELETE)
						$debugString.= "DELETE";
					else
						$debugString= "wrong type";
					$debugString.= "</b> ".$args[$o+1]." in get-string \"".$this->param_vars."\"";
					Tag::echoDebug("stquerystring", $debugString);
				}
				$newValue= $args[$o+1];
				if(is_array($newValue))
				{//echo "$newValue<br />";
					foreach($newValue as $key=>$value)
					{
						$this->getParamString($args[$o], $value);
					}
					return;
				}
				$this->makeNewArray($args[$o], $newValue);
			}
			if($this->param_vars=="?")
				$this->param_vars= "";
			elseif(substr($this->param_vars, 0, 1)=="&")
				$this->param_vars= "?".substr($this->param_vars, 1);
			$this->param_vars= preg_replace("/&&/", "&", $this->param_vars);
			Tag::echoDebug("stquerystring", "result is \"".$this->param_vars."\"<br />");
		}
		function makeNewArray($type= null, $sWithOrWithout= null)
		{
			if($type===null)
				return false;
			$split= preg_split("/=/", $sWithOrWithout);
			$key= $split[0];
			$value= $split[1];
			// erstelle key f�r regular expression
			//$key2= str_replace("[", "\\[", $key);
			//$key2= str_replace("]", "\\]", $key2);
			$key2= preg_quote($key);

			if(preg_match("/".$key2."=([^&]*)(&|$)/", $this->param_vars, $preg))
			{// wenn die Variable bereits im GET-Bereich ist
			 // diese 	bei UPDATE aktualieren,
			 //			bei INSERT ebenfalls aktualisieren und ein older_get einf�gen
			 //		und	bei DELETE diese l�schen

				if(	$type==STINSERT
					or
					$type==STUPDATE	)
				{
					$this->param_vars= preg_replace("/$key2=".$preg[1]."/", "$key=$value", $this->param_vars);
					if(Tag::isDebug())
					{
						if($type==STINSERT) Tag::echoDebug("stquerystring.insert", "update $key from ".$preg[1]." to $value for insert");
						if($type==STUPDATE) Tag::echoDebug("stquerystring.update", "update $key from ".$preg[1]." to $value");
					}
					if($type==STINSERT)
					{
						$string= $this->getOlderVar($key);
						$this->makeNewArray(STINSERT, $string."=".$preg[1]);
						Tag::echoDebug("stquerystring.insert", "update $sWithOrWithout");
					}
					return true;
				}elseif($type==STDELETE)
				{
					//print_r($preg);echo "<br />";
					//echo $this->param_vars."<br />";
					$older= $this->getOlderVar($key);
					$result= $this->makeNewArray(STDELETE, $older);
					/*if(Tag::isDebug())
					{
						$debugString= "delete result: $older ";
						if($)
						Tag::echoDebug("stquerystring.delete", $debugString);
					}*/
					if($result)
					{// wenn eine �tere Variable gel�scht wurde,
					 // die augenblickliche updaten und diese als gel�scht zur�ckmelden

					 	$this->makeNewArray(STUPDATE, $key."=".$result);
						return $preg[1];
					}// sonst wird sie gel�scht und zur�ckgegeben
					$this->param_vars= preg_replace("/&?".$key2."=([^&]*)/", "", $this->param_vars);
					Tag::echoDebug("stquerystring.delete", "delete $key, return ".$preg[1]);
					return $preg[1];
				}else
					echo "<b>WARNING</b> no right type for makeNewArray()<br />";
			}elseif($type==STDELETE)
			{
				Tag::echoDebug("stquerystring.delete", "$key2 for delete has maybe no logical operator");
				$split= preg_split("/&/", $this->param_vars);
				$param_vars= "";
				foreach($split as $value)
				{
					if($value!=$key2 && $value!="?".$key2)
						$param_vars.= $value."&";
				}
				$this->param_vars= substr($param_vars, 0, strlen($param_vars)-1);
				
			}elseif($type==STINSERT
					or
					$type==STUPDATE	)
			{// sonst wenn die Variable nicht im GET-Bereich ist
			 // bei INSERT oder UPDATE hinzuf�gen
  				$lastChar= substr($this->param_vars, count($this->param_vars)-1);
  				if(	$lastChar!="?"
  					and
  					$lastChar!="&"	)
  				{
  					$this->param_vars.= "&";
  				}
					$this->param_vars.= $sWithOrWithout;
					if(Tag::isDebug())
					{
						if($type==STINSERT) Tag::echoDebug("stquerystring.insert", "insert $sWithOrWithout");
						if($type==STUPDATE) Tag::echoDebug("stquerystring.update", "insert $sWithOrWithout for update");
					}
					return true;
			}
			return false;
		}
		function getOlderVar($currentVar)
		{
			$split= preg_split("/[\[\]]/", $currentVar);
			$string= "older_get";//print_r($split);echo " ".$string."<br />";
			foreach($split as $spez)
			{
				if($spez)
					$string.= "[$spez]";
			}
			return $string;
		}
  /**
   *  gibt ein verkn�pfungs-Tag auf eine Cascade Sylesheet aus
   *
	 *  @param: $FileName	Cascade Sylesheet-Datei mit Pfadangabe
	 *  @param: $title	Titel des Stylesheets, muss nicht angegeben werden
   *
	 *  @Autor: Alexander Kolli
   */
	 	static function getCssLink($FileName, $media= null, $title= null)
		{
			$link= new LinkTag();
				$link->rel("stylesheet");
				$link->href($FileName);
				$link->type("text/css");
				if($media)
					$link->media($media);
				if($title)
					$link->title($title);
			return $link;
		}
  /**
   *  gibt ein JavaScript-Statement im HTML, innerhalb des Script-Tags, aus
   *
	 *  @param: $src	der Dateiname einer javascript-Datei, <br>
	 *				wird dieser nicht angegeben, kann auch bei der Datei<br>
	 *				welche man erh&auml;lt mit -&gt;add -&gt; javascript eingef&uuml;gt werden
   *
	 *  @Autor: Alexander Kolli
   */
		function getJavaScriptTag($src= null)
		{
			$script= new ScriptTag();
				$script->type("text/javascript");
				if($src)
					$script->src($src);
			return $script;
		}
  /**
   *  erzeugt einen select-Tag aus einem Array
	 *
	 *  @param: $array	das Array, in welchem alle Dateien gespeichert sind
	 *  @param: $nKey		der Key im Array, welchen Wert der Eintrag bei einem form-send haben soll
	 *  @param: $nValue	der Key im Array, welcher Eitrag im Pop-Up-Menue angezeigt werden soll
	 *  @param: $Selector	wenn ein 4. Parameter als SelectTag() angegeben wird, werden die option-Tags in diesem gespeichert.
	 *					<br>(4. und 5. Parameter k�nnen vertauscht werden)
	 *  @param: $selected	wenn ein 5. Parameter angegeben wird, wird dieser als der ausgew�hlte deffiniert.
	 *					<br>(4. und 5. Parameter k�nnen vertauscht werden)
   *
	 *  @Autor: Alexander Kolli
   */
		function getSelectArray($array, $nKey, $nValue)
		{
			if(!is_array($array))
				echo "<br><b>Warning:</b> Invalid first argument supplied for getSelectArray(), must be an <b>Array</b>";
			$args= func_get_args();
			$tag= null;
			$selectTag= null;
			$selected= null;
			if(count($args)>3)
			{
				for($n=3; $n<count($args); $n++)
				{
					if(	get_class($args[$n])=="selecttag"
						or
						is_subclass_of($args[$n], "selecttag"))
					{
						$selectTag= &$args[$n];
					}elseif(is_subclass_of($args[$n], "tag"))
						$tag= &$args[$n];
					else
						$selected= $args[$n];
				}
			}
			if($selectTag==null)
				$selectTag= new SelectTag();
			$selectTag= $this->getSelectArrayA($array, $nKey, $nValue, $selectTag, $selected);
			if($tag!=null)
				$tag->add($selectTag);
			else
				$tag= $selectTag;
			return $tag;
		}
		function getSelectArrayA($array, $nKey, $nValue, $selectTag, $selected)
		{
			foreach($array as $row)
			{
				$option= new OptionTag();
				$option->value($row[$nKey]);
				$option->add($row[$nValue]);
				//echo "'".$selected."'=='".$row[$nKey]."'<br>";
				if($selected==$row[$nKey])
					$option->selected();
				$selectTag->add($option);
			}
			return $selectTag;
		}
}
?>