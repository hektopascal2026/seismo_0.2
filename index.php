<?php
session_start();

require_once 'config.php';
require_once 'vendor/autoload.php';

use SimplePie\SimplePie;

// Initialize database tables
initDatabase();

$action = $_GET['action'] ?? 'index';
$pdo = getDbConnection();

switch ($action) {
    case 'index':
        // Show main page with entries only (no feeds section)
        $searchQuery = trim($_GET['q'] ?? '');

        // Tag filter: selected tags from query (multi-select)
        $selectedTags = isset($_GET['tags']) ? array_filter((array)$_GET['tags']) : [];

        // Get all unique tags (categories)
        $tagsStmt = $pdo->query("SELECT DISTINCT category FROM feeds WHERE category IS NOT NULL AND category != '' ORDER BY category");
        $tags = $tagsStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // If search query exists, show search results instead of latest items
        if (!empty($searchQuery)) {
            $latestItems = searchFeedItems($pdo, $searchQuery, 100, $selectedTags);
            $searchResultsCount = count($latestItems);
        } else {
            // Get latest 30 items from enabled feeds only, optionally filtered by tags
            if (!empty($selectedTags)) {
                $placeholders = implode(',', array_fill(0, count($selectedTags), '?'));
                $sql = "
                    SELECT fi.*, f.title as feed_title 
                    FROM feed_items fi
                    JOIN feeds f ON fi.feed_id = f.id
                    WHERE f.disabled = 0
                      AND f.category IN ($placeholders)
                    ORDER BY fi.published_date DESC, fi.cached_at DESC
                    LIMIT 30
                ";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($selectedTags);
                $latestItems = $stmt->fetchAll();
            } else {
                $latestItemsStmt = $pdo->query("
                    SELECT fi.*, f.title as feed_title 
                    FROM feed_items fi
                    JOIN feeds f ON fi.feed_id = f.id
                    WHERE f.disabled = 0
                    ORDER BY fi.published_date DESC, fi.cached_at DESC
                    LIMIT 30
                ");
                $latestItems = $latestItemsStmt->fetchAll();
            }
            $searchResultsCount = null;
        }
        
        // Get last feed refresh date/time
        $lastRefreshStmt = $pdo->query("SELECT MAX(last_fetched) as last_refresh FROM feeds WHERE last_fetched IS NOT NULL");
        $lastRefreshResult = $lastRefreshStmt->fetch();
        $lastRefreshDate = null;
        if ($lastRefreshResult && $lastRefreshResult['last_refresh']) {
            $lastRefreshDate = date('d.m.Y H:i', strtotime($lastRefreshResult['last_refresh']));
        }
        
        // Get last code change date (use modification time of index.php)
        $lastChangeDate = date('d.m.Y', filemtime(__FILE__));
        
        include 'views/index.php';
        break;
        
    case 'feeds':
        // Show feeds management page
        $selectedCategory = $_GET['category'] ?? null;
        
        // Set default category "unsortiert" for feeds without category
        $pdo->exec("UPDATE feeds SET category = 'unsortiert' WHERE category IS NULL OR category = ''");
        
        // Get all unique categories
        $categoriesStmt = $pdo->query("SELECT DISTINCT category FROM feeds WHERE category IS NOT NULL AND category != '' ORDER BY category");
        $categories = $categoriesStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Get feeds (filtered by category if selected)
        if ($selectedCategory) {
            $stmt = $pdo->prepare("SELECT * FROM feeds WHERE category = ? ORDER BY created_at DESC");
            $stmt->execute([$selectedCategory]);
        } else {
            $stmt = $pdo->query("SELECT * FROM feeds ORDER BY created_at DESC");
        }
        $feeds = $stmt->fetchAll();
        
        // Get last code change date (use modification time of index.php)
        $lastChangeDate = date('d.m.Y', filemtime(__FILE__));
        
        include 'views/feeds.php';
        break;
        
    case 'mail':
        // Show mail page
        // Get latest emails (if table exists)
        $emails = [];
        $mailTableError = null;
        $lastMailRefreshDate = null;
        $showAll = isset($_GET['show_all']) || isset($_SESSION['email_refresh_count']);
        $limit = $showAll ? 10000 : 50; // Show all emails when refreshed
        
        // Get table name from session if available (set by refresh function)
        $tableName = $_SESSION['email_table_name'] ?? 'emails';
        
        try {
            // Check what tables exist
            $allTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            
            // Try to find the emails table (case-insensitive)
            // Priority: fetched_emails (cronjob default), then emails, then any table with mail/email
            $foundTable = null;
            foreach ($allTables as $table) {
                if (strtolower($table) === 'fetched_emails') {
                    $foundTable = $table;
                    break;
                }
            }
            
            if (!$foundTable) {
                foreach ($allTables as $table) {
                    if (strtolower($table) === 'emails' || strtolower($table) === 'email') {
                        $foundTable = $table;
                        break;
                    }
                }
            }
            
            // If not found, look for any table with 'mail' or 'email' in the name
            if (!$foundTable) {
                foreach ($allTables as $table) {
                    if (stripos($table, 'mail') !== false || stripos($table, 'email') !== false) {
                        $foundTable = $table;
                        break;
                    }
                }
            }
            
            if (!$foundTable) {
                $mailTableError = "No emails table found. Available tables: " . implode(', ', $allTables);
            } else {
                $tableName = $foundTable; // Use the actual table name (case-sensitive)
                
                // Refreshed: last time the fetch script added an email (latest created_at or similar timestamp column)
                // Try different possible timestamp column names (including cronjob's date_utc)
                $timestampColumns = ['created_at', 'date_utc', 'date_received', 'date_sent', 'timestamp', 'created'];
                $lastRefreshDate = null;
                
                foreach ($timestampColumns as $col) {
                    try {
                        $lastMailRefreshStmt = $pdo->query("SELECT MAX(`$col`) AS last_refresh FROM `$tableName` WHERE `$col` IS NOT NULL");
                        $lastMailRefreshResult = $lastMailRefreshStmt->fetch();
                        if ($lastMailRefreshResult && $lastMailRefreshResult['last_refresh']) {
                            $lastRefreshDate = $lastMailRefreshResult['last_refresh'];
                            break;
                        }
                    } catch (PDOException $e) {
                        // Column doesn't exist, try next
                        continue;
                    }
                }
                
                if ($lastRefreshDate) {
                    $lastMailRefreshDate = date('d.m.Y H:i', strtotime($lastRefreshDate));
                }

                // Get count of emails for debugging
                $countStmt = $pdo->query("SELECT COUNT(*) as count FROM `$tableName`");
                $countResult = $countStmt->fetch();
                $emailCount = $countResult['count'] ?? 0;

                // Get column names to understand the structure
                $descStmt = $pdo->query("DESCRIBE `$tableName`");
                $tableColumns = $descStmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Try to get emails - map cronjob columns to expected columns
                try {
                    // Check if this is the cronjob table structure (has from_addr, body_text, body_html, date_utc)
                    $isCronjobTable = in_array('from_addr', $tableColumns) && 
                                     (in_array('body_text', $tableColumns) || in_array('body_html', $tableColumns));
                    
                    if ($isCronjobTable) {
                        // Use cronjob column names and map them
                        $selectClause = "
                            id,
                            subject,
                            from_addr as from_email,
                            from_addr as from_name,
                            date_utc as date_received,
                            date_utc as date_sent,
                            body_text as text_body,
                            body_html as html_body,
                            created_at
                        ";
                        $orderBy = "created_at DESC";
                    } else {
                        // Try standard column names
                        $selectColumns = [];
                        $columnMap = [
                            'id' => 'id',
                            'subject' => 'subject',
                            'from_email' => 'from_email',
                            'from_name' => 'from_name',
                            'created_at' => 'created_at',
                            'date_received' => 'date_received',
                            'date_sent' => 'date_sent',
                            'text_body' => 'text_body',
                            'html_body' => 'html_body'
                        ];
                        
                        foreach ($columnMap as $expected => $actual) {
                            if (in_array($actual, $tableColumns)) {
                                $selectColumns[] = "`$actual` as `$expected`";
                            }
                        }
                        
                        if (empty($selectColumns)) {
                            // Fallback to SELECT *
                            $selectClause = '*';
                        } else {
                            $selectClause = implode(', ', $selectColumns);
                        }
                        
                        // Determine ORDER BY column
                        $orderBy = 'id DESC'; // Default
                        foreach (['created_at', 'date_utc', 'date_received', 'date_sent', 'id'] as $orderCol) {
                            if (in_array($orderCol, $tableColumns)) {
                                $orderBy = "`$orderCol` DESC";
                                break;
                            }
                        }
                    }
                    
                    $stmt = $pdo->query("
                        SELECT $selectClause
                        FROM `$tableName`
                        ORDER BY $orderBy
                        LIMIT $limit
                    ");
                    $emails = $stmt->fetchAll();
                    
                    // Post-process emails to parse from_addr if needed
                    foreach ($emails as &$email) {
                        // If from_email and from_name are the same (both from_addr), parse it
                        if (isset($email['from_email']) && isset($email['from_name']) && 
                            $email['from_email'] === $email['from_name'] && 
                            !empty($email['from_email'])) {
                            $fromAddr = $email['from_email'];
                            // Parse "Name" <email@domain.com> or just email@domain.com
                            if (preg_match('/^"([^"]+)"\s*<(.+)>$/', $fromAddr, $matches)) {
                                $email['from_name'] = $matches[1];
                                $email['from_email'] = $matches[2];
                            } elseif (preg_match('/^(.+)\s*<(.+)>$/', $fromAddr, $matches)) {
                                $email['from_name'] = trim($matches[1]);
                                $email['from_email'] = $matches[2];
                            } elseif (preg_match('/^(.+@.+)$/', $fromAddr)) {
                                // Just email, no name
                                $email['from_email'] = $fromAddr;
                                $email['from_name'] = '';
                            }
                        }
                    }
                    unset($email); // Break reference
                } catch (PDOException $e) {
                    // If that fails, try SELECT *
                    try {
                        $stmt = $pdo->query("SELECT * FROM `$tableName` LIMIT $limit");
                        $emails = $stmt->fetchAll();
                        $mailTableError = "Warning: Using SELECT * query. Table columns: " . implode(', ', $tableColumns) . ". Original error: " . $e->getMessage();
                    } catch (PDOException $e2) {
                        $mailTableError = "Query error: " . $e2->getMessage() . ". Table: $tableName, Columns: " . implode(', ', $tableColumns);
                        $emails = [];
                    }
                }
                
                // Debug: if count > 0 but emails array is empty, there might be a column mismatch
                if ($emailCount > 0 && empty($emails)) {
                    $mailTableError = "Found $emailCount email(s) in table '$tableName' but query returned no results. Table columns: " . implode(', ', $tableColumns);
                } elseif ($emailCount > 0 && count($emails) > 0) {
                    // Success - clear any previous errors
                    if (isset($_SESSION['email_refresh_count'])) {
                        unset($_SESSION['email_refresh_count']);
                    }
                }
            }
        } catch (PDOException $e) {
            // Table might not exist yet on some installations or there's a query error
            $mailTableError = "Database error: " . $e->getMessage();
        }

        // Get last code change date (use modification time of index.php)
        $lastChangeDate = date('d.m.Y', filemtime(__FILE__));
        
        include 'views/mail.php';
        break;
        
    case 'add_feed':
        handleAddFeed($pdo);
        break;
        
    case 'delete_feed':
        handleDeleteFeed($pdo);
        break;
        
    case 'toggle_feed':
        handleToggleFeed($pdo);
        break;
        
    case 'view_feed':
        $feedId = (int)$_GET['id'] ?? 0;
        viewFeed($pdo, $feedId);
        break;
        
    case 'refresh_feed':
        $feedId = (int)$_GET['id'] ?? 0;
        refreshFeed($pdo, $feedId);
        header('Location: ?action=view_feed&id=' . $feedId);
        break;
        
    case 'refresh_all_feeds':
        refreshAllFeeds($pdo);
        $currentAction = $_GET['from'] ?? 'index';
        $redirectUrl = '?action=' . $currentAction;
        if ($currentAction === 'view_feed' && isset($_GET['id'])) {
            $redirectUrl .= '&id=' . (int)$_GET['id'];
        } elseif ($currentAction === 'feeds' && isset($_GET['category'])) {
            $redirectUrl .= '&category=' . urlencode($_GET['category']);
        }
        $_SESSION['success'] = 'All feeds refreshed successfully';
        header('Location: ' . $redirectUrl);
        break;
        
    case 'api_feeds':
        header('Content-Type: application/json');
        $stmt = $pdo->query("SELECT * FROM feeds ORDER BY created_at DESC");
        echo json_encode($stmt->fetchAll());
        break;
        
    case 'api_items':
        header('Content-Type: application/json');
        $feedId = (int)$_GET['feed_id'] ?? 0;
        $stmt = $pdo->prepare("SELECT * FROM feed_items WHERE feed_id = ? ORDER BY published_date DESC LIMIT 50");
        $stmt->execute([$feedId]);
        echo json_encode($stmt->fetchAll());
        break;
        
    case 'api_tags':
        header('Content-Type: application/json');
        $stmt = $pdo->query("SELECT DISTINCT category FROM feeds WHERE category IS NOT NULL AND category != '' ORDER BY category");
        $tags = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode($tags);
        break;
        
    case 'update_feed_tag':
        handleUpdateFeedTag($pdo);
        break;
        
    case 'refresh_emails':
        refreshEmails($pdo);
        $currentAction = $_GET['from'] ?? 'mail';
        $redirectUrl = '?action=' . $currentAction . '&show_all=1';
        // Success message is set in refreshEmails() function
        header('Location: ' . $redirectUrl);
        break;
        
    default:
        header('Location: ?action=index');
        break;
}

function handleAddFeed($pdo) {
    $url = filter_input(INPUT_POST, 'url', FILTER_SANITIZE_URL);
    
    if (!$url) {
        $_SESSION['error'] = 'Please provide a valid URL';
        header('Location: ?action=feeds');
        return;
    }
    
    // Parse feed to validate and get info
    $feed = new \SimplePie\SimplePie();
    $feed->set_feed_url($url);
    $feed->enable_cache(false);
    $feed->init();
    $feed->handle_content_type();
    
    if ($feed->error()) {
        $_SESSION['error'] = 'Error parsing feed: ' . $feed->error();
        header('Location: ?action=feeds');
        return;
    }
    
    // Check if feed already exists
    $stmt = $pdo->prepare("SELECT id FROM feeds WHERE url = ?");
    $stmt->execute([$url]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = 'Feed already exists';
        header('Location: ?action=feeds');
        return;
    }
    
    // Insert feed with default category "unsortiert"
    $stmt = $pdo->prepare("INSERT INTO feeds (url, title, description, link, category) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $url,
        $feed->get_title() ?: 'Untitled Feed',
        $feed->get_description() ?: '',
        $feed->get_link() ?: $url,
        'unsortiert'
    ]);
    
    $feedId = $pdo->lastInsertId();
    
    // Fetch and cache items
    cacheFeedItems($pdo, $feedId, $feed);
    
    $_SESSION['success'] = 'Feed added successfully';
    header('Location: ?action=feeds');
}

function handleDeleteFeed($pdo) {
    $feedId = (int)$_GET['id'] ?? 0;
    
    $stmt = $pdo->prepare("DELETE FROM feeds WHERE id = ?");
    $stmt->execute([$feedId]);
    
    $_SESSION['success'] = 'Feed deleted successfully';
    header('Location: ?action=feeds');
}

function handleToggleFeed($pdo) {
    $feedId = (int)$_GET['id'] ?? 0;
    
    // Get current disabled status
    $stmt = $pdo->prepare("SELECT disabled FROM feeds WHERE id = ?");
    $stmt->execute([$feedId]);
    $feed = $stmt->fetch();
    
    if (!$feed) {
        $_SESSION['error'] = 'Feed not found';
        header('Location: ?action=feeds');
        return;
    }
    
    // Toggle disabled status
    $newStatus = $feed['disabled'] ? 0 : 1;
    $updateStmt = $pdo->prepare("UPDATE feeds SET disabled = ? WHERE id = ?");
    $updateStmt->execute([$newStatus, $feedId]);
    
    $statusText = $newStatus ? 'disabled' : 'enabled';
    $_SESSION['success'] = 'Feed ' . $statusText . ' successfully';
    header('Location: ?action=feeds');
}

function viewFeed($pdo, $feedId) {
    // Get feed info
    $stmt = $pdo->prepare("SELECT * FROM feeds WHERE id = ?");
    $stmt->execute([$feedId]);
    $feed = $stmt->fetch();
    
    if (!$feed) {
        header('Location: ?action=index');
        return;
    }
    
    // Get cached items
    $stmt = $pdo->prepare("SELECT * FROM feed_items WHERE feed_id = ? ORDER BY published_date DESC LIMIT 100");
    $stmt->execute([$feedId]);
    $items = $stmt->fetchAll();
    
    // Check if feed needs refresh
    $needsRefresh = false;
    if ($feed['last_fetched'] === null || 
        (time() - strtotime($feed['last_fetched'])) > CACHE_DURATION) {
        $needsRefresh = true;
    }
    
    include 'views/feed.php';
}

function refreshFeed($pdo, $feedId) {
    $stmt = $pdo->prepare("SELECT * FROM feeds WHERE id = ?");
    $stmt->execute([$feedId]);
    $feed = $stmt->fetch();
    
    if (!$feed) {
        return;
    }
    
    // Parse feed
    $simplepie = new \SimplePie\SimplePie();
    $simplepie->set_feed_url($feed['url']);
    $simplepie->enable_cache(false);
    $simplepie->init();
    $simplepie->handle_content_type();
    
    if (!$simplepie->error()) {
        // Update feed info
        $updateStmt = $pdo->prepare("UPDATE feeds SET title = ?, description = ?, link = ?, last_fetched = NOW() WHERE id = ?");
        $updateStmt->execute([
            $simplepie->get_title() ?: $feed['title'],
            $simplepie->get_description() ?: $feed['description'],
            $simplepie->get_link() ?: $feed['link'],
            $feedId
        ]);
        
        // Cache items
        cacheFeedItems($pdo, $feedId, $simplepie);
    }
}

function cacheFeedItems($pdo, $feedId, $simplepie) {
    $stmt = $pdo->prepare("INSERT INTO feed_items (feed_id, guid, title, link, description, content, author, published_date) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                          ON DUPLICATE KEY UPDATE 
                          title = VALUES(title),
                          link = VALUES(link),
                          description = VALUES(description),
                          content = VALUES(content),
                          author = VALUES(author),
                          published_date = VALUES(published_date),
                          cached_at = NOW()");
    
    foreach ($simplepie->get_items() as $item) {
        $guid = $item->get_id() ?: md5($item->get_link());
        $published = $item->get_date('Y-m-d H:i:s') ?: date('Y-m-d H:i:s');
        
        $stmt->execute([
            $feedId,
            $guid,
            $item->get_title() ?: 'Untitled',
            $item->get_link() ?: '',
            $item->get_description() ?: '',
            $item->get_content() ?: '',
            $item->get_author() ? $item->get_author()->get_name() : '',
            $published
        ]);
    }
}

function refreshAllFeeds($pdo) {
    // Get all feeds
    $stmt = $pdo->query("SELECT id FROM feeds ORDER BY id");
    $feeds = $stmt->fetchAll();
    
    // Refresh each feed
    foreach ($feeds as $feed) {
        refreshFeed($pdo, $feed['id']);
    }
}

function searchFeedItems($pdo, $query, $limit = 100, $selectedTags = []) {
    // Prepare search term with wildcards
    $searchTerm = '%' . $query . '%';
    
    // Base SQL: search in title, description, and content (only from enabled feeds)
    $sql = "
        SELECT fi.*, f.title as feed_title 
        FROM feed_items fi
        JOIN feeds f ON fi.feed_id = f.id
        WHERE f.disabled = 0
          AND (fi.title LIKE ? 
           OR fi.description LIKE ? 
           OR fi.content LIKE ?)
    ";
    $params = [$searchTerm, $searchTerm, $searchTerm];
    
    // Optional tag filter
    if (!empty($selectedTags)) {
        $placeholders = implode(',', array_fill(0, count($selectedTags), '?'));
        $sql .= " AND f.category IN ($placeholders)";
        $params = array_merge($params, $selectedTags);
    }
    
    $sql .= " ORDER BY fi.published_date DESC, fi.cached_at DESC LIMIT ?";
    $params[] = $limit;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function highlightSearchTerm($text, $searchQuery) {
    if (empty($searchQuery) || empty($text)) {
        return htmlspecialchars($text);
    }
    
    // Escape the text first for safe HTML output
    $escapedText = htmlspecialchars($text);
    
    // Escape the search query for use in regex (to handle special regex characters)
    $escapedQuery = preg_quote($searchQuery, '/');
    
    // Case-insensitive highlight - replace matches with highlighted version
    $highlighted = preg_replace(
        '/' . $escapedQuery . '/i',
        '<mark class="search-highlight">$0</mark>',
        $escapedText
    );
    
    return $highlighted;
}

function handleUpdateFeedTag($pdo) {
    header('Content-Type: application/json');
    
    $feedId = (int)$_POST['feed_id'] ?? 0;
    $tag = trim($_POST['tag'] ?? '');
    
    if (!$feedId) {
        echo json_encode(['success' => false, 'error' => 'Invalid feed ID']);
        return;
    }
    
    // Validate tag - cannot be empty
    if (empty($tag)) {
        echo json_encode(['success' => false, 'error' => 'Tag cannot be empty']);
        return;
    }
    
    // Update feed tag
    $stmt = $pdo->prepare("UPDATE feeds SET category = ? WHERE id = ?");
    $stmt->execute([$tag, $feedId]);
    
    echo json_encode(['success' => true, 'tag' => $tag]);
}

function refreshEmails($pdo) {
    // This function triggers a refresh/reload of emails from the database
    // The actual loading happens in the 'mail' case
    // We just need to ensure the table exists and is accessible
    try {
        // First, let's check what tables exist (for debugging)
        $allTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $tableNames = implode(', ', $allTables);
        
        // Check for fetched_emails (cronjob default), then emails, then any email-related table
        $tableName = null;
        foreach ($allTables as $table) {
            if (strtolower($table) === 'fetched_emails') {
                $tableName = $table;
                break;
            }
        }
        
        if (!$tableName) {
            $tableCheck = $pdo->query("SHOW TABLES LIKE 'emails'");
            if ($tableCheck->rowCount() > 0) {
                $tableName = 'emails';
            }
        }
        
        if (!$tableName) {
            foreach ($allTables as $table) {
                if (stripos($table, 'mail') !== false || stripos($table, 'email') !== false) {
                    $tableName = $table;
                    break;
                }
            }
        }
        
        if (!$tableName) {
            $_SESSION['error'] = "No emails table found. Available tables: $tableNames";
            return;
        }
        
        // Get count of emails from the actual table
        try {
            $countStmt = $pdo->query("SELECT COUNT(*) as count FROM `$tableName`");
            $countResult = $countStmt->fetch();
            $emailCount = $countResult['count'] ?? 0;
            
            // Get column names to see the structure
            $descStmt = $pdo->query("DESCRIBE `$tableName`");
            $columns = $descStmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Store info in session
            $_SESSION['email_refresh_count'] = $emailCount;
            $_SESSION['email_table_name'] = $tableName;
            $_SESSION['email_table_columns'] = $columns;
            
            if ($emailCount > 0) {
                $_SESSION['success'] = "Emails refreshed successfully. Found $emailCount email(s) in table '$tableName'.";
            } else {
                $_SESSION['success'] = "Emails refreshed. Table '$tableName' exists but contains 0 emails. Available tables: $tableNames";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error querying table '$tableName': " . $e->getMessage() . ". Available tables: $tableNames";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error refreshing emails: ' . $e->getMessage();
    }
}
