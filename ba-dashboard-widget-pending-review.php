<?php /*

/*
Plugin Name:  Pendig Reviews Dashboard Widget
Plugin URI:   http://wordpress.org/extend/plugins/pendig-reviews-dashboard-widget/
Description:  Displays an Widget on your WordPress 2.7+ dashboard. The Widget shows a list of pending reviews of posts and pages (you can hide the page entrys from the list). Also in the configuration part of the widget you can set how many pending entrys you would like to display or hide the date or author information.
Version:      1.0.3.1
Author:       Stefan Brandt
Author URI:   http://blog.brandt-net.de/
Min WP Version: 2.7.*
Max WP Version: 3.8.0
*/

/*  Copyright 2008-2013 stefan Brandt  (email : blog@brandt-net.de)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define('BADashboard_PendingReview_DOMAIN', 'ba-dashboard-widget-pending-review');
define('BADashboard_PendingReview_OPTION_NAME', 'BADashboardPendingReview');
define('BADashboard_PendingReview_WidgetID', 'BADashboardPendingReview');

function BADashboardPendingReviewInitLanguageFiles() {
	// load language file
	if (function_exists('load_plugin_textdomain')) {
		if ( !defined('WP_PLUGIN_DIR') ) {
			load_plugin_textdomain(BADashboard_PendingReview_DOMAIN, str_replace( ABSPATH, '', dirname(__FILE__) ) . '/languages');
		} else {
			load_plugin_textdomain(BADashboard_PendingReview_DOMAIN, false, dirname( plugin_basename(__FILE__) ) . '/languages');
		}
	}
}

/** Main Widget function */
function BADashboardPendingReview_Main() {
	// Add the widget to the dashboard
	global $wpdb;
	$widget_options = BADashboardPendingReview_Options();

	$request = "SELECT $wpdb->posts.*, display_name as name FROM $wpdb->posts LEFT JOIN $wpdb->users ON $wpdb->posts.post_author=$wpdb->users.ID ";
	if ($widget_options['hidepages']) {
		$request .= "WHERE post_status='pending' AND post_type IN ('post') ";
	} else {
		$request .= "WHERE post_status='pending' AND post_type IN ('post','page') ";
	}

	$request .= "ORDER BY post_date DESC LIMIT ".$widget_options['items_view_count'];
	$posts = $wpdb->get_results($request);

	if ( $posts ) {
		echo "<ul id='ba-widget-pending-review-list'>\n";

		foreach ( $posts as $post ) {
			if (current_user_can( 'edit_post', $post->ID )) {
				$post_meta = sprintf('%s', '<a href="post.php?action=edit&amp;post=' . $post->ID . '">' . get_the_title($post->ID) . '</a> ' );
			} else {
				$post_meta = sprintf('%s', '<span style="text-decoration:underline">' . get_the_title() . '</span>' );
			}

			if($widget_options['showauthor']) {
				$post_meta.= sprintf('%s %s', __('by', BADashboard_PendingReview_DOMAIN), '<strong>'. $post->name .'</strong> ' );
			}

			if($widget_options['showtime']) {
				$time = get_post_time('G', true);

				if ( ( abs(time() - $time) ) < 86400 ) {
					$h_time = sprintf( __('%s ago', BADashboard_PendingReview_DOMAIN), human_time_diff( $time ) );
				} else {
					$h_time = mysql2date(__('Y/m/d'), $post->post_date);
				}

				$post_meta.= sprintf( __('&#8212; %s', BADashboard_PendingReview_DOMAIN),'<abbr title="' . get_post_time(__('Y/m/d H:i:s')) . '">' . $h_time . '</abbr>' );
			}

			echo "<li class='post-meta'>" . $post_meta . "</li>";
		}

		echo "</ul>\n";
	} else {
		echo '<p>' . _e( "No pending posts found.", BADashboard_PendingReview_DOMAIN ) . "</p>\n";
	}

}

/**
 * Setup the widget.
 * - reads the saved options from the database
 */
function BADashboardPendingReview_Setup() {
	$options = BADashboardPendingReview_Options();

	if ( 'post' == strtolower($_SERVER['REQUEST_METHOD']) && isset( $_POST['widget_id'] ) && BADashboard_PendingReview_WidgetID == $_POST['widget_id'] ) {
		foreach ( array( 'items_view_count', 'hidepages', 'showtime', 'showauthor' ) as $key )
		$options[$key] = $_POST[$key];
		update_option( BADashboard_PendingReview_OPTION_NAME, $options );
	}

	?>
<p><label for="items_view_count"><?php _e('How many pending entrys would you like to display?', BADashboard_PendingReview_DOMAIN ); ?>
<select id="items_view_count" name="items_view_count">

<?php
for ( $i = 5; $i <= 20; $i = $i + 1 )
echo "<option value='$i'" . ( $options['items_view_count'] == $i ? " selected='selected'" : '' ) . ">$i</option>";
?>
</select> </label></p>

<p><label for="hidepages"> <input id="hidepages" name="hidepages"
	type="checkbox" value="1"
	<?php if ( $options['hidepages'] == 1 ) echo ' checked="checked"'; ?> />
	<?php _e('Hide Pages?', BADashboard_PendingReview_DOMAIN ); ?> </label></p>


<p><label for="showauthor"> <input id="showauthor" name="showauthor"
	type="checkbox" value="1"
	<?php if ( $options['showauthor'] == 1 ) echo ' checked="checked"'; ?> />
	<?php _e('Show author?', BADashboard_PendingReview_DOMAIN ); ?> </label></p>

<p><label for="showtime"> <input id="showtime" name="showtime"
	type="checkbox" value="1"
	<?php if ( $options['showtime'] == 1 ) echo ' checked="checked"'; ?> />
	<?php _e('Show date?', BADashboard_PendingReview_DOMAIN ); ?> </label></p>

	<?php
} //end function

/** Options */

/** Configuration Options of the widget */
function BADashboardPendingReview_Options() {
	$defaults = array( 'items_view_count' => 5, 'hidepages' => 0, 'showtime' => 1, 'showauthor' => 1);
	if ( ( !$options = get_option( BADashboard_PendingReview_OPTION_NAME ) ) || !is_array($options) )
	$options = array();
	return array_merge( $defaults, $options );
}

/** initial the widget */
function BADashboardPendingReview_Init() {
	wp_add_dashboard_widget( BADashboard_PendingReview_WidgetID, __('Pending Reviews', BADashboard_PendingReview_DOMAIN), 'BADashboardPendingReview_Main', 'BADashboardPendingReview_Setup');
}


//*******************************************************************
// Start main
//*******************************************************************
{
	//Check WP Content Url
	// Pre-2.6 compatibility
	if ( !defined('WP_CONTENT_URL') )
	define( 'WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
	if ( !defined('WP_CONTENT_DIR') )
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );

	//Init the language files
	BADashboardPendingReviewInitLanguageFiles();

	/** use hook, to integrate the widget */
	add_action('wp_dashboard_setup', 'BADashboardPendingReview_Init');

} // end main



?>