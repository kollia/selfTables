<?php 

require_once( $_stsession );
require_once( $_stdbsessionhandler );


class STDbSession extends STSession
{
    private $bStoreFile= false;
    
    protected function __construct()
    {
        // nothing to do
        STSession::__construct();
    }
    public static function init(&$Db, $prefix= null)
    {
        global $global_selftable_session_class_instance, $DBin_UserDatabase;
        
        $define_table= false;
        if(!isset($global_selftable_session_class_instance[0]))
        {
            $global_selftable_session_class_instance[0]= new STDbSession($Db, $prefix);
            $define_table= true;
        }
        STSession::init($Db);
        $desc= &STDbTableDescriptions::instance($Db->getName());        
        $desc->table("Sessions");
        $desc->column("Sessions", "ses_id", "varchar(32)", false);
        $desc->primaryKey("Sessions", "ses_id");
        $desc->column("Sessions", "ses_time", "INT", false);
        $desc->column("Sessions", "ses_value", "MEDIUMTEXT", false);
        
        if($define_table && isset($prefix))
        {
            $desc= &STDbTableDescriptions::instance($this->database->getName());
            $desc->setPrefixToTables($prefix);
        }
    }
    public function storeSessionOnFile()
    {
        $this->bStoreFile= true;
    }
    protected function session_storage_place()
    {
        if(!$this->bStoreFile)
            session_set_save_handler( new STDbSessionHandler($this->database), true);
        else
            STSession::session_store_place();
    }
}

?>