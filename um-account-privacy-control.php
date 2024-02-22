<?php
/**
 * Plugin Name:     Ultimate Member - Account Privacy Control
 * Description:     Extension to Ultimate Member to Manage Account Privacy from the backend.
 * Version:         2.2.0
 * Requires PHP:    7.4
 * Author:          Miss Veronica
 * License:         GPL v2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Author URI:      https://github.com/MissVeronica?tab=repositories
 * Text Domain:     ultimate-member
 * Domain Path:     /languages
 * UM version:      2.8.3
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'UM' ) ) return;

class UM_Account_Privacy_Control {

    public function __construct() {

        if( is_admin()) {

            add_filter( 'um_admin_bulk_user_actions_hook',          array( $this, 'um_admin_bulk_user_actions_privacy' ), 10, 1 );
            add_filter( 'um_settings_structure',                    array( $this, 'um_settings_structure_privacy_registration' ), 10, 1 );

            add_filter( 'manage_users_columns',                     array( $this, 'manage_users_columns_custom_privacy' ));
            add_filter( 'manage_users_custom_column',               array( $this, 'manage_users_custom_column_privacy' ), 10, 3 );

            add_action( "um_admin_custom_hook_um_directory_yes",    array( $this, "um_admin_custom_hook_directory_yes" ), 10, 1 );
            add_action( "um_admin_custom_hook_um_directory_no",     array( $this, "um_admin_custom_hook_directory_no" ), 10, 1 );

            add_action( "um_admin_custom_hook_um_privacy_only_me",  array( $this, "um_admin_custom_hook_privacy_yes" ), 10, 1 );
            add_action( "um_admin_custom_hook_um_privacy_everyone", array( $this, "um_admin_custom_hook_privacy_no" ), 10, 1 );

        } else {

            add_filter( 'um_registration_complete',                 array( $this, 'um_registration_complete_privacy' ), 10, 1 );
            add_filter( 'um_redirect_home_custom_url',              array( $this, 'um_redirect_privacy_custom_url' ), 9, 3 );
        }
    }

    public function um_admin_bulk_user_actions_privacy( $actions ) {

        $actions['um_directory_yes']     = array( 'label' => __( 'Directory Privacy Yes', 'ultimate-member' ));
        $actions['um_directory_no']      = array( 'label' => __( 'Directory Privacy No', 'ultimate-member' ));

        $actions['um_privacy_only_me']   = array( 'label' => __( 'Account Privacy Yes', 'ultimate-member' ));
        $actions['um_privacy_everyone']  = array( 'label' => __( 'Account Privacy No', 'ultimate-member' ));

        return $actions;
    }

    public function um_admin_custom_hook_directory_yes( $user_id ) {

        update_user_meta( $user_id, 'hide_in_members', array( 'Yes' ) );
        UM()->user()->remove_cache( $user_id );
        um_fetch_user( $user_id );
    }

    public function um_admin_custom_hook_directory_no( $user_id ) {

        delete_user_meta( $user_id, 'hide_in_members' );
        UM()->user()->remove_cache( $user_id );
        um_fetch_user( $user_id );
    }

    public function um_admin_custom_hook_privacy_yes( $user_id ) {

        update_user_meta( $user_id, 'profile_privacy', 'Only me' );
        UM()->user()->remove_cache( $user_id );
        um_fetch_user( $user_id );
    }

    public function um_admin_custom_hook_privacy_no( $user_id ) {

        update_user_meta( $user_id, 'profile_privacy', 'Everyone' );
        UM()->user()->remove_cache( $user_id );
        um_fetch_user( $user_id );
    }



    public function um_redirect_privacy_custom_url( $url,  $requested_user_id, $is_my_profile ) {

        if (   UM()->user()->is_private_profile( $requested_user_id ) &&
            ! um_can_view_profile( $requested_user_id ) &&
            ! $is_my_profile ) {

            if ( ! empty( UM()->options()->get( 'um_profile_privacy_url' ))) {
                $url = add_query_arg( 'redirect_msg', 'pvt_profile', esc_url( UM()->options()->get( 'um_profile_privacy_url' ) ));
            }
        }

        return $url;
    }

    public function manage_users_columns_custom_privacy( $columns ) {

        $columns['um_custom_directory'] = __( 'Directory', 'ultimate-member' );
        $columns['um_custom_privacy']   = __( 'Account', 'ultimate-member' );

        return $columns;
    }

    public function manage_users_custom_column_privacy( $value, $column_name, $user_id ) {

        if ( $column_name == 'um_custom_directory' ) {

            um_fetch_user( $user_id );
            $status = maybe_unserialize( um_user( 'hide_in_members' ));

            if( is_array( $status ) && isset( $status[0] ) && $status[0] == 'Yes' ) {
                $value = __( 'Hide', 'ultimate-member' );
            } else {
                $value = __( 'Show', 'ultimate-member' );
            }
        }

        if ( $column_name == 'um_custom_privacy' ) {

            um_fetch_user( $user_id );
            $status = um_user( 'profile_privacy' );

            if( $status == 'Only me' ) {
                $value = __( 'Only me', 'ultimate-member' );
            } else {
                $value = __( 'Everyone', 'ultimate-member' );
            }
        }
        return $value;
    }

    public function um_registration_complete_privacy( $user_id ) {

        if ( ! empty( UM()->options()->get( 'um_profile_privacy_account' ))) {

            if( UM()->options()->get( 'um_profile_privacy_account' ) == 'onlyme' ) {
                update_user_meta( $user_id, 'profile_privacy', 'Only me' );
            }
            if( UM()->options()->get( 'um_profile_privacy_account' ) == 'everyone' ) {
                update_user_meta( $user_id, 'profile_privacy', 'Everyone' );
            }
        }
    }

    public function um_settings_structure_privacy_registration( $settings_structure ) {

        $settings_structure['access']['sections']['other']['form_sections']['profile_privacy_account']['title']       = __( 'Registration Profile Privacy', 'ultimate-member' );
        $settings_structure['access']['sections']['other']['form_sections']['profile_privacy_account']['description'] = __( 'Plugin version 2.2.0 - tested with UM 2.8.3', 'ultimate-member' );

        $settings_structure['access']['sections']['other']['form_sections']['profile_privacy_account']['fields'][] = array(
                'id'            => 'um_profile_privacy_account',
                'type'          => 'select',
                'options'       => array( 'empty'    => '', 
                                          'onlyme'   => __( 'Only me', 'ultimate-member' ),
                                          'everyone' => __( 'Everyone', 'ultimate-member' ) ),
                'label'         => __( 'User Account', 'ultimate-member' ),
                'description'   => __( 'Set Account Profile Privacy at Registration', 'ultimate-member' )
            );

        $settings_structure['access']['sections']['other']['form_sections']['profile_privacy_account']['fields'][] = array(
                'id'            => 'um_profile_privacy_url',
                'type'          => 'text',
                'label'         => __( 'Redirect URL', 'ultimate-member' ),
                'description'   => __( 'Page when trying to access a Private User Page', 'ultimate-member' )
            );
        
        return $settings_structure;
    }

}

new UM_Account_Privacy_Control();
