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
            color: #000000;
        }
        
        .styleguide-section h3 {
            font-size: 20px;
            font-weight: 600;
            margin-top: 30px;
            margin-bottom: 15px;
            color: #000000;
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
                Seismo
            </a>
            <a href="?action=feeds" class="nav-link">Feeds</a>
            <a href="?action=mail" class="nav-link">Mail</a>
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

        <!-- Logo Section -->
        <section class="styleguide-section">
            <h2>Logo</h2>
            <p>The Seismo logo features a black waveform on a light yellow background (#FFFFC5). The waveform represents seismic activity, aligning with the project's name.</p>
            
            <div class="logo-showcase">
                <div class="logo-variant">
                    <div class="logo-variant-label">Standard Size</div>
                    <svg class="logo-icon" viewBox="0 0 24 16" xmlns="http://www.w3.org/2000/svg" style="height: 32px;">
                        <rect width="24" height="16" fill="#FFFFC5"/>
                        <path d="M0,8 L4,12 L6,4 L10,10 L14,2 L18,8 L20,6 L24,8" stroke="#000000" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                
                <div class="logo-variant">
                    <div class="logo-variant-label">Large Size</div>
                    <svg class="logo-icon logo-icon-large" viewBox="0 0 24 16" xmlns="http://www.w3.org/2000/svg" style="height: 64px;">
                        <rect width="24" height="16" fill="#FFFFC5"/>
                        <path d="M0,8 L4,12 L6,4 L10,10 L14,2 L18,8 L20,6 L24,8" stroke="#000000" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            
            <h3>SVG Code</h3>
            <div class="code-block">
&lt;svg class="logo-icon" viewBox="0 0 24 16" xmlns="http://www.w3.org/2000/svg"&gt;
    &lt;rect width="24" height="16" fill="#FFFFC5"/&gt;
    &lt;path d="M0,8 L4,12 L6,4 L10,10 L14,2 L18,8 L20,6 L24,8" stroke="#000000" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/&gt;
&lt;/svg&gt;
            </div>
        </section>

        <!-- Colors Section -->
        <section class="styleguide-section">
            <h2>Colors</h2>
            <p>The color palette is minimal and high-contrast, using black and white as primary colors with strategic use of yellow for highlights.</p>
            
            <div>
                <div class="color-swatch" style="background-color: #000000;">
                    <div class="color-swatch-info">#000000<br>Black</div>
                </div>
                <div class="color-swatch" style="background-color: #FFFFFF; border-color: #000000;">
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
                <div class="color-swatch" style="background-color: #F5F5F5; border-color: #000000;">
                    <div class="color-swatch-info">#F5F5F5<br>Light Gray</div>
                </div>
            </div>
        </section>

        <!-- Typography Section -->
        <section class="styleguide-section">
            <h2>Typography</h2>
            <p>Seismo uses system fonts for optimal performance and native feel across platforms.</p>
            
            <h3>Font Family</h3>
            <div class="code-block">-apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif</div>
            
            <h3>Headings</h3>
            <div class="typography-sample">
                <h1 style="margin: 0;">Heading 1 - 32px, Bold (700)</h1>
            </div>
            <div class="typography-sample">
                <h2 style="margin: 0;">Heading 2 - 24px, Semi-bold (600)</h2>
            </div>
            <div class="typography-sample">
                <h3 style="margin: 0;">Heading 3 - 20px, Semi-bold (600)</h3>
            </div>
            
            <h3>Body Text</h3>
            <div class="typography-sample">
                <p style="margin: 0;">Body text - 16px, Regular (400), Line-height: 1.6</p>
            </div>
            
            <h3>Small Text</h3>
            <div class="typography-sample">
                <p style="margin: 0; font-size: 14px;">Small text - 14px, Regular (400)</p>
            </div>
        </section>

        <!-- Buttons Section -->
        <section class="styleguide-section">
            <h2>Buttons</h2>
            <p>Buttons use a consistent 2px black border and have hover states that invert colors.</p>
            
            <div class="component-demo">
                <h3>Button Variants</h3>
                <div style="display: flex; gap: 12px; flex-wrap: wrap; margin: 20px 0;">
                    <a href="#" class="btn btn-primary">Primary Button</a>
                    <a href="#" class="btn btn-secondary">Secondary Button</a>
                    <a href="#" class="btn btn-danger">Danger Button</a>
                    <a href="#" class="btn btn-warning">Warning Button</a>
                    <a href="#" class="btn btn-success">Success Button</a>
                </div>
            </div>
        </section>

        <!-- Navigation Section -->
        <section class="styleguide-section">
            <h2>Navigation</h2>
            <p>The main navigation uses tabs with a 2px black border. Active state has black background with white text.</p>
            
            <div class="component-demo">
                <nav class="main-nav">
                    <a href="#" class="nav-link active">Active Link</a>
                    <a href="#" class="nav-link">Inactive Link</a>
                    <a href="#" class="nav-link">Another Link</a>
                </nav>
            </div>
        </section>

        <!-- Cards Section -->
        <section class="styleguide-section">
            <h2>Cards</h2>
            <p>Cards are used for displaying content items with a subtle border and hover effect.</p>
            
            <div class="component-demo">
                <div class="entry-card">
                    <div class="entry-header">
                        <span class="entry-feed">Feed Name</span>
                        <span class="entry-date">01.01.2024 12:00</span>
                    </div>
                    <h3 class="entry-title">
                        <a href="#">Card Title Example</a>
                    </h3>
                    <div class="entry-content">
                        This is an example of card content. Cards can contain various types of information and are used throughout the application for displaying feed items, emails, and other content.
                    </div>
                    <a href="#" class="entry-link">Read more →</a>
                </div>
            </div>
        </section>

        <!-- Messages Section -->
        <section class="styleguide-section">
            <h2>Messages</h2>
            <p>Messages provide feedback to users for success, error, and info states.</p>
            
            <div class="component-demo">
                <div class="message message-success">Success message: Operation completed successfully.</div>
                <div class="message message-error">Error message: Something went wrong. Please try again.</div>
                <div class="message message-info">Info message: This is an informational message.</div>
            </div>
        </section>

        <!-- Form Elements Section -->
        <section class="styleguide-section">
            <h2>Form Elements</h2>
            <p>Form inputs use a consistent 2px black border and have focus states.</p>
            
            <div class="component-demo">
                <h3>Input Fields</h3>
                <input type="text" class="search-input" placeholder="Search input example" style="margin-bottom: 15px; display: block; width: 100%; max-width: 400px;">
                <input type="text" class="feed-input" placeholder="Feed input example" style="display: block; width: 100%; max-width: 400px;">
            </div>
        </section>

        <!-- Search Highlight Section -->
        <section class="styleguide-section">
            <h2>Search Highlight</h2>
            <p>Search terms are highlighted with a light yellow background (#FFFFC5) to make them easily visible.</p>
            
            <div class="component-demo">
                <p>This is an example of text with <mark class="search-highlight">highlighted search terms</mark> that match the user's query.</p>
            </div>
        </section>

        <!-- Tag Filters Section -->
        <section class="styleguide-section">
            <h2>Tag Filters</h2>
            <p>Tag filters allow users to filter content by categories. Active tags have a black background.</p>
            
            <div class="component-demo">
                <div class="tag-filter-list">
                    <label class="tag-filter-pill tag-filter-pill-active">
                        <input type="checkbox" checked>
                        <span>Active Tag</span>
                    </label>
                    <label class="tag-filter-pill">
                        <input type="checkbox">
                        <span>Inactive Tag</span>
                    </label>
                    <label class="tag-filter-pill">
                        <input type="checkbox">
                        <span>Another Tag</span>
                    </label>
                </div>
            </div>
        </section>

        <!-- Spacing Section -->
        <section class="styleguide-section">
            <h2>Spacing</h2>
            <p>Consistent spacing is used throughout the application for visual hierarchy and readability.</p>
            
            <div class="component-demo">
                <p><strong>Container:</strong> Max-width 1200px, padding 40px 20px</p>
                <p><strong>Section gaps:</strong> 20-40px between major sections</p>
                <p><strong>Card padding:</strong> 20-24px</p>
                <p><strong>Button padding:</strong> 12px 24px</p>
            </div>
        </section>

        <!-- Borders Section -->
        <section class="styleguide-section">
            <h2>Borders</h2>
            <p>Borders use 2px solid black (#000000) for primary elements like buttons, cards, and navigation tabs, and 1px for subtle dividers.</p>
            
            <div class="component-demo">
                <div style="border: 2px solid #000000; padding: 20px; margin: 10px 0;">2px solid border (primary - buttons, cards)</div>
                <div style="border: 2px solid #000000; padding: 20px; margin: 10px 0;">2px solid border (navigation tabs)</div>
                <div style="border: 1px solid #cccccc; padding: 20px; margin: 10px 0;">1px solid border (subtle dividers)</div>
            </div>
        </section>
    </div>
</body>
</html>
