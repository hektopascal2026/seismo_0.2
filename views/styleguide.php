<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Style Guide - Seismo</title>
    <link rel="stylesheet" href="<?= getBasePath() ?>/assets/css/style.css">
    <style>
        .styleguide-section {
            margin-bottom: 60px;
            padding-bottom: 40px;
            border-bottom: 2px solid #000000;
        }
        
        .styleguide-section:last-child {
            border-bottom: none;
        }
        
        .styleguide-section h2 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 30px;
        }
        
        .styleguide-section h3 {
            font-size: 20px;
            font-weight: 600;
            margin-top: 30px;
            margin-bottom: 15px;
        }
        
        .color-swatch {
            display: inline-block;
            width: 120px;
            height: 120px;
            border: 2px solid #000000;
            margin-right: 20px;
            margin-bottom: 20px;
            vertical-align: top;
            position: relative;
        }
        
        .color-swatch-info {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: rgba(255, 255, 255, 0.95);
            padding: 8px;
            font-size: 12px;
            font-weight: 600;
            border-top: 2px solid #000000;
        }
        
        .logo-showcase {
            display: flex;
            gap: 40px;
            align-items: center;
            margin: 30px 0;
            flex-wrap: wrap;
        }
        
        .logo-variant {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }
        
        .logo-variant-label {
            font-size: 14px;
            font-weight: 600;
            color: #666666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .component-demo {
            border: 1px solid #cccccc;
            padding: 20px;
            margin: 20px 0;
            background-color: #ffffff;
        }
        
        .code-block {
            background-color: #f5f5f5;
            border: 1px solid #000000;
            padding: 15px;
            margin: 15px 0;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            overflow-x: auto;
        }
        
        .typography-sample {
            margin: 20px 0;
            padding: 15px;
            border-left: 4px solid #000000;
            background-color: #fafafa;
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
            <a href="?action=substack" class="nav-link">Substack</a>
            <a href="?action=settings" class="nav-link">Settings</a>
        </nav>

        <header>
            <h1>
                <svg class="logo-icon logo-icon-large" viewBox="0 0 24 16" xmlns="http://www.w3.org/2000/svg">
                    <rect width="24" height="16" fill="#FFFFC5"/>
                    <path d="M0,8 L4,12 L6,4 L10,10 L14,2 L18,8 L20,6 L24,8" stroke="#000000" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Style Guide
            </h1>
            <p class="subtitle">Design system documentation for Seismo</p>
        </header>

        <!-- Logo -->
        <section class="styleguide-section">
            <h2>Logo</h2>
            <p>Black waveform on light yellow (#FFFFC5) background. Use <code>.logo-icon</code> for inline (1em height) and <code>.logo-icon-large</code> for header size (32px).</p>
            
            <div class="logo-showcase">
                <div class="logo-variant">
                    <div class="logo-variant-label">Inline (1em)</div>
                    <svg class="logo-icon" viewBox="0 0 24 16" xmlns="http://www.w3.org/2000/svg" style="height: 16px;">
                        <rect width="24" height="16" fill="#FFFFC5"/>
                        <path d="M0,8 L4,12 L6,4 L10,10 L14,2 L18,8 L20,6 L24,8" stroke="#000000" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="logo-variant">
                    <div class="logo-variant-label">Large (32px)</div>
                    <svg class="logo-icon logo-icon-large" viewBox="0 0 24 16" xmlns="http://www.w3.org/2000/svg">
                        <rect width="24" height="16" fill="#FFFFC5"/>
                        <path d="M0,8 L4,12 L6,4 L10,10 L14,2 L18,8 L20,6 L24,8" stroke="#000000" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
        </section>

        <!-- Colors -->
        <section class="styleguide-section">
            <h2>Colors</h2>
            <p>Minimal high-contrast palette. Black and white primary, yellow for highlights, semantic colors for buttons.</p>
            
            <h3>Core</h3>
            <div>
                <div class="color-swatch" style="background-color: #000000;">
                    <div class="color-swatch-info">#000000<br>Black</div>
                </div>
                <div class="color-swatch" style="background-color: #FFFFFF;">
                    <div class="color-swatch-info">#FFFFFF<br>White</div>
                </div>
                <div class="color-swatch" style="background-color: #FFFFC5;">
                    <div class="color-swatch-info">#FFFFC5<br>Yellow</div>
                </div>
                <div class="color-swatch" style="background-color: #333333;">
                    <div class="color-swatch-info">#333333<br>Dark Gray</div>
                </div>
                <div class="color-swatch" style="background-color: #666666;">
                    <div class="color-swatch-info">#666666<br>Gray</div>
                </div>
                <div class="color-swatch" style="background-color: #F5F5F5;">
                    <div class="color-swatch-info">#F5F5F5<br>Light Gray</div>
                </div>
            </div>
            
            <h3>Semantic</h3>
            <div>
                <div class="color-swatch" style="background-color: #FF2C2C;">
                    <div class="color-swatch-info">#FF2C2C<br>Danger</div>
                </div>
                <div class="color-swatch" style="background-color: #ff9900;">
                    <div class="color-swatch-info">#ff9900<br>Warning</div>
                </div>
                <div class="color-swatch" style="background-color: #00aa00;">
                    <div class="color-swatch-info">#00aa00<br>Success</div>
                </div>
            </div>

            <h3>Tag Colors</h3>
            <div>
                <div class="color-swatch" style="background-color: #add8e6;">
                    <div class="color-swatch-info">#add8e6<br>RSS Tags</div>
                </div>
                <div class="color-swatch" style="background-color: #FFDBBB;">
                    <div class="color-swatch-info">#FFDBBB<br>Email Tags</div>
                </div>
            </div>
        </section>

        <!-- Typography -->
        <section class="styleguide-section">
            <h2>Typography</h2>
            <p>System font stack for native feel. All sizes in px, weights 400-700.</p>
            
            <div class="code-block">-apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif</div>
            
            <div class="typography-sample">
                <h1 style="margin: 0;">H1 &mdash; 32px, 700</h1>
            </div>
            <div class="typography-sample">
                <h2 style="margin: 0;">H2 &mdash; 24px, 600</h2>
            </div>
            <div class="typography-sample">
                <h3 style="margin: 0;">H3 &mdash; 20px, 600</h3>
            </div>
            <div class="typography-sample">
                <p style="margin: 0;">Body &mdash; 16px, 400, line-height 1.6</p>
            </div>
            <div class="typography-sample">
                <p style="margin: 0; font-size: 14px;">Small &mdash; 14px, 400</p>
            </div>
            <div class="typography-sample">
                <p style="margin: 0; font-size: 13px;">Meta &mdash; 13px, 500 (card headers, tags)</p>
            </div>
        </section>

        <!-- Navigation -->
        <section class="styleguide-section">
            <h2>Navigation</h2>
            <p>Tab bar with 2px black border. Adjacent tabs overlap by 2px (<code>margin-left: -2px</code>) so shared borders stay 2px. Active tab: black background, white text. Tabs sit 2px above the bottom border to create the tab effect.</p>

            <div class="component-demo">
                <nav class="main-nav">
                    <a href="#" class="nav-link active">Active</a>
                    <a href="#" class="nav-link">Inactive</a>
                    <a href="#" class="nav-link">Another</a>
                </nav>
            </div>
        </section>

        <!-- Buttons -->
        <section class="styleguide-section">
            <h2>Buttons</h2>
            <p>2px border, hover inverts to colored background with white text. Padding: 10px 20px, font-size: 16px, font-weight: 600.</p>

            <div class="component-demo">
                <div style="display: flex; gap: 12px; flex-wrap: wrap; margin: 20px 0;">
                    <a href="#" class="btn btn-primary">Primary</a>
                    <a href="#" class="btn btn-secondary">Secondary</a>
                    <a href="#" class="btn btn-danger">Danger</a>
                    <a href="#" class="btn btn-warning">Warning</a>
                    <a href="#" class="btn btn-success">Success</a>
                </div>
                <p><strong>Primary:</strong> black bg, white text. <strong>Secondary:</strong> white bg, black border. <strong>Danger:</strong> #FF2C2C border. <strong>Warning:</strong> #ff9900 border. <strong>Success:</strong> #00aa00 border.</p>
            </div>
        </section>

        <!-- Cards -->
        <section class="styleguide-section">
            <h2>Cards</h2>
            <p>2px black border, 14px 16px padding. Hover adds <code>box-shadow: 2px 2px 0px #000000</code> without changing size.</p>
            
            <div class="component-demo">
                <div class="entry-card">
                    <div class="entry-header">
                        <span class="entry-feed">Source Name</span>
                        <span class="entry-date">24.01.2026 12:00</span>
                    </div>
                    <h3 class="entry-title">
                        <a href="#">Entry Title Example</a>
                    </h3>
                    <div class="entry-content entry-preview">
                        Preview text truncated to 200 characters. Cards display feed items, emails, and Substack posts with consistent styling across all pages...
                    </div>
                    <div class="entry-full-content" style="display:none">Full expanded content shown when the user clicks expand. This replaces the preview and shows the complete text in a pre-wrapped format that preserves line breaks. The content area uses the same font size and color as the preview.</div>
                    <div class="entry-actions">
                        <a href="#" class="entry-link">Read more &rarr;</a>
                        <button class="btn btn-secondary entry-expand-btn" style="font-size: 14px; padding: 8px 16px;">&#9660; expand</button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Expand / Collapse -->
        <section class="styleguide-section">
            <h2>Expand / Collapse</h2>
            <p>Entries with content longer than 200 characters get a toggle button. Per-entry: "&#9660; expand" / "&#9650; collapse". Global: "&#9660; expand all" / "&#9650; collapse all" in the section title row. Triangle arrows indicate direction.</p>
            
            <h3>Section Title with Global Toggle</h3>
            <div class="component-demo">
                <div class="section-title-row">
                    <h2 class="section-title" style="margin-bottom: 0;">Refreshed: 24.01.2026 12:00</h2>
                    <button class="btn btn-secondary entry-expand-all-btn" style="font-size: 14px; padding: 8px 16px;">&#9660; expand all</button>
                </div>
            </div>
            
            <h3>Per-Entry Buttons</h3>
            <div class="component-demo">
                <div style="display: flex; gap: 12px;">
                    <button class="btn btn-secondary" style="font-size: 14px; padding: 8px 16px;">&#9660; expand</button>
                    <button class="btn btn-secondary" style="font-size: 14px; padding: 8px 16px;">&#9650; collapse</button>
                </div>
            </div>
        </section>

        <!-- Tag Filters -->
        <section class="styleguide-section">
            <h2>Tag Filters</h2>
            <p>Two filter patterns: checkbox pills (main page, multi-select) and category buttons (RSS, Mail, Substack pages, single-select). Both use 6px 12px padding, 13px font, 500 weight.</p>
            
            <h3>Checkbox Pills (Main Page)</h3>
            <div class="component-demo">
                <p style="margin-bottom: 12px;"><strong>RSS</strong> (active: #add8e6)</p>
                <div class="tag-filter-list" style="margin-bottom: 20px;">
                    <label class="tag-filter-pill" style="background-color: #add8e6;">
                        <input type="checkbox" checked>
                        <span>Active Tag</span>
                    </label>
                    <label class="tag-filter-pill">
                        <input type="checkbox">
                        <span>Inactive Tag</span>
                    </label>
                </div>
                
                <p style="margin-bottom: 12px;"><strong>Email</strong> (active: #FFDBBB)</p>
                <div class="tag-filter-list">
                    <label class="tag-filter-pill" style="background-color: #FFDBBB;">
                        <input type="checkbox" checked>
                        <span>Active Tag</span>
                    </label>
                    <label class="tag-filter-pill">
                        <input type="checkbox">
                        <span>Inactive Tag</span>
                    </label>
                </div>
            </div>
            
            <h3>Category Buttons (RSS, Mail, Substack Pages)</h3>
            <div class="component-demo">
                <div class="category-filter">
                    <a href="#" class="category-btn" style="background-color: #add8e6;">All</a>
                    <a href="#" class="category-btn">Category 1</a>
                    <a href="#" class="category-btn">Category 2</a>
                </div>
            </div>
        </section>

        <!-- Tag Inputs -->
        <section class="styleguide-section">
            <h2>Tag Inputs (Settings)</h2>
            <p>Editable tag inputs on the settings page. Press Enter to save, Escape to cancel. Visual feedback: gray border while saving, green border/background (#00aa00 / #f0fff0) for 2 seconds on success.</p>
            
            <div class="component-demo">
                <h3>Per-Feed Tag</h3>
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 20px;">
                    <label style="font-weight: 600; font-size: 14px;">Tag:</label>
                    <div class="tag-input-wrapper">
                        <input type="text" class="feed-tag-input" value="example-tag" style="width: 150px;" readonly>
                    </div>
                </div>

                <h3>"All Tags" Rename</h3>
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    <div class="feed-tag-input-wrapper" style="display: inline-flex;">
                        <input type="text" class="feed-tag-input" value="tag-name" style="width: auto; min-width: 100px; padding: 6px 12px;" readonly>
                    </div>
                </div>
                
                <h3>Save States</h3>
                <div style="display: flex; gap: 16px; flex-wrap: wrap; margin-top: 10px;">
                    <div>
                        <div style="font-size: 12px; font-weight: 600; color: #666; margin-bottom: 4px;">DEFAULT</div>
                        <input type="text" class="feed-tag-input" value="normal" style="width: 120px;" readonly>
                    </div>
                    <div>
                        <div style="font-size: 12px; font-weight: 600; color: #666; margin-bottom: 4px;">SAVING</div>
                        <input type="text" class="feed-tag-input feed-tag-saving" value="saving..." style="width: 120px;" readonly>
                    </div>
                    <div>
                        <div style="font-size: 12px; font-weight: 600; color: #666; margin-bottom: 4px;">SAVED</div>
                        <input type="text" class="feed-tag-input feed-tag-saved" value="saved" style="width: 120px;" readonly>
                    </div>
                </div>
            </div>
        </section>

        <!-- Messages -->
        <section class="styleguide-section">
            <h2>Messages</h2>
            <p>Feedback messages: 1px border, 10px 14px padding.</p>
            
            <div class="component-demo">
                <div class="message message-success">Success: Operation completed.</div>
                <div class="message message-error">Error: Something went wrong.</div>
                <div class="message message-info">Info: Informational message.</div>
            </div>
        </section>

        <!-- Forms -->
        <section class="styleguide-section">
            <h2>Forms</h2>
            <p>Inputs: 2px black border, 10px 14px padding, 16px font. Focus: #fafafa background.</p>
            
            <div class="component-demo">
                <input type="text" class="search-input" placeholder="Search input" style="margin-bottom: 15px; display: block; width: 100%; max-width: 400px;">
                <input type="text" class="feed-input" placeholder="Feed/URL input" style="display: block; width: 100%; max-width: 400px;">
            </div>
        </section>

        <!-- Search Highlight -->
        <section class="styleguide-section">
            <h2>Search Highlight</h2>
            <p>Matching search terms highlighted with yellow background (#FFFFC5).</p>
            
            <div class="component-demo">
                <p>Example text with <mark class="search-highlight">highlighted terms</mark> matching the query.</p>
            </div>
        </section>

        <!-- Spacing & Borders -->
        <section class="styleguide-section">
            <h2>Spacing &amp; Borders</h2>
            
            <div class="component-demo">
                <p><strong>Container:</strong> max-width 1200px, padding 20px</p>
                <p><strong>Cards:</strong> 14px 16px padding, 10px gap between cards</p>
                <p><strong>Buttons:</strong> 10px 20px padding</p>
                <p><strong>Nav:</strong> 10px 20px padding per tab, 16px margin-bottom</p>
                <p><strong>Section gaps:</strong> 16-24px between sections</p>
            </div>
            
            <div class="component-demo">
                <div style="border: 2px solid #000000; padding: 20px; margin: 10px 0;">2px solid &mdash; buttons, cards, nav tabs, inputs</div>
                <div style="border: 1px solid #cccccc; padding: 20px; margin: 10px 0;">1px solid &mdash; dividers, category filter bottom</div>
                <div style="border: 1px solid #000000; padding: 20px; margin: 10px 0;">1px solid black &mdash; messages</div>
            </div>
        </section>

        <!-- Hover Effects -->
        <section class="styleguide-section">
            <h2>Hover Effects</h2>
            <p>Cards get a <code>box-shadow: 2px 2px 0px #000000</code> on hover without changing size. Buttons and nav links get a background color change. The floating refresh button has a permanent shadow and inverts on hover.</p>
            
            <div class="component-demo">
                <p>Hover the card and button below:</p>
                <div class="entry-card" style="max-width: 400px; margin: 16px 0;">
                    <div class="entry-header">
                        <span class="entry-feed">Card hover</span>
                    </div>
                    <h3 class="entry-title"><a href="#">Shadow appears on hover</a></h3>
                </div>
                <div style="display: flex; gap: 12px;">
                    <a href="#" class="btn btn-secondary">Button hover</a>
                    <a href="#" class="floating-refresh-btn" style="position: static;">Refresh</a>
                </div>
            </div>
        </section>
    </div>

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

        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.entry-expand-btn');
            if (!btn) return;
            var card = btn.closest('.entry-card');
            if (!card) return;
            var full = card.querySelector('.entry-full-content');
            if (!full) return;
            if (full.style.display === 'block') {
                collapseEntry(card, btn);
            } else {
                expandEntry(card, btn);
            }
        });

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
