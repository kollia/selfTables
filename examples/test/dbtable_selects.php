<?php 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once( "dbselftables/st_pathdef.inc.php");
require_once( $_stdbmysql );

STCheck::debug("db.statements");

$db= new STDbMySql("simpleTest");
$db->connect("10.21.170.61", "root", "mysql");
$db->database("UserManagement");


$project= $db->getTable("MUProject");
$where= new STDbWhere("Name='ONLINE'", "Group", "and");

$selector= new STDbSelector($project);
$selector->distinct();
$selector->select("MUCluster", "ID");
$selector->select("MUProject", "Name");
$selector->select("MUCluster", "ProjectID");
$selector->where($where);
$selector->execute();

$res= $selector->getResult();
st_print_r($res);

// selection should not set where clause
$statement= $project->getStatement();
echo "project statement:'$statement'<br><br><br>";

//------------------------------------------------------------------------------------------------------------------------------
// check incorrect where statement -> andWhere() delete where()

$oUserTable= $db->getTable("MUUser");
$userSelector= new STDbSelector($userTable);
$userSelector->select("User", "ID");
$userSelector->where("UserName='".$user."'");
$userSelector->andWhere("Pwd=password('".$password."')");
$userSelector->execute();

$res= $userSelector->getResult();
st_print_r($res);

// selection should not set where clause
$statement= $userSelector->getStatement();
echo "project statement:'$statement'<br><br><br>";













?>