<?php

$dbselftables= 'dbselftables'; // <- maybe also > dbselftables-x.x-RC
require_once "$dbselftables/st_pathdef.inc.php";
require_once $_stdbmariadb;
require_once $_stsitecreator;

$db= new STDbMariaDb();
$db->connect('<host>', '<user>', '<password>');
$db->database('<your preferred database>');

$creator= new STSiteCreator($db);
$creator->addCssLink("$dbselftables/design/websitecolors.css");
$creator->execute();
$creator->display();
