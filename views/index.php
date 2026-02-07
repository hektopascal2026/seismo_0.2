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
                <svg class="logo-icon logo-icon-nav" width="48" height="32" viewBox="0 0 24 16" xmlns="http://www.w3.org/2000/svg">
                    <rect width="24" height="16" fill="#FFFFC5"/>
                    <path d="M0,8 L4,12 L6,4 L10,10 L14,2 L18,8 L20,6 L24,8" stroke="#000000" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Feed
            </a>
            <a href="?action=feeds" class="nav-link">RSS</a>
            <a href="?action=mail" class="nav-link">Mail</a>
            <a href="?action=settings" class="nav-link">Settings</a>
        </nav>

        <header>
            <h1>
                <svg class="logo-icon logo-icon-large" width="48" height="32" viewBox="0 0 24 16" xmlns="http://www.w3.org/2000/svg">
                    <rect width="24" height="16" fill="#FFFFC5"/>
                    <path d="M0,8 L4,12 L6,4 L10,10 L14,2 L18,8 L20,6 L24,8" stroke="#000000" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Seismo
            </h1>
            <p class="subtitle">ein Prototyp von hektopascal.org | v0.2.2</p>
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
            <form method="GET" class="search-form">
                <input type="hidden" name="action" value="index">
                <input type="hidden" name="tags_submitted" value="1">
                <input type="search" name="q" placeholder="Search..." class="search-input" value="<?= htmlspecialchars($searchQuery ?? '') ?>">
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if (!empty($searchQuery) || !empty($selectedTags) || !empty($selectedEmailTags)): ?>
                    <a href="?action=index" class="btn btn-secondary">Clear</a>
                <?php endif; ?>

                <?php if (!empty($tags)): ?>
                    <div class="tag-filter-section">
                        <div class="tag-filter-label" style="margin-bottom: 8px;">RSS Tags:</div>
                        <div class="tag-filter-list">
                            <?php foreach ($tags as $tag): ?>
                                <?php 
                                    $isSelected = !empty($selectedTags) && in_array($tag, $selectedTags, true);
                                ?>
                                <label class="tag-filter-pill<?= $isSelected ? ' tag-filter-pill-active' : '' ?>"<?= $isSelected ? ' style="background-color: #add8e6;"' : '' ?>>
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
                
                <?php if (!empty($emailTags)): ?>
                    <div class="tag-filter-section" style="margin-top: 16px;">
                        <div class="tag-filter-label" style="margin-bottom: 8px;">Email Tags:</div>
                        <div class="tag-filter-list">
                            <?php foreach ($emailTags as $tag): ?>
                                <?php 
                                    $isSelected = !empty($selectedEmailTags) && in_array($tag, $selectedEmailTags, true);
                                ?>
                                <label class="tag-filter-pill<?= $isSelected ? ' tag-filter-pill-active' : '' ?>"<?= $isSelected ? ' style="background-color: #FFDBBB;"' : '' ?>>
                                    <input 
                                        type="checkbox" 
                                        name="email_tags[]" 
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
                <div class="section-title-row">
                    <h2 class="section-title">
                        Search Results<?= $searchResultsCount !== null ? ' (' . $searchResultsCount . ')' : '' ?>
                        <span style="font-weight: 400; font-size: 18px; color: #666;">for "<?= htmlspecialchars($searchQuery) ?>"</span>
                    </h2>
                    <button class="btn btn-secondary entry-expand-all-btn" style="font-size: 14px; padding: 8px 16px;">&#9660; expand</button>
                </div>
            <?php else: ?>
                <div class="section-title-row">
                    <h2 class="section-title">
                        <?php if ($lastRefreshDate): ?>
                            Refreshed: <?= htmlspecialchars($lastRefreshDate) ?>
                        <?php else: ?>
                            Refreshed: Never
                        <?php endif; ?>
                    </h2>
                    <button class="btn btn-secondary entry-expand-all-btn" style="font-size: 14px; padding: 8px 16px;">&#9660; expand</button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($allItems)): ?>
                <?php foreach ($allItems as $itemWrapper): ?>
                    <?php if ($itemWrapper['type'] === 'feed'): ?>
                        <?php $item = $itemWrapper['data']; ?>
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
                                    <?php if (!empty($searchQuery)): ?>
                                        <?= highlightSearchTerm($item['title'], $searchQuery) ?>
                                    <?php else: ?>
                                        <?= htmlspecialchars($item['title']) ?>
                                    <?php endif; ?>
                                </a>
                            </h3>
                            <?php if ($item['description'] || $item['content']): ?>
                                <div class="entry-content entry-preview">
                                    <?php 
                                        if (!empty($searchQuery)) {
                                            echo highlightSearchTerm($contentPreview, $searchQuery);
                                        } else {
                                            echo htmlspecialchars($contentPreview);
                                        }
                                    ?>
                                </div>
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
                    <?php else: ?>
                        <?php $email = $itemWrapper['data']; ?>
                        <?php
                            $dateValue = $email['date_received'] ?? $email['date_utc'] ?? $email['created_at'] ?? $email['date_sent'] ?? null;
                            $createdAt = $dateValue ? date('d.m.Y H:i', strtotime($dateValue)) : '';
                            
                            $fromName = trim((string)($email['from_name'] ?? ''));
                            $fromEmail = trim((string)($email['from_email'] ?? ''));
                            $fromDisplay = $fromName !== '' ? $fromName : ($fromEmail !== '' ? $fromEmail : 'Unknown sender');

                            $subject = trim((string)($email['subject'] ?? ''));
                            if ($subject === '') $subject = '(No subject)';

                            $body = (string)($email['text_body'] ?? '');
                            if ($body === '') {
                                $body = strip_tags((string)($email['html_body'] ?? ''));
                            }
                            $body = trim(preg_replace('/\s+/', ' ', $body ?? ''));
                            $bodyPreview = mb_substr($body, 0, 200);
                            if (mb_strlen($body) > 200) $bodyPreview .= '...';
                            $hasMore = mb_strlen($body) > 200;
                        ?>
                        <div class="entry-card">
                            <div class="entry-header">
                                <span class="entry-feed"><?= htmlspecialchars($fromDisplay) ?></span>
                                <?php if ($createdAt): ?>
                                    <span class="entry-date"><?= htmlspecialchars($createdAt) ?></span>
                                <?php endif; ?>
                            </div>
                            <h3 class="entry-title">
                                <?php if (!empty($searchQuery)): ?>
                                    <?= highlightSearchTerm($subject, $searchQuery) ?>
                                <?php else: ?>
                                    <?= htmlspecialchars($subject) ?>
                                <?php endif; ?>
                            </h3>
                            <div class="entry-content entry-preview">
                                <?php 
                                    if (!empty($searchQuery)) {
                                        echo highlightSearchTerm($bodyPreview, $searchQuery);
                                    } else {
                                        echo htmlspecialchars($bodyPreview);
                                    }
                                ?>
                            </div>
                            <div class="entry-full-content" style="display:none"><?= htmlspecialchars($body) ?></div>
                            <?php if ($hasMore): ?>
                                <div class="entry-actions">
                                    <button class="btn btn-secondary entry-expand-btn" style="font-size: 14px; padding: 8px 16px;">&#9660; expand</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
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
