<?php
use Clanspress\Clanspress\Main;

/**
 * Grab the Main object and return it.
 * Wrapper for Main::instance().
 *
 * @since 0.0.1
 * @return Main Singleton instance of plugin class.
 */
function clanspress() {
	return Main::instance();
}
