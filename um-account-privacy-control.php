<?php
/**
 * Plugin Name:     Ultimate Member - Account Privacy Control
 * Description:     Extension to Ultimate Member to Manage Account Privacy from the backend.
 * Version:         1.0.0 
 * Requires PHP:    7.4
 * Author:          Miss Veronica
 * License:         GPL v2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Author URI:      https://github.com/MissVeronica?tab=repositories
 * Text Domain:     ultimate-member
 * Domain Path:     /languages
 * UM version:      2.5.3
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'UM' ) ) return;

if( is_admin()) {

    add_filter( 'um_admin_bulk_user_actions_hook',          'um_admin_bulk_user_actions_privacy', 10, 1 );

    add_filter( 'manage_users_columns',                     'manage_users_columns_custom_privacy' );
    add_filter( 'manage_users_custom_column',               'manage_users_custom_column_privacy', 10, 3 );

    add_action( "um_admin_custom_hook_um_directory_yes",    "um_admin_custom_hook_directory_yes", 10, 1 );
    add_action( "um_admin_custom_hook_um_directory_no",     "um_admin_custom_hook_directory_no", 10, 1 );

    add_action( "um_admin_custom_hook_um_privacy_only_me",  "um_admin_custom_hook_privacy_yes", 10, 1 );
    add_action( "um_admin_custom_hook_um_privacy_everyone", "um_admin_custom_hook_privacy_no", 10, 1 );
}

function um_admin_bulk_user_actions_privacy( $actions ) {

    $actions['um_directory_yes']     = array( 'label' => __( 'Directory Privacy Yes', 'ultimate-member' ));
    $actions['um_directory_no']      = array( 'label' => __( 'Directory Privacy No', 'ultimate-member' ));

    $actions['um_privacy_only_me']   = array( 'label' => __( 'Account Privacy Yes', 'ultimate-member' ));
    $actions['um_privacy_everyone']  = array( 'label' => __( 'Account Privacy No', 'ultimate-member' ));

    return $actions;
}

function um_admin_custom_hook_directory_yes( $user_id ) {

    update_user_meta( $user_id, 'hide_in_members', array( 'Yes' ) );
    UM()->user()->remove_cache( $user_id );
    um_fetch_user( $user_id );
}

function um_admin_custom_hook_directory_no( $user_id ) {

    delete_user_meta( $user_id, 'hide_in_members' );
    UM()->user()->remove_cache( $user_id );
    um_fetch_user( $user_id );
}

function um_admin_custom_hook_privacy_yes( $user_id ) {

    update_user_meta( $user_id, 'profile_privacy', 'Only me' );
    UM()->user()->remove_cache( $user_id );
    um_fetch_user( $user_id );
}

function um_admin_custom_hook_privacy_no( $user_id ) {

    update_user_meta( $user_id, 'profile_privacy', 'Everyone' );
    UM()->user()->remove_cache( $user_id );
    um_fetch_user( $user_id );
}

function manage_users_columns_custom_privacy( $columns ) {

    $columns['um_custom_directory'] = __( 'Directory', 'ultimate-member' );
    $columns['um_custom_privacy']   = __( 'Account', 'ultimate-member' );

    return $columns;
}

function manage_users_custom_column_privacy( $value, $column_name, $user_id ) {

    if ( $column_name == 'um_custom_directory' ) {

        um_fetch_user( $user_id );
        $status = maybe_unserialize( um_user( 'hide_in_members' ));

        if( is_array( $status ) && isset( $status[0] ) && $status[0] == 'Yes' ) {
            $value = __( 'Yes', 'ultimate-member' );
        } else { 
            $value = __( 'No', 'ultimate-member' );
        }   
    }

    if ( $column_name == 'um_custom_privacy' ) {

        um_fetch_user( $user_id );
        $status = um_user( 'profile_privacy' );

        if( $status == 'Only me' ) {
            $value = __( 'Only Me', 'ultimate-member' );
        } else { 
            $value = __( 'Everyone', 'ultimate-member' );
        }   
    }
    return $value;
}
