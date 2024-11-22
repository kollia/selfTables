<?php

$dbselftables= 'dbselftables';
require_once "$dbselftables/st_pathdef.inc.php";
require_once $_stdbmariadb;

//STCheck::debug(true); // <- a good choice for developing

$db= new STDbMariaDb();
$db->connect('<host>', '<user>', '<password>');
$db->database('<your preferred database>');

$country= $db->getTable("Country");
$country->setDisplayName("existing Countries");
$country->identifColumn("name", "Country");
$country->select("name", "Name");
$country->setMaxRowSelect(20);

$state= $db->getTable("State");
$state->setDisplayName("States");
$state->identifColumn("name", "State");
$state->select("name", "Name");
$state->select("country", "from Country");
$state->orderBy("name");
$state->setMaxRowSelect(20);

$county= $db->getTable("County");
$county->identifColumn("name", "County");
$county->select("state", "State");
$county->select("name", "County");
$county->setMaxRowSelect(50);

$person= $db->getTable("Person");
$person->identifColumn("first_name", "first Name");
$person->identifColumn("last_name", "last Name");
$person->select("first_name", "first Name");
$person->select("last_name", "last Name");
$person->select("address", "Address");
$person->setMaxRowSelect(50);

$address= $db->getTable("Address");
$address->identifColumn("city", "City");
$address->identifColumn("street", "Street");
//$address->identifColumn("county", "County");
$address->select("city", "City");
$address->select("street", "Street");
$address->select("county", "from County");
$address->setMaxRowSelect(50);

$bill= $db->getTable("Bill");
$bill->identifColumn("bill_id", "Bill");
$bill->select("bill_id", "Bill");
$bill->select("person", "for Person");
$bill->setMaxRowSelect(50);

$order= $db->getTable("Order");
$order->select("count", "Count");
$order->select("article", "Article");
$order->setMaxRowSelect(50);

$article= $db->getTable("Article");
$article->identifColumn("title", "Article");
$article->identifColumn("price", "Price");
$article->select("title", "Article");
$article->select("content", "Description");
$article->select("price", "Price");
$article->setMaxRowSelect(50);
