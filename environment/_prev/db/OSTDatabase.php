<?php

//last change 23.11.2004
require_once($php_tools_class);
require_once($database_selector);
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
	function __construct($identifName= "main-menue", $defaultTyp= MYSQL_NUM, $DBtype= "MYSQL")
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
		$this->conn= new mysqli($host, $user, $passwd, $database);
		// alex 17/05/2005: obwohl referenze auf innere DB besteht
		//					wird die Connection dort nicht eingetragen.
		//					keine Ahnung warum !!!
		$this->db->conn= $this->conn;
  		if($this->conn->connect_errno)
  		{
			$this->error= true;
  			echo "can not make the connection to host <b>$host</b><br>";
  			echo "with user <b>$user</b><br>";
  			echo "<b>ERROR".$this->conn->connect_errno.": </b>".$this->conn->connect_error;
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