<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Substack - Seismo</title>
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
            <a href="?action=feeds" class="nav-link">RSS</a>
            <a href="?action=mail" class="nav-link">Mail</a>
            <a href="?action=substack" class="nav-link active">Substack</a>
            <a href="?action=settings" class="nav-link">Settings</a>
        </nav>

        <header>
            <h1>Substack</h1>
            <p class="subtitle">Substack newsletters</p>
        </header>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="message message-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="message message-error"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Add Substack Section -->
        <div class="add-feed-section">
            <form method="POST" action="?action=add_substack" class="add-feed-form">
                <input type="text" name="url" placeholder="Enter Substack URL (e.g., example.substack.com)" required class="feed-input">
                <button type="submit" class="btn btn-primary">Subscribe</button>
            </form>
        </div>

        <?php if (!empty($substackCategories)): ?>
        <div class="category-filter-section">
            <div class="category-filter">
                <a href="?action=substack"
                   class="category-btn <?= !$selectedSubstackCategory ? 'active' : '' ?>"
                   <?= !$selectedSubstackCategory ? 'style="background-color: #C5B4D1;"' : '' ?>>
                    All
                </a>
                <?php foreach ($substackCategories as $category): ?>
                    <a href="?action=substack&category=<?= urlencode($category) ?>"
                       class="category-btn <?= $selectedSubstackCategory === $category ? 'active' : '' ?>"
                       <?= $selectedSubstackCategory === $category ? 'style="background-color: #C5B4D1;"' : '' ?>>
                        <?= htmlspecialchars($category) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="latest-entries-section">
            <div class="section-title-row">
                <h2 class="section-title">
                    <?php if ($lastSubstackRefreshDate): ?>
                        Refreshed: <?= htmlspecialchars($lastSubstackRefreshDate) ?>
                    <?php else: ?>
                        Refreshed: Never
                    <?php endif; ?>
                </h2>
                <button class="btn btn-secondary entry-expand-all-btn" style="font-size: 14px; padding: 8px 16px;">&#9660; expand all</button>
            </div>

            <?php if (empty($substackItems)): ?>
                <div class="empty-state">
                    <?php if ($selectedSubstackCategory): ?>
                        <p>No entries found in "<?= htmlspecialchars($selectedSubstackCategory) ?>". <a href="?action=substack">View all entries</a></p>
                    <?php else: ?>
                        <p>No Substack posts yet. Subscribe to a newsletter above to see posts here.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($substackItems as $item): ?>
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
    <a href="?action=refresh_all_substacks<?= $selectedSubstackCategory ? '&category=' . urlencode($selectedSubstackCategory) : '' ?>" class="floating-refresh-btn" title="Refresh all Substack feeds">Refresh</a>

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
            btn.textContent = !isExpanded ? '\u25B2 collapse all' : '\u25BC expand all';
        });
    })();
    </script>
</body>
</html>
