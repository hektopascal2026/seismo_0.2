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

        // Get all unique tags (categories) from RSS feeds
        $tagsStmt = $pdo->query("SELECT DISTINCT category FROM feeds WHERE category IS NOT NULL AND category != '' ORDER BY category");
        $tags = $tagsStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Get all unique email tags (excluding unclassified)
        $emailTagsStmt = $pdo->query("SELECT DISTINCT tag FROM sender_tags WHERE tag IS NOT NULL AND tag != '' AND tag != 'unclassified' AND removed_at IS NULL ORDER BY tag");
        $emailTags = $emailTagsStmt->fetchAll(PDO::FETCH_COLUMN);

        // Tag filter: selected tags from query (multi-select)
        // On first visit (no form submitted), auto-select all tags except "unsortiert"
        $tagsSubmitted = isset($_GET['tags_submitted']);
        if ($tagsSubmitted) {
            $selectedTags = isset($_GET['tags']) ? array_values(array_filter((array)$_GET['tags'], 'strlen')) : [];
            $selectedEmailTags = isset($_GET['email_tags']) ? array_values(array_filter((array)$_GET['email_tags'], 'strlen')) : [];
        } else {
            // First visit: auto-select all tags except "unsortiert"
            $selectedTags = array_values(array_filter($tags, function($t) { return $t !== 'unsortiert'; }));
            $selectedEmailTags = array_values(array_filter($emailTags, function($t) { return $t !== 'unsortiert' && $t !== 'unclassified'; }));
        }
        
        // If search query exists, show search results instead of latest items
        if (!empty($searchQuery)) {
            $latestItems = searchFeedItems($pdo, $searchQuery, 100, $selectedTags);
            $searchEmails = searchEmails($pdo, $searchQuery, 100, $selectedEmailTags);
            $searchResultsCount = count($latestItems) + count($searchEmails);
        } else {
            // Get latest 30 items from enabled feeds only, filtered by tags
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
            } elseif (!$tagsSubmitted) {
                // First visit with no form submission: show all
                $latestItemsStmt = $pdo->query("
                    SELECT fi.*, f.title as feed_title 
                    FROM feed_items fi
                    JOIN feeds f ON fi.feed_id = f.id
                    WHERE f.disabled = 0
                    ORDER BY fi.published_date DESC, fi.cached_at DESC
                    LIMIT 30
                ");
                $latestItems = $latestItemsStmt->fetchAll();
            } else {
                // User explicitly deselected all tags: show nothing
                $latestItems = [];
            }
            $searchResultsCount = null;
        }
        
        // Get emails and merge with feed items
        if (!empty($searchQuery)) {
            $emails = $searchEmails;
        } else {
            if (!empty($selectedEmailTags)) {
                $emails = getEmailsForIndex($pdo, 30, $selectedEmailTags);
            } elseif (!$tagsSubmitted) {
                // First visit: show all emails
                $emails = getEmailsForIndex($pdo, 30, []);
            } else {
                // User explicitly deselected all email tags: show nothing
                $emails = [];
            }
        }
        
        // Merge and sort by date
        $allItems = [];
        
        // Add feed items
        foreach ($latestItems as $item) {
            $dateValue = $item['published_date'] ?? $item['cached_at'] ?? null;
            $allItems[] = [
                'type' => 'feed',
                'date' => $dateValue ? strtotime($dateValue) : 0,
                'data' => $item
            ];
        }
        
        // Add emails
        foreach ($emails as $email) {
            $dateValue = $email['date_received'] ?? $email['date_utc'] ?? $email['created_at'] ?? $email['date_sent'] ?? null;
            $allItems[] = [
                'type' => 'email',
                'date' => $dateValue ? strtotime($dateValue) : 0,
                'data' => $email
            ];
        }
        
        // Sort by date (newest first)
        usort($allItems, function($a, $b) {
            return $b['date'] - $a['date'];
        });
        
        // Limit to 30 items total (or more for search)
        $limit = !empty($searchQuery) ? 200 : 30;
        $allItems = array_slice($allItems, 0, $limit);
        
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

        case 'ai_view_unified':
    // 1. Fetch RSS Items
    $latestItemsStmt = $pdo->query("
        SELECT fi.*, f.title as feed_title 
        FROM feed_items fi
        JOIN feeds f ON fi.feed_id = f.id
        WHERE f.disabled = 0
        ORDER BY fi.published_date DESC
        LIMIT 50
    ");
    $latestItems = $latestItemsStmt->fetchAll();

    // 2. Fetch Emails
    $emails = getEmailsForIndex($pdo, 50, []);

    // 3. Merge into unified list
    $allItems = [];
    foreach ($latestItems as $item) {
        $date = $item['published_date'] ?? $item['cached_at'] ?? 0;
        $allItems[] = [
            'source'  => $item['feed_title'],
            'date'    => strtotime($date),
            'title'   => $item['title'],
            'content' => strip_tags($item['content'] ?: $item['description']),
            'link'    => $item['link']
        ];
    }

    foreach ($emails as $email) {
        $date = $email['date_received'] ?? $email['date_utc'] ?? $email['created_at'] ?? 0;
        $from = ($email['from_name'] ?: $email['from_email']) ?: 'Unknown';
        $allItems[] = [
            'source'  => "EMAIL: $from",
            'date'    => strtotime($date),
            'title'   => $email['subject'] ?: '(No Subject)',
            'content' => strip_tags($email['text_body'] ?: $email['html_body'] ?: ''),
            'link'    => '#'
        ];
    }

    // 4. Sort chronologically (Newest First)
    usort($allItems, function($a, $b) {
        return $b['date'] - $a['date'];
    });

    include 'views/ai_view_unified.php';
    break;

    case 'ai_view':
        // Find the right table name (matches your system's logic)
        $allTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $tableName = 'fetched_emails'; // default
        foreach ($allTables as $table) {
            if (strtolower($table) === 'fetched_emails') { $tableName = $table; break; }
            if (strtolower($table) === 'emails') { $tableName = $table; }
        }

        // Fetch emails
        $stmt = $pdo->query("SELECT * FROM `$tableName` ORDER BY id DESC LIMIT 100");
        $emails = $stmt->fetchAll();

        // Load the specialized AI view
        include 'views/ai_view.php';
        break;
        
    case 'feeds':
        // Show RSS entries page
        $selectedCategory = $_GET['category'] ?? null;
        
        // Set default category "unsortiert" for feeds without category
        $pdo->exec("UPDATE feeds SET category = 'unsortiert' WHERE category IS NULL OR category = ''");
        
        // Get all unique categories
        $categoriesStmt = $pdo->query("SELECT DISTINCT category FROM feeds WHERE category IS NOT NULL AND category != '' ORDER BY category");
        $categories = $categoriesStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Get RSS entries (from enabled feeds, filtered by category if selected)
        if ($selectedCategory) {
            $stmt = $pdo->prepare("
                SELECT fi.*, f.title as feed_title
                FROM feed_items fi
                JOIN feeds f ON fi.feed_id = f.id
                WHERE f.disabled = 0 AND f.category = ?
                ORDER BY fi.published_date DESC, fi.cached_at DESC
                LIMIT 50
            ");
            $stmt->execute([$selectedCategory]);
        } else {
            $stmt = $pdo->query("
                SELECT fi.*, f.title as feed_title
                FROM feed_items fi
                JOIN feeds f ON fi.feed_id = f.id
                WHERE f.disabled = 0
                ORDER BY fi.published_date DESC, fi.cached_at DESC
                LIMIT 50
            ");
        }
        $rssItems = $stmt->fetchAll();
        
        // Get last feed refresh date/time
        $lastRefreshStmt = $pdo->query("SELECT MAX(last_fetched) as last_refresh FROM feeds WHERE last_fetched IS NOT NULL");
        $lastRefreshRow = $lastRefreshStmt->fetch();
        $lastRssRefreshDate = $lastRefreshRow['last_refresh'] ? date('d.m.Y H:i', strtotime($lastRefreshRow['last_refresh'])) : null;
        
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
        
        // Get all unique email tags (excluding unclassified and removed senders)
        $emailTagsStmt = $pdo->query("SELECT DISTINCT tag FROM sender_tags WHERE tag IS NOT NULL AND tag != '' AND tag != 'unclassified' AND removed_at IS NULL ORDER BY tag");
        $emailTags = $emailTagsStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Get selected email tag filter
        $selectedEmailTag = $_GET['email_tag'] ?? null;
        
        // Get disabled sender emails (including removed senders)
        $disabledStmt = $pdo->query("SELECT from_email FROM sender_tags WHERE disabled = 1 OR removed_at IS NOT NULL");
        $disabledEmails = $disabledStmt->fetchAll(PDO::FETCH_COLUMN);
        
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
                    
                    // Build WHERE clause to exclude disabled senders and filter by tag if selected
                    $whereClause = "1=1";
                    $params = [];
                    
                    // Exclude disabled senders
                    if (!empty($disabledEmails)) {
                        $placeholders = implode(',', array_fill(0, count($disabledEmails), '?'));
                        // Handle both from_email and from_addr columns
                        if ($isCronjobTable) {
                            $whereClause = "from_addr NOT IN ($placeholders)";
                        } else {
                            $whereClause = "from_email NOT IN ($placeholders)";
                        }
                        $params = $disabledEmails;
                    }
                    
                    // Filter by email tag if selected
                    if ($selectedEmailTag) {
                        $tagStmt = $pdo->prepare("SELECT from_email FROM sender_tags WHERE tag = ? AND removed_at IS NULL");
                        $tagStmt->execute([$selectedEmailTag]);
                        $taggedEmails = $tagStmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        if (!empty($taggedEmails)) {
                            $tagPlaceholders = implode(',', array_fill(0, count($taggedEmails), '?'));
                            if ($isCronjobTable) {
                                // Always append with AND to avoid malformed "1=1from_addr" when no previous conditions
                                $whereClause .= " AND from_addr IN ($tagPlaceholders)";
                            } else {
                                $whereClause .= " AND from_email IN ($tagPlaceholders)";
                            }
                            $params = array_merge($params, $taggedEmails);
                        } else {
                            // No emails with this tag, return empty
                            $emails = [];
                            break;
                        }
                    }
                    
                    $sql = "SELECT $selectClause FROM `$tableName` WHERE $whereClause ORDER BY $orderBy LIMIT $limit";
                    if (!empty($params)) {
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($params);
                    } else {
                        $stmt = $pdo->query($sql);
                    }
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
                    
                    // Sort emails chronologically by date
                    usort($emails, function($a, $b) {
                        $dateA = $a['date_received'] ?? $a['date_utc'] ?? $a['created_at'] ?? $a['date_sent'] ?? '';
                        $dateB = $b['date_received'] ?? $b['date_utc'] ?? $b['created_at'] ?? $b['date_sent'] ?? '';
                        $timeA = $dateA ? strtotime($dateA) : 0;
                        $timeB = $dateB ? strtotime($dateB) : 0;
                        return $timeB - $timeA; // Newest first
                    });
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
        
    case 'delete_email':
        handleDeleteEmail($pdo);
        break;
        
    case 'settings':
        // Show settings page
        $pdo = getDbConnection();
        
        // Get all feeds for RSS section
        $feedsStmt = $pdo->query("SELECT * FROM feeds ORDER BY created_at DESC");
        $allFeeds = $feedsStmt->fetchAll();
        
        // Get all unique tags from feeds
        $tagsStmt = $pdo->query("SELECT DISTINCT category FROM feeds WHERE category IS NOT NULL AND category != '' ORDER BY category");
        $allTags = $tagsStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Get all unique email tags (excluding unclassified and removed senders)
        $emailTagsStmt = $pdo->query("SELECT DISTINCT tag FROM sender_tags WHERE tag IS NOT NULL AND tag != '' AND tag != 'unclassified' AND removed_at IS NULL ORDER BY tag");
        $allEmailTags = $emailTagsStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Get all unique senders and their tags for Mail section
        $senderTags = [];
        try {
            // Find email table
            $allTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            $emailTableName = null;
            
            foreach ($allTables as $table) {
                if (strtolower($table) === 'fetched_emails') {
                    $emailTableName = $table;
                    break;
                }
            }
            
            if (!$emailTableName) {
                foreach ($allTables as $table) {
                    if (strtolower($table) === 'emails' || strtolower($table) === 'email') {
                        $emailTableName = $table;
                        break;
                    }
                }
            }
            
            if (!$emailTableName) {
                foreach ($allTables as $table) {
                    if (stripos($table, 'mail') !== false || stripos($table, 'email') !== false) {
                        $emailTableName = $table;
                        break;
                    }
                }
            }
            
            if ($emailTableName) {
                // Get column names to determine which columns exist
                $descStmt = $pdo->query("DESCRIBE `$emailTableName`");
                $tableColumns = $descStmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Determine which columns to use
                $hasFromEmail = in_array('from_email', $tableColumns);
                $hasFromAddr = in_array('from_addr', $tableColumns);
                $hasFromName = in_array('from_name', $tableColumns);
                
                // Build query based on available columns
                if ($hasFromEmail && $hasFromName) {
                    $sendersStmt = $pdo->query("
                        SELECT DISTINCT 
                            from_email as email,
                            COALESCE(from_name, '') as name
                        FROM `$emailTableName`
                        WHERE from_email IS NOT NULL AND from_email != ''
                        ORDER BY from_email
                    ");
                } elseif ($hasFromAddr) {
                    $sendersStmt = $pdo->query("
                        SELECT DISTINCT 
                            from_addr as email,
                            '' as name
                        FROM `$emailTableName`
                        WHERE from_addr IS NOT NULL AND from_addr != ''
                        ORDER BY from_addr
                    ");
                } else {
                    $sendersStmt = null;
                }
                
                if ($sendersStmt) {
                    $senders = $sendersStmt->fetchAll();
                } else {
                    $senders = [];
                }
                
                // Determine which email column and date columns to use for "newer email" checks
                $emailCol = $hasFromEmail ? 'from_email' : ($hasFromAddr ? 'from_addr' : null);
                $hasDateReceived = in_array('date_received', $tableColumns);
                $hasCreatedAt = in_array('created_at', $tableColumns);
                
                // Auto-tag new senders, re-activate removed senders only if newer emails exist
                foreach ($senders as $sender) {
                    $email = $sender['email'];
                    $tagStmt = $pdo->prepare("SELECT tag, disabled, removed_at FROM sender_tags WHERE from_email = ?");
                    $tagStmt->execute([$email]);
                    $tagResult = $tagStmt->fetch();
                    
                    if (!$tagResult) {
                        // Genuinely new sender — auto-tag with "unclassified"
                        $insertStmt = $pdo->prepare("INSERT INTO sender_tags (from_email, tag, disabled) VALUES (?, 'unclassified', 0)");
                        $insertStmt->execute([$email]);
                        $tagResult = ['tag' => 'unclassified', 'disabled' => 0, 'removed_at' => null];
                    } elseif ($tagResult['removed_at'] && $emailCol) {
                        // Sender was removed — check if a newer email has arrived since removal
                        $dateCond = [];
                        if ($hasDateReceived) $dateCond[] = "date_received > ?";
                        if ($hasCreatedAt) $dateCond[] = "created_at > ?";
                        
                        if (!empty($dateCond)) {
                            $dateWhere = '(' . implode(' OR ', $dateCond) . ')';
                            $newerStmt = $pdo->prepare("
                                SELECT 1 FROM `$emailTableName`
                                WHERE `$emailCol` = ? AND $dateWhere
                                LIMIT 1
                            ");
                            $removedAt = $tagResult['removed_at'];
                            $params = [$email];
                            if ($hasDateReceived) $params[] = $removedAt;
                            if ($hasCreatedAt) $params[] = $removedAt;
                            $newerStmt->execute($params);
                            
                            if ($newerStmt->fetch()) {
                                // New email arrived after removal — re-activate
                                $reactivateStmt = $pdo->prepare("UPDATE sender_tags SET removed_at = NULL WHERE from_email = ?");
                                $reactivateStmt->execute([$email]);
                                $tagResult['removed_at'] = null;
                            }
                        }
                    }
                    
                    // Only show senders that are not removed
                    if (empty($tagResult['removed_at'])) {
                        $senderTags[] = [
                            'email' => $email,
                            'name' => $sender['name'],
                            'tag' => $tagResult['tag'],
                            'disabled' => (bool)$tagResult['disabled']
                        ];
                    }
                }
            }
        } catch (PDOException $e) {
            // Error getting senders
        }
        
        include 'views/settings.php';
        break;
        
    case 'update_sender_tag':
        handleUpdateSenderTag($pdo);
        break;
        
    case 'toggle_sender':
        handleToggleSender($pdo);
        break;
        
    case 'delete_sender':
        handleDeleteSender($pdo);
        break;
        
    case 'rename_tag':
        handleRenameTag($pdo);
        break;
        
    case 'rename_email_tag':
        handleRenameEmailTag($pdo);
        break;
        
    case 'api_email_tags':
        header('Content-Type: application/json');
        $stmt = $pdo->query("SELECT DISTINCT tag FROM sender_tags WHERE tag IS NOT NULL AND tag != '' AND tag != 'unclassified' AND removed_at IS NULL ORDER BY tag");
        $tags = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode($tags);
        break;
        
    case 'styleguide':
        // Get last code change date (use modification time of index.php)
        $lastChangeDate = date('d.m.Y', filemtime(__FILE__));
        include 'views/styleguide.php';
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
    $from = $_GET['from'] ?? 'feeds';
    
    $stmt = $pdo->prepare("DELETE FROM feeds WHERE id = ?");
    $stmt->execute([$feedId]);
    
    $_SESSION['success'] = 'Feed deleted successfully';
    $redirectUrl = $from === 'settings' ? '?action=settings' : '?action=feeds';
    header('Location: ' . $redirectUrl);
}

function handleToggleFeed($pdo) {
    $feedId = (int)$_GET['id'] ?? 0;
    $from = $_GET['from'] ?? 'feeds';
    
    // Get current disabled status
    $stmt = $pdo->prepare("SELECT disabled FROM feeds WHERE id = ?");
    $stmt->execute([$feedId]);
    $feed = $stmt->fetch();
    
    if (!$feed) {
        $_SESSION['error'] = 'Feed not found';
        $redirectUrl = $from === 'settings' ? '?action=settings' : '?action=feeds';
        header('Location: ' . $redirectUrl);
        return;
    }
    
    // Toggle disabled status
    $newStatus = $feed['disabled'] ? 0 : 1;
    $updateStmt = $pdo->prepare("UPDATE feeds SET disabled = ? WHERE id = ?");
    $updateStmt->execute([$newStatus, $feedId]);
    
    $statusText = $newStatus ? 'disabled' : 'enabled';
    $_SESSION['success'] = 'Feed ' . $statusText . ' successfully';
    $redirectUrl = $from === 'settings' ? '?action=settings' : '?action=feeds';
    header('Location: ' . $redirectUrl);
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

function getEmailsForIndex($pdo, $limit = 30, $selectedEmailTags = []) {
    $emails = [];
    
    try {
        // Get disabled or removed sender emails
        $disabledStmt = $pdo->query("SELECT from_email FROM sender_tags WHERE disabled = 1 OR removed_at IS NOT NULL");
        $disabledEmails = $disabledStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Get emails by selected tags if any (only from active senders)
        $taggedEmails = [];
        if (!empty($selectedEmailTags)) {
            $tagPlaceholders = implode(',', array_fill(0, count($selectedEmailTags), '?'));
            $tagStmt = $pdo->prepare("SELECT from_email FROM sender_tags WHERE tag IN ($tagPlaceholders) AND removed_at IS NULL");
            $tagStmt->execute($selectedEmailTags);
            $taggedEmails = $tagStmt->fetchAll(PDO::FETCH_COLUMN);
        }
        
        // Find email table
        $allTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $tableName = null;
        
        foreach ($allTables as $table) {
            if (strtolower($table) === 'fetched_emails') {
                $tableName = $table;
                break;
            }
        }
        
        if (!$tableName) {
            foreach ($allTables as $table) {
                if (strtolower($table) === 'emails' || strtolower($table) === 'email') {
                    $tableName = $table;
                    break;
                }
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
        
        if ($tableName) {
            // Get column names
            $descStmt = $pdo->query("DESCRIBE `$tableName`");
            $tableColumns = $descStmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Check if this is the cronjob table structure
            $isCronjobTable = in_array('from_addr', $tableColumns) && 
                             (in_array('body_text', $tableColumns) || in_array('body_html', $tableColumns));
            
            if ($isCronjobTable) {
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
                    $selectClause = '*';
                } else {
                    $selectClause = implode(', ', $selectColumns);
                }
                
                $orderBy = 'id DESC';
                foreach (['created_at', 'date_utc', 'date_received', 'date_sent', 'id'] as $orderCol) {
                    if (in_array($orderCol, $tableColumns)) {
                        $orderBy = "`$orderCol` DESC";
                        break;
                    }
                }
            }
            
            // Build WHERE clause to exclude disabled senders and filter by tags
            $whereClause = "1=1";
            $params = [];
            
            // Exclude disabled senders
            if (!empty($disabledEmails)) {
                $placeholders = implode(',', array_fill(0, count($disabledEmails), '?'));
                // Handle both from_email and from_addr columns
                if ($isCronjobTable) {
                    $whereClause = "from_addr NOT IN ($placeholders)";
                } else {
                    $whereClause = "from_email NOT IN ($placeholders)";
                }
                $params = $disabledEmails;
            }
            
            // Filter by email tags if selected
            if (!empty($selectedEmailTags) && !empty($taggedEmails)) {
                $tagPlaceholders = implode(',', array_fill(0, count($taggedEmails), '?'));
                if ($isCronjobTable) {
                    // Always append with AND to avoid malformed "1=1from_addr" when no previous conditions
                    $whereClause .= " AND from_addr IN ($tagPlaceholders)";
                } else {
                    $whereClause .= " AND from_email IN ($tagPlaceholders)";
                }
                $params = array_merge($params, $taggedEmails);
            } elseif (!empty($selectedEmailTags) && empty($taggedEmails)) {
                // No emails with selected tags, return empty
                return [];
            }
            
            $sql = "SELECT $selectClause FROM `$tableName` WHERE $whereClause ORDER BY $orderBy LIMIT $limit";
            if (!empty($params)) {
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
            } else {
                $stmt = $pdo->query($sql);
            }
            $emails = $stmt->fetchAll();
            
            // Post-process emails to parse from_addr if needed
            foreach ($emails as &$email) {
                if (isset($email['from_email']) && isset($email['from_name']) && 
                    $email['from_email'] === $email['from_name'] && 
                    !empty($email['from_email'])) {
                    $fromAddr = $email['from_email'];
                    if (preg_match('/^"([^"]+)"\s*<(.+)>$/', $fromAddr, $matches)) {
                        $email['from_name'] = $matches[1];
                        $email['from_email'] = $matches[2];
                    } elseif (preg_match('/^(.+)\s*<(.+)>$/', $fromAddr, $matches)) {
                        $email['from_name'] = trim($matches[1]);
                        $email['from_email'] = $matches[2];
                    } elseif (preg_match('/^(.+@.+)$/', $fromAddr)) {
                        $email['from_email'] = $fromAddr;
                        $email['from_name'] = '';
                    }
                }
            }
            unset($email);
        }
    } catch (PDOException $e) {
        // Error getting emails, return empty array
    }
    
    return $emails;
}

function searchEmails($pdo, $query, $limit = 100, $selectedEmailTags = []) {
    $emails = [];
    $searchTerm = '%' . $query . '%';
    
    try {
        // Get disabled or removed sender emails
        $disabledStmt = $pdo->query("SELECT from_email FROM sender_tags WHERE disabled = 1 OR removed_at IS NOT NULL");
        $disabledEmails = $disabledStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Get emails by selected tags if any (only from active senders)
        $taggedEmails = [];
        if (!empty($selectedEmailTags)) {
            $tagPlaceholders = implode(',', array_fill(0, count($selectedEmailTags), '?'));
            $tagStmt = $pdo->prepare("SELECT from_email FROM sender_tags WHERE tag IN ($tagPlaceholders) AND removed_at IS NULL");
            $tagStmt->execute($selectedEmailTags);
            $taggedEmails = $tagStmt->fetchAll(PDO::FETCH_COLUMN);
        }
        
        // Find email table
        $allTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $tableName = null;
        
        foreach ($allTables as $table) {
            if (strtolower($table) === 'fetched_emails') {
                $tableName = $table;
                break;
            }
        }
        
        if (!$tableName) {
            foreach ($allTables as $table) {
                if (strtolower($table) === 'emails' || strtolower($table) === 'email') {
                    $tableName = $table;
                    break;
                }
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
        
        if ($tableName) {
            // Get column names
            $descStmt = $pdo->query("DESCRIBE `$tableName`");
            $tableColumns = $descStmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Check if this is the cronjob table structure
            $isCronjobTable = in_array('from_addr', $tableColumns) && 
                             (in_array('body_text', $tableColumns) || in_array('body_html', $tableColumns));
            
            if ($isCronjobTable) {
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
                $whereClause = "(subject LIKE ? OR body_text LIKE ? OR body_html LIKE ? OR from_addr LIKE ?)";
                $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
            } else {
                $selectColumns = [];
                $whereColumns = [];
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
                        if (in_array($actual, ['subject', 'from_email', 'from_name', 'text_body', 'html_body'])) {
                            $whereColumns[] = "`$actual` LIKE ?";
                        }
                    }
                }
                
                if (empty($selectColumns)) {
                    $selectClause = '*';
                    $whereClause = "1=1";
                    $params = [];
                } else {
                    $selectClause = implode(', ', $selectColumns);
                    $whereClause = '(' . implode(' OR ', $whereColumns) . ')';
                    $params = array_fill(0, count($whereColumns), $searchTerm);
                }
            }
            
            // Build WHERE clause to exclude disabled senders and filter by tags
            $whereParts = [$whereClause];
            $whereParams = $params;
            
            // Exclude disabled senders
            if (!empty($disabledEmails)) {
                $placeholders = implode(',', array_fill(0, count($disabledEmails), '?'));
                if ($isCronjobTable) {
                    $whereParts[] = "from_addr NOT IN ($placeholders)";
                } else {
                    $whereParts[] = "from_email NOT IN ($placeholders)";
                }
                $whereParams = array_merge($whereParams, $disabledEmails);
            }
            
            // Filter by email tags if selected
            if (!empty($selectedEmailTags) && !empty($taggedEmails)) {
                $tagPlaceholders = implode(',', array_fill(0, count($taggedEmails), '?'));
                if ($isCronjobTable) {
                    $whereParts[] = "from_addr IN ($tagPlaceholders)";
                } else {
                    $whereParts[] = "from_email IN ($tagPlaceholders)";
                }
                $whereParams = array_merge($whereParams, $taggedEmails);
            } elseif (!empty($selectedEmailTags) && empty($taggedEmails)) {
                // No emails with selected tags, return empty
                return [];
            }
            
            $finalWhereClause = implode(' AND ', $whereParts);
            
            $stmt = $pdo->prepare("
                SELECT $selectClause
                FROM `$tableName`
                WHERE $finalWhereClause
                ORDER BY created_at DESC, date_received DESC, id DESC
                LIMIT $limit
            ");
            $stmt->execute($whereParams);
            $emails = $stmt->fetchAll();
            
            // Post-process emails to parse from_addr if needed
            foreach ($emails as &$email) {
                if (isset($email['from_email']) && isset($email['from_name']) && 
                    $email['from_email'] === $email['from_name'] && 
                    !empty($email['from_email'])) {
                    $fromAddr = $email['from_email'];
                    if (preg_match('/^"([^"]+)"\s*<(.+)>$/', $fromAddr, $matches)) {
                        $email['from_name'] = $matches[1];
                        $email['from_email'] = $matches[2];
                    } elseif (preg_match('/^(.+)\s*<(.+)>$/', $fromAddr, $matches)) {
                        $email['from_name'] = trim($matches[1]);
                        $email['from_email'] = $matches[2];
                    } elseif (preg_match('/^(.+@.+)$/', $fromAddr)) {
                        $email['from_email'] = $fromAddr;
                        $email['from_name'] = '';
                    }
                }
            }
            unset($email);
        }
    } catch (PDOException $e) {
        // Error searching emails, return empty array
    }
    
    return $emails;
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

function handleRenameTag($pdo) {
    header('Content-Type: application/json');
    
    $oldTag = trim($_POST['old_tag'] ?? '');
    $newTag = trim($_POST['new_tag'] ?? '');
    
    if (empty($oldTag) || empty($newTag)) {
        echo json_encode(['success' => false, 'error' => 'Both old and new tag names are required']);
        return;
    }
    
    if ($oldTag === $newTag) {
        echo json_encode(['success' => false, 'error' => 'New tag name must be different from old tag name']);
        return;
    }
    
    // Update all feeds with the old tag to the new tag
    $stmt = $pdo->prepare("UPDATE feeds SET category = ? WHERE category = ?");
    $stmt->execute([$newTag, $oldTag]);
    
    $affectedRows = $stmt->rowCount();
    
    echo json_encode(['success' => true, 'affected' => $affectedRows]);
}

function handleRenameEmailTag($pdo) {
    header('Content-Type: application/json');
    
    $oldTag = trim($_POST['old_tag'] ?? '');
    $newTag = trim($_POST['new_tag'] ?? '');
    
    if (empty($oldTag) || empty($newTag)) {
        echo json_encode(['success' => false, 'error' => 'Both old and new tag names are required']);
        return;
    }
    
    if ($oldTag === $newTag) {
        echo json_encode(['success' => false, 'error' => 'New tag name must be different from old tag name']);
        return;
    }
    
    // Update all sender_tags with the old tag to the new tag
    $stmt = $pdo->prepare("UPDATE sender_tags SET tag = ? WHERE tag = ?");
    $stmt->execute([$newTag, $oldTag]);
    
    $affectedRows = $stmt->rowCount();
    
    echo json_encode(['success' => true, 'affected' => $affectedRows]);
}

function handleUpdateSenderTag($pdo) {
    header('Content-Type: application/json');
    
    $fromEmail = trim($_POST['from_email'] ?? '');
    $tag = trim($_POST['tag'] ?? '');
    
    if (empty($fromEmail)) {
        echo json_encode(['success' => false, 'error' => 'Invalid sender email']);
        return;
    }
    
    // Insert or update sender tag (preserve disabled status)
    $stmt = $pdo->prepare("INSERT INTO sender_tags (from_email, tag, disabled) VALUES (?, ?, 0) ON DUPLICATE KEY UPDATE tag = ?");
    $stmt->execute([$fromEmail, $tag, $tag]);
    
    echo json_encode(['success' => true, 'tag' => $tag]);
}

function handleToggleSender($pdo) {
    $fromEmail = trim($_POST['email'] ?? $_GET['email'] ?? '');
    $from = $_POST['from'] ?? $_GET['from'] ?? 'settings';
    
    if (empty($fromEmail)) {
        $_SESSION['error'] = 'Invalid sender email';
        header('Location: ?action=settings');
        return;
    }
    
    // Get current disabled status
    $stmt = $pdo->prepare("SELECT disabled FROM sender_tags WHERE from_email = ?");
    $stmt->execute([$fromEmail]);
    $result = $stmt->fetch();
    
    if (!$result) {
        // If sender doesn't exist in sender_tags, create it
        $newStatus = 1; // Disable
        $stmt = $pdo->prepare("INSERT INTO sender_tags (from_email, tag, disabled) VALUES (?, 'unclassified', ?)");
        $stmt->execute([$fromEmail, $newStatus]);
    } else {
        // Toggle disabled status
        $newStatus = $result['disabled'] ? 0 : 1;
        $updateStmt = $pdo->prepare("UPDATE sender_tags SET disabled = ? WHERE from_email = ?");
        $updateStmt->execute([$newStatus, $fromEmail]);
    }
    
    $statusText = $newStatus ? 'disabled' : 'enabled';
    $_SESSION['success'] = 'Sender ' . $statusText . ' successfully';
    header('Location: ?action=settings');
}

function handleDeleteSender($pdo) {
    $fromEmail = trim($_POST['email'] ?? $_GET['email'] ?? '');
    $from = $_POST['from'] ?? $_GET['from'] ?? 'settings';
    
    if (empty($fromEmail)) {
        $_SESSION['error'] = 'Invalid sender email';
        header('Location: ?action=settings');
        return;
    }
    
    // Mark sender as removed (don't delete — keeps record so auto-tag won't re-add them).
    // They reappear only when a new email arrives after the removal timestamp.
    $stmt = $pdo->prepare("UPDATE sender_tags SET removed_at = NOW(), tag = 'unclassified' WHERE from_email = ?");
    $stmt->execute([$fromEmail]);
    
    $_SESSION['success'] = "Sender removed from Seismo.\nFuture emails from this address will be tagged as \"unsortiert\" until you reassign them.\nTo stop receiving these emails, you need to manually unsubscribe from the sender's press releases.";
    header('Location: ?action=settings');
}

function handleDeleteEmail($pdo) {
    $emailId = (int)($_GET['id'] ?? 0);
    $confirm = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';
    
    if (!$emailId) {
        $_SESSION['error'] = 'Invalid email ID';
        header('Location: ?action=mail');
        return;
    }
    
    // Require confirmation parameter (prevents accidental deletions from direct URL access)
    if (!$confirm) {
        $_SESSION['error'] = 'Deletion requires confirmation';
        header('Location: ?action=mail');
        return;
    }
    
    try {
        // Find the email table (same logic as in mail case)
        $allTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $tableName = null;
        
        foreach ($allTables as $table) {
            if (strtolower($table) === 'fetched_emails') {
                $tableName = $table;
                break;
            }
        }
        
        if (!$tableName) {
            foreach ($allTables as $table) {
                if (strtolower($table) === 'emails' || strtolower($table) === 'email') {
                    $tableName = $table;
                    break;
                }
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
            $_SESSION['error'] = 'Email table not found';
            header('Location: ?action=mail');
            return;
        }
        
        // Verify email exists before deleting
        $checkStmt = $pdo->prepare("SELECT id FROM `$tableName` WHERE id = ?");
        $checkStmt->execute([$emailId]);
        if (!$checkStmt->fetch()) {
            $_SESSION['error'] = 'Email not found';
            header('Location: ?action=mail');
            return;
        }
        
        // Safe delete using prepared statement
        $deleteStmt = $pdo->prepare("DELETE FROM `$tableName` WHERE id = ?");
        $deleteStmt->execute([$emailId]);
        
        $_SESSION['success'] = 'Email deleted successfully';
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error deleting email: ' . $e->getMessage();
    }
    
    header('Location: ?action=mail');
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
