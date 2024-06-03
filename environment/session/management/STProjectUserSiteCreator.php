<?php

require_once($_stusersitecreator);

    /**
     * All registered projects.<br />
     * Beginning always with the 'Login' var which point to 'Registration' or 'ProjectOverview'.
     * The project key 'var' have to be always the same than the array key.
     * If the 'name' project key set to null, there is no entry in database project table.
     * @var array
     */
$__global_registered_project_containers= array(      "Login" => array(  "name" => null,
                                                                        "var" => "Login",
                                                                        "container" => "ProjectOverviewRegistration",
                                                                        "object" => "STProjectOverviewList",
                                                                        "source" => "_stprojectoverviewlist",
                                                                        "sourcevar" => true                          ),
                                                                        "ProjectOverview" => array( "name" => "Website Access",
                                                                                                    "var" => "ProjectOverview",
                                                                                                    "container" => "ProjectOverviewRegistration",
                                                                                                    "position" => 0,
                                                                                                    "object" => "STProjectOverviewList",
                                                                                                    "source" =>"_stprojectoverviewlist",
                                                                                                    "sourcevar" => true,
                                                                                                    "description" => "Access for unknown user"      ),
                                                                        "ProjectFrame" => array(    "name" => null,
                                                                                                    "var" => "ProjectFrame",
                                                                                                    "container" => "ProjectFrame",
                                                                                                    "object" => "STProjectUserFrame",
                                                                                                    "source" =>"_stprojectuserframe",
                                                                                                    "sourcevar" => true                    ),
                                                                        "Navigation" => array(  "name" => null,
                                                                                                "var" => "Navigation",
                                                                                                "container" => "ProjectOverviewRegistration",
                                                                                                "object" => "STProjectOverviewList",
                                                                                                "source" => "_stprojectoverviewlist",
                                                                                                "sourcevar" => true                          ),
                                                                        "Registration" => array(    "name" => null,
                                                                                                    "var" => "Registration",
                                                                                                    "container" => "userRegistration",
                                                                                                    "object" => "STUserProfileContainer",
                                                                                                    "source" => "_stuserprofilecontainer",
                                                                                                    "sourcevar" => true                          ),
                                                                        "UserProfile" => array( "name" => "Profile",
                                                                                                "var" => "UserProfile",
                                                                                                "container" => "userprofile",
                                                                                                "position" => -1,
                                                                                                "object" => "STUserProfileContainer",
                                                                                                "source" =>"_stuserprofilecontainer",
                                                                                                "sourcevar" => true,
                                                                                                "description" => "Own User Profile" ),
                                                                        "UserManagement" => array(  "name" =>"UserManagement",
                                                                                                    "var" => "UserManagement",
                                                                                                    "container" => "usermanagement",
                                                                                                    "position" => 0,
                                                                                                    "object" => "STUserManagement",
                                                                                                    "source" =>"_stusermanagement",
                                                                                                    "sourcevar" => true,
                                                                                                    "description" => "Management for all user and projects" ),
                                                                        "ExampleProject" => array(  "name" => "DB selfTables",
                                                                                                    "var" => "ExampleProject",
                                                                                                    "container" => "dbselftables",
                                                                                                    "position" => 0,
                                                                                                    "description" => "selfTables framework to create first database API",
                                                                                                    "path" => "https://github.com/kollia/dbselftables",
                                                                                                    "target" => "blank"                                                     )   );

class STProjectUserSiteCreator extends STUserSiteCreator
{
    /**
     * definition for default images
     * on login screen and navigation bar
     * @var array
     */
    private $image= array();

    /**
     * prefix path add to all projects inside database     * 
     * @var string
     */
    private $prefixPath= "";
    /**
     * login entry point how to reach
     * this ProjectUserSite where holed
     * more then one container
     */
    private $sLoginEntryPointUrl;
    /**
     * Describe first project var after Login/Registration and ProjectOverview.<br />
     * If name is 'ProjectFrame' the solution displayed with Navigation Bar in extra frame.
     * Otherwise it displays the project in full frame.
     * @var string
     */
    var $firstProjectName= "ProjectFrame";
    /**
     * struct of general registration properties.<br />
     * outsideRegistration - whether an user can also register by him self from outside<br />
     * adminActivation - whether the administrator need to activate after registration the user
     * dummyUser - whether is allowed to create an dummy user how have no password and cannot login
     * @var array
     */
    private $registrationProperties= array( 'outsideRegistration' =>    true,
                                            'adminActivation' =>        false,
                                            'dummyUser' =>              true    );
    /**
     * All registered projects.<br />
     * Beginning always with the 'Login' var which point to 'Registration' or 'ProjectOverview'.
     * The project key 'var' have to be always the same than the array key.
     * If the 'name' project key set to null, there is no entry in database project table.
     * @var array
     */
    private $aProjects= array();
    
    public function __construct(STObjectContainer $container, string $dbTablePrefix= null, string $loginEntryUrl= null, string $bodyClass= "ProjectAccessBody")
    {
        global $HTTP_SERVER_VARS;
        global $__global_registered_project_containers;
        
        $query= new STQueryString();
        $projectID= $query->getParameterValue("ProjectID");
        $show= $query->getParameterValue("show");

        $this->aProjects= $__global_registered_project_containers;
        $this->predefineContainers($container->getName());

        // if currentContainer will be not defined as frame,
        // name should be defined after known the session be set
        // to decide whether need the Login or ProjectOverview page
        $currentContainer= array();

        if( isset($projectID) &&
            (   !isset($show) ||
                $show == "frame"    )   )
        {// need project frame
            $currentContainer['container']= $this->aProjects['ProjectFrame']['container'];
            $currentContainer['name']= $this->aProjects['ProjectFrame']['name'];
        }
        $projectTableName= $container->getTableName("Project");
        // if find Project table on database,
        // ProjectUserManagement should be installed
        // and database should be correct (bCorrectDb==true)    
        $bCorrectDb= $container->getDatabase()->isTable($projectTableName);
        if( $bCorrectDb &&
            (   !isset($currentContainer['container']) ||
                $currentContainer['container'] != $this->aProjects['ProjectFrame']['container']   ) )
        {
            // do not need registration if only a STFrameContainer called
            // or when not tables are installed ($bCorrectDb = false) (DB tables are not installed)
            $instance= STSession::instance();
            if(!isset($instance))
            {
                STCheck::alert(!isset($dbTablePrefix), "please define before creating STUserProjectManagement a STUserSession or define \$dbTablePrefix inside constructor");
                STUserSession::init($container, $dbTablePrefix);
            }
            $instance->setDbProjectName("ProjectOverview", $this->aProjects['ProjectOverview']['name']);
            if(!isset($loginEntryUrl))
                $loginEntryUrl= $HTTP_SERVER_VARS["SCRIPT_NAME"];
            $vscode_debug= true;
            if($vscode_debug)
            {
                $read_file= false;
                if($loginEntryUrl == "/")
                    $read_file= true;
                else
                {
                    $file= pathinfo($loginEntryUrl);
                    if( isset($file['extension']) &&
                        $file['extension'] == "php"     )
                    {
                        $read_file= true;
                    }
                }
                if(!$read_file)
                    exit;
            }
            $this->sLoginEntryPointUrl= $loginEntryUrl;
            $instance->startPage($loginEntryUrl);

            // set first dummy-container to check whether user is logged-in
            $bLoggedIn= false;
            if( isset($show) &&
                $show == "project" &&
                isset($projectID)   )
            {
                $currentContainer['name']= $this->getDbProjectNameID($container, $projectID);
                foreach($this->aProjects as $project)
                {
                    if($project['name'] == $currentContainer['name'])
                    {
                        $currentContainer['container']= $project['container'];
                        break;
                    }
                }
            }else
            {
                switch($show)
                {
                    case "registration":
                        $currentContainer['container']= $this->aProjects['Registration']['container'];
                        $currentContainer['name']= $this->aProjects['Registration']['name'];
                        break;
                    case "navigation";
                        $currentContainer['container']= $this->aProjects['Navigation']['container'];
                        $currentContainer['name']= $this->aProjects['ProjectOverview']['name'];
                        break;
                    case "list":
                    default:
                        $currentContainer['container']= $this->aProjects['ProjectOverview']['container'];
                        $currentContainer['name']= $this->aProjects['ProjectOverview']['name'];
                        break;
                }
                $projectID= $this->getDbProjectNameID($container, $currentContainer['name']);
                if(!isset($projectID))
                {
                    STCheck::warning(1, "STProjectUserSiteCreator::_construct()", "project '{$currentContainer['name']}' inside Project table not exist, maybe UserManagement habe to be installed!");
                    $bCorrectDb= false;
                }
            }
            if(isset($projectID))
                $bLoggedIn= $instance->verifyLogin($currentContainer['name'], $loginEntryUrl);
            
            if(!$bLoggedIn)
            {
                $currentContainer['container']= $this->aProjects['Login']['container'];
                $currentContainer['name']= $this->aProjects['Login']['name'];
            }else
            {
                $session= STUserSession::instance();
                $registration= $session->getSessionVar("ST_REGISTRATION");
                if( isset($registration) &&
                    $registration == true   )
                {
                    $currentContainer['container']= $this->aProjects['Registration']['container'];
                    $currentContainer['name']= $this->aProjects['Registration']['name'];
                }
            }

        }elseif(!count($currentContainer))
        {
            $currentContainer['container']= $this->aProjects['Login']['container'];
            $currentContainer['name']= $this->aProjects['Login']['name'];

        }elseif(STCheck::isDebug() &&
                $currentContainer['container'] == $this->aProjects['ProjectFrame']['container'] )
        {
            STCheck::doNotOutputObBuffer();
            STCheck::debug(false);
        }

        if(!$bCorrectDb)
        {// database not correct, usermanagement should be installed
            $projectID= 0;
        }
        
        $containerObj= STObjectContainer::getContainer($currentContainer['container']); 
        STUserSiteCreator::__construct($projectID, $containerObj);
    }
    public static function getContainerProjectDefinition(string $case)
    {
        global $__global_registered_project_containers;

        STCheck::alert(!isset($__global_registered_project_containers[$case]), "STProjectUserSiteCreator::getContainerProjectDefinition", "project container of '$case' does not exist", 1);
        return $__global_registered_project_containers[$case];
    }
    /**
     * read from database project ID and Name
     * 
     * @param string|int $idOrDbName can be the ID of project or name
     * @return string|int|null return the ID when Name given or the Name when ID given, otherwise when entry not found is result null
     */
    protected function getDbProjectNameID(STObjectContainer $container, string|int $idOrDbName)
    {
        $project= $container->getTable("Project");
        $selector= new STDbSelector($project);
        $selector->select("Project", "ID", "ID");
        $selector->select("Project", "Name", "Name");
        $selector->allowQueryLimitation(false);
        if(is_numeric($idOrDbName))
            $selector->where("ID=$idOrDbName");
        else
            $selector->where("Name='$idOrDbName'");
        $selector->execute();
        $res= $selector->getResult();
        if(!isset($res[0]))
            return null;
        if(is_numeric($idOrDbName))
            return $res[0]['Name'];
        return $res[0]['ID'];
    }
    public function getLoginEntryPointUrl()
    {
        return $this->sLoginEntryPointUrl;
    }
    /**
     * user should not register by him self from outside
     */
    public function noOutsideRegistration()
    { $this->registrationProperties['outsideRegistration']= false; }
    /**
     * Administrator need to have activate user after registration
     */
    public function needAdminActivation()
    {
        $this->registrationProperties['adminActivation']= true;
        $this->registrationProperties['dummyUser']= false;
    }
    /**
     * It should be not allowed to create an dummy user
     * how have no password and cannot login
     * 
     * @param bool $bAllow whether should allow an dummy user
     */
    public function allowDummyUser(bool $bAllow= true)
    {
        $this->registrationProperties['dummyUser']= $bAllow;
    }
    public function readRegistrationProperties()
    { return $this->registrationProperties; }
    /**
     * initialisation should be done before initialisation of container
     * (some getTable(), getAction(), ...)
     * if method <code>install()</code> be used, initialisation should be also before done
     */
    protected function initialPredefinedStates()
    {
        if( $this->prefixPath != "" &&
            (   typeof($this->tableContainer, "STProjectOverviewList") ||
                typeof($this->tableContainer, "STProjectUserFrame")     )
                                             )
        {
            $this->addClientRootPath($this->prefixPath);
        }
        if(typeof($this->tableContainer, "STUserManagement"))
        {
            if($this->registrationProperties['adminActivation'])
                $this->tableContainer->needAdminActivation();
            $this->tableContainer->allowDummyUser($this->registrationProperties['dummyUser']);

        }elseif(typeof($this->tableContainer, "STUserProfileContainer"))
        {
            if($this->registrationProperties['adminActivation'])
                $this->tableContainer->useAdminActivation();
        }
    }
    public function execute($onError= onErrorMessage)
    {
        $this->initialPredefinedStates();
        STUserSiteCreator::execute($onError);
    }
    protected function predefineContainers(string $databaseContainerName, array $noProjectRegister= array())
    {
        global $_stum_installcontainer;
        global $_stuserclustergroupmanagement;
        global $_stclustergroupassignment;
        foreach($this->aProjects as &$projectObj)
        {
            if( isset($projectObj['source']) &&
                isset($projectObj['sourcevar']) &&
                $projectObj['sourcevar'] == true   )
            {
                $projectvar= $projectObj['source'];
                global $$projectvar;
                $projectObj['source']= $$projectvar;
            }
        }

        STObjectContainer::predefine("um_install", "STUM_InstallContainer", $databaseContainerName, $_stum_installcontainer);
        foreach($this->aProjects as $project)
        {
            if( isset($project['object']) &&
                !in_array($project['var'], $noProjectRegister)  )
            {
                STObjectContainer::predefine(   $project['container'],
                                                $project['object'],
                                                $databaseContainerName,
                                                $project['source']      );
            }
        }
        $usermanagementDb= $this->aProjects['UserManagement']['container'];
        STObjectContainer::predefine("UserClusterGroupManagement", "STUserClusterGroupManagement", $usermanagementDb, $_stuserclustergroupmanagement);
        STObjectContainer::predefine("ClusterGroupAssignment", "STClusterGroupAssignment", $usermanagementDb, $_stclustergroupassignment);

        // do not need register projects
        // because registerProject() only store
        // project inside $this->aProjects
        // only need to store database project name
        // inside session
        $session= STUserSession::instance();
        foreach($this->aProjects as $project)
        {
            if(isset($project['name']))
                $session->setDbProjectName($project['var'], $project['name']);
        }
    }
    /**
     * register project witch is representing 
     * from an exist container
     * 
     * @param string $containerName name of predefined container
     * @param string $projectName name of project inside database
     * @param int $pos position of project inside displayed List
     *                  normally pos should insert with 0 and has the position sorted by project name.
     *                  If pos is negative the position will be displayed as first.
     *                  All positive numbers will be displayed after the name sorted projects
     * @param string $description displayed description of project in list
     */
    public function registerProject(string $containerName, string $projectName, $pos, string $description)
    {
        $bContainerNameExist= false;
        $projectKey= null;
        foreach($this->aProjects as $key => $project)
        {
            if($containerName == $key)
               $bContainerNameExist= true; 
            if($project['container'] == $containerName)
            {
                $projectKey= $key;
                break;
            }
        }
        if(!isset($projectKey))
        {
            STCheck::alert($bContainerNameExist, "STProjectUserSiteCreator::registerProject()",
                            "cannot register project with name '$containerName', because this variable exist", 1);
            $projectKey= $containerName;
        }
        $this->aProjects["name"]= $projectName;
        $this->aProjects["var"]= $projectKey;
        $this->aProjects["container"]= $containerName;
        $this->aProjects["description"]= $description;
        $this->aProjects["position"]= $pos;
        $session->setDbProjectName($projectKey, $profileName);
    }
    public function addClientRootPath(string $path)
    {
        $this->prefixPath= $path;
    }
    /**
     * Display an logo in the left upper corner.<br />
     * For the ProjectOverview-, Login- and Registration- container.
     * 
     * @param string $address image file
     * @param int $width image width (default: auto)
     * @param int $height image height (default: 140px)
     * @param string $alt alternative text for image (default: 'DB selfTables Homepage')
     */
    public function setOverviewLogo(string $address, int $width= null, int $height= 140, string $alt= "DB selfTables Homepage")
    {
        $containerName= $this->tableContainer->getName();
        if( $containerName == $this->aProjects['ProjectOverview']['container'] ||
            $containerName == $this->aProjects['Login']['container'] ||
            $containerName == $this->aProjects['Registration']['container']         )
        {
            $this->tableContainer->setOverviewLogo($address, $width, $height, $alt);
        }
    }
    /**
     * Set image as background into body tag.<br />
     * For the ProjectOverview-, Login- and Registration- container.
     * 
     * @param string $address image file
     * @param bool $repeat whether image should repeat (default: false)
     */
    public function setOverviewBackground(string $address, bool $repeat= false)
    {
        $containerName= $this->tableContainer->getName();
        if( $containerName == $this->aProjects['ProjectOverview']['container'] ||
            $containerName == $this->aProjects['Login']['container'] ||
            $containerName == $this->aProjects['Registration']['container']         )
        {
            $this->tableContainer->setOverviewBackground($address, $repeat);
        }
    }
    /**
     * Set image as background with same height size after the logo.<br />
     * For the ProjectOverview-, Login- and Registration- container.
     * 
     * @param string $address image file
     * @param bool $repeat whether image should repeat (default: true)
     */
    public function setOverviewBannerBackground(string $address, bool $repeat= true)
    {
        $containerName= $this->tableContainer->getName();
        if( $containerName == $this->aProjects['ProjectOverview']['container'] ||
            $containerName == $this->aProjects['Login']['container'] ||
            $containerName == $this->aProjects['Registration']['container']         )
        {
            $this->tableContainer->setOverviewBannerBackground($address, $repeat);
        }
    }
    /**
     * Display an logo in the left upper corner.<br />
     * Only for the navigation frame.
     * 
     * @param string $address image file
     * @param int $width image width (default: auto)
     * @param int $height image height (default: 140px)
     * @param string $alt alternative text for image (default: 'DB selfTables Homepage')
     */
    public function setNavigationLogo(string $address, int $width= null, int $height= 70, string $alt= null)
    {
        if($this->tableContainer->getName() == $this->aProjects['Navigation']['container'])
            $this->tableContainer->setNavigationLogo($address, $width, $height, $alt);
    }
    /**
     * Set image as background with same height size after the logo.<br />
     * Only for the navigation frame.
     * 
     * @param string $address image file
     * @param bool $repeat whether image should repeat (default: true)
     */
    public function setNavigationBannerBackground(string $address, $repeat= true)
    {
        if($this->tableContainer->getName() == $this->aProjects['Navigation']['container'])
            $this->tableContainer->setNavigationBannerBackground($address, $repeat);
    }
    public function install()
    {
        $this->initialPredefinedStates();
        // write first overview name into database
		//$this->dbProjectInsert($this->projectOverviewDbName, /*sort*/0, "only for basic database logs");

        STCheck::echoDebug("install", "<b>install</b> first all tables on database");
        $this->installDbTables();

        STCheck::echoDebug("install", "<b>install</b> than all additional registered projects");
        // install first ProjectOverview
        // (required for all unknown access )
        foreach($this->aProjects as $project)
            if($project['container'] == $this->aProjects['ProjectOverview']['container'])
                $this->saveProject($project['var']);

        // install than UserManagement
        // (required also for primary groups ONLINE and LOGGED_IN )
        foreach($this->aProjects as $project)
            if($project['container'] == $this->aProjects['UserManagement']['container'])
                $this->saveProject($project['var']);

        // afterwards all other registered projects
        foreach($this->aProjects as $project)
            if( $project['container'] != $this->aProjects['ProjectOverview']['container'] &&
                $project['container'] != $this->aProjects['UserManagement']['container']    )
            {
                $this->saveProject($project['var']);
            }

        STCheck::echoDebug("install", "<b>install</b> at last all cluster with groups");
        $this->installContainer();
    }
    private function saveProject(string $key)
    {
        $project= $this->aProjects[$key]['name'];
        if(!isset($project)) // if project name not be set
            return;          // need no entry inside database
        $pos= $this->aProjects[$key]['position'];
        $description= $this->aProjects[$key]['description'];
        $path= "X";
        $target= "SELF";
        STCheck::echoDebug("install", "check whether should install project $project into database");
        if($project == $this->aProjects['ExampleProject']['name'])
        {
            $path= $this->aProjects['ExampleProject']['path'];
            $target= $this->aProjects['ExampleProject']['target'];
        }
        $ID= $this->dbProjectInsert($project, $pos, $description, $path, $target);
        $this->aProjects[$key]['ID']= $ID;
    }
	private function dbProjectInsert(string $projectName, int $sort, string $description, string $path= "X", string $target= "SELF") : int|bool
	{
		global $HTTP_SERVER_VARS;

		$instance= &STSession::instance();

        $project= $this->getTable("Project");
        if(!typeof($project, "STDbTable" ))
        {
            // table not exist on database
            // have to be first installed
            return false;
        }
    	$selector= new STDbSelector($project);
        $selector->select("Project", "ID", "ID");
		$selector->where("Project", "Name='".$projectName."'");
    	$selector->execute();
    	$userManagementID= $selector->getSingleResult();
		if(!isset($userManagementID))
		{
		    $desc= STDbTableDescriptions::instance($this->getDatabase()->getDatabaseName());
			// fill project-cluster per hand
			// because no project is inserted
			// and the system do not found what we want
/*			$instance->projectCluster= array(	$desc->getColumnName("Project", "has_access")=>"STUM-Access_".$projectName,
												$desc->getColumnName("Project", "can_insert")=>"STUM-Insert_".$projectName,
												$desc->getColumnName("Project", "can_update")=>"STUM-Update_".$projectName,
												$desc->getColumnName("Project", "can_delete")=>"STUM-Delete_".$projectName	);*/
            STCheck::echoDebug("install", "<b>install</b> project $project into database");
			$project->identifColumn("Name");
    		$project->accessBy("STUM-Access", STLIST);
    		$project->accessBy("STUM-Insert", STINSERT);
    		$project->accessBy("STUM-Update", STUPDATE);
    		$project->accessBy("STUM-Delete", STDELETE);
			if($path === "")
				$path= $HTTP_SERVER_VARS["SCRIPT_NAME"];
			$inserter= new STDbInserter($project);
			$inserter->fillColumn("Name", $projectName);
			$inserter->fillColumn("sort", $sort);
			$inserter->fillColumn("Path", $path);
			$inserter->fillColumn("Target", $target);
			$inserter->fillColumn("Description", "Listing and changing of all access permissions at project UserManagement");
			$inserter->fillColumn("DateCreation", "sysdate()");
			$inserter->execute();

			$userManagementID= $inserter->getLastInsertID();
/*			if($userManagementID!==1)
			{
				$instance->projectID= $userManagementID;

				$partition= $this->getTable("Partition");
				$updater= new STDbUpdater($partition);
				$updater->update("ProjectID", $userManagementID);
				$updater->execute();

				$cluster= $this->getTable("Cluster");
				$updater= new STDbUpdater($cluster);
				$updater->update("ProjectID", $userManagementID);
				$where= new STDbWhere("ID like 'STUM-Access%'");
				$where->orWhere("ID like 'STUM-Insert%'");
				$where->orWhere("ID like 'STUM-Update%'");
				$where->orWhere("ID like 'STUM-Delete%'");
				$updater->where($where);
				$updater->execute();
			}*/
		}
		return $userManagementID;
	}
}

?>