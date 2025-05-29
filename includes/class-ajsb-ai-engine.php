<?php
/**
 * AI Engine for job matching and recommendations
 */

if (!defined('ABSPATH')) {
    exit;
}

class AJSB_AI_Engine {
    
    private $settings;
    
    public function __construct() {
        $this->settings = get_option('ajsb_settings', array());
        
        // Schedule AI recommendation updates
        add_action('ajsb_update_ai_recommendations', array($this, 'update_all_recommendations'));
        
        if (!wp_next_scheduled('ajsb_update_ai_recommendations')) {
            wp_schedule_event(time(), 'daily', 'ajsb_update_ai_recommendations');
        }
    }
    
    /**
     * Generate job recommendations for a user
     */
    public function generate_recommendations($user_id, $limit = 10) {
        $profile = AJSB_User_Profile::get_by_user_id($user_id);
        
        if (!$profile) {
            return array();
        }
        
        // Get active jobs
        $jobs = AJSB_Job::get_jobs(array(
            'status' => 'active',
            'limit' => 100 // Get more jobs to analyze
        ));
        
        $recommendations = array();
        
        foreach ($jobs as $job) {
            $score = $this->calculate_job_match_score($job, $profile);
            
            if ($score > 30) { // Only recommend jobs with >30% match
                $recommendations[] = array(
                    'job' => $job,
                    'score' => $score,
                    'reasons' => $this->get_match_reasons($job, $profile, $score)
                );
            }
        }
        
        // Sort by score descending
        usort($recommendations, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        // Limit results
        $recommendations = array_slice($recommendations, 0, $limit);
        
        // Save recommendations to database
        $this->save_recommendations($user_id, $recommendations);
        
        return $recommendations;
    }
    
    /**
     * Calculate match score between job and user profile
     */
    public function calculate_job_match_score($job, $profile) {
        $score = 0.0;
        $weights = array(
            'skills' => 40,
            'experience' => 20,
            'location' => 15,
            'job_type' => 10,
            'salary' => 10,
            'remote' => 5
        );
        
        // Skills matching
        $skills_score = $this->calculate_skills_match($job, $profile);
        $score += $skills_score * ($weights['skills'] / 100);
        
        // Experience level matching
        $experience_score = $this->calculate_experience_match($job, $profile);
        $score += $experience_score * ($weights['experience'] / 100);
        
        // Location matching
        $location_score = $this->calculate_location_match($job, $profile);
        $score += $location_score * ($weights['location'] / 100);
        
        // Job type matching
        $job_type_score = $this->calculate_job_type_match($job, $profile);
        $score += $job_type_score * ($weights['job_type'] / 100);
        
        // Salary matching
        $salary_score = $this->calculate_salary_match($job, $profile);
        $score += $salary_score * ($weights['salary'] / 100);
        
        // Remote work matching
        $remote_score = $this->calculate_remote_match($job, $profile);
        $score += $remote_score * ($weights['remote'] / 100);
        
        return round($score, 2);
    }
    
    /**
     * Calculate skills match score
     */
    private function calculate_skills_match($job, $profile) {
        if (empty($job->skills) || empty($profile->skills)) {
            return 0;
        }
        
        $job_skills = array_map('trim', explode(',', strtolower($job->skills)));
        $user_skills = array_map('trim', explode(',', strtolower($profile->skills)));
        
        $matching_skills = array_intersect($job_skills, $user_skills);
        
        if (empty($job_skills)) {
            return 0;
        }
        
        return (count($matching_skills) / count($job_skills)) * 100;
    }
    
    /**
     * Calculate experience level match score
     */
    private function calculate_experience_match($job, $profile) {
        if (empty($job->experience_level) || empty($profile->experience_level)) {
            return 50; // Neutral score if not specified
        }
        
        $levels = array('entry', 'junior', 'mid', 'senior', 'lead', 'executive');
        
        $job_index = array_search($job->experience_level, $levels);
        $user_index = array_search($profile->experience_level, $levels);
        
        if ($job_index === false || $user_index === false) {
            return 50;
        }
        
        $difference = abs($job_index - $user_index);
        
        switch ($difference) {
            case 0:
                return 100; // Perfect match
            case 1:
                return 75;  // Close match
            case 2:
                return 50;  // Acceptable match
            default:
                return 25;  // Poor match
        }
    }
    
    /**
     * Calculate location match score
     */
    private function calculate_location_match($job, $profile) {
        // Remote jobs always match
        if ($job->remote_work) {
            return 100;
        }
        
        if (empty($job->location) || empty($profile->location)) {
            return 50;
        }
        
        // Simple location matching (can be enhanced with geocoding)
        $job_location = strtolower($job->location);
        $user_location = strtolower($profile->location);
        
        if ($job_location === $user_location) {
            return 100;
        }
        
        // Check if locations contain similar words
        $job_words = explode(' ', $job_location);
        $user_words = explode(' ', $user_location);
        
        $common_words = array_intersect($job_words, $user_words);
        
        if (!empty($common_words)) {
            return 75;
        }
        
        return 25;
    }
    
    /**
     * Calculate job type match score
     */
    private function calculate_job_type_match($job, $profile) {
        if (empty($job->job_type) || empty($profile->desired_job_type)) {
            return 50;
        }
        
        if ($job->job_type === $profile->desired_job_type) {
            return 100;
        }
        
        // Some job types are compatible
        $compatible_types = array(
            'full-time' => array('contract'),
            'part-time' => array('freelance'),
            'contract' => array('full-time', 'freelance'),
            'freelance' => array('part-time', 'contract')
        );
        
        if (isset($compatible_types[$job->job_type]) && 
            in_array($profile->desired_job_type, $compatible_types[$job->job_type])) {
            return 75;
        }
        
        return 25;
    }
    
    /**
     * Calculate salary match score
     */
    private function calculate_salary_match($job, $profile) {
        if (empty($job->salary_min) || empty($profile->desired_salary_min)) {
            return 50;
        }
        
        $job_min = (float)$job->salary_min;
        $job_max = !empty($job->salary_max) ? (float)$job->salary_max : $job_min;
        $user_min = (float)$profile->desired_salary_min;
        $user_max = !empty($profile->desired_salary_max) ? (float)$profile->desired_salary_max : $user_min * 1.5;
        
        // Check if salary ranges overlap
        if ($job_max >= $user_min && $job_min <= $user_max) {
            // Calculate overlap percentage
            $overlap_start = max($job_min, $user_min);
            $overlap_end = min($job_max, $user_max);
            $overlap = $overlap_end - $overlap_start;
            
            $user_range = $user_max - $user_min;
            $job_range = $job_max - $job_min;
            
            $avg_range = ($user_range + $job_range) / 2;
            
            if ($avg_range > 0) {
                return min(100, ($overlap / $avg_range) * 100);
            }
            
            return 100;
        }
        
        // No overlap - calculate how close they are
        if ($job_max < $user_min) {
            $gap = $user_min - $job_max;
            $gap_percentage = ($gap / $user_min) * 100;
            return max(0, 100 - $gap_percentage);
        }
        
        if ($job_min > $user_max) {
            return 100; // Job pays more than expected - good for user
        }
        
        return 0;
    }
    
    /**
     * Calculate remote work match score
     */
    private function calculate_remote_match($job, $profile) {
        $job_remote = (bool)$job->remote_work;
        $user_remote = (bool)$profile->remote_preference;
        
        if ($job_remote && $user_remote) {
            return 100; // Both prefer remote
        }
        
        if (!$job_remote && !$user_remote) {
            return 100; // Both prefer office
        }
        
        if ($job_remote && !$user_remote) {
            return 75; // Job is remote but user prefers office (still good)
        }
        
        return 50; // User wants remote but job is office-based
    }
    
    /**
     * Get match reasons for explanation
     */
    private function get_match_reasons($job, $profile, $score) {
        $reasons = array();
        
        // Skills match
        if (!empty($job->skills) && !empty($profile->skills)) {
            $job_skills = array_map('trim', explode(',', strtolower($job->skills)));
            $user_skills = array_map('trim', explode(',', strtolower($profile->skills)));
            $matching_skills = array_intersect($job_skills, $user_skills);
            
            if (!empty($matching_skills)) {
                $reasons[] = sprintf(
                    __('Matching skills: %s', 'ai-job-search-board'),
                    implode(', ', array_slice($matching_skills, 0, 3))
                );
            }
        }
        
        // Experience level
        if ($job->experience_level === $profile->experience_level) {
            $reasons[] = __('Perfect experience level match', 'ai-job-search-board');
        }
        
        // Location
        if ($job->remote_work && $profile->remote_preference) {
            $reasons[] = __('Remote work opportunity', 'ai-job-search-board');
        } elseif (!empty($job->location) && !empty($profile->location) && 
                  stripos($profile->location, $job->location) !== false) {
            $reasons[] = __('Location match', 'ai-job-search-board');
        }
        
        // Job type
        if ($job->job_type === $profile->desired_job_type) {
            $reasons[] = __('Preferred job type', 'ai-job-search-board');
        }
        
        // Salary
        if (!empty($job->salary_min) && !empty($profile->desired_salary_min) && 
            $job->salary_min >= $profile->desired_salary_min) {
            $reasons[] = __('Meets salary expectations', 'ai-job-search-board');
        }
        
        return $reasons;
    }
    
    /**
     * Save recommendations to database
     */
    private function save_recommendations($user_id, $recommendations) {
        global $wpdb;
        
        $table = AJSB_Database::get_table_name('ai_recommendations');
        
        // Clear existing recommendations for this user
        $wpdb->delete($table, array('user_id' => $user_id));
        
        // Insert new recommendations
        foreach ($recommendations as $rec) {
            $wpdb->insert($table, array(
                'user_id' => $user_id,
                'job_id' => $rec['job']->id,
                'match_score' => $rec['score'],
                'reasons' => json_encode($rec['reasons']),
                'created_at' => current_time('mysql')
            ));
        }
    }
    
    /**
     * Get saved recommendations for user
     */
    public function get_user_recommendations($user_id, $limit = 10) {
        global $wpdb;
        
        $recommendations_table = AJSB_Database::get_table_name('ai_recommendations');
        $jobs_table = AJSB_Database::get_table_name('jobs');
        
        $sql = "SELECT r.*, j.* 
                FROM $recommendations_table r 
                LEFT JOIN $jobs_table j ON r.job_id = j.id 
                WHERE r.user_id = %d AND j.status = 'active' 
                ORDER BY r.match_score DESC 
                LIMIT %d";
        
        return $wpdb->get_results($wpdb->prepare($sql, $user_id, $limit));
    }
    
    /**
     * Update recommendations for all users
     */
    public function update_all_recommendations() {
        global $wpdb;
        
        // Get all users with profiles
        $profiles_table = AJSB_Database::get_table_name('user_profiles');
        $user_ids = $wpdb->get_col("SELECT user_id FROM $profiles_table");
        
        foreach ($user_ids as $user_id) {
            $this->generate_recommendations($user_id, 20);
        }
    }
    
    /**
     * Get job recommendations using external AI API
     */
    public function get_ai_powered_recommendations($user_id, $job_description) {
        if (empty($this->settings['ai_api_key']) || empty($this->settings['ai_provider'])) {
            return array();
        }
        
        $profile = AJSB_User_Profile::get_by_user_id($user_id);
        if (!$profile) {
            return array();
        }
        
        switch ($this->settings['ai_provider']) {
            case 'openai':
                return $this->get_openai_recommendations($profile, $job_description);
            default:
                return array();
        }
    }
    
    /**
     * Get recommendations from OpenAI
     */
    private function get_openai_recommendations($profile, $job_description) {
        $api_key = $this->settings['ai_api_key'];
        
        $prompt = $this->build_ai_prompt($profile, $job_description);
        
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'model' => 'gpt-3.5-turbo',
                'messages' => array(
                    array(
                        'role' => 'system',
                        'content' => 'You are an AI job matching assistant. Analyze the candidate profile and job description to provide matching insights.'
                    ),
                    array(
                        'role' => 'user',
                        'content' => $prompt
                    )
                ),
                'max_tokens' => 500,
                'temperature' => 0.7
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array();
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['choices'][0]['message']['content'])) {
            return $this->parse_ai_response($data['choices'][0]['message']['content']);
        }
        
        return array();
    }
    
    /**
     * Build AI prompt for job matching
     */
    private function build_ai_prompt($profile, $job_description) {
        $prompt = "Analyze this candidate profile and job description for compatibility:\n\n";
        
        $prompt .= "CANDIDATE PROFILE:\n";
        $prompt .= "Skills: " . ($profile->skills ?? 'Not specified') . "\n";
        $prompt .= "Experience Level: " . ($profile->experience_level ?? 'Not specified') . "\n";
        $prompt .= "Location: " . ($profile->location ?? 'Not specified') . "\n";
        $prompt .= "Remote Preference: " . ($profile->remote_preference ? 'Yes' : 'No') . "\n";
        $prompt .= "Desired Job Type: " . ($profile->desired_job_type ?? 'Not specified') . "\n";
        
        if (!empty($profile->bio)) {
            $prompt .= "Bio: " . substr($profile->bio, 0, 200) . "\n";
        }
        
        $prompt .= "\nJOB DESCRIPTION:\n" . substr($job_description, 0, 500) . "\n\n";
        
        $prompt .= "Please provide:\n";
        $prompt .= "1. Match score (0-100)\n";
        $prompt .= "2. Top 3 matching points\n";
        $prompt .= "3. Top 2 potential concerns\n";
        $prompt .= "4. Overall recommendation (Highly Recommended/Recommended/Consider/Not Recommended)\n";
        
        return $prompt;
    }
    
    /**
     * Parse AI response
     */
    private function parse_ai_response($response) {
        // Simple parsing - can be enhanced
        $lines = explode("\n", $response);
        $result = array(
            'score' => 0,
            'matches' => array(),
            'concerns' => array(),
            'recommendation' => 'Consider'
        );
        
        foreach ($lines as $line) {
            if (preg_match('/score.*?(\d+)/i', $line, $matches)) {
                $result['score'] = (int)$matches[1];
            }
            
            if (stripos($line, 'matching') !== false || stripos($line, 'match') !== false) {
                $result['matches'][] = trim($line);
            }
            
            if (stripos($line, 'concern') !== false) {
                $result['concerns'][] = trim($line);
            }
            
            if (stripos($line, 'recommendation') !== false) {
                if (stripos($line, 'highly') !== false) {
                    $result['recommendation'] = 'Highly Recommended';
                } elseif (stripos($line, 'not recommended') !== false) {
                    $result['recommendation'] = 'Not Recommended';
                } elseif (stripos($line, 'recommended') !== false) {
                    $result['recommendation'] = 'Recommended';
                }
            }
        }
        
        return $result;
    }
}