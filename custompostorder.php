<?php
/**
 * @package B&T Newsletter Order Up!
 */
/*
Plugin Name: Newsletter Order
Plugin URI: http://drewgourley.com/order-up-custom-ordering-for-wordpress/
Description: Allows for the ordering of posts and custom post types through a simple drag-and-drop interface.
Version: 2.3.1
Author: Drew Gourley
Author URI: http://drewgourley.com/
License: GPLv2 or later
 */

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

$custompostorder_defaults = array('post' => 0, 'per_page' => 12, 'post_type' => 'post', 'post_types' => array('post'), 'post_type_count' => array('post' => 6));
$args = array( 'public' => true, '_builtin' => false );
$output = 'objects';

$custompostorder_defaults = apply_filters('custompostorder_defaults', $custompostorder_defaults);
$custompostorder_settings = get_option('custompostorder_settings');
$custompostorder_settings = wp_parse_args($custompostorder_settings, $custompostorder_defaults);
add_action('admin_init', 'custompostorder_register_settings');

function custompostorder_register_settings()
{
    register_setting('custompostorder_settings', 'custompostorder_settings', 'custompostorder_settings_validate');
}

/**
 * Update settings when the form is submitted
 */
function custompostorder_update_settings()
{

    global $custompostorder_settings, $custompostorder_defaults;

    // update settings on the main page

    if ( isset($custompostorder_settings['update']) ) {

        if ( !is_numeric($custompostorder_settings['per_page'] ) || $custompostorder_settings['per_page'] < 1 ) {
            echo '<div class="error fade" id="message"><p>The Entries Per Page setting must be a positive integer, value reset to default.</p></div>';
            $custompostorder_settings['per_page'] = $custompostorder_defaults['per_page'];
        }

        if ( strlen($custompostorder_settings['post_type']) == 0 ) {
            $custompostorder_settings['post_type'] = $custompostorder_defaults['post_type'];
        }

        $custompostorder_settings['per_page'] = min( 80, $custompostorder_settings['per_page'] );

        $custompostorder_settings['post_types'] = unserialize(urldecode($custompostorder_settings['post_types']));
        $custompostorder_settings['max_post_type'] = unserialize(urldecode($custompostorder_settings['max_post_type']));


        echo '<div class="updated fade" id="message"><p>Custom Post Order settings '.$custompostorder_settings['update'].'.</p></div>';
        unset($custompostorder_settings['update']);

    }
    // update the settings on the settings page
    // this is mostly to determine which post types to show
    elseif ( isset($custompostorder_settings["settings"]) ) {

        foreach( $custompostorder_settings["max_post_type"] as $key => $max_post_type ) {
            if ( $max_post_type == '' ) {
                $custompostorder_settings["max_post_type"][$key] = 0;
            }
        }

        if ( isset($custompostorder_settings["post_types"]) ) {
            $custompostorder_settings['post_types'] = $custompostorder_settings['post_types'];
        }
        else {
            $custompostorder_settings['post_types'] = $custompostorder_defaults['post_types'];
        }

        echo '<div class="updated fade" id="message"><p>Custom Post Order settings '.$custompostorder_settings['settings'].'.</p></div>';
        unset($custompostorder_settings['settings']);

    }



    update_option('custompostorder_settings', $custompostorder_settings);
}

function custompostorder_settings_validate($input)
{
    $input['post'] = ($input['post'] == 1 ? 1 : 0);
    $args = array( 'public' => true, '_builtin' => false );
    $output = 'objects';
    $input['per_page'] = wp_filter_nohtml_kses($input['per_page']);
    return $input;
}

/**
 * Define the menu for the admin
 */
function custompostorder_menu()
{
    $args = array( 'public' => true, '_builtin' => false );
    $output = 'objects';
    add_menu_page(__('Newsletter Order'),  __('Newsletter Order'), 'edit_posts', 'custompostorder', 'custompostorder', plugins_url('images/post_order.png', __FILE__), 120);
    add_submenu_page('custompostorder', __('Order Newsletters'), __('Order Newsletters'), 'edit_posts', "custompostorder", 'custompostorder');
    add_submenu_page('custompostorder', __('Settings'), __('Settings'), 'edit_posts', "custompostordersettings", 'custompostordersettings');

    // this adds the menu under the post menu
    add_posts_page(__('Order Newsletters', 'custompostorder'), __('Order Newsletters', 'custompostorder'), 'edit_posts', 'custompostorder', 'custompostorder');

    // this adds the menu under custom post type "news_article"
    add_submenu_page('edit.php?post_type=news_article', __('Order Newsletters'), __('Order Newsletters'), 'edit_posts', "custompostorder", 'custompostorder');
}

/**
 * Define custom CSS
 */
function custompostorder_css()
{
    if ( isset($_GET['page']) ) {
        $pos_page = $_GET['page'];
    } else {
        $pos_page = '';
    }
    $pos_args = 'custompostorder';
    $pos = strpos( $pos_page, $pos_args );
    if ( $pos !== false ) {
        wp_enqueue_style('custompostorder', plugins_url('css/custompostorder.css', __FILE__), 'screen');
    }
}

/**
 * Defined custom JS libraries
 */
function custompostorder_js_libs() {
    if ( isset($_GET['page']) ) {
        $pos_page = $_GET['page'];
    } else {
        $pos_page = '';
    }
    $pos_args = 'custompostorder';
    $pos = strpos( $pos_page, $pos_args );
    if ( $pos !== false ) {
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-sortable');
    }
}
add_action('admin_menu', 'custompostorder_menu');
add_action('admin_menu', 'custompostorder_css');
add_action('admin_print_scripts', 'custompostorder_js_libs');

/**
 * Main Page
 */
function custompostorder()
{
    global $custompostorder_settings;
    custompostorder_update_settings();
    $options = $custompostorder_settings;
    if ( isset( $_GET['paged'] ) ) {
        $page = max( 1, $_GET['paged'] );
    } else {
        $page = 1;
    }
    $settings = '';
    $parent_ID = 0;

    if ( $_GET['page'] == 'custompostorder' ) {
        $args = array( 'public' => true, '_builtin' => false );
        $output = 'objects';
        $settings .= '<input name="custompostorder_settings[post]" type="checkbox" value="1" ' . checked('1', $options['post'], false) . ' /> <label for="custompostorder_settings[post]">Check this box if you want to enable Automatic Sorting of all queries from this post type.</label>';
        $type_label = 'Newsletter';
        $type = 'post';
    } else {
        $args = array( 'public' => true, '_builtin' => false );
        $output = 'objects';
        $settings .= '<input name="custompostorder_settings[post]" type="hidden" value="' . $options['post'] . '" />';
    }

    if (isset($_POST['go-sub-posts'])) {
        $parent_ID = $_POST['sub-posts'];
    }
    elseif (isset($_POST['hidden-parent-id'])) {
        $parent_ID = $_POST['hidden-parent-id'];
    }
    if (isset($_POST['return-sub-posts'])) {
        $parent_post = get_post($_POST['hidden-parent-id']);
        $parent_ID = $parent_post->post_parent;
    }
    $message = "";
    if (isset($_POST['order-submit'])) {
        custompostorder_update_order( $page );
    }
?>
<div class='wrap'>
    <?php screen_icon('custompostorder'); ?>
    <h2><?php _e('Order ' . $type_label, 'custompostorder'); ?></h2>
    <form name="custom-order-form" method="post" action="">
        <?php

            $args = array(
                'orderby' => 'menu_order',
                'order' => 'ASC',
                'post_parent' => $parent_ID,
                'post_type' => $options['post_type'],
                'posts_per_page' => $options['per_page'],
                'paged' => $page,
                'meta_key' => 'add_to_newsletter',
                'meta_value' => '1'
            );

            $query = new WP_Query( $args );

            if ( ! $query->have_posts() ) {
                unset($args["meta_key"]);
                unset($args["meta_value"]);
                $args["category_name"] = "newsletter";
                $query = new WP_Query( $args );

            }

            if ( $query->have_posts() ) {

                if ( $page !== 1 ) {
                    $prev_page = $page-1;
                    $args['paged'] = $prev_page;
                    $prev_query = new WP_Query( $args );

                }

                if ( $page !== $query->max_num_pages ) {
                    $next_page = $page+1;
                    $args['paged'] = $next_page;
                    $next_query = new WP_Query( $args );
                }


        ?>
        <div id="poststuff" class="metabox-holder">

            <div class="widget order-widget">
                <h3 class="widget-top"><?php _e( $type_label, 'custompostorder') ?> | <small><?php _e('Order the news by dragging and dropping them into the desired order.', 'custompostorder') ?></small></h3>
                <div class="misc-pub-section">
                    <ul id="custom-order-list">

                        <?php if ( isset( $prev_query ) ) { if ( $prev_query->have_posts() ) { while ( $prev_query->have_posts() ) : $prev_query->the_post(); ?>
                        <li id="id_<?php the_ID(); ?>" class="lineitem outer"><?php the_title(); ?></li>
                        <?php endwhile; } } ?>

                        <?php while ( $query->have_posts() ) : $query->the_post(); ?>
                        <li id="id_<?php the_ID(); ?>" class="lineitem"><input type="checkbox" name="show_posts[]" value="<?php the_ID() ?>" style="display:none;"><?php the_title(); ?></li>
                        <?php endwhile; ?>

                        <?php if ( isset( $next_query ) ) { if ( $next_query->have_posts() ) { while ( $next_query->have_posts() ) : $next_query->the_post(); ?>
                        <li id="id_<?php the_ID(); ?>" class="lineitem outer"><?php the_title(); ?></li>
                        <?php endwhile; } } ?>

                    </ul>
                </div>

                <?php $big = 999999999;

                $args = array(
                    'base' => str_replace( $big, '%#%', get_pagenum_link( $big ) ),
                    'format' => '&paged=%#%',
                    'prev_next' => false,
                    'current' => $page,
                    'total' => $query->max_num_pages
                    );

                $pagination = paginate_links($args);

                if ( !empty($pagination) ) { ?>

                <div class="misc-pub-section">
                    <div class="tablenav" style="margin:0">
                        <div class="tablenav-pages">
                            <span class="pagination-links"><?php echo $pagination; ?></span>
                        </div>
                    </div>
                </div>

                <?php } ?>

                <div class="misc-pub-section misc-pub-section-last">
                    <?php if ($parent_ID != 0) { ?>
                        <input type="submit" class="button" style="float:left" id="return-sub-posts" name="return-sub-posts" value="<?php _e('Return to Parent', 'custompostorder'); ?>" />
                    <?php } ?>
                    <div id="publishing-action">
                        <img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" id="custom-loading" style="display:none" alt="" />
                        <input type="submit" name="order-submit" id="order-submit" class="button-primary" value="<?php _e('Update Order', 'custompostorder') ?>" />
                    </div>
                    <div class="clear"></div>
                    </div>
                <input type="hidden" id="hidden-custom-order" name="hidden-custom-order" />
                <input type="hidden" id="hidden-parent-id" name="hidden-parent-id" value="<?php echo $parent_ID; ?>" />
            </div>

            <?php $dropdown = custompostorder_sub_query( $query, $type ); if( !empty($dropdown) ) { ?>

            <div class="widget order-widget">
                <h3 class="widget-top"><?php _e('Sub-' . $type_label, 'custompostorder'); ?> | <small><?php _e('Choose a post from the drop down to order its sub-posts.', 'custompostorder'); ?></small></h3>
                <div class="misc-pub-section misc-pub-section-last">
                    <select id="sub-posts" name="sub-posts">
                        <?php echo $dropdown; ?>
                    </select>
                    <input type="submit" name="go-sub-posts" class="button" id="go-sub-posts" value="<?php _e('Order Sub-posts', 'custompostorder') ?>" />
                </div>
            </div>

            <?php } ?>
        </div>
        <?php } else { ?>
        <p><?php _e('No posts found', 'customtaxorder'); ?></p>
        <?php } ?>
    </form>
    <form method="post" action="options.php">
        <?php settings_fields('custompostorder_settings'); ?>
        <table class="form-table">
            <tr valign="top"><th scope="row">Auto-Sort Queries</th>
            <td><?php echo $settings; ?></td>
            </tr>
            <tr valign="top"><th scope="row">Entries Per Page</th>
            <td><input name="custompostorder_settings[per_page]" type="text" value="<?php echo $options['per_page']; ?>" style="width:48px" /></td>
            </tr>
            <tr valign="top"><th scope="row">Post Type</th>
            <td>
                <select name="custompostorder_settings[post_type]">
                    <?php foreach($options["post_types"] as $pt) : ?>
                        <option value="<?php echo $pt; ?>" <?php if ( $pt == $options['post_type'] ) echo 'selected="selected"' ?>><?php echo $pt; ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <tr valign="top"><th scope="row">Number of Articles</th>
            <td>
                <table class="form-table">
                    <?php foreach($options["post_types"] as $pt) : ?>
                    <tr>
                        <th><?php echo $pt ?></th>
                        <td><input type="text" name="post_type_count[<?php echo $pt ?>]" value=""></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </td>
            </tr>
        </table>

        <input type="hidden" name="custompostorder_settings[post_types]" value="<?php echo urlencode(serialize($options["post_types"])) ?>" />
        <input type="hidden" name="custompostorder_settings[max_post_type]" value="<?php echo urlencode(serialize($options["max_post_type"])) ?>" />
        <input type="hidden" name="custompostorder_settings[update]" value="Updated" />
        <p class="submit">
            <input type="submit" class="button-primary" value="<?php _e('Save Settings') ?>" />
        </p>

    </form>
</div>

<?php if ( $query->have_posts() ) { ?>
<script type="text/javascript">
// <![CDATA[
    jQuery(document).ready(function($) {
        $("#custom-loading").hide();
        $("#order-submit").click(function() {
            orderSubmit();
        });
    });
    function custompostorderAddLoadEvent(){
        jQuery("#custom-order-list").sortable({
            placeholder: "sortable-placeholder",
            revert: false,
            tolerance: "pointer"
        });
    };
    addLoadEvent(custompostorderAddLoadEvent);
    function orderSubmit() {
        var newOrder = jQuery("#custom-order-list").sortable("toArray");
        jQuery("#custom-loading").show();
        jQuery("#hidden-custom-order").val(newOrder);
        return true;
    }
// ]]>
</script>
<?php }
}

/**
 * Process submitted form
 */
function custompostorder_update_order( $page = 1 )
{
    global $custompostorder_settings;
    $options = $custompostorder_settings;
    if ( isset( $_POST['hidden-custom-order'] ) && $_POST['hidden-custom-order'] != '' ) {

        if ( $page > 2 ) {
            $offset = ( $page - 1 ) * $options['per-page'];
        }
        else {
            $offset = 0;
        }

        //update_option('custompostorder_order', $_POST["show_posts"]);

        $new_order = $_POST['hidden-custom-order'];
        $IDs = explode(",", $new_order);
        $result = count($IDs);

        for ( $i = 0; $i < $result; $i++ ) {

            $str = str_replace("id_", "", $IDs[$i]);
            $order = $i + $offset;
            $update = array('ID' => $str, 'menu_order' => $order);
            wp_update_post( $update );

        }

        echo '<div id="message" class="updated fade"><p>'. __('Order updated successfully.', 'custompostorder').'</p></div>';

    }
    else {

        echo '<div id="message" class="error fade"><p>'. __('An error occured, order has not been saved.', 'custompostorder').'</p></div>';
    }
}
function custompostorder_sub_query( $query, $type ) {
    $options = '';
    while ( $query->have_posts() ) : $query->the_post();
        $page_ID = get_the_ID();
        $args = array( 'post_parent' => $page_ID, 'post_type' => $type );
        $subpages = new WP_Query( $args );
        if ( $subpages->have_posts() ) {
            $options .= '<option value="' . $page_ID . '">' . get_the_title($page_ID) . '</option>';
        }
    endwhile;
    return $options;
}

/**
 * Settings Page
 */
function custompostordersettings() {

    global $custompostorder_settings;
    custompostorder_update_settings();
    $options = $custompostorder_settings;

    ?>
    <div class="wrap">
    <?php screen_icon('custompostorder'); ?>
    <h2><?php _e('Order Settings', 'custompostorder'); ?></h2>

    <form method="post" action="options.php">
        <?php settings_fields('custompostorder_settings'); ?>
        <table class="form-table">
            <tr valign="top"><th scope="row">Order Post Types</th>
                <td>
                    <table>
                    <?php foreach(get_post_types() as $post_type) :

                        $checked = ( in_array($post_type, $options["post_types"]) ? 'checked="checked"' : "" );
                        ?>
                        <tr>
                            <td><input type="checkbox" name="custompostorder_settings[post_types][]" value="<?php echo $post_type ?>" <?php echo $checked ?> /> <?php echo $post_type ?><br></td>
                            <td><input type="text" name="custompostorder_settings[max_post_type][<?php echo $post_type ?>]" value="<?php echo (isset($options["max_post_type"][$post_type])) ? $options["max_post_type"][$post_type] : 0 ?>"></td>
                        </tr>

                    <?php endforeach; ?>
                    </table>
                </td>
            </tr>
        </table>

        <input type="hidden" name="custompostorder_settings[settings]" value="Updated" />
        <p class="submit">
            <input type="submit" class="button-primary" value="<?php _e('Save Settings') ?>" />
        </p>
    </form>
</div>
<?php
}

?>
