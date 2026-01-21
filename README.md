# \# 📚 Manga Tracker



A personal manga collection management system built with PHP, MySQL, and vanilla JavaScript. Track your reading progress, upload chapters, and organize your manga library.



### \## ✨ Features



\- \*\*Secure Authentication\*\* - Password-protected access to your collection

\- \*\*Manga Management\*\* - Add, edit, and delete manga entries

\- \*\*Chapter Upload System\*\* - Upload and manage manga chapters as ZIP files

\- \*\*Reading Progress\*\* - Track current chapter and reading status

\- \*\*Image Support\*\* - Upload cover images or use external URLs

\- \*\*Personal Notes\*\* - Add notes for each manga

\- \*\*Status Tracking\*\* - Mark manga as "Reading" or "Completed"

\- \*\*Responsive Design\*\* - Works on desktop and mobile devices

\- \*\*Statistics Dashboard\*\* - View your collection stats at a glance



### \## 🚀 Installation



#### \### Prerequisites



\- PHP 7.4 or higher

\- MySQL 5.7 or higher

\- Apache/Nginx web server

\- Minimum 512MB PHP memory limit



#### \### Database Setup



**1. Create a MySQL database:**

```sql

CREATE DATABASE manga\_collection CHARACTER SET utf8mb4 COLLATE utf8mb4\_unicode\_ci;

```



**2. Create the `mangas` table:**

```sql

CREATE TABLE mangas (

&nbsp;   id INT AUTO\_INCREMENT PRIMARY KEY,

&nbsp;   title VARCHAR(255) NOT NULL,

&nbsp;   image VARCHAR(500),

&nbsp;   reading\_link VARCHAR(500) NOT NULL,

&nbsp;   current\_chapter VARCHAR(100) NOT NULL,

&nbsp;   status ENUM('reading', 'completed') DEFAULT 'reading',

&nbsp;   notes TEXT,

&nbsp;   date\_added TIMESTAMP DEFAULT CURRENT\_TIMESTAMP,

&nbsp;   date\_updated TIMESTAMP DEFAULT CURRENT\_TIMESTAMP ON UPDATE CURRENT\_TIMESTAMP

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

```



**3. Create the `manga\_chapters` table:**

```sql

CREATE TABLE manga\_chapters (

&nbsp;   id INT AUTO\_INCREMENT PRIMARY KEY,

&nbsp;   manga\_id INT NOT NULL,

&nbsp;   chapter\_number VARCHAR(50) NOT NULL,

&nbsp;   file\_path VARCHAR(255) NOT NULL,

&nbsp;   file\_size BIGINT NOT NULL,

&nbsp;   date\_added TIMESTAMP DEFAULT CURRENT\_TIMESTAMP,

&nbsp;   FOREIGN KEY (manga\_id) REFERENCES mangas(id) ON DELETE CASCADE,

&nbsp;   INDEX idx\_manga\_id (manga\_id)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

```



#### \### File Structure



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

├── add\_manga.php              # Add/update manga handler

├── get\_mangas.php             # Fetch manga list

├── delete\_manga.php           # Delete manga handler

├── manage\_chapters.php        # Chapter management handler

├── check\_php\_config.php       # PHP configuration checker

├── .htaccess                  # PHP configuration overrides

└── README.md                  # This file

```



#### \### Configuration



**1. \*\*Database Connection\*\* - Edit `config/mysql.php`:**

```php

$mysql\_host = 'localhost';

$mysql\_user = 'your\_username';

$mysql\_password = 'your\_password';

$mysql\_dbname = 'manga\_collection';

```



**2. \*\*Password Setup\*\* - The default password is `manga2024`. To change it:**

&nbsp;  - Generate a new hash:

&nbsp;  ```php

&nbsp;  echo password\_hash('your\_new\_password', PASSWORD\_DEFAULT);

&nbsp;  ```

&nbsp;  - Update the hash in `index.php` (line 16)



**3. \*\*Directory Permissions\*\* - Ensure these directories are writable:**

```bash

chmod 755 img/manga/

chmod 755 archives/chapters/

```



**4. \*\*PHP Configuration\*\* - The `.htaccess` file sets:**

&nbsp;  - upload\_max\_filesize: 210M

&nbsp;  - post\_max\_size: 220M

&nbsp;  - max\_execution\_time: 300s

&nbsp;  - memory\_limit: 512M



#### \### Verification



**Run `check\_php\_config.php` to verify your setup:**

\- Check PHP upload limits

\- Verify directory permissions

\- Confirm database tables exist



***\*\*Important:\*\**** Delete `check\_php\_config.php` after verification for security.



### \## 📖 Usage



#### \### Login

1\. Navigate to `index.php`

2\. Enter your password (default: `manga2024`)



#### \### Add a Manga

1\. Click "Add a manga" button

2\. Fill in the required fields:

&nbsp;  - Title (required)

&nbsp;  - Cover image (upload or URL)

&nbsp;  - Reading link (required)

&nbsp;  - Current chapter (required)

&nbsp;  - Status (reading/completed)

&nbsp;  - Personal notes (optional)

3\. Click "Save"



#### \### Manage Chapters

1\. Click the 📦 icon on any manga card

2\. Upload chapters:

&nbsp;  - Enter chapter number

&nbsp;  - Select ZIP file (max 200MB)

&nbsp;  - Click "Upload chapter"

3\. Download or delete chapters as needed



#### \### Edit/Delete Manga

\- Click on any manga card to edit

\- Use the 🗑️ button to delete (with confirmation)



### \## 🐛 Troubleshooting



#### \### Upload Failures

\- Check PHP upload limits in `.htaccess`

\- Verify directory permissions (755 for directories)

\- Check available disk space

\- Review error logs



#### \### Database Connection Issues

\- Verify credentials in `config/mysql.php`

\- Ensure MySQL service is running

\- Check database and table existence

\- Verify user permissions



#### \### Images Not Displaying

\- Check file paths are correct

\- Verify image directory permissions

\- Ensure images are valid formats (JPEG, PNG, GIF, WebP)



### \## 📝 License



This project is open source and available for personal use.



### \## 🤝 Contributing



Feel free to submit issues and enhancement requests!



### \## 📧 Support



For questions or issues, please check the troubleshooting section or review the code comments for detailed implementation information.



---



\*\*Version:\*\* 1.0.0  

\*\*Last Updated:\*\* January 2026

