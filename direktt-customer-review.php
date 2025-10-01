<?php

/**
 * Plugin Name: Direktt Customer Review
 * Description: Direktt Customer Review Direktt Plugin
 * Version: 1.0.0
 * Author: Direktt
 * Author URI: https://direktt.com/
 * License: GPL2
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'plugins_loaded', 'direktt_customer_review_activation_check', -20 );

function direktt_customer_review_activation_check() {
    if ( ! function_exists( 'is_plugin_active' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $required_plugin = 'direktt-plugin/direktt.php';

    if ( ! is_plugin_active( $required_plugin ) ) {
        add_action(
            'after_plugin_row_direktt-customer-review/direktt-customer-review.php',
            function ( $plugin_file, $plugin_data, $status ) {
				$colspan = 3;
				?>
            <tr class="plugin-update-tr">
                <td colspan="<?php echo esc_attr( $colspan ); ?>" style="box-shadow: none;">
                    <div style="color: #b32d2e; font-weight: bold;">
                        <?php echo esc_html__( 'Direktt Customer Review requires the Direktt WordPress Plugin to be active. Please activate Direktt WordPress Plugin first.', 'direktt-customer-review' ); ?>
                    </div>
                </td>
            </tr>
				<?php
			},
            10,
            3
        );

        deactivate_plugins( plugin_basename( __FILE__ ) );
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
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['direktt_admin_review_nonce'] ) && wp_verify_nonce( $_POST['direktt_admin_review_nonce'], 'direktt_admin_review_save' ) ) {
        // update options based on form submission
        update_option( 'direktt_review_template', intval( $_POST['direktt_review_template'] ) );
        update_option( 'direktt_review_min_rating', intval( $_POST['direktt_review_min_rating'] ) );
        update_option( 'direktt_review_max_rating', intval( $_POST['direktt_review_max_rating'] ) );
        update_option( 'direktt_review_threshold', intval( $_POST['direktt_review_threshold'] ) );
        update_option( 'direktt_review_under_treshold_template', intval( $_POST['direktt_review_under_threshold_template'] ) );
        update_option( 'direktt_review_over_treshold_template', intval( $_POST['direktt_review_over_threshold_template'] ) );
        update_option( 'direktt_review_send_to_admin', isset( $_POST['direktt_review_send_to_admin'] ) ? 'yes' : 'no' );
        update_option( 'direktt_review_admin_template', intval( $_POST['direktt_review_admin_template'] ) );
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
        'meta_query'     => array(
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

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="direktt_review_template"><?php echo esc_html__( 'Review Message Template', 'direktt-customer-review' ); ?></label></th>
                    <td>
                        <select name="direktt_review_template" id="direktt_review_template">
                            <option value="0"><?php echo esc_html__( 'Select Template', 'direktt-customer-review' ); ?></option>
                            <?php foreach ( $template_posts as $post ) : ?>
                                <option value="<?php echo esc_attr( $post->ID ); ?>" <?php selected( $review_template, $post->ID ); ?>>
                                    <?php echo esc_html( $post->post_title ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">!!!TODO!!!</p><!-- TODO -->
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="direktt_review_min_rating"><?php echo esc_html__( 'Minimum Rating', 'direktt-customer-review' ); ?></label></th>
                    <td>
                        <input type="number" name="direktt_review_min_rating" id="direktt_review_min_rating" value="<?php echo esc_attr( $review_min_rating ); ?>" min="0" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="direktt_review_max_rating"><?php echo esc_html__( 'Maximum Rating', 'direktt-customer-review' ); ?></label></th>
                    <td>
                        <input type="number" name="direktt_review_max_rating" id="direktt_review_max_rating" value="<?php echo esc_attr( $review_max_rating ); ?>" min="0" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="direktt_review_threshold"><?php echo esc_html__( 'Threshold Rating', 'direktt-customer-review' ); ?></label></th>
                    <td>
                        <input type="number" name="direktt_review_threshold" id="direktt_review_threshold" value="<?php echo esc_attr( $review_threshold ); ?>" min="0" />
                        <p class="description"><?php echo esc_html__( 'If rating is below this threshold, the under threshold template will be used.', 'direktt-customer-review' ); ?></p>
                        <p class="description"><?php echo esc_html__( 'If rating is above this threshold, the over threshold template will be used.', 'direktt-customer-review' ); ?></p>
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
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="direktt_review_send_to_admin"><?php echo esc_html__( 'Send to Admin', 'direktt-loyalty-program' ); ?></label></th>
                    <td>
                        <input type="checkbox" name="direktt_review_send_to_admin" id="direktt_review_send_to_admin" value="yes" <?php checked( $send_to_admin ); ?> />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="direktt_review_admin_template"><?php echo esc_html__( 'Admin Template', 'direktt-customer-review' ); ?></label></th>
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
                        <p class="description"><?php echo esc_html__( 'You can use placeholders', 'direktt-customer-review' ); ?> <?php echo esc_html( '#display_name#' ); ?> <?php echo esc_html__( 'for display name, and', 'direktt-customer-review' ); ?> <?php echo esc_html( '#subscription_id#' ); ?> <?php echo esc_html__( 'for subscription id.', 'direktt-customer-review' ); ?></p>
                    </td>
                </tr>
            </table>

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
                            alert('Minimum Rating must be less than Maximum Rating.');
                            event.preventDefault();
                            return;
                        }

                        if ((maxRating - minRating) > 5) {
                            alert('The difference between Minimum and Maximum Rating cannot exceed 5.');
                            event.preventDefault();
                            return;
                        }

                        if (threshold < minRating || threshold > maxRating) {
                            alert('Threshold Rating must be between Minimum and Maximum Rating.');
                            event.preventDefault();
                            return;
                        }
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
    $subscription_id = isset( $_GET['subscriptionId'] ) ? sanitize_text_field( wp_unslash( $_GET['subscriptionId'] ) ) : false;
    $profile_user    = Direktt_User::get_user_by_subscription_id( $subscription_id );
    if ( ! $profile_user ) {
        echo '<div class="notice notice-error"><p>' . esc_html__( 'User not found.', 'direktt' ) . '</p></div>';
        return;
    }
    $user_id = $profile_user['ID'];

    ?>
    <div class="direktt-review-profile-tool">
        <?php
        echo Direktt_Public::direktt_render_alert_popup( 'direktt-review-alert', '' );
        ?>
        <div class="direktt-review-header">
            <button class="button direktt-send-review" data-subscription-id="<?php echo esc_attr( $subscription_id ); ?>">
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

                    $( '#direktt-review-alert .direktt-popup-ok' ).on('click', function (event) {
                        event.preventDefault();
                        $( '#direktt-review-alert' ).removeClass( 'direktt-popup-on' );
                    });
                });
            </script>
            <h2><?php echo esc_html__( 'Recent Reviews', 'direktt-customer-review' ); ?></h2>
        </div>
        <div class="direktt-reviews-list">
            <?php
            $reviews = get_post_meta( $user_id, 'direktt_reviews', true );
            if ( is_array( $reviews ) && ! empty( $reviews ) ) {
                $reviews = array_reverse( $reviews );
                echo '<table class="direktt-review-profile-tool-table">';
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
                                    alert(response.data.message);
                                } else {
                                    alert(response.data.error);
                                }
                                $( '.direktt-send-review' ).prop('disabled', false).text('<?php echo esc_js( __( 'Send review template to user', 'direktt-customer-review' ) ); ?>');
                            },
                            error: function () {
                                alert('<?php echo esc_js( __( 'An error occurred while sending the review template.', 'direktt-customer-review' ) ); ?>');
                                $( '.direktt-send-review' ).prop('disabled', false).text('<?php echo esc_js( __( 'Send review template to user', 'direktt-customer-review' ) ); ?>');
                            }
                        });
                    });
                });
            </script>
        </div>
        <div class="direktt-reviews-list">
            <?php
            $reviews = get_post_meta( $user_id, 'direktt_reviews', true );
            if ( is_array( $reviews ) && ! empty( $reviews ) ) {
                $reviews = array_reverse( $reviews );
                // TODO pitanje da li treba ograniciti broj review-a
                // $reviews = array_slice( $reviews, 0, 20 );
                echo '<table class="widefat striped">';
					echo '<thead>';
					echo '<tr><td>Time</td><td>Rating</td></tr>';
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
                    'successMessage' => 'Your review has been recorded! Thanks!'
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
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'direktt_send_review_template' ) ) {
        wp_send_json_error( array( 'error' => esc_html__( 'Invalid nonce.', 'direktt-customer-review' ) ) );
    }

    $subscription_id = sanitize_text_field( $_POST['subscriptionId'] );

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
