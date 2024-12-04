# Datolab Auto SEO Plugin

The **Datolab Auto SEO** plugin enhances your WordPress site's SEO by automatically generating and associating SEO tags and categories for your posts. Additionally, it can associate related posts based on shared categories and tags, helping to improve your site's visibility and user engagement.

## Features

- Automatically generates SEO tags and categories for draft posts.
- Associates related posts based on shared categories and tags.
- Custom WP CLI commands for easy management and automation of SEO tasks.
- Comprehensive error logging and monitoring system.
- **Requires an OpenAI API Key** to function properly.

## Error Logging and Monitoring

The plugin includes a robust error logging and monitoring system that helps track and debug issues:

### Log Features
- Automatic log rotation (5MB file size limit)
- Keeps last 5 backup log files
- JSON-formatted context data
- Timestamps for all entries
- User tracking
- Email notifications for critical errors

### Log Location
Logs are stored in `wp-content/plugins/datolab-auto-seo/includes/logs/datolab-auto-seo.log`

### What's Being Logged
- API calls and responses
- Error messages and status codes
- Request and response metrics
- User actions
- System warnings and information
- Performance metrics

### Email Notifications
The system automatically sends email notifications to the WordPress admin for critical errors, including:
- API failures
- Authentication issues
- System-critical errors

## WP CLI Commands

This plugin adds custom WP CLI commands to automate SEO tasks and related posts association.

### Available Commands

1. **Process Draft Posts**
   - **Command**: 
     ```bash
     wp datolab-auto-seo process
     ```
   - **Description**: Processes all draft posts to generate SEO tags and categories.

2. **Associate Related Posts**
   - **Command**: 
     ```bash
     wp datolab-related-posts associate
     ```
   - **Description**: Finds and associates related posts based on shared categories and tags.

### Running Commands Manually

To manually run the commands, simply execute the desired command in your terminal.

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
- **OpenAI API Key**: Make sure to set your OpenAI API key in the plugin settings to enable the generation of SEO tags and categories.

## Contributing

Contributions are welcome! If you have suggestions for improvements or new features, please open an issue or submit a pull request.

## License

This plugin is licensed under the [GPL-2.0 License](https://opensource.org/licenses/GPL-2.0).

## Acknowledgments

- Thanks to the WordPress community for their continuous support and contributions.
- Special thanks to the developers of the OpenAI API for providing the tools to enhance SEO capabilities.

---

For more information, visit the [official plugin page](https://github.com/your-repo-link) or check out the [documentation](https://github.com/your-repo-link/wiki).
