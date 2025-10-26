<?php
// Get the absolute path to the WordPress installation
$wordpress_path = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))); // Adjust as needed

// Include PHPMailer from WordPress
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    require_once $wordpress_path . '/wp-includes/PHPMailer/PHPMailer.php';
    require_once $wordpress_path . '/wp-includes/PHPMailer/SMTP.php';
    require_once $wordpress_path . '/wp-includes/PHPMailer/Exception.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


// 1. Update database table structure
register_activation_hook(__FILE__, 'create_submissions_table');
function create_submissions_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'form_submissions';
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        email varchar(100) NOT NULL,
        phone varchar(20) NOT NULL,
        experience varchar(50) NOT NULL,
        industry varchar(50) NOT NULL,
        outcomes varchar(50) NOT NULL,
        dedication varchar(50) NOT NULL,
        challenges varchar(50) NOT NULL,
        areas varchar(50) NOT NULL,
        salary varchar(50) NOT NULL,
        otp varchar(6) NOT NULL,
        is_verified tinyint(1) DEFAULT 0 NOT NULL,
        submission_date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) ".$wpdb->get_charset_collate().";";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}



// 2. Modified REST API endpoint
add_action('acf/init', function () {
    global $acf_field_options;
    $acf_field_options = [
        'experience'  => get_acf_field_slugs('experience_mcq_options'),
        'industry'    => get_acf_field_slugs('industry_mcq_options'),
        'outcomes'    => get_acf_field_slugs('outcomes_mcq_options'),
        'dedication'  => get_acf_field_slugs('dedication_mcq_options'),
        'challenges'  => get_acf_field_slugs('challenges_mcq_options'),
        'areas'       => get_acf_field_slugs('focus_areas_mcq_option'),
        'salary'      => get_acf_field_slugs('salary_mcq_options')
    ];
});

function get_acf_field_slugs($field_name) {
    if (!function_exists('get_field')) {
        return [];
    }

    $args = [
        'post_type'      => 'reports',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'order'          => 'ASC',
    ];

    $query = new WP_Query($args);
    $values = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $field_value = get_field($field_name);
            if ($field_value) {
                if (is_array($field_value)) {
                    foreach ($field_value as $value) {
                        $values[] = sanitize_title($value);
                    }
                } else {
                    $values[] = sanitize_title($field_value);
                }
            }
        }
    }
    wp_reset_postdata();

    return array_unique($values);
}

add_action('rest_api_init', function () {
    global $acf_field_options;

    function create_validation_callback($valid_options) {
        return function($param) use ($valid_options) {
            if (is_array($param)) {
                foreach ($param as $value) {
                    if (!in_array($value, $valid_options)) {
                        return false; // If any value is invalid, reject request
                    }
                }
                return true; // All values are valid
            }
            return in_array($param, $valid_options);
        };
    }
    

    register_rest_route('api', '/submit', [
        'methods'  => 'POST',
        'callback' => 'handle_form_submission',
        'args'     => [
            'name'       => ['required' => true, 'sanitize_callback' => 'sanitize_text_field'],
            'email'      => ['required' => true, 'validate_callback' => 'is_email'],
            'phone'      => [ 'required'=>true, 'validate_callback'=>function ($param){ return preg_match('/^\+?[0-9]{7,15}$/', $param);} ],
            'experience'  => ['required' => true, 'validate_callback' => create_validation_callback($acf_field_options['experience'])],
            'industry'    => ['required' => true, 'validate_callback' => create_validation_callback($acf_field_options['industry'])],
            'outcomes'    => ['required' => true, 'validate_callback' => create_validation_callback($acf_field_options['outcomes'])],
            'dedication'  => ['required' => true, 'validate_callback' => create_validation_callback($acf_field_options['dedication'])],
            'challenges'  => ['required' => true, 'validate_callback' => create_validation_callback($acf_field_options['challenges'])],
            'areas'       => ['required' => true, 'validate_callback' => create_validation_callback($acf_field_options['areas'])],
            'salary'      => ['required' => true, 'validate_callback' => create_validation_callback($acf_field_options['salary'])],
        ]
    ]);

    register_rest_route('api', '/verify', [
        'methods'  => 'POST',
        'callback' => 'verify_otp',
        'permission_callback' => '__return_true'
    ]);
});


function handle_form_submission(WP_REST_Request $request) {
    session_start();
    include_once('config.php');
    global $wpdb;

    $params = $request->get_params();

    // Generate OTP
    $otp = mt_rand(1000, 9999);
    
    // Hash the OTP before storing it
    $hashed_otp = password_hash($otp, PASSWORD_DEFAULT);

    // Convert multi-select arrays into comma-separated strings
    $industry   = isset($params['industry']) ? implode(',', array_map('sanitize_text_field', (array) $params['industry'])) : '';
    $outcomes   = isset($params['outcomes']) ? implode(',', array_map('sanitize_text_field', (array) $params['outcomes'])) : '';
    $challenges = isset($params['challenges']) ? implode(',', array_map('sanitize_text_field', (array) $params['challenges'])) : '';
    $areas      = isset($params['areas']) ? implode(',', array_map('sanitize_text_field', (array) $params['areas'])) : '';

    // Store submission with hashed OTP
    $wpdb->insert($wpdb->prefix . 'form_submissions', [
        'name'       => sanitize_text_field($params['name']),
        'email'      => sanitize_email($params['email']),
        'phone'      => sanitize_text_field($params['phone']),
        'experience' => sanitize_text_field($params['experience']),
        'industry'   => $industry,   // Stored as CSV
        'outcomes'   => $outcomes,   // Stored as CSV
        'challenges' => $challenges, // Stored as CSV
        'areas'      => $areas,      // Stored as CSV
        'dedication' => sanitize_text_field($params['dedication']),
        'salary'     => sanitize_text_field($params['salary']),
        'otp'        => $hashed_otp, // Store the hashed OTP
        'submission_date' => current_time('mysql')
    ]);

    // Send OTP to user via email
    $mail_sent = send_otp_email($params['email'], $params['name'], $otp);

    return new WP_REST_Response([
        'status'  => $mail_sent,
        'message' => $mail_sent ? 'Otp_sent' : 'Otp_notsent'
    ], $mail_sent ? 200 : 500);
}
function send_otp_email($to, $name, $otp) {
    $mail = new PHPMailer(true);
    
    try {
        // Configure Gmail SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'arun.basavaraja@terralogic.com';
        $mail->Password   = 'pjck ujcq vznw xpcg'; // Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Set From and To
        $mail->setFrom('arun.basavaraja@terralogic.com', 'Verification Service');
        $mail->addReplyTo('arun.basavaraja@terralogic.com', 'Verification Service');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = 'Your OTP for Verification';
        $mail->Body    = "<p>Hello $name,</p><p>Your OTP for verification is: <strong>$otp</strong></p>";

        return $mail->send();

    } catch (Exception $e) {
        error_log('Email Error: ' . $mail->ErrorInfo);
        return false;
    }
}

function verify_otp(WP_REST_Request $request) {
    global $wpdb;

    // Get parameters from the request
    $email = sanitize_email($request->get_param('email'));
    $otp   = sanitize_text_field($request->get_param('otp'));

    // Fetch the latest submission for this email
    $table_name = $wpdb->prefix . 'form_submissions';
    $latest_submission = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $table_name WHERE email = %s ORDER BY id DESC LIMIT 1",
            $email
        )
    );

    if (!$latest_submission) {
        return new WP_REST_Response([
            'status'  => false,
            'message' => 'Email not found in records.'
        ], 400);
    }

    // Check if the OTP has expired (more than 2 minutes old)
    $otp_created_time = strtotime($latest_submission->submission_date); // Convert to timestamp
    $current_time = time(); // Get current timestamp

    if (($current_time - $otp_created_time) > 120) { // 120 seconds = 2 minutes
        return new WP_REST_Response([
            'status'  => false,
            'message' => 'OTP expired. Please request a new one.'
        ], 400);
    }

    // Verify the OTP
    if (password_verify($otp, $latest_submission->otp)) {

        // Update is_verified to true (1) for the latest user entry
        $updated = $wpdb->update(
            $table_name,
            ['is_verified' => 1],  // Updating the is_verified field
            ['id' => $latest_submission->id],   // Where condition (latest entry)
            ['%d'], // Data format (integer)
            ['%d']  // Where condition format (integer)
        );

        if ($updated === false) {
            return new WP_REST_Response([
                'status'  => 'error',
                'message' => 'Database update failed'
            ], 500);
        }

        // Convert CSV fields back into arrays
        $industry   = !empty($latest_submission->industry) ? explode(',', $latest_submission->industry) : [];
        $outcomes   = !empty($latest_submission->outcomes) ? explode(',', $latest_submission->outcomes) : [];
        $challenges = !empty($latest_submission->challenges) ? explode(',', $latest_submission->challenges) : [];
        $areas      = !empty($latest_submission->areas) ? explode(',', $latest_submission->areas) : [];

        // Determine PDFs based on multi-select fields
        $pdfs = determine_pdfs(
            $latest_submission->experience,
            $industry,
            $outcomes,
            $challenges,
            $areas,
            $latest_submission->dedication,
            $latest_submission->salary
        );

        $mail_sent = send_pdf_email(
            $latest_submission->email,
            $latest_submission->name,
            $pdfs
        );

        return new WP_REST_Response([
            'status'  => $mail_sent ? 'success' : 'error',
            'message' => $mail_sent ? 'OTP verified, and PDFs sent successfully' : 'OTP verified, but error sending email',
            'email'   => $email,
            'is_verified' => true,
            'pdfs'    => $pdfs
        ], $mail_sent ? 200 : 500);

    } else {
        return new WP_REST_Response([
            'status'  => false,
            'message' => 'Invalid OTP. Please try again.'
        ], 400);
    }
}

// 4. PDF Selection Algorithm
function determine_pdfs($experience, $industry, $outcomes, $dedication, $challenges, $areas, $salary) {
    // PDF map based on experience and salary
    $pdf_map = [
        '0-3-years' => [
            'less-than-6-lakhs-per-anum' => ['starter-guide.pdf'],
            '6-12-lpa' => ['career-growth.pdf'],
            '13-18-lpa' => ['advanced-skills.pdf'],
            '19-24-lpa' => ['executive-tips.pdf']
        ],
        '4-6 Years' => [
            'default' => ['management-handbook.pdf']
        ],
        '7-9 Years' => [
            'default' => ['mid-level-leadership.pdf']
        ],
        '10-13 Years' => [
            'default' => ['senior-management.pdf']
        ],
        '13-15 Years' => [
            'default' => ['executive-strategies.pdf']
        ],
        '16+ Years' => [
            'management' => ['ceo-insights.pdf'],
            'entrepreneurship' => ['business-innovations.pdf']
        ]
    ];

    $selected_pdfs = [];

    // Experience-based selection
    if (isset($pdf_map[$experience])) {
        if (isset($pdf_map[$experience][$salary])) {
            $selected_pdfs = array_merge($selected_pdfs, $pdf_map[$experience][$salary]);
        } elseif (isset($pdf_map[$experience]['default'])) {
            $selected_pdfs = array_merge($selected_pdfs, $pdf_map[$experience]['default']);
        }
    }

    // Outcomes PDF mapping
    $outcomes_map = [
        'advance-within-my-current-role' => 'current-role-advancement.pdf',
        'pursue-leadership-and-managerial-positions' => 'team-management.pdf',
        'switch-to-a-different-industry' => 'industry-switch.pdf',
        'share-expertise-become-a-mentor' => 'mentorship-guide.pdf',
        'pursue-entrepreneurial-endeavors' => 'startup-tips.pdf',
        'build-a-professional-network' => 'networking-tips.pdf',
        'be-updated-with-industry-evolution' => 'industry-evolution.pdf'
    ];
    foreach ($outcomes as $goal) {
        if (isset($outcomes_map[$goal])) {
            $selected_pdfs[] = $outcomes_map[$goal];
        }
    }

    // Industry PDF mapping
    $industry_map = [
        'technology-and-digital-services' => 'tech-trends.pdf',
        'design' => 'design-inspiration.pdf',
        'data-science-business-analytics' => 'data-insights.pdf',
        'ai-machine-learning' => 'ai-future.pdf',
        'cloud-computing' => 'cloud-guide.pdf',
        'banking-financial-services' => 'finance-strategies.pdf',
        'cyber-security' => 'cybersecurity.pdf',
        'management' => 'management-bestpractices.pdf',
        'human-resource' => 'hr-tactics.pdf',
        'others' => 'industry-overview.pdf'
    ];
    foreach ($industry as $ind) {
        if (isset($industry_map[$ind])) {
            $selected_pdfs[] = $industry_map[$ind];
        }
    }

    // Challenges PDF mapping
    $challenges_map = [
        'cant-find-the-right-course-from-a-top-institute' => 'top-institute-guide.pdf',
        'not-enough-time-to-learn-new-skills' => 'time-management.pdf',
        'unsure-about-which-skills-are-in-demand' => 'skill-demand-trends.pdf',
        'difficulty-in-switching-careers' => 'career-transition.pdf'
    ];
    foreach ($challenges as $challenge) {
        if (isset($challenges_map[$challenge])) {
            $selected_pdfs[] = $challenges_map[$challenge];
        }
    }

    // Areas of Interest PDF mapping
    $areas_map = [
        'i-am-still-figuring-out' => 'career-exploration.pdf',
        'general-management-and-leadership' => 'leadership-skills.pdf',
        'technology-and-innovation' => 'tech-innovation.pdf',
        'entrepreneurship-and-business' => 'entrepreneurship.pdf',
        'data-science-and-analytics' => 'data-science-guide.pdf'
    ];
    foreach ($areas as $area) {
        if (isset($areas_map[$area])) {
            $selected_pdfs[] = $areas_map[$area];
        }
    }

    return array_unique($selected_pdfs);
}

// 5. Email sending with PDF attachments
// function send_pdf_email($email, $name, $pdfs) {
//     global $wpdb;

//     // Ensure user is verified before sending PDFs
//     $is_verified = $wpdb->get_var(
//         $wpdb->prepare(
//             "SELECT is_verified FROM {$wpdb->prefix}form_submissions WHERE email = %s ORDER BY id DESC LIMIT 1",
//             $email
//         )
//     );    
//     // echo '<pre>';
//     // print_r($is_verified);
//     // echo '</pre>';

//     if ($is_verified != 1) {
//         // echo '<pre>';
//         // print_r('not verified');
//         // echo '</pre>';
//         return false; // Do not send PDFs if user is not verified
//     }

//     $mail = new PHPMailer(true);
    
//     try {
//         // Configure SMTP (from previous setup)
//         $mail->isSMTP();
//         $mail->Host = SMTP_HOST;  // Specify main SMTP server
//         $mail->SMTPAuth = true;  // Enable SMTP authentication
//         $mail->Username = SMTP_USER; 
//         $mail->Password = SMTP_PASSWORD;
//         $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;  // Enable TLS encryption
//         $mail->Port = SMTP_PORT;  // TCP port to connect to

//         // Optionally check SMTP connection explicitly
//         if (!$mail->smtpConnect()) {
//             echo "<script>console.error('Failed to connect to SMTP server for {$email}.');</script>";
//             error_log("Failed to connect to SMTP server for {$email}.");
//             die;
//         }
//         // var_dump('smtp connected');
//         // echo "<script>console.log('smtp connected');</script>";
//         // echo '<pre>';
//         // print_r($pdfs);
//         // echo '</pre>';

//         $mail->isHTML(true);
//         // Email content
//         $mail->clearAllRecipients();
//         $mail->setFrom('careers@example.com', 'Career Services');
//         $mail->addAddress($email);
//         $mail->Subject = 'Your Custom Career Resources';

//         // Attach PDFs from uploads directory
//         $uploads_dir = wp_upload_dir()['basedir'].'/career-pdfs/';
//         foreach ($pdfs as $pdf) {
//             $path = $uploads_dir.sanitize_file_name($pdf);
//             if (file_exists($path)) {
//                 $mail->addAttachment($path);
//             }
//         }

//         // HTML body with personalized message
//         $mail->Body = "
//             <h1>Hi $name!</h1>
//             <p>Based on your experience and goals, we're sending these resources:</p>
//             <ul>
//                 <li>".implode('</li><li>', $pdfs)."</li>
//             </ul>
//         ";

//         return $mail->send();
//     } catch (Exception $e) {
//         error_log('Email Error: '.$e->getMessage());
//         return false;
//     }
// }
function send_pdf_email($email, $name, $pdfs) {
    global $wpdb;

    // Ensure user is verified before sending PDFs
    $is_verified = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT is_verified FROM {$wpdb->prefix}form_submissions WHERE email = %s ORDER BY id DESC LIMIT 1",
            $email
        )
    );    

    if ($is_verified != 1) {
        return false; // Do not send PDFs if user is not verified
    }

    $mail = new PHPMailer(true);

    try {
        // Configure SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'arun.basavaraja@terralogic.com';
        $mail->Password   = 'pjck ujcq vznw xpcg'; // Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Optional: Remove die (never use in API)
        if (!$mail->smtpConnect()) {
            error_log("Failed to connect to SMTP server for {$email}.");
            return false;
        }

        $mail->clearAllRecipients();
        $mail->setFrom('arun.basavaraja@terralogic.com', 'Career Services');
        $mail->addReplyTo('arun.basavaraja@terralogic.com', 'Career Services');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Your Custom Career Resources';

        // Attach PDFs from uploads directory
        $uploads_dir = wp_upload_dir()['basedir'] . '/career-pdfs/';
        foreach ($pdfs as $pdf) {
            $path = $uploads_dir . sanitize_file_name($pdf);
            if (file_exists($path)) {
                $mail->addAttachment($path);
            }
        }

        // Email Body
        $mail->Body = "
            <h1>Hi $name!</h1>
            <p>Based on your experience and goals, we're sending these resources:</p>
            <ul>
                <li>" . implode('</li><li>', array_map('htmlspecialchars', $pdfs)) . "</li>
            </ul>
        ";

        return $mail->send();
    } catch (Exception $e) {
        error_log('Email Error: ' . $e->getMessage());
        return false;
    }
}
