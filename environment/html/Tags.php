<?php

require_once($_stcheck);

$tag_spaces= 0;
$tagCount= 0;

$HTML_TAG_CONTAINER= null;

class Tag extends STCheck
{
    /**
     * name of the tag
     * @var string $tag
     */    
	protected $tag;
	/**
	 * whether tag has an end tag
	 * @var boolean $bEndTag
	 */
	private $bEndTag;
	/**
	 * all tags inside this current tag
	 * @var array $inherit
	 */
	protected $inherit;
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
					$HTML_CLASS_DEBUG_CONTENT_SHOWN,
					$global_SESSION_noRegister_SHOWEN,
					$global_SESSION_noRegister_onLine,
					$global_set_DEBUG_onLine_byFirst;

            $displayString= "";
            
            if( $HTML_CLASS_DEBUG_CONTENT &&
                !typeof($this, "TextAreaTag")   )
            {
                $indention= true;
            }else
                $indention= false;
			if($indention)
			{
			    $displayString.= $this->spaces($tag_spaces);
				if(!$HTML_CLASS_DEBUG_CONTENT_SHOWN)
				{
      				$displayString.= "</pre></td></tr></table>";
    				$displayString.= "<table width='100%' bgcolor='white'><tr><td>";
    				$displayString.= "<center>this side is set for <b>DEBUG-session</b> ";
    				$displayString.= "(STCheck::debug(<font color='blue'>true</font>))";
					preg_match("/([^\\\\\/]+)$/", $global_set_DEBUG_onLine_byFirst['file'], $ereg);
					$file= $ereg[1];
					$line= $global_set_DEBUG_onLine_byFirst['line'];
					$displayString.= "<br />first on <b>file:</b>".$file." <b>line:</b>".$line." <b>";
    				if($global_SESSION_noRegister_SHOWEN)
    				{
    				    $displayString.= "<br /><b>WARNING:</b> SESSION set to noRegister <b>:WARNING</b><br />";
    				    $displayString.= $global_SESSION_noRegister_onLine;
    				}
      				$displayString.= "</center>";
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
        		if($indention)
					$tag_spaces++;
				if(is_String($tag) or is_numeric($tag))
				{
            		if($indention)
						$displayString.= $this->spaces($tag_spaces);
					$displayString.= $tag; //htmlspecialchars($tag);
				}else
				{
					if($indention and !is_subclass_of($tag, "TAG") and !$this->isScript)
					{
					    echo $displayString;
						echo "\n<br><b>ERROR:</b> bei den HTML-Tags d�rfen nur Strings und HTML-Tags hinzugef�gt werden<br>\n";
						st_print_r($tag);
						exit();
					}
					$displayString.= $tag->getDisplayString();
				}
        		if($indention)
					$tag_spaces--;
			}
			$displayString.= $this->getBehindSubTagString();
			if($indention)
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
		function addBefore(string|Tag|jsFunctionBase|array|null $tag)
		{
			$this->addObjBefore($tag, false, 2);
		}
		function addObjBefore(string|Tag|jsFunctionBase|array|null &$tag, $bWarningShowed= false, int $outFunc= 1)
		{//echo get_class($tag)."<br />";
			$bWarningShowed= false;
			if(	Tag::isDebug()
				and
				!$bWarningShowed	)
			{
				if(	typeof($this, "TableTag")
					and
					( 	!typeof($tag, "RowTag", "null")
						and
						!typeof($tag, "FormTag", "null")	)	)
				{
					STCheck::is_warning(1, "Tag::add()", "in TableTag should be only insert an RowTag", $outFunc);
					$bWarningShowed= true;
				}
				if(	typeof($this, "RowTag")
					and
					!typeof($tag, "ColumnTag", "null")	)
				{
					STCheck::is_warning(1, "Tag::add()", "in RowTag should be only insert an ColumnTag", $outFunc);
					$bWarningShowed= true;
				}
			}
			$inherit[]= &$tag;
			foreach($this->inherit as $key=>$value)
			{
				$inherit[]= &$this->inherit[$key];
			}
			$this->inherit= &$inherit;
		}
		public function showLine(int $count= 1)
		{
		    $lines= stTools::getBackTrace(1, $count);
		    $str= "";
		    foreach ($lines as $line)
		        $str.= "$line<br />";
	        $this->add($str);
		}
		public function add(string|Tag|jsFunctionBase|array|null $tag, int $outFunc= 1)
		{
			$this->addObj($tag, false, 2);
		}		
		public function addObj(string|Tag|jsFunctionBase|array|null &$tag, $bWarningShowed= false, int $outFunc= 1)
		{
			if($tag==null)
				return;
			if(	Tag::isDebug()
				and
				!$bWarningShowed	)
			{
				if(	typeof($this, "TableTag")
					and
					( 	!typeof($tag, "RowTag", "null")
						and
						!typeof($tag, "FormTag", "null")	)	)
				{
					STCheck::is_warning(1, "Tag::add()", "in TableTag should be only insert an RowTag", $outFunc);
				}
				if(	typeof($this, "RowTag")
					and
					!typeof($tag, "ColumnTag", "null")	)
				{
					STCheck::is_warning(1, "Tag::add()", "in RowTag should be only insert an ColumnTag", $outFunc);
				}
			}
			STCheck::warning(!$this->bEndTag, "the tag <b>&lt;{$this->tag}&gt;</b> can not inherit a tag</b>", $outFunc);
			$this->addArrayContent($tag);
		}
		private function addArrayContent(string|Tag|jsFunctionBase|array|null &$tag)
		{
			if(is_array($tag))
			{
				foreach($tag as &$content)
				{
					if(is_array($content))
						$this->addArrayContent($content);
					else
						$this->inherit[]= &$content;
				}
			}else
				$this->inherit[]= &$tag;
		}
		function addObjBehind($tagName, string|Tag|jsFunctionBase|array|null &$tag, $bWarningShowed= false, int $outFunc= 1)
		{//echo get_class($tag)."<br />";
			$bWarningShowed= false;
			if(	Tag::isDebug()
				and
				!$bWarningShowed	)
			{
				if(	typeof($this, "TableTag")
					and
					( 	!typeof($tag, "RowTag", "null")
						and
						!typeof($tag, "FormTag", "null")	)	)
				{
					STCheck::is_warning(1, "Tag::add()", "in TableTag should be only insert an RowTag", $outFunc);
					$bWarningShowed= true;
				}
				if(	typeof($this, "RowTag")
					and
					!typeof($tag, "ColumnTag", "null")	)
				{
					STCheck::is_warning(1, "Tag::add()", "in RowTag should be only insert an ColumnTag", $outFunc);
					$bWarningShowed= true;
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
		{
			$this->addObjBehind($tagName, $tag, false, 2);
		}
	function append(string|Tag|jsFunctionBase|array|null $value)
	{
		// take Tag:: and not $this->
		// because if function addObj is overloaded
		// the compiler takes the new addObj funktion
		Tag::addObj($value, false, 2);
	}
	function appendObj(string|Tag|jsFunctionBase|array|null &$value)
	{
		// take Tag:: and not $this->
		// because if function addObj is overloaded
		// the compiler takes the new addObj funktion
		Tag::addObj($value, false, 2);
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