<?php
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "anna";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$start_time = microtime(true);

// Get query
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($q === '') {
    die("Please enter a search query.");
}

// Pagination
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Split query into words
$words = preg_split('/\s+/', $q);
$words = array_filter($words); // remove empty

// Build WHERE clause (AND logic)
$whereParts = [];
$params = [];
$types = "";

foreach ($words as $word) {
    $whereParts[] = "(title LIKE ? OR description LIKE ?)";
    $likeWord = "%" . $word . "%";

    $params[] = $likeWord;
    $params[] = $likeWord;

    $types .= "ss"; // two strings per word
}

$whereSQL = implode(" AND ", $whereParts);

// ----------------------
// COUNT total results
// ----------------------
$countSql = "SELECT COUNT(*) AS total FROM search_items WHERE $whereSQL";
$countStmt = $conn->prepare($countSql);
if (!$countStmt) {
    die("Prepare failed: " . $conn->error);
}

$countStmt->bind_param($types, ...$params);
$countStmt->execute();
$countResult = $countStmt->get_result()->fetch_assoc();
$totalResults = (int)$countResult['total'];
$countStmt->close();

// ----------------------
// Fetch results with pagination
// ----------------------
$searchSql = "
    SELECT id, title, description, page_name, page_fav_icon_path, page_url, created_at
    FROM search_items
    WHERE $whereSQL
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
";

$searchStmt = $conn->prepare($searchSql);
if (!$searchStmt) {
    die("Prepare failed: " . $conn->error);
}

// add limit + offset
$searchParams = $params;
$searchParams[] = $limit;
$searchParams[] = $offset;

$searchTypes = $types . "ii"; // integers

$searchStmt->bind_param($searchTypes, ...$searchParams);
$searchStmt->execute();
$results = $searchStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$searchStmt->close();

$conn->close();

$end_time = microtime(true);
$time_taken = round($end_time - $start_time, 4);

$totalPages = ($totalResults > 0) ? ceil($totalResults / $limit) : 1;

// Highlight function (highlights each word)
function highlightWords($text, $words) {
    foreach ($words as $w) {
        $w = preg_quote($w, '/');
        $text = preg_replace("/($w)/i", "<span class='highlight'>$1</span>", $text);
    }
    return $text;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Search results</title>

    <link rel="stylesheet" href="style.css" type="text/css"/>
    <style>
        .highlight { background: yellow; font-weight: bold; }
        .pagination { margin: 25px 0; display: flex; gap: 8px; flex-wrap: wrap; }
        .pagination a {
            padding: 8px 12px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            text-decoration: none;
            color: #333;
        }
        .pagination a.active {
            font-weight: bold;
            border: 2px solid #333;
        }
        .stats { margin: 15px 0; color: #444; }
    </style>
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
                required
            >
        </form>
    </div>
</header>

<main>

<div class="stats">
    About <b><?php echo number_format($totalResults); ?></b> results (<?php echo $time_taken; ?> seconds)
</div>

<?php if (empty($results)): ?>
    <p style="margin-top:20px;">No results found for <b><?php echo htmlspecialchars($q); ?></b>.</p>
<?php else: ?>

    <?php foreach ($results as $row): ?>
        <?php
            // Escape FIRST (security)
            $pageUrl  = htmlspecialchars($row['page_url']);
            $pageName = htmlspecialchars($row['page_name']);
            $favicon  = htmlspecialchars($row['page_fav_icon_path']);

            $title = htmlspecialchars($row['title']);
            $desc  = htmlspecialchars($row['description']);

            // Highlight AFTER escaping
            $title = highlightWords($title, $words);
            $desc  = highlightWords($desc, $words);
        ?>

        <div class="result-item">
            <div class="result-header">
                <img src="<?php echo $favicon; ?>" class="favicon" alt="" loading="lazy">
                <a href="<?php echo $pageUrl; ?>" class="result-url" target="_blank">
                    <?php echo $pageName; ?>
                </a>
            </div>

            <h3 class="result-title">
                <a href="<?php echo $pageUrl; ?>" target="_blank">
                    <?php echo $title; ?>
                </a>
            </h3>

            <div class="result-snippet">
                <?php echo $desc; ?>
            </div>
        </div>

    <?php endforeach; ?>

<?php endif; ?>

<!-- Pagination -->
<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="results.php?q=<?php echo urlencode($q); ?>&page=<?php echo $page - 1; ?>">Prev</a>
    <?php endif; ?>

    <?php
    $startPage = max(1, $page - 3);
    $endPage = min($totalPages, $page + 3);

    for ($p = $startPage; $p <= $endPage; $p++):
    ?>
        <a class="<?php echo ($p == $page) ? 'active' : ''; ?>"
           href="results.php?q=<?php echo urlencode($q); ?>&page=<?php echo $p; ?>">
            <?php echo $p; ?>
        </a>
    <?php endfor; ?>

    <?php if ($page < $totalPages): ?>
        <a href="results.php?q=<?php echo urlencode($q); ?>&page=<?php echo $page + 1; ?>">Next</a>
    <?php endif; ?>
</div>

</main>
</body>
</html>