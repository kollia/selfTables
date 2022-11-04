<?php 

require_once( $_stsession );
require_once( $_stdbsessionhandler );


/**
 if you store sessions in databases, check that garbage collecting of sessions in PHP 
 is really activated (it's not the case on Debian-like distributions, they decided to 
 garbage sessions with their own cron and altered the php.ini so that it never launch 
 any gc, so check the session.gc_probability and session.gc_divisor). The main problem 
 of sessionstorage in database is that it means a lot of write queries and a lot of 
 conflicting access in the database. This is a great way of stressing a database server 
 like MySQL. So IMHO using another solution is better, this keeps your read/write ratio 
 in a better web-database way.

 You could also keep the file storage system and simply share the file directory between 
 servers with NFS. Alter the session.save_path setting to use something other than /tmp. 
 But NFS is by definition not the fastest wày of using a disk. Prefer memcached or mongodb 
 for fast access.

 If the only thing you need to share between the server is authentification, then instead 
 of sharing the real session storage you could share authentification credentials. Like the 
 OpenId system in SO, it's what we call an SSO, for the web part you have several solutions, 
 from OpenId to CAS, and others. If the data is merged on the client side (ajax, ESI-gate) 
 then you do not really need a common session data storage on server-side. This will avoid 
 having 3 of your 5 impacted web application writing data in the shared session in the same 
 time. Other session sharing techniques (database, NFS, even memcached) are mostly used to 
 share your data between several servers because Load Balancing tools can put your sequential 
 HTTP request from one server to another, but if you really mean parallel gathering of data 
 you should really study SSO.
 
 from: https://stackoverflow.com/questions/6490875/how-to-manage-a-single-php5-session-on-multiple-apache-servers
 */

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