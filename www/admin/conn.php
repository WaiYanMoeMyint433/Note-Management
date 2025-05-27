<?php
// SAMPLE MYSQLi DATABASE CONNECTION
$servername = "mysql-server";
$username = "root";
$password = "root";
$database = "nms2";

$conn = new mysqli($servername, $username, $password);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$result = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$database'");
if ($result->num_rows == 0) {
    $sql = "CREATE DATABASE IF NOT EXISTS $database";
    if ($conn->query($sql) === TRUE) {
        $conn->select_db($database);

        $sqlFile = dirname(__DIR__, 2) . '/mysql/sql/nms2.sql';
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            $conn->multi_query($sql);

            while ($conn->more_results() && $conn->next_result()) {
                $result = $conn->store_result();
                if ($result instanceof mysqli_result) {
                    $result->free();
                }
            }
        }
    } else {
        die("Error creating database: " . $conn->error);
    }
}

$conn->select_db($database);
