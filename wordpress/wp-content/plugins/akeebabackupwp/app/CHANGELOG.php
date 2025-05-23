<?php die();?>
Akeeba Solo 8.3.0
================================================================================
~ Make accurate PHP CLI path detection optional
# [HIGH] Some OneDrive multipart uploads fail

Akeeba Solo 8.2.7
================================================================================
! Could not work with MySQL 5.x and MariaDB 10.x

Akeeba Solo 8.2.5
================================================================================
+ More accurate information about PHP CLI in the Schedule Automatic Backups page
+ Improved database dump engine
~ Option to disable PHP version checks for updates
~ Adjust the size and text on the warning about ad blockers

Akeeba Solo 8.2.4
================================================================================
+ Edit and reset the cache directory (Joomla! 5.1+) on restoration
+ Remove MariaDB MyISAM option PAGE_CHECKSUM from the database dump
~ Improve database dump with table names similar to default values
~ Change the wording of the message when navigating to an off-site directory in the directory browser
~ PHP 8.4 compatibility: MD5 and SHA-1 functions are deprecated
# [HIGH] Custom OAuth2 token refresh did not work reliably
# [MEDIUM] Tables or databases named `0` can cause the database dump to stop prematurely, or not execute at all
# [MEDIUM] Akeeba Backup CORE showed the WP-CRON link but the feature is only shipped with Professional

Akeeba Solo 8.2.3
================================================================================
+ Option to avoid using `flush()` on broken servers
# [HIGH] OAuth2 Helpers didn't work properly due to a typo in the released version

Akeeba Solo 8.2.2
================================================================================
- Remove the deprecated, ineffective CURLOPT_BINARYTRANSFER flag
+ Alternate Configuration page saving method which doesn't hit maximum POST parameter count limits
+ ShowOn in the System Configuration page
+ Self-hosted OAuth2 helpers
# [LOW] Deprecation notice in Configuration Wizard

Akeeba Solo 8.2.1
================================================================================
+ Upload to OneDrive (app-specific folder)
# [LOW] PHP error when two processes try to store update information concurrently

Akeeba Solo 8.2.0
================================================================================
! Cannot complete the setup due to an inversion of login in the Setup view
+ Expert options for the Upload to Amazon S3 configuration
+ Separate remote and local quota settings
# [MEDIUM] Clicking on Backup Now would start the backup automatically

Akeeba Solo 8.1.2
================================================================================
+ Automatically downgrade utf8mb4_900_* collations to utf8mb4_unicode_520_ci on MariaDB
+ Joomla restoration: allows you to change the robots (search engine) option
~ Change the message when the PHP or WordPress requirements are not met in available updates
~ Remove the message about the release being 120 days old

Akeeba Solo 8.1.1
================================================================================
- Removed support for Akeeba Backup JSON API v1 (APIv1)
- Removed support for the legacy Akeeba Backup JSON API endpoint (wp-content/plugins/akeebabackupwp/app/index.php)
# [MEDIUM] PHP error when adding Solo to the backup

Akeeba Solo 8.1.0
================================================================================
# [HIGH] PHP error in Manage Backups when you have pending or failed backups

Akeeba Solo 8.1.0.b1
================================================================================
# [LOW] Downgrading from Pro to Core would make it so that you always saw an update available
# [LOW] Management column show the wrong file extension for the last file you need to download

Akeeba Solo 8.0.0
================================================================================
+ Minimum PHP version is now 7.4.0
+ Using Composer to load all internal dependencies (AWF, backup engine, S3 library)
+ Workaround for Wasabi S3v4 signatures
+ Support for uploading to Shared With Me folders in Google Drive
~ Improved error reporting, removing the unhelpful "(HTML containing script tags)" message
~ Improved mixed– and upper–case database prefix support at backup time
# [MEDIUM] Resetting corrupt backups can cause a crash of the Control Panel page
# [MEDIUM] Upload to S3 would always use v2 signatures with a custom endpoint.
# [MEDIUM] Some transients need data replacements to take place in WP 6.3
