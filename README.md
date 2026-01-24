# Seismo

A simple PHP-based RSS feed reader and email viewer.

## Features

- RSS feed aggregation and management
- Email viewing from database tables
- Full-text search across feed items
- Tag-based filtering and organization
- Clean, responsive interface

## Requirements

- PHP >= 7.2
- MySQL/MariaDB database
- Composer

## Quick Start

1. **Install dependencies**
   ```bash
   composer install
   ```

2. **Configure database**
   - Edit `config.php` with your database credentials

3. **Run the app**
   ```bash
   php -S localhost:8000
   ```

4. **Open in browser**
   - Visit `http://localhost:8000`
   - Database tables are created automatically

## Usage

- **Add feeds**: Go to Feeds page, enter RSS URLs
- **Search**: Use the search box on the main page
- **Filter**: Use tag/category filters
- **View emails**: Navigate to the Mail page

## Dependencies

- SimplePie (RSS parsing)
- PHP MIME Mail Parser (email parsing)

## License

Prototype project by hektopascal.org.
