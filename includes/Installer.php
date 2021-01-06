<?php

namespace Springdevs\TutorSubscrpt;

/**
 * Class Installer
 * @package Springdevs\TutorSubscrpt
 */
class Installer {
    /**
     * Run the installer
     *
     * @return void
     */
    public function run() {
        $this->add_version();
        $this->create_tables();
    }

    /**
     * Add time and version on DB
     */
    public function add_version() {
        $installed = get_option( 'Tutor LMS Subscription_installed' );

        if ( ! $installed ) {
            update_option( 'Tutor LMS Subscription_installed', time() );
        }

        update_option( 'Tutor LMS Subscription_version', TUTOR_SUBSCRPT_VERSION );

    }

    /**
     * Create necessary database tables
     *
     * @return void
     */
    public function create_tables() {
        if ( ! function_exists( 'dbDelta' ) ) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        
    }

    
}
