<?php

require_once($_stquerystring);

class GetHtml extends STQueryString
{	
		// deprecated
		function getParamString($type= null, $WithOrWithout= null)
		{
			if(	$type
				and
				$WithOrWithout	)
			{
				$this->make($type, $WithOrWithout);
			}
			return $this->getStringVars();
		}
		function &getHiddenParamTags($without= null)
		{
			global $HTTP_SERVER_VARS;
			
			Tag::deprecated("GetHtml::getHiddenParams()", "GetHtml::getHiddenParamTags()");
			if($without)
			{// zerlege Variable $without, für das array $aWithout,
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
			// und zerlege ihn für das array $split in einzelne Variablen
			$string= rawurldecode($HTTP_SERVER_VARS["QUERY_STRING"]);
			return GetHtml::createHiddenParamTags($string, $aWithout);
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
}
?>