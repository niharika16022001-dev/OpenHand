# AI Job Search Board WordPress Plugin

A comprehensive WordPress plugin that creates an intelligent job search board powered by AI for better job matching and candidate recommendations.

## Features

### 🤖 AI-Powered Matching
- Intelligent job-candidate matching using AI algorithms
- Personalized job recommendations based on user profiles
- Skills-based matching with scoring system
- Integration with OpenAI API for enhanced matching

### 💼 Job Management
- Complete job posting and management system
- Custom job post type with detailed meta fields
- Job categories, types, and experience levels
- Salary ranges and location-based filtering
- Featured jobs and expiration dates
- Remote work options

### 👥 User Profiles
- Comprehensive candidate profiles
- Skills, experience, and education tracking
- Portfolio and social media links
- Salary expectations and availability
- AI profile generation for better matching

### 📊 Application System
- Easy job application process
- Application status tracking
- Cover letter and resume management
- Email notifications for status updates
- Bulk application management for employers

### 📈 Analytics & Reporting
- Job view tracking and analytics
- Application statistics and trends
- Popular jobs and search terms
- Candidate engagement metrics
- Admin dashboard with insights

### 🎨 Frontend Features
- Responsive job search interface
- Advanced filtering and search
- AJAX-powered job listings
- Modal-based application forms
- Mobile-friendly design

## Installation

1. Download the plugin files
2. Upload to your WordPress `/wp-content/plugins/` directory
3. Activate the plugin through the WordPress admin
4. Configure settings in **AI Job Board > Settings**
5. Create job listing pages using the provided shortcodes

## Shortcodes

### Job Listings
```
[ajsb_job_listings limit="10" show_search="true" show_pagination="true"]
```

### Job Search Form
```
[ajsb_job_search]
```

### User Profile
```
[ajsb_user_profile]
```

### User Applications
```
[ajsb_user_applications]
```

### AI Recommendations
```
[ajsb_ai_recommendations limit="5"]
```

### Job Statistics
```
[ajsb_job_stats show="total,active,applications,candidates"]
```

## Configuration

### Basic Settings
- Jobs per page
- Registration requirements
- Email notifications
- Default job expiration

### AI Settings
- Enable/disable AI matching
- AI provider selection (OpenAI)
- API key configuration
- Matching algorithm weights

## Database Tables

The plugin creates the following custom tables:

- `wp_ajsb_jobs` - Job listings
- `wp_ajsb_applications` - Job applications
- `wp_ajsb_user_profiles` - User profiles
- `wp_ajsb_job_views` - Job view tracking
- `wp_ajsb_ai_recommendations` - AI recommendations

## API Integration

### OpenAI Integration
Configure your OpenAI API key in the settings to enable:
- Enhanced job-candidate matching
- Intelligent skill extraction
- Automated job categorization
- Personalized recommendations

## Customization

### Templates
Override plugin templates by copying them to your theme:
```
/wp-content/themes/your-theme/ajsb-templates/
```

### Hooks and Filters
The plugin provides numerous hooks for customization:

#### Actions
- `ajsb_job_created` - After job creation
- `ajsb_application_submitted` - After application submission
- `ajsb_profile_updated` - After profile update

#### Filters
- `ajsb_job_search_args` - Modify job search parameters
- `ajsb_match_score_weights` - Customize AI matching weights
- `ajsb_email_templates` - Customize email templates

### CSS Customization
Add custom styles to override default styling:
```css
.ajsb-job-card {
    /* Your custom styles */
}
```

## Requirements

- WordPress 5.0+
- PHP 7.4+
- MySQL 5.6+
- cURL extension (for AI API calls)

## File Structure

```
ai-job-search-board/
├── ai-job-search-board.php          # Main plugin file
├── includes/                        # Core functionality
│   ├── class-ajsb-database.php     # Database management
│   ├── class-ajsb-job.php          # Job management
│   ├── class-ajsb-application.php  # Application handling
│   ├── class-ajsb-user-profile.php # User profiles
│   ├── class-ajsb-ai-engine.php    # AI matching engine
│   ├── class-ajsb-ajax.php         # AJAX handlers
│   └── class-ajsb-shortcodes.php   # Shortcode handlers
├── admin/                           # Admin interface
│   └── class-ajsb-admin.php        # Admin functionality
├── public/                          # Frontend functionality
│   └── class-ajsb-frontend.php     # Frontend handlers
├── assets/                          # Static assets
│   ├── css/                        # Stylesheets
│   │   ├── frontend.css            # Frontend styles
│   │   └── admin.css               # Admin styles
│   └── js/                         # JavaScript files
│       ├── frontend.js             # Frontend scripts
│       └── admin.js                # Admin scripts
├── templates/                       # Template files
├── languages/                       # Translation files
└── README.md                        # Documentation
```

## Development

### Setting up Development Environment
1. Clone the repository
2. Install WordPress development environment
3. Activate the plugin
4. Configure AI API keys for testing

### Contributing
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## Security

- All user inputs are sanitized and validated
- CSRF protection on all forms
- Capability checks for admin functions
- SQL injection prevention
- XSS protection

## Performance

- Efficient database queries with proper indexing
- AJAX loading for better user experience
- Caching for AI recommendations
- Optimized asset loading
- Database query optimization

## Troubleshooting

### Common Issues

**Jobs not displaying:**
- Check if the jobs table was created properly
- Verify shortcode syntax
- Ensure jobs have 'active' status

**AI matching not working:**
- Verify API key configuration
- Check AI provider settings
- Ensure user profiles are complete

**Application emails not sending:**
- Check WordPress email configuration
- Verify email notification settings
- Test with SMTP plugin if needed

### Debug Mode
Enable WordPress debug mode to see detailed error messages:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Changelog

### Version 1.0.0
- Initial release
- Core job board functionality
- AI-powered matching system
- User profiles and applications
- Admin dashboard and analytics
- Responsive frontend design

## License

This plugin is licensed under the GPL v2 or later.

## Support

For support and documentation, please visit:
- Plugin documentation
- GitHub issues
- WordPress.org support forums

## Credits

Developed by the OpenHand Team
- AI matching algorithms
- Responsive design
- WordPress best practices
- Security implementation