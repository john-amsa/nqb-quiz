<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://https://amsa.org.au/
 * @since             1.0.0
 * @package           Nqb_quiz
 *
 * @wordpress-plugin
 * Plugin Name:       NQB quiz
 * Plugin URI:        https://nqb_quiz
 * Description:       A plugin for the AMSA MedEd National Question Bank. 
 * Version:           1.0.0
 * Author:            John
 * Author URI:        https://https://amsa.org.au//
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       nqb_quiz
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'NQB_QUIZ_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-nqb_quiz-activator.php
 */
function activate_nqb_quiz() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-nqb_quiz-activator.php';
	Nqb_quiz_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-nqb_quiz-deactivator.php
 */
function deactivate_nqb_quiz() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-nqb_quiz-deactivator.php';
	Nqb_quiz_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_nqb_quiz' );
register_deactivation_hook( __FILE__, 'deactivate_nqb_quiz' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-nqb_quiz.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_nqb_quiz() {

	$plugin = Nqb_quiz::instance(); //nqb quiz is a singleton
	$plugin->run();

}
run_nqb_quiz();
