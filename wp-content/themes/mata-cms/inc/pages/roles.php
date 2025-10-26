<?php
defined('ABSPATH') || exit;

/**
 * -------------------------------
 * JWT Auth Setup (wp-config.php)
 * -------------------------------
 * Make sure these lines are in wp-config.php:
 *
 * define('JWT_AUTH_SECRET_KEY', 'your-top-secret-key');
 * define('JWT_AUTH_CORS_ENABLE', true);
 *
 * Install & activate: JWT Authentication for WP REST API
 */

/**
 * -------------------------------
 * Role-based API: Custom Post Type Data
 * -------------------------------
 */
function roles_cpt_data(WP_REST_Request $request) {

    // Get current user
    $user = wp_get_current_user();

    // Check allowed roles
    $allowed_roles = ['administrator', 'editor']; // customize roles
    if (!array_intersect($allowed_roles, $user->roles)) {
        return [
            'status' => false,
            'message' => 'You are not allowed to access this API',
        ];
    }

    // Fetch custom post type 'roles'
    $args = [
        'post_type' => 'roles',   // <-- your custom post type slug
        'posts_per_page' => -1,   // all posts
        'post_status' => 'publish'
    ];
    $posts = get_posts($args);
    $data = [];

    foreach ($posts as $post) {

         $repeater_data = [];

    // Check if ACF repeater field has rows
    if (have_rows('roles_optiona', $post->ID)) {
        while (have_rows('roles_optiona', $post->ID)) {
            the_row();
            $repeater_data[] = [
                'optionname' => get_sub_field('optionname'),
            ];
        }
    }
        $data[] = [
            'id' => $post->ID,
            'title' => get_the_title($post->ID),
            'role_details' => $repeater_data,
        ];
    }

    if (!empty($data)) {
        return [
            'status' => true,
            'message' => 'success',
            'data' => $data
        ];
    } else {
        return [
            'status' => false,
            'message' => 'No roles found',
        ];
    }
}

/**
 * -------------------------------
 * Register REST API Route
 * -------------------------------
 */
add_action('rest_api_init', function () {
    register_rest_route('api', 'roles', [
        'methods' => 'POST',
        'callback' => 'roles_cpt_data',
        'permission_callback' => function () {
            return is_user_logged_in(); // JWT token must be valid
        }
    ]);
});
