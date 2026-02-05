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
    <meta name="description" content="Search anything with our simple and fast search engine." />
    <meta name="keywords" content="search, engine, simple, fast, web search" />
    <meta name="author" content="Your Name" />
    <meta name="theme-color" content="#ffffff" />
    <link rel="apple-touch-icon" href="apple-touch-icon.png" />
    <link rel="manifest" href="manifest.json" />
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

    // text search
    $stmt = $conn->prepare("
        SELECT *
        FROM search_items
        WHERE MATCH(title, description)
        AGAINST(? IN NATURAL LANGUAGE MODE)
    ");
    $stmt->bind_param("s", $q);
    $stmt->execute();
    $result = $stmt->get_result();
    $count  = $result->num_rows;

    $end_time = microtime(true);
    $time_taken = round($end_time - $start_time, 2);

    echo "<div class='stats'>About $count results ($time_taken seconds)</div>";

    if ($count > 0) {
        while ($row = $result->fetch_assoc()) {

            // data from database
            $pageUrl   = $row['page_url'];
            $pageTitle = $row['title'];
            $pageDesc  = $row['description'];
            $pageName  = $row['page_name'];
            $favicon   = $row['page_fav_icon_path'];

            // highlight keyword yellow
            $safeQ = preg_quote($q, '/');
            $pageTitle = preg_replace("/($safeQ)/i", "<span class='highlight'>$1</span>", $pageTitle);
            $pageDesc  = preg_replace("/($safeQ)/i", "<span class='highlight'>$1</span>", $pageDesc);
?>
    <div class="result-item">
        <div class="result-header">
            <img 
                src="<?php echo htmlspecialchars($favicon); ?>"
                class="favicon"
                alt=""
                loading="lazy"
            >
            <a href="<?php echo htmlspecialchars($pageUrl); ?>" class="result-url" target="_blank">
                <?php echo htmlspecialchars($pageName); ?>
            </a>
        </div>

        <h3 class="result-title">
            <a href="<?php echo htmlspecialchars($pageUrl); ?>" target="_blank">
                <?php echo $pageTitle; ?>
            </a>
        </h3>

        <div class="result-snippet">
            <?php echo $pageDesc; ?>
        </div>
    </div>
<?php
        }
    } else {
        echo "<p style='margin-top:20px;'>No results found</p>";
    }

    $stmt->close();
}

include 'includes/footer.php';
?>

</main>

</body>
</html>
