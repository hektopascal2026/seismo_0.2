<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Seismo</title>
    <link rel="stylesheet" href="<?= getBasePath() ?>/assets/css/style.css">
    <style>
        .settings-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #000000;
        }
        
        .settings-section:last-child {
            border-bottom: none;
        }
        
        .settings-section h2 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 16px;
            color: #000000;
        }
        
        .settings-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .settings-item {
            border: 1px solid #000000;
            padding: 12px 16px;
            background-color: #ffffff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .settings-item-info {
            flex: 1;
            min-width: 200px;
        }
        
        .settings-item-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 4px;
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
                Feed
            </a>
            <a href="?action=feeds" class="nav-link">RSS</a>
            <a href="?action=mail" class="nav-link">Mail</a>
            <a href="?action=settings" class="nav-link active">Settings</a>
        </nav>

        <header>
            <h1>Settings</h1>
            <p class="subtitle">Manage sources and tags</p>
        </header>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="message message-success"><?= nl2br(htmlspecialchars($_SESSION['success'])) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="message message-error"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- RSS Section -->
        <section class="settings-section">
            <h2>RSS</h2>
            
            <!-- Add Feed Section -->
            <div class="add-feed-section" style="margin-bottom: 16px;">
                <form method="POST" action="?action=add_feed" class="add-feed-form">
                    <input type="url" name="url" placeholder="Enter RSS feed URL (e.g., https://example.com/feed.xml)" required class="feed-input">
                    <button type="submit" class="btn btn-primary">Add Feed</button>
                </form>
            </div>
            
            <!-- All Tags Section -->
            <?php if (!empty($allTags)): ?>
                <div style="margin-bottom: 16px; padding: 12px; background-color: #ffffff;">
                    <h3 style="font-size: 20px; font-weight: 600; margin-bottom: 8px; color: #000000;">All Tags</h3>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                        <?php foreach ($allTags as $tag): ?>
                            <div class="feed-tag-input-wrapper" style="display: inline-flex;">
                                <input 
                                    type="text" 
                                    class="feed-tag-input all-tag-input" 
                                    value="<?= htmlspecialchars($tag) ?>" 
                                    data-original-tag="<?= htmlspecialchars($tag) ?>"
                                    data-tag-name="<?= htmlspecialchars($tag, ENT_QUOTES) ?>"
                                    style="width: auto; min-width: 100px; padding: 6px 12px;"
                                >
                                <span class="feed-tag-indicator"></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
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
                                <?php if (!empty($feed['description'])): ?>
                                    <div class="settings-item-meta"><?= htmlspecialchars($feed['description']) ?></div>
                                <?php endif; ?>
                                <div class="settings-item-meta"><?= htmlspecialchars($feed['url']) ?></div>
                                <?php if ($feed['last_fetched']): ?>
                                    <div class="settings-item-meta">Last updated: <?= date('d.m.Y H:i', strtotime($feed['last_fetched'])) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="settings-item-actions" style="flex-direction: column; align-items: flex-end; gap: 10px;">
                                <div style="display: flex; gap: 10px;">
                                    <a href="?action=toggle_feed&id=<?= $feed['id'] ?>&from=settings" class="btn <?= $feed['disabled'] ? 'btn-success' : 'btn-warning' ?>" style="font-size: 14px; padding: 8px 16px;">
                                        <?= $feed['disabled'] ? 'Enable' : 'Disable' ?>
                                    </a>
                                    <a href="?action=delete_feed&id=<?= $feed['id'] ?>&from=settings" 
                                       class="btn btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this feed? This action cannot be undone.');"
                                       style="font-size: 14px; padding: 8px 16px;">
                                        Delete
                                    </a>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <label style="font-weight: 600; font-size: 14px;">Tag:</label>
                                    <div class="tag-input-wrapper">
                                        <input 
                                            type="text" 
                                            class="tag-input feed-tag-input" 
                                            value="<?= htmlspecialchars($feed['category'] ?? 'unsortiert') ?>" 
                                            data-feed-id="<?= $feed['id'] ?>"
                                            data-original-tag="<?= htmlspecialchars($feed['category'] ?? 'unsortiert') ?>"
                                            style="width: 150px;"
                                        >
                                        <span class="feed-tag-indicator"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Mail Section -->
        <section class="settings-section">
            <h2>Mail</h2>
            
            <!-- All Email Tags Section -->
            <?php if (!empty($allEmailTags)): ?>
                <div style="margin-bottom: 16px; padding: 12px; background-color: #ffffff;">
                    <h3 style="font-size: 20px; font-weight: 600; margin-bottom: 8px; color: #000000;">All Tags</h3>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                        <?php foreach ($allEmailTags as $tag): ?>
                            <div class="feed-tag-input-wrapper" style="display: inline-flex;">
                                <input 
                                    type="text" 
                                    class="feed-tag-input all-email-tag-input" 
                                    value="<?= htmlspecialchars($tag) ?>" 
                                    data-original-tag="<?= htmlspecialchars($tag) ?>"
                                    data-tag-name="<?= htmlspecialchars($tag, ENT_QUOTES) ?>"
                                    style="width: auto; min-width: 100px; padding: 6px 12px;"
                                >
                                <span class="feed-tag-indicator"></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
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
                            </div>
                            <div class="settings-item-actions" style="flex-direction: column; align-items: flex-end; gap: 10px;">
                                <div style="display: flex; gap: 10px;">
                                    <form method="POST" action="?action=toggle_sender" style="margin: 0;">
                                        <input type="hidden" name="email" value="<?= htmlspecialchars($sender['email']) ?>">
                                        <button type="submit" class="btn <?= $sender['disabled'] ? 'btn-success' : 'btn-warning' ?>" style="font-size: 14px; padding: 8px 16px;">
                                            <?= $sender['disabled'] ? 'Enable' : 'Disable' ?>
                                        </button>
                                    </form>
                                    <form method="POST" action="?action=delete_sender" style="margin: 0;">
                                        <input type="hidden" name="email" value="<?= htmlspecialchars($sender['email']) ?>">
                                        <button type="submit" class="btn btn-danger" style="font-size: 14px; padding: 8px 16px;">
                                            Remove
                                        </button>
                                    </form>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <label style="font-weight: 600; font-size: 14px;">Tag:</label>
                                    <div class="tag-input-wrapper">
                                        <input 
                                            type="text" 
                                            class="tag-input" 
                                            value="<?= htmlspecialchars($sender['tag'] ?? '') ?>" 
                                            placeholder="Enter tag..."
                                            data-sender-email="<?= htmlspecialchars($sender['email']) ?>"
                                            data-original-tag="<?= htmlspecialchars($sender['tag'] ?? '') ?>"
                                            style="width: 150px;"
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <script>
        // Feed tag management (same as feeds.php)
        (function() {
            let allTags = [];
            let allEmailTags = [];
            let currentSuggestions = [];
            let activeInput = null;
            let suggestionList = null;
            
            // Load all tags on page load
            fetch('?action=api_tags')
                .then(response => response.json())
                .then(tags => {
                    allTags = tags;
                })
                .catch(err => console.error('Error loading tags:', err));
            
            // Load all email tags on page load
            fetch('?action=api_email_tags')
                .then(response => response.json())
                .then(tags => {
                    allEmailTags = tags;
                })
                .catch(err => console.error('Error loading email tags:', err));
            
            // Create suggestion dropdown
            function createSuggestionList() {
                const list = document.createElement('ul');
                list.className = 'feed-tag-suggestions';
                list.style.display = 'none';
                document.body.appendChild(list);
                return list;
            }
            
            suggestionList = createSuggestionList();
            
            // Show suggestions
            function showSuggestions(input, suggestions) {
                if (!suggestions.length) {
                    suggestionList.style.display = 'none';
                    return;
                }
                
                suggestionList.innerHTML = '';
                suggestions.forEach(tag => {
                    const li = document.createElement('li');
                    li.textContent = tag;
                    li.addEventListener('click', () => {
                        input.value = tag;
                        input.dispatchEvent(new Event('input'));
                        hideSuggestions();
                    });
                    suggestionList.appendChild(li);
                });
                
                const rect = input.getBoundingClientRect();
                suggestionList.style.top = (rect.bottom + window.scrollY) + 'px';
                suggestionList.style.left = (rect.left + window.scrollX) + 'px';
                suggestionList.style.width = rect.width + 'px';
                suggestionList.style.display = 'block';
            }
            
            function hideSuggestions() {
                suggestionList.style.display = 'none';
            }
            
            // Filter tags based on input
            function filterTags(query, useEmailTags = false) {
                const tagsToSearch = useEmailTags ? allEmailTags : allTags;
                if (!query || query === 'unsortiert') {
                    return [];
                }
                const lowerQuery = query.toLowerCase();
                return tagsToSearch.filter(tag => 
                    tag.toLowerCase().includes(lowerQuery) && tag !== query
                ).slice(0, 5);
            }
            
            // Check if tag is new
            function isNewTag(tag, useEmailTags = false) {
                const tagsToSearch = useEmailTags ? allEmailTags : allTags;
                return tag && tag !== 'unsortiert' && !tagsToSearch.includes(tag);
            }
            
            // Update indicator
            function updateIndicator(input, value, useEmailTags = false) {
                const indicator = input.parentElement.querySelector('.feed-tag-indicator');
                if (indicator) {
                    if (isNewTag(value, useEmailTags)) {
                        indicator.textContent = 'new';
                        indicator.className = 'feed-tag-indicator feed-tag-new';
                    } else {
                        indicator.textContent = '';
                        indicator.className = 'feed-tag-indicator';
                    }
                }
            }
            
            // Handle feed tag inputs (exclude all-tag-input and all-email-tag-input which have their own handlers)
            document.querySelectorAll('.feed-tag-input:not(.all-tag-input):not(.all-email-tag-input)').forEach(input => {
                input.addEventListener('focus', function() {
                    activeInput = this;
                    const value = this.value.trim();
                    const useEmailTags = this.classList.contains('all-email-tag-input');
                    if (value && value !== 'unsortiert') {
                        const suggestions = filterTags(value, useEmailTags);
                        showSuggestions(this, suggestions);
                    }
                    updateIndicator(this, value, useEmailTags);
                });
                
                input.addEventListener('input', function() {
                    const value = this.value.trim();
                    const useEmailTags = this.classList.contains('all-email-tag-input');
                    updateIndicator(this, value, useEmailTags);
                    
                    if (value && value !== 'unsortiert') {
                        const suggestions = filterTags(value, useEmailTags);
                        showSuggestions(this, suggestions);
                    } else {
                        hideSuggestions();
                    }
                });
                
                input.addEventListener('blur', function() {
                    setTimeout(() => hideSuggestions(), 200);
                });
                
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const value = this.value.trim();
                        
                        if (!value || value === '') {
                            this.value = this.dataset.originalTag || 'unsortiert';
                            updateIndicator(this, this.value);
                            hideSuggestions();
                            return;
                        }
                        
                        const feedId = this.dataset.feedId;
                        const formData = new FormData();
                        formData.append('feed_id', feedId);
                        formData.append('tag', value);
                        
                        this.classList.add('feed-tag-saving');
                        
                        fetch('?action=update_feed_tag', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                this.dataset.originalTag = value;
                                this.classList.remove('feed-tag-saving');
                                this.classList.add('feed-tag-saved');
                                setTimeout(() => {
                                    this.classList.remove('feed-tag-saved');
                                }, 2000);
                                this.blur();
                                hideSuggestions();
                                return fetch('?action=api_tags');
                            } else {
                                this.classList.remove('feed-tag-saving');
                                alert('Error: ' + (data.error || 'Failed to update tag'));
                            }
                        })
                        .then(response => response ? response.json() : null)
                        .then(tags => {
                            if (tags) {
                                allTags = tags;
                            }
                        })
                        .catch(err => {
                            console.error('Error updating tag:', err);
                            this.classList.remove('feed-tag-saving');
                            alert('Error updating tag');
                        });
                    } else if (e.key === 'Escape') {
                        this.value = this.dataset.originalTag || 'unsortiert';
                        updateIndicator(this, this.value);
                        hideSuggestions();
                        this.blur();
                    }
                });
            });
            
            // Close suggestions when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.feed-tag-input-wrapper') && !e.target.closest('.feed-tag-suggestions')) {
                    hideSuggestions();
                }
            });
        })();
        
        // Handle "All Tags" editable inputs (both RSS and Email tags)
        document.querySelectorAll('.all-tag-input, .all-email-tag-input').forEach(input => {
            input.addEventListener('focus', function() {
                activeInput = this;
                const value = this.value.trim();
                const useEmailTags = this.classList.contains('all-email-tag-input');
                if (value && value !== 'unsortiert') {
                    const suggestions = filterTags(value, useEmailTags);
                    showSuggestions(this, suggestions);
                }
                updateIndicator(this, value, useEmailTags);
            });
            
            input.addEventListener('input', function() {
                const value = this.value.trim();
                const useEmailTags = this.classList.contains('all-email-tag-input');
                updateIndicator(this, value, useEmailTags);
                
                if (value && value !== 'unsortiert') {
                    const suggestions = filterTags(value, useEmailTags);
                    showSuggestions(this, suggestions);
                } else {
                    hideSuggestions();
                }
            });
            
            input.addEventListener('blur', function() {
                setTimeout(() => hideSuggestions(), 200);
            });
            
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const value = this.value.trim();
                    const oldTag = this.dataset.tagName;
                    
                    // Validation: cannot be empty
                    if (!value || value === '') {
                        this.value = this.dataset.originalTag;
                        const useEmailTags = this.classList.contains('all-email-tag-input');
                        updateIndicator(this, this.value, useEmailTags);
                        hideSuggestions();
                        return;
                    }
                    
                    // If unchanged, do nothing
                    if (value === oldTag) {
                        this.blur();
                        hideSuggestions();
                        return;
                    }
                    
                    // Determine if this is an email tag or RSS tag
                    const isEmailTag = this.classList.contains('all-email-tag-input');
                    const action = isEmailTag ? 'rename_email_tag' : 'rename_tag';
                    const apiAction = isEmailTag ? 'api_email_tags' : 'api_tags';
                    
                    // Rename tag
                    const formData = new FormData();
                    formData.append('old_tag', oldTag);
                    formData.append('new_tag', value);
                    
                    this.classList.add('feed-tag-saving');
                    
                    fetch('?action=' + action, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.dataset.originalTag = value;
                            this.dataset.tagName = value;
                            
                            this.classList.remove('feed-tag-saving');
                            this.classList.add('feed-tag-saved');
                            
                            setTimeout(() => {
                                this.classList.remove('feed-tag-saved');
                            }, 2000);
                            
                            this.blur();
                            hideSuggestions();
                            
                            // Reload tags list
                            return fetch('?action=' + apiAction);
                        } else {
                            this.classList.remove('feed-tag-saving');
                            alert('Error: ' + (data.error || 'Failed to rename tag'));
                            this.value = this.dataset.originalTag;
                            const useEmailTags = this.classList.contains('all-email-tag-input');
                            updateIndicator(this, this.value, useEmailTags);
                        }
                    })
                    .then(response => response ? response.json() : null)
                    .then(tags => {
                        if (tags) {
                            if (isEmailTag) {
                                allEmailTags = tags;
                            } else {
                                allTags = tags;
                            }
                        }
                    })
                    .catch(err => {
                        console.error('Error renaming tag:', err);
                        this.classList.remove('feed-tag-saving');
                        alert('Error renaming tag');
                        this.value = this.dataset.originalTag;
                        const useEmailTags = this.classList.contains('all-email-tag-input');
                        updateIndicator(this, this.value, useEmailTags);
                    });
                } else if (e.key === 'Escape') {
                    this.value = this.dataset.originalTag;
                    const useEmailTags = this.classList.contains('all-email-tag-input');
                    updateIndicator(this, this.value, useEmailTags);
                    hideSuggestions();
                    this.blur();
                }
            });
        });
        
        // Handle sender tag updates
        document.querySelectorAll('.tag-input:not(.feed-tag-input)').forEach(function(input) {
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
