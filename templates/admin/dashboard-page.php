
<div class="wrap">
    <h1><?php _e('Client-Side Image Generator', 'csig'); ?></h1>
    <p><?php _e('Generate images from HTML elements on your website.', 'csig'); ?></p>
    
    <div class="csig-dashboard">
        <div class="csig-dashboard-cards">
            <div class="card">
                <h2><?php _e('Quick Start', 'csig'); ?></h2>
                <ol>
                    <li><?php _e('Create a new Image Job', 'csig'); ?> <a href="<?php echo admin_url('post-new.php?post_type=csig_job'); ?>" class="button button-small"><?php _e('Add New Job', 'csig'); ?></a></li>
                    <li><?php _e('Set the URL and CSS selector (.csig-card by default)', 'csig'); ?></li>
                    <li><?php _e('Configure output format and quality', 'csig'); ?></li>
                    <li><?php _e('Publish the job and click "Run Job"', 'csig'); ?></li>
                </ol>
            </div>
            
            <div class="card">
                <h2><?php _e('Recent Jobs', 'csig'); ?></h2>
                <?php
                $recent_jobs = get_posts(array(
                    'post_type' => 'csig_job',
                    'post_status' => 'publish',
                    'numberposts' => 5,
                    'orderby' => 'date',
                    'order' => 'DESC'
                ));
                
                if ($recent_jobs): ?>
                    <ul>
                        <?php foreach ($recent_jobs as $job): ?>
                        <li>
                            <a href="<?php echo get_edit_post_link($job->ID); ?>"><?php echo esc_html($job->post_title); ?></a>
                            <small> - <a href="<?php echo admin_url('admin.php?page=csig-capture&job_id=' . $job->ID); ?>"><?php _e('Run Job', 'csig'); ?></a></small>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <p><a href="<?php echo admin_url('edit.php?post_type=csig_job'); ?>"><?php _e('View All Jobs', 'csig'); ?></a></p>
                <?php else: ?>
                    <p><?php _e('No jobs created yet.', 'csig'); ?> <a href="<?php echo admin_url('post-new.php?post_type=csig_job'); ?>"><?php _e('Create your first job', 'csig'); ?></a></p>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2><?php _e('Statistics', 'csig'); ?></h2>
                <?php
                $job_count = wp_count_posts('csig_job');
                $total_runs = 0;
                
                $all_jobs = get_posts(array(
                    'post_type' => 'csig_job',
                    'post_status' => 'publish',
                    'numberposts' => -1
                ));
                
                foreach ($all_jobs as $job) {
                    $run_count = get_post_meta($job->ID, '_csig_run_count', true) ?: 0;
                    $total_runs += $run_count;
                }
                ?>
                <ul>
                    <li><strong><?php echo $job_count->publish; ?></strong> <?php _e('Active Jobs', 'csig'); ?></li>
                    <li><strong><?php echo $total_runs; ?></strong> <?php _e('Total Runs', 'csig'); ?></li>
                </ul>
            </div>
        </div>
    </div>
    
    <style>
    .csig-dashboard-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    .csig-dashboard-cards .card {
        padding: 20px;
    }
    .csig-dashboard-cards .card h2 {
        margin-top: 0;
    }
    </style>
</div>