# Seismo 0.2

A PHP-based RSS feed reader and email viewer application. Seismo allows you to aggregate, manage, and view RSS feeds and emails in a unified interface.

## Features

### RSS Feed Management
- **Feed Aggregation**: Add and manage multiple RSS feeds
- **Automatic Refresh**: Feeds can be refreshed manually or automatically
- **Categorization**: Organize feeds with tags/categories
- **Search**: Full-text search across all feed items (title, description, content)
- **Tag Filtering**: Filter feed items by category tags
- **Feed Management**: Enable/disable feeds, view individual feed details
- **Caching**: Feed items are cached in the database for fast access

### Email Viewing
- **Email Display**: View emails from database tables
- **Flexible Table Support**: Automatically detects email tables (supports `fetched_emails`, `emails`, or custom tables)
- **Email Parsing**: Handles different email table structures and formats
- **Refresh Functionality**: Refresh email list from database

### User Interface
- **Clean Design**: Modern, responsive interface
- **Navigation**: Easy navigation between feeds, mail, and main view
- **Search Highlighting**: Search terms are highlighted in results
- **Status Indicators**: Shows last refresh times and feed status

## Requirements

- PHP >= 7.2
- MySQL/MariaDB database
- Composer (for dependency management)
- Web server (Apache, Nginx, or PHP built-in server)

## Installation

1. **Clone or download the repository**
   ```bash
   cd seismo_0.2
   ```

2. **Install dependencies using Composer**
   ```bash
   composer install
   ```

3. **Configure the database**
   - Edit `config.php` and update the database credentials:
     ```php
     define('DB_HOST', 'localhost:3306');
     define('DB_NAME', 'your_database_name');
     define('DB_USER', 'your_username');
     define('DB_PASS', 'your_password');
     ```

4. **Set up the web server**
   - Point your web server document root to the project directory
   - Or use PHP's built-in server:
     ```bash
     php -S localhost:8000
     ```

5. **Access the application**
   - Open your browser and navigate to `http://localhost:8000` (or your configured URL)
   - The database tables will be created automatically on first access

## Configuration

### Database Settings
Edit `config.php` to configure:
- Database host, name, username, and password
- Cache duration (default: 3600 seconds / 1 hour)

### Cache Duration
The `CACHE_DURATION` constant in `config.php` controls how long feeds are cached before being considered stale. Default is 3600 seconds (1 hour).

## Usage

### Adding RSS Feeds
1. Navigate to the "Feeds" page
2. Enter the RSS feed URL in the form
3. Click "Add Feed"
4. The feed will be fetched and cached automatically

### Managing Feeds
- **View Feed**: Click on a feed to see all its items
- **Refresh Feed**: Click the refresh button to update a single feed
- **Refresh All**: Use the "Refresh All Feeds" button to update all feeds
- **Enable/Disable**: Toggle feeds on/off to control which feeds appear in the main view
- **Categorize**: Add tags/categories to organize your feeds
- **Delete**: Remove feeds you no longer need

### Searching
- Use the search box on the main page to search across all feed items
- Search works on titles, descriptions, and content
- Combine search with tag filters for more precise results

### Viewing Emails
1. Navigate to the "Mail" page
2. The application will automatically detect email tables in your database
3. Use the "Refresh Emails" button to reload emails from the database
4. Emails are displayed with subject, sender, and content

## Project Structure

```
seismo_0.2/
├── assets/
│   └── css/
│       └── style.css          # Application styles
├── views/
│   ├── index.php               # Main page view
│   ├── feeds.php               # Feeds management view
│   ├── feed.php                # Individual feed view
│   └── mail.php                # Email view
├── vendor/                     # Composer dependencies
├── config.php                  # Database and application configuration
├── index.php                   # Main application router
├── composer.json               # PHP dependencies
└── README.md                   # This file
```

## Dependencies

- **SimplePie** (`simplepie/simplepie`): RSS/Atom feed parsing
- **PHP MIME Mail Parser** (`php-mime-mail-parser/php-mime-mail-parser`): Email parsing (if needed)

## Database Schema

The application automatically creates the following tables:

### `feeds`
- Stores RSS feed information
- Fields: id, url, title, description, link, category, disabled, last_fetched, created_at

### `feed_items`
- Stores cached feed items
- Fields: id, feed_id, guid, title, link, description, content, author, published_date, cached_at

### `emails`
- Stores email information (optional, can use external tables)
- Fields: id, subject, from_email, from_name, text_body, html_body, date_received, date_sent, created_at

## API Endpoints

The application provides JSON API endpoints:

- `?action=api_feeds` - Get all feeds as JSON
- `?action=api_items&feed_id=X` - Get items for a specific feed
- `?action=api_tags` - Get all available tags/categories

## Development

### Adding New Features
- Main application logic is in `index.php`
- Views are in the `views/` directory
- Database initialization is in `config.php` (`initDatabase()` function)
- Styling can be customized in `assets/css/style.css`

## License

This is a prototype project by hektopascal.org.

## Version

Current version: 0.2
