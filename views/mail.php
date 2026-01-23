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
            <a href="?action=index" class="nav-link">Seismo</a>
            <a href="?action=feeds" class="nav-link">Feeds</a>
            <a href="?action=mail" class="nav-link active">Mail</a>
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

        <div class="latest-entries-section">
            <h2 class="section-title">
                <?php if (!empty($lastMailRefreshDate)): ?>
                    Refreshed: <?= htmlspecialchars($lastMailRefreshDate) ?>
                <?php else: ?>
                    Refreshed: Never
                <?php endif; ?>
            </h2>

            <?php if (!empty($mailTableError)): ?>
                <div class="message message-error">
                    <strong>Error:</strong> <?= htmlspecialchars($mailTableError) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($emails)): ?>
                <?php foreach ($emails as $email): ?>
                    <?php
                        // Get date from various possible fields
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
                    ?>

                    <div class="entry-card">
                        <div class="entry-header">
                            <span class="entry-feed"><?= htmlspecialchars($fromDisplay) ?></span>
                            <span class="entry-date"><?= htmlspecialchars($createdAt) ?></span>
                        </div>
                        <h3 class="entry-title"><?= htmlspecialchars($subject) ?></h3>
                        <div class="entry-content"><?= htmlspecialchars($bodyPreview) ?></div>
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
</body>
</html>
