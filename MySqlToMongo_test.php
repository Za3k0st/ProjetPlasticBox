<?php

require_once("MySqlToMongo.php");

$mysql_host = "localhost";
$mysql_user = "root";
$mysql_pswd = "";
$mysql_database = "test";

$mongo_host = "localhost";
$mongo_user = "";
$mongo_pswd = "";
$mongo_database = "test";

$imp = new MySqlToMongo();

//Establishing connections to mysql and mongo instances
$imp->mysql_connect($mysql_host, $mysql_user, $mysql_pswd, $mysql_database);
$imp->mongodb_connect($mongo_host, $mongo_user, $mongo_pswd, $mongo_database);

//Set the number of concurrent records to insert
$imp->setBatchRecords(1000);

//Set debug mode to read debug messages
$imp->setDebugMode(true);

//Get all tables from the database
//if you want to import single tables just pass an array of names ex: $list("tbl1","tbl2")
$tables = $imp->getMySqlTables();

//Set the dropping tables if exists into mongo. If false it will append records
$imp->setDropIfExists(true);

//Starting import session
$imp->import($tables);