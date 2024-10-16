# Datolab Auto SEO Plugin

The Datolab Auto SEO plugin enhances your WordPress site's SEO by automatically generating and associating SEO tags and categories for your posts. Additionally, it can associate related posts based on shared categories and tags.

## WP CLI Commands

This plugin adds custom WP CLI commands to automate SEO tasks and related posts association.

### Running Commands Manually

To manually run the SEO processing command, use:

```bash
wp datolab-auto-seo process
```

This command processes all draft posts to generate SEO tags and categories.

To associate related posts based on shared categories and tags, use:

```bash
wp datolab-related-posts associate
```

This command finds and associates related posts.

### Scheduling Commands with Crontab

To automate the execution of these commands, you can schedule them using crontab on your server. This requires SSH access to your server.

1. **Open Crontab Configuration**

   Open your user's crontab file by running:

   ```bash
   crontab -e
   ```

2. **Add Scheduled Tasks**

   Add lines for each WP CLI command you want to schedule. Specify the time and frequency for the command execution.

   For example, to run the SEO processing command every day at 3 AM:

   ```cron
   0 3 * * * cd /path/to/your/wordpress/installation && wp datolab-auto-seo process
   ```

   To associate related posts every day at 4 AM:

   ```cron
   0 4 * * * cd /path/to/your/wordpress/installation && wp datolab-related-posts associate
   ```

   Replace `/path/to/your/wordpress/installation` with the actual path to your WordPress installation directory.

3. **Save and Exit**

   Save the changes and exit the editor. The crontab will automatically install the new schedule.

### Notes

- Ensure that the user account running the crontab has the necessary permissions to execute WP CLI commands.
- Adjust the scheduling times and frequencies according to your needs.
- It's recommended to log the output of each command for debugging purposes. You can do this by appending `>> /path/to/log/file 2>&1` to each cron job entry.

