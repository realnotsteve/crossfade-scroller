<?php
/**
 * Plugin Name: Scroll Crossfade Gallery for Elementor
 * Description: Elementor widget that crossfades a gallery of images based on scroll position.
 * Version:     1.9.0
 * Author:      Bill Evans
 * Text Domain: scroll-crossfade-gallery-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'SCG_ELEM_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'SCG_ELEM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

final class SCG_Elem_Plugin {
    const MIN_ELEMENTOR_VERSION = '3.0.0';
    const MIN_PHP_VERSION       = '7.2';
    private static $_instance = null;

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function __construct() {
        add_action( 'plugins_loaded', [ $this, 'on_plugins_loaded' ] );
    }

    public function on_plugins_loaded() {
        if ( ! did_action( 'elementor/loaded' ) ) {
            add_action( 'admin_notices', [ $this, 'admin_notice_missing_elementor' ] );
            return;
        }

        if ( version_compare( PHP_VERSION, self::MIN_PHP_VERSION, '<' ) ) {
            add_action( 'admin_notices', [ $this, 'admin_notice_minimum_php' ] );
            return;
        }

        add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
        add_action( 'elementor/frontend/after_register_scripts', [ $this, 'register_frontend_scripts' ] );
        add_action( 'elementor/frontend/after_register_styles', [ $this, 'register_frontend_styles' ] );
    }

    public function admin_notice_missing_elementor() {
        if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );

        $message = sprintf(
            esc_html__( '"%1$s" requires "%2$s" to be installed and activated.', 'scroll-crossfade-gallery-elementor' ),
            '<strong>Scroll Crossfade Gallery for Elementor</strong>',
            '<strong>Elementor</strong>'
        );

        printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
    }

    public function admin_notice_minimum_php() {
        if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );

        $message = sprintf(
            esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'scroll-crossfade-gallery-elementor' ),
            '<strong>Scroll Crossfade Gallery for Elementor</strong>',
            '<strong>PHP</strong>',
            self::MIN_PHP_VERSION
        );

        printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
    }

    public function register_frontend_scripts() {
        wp_register_script(
            'scg-scroll-crossfade',
            SCG_ELEM_PLUGIN_URL . 'assets/js/scroll-crossfade.js',
            [ 'jquery' ],
            '1.9.0',
            true
        );
    }

    public function register_frontend_styles() {
        wp_register_style(
            'scg-scroll-crossfade',
            SCG_ELEM_PLUGIN_URL . 'assets/css/scroll-crossfade.css',
            [],
            '1.9.0'
        );
    }

    public function register_widgets( $widgets_manager ) {
        require_once SCG_ELEM_PLUGIN_PATH . 'includes/class-scroll-crossfade-gallery-widget.php';
        $widgets_manager->register( new \SCG_Elem_Scroll_Crossfade_Gallery_Widget() );
    }
}

SCG_Elem_Plugin::instance();
