<?php
    class Helper {
        
        /*  Source sql script from file.
         *  
         *  Parse sql script file line by line and source it.
         *
         *  @param string $filename
         *  @param string $hostname
         *  @param string $username
         *  @param string $password
         *  @param string @dbName
         *
         *  @return void
         */
        
        
        public static function sourceSql($filename, $hostname, $username, $password, $dbName) {
            mysql_connect($hostname, $username, $password) or die('Error connecting to MySQL server: ' . mysql_error());
            
            $templine = '';
            $lines = file($filename);
            
            foreach ($lines as $line) {
                // Skip it if it's a comment
                if (substr($line, 0, 2) == '--' || $line == '')
                    continue;
                // Add this line to the current segment
                $templine .= $line;
                // If it has a semicolon at the end, it's the end of the query
                if (substr(trim($line), -1, 1) == ';') {
                    // Perform the query
                    mysql_query($templine) or print('Error performing query \'<strong>' . $templine . '\': ' . mysql_error() . '<br /><br />');
                    $templine = '';
                }
            }
        }
    }

?>

