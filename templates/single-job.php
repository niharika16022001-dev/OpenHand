<?php
/**
 * Single Job Template
 * 
 * This template can be overridden by copying it to yourtheme/ajsb-templates/single-job.php
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="ajsb-single-job-container">
    <?php while (have_posts()) : the_post(); ?>
        
        <article id="post-<?php the_ID(); ?>" <?php post_class('ajsb-single-job'); ?>>
            
            <?php
            // Get job data
            $job = AJSB_Job::get(get_the_ID());
            
            if ($job): ?>
                
                <div class="ajsb-job-header">
                    <div class="ajsb-job-title-section">
                        <h1 class="ajsb-job-title"><?php echo esc_html($job->title); ?></h1>
                        
                        <div class="ajsb-job-company-info">
                            <h2 class="ajsb-company-name"><?php echo esc_html($job->company); ?></h2>
                            <div class="ajsb-job-location">
                                <i class="ajsb-icon-location"></i>
                                <?php echo esc_html($job->location); ?>
                                <?php if ($job->remote_work): ?>
                                    <span class="ajsb-remote-badge"><?php _e('Remote', 'ai-job-search-board'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($job->featured): ?>
                        <div class="ajsb-featured-badge">
                            <i class="ajsb-icon-star"></i>
                            <?php _e('Featured', 'ai-job-search-board'); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="ajsb-job-meta-info">
                    <div class="ajsb-meta-grid">
                        <div class="ajsb-meta-item">
                            <span class="ajsb-meta-label"><?php _e('Job Type', 'ai-job-search-board'); ?></span>
                            <span class="ajsb-meta-value">
                                <?php
                                $job_types = AJSB_Job::get_job_types();
                                echo esc_html($job_types[$job->job_type] ?? $job->job_type);
                                ?>
                            </span>
                        </div>
                        
                        <div class="ajsb-meta-item">
                            <span class="ajsb-meta-label"><?php _e('Experience Level', 'ai-job-search-board'); ?></span>
                            <span class="ajsb-meta-value">
                                <?php
                                $experience_levels = AJSB_Job::get_experience_levels();
                                echo esc_html($experience_levels[$job->experience_level] ?? $job->experience_level);
                                ?>
                            </span>
                        </div>
                        
                        <?php if (!empty($job->salary_min)): ?>
                            <div class="ajsb-meta-item">
                                <span class="ajsb-meta-label"><?php _e('Salary', 'ai-job-search-board'); ?></span>
                                <span class="ajsb-meta-value">
                                    <?php
                                    $salary = number_format($job->salary_min);
                                    if (!empty($job->salary_max) && $job->salary_max != $job->salary_min) {
                                        $salary .= ' - ' . number_format($job->salary_max);
                                    }
                                    echo esc_html($job->currency . ' ' . $salary);
                                    ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="ajsb-meta-item">
                            <span class="ajsb-meta-label"><?php _e('Posted', 'ai-job-search-board'); ?></span>
                            <span class="ajsb-meta-value">
                                <?php echo human_time_diff(strtotime($job->created_at)); ?> <?php _e('ago', 'ai-job-search-board'); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($job->skills)): ?>
                    <div class="ajsb-job-skills-section">
                        <h3><?php _e('Required Skills', 'ai-job-search-board'); ?></h3>
                        <div class="ajsb-skills-tags">
                            <?php
                            $skills = explode(',', $job->skills);
                            foreach ($skills as $skill):
                                ?>
                                <span class="ajsb-skill-tag"><?php echo esc_html(trim($skill)); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="ajsb-job-content">
                    <div class="ajsb-job-description">
                        <h3><?php _e('Job Description', 'ai-job-search-board'); ?></h3>
                        <div class="ajsb-description-content">
                            <?php echo wp_kses_post(wpautop($job->description)); ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($job->requirements)): ?>
                        <div class="ajsb-job-requirements">
                            <h3><?php _e('Requirements', 'ai-job-search-board'); ?></h3>
                            <div class="ajsb-requirements-content">
                                <?php echo wp_kses_post(wpautop($job->requirements)); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($job->benefits)): ?>
                        <div class="ajsb-job-benefits">
                            <h3><?php _e('Benefits', 'ai-job-search-board'); ?></h3>
                            <div class="ajsb-benefits-content">
                                <?php echo wp_kses_post(wpautop($job->benefits)); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="ajsb-application-section">
                    <?php
                    // Check if user has already applied
                    $applied = false;
                    $application = null;
                    
                    if (is_user_logged_in()) {
                        $application = AJSB_Application::get_by_job_and_user($job->id, get_current_user_id());
                        $applied = !empty($application);
                    }
                    ?>
                    
                    <?php if ($applied): ?>
                        <div class="ajsb-application-status-card">
                            <div class="ajsb-status-header">
                                <h3><?php _e('Your Application', 'ai-job-search-board'); ?></h3>
                                <span class="ajsb-application-date">
                                    <?php echo date_i18n(get_option('date_format'), strtotime($application->applied_at)); ?>
                                </span>
                            </div>
                            
                            <div class="ajsb-status-info">
                                <div class="ajsb-status-badge ajsb-status-<?php echo esc_attr($application->status); ?>">
                                    <?php
                                    $statuses = AJSB_Application::get_statuses();
                                    echo esc_html($statuses[$application->status] ?? $application->status);
                                    ?>
                                </div>
                                
                                <?php if ($application->ai_match_score): ?>
                                    <div class="ajsb-match-score-display">
                                        <span class="ajsb-score-label"><?php _e('Match Score:', 'ai-job-search-board'); ?></span>
                                        <span class="ajsb-score-value"><?php echo round($application->ai_match_score); ?>%</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($application->notes)): ?>
                                <div class="ajsb-application-notes">
                                    <strong><?php _e('Notes:', 'ai-job-search-board'); ?></strong>
                                    <p><?php echo esc_html($application->notes); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                    <?php else: ?>
                        <div class="ajsb-apply-card">
                            <?php if (is_user_logged_in()): ?>
                                
                                <?php
                                // Show AI match score if available
                                $profile = AJSB_User_Profile::get_by_user_id(get_current_user_id());
                                if ($profile) {
                                    $ai_engine = new AJSB_AI_Engine();
                                    $match_score = $ai_engine->calculate_job_match_score($job, $profile);
                                    
                                    if ($match_score > 0):
                                        ?>
                                        <div class="ajsb-ai-match-preview">
                                            <div class="ajsb-match-score-circle" data-score="<?php echo esc_attr($match_score); ?>">
                                                <span class="ajsb-score-text"><?php echo round($match_score); ?>%</span>
                                            </div>
                                            <div class="ajsb-match-info">
                                                <h4><?php _e('AI Match Score', 'ai-job-search-board'); ?></h4>
                                                <p><?php _e('Based on your profile, this job is a good match for your skills and preferences.', 'ai-job-search-board'); ?></p>
                                            </div>
                                        </div>
                                    <?php endif;
                                }
                                ?>
                                
                                <div class="ajsb-apply-actions">
                                    <button class="ajsb-btn ajsb-btn-primary ajsb-btn-large ajsb-apply-btn" data-job-id="<?php echo esc_attr($job->id); ?>">
                                        <i class="ajsb-icon-send"></i>
                                        <?php _e('Apply for this Job', 'ai-job-search-board'); ?>
                                    </button>
                                    
                                    <div class="ajsb-apply-info">
                                        <p><?php _e('Click to apply with your profile information and cover letter.', 'ai-job-search-board'); ?></p>
                                    </div>
                                </div>
                                
                            <?php else: ?>
                                <div class="ajsb-login-required">
                                    <h3><?php _e('Ready to Apply?', 'ai-job-search-board'); ?></h3>
                                    <p><?php _e('Please log in or create an account to apply for this position.', 'ai-job-search-board'); ?></p>
                                    
                                    <div class="ajsb-auth-buttons">
                                        <a href="<?php echo wp_login_url(get_permalink()); ?>" class="ajsb-btn ajsb-btn-primary">
                                            <?php _e('Log In', 'ai-job-search-board'); ?>
                                        </a>
                                        <a href="<?php echo wp_registration_url(); ?>" class="ajsb-btn ajsb-btn-secondary">
                                            <?php _e('Create Account', 'ai-job-search-board'); ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="ajsb-job-footer">
                    <div class="ajsb-share-section">
                        <h4><?php _e('Share this Job', 'ai-job-search-board'); ?></h4>
                        <div class="ajsb-share-buttons">
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(get_permalink()); ?>&text=<?php echo urlencode($job->title . ' at ' . $job->company); ?>" target="_blank" class="ajsb-share-btn ajsb-share-twitter">
                                <i class="ajsb-icon-twitter"></i>
                                <?php _e('Twitter', 'ai-job-search-board'); ?>
                            </a>
                            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode(get_permalink()); ?>" target="_blank" class="ajsb-share-btn ajsb-share-linkedin">
                                <i class="ajsb-icon-linkedin"></i>
                                <?php _e('LinkedIn', 'ai-job-search-board'); ?>
                            </a>
                            <a href="mailto:?subject=<?php echo urlencode($job->title . ' at ' . $job->company); ?>&body=<?php echo urlencode('Check out this job: ' . get_permalink()); ?>" class="ajsb-share-btn ajsb-share-email">
                                <i class="ajsb-icon-email"></i>
                                <?php _e('Email', 'ai-job-search-board'); ?>
                            </a>
                        </div>
                    </div>
                    
                    <div class="ajsb-job-actions">
                        <a href="<?php echo get_permalink(get_page_by_path('jobs')); ?>" class="ajsb-btn ajsb-btn-secondary">
                            <i class="ajsb-icon-arrow-left"></i>
                            <?php _e('Back to Jobs', 'ai-job-search-board'); ?>
                        </a>
                    </div>
                </div>
                
            <?php else: ?>
                <div class="ajsb-job-not-found">
                    <h1><?php _e('Job Not Found', 'ai-job-search-board'); ?></h1>
                    <p><?php _e('The job you are looking for could not be found or may have expired.', 'ai-job-search-board'); ?></p>
                    <a href="<?php echo get_permalink(get_page_by_path('jobs')); ?>" class="ajsb-btn ajsb-btn-primary">
                        <?php _e('Browse All Jobs', 'ai-job-search-board'); ?>
                    </a>
                </div>
            <?php endif; ?>
            
        </article>
        
    <?php endwhile; ?>
</div>

<?php get_footer(); ?>