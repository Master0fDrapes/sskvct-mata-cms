<?php 

function contactform(WP_REST_Request $request) {
    // Check if Contact Form 7 is active
    if (!class_exists('WPCF7_ContactForm')) {
        return new WP_REST_Response('Contact Form 7 is not active or loaded.', 500);
    }

    // Extract and sanitize request parameters
    $text = sanitize_text_field($request->get_param('text-902'));
    $email = sanitize_email($request->get_param('email'));

    // Validation for required fields
    if (empty($text && $email)) {
        return new WP_REST_Response([
            'status' => false,
            'message' => 'The name,email field is required.',
        ], 400);
    }

    if (empty($email)) {
        return new WP_REST_Response([
            'status' => false,
            'message' => 'The email field is required.',
        ], 400);
    }

    // Validate email format
    if (!is_email($email)) {
        return new WP_REST_Response([
            'status' => false,
            'message' => 'The email provided is not valid.',
        ], 400);
    }

    // Define the form ID
    $cf7_form_id = ""; 

    if ($_SERVER['HTTP_HOST'] === 'localhost') {
        $cf7_form_id = 445; // Form ID for localhost
    } elseif ($_SERVER['HTTP_HOST'] === 'terra-cms.irepo.in') {
        $cf7_form_id = 805; // Form ID for terra-cms.irepo.in
    }
    
    $form = WPCF7_ContactForm::get_instance($cf7_form_id);
    

    // Check if the form exists
    if (!$form) {
        return new WP_REST_Response([
            'status' => false,
            'message' => "Contact Form 7 form with ID $cf7_form_id not found.",
        ], 400);
    }

    // Prepare posted data
    $submission_data = [
        'text-902'    => $text,
        'email-325'   => $email,
    ];

    // Use WPCF7's submission handler
    $_POST = [
        '_wpcf7'          => $cf7_form_id,
        '_wpcf7_version'  => WPCF7_VERSION,
        '_wpcf7_locale'   => get_locale(),
        '_wpcf7_unit_tag' => 'wpcf7-f' . $cf7_form_id . '-p1-o1',
        '_wpcf7_container_post' => 0,
        'text-902'        => $text,
        'email-325'       => $email,
    ];

    // Submit the form
    $result = $form->submit();

    if ($result) {
        return new WP_REST_Response([
            'status'  => true,
            'message' => 'Form submitted successfully.',
        ], 200);
    } else {
        // Debug failure
        error_log(print_r($form->prop('messages'), true));
        return new WP_REST_Response([
            'status' => false,
            'message' => 'Form submission failed.',
        ], 500);
    }
}

// Register custom REST API route
add_action('rest_api_init', function () {
    register_rest_route('api', '/contactform/', [
        'methods' => 'POST',
        'callback' => 'contactform',
        'permission_callback' => '__return_true',
    ]);
});
