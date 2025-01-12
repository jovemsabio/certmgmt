<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

try {
    $pdo = new PDO('sqlite:./db2.sqlt');
} catch (PDOException $e) {
    print "Error: " . $e->getMessage();
    exit(2);
}

