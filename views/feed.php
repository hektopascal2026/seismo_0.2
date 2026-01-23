<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($feed['title']) ?> - Seismo</title>
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
                Seismo
            </a>
            <a href="?action=feeds" class="nav-link active">Feeds</a>
            <a href="?action=mail" class="nav-link">Mail</a>
        </nav>

        <header>
            <h1><?= htmlspecialchars($feed['title']) ?></h1>
        </header>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="message message-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="feed-header">
            <div class="feed-info">
                <?php if ($feed['description']): ?>
                    <p class="feed-description"><?= htmlspecialchars($feed['description']) ?></p>
                <?php endif; ?>
                <p class="feed-meta-small">
                    <a href="<?= htmlspecialchars($feed['link'] ?: $feed['url']) ?>" target="_blank" class="feed-link"><?= htmlspecialchars($feed['url']) ?></a>
                    <?php if ($feed['last_fetched']): ?>
                        | Last updated: <?= date('M j, Y g:i A', strtotime($feed['last_fetched'])) ?>
                    <?php endif; ?>
                </p>
            </div>
            <div class="feed-actions-top">
                <a href="?action=refresh_feed&id=<?= $feed['id'] ?>" class="btn btn-primary">Refresh Feed</a>
                <a href="?action=feeds" class="btn btn-secondary">Back to Feeds</a>
            </div>
        </div>

        <?php if ($needsRefresh): ?>
            <div class="message message-info">
                This feed may be outdated. Click "Refresh Feed" to update.
            </div>
        <?php endif; ?>

        <div class="items-list">
            <?php if (empty($items)): ?>
                <div class="empty-state">
                    <p>No items found in this feed. Try refreshing the feed.</p>
                </div>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                    <article class="item-card">
                        <h3 class="item-title">
                            <a href="<?= htmlspecialchars($item['link']) ?>" target="_blank" rel="noopener">
                                <?= htmlspecialchars($item['title']) ?>
                            </a>
                        </h3>
                        <div class="item-meta">
                            <?php if ($item['published_date']): ?>
                                <span class="item-date"><?= date('M j, Y g:i A', strtotime($item['published_date'])) ?></span>
                            <?php endif; ?>
                            <?php if ($item['author']): ?>
                                <span class="item-author">by <?= htmlspecialchars($item['author']) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($item['description'] || $item['content']): ?>
                            <div class="item-content">
                                <?= $item['content'] ?: strip_tags($item['description'], '<p><a><strong><em><br>') ?>
                            </div>
                        <?php endif; ?>
                        <a href="<?= htmlspecialchars($item['link']) ?>" target="_blank" rel="noopener" class="item-link">Read more →</a>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Floating Refresh Button -->
    <a href="?action=refresh_all_feeds&from=view_feed&id=<?= $feed['id'] ?>" class="floating-refresh-btn" title="Refresh all feeds">Refresh</a>
</body>
</html>
