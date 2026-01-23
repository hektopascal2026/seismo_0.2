<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feeds - Seismo</title>
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
            <a href="?action=settings" class="nav-link">Settings</a>
        </nav>

        <header>
            <h1>Feeds</h1>
            <p class="subtitle">RSS management</p>
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

        <hr class="section-divider">

        <?php if (!empty($categories) || isset($selectedCategory)): ?>
        <div class="category-filter-section">
            <div class="category-filter">
                <a href="?action=feeds" class="category-btn <?= !$selectedCategory ? 'active' : '' ?>">All Feeds</a>
                <?php foreach ($categories as $category): ?>
                    <a href="?action=feeds&category=<?= urlencode($category) ?>" class="category-btn <?= $selectedCategory === $category ? 'active' : '' ?>">
                        <?= htmlspecialchars($category) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="feeds-grid">
            <?php if (empty($feeds)): ?>
                <div class="empty-state">
                    <?php if ($selectedCategory): ?>
                        <p>No feeds found in category "<?= htmlspecialchars($selectedCategory) ?>". <a href="?action=feeds">View all feeds</a></p>
                    <?php else: ?>
                        <p>No feeds added yet. Add your first RSS feed above.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($feeds as $feed): ?>
                    <div class="feed-card">
                        <h3 class="feed-title">
                            <a href="?action=view_feed&id=<?= $feed['id'] ?>">
                                <?= htmlspecialchars($feed['title']) ?>
                            </a>
                        </h3>
                        <?php if ($feed['description']): ?>
                            <p class="feed-description"><?= htmlspecialchars($feed['description']) ?></p>
                        <?php endif; ?>
                        <div class="feed-tag-section">
                            <label class="feed-tag-label">Tag:</label>
                            <div class="feed-tag-input-wrapper">
                                <input 
                                    type="text" 
                                    class="feed-tag-input" 
                                    value="<?= htmlspecialchars($feed['category'] ?? 'unsortiert') ?>" 
                                    data-feed-id="<?= $feed['id'] ?>"
                                    data-original-tag="<?= htmlspecialchars($feed['category'] ?? 'unsortiert') ?>"
                                >
                                <span class="feed-tag-indicator"></span>
                            </div>
                        </div>
                        <div class="feed-meta">
                            <span class="feed-url"><?= htmlspecialchars($feed['url']) ?></span>
                            <?php if ($feed['last_fetched']): ?>
                                <span class="feed-updated">Updated: <?= date('M j, Y g:i A', strtotime($feed['last_fetched'])) ?></span>
                            <?php else: ?>
                                <span class="feed-updated">Never updated</span>
                            <?php endif; ?>
                        </div>
                        <div class="feed-actions">
                            <a href="?action=view_feed&id=<?= $feed['id'] ?>" class="btn btn-secondary">View</a>
                            <a href="?action=toggle_feed&id=<?= $feed['id'] ?>" class="btn <?= $feed['disabled'] ? 'btn-success' : 'btn-warning' ?>">
                                <?= $feed['disabled'] ? 'Enable' : 'Disable' ?>
                            </a>
                            <a href="?action=delete_feed&id=<?= $feed['id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this feed?')">Delete</a>
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
            let allTags = [];
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
            function filterTags(query) {
                if (!query || query === 'unsortiert') {
                    return [];
                }
                const lowerQuery = query.toLowerCase();
                return allTags.filter(tag => 
                    tag.toLowerCase().includes(lowerQuery) && tag !== query
                ).slice(0, 5);
            }
            
            // Check if tag is new
            function isNewTag(tag) {
                return tag && tag !== 'unsortiert' && !allTags.includes(tag);
            }
            
            // Update indicator
            function updateIndicator(input, value) {
                const indicator = input.parentElement.querySelector('.feed-tag-indicator');
                if (isNewTag(value)) {
                    indicator.textContent = 'new';
                    indicator.className = 'feed-tag-indicator feed-tag-new';
                } else {
                    indicator.textContent = '';
                    indicator.className = 'feed-tag-indicator';
                }
            }
            
            // Handle input events
            document.querySelectorAll('.feed-tag-input').forEach(input => {
                input.addEventListener('focus', function() {
                    activeInput = this;
                    const value = this.value.trim();
                    if (value && value !== 'unsortiert') {
                        const suggestions = filterTags(value);
                        showSuggestions(this, suggestions);
                    }
                    updateIndicator(this, value);
                });
                
                input.addEventListener('input', function() {
                    const value = this.value.trim();
                    updateIndicator(this, value);
                    
                    // Show suggestions if user has typed something (even if they deleted "unsortiert")
                    if (value && value !== 'unsortiert') {
                        const suggestions = filterTags(value);
                        showSuggestions(this, suggestions);
                    } else if (this.value.length > 0 && this.value.trim() === '') {
                        // User is typing but hasn't entered anything yet - hide suggestions
                        hideSuggestions();
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
                        
                        // Validation: cannot be empty or just "unsortiert" deleted
                        if (!value || value === '') {
                            this.value = this.dataset.originalTag || 'unsortiert';
                            updateIndicator(this, this.value);
                            hideSuggestions();
                            return;
                        }
                        
                        // Save tag
                        const feedId = this.dataset.feedId;
                        const formData = new FormData();
                        formData.append('feed_id', feedId);
                        formData.append('tag', value);
                        
                        // Add saving state
                        this.classList.add('feed-tag-saving');
                        
                        fetch('?action=update_feed_tag', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                this.dataset.originalTag = value;
                                
                                // Visual feedback: show saved state
                                this.classList.remove('feed-tag-saving');
                                this.classList.add('feed-tag-saved');
                                
                                setTimeout(() => {
                                    this.classList.remove('feed-tag-saved');
                                }, 2000);
                                
                                this.blur();
                                hideSuggestions();
                                
                                // Reload tags list
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
    </script>
</body>
</html>
