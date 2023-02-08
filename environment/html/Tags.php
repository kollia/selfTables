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

		function __construct(string $name, bool $bEndTag, string $class= null)
		{
			$this->tag= $name;
			$this->bEndTag= $bEndTag;
			$this->class($class);
			$this->inherit= array();

			$this->aNames= array();
			if($class)
				$this->insertAttribute("class", $class);
		}
		function id($name)
		{
		    $this->insertAttribute("id", $name);
		}
		function class($name)
		{
		    $this->insertAttribute("class", $name);
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
			STCheck::is_warning($nParams>$nLast, "Tag::paramCheck()", "function has no more than ".$count." params", 1);
		}
		private function spez(bool &$require) : string
		{
		    $displayString= "";
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
					    $displayString.= " ".$key;
						if(isset($value))
						    $displayString.= "=\"".$value."\"";
					}
				}
			}
			return $displayString;
		}
		public function insertAttribute($name, $term)
		{
			$this->aNames[$name]= $term;
		}
		public function hasAttribut(string $name) : bool
		{
		    foreach($this->aNames as $key => $value)
		    {
		        if($key == $name)
		            return true;
		    }
		    return false;
		}
		protected function spaces($num)
		{
		    $displayString= "\n";
			for($i= 0; $i<$num; $i++)
			    $displayString.= "  ";
			return $displayString;
		}
		protected function getBevorSubTagString()
		{//funktion zum �berladen
		}
		protected function getBehindSubTagString()
		{//funktion zum �berladen
		}
		public function display()
		{
		    echo $this->getDisplayString();
		}
		public function getDisplayString()
		{
			global 	$tag_spaces,
					$HTML_CLASS_DEBUG_CONTENT,
					$HTML_CLASS_DEBUG_CONTENT_SHOWN;

            $displayString= "";
            
			if($HTML_CLASS_DEBUG_CONTENT)
			{
			    $displayString.= $this->spaces($tag_spaces);
				if(!$HTML_CLASS_DEBUG_CONTENT_SHOWN)
				{
      				$displayString.= "</pre></td></tr></table>";
    				$displayString.= "<table width='100%' bgcolor='white'><tr><td>";
    				$displayString.= "<center>this side is set for <b>DEBUG-session</b> ";
      				$displayString.= "(STCheck::debug(<font color='blue'>true</font>))</center>";
					$displayString.= "</td></tr></table>";
					$HTML_CLASS_DEBUG_CONTENT_SHOWN= true;
				}
			}
			$displayString.= $this->startTag();
			if(!$this->bEndTag)
			    return $displayString;
			$displayString.= $this->getBevorSubTagString();
			foreach($this->inherit as $tag)
			{
        		if($HTML_CLASS_DEBUG_CONTENT)
					$tag_spaces++;
				if(is_String($tag) or is_numeric($tag))
				{
            		if($HTML_CLASS_DEBUG_CONTENT)
						$displayString.= $this->spaces($tag_spaces);
					$displayString.= $tag; //htmlspecialchars($tag);
				}else
				{
					if($HTML_CLASS_DEBUG_CONTENT and !is_subclass_of($tag, "TAG") and !$this->isScript)
					{
					    echo $displayString;
						echo "\n<br><b>ERROR:</b> bei den HTML-Tags d�rfen nur Strings und HTML-Tags hinzugef�gt werden<br>\n";
						st_print_r($tag);
						exit();
					}
					$displayString.= $tag->getDisplayString();
				}
        		if($HTML_CLASS_DEBUG_CONTENT)
					$tag_spaces--;
			}
			$displayString.= $this->getBehindSubTagString();
			if($HTML_CLASS_DEBUG_CONTENT)
			    $displayString.= $this->spaces($tag_spaces);
			$displayString.= $this->endTag();
			return $displayString;
		}
		protected function startTag()
		{
		    $displayString= "<";
		    $displayString.= $this->tag;
			$require= false;
			$displayString.= $this->spez($require);
			if(!$this->bEndTag)
			    $displayString.= " /";
			$displayString.= ">";
			if($require)
				$displayString.= $this->spez($require);
			return $displayString;
		}
		protected function endTag()
		{
		    $displayString= "</";
		    $displayString.= $this->tag;
		    $displayString.= ">";
		    return $displayString;
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
					STCheck::is_warning(1, "Tag::add()", "in TableTag should be only insert an RowTag");
					$showWarning= true;
				}
				if(	typeof($this, "RowTag")
					and
					!typeof($tag, "ColumnTag", "null")	)
				{
					STCheck::is_warning(1, "Tag::add()", "in RowTag should be only insert an ColumnTag");
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
					STCheck::is_warning(1, "Tag::add()", "in TableTag should be only insert an RowTag");
					$showWarning= true;
				}
				if(	typeof($this, "RowTag")
					and
					!typeof($tag, "ColumnTag", "null")	)
				{
					STCheck::is_warning(1, "Tag::add()", "in RowTag should be only insert an ColumnTag");
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
					STCheck::is_warning(1, "Tag::add()", "in TableTag should be only insert an RowTag");
					$showWarning= true;
				}
				if(	typeof($this, "RowTag")
					and
					!typeof($tag, "ColumnTag", "null")	)
				{
					STCheck::is_warning(1, "Tag::add()", "in RowTag should be only insert an ColumnTag");
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
					STCheck::is_warning(1, "Tag::add()", "in TableTag should be only insert an RowTag");
				}
				if(	typeof($this, "RowTag")
					and
					!typeof($tag, "ColumnTag", "null")	)
				{
					STCheck::is_warning(1, "Tag::add()", "in RowTag should be only insert an ColumnTag");
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
					STCheck::is_warning(1, "Tag::add()", "in TableTag should be only insert an RowTag");
					$showWarning= true;
				}
				if(	typeof($this, "RowTag")
					and
					!typeof($tag, "ColumnTag", "null")	)
				{
					STCheck::is_warning(1, "Tag::add()", "in RowTag should be only insert an ColumnTag");
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
					STCheck::is_warning(1, "Tag::add()", "in TableTag should be only insert an RowTag");
					$showWarning= true;
				}
				if(	typeof($this, "RowTag")
					and
					!typeof($tag, "ColumnTag", "null")	)
				{
					STCheck::is_warning(1, "Tag::add()", "in RowTag should be only insert an ColumnTag");
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
require_once($_stenvironmenttools_path."/html/FrameSetTag.php");

?>