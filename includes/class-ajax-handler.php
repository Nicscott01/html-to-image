
public function csig_save_image() {
    check_ajax_referer('csig_save_image', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'csig'));
    }
    
    $image_data = $_POST['image_data'] ?? '';
    $element_index = intval($_POST['element_index'] ?? 0);
    $job_id = intval($_POST['job_id'] ?? 0);
    $custom_filename = sanitize_file_name($_POST['custom_filename'] ?? '');
    $overwrite_files = ($_POST['overwrite_files'] ?? '0') === '1';
    
    if (empty($image_data)) {
        wp_send_json_error(__('No image data received', 'csig'));
    }
    
    // Get job settings
    $job_settings = Job_Post_Type::get_job_settings($job_id);
    
    // Parse the data URL
    if (strpos($image_data, 'data:image/png;base64,') !== 0) {
        wp_send_json_error(__('Invalid image data format', 'csig'));
    }
    
    $base64_data = substr($image_data, strlen('data:image/png;base64,'));
    $binary_data = base64_decode($base64_data);
    
    if ($binary_data === false) {
        wp_send_json_error(__('Failed to decode image data', 'csig'));
    }
    
    // Generate filename
    if (!empty($custom_filename)) {
        // Use custom filename
        $filename = $custom_filename . '.png';
    } else {
        // Use default naming
        $job_title = sanitize_file_name(get_the_title($job_id));
        $timestamp = current_time('Y-m-d_H-i-s');
        $filename = $job_title . '_' . $timestamp . '_' . ($element_index + 1) . '.png';
    }
    
    // Create the directory if it doesn't exist
    $save_folder = $job_settings['saveFolder'];
    $upload_dir = wp_upload_dir();
    $target_dir = $upload_dir['basedir'] . '/' . $save_folder;
    
    if (!file_exists($target_dir)) {
        wp_mkdir_p($target_dir);
    }
    
    $target_path = $target_dir . '/' . $filename;
    $target_url = $upload_dir['baseurl'] . '/' . $save_folder . '/' . $filename;
    
    // Handle filename conflicts based on overwrite setting
    if (!$overwrite_files) {
        $counter = 1;
        $original_filename = $filename;
        while (file_exists($target_path)) {
            $path_info = pathinfo($original_filename);
            $filename = $path_info['filename'] . '_' . $counter . '.' . $path_info['extension'];
            $target_path = $target_dir . '/' . $filename;
            $target_url = $upload_dir['baseurl'] . '/' . $save_folder . '/' . $filename;
            $counter++;
        }
    }
    
    // Save the file
    if (file_put_contents($target_path, $binary_data) === false) {
        wp_send_json_error(__('Failed to save image file', 'csig'));
    }
    
    $message = $overwrite_files && file_exists($target_path) 
        ? __('Image saved successfully (overwritten)', 'csig')
        : __('Image saved successfully', 'csig');
    
    wp_send_json_success(array(
        'message' => $message,
        'filename' => $filename,
        'url' => $target_url,
        'overwritten' => $overwrite_files && file_exists($target_path)
    ));
}

public function csig_save_pdf() {
    check_ajax_referer('csig_save_image', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'csig'));
    }
    
    $pdf_data = $_POST['pdf_data'] ?? '';
    $element_index = intval($_POST['element_index'] ?? 0);
    $job_id = intval($_POST['job_id'] ?? 0);
    $custom_filename = sanitize_file_name($_POST['custom_filename'] ?? '');
    $overwrite_files = ($_POST['overwrite_files'] ?? '0') === '1';
    
    if (empty($pdf_data)) {
        wp_send_json_error(__('No PDF data received', 'csig'));
    }
    
    // Get job settings
    $job_settings = Job_Post_Type::get_job_settings($job_id);
    
    // Parse the data URL
    if (strpos($pdf_data, 'data:application/pdf;base64,') !== 0) {
        wp_send_json_error(__('Invalid PDF data format', 'csig'));
    }
    
    $base64_data = substr($pdf_data, strlen('data:application/pdf;base64,'));
    $binary_data = base64_decode($base64_data);
    
    if ($binary_data === false) {
        wp_send_json_error(__('Failed to decode PDF data', 'csig'));
    }
    
    // Generate filename
    if (!empty($custom_filename)) {
        // Use custom filename
        $filename = $custom_filename . '.pdf';
    } else {
        // Use default naming
        $job_title = sanitize_file_name(get_the_title($job_id));
        $timestamp = current_time('Y-m-d_H-i-s');
        $filename = $job_title . '_' . $timestamp . '_' . ($element_index + 1) . '.pdf';
    }
    
    // Create the directory if it doesn't exist
    $save_folder = $job_settings['saveFolder'];
    $upload_dir = wp_upload_dir();
    $target_dir = $upload_dir['basedir'] . '/' . $save_folder;
    
    if (!file_exists($target_dir)) {
        wp_mkdir_p($target_dir);
    }
    
    $target_path = $target_dir . '/' . $filename;
    $target_url = $upload_dir['baseurl'] . '/' . $save_folder . '/' . $filename;
    
    // Handle filename conflicts based on overwrite setting
    if (!$overwrite_files) {
        $counter = 1;
        $original_filename = $filename;
        while (file_exists($target_path)) {
            $path_info = pathinfo($original_filename);
            $filename = $path_info['filename'] . '_' . $counter . '.' . $path_info['extension'];
            $target_path = $target_dir . '/' . $filename;
            $target_url = $upload_dir['baseurl'] . '/' . $save_folder . '/' . $filename;
            $counter++;
        }
    }
    
    // Save the file
    if (file_put_contents($target_path, $binary_data) === false) {
        wp_send_json_error(__('Failed to save PDF file', 'csig'));
    }
    
    $message = $overwrite_files && file_exists($target_path) 
        ? __('PDF saved successfully (overwritten)', 'csig')
        : __('PDF saved successfully', 'csig');
    
    wp_send_json_success(array(
        'message' => $message,
        'filename' => $filename,
        'url' => $target_url,
        'overwritten' => $overwrite_files && file_exists($target_path)
    ));
}