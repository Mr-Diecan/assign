<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Search results</title>
    <link rel="stylesheet" href="style.css" type="text/css"/>
    <link rel="icon" href="favicon.ico" type="image/x-icon" />
</head>

<body>

<header>
    <div class="search-bar-container">
        <a href="index.html" class="logo">Search</a>

        <form class="search-form" action="results.php" method="get">
            <span class="search-icon">🔍</span>
            <input 
                type="search" 
                name="q" 
                class="search-input" 
                value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>" 
                placeholder="Search..."
            >
        </form>
    </div>
</header>

<main>

<?php
$startTime = microtime(true);
require_once 'connection.php';

$query = trim($_GET['q'] ?? '');
$results = [];
$resultsPerPage = 20;

$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1);
$offset = ($page - 1) * $resultsPerPage;

$totalResults = 0;
$totalPages = 0;
$timeTaken = 0;

function highlightKeywords($text, $keywords) {
    if (empty($keywords)) return htmlspecialchars($text);
    $keywords = preg_quote($keywords, '/');
    return preg_replace(
        "/($keywords)/i",
        '<span class="highlight">$1</span>',
        htmlspecialchars($text)
    );
}

if (!empty($query)) {

    $searchTerm = "%{$query}%";
    $exactTerm  = $query;

    $countSql = "
        SELECT COUNT(*) 
        FROM search_items
        WHERE title LIKE :search
           OR description LIKE :search
    ";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute([':search' => $searchTerm]);
    $totalResults = $countStmt->fetchColumn();
    $totalPages = ceil($totalResults / $resultsPerPage);

    $sql = "
        SELECT 
            title,
            description,
            page_name,
            page_fav_icon_path,
            page_url,
            (
                (CASE WHEN title = :exact THEN 10 ELSE 0 END) +
                (CASE WHEN title LIKE :search THEN 5 ELSE 0 END) +
                (CASE WHEN description LIKE :search THEN 2 ELSE 0 END)
            ) AS relevance_score
        FROM search_items
        WHERE title LIKE :search
           OR description LIKE :search
        ORDER BY relevance_score DESC, created_at DESC
        LIMIT {$resultsPerPage} OFFSET {$offset}
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':search' => $searchTerm,
        ':exact'  => $exactTerm
    ]);

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$endTime = microtime(true);
$timeTaken = $endTime - $startTime;
?>

<div class="stats">
    <?php echo number_format($totalResults); ?> result(s) found
    (Search took <?php echo round($timeTaken, 3); ?> seconds)
</div>

<?php if (!empty($results)): ?>
    <?php foreach ($results as $item): ?>
        <div class="result-item">

            <div class="result-header">
                <img src="<?php echo htmlspecialchars($item['page_fav_icon_path']); ?>" 
                     class="favicon" alt="favicon">

                <div class="page-info">
                    <span class="page-name">
                        <?php echo htmlspecialchars($item['page_name']); ?>
                    </span><br>

                    <a href="<?php echo htmlspecialchars($item['page_url']); ?>" 
                       class="result-url" 
                       target="_blank">
                        <?php echo htmlspecialchars($item['page_url']); ?>
                    </a>
                </div>
            </div>

            <h3 class="result-title">
                <a href="<?php echo htmlspecialchars($item['page_url']); ?>" target="_blank">
                    <?php echo highlightKeywords($item['title'], $query); ?>
                </a>
            </h3>

            <div class="result-snippet">
                <?php 
                    $snippet = substr($item['description'], 0, 200);
                    echo highlightKeywords($snippet, $query) . "...";
                ?>
            </div>

        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="result-item">
        <h3>No results found for "<?php echo htmlspecialchars($query); ?>"</h3>
    </div>
<?php endif; ?>

<?php if ($totalPages > 1): ?>
<div class="pagination">

    <?php if ($page > 1): ?>
        <a class="page-link" href="?q=<?php echo urlencode($query); ?>&page=<?php echo $page - 1; ?>">« Prev</a>
    <?php endif; ?>

    <?php if ($page > 3): ?>
        <a class="page-link" href="?q=<?php echo urlencode($query); ?>&page=1">1</a>
        <span class="dots">…</span>
    <?php endif; ?>

    <?php
        $start = max(1, $page - 2);
        $end   = min($totalPages, $page + 2);

        for ($i = $start; $i <= $end; $i++):
    ?>

        <?php if ($i == $page): ?>
            <span class="page-link current"><?php echo $i; ?></span>
        <?php else: ?>
            <a class="page-link" href="?q=<?php echo urlencode($query); ?>&page=<?php echo $i; ?>">
                <?php echo $i; ?>
            </a>
        <?php endif; ?>

    <?php endfor; ?>

    <?php if ($page < $totalPages - 2): ?>
        <span class="dots">…</span>
        <a class="page-link" href="?q=<?php echo urlencode($query); ?>&page=<?php echo $totalPages; ?>">
            <?php echo $totalPages; ?>
        </a>
    <?php endif; ?>

    <?php if ($page < $totalPages): ?>
        <a class="page-link" href="?q=<?php echo urlencode($query); ?>&page=<?php echo $page + 1; ?>">Next »</a>
    <?php endif; ?>

</div>
<?php endif; ?>

</main>

</body>
</html>
