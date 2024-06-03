<?php

global $_dbselftable_tinymce_path;

//$_dbselftable_client_root=				    $_SERVER['DOCUMENT_ROOT'];
$_dbselftable_client_root=                      "";
$_dbselftable_defaultScripts=			        $_dbselftable_client_root."design/";
$_dbselftable_tinymce_path=                     $_dbselftable_defaultScripts."tinymce/";
$global_st_plugins['editors']['_tinymce']=		 __DIR__."/TinyMCE.php";
$global_st_plugins['editors']['_tinymce_row']=	 __DIR__."/TinyMCE_row.php";

?>