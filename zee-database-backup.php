<?php
/**
 * Plugin Name: ZeeDatabaseBackup
 * Plugin URI: https://codewithzubi.com
 * Description: A modern and easy-to-use WordPress database backup solution
 * Version: 1.0.0
 * Author: Muhammad Zubair
 * Author URI: https://codewithzubi.com
 * Text Domain: zee-database-backup
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class EasyDBBackup {
    private $plugin_path;
    private $backup_dir;
    private $backup_url;
    
    public function __construct() {
        $this->plugin_path = plugin_dir_path(__FILE__);
        $this->backup_dir = $this->plugin_path . 'assets/backups/';
        $this->backup_url = plugin_dir_url(__FILE__) . 'assets/backups/';
        
        // Create backup directory if it doesn't exist
        if (!file_exists($this->backup_dir)) {
            wp_mkdir_p($this->backup_dir);
            
            // Create .htaccess file to protect backups
            $htaccess = "deny from all";
            file_put_contents($this->backup_dir . '.htaccess', $htaccess);
        }
        
        // Register admin menu
        add_action('admin_menu', array($this, 'register_admin_menu'));
        
        // Register admin styles and scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Register AJAX actions
        add_action('wp_ajax_easy_db_backup_create', array($this, 'create_backup'));
        add_action('wp_ajax_easy_db_backup_delete', array($this, 'delete_backup'));
    }
    
    public function register_admin_menu() {
        add_menu_page(
            __('Zee DB Backup', 'easy-db-backup'),
            __('Zee DB Backup', 'easy-db-backup'),
            'manage_options',
            'easy-db-backup',
            array($this, 'admin_page'),
            'dashicons-database-export',
            100
        );
    }
    
    public function enqueue_admin_assets($hook) {
        if ($hook != 'toplevel_page_easy-db-backup') {
            return;
        }
        
        // Enqueue styles
        wp_enqueue_style('easy-db-backup-admin', plugin_dir_url(__FILE__) . 'assets/css/admin.css', array(), '1.0.0');
        
        // Enqueue scripts
        wp_enqueue_script('easy-db-backup-admin', plugin_dir_url(__FILE__) . 'assets/js/admin.js', array('jquery'), '1.0.0', true);
        
        // Localize script
        wp_localize_script('easy-db-backup-admin', 'easyDbBackup', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('easy-db-backup-nonce'),
            'creating_backup' => __('Creating backup...', 'easy-db-backup'),
            'backup_created' => __('Backup created successfully!', 'easy-db-backup'),
            'backup_failed' => __('Backup failed. Please try again.', 'easy-db-backup'),
            'confirm_delete' => __('Are you sure you want to delete this backup?', 'easy-db-backup'),
            'deleting' => __('Deleting...', 'easy-db-backup'),
            'deleted' => __('Backup deleted successfully!', 'easy-db-backup'),
            'delete_failed' => __('Delete failed. Please try again.', 'easy-db-backup')
        ));
    }
    
    public function admin_page() {
        ?>
        <div class="wrap easy-db-backup-wrap">
            <h1><?php _e('Zee DB Backup', 'easy-db-backup'); ?></h1>
            
            <div class="easy-db-backup-card">
                <div class="easy-db-backup-card-header">
                    <h2><?php _e('Create Backup', 'easy-db-backup'); ?></h2>
                </div>
                <div class="easy-db-backup-card-body">
                    <p><?php _e('Click the button below to create a new backup of your WordPress database.', 'easy-db-backup'); ?></p>
                    <button id="easy-db-backup-create" class="button button-primary">
                        <span class="dashicons dashicons-database-export"></span>
                        <?php _e('Create Backup', 'easy-db-backup'); ?>
                    </button>
                    <div id="easy-db-backup-create-status" class="easy-db-backup-status"></div>
                </div>
            </div>
            
            <div class="easy-db-backup-card">
                <div class="easy-db-backup-card-header">
                    <h2><?php _e('Backup History', 'easy-db-backup'); ?></h2>
                </div>
                <div class="easy-db-backup-card-body">
                    <div id="easy-db-backup-list">
                        <?php $this->display_backups(); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function display_backups() {
        $backups = $this->get_backups();
        
        if (empty($backups)) {
            echo '<p class="easy-db-backup-no-backups">' . __('No backups found.', 'easy-db-backup') . '</p>';
            return;
        }
        
        echo '<table class="wp-list-table widefat fixed striped easy-db-backup-table">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . __('Filename', 'easy-db-backup') . '</th>';
        echo '<th>' . __('Size', 'easy-db-backup') . '</th>';
        echo '<th>' . __('Date', 'easy-db-backup') . '</th>';
        echo '<th>' . __('Actions', 'easy-db-backup') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($backups as $backup) {
            echo '<tr data-backup="' . esc_attr($backup['filename']) . '">';
            echo '<td>' . esc_html($backup['filename']) . '</td>';
            echo '<td>' . esc_html($backup['size']) . '</td>';
            echo '<td>' . esc_html($backup['date']) . '</td>';
            echo '<td class="easy-db-backup-actions">';
            echo '<a href="' . esc_url($backup['download_url']) . '" class="button button-small easy-db-backup-download"><span class="dashicons dashicons-download"></span> ' . __('Download', 'easy-db-backup') . '</a>';
            echo '<button class="button button-small easy-db-backup-delete"><span class="dashicons dashicons-trash"></span> ' . __('Delete', 'easy-db-backup') . '</button>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
    }
    
    public function get_backups() {
        $backups = array();
        
        if (!file_exists($this->backup_dir)) {
            return $backups;
        }
        
        $files = scandir($this->backup_dir);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || $file === '.htaccess' || !is_file($this->backup_dir . $file)) {
                continue;
            }
            
            if (pathinfo($file, PATHINFO_EXTENSION) !== 'sql') {
                continue;
            }
            
            $file_path = $this->backup_dir . $file;
            $file_size = size_format(filesize($file_path));
            $file_date = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), filemtime($file_path));
            
            $backups[] = array(
                'filename' => $file,
                'size' => $file_size,
                'date' => $file_date,
                'download_url' => wp_nonce_url(admin_url('admin-ajax.php?action=easy_db_backup_download&file=' . urlencode($file)), 'easy_db_backup_download_' . $file)
            );
        }
        
        // Sort backups by date (newest first)
        usort($backups, function($a, $b) {
            $file_a = $this->backup_dir . $a['filename'];
            $file_b = $this->backup_dir . $b['filename'];
            return filemtime($file_b) - filemtime($file_a);
        });
        
        return $backups;
    }
    
    public function create_backup() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'easy-db-backup-nonce')) {
            wp_send_json_error(__('Security check failed.', 'easy-db-backup'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission to perform this action.', 'easy-db-backup'));
        }
        
        // Get database credentials from wp-config.php
        $db_host = DB_HOST;
        $db_name = DB_NAME;
        $db_user = DB_USER;
        $db_pass = DB_PASSWORD;
        
        // Create backup filename
        $date = date('Y-m-d-H-i-s');
        $filename = 'backup-' . $db_name . '-' . $date . '.sql';
        $file_path = $this->backup_dir . $filename;
        
        // Use mysqldump if available
        $mysqldump_path = $this->find_mysqldump();
        
        if ($mysqldump_path) {
            // Create backup using mysqldump
            $command = sprintf(
                '%s --host=%s --user=%s --password=%s %s > %s',
                escapeshellarg($mysqldump_path),
                escapeshellarg($db_host),
                escapeshellarg($db_user),
                escapeshellarg($db_pass),
                escapeshellarg($db_name),
                escapeshellarg($file_path)
            );
            
            $output = array();
            $return_var = 0;
            exec($command, $output, $return_var);
            
            if ($return_var !== 0) {
                wp_send_json_error(__('Failed to create database backup using mysqldump.', 'easy-db-backup'));
            }
        } else {
            // Create backup using PHP
            $backup = $this->backup_database_php($db_host, $db_name, $db_user, $db_pass);
            
            if (!$backup) {
                wp_send_json_error(__('Failed to create database backup.', 'easy-db-backup'));
            }
            
            file_put_contents($file_path, $backup);
        }
        
        // Check if backup file was created successfully
        if (!file_exists($file_path)) {
            wp_send_json_error(__('Failed to create backup file.', 'easy-db-backup'));
        }
        
        // Return success response with updated backup list
        ob_start();
        $this->display_backups();
        $backups_html = ob_get_clean();
        
        wp_send_json_success(array(
            'message' => __('Backup created successfully!', 'easy-db-backup'),
            'backups_html' => $backups_html
        ));
    }
    
    public function delete_backup() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'easy-db-backup-nonce')) {
            wp_send_json_error(__('Security check failed.', 'easy-db-backup'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission to perform this action.', 'easy-db-backup'));
        }
        
        // Check if filename is provided
        if (!isset($_POST['filename']) || empty($_POST['filename'])) {
            wp_send_json_error(__('No backup file specified.', 'easy-db-backup'));
        }
        
        $filename = sanitize_file_name($_POST['filename']);
        $file_path = $this->backup_dir . $filename;
        
        // Check if file exists
        if (!file_exists($file_path)) {
            wp_send_json_error(__('Backup file not found.', 'easy-db-backup'));
        }
        
        // Delete file
        if (!unlink($file_path)) {
            wp_send_json_error(__('Failed to delete backup file.', 'easy-db-backup'));
        }
        
        // Return success response with updated backup list
        ob_start();
        $this->display_backups();
        $backups_html = ob_get_clean();
        
        wp_send_json_success(array(
            'message' => __('Backup deleted successfully!', 'easy-db-backup'),
            'backups_html' => $backups_html
        ));
    }
    
    public function find_mysqldump() {
        // Common paths for mysqldump
        $paths = array(
            'mysqldump',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            '/usr/local/mysql/bin/mysqldump',
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 5.7\\bin\\mysqldump.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe'
        );
        
        foreach ($paths as $path) {
            $output = array();
            $return_var = 0;
            exec("$path --version 2>&1", $output, $return_var);
            
            if ($return_var === 0) {
                return $path;
            }
        }
        
        return false;
    }
    
    public function backup_database_php($host, $db_name, $user, $pass) {
        try {
            // Connect to database
            $mysqli = new mysqli($host, $user, $pass, $db_name);
            
            if ($mysqli->connect_error) {
                return false;
            }
            
            $mysqli->set_charset('utf8');
            
            // Get all tables
            $tables = array();
            $result = $mysqli->query('SHOW TABLES');
            
            while ($row = $result->fetch_row()) {
                $tables[] = $row[0];
            }
            
            $output = "-- Easy DB Backup\n";
            $output .= "-- Database: $db_name\n";
            $output .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
            $output .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
            $output .= "SET time_zone = \"+00:00\";\n\n";
            
            // Process each table
            foreach ($tables as $table) {
                $output .= "-- Table structure for table `$table`\n";
                $output .= "DROP TABLE IF EXISTS `$table`;\n";
                
                $result = $mysqli->query("SHOW CREATE TABLE `$table`");
                $row = $result->fetch_row();
                $output .= $row[1] . ";\n\n";
                
                $output .= "-- Dumping data for table `$table`\n";
                
                $result = $mysqli->query("SELECT * FROM `$table`");
                $column_count = $result->field_count;
                
                while ($row = $result->fetch_row()) {
                    $output .= "INSERT INTO `$table` VALUES (";
                    
                    for ($i = 0; $i < $column_count; $i++) {
                        if (isset($row[$i])) {
                            $value = $row[$i];
                            $value = str_replace('\\', '\\\\', $value);
                            $value = str_replace('\'', '\\\'', $value);
                            $output .= "'" . $value . "'";
                        } else {
                            $output .= "NULL";
                        }
                        
                        if ($i < ($column_count - 1)) {
                            $output .= ",";
                        }
                    }
                    
                    $output .= ");\n";
                }
                
                $output .= "\n\n";
            }
            
            $mysqli->close();
            
            return $output;
        } catch (Exception $e) {
            return false;
        }
    }
}

// Initialize plugin
add_action('plugins_loaded', function() {
    new EasyDBBackup();
});

// Handle download request
add_action('wp_ajax_easy_db_backup_download', function() {
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have permission to perform this action.', 'easy-db-backup'));
    }
    
    // Check if file parameter is provided
    if (!isset($_GET['file']) || empty($_GET['file'])) {
        wp_die(__('No backup file specified.', 'easy-db-backup'));
    }
    
    // Verify nonce
    $file = sanitize_file_name($_GET['file']);
    check_admin_referer('easy_db_backup_download_' . $file);
    
    // Get backup directory
    $backup_dir = plugin_dir_path(__FILE__) . 'assets/backups/';
    $file_path = $backup_dir . $file;
    
    // Check if file exists
    if (!file_exists($file_path)) {
        wp_die(__('Backup file not found.', 'easy-db-backup'));
    }
    
    // Set headers for download
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file_path));
    
    // Clear output buffer
    ob_clean();
    flush();
    
    // Read file and output
    readfile($file_path);
    exit;
});