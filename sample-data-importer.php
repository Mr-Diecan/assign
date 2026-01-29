<?php
// sample-data-importer.php

// 1. Unganisha na database
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'search';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. Usisahau kuinclude file lenye $sampleData
// Kwa mfano, ikiwa data iko kwenye file moja:
require_once 'sample-data.php'; // file lenye $sampleData array

// 3. Loop kupitia kila item na kuingiza kwenye database
foreach ($sampleData as $item) {
    // Safisha data ili kuepuka SQL injection
    $title = $conn->real_escape_string($item['title']);
    $description = $conn->real_escape_string($item['description']);
    $page_name = $conn->real_escape_string($item['page_name']);
    $page_fav_icon_path = $conn->real_escape_string($item['page_fav_icon_path']);
    $page_url = $conn->real_escape_string($item['page_url']);
    $created_at = $conn->real_escape_string($item['created_at']);

    // SQL query ya kuingiza
    $sql = "INSERT INTO search_items (title, description, page_name, page_fav_icon_path, page_url, created_at) 
            VALUES ('$title', '$description', '$page_name', '$page_fav_icon_path', '$page_url', '$created_at')";

    if ($conn->query($sql) === TRUE) {
        echo "Record inserted successfully: " . $item['title'] . "<br>";
    } else {
        echo "Error inserting record: " . $conn->error . "<br>";
    }
}

// 4. Funga connection
$conn->close();

echo "Data import completed!";
?>