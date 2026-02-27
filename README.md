# WordPress Export Import Plugin

A simple, reliable WordPress plugin for exporting and importing complete WordPress sites including database, plugins, themes, uploads, and users.

## Features

- **Full Site Export**: Export your entire WordPress site as a single ZIP file
- **Complete Restore**: Import and restore all site data with one click
- **Database Included**: Exports all WordPress tables with data
- **Media Files**: Includes all uploads, themes, and plugins
- **User Data**: Preserves all users and their roles
- **WooCommerce Compatible**: Exports products, orders, and customer data
- **Shared Hosting Friendly**: Optimized for servers with limited resources

## Requirements

- WordPress 5.0 or higher
- PHP 7.2.24 or higher
- ZipArchive PHP extension
- Sufficient disk space (at least 100MB free)
- Administrator access

## Installation

1. Download the plugin ZIP file
2. Upload to `/wp-content/plugins/` directory
3. Extract the files
4. Activate the plugin through the 'Plugins' menu in WordPress
5. Navigate to **Tools > Export/Import** to use the plugin

## Usage

### Exporting a Site

1. Go to **Tools > Export/Import** in your WordPress admin
2. Click the **Export Site** button
3. Wait for the export to complete (may take several minutes for large sites)
4. Your browser will download a ZIP file named `wordpress-export-YYYY-MM-DD-HHMMSS.zip`
5. Save this file securely

### Importing a Site

⚠️ **CRITICAL WARNING**: Import will **COMPLETELY REPLACE** all existing data on the destination site!

**Before importing:**
1. **Always export your destination site first** as a backup
2. Ensure you have enough disk space
3. Verify the import file is valid and uncorrupted

**Import steps:**
1. Go to **Tools > Export/Import** on the destination WordPress site
2. Click **Choose File** and select your export ZIP file
3. Click **Import Site**
4. Wait for the import to complete (do not close your browser)
5. You will be logged out - log back in with credentials from the source site

### URL Changes

If the destination site has a different URL than the source:
- The plugin automatically updates `siteurl` and `home` options
- Serialized data in the database is updated with search/replace
- You may need to flush permalinks: **Settings > Permalinks > Save Changes**

## What Gets Exported/Imported

### Database
- All WordPress core tables
- Custom tables with WordPress prefix
- All data including posts, pages, users, options, etc.

### Files
- `/wp-content/plugins/` - All plugins
- `/wp-content/themes/` - All themes  
- `/wp-content/uploads/` - All media files
- `/wp-content/mu-plugins/` - Must-use plugins (if present)

### What's NOT Included
- WordPress core files (wp-admin, wp-includes)
- `wp-config.php` (database credentials remain unchanged)
- `.htaccess` file
- Other files outside `wp-content`

## Troubleshooting

### Export Issues

**"ZipArchive extension not available"**
- Contact your hosting provider to enable the PHP ZipArchive extension

**"Insufficient disk space"**
- Free up space in your uploads directory
- Delete old backup files

**Export times out**
- Large sites may take 5-10 minutes
- If it fails, try on a server with higher limits
- Contact your host about increasing PHP execution time

### Import Issues

**"File upload failed"**
- Check PHP upload_max_filesize and post_max_size settings
- For large exports, increase these limits in php.ini
- Or use FTP to upload the ZIP to wp-content/uploads/wei-temp/

**"Cannot extract ZIP file"**
- Verify the ZIP file is not corrupted
- Ensure sufficient disk space on destination

**Site is broken after import**
- Check that WordPress core files are intact
- Verify file permissions (755 for directories, 644 for files)
- Clear browser cache and try logging in with source site credentials

**Database errors during import**
- Ensure MySQL user has sufficient privileges (DROP, CREATE, INSERT)
- Check MySQL max_allowed_packet size for large databases

## Best Practices

1. **Test First**: Always test on a staging site before production
2. **Backup Everything**: Export both source and destination before import
3. **Check Requirements**: Verify PHP version and extensions
4. **Monitor Resources**: Watch disk space and memory during operations
5. **Fresh Install**: Import works best on a fresh WordPress installation
6. **Keep Exports Secure**: Export files contain your entire site including passwords

## Limitations

- **Single Site Only**: Does not support WordPress Multisite
- **No Incremental Backup**: Each export is a complete snapshot
- **No Scheduling**: Manual operation only
- **Full Replace Only**: Cannot merge with existing content
- **Server Limits**: Subject to PHP memory and execution time limits

## Security

- Only administrators can export/import
- All operations use WordPress nonces for CSRF protection
- Temporary files are stored in protected upload directory
- Files are automatically cleaned up after operations

## Support

For issues, questions, or contributions:
- GitHub: https://github.com/Jasoncheery/wordpress-export-import
- Create an issue with detailed information about your problem

## License

GPL v2 or later

## Changelog

### 1.0.0
- Initial release
- Full site export to ZIP
- Full site import with database restore
- Automatic URL updating
- Shared hosting optimizations
