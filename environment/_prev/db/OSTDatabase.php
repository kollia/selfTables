<?

//last change 23.11.2004
require_once($php_tools_class);
require_once($database_selector);
//require_once($stdbtablecontainer);
require_once($_stdatabase);
require_once($_stdbmysql);

	/**
	*	 class OSTDatabase
	*	 	   zugriff auf Datenbank
	*
	*		   @Autor: Alexander Kolli
	*		   @version: 1.0
	*/
class OSTDatabase extends STDbMySql
{

	  /**
		*  Konstruktor f�r Zugriffs-deffinition
		*
		*/
	function OSTDatabase($identifName= "main-menue", $defaultTyp= MYSQL_NUM, $DBtype= "MYSQL")
   	{
		STDatabase::STDatabase($identifName, $defaultTyp, "MYSQL");
  	}
	/**
	*  Verbindungs-Aufbau zur Datenbank
	*
	*  @param $host: Hostname
	*  @param $user: Username
	*  @param $passwd: Passwort
	*/
	function connect($host= null, $user= null, $passwd= null)
	{
   		$this->conn= @mysql_connect($host, $user, $passwd);
		// alex 17/05/2005: obwohl referenze auf innere DB besteht
		//					wird die Connection dort nicht eingetragen.
		//					keine Ahnung warum !!!
		$this->db->conn= $this->conn;
  		if(!$this->conn)
  		{
				$this->error= true;
  			echo "can not make the connection to host <b>$host</b><br>";
  			echo "with user <b>$user</b><br>";
  			echo "<b>ERROR".@mysql_errno($this->conn).": </b>".@mysql_error($this->conn);
  			exit;
  		}
  		$this->host= $host;
  		$this->user= $user;
	}
	// deprecated
   	function toDatabase($database, $onError= onErrorStop)
	{
		$this->useDatabase($database, $onError);
	}
		/**
		*  wechselt zu der angegebenen Datenbank
		*
		*  @param $database: Datenbank-Name
		*  @param $onError: ob die Methode Fehler anzeigen soll und beendet werden soll.
		*					<br>noErrorShow - Fehler wird nicht angezeigt und Programm nicht beendet
		*					<br>onErrorShow - Fehler wird angezeigt aber Programm nicht beendet
		*  		  			<br>onErrorStop - Fehler wird angezeigt und Programm beendet
		*/
/*   	function useDatabase($database, $onError= onErrorStop)
   	{
		$this->dbName= $database;
		// alex 17/05/2005: obwohl referenze auf innere DB besteht
		//					wird der DB-Name dort nicht eingetragen.
		//					keine Ahnung warum !!!
		$this->db->dbName= $this->dbName;
  		if(!mysql_select_db("$database", $this->conn))
  		{
				$this->error= true;
				if($onError>noErrorShow)
				{
    			echo "<br>can not reache database <b>$database</b>,<br>";
					if( $this->conn==null )
					 	echo "with no connection be set<br>";
					else
					{
        			echo "with user <b>$this->user</b> on host <b>$this->host</b><br>";
        			echo "<b>ERROR".mysql_errno($this->conn).": </b>".mysql_error($this->conn);
					}
					if($onError==onErrorStop)
    				exit();
				}
  		}
	}*/
	function solution($statement, $typ= null, $onError= onErrorStop)
	{
		$typ= $this->getTyp($typ);
	 	return $this->fetch($statement, $typ, $onError);
	}
	function getRegexpOperator()
	{
		return "regexp";
	}
	function getLikeOperator()
	{
		return "like";
	}
	function getIsOperator()
	{
		return "=";
	}
	function getIsNotOperator()
	{
		return "!=";
	}
	function getIsNullOberator()
	{
		return "is";
	}
	function getIsNotNullOperator()
	{
		return "is not";
	}
	function getIntLen()
	{
		return 11;
	}
	function getBigIntLen()
	{
		return 20;
	}
	function getTextLen()
	{
		return 65535;
	}
}

 ?>