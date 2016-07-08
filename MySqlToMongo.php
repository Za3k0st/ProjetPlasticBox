<?php

/**
 * Purpose : 
 * This class allow to import a mysql database (or single tables)
 * into a mongodb database.
 * It will create the database and each collections if they not exists 
 * and will map all fields from mysql to relative mongo definitions
 * The import session will create also all indexes structure from mysql to mongodb
 * 
 * Note: To use this class you must have already installed php libraries for mongodb
 * 
 * @author  : Principe Orazio (orazio.principe@gmail.com)
 * @websites: http://principeorazio.wordpress.com http://www.dbpersister.com
 * @version : 1.1
 * @date    : 08/08/2014
 *
 * @notes:
 * Version 1.1: add decimal fields support
 * 
 * @license http://www.opensource.org/licenses/lgpl-3.0.html
 * 
 */
class MySqlToMongo
{
    
    //List of mysql field types
    private $mysql_data_type_hash = array(
        1=>'tinyint',
        2=>'smallint',
        3=>'int',
        4=>'float',
        5=>'double',
        7=>'timestamp',
        8=>'bigint',
        9=>'mediumint',
        10=>'date',
        11=>'time',
        12=>'datetime',
        13=>'year',
        16=>'bit',
        //252 is currently mapped to all text and blob types (MySQL 5.0.51a)
        252=>'string',
        253=>'varchar',
        254=>'char',
        246=>'decimal'
    );
    
    
    
    
    /**
     * Variable for mysql connection
     * @var mysqli
     */
    private $mysqli;
    
    /**
     * Second variable for mysql connection
     * This second variable is used to make custom operations during import
     * session
     * 
     * @var mysqli
     */
    private $mysqli2;
    
    /**
     * List of mysql tables to import
     * 
     * @var array
     */
    private $mysql_tables;
    
    /**
     * Variable for mongodb connection
     * @var MongoClient
     */
    private $mongo_client;
    
    /**
     * Variable for mongodb selected database
     * @var MongoClient
     */
    private $mongo_db;
    
    /**
     * Enable/Disable debug logging
     * @var bool
     */
    private $debugMode = false;
    
    /**
     * Number of records to insert at one time
     * @var int
     */
    private $batchRecords = 10000;
    
    /**
     * Drop collection on mongodb if already exists
     * 
     * @var boolean
     */
    private $dropIfExists = true;
    
    
    public function __construct()
    {
        //Your custom code here
    }
    
    /**
     * Used to release connections
     */
    public function __destruct()
    {
        try {
            @$this->mysqli->close();
        }
        catch (Exception $e) {}
        
        try {
            @$this->mysqli2->close();
        }
        catch (Exception $e) {}
        
        try {
            @$this->mongo_client->close();
        }
        catch (Exception $e) {}
    }
    
    
    /**
     * Set the number of the concurrent records to insert into mongo
     * for each transaction
     * 
     * @param int $batchRecords
     */
    public function setBatchRecords($batchRecords)
    {
        $this->batchRecords = $batchRecords;
    }

    /**
     * If $dropIfExists parameter is set to true the import session will create
     * the collection with all new indexes founds on mysql table, otherwise
     * it will append all records without change the target structure
     * 
     * Default value = TRUE
     * 
     * @param type $dropIfExists
     */
    public function setDropIfExists($dropIfExists)
    {
        $this->dropIfExists = $dropIfExists;
    }

        
        
    /**
     * Print echo messages during operations
     * 
     * @param boolean $debugMode
     */
    public function setDebugMode($debugMode) {
        $this->debugMode = (boolean) $debugMode;
    }
    
    /**
     * Connect to mysql database
     * 
     * @param string $mysql_host
     * @param string $mysql_user
     * @param string $mysql_pswd
     * @param string $mysql_database
     * @param int $mysql_port
     */
    public function mysql_connect($mysql_host, $mysql_user, $mysql_pswd, $mysql_database, $mysql_port = 3306)
    {
        try {
            $this->mysqli = new mysqli($mysql_host, $mysql_user, $mysql_pswd, $mysql_database, $mysql_port);
            $this->mysqli->query("SET CHARACTER_SET_RESULTS=utf8");
            $this->mysqli->query("SET NAMES utf8");
            
            $this->mysqli2 = new mysqli($mysql_host, $mysql_user, $mysql_pswd, $mysql_database, $mysql_port);
            $this->mysqli2->query("SET CHARACTER_SET_RESULTS=utf8");
            $this->mysqli2->query("SET NAMES utf8");
        }
        catch (Exception $e) {
            die("\nError connecting to mysql: " . $e->getMessage());
        }
    }
    
    /**
     * Connect to mongodb database
     * 
     * @param string $mongo_host
     * @param string $mongo_user
     * @param string $mongo_pswd
     * @param string $mongo_database
     * @param array  $mongo_options
     */
    public function mongodb_connect($mongo_host, $mongo_user, $mongo_pswd, $mongo_database, $mongo_options = array())
    {
        try {
            $this->mongo_client = new MongoClient("mongodb://" . $mongo_user . ":" . $mongo_pswd . "@" . $mongo_host, $mongo_options);
            $this->mongo_db = $this->mongo_client->selectDB($mongo_database);
        }
        catch (Exception $e) {
            die("\nError connecting to mongo: " . $e->getMessage());
        }
    }
    
    /**
     * @return array Return the entire set of tables for the selected mysql database
     */
    public function getMySqlTables()
    {
        $sql = "SHOW TABLES";
        $rs = $this->mysqli->query($sql);
        $this->mysql_tables = array();
        while ($row = $rs->fetch_array()) {
            $this->mysql_tables[] = $row[0];
        }
        return $this->mysql_tables;
    }
    
    
    /**
     * Import tables from mysql to mongodb
     * 
     * @param array $tables
     * @param bool  $dropIfExists   Drop collection from mongo if already exists
     */
    public function import($tables)
    {
        if(!is_array($tables)) throw new Exception("tables is not an array");
        
        $counter = 0;
        foreach($tables as $table):
            
            //Create the class reflection
            if($this->dropIfExists) {
                $this->dropCollection($table);
            }
            
            $this->insertValues($table);
        
            $counter++;
//            if($counter > 2) {
//                break;
//            }
        endforeach;
        
    }
    
    
    
    ##################### START PRIVATE METHODS ##############################
    
    /**
     * Create index into mongo collection fetching them from the mysql table
     * passed as parameter
     * 
     * To execute this method you must call first:
     * setDropIfExists(TRUE);
     * 
     * @see setDropIfExists
     * @param string $table The mysql table
     * @param MongoCollection $coll The mongodb collection
     */
    private function createIndexes($table, $coll)
    {
        if(!$this->dropIfExists) {
            return true;
        }
        
        $sql = "SHOW INDEX FROM ".$table;
        $rs  = $this->mysqli2->query($sql);
        if(!$rs) {
            throw new Exception("Error getting index from table $table");
        }
        
        // This variable is used to understand 
        // when the resultset change to another index
        $last_field_name = "";
        
        // Array with the list of fields for the current index
        $fields = array();
        
        // Options for the current index
        $index_options = array();
        
        while($row = $rs->fetch_object()):
            
            if($last_field_name !== $row->Key_name) {
                //New index mapping, save previous index if exists
                if(!empty($fields)) {
                    //Save the index to mongodb
                    $coll->ensureIndex($fields, $index_options);
                    
                    //reset fields array
                    $fields = array();
                }
            }
            $last_field_name = $row->Key_name;

            //Add the field name
            $fields[$row->Column_name] = 1;
        
            //Index setting for mongo
            $index_options = array(
                "unique"    => $row->Non_unique == 0,
                "dropDups"  => FALSE,
                "sparse"    => FALSE,
                "name"      => $row->Key_name
            );
        
        endwhile;
        
        //Insert last index if exists
        if(!empty($fields)) {
            //Save the index to mongodb
            $coll->ensureIndex($fields, $index_options);

            //reset fields array
            $fields = array();
        }
        
    }
    
    
    /**
     * @var stdClass $finfo
     * @var stdClass &$obj
     * 
     * @return stdClass Create a standard class representing the mysql table structure
     */
    private function setObjectMapping($finfo, &$obj)
    {
        
        foreach($finfo as $field):
            
            $type = @$this->mysql_data_type_hash[$field->type];
            if(empty($type)) {
                //Mysql field type not found
                continue;
            }
            
            if($type === "int" || $type === "tinyint" || $type === "smallint") {
                $obj->{$field->name} = (int) $obj->{$field->name};
            }
            else if($type === "date" || $type === "datetime") {
                $obj->{$field->name} = new MongoDate(strtotime($obj->{$field->name}));
            }
            else if($type === "decimal" || $type === "float") {
                $obj->{$field->name} = (float) $obj->{$field->name};
            }
            else if($type === "double") {
                $obj->{$field->name} = (double) $obj->{$field->name};
            }
            
        endforeach;
        
    }
    
    /**
     * Drop the collection on mongodb
     * @param string $table
     * @return boolean
     */
    private function dropCollection($table)
    {
        $coll = $this->mongo_db->selectCollection($table);
        $coll->drop();
        return true;
    }


    /**
     * Insert data from mysql to mongo
     * 
     * @param string $table
     * @param stdClass $class
     */
    private function insertValues($table)
    {
        echo date("Y-m-d H:i:s")." Insert record for $table ... ";
        $this->log("\n");
        $coll = $this->mongo_db->selectCollection($table);
        
        $sql = "SELECT * FROM ".$table;
        $rs = $this->mysqli->query($sql, MYSQLI_USE_RESULT);
        $counter = 0;

        //Get fields types for mapping
        $finfo = $rs->fetch_fields();
        
        //Variable to check if indexes are created for this collection
        $index_created = false;
        
        //List of all elements to insert
        $elements = array();        
        while($row = $rs->fetch_object()):
            try {
                $counter++;
                
                $this->setObjectMapping($finfo, $row);
                $elements[] = $row;
                
                if(count($elements) >= $this->batchRecords) {
                    @$coll->batchInsert($elements);
                    $elements = array();
                    $this->log("\t$table $counter records\n");
                    
                    //Create all index if they are not created yet
                    if(!$index_created) {
                        //Create table index
                        $this->createIndexes($table, $coll);
                    }
                    
//                    //DEBUG
//                    echo "[OK]\n";
//                    return true;
                }
                
                //Use this function to insert single records
                //@$coll->insert($row);
            }
            catch(Exception $e) {
                echo "[ERR]\n";
                $this->log("\t$table -> " . $e->getMessage()."\n");
                return false;
            }
        endwhile;
        
        //Insert remaining objects
        try {
            @$coll->batchInsert($elements);

            //Create all index if they are not created yet
            if(!$index_created) {
                //Create table index
                $this->createIndexes($table, $coll);
            }

            //Release memory
            unset($elements);
            $this->log("\n\t$table $counter records");
        }
        catch(Exception $e) {
            echo "[ERR]\n";
            $this->log("\t$table -> " . $e->getMessage()."\n");
            return false;
        }
        
        echo "[OK]\n";
        return true;
    }
    

    /**
     * Print a message
     * 
     * @param string $msg
     */
    private function log($msg) {
        if ($this->debugMode) {
            echo $msg;
        }
    }

    
    ##################### END PRIVATE METHODS ##############################
    
}
