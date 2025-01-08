<?php

require_once($php_html_description);

interface STContainerTempl
{
    /**
     * name of container
     * @return string name of container
     */
    public function getName() : string;
    /**
     * return actual table name, or correct table name in database (pre-definition from STDbTableDescriptions is allowed)
     * 
     * @param string $name name of the table or null if want to know the current actual table
     * @return string name of table
     */
    public function getTableName(string $name= null);
    /**
     * return table object if exist
     * 
     * @param string $sTableName name of table, if not given method return current table
     * @param string $sContainer name of container, if not given table should come from current container
     * @param STBaseTable|null table object or null if not exist
     */
    public function &getTable(string $sTableName= null, string $sContainer= null);
    /**
     * whether table object exist inside container
     *
     * @param string $tableName name of table
     * @return bool whether exist
     */
    public function hasTable(string $tableName) : bool;
}

abstract class STBaseContainer extends BodyTag implements STContainerTempl
{
    /**
     * language and format for text messages
     * and nation for date
     * @var array
     */
    var $locale= array( "language"  => 'en',
                        "nation"    => 'XXX', // <- undefined
                        "format"    => 'UTF-8' ,
						"changed"	=> false     );
    /**
     * message handling for differnt languages
     * @var STMessageHandling
     */
    protected $msgBox;
    /**
     * array of backbutton addresses
     * for backbutton or container which to go back
     * @var array
     */
    protected $aContainerAdress;
    /**
     * object of executing creator class
     * @var STSiteCreator
     */
    protected $oExternSideCreator= null;
    /**
     * parent container produced before
     * @var STBaseContainer
     */
    protected $parentContainer= null;
	var $bFirstContainer= false; // ob der Container der erste fuer STDbSiteCreator ist
	var $nLevel= null; // auf welcher Ebene sich der Contiainer befindet
	var $name;  // Name des Containers mit dem er als Objekt gehandelt wird
				// und für den Button wenn kein identifName angegeben wird
	var $defaultTitles= array(); // Titel spezifisch für jede Aktion (INSERT, UPDATE, LIST)
	var $get_vars;
	var	$identifName= null; // Name des Containers wenn er als Button angezeigt wird
	var $projectIdentif= null; // Name des Projektes, wenn er nicht gesetzt wird, wird der Projekt-Name aus STSiteCreator genommen
	var $aContainer= array(); // alle STBaseContainer welche zur Auswahl aufgelistet werden

	var $aBehindHeadLineButtons= array();
	var $aBehindProjectIdentif= array();
	/**
	 * All html-tags or strings
	 * which should add least of all tags
	 * inside body
	 * @param array $aBodyEndTags
	 */
	protected $aBodyEndTags= array();

	var $aNavigationTables= array(); // Alle Tabellen die im Container zusaelich in einem STListBox angezeigt werden sollen
	var	$getParmNavigation= array(); // alle Parameter die bei einem link aus dem NavigationTable
									 // eingestellt werden
	var	$getParmListLinks= array();	// alle Parameter die bei einem link aus dem Main-Table
									// eingestellt werden
	var	$bCreated= null;// wheter the container is created
						// null - container is not created
						// false - container will be created, makes an warning for the calling methode, that can has a wrong result
						// true - container is created
	/**
	 * whether the messages for translation
	 * be created
	 */
	private $bMessagesInstalled= false;
	var	$bInitialize= null;// wheter the container is initialized
						// null - container is not initialized
						// false - container will be initialize
						// true - container is initialized
	var	$aAccessClusters= array(); // access cluster to this container
	/**
	 * whether backbutton
	 * should be set
	 * @var bool
	 */
	protected $bBackButton= null;
	/**
	 * name of backbutton container
	 * @var string
	 */
	protected $sBackContainer= "unknown";
	var $backButtonAddress= null;
	var $sBackButton= null;
	var $starterPage= "";
	
	var $headTag= "";
	var $chooseTitle= "";
	/**
	 * whether container use also default css links
	 * defined from global SiteCreator
	 * @var boolean
	 */
	protected $bUseDefaultCssLinks= true;
	/**
	 * all defined css links inside this container
	 * @var array
	 */
	protected $aCssLinks= array();

	function __construct($name, &$container= null, $bodyClass= "body_content")
	{
	    global $global_first_objectContainerName,
	           $global_array_all_exist_stobjectcontainers;
	    
	    if(STCheck::isDebug())
	    {
            STCheck::param($name, 0, "string");
            STCheck::param($container, 1, "STBaseContainer", "null");
            STCheck::param($bodyClass, 2, "string");
    	    STCheck::echoDebug("container", "create new container-object ".get_class($this)."(<b>$name</b>)");
	    }
		$this->name= $name;
		if( isset($container) && 
		    $container->name != $this->name )
		{
		    $this->parentContainer= &$container;
		}
		$this->msgBox= new STMessageHandling(get_class($this)); // <-- not implemented jet by STObjectContainer
		Tag::alert(isset($global_array_all_exist_stobjectcontainers[$name]),
					"STBaseContainer::STBaseContainer(\"$name\"",
					"container \"$name\" already exists");
		$global_array_all_exist_stobjectcontainers[$name]= &$this;
		if( !isset($global_first_objectContainerName) ||
		    trim($global_first_objectContainerName) == "" )
		{
		    $global_first_objectContainerName= $name;
		}
		BodyTag::__construct($bodyClass);
	}	
	/**
	 * dummy method to create messages for different languages.<br />
	 * inside class methods (create(), init(), ...) you get messages from <code>$this->getMessageContent(<message id>)</code><br />
	 * inside this method depending the <code>$language</code> define messages with <code>this->setMessageContent(<message id>, <message>)</code><br />
	 * see STMessageHandling
	 * 
	 * @param string $language current language like 'en', 'de', ...
	 * @param string $nation current nation of language like 'US', 'GB', 'AT'. If not defined, default is 'XXX'
	 */
	protected function createMessages(string $language, string $nation)
	{
	    // dummy method 
	}
	protected function createMessageContent()
	{
		if(!$this->bMessagesInstalled)
		{		
			$locale= $this->getLanguage();	
			$this->createMessages($locale['language'], $locale['nation']);
			$this->bMessagesInstalled= true;
		}
	}
	protected function setMessageContent(string $messageID, string $messageContent, int $errOut= 0)
	{
	    $this->msgBox->setMessageContent($messageID, $messageContent, ++$errOut);
	}
	protected function getMessageContent(string $messageID, string $parameter= null, $outFunc= 0) : string
	{
	    $paramArray= array();	   
		if(isset($parameter))
		{
			if(is_string($parameter))
			{
				$have= func_num_args();
				$args= func_get_args();
				for($count=1; $count<($have-1); $count++)
					$paramArray[]= $args[$count];
				if(!is_int($args[$have-1]))
				{
					$paramArray[]= $args[$have-1];
					$outFunc= 0;
				}else
					$outFunc= $count;
				$have-= 1;
			}else
			{
				$paramArray= $parameter;
				$have= count($paramArray);
			}
			
		}
        return $this->msgBox->getMessageContent($messageID, $paramArray, ++$outFunc);
	}
	public function doContainerInstallation()
	{
		$this->createMessageContent();
		$this->installContainer();
	}
	protected function installContainer()
	{/* dummy method */}
	public function clearFirstObjectContainer()
	{
	    global $global_first_objectContainerName;
	    
	    $global_first_objectContainerName= "";
	}
	function containerLevel(int $nLevel)
	{
	    $this->nLevel= $nLevel;
	}
	function getContainerLevel()
	{
	    return $this->nLevel;
	}
	function title($title, $action= null)
	{
		if(!isset($action))
		{
			$this->defaultTitles[STCHOOSE]= $title;
			$this->defaultTitles[STLIST]= $title;
			$this->defaultTitles[STINSERT]= $title;
			$this->defaultTitles[STUPDATE]= $title;
		}else
			$this->defaultTitles[$action]= $title;
	}
	function getTitle() : string
	{
		$action= $this->getAction();
		if(	isset($action) &&
			isset($this->defaultTitles[$action])	)
		{
			$title= $this->defaultTitles[$action];
		}else
			$title= $action;
		$table= $this->getTable();
		if(isset($table))
			$title.= " ".$table->getTitle();
		if(!isset($title))
		    return "";
		return trim($title);
		
	}
	public function setDefaultLanguage(string $lang, string $nation= "XXX")
	{
	    if(preg_match("/_/", $lang))
	    {
	        $split= preg_split("_/", $lang);
	        $lang= $split[0];
	        $nation= $split[1];
	    }
	    if(STCheck::warning(   $lang != "en" &&
	                           $lang != "de"   , "STBaseContainer::setLanguage()", "only en or de currently allowed"))
	    {
	        $lang= "en";
	    }
	    $this->locale['language']= $lang;
	    $this->locale['nation']= $nation;
		$this->locale['changed']= true;
	}
	/**
	 * return language from container if changed,
	 * otherwise from the parent container.
	 * If no one changed, return the default language structure
	 * with language 'en' and 'XXX' as nation
	 * 
	 * @return struct language
	 */
	protected function getLanguage()
	{
		if($this->locale['changed'])
			return $this->locale;
		if(isset($this->parentContainer))
			return $this->parentContainer->getLanguage();
		return $this->locale;
	}
	function showLogoutButton($buttonName= "log out", $align= "right", $buttonId= "logoutMainButton")
	{
		if(STCheck::is_warning(!STSession::sessionGenerated(), "STBaseContainer::showLogoutButton()", "no session for logout button generated"))
			return;
		$session= &STSession::instance();
		$logout= &$session->getLogoutButton($buttonName, $buttonId);
		$table= new st_tableTag();
		$table->width("100%");
		$table->addObj($logout);
		$table->columnAlign($align);
		$this->addObj($table);
	}
	function setTitle(string $title)
	{
		$this->chooseTitle= $title;
	}
	function &getHead($defaultTitle= "unknown")
	{
		Tag::paramCheck($defaultTitle, 1, "string");

		if($this->headTag)
			return $this->headTag;
  		$head= new HeadTag();
  		$titleString= $this->getTitle();
  		if( !isset($titleString) ||
  		    trim($titleString) == ""    )
  		{
  		    $titleString= $defaultTitle;
  		}
  		$title= new TitleTag($titleString);
  		$head->add($title);
  		$cssLinks= $this->oExternSideCreator->getCssLinks();
  		foreach($cssLinks as $css)
			$head->add($css);
		$this->headTag= &$head;
		return $head;
	}
	public function needDefaultCssLinks()
	{
	    if(!$this->bUseDefaultCssLinks)
	        return false;
	    if(isset($this->parentContainer))
	        return $this->parentContainer->needDefaultCssLinks();
	    return true;
	}
	function setCssLink($href, $media= "all", $title= "protokoll Stylesheet")
	{
	    $this->bUseDefaultCssLinks= false;
	    if($media == "all")
	        $this->aCssLinks= array();
	    else
	        $this->aCssLinks[$media]= array();
	        $this->aCssLinks[$media][]= array( "href"  =>	$href,
	                                           "title" =>	$title	);
	}
	function addCssLink($href, $media= "all", $title= "protokoll Stylesheet")
	{
	    $this->aCssLinks[$media][]= array( "href"  =>	$href,
	                                       "title" =>	$title	);
	}
	function getCssLinks()
	{
		$aLinks= array();
		foreach($this->aCssLinks as $media => $mediaLinks)
		{
		    foreach($mediaLinks as $aLink)
			    $aLinks[]= STQueryString::getCssLink($aLink["href"], $media, $aLink['title']);
		}
		if(isset($this->parentContainer))
		{
		    $aContainerLinks= $this->parentContainer->getCssLinks();
		    foreach($aContainerLinks as $link)
		        $aLinks[]= $link;
		}
		return $aLinks;
	}
	/**
	 * get current [stget] query string
	 * or from container in paraeter
	 * 
	 * @param string $containerName if set this parameter get older [stget] variable from this container
	 * @return array|null [stget] array if exist, otherwise null
	 */
	public function stgetParams(string $containerName= null)
	{
	    Tag::paramCheck($containerName, 1, "string", "null");
	    
        $params= new STQueryString();
        $stget= $params->getArrayVars();
        if(isset($stget["stget"]))
            $stget= $stget["stget"];
        else
            $stget= null;
        if(	!isset($containerName) ||
            trim($containerName) == "" )
        {
            return $stget;
        }
        while($stget)
        {
            if(	isset($stget["container"]) &&
                $stget["container"]===$containerName    )
            {
                return $stget;
            }
            if(isset($stget["older"]["stget"]))
                $stget= $stget["older"]["stget"];
            else
                $stget= null;
        }
        return null;
	}
	function navigationTable($table, $forTable= STALLDEF, $pos= null, $classId= "STNavigationTable")
	{
		Tag::paramCheck($table, 1, "string", "STBaseTable");

		if(typeof($table, "string"))
			$table= $this->getTable($table);
		if($forTable!==STALLDEF)
		{
			if(!typeof($this, "STObjectContainer"))
			{
				STCheck::is_warning(1, "STBaseContainer::navigationTable()", "in container ".get_class($this)." second parameter can only be STALLDEF");
				$forTable= STALLDEF;
			}else
				$forTable= $this->getTableName($forTable);
		}
		$array= array("table"=>$table, "pos"=>$pos, "for"=>$forTable, "class"=>$classId);
		$this->aNavigationTables[]= $array;
	}
	function getDefinedContainerNames()
	{
		// method needs initialization properties
		// which containers are defined
		//$this->createContainer();

		$aContainer= array();
		foreach($this->aContainer as $container=>$by)
			$aContainer[]= $container;
		return $aContainer;
	}
	function &needContainer($container)
	{
		Tag::paramCheck($container, 1, "STBaseContainer", "string");
		//Tag::paramCheck($className, 2, "string", "null");
		//STCheck::is_warning(typeof($container, "STBaseContainer") && $className && !typeof($container, $className),
		//					"STBaseContainer::needContainer()", "given container in first param not the same object of second param");

		if(typeof($container, "STBaseContainer"))
		{
			$containerName= $container->getName();
			// container is a copy, so take it from List for return
			$container= STBaseContainer::getContainer($containerName);
		}else
		{
			$containerName= $container;
			$container= &STBaseContainer::getContainer($containerName);
			if(!$container)
			{
				$container= new STObjectContainer($containerName, $this);
			}
		}
		// maybe aContainer is set before from STObjectContainer
		// so do not clear the variable
		if(!isset($this->aContainer[$containerName]))
			$this->aContainer[$containerName]= true;
		// alex 04/08/2005: brauch ich doch nicht
/*		// alex 04/08/2005:	trage den ParentContainer im hereinkommenden ein
		//					ist dieser bereits ein container darf der ParentContainer
		//					jedoch nur im Container aus der Container-Liste eingetragen werden
		//					da der hereinkommende keine Refferenz auf diesen hat
		$oContainer= &$this->getContainer($container);
		$oContainer->setParentContainer($container);*/
		return $container;
	}
	function getContainerGetParams($containerName= "")
	{
		Tag::paramCheck($containerName, 1, "string", "empty(string)");

		$vars= new STQueryString();
		$vars= $vars->getArrayVars();
		if(isset($vars["stget"]))
			$vars= $vars["stget"];
		else
			$vars= null;
		if(!$containerName)
			$containerName= $this->name;
		while(isset($vars))
		{
			if(	isset($vars["container"]) &&
				$vars["container"] == $this->name	)
			{
				return $vars;
			}
			if(isset($vars["older"]["stget"]))
				$vars= $vars["older"]["stget"];
			else
				$vars= null;
		}
		return null;
	}
	function getOlderContainerName()
	{
		//$vars= new STQueryString();
		//$vars= $vars->getArrayVars();
		//$vars= $vars["stget"];
		if($this->bFirstContainer)
		{
			// container is the first,
			// no older can be set
			return "";
		}

		//search for $this container in the get-params
		//to get back the older one
		$vars= $this->getContainerGetParams();
		if( !isset($vars["older"]["container"]) ||
		    trim($vars["older"]["container"]) == ""   )
		{// older container must be the first
			global	$global_first_objectContainerName;

			$olderContainerName= $global_first_objectContainerName;
		}else
			$olderContainerName= $vars["older"]["container"];
		return $olderContainerName;
	}
	function &getOlderContainer()
	{
		/*$oldContainerName= $HTTP_GET_VARS["stget"]["older"]["stget"]["container"];
		if(!$oldContainerName)
		{

			if(	$vars["stget"]["container"]
				and
				$this->sFirstTableContainerName!=$HTTP_GET_VARS["stget"]["container"]	)
		{
			$oldContainerName= $this->sFirstTableContainerName;
		}*/
		$older= $this->getOlderContainerName();
		if($older)
			$container= &STObjectContainer::getContainer($older);
		return $container;
	}
	public function setExternSiteCreator(STSiteCreator &$externSiteCreator)
	{
		$this->oExternSideCreator= &$externSiteCreator;
	}	
	/**
	 * whether site need a button to go back
	 *
	 * @param bool $bNeed whether need a backbutton
	 */
	public function needBackButton(bool $bNeed)
	{
		$this->bBackButton= $bNeed;
	}
	function execute(&$externSiteCreator, $onError)
	{
	    // create message pool for language
	    $this->createMessageContent();
		$this->setExternSiteCreator($externSiteCreator);
	    // initial container
	    $this->initContainer();

		$action= $this->getAction();
		if(!isset($this->bBackButton))
			$this->bBackButton= $this->oExternSideCreator->bBackButton;
		if(!$this->backButtonAddress)
		    $this->backButtonAddress=  $this->oExternSideCreator->backButtonAddress;
		if(	$action==STLIST
			or
			$action==STCHOOSE
			or
			!$action				)
		{
			$container= &$this->getOlderContainer();
			if(isset($container))
			{
				$this->sBackButton= $container->getDisplayName();
				$this->sBackContainer= $container->getName();
			}
		}else
			$this->sBackContainer= $this->getName();

		if(!typeof($this, "STObjectContainer")) // otherwise the endBodyTags will be inserted
			foreach($this->aBodyEndTags as $tag) // on end of STObjectContainer
				$this->addObj($tag);
		if(!isset($this->sBackButton))
		    $this->sBackButton= $this->oExternSideCreator->sBackButton;
		return "NOERROR";
	}
	/**
	 * method will be called by creation
	 * when container need to know which tables inside
	 * with witch names
	 */
	abstract protected function create();
	protected function createContainer()
	{
	    if(!isset($this->bCreated))// if bCreated is false, container is inside the creation phase
	    {
			$this->bCreated= false;
			STCheck::echoDebug("container", "starting create routine for container ".get_class($this)."(<b>$this->name</b>)");
			$this->create();			
			$this->bCreated= true;
		}
	}
	/**
	 * method will be called when container has an action
	 * to display as STListBox or STItembox
	 *
	 * @param string $action current action of container STInsert/STUpdate/STList/STDelete
	 * @param string $table which table is in action
	 */
	abstract protected function init(string $action, string $table);
	protected function initContainer()
	{
	    // method needs initialization properties
	    // only if he is not initializised
	    // and not in the create or initialize phase
	    // to know which tables are defined
		if(!isset($this->bInitialize))
		{
		    if(!isset($this->bCreated))
		    {
		        $this->createContainer();
		    }else if($this->bCreated !== true)
		        return; // container is currently inside the creation phase
			$this->bInitialize= false;
			STCheck::echoDebug("container", "starting initial routine for container ".get_class($this)."(<b>$this->name</b>)");
			$action= $this->getAction();
			$table= $this->getTableName();
			if( !isset($table) ||
			    trim($table == "")   )
			{
			    $table= $this->getFirstTableName();
			    if( !isset($table) ||
			        trim($table == "")   )
			    {
			        // no first table is selected
			        $table= "";
			        $action= STCHOOSE;
			    }
			}			
			$this->init($action, $table);
			$this->bInitialize= true;
		}
	}
	function currentContainer()
	{
		$get= new STQueryString();
		$get= $get->getArrayVars();
		$containerName= "";
		if(	isset($get["stget"]) &&
			isset($get["stget"]["container"])	)
		{
			$containerName= $get["stget"]["container"];
		}
		if($this->name==$containerName)
			return true;
		if(!$containerName)
		{
			if($this->bFirstContainer)
				return true;
		}
		return false;
	}
	function insertByContainerLink($param, $name= STALLDEF)
	{
		$this->getParmButton[]= array(	"container"=>$name,
										"action"=>STINSERT,
										"param"=>$param		);
	}
	function updateByContainerLink($param, $name= STALLDEF)
	{
		$this->getParmButton[]= array(	"container"=>$name,
										"action"=>STUPDATE,
										"param"=>$param		);
	}
	function deleteByContainerLink($param, $name= STALLDEF)
	{
		$this->getParmButton[]= array(	"container"=>$name,
										"action"=>STDELETE,
										"param"=>$param		);
	}
	function insertByMainListLink($param)
	{
		$this->getParmListLinks[STINSERT][]= $param;
	}
	function updateByMainListLink($param)
	{
		$this->getParmListLinks[STUPDATE][]= $param;
	}
	function deleteByMainListLink($param)
	{
		$this->getParmListLinks[STDELETE][]= $param;
	}
	function insertByNavigationLink($param, $name= "STNavigationTable")
	{
		$this->getParmNavigation[$name][STINSERT][]= $param;
	}
	function updateByNavigationLink($param, $name= "STNavigationTable")
	{
		$this->getParmNavigation[$name][STUPDATE][]= $param;
	}
	function deleteByNavigationLink($param, $name= "STNavigationTable")
	{
		$this->getParmNavigation[$name][STDELETE][]= $param;
	}
	function setGlobalObjectContainer($container)
	{
		STCheck::paramCheck($container, 1, "string", "STBaseContainer");
		global	$global_first_objectContainerName,
				$global_array_all_exist_stobjectcontainers;

		if(typeof($container, "STBaseContainer"))
			$container= $container->getName();
		elseif(Tag::isDebug())
		{
			$bExist= false;
			foreach($global_array_all_exist_stobjectcontainers as $name=>$container)
			{
				if($name==$container)
					$bExist= true;
			}
			Tag::alert(!$bExist, "STBaseContainer::setGlobalObjectContainer()",
														"container ".$container." not exist in list");
		}
		$global_first_objectContainerName= $container;
	}
	/**
	 * return obj of created container.
	 * 
	 * @param string|bool §containerName name of container, when parameter not given, method return object of first container,
	 * 										if parameter a boolean of true, return object can be null if not exist, otherwise the same if not given
	 * @param string|bool $className name of container class, if parameter a boolean of true, if parameter a boolean of true, return object can be null
	 */
	public static function &getContainer($containerName= null, string|bool $className= null, string $fromContainer= null) : object|null
	{
		global	$global_st_pathdef_inc_location_path,
				$global_first_objectContainerName,
				$global_array_all_exist_stobjectcontainers,
				$global_array_exist_stobjectcontainer_with_classname,
				$_selftable_first_main_database_name;

		STCheck::param($containerName, 0, "string", "null", "bool");
		
		$bAllowNullObj= false;
		if(typeof($containerName, "bool"))
		{
		    $bAllowNullObj= $containerName;
		    $containerName= null;
		}
		if(typeof($className, "bool"))
		{
			$bAllowNullObj= $className;
			$className= null;
		}
		if(	!isset($containerName) &&
			!isset($className)			)
		{
			$query= new STQueryString();
			$containerName= $query->getArrayVars("stget", "container");
			//$containerName= $get["stget"]["container"];
			if(!$containerName)
			{
			    $containerName= $global_first_objectContainerName;
				if(!$containerName)
					$containerName= $_selftable_first_main_database_name;
				STCheck::is_warning(!$bAllowNullObj && !$containerName, "STBaseContainer::getContainer()",
																"no globaly container set");
				if(!$containerName)
				{
					if( isset($this) &&
					    typeof($this, "STBaseContainer")	)// if ask globaly STBaseContainer::getContainer() $this is null
					{
						// if the function is called with STBaseContainer::getContainer();
						// inside from an Object, $this is the reference to this one
						// so give back this reference only by an object from STBaseContainer
						return $this;
					}
					$null= null;
					return $null;
				}
			}
		}
		foreach($global_array_all_exist_stobjectcontainers as $name=>$container)
		{
			if( isset($containerName) &&
				$name==$containerName	)
			{
			    $containerObj= &$global_array_all_exist_stobjectcontainers[$name];
				return $containerObj;
			}
		}
		// if contianer name not exist
		// make second search for classname
		if(isset($className))
		{
			foreach($global_array_all_exist_stobjectcontainers as $name=>$container)
			{
				if(typeof($container, $className))
					return $container;
			}
		}
		if(	(	!isset($className) ||
				!$className				) &&
			isset($global_array_exist_stobjectcontainer_with_classname[$containerName]["class"])	)
		{	// if param className not set,
			// search in the globaly array
			// which be created with STObjectContainer::install()
			$className= $global_array_exist_stobjectcontainer_with_classname[$containerName]["class"];
		}
		if(	!isset($className) ||
			!$className				)
		{
			// if container name not exist, no class name exist
			// and container name not exist in global predefined stobjectcontainer list
			// search whether container name is only a database name
			foreach($global_array_all_exist_stobjectcontainers as $name=>$container)
			{
				if( $containerName == $container->getDatabaseName()	)
				{
					$containerObj= &$global_array_all_exist_stobjectcontainers[$name];
					return $containerObj;
				}
			}
		}
		if(	(	!isset($fromContainer) ||
				!$fromContainer				) &&
				isset($global_array_exist_stobjectcontainer_with_classname[$containerName]["from"])	)
		{
			$fromContainer= $global_array_exist_stobjectcontainer_with_classname[$containerName]["from"];
			
		}else
			$fromContainer= $_selftable_first_main_database_name;
			

		// if className is an exist database class
		// it should be no error and the second parameter
		// is for the default database selection result
		if(	STDatabase::existDatabaseClassName($className)	)
		{
			if(	$fromContainer!==STSQL_NUM
				and
				$fromContainer!==STSQL_ASSOC
				and
				$fromContainer!==STSQL_BOTH		)
			{
				$fromContainer= STSQL_NUM;
			}
		}else
		{
			$bSetContainer= false;
			if($fromContainer==="userDb")
			{
				if(STUserSession::sessionGenerated())
				{
					$instance= &STuserSession::instance();
					$fromContainer= &$instance->getUserDb();
				}else
					$fromContainer= &STBaseContainer::getContainer($_selftable_first_main_database_name);
			}elseif(isset($fromContainer))
			    $fromContainer= &STBaseContainer::getContainer($fromContainer);
			    STCheck::alert(!$bAllowNullObj && (!isset($fromContainer) || $fromContainer === null), "STBaseContainer::getContainer()",
						"no exist container '$containerName' found, or any script to install be set");
			if( !STCheck::isDebug() &&
			    !isset($fromContainer)   )
			{
			    exit();
			}
		}

		STCheck::alert(!$fromContainer, "STBaseContainer::getContainer()", "container '$containerName' is not set in container-List");

		if(isset($global_array_exist_stobjectcontainer_with_classname[$containerName]["source"]))
		{
			// before add source of new container
			// add also all global defined variables inside st_pathdef.inc.php
			require($global_st_pathdef_inc_location_path);
			require_once($global_array_exist_stobjectcontainer_with_classname[$containerName]["source"]);
			$containerObj= new $className($containerName, $fromContainer);
			return $containerObj;
		}

		if($bAllowNullObj)
		{
			$null= null;
			return $null;	
		}
		$firstDefaultContainer= null;
		if(isset($global_array_all_exist_stobjectcontainers[$global_first_objectContainerName]))
		{
		    $firstDefaultContainerName= $global_first_objectContainerName;
		    $firstDefaultContainer= $global_array_all_exist_stobjectcontainers[$global_first_objectContainerName];
		}elseif(!empty($global_array_all_exist_stobjectcontainers))
		{
			showLine();
			st_print_r($global_array_all_exist_stobjectcontainers);
		    $firstDefaultContainerName= array_key_first($global_array_all_exist_stobjectcontainers);
		    $firstDefaultContainer= $global_array_all_exist_stobjectcontainers[$firstDefaultContainerName];
		}
		if(isset($firstDefaultContainer))
		{
		    if(STCheck::isDebug())
		    {
		        $dbg= "access";
		        if(STCheck::isDebug("container"))
		            $dbg= "container";
		        STCheck::echoDebug($dbg, "container $containerName do not exist, take first founded container '$firstDefaultContainerName'");
		    }
		    return $firstDefaultContainer;
		}
	    if($className == null)
	        $className= "STObjectContainer";
        if(STCheck::isDebug("container"))
        {
            $space= STCheck::echoDebug("container", "create new container $containerName as <b>class:</b>$className");
            STCheck::echoSpace($space);
            echo "<b>WARNING</b> --------------------------------------------------------------------------------------- <b>WARNING</b><br />";
            STCheck::echoSpace($space);
            echo "        maybe <b>$containerName</b> will be created to late<br>";
            STCheck::echoSpace($space);
            echo "        try to create earlyer<br>";
            STCheck::echoSpace($space);
            echo "             (inside constructer when objects used<br>";
            STCheck::echoSpace($space);
            echo "              or immediately in index file<br>";
            STCheck::echoSpace($space);
            echo "<b>WARNING</b> --------------------------------------------------------------------------------------- <b>WARNING</b><br />";
            showBackTrace();
        }
		$containerObj= new $className($containerName, $fromContainer);
		return $containerObj;
	}
	public static function existContainer($containerName)
	{
		global	$global_array_all_exist_stobjectcontainers;

		foreach($global_array_all_exist_stobjectcontainers as $name=>$container)
		{
			if($name==$containerName)
				return true;
		}
		return false;
	}
	function getContainerName()
	{
		$container= &STBaseContainer::getContainer();
		$containerName= null;
		if($container)
			$containerName= $container->getName();
		return $containerName;
	}
	static function &getAllContainer()
	{
		global	$global_array_all_exist_stobjectcontainers;

		return $global_array_all_exist_stobjectcontainers;
	}
	static function getAllContainerNames()
	{
		global	$global_array_exist_stobjectcontainer_with_classname,
				$global_array_all_exist_stobjectcontainers;

		$aRv= array();
		foreach($global_array_all_exist_stobjectcontainers as $name=>$container)
			$aRv[]= $name;
		foreach($global_array_exist_stobjectcontainer_with_classname as $name=>$content)
		{
			if(!isset($global_array_all_exist_stobjectcontainers[$name]))
				$aRv[]= $name;
		}
		return $aRv;
	}
	// alex 04/08/2005: brauch ich doch nicht
/*	function setParentContainer($container)
	{
		if(!$this->sParentContainer)
		{
			if(typeof($container, "STBaseContainer"))
				$container= $container->getName();
			$this->sParentContainer= $container;
		}
	}
	function &getParentContainer()
	{
		if(!$this->sParentContainer)
			return null;
		return $this->getContainer($this->sParentContainer);
	}
	function getParentContainerName()
	{
		return $this->sParentContainer;
	}*/
	function getName() : string
	{
		return $this->name;
	}
	function setDisplayName($name)
	{
		$this->identifName= $name;
	}
	function setProjectDisplayName($project)
	{
		$this->projectIdentif= $project;
	}
	function getProjectDisplayName()
	{
		return $this->projectIdentif;
	}
	function getDisplayName()
	{
		// method needs initialization properties
		// which name is set to display
		$this->createContainer();

		$name= $this->identifName;
		if(!isset($name))
			$name= $this->name;
		return $name;
	}
	function need(&$object)
	{
		Tag::paramCheck($object, 1, "STBaseTable", "STBaseContainer");

		if(typeof($object, "STBaseContainer"))
			$this->needContainer($object);
		else
			$this->needTableObject($object);
	}
	function addObjBehindHeadLineButtons(&$tag)
	{
		Tag::paramCheck($tag, 1, "Tag", "string");

		if(typeof($tag, "STSearchBox"))
			$this->asSearchBox[$tag->categoryName]= "exist";
		$this->aBehindHeadLineButtons[]= &$tag;
	}
	function addBehindHeadLineButtons($tag)
	{
		Tag::paramCheck($tag, 1, "Tag", "string");

		if(typeof($tag, "STSearchBox"))
			$this->asSearchBox[$tag->categoryName]= "exist";
		$this->aBehindHeadLineButtons[]= &$tag;
	}
	function addObjBehindProjectIdentif(&$tag)
	{
		Tag::paramCheck($tag, 1, "Tag", "string");

		if(typeof($tag, "STSearchBox"))
			$this->asSearchBox[$tag->categoryName]= "exist";
		$this->aBehindProjectIdentif[]= &$tag;
	}
	function addBehindProjectIdentif($tag)
	{
		Tag::paramCheck($tag, 1, "Tag", "string");

		if(typeof($tag, "STSearchBox"))
			$this->asSearchBox[$tag->categoryName]= "exist";
		$this->aBehindProjectIdentif[]= &$tag;
	}
	/**
	 * Add inserted html-tag or string at least of all tags
	 * inside body
	 * 
	 * @param string|Tag $tag to insert tag or string
	 */
	public function addOnBodyEnd(string|Tag $tag)
	{
		if(typeof($tag, "STSearchBox"))
			$this->asSearchBox[$tag->categoryName]= "exist";
		$this->aBodyEndTags[]= &$tag;
	}
	function setMainTable($mainTable)
	{
		Tag::paramCheck($mainTable, 1, "Tag");

		$this->oMainTable= $mainTable;
	}
		function addAllInSide(&$createdTags)
		{
		    if(typeof($createdTags, "IFrameTag"))
		    {
    			$action= $this->getAction();
    			$tableName= $this->getTableName();
    			$get_vars= array();
    			if($action)
    				$get_vars["action"]= $action;
    			if($tableName)
    				$get_vars["table"]= $tableName;
    			$get_vars["container"]= $this->getName();
    
      			$headline= &$this->getHeadline($get_vars);
    			$this->appendObj($headline);
		    }


			// include navigationTables with null-pos
			// also count the others
			/////////////////////////////////////////////////////////////////
			$bNeedNavis= false;
			$nNeedTopNavis= 0;
			$nNeedRightNavis= 0;
			$nNeedBottomNavis= 0;
			$nNeedLeftNavis= 0;
			if(typeof($this, "STObjectContainer"))
				$currentTable= $this->getTableName();
			else
			    $currentTable= STALLDEF;
			foreach($this->aNavigationTables as $key=>$navi)
			{
				if(	$navi["for"]===STALLDEF
					or
					$navi["for"]===$currentTable	)
				{
					// to ask later only for YES
					$this->aNavigationTables[$key]["for"]= "YES";

    				$list= new STListBox($this->db, $navi["class"]);
    				$this->oExternSideCreator->setAccessForColumnsInTable($navi["table"], $list);
    				$list->table($navi["table"]);
    				$list->doContainerManagement($this->bContainerManagement);
    				if(is_array($this->getParmNavigation[$navi["class"]]))
    				{
    					foreach($this->getParmNavigation[$navi["class"]] as $action=>$do)
    					{
    						foreach($do as $param)
    						{
    							if($action==STINSERT)
    								$list->insertParam($param);
    							elseif($action==STUPDATE)
    								$list->updateParam($param);
    							elseif($action==STDELETE)
    								$list->deleteParam($param);
    						}
    					}
    				}
    				$list->execute();
    				$this->aNavigationTables[$key]["tabelName"]= $navi["table"]->getDisplayName();
    				$this->aNavigationTables[$key]["list"]= &$list;
    				if($navi["pos"]!==null)
    				{
    					if($navi["pos"]==STTOP)
    						++$nNeedTopNavis;
    					elseif($navi["pos"]==STRIGHT)
    						++$nNeedRightNavis;
    					elseif($navi["pos"]==STBOTTOM)
    						++$nNeedBottomNavis;
    					elseif($navi["pos"]==STLEFT)
    						++$nNeedLeftNavis;
    					$bNeedNavis= true;
    				}else
    					$this->add($list);
				}
			}//end foreach(aNavigationTables)
			/////////////////////////////////////////////////////////////////
			
			if($bNeedNavis)
			{
				$table= new TableTag();
					$table->width("100%");
				if($nNeedTopNavis)
				{
					$ttr= new RowTag();
						$ttd= new ColumnTag(TD);
					foreach($this->aNavigationTables as $key=>$navi)
					{
						if(	$navi["pos"]==STTOP
							and
							$navi["for"]==="YES"	)
						{
							$ttd->addObj($this->aNavigationTables[$key]["list"]);
						}
					}
						$ttr->addObj($ttd);
					$table->addObj($ttr);
				}
					$mtr= new RowTag();
				if($nNeedLeftNavis)
				{
						$ltd= new ColumnTag(TD);
					foreach($this->aNavigationTables as $key=>$navi)
					{
						if(	$navi["pos"]==STLEFT
							and
							$navi["for"]==="YES"	)
						{
							$ltd->addObj($this->aNavigationTables[$key]["list"]);
						}
					}
						$mtr->addObj($ltd);
				}
						$mtd= new ColumnTag(TD);
							$mtd->addObj($createdTags);
						$mtr->addObj($mtd);
				if($nNeedRightNavis)
				{
						$rtd= new ColumnTag(TD);
					foreach($this->aNavigationTables as $key=>$navi)
					{
						if(	$navi["pos"]==STRIGHT
							and
							$navi["for"]==="YES"	)
						{
							$rtd->addObj($this->aNavigationTables[$key]["list"]);
						}
					}
						$mtr->addObj($rtd);
				}
				
				$outtr= new RowTag();
				$outtd= new ColumnTag(TD);
				$outtd->add(__file__.__line__);
				$outtr->add($outtd);
				$table->add($outtr);
				
					$table->addObj($mtr);

				if($nNeedBottomNavis)
				{
					$btr= new RowTag();
						$btd= new ColumnTag(TD);
					foreach($this->aNavigationTables as $key=>$navi)
					{
						if(	$navi["pos"]==STBOTTOM
							and
							$navi["for"]==="YES"	)
						{
							$btd->addObj($this->aNavigationTables[$key]["list"]);
						}
					}
						$btr->addObj($btd);
					$table->addObj($btr);
				}
				$this->appendObj($table);
			}else // if($bNeedNavis)
				$this->appendObj($createdTags);
		}
		function deleteQueryContainerToLevel(&$oGetParam, $nAktLevel)
		{
			if($nAktLevel===null)
				return;
			Tag::echoDebug("containerChoiceDelete", "delete containers to level $nAktLevel");
			do{
				$params= $oGetParam->getArrayVars();
				$container= $params["stget"]["container"];
				if($container)
				{
    				$container= $this->getContainer($container);
					$nCountLevel= $container->getContainerLevel();
    				if(	$nCountLevel===null
						or
						$nAktLevel<=$nCountLevel	)
					{
						Tag::echoDebug("containerChoiceDelete", "delete container ".$container->getName());
    					$this->deleteQueryContainer($oGetParam);
					}
				}
			}while(	$container
					and
					(	$nCountLevel===null
						or
						$nAktLevel<=$nCountLevel	)	);
		}
		function hasDynamicAccessToOneEntry()
		{
		    return false;
		}
		function deleteQueryContainer(&$oGetParam)
		{
			$params= $oGetParam->getArrayVars();
			if( !isset($params["stget"]["container"]) ||
			    !$params["stget"]["container"]           )
			{
				return false;
			}
			$oGetParam->removeContainer();
/*			$oGetParam->delete("stget[table]");
			$oGetParam->delete("stget[action]");
			$oGetParam->delete("stget[container]");
			$vars= $oGetParam->getArrayVars();
			$fromLink= $vars["stget"]["link"]["from"];
			if(is_array($fromLink))
			{
				foreach($fromLink as $link)
				{
					if(is_array($link))
						foreach($link as $table=>$column)
							$oGetParam->delete("stget[".$table."][".$column."]");
				}
			}
			$oGetParam->delete("stget[link][from]");*/
			return true;
		}
		function addParamsByButton(&$oParam, $name)
		{
			if(	isset($this->getParmButton) &&
				is_array($this->getParmButton)	)
			{
    			foreach($this->getParmButton as $set)
    			{
    				if(	$name==$set["container"]
    					or
    					$set["container"]==STALLDEF	)
    				{
    					$oParam->make($set["action"], $set["param"]);
    				}
    			}
			}
			return;

			$getParams= $this->getParmButton[$name];
			if(isset($getParams))
			{
				if(isset($getParams[STINSERT]))
					foreach($getParams[STINSERT] as $param)
						$oParam->insert($param);
				if(isset($getParams[STUPDATE]))
					foreach($getParams[STUPDATE] as $param)
						$oParam->update($param);
				if(isset($getParams[STDELETE]))
					foreach($getParams[STDELETE] as $param)
						$oParam->delete($param);
			}
			$getParams= $this->getParmButton[STALLDEF];
			if(isset($getParams))
			{
				if(isset($getParams[STINSERT]))
					foreach($getParams[STINSERT] as $param)
						$oParam->insert($param);
				if(isset($getParams[STUPDATE]))
					foreach($getParams[STUPDATE] as $param)
						$oParam->update($param);
				if(isset($getParams[STDELETE]))
					foreach($getParams[STDELETE] as $param)
						$oParam->delete($param);
			}
		}
		function getAddressToContainer($bHtmlArray= false)
		{
			Tag::paramCheck($bHtmlArray, 1, "boolean", "STQueryString");

			$bAsObject= false;
			if(typeof($bHtmlArray, "STQueryString"))
			{
				$bAsObject= true;
				$oGet= &$bHtmlArray;
			}else
				$oGet= new STQueryString();

			$oGet->insert("stget[action]=");
			$oGet->insert("stget[table]=");
			$oGet->insert("stget[container]=".$this->getName());

			if($bAsObject)
				return $oGet;
			if($bHtmlArray)
				return $oGet->getArrayVars;
			return $oGet->getStringVars;
		}
		function &getHeadline($get_vars)
		{
			$div= new DivTag();
			$this->makeHeadlineButtons($div, $get_vars);
			$project= $this->getProjectDisplayName();
    		if($project)
    		{
      			$div->add(br());
      			$div->add(br());
      			$div->add(br());
      			$div->add(br());
    			$center= new CenterTag();
    				$center->add($project);
    			$div->add($center);
    		}
        	$this->aBehindProjectIdentif= array_merge(	$this->oExternSideCreator->aBehindProjectIdentif,
														$this->aBehindProjectIdentif);
        	$anz= count($this->aBehindProjectIdentif);
        	if($anz)
        	{
        		for($n= 0; $n<$anz; $n++)
        		{
        			$tag= &$this->aBehindProjectIdentif[$n];
        			$div->addObj($tag);
        		}
        	}
  			$div->add(br());
  			$div->add(br());
			return $div;
		}
		function makeHeadlineButtons(&$divTag, $get_vars)
		{
			global $HTTP_SERVER_VARS;

			$HTTP_GET_VARS= new STQueryString();
			$HTTP_GET_VARS= $HTTP_GET_VARS->getArrayVars();

			// alex 16/06/2005: definition des BackButtons an den Anfang der Funktion gezogen,
			//					da er bei der Parameter-Auswahl noch ver�ndert wird
    		$sBackButtonName= $this->sBackButton;
    		$sBackButtonContainerName= null;
			if(	(	$this->oExternSideCreator->sFirstTableContainerName!=$this->getName() &&
					$this->oExternSideCreator->bContainerManagement								) ||
				(	isset($this->backButtonAddress) &&
					trim($this->backButtonAddress) != ""	) ||
				(	isset($get_vars["action"]) &&
					(	$get_vars["action"]==STINSERT ||
						$get_vars["action"]==STUPDATE		)		)										)
			{// erzeuge BackButton

					if(	!isset($this->backButtonAddress) ||
						trim($this->backButtonAddress) == ""	)
					{
						$get= new STQueryString();//$get_vars);
						if(STCheck::isDebug("containerChoice"))
						{
							STCheck::echoDebug("containerChoice", "no backButtonAddress be set,");
							STCheck::echoDebug("containerChoice", "so create an Address for back-button.");
							echo "<br />";
							$space= STCheck::echoDebug("containerChoice", "incomming query fields before changing for back-Button");
							st_print_r($get_vars,5, $space);
						}

						if(	(	isset($get_vars["action"]) &&
								(	$get_vars["action"] == STLIST ||
									$get_vars["action"] == STCHOOSE	)	) ||
							typeof($this, "STFrameContainer")	)
						{
							if(STCheck::isDebug())
							{
								Tag::echoDebug("containerChoice", "action is STLIST/STCHOOSE or container is an STFrameContainer,");
								$msgstr= "so delete the container and if it has also the first row (stget[firstrow][";
								if(isset($get_vars["table"]))
									$msgstr.= $get_vars["table"];
								$msgstr.= "])";
								Tag::echoDebug("containerChoice", $msgstr);
							}
							if(isset($get_vars["table"]))
							    $get->delete("stget[firstrow][".$get_vars["table"]."]");
							if(	!isset($this->bBackButton) ||
								$this->bBackButton == true		)
							{
								$this->bBackButton= $this->deleteQueryContainer($get);
								if(!$this->bBackButton)
								{
									Tag::echoDebug("containerChoice", "the current container is the first,");
									Tag::echoDebug("containerChoice", "or all before has set ->forwardByOneEntry()");
									Tag::echoDebug("containerChoice", "and this tables have only one entry.");
								}
							}elseif(!$this->bBackButton)
							{
								Tag::echoDebug("containerChoice", "do not need Back-Button was set before");
							}

						}elseif(isset($get_vars["action"]) &&	
								(	$get_vars["action"]==STINSERT ||
									$get_vars["action"]==STUPDATE 	)	)
						{
							if($this->sFirstAction==$get_vars["action"])
							{
								/*
								 * undocumented because do not know why display this toDo-message
								if(count($this->aContainer))
								{
									echo "file:".__file__." line:".__line__."<br />";
									echo "toDo: first action for this container is ".$get_vars["action"]."<br />";
									echo "      can also choose to other container<br />";
									echo "      nothing is to do?";
									exit;
								}*/
								$this->bBackButton= $this->deleteQueryContainer($get);
							}else
							{
								$get->update("stget[action]=".STLIST);
								//st_print_r($get->getArrayVars(),10);
								$table= $this->getTable($get_vars["table"]);
								// alex 2006/05/21:	delete limitation only
								//					if the user wants
								if($table->getDeleteLimitationOrder()=="true")
								{
								    $get->removeLimitation();
								}

								$sBackButtonName= $this->sBackButton;
								//$get->getParamString(STUPDATE, "stget[table]=".$get_vars["table"];
							}
						}elseif(	isset($get_vars["action"]) &&
									$get_vars["action"]==STDELETE	)
						{
							$get->update("stget[action]=".STCHOOSE);
							$get->delete("stget[table]");
							$get->delete("stget[value]");
						}/*elseif($get_vars["action"]==STCHOOSE)
						{
    						$get->getParamString(STDELETE, "stget[action]");//=".STCHOOSE);
    						$get->getParamString(STDELETE, "stget[table]");
    						$get->getParamString(STDELETE, "stget[firstrow][".$get_vars["table"]."]");
    						$get->getParamString(STDELETE, "stget[container]");
    						}*/

						$sBackButtonContainerName= $this->sBackContainer;
						if(!$sBackButtonContainerName)
							$sBackButtonContainerName= $this->sFirstTableContainerName;
						//echo "BackButton:".$sBackButtonName."<br />";
						//echo "BackContainer:".$sBackButtonContainerName."<br />";

						$this->addParamsByButton($get, $sBackButtonContainerName);
						$backAddress= "";
						if(isset($this->starterPage))
							$backAddress= $this->starterPage;
						$backAddress.= $get->getStringVars();
						if(STCheck::isDebug("containerChoice"))
						{
							echo "<br />";
							$space= STCheck::echoDebug("containerChoice", "query after changing for back-Button");
							st_print_r($get->getArrayVars(), 10, $space);
							echo "<br />";
						}

					}else
						$backAddress= $this->backButtonAddress;
					if($this->bBackButton)
					{
						if(!$backAddress)
						{
							$backAddress= $this->starterPage;
							if(!$backAddress)
								$backAddress= $HTTP_SERVER_VARS["SCRIPT_NAME"];
						}
    					$backButton= new ButtonTag("backButton");
    						$backButton->add($sBackButtonName);
    						$backButton->onClick("javascript:location='".$backAddress."'");
							$this->aContainerAdress[$sBackButtonContainerName]= $backAddress;
						STCheck::echoDebug("containerChoice", "set back-button to container <b>".$sBackButtonContainerName.
																"</b> with name \"".$sBackButtonName."\"");
					}else
						STCheck::echoDebug("containerChoice", "do not need any back-button");
					STCheck::echoDebug("containerChoice", "---------------------------------------------------------------------------");
				//}
			}//end if(display backbutton)

		$bNeededBackButton= false;
		$containerButtons= array();
		$tableName= $this->getTableName();
		if(STCheck::isDebug())
		{
			$nContainer= count($this->aContainer);
			if($nContainer==0)
				$message= "no container";
			elseif($nContainer==1)
				$message= "one container";
			elseif($nContainer==2)
				$message= "two container are";
			else
				$message= $nContainer+" container are";
			$message.= " be set to choose";
			STCheck::echoDebug("containerChoice", $message);
		}
		if(	count($this->aContainer) )
		{
			if(STCheck::isDebug("containerChoice"))
			{
				STCheck::echoDebug("containerChoice", "exist container buttons:");
				echo "<b>[</b>containerChoice<b>]:</b> ";
				st_print_r($this->aContainer, 3, 19);
				echo "<br />";
				STCheck::echoDebug("containerChoice", "display for action <b>".$get_vars["action"]."</b>");
			}
			foreach($this->aContainer as $containerName=>$by)
			{
				STCheck::echoDebug("containerChoice", "<b>choice</b> for container ".$containerName);
				if($containerName!=$sBackButtonContainerName)
				{//echo "action:".$get_vars["action"];st_print_r($by,2);
					Tag::echoDebug("containerChoice", "container is not the before created back-button");
					// variable ->aContainer displays:
					//  array([containerName]=> array( [tableName/all table]=> array( [action/all actions]=> boolean(true/false) ) ) )
					if( $by===true
						or
						isset($by[STALLDEF][STALLDEF])
						or
						isset($by[STALLDEF][$get_vars["action"]])
						or
						isset($by[$tableName][STALLDEF])
						or
						isset($by[$tableName][$get_vars["action"]])	)
					{
						STCheck::echoDebug("containerChoice", "container should be notify");
    						$get= new STQueryString();
							$this->addParamsByButton($get, $containerName);
							// is button for an back-container
							$older= null;
							if(isset($HTTP_GET_VARS["stget"]["older"]))
							    $older= $HTTP_GET_VARS["stget"]["older"];
							$olderButtons= 1;
							$isBackButton= false;
							while($older)
							{
								if(isset($older["stget"]["container"]))
								{
									++$olderButtons;
									if($older["stget"]["container"]==$containerName)
									{
										$isBackButton= true;
										break;
									}
								}else
									break;
								if(isset($older["stget"]["older"]))
									$older= $older["stget"]["older"];
								else
									$older= null;
							}

							if($containerName==$this->oExternSideCreator->sFirstTableContainerName)
							{
								$isBackButton= true;
								++$olderButtons;// now delete all container in get-vars
							}
							if($isBackButton)
							{
								Tag::echoDebug("containerChoice", "container is an older back-button,");
								Tag::echoDebug("containerChoice", "so delete for address all before");
								for($n=$olderButtons; $n>1; $n--)
								{
									$from= $new_get["stget"]["link"]["from"];
									if(is_array($from))
										foreach($from as $delete)
											$get->delete("stget[".$delete."]");
									$aktParams= $get->getArrayVars();
									if(is_array($aktParams["stget"]["link"]["from"]))
									{
										$links= $aktParams["stget"]["link"]["from"];
										foreach($links as $do)
										{
											foreach($do as $tableName=>$column)
												$get->delete("stget[".$tableName."][".$column."]");
										}
									}
									$this->bBackButton= $this->deleteQueryContainer($get);/*("stget[link][from]");
									$get->delete("stget[table]");
    								$get->delete("stget[action]");
    								$get->delete("stget[container]");*/
								}
							}else
							{
								if($containerName!=$this->getName())
								{
									Tag::echoDebug("containerChoice", "container is not aktual container");
									$oContainer= &STBaseContainer::getContainer($containerName);
									$nLevel= $oContainer->getContainerLevel();
									if(!$this->oExternSideCreator->bContainerManagement)
									{
										Tag::echoDebug("containerChoice", "but because the container-management is switched of,");
										Tag::echoDebug("containerChoice", "make only an update to the aktual");
									    $make= STUPDATE;
									}elseif($nLevel!==null)
									{
										Tag::echoDebug("containerChoice", "but he has the level ".$nLevel);
										Tag::echoDebug("containerChoice", "so delete all container before to this level");
									    $make= STINSERT;
										// 25/05/2006 alex: had a propblem with php Version 4.3.10-15
										//					in an extendee class from STSubGalleryContainer
										//					php does not found the method
										//					so I sayed -> search in STBaseContainer
										STBaseContainer::deleteQueryContainerToLevel($get, $nLevel);
									    //$make= STUPDATE;
										//$get->deleteOlderByCase($this->getContainerLevel());

									}else
									{
										Tag::echoDebug("containerChoice", "so shift the others back, because container-management be set");
									    $make= STINSERT;
									}
    								$get->make($make, "stget[table]=");
    								$get->make($make, "stget[action]=");
    								$get->make($make, "stget[container]=".$containerName);
									$get->make($make, "stget[link][from]=");
								}else
								{
									Tag::echoDebug("containerChoice", "container is the aktual, delete stget[link][from]");
									if(is_array($HTTP_GET_VARS["stget"]["link"]["from"]))
									{
										/*$linkParams= $HTTP_GET_VARS["stget"]["link"]["from"];
										foreach($linkParams as $from)
										{
											$get->delete("stget[".$from."]");
										}*/
										$get->delete("stget[link][from]");
									}
								}
							}
    						$sButtonAddress= $get->getStringVars();
    						$oContainer= &STBaseContainer::getContainer($containerName);
    						$sButtonName= $oContainer->getDisplayName();
							if($oContainer->hasContainerAccess())
							{
								Tag::echoDebug("containerChoice", "user have access to container, so make button in list");
								$button= new ButtonTag("backButton");
									$button->add($sButtonName);
									$button->onClick("javascript:location='".$sButtonAddress."'");
								$containerButtons[]= $button;
								$this->aContainerAdress[$oContainer->getName()]= $sButtonAddress;
							}else
								Tag::echoDebug("containerChoice", "user has no access for container to see");
					}// end if($by===true)
				}else
				{
					Tag::echoDebug("containerChoice", "container is the before created back-button");
					$containerButtons[]= $backButton;
					$bNeededBackButton= true;
				}//end if($containerName!=$sBackButtonContainerName)
			}//end foreach($this->aContainer)
			STCheck::echoDebug("containerChoice", "---------------------------------------------------------------------------");
		}//end if(	count($this->aContainer) )
		STCheck::echoDebug("containerChoice", " ");


		// f�ge die buttons geordnet in die Tabelle
		$table= new TableTag();
			$table->width("100%");
		foreach($containerButtons as $button)
		{
			$tr= new RowTag();
				$td= new ColumnTag(TD);
					$td->align("right");
					$td->add($button);
				$tr->add($td);
			$table->add($tr);
		}
		if(	!$bNeededBackButton &&
			$sBackButtonContainerName &&
			isset($backButton)				)
		{
			$tr= new RowTag();
				$td= new ColumnTag(TD);
					$td->align("right");
					$td->add($backButton);
				$tr->add($td);
			$table->add($tr);
			$this->aContainer[$sBackButtonContainerName]= STBaseContainer::getContainer($sBackButtonContainerName);
		}
		$divTag->addObj($table);

		$this->aBehindHeadLineButtons= array_merge(	$this->oExternSideCreator->aBehindHeadLineButtons,
													$this->aBehindHeadLineButtons);
		$anz= count($this->aBehindHeadLineButtons);
		if($anz)
		{
			for($n= 0; $n<$anz; $n++)
			{
				$tag= &$this->aBehindHeadLineButtons[$n];
				$divTag->addObj($tag);
			}
		}
	}
	/**
	 * whether table object exist inside container
	 *
	 * @param string $tableName name of table
	 * @return bool whether exist
	 */
	public function hasTable(string $tableName) : bool
	{
	    // dummy function for STSiteCreator
	    // this container STBaseContainer contains no table
	    return false;
	}
	public function &getTable(string $sTableName= null, string $sContainer= null)
	{
		$Rv= null;
		// dummy function for STSiteCreator
		// this container STBaseContainer contains no table
		return $Rv;
	}
	function getTables()
	{
		// dummy function for STSessionSiteCreator
		// this container STBaseContainer contains no table
		return array();
	}
	public function getFirstTableName()
	{
		// dummy function for STSiteCreator
		// this container STBaseContainer contains no table
		return null;
	}
	public function getTableName(string $name= null)
	{
		// dummy function for STSessionSiteCreator
		// this container STBaseContainer contains no table
		return null;
	}
	public function getAction()
	{
		// dummy function for STSessionSiteCreator
		// this container STBaseContainer need no action
		return null;
	}
	public function getDatabase()
	{
		// dummy function for STSessionSiteCreator
		// this container STBaseContainer contains no database
		return null;
	}

	// deprecated
	function containerChoice($container)
	{
		Tag::deprecated("STDbBaseContainer::needContainer($container)", "STDbTableContainer::containerChoice($container)");
		$this->needContainer($container);
	}
	function setIdentification($name)
	{
		Tag::deprecated("STDbBaseContainer::setDisplayName($name)", "STDbTableContainer::setIdentification($name)");
		$this->setDisplayName($name);
	}
	function setProjectIdentifier($project)
	{
		Tag::deprecated("STDbBaseContainer::setDisplayName($project)", "STDbTableContainer::setProjectIdentifier($project)");
		$this->setProjectDisplayName($project);
	}
	function getProjectIdentifier()
	{
		Tag::deprecated("STDbBaseContainer::getProjectDisplayName()", "STDbTableContainer::getProjectIdentifier()");
		return $this->getProjectDisplayName();
	}
	function getIdentification()
	{
		Tag::deprecated("STDbBaseContainer::getDisplayName()", "STDbTableContainer::getIdentification()");
		return $this->getDisplayName();
	}
	function createCluster(string $clusterName, int|string $projectNameID, string $description, bool $addGroup= true)
	{
		$instance= &STUserSession::instance();
		if(isset($instance))
			return $instance->createCluster($clusterName, $projectNameID, $description, $addGroup);
		return false;
	}
	function createGroup(string $groupName, string $domainName)
	{
		$instance= &STUserSession::instance();
		if(isset($instance))
			return $instance->createGroup($groupName, $domainName);
		return "NOUSERSESSIONEXIST";
	}
	function joinUserGroup($user, $group)
	{
		STCheck::paramCheck($user, 1, "int", "string");
		STCheck::paramCheck($group, 2, "int", "string");

		$instance= &STUserSession::instance();
		if(isset($instance))
		{
			return $instance->joinUserGroup($user, $group);
		}
		return "NOUSERSESSIONEXIST";
	}
	function joinClusterGroup($clusterName, $group)
	{
		$instance= &STUserSession::instance();
		if(isset($instance))
		{
			return $instance->joinClusterGroup($clusterName, $group);
		}
		return -1;
	}
	public function getAccessCluster($action)
	{
	    $aRv= array();
	    if(isset($this->aAccessClusters[STALLDEF]))
	        $aRv= $this->aAccessClusters[STALLDEF];
        if(isset($this->aAccessClusters[$action]))
            $aRv= array_merge($aRv, $this->aAccessClusters[$action]);
	    return $aRv;
	}
	public function checkPermission()
	{
	    $this->hasAccess(/*action unknown*/null, /*loginOnFail*/true);
	    $action= $this->getAction();
	    $table= $this->getTable();
	    if(isset($table))
	        $table->hasAccess($action, /*loginOnFail*/true);
	}
	/**
	 * whether current user has acces to container
	 * 
	 * @param string $action permisson for this action, if not set from current action
	 * @param boolean $loginOnFail if set goto by fault to login page (default:false)
	 * @return boolean whether user has access, if no session defined alway true
	 */
	public function hasAccess($action= null, $loginOnFail= false) : bool
	{
	    if( !StCheck::isDebug("useExtern") &&
		//	count($this->aAccessClusters) &&
	        STSession::sessionGenerated()      )
	    {
	        $instance= &STUserSession::instance();
	        if(!isset($action))
	            $action= $this->getAction();
            $clusters= $this->getAccessCluster($action);
            foreach($clusters as $aCluster)
            {
                $access=  $instance->hasAccess($aCluster['cluster'], $aCluster['info'], $aCluster['customID'], /*loginOnFail*/false);
                if($access)
                    return true;
            }
			if($loginOnFail)
			{
				if(isset($this->aAccessClusters[$action]))
					$firstCluster= reset($this->aAccessClusters[$action]);
				if(	!isset($firstCluster) &&
					isset($this->aAccessClusters[STALLDEF]))
				{
					$firstCluster= reset($this->aAccessClusters[STALLDEF]);
				}
				if(isset($firstCluster))
					$instance->hasAccess($aCluster['cluster'], $aCluster['info'], $aCluster['customID'], /*loginOnFail*/true);
				else
					return true;
			}
            return false;
	    }
	    return true;
	}
	/**
	 * user will be forward to login page
	 * if he has no access to cluster
	 * 
	 * @param string $cluster access cluster name
	 * @param string $action action can be set to:<br /> 
	 *                         <table>
	 *                             <tr>
	 *                                 <td>
	 *                                     STALLDEF
	 *                                 </td><td>-</td>
	 *                                     for all actions (default)
	 *                                 <td>
	 *                                 </td>
	 *                             </tr>
	 *                             <tr>
	 *                                 <td>
	 *                                     STLIST
	 *                                 </td><td>-</td>
	 *                                 <td>
	 *                                     show only the content of table
	 *                                 </td>
	 *                             </tr>
	 *                             <tr>
	 *                                 <td>
	 *                                     STINSERT
	 *                                 </td><td>-</td>
	 *                                 <td>
	 *                                     insert somthing into table
	 *                                 </td>
	 *                             </tr>
	 *                             <tr>
	 *                                 <td>
	 *                                     STUPDATE
	 *                                 </td><td>-</td>
	 *                                 <td>
	 *                                     update row of table
	 *                                 </td>
	 *                             </tr>
	 *                             <tr>
	 *                                 <td>
	 *                                     STDELETE
	 *                                 </td><td>-</td>
	 *                                 <td>
	 *                                     delete row of table
	 *                                 </td>
	 *                             </tr>
	 *                         </table>
	 *                       if no action be set, only the third description string,
	 *                       action will be also the default (STALLDEF)
	 * @param string $sInfoString description of cluster for logging table
	 * @param int $customID custom id for logging table if need
	 */
	public function accessBy(string $cluster, $action= STALLDEF, $sInfoString= "", int $customID= null)
	{
	    STCheck::param($action, 1, "string", "int");
	    STCheck::param($sInfoString, 2, "string", "empty(string)", "int");
	    
	    if( $action != STALLDEF &&
            $action != STLIST &&
	        $action != STINSERT &&
	        $action != STUPDATE &&
	        $action != STDELETE    )
	    {
	        $sInfoString= $action;
	        $action= STALLDEF;
	    }
	    if(is_integer($sInfoString))
	    {
	        $customID= $sInfoString;
	        $sInfoString= "";
	    }
	    
		if(!$sInfoString)
			$sInfoString= "acces to container ".$this->getDisplayName()."(".$this->name.")";
		$this->aAccessClusters[$action][]= array(	"cluster"	=>	$cluster,
        		                                    "action"    =>  $action,
        											"info"		=>	$sInfoString,
        											"customID"	=>	$customID		);
	}	
		function hasContainerAccess($makeError= false)
		{
			Tag::echoDebug("access", "function <b>hasContainerAccess()</b> to container \"".$this->name."\" ?");

			if(!count($this->aAccessClusters))
			{
				Tag::echoDebug("access", "no cluster for this container be set, so return true for having access");
				return true;
			}
			if(!STSession::sessionGenerated())
			{
				Tag::echoDebug("access", "no session generated, so return true for having access");
				return true;
			}
			$instance= &STSession::instance();
			foreach($this->aAccessClusters as $action => $clusters)
			{
				foreach($clusters as $cluster)
				{
					if($instance->hasAccess($cluster["cluster"], $cluster["info"], $cluster["customID"], $makeError))
					{
						STCheck::echoDebug("access", "user has $action-<b>access</b> to this container");
						return true;
					}
				}
			}
			Tag::echoDebug("access", "user has <b>no access</b> to this container");
			return false;
		}
}

?>
