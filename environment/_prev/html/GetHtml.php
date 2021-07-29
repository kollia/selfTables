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
}
?>