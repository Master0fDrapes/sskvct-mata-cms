<?php

require_once('admin-forms.php');

add_action('rest_api_init', function () {
    register_rest_route('api', '/form-submission', [
        'methods' => 'POST',
        'callback' => 'handle_assistant_form_submission',
        'permission_callback' => '__return_true',
    ]);
});

function handle_assistant_form_submission($request)
{
    global $wpdb;

    $params = $request->get_json_params();

    // Basic fields
    $formname =sanitize_text_field($params['formname'] ?? '');
    $name = sanitize_text_field($params['name'] ?? '');
    $email = sanitize_email($params['email'] ?? '');
    $phone = sanitize_text_field($params['phone'] ?? '');
    $city = sanitize_text_field($params['city'] ?? '');
    $message = sanitize_textarea_field($params['message'] ?? '');
    $checkbox = isset($params['privacy_policy'])?$params['privacy_policy'] : 0;
    $companyname = sanitize_textarea_field($params['companyname'] ?? '');
    $designation = sanitize_textarea_field($params['designation'] ?? '');
    $country = sanitize_textarea_field($params['country'] ?? '');
    $programtype = sanitize_textarea_field($params['programtype'] ?? '');

    // UTM fields (optional, with fallback)
    $utm_source = trim(strtolower($params['utm_source'] ?? 'Direct'));
    $utm_medium = trim(strtolower($params['utm_medium'] ?? 'Direct'));
    $utm_campaign = trim(strtolower($params['utm_campaign'] ?? ($params['project-name'] ?? 'Direct')));
    $utm_adgroup = trim(strtolower($params['utm_term'] ?? 'Direct'));
    $utm_adcopy = trim(strtolower($params['utm_content'] ?? 'Direct'));
    $page_url = sanitize_text_field($params['page_url'] ?? '');
    $page_title = sanitize_text_field($params['page_title'] ?? '');
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $browser_name = sanitize_text_field($params['browser_name'] ?? '');
    $browser_version = sanitize_text_field($params['browser_version'] ?? '');
    $user_agent = sanitize_textarea_field($_SERVER['HTTP_USER_AGENT'] ?? '');
    $device_type = sanitize_text_field($params['device_type'] ?? '');
    $os_name = sanitize_text_field($params['os_name'] ?? '');
    $os_version = sanitize_text_field($params['os_version'] ?? '');
    $screen_resolution = sanitize_text_field($params['screen_resolution'] ?? '');
$matched_form_id = ''; 
$post_id = get_the_ID(); 
var_dump($post_id);
$page_title = get_the_title($post_id);
var_dump($page_title);

// Loop through the repeater field rows and match UTM values
if (have_rows('utm_params', $post_id)) {
    while (have_rows('utm_params', $post_id)) {
        the_row();

        $utm_source_field   = get_sub_field('utm_source_params');
        $utm_medium_field   = get_sub_field('utm_medium_params');
        $utm_campaign_field = get_sub_field('utm_campaign_params');

        if (
            $utm_source === $utm_source_field &&
            $utm_medium === $utm_medium_field &&
            $utm_campaign === $utm_campaign_field
        ) {
            $matched_form_id = get_sub_field('utm_form_id_params');
            break;
        }
    }
}


$data['form_id'] = $matched_form_id ?: '';

    // Validate required fields
    if (empty($name) || empty($email) || empty($phone)) {
        return new WP_REST_Response([
            'status' => false,
            'message' => 'Name, Email, and Phone are required.'
        ], 400);
    }

    // Insert into DB (no UTM config checks)
    $table_name = 'jaro_forms_submissions';

    $inserted = $wpdb->insert($table_name, [
        'name' => $name,
        'form_name' => $formname,
        'email' => $email,
        'phone' => $phone,
        'city' => $city,
        'message' => $message,
      
        'agree' => $checkbox,
        'companyname' => $companyname,
        'designation' => $designation,
        'country' => $country,
        'programtype' => $programtype,
        'utm_source' => $utm_source,
        'utm_medium' => $utm_medium,
        'utm_campaign' =>$data['form_id'] ,
        'utm_adgroup' => $utm_adgroup,
        'utm_adcopy' => $utm_adcopy,
        'page_url' => $page_url,
        'page_title' => $page_title,
        'ip_address' => $ip_address,
        'browser_name' => $browser_name,
        'browser_version' => $browser_version,
        'user_agent' => $user_agent,
        'device_type' => $device_type,
        'os_name' => $os_name,
        'os_version' => $os_version,
        'screen_resolution' => $screen_resolution,
    ]);


    if ($inserted) {
        return new WP_REST_Response([
            'status' => true,
            'message' => 'Form submitted and saved successfully.',
            'insert_id' => $wpdb->insert_id // optional
        ], 200);
    } else {
        return new WP_REST_Response([
            'status' => false,
            'message' => 'Database insert failed.'
        ], 500);
    }
}

