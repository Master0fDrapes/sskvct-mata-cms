<?php

//Theme Support
add_theme_support('post-thumbnails', array('courses'));
add_theme_support('post-thumbnails');
add_theme_support('search-form');
add_theme_support('woocommerce');


//Tweaks & Custom Admin Welcome Message

require_once 'inc/junk_remove.php';
require_once 'inc/custom-admin-welcome.php';

//API's

require_once 'inc/pages/pages.php';





//Post type ,Taxonomies

require_once 'inc/post-type/postType.php';
require_once 'inc/taxonomy/taxonomies.php';

//admin

require_once 'inc/admin/codestar-framework.php';
require_once 'inc/admin-options.php';

//Add support for uploading SVG inside Wordpress Media Uploader

function svg_mime_types($mimes)
{
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
}
add_filter('upload_mimes', 'svg_mime_types');

//Custom admin footer text
function custom_admin_footer() {}
add_filter('admin_footer_text', 'custom_admin_footer');

//Menu
function menu_reg()
{
    register_nav_menus(
        array(
            'head_menu' => __('Header Menu'),
            'footer_menu' => __('Footer Menu'),
        )
    );
}
add_action('init', 'menu_reg');

// Build a hierarchical tree structure for menu items
function buildTree(array &$elements, $parentId = 0)
{
    $branch = array();
    $i = 0;
    foreach ($elements as &$element) {
        if ($element->menu_item_parent == $parentId) {
            $children = buildTree($elements, $element->ID);
            if ($children)
                $element->wpse_children = $children;

            $branch[$i++] = $element;
            unset($element);
        }
    }
    return $branch;
}
function wpse_nav_menu_2_tree($menu_id)
{
    $items = wp_get_nav_menu_items($menu_id);
    return $items ? buildTree($items, 0) : null;
}

//Read Time
function reading_time($id)
{
    global $post;
    $content = get_post_field('post_content', $id);
    $word_count = str_word_count(strip_tags($content));
    $readingtime = ceil($word_count / 200);
    if ($readingtime == 1) {
        $timer = " Min to Read";
    } else {
        $timer = " Mins to Read";
    }
    $totalreadingtime = $readingtime . $timer;
    return $totalreadingtime;
}


//Get Terms in array
function getTermsNameInArray($post_id, $taxonomy)
{
    $terms = get_the_terms($post_id, $taxonomy);
    return !empty($terms) && !is_wp_error($terms) ? wp_list_pluck($terms, 'name') : [];
};

//Pemove p Tag in Content
remove_filter('the_content', 'wpautop');
remove_filter('the_excerpt', 'wpautop');
