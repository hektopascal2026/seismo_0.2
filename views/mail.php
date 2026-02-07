<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mail - Seismo</title>
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
            <a href="?action=mail" class="nav-link active">Mail</a>
            <a href="?action=settings" class="nav-link">Settings</a>
        </nav>

        <header>
            <h1>Mail</h1>
            <p class="subtitle">Mail management</p>
        </header>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="message message-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="message message-error"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (!empty($emailTags) || isset($selectedEmailTag)): ?>
        <div class="category-filter-section">
            <div class="category-filter">
                <a href="?action=mail"
                   class="category-btn <?= !$selectedEmailTag ? 'active' : '' ?>"
                   <?= !$selectedEmailTag ? 'style="background-color: #FFDBBB;"' : '' ?>>
                    All Emails
                </a>
                <?php foreach ($emailTags as $tag): ?>
                    <a href="?action=mail&email_tag=<?= urlencode($tag) ?>"
                       class="category-btn <?= $selectedEmailTag === $tag ? 'active' : '' ?>"
                       <?= $selectedEmailTag === $tag ? 'style="background-color: #FFDBBB;"' : '' ?>>
                        <?= htmlspecialchars($tag) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="latest-entries-section">
            <div class="section-title-row">
                <h2 class="section-title">
                    <?php if (!empty($lastMailRefreshDate)): ?>
                        Refreshed: <?= htmlspecialchars($lastMailRefreshDate) ?>
                    <?php else: ?>
                        Refreshed: Never
                    <?php endif; ?>
                </h2>
                <button class="btn btn-secondary entry-expand-all-btn" style="font-size: 14px; padding: 8px 16px;">ausklappen</button>
            </div>

            <?php if (!empty($mailTableError)): ?>
                <div class="message message-error">
                    <strong>Error:</strong> <?= htmlspecialchars($mailTableError) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($emails)): ?>
                <?php foreach ($emails as $email): ?>
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
                        $bodyPreview = mb_substr($body, 0, 400);
                        if (mb_strlen($body) > 400) $bodyPreview .= '...';
                        $hasMore = mb_strlen($body) > 400;
                    ?>

                    <div class="entry-card">
                        <div class="entry-header">
                            <span class="entry-feed"><?= htmlspecialchars($fromDisplay) ?></span>
                            <span class="entry-date"><?= htmlspecialchars($createdAt) ?></span>
                        </div>
                        <h3 class="entry-title"><?= htmlspecialchars($subject) ?></h3>
                        <div class="entry-content entry-preview"><?= htmlspecialchars($bodyPreview) ?></div>
                        <div class="entry-full-content"><?= htmlspecialchars($body) ?></div>
                        <div class="entry-actions">
                            <?php if ($hasMore): ?>
                                <button class="btn btn-secondary entry-expand-btn" style="font-size: 14px; padding: 8px 16px;">ausklappen</button>
                            <?php endif; ?>
                            <?php if (isset($email['id'])): ?>
                                <a href="?action=delete_email&id=<?= (int)$email['id'] ?>&confirm=yes" 
                                   class="btn btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this email? This action cannot be undone.');"
                                   style="font-size: 14px; padding: 8px 16px;">
                                    Delete Email
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>No emails yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Floating Refresh Button -->
    <a href="?action=refresh_emails&from=mail" class="floating-refresh-btn" title="Refresh emails">Refresh</a>

    <script>
    (function() {
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.entry-expand-btn');
            if (!btn) return;
            var card = btn.closest('.entry-card');
            var preview = card.querySelector('.entry-preview');
            var full = card.querySelector('.entry-full-content');
            if (!preview || !full) return;
            
            var isExpanded = full.classList.contains('expanded');
            if (isExpanded) {
                full.classList.remove('expanded');
                preview.style.display = '';
                btn.textContent = 'ausklappen';
            } else {
                full.classList.add('expanded');
                preview.style.display = 'none';
                btn.textContent = 'einklappen';
            }
        });

        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.entry-expand-all-btn');
            if (!btn) return;
            
            var expanding = btn.textContent.trim() === 'ausklappen';
            var cards = document.querySelectorAll('.entry-card');
            cards.forEach(function(card) {
                var preview = card.querySelector('.entry-preview');
                var full = card.querySelector('.entry-full-content');
                var entryBtn = card.querySelector('.entry-expand-btn');
                if (!preview || !full) return;
                
                if (expanding) {
                    full.classList.add('expanded');
                    preview.style.display = 'none';
                    if (entryBtn) entryBtn.textContent = 'einklappen';
                } else {
                    full.classList.remove('expanded');
                    preview.style.display = '';
                    if (entryBtn) entryBtn.textContent = 'ausklappen';
                }
            });
            btn.textContent = expanding ? 'einklappen' : 'ausklappen';
        });
    })();
    </script>
</body>
</html>
