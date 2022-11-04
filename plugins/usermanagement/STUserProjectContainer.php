<?php 

require_once( $_stobjectcontainer );
require_once( $_stframecontainer );

class STUserProjectContainer extends STBaseContainer
{
    private $homepageLogo= null;
    private $homepageBanner= null;
    private $navigationLogo= null;
    private $navigationBanner= null;
    private $database= null;
    private $loginMaskDescription= "Please insert your Access Data:";
    private $loginMask= null;
    private $accessableProjects= array();
    private $accessibilityString= "Existing Web-Applications:";
    private $accessibilityProjectMask= null;
    private $availableSite= null;
    /**
     * add html tags before execute container
     * @var array
     */
    private $addedContent= array();
    
    public function __construct($userDb, string $name= "ProjectAccess", string $bodyClass= "ProjectAccessBody")
    {
        STCheck::paramCheck($userDb, 1, "STDatabase");
        
        $this->database= $userDb;
        STBaseContainer::__construct($name, $bodyClass);
    }
    public function setLoginMaskDescription(string $description)
    {
        $this->loginMaskDescription= $description;
    }
    public function setAccessibilityProjectString(string $string)
    {
        $this->accessibilityString= $string;
    }
    public function setHomepageLogo(string $address)
    {
        $this->homepageLogo= $address;
    }
    public function setHomepageBanner(string $address)
    {
        $this->homepageBanner= $address;
    }
    public function setNavigationLogo(string $address)
    {
        $this->navigationLogo= $address;
    }
    public function setNavigationBanner(string $address)
    {
        $this->navigationBanner= $address;
    }
    public function getDatabase()
    {
        return $this->database;
    }
    protected function create()
    {
        //$this->database->verifyLogin("##StartPage");
        STCheck::echoDebug("user", "entering ProjectAccess init function...");
        $cluster= $this->database->getTable("Cluster");
        $selector= new STDbSelector($cluster);
        $selector->select("Cluster", "ID", "ClusterID");
        $selector->select("Project", "ID");
        $selector->select("Project", "Name");
        $selector->select("Project", "Path");
        $selector->select("Project", "Description");//"Description");
        $selector->select("Project", "DateCreation");
        $selector->orderBy("Project", "Name");
        $selector->execute();
        $result= $selector->getResult();
        
        $user= STSession::instance();
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
        foreach( $result as $row )
        {
            if(STCheck::isDebug("user"))
                echo "<br />\n";
            $access_msg= "Search whether user has access for Project Nr. ".htmlspecialchars( $row[ 'ID' ] )."): <em>";
            $access_msg.= "<b> - " .htmlspecialchars( $row[ 'Name' ] )."</em> - </b>:";
            if( $user->hasAccess( $row[ 'ClusterID' ], $access_msg ) )
            {
                if( Tag::isDebug() )
                {
                    $msg= "<b>GRANTED</b> (with membership in Cluster(";
                    $msg.= htmlspecialchars( $row[ 'ClusterID' ] )."))";
                    STCheck::echoDebug("user", $msg);
                }
                $this->accessableProjects[ $row[ 'ID' ] ] =
                                        array(
                                            'Name' => $row[ 'Name' ],
                                            'Path' => $row[ 'Path' ],
                                            'Description' => $row[ 'Description' ],
                                            'DateCreation' => $row[ 'DateCreation' ]	);
            }else
                STCheck::echoDebug("user", "<b>DENIED</b>");
        }
        if( Tag::isDebug("user") ) 
            echo "".$this->toString();
    }
    public function toString()
    {
        echo '<br /><br /><br />';
        STCheck::echoDebug("user", "init found following accessable projects:");
        $res  = '<table border="0" bgcolor="black" cellpadding="2" cellspacing="1" >';
        $res .= '<tr><td colspan="3" bgcolor="#E0E0E0">Accessable Projects</td></tr>';
        $res .= '<tr><td bgcolor="white">ID</td><td bgcolor="white">description</td><td bgcolor="white">Path</td></tr>';
        foreach( $this->accessableProjects as $projectID =>$project ){
            $res .= '<tr><td bgcolor="white" colspan="3">'.htmlspecialchars( $project[ 'Name' ] ).'</td></tr>';
            $res .= '<tr><td bgcolor="white">'.$projectID.'</td>';
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
        
        $access= STSession::instance();
        if( isset($access) &&
            $access->isLoggedIn()  )
        {
            $this->availableSite['LoggedIn']= true;
        }else
            $this->availableSite['LoggedIn']= false;
            
        $get= new STQueryString();
        $projectID= $get->getUrlParamValue("ProjectID");
        if(!is_numeric($projectID))
            $projectID= 0;
        $this->availableSite['project']= $projectID;
        $show= $get->getUrlParamValue("show");
        if( $projectID > 0 &&
            isset($this->accessableProjects[$projectID]) &&
            (   !isset($show) ||
                $show == "frame"   )                            )
        {
            $this->availableSite['show']= "frame";
        }elseif($projectID == 0)
            $this->availableSite['show']= "list";
        elseif(isset($show))
            $this->availableSite['show']= $show;
        else
            $this->availableSite['show']= "project";
        return $this->availableSite;
    }
    function addObj(&$tag, $showWarning = false)
    {
        $this->addedContent[]= $tag;
    }
    function execute(&$externSideCreator, $onError)
    {
        STBaseContainer::execute($externSideCreator, $onError);  
        $available= $this->showAvailableSite();
        
        if($available['show'] == "frame")
        {
            $get= new STQueryString();
            $debug= $get->getUrlParamValue("debug");
            $preg= preg_split("/\?/", $this->accessableProjects[$available['project']]['Path']);
            $projectAddress= $preg[0];
            $projectQueryString= null;
            if(isset($preg[1]))
                $projectQueryString= $preg[1];
            $projectQuery= new STQueryString($projectQueryString);
            if(isset($debug))
                $projectQuery->update("debug=$debug");
            $projectQuery->delete("ProjectID");
            $projectAddress.= $projectQuery->getUrlParamString();
            $frame= new STFrameContainer();
            $frame->framesetRows("70,*");
            $frame->setFramePath("?show=navigation&ProjectID=".$available['project']);
            $frame->setFramePath($projectAddress);
            $result= $frame->execute($externSideCreator, $onError);
            
            $this->tag= "frameset";
            $this->class= "STFrame";
            foreach($frame->inherit as $tag)
            {
                STBaseContainer::append($tag);
            }
            foreach($frame->aNames as $attribute => $value)
                $this->insertAttribute($attribute, $value);
            return $result;
        }
        //st_print_r($available);
        $get= new STQueryString();
        $user= STSession::instance();
        if($available['show'] == "list")
        {
            if( isset($this->homepageLogo) ||
                isset($this->homepageBanner)    )
            {
                $table= new st_tableTag();
                    $table->border(0);
                    $table->cellpadding(0);
                    $table->cellspacing(0);
                    $table->width("100%");                    
                    $a= new ATag();
                        $a->href("index.php".$get->getUrlParamString());
                        $a->target("_top");
                        $img= new ImageTag();
                            $img->src($this->homepageLogo);
                            $img->border(0);
                            $img->alt("DB selftables Homepage");
                        $a->add($img);
                    $table->add($a);
                    $table->columnBackground($this->homepageBanner);
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
                        $table->columnBackground($this->homepageBanner);
                        $table->width("100%");
                        $table->align("right");
                    }
                $this->append($table);    
            }
        }elseif($available['show'] == "navigation")
        {  
            $get->delete("show");
            $get->delete("ERROR");
            $get->delete("ProjectID");
            if( isset($this->navigationLogo) ||
                isset($this->navigationBanner)    )
            {
                $table= new st_tableTag();
                    $table->border(0);
                    $table->cellpadding(0);
                    $table->cellspacing(0);
                    $table->width("100%");                    
                    $a= new ATag();
                        $a->href("index.php".$get->getUrlParamString());
                        $a->target("_top");
                        $img= new ImageTag();
                            $img->src($this->navigationLogo);
                            $img->border(0);
                            $img->alt("DB selftables Homepage");
                        $a->add($img);
                    $table->add($a);
                    $table->columnBackground($this->navigationBanner);
                    $navSpan= new SpanTag();
                        $script= new JavaScriptTag();
                            $function= new jsFunction("myOnSubmit", "myTarget");
                                $function->add("top.location.href = myTarget;");
                                $function->add("return false;");
                            $script->add($function);
                        $navSpan->add($script);
                        $form= new FormTag();
                            $form->name("myForm");
                            $form->onSubmit("myOnSubmit( myForm.mySelect.value )");
                            $form->add("Web-Applikation: ");
                            $select= new SelectTag();
                                $select->onChange("top.location.href=this.value;");
                                $select->name("mySelect");
                            foreach( $this->accessableProjects as $projectID => $project )
                            {
                                $option= new OptionTag();
                                    $get->update("ProjectID=$projectID");
                                    $option->value($get->getUrlParamString());
                                    $option->add($project['Name']);
                                if($projectID == $available['project'])
                                    $option->selected();
                                $select->add($option);
                            }
                            $form->add($select);
                            $input= new InputTag("button");
                                $input->type("submit");
                                $input->value("GO&nbsp;&gt;&gt;");
                            $form->add($input);
                        $navSpan->add($form);
                    $table->add($navSpan);
                    $table->columnBackground($this->navigationBanner);
                    $table->columnWidth("100%");
                    $table->columnAlign("center");
                    $table->columnClass("fontSmaller");
                    $table->columnStyle("font-weight:bold;");
                    $table->columnNowrap();
                    if($user->isLoggedIn())
                    {
                        $div= new DivTag();
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
                        $table->add($div);
                        //$table->columnBackground($this->navigationBanner);
                        $table->width("100%");
                        $table->columnAlign("right");
                    }
                $this->append($table);    
            }
        }
        foreach($this->addedContent as $tag)
        {
            $this->appendObj($tag);
        }
        if( $available['show'] != "navigation" &&
            !$available['LoggedIn']                 )
        {
            $this->getLoginMask();  
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
    private function &getLoginMask() : object
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
        $session= STSession::instance();
        $error= $session->getLoginError();
        switch ($error)
        {
            case 0:
                $errorString= "";
                break;
            case 1:
                $errorString= "mit diesem User-Namen besteht keine Berechtigung";
                break;
            case 2:
                $errorString= "Passwort stimmt nicht mit User-Namen &uuml;berein";
                break;
            case 3:
                $errorString= "Multiple UserName in LDAP found!";
                break;
            case 4:
                $errorString= "Unknown error in LDAP authentication!";
                break;
            case 5:
                $errorString= "Sie haben keinen Zugriff auf diese Daten!";
                break;
            default:
                $errorString= "<b>UNKNOWN</b> Error type (".$_GET["ERROR"].") found!";
                break;
        }
            
        $Get= new STQueryString();
        $Get->delete("ERROR");
        $Get->delete("doLogout");
        $Get->delete("from");
        //$Get->delete("user");
        $session= STSession::instance();
        $user= $session->getUserName();
        if(	$user == "" &&
            isset($_GET["user"]) &&
            $_GET["user"]		)
        {
            $user= $_GET["user"];
        }
        $action= $Get->getStringVars();
        
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
                    $divx->add("INPUT of form-tag sending to address <b>'$action'</b><br />");
                }
                    $divx->add($this->loginMaskDescription);
                $layout->add($divx);
                $layout->colspan(3);
            
            $layout->nextRow();
            if($error > 0)
            {
                $layout->add("&#160;");
                $layout->add("&#160;");
                $divE= new DivTag("loginError");
                    $divE->add($errorString);
                $layout->add($divE);
                $layout->colspan(2);
                $layout->nextRow();
                    
            }
                $layout->add("&#160;");
                $layout->columnWidth("5%");
                $layout->add("&#160;");
                $layout->columnWidth("5%");
                $layout->add("&#160;");
                $layout->columnWidth("10%");
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
                $layout->add($table);
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
                $p= new PTag();
                    $p->add(br());
                    $h1= new HTag(1);
                        $h1->add($this->accessibilityString);
                    $p->add($h1);
                $table->add($p);
                $table->colspan(2);
            $table->nextRow();
                $table->add("&#160;");
                $table->columnWidth("20%");
                $table->add("&#160;");
                $table->columnWidth("10%");
                $divo= new DivTag();
                    $lu= new  st_tableTag("ListTable");
                    foreach( $this->accessableProjects as $projectID => $project )
                    {
                        $divI= new DivTag();
                            $a= new ATag();
                                $href= "?ProjectID=";
                                $href.= urlencode( $projectID );
                                //$href.= "&To";
                                //$href.= urlencode( $project[ 'Path' ] );
                                $a->href($href);
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