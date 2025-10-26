  <?php

  function custom_post_type_Roles()
  {
    $labels = array(
      'name' => _x('Roles', 'Post Type General Name', 'text_domain'),
      'singular_name' => _x('Roles', 'Post Type Singular Name', 'text_domain'),
      'menu_name' => __('Roles', 'text_domain'),
      'name_admin_bar' => __('Roles Type', 'text_domain'),
      'archives' => __('Item Archives', 'text_domain'),
      'attributes' => __('Item Attributes', 'text_domain'),
      'parent_item_colon' => __('Parent Item:', 'text_domain'),
      'all_items' => __('All Items', 'text_domain'),
      'add_new_item' => __('Add New Roles', 'text_domain'),
      'add_new' => __('Add New Roles', 'text_domain'),
      'new_item' => __('New Roles', 'text_domain'),
      'edit_item' => __('Edit Roles', 'text_domain'),
      'update_item' => __('Update Roles', 'text_domain'),
      'view_item' => __('View Roles', 'text_domain'),
      'view_items' => __('View Roles', 'text_domain'),
      'search_items' => __('Search Roles', 'text_domain'),
      'not_found' => __('Not found', 'text_domain'),
      'not_found_in_trash' => __('Not found in Trash', 'text_domain'),
      'featured_image' => __('Featured Image', 'text_domain'),
      'set_featured_image' => __('Set featured image', 'text_domain'),
      'remove_featured_image' => __('Remove featured image', 'text_domain'),
      'use_featured_image' => __('Use as featured image', 'text_domain'),
      'insert_into_item' => __('Insert into item', 'text_domain'),
      'uploaded_to_this_item' => __('Uploaded to this item', 'text_domain'),
      'items_list' => __('Roles list', 'text_domain'),
      'items_list_navigation' => __('Roles list navigation', 'text_domain'),
      'filter_items_list' => __('Roles items list', 'text_domain'),
    );
    $args = array(
      'label' => __('Roles', 'text_domain'),
      'description' => __('Roles Description', 'text_domain'),
      'menu_icon' => 'dashicons-welcome-learn-more',
      'labels' => $labels,
      'supports' => array('title', 'editor', 'thumbnail'),
      'taxonomies' => array('specialization_cat', 'university_cat', 'program_type_cat', 'duration_cat', 'admission_cat', 'skills_cat', 'tags'),
      'hierarchical' => false,
      'public' => true,
      'show_ui' => true,
      'show_in_menu' => true,
      'menu_position' => 5,
      'show_in_rest' => true,
      'show_in_admin_bar' => true,
      'show_in_nav_menus' => true,
      'can_export' => true,
      'has_archive' => true,
      'exclude_from_search' => false,
      'publicly_queryable' => true,
      'capability_type' => 'page',
    );
    register_post_type('Roles', $args);
  }
  add_action('init', 'custom_post_type_Roles', 0);
