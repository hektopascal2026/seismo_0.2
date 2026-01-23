<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seismo</title>
    <link rel="stylesheet" href="<?= getBasePath() ?>/assets/css/style.css">
</head>
<body>
    <div class="container">
        <!-- Navigation Menu -->
        <nav class="main-nav">
            <a href="?action=index" class="nav-link active">
                <svg class="logo-icon" viewBox="0 0 24 16" xmlns="http://www.w3.org/2000/svg">
                    <rect width="24" height="16" fill="#FFFFC5"/>
                    <path d="M0,8 L4,12 L6,4 L10,10 L14,2 L18,8 L20,6 L24,8" stroke="#000000" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Seismo
            </a>
            <a href="?action=feeds" class="nav-link">Feeds</a>
            <a href="?action=mail" class="nav-link">Mail</a>
        </nav>

        <header>
            <h1>
                <svg class="logo-icon logo-icon-large" viewBox="0 0 24 16" xmlns="http://www.w3.org/2000/svg">
                    <rect width="24" height="16" fill="#FFFFC5"/>
                    <path d="M0,8 L4,12 L6,4 L10,10 L14,2 L18,8 L20,6 L24,8" stroke="#000000" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Seismo
            </h1>
            <p class="subtitle">ein Prototyp von hektopascal.org | v0.2</p>
        </header>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="message message-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="message message-error"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Search Box -->
        <div class="search-section">
            <form method="GET" action="?action=index" class="search-form">
                <input type="search" name="q" placeholder="Search..." class="search-input" value="<?= htmlspecialchars($searchQuery ?? '') ?>">
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if (!empty($searchQuery) || !empty($selectedTags)): ?>
                    <a href="?action=index" class="btn btn-secondary">Clear</a>
                <?php endif; ?>

                <?php if (!empty($tags)): ?>
                    <div class="tag-filter-section">
                        <div class="tag-filter-list">
                            <?php foreach ($tags as $tag): ?>
                                <?php 
                                    $isSelected = empty($selectedTags) || in_array($tag, $selectedTags, true);
                                ?>
                                <label class="tag-filter-pill<?= $isSelected ? ' tag-filter-pill-active' : '' ?>">
                                    <input 
                                        type="checkbox" 
                                        name="tags[]" 
                                        value="<?= htmlspecialchars($tag) ?>" 
                                        <?= $isSelected ? 'checked' : '' ?>
                                        onchange="this.form.submit()"
                                    >
                                    <span><?= htmlspecialchars($tag) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Latest Entries from All Feeds / Search Results -->
        <div class="latest-entries-section">
            <?php if (!empty($searchQuery)): ?>
                <h2 class="section-title">
                    Search Results<?= $searchResultsCount !== null ? ' (' . $searchResultsCount . ')' : '' ?>
                    <span style="font-weight: 400; font-size: 18px; color: #666;">for "<?= htmlspecialchars($searchQuery) ?>"</span>
                </h2>
            <?php else: ?>
                <h2 class="section-title">
                    <?php if ($lastRefreshDate): ?>
                        Refreshed: <?= htmlspecialchars($lastRefreshDate) ?>
                    <?php else: ?>
                        Refreshed: Never
                    <?php endif; ?>
                </h2>
            <?php endif; ?>
            
            <?php if (!empty($latestItems)): ?>
                <?php foreach ($latestItems as $item): ?>
                    <div class="entry-card">
                        <div class="entry-header">
                            <span class="entry-feed"><?= htmlspecialchars($item['feed_title']) ?></span>
                            <?php if ($item['published_date']): ?>
                                <span class="entry-date"><?= date('d.m.Y H:i', strtotime($item['published_date'])) ?></span>
                            <?php endif; ?>
                        </div>
                        <h3 class="entry-title">
                            <a href="<?= htmlspecialchars($item['link']) ?>" target="_blank" rel="noopener">
                                <?php if (!empty($searchQuery)): ?>
                                    <?= highlightSearchTerm($item['title'], $searchQuery) ?>
                                <?php else: ?>
                                    <?= htmlspecialchars($item['title']) ?>
                                <?php endif; ?>
                            </a>
                        </h3>
                        <?php if ($item['description'] || $item['content']): ?>
                            <div class="entry-content">
                                <?php 
                                    $content = strip_tags($item['content'] ?: $item['description']);
                                    $contentPreview = mb_substr($content, 0, 200);
                                    if (mb_strlen($content) > 200) $contentPreview .= '...';
                                    
                                    if (!empty($searchQuery)) {
                                        echo highlightSearchTerm($contentPreview, $searchQuery);
                                    } else {
                                        echo htmlspecialchars($contentPreview);
                                    }
                                ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($item['link']): ?>
                            <a href="<?= htmlspecialchars($item['link']) ?>" target="_blank" rel="noopener" class="entry-link">Read more →</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <?php if (!empty($searchQuery)): ?>
                        <p>No results found for "<?= htmlspecialchars($searchQuery) ?>". Try a different search term.</p>
                    <?php else: ?>
                        <p>No entries available yet. Add feeds to see entries here.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Floating Refresh Button -->
    <a href="?action=refresh_all_feeds&from=index" class="floating-refresh-btn" title="Refresh all feeds">Refresh</a>
</body>
</html>
