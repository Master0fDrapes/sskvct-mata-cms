<?php function create_topics_cat_taxonomy() {
    $labels = array(
        'name'              => _x( 'Topics Categories', 'taxonomy general name', 'text_domain' ),
        'singular_name'     => _x( 'Topics Category', 'taxonomy singular name', 'text_domain' ),
        'search_items'      => __( 'Search Topics Categories', 'text_domain' ),
        'all_items'         => __( 'All Topics Categories', 'text_domain' ),
        'parent_item'       => __( 'Parent Topics Category', 'text_domain' ),
        'parent_item_colon' => __( 'Parent Topics Category:', 'text_domain' ),
        'edit_item'         => __( 'Edit Topics Category', 'text_domain' ),
        'update_item'       => __( 'Update Topics Category', 'text_domain' ),
        'add_new_item'      => __( 'Add New Topics Category', 'text_domain' ),
        'new_item_name'     => __( 'New Topics Category Name', 'text_domain' ),
        'menu_name'         => __( 'Topics Category', 'text_domain' ),
    );
    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'topics-cat' ),
    );
    register_taxonomy( 'topics_cat', array( 'faqs' ), $args );
}
add_action( 'init', 'create_topics_cat_taxonomy', 0 );