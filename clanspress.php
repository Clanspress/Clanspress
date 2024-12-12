<?php
/**
 * Plugin Name:  Clanspress
 * Plugin URI:   https://clanspress.com
 * Description:  Team management system plugin for Wordpress..
 * Version:      0.0.1
 * Requires PHP: 8.1
 * Author:       Clanspress
 * Author URI:   https://clanspress.com
 * Donate link:  https://clanspress.com
 * License:      See license.txt
 * Text Domain:  clanspress
 * Domain Path:  /languages
 *
 * @link    https://clanspress.com
 *
 * @package clanspress
 * @version 0.0.1
 */
namespace Clanspress\Clanspress;

// Use composer autoload.
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/shortcut-function.php';

/**
 * Main initiation class.
 *
 * @since 0.1.0
 */
final class Main {
    /**
     * Current version.
     *
     * @since 0.0.1
     * @var string
     */
    const VERSION = '0.0.1';

    /**
     * What version of maintenance upgrades we are at.
     *
     * @since 0.0.1
     * @var int
     */
    const MAINTENANCE_VERSION = 1;

    /**
     * Singleton instance of plugin.
     *
     * @since 0.0.1
     * @var Main|null
     */
    protected static ?Main $instance = null;

    /**
     * The token, used to prefix values in DB.
     *
     * @since 0.1.0
     * @var   string
     */
    public string $_token = 'clanspress';

    /**
     * URL of plugin directory.
     *
     * @since 0.0.1
     * @var string
     */
    protected string $url = '';

    /**
     * Path of plugin directory.
     *
     * @since 0.0.1
     * @var string
     */
    protected string $path = '';

    /**
     * Plugin basename.
     *
     * @since 0.0.1
     * @var string
     */
    protected string $basename = '';

    /**
     * The main plugin file.
     *
     * @since 0.0.1
     * @var string
     */
    public string $file;

    /**
     * Detailed activation error messages.
     *
     * @since 0.0.1
     * @var array
     */
    protected array $activation_errors = [];

    /**
     * Creates or returns an instance of this class.
     *
     * @since 0.0.1
     * @return Main A single instance of this class.
     */
    public static function instance(): Main {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Sets up our plugin.
     *
     * @since 0.0.1
     */
    protected function __construct() {
        $this->file = basename( __FILE__ );
        $this->basename = plugin_basename( __FILE__ );
        $this->url = plugin_dir_url( __FILE__ );
        $this->path = plugin_dir_path( __FILE__ );
    }

    /**
     * Activate the plugin.
     *
     * @since 0.0.1
     */
    public function _activate() {
        // Bail early if requirements aren't met.
        if ( ! $this->check_requirements() ) {
            return;
        }

        // Make sure any rewrite functionality has been loaded.
        flush_rewrite_rules();
    }

    /**
     * Check if the plugin meets requirements and
     * disable it if they are not present.
     *
     * @since 0.1.0
     *
     * @return boolean True if requirements met, false if not.
     */
    public function check_requirements(): bool {
        // Bail early if plugin meets requirements.
        if ( $this->meets_requirements() ) {
            return true;
        }

        // Add a dashboard notice.
        add_action( 'all_admin_notices', [ $this, 'requirements_not_met_notice' ] );

        // Deactivate our plugin.
        add_action( 'admin_init', [ $this, 'deactivate_me' ] );

        // Didn't meet the requirements.
        return false;
    }

    /**
     * Check that all plugin requirements are met.
     *
     * @since 0.0.1
     *
     * @return boolean True if requirements are met.
     */
    public function meets_requirements(): bool {
        $valid = true;

        // if ( ! function_exists( 'some_plugin' ) ) {
        // 	$this->activation_errors[] = __( 'Some plugin is required.', 'clanspress' );
        // 	$valid = false;
        // }

        return $valid;
    }

    /**
     * Adds a notice to the dashboard if the plugin requirements are not met.
     *
     * @since 0.0.1
     */
    public function requirements_not_met_notice(): void {
        // Compile default message.
        $default_message = sprintf(
            __(
                'Clanspress Plugin is missing requirements and has been <a href="%s">deactivated</a>. Please make sure all requirements are available.',
                'clanspress'
            ),
            admin_url( 'plugins.php' )
        );

        // Default details to null.
        $details = null;

        // Add details if any exist.
        if ( $this->activation_errors && is_array( $this->activation_errors ) ) {
            $details = '<small>' . implode( '</small><br /><small>', $this->activation_errors ) . '</small>';
        }

        // Output errors.
        ?>
        <div id="message" class="error">
            <p><?php echo wp_kses_post( $default_message ); ?></p>
            <?php echo wp_kses_post( $details ); ?>
        </div>
        <?php
    }

    /**
     * Deactivate the plugin.
     * Uninstall routines should be in uninstall.php.
     *
     * @since 0.0.1
     */
    public function _deactivate() {
    }

    /**
     * Hooks run at 0.
     *
     * @since 0.0.1
     */
    public function early_hooks() {
    }

    /**
     * Add hooks and filters.
     * Priority needs to be
     * < 10 for CPT_Core,
     * < 5 for Taxonomy_Core,
     * and 0 for Widgets because widgets_init runs at init priority 1.
     *
     * @since 0.0.1
     */
    public function hooks() {
        add_action( 'init', [ $this, 'init' ], 0 );
    }

    /**
     * Init hooks
     *
     * @since 0.0.1
     */
    public function init() {
        // Bail early if requirements aren't met.
        if ( ! $this->check_requirements() ) {
            return;
        }

        // Load translated strings for plugin.
        load_plugin_textdomain( 'clanspress', false, dirname( $this->basename ) . '/languages/' );

        // Perform maintenance
        $this->maybe_run_maintenance();
    }

    /**
     * Check if any necessary maintenance tasks need to be run and execute them.
     *
     * @since 0.0.1
     */
    public function maybe_run_maintenance() {
        $maintenance_version = (int) get_option( $this->_token . '_maint_version' );

        if ( $maintenance_version < self::MAINTENANCE_VERSION ) {
            for ( $version = $maintenance_version + 1; $version <= self::MAINTENANCE_VERSION; $version ++ ) {
                Maintenance::run( $version );
            }
        }

        update_option( $this->_token . '_maint_version', self::MAINTENANCE_VERSION );
    }
}

// Kick it off.
add_action( 'plugins_loaded', [ clanspress(), 'early_hooks' ], 0 );
add_action( 'plugins_loaded', [ clanspress(), 'hooks' ] );

// Activation and deactivation.
register_activation_hook( __FILE__, [ clanspress(), '_activate' ] );
register_deactivation_hook( __FILE__, [ clanspress(), '_deactivate' ] );
