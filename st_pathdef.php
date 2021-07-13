<?php

	//--------------------------------------------------------------------------
	//
	//         allowed debug string's for STCheck::debug("<string>")
	//        ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	//
	//     true		-	only set to debug and check parameters in some method's (true is an boolean no string)
	//     query    -   show incomming query of GET or POST URL's
	//     db.statement          -  show created statements of self-Tables
	//     db.statement.time     -  show how long statement need to fetch from database
	//     db.statements.select  -  show select statement by creation
	//     db.statements.table   -  show part of table creation statement
	//     db.statments.where    -  show part of creation by where statement
	//     db.statements.aliases -  alias definition for table's
	//     db.table.fk           -  show FOREIGN KEY definitions
	//     show.db.fields        -  by display db fields inside INSERT or UPDATE box, show flags of field
	//     container             -  show container creation and initialling
	//     table                 -  show table creation and initialling
	//     access                -  show whether user has access to different objects
	//     log                   -  tracing recursive function names passed to calling one or more before defined position 
	//
	//--------------------------------------------------------------------------
	

	error_reporting(E_ERROR | E_WARNING | E_PARSE);
	//--------------------------------------------------------------------------
	// set php variables
	//--------------------------------------------------------------------------
	$PHP_SELF= $_SERVER["SCRIPT_NAME"];
	
	$HTTP_SERVER_VARS= &$_SERVER;
	$HTTP_GET_VARS= &$_GET;
	$HTTP_POST_VARS= &$_POST;
	$HTTP_COOKIE_VARS= &$_COOKIE;
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
		
		if(phpversion() < 5)
		{
			foreach($this->aSessionVars as $var)
			{
				session_register($var);
				global $$var;
				$this->session_vars[$var]= &$$var;
			}
		}else
		{			
			$globalVar= $_SESSION;
			$HTTP_SESSION_VARS= $_SESSION;
		} 
	}
	//--------------------------------------------------------------------------
	
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

	define("STHORIZONTAL", 0x1);// Horizontale Gliederung der Reihen
	define("STVERTICAL", 0x2);// Vertikale Gliederung der Reihen

	define("noErrorShow", 0);// Es wird kein Fehler aufgelistet, Methode rennt durch
	//define("noDebugErrorShow", 4);// auch im Debug Modus wird kein Fehler aufgelistet
	define("onErrorShow", 1);// Der Fehler wird angezeigt, aber Methode rennt durch
	define("onErrorStop", 2);// Der Fehler wird angezeigt und das Programm beendet
	define("onErrorMessage", 3);// Der erste Fehler wird mittels Message-Box angezeigt


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

	// globaly varibles
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
		$toolsPath=                 "selftables";
		$host=						"localhost";
		$_stenvironenttools_path=	$toolsPath."/environent";
		$_stcmstools_path=			$toolsPath."/plugins";
		$php_tools=					"tools.php";
		$_defaultScripts=			$client_root."/defaultScripts/";
		$_tinymce_path=				$_defaultScripts."tiny_mce/";
		$default_css_link=			$_defaultScripts."default.css";
		$_st_set_session_global=	false;
		$_st_max_query_length=		 0;
		$_st_max_debug_query_length= 0;

		// UserManagement Login
		$USERCLASS=					"OSTUser";
		$DBin_UserHost=				"localhost";
		$DBin_UserUser=				"usermanagement";
		$DBin_UserPwd=				"hupfauf45";
		$DBin_UserDatabase=			"UserManagement";
		//$st_user_login_mask=		"http://".$host.$client_root."/login.php";
		$st_user_navigator_mask=	"/show.php";


		$php_tools_class=			$_stenvironenttools_path."/myTools.php";
		$_stunderphp40=				$_stenvironenttools_path."/STUnderPHP40.php";
		$_stcheck=					$_stenvironenttools_path."/html/STCheck.php";
		$php_html_description=		$_stenvironenttools_path."/html/Tags.php";
		$_stquerystring=			$_stenvironenttools_path."/html/STQueryString.php";
		$_stpostarray=				$_stenvironenttools_path."/html/STPostArray.php";
		$php_htmltag_class=			$_stenvironenttools_path."/_prev/html/GetHtml.php";
		$php_javascript=			$_stenvironenttools_path."/html/JavascriptTag.php";

		$stdbtablecontainer=		$_stenvironenttools_path."/_prev/db/STDbTableContainer.php";
		$_stdatabase=				$_stenvironenttools_path."/db/STDatabase.php";
		$_stdbmysql=				$_stenvironenttools_path."/db/STDbMySql.php";
		$_staliastable=				$_stenvironenttools_path."/db/STAliasTable.php";
		$_stdbtable=				$_stenvironenttools_path."/db/STDbTable.php";
		$_stdbdeftable=				$_stenvironenttools_path."/db/STDbDefTable.php";
		$_stdbwhere=				$_stenvironenttools_path."/db/STDbWhere.php";
		$database_where_clausel=	$_stenvironenttools_path."/_prev/db/OSTDbWhere.php";
		$database_selector=			$_stenvironenttools_path."/_prev/db/OSTDbSelector.php";
		$_stdbselector=				$_stenvironenttools_path."/db/STDbSelector.php";
		$_stdbinserter=				$_stenvironenttools_path."/db/STDbInserter.php";
		$_stdbdefinserter=			$_stenvironenttools_path."/db/STDbDefInserter.php";
		$_stdbupdater=				$_stenvironenttools_path."/db/STDbUpdater.php";
		$_stdbdeleter=				$_stenvironenttools_path."/db/STDbDeleter.php";
		$_stdbtablecreator= 		$_stenvironenttools_path."/db/STDbTableCreator.php";
		$_stdbtabledescriptions=	$_stenvironenttools_path."/db/STDbTableDescriptions.php";

		$stmessagehandling=			$_stenvironenttools_path."/box/STMessageHandling.php";
		$_ostcallbackclass=			$_stenvironenttools_path."/_prev/box/OSTCallbackClass.php";
		$base_table=				$_stenvironenttools_path."/_prev/box/OSTBaseTableBox.php";
		$insert_update=				$_stenvironenttools_path."/_prev/box/OSTBox.php";
		$table_out=					$_stenvironenttools_path."/_prev/box/OSTTable.php";
		$_stsearchbox=				$_stenvironenttools_path."/box/STSearchBox.php";
		$_stcategorygroup=			$_stenvironenttools_path."/box/STCategoryGroup.php";
		$_stbasesearch=				$_stenvironenttools_path."/box/STBaseSearch.php";
		$search_table=				$_stenvironenttools_path."/_prev/box/OSTSearchBox.php";
		$choose_table=				$_stenvironenttools_path."/box/STChooseTable.php";
		$_stsidecreator=			$_stenvironenttools_path."/box/STSideCreator.php";
		$_stsessionsidecreator=		$_stenvironenttools_path."/box/STSessionSideCreator.php";
		$_stusersidecreator=		$_stenvironenttools_path."/box/STUserSideCreator.php";
		$base_site_creator=			$_stenvironenttools_path."/_prev/box/STDbSiteCreator.php";
		$site_creator=				$_stenvironenttools_path."/_prev/box/OSTDbSiteCreator.php";
		$ostquestionbox=			$_stenvironenttools_path."/_prev/box/OSTQuestionBox.php";
		$stselectbox=				$_stenvironenttools_path."/box/STSelectBox.php";
		$_stdownload=				$_stenvironenttools_path."/box/STDownload.php";
		$_stbasecontainer=			$_stenvironenttools_path."/box/STBaseContainer.php";
		$_stframecontainer=			$_stenvironenttools_path."/box/STFrameContainer.php";
		$_stobjectcontainer=		$_stenvironenttools_path."/box/STObjectContainer.php";
		$_tinymce=					$_stenvironenttools_path."/box/TinyMCE.php";
		$_tinymce_row=				$_stenvironenttools_path."/box/TinyMCE_row.php";

		$_stsession=				$_stenvironenttools_path."/user/STSession.php";
		$_stusersession=			$_stenvironenttools_path."/user/STUserSession.php";
		$stuser=					$_stenvironenttools_path."/user/STUser.php";
		$ostuser=					$_stenvironenttools_path."/_prev/user/OSTUser.php";
		$user_admin=				$ostuser;
		$user_property=				$_stenvironenttools_path."/_prev/user/my_inc_user_check.php";
		$ostuser_projectaccess=		$_stenvironenttools_path."/_prev/user/OSTUser_ProjectAccess.php";

		$_ostusersession=			$_stenvironenttools_path."/_prev/user/OSTUserSession.php";
		$_ostsidecreator=			$_stenvironenttools_path."/_prev/box/OSTSideCreator.php";
		$mysql_database=			$_stenvironenttools_path."/_prev/db/OSTDatabase.php";
		$database_tables=			$_stenvironenttools_path."/_prev/db/OSTDbTable.php";

		$_sttabledescriptions=		$_stenvironenttools_path."/box/fault_class.php";

		/**********************************************************************\
		|**         selfTables - CMS System                                  **|
		\**********************************************************************/
		$_stum_installcontainer=		$_stcmstools_path."/usermanagement/STUM_InstallContainer.php";
		$_stusermanagement_install=		$_stcmstools_path."/usermanagement/stusermanagement_install.php";
		$_stusermanagement=				$_stcmstools_path."/usermanagement/STUserManagement.php";
		$_stprojectmanagement=			$_stcmstools_path."/usermanagement/STProjectManagement.php";
		$_stpartitionmanagement=		$_stcmstools_path."/usermanagement/STPartitionManagement.php";
		$_stusergroupmanagement=		$_stcmstools_path."/usermanagement/STUserGroupManagement.php";
		$_stgroupgroupmanagement=		$_stcmstools_path."/usermanagement/STGroupGroupManagement.php";
		$_stclustergroupmanagement=		$_stcmstools_path."/usermanagement/STClusterGroupManagement.php";
		$_stgallerycontainer_install=	$_stcmstools_path."/gallery/STGalleryContainer_install.php";
		$_stgallerycontainer=			$_stcmstools_path."/gallery/STGalleryContainer.php";
		$_stsubgallerycontainer=		$_stcmstools_path."/gallery/STSubGalleryContainer.php";
		$_stseriescontainer_install=	$_stcmstools_path."/calendar/stseriescontainer_install.php";
		$_stcalendarserie=				$_stcmstools_path."/calendar/STCalendarSerieForm.php";

?>