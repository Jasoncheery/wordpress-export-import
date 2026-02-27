# Testing Guide

This document provides a comprehensive testing plan for the WordPress Export Import plugin.

## Prerequisites

Before testing, ensure you have:
- Two WordPress installations (source and destination)
- Administrator access to both sites
- At least 500MB free disk space on each
- PHP 7.2.24+ with ZipArchive extension enabled

## Test Environment Setup

### Source Site (Site A)
1. Install WordPress 5.0 or higher
2. Create test content:
   - 5-10 posts with featured images
   - 3-5 pages
   - 2-3 users with different roles
   - Upload 10+ media files
   - Install 2-3 plugins
   - Activate a custom theme
3. If testing WooCommerce:
   - Install WooCommerce
   - Create 5+ products
   - Create 2+ test orders

### Destination Site (Site B)
1. Fresh WordPress installation
2. Same or higher WordPress version as Site A
3. Different domain/URL (to test URL migration)

## Test Cases

### TC1: Basic Export

**Objective**: Verify plugin can export a complete site

**Steps:**
1. Install and activate the plugin on Site A
2. Navigate to Tools > Export/Import
3. Click "Export Site" button
4. Wait for export to complete

**Expected Results:**
- Export completes without errors
- ZIP file downloads automatically
- Filename format: `wordpress-export-YYYY-MM-DD-HHMMSS.zip`
- ZIP file size is reasonable (check it's not 0 bytes)

**Pass/Fail:** ___________

---

### TC2: Export Contents Verification

**Objective**: Verify export ZIP contains all required files

**Steps:**
1. Extract the downloaded ZIP file
2. Examine the contents

**Expected Results:**
- `manifest.json` exists and contains:
  - version, timestamp, site_url, wp_version
  - table_prefix, active_plugins, active_theme
- `database.sql` exists and is not empty
- `wp-content/plugins/` directory exists
- `wp-content/themes/` directory exists
- `wp-content/uploads/` directory exists
- All files from source site are present

**Pass/Fail:** ___________

---

### TC3: Basic Import (Same URL)

**Objective**: Import to a site with the same URL

**Steps:**
1. Create a backup of Site A
2. Delete some content from Site A
3. Import the backup ZIP on Site A
4. Log in with original credentials

**Expected Results:**
- Import completes successfully
- All deleted content is restored
- Site functions normally
- No broken links or missing images

**Pass/Fail:** ___________

---

### TC4: Import with URL Migration

**Objective**: Import to a site with different URL

**Steps:**
1. Install and activate plugin on Site B (different URL)
2. Navigate to Tools > Export/Import
3. Upload the export ZIP from Site A
4. Click "Import Site"
5. Wait for import to complete
6. Log in using Site A credentials

**Expected Results:**
- Import completes without errors
- Can log in with Site A admin credentials
- All content is present (posts, pages, media)
- All plugins are present (but may need reactivation)
- Theme is applied correctly
- URLs are updated to Site B's domain
- Internal links work correctly
- Images display properly

**Pass/Fail:** ___________

---

### TC5: Database Verification

**Objective**: Verify database is completely restored

**Steps:**
1. After import on Site B, check:
   - Posts count matches Site A
   - Pages count matches Site A
   - Users count matches Site A
   - Comments count matches Site A
2. Verify user roles are preserved
3. Check plugin settings are preserved
4. Verify theme customizer settings

**Expected Results:**
- All database tables are present
- All data matches source site
- No data corruption
- Settings are preserved

**Pass/Fail:** ___________

---

### TC6: File System Verification

**Objective**: Verify all files are restored

**Steps:**
1. After import, check wp-content directories:
   - Compare plugin list with Site A
   - Compare theme list with Site A
   - Check uploads directory for media files
2. Verify file permissions are correct

**Expected Results:**
- All plugins present (check wp-content/plugins/)
- All themes present (check wp-content/themes/)
- All uploads present (check wp-content/uploads/)
- Files are readable/writable as needed

**Pass/Fail:** ___________

---

### TC7: WooCommerce Data (if applicable)

**Objective**: Verify WooCommerce data is preserved

**Steps:**
1. After import, check:
   - Products count
   - Orders count
   - Customer data
   - WooCommerce settings

**Expected Results:**
- All products present with correct data
- All orders present
- Customer accounts work
- WooCommerce settings preserved

**Pass/Fail:** ___________

---

### TC8: Large Site Test

**Objective**: Test with a larger site

**Setup:**
- Site with 100+ posts
- 500+ media files
- 10+ plugins
- Database size >50MB

**Steps:**
1. Export large site
2. Import to destination

**Expected Results:**
- Export completes (may take 5-10 minutes)
- Import completes without timeout
- All data is preserved
- No memory errors

**Pass/Fail:** ___________

---

### TC9: Error Handling - Missing ZipArchive

**Objective**: Verify proper error message when ZipArchive unavailable

**Steps:**
1. On a server without ZipArchive extension
2. Try to export or import

**Expected Results:**
- Clear error message: "ZipArchive extension is required"
- No PHP fatal errors
- User can still access admin

**Pass/Fail:** ___________

---

### TC10: Error Handling - Insufficient Disk Space

**Objective**: Verify disk space checking

**Steps:**
1. On a server with <100MB free space
2. Try to export

**Expected Results:**
- Error message: "Insufficient disk space"
- Operation stops gracefully
- No partial files left behind

**Pass/Fail:** ___________

---

### TC11: Error Handling - Corrupted ZIP

**Objective**: Verify handling of invalid import files

**Steps:**
1. Create a corrupted ZIP file
2. Try to import it

**Expected Results:**
- Error message about invalid ZIP
- Site remains functional
- No database corruption

**Pass/Fail:** ___________

---

### TC12: Maintenance Mode

**Objective**: Verify maintenance mode during import

**Steps:**
1. Start import process
2. In another browser, try to access the site
3. Wait for import to complete
4. Verify site is accessible again

**Expected Results:**
- Site shows maintenance message during import
- Other users cannot access site during import
- Maintenance mode is removed after import
- Site is fully accessible after completion

**Pass/Fail:** ___________

---

### TC13: Security - Non-Admin Access

**Objective**: Verify only admins can export/import

**Steps:**
1. Log in as Editor or Subscriber
2. Try to access Tools > Export/Import

**Expected Results:**
- Menu item not visible to non-admins
- Direct URL access is blocked
- Proper permission error shown

**Pass/Fail:** ___________

---

### TC14: Cleanup Verification

**Objective**: Verify temporary files are cleaned up

**Steps:**
1. Before export, check wp-content/uploads/wei-temp/
2. Perform export
3. After export, check the directory again
4. Repeat for import

**Expected Results:**
- Temporary directories are created during operation
- All temporary files are removed after completion
- No leftover files after successful operations

**Pass/Fail:** ___________

---

### TC15: Multiple Exports

**Objective**: Verify multiple exports work correctly

**Steps:**
1. Export site
2. Add new content
3. Export again
4. Compare both exports

**Expected Results:**
- Both exports complete successfully
- Second export contains new content
- Both exports are independent
- No conflicts or errors

**Pass/Fail:** ___________

---

## Performance Benchmarks

Record the following for your test environment:

| Metric | Value |
|--------|-------|
| Site size (total) | _____ MB |
| Database size | _____ MB |
| Number of files | _____ |
| Export time | _____ seconds |
| Export ZIP size | _____ MB |
| Import time | _____ seconds |
| Peak memory usage | _____ MB |

## Known Limitations to Verify

- [ ] Does not support WordPress Multisite
- [ ] Cannot merge with existing content (full replace only)
- [ ] Does not export wp-config.php
- [ ] Does not export .htaccess
- [ ] Does not export WordPress core files

## Issues Found

Document any issues discovered during testing:

### Issue 1
**Description:**
**Steps to Reproduce:**
**Expected:**
**Actual:**
**Severity:** Critical / High / Medium / Low

---

## Test Summary

**Total Test Cases:** 15
**Passed:** _____
**Failed:** _____
**Blocked:** _____
**Not Tested:** _____

**Overall Status:** Pass / Fail / Partial

**Tester Name:** _______________
**Test Date:** _______________
**Plugin Version:** 1.0.0
**WordPress Version:** _______________
**PHP Version:** _______________
**Server Environment:** _______________

## Recommendations

Based on testing results, list any recommendations for:
1. Code improvements
2. Documentation updates
3. Feature additions
4. Bug fixes

---

## Automated Testing (Future)

For future development, consider:
- PHPUnit tests for core functions
- Integration tests with WordPress test suite
- Automated export/import cycle tests
- Performance regression tests
