<?php

/**
 * Plugin Name: Direktt Customer Review
 * Description: Direktt Customer Review Direktt Plugin
 * Version: 1.0.2
 * Author: Direktt
 * Author URI: https://direktt.com/
 * License: GPL2
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$direktt_customer_review_plugin_version = "1.0.2";
$direktt_customer_review_github_update_cache_allowed = true;

require_once plugin_dir_path( __FILE__ ) . 'direktt-github-updater/class-direktt-github-updater.php';

$direktt_customer_review_plugin_github_updater  = new Direktt_Github_Updater( 
    $direktt_customer_review_plugin_version, 
    'direktt-customer-review/direktt-customer-review.php',
    'https://raw.githubusercontent.com/direktt/direktt-customer-review/master/info.json',
    'direktt_customer_review_github_updater',
    $direktt_customer_review_github_update_cache_allowed );

add_filter( 'plugins_api', array( $direktt_customer_review_plugin_github_updater, 'github_info' ), 20, 3 );
add_filter( 'site_transient_update_plugins', array( $direktt_customer_review_plugin_github_updater, 'github_update' ));
add_filter( 'upgrader_process_complete', array( $direktt_customer_review_plugin_github_updater, 'purge'), 10, 2 );

add_action( 'plugins_loaded', 'direktt_customer_review_activation_check', -20 );

function direktt_customer_review_activation_check() {
    if ( ! function_exists( 'is_plugin_active' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $required_plugin = 'direktt/direktt.php';
    $is_required_active = is_plugin_active($required_plugin)
        || (is_multisite() && is_plugin_active_for_network($required_plugin));

    if (! $is_required_active) {
        // Deactivate this plugin
        deactivate_plugins(plugin_basename(__FILE__));

        // Prevent the “Plugin activated.” notice
        if (isset($_GET['activate'])) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Justification: not a form processing, just removing a query var.
            unset($_GET['activate']);
        }

        // Show an error notice for this request
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error is-dismissible"><p>'
                . esc_html__('Direktt Customer Review activation failed: The Direktt WordPress Plugin must be active first.', 'direktt-customer-review')
                . '</p></div>';
        });

        // Optionally also show the inline row message in the plugins list
        add_action(
            'after_plugin_row_direktt-customer-review/direktt-customer-review.php',
            function () {
                echo '<tr class="plugin-update-tr"><td colspan="3" style="box-shadow:none;">'
                    . '<div style="color:#b32d2e;font-weight:bold;">'
                    . esc_html__('Direktt Customer Review requires the Direktt WordPress Plugin to be active. Please activate it first.', 'direktt-customer-review')
                    . '</div></td></tr>';
            },
            10,
            0
        );
    }
}

add_action( 'direktt_setup_settings_pages', 'setup_review_settings_page' );

function setup_review_settings_page() {
    Direktt::add_settings_page(
        array(
            'id'       => 'review',
            'label'    => esc_html__( 'Customer Review Settings', 'direktt-customer-review' ),
            'callback' => 'render_review_settings_page',
            'priority' => 2,
        )
    );
}

function render_review_settings_page() {
    $success = false;

    // Handle form submission
    if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['direktt_admin_review_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['direktt_admin_review_nonce'] ) ), 'direktt_admin_review_save' ) ) {
        // update options based on form submission
        update_option( 'direktt_review_template', isset( $_POST['direktt_review_template'] ) ? intval( $_POST['direktt_review_template'] ) : 0 );
        update_option( 'direktt_review_min_rating', isset( $_POST['direktt_review_min_rating'] ) ? intval( $_POST['direktt_review_min_rating'] ) : 0 );
        update_option( 'direktt_review_max_rating', isset( $_POST['direktt_review_max_rating'] ) ? intval( $_POST['direktt_review_max_rating'] ) : 0 );
        update_option( 'direktt_review_threshold', isset( $_POST['direktt_review_threshold'] ) ? intval( $_POST['direktt_review_threshold'] ) : 0 );
        update_option( 'direktt_review_under_treshold_template', isset( $_POST['direktt_review_under_threshold_template'] ) ? intval( $_POST['direktt_review_under_threshold_template'] ) : 0 );
        update_option( 'direktt_review_over_treshold_template', isset( $_POST['direktt_review_over_threshold_template'] ) ? intval( $_POST['direktt_review_over_threshold_template'] ) : 0 );
        update_option( 'direktt_review_send_to_admin', isset( $_POST['direktt_review_send_to_admin'] ) ? 'yes' : 'no' );
        update_option( 'direktt_review_admin_template', isset( $_POST['direktt_review_admin_template'] ) ? intval( $_POST['direktt_review_admin_template'] ) : 0 );
        $success = true;
    }

    // Load stored values
    $review_template                 = intval( get_option( 'direktt_review_template', 0 ) );
    $review_min_rating               = intval( get_option( 'direktt_review_min_rating', 1 ) );
    $review_max_rating               = intval( get_option( 'direktt_review_max_rating', 5 ) );
    $review_threshold                = intval( get_option( 'direktt_review_threshold', 3 ) );
    $review_under_threshold_template = intval( get_option( 'direktt_review_under_treshold_template', 0 ) );
    $review_over_threshold_template  = intval( get_option( 'direktt_review_over_treshold_template', 0 ) );
    $send_to_admin                   = intval( get_option( 'direktt_review_send_to_admin', 0 ) );
    $send_to_admin                   = get_option( 'direktt_review_send_to_admin', 'no' ) === 'yes';
    $review_admin_template           = intval( get_option( 'direktt_review_admin_template', 0 ) );

    // Query for template posts
    $template_args  = array(
        'post_type'      => 'direkttmtemplates',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- - Justification: bounded, cached, selective query on small dataset
            array(
                'key'     => 'direkttMTType',
                'value'   => array( 'all', 'none' ),
                'compare' => 'IN',
            ),
        ),
    );
    $template_posts = get_posts( $template_args );
    ?>
    <div class="wrap">
        <?php if ( $success ) : ?>
            <div class="updated notice is-dismissible">
                <p><?php echo esc_html__( 'Settings saved successfully.', 'direktt-customer-review' ); ?></p>
            </div>
        <?php endif; ?>
        <form method="post" action="">
            <?php wp_nonce_field( 'direktt_admin_review_save', 'direktt_admin_review_nonce' ); ?>

<<<<<<< HEAD
            <h2 class="title"><?php echo esc_html__( 'Review Message', 'direktt-customer-review' ); ?></h2>
            <table class="form-table">
=======
            <table class="form-table direktt-customer-review-table">
>>>>>>> 7190d9ec3be586e337fe68d6b8fe89f34108bb95
                <tr>
                    <th scope="row"><label for="direktt_review_template"><?php echo esc_html__( 'Message Template', 'direktt-customer-review' ); ?></label></th>
                    <td>
                        <select name="direktt_review_template" id="direktt_review_template">
                            <option value="0"><?php echo esc_html__( 'Select Template', 'direktt-customer-review' ); ?></option>
                            <?php foreach ( $template_posts as $post ) : ?>
                                <option value="<?php echo esc_attr( $post->ID ); ?>" <?php selected( $review_template, $post->ID ); ?>>
                                    <?php echo esc_html( $post->post_title ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php echo esc_html__( 'This message template will be followed by the interactive message with the rating buttons', 'direktt-customer-review' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html__( 'Rating', 'direktt-customer-review' ); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php echo esc_html__( 'Rating', 'direktt-customer-review' ); ?></span></legend>
                            <label for="direktt_review_min_rating"><?php echo esc_html__( 'Minimum', 'direktt-customer-review' ); ?></label>
                            <input type="number" name="direktt_review_min_rating" id="direktt_review_min_rating" value="<?php echo esc_attr( $review_min_rating ); ?>" min="0" class="small text"/>
                            <br>
                            <label for="direktt_review_max_rating"><?php echo esc_html__( 'Maximum', 'direktt-customer-review' ); ?></label>
                            <input type="number" name="direktt_review_max_rating" id="direktt_review_max_rating" value="<?php echo esc_attr( $review_max_rating ); ?>" min="0" class="small text"/>
                        </fieldset>
                        <p class="description"><?php echo esc_html__( 'Define the rating scale for the customer reviews.', 'direktt-customer-review' ); ?></p>
                    </td>
                </tr>
            </table>
            <h2 class="title"><?php echo esc_html__( 'Review Handling', 'direktt-customer-review' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="direktt_review_threshold"><?php echo esc_html__( 'Threshold Rating', 'direktt-customer-review' ); ?></label></th>
                    <td>
                        <input type="number" name="direktt_review_threshold" id="direktt_review_threshold" value="<?php echo esc_attr( $review_threshold ); ?>" min="0" />
                        <p class="description"><?php echo esc_html__( 'Define the threshold rating to differentiate between positive and negative reviews.', 'direktt-customer-review' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="direktt_review_under_threshold_template"><?php echo esc_html__( 'Under Threshold Template', 'direktt-customer-review' ); ?></label></th>
                    <td>
                        <select name="direktt_review_under_threshold_template" id="direktt_review_under_threshold_template">
                            <option value="0"><?php echo esc_html__( 'Select Template', 'direktt-customer-review' ); ?></option>
                            <?php foreach ( $template_posts as $post ) : ?>
                                <option value="<?php echo esc_attr( $post->ID ); ?>" <?php selected( $review_under_threshold_template, $post->ID ); ?>>
                                    <?php echo esc_html( $post->post_title ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php echo esc_html__( 'If rating is below this threshold, the under threshold template will be used.', 'direktt-customer-review' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="direktt_review_over_threshold_template"><?php echo esc_html__( 'Over Threshold Template', 'direktt-customer-review' ); ?></label></th>
                    <td>
                        <select name="direktt_review_over_threshold_template" id="direktt_review_over_threshold_template">
                            <option value="0"><?php echo esc_html__( 'Select Template', 'direktt-customer-review' ); ?></option>
                            <?php foreach ( $template_posts as $post ) : ?>
                                <option value="<?php echo esc_attr( $post->ID ); ?>" <?php selected( $review_over_threshold_template, $post->ID ); ?>>
                                    <?php echo esc_html( $post->post_title ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php echo esc_html__( 'If rating is above this threshold, the over threshold template will be used.', 'direktt-customer-review' ); ?></p>
                    </td>
                </tr>
            </table>
            <h3><?php echo esc_html__( 'Send Message to Admin', 'direktt-customer-review' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="direktt_review_send_to_admin"><?php echo esc_html__( 'Enable', 'direktt-customer-review' ); ?></label></th>
                    <td>
                        <input type="checkbox" name="direktt_review_send_to_admin" id="direktt_review_send_to_admin" value="yes" <?php checked( $send_to_admin ); ?> />
                        <label for="direktt_review_send_to_admin" class="description"><?php echo esc_html__( 'When enabled, a notification will be sent to the admin when a review is submitted.', 'direktt-customer-review' ); ?></label>
                    </td>
                </tr>
                <tr id="direktt-customer-review-settings-mt-admin-row">
<<<<<<< HEAD
                    <th scope="row"><label for="direktt_review_admin_template"><?php echo esc_html__( 'Message Template', 'direktt-customer-review' ); ?></label></th>
=======
                    <th scope="row"><label for="direktt_review_admin_template"><?php echo esc_html__( 'Admin Template', 'direktt-customer-review' ); ?></label></th>
>>>>>>> 7190d9ec3be586e337fe68d6b8fe89f34108bb95
                    <td>
                        <select name="direktt_review_admin_template" id="direktt_review_admin_template">
                            <option value="0"><?php echo esc_html__( 'Select Template', 'direktt-customer-review' ); ?></option>
                            <?php foreach ( $template_posts as $post ) : ?>
                                <option value="<?php echo esc_attr( $post->ID ); ?>" <?php selected( $review_admin_template, $post->ID ); ?>>
                                    <?php echo esc_html( $post->post_title ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php echo esc_html__( 'This message will be sent to the admin when a review is submitted.', 'direktt-customer-review' ); ?></p>
<<<<<<< HEAD
                        <p class="description"><?php echo esc_html__( 'You can use following dynamic placeholders in this template:', 'direktt-customer-review' ); ?></p>
						<p class="description"><code><?php echo esc_html( '#display_name#' ); ?></code><?php echo esc_html__( ' - user\'s display name.', 'direktt-customer-review' ); ?></p>
						<p class="description"><code><?php echo esc_html( '#subscription_id#' ); ?></code><?php echo esc_html__( ' - user\'s subscription ID.', 'direktt-customer-review' ); ?></p>
					
=======
                        <p class="description"><?php echo esc_html__( 'You can use placeholders', 'direktt-customer-review' ); ?> <?php echo esc_html( '#display_name#' ); ?> <?php echo esc_html__( 'for display name, and', 'direktt-customer-review' ); ?> <?php echo esc_html( '#subscription_id#' ); ?> <?php echo esc_html__( 'for subscription id', 'direktt-customer-review' ); ?></p>
                        <p class="description"><?php echo esc_html__( 'and', 'direktt-customer-review' ); ?> <?php echo esc_html( '#rating#' ); ?> <?php echo esc_html__( 'for rating that selected.', 'direktt-customer-review' ); ?></p>
>>>>>>> 7190d9ec3be586e337fe68d6b8fe89f34108bb95
                    </td>
                </tr>
            </table>

            <?php
            // $allowed_html = wp_kses_allowed_html( 'post' );
            // echo wp_kses( Direktt_Public::direktt_render_alert_popup( 'direktt-review-settings-alert', '' ), $allowed_html );
            ?>

            <script>
                jQuery(document).ready(function ($) {
                    const form = $('form');
                    const minRatingInput = $('#direktt_review_min_rating');
                    const maxRatingInput = $('#direktt_review_max_rating');
                    const thresholdInput = $('#direktt_review_threshold');
                    
                    form.on('submit', function (event) {
                        const minRating = parseInt(minRatingInput.val(), 10);
                        const maxRating = parseInt(maxRatingInput.val(), 10);
                        const threshold = parseInt(thresholdInput.val(), 10);
                        
                        if (minRating >= maxRating) {
                            $( '#direktt-review-settings-alert' ).addClass( 'direktt-popup-on' );
                            $( '#direktt-review-settings-alert .direktt-popup-text' ).text( '<?php echo esc_js( __( 'Minimum Rating must be less than Maximum Rating.', 'direktt-customer-review' ) ); ?>' );
                            event.preventDefault();
                            return;
                        }

                        if (threshold < minRating || threshold > maxRating) {
                            $( '#direktt-review-settings-alert' ).addClass( 'direktt-popup-on' );
                            $( '#direktt-review-settings-alert .direktt-popup-text' ).text( '<?php echo esc_js( __( 'Threshold Rating must be between Minimum and Maximum Rating.', 'direktt-customer-review' ) ); ?>' );
                            event.preventDefault();
                            return;
                        }
                    });

                    $( '#direktt-review-settings-alert .direktt-popup-ok' ).off('click').on('click', function (event) {
                        event.preventDefault();
                        $( '#direktt-review-settings-alert' ).removeClass( 'direktt-popup-on' );
                    });
                });
            </script>

            <?php submit_button( esc_html__( 'Save Settings', 'direktt-customer-review' ) ); ?>
        </form>
    </div>
    <?php
}

add_action( 'direktt_setup_profile_tools', 'setup_review_profile_tools' );

function setup_review_profile_tools() {
    Direktt_Profile::add_profile_tool(
        array(
            'id'         => 'review-profile-tool',
            'label'      => esc_html__( 'Reviews', 'direktt-customer-review' ),
            'callback'   => 'render_review_profile_tool',
            'categories' => array(),
            'tags'       => array(),
            'priority'   => 2,
        )
    );
}

function render_review_profile_tool() {
    $subscription_id = isset( $_GET['subscriptionId'] ) ? sanitize_text_field( wp_unslash( $_GET['subscriptionId'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Justification: not a form processing, used for content rendering.
    $profile_user    = Direktt_User::get_user_by_subscription_id( $subscription_id );
    if ( ! $profile_user ) {
        echo '<div class="notice notice-error"><p>' . esc_html__( 'User not found.', 'direktt-customer-review' ) . '</p></div>';
        return;
    }
    $user_id = $profile_user['ID'];

    ?>
    <div class="direktt-review-profile-tool">
        <?php
        $allowed_html = wp_kses_allowed_html( 'post' );
        echo wp_kses( Direktt_Public::direktt_render_alert_popup( 'direktt-review-alert', '' ), $allowed_html );
        ?>
        <div class="direktt-review-header">
            <button class="button button-large button-primary direktt-send-review" data-subscription-id="<?php echo esc_attr( $subscription_id ); ?>">
                <?php echo esc_html__( 'Send review template to user', 'direktt-customer-review' ); ?>
            </button>
            <?php wp_nonce_field( 'direktt_send_review_template', 'direktt_send_review_template_nonce' ); ?>
            <script>
                jQuery(document).ready(function ($) {
                    $('.direktt-send-review').off('click').on('click', function () {
                        event.preventDefault();
                        const subscriptionId = $(this).data('subscription-id');
                        const data = {
                            action: 'direktt_send_review_template',
                            subscriptionId: subscriptionId,
                            nonce: $('#direktt_send_review_template_nonce').val()
                        };
                        $( this ).prop('disabled', true).text('<?php echo esc_js( __( 'Sending...', 'direktt-customer-review' ) ); ?>');
                        $.ajax({
                            url: '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
                            type: 'POST',
                            data: data,
                            success: function (response) {
                                if (response.success) {
                                    $( '#direktt-review-alert' ).addClass( 'direktt-popup-on' );
                                    $( '#direktt-review-alert .direktt-popup-text' ).text( response.data.message );
                                } else {
                                    $( '#direktt-review-alert' ).addClass( 'direktt-popup-on' );
                                    $( '#direktt-review-alert .direktt-popup-text' ).text( response.data.error );
                                }
                                $( '.direktt-send-review' ).prop('disabled', false).text('<?php echo esc_js( __( 'Send review template to user', 'direktt-customer-review' ) ); ?>');
                            },
                            error: function () {
                                $( '#direktt-review-alert' ).addClass( 'direktt-popup-on' );
                                $( '#direktt-review-alert .direktt-popup-text' ).text( '<?php echo esc_js( __( 'An error occurred while sending the review template.', 'direktt-customer-review' ) ); ?>' );
                                $( '.direktt-send-review' ).prop('disabled', false).text('<?php echo esc_js( __( 'Send review template to user', 'direktt-customer-review' ) ); ?>');
                            }
                        });
                    });

                    $( '#direktt-review-alert .direktt-popup-ok' ).off('click').on('click', function (event) {
                        event.preventDefault();
                        $( '#direktt-review-alert' ).removeClass( 'direktt-popup-on' );
                    });
                });
            </script>
        </div>
        <h2><?php echo esc_html__( 'Recent Reviews', 'direktt-customer-review' ); ?></h2>
		<?php
		$reviews = get_post_meta( $user_id, 'direktt_reviews', true );
		if ( is_array( $reviews ) && ! empty( $reviews ) ) {
			$reviews = array_reverse( $reviews );
			echo '<table class="direktt-table direktt-table-last-column-align-right direktt-review-profile-tool-table">';
				echo '<thead>';
					echo '<tr><th>' . esc_html__( 'Time', 'direktt-customer-review' ) . ' </th><th> ' . esc_html__( 'Rating', 'direktt-customer-review' ) . '</th></tr>';
				echo '</thead>';
				echo '<tbody>';
			foreach ( $reviews as $review ) {
				$date   = human_time_diff( $review['timestamp'] ) . ' ago';
				$rating = intval( $review['rating'] );
				echo '<tr><td>' . esc_html( $date ) . ' </td><td> ' . esc_html( $rating ) . '</td></tr>';
			}
				echo '</tbody>';
			echo '</table>';
		} else {
			echo '<p>' . esc_html__( 'No reviews found.', 'direktt-customer-review' ) . '</p>';
		}
		?>
    </div>
    <?php
}

function direktt_review_add_meta_box() {
    add_meta_box(
        'direktt_review_program_meta_box',
        __( 'Reviews', 'direktt-customer-review' ),
        'render_review_meta_box',
        'direkttusers',
        'side',
        'default'
    );
}

add_action( 'add_meta_boxes', 'direktt_review_add_meta_box' );

function render_review_meta_box( $post ) {
    $user_id = $post->ID;
    $user    = Direktt_User::get_user_by_post_id( $user_id );
    if ( ! $user ) {
        return;
    }
    $subscription_id = $user['direktt_user_id'];
    ?>
    <div class="direktt-review-meta-box">
        <div class="direktt-review-header">
            <div id="direktt-send-review-notice" style="display: none;"><p></p></div>
            <button class="button direktt-send-review" data-subscription-id="<?php echo esc_attr( $subscription_id ); ?>">
                <?php echo esc_html__( 'Send review template to user', 'direktt-customer-review' ); ?>
            </button>
            <?php wp_nonce_field( 'direktt_send_review_template', 'direktt_send_review_template_nonce' ); ?>
            <h4><?php echo esc_html__( 'Recent Reviews', 'direktt-customer-review' ); ?></h4>
            <script>
                jQuery(document).ready(function ($) {
                    $('.direktt-send-review').on('click', function () {
                        event.preventDefault();
                        const subscriptionId = $(this).data('subscription-id');
                        const data = {
                            action: 'direktt_send_review_template',
                            subscriptionId: subscriptionId,
                            nonce: $('#direktt_send_review_template_nonce').val()
                        };
                        $( this ).prop('disabled', true).text('<?php echo esc_js( __( 'Sending...', 'direktt-customer-review' ) ); ?>');
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: data,
                            success: function (response) {
                                if (response.success) {
                                    $( '#direktt-send-review-notice' ).removeClass( 'error' ).addClass( 'notice' ).addClass( 'notice-success' );
                                    $( '#direktt-send-review-notice p' ).text(response.data.message);
                                    $( '#direktt-send-review-notice' ).show();
                                } else {
                                    $( '#direktt-send-review-notice' ).addClass( 'error' ).addClass( 'notice' ).removeClass( 'notice-success' );
                                    $( '#direktt-send-review-notice p' ).text(response.data.error);
                                    $( '#direktt-send-review-notice' ).show();
                                }
                                $( '.direktt-send-review' ).prop('disabled', false).text('<?php echo esc_js( __( 'Send review template to user', 'direktt-customer-review' ) ); ?>');
                            },
                            error: function () {
                                $( '#direktt-send-review-notice' ).addClass( 'error' ).addClass( 'notice' ).removeClass( 'notice-success' );
                                $( '#direktt-send-review-notice p' ).text('<?php echo esc_js( __( 'An error occurred while sending the review template.', 'direktt-customer-review' ) ); ?>');
                                $( '#direktt-send-review-notice' ).show();
                                $( '.direktt-send-review' ).prop('disabled', false).text('<?php echo esc_js( __( 'Send review template to user', 'direktt-customer-review' ) ); ?>');
                            }
                        });
                    });
                });
            </script>
        </div>
		<?php
		$reviews = get_post_meta( $user_id, 'direktt_reviews', true );
		if ( is_array( $reviews ) && ! empty( $reviews ) ) {
			$reviews = array_reverse( $reviews );
			// TODO pitanje da li treba ograniciti broj review-a
			// $reviews = array_slice( $reviews, 0, 20 );
			echo '<table class="widefat striped">';
				echo '<thead>';
				echo '<tr><td>' . esc_html__( 'Time', 'direktt-customer-review' ) . ' </td><td> ' . esc_html__( 'Rating', 'direktt-customer-review' ) . '</td></tr>';
				echo '</thead>';
				echo '<tbody>';
			foreach ( $reviews as $review ) {
				$date   = wp_date( 'Y-m-d H:i:s', $review['timestamp'] );
				$rating = intval( $review['rating'] );
				echo '<tr><td>' . esc_html( $date ) . '</td><td>' . esc_html( $rating ) . '</td></tr>';
			}
				echo '</tbody>';
			echo '</table>';
		} else {
			echo '<p>' . esc_html__( 'No reviews found.', 'direktt-customer-review' ) . '</p>';
		}
		?>
    </div>
    <?php
}

function direktt_create_review_buttons(){

    $review_min_rating = intval( get_option( 'direktt_review_min_rating', 1 ) );
    $review_max_rating = intval( get_option( 'direktt_review_max_rating', 5 ) );

    $msg_obj = array();

    $ctr = $review_min_rating;
    while ( $ctr <= $review_max_rating ) {
        $msg_obj[] = array(
            'txt'    => '',
            'label'  => "$ctr",
            'action' => array(
                'type'    => 'api',
                'params'  => array(
                    'actionType' => 'submit_review',
                    'successMessage' => esc_html__( 'Your review has been recorded! Thanks!', 'direktt-customer-review' ),
                ),
                'retVars' => (object) array(
                    'rating' => "$ctr",
                ),
            ),
        );
        ++$ctr;
    }

    $review_message = array(
        'type'    => 'rich',
        'content' => wp_json_encode(
            array(
                'subtype' => 'buttons',
                'msgObj'  => $msg_obj,
            )
        ),
    );

    return $review_message;
}

function direktt_send_review_messages( $subscription_id ) {
    $user         = Direktt_User::get_user_by_subscription_id( $subscription_id );
    $display_name = get_the_title( $user['ID'] );

    $review_template   = intval( get_option( 'direktt_review_template', 0 ) );

    Direktt_Message::send_message_template(
        array( $subscription_id ),
        $review_template,
        array()
    );

    $review_message = direktt_create_review_buttons();

    Direktt_Message::send_message( array( $subscription_id => $review_message ) );
}

function handle_direktt_send_review_template() {
    if ( ! isset( $_POST['nonce'], $_POST['subscriptionId'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'direktt_send_review_template' ) ) {
        wp_send_json_error( array( 'error' => esc_html__( 'Invalid request.', 'direktt-customer-review' ) ) );
    }

    $subscription_id = sanitize_text_field( wp_unslash( $_POST['subscriptionId'] ) );

    direktt_send_review_messages( $subscription_id );

    wp_send_json_success( array( 'message' => esc_html__( 'Review template sent successfully.', 'direktt-customer-review' ) ) );
}


add_action( 'wp_ajax_direktt_send_review_template', 'handle_direktt_send_review_template' );

function on_init_review() {
    global $direktt_user;
    $subscription_id = $direktt_user['direktt_user_id'] ?? '';

    direktt_send_review_messages( $subscription_id );

    $data = array(
        'message' => esc_html__( 'Review messages sent successfully.', 'direktt-customer-review' ),
    );
    wp_send_json_success( $data, 200 );
}

add_action( 'direktt/action/init_review', 'on_init_review' );

function on_submit_review( $request ) {
    if ( array_key_exists( 'rating', $request ) ) {
        global $direktt_user;
        $subscription_id = $direktt_user['direktt_user_id'] ?? '';
        $rating          = intval( $request['rating'] );

        $review_threshold                = intval( get_option( 'direktt_review_threshold', 3 ) );
        $review_under_threshold_template = intval( get_option( 'direktt_review_under_treshold_template', 0 ) );
        $review_over_threshold_template  = intval( get_option( 'direktt_review_over_treshold_template', 0 ) );

        if ( $rating <= $review_threshold ) {
            Direktt_Message::send_message_template(
                array( $subscription_id ),
                $review_under_threshold_template,
                array()
            );
        } else {
            Direktt_Message::send_message_template(
                array( $subscription_id ),
                $review_over_threshold_template,
                array()
            );
        }

        $send_to_admin = get_option( 'direktt_review_send_to_admin', 'no' ) === 'yes';
        $admin_template = intval( get_option( 'direktt_review_admin_template', 0 ) );

        if ( $send_to_admin && $admin_template ) {
            $display_name = $direktt_user['direktt_display_name'];

            Direktt_Message::send_message_template_to_admin(
                $admin_template,
                array(
                    'subscription_id' => $subscription_id,
                    'display_name'    => $display_name,
                    'rating'          => $rating,
                )
            );
        }

        $review = array(
            'timestamp' => time(),
            'rating'    => $rating,
        );

        $review_min_rating = intval( get_option( 'direktt_review_min_rating', 1 ) );

        $profile_user = Direktt_User::get_user_by_subscription_id( $subscription_id );
        $user_id      = $profile_user['ID'];
        $reviews      = get_post_meta( $user_id, 'direktt_reviews', true );
        if ( ! is_array( $reviews ) ) {
            $reviews = array();
        }
        $reviews[] = $review;
        update_post_meta( $user_id, 'direktt_reviews', $reviews );

        $data = array();

        $review_message = direktt_create_review_buttons();
        $new_content = json_decode($review_message['content']);
        $new_content->disabled = true;
        $new_content->msgObj[$rating-$review_min_rating]->accent = true;


        Direktt_Message::update_message($subscription_id, sanitize_text_field($request['messageId']), json_encode($new_content));

        wp_send_json_success( $data, 200 );
    } else {
        wp_send_json_error( new WP_Error( 'missing_parameter', esc_html__( 'Subscription Id or Rating is missing', 'direktt-customer-review' ) ), 400 );
    }
}

add_action( 'direktt/action/submit_review', 'on_submit_review', 10, 1 );
