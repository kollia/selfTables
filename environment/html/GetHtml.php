<?php

require_once($php_html_description);

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
			return GetHtml::getCssLink($FileName, $title);
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
			return GetHtml::getJavaScriptTag($src);
		}		
		
// --------------------------------------------------------------------------------------------------
// --------------------------------------------------------------------------------------------------
// ------------------  Ausf�hrungs-Klasse  ----------------------------------------------------------

class GetHtml
{	
		var $param_vars;
		
		function __construct($param_vars= null)
		{
			global	$HTTP_GET_VARS;
			
			if(is_string($param_vars))
			{// alex 03/05/2005:	es darf nun bei einem String
			 //					auch die Adresse selbst mitgegeben werden
				if(preg_match("/\?/", $param_vars))
				{
					$split= preg_split("/\?/", $param_vars);
					$param_vars= $split[1];
				}
			}
			$this->param_vars= $param_vars;
			if(!$this->param_vars)
				$this->resetParams();
		}
		// deprecated
		function getParamString($type= null, $WithOrWithout= null)
		{
			if(	$type
				and
				$WithOrWithout	)
			{
				$this->make($type, $WithOrWithout);
			}
			return $this->createParamString($this->param_vars);
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
		function getStringVars()
		{
			return $this->createParamString($this->param_vars);
		}
		function getArrayVars()
		{
			return $this->param_vars;
		}
		function insert($paramValue)
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
		function delete($param)
		{
			$array= $this->splitParamValue($param);
			$params= $array["params"];
			$param_vars= $this->param_vars;
			// wenn der Parameter nicht existiert -> abbrechen
			foreach($params as $param)
			{
				if(!isset($param_vars[$param]))
					return;
				$param_vars= $param_vars[$param];
			}
			// suche ob es einen older gibt;
			// search oldest entry
			$oldest= &$this->param_vars["stget"];
			$values= array();
			$aktPos= 1;
			$param_vars= &$this->param_vars["stget"];
			while($aktPos)//param_vars["older"])
			{		
				$aktPos= $param_vars["older"];
				foreach($params as $param)
				{
					if(!isset($aktPos))
						break;
					$aktPos= $aktPos[$param];
				}
				if(isset($aktPos))
				{
					$values[]= $aktPos;
					$oldest= &$param_vars;
					$param_vars= &$param_vars["older"]["stget"];
				}
			}
			// delete created older[stget] -> create by reference
			// -> $param_vars= &$param_vars["older"]["stget"];
			/*if($oldest["older"]["stget"]===null)
			{
				if(count($oldest["older"])==1)
					unset($oldest["older"]);
				else
					unset($oldest["older"]["stget"]);
			}*/
			if(count($values))
			{// clear oldest
				$params= array_merge(array("older"), $params);
			}else
			{// clear aktual
				$oldest= &$this->param_vars;
				$params= $array["params"];
			}
			
			
			$steps= count($params);
			$lastStep= 0;
			$aktPos= $oldest;
			for($c= 0; $c<$steps; $c++)
			{// search first array with one entry to delete
				if(	!is_array($aktPos)
					or
					count($aktPos)>1	)
				{
					$lastStep= $c;
				}
				$aktPos= $aktPos[$params[$c]];
			}
			// goto first array (lastStep)
			for($c= 0; $c<$lastStep; $c++)
				$oldest= &$oldest[$params[$c]];
			// delete
			unset($oldest[$params[$c]]);

			// update all params bevore
			if(!count($values))
				return; // no params to update				
			$param_vars= &$this->param_vars;
			$params= $array["params"];
			$steps= count($params)-1;
			$lastParam= null;// for delete
			foreach($values as $value)
			{
				$Pos= &$param_vars;
				for($c= 0; $c<$steps; $c++)
					$Pos= &$Pos[$params[$c]];
				$Pos[$params[$steps]]= $value;
				$lastParam= &$param_vars;
				$param_vars= &$param_vars["stget"]["older"];
			}
			// delete created older -> create by reference
			// -> $param_vars= &$param_vars["stget"]["older"];
			if($lastParam["stget"]["older"]===null)
				unset($lastParam["stget"]["older"]);
			return;	
		}
		function update($paramValue)
		{
			$array= $this->splitParamValue($paramValue);
			$this->updateA($array, $this->param_vars);
		}
		/*private*/function updateA($array, &$param_vars)
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
		function splitParamValue($paramValue)
		{
			$split= preg_split("/=/", $paramValue);
			$param= $split[0];
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
					$this->createArrayA($param, $aParams);
			}
			
		}
		function createArrayA($paramValue, &$aParams)
		{
			$array= $this->splitParamValue($paramValue);
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
		function createParamString($param_vars)
		{
			if(!$param_vars)
				return "";
			foreach($param_vars as $key=>$value) //"?wer=du&old[d]=23&old[d][v]=1"
			{
				if($string)
					$string.= "&";
				$firstString= $key;
				if(is_array($value))
				{
					$string.= $this->createArrayString($firstString, $value);
				}else
					$string.= $firstString."=".$value;
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
		function resetParams()
		{
			global	$HTTP_GET_VARS,
					$HTTP_COOKIE_VARS;
			
			$session_name= session_name();
			$this->param_vars= $HTTP_GET_VARS;
			$session_id= session_id();
			if($session_id)
			{
				if($HTTP_COOKIE_VARS[$session_name]!=$session_id)
					$this->update($session_name."=".$session_id);
				else
					$this->delete($session_name."=".$session_id);
			}
		}
		function &getHiddenParamTags($without= null)
		{
			global $HTTP_SERVER_VARS;
			
			Tag::deprecated("GetHtml::getHiddenParams()", "GetHtml::getHiddenParamTags()");
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
			return GetHtml::createHiddenParamTags($string, $aWithout);
		}
		function &getHiddenParams()
		{
			$getString= $this->getStringVars();
			return GetHtml::createHiddenParamTags($getString);
		}
		function &createHiddenParamTags($paramString, $aWithout= array())
		{
			if(substr($paramString, 0, 1)=="?")
				$paramString= substr($paramString, 1);
			$split= preg_split("/&/", $paramString);
			
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
					Tag::echoDebug("gethtml", $debugString);
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
			Tag::echoDebug("gethtml", "result is \"".$this->param_vars."\"<br />");
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
						if($type==STINSERT) Tag::echoDebug("gethtml.insert", "update $key from ".$preg[1]." to $value for insert");
						if($type==STUPDATE) Tag::echoDebug("gethtml.update", "update $key from ".$preg[1]." to $value");
					}	
					if($type==STINSERT)
					{
						$string= $this->getOlderVar($key);
						$this->makeNewArray(STINSERT, $string."=".$preg[1]);
						Tag::echoDebug("gethtml.insert", "update $sWithOrWithout");
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
						Tag::echoDebug("gethtml.delete", $debugString);
					}*/
					if($result)
					{// wenn eine �tere Variable gel�scht wurde,
					 // die augenblickliche updaten und diese als gel�scht zur�ckmelden
					 	
					 	$this->makeNewArray(STUPDATE, $key."=".$result);
						return $preg[1];
					}// sonst wird sie gel�scht und zur�ckgegeben
					$this->param_vars= preg_replace("/&?".$key2."=([^&]*)/", "", $this->param_vars);
					Tag::echoDebug("gethtml.delete", "delete $key, return ".$preg[1]);
					return $preg[1];
				}else
					echo "<b>WARNING</b> no right type for makeNewArray()<br />";
			}elseif($type==STDELETE)
			{
				Tag::echoDebug("gethtml.delete", "$key2 for delete has maybe no logical operator");
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
						if($type==STINSERT) Tag::echoDebug("gethtml.insert", "insert $sWithOrWithout");
						if($type==STUPDATE) Tag::echoDebug("gethtml.update", "insert $sWithOrWithout for update");
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
		/*function createParamString($param_vars)
		{
			if(!$param_vars)
				return "";
			foreach($param_vars as $key=>$value) //"?wer=du&old[d]=23&old[d][v]=1"
			{//echo $key."=>".$value."<br />";
				if($string)
					$string.= "&";
				if(is_array($value))
					$string.= $key.$this->createArrayString($key, $value);
				else
					$string.= "$key=$value";
			}
			return "?$string";
		}
		function createArrayString($key, $paramArray)
		{
			$string= "";
			foreach($paramArray as $pKey=>$pValue)
			{//echo $pKey."=>".$pValue."<br />";
				if($string)
					$string.= "&";				
				if(is_array($pValue))
				{
					$string2= "";
					while($pValue)
					{print_r($pValue);
						if($string2)
							$string2.= "&";echo "more: $key[".$this->createArrayString($pKey, $pValue)."<br>";
						$string2.= "$key".moreArrays($pValue);
					}
					$string.= $string2;
				}else
				{
					$string.= $key."[$pKey]=$pValue";
				}
			}
			return $string;
		}*/
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
			$link= new LinkTag();
				$link->rel("stylesheet");
				$link->href($FileName);
				$link->type("text/css");
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