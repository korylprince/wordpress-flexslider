<?php
/*
Plugin Name: Flexslider
Plugin URI: https://github.com/korylprince/wordpress-flexslider
Description: Flexslider for Wordpress
Version: 1.0 
Author: Kory Prince 
Author URI: http://unstac.tk/ 
License: GPL2
 */
/*  Copyright 2013 Kory Prince  (email : korylprince@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

wp_register_style('flexslider',plugins_url('css/flexslider.css', __FILE__));
wp_register_style('flexslider-uploader',plugins_url('css/uploader.css', __FILE__));
wp_register_script('flexslider',plugins_url('js/jquery.flexslider-min.js', __FILE__));
wp_register_script('flexslider-uploader',plugins_url('js/uploader.js', __FILE__));

add_action( 'wp_enqueue_scripts', 'flexslider_register_scripts' );

function flexslider_register_scripts() { 
    wp_enqueue_style('flexslider');
    wp_enqueue_script('jquery');
    wp_enqueue_script('flexslider');
}

function flexslider_register_admin_scripts() { 
    wp_enqueue_style('flexslider-uploader');
    wp_enqueue_script('jquery');
    wp_enqueue_script('flexslider-uploader');
}

function flexslider_insert($attrs) {
    return '<span>Flexslider</span>';
}
add_shortcode('flexslider','flexslider_insert');


add_action( 'add_meta_boxes', 'flexslider_add_meta_box' );
add_action( 'save_post', 'flexslider_save' );

function flexslider_add_meta_box() {
    $screens = array( 'post', 'page' );
    foreach ($screens as $screen) {
        add_meta_box(
            'flexslider',
            'Flexslider',
            'flexslider_create_meta_box',
            $screen
        );
    }
    add_action( 'admin_enqueue_scripts','flexslider_register_admin_scripts');
}

function flexslider_create_meta_box($post) {
    wp_nonce_field( plugin_basename( __FILE__ ), 'flexslider_nonce' );
    $id = $post->ID;
    $order = get_post_meta($id, '_flexslider_order',true);
    if (isset($_GET['flexslider_message']) && $_GET['flexslider_message'] == 1 ) {
        echo '<strong style="color:#f00;">Order must be an Integer!</strong><br />';
    }
    echo '<label for="_flexslider_order">Order:</label> <input type="text" id="_flexslider_order" name="_flexslider_order" value="'.esc_attr($order).'" size="25" /><br />';

        echo '<div id="flexslider_uploader" data-type="'.get_post_type($id).'">'
        .flexslider_create_image($id)
        .'</div>';
}

function flexslider_create_image ($id) {
    $image_id = get_post_meta($id, '_flexslider_image',true);
    if ($image_id) {
        return '<img id="flexslider_image" src="'.wp_get_attachment_url($image_id).'" data-id='.$image_id.' /><br />'
            .'<a id="flexslider_remove">Remove Slider Image</a>';
    }
    else {
        return '<a id="flexslider_add">Add Slider Image</a>';
    }

}

function flexslider_save( $post_id, $ajax = false ) {
    // First we need to check if the current user is authorised to do this action. 
    if ( 'page' == $_POST['post_type'] ) {
        if ( ! current_user_can( 'edit_page', $post_id ) )
            return;
    } else {
        if ( ! current_user_can( 'edit_post', $post_id ) )
            return;
    }

    // Secondly we need to check if the user intended to change this value.
    if ( ! isset( $_POST['flexslider_nonce'] ) || ! wp_verify_nonce( $_POST['flexslider_nonce'], plugin_basename( __FILE__ ) ) )
        return;

    //Thirdly check if trying to autosave
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
        return;

    // Finally we can save the value to the database
    $post_ID = $_POST['post_ID'];
    if (!$ajax) {
        //sanitize user input
        $order = sanitize_text_field( $_POST['_flexslider_order'] );
        if (is_numeric($order)) {
            $order = intval($order);
            add_post_meta($post_ID, '_flexslider_order', $order, true) or
                update_post_meta($post_ID, '_flexslider_order', $order);
        }
        elseif ($order == '') {
            add_post_meta($post_ID, '_flexslider_order', $order, true) or
                update_post_meta($post_ID, '_flexslider_order', $order);
        }
        else {
            add_filter('redirect_post_location','flexslider_invalid_order');
        }
    }
    else {
        $image_id = sanitize_text_field( $_POST['_flexslider_image'] );
        if ($image_id == -1) { $image_id = '';}
        add_post_meta($post_ID, '_flexslider_image', $image_id, true) or
        update_post_meta($post_ID, '_flexslider_image', $image_id);
    }
}

function flexslider_invalid_order($loc) {
    return add_query_arg('flexslider_message',1,$loc);
}

add_action('wp_ajax_set-flexslider-image','flexslider_save_image');

function flexslider_save_image() {
    flexslider_save($_POST['post_ID'],true);
    header('Content-type: text/json');
    echo json_encode(array('success'=>true,'data'=>flexslider_create_image($_POST['post_ID'])));
    die();
}

function flexslider_shortcode($attrs) {

    echo '
<div class="flexslider">
    <ul class="slides">';
    
    $posts = new WP_Query( 'meta_key=_flexslider_image' );
    $posts = $posts->posts;
    $pages = new WP_Query( 'meta_key=_flexslider_image&post_type=page' );
    $pages = $pages->posts;
    foreach( array_merge($posts,$pages) as $post) {
        $id = $post->ID;
        //skip if empty
        if (get_post_meta($id,'_flexslider_image',true) == '') {continue;}
        echo '<li data-order="'.get_post_meta($id,'_flexslider_order',true).'"><a class="slider-link" href="'.get_permalink($id).'"><img src="'.wp_get_attachment_url(get_post_meta($id,'_flexslider_image',true), 'large' ).'" /></a></li>';

    }

    echo '</ul></div>';

    echo '<script type="text/javascript">
    jQuery(window).load(function() {
        var li = jQuery(".slides li");
        li.detach().sort(function(a,b){
            var ao = jQuery(a).data("order") || 1000;
            var bo = jQuery(b).data("order") || 1000;
            return ao-bo;
        });
        jQuery(".slides").append(li);
        jQuery(".flexslider").flexslider({
            animation: "fade",
            smoothHeight: true,
            animationSpeed: 500
        });
    });
</script>';
}
add_shortcode('flexslider','flexslider_shortcode');

?>
