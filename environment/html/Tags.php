<?php

require_once($_stcheck);

$tag_spaces= 0;
$tagCount= 0;

$HTML_TAG_CONTAINER= null;

class Tag extends STCheck
{
		var $tag;
		var $bEndTag;
		var $inherit;
		var $aNames;
		var $isScript= false;

		function __construct($name, $bEndTag, $class= null)
		{
/*			global $tagCount;
			$tagCount++;
			echo "create $tagCount $name-Tag<br />";*/
			$this->tag= $name;
			$this->bEndTag= $bEndTag;
			$this->class= $class;
			$this->inherit= array();

			$this->aNames= array();
			if($class)
				$this->insertAttribute("class", $class);
		}
		protected static function error_message($symbol, $trigger, $functionName, $message, $outFunc)
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
				showErrorTrace($outFunc+3, 1);
				return true;
			}
			return false;
		}
		static function warning($trigger, $functionName, $message, $outFunc= 0)
		{
			return Tag::error_message("Warning", $trigger, $functionName, $message, $outFunc);
		}
		function error($trigger, $functionName, $message, $outFunc= 0)
		{
			return Tag::error_message("Error", $trigger, $functionName, $message, $outFunc);
		}
		public static function alert($trigger, $functionName, $message, $outFunc= 0)
		{
			if(Tag::error_message("Fatal Error", $trigger, $functionName, $message, $outFunc))
				exit;
			return false;
		}
		static function lastParam($nLast, $nParams)
		{
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
		//function debug($a,$b,$boolean= true)
		public static function debug($boolean= true)
		{
			global	$HTTP_GET_VARS,
					$HTTP_POST_VARS,
					$HTTP_POST_FILES,
					$HTML_CLASS_DEBUG_CONTENT,
					$HTML_CLASS_DEBUG_CONTENT_CLASS_FUNCTION,
					$_COOKIE,
					$_SESSION;

			if($boolean !== false)
				error_reporting(E_ALL);
			if(	!$HTML_CLASS_DEBUG_CONTENT
				and
				$boolean
				and
				Tag::isDebug("query")			)
			{
				echo "\n<table bgcolor='white'><tr><td><pre>\n";
				if(count($HTTP_GET_VARS))
				{
					echo "incomming <b>GET-VARS:</b>";
					st_print_r($HTTP_GET_VARS, 20);
				}else
					echo "no incomming <b>GET-VARS</b><br />";
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
				if(	count($_COOKIE)	)
				{
					echo "seting <b>COOKIE-VARS:</b>";
					st_print_r($_COOKIE, 20);
				}
				if( count($_SESSION)	)
				{
					echo "setting <br>SESSION-VARS:</b>";
					st_print_r($_SESSION, 20);
				}
				if(count($HTTP_POST_FILES))
				{
					echo "incomming <b>POST-FILES:</b>";
					st_print_r($HTTP_POST_FILES, 20);
				}
				echo "<br />\n";
			}
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
		public static function isDebug($inClassFunction= null)
		{
			global $HTML_CLASS_DEBUG_CONTENT;
			global $HTML_CLASS_DEBUG_CONTENT_CLASS_FUNCTION;
			
			if(!$HTML_CLASS_DEBUG_CONTENT)
				return false;
			if(is_string($inClassFunction))
			{
				//echo "inClass ".$inClassFunction."=".$HTML_CLASS_DEBUG_CONTENT_CLASS_FUNCTION."<br />";;
				//echo "<br />preg_match('/(^|\/)".$inClassFunction."/i', '".$HTML_CLASS_DEBUG_CONTENT_CLASS_FUNCTION."')";
				if(	preg_match("/(^|\/)".$inClassFunction."/i", $HTML_CLASS_DEBUG_CONTENT_CLASS_FUNCTION))
				{//echo " true<br />";
					return true;
				}
				//echo " false<br />";
				return false;
			}
			return true;
		}
		function spez($require= false)
		{
			$include= $require;
			foreach($this->aNames as $key => $value)
			{
				if(substr($key, 0, 1)=="#")
				{
					if($include==true)
					{
						global	$HTTP_GET_VARS,
								$HTTP_POST_VARS;

						if(preg_match("/#include/i", $key))
							include($value);
						elseif(preg_match("/#require/i", $key))
							require($value);
						elseif(preg_match("/#require_once/i", $key))
							require_once($value);
					}
					$require= true;
				}else
				{
					if($include==false)
					{
						echo " ".$key;
						if(isset($value))
							echo "=\"".$value."\"";
					}
				}
			}
			return $require;
		}
		function insertAttribute($name, $term)
		{
			$this->aNames[$name]= $term;
		}
		function spaces($num)
		{
			echo "\n";
			for($i= 0; $i<$num; $i++)
				echo "  ";
		}
		function bevorSubTags()
		{//funktion zum �berladen
		}
		function behindSubTags()
		{//funktion zum �berladen
		}
		function display()
		{
			global 	$tag_spaces,
					$HTML_CLASS_DEBUG_CONTENT,
					$HTML_CLASS_DEBUG_CONTENT_SHOWN;


			if($HTML_CLASS_DEBUG_CONTENT)
			{
				$this->spaces($tag_spaces);
				if(!$HTML_CLASS_DEBUG_CONTENT_SHOWN)
				{
  				echo "</pre></td></tr></table>";
				echo "<table width='100%' bgcolor='white'><tr><td>";
				echo "<center>this side is set for <b>DEBUG-session</b> ";
  				echo "(STCheck::debug(<font color='blue'>true</font>))</center>";
					echo "</td></tr></table>";
					$HTML_CLASS_DEBUG_CONTENT_SHOWN= true;
				}
			}
			echo $this->startTag();
			if(!$this->bEndTag)
				return;
			$this->bevorSubTags();
			foreach($this->inherit as $tag)
			{
        		if($HTML_CLASS_DEBUG_CONTENT)
					$tag_spaces++;
				if(is_String($tag) or is_numeric($tag))
				{
            		if($HTML_CLASS_DEBUG_CONTENT)
						$this->spaces($tag_spaces);
					echo $tag; //htmlspecialchars($tag);
				}else
				{
					if($HTML_CLASS_DEBUG_CONTENT and !is_subclass_of($tag, "TAG") and !$this->isScript)
					{
						echo "\n<br><b>ERROR:</b> bei den HTML-Tags d�rfen nur Strings und HTML-Tags hinzugef�gt werden<br>\n";
						st_print_r($tag);
						exit();
					}
					$tag->display();
				}
        		if($HTML_CLASS_DEBUG_CONTENT)
					$tag_spaces--;
			}
			$this->behindSubTags();
			if($HTML_CLASS_DEBUG_CONTENT)
				$this->spaces($tag_spaces);
			echo $this->endTag();
		}
		function startTag()
		{
			echo "<";
			echo $this->tag;
			$require= $this->spez();
			if(!$this->bEndTag)
				echo " /";
			echo ">";
			if($require)
				$this->spez(true);
		}
		function endTag()
		{
			echo "</";
			echo $this->tag;
			echo ">";
		}
		function addBefore($tag)
		{//echo get_class($tag)."<br />";
			$showWarning= false;
			if(Tag::isDebug())
			{
				if(	typeof($this, "TableTag")
					and
					( 	!typeof($tag, "RowTag", "null")
						and
						!typeof($tag, "FormTag", "null")	)	)
				{
					Tag::warning(1, "Tag::add()", "in TableTag should be only insert an RowTag");
					$showWarning= true;
				}
				if(	typeof($this, "RowTag")
					and
					!typeof($tag, "ColumnTag", "null")	)
				{
					Tag::warning(1, "Tag::add()", "in RowTag should be only insert an ColumnTag");
					$showWarning= true;
				}
			}
			$this->addObjBefore($tag, true);
		}
		function addObjBefore(&$tag, $showWarning= false)
		{//echo get_class($tag)."<br />";
			$showWarning= false;
			if(	Tag::isDebug()
				and
				!$showWarning	)
			{
				if(	typeof($this, "TableTag")
					and
					( 	!typeof($tag, "RowTag", "null")
						and
						!typeof($tag, "FormTag", "null")	)	)
				{
					Tag::warning(1, "Tag::add()", "in TableTag should be only insert an RowTag");
					$showWarning= true;
				}
				if(	typeof($this, "RowTag")
					and
					!typeof($tag, "ColumnTag", "null")	)
				{
					Tag::warning(1, "Tag::add()", "in RowTag should be only insert an ColumnTag");
					$showWarning= true;
				}
			}
			$inherit[]= &$tag;
			foreach($this->inherit as $key=>$value)
			{
				$inherit[]= &$this->inherit[$key];
			}
			$this->inherit= &$inherit;
		}
		function add($tag)
		{//echo get_class($tag)."<br />";
			$showWarning= false;
			if(1)
			{
				if(	typeof($this, "TableTag")
					and
					( 	!typeof($tag, "RowTag", "null")
						and
						!typeof($tag, "FormTag", "null")	)	)
				{
					Tag::warning(1, "Tag::add()", "in TableTag should be only insert an RowTag");
					$showWarning= true;
				}
				if(	typeof($this, "RowTag")
					and
					!typeof($tag, "ColumnTag", "null")	)
				{
					Tag::warning(1, "Tag::add()", "in RowTag should be only insert an ColumnTag");
					$showWarning= true;
				}
			}
			$this->addObj($tag, $showWarning);
		}
		function addObj(&$tag, $showWarning= false)
		{
			if(	Tag::isDebug()
				and
				!$showWarning	)
			{
				if(	typeof($this, "TableTag")
					and
					( 	!typeof($tag, "RowTag", "null")
						and
						!typeof($tag, "FormTag", "null")	)	)
				{
					Tag::warning(1, "Tag::add()", "in TableTag should be only insert an RowTag");
				}
				if(	typeof($this, "RowTag")
					and
					!typeof($tag, "ColumnTag", "null")	)
				{
					Tag::warning(1, "Tag::add()", "in RowTag should be only insert an ColumnTag");
				}
			}
			if($tag==null)
				return;
			if(!$this->bEndTag)
			{
				echo "\n<br>the tag <b>&lt;";
				echo $this->tag;
				echo "&gt;</b> can not inherit a tag</b><br>\n";
				exit;
			}
			$this->inherit[count($this->inherit)]= &$tag;
		}
		function addObjBehind($tagName, &$tag, $showWarning= false)
		{//echo get_class($tag)."<br />";
			$showWarning= false;
			if(	Tag::isDebug()
				and
				!$showWarning	)
			{
				if(	typeof($this, "TableTag")
					and
					( 	!typeof($tag, "RowTag", "null")
						and
						!typeof($tag, "FormTag", "null")	)	)
				{
					Tag::warning(1, "Tag::add()", "in TableTag should be only insert an RowTag");
					$showWarning= true;
				}
				if(	typeof($this, "RowTag")
					and
					!typeof($tag, "ColumnTag", "null")	)
				{
					Tag::warning(1, "Tag::add()", "in RowTag should be only insert an ColumnTag");
					$showWarning= true;
				}
			}
			$bInserted= false;
			$inherit= array();
			foreach($this->inherit as $key=>$value)
			{
				$inherit[]= &$this->inherit[$key];
				if(typeof($this->inherit[$key], $tagName))
				{
					$inherit[]= &$tag;
					$bInserted= true;
				}
			}
			if(!$bInserted)
				$inherit[]= &$tag;
			$this->inherit= &$inherit;
		}
		function addBehind($tagName, $tag)
		{//echo get_class($tag)."<br />";
			$showWarning= false;
			if(Tag::isDebug())
			{
				if(	typeof($this, "TableTag")
					and
					( 	!typeof($tag, "RowTag", "null")
						and
						!typeof($tag, "FormTag", "null")	)	)
				{
					Tag::warning(1, "Tag::add()", "in TableTag should be only insert an RowTag");
					$showWarning= true;
				}
				if(	typeof($this, "RowTag")
					and
					!typeof($tag, "ColumnTag", "null")	)
				{
					Tag::warning(1, "Tag::add()", "in RowTag should be only insert an ColumnTag");
					$showWarning= true;
				}
			}
			$this->addObjBehind($tagName, $tag, true);
		}
	function append($value)
	{
		Tag::paramCheck($value, 1, "Tag", "string", "null");

		// take Tag:: and not $this->
		// because if function addObj is overloaded
		// the compiler takes the new addObj funktion
		Tag::addObj($value);
	}
	function appendObj(&$value)
	{
		Tag::paramCheck($value, 1, "Tag", "string", "null");

		// take Tag:: and not $this->
		// because if function addObj is overloaded
		// the compiler takes the new addObj funktion
		Tag::addObj($value);
	}
		function clear()
		{
			$aRv= $this->inherit;
			$this->inherit= array();
			return $aRv;
		}
		function insideInclude($fileName)
		{
			$this->aNames["#include"]= $fileName;
		}
		function insideRequire($fileName)
		{
			$this->aNames["#require"]= $fileName;
		}
		function insideRequire_once($fileName)
		{
			$this->aNames["#require_once"]= $fileName;
		}
		function &getElementsByTagName($tagName)
    {
    	$aTags= array();
        $this->private_getElementByTagName($tagName, $aTags);
        return $aTags;
    }
		function &getElementByTagName($tagName, $count= 0)
		{
			return $this->private_getElementByTagName($tagName, $count);
		}
    private function &private_getElementByTagName($tagName, &$count)
    {
    	if(strtolower($tagName)==$this->tag)
        {
            if(is_array($count))
            {
    			$count[]= &$this;
            }else
            {
            	if($count==0)
                	return $this;
                else
                	$count--;
            }
        }
        $inheritCount= count($this->inherit);
        for($n= 0; $n<$inheritCount; $n++)
        {
        	$tag= &$this->inherit[$n];
            if(	is_object($tag) &&
            	is_subclass_of($tag, "Tag")	)
            {
            	$get= &$tag->private_getElementByTagName($tagName, $count);
                if($get)
                	return $get;
            }
        }
        $oRV= null;
		return $oRv;
	}
}

require_once($_stenvironmenttools_path."/html/HtmlTag.php");
require_once($_stenvironmenttools_path."/html/HeadTag.php");
require_once($_stenvironmenttools_path."/html/MetaTag.php");
require_once($_stenvironmenttools_path."/html/LinkTag.php");
require_once($_stenvironmenttools_path."/html/BodyTag.php");
require_once($_stenvironmenttools_path."/html/FontTag.php");
require_once($_stenvironmenttools_path."/html/BrTag.php");
require_once($_stenvironmenttools_path."/html/ATag.php");
require_once($_stenvironmenttools_path."/html/SpanTag.php");
require_once($_stenvironmenttools_path."/html/DivTag.php");
require_once($_stenvironmenttools_path."/html/ScriptTag.php");
require_once($_stenvironmenttools_path."/html/BTag.php");
require_once($_stenvironmenttools_path."/html/PTag.php");
require_once($_stenvironmenttools_path."/html/HTag.php");
require_once($_stenvironmenttools_path."/html/EmTag.php");
require_once($_stenvironmenttools_path."/html/CenterTag.php");
require_once($_stenvironmenttools_path."/html/TableTag.php");
require_once($_stenvironmenttools_path."/html/st_tableTag.php");
require_once($_stenvironmenttools_path."/html/ListingTags.php");
require_once($_stenvironmenttools_path."/html/TitleTag.php");
require_once($_stenvironmenttools_path."/html/FormTag.php");
require_once($_stenvironmenttools_path."/html/ImageTag.php");
require_once($_stenvironmenttools_path."/html/ButtonTag.php");
require_once($_stenvironmenttools_path."/html/InputTag.php");
require_once($_stenvironmenttools_path."/html/TextAreaTag.php");
require_once($_stenvironmenttools_path."/html/SelectTag.php");
require_once($_stenvironmenttools_path."/html/StyleTag.php");
require_once($_stenvironmenttools_path."/html/FieldSetTag.php");
require_once($_stenvironmenttools_path."/html/LegendTag.php");
require_once($_stenvironmenttools_path."/html/IFrameTag.php");

?>