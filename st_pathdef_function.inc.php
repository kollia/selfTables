<?php

// all functions are outsourced from st_pathdef.inc.php
// because includes/requires inside function should not
// include the functions again
// and also some global variables

	// globaly variables
	$global_first_objectContainer= null;
	$global_boolean_installed_objectContainer= false;
	$global_array_exist_stobjectcontainer_with_classname= array();
	$global_array_all_exist_stobjectcontainers= array();

define("STBLINDDB", "STBLINDDB");
define("MYSQL_NUM", 0x10);
define("MYSQL_ASSOC", 0X01);
define("MYSQL_BOTH", 0x11);
define("STSQL_NUM", MYSQL_NUM);
define("STSQL_ASSOC", MYSQL_ASSOC);
define("STSQL_BOTH", MYSQL_BOTH);

define("NUM_STfetchArray", -1);// erzeugt in der Datenbank ein Array zur Suche
define("ASSOC_STfetchArray", -2);// erzeugt in der Datenbank ein Array zur Suche
define("BOTH_STfetchArray", -3);// erzeugt in der Datenbank ein Array zur Suche


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

function global_debug_definition(bool $define)    
{
    if($define)
    {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
    }else
        error_reporting(E_ERROR | E_WARNING | E_PARSE);
}

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

function remove_document_root_or_include_path($dir)
{
    $document_root= $_SERVER['DOCUMENT_ROOT'];
    if(preg_match("#^$document_root#", $dir))
    {
        $sRv= substr($dir, strlen($_SERVER['DOCUMENT_ROOT']));
        return $sRv;
    }
    $include_path= preg_split('/:/', get_include_path());
    foreach($include_path as $path)
    {
        $rpath= preg_replace("/\./", "\\.", $path);
        if(preg_match("#^$rpath#", $dir))
        {
            $sRv= substr($dir, strlen($path)+1);
            return $sRv;
        }
    }
    return $dir;
}

?>