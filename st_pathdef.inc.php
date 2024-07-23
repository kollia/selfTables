<?php

	//--------------------------------------------------------------------------
	//
	//         allowed debug string's for STCheck::debug("<string>")
	//        ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	//
	//     true		             -  only set to debug and check parameters in some method's 
	//                              (parameter can also be empty or boolean true)
	//                              in this case and also by all other debug strings when set
	//                              the error reporting of PHP will be set to E_ALL
	//                              with display_errors and display_startup_errors
	//     install               -  show debug info when ProjectUserManagement set to install
	//     query                 -  show incoming query of GET, POST URL's or uploaded FILES
    //     query.limitation      -  show manipulation of query for new container or action set
    //     performance           -  show needed performance of time from hole site
    //     db.descriptions       -  show which tables are described on database
	//     db.statement          -  show created statements of self-Tables 
	//     db.statement.from     -  show also trace from were statement was called
	//     db.statements.from    -  show trace also from where statement was fired to database
	//     db.statement.time     -  show how long statement need to fetch from database
	//     db.statement.modify   -  show creation of insert/update statement
    //     db.statement.insert   -  same as db.statement.modify
    //     db.statement.update   -  same as db.statement.modify
    //     db.main.statement     -  show only created main statement of displayed list table or item box
	//     db.statements.select  -  show select statement by creation
	//     db.statements.table   -  show part of table creation statement
	//     db.statements.where   -  show part of creation by where statement
	//     db.statements.aliases -  alias definition for table's
	//     db.table.fk           -  show FOREIGN KEY definitions
	//	   db.test				 -	allow only 'select' and 'show' commands on database, for each other write only statement on debug output
	//     db.test.session       -  by testing with db.test it output normally sql statements but update and insert anyway sessions on db. with db.test.session it makes also the same behavior for sessions
	//     show.db.fields        -  by display db fields inside INSERT or UPDATE box, show flags of field
	//     container             -  show container creation and initialling
    //     containerChoice       -  show also choice of container for back-button
	//     table                 -  show table creation and initialling
	//     access                -  show whether user has access to different objects
	//     session               -  show all about a session
	//     listbox.properties    -  show column properties for every row inside STListBox creation 
    //     itembox.columns       -  show all column content for every row inside STItemBox creation
	//     STMessageHandling     -  all about message handling
	//     log                   -  tracing recursive function names passed to calling one or more before defined position 
	//
	//--------------------------------------------------------------------------
	
	
	// all functions are outsourced from st_pathdef.inc.php
	// because includes/requires inside function should not
	// include the functions again
	require_once(__DIR__."/st_pathdef_function.inc.php");
	$__defined_include_paths= null;

	/**********************************************************
	 * global debug settings
	 * for development required on begin
	 */
    $__globally_debug_defined= false;
    global_debug_definition($__globally_debug_defined);

	//--------------------------------------------------------------------------
	// set php variables
	//--------------------------------------------------------------------------
	$PHP_SELF= $_SERVER["SCRIPT_NAME"];
	
	$HTTP_SERVER_VARS= &$_SERVER;
	$HTTP_GET_VARS= &$_GET;
	$HTTP_POST_VARS= &$_POST;
	$HTTP_COOKIE_VARS= &$_COOKIE;
	/**
	 * first HTTP_GET_VARS
	 * which can synchronized 
	 * with new values 
	 * @var array $global_selftables_queryArray
	 */
	$global_selftables_queryArray= null;



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
	// own global st_pathdef location
	$global_st_pathdef_inc_location_path= __DIR__."/st_pathdef.inc.php";
	////////////////////////////////////////////
		$_dbselftable_root=         __DIR__;
		$_stenvironmenttools_path=	__DIR__."/environment";
		$_stcmstools_path=			__DIR__."/plugins";
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
		$_stchoosebox=				$_stenvironmenttools_path."/base/STChooseBox.php";
		$_stdownload=				$_stenvironmenttools_path."/base/STDownload.php";
		$_stbasecontainer=			$_stenvironmenttools_path."/base/STBaseContainer.php";
		$_stframecontainer=			$_stenvironmenttools_path."/base/STFrameContainer.php";
		$_stobjectcontainer=		$_stenvironmenttools_path."/base/STObjectContainer.php";
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
		$_stdbsqlwherecases=        $_stenvironmenttools_path."/db/STDbSqlWhereCases.php";
		$_stdbupdater=				$_stenvironmenttools_path."/db/STDbUpdater.php";
		$_stdbdeleter=				$_stenvironmenttools_path."/db/STDbDeleter.php";
		$_stdbtablecreator= 		$_stenvironmenttools_path."/db/STDbTableCreator.php";
		$_stdbtabledescriptions=	$_stenvironmenttools_path."/db/STDbTableDescriptions.php";
		
		$_stuser=					$_stenvironmenttools_path."/session/STUser.php";
		$_stsession=				$_stenvironmenttools_path."/session/STSession.php";
		$_stdbsession=              $_stenvironmenttools_path."/session/STDbSession.php";
		$_stdbsessionhandler=       $_stenvironmenttools_path."/session/STDbSessionHandler.php";
		$_stusersession=            $_stenvironmenttools_path."/session/STUserSession.php";
		$_stsessionsitecreator=		$_stenvironmenttools_path."/session/STSessionSiteCreator.php";
		$_stusersitecreator=		$_stenvironmenttools_path."/session/STUserSiteCreator.php";


		/**********************************************************************\
		|**         selfTables - project - usermanagement                    **|
		\**********************************************************************/
		$_stusermanagement=					$_stenvironmenttools_path."/session/management/STUserManagement.php";
		$_stuserclustergroupmanagement=     $_stenvironmenttools_path."/session/management/STUserClusterGroupManagement.php";		
		$_stclustergroupassignment=			$_stenvironmenttools_path."/session/management/STClusterGroupAssignment.php";		
		$_stum_installcontainer=			$_stenvironmenttools_path."/session/management/STUM_InstallContainer.php";
		$_stusermanagement_install=			$_stenvironmenttools_path."/session/management/stusermanagement_install.php";
		$_st_registration_text=				$_stenvironmenttools_path."/session/management/st_registration_text.php";
		$_stbackgroundimagesdbcontainer=	$_stenvironmenttools_path."/session/management/STBackgroundImagesDbContainer.php";
		$_stprojectoverviewlist=			$_stenvironmenttools_path."/session/management/STProjectOverviewList.php";
		$_stuserprofilecontainer=           $_stenvironmenttools_path."/session/management/STUserProfileContainer.php";
		$_stusersignaturecontainer=			$_stenvironmenttools_path."/session/management/STUserSignatureContainer.php";
		$_stprojectuserframe=		        $_stenvironmenttools_path."/session/management/STProjectUserFrame.php";			
		$_stusermanagementsession=			$_stenvironmenttools_path."/session/management/STUserManagementSession.php";
		$_stprojectmanagement=				$_stenvironmenttools_path."/session/management/STProjectManagement.php";
		$_stpartitionmanagement=			$_stenvironmenttools_path."/session/management/STPartitionManagement.php";
		$_stusergroupmanagement=			$_stenvironmenttools_path."/session/management/STUserGroupManagement.php";
		$_stgroupgroupmanagement=			$_stenvironmenttools_path."/session/management/STGroupGroupManagement.php";
		$_stprojectusersitecreator=			$_stenvironmenttools_path."/session/management/STProjectUserSiteCreator.php";
		
		$_stgallerycontainer_install=		$_stcmstools_path."/gallery/STGalleryContainer_install.php";
		$_stgallerycontainer=				$_stcmstools_path."/gallery/STGalleryContainer.php";
		$_stsubgallerycontainer=			$_stcmstools_path."/gallery/STSubGalleryContainer.php";

		//st_check_require_once($_sttools);
		//echo __FILE__.__LINE__."<br>";
		require_once($_sttools);
		require_once($_stcheck);
?>
