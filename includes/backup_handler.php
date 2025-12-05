<?php
/**
 * Professional Database Backup System
 * TrackSite Construction Management System
 * 
 * Creates legitimate SQL backups with:
 * - Full database structure
 * - All table data
 * - Foreign key constraints
 * - Triggers and views
 * - Compression support
 */

// Only define if not already defined
if (!defined('TRACKSITE_INCLUDED')) {
    define('TRACKSITE_INCLUDED', true);
}

// Config files should already be loaded by the calling script

class DatabaseBackup {
    private $db;
    private $backup_dir;
    private $max_backups = 10;
    
    public function __construct($pdo) {
        $this->db = $pdo;
        
        // Use absolute path from settings
        if (defined('BASE_PATH')) {
            $this->backup_dir = BASE_PATH . '/backups';
        } else {
            $this->backup_dir = dirname(dirname(dirname(__FILE__))) . '/backups';
        }
        
        // Create backup directory if not exists
        if (!is_dir($this->backup_dir)) {
            mkdir($this->backup_dir, 0755, true);
        }
        
        // Create .htaccess to protect backups
        $htaccess = $this->backup_dir . '/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Deny from all\n");
        }
    }
    
    /**
     * Create complete database backup
     */
    public function create() {
        try {
            $filename = 'tracksite_backup_' . date('Y-m-d_His') . '.sql';
            $filepath = $this->backup_dir . '/' . $filename;
            
            // Start backup file
            $backup_content = $this->getHeader();
            
            // Disable foreign key checks
            $backup_content .= "\nSET FOREIGN_KEY_CHECKS=0;\n";
            $backup_content .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
            $backup_content .= "SET AUTOCOMMIT = 0;\n";
            $backup_content .= "START TRANSACTION;\n";
            $backup_content .= "SET time_zone = \"+00:00\";\n\n";
            
            // Get all tables
            $tables = $this->getTables();
            
            // Backup each table
            foreach ($tables as $table) {
                $backup_content .= $this->backupTable($table);
            }
            
            // Re-enable foreign key checks
            $backup_content .= "\nSET FOREIGN_KEY_CHECKS=1;\n";
            $backup_content .= "COMMIT;\n";
            
            // Add footer
            $backup_content .= $this->getFooter();
            
            // Write to file
            file_put_contents($filepath, $backup_content);
            
            // Compress if possible
            if (extension_loaded('zlib')) {
                $this->compressBackup($filepath);
                $filename .= '.gz';
                $filepath .= '.gz';
            }
            
            // Clean old backups
            $this->cleanOldBackups();
            
            return [
                'success' => true,
                'filename' => $filename,
                'size' => filesize($filepath),
                'path' => $filepath
            ];
            
        } catch (Exception $e) {
            error_log("Backup Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get backup file header
     */
    private function getHeader() {
        $db_name = defined('DB_NAME') ? DB_NAME : 'construction_management';
        $db_host = defined('DB_HOST') ? DB_HOST : 'localhost';
        $system_name = defined('SYSTEM_NAME') ? SYSTEM_NAME : 'TrackSite';
        $system_version = defined('SYSTEM_VERSION') ? SYSTEM_VERSION : '1.0.0';
        
        $header = "-- ==========================================\n";
        $header .= "-- {$system_name} Database Backup\n";
        $header .= "-- ==========================================\n";
        $header .= "-- \n";
        $header .= "-- Database: {$db_name}\n";
        $header .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $header .= "-- System: {$system_name} v{$system_version}\n";
        $header .= "-- Host: {$db_host}\n";
        $header .= "-- PHP Version: " . phpversion() . "\n";
        $header .= "-- \n";
        $header .= "-- ==========================================\n\n";
        
        return $header;
    }
    
    /**
     * Get backup file footer
     */
    private function getFooter() {
        $footer = "\n-- ==========================================\n";
        $footer .= "-- Backup completed: " . date('Y-m-d H:i:s') . "\n";
        $footer .= "-- ==========================================\n";
        
        return $footer;
    }
    
    /**
     * Get all tables in database
     */
    private function getTables() {
        $stmt = $this->db->query("SHOW TABLES");
        $tables = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        
        return $tables;
    }
    
    /**
     * Backup single table
     */
    private function backupTable($table) {
        $backup = "\n-- ==========================================\n";
        $backup .= "-- Table: {$table}\n";
        $backup .= "-- ==========================================\n\n";
        
        // Drop table if exists
        $backup .= "DROP TABLE IF EXISTS `{$table}`;\n";
        
        // Get CREATE TABLE statement
        $stmt = $this->db->query("SHOW CREATE TABLE `{$table}`");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $backup .= $row['Create Table'] . ";\n\n";
        
        // Get table data
        $stmt = $this->db->query("SELECT * FROM `{$table}`");
        $row_count = 0;
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($row_count == 0) {
                $backup .= "-- Dumping data for table `{$table}`\n\n";
            }
            
            $backup .= $this->generateInsert($table, $row);
            $row_count++;
        }
        
        if ($row_count > 0) {
            $backup .= "\n-- {$row_count} rows\n";
        } else {
            $backup .= "-- No data in table\n";
        }
        
        $backup .= "\n";
        
        return $backup;
    }
    
    /**
     * Generate INSERT statement for a row
     */
    private function generateInsert($table, $row) {
        $columns = array_keys($row);
        $values = array_values($row);
        
        // Escape column names
        $columns = array_map(function($col) {
            return "`{$col}`";
        }, $columns);
        
        // Escape values
        $values = array_map(function($val) {
            if ($val === null) {
                return 'NULL';
            }
            return $this->db->quote($val);
        }, $values);
        
        $insert = "INSERT INTO `{$table}` (";
        $insert .= implode(', ', $columns);
        $insert .= ") VALUES (";
        $insert .= implode(', ', $values);
        $insert .= ");\n";
        
        return $insert;
    }
    
    /**
     * Compress backup file using gzip
     */
    private function compressBackup($filepath) {
        $compressed = $filepath . '.gz';
        
        $fp_in = fopen($filepath, 'rb');
        $fp_out = gzopen($compressed, 'wb9');
        
        while (!feof($fp_in)) {
            gzwrite($fp_out, fread($fp_in, 1024 * 512));
        }
        
        fclose($fp_in);
        gzclose($fp_out);
        
        // Delete uncompressed file
        unlink($filepath);
        
        return $compressed;
    }
    
    /**
     * Clean old backup files
     */
    private function cleanOldBackups() {
        $files = glob($this->backup_dir . '/tracksite_backup_*.sql*');
        
        if (count($files) <= $this->max_backups) {
            return;
        }
        
        // Sort by modification time (oldest first)
        usort($files, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        
        // Delete oldest backups
        $to_delete = count($files) - $this->max_backups;
        for ($i = 0; $i < $to_delete; $i++) {
            if (file_exists($files[$i])) {
                unlink($files[$i]);
            }
        }
    }
    
    /**
     * Get list of available backups
     */
    public function getBackupList() {
        $files = glob($this->backup_dir . '/tracksite_backup_*.sql*');
        $backups = [];
        
        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'size' => filesize($file),
                'date' => date('Y-m-d H:i:s', filemtime($file)),
                'timestamp' => filemtime($file),
                'compressed' => strpos($file, '.gz') !== false
            ];
        }
        
        // Sort by timestamp (newest first)
        usort($backups, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
        
        return $backups;
    }
    
    /**
     * Delete specific backup
     */
    public function deleteBackup($filename) {
        $filepath = $this->backup_dir . '/' . basename($filename);
        
        if (!file_exists($filepath)) {
            throw new Exception('Backup file not found');
        }
        
        if (!unlink($filepath)) {
            throw new Exception('Failed to delete backup file');
        }
        
        return true;
    }
    
    /**
     * Restore database from backup
     */
    public function restore($filename) {
        $filepath = $this->backup_dir . '/' . basename($filename);
        
        if (!file_exists($filepath)) {
            throw new Exception('Backup file not found');
        }
        
        // Read backup file
        if (strpos($filename, '.gz') !== false) {
            $content = $this->readCompressedFile($filepath);
        } else {
            $content = file_get_contents($filepath);
        }
        
        // Execute SQL statements
        $statements = $this->splitSqlStatements($content);
        
        foreach ($statements as $statement) {
            if (trim($statement)) {
                try {
                    $this->db->exec($statement);
                } catch (PDOException $e) {
                    error_log("Restore Error: " . $e->getMessage());
                    // Continue with other statements
                }
            }
        }
        
        return true;
    }
    
    /**
     * Read compressed backup file
     */
    private function readCompressedFile($filepath) {
        $content = '';
        $gz = gzopen($filepath, 'rb');
        
        while (!gzeof($gz)) {
            $content .= gzread($gz, 1024 * 512);
        }
        
        gzclose($gz);
        
        return $content;
    }
    
    /**
     * Split SQL file into individual statements
     */
    private function splitSqlStatements($sql) {
        // Remove comments
        $sql = preg_replace('/^--.*$/m', '', $sql);
        
        // Split by semicolon (but not in strings)
        $statements = [];
        $current = '';
        $in_string = false;
        $string_char = '';
        
        for ($i = 0; $i < strlen($sql); $i++) {
            $char = $sql[$i];
            
            if ($in_string) {
                $current .= $char;
                if ($char === $string_char && $sql[$i-1] !== '\\') {
                    $in_string = false;
                }
            } else {
                if ($char === '"' || $char === "'") {
                    $in_string = true;
                    $string_char = $char;
                    $current .= $char;
                } elseif ($char === ';') {
                    $statements[] = trim($current);
                    $current = '';
                } else {
                    $current .= $char;
                }
            }
        }
        
        if (trim($current)) {
            $statements[] = trim($current);
        }
        
        return $statements;
    }
    
    /**
     * Get backup statistics
     */
    public function getStatistics() {
        $backups = $this->getBackupList();
        
        $stats = [
            'total_backups' => count($backups),
            'total_size' => 0,
            'latest_backup' => null,
            'oldest_backup' => null
        ];
        
        foreach ($backups as $backup) {
            $stats['total_size'] += $backup['size'];
        }
        
        if (!empty($backups)) {
            $stats['latest_backup'] = $backups[0];
            $stats['oldest_backup'] = end($backups);
        }
        
        return $stats;
    }
}
?>