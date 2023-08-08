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
        $this->sUsingFunctions.= "open() // nothing to do<br>";
        return true;
        
    }
    public function read(string $sessionId) : string
    {
        $this->sUsingFunctions.= "read('$sessionId')<br>";
        $lifetime= ini_get("session.gc_maxlifetime");
        $oSessionTable= $this->database->getTable("Sessions");
        $oSelector= new STDbSelector($oSessionTable);
        $oSelector->select("Sessions", "ses_id");
        $oSelector->select("Sessions", "ses_value");
        $oSelector->select("Sessions", "ses_time");
        $oSelector->where("ses_id='".$this->database->real_escape_string($sessionId)."'");
        $count= $oSelector->execute();
        $res= $oSelector->getResult();
        if($oSelector->getErrorId() != 0)
        {
            STCheck::warning(true, "cannot read session from database: ".$oSelector->getErrorString());
            return false;
        }
        $rowNr= 0;
        foreach($res as $row)
        {
            if($row['ses_id'] == $sessionId)
                break;
            $rowNr++;
        }
        if(STCheck::isDebug())
        {
            showLine();
            echo "count of selected sessions are $count<br>";
            st_print_r($res,4);
        }
        if($count > 0)
            $this->sUsingFunctions.= " &#160;cookie session hold $lifetime seconds<br />\n";
        if(isset($res[$rowNr]["ses_value"]))
            $this->existID= $sessionId;
        if( !isset($res[$rowNr]["ses_value"]) ||
            (time() - $res[$rowNr]["ses_time"]) > $lifetime )
        {
            $this->sUsingFunctions.= " &#160;do not found session";
            if(isset($res[$rowNr]["ses_time"]))
                $this->sUsingFunctions.= ", was ".(time() - $res[$rowNr]["ses_time"] - $lifetime)." seconds to old";
            $this->sUsingFunctions.= "<br>";
            $this->sUsingFunctions.= " return <b>false</b><br>\n";
            return false;
        }
        $this->sUsingFunctions.= " &#160;found session was ".(time() - $res[$rowNr]["ses_time"])." seconds alive<br>\n";
        $this->sUsingFunctions.= " &#160;session <b>OK</b>, return <b>all</b> session variables<br>\n";
        //$this->sUsingFunctions.= " &#160;-------------------------------------------------------------------------------------<br />";
        //$this->sUsingFunctions.= " &#160;".print_r($res[$rowNr]["ses_value"], true)."<br>";
        //$this->sUsingFunctions.= " &#160;-------------------------------------------------------------------------------------<br />";
        if(STCheck::isDebug("session"))
        {
            session_decode($res[$rowNr]["ses_value"]);
            st_print_r($_SESSION);
        }
        if(isset($_SESSION['ST_USER']))
            $user= "user: ".$_SESSION['ST_USER'];
        else
            $user= "Unknown User";
        if( isset($_SESSION['ST_LOGGED_IN']) &&
            $_SESSION['ST_LOGGED_IN'] > 0       )
        {
            $loggedin= "logged in";
        }else
            $loggedin= "not logged in";
        $this->sUsingFunctions.= " &#160;user $user $loggedin<br />";
        return $res[$rowNr]["ses_value"];
            
    }
    public function write(string $sessionId, string $data) : bool
    {
        if(STCheck::isDebug())
        {
            echo "<pre>";
            echo "------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------<br />";
            echo "          write database SESSION<br />";
            echo "</pre>";
        }
        $err_msg= "";
        if(STCheck::isDebug("session"))
            $this->sUsingFunctions.= "write('$sessionId', [\$data])<br />";
        $oSessionTable= $this->database->getTable("Sessions");
        $this->sUsingFunctions.= " &#160;exist session: '".$this->existID."'<br />";
        $this->sUsingFunctions.= " &#160;current session: '$sessionId'<br />";
        if($this->existID != $sessionId)
        {
            $this->sUsingFunctions.= " &#160;so insert new session into database<br />";
            $oInsert= new STDbInserter($oSessionTable);
            $oInsert->fillColumn("ses_id", $this->database->real_escape_string($sessionId));
            $oInsert->fillColumn("ses_time", time());
            $oInsert->fillColumn("ses_value", $this->database->real_escape_string($data));
            $res= $oInsert->execute();
            $this->sUsingFunctions.=  " &#160;result: ".print_r($res, true)."<br>";
            if($res > 0)
            {
                $err_msg= " &#160;cannot write session into database: ".$oInsert->getErrorString()."<br />";
                $err_msg.= " &#160;&#160;statement:'".$oInsert->getStatement()."'";
            }
            //$this->sUsingFunctions.= "  ".$oInsert->getStatement()."<br />";            
        }else
        {
            $this->sUsingFunctions.= " &#160;so update session inside database<br />";
            $oUpdater= new STDbUpdater($oSessionTable);
            $oUpdater->update("ses_value", $this->database->real_escape_string($data));
            $oUpdater->update("ses_time", time());
            $oUpdater->where("ses_id='".$this->database->real_escape_string($sessionId)."'");
            $res= $oUpdater->execute();
            $this->sUsingFunctions.= " &#160;result: ".print_r($res, true)."<br>";
            if($res > 0)
            {
                $err_msg= "cannot update session inside database: ".$oUpdater->getErrorString()."<br />";
                $err_msg.= " &#160;statement:'".$oUpdater->getStatement()."'";
            }
        }
        if($err_msg != "")
        {
            $this->sUsingFunctions.= " &#160;$err_msg<br />";
            $this->sUsingFunctions.= " &#160;return <b>false</b><br />";
            STCheck::warning(true, "STDbSessionHandler::write()", $err_msg);
            return false;
        }
        $this->sUsingFunctions.= " &#160;return <b>true</b><br />";
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
            $this->sUsingFunctions.= " &#160;return <b>false</b><br />";
            return false;
        }
        if($res == 0)
        {
            $this->sUsingFunctions.= " &#160;no session to destroy<br />";
            $this->sUsingFunctions.= " &#160;return <b>true</b><br />";
            return true;
        }
        $this->sUsingFunctions.= " &#160;return <b>false</b><br />";
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