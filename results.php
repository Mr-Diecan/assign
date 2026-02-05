<?php
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "search";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$start_time = microtime(true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Search results</title>

    <link rel="stylesheet" href="style.css" type="text/css"/>
    <link rel="icon" href="favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap" />
</head>
<body>

<header>
    <div class="search-bar-container">
        <a href="index.php" class="logo">Search</a>

        <form class="search-form" action="results.php" method="get">
            <span class="search-icon">🔍</span>
            <input 
                type="search" 
                name="q" 
                class="search-input" 
                value="<?php echo htmlspecialchars($q); ?>" 
                placeholder="Search..."
            >
        </form>
    </div>
</header>

<main>

<?php
if ($q !== "") {

    $sql = "
        SELECT * FROM search_items
        WHERE title LIKE '%$q%'
        OR description LIKE '%$q%'
    ";
    $result = $conn->query($sql);
    $count  = $result->num_rows;

    $end_time = microtime(true);
    $time_taken = round($end_time - $start_time, 2);

    echo "<div class='stats'>About $count results ($time_taken seconds)</div>";

    if ($count > 0) {
        while ($row = $result->fetch_assoc()) {

            // highlight keyword
            $title = preg_replace("/($q)/i", "<span class='highlight'>$1</span>", $row['title']);
            $desc  = preg_replace("/($q)/i", "<span class='highlight'>$1</span>", $row['description']);
            $url   = $row['url'];
            $domain = parse_url($url, PHP_URL_HOST);
?>
    <div class="result-item">
        <div class="result-header">
            <img 
                src="https://www.google.com/s2/favicons?domain=<?php echo $domain; ?>&sz=32"
                class="favicon"
                loading="lazy"
            >
            <a href="<?php echo $url; ?>" class="result-url" target="_blank">
                <?php echo $domain; ?>
            </a>
        </div>

        <h3 class="result-title">
            <a href="<?php echo $url; ?>" target="_blank">
                <?php echo $title; ?>
            </a>
        </h3>

        <div class="result-snippet">
            <?php echo $desc; ?>
        </div>
    </div>
<?php
        }
    } else {
        echo "<p style='margin-top:20px;'>No results found</p>";
    }
}
?>

</main>

</body>
</html>
