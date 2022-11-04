<?php

require_once($_stquerystring);

$member_tag_def= false;
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

class STCheck
{
	var $m_bOpenErr= false;
	
	private static function error_message($symbol, $trigger, $functionName, $message, $outFunc)
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
			showErrorTrace($outFunc+3, 10);
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
			showErrorTrace($fromFile);
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
		for($n= 0; $n < $backtraceCount; ++$n)
			echo "&#160;";
		echo $prefStr;
		if(	is_array($value) ||
			is_object($value)	)
		{
			$intent= $backtraceCount + STCheck::countHtmlCode($prefStr);
			st_print_r($value, $deep, $intent);
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
		static function warning($trigger, $functionName, $message, $outFunc= 0)
		{
			STCheck::deprecated("STCheck::is_warning()", "STCheck::warning()");
			STCheck::is_warning($trigger, $functionName, $message, $outFunc= 0);
		}
		static function is_warning($trigger, $functionName, $message, $outFunc= 0)
		{
			if(!STCheck::isDebug())
			{
				if($trigger)
					return true;
				return false;
			}
			return STCheck::error_message("Warning", $trigger, $functionName, $message, $outFunc);
		}
		/*
		 * 2021/07/29 alex: change function from error() to is_error() for php8 compatibility
		 * 					with STDatabase class where an error function
		 * 					be with no parameters
		 */
		public static function is_error($trigger, $functionName, $message, $outFunc= 0)
		{
			if(!STCheck::isDebug())
			{
				if($trigger)
					return true;
				return false;
			}
			return STCheck::error_message("Error", $trigger, $functionName, $message, $outFunc);
		}
		public static function alert($trigger, $functionName, $message, $outFunc= 0)
		{
			STCheck::deprecated("STCheck::is_alert()", "STCheck::alert()");
			STCheck::is_alert($trigger, $functionName, $message, $outFunc);
		}
		public static function is_alert($trigger, $functionName, $message, $outFunc= 0)
		{
			if(!Tag::isDebug())
			{
				if($trigger)
					return true;
				return false;
			}
			if(Tag::error_message("Fatal Error", $trigger, $functionName, $message, $outFunc))
				exit;
			return false;
		}
		static function deprecated($newFunction, $oldFunction= null)
		{
			if(Tag::isDebug("deprecated"))
			{
				Tag::error_message("deprecated", true, $oldFunction, " -> take newer: $newFunction", 1);
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
			Tag::warning($nParams>$nLast, "Tag::paramCheck()", "function has no more than ".$count." params", 1);
		}
		public static function paramCheck($param, $paramNr, $type1, $type2= null, $type3= null)
		{//echo "function paramCheck($param, $paramNr, $type1, $type2)<br />";
			--$paramNr;
			$params= func_get_args();

			return STCheck::param($param, $paramNr, $params);
		}
		public static function parameter($param, $paramNr, $type1, $type2= null, $type3= null)
		{//echo "function paramCheck($param, $paramNr, $type1, $type2)<br />";
			--$paramNr;
			$params= func_get_args();

			return STCheck::param($param, $paramNr, $params);
		}
		public static function param($param, $paramNr, $type1, $type2= null, $type3= null)
		{	//echo "function param(";st_print_r($param, 0);
			//			echo ", ";st_print_r($paramNr, 0);
			//			echo ", ";st_print_r($type1, 0); 
			//			echo ", ";st_print_r($type2, 0);
			//			echo "<br />";
		    global $member_tag_def;
			if(!Tag::isDebug())
				return;
			//showErrorTrace();echo "<br><br>";
			if(!is_numeric($paramNr))
			{
				STCheck::error_message("Error in parameter", true, "STCheck::param()",
									"2. parameter(=".$paramNr.") can only be an number", 0);
				exit();
			}
			if(is_array($type1))
				$args= $type1;
			else
				$args= func_get_args();
			if($args[2]=="check")
			{
				STCheck::warning((!isset($args[3])||!is_bool($args[3])), "STCheck::param()", 
					"fourth parameter not be set, or no correct boolean");
				$bError= false;
				if(count($args)<4)
					$bError= true;
				$begin= 4;
			}else
			{
				$types= array();
				$empty= array();
				$c= count($args);
				for($n= 2; $n<$c; $n++)
				{
					if(preg_match("/^empty\((.+)\)$/i", $args[$n], $preg))
						$empty[trim($preg[1])]= "empty";
					else
						$types[]= $args[$n];
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
					for($n= $begin; $n<($c-1); $n++)
						$types.= $args[$n].", ";
					$types= substr($types, 0, strlen($types)-2)." or ".$args[$c-1];
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
				}
				elseif(is_object($param))
					$param= "object(".get_class($param).")";
				elseif(is_array($param))
				{					
					$param= "Array()";
				}
				STCheck::error_message("Error in parameters", true, "Tag::paramCheck()",
									$count." parameter(=".$param.") can be ".$types, 0);
				exit();
			}
		}
		//function debug($a,$b,$boolean= true)
		public static function debug($boolean= true)
		{
			global	$HTTP_POST_VARS,
					$HTTP_POST_FILES,
					$HTTP_COOKIE_VARS,
					$HTML_CLASS_DEBUG_CONTENT,
					$HTML_CLASS_DEBUG_CONTENT_CLASS_FUNCTION,
					$global_logfile_dataname;
			
			if($boolean !== false)
				error_reporting(E_ALL);
			if(	!$HTML_CLASS_DEBUG_CONTENT
				and
				$boolean	)
			{
				$param= new STQueryString();
				$HTTP_GET_VARS= $param->getArrayVars();
				echo "\n<table bgcolor='white'><tr><td><pre>\n";
				if(file_exists($global_logfile_dataname))
					@unlink($global_logfile_dataname);
			}
			Tag::print_query_post();
			if(	$HTML_CLASS_DEBUG_CONTENT
				and
				!$boolean	)
			{
				echo "\n</pre>\n";
			}
			if($boolean)
			{
				$HTML_CLASS_DEBUG_CONTENT= true;
				if(is_string($boolean))
				{
					if($HTML_CLASS_DEBUG_CONTENT_CLASS_FUNCTION)
						$HTML_CLASS_DEBUG_CONTENT_CLASS_FUNCTION.= "/".$boolean;
					else
						$HTML_CLASS_DEBUG_CONTENT_CLASS_FUNCTION= $boolean;
				}
			}else
			{
				$HTML_CLASS_DEBUG_CONTENT= false;
				$HTML_CLASS_DEBUG_CONTENT_CLASS_FUNCTION= "";
			}
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
					echo "incomming <b>QUERY-STRING:</b>";
					st_print_r($HTTP_GET_VARS, 20);
				}else
					echo "no incomming <b>QUERY-STRING</b><br />";
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
					echo "incomming <b>POST-VARS:</b>";
					st_print_r($post, 20);
				}else
					echo "no incomming <b>POST-VARS</b><br />";
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
					echo "incomming <b>POST-FILES:</b>";
					st_print_r($HTTP_POST_FILES, 20);
				}
				echo "<br />\n";
				$HTML_CLASS_DEBUG_CONTENT_PRINT_QUERY= true;
			}
		}
		public static function isDebug($inClassFunction= null)
		{
			global $HTML_CLASS_DEBUG_CONTENT;
			global $HTML_CLASS_DEBUG_CONTENT_CLASS_FUNCTION;

			if(!$HTML_CLASS_DEBUG_CONTENT)
				return false;
			if(is_string($inClassFunction))
			{
			    if($HTML_CLASS_DEBUG_CONTENT_CLASS_FUNCTION != "")
			    {
    				//echo "inClass ".$inClassFunction."=".$HTML_CLASS_DEBUG_CONTENT_CLASS_FUNCTION."<br />";;
    				//echo "<br />preg_match('/(^|\/)".$inClassFunction."/i', '".$HTML_CLASS_DEBUG_CONTENT_CLASS_FUNCTION."')";
    			    if(	preg_match("/(^|\/)".$inClassFunction."/i", $HTML_CLASS_DEBUG_CONTENT_CLASS_FUNCTION)   )
    				{//echo " true<br />";
    					return true;
    				}
			    }
				//echo " false<br />";
				return false;
			}
			return true;
		}
		/**
		 * Debug message whitch should be showen on screen.<br />
		 * Only when debugging be set for defined <code>$inClassFunction</code>
		 * 
		 * @param string $inClassFunction for which debug output the string should be displayed
		 * @param string $string the debug message
		 * @param string $break whether should be made an carage return after the displayed string
		 * @return number count of intented spaces
		 */
		public static function echoDebug($inClassFunction, $string= null, $break= true)
		{
			global $HTML_CLASS_DEBUG_CONTENT;

			if(!STCheck::isDebug())
				return 0;
			STCheck::print_query_post();
			if(STCheck::isDebug($inClassFunction))
			{
    			if($string===null)
    			{
    				//if($HTML_CLASS_DEBUG_CONTENT===true)
    				//	echo $inClassFunction;
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
					$pref= "<b>[</b>$inClassFunction<b>]</b> ";
					$pref.= "<b>file:</b>".$file." <b>line:</b>".$line." <b>:</b> ";
					$space+= STCheck::countHtmlCode($pref);
				}
				return STCheck::writeIntentedLineB(count($backtrace), $pref, $string, /*deep*/0, $break) + $space + 1;
			}
			return 0;
		}
		function getSpaces($num)
		{
			$sp= "";
			for($i= 0; $i<$num; $i++)
				$sp.= " ";
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