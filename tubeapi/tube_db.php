<?php
    class TubeDb {
        
        private $conn;

        public function __construct() {
            include_once "db_config.php";
            $this->conn = new mysqli(dbServer, dbUser, dbPassword, dbName);
         
        }

        function dbConnect() {
            return $conn;
        }

    }

?>
