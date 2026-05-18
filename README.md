# 📚 Manga Tracker

A personal manga collection management system built with PHP, MySQL, and vanilla JavaScript. Track your reading progress, upload chapters, and organize your manga library.

## ✨ Features

- **Secure Authentication** – Password-protected access to your collection  
- **Manga Management** – Add, edit, and delete manga entries  
- **Chapter Upload System** – Upload and manage manga chapters as ZIP files  
- **Chapter Count Badge** – See at a glance how many chapters are archived locally for each manga  
- **Reading Progress** – Track current chapter and reading status  
- **Image Support** – Upload cover images or use external URLs  
- **Personal Notes** – Add notes for each manga  
- **Star Rating** – Rate each manga from 0 to 5 stars  
- **Status Tracking** – Mark manga as "Reading" or "Completed"  
- **Reading Language** – Track and display the language of each manga with a flag badge  
- **Search Bar** – Real-time search filtering by title or personal notes  
- **Light/Dark Theme Toggle** – Switch between light and dark mode with instant transitions and persistent user preference  
- **Responsive Design** – Works on desktop and mobile devices  
- **Statistics Dashboard** – View your collection stats at a glance  

## 🚀 Installation

### Prerequisites

- PHP 7.4 or higher  
- MySQL 5.7 or higher  
- Apache/Nginx web server  
- Minimum 512MB PHP memory limit  

### Database Setup

1. Create a MySQL database:
```sql
CREATE DATABASE manga_collection CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Create the `mangas` table:
```sql
CREATE TABLE mangas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    image VARCHAR(500),
    reading_link VARCHAR(500) NOT NULL,
    current_chapter VARCHAR(100) NOT NULL,
    status ENUM('reading', 'completed') DEFAULT 'reading',
    language VARCHAR(10) DEFAULT 'fr',
    notes TEXT,
    rating TINYINT UNSIGNED DEFAULT 0,
    date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

> **Upgrading from v1.0.0?** Run this migration to add the `language` column:
> ```sql
> ALTER TABLE mangas ADD COLUMN language VARCHAR(10) DEFAULT 'fr' AFTER status;
> UPDATE mangas SET language = 'fr' WHERE language IS NULL;
> ```

> **Upgrading from v1.1.0?** Run this migration to add the `rating` column:
> ```sql
> ALTER TABLE mangas ADD COLUMN rating TINYINT UNSIGNED DEFAULT 0 AFTER notes;
> ```
> The application also handles this migration automatically on first run.

3. Create the `manga_chapters` table:
```sql
CREATE TABLE manga_chapters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    manga_id INT NOT NULL,
    chapter_number VARCHAR(50) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size BIGINT NOT NULL,
    date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (manga_id) REFERENCES mangas(id) ON DELETE CASCADE,
    INDEX idx_manga_id (manga_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### File Structure

```
manga-tracker/
├── config/
│   └── mysql.php              # Database configuration
├── img/
│   └── manga/                 # Uploaded manga covers
├── archives/
│   └── chapters/              # Uploaded chapter files
├── style/
│   └── manga.css              # Application styles
├── js/
│   └── manga.js               # Client-side JavaScript
├── index.php                  # Main application page
├── add_manga.php              # Add/update manga handler
├── get_mangas.php             # Fetch manga list
├── delete_manga.php           # Delete manga handler
├── manage_chapters.php        # Chapter management handler
├── check_php_config.php       # PHP configuration checker
├── .htaccess                  # PHP configuration overrides
└── README.md                  # This file
```

### Configuration

1. **Database Connection** – Edit `config/mysql.php`:
```php
$mysql_host = 'localhost';
$mysql_user = 'your_username';
$mysql_password = 'your_password';
$mysql_dbname = 'manga_collection';
```

2. **Password Setup** – The default password is `manga2024`. To change it:
   - Generate a new hash:
   ```php
   echo password_hash('your_new_password', PASSWORD_DEFAULT);
   ```
   - Update the hash in `index.php` (line 16)

3. **Directory Permissions** – Ensure these directories are writable:
```bash
chmod 755 img/manga/
chmod 755 archives/chapters/
```

4. **PHP Configuration** – The `.htaccess` file sets:
   - upload_max_filesize: 210M
   - post_max_size: 220M
   - max_execution_time: 300s
   - memory_limit: 512M

### Verification

Run `check_php_config.php` to verify your setup:
- Check PHP upload limits
- Verify directory permissions
- Confirm database tables exist

**Important:** Delete `check_php_config.php` after verification for security.

## 📖 Usage

### Login
1. Navigate to `index.php`
2. Enter your password (default: `manga2024`)

### Theme Toggle
1. Use the ☀️/🌙 button in the top bar to switch between light and dark mode
2. The theme updates instantly without reloading the page
3. Your preference is saved automatically and restored on your next visit
4. If no preference is saved, the site follows your system theme

### Add a Manga
1. Click "Add a manga" button
2. Fill in the required fields:
   - Title (required)
   - Cover image (upload or URL)
   - Reading link (required)
   - Current chapter (required)
   - Status (reading/completed)
   - Reading language (flag badge displayed on card)
   - Personal notes (optional)
   - Rating from 0 to 5 (optional)
3. Click "Save"

### Search
- Use the search bar above the manga grid to filter in real time
- Searches across both **title** and **personal notes**
- Click ✕ to clear the search and restore the full collection

### Manage Chapters
1. Click the 📦 icon on any manga card
2. Upload chapters:
   - Enter chapter number
   - Select ZIP file (max 200MB)
   - Click "Upload chapter"
3. Download or delete chapters as needed

### Edit/Delete Manga
- Click on any manga card to edit
- Use the 🗑️ button to delete (with confirmation)

## 🐛 Troubleshooting

### Upload Failures
- Check PHP upload limits in `.htaccess`
- Verify directory permissions (755 for directories)
- Check available disk space
- Review error logs

### Database Connection Issues
- Verify credentials in `config/mysql.php`
- Ensure MySQL service is running
- Check database and table existence
- Verify user permissions

### Images Not Displaying
- Check file paths are correct
- Verify image directory permissions
- Ensure images are valid formats (JPEG, PNG, GIF, WebP)

### Rating Column Missing
If you see a database error related to the `rating` column, the automatic migration should handle it on first request. If the issue persists, run the migration manually:
```sql
ALTER TABLE mangas ADD COLUMN rating TINYINT UNSIGNED DEFAULT 0 AFTER notes;
```

## 📝 License

This project is open source and available for personal use.

## 🤝 Contributing

Feel free to submit issues and enhancement requests!

## 📧 Support

For questions or issues, please check the troubleshooting section or review the code comments for detailed implementation information.

---

**Version:** 1.2.0  
**Last Updated:** May 2026