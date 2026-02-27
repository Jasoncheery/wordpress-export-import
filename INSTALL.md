# Installation Guide

## Quick Install

### Method 1: Direct Upload (Recommended)

1. Download or clone this repository
2. If cloned, create a ZIP file of the plugin directory
3. In WordPress admin, go to **Plugins > Add New > Upload Plugin**
4. Choose the ZIP file and click **Install Now**
5. Click **Activate Plugin**

### Method 2: Manual Installation

1. Download or clone this repository
2. Upload the `wordpress-export-import` folder to `/wp-content/plugins/`
3. In WordPress admin, go to **Plugins**
4. Find "WordPress Export Import" and click **Activate**

### Method 3: FTP Installation

1. Download or clone this repository
2. Connect to your server via FTP
3. Navigate to `/wp-content/plugins/`
4. Upload the entire `wordpress-export-import` folder
5. In WordPress admin, activate the plugin

## Verification

After installation, verify the plugin is working:

1. Go to **Tools** in WordPress admin
2. You should see **Export/Import** menu item
3. Click it to access the plugin interface

## System Requirements Check

Before using the plugin, verify your server meets the requirements:

### Required
- WordPress 5.0 or higher
- PHP 7.2.24 or higher
- ZipArchive PHP extension

### Recommended
- PHP 7.4 or higher
- 512MB PHP memory limit
- 300 seconds PHP execution time
- 100MB+ free disk space

### Check PHP Extensions

To verify ZipArchive is available, create a file called `check.php` in your WordPress root:

```php
<?php
if (class_exists('ZipArchive')) {
    echo "✓ ZipArchive is available";
} else {
    echo "✗ ZipArchive is NOT available - contact your host";
}
?>
```

Visit `yoursite.com/check.php` in your browser, then delete the file.

### Check PHP Settings

Create `phpinfo.php` in your WordPress root:

```php
<?php phpinfo(); ?>
```

Visit `yoursite.com/phpinfo.php` and check:
- **memory_limit**: Should be at least 256M (512M recommended)
- **max_execution_time**: Should be at least 60 (300 recommended)
- **upload_max_filesize**: Should be at least 64M (256M recommended)
- **post_max_size**: Should be at least 64M (256M recommended)

**Important:** Delete `phpinfo.php` after checking for security.

## Troubleshooting Installation

### Plugin doesn't appear in admin

**Possible causes:**
- Files not uploaded correctly
- Wrong directory structure
- File permissions issue

**Solution:**
1. Verify the structure is: `/wp-content/plugins/wordpress-export-import/wordpress-export-import.php`
2. Check file permissions: 755 for directories, 644 for files
3. Check WordPress error log for PHP errors

### "Plugin could not be activated because it triggered a fatal error"

**Possible causes:**
- PHP version too old
- Missing PHP extensions
- Syntax errors (unlikely in released version)

**Solution:**
1. Check PHP version: must be 7.2.24 or higher
2. Enable ZipArchive extension
3. Check error message for specific issue
4. Contact your hosting provider for assistance

### Menu item doesn't appear

**Possible causes:**
- User doesn't have admin privileges
- Plugin not fully activated
- Conflict with another plugin

**Solution:**
1. Ensure you're logged in as Administrator
2. Deactivate and reactivate the plugin
3. Try with default WordPress theme
4. Disable other plugins temporarily

## Updating the Plugin

### From GitHub

1. Download the latest version
2. Deactivate the current plugin
3. Delete the old plugin folder
4. Upload the new version
5. Activate the plugin

**Note:** Your export files are stored separately and won't be affected.

### Backup Before Update

Always export your site before updating the plugin:
1. Use the plugin to export your site
2. Save the ZIP file securely
3. Then proceed with the update

## Uninstallation

### Standard Uninstall

1. Go to **Plugins** in WordPress admin
2. Deactivate "WordPress Export Import"
3. Click **Delete**
4. Confirm deletion

### Manual Uninstall

1. Connect via FTP
2. Navigate to `/wp-content/plugins/`
3. Delete the `wordpress-export-import` folder

### Clean Uninstall

The plugin stores minimal data in WordPress:
- Transients (temporary data, auto-expires)
- No permanent options or database tables

To ensure complete cleanup:
1. Delete the plugin as above
2. Run this SQL query (optional):
```sql
DELETE FROM wp_options WHERE option_name LIKE '%wei_%';
DELETE FROM wp_options WHERE option_name LIKE '%_transient_wei_%';
```

## Post-Installation Setup

### Recommended Settings

After installation, configure your server for optimal performance:

#### PHP Settings (php.ini or .htaccess)

For **php.ini**:
```ini
memory_limit = 512M
max_execution_time = 300
upload_max_filesize = 256M
post_max_size = 256M
```

For **.htaccess** (if using Apache):
```apache
php_value memory_limit 512M
php_value max_execution_time 300
php_value upload_max_filesize 256M
php_value post_max_size 256M
```

#### WordPress Settings

No WordPress configuration changes needed. The plugin works out of the box.

### First Use

1. **Test Export**: Export a small test site first
2. **Verify ZIP**: Download and extract to verify contents
3. **Test Import**: Import to a staging site before production
4. **Backup**: Always keep backups of important exports

## Security Considerations

### File Permissions

Ensure proper permissions:
- Plugin files: 644
- Plugin directories: 755
- wp-content/uploads/: 755 (WordPress default)

### Access Control

- Only administrators can access export/import functions
- Export files contain sensitive data - store securely
- Delete old export files from downloads folder
- Use HTTPS for admin access

### Server Security

- Keep WordPress updated
- Keep PHP updated
- Use strong admin passwords
- Enable WordPress security plugins if needed

## Support

If you encounter issues during installation:

1. Check this guide's troubleshooting section
2. Review the main README.md
3. Check GitHub issues: https://github.com/Jasoncheery/wordpress-export-import/issues
4. Create a new issue with:
   - WordPress version
   - PHP version
   - Server environment
   - Error messages
   - Steps to reproduce

## Next Steps

After successful installation:

1. Read the [README.md](README.md) for usage instructions
2. Review the [TESTING.md](TESTING.md) if you want to test thoroughly
3. Create your first export to familiarize yourself with the plugin
4. Keep the export file as a backup

---

**Ready to use?** Go to **Tools > Export/Import** in your WordPress admin!
