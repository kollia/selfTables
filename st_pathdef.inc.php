<?php

	//--------------------------------------------------------------------------
	//
	//         allowed debug string's for STCheck::debug("<string>")
	//        ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	//
	//     true		             -  only set to debug and check parameters in some method's (true is an boolean no string)
	//     query                 -  show incomming query of GET /POST URL's or uploaded FILES
    //     query.limitation      -  show manipulation of query for new container or action set
    //     performance           -  show needed performance of time from hole site
    //     db.descriptions       -  show which tables are described on database
	//     db.statement          -  show created statements of self-Tables
	//     db.statement.from     -  show also trace from were statement was called
	//     db.statement.time     -  show how long statement need to fetch from database
	//     db.statement.modify   -  show creation of insert/update statement
    //     db.statement.insert   -  same as db.statement.modify
    //     db.statement.update   -  same as db.statement.modify
	//     db.statements.select  -  show select statement by creation
	//     db.statements.table   -  show part of table creation statement
	//     db.statments.where    -  show part of creation by where statement
	//     db.statements.aliases -  alias definition for table's
	//     db.table.fk           -  show FOREIGN KEY definitions
	//	   db.test				 -	allow only 'select' and 'show' commands on database, for each other write only statement on debug output
	//     db.test.session       -  by testing with db.test it output normaly sql statements but update and insert anyway sessions on db. with db.test.session it makes also the same behavior for sessions
	//     show.db.fields        -  by display db fields inside INSERT or UPDATE box, show flags of field
	//     container             -  show container creation and initialling
    //     containerChoice       -  show also choise of container for backbutton
	//     table                 -  show table creation and initialling
	//     access                -  show whether user has access to different objects
	//     session               -  show all about a session
	//     listbox.properties    -  show column properties for every row insede STListBox creation 
    //     itembox.columns       -  show all column content for every row insede STItemBox creation
	//     log                   -  tracing recursive function names passed to calling one or more before defined position 
	//
	//--------------------------------------------------------------------------
	
    $__globally_debug_defined= false;
    function global_debug_definition(bool $define)    
    {
        global $__globally_debug_defined;
        
        $__globally_debug_defined= $define;
        if($define)
        {
    		ini_set('display_errors', 1);
    		ini_set('display_startup_errors', 1);
    		error_reporting(E_ALL);
    	}else
    		error_reporting(E_ERROR | E_WARNING | E_PARSE);
    }
    global_debug_definition($__globally_debug_defined);

	//--------------------------------------------------------------------------
	// set php variables
	//--------------------------------------------------------------------------
	$PHP_SELF= $_SERVER["SCRIPT_NAME"];
	
	$HTTP_SERVER_VARS= &$_SERVER;
	$HTTP_GET_VARS= &$_GET;
	$HTTP_POST_VARS= &$_POST;
	$HTTP_COOKIE_VARS= &$_COOKIE;
	//$HTTP_SESSION_VARS= &$_SESSION;
	/**
	 * function to define the global SESSION variable
	 * after session_start() inside method <code>STSession::registerSession()</code>	 * 
	 * for all older PHP versions
	 * 
	 * @param globalVar variable which should be defined with global SESSION variable  
	 */
	function register_global_SESSION_VAR(&$globalVar)
	{
		global $HTTP_SESSION_VARS;
		
		$globalVar= $_SESSION;
		//$HTTP_SESSION_VARS= $_SESSION;
	}
	//--------------------------------------------------------------------------

	$__defined_include_paths= null;
	function st_check_require_once($file)
	{
		global $__globally_debug_defined;
		global $__defined_include_paths;

		if($__globally_debug_defined)
		{
			$trace= debug_backtrace();
			if(!isset($__defined_include_paths))
			{
				$include_path= get_include_path();
				$__defined_include_paths= preg_split("/:/", $include_path);
			}
			$ffile= $file;
			if(substr($file, 0, 1) != "/")
			{
				foreach($__defined_include_paths as $path)
				{
					if(file_exists("$path/$file"))
					{
						$ffile= "$path/$file";
						break;
					}
				}
			}
			if(	!isset($file) ||
				trim($file) == "" ||
				is_numeric($file) ||
				!file_exists($ffile)	)
			{
				echo "ERROR: file in require_once('$file') does not exist<br>";
				echo "<b>file:</b>".$trace[0]['file']. "  <b>line:</b>".$trace[0]['line']."<br>";
        		throw new Exception ("require_once('$file') file does not exist");
			}
			if(!is_readable($ffile))
			{
				echo "ERROR: file in require_once('$file') is not readable<br>";
				echo "<b>file:</b>".$trace[0]['file']. "  <b>line:</b>".$trace[0]['line']."<br>";
        		throw new Exception ("require_once('$file') file is not readable");
			}
			echo "<b>found require_once('</b>$file<b>')</b> on <b>file:</b>".$trace[0]['file']. "  <b>line:</b>".$trace[0]['line']."<br>";
		}
		
	}

	define("STBLINDDB", "STBLINDDB");
	define("MYSQL_NUM", 0x10);
	define("MYSQL_ASSOC", 0X01);
	define("MYSQL_BOTH", 0x11);
	define("STSQL_NUM", MYSQL_NUM);
	define("STSQL_ASSOC", MYSQL_ASSOC);
	define("STSQL_BOTH", MYSQL_BOTH);

	define("STCHOOSE", "choose");
	define("STLIST",   "list");
	define("STINSERT", "insert");
	define("STUPDATE", "update");
	define("STDELETE", "delete");
	define("STADMIN", "adminAccess");
	define("STALLDEF", "##all");

	define("STPOST", "post");
	define("STGET", "get");

	define("STLOGIN", 0);
	define("STLOGIN_ERROR", 1);
	define("STLOGOUT", 2);
	define("STACCESS", 3);
	define("STACCESS_ERROR", 4);
	define("STDEBUG", 5);
	
	define("STINNERJOIN", "stinnerjoin");
	define("STOUTERJOIN", "stouterjoin");
	define("STLEFTJOIN", "stleftjoin");
	define("STRIGHTJOIN", "strightjoin");

	define("STHORIZONTAL", 0x1);// Horizontal divison of the rows
	define("STVERTICAL", 0x2);// Vertikal divison of the rows

	define("noErrorShow", 0);// no error is listed, method runs through
	define("onDebugErrorShow", 1);// an error is only listed in debug mode
	define("onErrorMessage", 2);// the first error is only displayed using a message box,
	                            // if the method has no posibility to show a message box,
	                            // and also in the debug session, the display is: onDebugErrorShow
	define("onErrorShow", 3);// the error is displayed but method runs through
	define("onErrorStop", 4);// the error is displayed and the program will be terminated


	//old defines
	define("ST_LIST", "list");
	define("ST_CHOOSE", "choose");
	define("INSERT", "insert");
	define("UPDATE", "update");
	define("DELETE", "delete");
	define("POST", "post");
	define("GET", "get");
	define("HORIZONTAL", 0x1);// Horizontale Gliederung der �berschrift
	define("VERTICAL", 0x2);// Vertikale Gliederung der �berschrift
	define("LOGIN", 0);
	define("LOGIN_ERROR", 1);
	define("LOGOUT", 2);
	define("ACCESS", 3);
	define("ACCESS_ERROR", 4);
	define("DEBUG", 5);

	// n�chsten 2 werden fallen
	define("addUser", "addUser");
	define("addGroup", "addGroup");

	define("NUM_OSTfetchArray", -1);// erzeugt in der Datenbank ein Array zur Suche
	define("ASSOC_OSTfetchArray", -2);// erzeugt in der Datenbank ein Array zur Suche
  	define("BOTH_OSTfetchArray", -3);// erzeugt in der Datenbank ein Array zur Suche

	// globaly variables
	$global_first_objectContainer= null;
	$global_boolean_install_objectContainer= false;
	$global_array_exist_stobjectcontainer_with_classname= array();
	$global_array_all_exist_stobjectcontainers= array();
	// all query parameter shouldn't calculated
	// inside stget number from database
	$global_selftables_do_not_allow_sth= array();
	// log messages will be write as this variable
	// and should be deltet before create side
	// to beginning with an empty log-file
	$global_logfile_dataname= "develop.log";
	$global_last_backtrace= array();
	// for save one item of STSession object
	// php can not send only an object in an global var,
	// so it is packed in an array
	$global_selftable_session_class_instance= array();
	////////////////////////////////////////////
		//$client_root=				$_SERVER['DOCUMENT_ROOT'];
		$client_root=				"/";
		$_stenvironmenttools_path=	__DIR__."/environment";
		$_stcmstools_path=			__DIR__."/plugins";
		$_defaultScripts=			$client_root."/defaultScripts/";
		$_tinymce_path=				$_defaultScripts."tiny_mce/";
		$default_css_link=			$_defaultScripts."default.css";
		$_st_set_session_global=	false;
		$_st_max_query_length=		 0;
		$_st_max_debug_query_length= 0;


		$_sttools=					$_stenvironmenttools_path."/stTools.php";
		$_stcheck=					$_stenvironmenttools_path."/html/STCheck.php";
		$php_html_description=		$_stenvironmenttools_path."/html/Tags.php";
		$_stquerystring=			$_stenvironmenttools_path."/html/STQueryString.php";
		$_stpostarray=				$_stenvironmenttools_path."/html/STPostArray.php";
		$php_javascript=			$_stenvironmenttools_path."/html/JavascriptTag.php";
		
		$_stmessagehandling=		$_stenvironmenttools_path."/base/STMessageHandling.php";
		$_stcallbackclass=			$_stenvironmenttools_path."/base/STCallbackClass.php";
		$_stbasebox=			    $_stenvironmenttools_path."/base/STBaseBox.php";
		$_stbasetable=				$_stenvironmenttools_path."/base/STBaseTable.php";
		$_stitembox=				$_stenvironmenttools_path."/base/STItemBox.php";
		$_stlistbox=				$_stenvironmenttools_path."/base/STListBox.php";
		$_stsearchbox=				$_stenvironmenttools_path."/base/STSearchBox.php";
		$_stcategorygroup=			$_stenvironmenttools_path."/base/STCategoryGroup.php";
		$_stbasesearch=				$_stenvironmenttools_path."/base/STBaseSearch.php";
		$_stchoosebox=				$_stenvironmenttools_path."/base/STChooseBox.php";
		$stselectbox=				$_stenvironmenttools_path."/base/STSelectBox.php";
		$_stdownload=				$_stenvironmenttools_path."/base/STDownload.php";
		$_stbasecontainer=			$_stenvironmenttools_path."/base/STBaseContainer.php";
		$_stframecontainer=			$_stenvironmenttools_path."/base/STFrameContainer.php";
		$_stobjectcontainer=		$_stenvironmenttools_path."/base/STObjectContainer.php";
		$_tinymce=					$_stenvironmenttools_path."/base/TinyMCE.php";
		$_tinymce_row=				$_stenvironmenttools_path."/base/TinyMCE_row.php";
		$_stsitecreator=			$_stenvironmenttools_path."/base/STSiteCreator.php";
		  
		$_stdatabase=				$_stenvironmenttools_path."/db/STDatabase.php";
		$_stdbmariadb=              $_stenvironmenttools_path."/db/STDbMariaDB.php";
		$_stdbmysql=				$_stenvironmenttools_path."/db/STDbMySql.php";
		$_stdbtable=				$_stenvironmenttools_path."/db/STDbTable.php";
		$_stdbdeftable=				$_stenvironmenttools_path."/db/STDbDefTable.php";
		$_stdbwhere=				$_stenvironmenttools_path."/db/STDbWhere.php";
		$_stdbselector=				$_stenvironmenttools_path."/db/STDbSelector.php";
		$_stdbinserter=				$_stenvironmenttools_path."/db/STDbInserter.php";
		$_stdbdefinserter=			$_stenvironmenttools_path."/db/STDbDefInserter.php";
		$_stdbsqlcases=             $_stenvironmenttools_path."/db/STDbSqlCases.php";
		$_stdbupdater=				$_stenvironmenttools_path."/db/STDbUpdater.php";
		$_stdbdeleter=				$_stenvironmenttools_path."/db/STDbDeleter.php";
		$_stdbtablecreator= 		$_stenvironmenttools_path."/db/STDbTableCreator.php";
		$_stdbtabledescriptions=	$_stenvironmenttools_path."/db/STDbTableDescriptions.php";
		$_stdbsitecreator=          $_stenvironmenttools_path."/db/STDbSiteCreator.php";
		
		$_stuser=					$_stenvironmenttools_path."/user/STUser.php";
		$_stsession=				$_stenvironmenttools_path."/user/STSession.php";
		$_stdbsession=              $_stenvironmenttools_path."/user/STDbSession.php";
		$_stdbsessionhandler=       $_stenvironmenttools_path."/user/STDbSessionHandler.php";
		$_stsessionsitecreator=		$_stenvironmenttools_path."/user/STSessionSiteCreator.php";
		$_stusersitecreator=		$_stenvironmenttools_path."/user/STUserSiteCreator.php";


		/**********************************************************************\
		|**         selfTables - CMS System                                  **|
		\**********************************************************************/
		//$DBin_UserDatabase=       "UserManagement";
		// UserManagement Login
		//$USERCLASS=               "STUser";
		$_stusersession=                    $_stcmstools_path."/usermanagement/STUserSession.php";
		$_stusermanagement=					$_stcmstools_path."/usermanagement/STUserManagement.php";
		$_stuserclustergroupmanagement=     $_stcmstools_path."/usermanagement/STUserClusterGroupManagement.php";
		
		$_stclustergroupmanagement=			$_stcmstools_path."/usermanagement/STClusterGroupManagement.php";
		
		$_stum_installcontainer=			$_stcmstools_path."/usermanagement/STUM_InstallContainer.php";
		$_stusermanagement_install=			$_stcmstools_path."/usermanagement/stusermanagement_install.php";
		$_stuserprofilecontainer=           $_stcmstools_path."/usermanagement/STUserProfileContainer.php";
		$_stusermanagementsession=			$_stcmstools_path."/usermanagement/STUserManagementSession.php";
		$_stuserprojectmanagement=	        $_stcmstools_path."/usermanagement/STUserProjectManagement.php";
		$_stprojectmanagement=				$_stcmstools_path."/usermanagement/STProjectManagement.php";
		$_stpartitionmanagement=			$_stcmstools_path."/usermanagement/STPartitionManagement.php";
		$_stusergroupmanagement=			$_stcmstools_path."/usermanagement/STUserGroupManagement.php";
		$_stgroupgroupmanagement=			$_stcmstools_path."/usermanagement/STGroupGroupManagement.php";
		$_stgallerycontainer_install=		$_stcmstools_path."/gallery/STGalleryContainer_install.php";
		$_stgallerycontainer=				$_stcmstools_path."/gallery/STGalleryContainer.php";
		$_stsubgallerycontainer=			$_stcmstools_path."/gallery/STSubGalleryContainer.php";
		$_stseriescontainer_install=		$_stcmstools_path."/calendar/stseriescontainer_install.php";
		$_stcalendarserie=					$_stcmstools_path."/calendar/STCalendarSerieForm.php";

		//st_check_require_once($_sttools);
		//echo __FILE__.__LINE__."<br>";
		require_once($_sttools);
		require_once($_stcheck);
?>
