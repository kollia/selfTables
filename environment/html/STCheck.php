<?php

require_once($_stquerystring);

/**
* when file cannot open, display error message only
* on first time
*/
$global_bOpenErrWritten= false;
/**
 * exit functionionality
 * by reached counting
 * @see STCheck::overflow_exit
 * @author kollia
 *
 */
$g__STCheck_exit_entry= array();
/**
 * STCheck set output buffer by first debug setting STCheck::debug(true) with ob_start()
 * if will be release buffer after set Session or when execute STSideCreator
 * @param bool
 */
$global_activeOutputBuffer= false;
$global_outputBufferWasErased= false;
$global_clearOutputBuffer= false;
/**
 * whether should show tat session set to noRegister
 * @var boolean $global_SESSION_noRegister_SHOWEN
 */
$global_SESSION_noRegister_SHOWEN= false;
$global_SESSION_noRegister_onLine= "";

/**
 * set struct of file and line
 * by defining debugging output.
 * It should deleted by first echoDebug otutput writing
 */
$global_set_DEBUG_onLine_byFirst= false;

/**
 * definition of explicit creation of statements
 * for "db.statement" debug output
 * @var array $__stdbtables_statement_count
 */
$__stdbtables_statement_count= array( 'db.statement'=>0, 'table'=>0 );
$__stdbtables_statement_count_from= array();
$__stdbtables_statement_count_to= array();

class STCheck
{
	var $m_bOpenErr= false;
	
	private static function error_message($symbol, $trigger, $functionName, $message, $outFunc, $countFunc= 1)
	{
		if($trigger)
		{
			if(phpVersionNeed("4.3.0"))
				$symbol.= ": ";
			echo "<br /><b>".$symbol."</b> ";
			if(!phpVersionNeed("4.3.0"))
				echo "in <b>".$functionName.":</b> ";
			echo $message;
			echo "<br />";
			if(phpVersionNeed("4.3.0"))
				echo "in ";
			showBackTrace($outFunc+1, $countFunc);
			return true;
		}
		return false;
	}
	public static function write($value, $deep= 1)
	{
		if(!STCheck::isDebug())
			return;
		$backtrace= debug_backtrace();
		$line= $backtrace[0]["line"];
		preg_match("/([^\\\\\/]+)$/", $backtrace[0]["file"], $ereg);
		$file= $ereg[1];
		$function= "";
		if(isset($backtrace[1]["class"]))
			$function.= $backtrace[1]["class"]."::";
		if(isset($backtrace[1]["function"]))
			$function.= $backtrace[1]["function"]."()";
		// define first prefix without html-Tags for counting
		$pref= "[ file:$file line:$line ";
		STCheck::overflow_exit("file:<b>$file</b> on line <b>$line</b>", 20, 1);
		if($function != "")
			$pref.= "in $function ";
		$pref.= "]: ";
		$space= count($backtrace);
		// define now original prefix for output
		$pref= "<b>[ file:</b>".$file." <b>line:</b>".$line." ";
		if($function != "")
			$pref.= "<b>in</b> $function ";
		$pref.= "<b>]:</b> ";
		return STCheck::writeIntentedLineB($space, $pref, $value, $deep);
	}
	public static function overflow_exit($fileString= "allPositions", $count= 50, $fromFile= 0)
	{
		global $g__STCheck_exit_entry;
		
		$fromFile+= 1;
		if(!isset($g__STCheck_exit_entry[$fileString]))
			$g__STCheck_exit_entry[$fileString]= 0;
		if($g__STCheck_exit_entry[$fileString] > $count)
		{
			echo "<br /><br />an overflow occured by '$fileString'<br />";
			showBackTrace($fromFile);
			exit();
		}
		++$g__STCheck_exit_entry[$fileString];
	}
	/**
	 * write log message or array on screen
	 * intented with several spaces recursive 
	 * by any method or function.
	 * 
	 * @param string $prefStr prefix string written before value of string or array
	 * @param string/array $value string or array which should be displayed
	 * @param number $deep when parameter $value is an array, how deep the array contents should be displayed
	 * @param boolean $break wheter should make an carage return after the written string
	 * @return number count of intented spaces
	 */
	private static function writeIntentedLine($prefStr, $value, $deep)
	{
		$backtrace= debug_backtrace();
		$space= count($backtrace) - 1;
		return STCheck::writeIntentedLineB($space, $prefStr, $value, $deep);
	}
	public static function countHtmlCode($code)
	{
		$read= true;
		$count= 0;
		
		for($i= 0; $i < strlen($code); ++$i)
		{
			if(	$code[$i] == "&" ||
				$code[$i] == "<"	)
			{
				$read= $code[$i];		
			}
			if($read === true)
				$count++;
			if(	(	$read == "&" &&
					$code[$i] == ";"	) ||
				(	$read == "<" &&
					$code[$i] == ">"	)	)
			{
				$read= true;		
			}
		}
		return $count;
	}
	/**
	 * write log message or array on screen
	 * intented with several spaces recursive 
	 * by any method or function.
	 * 
	 * @param number $backtraceCount count of called functions/methods
	 * @param string $prefStr prefix string written before value of string or array
	 * @param string|array $value string or array which should be displayed
	 * @param number $deep when parameter $value is an array, how deep the array contents should be displayed
	 * @param boolean $break wheter should make an carage return after the written string
	 * @return number count of intented spaces
	 */
	private static function writeIntentedLineB($backtraceCount, $prefStr, $value, $deep, $break= true)
	{	
		$backtraceCount*= 2;
		STCheck::echoSpace($backtraceCount);
		echo $prefStr;
		if(	is_array($value) ||
			is_object($value)	)
		{
			$intent= $backtraceCount + STCheck::countHtmlCode($prefStr);
			st_print_r($value, $deep, $intent);
		}elseif( is_string($value) &&
		         trim($value) != ""   )
		{
		    echo $value;
		}else
		    st_print_r($value, 1);
		if($break)
			echo "<br />";
		return $backtraceCount;
	}
		function setPageStartTime()
		{
			global	$_st_page_starttime_;

			if(!Tag::isDebug())
				return;
			$time= time();
			if($time> $_st_page_starttime_)
				$_st_page_starttime_= $time;
		}
		static function info($trigger, $functionName, $message, $outFunc= 0)
		{
		    /**
		     * see for compatibility STCheck::is_error()
		     */
		    if(!STCheck::isDebug())
		    {
		        if($trigger)
		            return true;
		        return false;
		    }
		    return STCheck::error_message("Info", $trigger, $functionName, $message, $outFunc+1);
		}
		static function warning($trigger, $functionName, $message, $outFunc= 0)
		{
			return STCheck::is_warning($trigger, $functionName, $message, $outFunc+1);
		}
		static function is_warning($trigger, $functionName, $message, $outFunc= 0)
		{
		    /**
		     * see for compatibility STCheck::is_error()
		     */
			if(!STCheck::isDebug())
			{
				if($trigger)
					return true;
				return false;
			}
			return STCheck::error_message("Warning", $trigger, $functionName, $message, $outFunc+1);
		}
		public static function is_error($trigger, $functionName, $message, $outFunc= 0)
		{
		    /**
		     * 2021/07/29 alex: change function from error() to is_error() for php8 compatibility
		     * 					with STDatabase class where an error function
		     * 					be with no parameters
		     */
			if(!STCheck::isDebug())
			{
				if($trigger)
					return true;
				return false;
			}
			return STCheck::error_message("Error", $trigger, $functionName, $message, $outFunc+1);
		}
		public static function alert($trigger, $functionName, $message, $outFunc= 0)
		{
			STCheck::is_alert($trigger, $functionName, $message, $outFunc+1);
		}
		public static function is_alert($trigger, $functionName, $message, $outFunc= 0)
		{
		    /**
		     * see for compatibility STCheck::is_error()
		     */
			if(!Tag::isDebug())
			{
				if($trigger)
					return true;
				return false;
			}
			if(Tag::error_message("Fatal Error", $trigger, $functionName, $message, $outFunc+1, 20))
				exit;
			return false;
		}
		static function deprecated($newFunction, $oldFunction= null)
		{
			if(Tag::isDebug())
			{
				Tag::error_message("deprecated", true, $oldFunction, " -> take newer: $newFunction", 2);
			}
		}
		static function lastParam($nLast, $nParams)
		{
			if(!Tag::isDebug())
				return;
			Tag::paramCheck($nLast, 1, "int");
			Tag::paramCheck($nParams, 2, "int");

    		if($nLast==1)
    			$count= "one";
    		elseif($nLast==2)
    			$count= "two";
    		elseif($nLast==3)
    			$count= "three";
			else
				$count= $nLast;
			if(is_array($nParams))
				$nParams= count($nParams);
			STCheck::is_warning($nParams>$nLast, "Tag::paramCheck()", "function has no more than ".$count." params", 1);
		}
		public static function paramCheck($param, int $paramNr, $type1, $type2= null, $type3= null)
		{//echo "function paramCheck($param, $paramNr, $type1, $type2)<br />";
			--$paramNr;
			$params= func_get_args();

			return STCheck::param($param, $paramNr, $params);
		}
		public static function parameter($param, int $paramNr, $type1, $type2= null, $type3= null)
		{//echo "function paramCheck($param, $paramNr, $type1, $type2)<br />";
			--$paramNr;
			$params= func_get_args();

			return STCheck::param($param, $paramNr, $params);
		}
		public static function param($param, int $paramNr, $type1, $type2= null, $type3= null)
		{	//echo "function param(";st_print_r($param, 0);
			//			echo ", ";st_print_r($paramNr, 0);
			//			echo ", ";st_print_r($type1, 0); 
			//			echo ", ";st_print_r($type2, 0);
			//			echo "<br />";
		    global $member_tag_def;
			if(!STCheck::isDebug())
				return;
			//showBackTrace();echo "<br><br>";
			if(!is_numeric($paramNr))
			{
				STCheck::error_message("Error in parameter", true, "STCheck::param()",
									"2. parameter(=".$paramNr.") can only be an number", 1);
				exit();
			}
			if(is_array($type1))
				$args= $type1;
			else
				$args= func_get_args();
			if($args[2]=="check")
			{
				STCheck::is_warning((!isset($args[3])||!is_bool($args[3])), "STCheck::param()", 
					"fourth parameter not be set, or no correct boolean", 1);
				$bError= false;
				if(count($args)>=4)
				{
				    $begin= 4;
				    $bError= true;
				    for($a= 3; $a<count($args); $a++)
				    {
				        if($args[$a] === true)
				        {
				            $bError= false;
				            break;
				        }
				    }
				}else
					$bError= true;
				
			}else
			{
				$types= array();
				$empty= array();
				$c= count($args);
				for($n= 2; $n<$c; $n++)
				{
					if($args[$n] !== null)
					{
						if(preg_match("/^empty\((.+)\)$/i", $args[$n], $preg))
							$empty[trim($preg[1])]= "empty";
						else
							$types[]= $args[$n];
					}else
						$types[]= "null";
				}
				$bError= !typeof($param, $types, $empty, 0);
				$begin= 2;
			}
			if($bError)
			{
				if($paramNr==0)
					$count= "first";
				elseif($paramNr==1)
					$count= "second";
				elseif($paramNr==2)
					$count= "third";
				else
					$count= ($paramNr+1).".";
				$c= count($args);
				$types= "an ";
				if($c<4)// es wurde nur ein Parameter, nach paramNr, mitgegeben
					$types.= $args[2];
				else
				{
					if($args[2]=="check")
						$types= "defined as ";
					if($c > ($begin+1))
					{
    					for($n= $begin; $n<($c-1); $n++)
    					    $types.= "'".$args[$n]."', ";
    					$types= substr($types, 0, strlen($types)-2)." or '";
					}
    				$types.= $args[$c-1]."'";
				}
				if(is_string($param))
					$param= "\"".$param."\"";
				elseif(is_object($param))
					$param= "object(".get_class($param).")";

				if(is_bool($param))
				{
					if($param)
						$param= "true";
					else
						$param= "false";
					$param= "boolean(".$param.")";
				}elseif(is_string($param))
				{
				    $param= "\"$param\"";
				}elseif(is_object($param))
					$param= "object(".get_class($param).")";
				elseif(is_array($param))
				{					
					$param= "Array()";
				}elseif(!isset($param) ||
				        $param == null      )
				{
				    $param= "-NULL-";
				}
				STCheck::error_message("Error in parameters", true, "Tag::paramCheck()",
									$count." parameter(=".$param.") can be ".$types, 2);
				exit();
			}
		}
		public static function doNotOutputObBuffer()
		{
			global $global_clearOutputBuffer;
			$global_clearOutputBuffer= true;
		}
		public static function end_outputBuffer()
		{
		    global $global_activeOutputBuffer,
		           $global_outputBufferWasErased,
				   $global_clearOutputBuffer;
		    
			if($global_clearOutputBuffer)
				ob_end_clean();
			elseif($global_activeOutputBuffer)
		        ob_end_flush();
	        $global_activeOutputBuffer= false;
	        $global_outputBufferWasErased= true;
		}
		/**
		 * set debugging state
		 * 
		 * @param boolean|string $dbg_str general debugging state by (true), or by explicit string, see: st_pathdef.inc.php
		 * @param integer $from output string "db.statement" only since the statement creation growing to this number
		 * @param integer $to do not output after this number occured
		 */
		public static function debug(bool|string $dbg_str= true, int $from= null, int $to= null)
		{
			global	$HTTP_POST_VARS,
					$HTTP_POST_FILES,
					$HTTP_COOKIE_VARS,
					$HTML_CLASS_DEBUG_CONTENT,
					$HTML_CLASS_DEBUG_CONTENT_CLASS_FUNCTION,
					$global_logfile_dataname,
					$global_activeOutputBuffer,
					$global_outputBufferWasErased,
					$global_SESSION_noRegister_SHOWEN,
					$global_set_DEBUG_onLine_byFirst,
					$__stdbtables_statement_count,
					$__stdbtables_statement_count_from,
					$__stdbtables_statement_count_to;
			
			if( isset($from) )
			{
			    $filter= STCheck::array_key_filter($dbg_str);
			    if( !is_string($dbg_str) ||
			        $filter == ""            )
    			{
    			    STCheck::debug(true);
    			    $msg= "if first parameter not contain as prefix (\"";
    			    $msg.= implode("\", \"", array_keys($__stdbtables_statement_count));
    			    $msg.= "\") second and third parameter";
    			    $msg.= " ( \$from and \$to ) are not allowed";
    			    STCheck::warning(1, "STCheck::debug()", $msg, 1);
    			    echo "<br />";
    			}else
    			{
    			    $__stdbtables_statement_count_from[$filter]= $from;
        			if(isset($to))
        			    $__stdbtables_statement_count_to[$filter]= $to;
    			}
			}
			if( is_string($dbg_str) ||
			    $dbg_str !== false       )
			{
			    global_debug_definition(true);
			    if( $global_activeOutputBuffer == false &&
			        $global_outputBufferWasErased == false   )
    			{
    			    $global_activeOutputBuffer= true;
    				ob_start();
    			}
			}else
			{
			    global_debug_definition(false);
			    $__stdbtables_statement_count_from= array();
			    $__stdbtables_statement_count_to= array();
			}
			if(	!$HTML_CLASS_DEBUG_CONTENT &&
				$dbg_str &&
				$dbg_str !== "test"				)
			{
				$param= new STQueryString();
				$HTTP_GET_VARS= $param->getArrayVars();
				echo "\n<table bgcolor='white'><tr><td><pre>\n";
				if(file_exists($global_logfile_dataname))
					@unlink($global_logfile_dataname);
				if(!$global_set_DEBUG_onLine_byFirst)
				{
					$backtrace= debug_backtrace();
					$global_set_DEBUG_onLine_byFirst= array("file" => $backtrace[0]['file'],
															"line" => $backtrace[0]['line'],
															"dbg"  => $dbg_str	);
				}
			}
			STCheck::print_query_post();
			if(	$HTML_CLASS_DEBUG_CONTENT
				and
				!$dbg_str	)
			{
				echo "\n</pre>\n";
			}
			if( $dbg_str === "noRegister_warning" &&
			    (    !isset($global_SESSION_noRegister_SHOWEN) ||
			         !$global_SESSION_noRegister_SHOWEN              )   )
			{
			    $global_SESSION_noRegister_SHOWEN= true;
			}
			if($dbg_str)
			{
				$HTML_CLASS_DEBUG_CONTENT= true;
				if(is_string($dbg_str))
				{
				    if( $dbg_str == "db.statement.update" ||
				        $dbg_str == "db.statement.insert"   )
				    {
				        $dbg_str.= "/db.statement.modify";
				    }elseif($dbg_str == "db.statement.modify")
				        $dbg_str.= "/db.statement.update/db.statement.insert";
					if($HTML_CLASS_DEBUG_CONTENT_CLASS_FUNCTION)
						$HTML_CLASS_DEBUG_CONTENT_CLASS_FUNCTION.= "/".$dbg_str;
					else
						$HTML_CLASS_DEBUG_CONTENT_CLASS_FUNCTION= $dbg_str;
				}
			}else
			{
				$HTML_CLASS_DEBUG_CONTENT= false;
				$HTML_CLASS_DEBUG_CONTENT_CLASS_FUNCTION= "";
			}
		}
		public static function test_tagClassAttributeLinks(string $type, string $class, string $attribute)
		{
			global $global_selftable_test_links;

			if(preg_match("/^###/", $class))
				$global_selftable_test_links[$type][$class][]= $attribute;
			else
				$global_selftable_test_links[$type][$class]= $attribute;
		}
		private static function print_query_post()
		{
			global	$HTTP_GET_VARS,
					$HTTP_POST_FILES,
					$HTTP_POST_VARS,
					$HTTP_COOKIE_VARS,
					$HTML_CLASS_DEBUG_CONTENT_PRINT_QUERY;

			if(!STCheck::isDebug("query"))
				return;
			if(!$HTML_CLASS_DEBUG_CONTENT_PRINT_QUERY)
			{
				if(isset($HTTP_GET_VARS["stget"]["nr"]))
					return;
				if(count($HTTP_GET_VARS))
				{
					echo "incoming <b>QUERY-STRING:</b>";
					st_print_r($HTTP_GET_VARS, 20);
				}else
					echo "no incoming <b>QUERY-STRING</b><br />";
				if(count($HTTP_POST_VARS))
				{
					$post= $HTTP_POST_VARS;
					if(	isset($post["doLogin"])
						and
						isset($post["pwd"]))
					{
						$len= strlen($post["pwd"]);
						$post["pwd"]= str_repeat("*", $len);
					}
					echo "incoming <b>POST-VARS:</b>";
					st_print_r($post, 20);
				}else
					echo "no incoming <b>POST-VARS</b><br />";
				if(	Tag::isDebug("cookie")
					and
					count($HTTP_COOKIE_VARS)	)
				{
					echo "seting <b>COOKIE-VARS:</b>";
					st_print_r($HTTP_COOKIE_VARS, 20);
				}
				if( isset($HTTP_POST_FILES) &&
				    is_array($HTTP_POST_FILES) &&
				    count($HTTP_POST_FILES)         )
				{
					echo "incoming <b>POST-FILES:</b>";
					st_print_r($HTTP_POST_FILES, 20);
				}
				echo "<br />\n";
				$HTML_CLASS_DEBUG_CONTENT_PRINT_QUERY= true;
			}
		}		
		private static function array_key_filter(string $dbg_str) : string
		{
		    global $__stdbtables_statement_count;

		    foreach($__stdbtables_statement_count as $type=>$nr)
		    {
		        if(preg_match("/^$type/", $dbg_str))
		            return $type;
		    }
		    return "";
		}
		/**
         * increase the debugging inClassFunction string.<br />
         * <b>WARNING:</b> do not set increasing inside any
         * if-sentence of <code>if( STChek::isDebug() )</code>
         * (this will not always reach the increasing!!)<br />
         * (the inclassFunction string has also be defined inside $__stdbtables_statement_count)
         * 
         * @param string $dbg_str definition for all debugging strings which should output on screen
         * @return number of increasing
		 */
		public static function increase(string $dbg_str) : int
		{
		    global $__stdbtables_statement_count;
		    
            if(!STCheck::warning(!isset($__stdbtables_statement_count[$dbg_str]),
                    "STCheck::incfrease()", "cannot increase [$dbg_str] debugging", 1))
            {
                $__stdbtables_statement_count[$dbg_str]++;
                return $__stdbtables_statement_count[$dbg_str];
            }
            return 0;
		}
		public static function getIncreaseNr(string $dbg_str) : int
		{
		    global $__stdbtables_statement_count;
		    
		    if(!STCheck::warning(!isset($__stdbtables_statement_count[$dbg_str]),
		        "STCheck::incfrease()", "debug string <b>$dbg_str</b> is not defined for increasing", 1))
		    {
		        return $__stdbtables_statement_count[$dbg_str];
		    }
		    return 0;
		}
		public static function isDebug($dbg_str= null)
		{
			global $HTML_CLASS_DEBUG_CONTENT,
			       $HTML_CLASS_DEBUG_CONTENT_CLASS_FUNCTION,
			       $__stdbtables_statement_count_from,
			       $__stdbtables_statement_count_to,
			       $__stdbtables_statement_count;

			if(!$HTML_CLASS_DEBUG_CONTENT)
				return false;
			if(is_string($dbg_str))
			{
			    if($HTML_CLASS_DEBUG_CONTENT_CLASS_FUNCTION != "")
			    {
			        $isType= STCheck::array_key_filter($dbg_str);
			        if(0) //$dbg_str == "db.statement.update" || $dbg_str == "db.statement.modify")
			        {
			            echo __FILE__.__LINE__."<br>";
			            echo "debug  string:'$dbg_str'<br />";
			            echo "allow strings:'$HTML_CLASS_DEBUG_CONTENT_CLASS_FUNCTION'<br />";
			            echo "isType:";st_print_r($isType);
			            echo "<br />preg_match('/(^|\/)".$dbg_str."/i', '".$HTML_CLASS_DEBUG_CONTENT_CLASS_FUNCTION."')<br />";
			            echo "count ";st_print_r($__stdbtables_statement_count);
			            echo " from:";st_print_r($__stdbtables_statement_count_from);
			            echo " to:";st_print_r($__stdbtables_statement_count_to);echo "<br>";
			        }
			        if( $isType !== "" &&
			            (   (   isset($__stdbtables_statement_count_from[$isType]) &&
			                    $__stdbtables_statement_count[$isType] < $__stdbtables_statement_count_from[$isType]  ) ||
			                (   isset($__stdbtables_statement_count_to[$isType]) &&
			                    $__stdbtables_statement_count[$isType] > $__stdbtables_statement_count_to[$isType]   )    )   )
			        {
			            return false;
			        }
    			    if(	preg_match("/(^|\/)".$dbg_str."/i", $HTML_CLASS_DEBUG_CONTENT_CLASS_FUNCTION)   )
    				{//echo " true<br />";
    					return true;
    				}
			    }
				//echo " false<br />";
				return false;
			}
			return true;
		}
		public static function echoSpace(int $count)
		{
		    echo STCheck::getSpaces($count);
		}
		/**
		 * Debug message which should be showen on screen.<br />
		 * Only when debugging be set for defined <code>$dbg_str</code>
		 * 
		 * @param string $dbg_str for which debug output the string should be displayed
		 * @param string $string the debug message
		 * @param string $break whether should be made an carage return after the displayed string
		 * @return number count of intented spaces
		 */
		public static function echoDebug(string $dbg_str, $string= null, $break= true)
		{
			global $global_set_DEBUG_onLine_byFirst;

			if(!STCheck::isDebug())
				return 0;
			STCheck::print_query_post();
			if(STCheck::isDebug($dbg_str))
			{
    			if($string===null)
    			{
    				//if($HTML_CLASS_DEBUG_CONTENT===true)
    				//	echo $dbg_str;
    			//echo "&gt;&gt;";
    				echo "<br />";
    				return;
    			}
    			$backtrace= array();
    			$pref= "";
    			$space= 0;
				if($string && phpVersionNeed("4.3.0"))
				{
					$backtrace= debug_backtrace();
					$line= $backtrace[0]["line"];
					preg_match("/([^\\\\\/]+)$/", $backtrace[0]["file"], $ereg);
					$file= $ereg[1];
					$pref= "<b>[</b>$dbg_str<b>]</b> ";
					$pref.= "<b>file:</b>".$file." <b>line:</b>".$line." <b>:</b> ";
					$space+= STCheck::countHtmlCode($pref);
				}
				if(!isset($string))
				    $outString= "( -NULL- )";
				else if(is_string($string))
				    $outString= $string;
				elseif(is_array($string))
				{
				    if(!empty($string))
				    {
    				    $key= array_key_first($string);    				    
    				    $outString= $string[$key];
    				    unset($string[$key]);
				    }else
				        $outString= "array( -empty- )";
				}
				$space= STCheck::writeIntentedLineB(count($backtrace), $pref, $outString, /*deep*/0, $break) + $space;
				if( !is_string($outString) ||
				    trim($outString) == ""      )
				{
				    $space+= 1;
				}
				if(is_array($string))
				{
				    $spaces= STCheck::getSpaces($space);
				    foreach($string as $row)
				        echo $spaces.$row."<br>";
				    echo "<br>";
				}
				return $space;
			}
			return 0;
		}
		public static function getSpaces($num)
		{
			$sp= "";
			for($i= 0; $i<$num; $i++)
			    $sp.= "&#160;";
			return $sp;
		}
		static function flog($message= "", $pos= 0)
		{
			global $global_last_backtrace;
			global $global_logfile_dataname;
			global $global_bOpenErrWritten;

			if(!STCheck::isDebug("log"))
				return;
			$first= ++$pos;
			$msg= "";
			$fd= @fopen($global_logfile_dataname, "a");
			if(	$fd == false )				
			{
				if($global_bOpenErrWritten == false)
				{
					echo "### WARNING: cannot open file '$global_logfile_dataname' to write debug information.<br />";
					echo "             maybe permission of current directory isn't set<br />";
					$global_bOpenErrWritten= true;
				}
			}else
				$global_bOpenErrWritten= false;
			if(phpVersionNeed("4.3.0"))
			{
				$backTrace= debug_backtrace();
				$count= count($backTrace);
				$calcCount= $count;
				$count2= count($global_last_backtrace);
				//echo "$count to $count2<br>";
				--$count2;
				for($o= $count-1; $o >= 0; --$o)
				{
					if($count2 < 0)
						break;
					if(	$backTrace[$o]["line"] !== $global_last_backtrace[$count2]["line"]
						||
						$backTrace[$o]["file"] !== $global_last_backtrace[$count2]["file"]
						||
						(	is_array($global_last_backtrace[$count2]["args"])
							&&
							is_array($backTrace[$o]["args"])
							&&
							count(array_diff($backTrace[$o]["args"], $global_last_backtrace[$count2]["args"]))	)	)
					{
				/*		if(	$backTrace[$o]["line"] === $global_last_backtrace[$count2]["line"]
							||
							$backTrace[$o]["file"] === $global_last_backtrace[$count2]["file"]	)
						{
							echo $backTrace[$o]["function"];
							st_print_r($backTrace[$o]["args"]);
							echo $global_last_backtrace[$count2]["function"];
							st_print_r($global_last_backtrace[$count2]["args"]);
							$stop= true;
						}*/
						$count= $o;
						break;
					}
					//echo $backTrace[$o]["line"]." is ".$global_last_backtrace[$count2]["line"]."<br>";
					--$count2;
				}
				if($o === -1)
					$count= 1;
				if($pos < $first)
					$pos= $first;

				//echo "starts by count $count<br>";
				//st_print_r($global_last_backtrace,2);
				//echo "[$count]$pos\n";
				//st_print_r($backTrace, 2);
				//echo "show row ".$pos." to ".($count)."<br>";
				for($o= $count; $o >= $pos; --$o)
				{
					if(isset($backTrace[$o]["file"]))
						$split= preg_split("/[\\\\]/", $backTrace[$o]["file"]);
					else
						$split= array();

					//echo $backTrace[$o]["function"]." file:".$split[count($split)-1]." line:".$backTrace[$o][line]."\n";
					$msg= "";
					$msg.= STCheck::getSpaces($calcCount - $o - 1);
					$msg.= "[".($calcCount - $o)."]";
					$msg.= $backTrace[$o-1]["function"]."(";
					$fmsg= "";
					if(is_array($backTrace[$o-1]["args"]))
					{
						foreach($backTrace[$o-1]["args"] as $key=>$arg)
						{
							if(is_array($arg))
							{
								$fmsg.= "array( -";
								if(count($arg) == 0)
									$fmsg.= "skip";
								else
									$fmsg.= "empty";
								$fmsg.= "- )";
							}elseif(is_object($arg))
								$fmsg.= "object('". get_class($arg)."')";
							elseif(is_string($arg))
								$fmsg.= "\"".$arg."\"";
							else
								$fmsg.= $arg;
							$fmsg.= ", ";
						}
						if($fmsg !== "")
							$fmsg= substr($fmsg, 0, strlen($fmsg)-2);
					}
					$msg.= $fmsg.") ";
					$msg.= "file:";
					if(count($split) > 0)
						$msg.= $split[count($split)-1]." ";
					else
						$msg.= "--- ";
					$msg.= "line:".$backTrace[$o-1]["line"]." ";
					if($message !== "" && $o == $pos)
						$msg.= "message: ".$message;
					$msg.= "\n";
					@fwrite($fd, $msg);
				}
			}else
			{
				$msg= "message: ".$message."\n";
				fwrite($fd, $msg);
			}

			@fwrite($fd, "\n");
			@fclose($fd);
			//if($stop)
			//	exit();
		}
}

/*
 *
 * 			******************************************
 * 			 *   STCheck::debug("<definitions>");   *
 * 			  *************************************
 *
 * true					-	if this boolean be set
 * 							STCheck checks all methods with params
 * 							and the html-output by the client is calibrated.
 * 							This boolean is also be set by defining the following strings
 *
 * db.statment			-	show all statments sending to database
 * db.statments.time	-	show time of waiting on database
 * db.statments.aliases	-	creating of aliases for table
 * db.statments.select	-	creating of select-statment which columns are needed
 * db.statments.table	-	creating join statment
 * db.statments.where	-	creating where statment
 *
 * performance			-	see start- and endtime by creating page
 * table				-	creating of table objects (STDbTable)
 * container			-	creating of container objects (STObjectContainer / STBaseContainer)
 *
 */

?>