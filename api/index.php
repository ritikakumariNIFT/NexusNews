<?php
/**
 * NEXUS NEWS - LIVE API PRODUCTION BUILD
 * Features: REST API Integration, Fault Tolerance, Dynamic Routing
 */

// 1. CONFIGURATION & API FETCHING
$apiKey = "2a03521c0ded47a99b9ef5759c9beec9";
// Fetching top US tech/general news. You can change 'category=technology' if you want!
$apiUrl = "https://newsapi.org/v2/top-headlines?country=us&category=technology&apiKey=" . $apiKey;

// NewsAPI requires a User-Agent header to work via PHP
$options = ["http" => ["header" => "User-Agent: NexusNews-Portfolio/1.0\r\n"]];
$context = stream_context_create($options);

// Attempt to fetch live data (with error suppression so it doesn't crash if the API fails)
$apiResponse = @file_get_contents($apiUrl, false, $context);
$liveData = $apiResponse ? json_decode($apiResponse, true) : null;

$formattedArticles = [];

// 2. FAULT TOLERANCE (If API works, use it. If not, use Fallback Mock Data)
if ($liveData && $liveData['status'] === 'ok' && !empty($liveData['articles'])) {
    foreach ($liveData['articles'] as $art) {
        // Skip articles without images or titles to keep the UI looking clean
        if (empty($art['urlToImage']) || empty($art['title']) || $art['title'] == '[Removed]') continue;
        
        $formattedArticles[] = [
            "id" => md5($art['url']), // Create a unique ID from the URL
            "title" => $art['title'],
            "category" => "Live Feed",
            "excerpt" => $art['description'] ?: "Click to read more about this breaking story.",
            "content" => $art['content'] ?: "Full content available on the publisher's website.",
            "author" => $art['author'] ?: $art['source']['name'] ?? "Staff Writer",
            "timestamp" => strtotime($art['publishedAt']),
            "image" => $art['urlToImage'],
            "source_url" => $art['url'] // Link to the actual BBC/CNN/TechCrunch article
        ];
    }
} else {
    // FALLBACK DATA: Keeps your portfolio looking perfect even if the API limit is reached
    $formattedArticles = [
        [
            "id" => "mock-101", 
            "title" => "API Limit Reached: Showing Archived Article on Quantum Computing", 
            "category" => "Archive", 
            "excerpt" => "Researchers have successfully stabilized a 1000-qubit processor...",
            "content" => "This is a fallback article because the live NewsAPI key reached its limit or failed to load. However, the system architecture gracefully caught the error and displayed this archived data instead.",
            "author" => "System Admin", 
            "timestamp" => time(), 
            "image" => "https://images.unsplash.com/photo-1635070041078-e363dbe005cb?w=800&q=80",
            "source_url" => "#"
        ]
    ];
}

// 3. ROUTING LAYER
$searchQuery = filter_input(INPUT_GET, 'q', FILTER_SANITIZE_SPECIAL_CHARS) ?: '';
$readId = filter_input(INPUT_GET, 'read', FILTER_SANITIZE_SPECIAL_CHARS);

// Handle Search
if ($searchQuery) {
    $formattedArticles = array_filter($formattedArticles, function($article) use ($searchQuery) {
        return stripos($article['title'], $searchQuery) !== false;
    });
}

// Find specific article for Read View
$singleArticle = null;
if ($readId) {
    foreach ($formattedArticles as $art) {
        if ($art['id'] === $readId) {
            $singleArticle = $art;
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexusNews <?= $singleArticle ? " | " . htmlspecialchars($singleArticle['title']) : " | Live Feed" ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --font-heading: 'Playfair Display', serif; --font-body: 'Inter', sans-serif; --brand-red: #e63946; --brand-dark: #111; }
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Playfair+Display:wght@700;900&display=swap');
        
        body { font-family: var(--font-body); background-color: #fafafa; }
        h1, h2, h3, .brand-logo { font-family: var(--font-heading); font-weight: 900; }
        
        .navbar-main { border-bottom: 2px solid #eee; background: white; }
        .brand-logo { font-size: 2.2rem; color: var(--brand-dark); text-decoration: none; letter-spacing: -1px; }
        .brand-logo span { color: var(--brand-red); }
        
        /* Grid Cards */
        .article-card { background: white; border: 1px solid #eaeaea; transition: 0.3s; height: 100%; display: flex; flex-direction: column; }
        .article-card:hover { box-shadow: 0 10px 20px rgba(0,0,0,0.05); transform: translateY(-3px); border-color: #ddd; }
        .article-img { width: 100%; height: 220px; object-fit: cover; border-bottom: 3px solid var(--brand-red); }
        
        /* Single Article */
        .read-header-img { width: 100%; height: 60vh; max-height: 500px; object-fit: cover; border-radius: 12px; margin-bottom: 30px; }
        .read-content { font-size: 1.15rem; line-height: 1.8; color: #444; }
        .live-badge { display: inline-block; animation: pulse 2s infinite; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
    </style>
</head>
<body>

<nav class="navbar-main shadow-sm py-3 mb-4 sticky-top">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-3">
        <a href="?" class="brand-logo">NEXUS<span>NEWS</span></a>
        
        <?php if(!$singleArticle): ?>
        <form method="GET" class="d-flex flex-grow-1 mx-md-4" style="max-width: 400px;">
            <input type="text" name="q" class="form-control rounded-0 border-dark shadow-none" placeholder="Search live feed..." value="<?= $searchQuery ?>">
            <button class="btn btn-dark rounded-0 px-4">Search</button>
        </form>
        <div class="text-danger fw-bold small live-badge d-none d-md-block">● LIVE DATA CONNECTED</div>
        <?php else: ?>
        <a href="?" class="btn btn-outline-dark rounded-pill px-4">&larr; Back to Live Feed</a>
        <?php endif; ?>
    </div>
</nav>

<div class="container pb-5">
    
    <?php if ($singleArticle): ?>
        <article class="mx-auto" style="max-width: 800px;">
            <div class="mb-4 text-center">
                <span class="badge bg-danger mb-3 px-3 py-2"><?= htmlspecialchars($singleArticle['category']) ?></span>
                <h1 class="display-5 fw-bold text-dark lh-sm"><?= htmlspecialchars($singleArticle['title']) ?></h1>
                <p class="text-muted mt-3">
                    Reported by <strong><?= htmlspecialchars($singleArticle['author']) ?></strong> | 
                    <?= date('F j, Y, g:i a', $singleArticle['timestamp']) ?>
                </p>
            </div>
            
            <img src="<?= htmlspecialchars($singleArticle['image']) ?>" class="read-header-img shadow-sm" alt="Article Image">
            
            <div class="read-content bg-white p-4 p-md-5 border rounded shadow-sm">
                <p class="lead fw-bold text-dark border-start border-danger border-4 ps-3 mb-4">
                    <?= htmlspecialchars($singleArticle['excerpt']) ?>
                </p>
                
                <p><?= nl2br(htmlspecialchars(preg_replace('/\[\+\d+ chars\]$/', '', $singleArticle['content']))) ?></p>
                
                <div class="alert alert-light border mt-5 text-center p-4">
                    <h5 class="fw-bold mb-3">Want to read the full report?</h5>
                    <p class="small text-muted mb-4">You are viewing a syndication snippet provided by the publisher via NewsAPI.</p>
                    <a href="<?= htmlspecialchars($singleArticle['source_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-danger btn-lg px-5 rounded-pill shadow-sm">
                        Read Full Story on Publisher Site ↗
                    </a>
                </div>
            </div>
        </article>

    <?php else: ?>
        <div class="d-flex justify-content-between align-items-end border-bottom border-dark pb-2 mb-4">
            <h2 class="m-0 fs-3 fw-bold">Top Stories</h2>
            <small class="text-muted">Auto-updating feed</small>
        </div>

        <div class="row g-4">
            <?php foreach ($formattedArticles as $article): ?>
                <div class="col-md-6 col-lg-4">
                    <article class="article-card rounded overflow-hidden">
                        <img src="<?= htmlspecialchars($article['image']) ?>" class="article-img">
                        <div class="p-4 d-flex flex-column flex-grow-1">
                            <div class="small text-danger fw-bold text-uppercase mb-2">
                                <?= date('M j, Y', $article['timestamp']) ?>
                            </div>
                            <h3 class="fs-5 fw-bold mb-3 lh-base text-dark" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                                <?= htmlspecialchars($article['title']) ?>
                            </h3>
                            <p class="text-muted flex-grow-1 small" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                <?= htmlspecialchars($article['excerpt']) ?>
                            </p>
                            <a href="?read=<?= $article['id'] ?>" class="btn btn-dark w-100 rounded-0 mt-3 border-0" style="background: var(--brand-dark);">
                                Read Story
                            </a>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($formattedArticles)): ?>
                <div class="col-12 text-center py-5">
                    <h4 class="text-muted">No articles found matching your search.</h4>
                    <a href="?" class="btn btn-danger mt-3 px-4">Clear Search</a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<footer class="bg-dark text-white text-center py-4 mt-auto">
    <p class="m-0 text-muted small">&copy; <?= date('Y') ?> NexusNews. Powered by Live NewsAPI Integration.</p>
</footer>

</body>
</html>
    <meta property="og:title" content="NexusNews | Global Intelligence">
    <meta property="og:type" content="website">
    
    <title>NexusNews | <?= htmlspecialchars($currentCategory) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root { --font-heading: 'Playfair Display', serif; --font-body: 'Inter', sans-serif; --brand-red: #e63946; --brand-dark: #111; }
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Playfair+Display:wght@700;900&display=swap');
        
        body { font-family: var(--font-body); background-color: #fafafa; color: #333; }
        h1, h2, h3, .brand-logo { font-family: var(--font-heading); font-weight: 900; }
        
        /* Top Navigation */
        .top-bar { background: var(--brand-dark); color: white; font-size: 0.8rem; padding: 5px 0; }
        .navbar-main { border-bottom: 2px solid #eee; background: white; position: sticky; top: 0; z-index: 1000; }
        .brand-logo { font-size: 2.2rem; color: var(--brand-dark); text-decoration: none; letter-spacing: -1px; }
        .brand-logo span { color: var(--brand-red); }
        .nav-category { font-weight: 600; color: #555; text-transform: uppercase; font-size: 0.85rem; padding: 10px 15px; }
        .nav-category:hover, .nav-category.active { color: var(--brand-red); }
        
        /* Article Cards */
        .article-card { background: white; border: 1px solid #eaeaea; transition: 0.3s; height: 100%; display: flex; flex-direction: column; }
        .article-card:hover { box-shadow: 0 10px 20px rgba(0,0,0,0.05); transform: translateY(-3px); }
        .article-img { width: 100%; height: 200px; object-fit: cover; border-bottom: 3px solid var(--brand-red); }
        .article-meta { font-size: 0.75rem; color: #888; text-transform: uppercase; font-weight: 600; margin-bottom: 8px; }
        .article-title { font-size: 1.25rem; line-height: 1.3; color: var(--brand-dark); margin-bottom: 10px; }
        .article-excerpt { font-size: 0.95rem; color: #666; flex-grow: 1; }
        
        /* Featured Layout */
        .hero-featured { background: var(--brand-dark); color: white; border-bottom: 5px solid var(--brand-red); }
        .hero-img { width: 100%; height: 100%; min-height: 400px; object-fit: cover; }
        .hero-title { font-size: 3rem; line-height: 1.1; margin-bottom: 20px; }
        .hero-excerpt { font-size: 1.2rem; color: #ccc; }

        /* API Badge */
        .api-badge { position: fixed; bottom: 20px; right: 20px; background: #000; color: #00ff00; padding: 10px 20px; border-radius: 5px; font-family: monospace; z-index: 9999; box-shadow: 0 4px 10px rgba(0,0,0,0.3); text-decoration: none;}
        .api-badge:hover { color: white; }
    </style>
</head>
<body>

<div class="top-bar d-none d-md-block">
    <div class="container d-flex justify-content-between">
        <span><?= date('l, F j, Y') ?> | Edition: Global</span>
        <span>Developer API Available</span>
    </div>
</div>

<nav class="navbar-main shadow-sm">
    <div class="container py-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="?" class="brand-logo">NEXUS<span>NEWS</span></a>
            <form method="GET" class="d-flex w-50 max-w-sm">
                <input type="text" name="q" class="form-control rounded-0 border-dark" placeholder="Search news..." value="<?= $searchQuery ?>">
                <button class="btn btn-dark rounded-0 px-4">Search</button>
            </form>
        </div>
        <div class="d-flex flex-wrap overflow-auto pb-2 border-top pt-2">
            <a href="?category=All" class="nav-category text-decoration-none <?= $currentCategory == 'All' ? 'active' : '' ?>">Top Stories</a>
            <a href="?category=Technology" class="nav-category text-decoration-none <?= $currentCategory == 'Technology' ? 'active' : '' ?>">Technology</a>
            <a href="?category=Finance" class="nav-category text-decoration-none <?= $currentCategory == 'Finance' ? 'active' : '' ?>">Finance</a>
            <a href="?category=Policy" class="nav-category text-decoration-none <?= $currentCategory == 'Policy' ? 'active' : '' ?>">Policy</a>
            <a href="?category=Infrastructure" class="nav-category text-decoration-none <?= $currentCategory == 'Infrastructure' ? 'active' : '' ?>">Infrastructure</a>
        </div>
    </div>
</nav>

<?php if ($featuredArticle): ?>
<section class="hero-featured mb-5">
    <div class="container-fluid p-0">
        <div class="row g-0">
            <div class="col-lg-7">
                <img src="<?= $featuredArticle['image'] ?>" class="hero-img" alt="Featured">
            </div>
            <div class="col-lg-5 p-5 d-flex flex-column justify-content-center">
                <span class="text-danger fw-bold text-uppercase mb-2">● Breaking News</span>
                <h1 class="hero-title"><?= htmlspecialchars($featuredArticle['title']) ?></h1>
                <p class="hero-excerpt"><?= htmlspecialchars($featuredArticle['excerpt']) ?></p>
                <div class="mt-4 text-muted small">
                    By <?= htmlspecialchars($featuredArticle['author']) ?> • <?= round((time() - $featuredArticle['timestamp']) / 3600) ?> hours ago
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<div class="container pb-5 <?= !$featuredArticle ? 'mt-5' : '' ?>">
    <div class="d-flex justify-content-between border-bottom pb-2 mb-4 align-items-end">
        <h2 class="m-0 fs-3">Latest <?= $currentCategory == 'All' ? 'Headlines' : htmlspecialchars($currentCategory) ?></h2>
        <?php if($searchQuery): ?> <span class="text-muted">Results for "<?= $searchQuery ?>"</span> <?php endif; ?>
    </div>

    <div class="row g-4">
        <?php 
        $count = 0;
        foreach ($filteredArticles as $article): 
            // Skip featured article in the grid to avoid duplication
            if ($featuredArticle && $article['id'] === $featuredArticle['id']) continue;
            $count++;
        ?>
            <div class="col-md-6 col-lg-4">
                <article class="article-card">
                    <img src="<?= $article['image'] ?>" class="article-img" loading="lazy" alt="Thumbnail">
                    <div class="p-4 d-flex flex-column flex-grow-1">
                        <div class="article-meta">
                            <span class="text-danger"><?= htmlspecialchars($article['category']) ?></span> • 
                            <?= date('M j, Y', $article['timestamp']) ?>
                        </div>
                        <h3 class="article-title">
                            <a href="#" class="text-decoration-none text-dark"><?= htmlspecialchars($article['title']) ?></a>
                        </h3>
                        <p class="article-excerpt"><?= htmlspecialchars($article['excerpt']) ?></p>
                        <div class="mt-3 pt-3 border-top small text-muted font-monospace">
                            By <?= htmlspecialchars($article['author']) ?>
                        </div>
                    </div>
                </article>
            </div>
        <?php endforeach; 
        if ($count === 0): ?>
            <div class="col-12 text-center py-5">
                <h4 class="text-muted">No news articles found for your criteria.</h4>
                <a href="?" class="btn btn-outline-danger mt-3">Clear Search</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<a href="?format=json" target="_blank" class="api-badge">
    { / } View as JSON API
</a>

<footer class="bg-dark text-white text-center py-4 mt-auto border-top border-danger border-5">
    <p class="m-0 text-muted small">&copy; <?= date('Y') ?> NexusNews Media Group. A Production-Grade Portfolio Build.</p>
</footer>

</body>
</html>
