<?php


function st_print_r($value, $deep=1, $space= 0, $bFirst= true)
{
    if($bFirst)
        echo stTools::getSpaces($space);
	if(	is_object($value)
		or
		is_array($value)	)
	{
		if(is_object($value))
			$f= get_class($value)."( ";
		else
			$f= "array( ";
		//STCheck::write($f);
		//echo "typeof($value, $aExclude, null, 0)<br />";
		if($deep>0)
//			and
//			!typeof($value, $aExclude, null, 0)	)
		{
			if($space==0)
				echo "\n";
			$space+= strlen($f);
			echo $f;
			$keyLen= 0;
			foreach($value as $key=>$entry)
			{
				$len= strlen($key)+2;
				if($len>$keyLen)
					$keyLen= $len;
			}
			$count= 1;
			$lastCount= count((array)$value);
			foreach($value as $key=>$entry)
			{
				$str= "[".$key."]";//echo $space." ".($keyLen-strlen($str));
				$str.= stTools::getSpaces((($keyLen-strlen($str))))." => ";
				if($count>1)
					echo stTools::getSpaces($space);
				echo $str;
				st_print_r($entry, ($deep-1), ($space+strlen($str)), false);
				if($count != $lastCount)
					echo "\n";
				$count++;
			}
			if($lastCount == 0)
				echo "-empty- )";
			else
				echo "      )";
			$space-= strlen($f);
		}else
		{
			if(	is_array($value) &&
				count($value) == 0	)
			{
				echo $f."-empty- )";
			}elseif (typeof($value, "STBaseTable"))
			{
			    echo $f.$value->Name."::".$value->ID." )";
			}else
				echo $f."-skip- )";
			$space-= strlen($f);
		}
		if($space==0 || $bFirst)
			echo "<br />\n";
	}elseif(is_bool($value))
	{
		echo "boolean(";
		if($value)
			echo "true)";
		else
			echo "false)";
	}elseif(is_string($value))
		echo "\"".$value."\"";
	elseif($value===null)
		echo "( -NULL- )";
	else
		echo $value;
}
function showErrorTrace($from= 0, $much= -3)
{
	if(!phpVersionNeed("4.3.0"))
		return;
	$from++; // damit der n�chste Aufruf  nicht angezeigt wird
	stTools::showErrorTrace($from, $much);
}
function printPassword($password, $placeholder= "*")
{
    echo stTools::getPlaceholdPassword($password, $placeholder, /*show password*/false);
}
function getPlaceholdPassword($password, $placeholder= "*")
{
    return stTools::getPlaceholdPassword($password, $placeholder, /*show password*/false);
}

/**
 * does first parameter exist as value in array.<br>
 * The most problem of this funktion is to ask in an if-sentens only with two to be equal
 * not three. By two '==' it is no difference between 0 and false
 *
 * @param string/int $value string or integer which should be searched
 * @param array $array array in which should be searched
 * @return string/false return the key from the value in the array, otherwise false
 */
function array_value_exists($value, $array)
{
	STCheck::param($value, 0, "string", "int");
	STCheck::param($array, 1, "array");

		if(!count($array))
			return false;
		reset($array);
		foreach($array as $aKey=>$aValue)
		{//echo "$aValue===$value<br />";
			if($aValue===$value)
			{
				//echo "value exist<br />";
				return $aKey;
			}
		}
		//echo "value not exist<br />";
		return false;
}
  /**
   *  ausgabe nur bei debug-session. (Tag::debug(true))<br>
	 *  Gibt ein Array oder einen String aus,<br>
	 *
	 *  @param: $value	gibt einnen String mit echo und ein Array mit print_r aus.<br>
	 *					wenn der Value <b>NULL</b> ist wird &gt;&gt;NULL&lt;&lt; ausgegeben
	 *  @param: $debug	Angabe von Aussen, ob der Value angezeigt werden soll
   *
	 *  @Autor: Alexander Kolli
   */
function out($value, $debug=null)
{
		global $HTML_CLASS_DEBUG_CONTENT;

		if($debug==null)
			$debug= $HTML_CLASS_DEBUG_CONTENT;

		$opject= new stTools();
		$sRv= $opject->out($value, $debug);

		return $sRv;
}

function typeof($object, $type, $empty= null, $pos= null)
{
	if($type===null)
	{
		if($object===null)
			return true;
		return false;
	}
	Tag::alert(!is_string($type)&&!is_array($type), "typeof", "second parameter can not be an object");
		if(is_string($type))
		{
			$aEmpty= array();
			$aType= preg_split("/[\[ ,\]]+/", $type);
			$anz= func_num_args();
			for($n= 2; $n<$anz; $n++)
			{
				$type= func_get_arg($n);
				$type= trim(strtolower($type));
				if(preg_match("/^empty\((.+)\)$/i", $type, $preg))
					$aEmpty[$preg[1]]= "empty";
				else
					$aType[]= $type;
			}
			$pos= 0;
			//if($object===null)

			if(Tag::isDebug("typeof"))
			{
				echo "<b>[typeof]</b>";
				if(is_object($object))
					echo "object(".get_class($object).")";
				elseif(is_array($object))
					echo "Array";
				else st_print_r($object);
				echo " =";
				st_print_r($aType);
			}
		}else
		{
			$aType= &$type;
			$aEmpty= &$empty;
		}
		if(is_object($object))
		{//if($type=="STDatabase")
			if(	strtolower(get_class($object))==strtolower($aType[$pos])
				or
				is_subclass_of($object, $aType[$pos]))
			{
				Tag::echoDebug("typeof", get_class($object)." == ".$aType[$pos]);
				return true;
			}
		}elseif($aType[$pos]=="array")
		{
			if(is_array($object))
			{
				Tag::echoDebug("typeof", "incomming object == ".$aType[$pos]);
				return true;
			}
		}elseif($aType[$pos]=="bool" || $aType[$pos]=="boolean")
		{
			if(is_bool($object))
			{
				Tag::echoDebug("typeof", $object." == ".$aType[$pos]);
				return true;
			}
		}elseif($aType[$pos]=="numeric")
		{
			if(is_numeric($object))
			{
				Tag::echoDebug("typeof", $object." == ".$aType[$pos]);
				return true;
			}
		}elseif($aType[$pos]=="string")
		{
			if(is_string($object))
			{
				if(	$object==="" &&
					(	!isset($aEmpty["string"]) ||
						$aEmpty["string"] != "empty"	)	)
				{
					return false;
				}
				Tag::echoDebug("typeof", $object." == ".$aType[$pos]);
				return true;
			}
		}elseif(preg_match("/^int(eger)?$/", $aType[$pos]))
		{
			if(is_integer($object))
			{
				Tag::echoDebug("typeof", $object." == ".$aType[$pos]);
				return true;
			}
		}elseif($aType[$pos]=="float")
		{
			if(is_float($object))
			{
				Tag::echoDebug("typeof", $object." == ".$aType[$pos]);
				return true;
			}
		}elseif($aType[$pos]=="double")
		{
			if(is_double($object))
			{
				Tag::echoDebug("typeof", $object." == ".$aType[$pos]);
				return true;
			}
		}elseif($aType[$pos]=="long")
		{
			if(is_long($object))
			{
				Tag::echoDebug("typeof", $object." == ".$aType[$pos]);
				return true;
			}
		}elseif($aType[$pos]=="null")
		{
			if($object===null)
			{
				Tag::echoDebug("typeof", "( -NULL- ) == ".$aType[$pos]);
				return true;
			}
		}elseif($aType[$pos]=="function")
		{
		    if($object != null)
		    {
    			if(function_exists($object))
    			{
    				Tag::echoDebug("typeof", $object." == ".$aType[$pos]);
    				return true;
    			}
		    }
		}
		$pos++;
		if(count($aType)>$pos)
			return typeof($object, $aType, $aEmpty, $pos);
		if(is_object($object))
			$sObject= get_class($object);
		else
			$sObject= &$object;

		if(Tag::isDebug("typeof"))
		{
			if(is_object($object))
				$object= get_class($object);
			elseif(is_array($object))
				$object= "array()";
			elseif(is_bool($object))
			{
				if($object == true)
					$object= "boolean(true)";
				else
					$object= "boolean(false)";
			}
			Tag::echoDebug("typeof", "type of \"".$object."\" is not ok");
		}
		return false;
}

function phpVersionNeed($needVersion, $functionName= null)
{
		global $HTML_CLASS_DEBUG_CONTENT;

		$version= phpversion();
		$aktVers= preg_split("/[.]/", $version);
		$needVers= preg_split("/[.]/", $needVersion);
		$bOk= true;
		$anz= count($aktVers);
		$anzA= count($needVers);
		if($anz<$anzA)
			$anz= $anzA;
		for($o= 0; $o<$anz; $o++)
		{
			$akt= $aktVers[$o];
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
		if(	!$bOk
			and
			$functionName
			and
			$HTML_CLASS_DEBUG_CONTENT	)
		{
			echo "<br />\naktual <b>PHP Version ".$version.":</b> ";
			echo "need Version <b>".$needVersion."</b> for ";
			echo $functionName."<br />\n";
		}
		return $bOk;
}

class stTools
{
	public static function showErrorTrace($from= 0, $much= -3)
    {
		$backTrace= debug_backtrace();

        foreach($backTrace as $function)
        {
			if($from<1)
			{
				$sFunc= "function";
    			if(	isset($function["function"]) &&
    				isset($function["class"]) &&
					$function["class"]==$function["function"]	)
				{
					$sFunc= "constructor";
					$function["class"]= "";
					$function["type"]= "";
				}
                echo "<b>$sFunc</b> ";
                if(isset($function["class"]))
                	echo $function["class"];
                if(isset($function["type"]))
                	echo $function["type"];
                if(isset($function["function"]))
                	echo $function["function"];
                echo "<b>(</b>";
                $params= "";
                foreach($function["args"] as $param)
                {
                    if(is_string($param))
                            $params.= "\"$param\", ";
                    elseif(is_numeric($param))
                            $params.= "$param, ";
                    elseif(is_object($param))
                            $params.= get_class($param)."(), ";
                    elseif(is_array($param))
                            $params.= "array(), ";
                }
                echo substr($params, 0, strlen($params)-2)."<b>)</b>";
                echo " <b>file</b> ";
                if(isset($function["file"]))
                    echo $function["file"];
                else
                    echo "no file";
                
                echo " <b>line:</b>";
                if(isset($function["line"]))
                    echo $function["line"];
                else
                    echo "no line";
                echo "<br />";
				--$much;
				if($much==0)
					break;
			}
			--$from;
        }
    }

    static function getPlaceholdPassword($password, $placeholder= "*", $showPWD= false)
    {
        $string= "";
        if(!$showPWD)
        {
            $count= strlen($password);
            for($n= 0; $n < $count; ++$n)
                $string.= $placeholder;
        }else
            $string= htmlspecialchars($password);
        return $string;
    }
	function out($value, $debug)
	{echo "Function out";exit;
			$type= gettype($value);
			if($type=="object")
				$type.= " ".get_class($value);
  		$sRv= "\n<br />(".$type.") ";
  		if(!isset($value))
  			$sRv.= "&gt;&gt;NULL&lt;&lt;<br>";
  		elseif(	is_array($value)
  				or
  				is_object($value) )
  		{
  			$sRv.= $this->print_array($value, strlen($sRv));
  		}else
  			$sRv.= $value;
			$sRv.= "<br />\n";
			if($debug==true)
				echo $sRv;
			return $sRv;
		}
		var $objects= array();
		function print_array($array, $spaces)
		{
			$sRv= " {<br />\n";
			$spaces+= 6;
			foreach($array as $key=>$value)
			{
				$sRv.= $this->getSpaces($spaces);
				$ob= $array[$key];
				if(!$this->in_objects($ob))
				{
					$this->objects[]= $array[$key];
  				$sRv.= "[$key] = ";
  				if(	is_array($value)
  					or
  					is_object($value)	)
  				{
  					$sRv.= $this->print_array($value, strlen($key." = ")+$spaces);
  				}else
  					$sRv.= $value;
				}else
				{
					if(is_array($value))
						$sRv.= "Array";
					else
						$sRv.= "Object ".get_class($value);
					$sRv.= " is shown";
				}
				$sRv.= "<br />\n";

			}
			$sRv.= $this->getSpaces($spaces)."}";
			return $sRv;

		}
		public static function getSpaces($n)
		{
			$sRv= "";
			for($o= 0; $o<$n; $o++)
				$sRv.= " ";
			return $sRv;
		}
		function in_objects(&$object)
		{
			if(	is_array($object)
				or
				is_object($object)	)
			{
				if(is_array($object))
					echo "Array ".reset($object);
				else
				{
					echo get_class($object); echo " ".$object->Name;
  					for($n= 0; $n<count($this->objects); $n++)
  					{
  						if($this->objects[$n]==$object)
  						{
							echo "==";
							if(is_array($this->objects[$n]))
								echo "Array ".reset($this->objects[$n]);
							else
							{
								echo get_class($this->objects[$n]); echo " ".$this->objects[$n]->Name;
								echo " is in array<br>";
  								return true;
  							}
  						}
						echo " is not in array<br>";
						return false;
					}
					return false;
				}
			}
		}
}

?>