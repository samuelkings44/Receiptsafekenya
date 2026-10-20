<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "PHP IS WORKING<br>";

require_once "db.php";

echo "DB FILE LOADED<br>";

if ($conn->connect_error) {
    echo "DATABASE CONNECTION FAILED";
} else {
    echo "DATABASE CONNECTION WORKING";
}

?>