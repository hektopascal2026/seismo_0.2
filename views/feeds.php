<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RSS - Seismo</title>
    <link rel="stylesheet" href="<?= getBasePath() ?>/assets/css/style.css">
</head>
<body>
    <div class="container">
        <!-- Navigation Menu -->
        <nav class="main-nav">
            <a href="?action=index" class="nav-link">
                <svg class="logo-icon" viewBox="0 0 24 16" xmlns="http://www.w3.org/2000/svg">
                    <rect width="24" height="16" fill="#FFFFC5"/>
                    <path d="M0,8 L4,12 L6,4 L10,10 L14,2 L18,8 L20,6 L24,8" stroke="#000000" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Feed
            </a>
            <a href="?action=feeds" class="nav-link active">RSS</a>
            <a href="?action=mail" class="nav-link">Mail</a>
            <a href="?action=settings" class="nav-link">Settings</a>
        </nav>

        <header>
            <h1>RSS</h1>
            <p class="subtitle">RSS entries</p>
        </header>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="message message-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="message message-error"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Add Feed Section -->
        <div class="add-feed-section">
            <form method="POST" action="?action=add_feed" class="add-feed-form">
                <input type="url" name="url" placeholder="Enter RSS feed URL (e.g., https://example.com/feed.xml)" required class="feed-input">
                <button type="submit" class="btn btn-primary">Add Feed</button>
            </form>
        </div>

        <?php if (!empty($categories) || isset($selectedCategory)): ?>
        <div class="category-filter-section">
            <div class="category-filter">
                <a href="?action=feeds"
                   class="category-btn <?= !$selectedCategory ? 'active' : '' ?>"
                   <?= !$selectedCategory ? 'style="background-color: #add8e6;"' : '' ?>>
                    All
                </a>
                <?php foreach ($categories as $category): ?>
                    <a href="?action=feeds&category=<?= urlencode($category) ?>"
                       class="category-btn <?= $selectedCategory === $category ? 'active' : '' ?>"
                       <?= $selectedCategory === $category ? 'style="background-color: #add8e6;"' : '' ?>>
                        <?= htmlspecialchars($category) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="latest-entries-section">
            <div class="section-title-row">
                <h2 class="section-title">
                    <?php if ($lastRssRefreshDate): ?>
                        Refreshed: <?= htmlspecialchars($lastRssRefreshDate) ?>
                    <?php else: ?>
                        Refreshed: Never
                    <?php endif; ?>
                </h2>
                <button class="btn btn-secondary entry-expand-all-btn" style="font-size: 14px; padding: 8px 16px;">&#9660; expand</button>
            </div>

            <?php if (empty($rssItems)): ?>
                <div class="empty-state">
                    <?php if ($selectedCategory): ?>
                        <p>No entries found in category "<?= htmlspecialchars($selectedCategory) ?>". <a href="?action=feeds">View all entries</a></p>
                    <?php else: ?>
                        <p>No RSS entries yet. Add feeds above to see entries here.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($rssItems as $item): ?>
                    <?php
                        $fullContent = strip_tags($item['content'] ?: $item['description']);
                        $contentPreview = mb_substr($fullContent, 0, 200);
                        if (mb_strlen($fullContent) > 200) $contentPreview .= '...';
                        $hasMore = mb_strlen($fullContent) > 200;
                    ?>
                    <div class="entry-card">
                        <div class="entry-header">
                            <span class="entry-feed"><?= htmlspecialchars($item['feed_title']) ?></span>
                            <?php if ($item['published_date']): ?>
                                <span class="entry-date"><?= date('d.m.Y H:i', strtotime($item['published_date'])) ?></span>
                            <?php endif; ?>
                        </div>
                        <h3 class="entry-title">
                            <a href="<?= htmlspecialchars($item['link']) ?>" target="_blank" rel="noopener">
                                <?= htmlspecialchars($item['title']) ?>
                            </a>
                        </h3>
                        <?php if ($item['description'] || $item['content']): ?>
                            <div class="entry-content entry-preview"><?= htmlspecialchars($contentPreview) ?></div>
                            <div class="entry-full-content" style="display:none"><?= htmlspecialchars($fullContent) ?></div>
                        <?php endif; ?>
                        <div class="entry-actions">
                            <?php if ($item['link']): ?>
                                <a href="<?= htmlspecialchars($item['link']) ?>" target="_blank" rel="noopener" class="entry-link">Read more →</a>
                            <?php endif; ?>
                            <?php if ($hasMore): ?>
                                <button class="btn btn-secondary entry-expand-btn" style="font-size: 14px; padding: 8px 16px;">&#9660; expand</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Floating Refresh Button -->
    <a href="?action=refresh_all_feeds&from=feeds<?= $selectedCategory ? '&category=' . urlencode($selectedCategory) : '' ?>" class="floating-refresh-btn" title="Refresh all feeds">Refresh</a>

    <script>
    (function() {
        function collapseEntry(card, btn) {
            var preview = card.querySelector('.entry-preview');
            var full = card.querySelector('.entry-full-content');
            if (!preview || !full) return;
            full.style.display = 'none';
            preview.style.display = '';
            if (btn) btn.textContent = '\u25BC expand';
        }

        function expandEntry(card, btn) {
            var preview = card.querySelector('.entry-preview');
            var full = card.querySelector('.entry-full-content');
            if (!preview || !full) return;
            preview.style.display = 'none';
            full.style.display = 'block';
            if (btn) btn.textContent = '\u25B2 collapse';
        }

        // Per-entry toggle
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.entry-expand-btn');
            if (!btn) return;
            var card = btn.closest('.entry-card');
            var full = card.querySelector('.entry-full-content');
            if (!full) return;
            if (full.style.display === 'block') {
                collapseEntry(card, btn);
            } else {
                expandEntry(card, btn);
            }
        });

        // Global toggle
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.entry-expand-all-btn');
            if (!btn) return;
            var isExpanded = btn.dataset.expanded === 'true';
            document.querySelectorAll('.entry-card').forEach(function(card) {
                var entryBtn = card.querySelector('.entry-expand-btn');
                if (isExpanded) {
                    collapseEntry(card, entryBtn);
                } else {
                    expandEntry(card, entryBtn);
                }
            });
            btn.dataset.expanded = !isExpanded;
            btn.textContent = !isExpanded ? '\u25B2 collapse' : '\u25BC expand';
        });
    })();
    </script>
</body>
</html>
