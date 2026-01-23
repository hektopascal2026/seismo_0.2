<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Seismo</title>
    <link rel="stylesheet" href="<?= getBasePath() ?>/assets/css/style.css">
    <style>
        .settings-section {
            margin-bottom: 60px;
            padding-bottom: 40px;
            border-bottom: 2px solid #000000;
        }
        
        .settings-section:last-child {
            border-bottom: none;
        }
        
        .settings-section h2 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 30px;
            color: #000000;
        }
        
        .settings-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .settings-item {
            border: 1px solid #000000;
            padding: 20px;
            background-color: #ffffff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .settings-item-info {
            flex: 1;
            min-width: 200px;
        }
        
        .settings-item-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
            color: #000000;
        }
        
        .settings-item-meta {
            font-size: 14px;
            color: #666666;
            margin-bottom: 4px;
        }
        
        .settings-item-tag {
            display: inline-block;
            padding: 4px 12px;
            background-color: #f5f5f5;
            border: 1px solid #000000;
            font-size: 13px;
            font-weight: 600;
            margin-top: 8px;
        }
        
        .settings-item-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .tag-input-wrapper {
            position: relative;
            display: inline-flex;
            align-items: center;
        }
        
        .tag-input {
            padding: 6px 12px;
            border: 2px solid #000000;
            background-color: #ffffff;
            color: #000000;
            font-size: 14px;
            font-family: inherit;
            font-weight: 500;
            width: 150px;
            transition: all 0.3s ease;
        }
        
        .tag-input:focus {
            outline: none;
            background-color: #fafafa;
            box-shadow: 0 0 0 2px rgba(0, 0, 0, 0.1);
        }
        
        .tag-input.tag-saving {
            border-color: #666666;
            background-color: #f5f5f5;
        }
        
        .tag-input.tag-saved {
            border-color: #00aa00;
            background-color: #f0fff0;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid #000000;
        }
        
        .status-badge.active {
            background-color: #00aa00;
            color: #ffffff;
        }
        
        .status-badge.inactive {
            background-color: #ffffff;
            color: #000000;
        }
    </style>
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
            <a href="?action=feeds" class="nav-link">Feeds</a>
            <a href="?action=mail" class="nav-link">Mail</a>
            <a href="?action=settings" class="nav-link active">Settings</a>
        </nav>

        <header>
            <h1>Settings</h1>
            <p class="subtitle">Manage sources and tags</p>
        </header>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="message message-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="message message-error"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- RSS Section -->
        <section class="settings-section">
            <h2>RSS</h2>
            <p>Manage your RSS feeds. Deactivate or delete feeds you no longer need.</p>
            
            <?php if (empty($allFeeds)): ?>
                <div class="empty-state">
                    <p>No feeds added yet.</p>
                </div>
            <?php else: ?>
                <div class="settings-list">
                    <?php foreach ($allFeeds as $feed): ?>
                        <div class="settings-item">
                            <div class="settings-item-info">
                                <div class="settings-item-title"><?= htmlspecialchars($feed['title']) ?></div>
                                <div class="settings-item-meta"><?= htmlspecialchars($feed['url']) ?></div>
                                <?php if ($feed['category']): ?>
                                    <span class="settings-item-tag"><?= htmlspecialchars($feed['category']) ?></span>
                                <?php endif; ?>
                                <?php if ($feed['last_fetched']): ?>
                                    <div class="settings-item-meta">Last updated: <?= date('d.m.Y H:i', strtotime($feed['last_fetched'])) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="settings-item-actions">
                                <span class="status-badge <?= $feed['disabled'] ? 'inactive' : 'active' ?>">
                                    <?= $feed['disabled'] ? 'Inactive' : 'Active' ?>
                                </span>
                                <a href="?action=toggle_feed&id=<?= $feed['id'] ?>&from=settings" class="btn btn-secondary" style="font-size: 14px; padding: 8px 16px;">
                                    <?= $feed['disabled'] ? 'Activate' : 'Deactivate' ?>
                                </a>
                                <a href="?action=delete_feed&id=<?= $feed['id'] ?>&from=settings" 
                                   class="btn btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this feed? This action cannot be undone.');"
                                   style="font-size: 14px; padding: 8px 16px;">
                                    Delete
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Mail Section -->
        <section class="settings-section">
            <h2>Mail</h2>
            <p>Manage tags for email senders. Edit tags to organize your emails.</p>
            
            <?php if (empty($senderTags)): ?>
                <div class="empty-state">
                    <p>No email senders found yet.</p>
                </div>
            <?php else: ?>
                <div class="settings-list">
                    <?php foreach ($senderTags as $sender): ?>
                        <div class="settings-item">
                            <div class="settings-item-info">
                                <div class="settings-item-title">
                                    <?= !empty($sender['name']) ? htmlspecialchars($sender['name']) : 'Unknown' ?>
                                </div>
                                <div class="settings-item-meta"><?= htmlspecialchars($sender['email']) ?></div>
                                <?php if ($sender['tag']): ?>
                                    <span class="settings-item-tag"><?= htmlspecialchars($sender['tag']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="settings-item-actions">
                                <div class="tag-input-wrapper">
                                    <input 
                                        type="text" 
                                        class="tag-input" 
                                        value="<?= htmlspecialchars($sender['tag'] ?? '') ?>" 
                                        placeholder="Enter tag..."
                                        data-sender-email="<?= htmlspecialchars($sender['email']) ?>"
                                        data-original-tag="<?= htmlspecialchars($sender['tag'] ?? '') ?>"
                                    >
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <script>
        // Handle sender tag updates
        document.querySelectorAll('.tag-input').forEach(function(input) {
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const value = this.value.trim();
                    const senderEmail = this.dataset.senderEmail;
                    const originalTag = this.dataset.originalTag || '';
                    
                    // If unchanged, do nothing
                    if (value === originalTag) {
                        return;
                    }
                    
                    // Save tag
                    const formData = new FormData();
                    formData.append('from_email', senderEmail);
                    formData.append('tag', value);
                    
                    // Add saving state
                    this.classList.add('tag-saving');
                    
                    fetch('?action=update_sender_tag', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.classList.remove('tag-saving');
                            this.classList.add('tag-saved');
                            this.dataset.originalTag = value;
                            
                            // Remove saved state after animation
                            setTimeout(() => {
                                this.classList.remove('tag-saved');
                            }, 600);
                        } else {
                            this.classList.remove('tag-saving');
                            alert('Error: ' + (data.error || 'Failed to update tag'));
                            this.value = originalTag;
                        }
                    })
                    .catch(error => {
                        this.classList.remove('tag-saving');
                        alert('Error updating tag');
                        this.value = originalTag;
                    });
                }
            });
            
            input.addEventListener('blur', function() {
                // Reset to original if empty and user didn't save
                if (this.value.trim() === '' && this.dataset.originalTag) {
                    this.value = this.dataset.originalTag;
                }
            });
        });
    </script>
</body>
</html>
