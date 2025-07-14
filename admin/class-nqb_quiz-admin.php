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

	private $question_loader;
	// a link to the main question loader

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

		add_action('admin_init', array($this, 'ajax_init'));

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

		 wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/nqb_quiz-admin.js', array('jquery'), $this->version, false);
    
		 $ajax_object = array(
			 'ajax_url' => admin_url('admin-ajax.php'),
			 'nonce' => wp_create_nonce('nqb_quiz_nonce')
		 );
		 
		 error_log('Localizing script with: ' . print_r($ajax_object, true));
		 
		 wp_localize_script($this->plugin_name, 'nqb_quiz_ajax_object', $ajax_object);
	 
	}

	/**
	 * Add an admin menu item and page.
	 *
	 * @since    1.0.0
	 */
	public function add_admin_menu() {
		add_menu_page(
			'NQB quiz extra settings',                // Page title
			'NQB quiz extra settings',                // Menu title
			'manage_options',           // Capability
			'nqb_quiz_load_csv',        // Menu slug
			array( $this, 'display_page' ),  // Function to display the page content
			'dashicons-admin-settings',         // Icon
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

		<div class="wrap">
			
			<p>Clear the question history for a specific user.</p>
			<div style="display: flex; gap: 10px; align-items: flex-start;">
				<input type="text" id="username-to-clear" placeholder="Enter username" class="regular-text">
				<button id="clear-user-questions" class="button button-secondary">Clear History</button>
			</div>
			<p id="clear-user-result"></p>
		</div>
        
        <hr style="margin: 30px 0;">
        <h2>Danger Zone</h2>
        <div style="background: #fff; padding: 20px; border-left: 4px solid #dc3545;">
            <h3 style="color: #dc3545;">Delete All Questions</h3>
            <p>Warning: This action cannot be undone. All questions will be permanently deleted.</p>
            <button id="delete-all-questions" class="button button-danger" style="background: #dc3545; color: white; border-color: #dc3545;">Delete All Questions</button>
            <p id="delete-questions-result"></p>
        </div>
    </div>
		
		<?php
	}

	/**
	 * H	ndle the AJAX request for running the question loader.
	 *
	 * @since    1.0.0
	 */
	public function load_csvs() {
		check_ajax_referer( 'nqb_quiz_nonce', 'security' );


		require_once dirname( plugin_dir_path( __FILE__ ) ) . '/includes/question loader/class-nqb_quiz-question_loader.php';
		error_log("run question loader called from admin");
		// Call the function from the class
		if ($this->question_loader == null){
			$question_loader = new Nqb_Quiz_Question_Loader();
			$this->question_loader = $question_loader;
		}
		
		$result = $this->question_loader->run_question_loader();

		$this->clear_all_question_history();
		// Return a response
		if ( $result ) {
			wp_send_json_success( 'CSV files loaded successfully!' );
		} else {
			wp_send_json_error( 'Failed to load CSV files.' );
		}
	}

	/**
	 * clears the question history for everyone
	 */
	public function clear_all_question_history(){
		delete_metadata('user',0,'seen_questions','',true);
	}

	/**
     * deletes all the questions of type sfwd question. useful for debugging.
     */
    public function delete_all_questions() {
		error_log("start deleting all questions");
        check_ajax_referer('nqb_quiz_nonce', 'security');
        $args = array(
            'post_type' => 'sfwd-question',
            'posts_per_page' => -1,
            'post_status' => 'any'
        );
    
        $questions = get_posts($args);
        $deleted_count = 0;
    
        foreach ($questions as $question) {
            if (wp_delete_post($question->ID, true)) {
                $deleted_count++;
            }
        }
    
        if ($deleted_count > 0) {
            wp_send_json_success("Successfully deleted {$deleted_count} questions!");
        } else {
            wp_send_json_error('No questions were found to delete.');
        }
    }



	

	/**clears the question history for one particular user */
	public function clear_user_question_history() {
		check_ajax_referer('nqb_quiz_nonce', 'security');
		
		if (!current_user_can('manage_options')) {
			wp_send_json_error('Insufficient permissions');
			return;
		}
	
		$username = sanitize_text_field($_POST['username']);
		$user = get_user_by('login', $username);
		
		if (!$user) {
			wp_send_json_error('User not found');
			return;
		}
	
		$deleted = delete_user_meta($user->ID, 'seen_questions');
		
		if ($deleted) {
			wp_send_json_success("Successfully cleared question history for user: $username");
		} else {
			wp_send_json_error('Failed to clear question history or no history existed');
		}
	}

	/**
	 * Initialize AJAX actions.
	 *
	 * @since 1.0.0
	 */
	public function ajax_init() {

		add_action( 'wp_ajax_load_csvs', array( $this, 'load_csvs' ) );
		add_action('wp_ajax_delete_all_questions', array($this, 'delete_all_questions'));
		add_action('wp_ajax_clear_user_question_history', array($this, 'clear_user_question_history'));

		
	}
	

}
