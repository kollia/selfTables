<?php

require_once $_stobjectcontainer;

class STProjectOverviewList extends STObjectContainer
{
    /**
     * definition for default images
     * on login screen and navigation bar
     * @var array
     */
    private $image= array();
    /**
     * content of tags or strings
     * added into the site
     */
    private $addedContent= array();
    /**
     * prefix path add to all projects inside database     * 
     * @var string
     */
    private $prefixPath= "";
    /**
     * all exist projects
     */
    private $accessableProjects= array();
    private $accessibilityString= null;
    private $accessibilityProjectMask= null;
    
    private $loginMask= null;
    private $availableSite= null;

    public function __construct(string $name, STObjectContainer &$container, string $bodyClass= "ProjectAccessBody")
    {
        STObjectContainer::__construct($name, $container, $bodyClass);
    }
	/**
	 * method to create messages for different languages.<br />
	 * inside class methods (create(), init(), ...) you get messages from <code>$this->getMessageContent(<message id>, <content>, ...)</code><br />
	 * inside this method depending the <code>$language</code> define messages with <code>$this->setMessageContent(<message id>, <message>)</code><br />
	 * see STMessageHandling
	 *
	 * @param string $language current language like 'en', 'de', ...
	 */
	protected function createMessages(string $language)
	{
        STObjectContainer::createMessages($language);
		if($language == "de")
		{
		    $this->setMessageContent("LoginMaskDescription", "
                                                        <h1>
                                                            Anmeldung: 
                                                        </h1>
                                                        <span class='fontBigger'>
                                                            Bitte melden sie sich mit ihren Konto-Daten an:
                                                        </span>");
		    $this->setMessageContent("AccessibilityProjectString", "Zur Verfügung stehende Webapplikationen:");
			
		}else // otherwise language have to be english "en"
		{
		    $this->setMessageContent("LoginMaskDescription", "
                                                        <h1>
                                                            Login: 
                                                        </h1>
                                                        <span class='fontBigger'>
                                                            Please login with your specific account:
                                                        </span>");
		    $this->setMessageContent("AccessibilityProjectString", "Existing Webapplikations:");
		}
	}
    public function setLoginMaskDescription(string $description)
    {
        $this->setMessageContent("LoginMaskDescription", $description);
    }
    public function setAccessibilityProjectString(string $string)
    {
        $this->accessibilityString= $string;
    }
    public function addClientRootPath(string $path)
    {
        $this->prefixPath= $path;
    }
    public function setOverviewLogo(string $address, int $width= null, int $height= 140, string $alt= "DB selftables Homepage")
    {
        $this->image['overview']['img']= $address;
        $this->image['overview']['height']= $height;
        $this->image['overview']['width']= $width;
        $this->image['overview']['alt']= $alt;
    }
    public function setOverviewBackground(string $address, $repeat= true)
    {
        $this->image['overview']['background']= $address;
        $this->image['overview']['background-repeat']= $repeat;
        $this->image['overview']['background-body']= true;
    }
    public function setOverviewBannerBackground(string $address, $repeat= true)
    {
        $this->image['overview']['background']= $address;
        $this->image['overview']['background-repeat']= $repeat;
        $this->image['overview']['background-body']= false;
    }
    public function setNavigationLogo(string $address, int $width= null, int $height= 70, string $alt= null)
    {
        $this->image['nav']['img']= $address;
        $this->image['nav']['height']= $height;
        $this->image['nav']['width']= $width;
        if(isset($alt))
            $this->image['nav']['alt']= $alt;
        else if(isset($this->image['overview']['alt']))
            $this->image['nav']['alt']= $this->image['overview']['alt'];
    }
    public function setNavigationBannerBackground(string $address, $repeat= true)
    {
        $this->image['nav']['background']= $address;
        $this->image['nav']['background-repeat']= $repeat;
    }
    protected function create()
    {
        $this->displayNoTables();
        //$user= $this->needTable("User");

        //$this->db->verifyLogin("##StartPage");
        STCheck::echoDebug("user", "entering ProjectAccess init function...");
        $cluster= $this->db->getTable("Cluster");
        $selector= new STDbSelector($cluster);
        $selector->select("Cluster", "ID", "ClusterID");
        $selector->select("Project", "ID", "ID");
        $selector->select("Project", "Name", "Name");
        $selector->select("Project", "sort", "sort");
        $selector->select("Project", "Path", "Path");
        $selector->select("Project", "Target", "target");
        $selector->select("Project", "Description", "Description");//"Description");
        $selector->select("Project", "DateCreation", "DateCreation");
        $selector->rightJoin("Cluster", "ProjectID", "Project", "ID");
        $selector->orderBy("Project", "sort, Name");
        $selector->allowQueryLimitation(false);
        $statement= $selector->getStatement();
        $selector->execute();
        $result= $selector->getResult();

        $user= STSession::instance();
        $projectOverviewContainer= $this->getName();
        if(STCheck::isDebug("user"))
        {
            $msg= "sessionvar ST_LOGGED_IN is ";
            $loggedin= $user->getSessionVar("ST_LOGGED_IN");
            if(isset($loggedin))
                $msg.= print_r($loggedin, /*return*/true);
            else
                $msg.= "<b>not</b> defined";
            STCheck::echoDebug("user", $msg);
            
            $cluster_membership= $user->getSessionVar("ST_CLUSTER_MEMBERSHIP");
            $space= STCheck::echoDebug("user", "</b> User is currently member of following clusters: ");
            if(is_array($cluster_membership) && count($cluster_membership))
            {
                st_print_r($cluster_membership, 1, $space);
            }else
                st_print_r("<em>NO CLUSTER</em>", 1, $space);
        }

        // sort to groups
        $sortResult= array();
        $overviewName= $user->getDbProjectName("ProjectOverview");
        foreach( $result as $row )
        {
            if($row['Name'] != $overviewName)
                $sortResult[$row['ID']][]= $row;
        }
        // search for access for every project
        foreach( $sortResult as $aProject )
        {
            $access_msg= "Search whether user has access for Project Nr. ".htmlspecialchars( $aProject[0][ 'ID' ] )."): <em>";
            $access_msg.= "<b> - " .htmlspecialchars( $aProject[0][ 'Name' ] )."</em> - </b>:";
            $bClusterLessAccess= false;
            $accessClusters= "";
            $deniedClusters= "";
            foreach ($aProject as $row)
            {
                if(!isset($row['ClusterID']))
                    $bClusterLessAccess= true; // no cluster set, in this case project is same as linked with ONLINE cluster
                elseif($user->hasAccess( $row[ 'ClusterID' ], $access_msg ))
                    $accessClusters.= $row[ 'ClusterID' ].", ";
                elseif(STCheck::isDebug())
                    $deniedClusters.= $row[ 'ClusterID' ].", ";
            }
            if($accessClusters != "")
                $accessClusters= substr($accessClusters, 0, strlen($accessClusters)-2);
            if($deniedClusters != "")
                $deniedClusters= substr($deniedClusters, 0, strlen($deniedClusters)-2);
            if(STCheck::isDebug("user"))
                echo "<br />\n";
            if( $bClusterLessAccess ||
                $accessClusters != "")
            {
                if( STCheck::isDebug("user") )
                {
                    $aMsgs= array();
                    $aMsgs[]= $access_msg;
                    if($bClusterLessAccess)
                    {                        
                        $msg= "<b>GRANTED</b> ";
                        $msg.= "because no cluster be set (virtual linked with ONLINE cluster)";
                        $aMsgs[]= $msg;
                    }
                    if($accessClusters != "")
                    {                     
                        $msg= "<b>GRANTED</b> ";
                        if($bClusterLessAccess)
                            $msg.= "also ";
                        $msg.= "with membership in Cluster(";
                        $msg.= htmlspecialchars( $accessClusters ).")";
                        $aMsgs[]= $msg;
                    }  
                    STCheck::echoDebug("user", $aMsgs);
                }
                $this->accessableProjects[] =
                                        array(
                                            'ID'   => $row[ 'ID' ],
                                            'Name' => $row[ 'Name' ],
                                            'Path' => $row[ 'Path' ],
                                            'Target' => $row[ 'target' ],
                                            'Description' => $row[ 'Description' ],
                                            'DateCreation' => $row[ 'DateCreation' ]	);
            }elseif(STCheck::isDebug("user"))
            {
                $aMsgs= array();
                $aMsgs[]= $access_msg;
                $msg= "<b>DENIED</b> because user have no membership in Cluster(";
                $msg.= htmlspecialchars( $deniedClusters ).")";
                $aMsgs[]= $msg;
                STCheck::echoDebug("user", $aMsgs);
            }
        }
        if( Tag::isDebug("user") ) 
            echo "".$this->toString();
    }
    protected function init(string $action, string $table)
    {
        
    }
    public function toString()
    {
        echo '<br /><br /><br />';
        STCheck::echoDebug("user", "init found following accessable projects:");
        $res  = '<table border="0" bgcolor="black" cellpadding="2" cellspacing="1" >';
        $res .= '<tr><td colspan="3" bgcolor="#E0E0E0">Accessable Projects</td></tr>';
        $res .= '<tr><td bgcolor="white">ID</td><td bgcolor="white">description</td><td bgcolor="white">Path</td></tr>';
        foreach( $this->accessableProjects as $project ){
            $res .= '<tr><td bgcolor="white" colspan="3">'.htmlspecialchars( $project[ 'Name' ] ).'</td></tr>';
            $res .= '<tr><td bgcolor="white">'.$project['ID'].'</td>';
            $res .= '    <td bgcolor="white">'.htmlspecialchars( $project[ 'Description' ] ).'</td>';
            $res .= '    <td bgcolor="white">'.htmlspecialchars( $project[ 'Path' ] ).'</td>';
            $res .= '</tr>';
        }
        $res .= '</table><br />';
        return $res;
        
    }
    public function hasProjects() : bool
    {
        if(count($this->accessableProjects) > 0)
            return true;
        return false;
    }
    public function getSiteTypeShow() : string
    {
        $available= $this->showAvailableSite();
        return $available['show'];
    }
    private function showAvailableSite()
    {
        if(isset($this->availableSite))
            return $this->availableSite;
        
        $this->createContainer();
        $this->availableSite= array();
        $this->availableSite['access']= false;
        
        $access= STSession::instance();
        if( isset($access) &&
            $access->isLoggedIn()  )
        {
            $this->availableSite['LoggedIn']= true;
            $this->availableSite['access']= true;
        }else
            $this->availableSite['LoggedIn']= false;
            
        $get= new STQueryString();
        $projectID= $get->getUrlParamValue("ProjectID");
        if(!is_numeric($projectID))
            $projectID= 0;
        $this->availableSite['project']= $projectID;
        
        $error= $access->getLoginError();
        if($error == 0)
        {
            $error= $get->getUrlParamValue("ERROR");
            if(!is_numeric($error))
                $error= 0;
        }
        $show= $get->getUrlParamValue("show");
        if( $projectID > 0 &&
            $this->isProjectAvailable($projectID) &&
            (   !isset($show) ||
                $show == "frame"   )                            )
        {
            $this->availableSite['show']= "frame";
        }elseif($projectID == 0)
            $this->availableSite['show']= "list";
        elseif(isset($show))
            $this->availableSite['show']= $show;
        elseif( !$this->isProjectAvailable($projectID) )
        {// user has no access to project, so show login
            $this->availableSite['show']= "list";
            $this->availableSite['access']= false;
            $error= 5;//user has no access to data
        }else
            $this->availableSite['show']= "project";
        $this->availableSite['error']= $error;
        return $this->availableSite;
    }
    private function isProjectAvailable(int $projectID) : bool
    {
        foreach($this->accessableProjects as $project)
        {
            if($project['ID'] === $projectID)
                return true;
        }
        return false;
    }
    public function addObj(&$tag, $showWarning = false)
    {
        $this->addedContent[]= $tag;
    }
    public function execute(&$externSideCreator, $onError)
    {
        $this->createMessageContent();
        $message= $this->getMessageContent("LoginMaskDescription");
        if(!isset($this->accessibilityProjectString))
            $this->setAccessibilityProjectString($this->getMessageContent("AccessibilityProjectString"));

        STObjectContainer::execute($externSideCreator, $onError);

  
        $available= $this->showAvailableSite();
        if( STCheck::isDebug() &&
            (   STCheck::isDebug("user") ||
                STCheck::isDebug("access")  )   )
        {
            $dbg= "access";
            if(STCheck::isDebug("user"))
                $dbg= "user";
            $space= STCheck::echoDebug($dbg, "access to site:");
            st_print_r($available,1, $space);
        }
        
        $get= new STQueryString();
        $user= STSession::instance();
        if($available['show'] == "list")
        {
            if(isset($this->image['overview']['img']))
            {
                $table= new st_tableTag();
                    $table->border(0);
                    $table->cellpadding(0);
                    $table->cellspacing(0);
                    $table->width("100%");                    
                    $a= new ATag();
                        $query= $user->getSessionUrlParameter();
                        if($query != "")
                            $query= "?".$query;
                        $userQuery= $get->getUrlParamValue("user");
                        if(isset($userQuery))
                        {
                            $userQuery= "user=".$userQuery;
                            if($query != "")
                                $query.= "&".$userQuery;
                            else
                                $query= "?".$userQuery;
                        }
                        $entryPoint= $externSideCreator->getLoginEntryPointUrl();
                        $a->href($entryPoint.$query);
                        $a->target("_top");
                        $img= new ImageTag();
                            $img->src($this->image['overview']['img']);
                            $img->height($this->image['overview']['height']);
                            $img->width($this->image['overview']['width']);
                            $img->border(0);
                            $img->alt($this->image['overview']['alt']);
                        $a->add($img);
                    $table->add($a);
                    if(isset($this->image['overview']['background']))
                    {
                        $onBody= false;
                        if($this->image['overview']['background-body'] == true)
                            $onBody= true;

                        $styleString= "background-image: url('{$this->image['overview']['background']}');";
                        if(isset($this->image['overview']['height']))
                        {
                            $styleString.= " background-size: auto {$this->image['overview']['height']};";
                            if($this->image['overview']['background-repeat'] == false)
                                $styleString.= " background-repeat: no-repeat;";
                        }
                        if($onBody)
                            $this->style($styleString);
                        else
                            $table->columnStyle($styleString);
                    }
                    if($user->isLoggedIn())
                    {
                        $div= new DivTag();
                            $logout= $user->getLogoutButton( "Logout" );
                            $div->add($logout);
                            $div->add(br());
                            $div->add("logged In as: ");
                            $b= new BTag();
                                $span= new SpanTag("colorONE");
                                    $span->add($user->getUserName());
                                $b->add($span);
                                $b->add("&nbsp;&nbsp;");
                            $div->add($b);
                            $table->add($div);
                        if(isset($this->image['overview']['background']))
                            $table->columnStyle($styleString);
                        //$table->width("100%");
                        $table->columnAlign("right");
                    }
                $this->append($table); 
            }
        }elseif($available['show'] == "navigation")
        {
            $get->delete("show");
            $get->delete("ERROR");
            $get->delete("ProjectID");
            //STCheck::debug(TRUE);
            if(isset($this->image['nav']))
                $logo= $this->image['nav'];
            elseif(isset($this->image['overview']))
            {
                $logo= $this->image['overview'];
                if(isset($logo['width']))
                    $logo['width']= $logo['width']/2;
                $logo['height']= $logo['height']/2;
            }else
                $logo= array();
            
            $table= new st_tableTag();
                $table->border(0);
                $table->cellpadding(0);
                $table->cellspacing(0);
                $table->width("100%");  
                $a= new ATag();
                    $entryPoint= $externSideCreator->getLoginEntryPointUrl();
                    $a->href($entryPoint.$get->getUrlParamString());
                    $a->target("_top");
                    $img= new ImageTag();
                        if(isset($logo['img'])) 
                            $img->src($logo['img']);
                        if(isset($logo['width']))
                            $img->width($logo['width']);
                        if(isset($logo['height']))
                            $img->height($logo['height']);
                        $img->border(0);
                        $img->alt($logo['alt']);
                    $a->add($img);
                $table->add($a);
                $styleString= "";
                if(isset($logo['background']))
                {
                    $styleString= "background-image: url('{$logo['background']}');";
                    if(isset($logo['height']))
                    {
                        $styleString.= " background-size: auto {$logo['height']};";
                        if($logo['background-repeat'] == false)
                            $styleString.= " background-repeat: no-repeat;";
                    }
                    $table->columnStyle($styleString);
                }
                $navSpan= new SpanTag("smallBoldFont");
                    $script= new JavaScriptTag();
                        $function= new jsFunction("myOnSubmit", "myTarget");
                            $function->add("top.location.href = myTarget;");
                            $function->add("return false;");
                        $script->add($function);
                    $navSpan->add($script);
                    $form= new FormTag();
                        $form->name("myForm");
                        $form->onSubmit("myOnSubmit( myForm.mySelect.value )");
                        $form->add("Web-Aplikation: ");
                        $select= new SelectTag();
                            $select->onChange("top.location.href=this.value;");
                            $select->name("mySelect");
                        foreach( $this->accessableProjects as $project )
                        {
                            if($project['Target'] == "SELF")
                            {
                                $projectID= $project['ID'];
                                $option= new OptionTag();
                                    $get->update("ProjectID=$projectID");
                                    $option->value($get->getUrlParamString());
                                    $option->add($project['Name']);
                                if($projectID == $available['project'])
                                    $option->selected();
                                $select->add($option);
                            }
                        }
                        $form->add($select);
                        $input= new InputTag("button");
                            $input->type("submit");
                            $input->value("GO&nbsp;&gt;&gt;");
                        $form->add($input);
                    $navSpan->add($form);
                $table->add($navSpan);
                if(isset($logo['background']))
                        $table->columnStyle($styleString);
                $table->columnWidth("100%");
                $table->columnAlign("center");
                $table->columnClass("fontSmaller");
                //$table->columnStyle("font-weight:bold;");
                $table->columnNowrap();
                $div= new DivTag();
                if($available['LoggedIn'])
                {                        
                    $logout= $user->getLogoutButton( "Logout" );
                    $div->add($logout);
                    $div->add(br());
                    $div->add("&#160;logged&#160;In&#160;as:&#160;");
                    $b= new BTag();
                        $span= new SpanTag("colorONE");
                            $span->add($user->getUserName());
                        $b->add($span);
                        $b->add("&nbsp;&nbsp;");
                    $div->add($b);
                }else
                {
                    $login= $user->getLogoutButton("Login");
                    $div->add($login);
                    $div->add(br());
                    $div->add(br());                        
                }
                $table->add($div);
                $table->width("100%");
                $table->columnAlign("right");
            $this->append($table); 
        }
        foreach($this->addedContent as $tag)
        {
            $this->appendObj($tag);
        }
        if( $available['show'] != "navigation" &&
            (   !$available['LoggedIn'] ||
                (   $available['project'] != 0 &&
                    !$this->isProjectAvailable($available['project'])    )   )   )
        {
            $this->getLoginMask($available);  
            $this->appendObj($this->loginMask);
        }
        if( $available['show'] == "list" &&
            $this->hasProjects()            )
        {
            $this->getAccessibleProjectList();
            $this->appendObj($this->accessibilityProjectMask);
        }
        return "NOERROR";
    }
    private function getLoginErrorString(array $available) : string
    {
        if(isset($this->loginMask))
            return $this->loginMask;

        $onloadTerm= "self.focus();document.loginform.";
        if( isset($_GET['user']) &&
            trim($_GET['user']) != ""   )
        {
            $onloadTerm.= "user";
        }else
            $onloadTerm.= "pwd";
            $onloadTerm.= ".focus()";
            $this->insertAttribute("onload", $onloadTerm);
       
        $errorString= "";
        if($available['error'] == 0)
            return "";
        $content= null;
        switch ($available['error'])
        {
            case 1:
                $errorMsg= "noUserAccess";//mit diesem User-Namen besteht keine Berechtigung";
                break;
            case 2:
                $errorMsg= "wrongUserPassword";//"Passwort stimmt nicht mit User-Namen &uuml;berein";
                break;
            case 3:
                $errorMsg= "multipleUser";//"Multiple UserName in LDAP found!";
                break;
            case 4:
                $errorMsg= "externalAuthenticationError";//"Unknown error in LDAP authentication!";
                break;
            case 5:
                $errorMsg= "noPermission";//"Sie haben keinen Zugriff auf diese Daten!";
                break;
            default:
                $errorMsg= "unknownError@";//<b>UNKNOWN</b> Error type ($error) found!";
                $content= "$error";
                break;
        }
        $errorString= $this->getMessageContent($errorMsg, $content);
        if( $available['error'] == 5 &&
            $available['LoggedIn'] &&
            !$available['access']       )
        {
            $errorString.= " ".$this->getMessageContent("doNewLogin");
        }
        return $errorString;
    }
    private function &getLoginMask(array $available) : object
    {
        //$Get->delete("user");
        $session= STSession::instance();
        $user= $session->getUserName();
        if(	$user == "" &&
            isset($_GET["user"]) &&
            $_GET["user"]		)
        {
            $user= $_GET["user"];
        }
        
        $div= new DivTag("STLoginDiv");
            $div->add(br());
            $div->add(br());
            $div->add(br());
            $div->add(br());
            $layout= new st_tableTag();
                $layout->border(0);
                $layout->add("&#160;");
                $layout->columnWidth("10%");
                $divx= new DivTag("loginMaskDescription");
                if(isset($_GET["debug"]))
                {
                    $Get= new STQueryString();
                    $Get->delete("ERROR");
                    $Get->delete("doLogout");
                    $Get->delete("from");
                    $action= $Get->getStringVars();
                    $divx->add("INPUT of form-tag sending to address <b>'$action'</b><br />");
                }
                    $maskDescription= $this->getMessageContent("LoginMaskDescription");
                    $divx->add($maskDescription);
                $layout->add($divx);
                $layout->colspan(3);
            
            $layout->nextRow();
            $errorString= $this->getLoginErrorString($available);
            if($errorString != "")
            {
                $layout->add("&#160;");
                $layout->add("&#160;");
                $divE= new DivTag("loginError");
                    $divE->add($errorString);
                $layout->add($divE);
                $layout->colspan(2);
                $layout->nextRow();
                    
            }
            if(!$available['LoggedIn'])
            {
                $layout->add("&#160;");
                $layout->columnWidth("5%");
                $layout->add("&#160;");
                $layout->columnWidth("5%");
                $layout->add("&#160;");
                $layout->columnWidth("10%");
                    $table= $this->getLoginFormTable($user);
                $layout->add($table);
            }
            $div->add($layout);
            $script= new JavaScriptTag();
                $inputPos= 0;
                if($user != "")
                    $inputPos= 1;
                $function= new jsFunction("doFocus");
                    $function->add("tag= document.getElementsByClassName('loginInput');");
                    $function->add("tag[$inputPos].focus();");
                $script->add($function);
                $script->add("window.onload= doFocus();");
                //$script->add("inp= document.getElementsByClassName('loginInput');");
                //$script->add("console.log(inp);");
                //$script->add("inp[1].focus();");
            $div->add($script);
        $this->loginMask= $div;
        return $this->loginMask;
    }
    private function getLoginFormTable(string $user)
    {
        $Get= new STQueryString();
        $Get->delete("ERROR");
        $Get->delete("doLogout");
        $Get->delete("from");
        $action= $Get->getStringVars();
        
        $table= new TableTag("loginTable");
            $table->border(0);
            $table->cellpadding(0);
            $table->cellspacing(0);
            //$table->style("border-width:1; border-style:outset; border-darkcolor:#000000; border-lightcolor:#ffffff");
            $form= new FormTag();
                $form->name("loginform");
                $form->action($action);
                $form->method("post");
                $tr= new RowTag();
                    $td= new ColumnTag();
                        $td->width(80);
                        $td->align("right");
                        $p= new PTag();
                            $p->style("margin-right: 4;");
                            $p->add("User Name:&#160; ");
                        $td->add($p);
                    $tr->add($td);
                    $td= new ColumnTag();
                        $td->width(175);
                        $input= new InputTag("loginInput");
                            $input->type("text");
                            $input->name("user");
                            $input->maxlen(60);
                            $input->size(28);
                            $input->tabindex(1);
                            if($user == "")
                                $input->autofocus();
                            $input->value($user);
                            $td->add($input);
                        $tr->add($td);
                    $td= new ColumnTag();
                        $td->width(100);
                        $td->rowspan(2);
                        $td->valign("top");
                        $td->align("center");
                        $p= new PTag();
                            $p->style("margin-top:3; margin-left:10");
                            $input= new InputTag("myInput");
                                $input->type("submit");
                                $input->tabindex(3);
                                $input->value("Login");
                            $p->add($input);
                        $td->add($p);
                    $tr->add($td);
                $form->add($tr);
                $tr= new RowTag();
                    $td= new  ColumnTag();
                        $td->width(80);
                        $td->align("right");
                        $p= new PTag();
                            $p->style("margin-right: 4;");
                            $p->add("Password:&#160; ");
                        $td->add($p);
                    $tr->add($td);
                    $td= new  ColumnTag();
                        $td->width(175);
                        $input= new InputTag("loginInput");
                            $input->type("password");
                            $input->name("pwd");
                            $input->tabindex(2);
                            if($user != "")
                                $input->autofocus();
                            $input->size(28);
                            $input->maxlen(60);
                        $td->add($input);
                    $tr->add($td);
                    $td= new  ColumnTag();
                        $input= new InputTag();
                            $input->type("hidden");
                            $input->name("doLogin");
                            $input->value(1);
                        $td->add($input);
                    $tr->add($td);
                $form->add($tr);
            $table->add($form);  
        return $table;
    }
    private function &getAccessibleProjectList() : object
    {
        if(isset($this->accessibilityProjectMask))
            return $this->accessibilityProjectMask;
         
        $div= new DivTag("AccessibleProjectList");
            $table= new st_tableTag("ListTable");
                $table->border(0);
                $table->cellpadding("10px");
                $table->cellspacing("2px");
                $table->add("&#160");
                $table->columnWidth("20%");
                $p= new PTag("AccessibilityProjects");
                    $p->add(br());
                    $accessibilityString= $this->accessibilityString;
                    if($accessibilityString == "")
                        $accessibilityString= $this->getMessageContent("AccessibilityProjectString");
                    $p->add($accessibilityString);
                $table->add($p);
                $table->colspan(2);
            $table->nextRow();
                $table->add("&#160;");
                $table->columnWidth("20%");
                $table->add("&#160;");
                $table->columnWidth("10%");
                $divo= new DivTag();
                    $lu= new  st_tableTag("ListTable");
                    foreach( $this->accessableProjects as $project )
                    {
                        $divI= new DivTag();
                            $a= new ATag();
                            if( $project['Target'] == "SELF" ||
                                $project['Path'] == "X"         )
                            {
                                $href= "?ProjectID=";
                                $href.= urlencode( $project['ID'] );
                                $session= STSession::getSessionUrlParameter();
                                if($session != "")
                                    $href.= "&".$session;
                            }else
                                $href= $project['Path'];

                                $a->href($href);
                                $a->target("_".strtolower($project['Target']));
                                $a->style("font-size:12pt;font-weight=bold;");
                                $a->add($project[ 'Name' ]);
                            $divI->add($a);
                            $divI->add(br());
                            $divI->add($project[ 'Description' ]);
                        $lu->add($divI);
                        $lu->nextRow();
                    }
                    $divo->add($lu);
                    $divo->add(br());
                    $divo->add(br());
                $table->add($divo);
                $table->columnValign("top");
                $table->columnAlign("left");
            $div->add($table);
        $this->accessibilityProjectMask= $div;
        return $this->accessibilityProjectMask;
    }
}

?>