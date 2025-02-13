<?php

require_once($_stcheck);

$tag_spaces= 0;
$tagCount= 0;

$HTML_TAG_CONTAINER= null;
/**
 * count all tags which are displayed
 * @var array $nDisplayCount
 */
$__nTagDisplayCount= 0;


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
			if(isset($class))
				$this->class($class);
			$this->inherit= array();

			$this->aNames= array();
			if($class)
				$this->insertAttribute("class", $class);
		}
		public function id(string $name)
		{
		    $this->insertAttribute("id", $name);
		}
		public function class(string $name)
		{
		    $this->insertAttribute("class", $name);
		}
		public function style(string $value)
		{
			$this->insertAttribute("style", $value);
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
			$this->aNames[strtolower($name)]= $term;
		}
		public function hasAttribut(string $name) : bool
		{
			if(isset($this->aNames[strtolower($name)]))
				return true;
		    return false;
		}
		public function getAttribut(string $name)
		{
			if(isset($this->aNames[strtolower($name)]))
				return $this->aNames[strtolower($name)];
			return null;
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
		private function read_test_json(string $jsonFilename) : array|null
		{
			$aRv= null;
			if (file_exists($jsonFilename))
			{
				$file = file_get_contents($jsonFilename);
				if (!$file)
				{
					echo "ERROR: cannot load file selftable_test_links.json<br />";
					exit();
				}
				$aRv= json_decode($file, true);

			}
			return $aRv;
		}
		/**
		 * sorting order of links
		 * inside array $global_selftable_test_links
		 * @var array $aTestTypes
		 */
		private array $aTestTypes= array("edit", "table");
		private int $nStopTestDisplayCount= 2; // if value -1 testing to end
		public function display()
		{
			if(	STCheck::isDebug("test") &&
				typeof($this, "STSiteCreator")	)
			{
				global $global_selftable_test_links;
				global $__global_finished_SiteCreator_result;

				$jsonFilename= "selftable_test_links.json";
				$reportFilename= "selftable_test_report.txt";
				$query= new STQueryString();
				$action= $query->getParameterValue("testdebug", "action");//"testdebug[action]");
				if(	isset($__global_finished_SiteCreator_result) &&
					$__global_finished_SiteCreator_result === "NOERROR" &&
					(	!isset($action) ||
						$action !== "finished"	)							)
				{
					$selftable_test_links= array();
					// Sort keys according to the order in $this->aTestTypes
					foreach ($this->aTestTypes as $orderKey)
					{
						if (isset($global_selftable_test_links[$orderKey]))
							$selftable_test_links[$orderKey] = $global_selftable_test_links[$orderKey];
					}
					$bNew= false;
					$nMaxEditLinks= 1;
					$script = pathinfo($_SERVER["SCRIPT_FILENAME"]);
					$report= "";
					$json= $this->read_test_json($jsonFilename);
					if(isset($json))
					{
						$type= $json['link-type'];
						if($json['status'] == "finished")
							$bNew= true;
					}else
					{
						$bNew= true;
						$json= array( 	"start" => time(),
										"status" => "running",
										"container" => "unknown",
										"table" => "unknown",
										"link-type" => "unknown",
										"link-class" => "",
										"onEditLinkCount" => -1,
										"onEditDeleteCount" => -1,
										"onTableTagCount" => -1								);
					}
					$bFinished= false;
					if($bNew)
					{
						reset($selftable_test_links);
						$type= key($selftable_test_links);						
						reset($selftable_test_links[$type]);
						echo "type: $type<br />";
						$json['start']= time();
						$json['status']= "running";
						$json['container']= $this->getContainerName();
						$json['table']= $this->getTableName();
						$json['link-type']= $type;
						$json['link-class']= key($selftable_test_links[$type]);
						$json['onEditLinkCount']= -1;
						$json['onEditDeleteCount']= -1;
						$json['onTableTagCount']= -1;
						$report= "\n\n\n\n\n\n\n\n";
						$report.= " ****************************************\n";
						$report.= " ***  new DBSelfTables test started\n";
						$report.= " ***  on ".date("d.m.Y H:i:s")."\n";
						$report.= " ***  file {$script['basename']}\n";
						$report.= " ***\n";
						$report.= "\n";
						$report.= "\n";
					}
					

					$report.= " *******************************************************************************\n";
					$report.= " ***  container: ".$this->getContainerName()."\n";
					$report.= " ***      table: ".$this->getTableName()."\n";
					$report.= " ***     action: ".$this->getAction()."\n";
					$report.= " ***     result: $__global_finished_SiteCreator_result\n";
					$report.= "\n";

					if( $this->nStopTestDisplayCount == -1 ||
						$this->nStopTestDisplayCount <= (	$json['onTableTagCount'] + 
															$json['onEditLinkCount'] +
															$json['onEditDeleteCount']	) )
					{
						$bFinished= true;
						$json['status']= "finished";
					}
					if($__global_finished_SiteCreator_result === "NOERROR")
					{
						if( !isset($selftable_test_links['edit']['###link'][$json['onEditLinkCount']+1]) &&
							!isset($selftable_test_links['edit']['###delete'][$json['onEditDeleteCount']+1])	)
						{ // loop through table links
							$type= "table";
							$json['onEditLinkCount']= -1;
							$json['onEditDeleteCount']= -1;
							$buttonClass= $json['link-class'];
							$onAttribute= $selftable_test_links[$type][$buttonClass];
							$tags= $this->getElementsByClass($buttonClass);
							$tagCount= $json['onTableTagCount'] + 1;
							if(isset($tags[$tagCount]))
							{
								$link= $tags[$tagCount]->getAttribut($onAttribute);
								$json['onTableTagCount']= $tagCount;
							}else
							{
								$bFinished= true;
								$link= " set query to finished";
								$json['status']= "finished";
							}
						}else
						{ // loop through edit links
							$type= "edit";
							$link= "window.location='";
							if(isset($selftable_test_links['edit']['###link'][$json['onEditLinkCount']+1]))
							{
								$json['onEditLinkCount']++;
								$link.= $selftable_test_links['edit']['###link'][$json['onEditLinkCount']];
							}else
							{
								$json['onEditDeleteCount']++;
								$link.= $selftable_test_links['edit']['###delete'][$json['onEditDeleteCount']];
							}
							$link.= "'";
						}

						echo "<pre>";
						showLine();
						echo "Current working directory: " . getcwd() . "<br />";
						echo "nextLink: $link<br />";
						st_print_r($selftable_test_links,2);
						st_print_r($json, 2);
						echo "</pre>";
					}else
					{ 
						$bFinished= true; 
						$json['status']= "finished";
					}

					if($bFinished)
					{
						$finishedtime= time() - $json['start'];
						$finishedtime= date("H:i:s", $finishedtime);
						$report.= " ***\n";
						$report.= " ***\n";
						$report.= " ***  Test finished in $finishedtime\n";
						$report.= " ********************************************************************************************************************************************************\n";
						$report.= "\n\n\n\n\n\n\n\n";
					}
					$jsonData= json_encode($json, JSON_PRETTY_PRINT);
					if(file_put_contents($jsonFilename, $jsonData) === false)
					{
						echo "ERROR: cannot write file selftable_test_links.json<br />";
						exit();
					}
					if(file_put_contents($reportFilename, $report, FILE_APPEND) === false)
					{
						echo "ERROR: cannot write file selftable_test_report.txt<br />";
						exit();
					}
					

					if($__global_finished_SiteCreator_result === "NOERROR")
					{
						if($bFinished)
						{
							$query->update("testdebug[action]=finished");
							$link= "alert('Test finished'); ";
							$link.= "location.href='".$query->getUrlParamString()."'";
						}
						$script= new JavaScriptTag();
							$script->add("setTimeout(function(){ $link; }, 5000);");
						$body= $this->getBody();
						$body->add($script);
					}
				}else
				{
					$json= $this->read_test_json($jsonFilename);

					$report=  " *******************************************************************************\n";
					$report.= " ***  container: ".$this->getContainerName()."\n";
					$report.= " ***      table: ".$this->getTableName()."\n";
					$report.= " ***     action: ".$this->getAction()."\n";
					$report.= " ***     result: $__global_finished_SiteCreator_result\n";
					$report.= "\n";
					
					$finishedtime= time() - $json['start'];
					$finishedtime= date("H:i:s", $finishedtime);
					$report.= " ***\n";
					$report.= " ***\n";
					$report.= " ***  Test finished in $finishedtime\n";
					$report.= " ********************************************************************************************************************************************************\n";
					$report.= "\n\n\n\n\n\n\n\n";
					
					if(file_put_contents($reportFilename, $report, FILE_APPEND) === false)
					{
						echo "ERROR: cannot write file selftable_test_report.txt<br />";
						exit();
					}
				}
			}
		    echo $this->getDisplayString(0);
		}
		public function getDisplayString($displayCount= 0)
		{
			global 	$tag_spaces,
					$__nTagDisplayCount,
					$HTML_CLASS_DEBUG_CONTENT,
					$HTML_CLASS_DEBUG_CONTENT_SHOWN,
					$global_SESSION_noRegister_SHOWEN,
					$global_SESSION_noRegister_onLine,
					$global_set_DEBUG_onLine_byFirst;

            $displayString= "";
			$displayCount++;
			$__nTagDisplayCount++;
            
            if( $HTML_CLASS_DEBUG_CONTENT &&
                !typeof($this, "TextAreaTag")   )
            {
                $indention= true;
            }else
                $indention= false;
			if($indention)
			{
			    $displayString.= $this->spaces($tag_spaces);
				if(	!$HTML_CLASS_DEBUG_CONTENT_SHOWN &&
					!STCheck::isDebug("test")			)
				{
      				$displayString.= "</pre></td></tr></table>";
    				$displayString.= "<table width='100%' bgcolor='white'><tr><td>";
    				$displayString.= "<center>this side is set for <b>DEBUG-session</b> ";
    				$displayString.= "(STCheck::debug(<font color='blue'>true</font>))";
					if($global_set_DEBUG_onLine_byFirst !== false)
					{
						preg_match("/([^\\\\\/]+)$/", $global_set_DEBUG_onLine_byFirst['file'], $ereg);
						$file= $ereg[1];
						$line= $global_set_DEBUG_onLine_byFirst['line'];
						$displayString.= "<br />first on <b>file:</b>".$file." <b>line:</b>".$line." <b>";
					}
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
					// *** debugging test for endless loop
					//echo "display tag {$this->tag} {$__nTagDisplayCount} $displayCount<br />";
					$displayString.= $tag->getDisplayString($displayCount);
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
	/**
	 * Return array of tags with the given attribute
	 * or the tag name itself when the attribute is "##tag"
	 * 
	 * @param string $attribute name of the attribute, or "##tag" for the tag name
	 * @param string $name value of the attribute
	 * @param int $count number of tags maximal to return
	 * @return array result array of tags
	 */
    private function &getElementsByAttribute(string $attribute, string $name, int &$count= null) : array
    {
		$aRv= array();
		$attribute= strtolower($attribute);
    	if(	(	$attribute == "##tag" &&
				strtolower($name) == $this->tag	) ||
			(	$attribute != "##tag" &&
				isset($this->aNames[$attribute])	)	)

        {
			$bFill= false;
			if($attribute == "class")
			{
				$names= explode(" ", $this->aNames[$attribute]);
				if(in_array($name, $names))
					$bFill= true;

			}elseif($attribute == "##tag" ||
					$this->aNames[$attribute] == $name	)
			{
            	$bFill= true;
			}
			if($bFill)
			{
				$aRv[]= &$this;
				if(isset($count))
				{
					$count--;
					if($count == -1)
						return $aRv;
				}
			}
        }
        $inheritCount= count($this->inherit);
        for($n= 0; $n<$inheritCount; $n++)
        {
        	$tag= &$this->inherit[$n];
            if(	is_object($tag) &&
            	is_subclass_of($tag, "Tag")	)
            {
            	$aRv= array_merge($aRv, $tag->getElementsByAttribute($attribute, $name, $count));
				if(	isset($count) &&
					$count == -1	)
				{
                	return $aRv;
				}
            }
        }
		return $aRv;
	}
	private function &getElementByAttribute(string $attribute, string $name, int $count= 0)
	{
		$tags= $this->getElementsByAttribute($attribute, $name, $count);
		if(isset($tags[0]))
			return $tags[0];
		$tag= null;
		return $tag;
	}
	public function &getElementByTagName(string $tagName, int $count= 0)
	{
		return $this->getElementByAttribute("##tag", $tagName, $count);
	}
	public function &getElementsByTagName(string $tagName) : array
	{
		return $this->getElementsByAttribute("##tag", $tagName);
	}
	public function &getElementById(string $name, int $count= 0)
	{
		return $this->getElementByAttribute("id", $name, $count);
	}
	public function &getElementsById(string $name) : array
	{
		return $this->getElementsByAttribute("id", $name);
	}
	public function &getElementByClass(string $name, int $count= 0)
	{
		return $this->getElementByAttribute("class", $name, $count);
	}
	public function &getElementsByClass(string $name) : array
	{
		return $this->getElementsByAttribute("class", $name);
	}
	public function &getElementByName(string $name, int $count= 0)
	{
		return $this->getElementByAttribute("name", $name, $count);
	}
	public function &getElementsByName(string $name) : array
	{
		return $this->getElementsByAttribute("name", $name);
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