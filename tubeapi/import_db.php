<?php
    function sourceSql($filename, $hostname, $username, $password, $dbName) {
        mysql_connect($hostname, $username, $password) or die('Error connecting to MySQL server: ' . mysql_error());
        
        //mysql_select_db($dbName) or die('Error selecting MySQL database: ' . mysql_error());

        $templine = '';
        $lines = file($filename);
        
        // Loop through each line
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
                // Reset temp variable to empty
                $templine = '';
            }
        }
    }

    sourceSql("db.sql", "localhost", "root", "11235813", "");
?>
