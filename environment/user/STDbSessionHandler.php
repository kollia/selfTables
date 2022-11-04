<?php 

require_once( $_stdbselector );
require_once( $_stdbinserter );
require_once( $_stdbupdater );
require_once( $_stdbdeleter );

// session configuration variables
// https://www.php.net/manual/en/session.configuration.php
//
class STDbSessionHandler implements SessionHandlerInterface
{
    private $database= null;
    private $existID= "";
    private $sUsingFunctions= "";
    
    public function __construct(&$database)
    {
        $this->database= &$database;
    }
    // http://www.mywebsolution.de/workshops/1/page_5/show_Sessions-in-PHP-Datenbank-basierte-Sessionverwaltung.html
    public function open(string $savePath, string $sessionName) : bool
    {
        //echo "open session on path:$savePath as name:$sessionName<br />";
        // nothing to do
        $this->sUsingFunctions.= "open()<br>";
        return true;
        
    }
    public function read(string $sessionId) : string
    {
        $this->sUsingFunctions.= "read('$sessionId')<br>";
        $lifetime= ini_get("session.cookie_lifetime");
        $oSessionTable= $this->database->getTable("Sessions");
        $oSelector= new STDbSelector($oSessionTable);
        $oSelector->select("Sessions", "ses_value");
        $oSelector->select("Sessions", "ses_time");
        $oSelector->where("ses_id='".$this->database->real_escape_string($sessionId)."'");
        $oSelector->execute();
        $res= $oSelector->getRowResult();
        if($oSelector->getErrorId() != 0)
        {
            STCheck::warning(true, "cannot read session from database: ".$oSelector->getErrorMessage());
            return false;
        }
        if(isset($res["ses_value"]))
            $this->existID= $sessionId;
        if( !isset($res["ses_value"]) ||
            (time() - $res["ses_time"]) > $lifetime )
        {
            $this->sUsingFunctions.= "do not found session";
            if(isset($res["ses_time"]))
                $this->sUsingFunctions.= ", was ".(time() - $res["ses_time"] - $lifetime)." seconds to old";
                $this->sUsingFunctions.= "<br>";
                return false;
        }
        $this->sUsingFunctions.= "found session was ".(time() - $res["ses_time"])." seconds alive<br>\n";
        return $res["ses_value"];
            
    }
    public function write(string $sessionId, string $data) : bool
    {
        $err_msg= "";
        if(STCheck::isDebug("session"))
            $this->sUsingFunctions.= "write('$sessionId', [\$data])<br />";
        $oSessionTable= $this->database->getTable("Sessions");
        $this->sUsingFunctions.= "  exist session: '".$this->existID."'<br />";
        $this->sUsingFunctions.= "    new session: '$sessionId'<br />";
        if($this->existID != $sessionId)
        {
            $this->sUsingFunctions.= "  so insert new session into database<br />";
            $oInsert= new STDbInserter($oSessionTable);
            $oInsert->fillColumn("ses_id", $this->database->real_escape_string($sessionId));
            $oInsert->fillColumn("ses_time", time());
            $oInsert->fillColumn("ses_value", $this->database->real_escape_string($data));
            $res= $oInsert->execute();
            $this->sUsingFunctions.= "result: ".print_r($res, true)."<br>";
            if($res > 0)
            {
                $err_msg= "cannot write session into database: ".$oInsert->getErrorString()."<br />";
                $err_msg.= " &#160;statement:'".$oInsert->getStatement()."'";
            }
            //$this->sUsingFunctions.= "  ".$oInsert->getStatement()."<br />";            
        }else
        {
            $this->sUsingFunctions.= "  so update session inside database<br />";
            $oUpdater= new STDbUpdater($oSessionTable);
            $oUpdater->update("ses_value", $this->database->real_escape_string($data));
            $oUpdater->update("ses_time", time());
            $oUpdater->where("ses_id='".$this->database->real_escape_string($sessionId)."'");
            $res= $oUpdater->execute();
            $this->sUsingFunctions.= "result: ".print_r($res, true)."<br>";
            if($res > 0)
            {
                $err_msg= "cannot update session inside database: ".$oUpdater->getErrorString()."<br />";
                $err_msg.= " &#160;statement:'".$oUpdater->getStatement()."'";
            }
        }
        if($err_msg != "")
        {
            $this->sUsingFunctions.= "  $err_msg<br />";
            STCheck::warning(true, "STDbSessionHandler::write()", $err_msg);
            return false;
        }
        return true;
    }
    public function destroy(string $sessionId) : bool
    {
        $this->sUsingFunctions.= "destroy('$sessionId')<br>";
        $oSessionTable= $this->database->getTable("Sessions");
        $del= new STDbDeleter($oSessionTable);
        $del->where("ses_id='".$this->database->real_escape_string($sessionId)."'");
        $res= $del->execute();
        if($del->getErrorId() != 0)
        {
            STCheck::warning(true, "cannot destroy session from database: ".$del->getErrorString());
            return false;
        }
        if($res == 0)
            return true;
        return false;
    }
    public function gc(int $lifetime) : int
    {
        $this->sUsingFunctions.= "gc() session cleaner on time $lifetime<br>";
        $oSessionTable= $this->database->getTable("Sessions");
        $del= new STDbDeleter($oSessionTable);
        $del->where("ses_time < $lifetime");
        $res= $del->execute();
        if($res == 0)
            return 2;// toDo: should return affected rows of deletion
        return false;
            
    }
    public function close() : bool
    {
        // nothing to do
        if(STCheck::isDebug("session"))
        {
            $this->sUsingFunctions.= "close() session<br>";
            STCheck::echoDebug("session", "session will be set in follow methods:");
            echo $this->sUsingFunctions;
        }
        return true;
    }
}
    
?>