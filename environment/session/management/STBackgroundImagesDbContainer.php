<?php

require_once $_stobjectcontainer;

abstract class STBackgroundImagesDbContainer extends STObjectContainer
{
    /**
     * for image creation
     * first add tag objects into member variable addedContent
     * after imageCreation implement into body
     * do not add more into variable addedContent
     */
    protected $bAddContent= true;
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
	 * method to create messages for different languages.<br />
	 * inside class methods (create(), init(), ...) you get messages from <code>$this->getMessageContent(<message id>, <content>, ...)</code><br />
	 * inside this method depending the <code>$language</code> define messages with <code>$this->setMessageContent(<message id>, <message>)</code><br />
	 * see STMessageHandling
	 *
	 * @param string $language current language like 'en', 'de', ...
	 * @param string $nation current nation of language like 'US', 'GB', 'AT'. If not defined, default is 'XXX'
	 */
	protected function createMessages(string $language, string $nation)
	{
        STObjectContainer::createMessages($language, $nation);
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
    public function addObj(Tag|jsFunctionBase|array|string|null &$tag, $bWarningShowed = false, int $outFunc = 1)
    {
        if($this->bAddContent)
            $this->addedContent[]= $tag;
        else
            STObjectContainer::addObj($tag);
    }
    protected function createOverviewImages(STSiteCreator $externSiteCreator, bool $bLogoutButton= false)
    {
        if(!isset($this->image['overview']['img']))
            return;
        
        $get= new STQueryString();
        $user= STSession::instance();
            
        $table= new st_tableTag();
            $table->border(0);
            $table->cellpadding(0);
            $table->cellspacing(0);
            $table->width("100%");                    
            $a= new ATag();
                $query= $user->getSessionUrlParameter();
                if($query != "")
                    $query= "?".$query;
                $userQuery= $get->getParameterValue("user");
                if(isset($userQuery))
                {
                    $userQuery= "user=".$userQuery;
                    if($query != "")
                        $query.= "&".$userQuery;
                    else
                        $query= "?".$userQuery;
                }
                $entryPoint= $externSiteCreator->getLoginEntryPointUrl();
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
            if( $bLogoutButton &&
                $user->isLoggedIn())
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

        foreach($this->addedContent as $tag)
        {
            $this->appendObj($tag);
        }
        $this->bAddContent= false;
    }
    protected function createNavigationImageBar(STSiteCreator $externSiteCreator, array $available)
    {
        $user= STSession::instance();
        $get= new STQueryString();
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
                $entryPoint= $externSiteCreator->getLoginEntryPointUrl();
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
                    if(isset($logo['alt']))
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
                    $form= $this->getAccessibleChooseBox();
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

        foreach($this->addedContent as $tag)
        {
            $this->appendObj($tag);
        }
        $this->bAddContent= false;
    }
    public function execute(&$externSiteCreator, $onError)
    {
		$session= STUserSession::instance();
		$registration= $session->getSessionVar("ST_REGISTRATION");
        if($registration)
            $this->createOverviewImages($externSiteCreator);
        // do not need overview images more
        $this->bAddContent= false;
        return STObjectContainer::execute($externSiteCreator, $onError);
    }
}

?>