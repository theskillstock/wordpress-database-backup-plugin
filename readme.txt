# Zee DB Backup - WordPress Database Backup Plugin

![Zee DB Backup](https://codewithzubi.com/uploads/post/CRUD-System-app-in-Laravel-9-7375.png)

A modern, user-friendly WordPress plugin for creating and managing database backups directly from your admin dashboard.

## 📋 Features

- **One-Click Backups**: Create database backups with a single click
- **Modern UI**: Clean, intuitive interface that integrates seamlessly with WordPress
- **Multiple Backup Methods**: Uses mysqldump (if available) or PHP-based backup as fallback
- **Backup Management**: Download or delete backups directly from the admin panel
- **Security**: Implements WordPress security best practices (nonces, permission checks, etc.)
- **No External Dependencies**: Everything runs within your WordPress installation
- **Responsive Design**: Works smoothly on desktop and mobile devices

## 🔧 Installation

### Automatic Installation
1. Log in to your WordPress admin panel
2. Navigate to "Plugins" → "Add New"
3. Search for "Zee DB Backup"
4. Click "Install Now" and then "Activate"

### Manual Installation
1. Download the plugin zip file
2. Log in to your WordPress admin panel
3. Navigate to "Plugins" → "Add New" → "Upload Plugin"
4. Choose the zip file and click "Install Now"
5. Activate the plugin

## 🚀 Usage

1. After activation, find "DB Backup" in your WordPress admin sidebar
2. Click the "Create Backup" button to generate a new database backup
3. View your backup history in the table below
4. Use the "Download" button to save a backup to your computer
5. Use the "Delete" button to remove unwanted backups


## ⚙️ How It Works

The plugin connects to your WordPress database using the credentials from your wp-config.php file. When you create a backup, it:

1. Attempts to use mysqldump if available on your server (preferred method)
2. Falls back to a PHP-based backup solution if mysqldump isn't available
3. Saves the SQL file in the plugin's assets/backups directory
4. Updates the backup history in the admin interface

## 🔒 Security

- Backup files are protected with .htaccess rules to prevent direct access
- All operations require valid nonces for CSRF protection
- Only users with 'manage_options' capability (administrators) can create or manage backups
- Database credentials are read directly from WordPress core functions

## 💻 Technical Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- MySQL 5.6 or higher / MariaDB 10.0 or higher
- Sufficient server memory to process your database size

## 📝 FAQ

### How large of a database can this plugin backup?
The plugin can handle databases of various sizes, but performance depends on your server resources. For very large databases (>500MB), you might experience timeouts with the PHP-based method. In such cases, we recommend ensuring mysqldump is available on your server.

### Where are my backups stored?
Backups are stored in the `/wp-content/plugins/zee-db-backup/assets/backups/` directory.

### Are backups compressed?
Currently, backups are stored as plain SQL files. A future update will add compression options.

### Can I schedule automatic backups?
The current version supports manual backups only. Scheduled backups will be added in a future release.

### Is this plugin compatible with multisite installations?
Yes, the plugin works with WordPress multisite installations.

## 🔄 Changelog

### 1.0.0
- Initial release
- One-click database backup functionality
- Download and delete backup capabilities
- Modern, responsive user interface

## 🛠️ Future Enhancements

- Scheduled automatic backups
- Cloud storage integration (Google Drive, Dropbox, etc.)
- Backup compression options
- Email notifications
- Selective table backups
- Database restoration functionality

## 🤝 Contributing

Contributions are welcome! If you'd like to contribute, please:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the GPL-2.0+ - see the [LICENSE](LICENSE) file for details.

## 👨‍💻 Author

- **Muhammad Zubair** - [GitHub Profile](https://github.com/theskillstock)

## 🙏 Acknowledgments

- Icon made by [Freepik](https://www.freepik.com) from [Flaticon](https://www.flaticon.com)
- Thanks to all contributors and testers

---

💡 If you find this plugin useful, please consider giving it a star on GitHub and sharing it with others!