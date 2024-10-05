<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://https://amsa.org.au/
 * @since      1.0.0
 *
 * @package    Nqb_quiz
 * @subpackage Nqb_quiz/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Nqb_quiz
 * @subpackage Nqb_quiz/admin
 * @author     John <john.miao@amsa.org.au>
 */
class Nqb_quiz_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Nqb_quiz_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Nqb_quiz_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/nqb_quiz-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Nqb_quiz_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Nqb_quiz_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/nqb_quiz-admin.js', array( 'jquery' ), $this->version, false );
		// Localize script for AJAX call
		wp_localize_script( $this->plugin_name, 'nqb_quiz_ajax_object', array( 
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'nqb_quiz_nonce' )
		) );
	}

	/**
	 * Add an admin menu item and page.
	 *
	 * @since    1.0.0
	 */
	public function add_admin_menu() {
		add_menu_page(
			'Load CSVs',                // Page title
			'Load CSVs',                // Menu title
			'manage_options',           // Capability
			'nqb_quiz_load_csv',        // Menu slug
			array( $this, 'display_page' ),  // Function to display the page content
			'dashicons-upload',         // Icon
			80                          // Position
		);
	}

	/**
	 * Display the admin page with the button.
	 *
	 * @since    1.0.0
	 */
	public function display_page() {
		?>
		<div class="wrap">
			<h1>Load CSVs</h1>
			<button id="load-csvs" class="button button-primary">Load CSVs</button>
			<p id="csv-loader-result"></p>
		</div>
		<?php
	}

	/**
	 * Handle the AJAX request for running the question loader.
	 *
	 * @since    1.0.0
	 */
	public function load_csvs() {
		check_ajax_referer( 'nqb_quiz_nonce', 'security' );

		require_once dirname( plugin_dir_path( __FILE__ ) ) . '/includes/question loader/class-nqb_quiz-question_loader.php';
		error_log("run question loader called from admin");
		// Call the function from the class
		$question_loader = new Nqb_Quiz_Question_Loader();
		$result = $question_loader->run_question_loader();

		// Return a response
		if ( $result ) {
			wp_send_json_success( 'CSV files loaded successfully!' );
		} else {
			wp_send_json_error( 'Failed to load CSV files.' );
		}
	}

	/**
	 * Initialize AJAX actions.
	 *
	 * @since 1.0.0
	 */
	public function ajax_init() {
		add_action( 'wp_ajax_load_csvs', array( $this, 'load_csvs' ) );
	}

}
