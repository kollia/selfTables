<?php 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once( "dbselftables/st_pathdef.inc.php");
require_once( $_stdbmysql );

STCheck::debug("db.statements");

$db= new STDbMySql("simpleTest");
$db->connect("10.21.170.61", "root", "mysql");
$db->toDatabase("UserManagement");


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
echo "project statement:'$statement'<br>";
?>