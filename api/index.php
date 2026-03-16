<?php
/**
 * NEXUS NEWS - PRODUCTION GRADE PORTFOLIO PIECE
 * Features: Security Sanitization, REST API Endpoint, Dynamic Routing, SEO Meta Tags
 */

// 1. DATA LAYER (Simulating a real Database or External API fetch)
$articles = [
    [
        "id" => "101", 
        "title" => "Quantum Computing Breaks New Barrier in Cryptography", 
        "slug" => "quantum-computing-cryptography",
        "category" => "Technology", 
        "excerpt" => "Researchers have successfully stabilized a 1000-qubit processor, signaling a major shift in global cybersecurity protocols.",
        "author" => "Dr. Aris Thorne", 
        "timestamp" => strtotime("-2 hours"), 
        "featured" => true,
        "image" => "https://images.unsplash.com/photo-1635070041078-e363dbe005cb?w=800&q=80"
    ],
    [
        "id" => "102", 
        "title" => "Global Markets Rally as Green Energy Subsidies Pass", 
        "slug" => "global-markets-green-energy",
        "category" => "Finance", 
        "excerpt" => "Tech and energy stocks saw a 4% surge following the latest international climate accord agreements.",
        "author" => "Elena Rostova", 
        "timestamp" => strtotime("-5 hours"), 
        "featured" => false,
        "image" => "https://images.unsplash.com/photo-1611974789855-9c2a0a7236a3?w=800&q=80"
    ],
    [
        "id" => "103", 
        "title" => "AI Regulation: What the New Tech Bill Means for Developers", 
        "slug" => "ai-regulation-tech-bill",
        "category" => "Policy", 
        "excerpt" => "A deep dive into the compliance requirements for open-source AI models in the upcoming fiscal year.",
        "author" => "Marcus Vance", 
        "timestamp" => strtotime("-1 day"), 
        "featured" => false,
        "image" => "https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=800&q=80"
    ],
    [
        "id" => "104", 
        "title" => "The Rise of Smart Grids in Urban Development", 
        "slug" => "smart-grids-urban",
        "category" => "Infrastructure", 
        "excerpt" => "How metropolitan areas are using IoT sensors to reduce power waste by up to 30%.",
        "author" => "Sarah Jenkins", 
        "timestamp" => strtotime("-2 days"), 
        "featured" => false,
        "image" => "https://images.unsplash.com/photo-1473341304170-971dccb5ac1e?w=800&q=80"
    ]
];

// 2. SECURITY & ROUTING LAYER
// Always sanitize inputs in production to prevent XSS and SQL Injection
$currentCategory = filter_input(INPUT_GET, 'category', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'All';
$searchQuery = filter_input(INPUT_GET, 'q', FILTER_SANITIZE_SPECIAL_CHARS) ?: '';
$isApiRequest = filter_input(INPUT_GET, 'format', FILTER_SANITIZE_SPECIAL_CHARS) === 'json';

// Filter Logic
$filteredArticles = array_filter($articles, function($article) use ($currentCategory, $searchQuery) {
    $matchCat = ($currentCategory === 'All' || $article['category'] === $currentCategory);
    $matchSearch = ($searchQuery === '' || stripos($article['title'], $searchQuery) !== false);
    return $matchCat && $matchSearch;
});

// 3. REST API ENDPOINT LAYER
// If a developer or app requests '?format=json', send JSON instead of HTML
if ($isApiRequest) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *'); // CORS policy
    echo json_encode([
        "status" => 200,
        "timestamp" => time(),
        "total_results" => count($filteredArticles),
        "data" => array_values($filteredArticles)
    ]);
    exit; // Stop rendering HTML
}

// 4. VIEW LAYER (HTML rendering)
$featuredArticle = null;
foreach ($articles as $art) {
    if ($art['featured'] && $currentCategory === 'All' && $searchQuery === '') {
        $featuredArticle = $art;
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="NexusNews - Breaking news, deep analysis, and global intelligence.">
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
