<?php 

require_once( $_stdbselector );
require_once( $_stdbinserter );
require_once( $_stdbupdater );
require_once( $_stdbdeleter );

/**
 * overwrite probability and divisor from php.ini
 * and start own garbage collector
 *
 * @var integer $global_dbsession_probability
 * @var integer $global_dbsession_devisor
 */
$global_dbsession_probability= 0;
$global_dbsession_divisor= 100;

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
        if(!$this->database->isDbTable("Session"))
        {
            STCheck::echoDebug("install", "Session table do not exist <b>do installation</b> on STUserSideCreator");
            return "";  
        }
        $this->sUsingFunctions.= "read('$sessionId')<br>";
        $lifetime= ini_get("session.gc_maxlifetime");
        $lasttime= time()-$lifetime;
        try{
            $oSessionTable= $this->database->getTable("Sessions");
            if($this->database->errno() == 1146)
            {
                STCheck::echoDebug("install", "Session table do not exist <b>do installation</b> on STUserSideCreator");
                return "";  
            }
        }catch(mysqli_sql_exception $ex)
        {
            if($ex->getCode() == 1146) // Session table not exist
            {
                STCheck::echoDebug("install", "Session table do not exist <b>do installation</b> on STUserSideCreator");
                return ""; 
            }
            throw $ex;
        }
        $oSelector= new STDbSelector($oSessionTable);
        $oSelector->select("Sessions", "ses_id");
        $oSelector->select("Sessions", "ses_value");
        $oSelector->select("Sessions", "ses_time");
        $oSelector->where("ses_id='".$this->database->real_escape_string($sessionId)."'");
        if(STCheck::isDebug("session"))            
            $oSelector->orWhere("ses_time>=$lasttime");
        $count= $oSelector->execute();
        $res= $oSelector->getResult();
        if($oSelector->getErrorId() != 0)
        {
            STCheck::warning(true, "cannot read session from database: ".$oSelector->getErrorString());
            return false;
        }
        $rowNr= -1;
        $rowCount= 0;
        STCheck::echoDebug("session");
        foreach($res as $row)
        {
            if(STCheck::isDebug("session"))
            {
                if($row['ses_id'] == $sessionId)
                    $cur= "*";
                else
                    $cur= " ";
                $session_data= $this->unserialize($row["ses_value"]);
                if( isset($session_data['ST_LOGGED_IN']) &&
                    $session_data['ST_LOGGED_IN'] == 1 &&
                    $row['ses_time'] >= $lasttime       )
                {
                    $tm_loggedin= time()-$row['ses_time'];
                    $tm_min= (int)($tm_loggedin / 60);
                    $tm_sec= $tm_loggedin - ($tm_min * 60);
                    $logged= "user <b>".$session_data['ST_USER']."</b> is logged in since ";
                    if($tm_min > 0)
                        $logged.= $tm_min." minutes and ";
                    $logged.= $tm_sec." seconds";
                }else
                    $logged= "nobody is logged in";
                STCheck::echoDebug("session", "session $cur<b>".$row['ses_id']."</b> - $logged");
            }
            if($row['ses_id'] == $sessionId)
            {
                $this->existID= $sessionId;
                if($row['ses_time'] >= $lasttime)
                {
                    $rowNr= $rowCount;
                    if(!STCheck::isDebug("session"))
                        break;
                }
            }
            $rowCount++;
        }
        STCheck::echoDebug("session");
        if($count > 0)
            $this->sUsingFunctions.= " &#160;every session should hold $lifetime seconds<br />\n";
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
        $this->sUsingFunctions.= " &#160; $user $loggedin<br />";
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
        
        global $global_dbsession_probability;
        global $global_dbsession_divisor;
        
        
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
        // check for garbage collector
        if($global_dbsession_probability)
        {
            $this->sUsingFunctions.= " &#160; -> try to start own garbage collector<br />";
            $probabilities= array();
            for($i= 0; $i < $global_dbsession_probability; $i++)
                $probabilities[]= rand(1, $global_dbsession_divisor);
            $random= rand(1, $global_dbsession_divisor);
            $start= false;
            foreach($probabilities as $probability)
            {
                $this->sUsingFunctions.= " &#160;&#160;&#160;&#160; random/collection $random/$probability";
                if($random == $probability)
                {
                    $this->sUsingFunctions.= " <- was correct<br />";
                    $start= true;
                    break;
                }else
                    $this->sUsingFunctions.= "<br />";
            }
            if($start)
            {
                $lifetime= ini_get("session.gc_maxlifetime");
                $this->gc($lifetime);
            }else
                $this->sUsingFunctions.= " &#160;&#160;&#160;&#160; <- was wrong<br />";
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
        $time= time() - $lifetime;
        $this->sUsingFunctions.= " &#160;gc() session cleaner on time $lifetime<br>";
        $oSessionTable= $this->database->getTable("Sessions");
        $del= new STDbDeleter($oSessionTable);
        $del->where("ses_time < $time");
        $res= $del->execute();
        if($res == 0)
        {
            $this->sUsingFunctions.= " &#160;remove x sessions<br />";
            return 2;// toDo: should return affected rows of deletion
        }
        $this->sUsingFunctions.= " &#160;cannot remove sessions <b>ERROR</b> ".$del->getErrorString()."<br />";
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
    public static function unserialize($session_data) {
        $method = ini_get("session.serialize_handler");
        switch ($method) {
            case "php":
                return self::unserialize_php($session_data);
                break;
            case "php_binary":
                return self::unserialize_phpbinary($session_data);
                break;
            default:
                throw new Exception("Unsupported session.serialize_handler: " . $method . ". Supported: php, php_binary");
        }
    }
    
    private static function unserialize_php($session_data) {
        $return_data = array();
        $offset = 0;
        while ($offset < strlen($session_data)) {
            if (!strstr(substr($session_data, $offset), "|")) {
                throw new Exception("invalid data, remaining: " . substr($session_data, $offset));
            }
            $pos = strpos($session_data, "|", $offset);
            $num = $pos - $offset;
            $varname = substr($session_data, $offset, $num);
            $offset += $num + 1;
            $data = unserialize(substr($session_data, $offset));
            $return_data[$varname] = $data;
            $offset += strlen(serialize($data));
        }
        return $return_data;
    }
    
    private static function unserialize_phpbinary($session_data) {
        $return_data = array();
        $offset = 0;
        while ($offset < strlen($session_data)) {
            $num = ord($session_data[$offset]);
            $offset += 1;
            $varname = substr($session_data, $offset, $num);
            $offset += $num;
            $data = unserialize(substr($session_data, $offset));
            $return_data[$varname] = $data;
            $offset += strlen(serialize($data));
        }
        return $return_data;
    }
}
    
?>