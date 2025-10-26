<?php
// Control core classes for avoid errors
if (class_exists('CSF')) {

  // Set a unique slug-like ID
  $prefix = 'tp_opt';

  // Create options
  CSF::createOptions($prefix, array(
    'menu_title' => 'Theme Options',
    'menu_slug'  => 'my-framework',
    'framework_title' => 'Theme Options',
  ));

  //logo
  CSF::createSection($prefix, array(
    'title'  => 'Logo & CTA',
    'fields' => array(
      array(
        'id'      => 'header-logo',
        'type'    => 'upload',
        'title'   => 'Logo main',
      ),
      array(
        'id'      => 'header-cta',
        'type'    => 'link',
        'title'   => 'Donate Now URL',
      ),
    )
  ));
  CSF::createSection($prefix, array(
    'title'  => 'Career Menu & CTA',
    'fields' => array(
      array(
        'id'      => 'header_cta_text',
        'type'    => 'text',
        'title'   => 'Add title ',
      ),
      array(
        'id'      => 'header_logo_career',
        'type'    => 'upload',
        'title'   => ' logo upload',
      ),
      array(
        'id'      => 'header_cta_url',
        'type'    => 'text',
        'title'   => 'Donate Now URL',
      ),
    )
  ));


  // Footer
  CSF::createSection($prefix, array(
    'title'  => 'Footer',
    'fields' => array(
      array(
        'id'      => 'footer_logo',
        'type'    => 'upload',
        'title'   => 'Footer Logo',
      ),
      array(
        'id'      => 'clear_doubts',
        'type'    => 'text',
        'title'   => 'Clear Doubt Contact',
      ),
      array(
        'id'      => 'jaro_phone',
        'type'    => 'text',
        'title'   => 'Footer Phone Number',
        'default' => ''
      ),
      array(
        'id'      => 'jaro_email',
        'type'    => 'text',
        'title'   => 'Footer Email',
        'default' => ''
      ),
      array(
        'id'      => 'footer_logo_description',
        'type'    => 'textarea',
        'title'   => 'Footer Logo Description',
        'default' => 'Lorem ipsum dollar.'
      ),
      array(
        'id'      => 'talk_to_expert',
        'type'    => 'text',
        'title'   => 'Talk to Expert Text',
        'default' => '.'
      ),
      array(
        'id'      => 'footer_about',
        'type'    => 'textarea',
        'title'   => 'Footer Content',
        'default' => 'Lorem ipsum dollar.'
      ),
    )
  ));


  // Social Media Tab
  CSF::createSection($prefix, array(
    'title'  => 'Social Media',
    'fields' => array(
      array(
        'id'      => 'social_media_twitter',
        'type'    => 'text',
        'title'   => 'Twitter',
      ),
      array(
        'id'      => 'social_media_facebook',
        'type'    => 'text',
        'title'   => 'Facebook',
      ),
      array(
        'id'      => 'social_media_instagram',
        'type'    => 'text',
        'title'   => 'Instagram',
      ),
      array(
        'id'      => 'social_media_linkedin',
        'type'    => 'text',
        'title'   => 'LinkedIn',
      ),
      array(
        'id'      => 'social_media_yt',
        'type'    => 'text',
        'title'   => 'YouTube',
      ),
    )
  ));
}
