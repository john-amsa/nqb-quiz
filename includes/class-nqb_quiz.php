<?php

/**
 * The file that defines the core plugin class
 * 
 * most of the logic is here
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://https://amsa.org.au/
 * @since      1.0.0
 *
 * @package    Nqb_quiz
 * @subpackage Nqb_quiz/includes
 */

//  todo

//  quiz engine wise
//  - autodetect shortcode values
//  - if no questions selected then no questions display
//  - randomise
//  - uf_display_page_id
//  - is user meta different for everyone?
//  - move Question to its own file
//  - system=> $heirachy needs a second extra tags section
//  - admin section looks really ugly
//  - default fallback if all questions have been seen
  

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Nqb_quiz
 * @subpackage Nqb_quiz/includes
 * @author     John <john.miao@amsa.org.au>
 */
class Nqb_quiz {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Nqb_quiz_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	protected static $_instance = null; // singleton instance

    private $wp_plugin_dir;

    protected $selector_form = null; // the page id with the selection form on it

    private static $SEARCH_FACTOR = 3; // to speed up searching for questions that fit the user selection

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'NQB_QUIZ_VERSION' ) ) {
			$this->version = NQB_QUIZ_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'nqb_quiz';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

        $this->wp_plugin_dir = plugin_dir_path(__FILE__);
		//the quiz filter
		add_action('plugins_loaded', array($this,'load_filter'));

		add_action( 'init', [ $this, 'run_selection_form' ] );
        // this create pages is broken, just use init to make one for now
        register_activation_hook(__FILE__, array($this, 'create_pages'));



	}

	public static function instance() {
        // singleton
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Nqb_quiz_Loader. Orchestrates the hooks of the plugin.
	 * - Nqb_quiz_i18n. Defines internationalization functionality.
	 * - Nqb_quiz_Admin. Defines all hooks for the admin area.
	 * - Nqb_quiz_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {


		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-nqb_quiz-loader.php';

        $this->loader = new Nqb_quiz_Loader();  //loads the questions in from a csv
    
		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-nqb_quiz-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-nqb_quiz-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-nqb_quiz-public.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/question loader/class-nqb_quiz-question_loader.php';
        

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Nqb_quiz_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Nqb_quiz_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Nqb_quiz_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

        // add button in admin menu for converting CSVs into ld-questions
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_menu' );
        // Handle the AJAX request for loading CSVs
        $this->loader->add_action( 'wp_ajax_load_csvs', $plugin_admin, 'load_csvs' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Nqb_quiz_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Nqb_quiz_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

    /**
     * this is the main filter for custom filtering the ldquiz so that only the user's selected find_questions
     * categories shows up. hooks onto learndash_fetch_quiz_questions
     */
	public function load_filter() { //
        add_filter('learndash_fetch_quiz_questions', array($this, 'filter_questions'), 10, 4);
    }

    /** 
     * hooked onto learndash's filter quiz questions
     * 
     */
    public function filter_questions($pro_questions, $quiz_id, $rand, $max) {
        // Log the quiz ID
        error_log('Quiz ID: ' . $quiz_id);

        // get selection from cookies
        $user_selection = $this->retrieve_selection();
        error_log("User selection contents: " . print_r(json_encode($user_selection), true));

        $quiz_size = $user_selection['size'];
        // find the qs that satisfy the selection
        $filtered_questions = $this->find_questions($user_selection,$pro_questions,$quiz_size);

        // this line turns the filter off for debugging
        // $filtered_questions = $pro_questions;

        error_log(print_r($pro_questions,true)); 
        error_log("found " .  count($filtered_questions) .  " questions");

        // $filtered_questions = $this->limit_questions($filtered_questions,2);
        $this->remember_seen_questions($filtered_questions);


       // Return the filtered questions
        return $filtered_questions;
    }

    function limit_questions($questions, $limit) {
        if (!is_array($questions) || !is_int($limit) || $limit < 1) {
            return $questions;
        }
        
        return array_slice($questions, 0, $limit);
    }
    
    function remember_seen_questions($questions, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id || !is_array($questions)) {
            return false;
        }
        
        // Get existing seen questions
        $seen_questions = get_user_meta($user_id, 'seen_questions', true);
        if (!is_array($seen_questions)) {
            $seen_questions = array();
        }
        
        // Extract question IDs and merge with existing
        $question_ids = array_map(function($question) {
            if ($question instanceof WpProQuiz_Model_Question) {
                return $question->getQuestionPostId();
            } elseif (is_array($question)) {
                return isset($question['id']) ? $question['id'] : null;
            }
            return null;
        }, $questions);
        
        // Filter out null values and merge with existing
        $question_ids = array_filter($question_ids);
        $seen_questions = array_unique(array_merge($seen_questions, $question_ids));
        // todo better way to do this
        
        // Update user meta
        update_user_meta($user_id, 'seen_questions', $seen_questions);
        
        // Log user metadata for verification
        $all_user_meta = get_user_meta($user_id);
        error_log('User Metadata after update:');
        error_log(print_r($all_user_meta, true));
        
        return $seen_questions;
    }
    

    /**
     * finds the questions which satisfies a user's selection
     * @param array $user_selection (a dictionary from the json)
     * @param mixed $pro_questions (Array: the pro_questions that come with the filter)
     * @return array
     */
    // private function find_questions($user_selection,$pro_questions){

    //     // the array to return
    //     $filtered_questions = array();

    //     // load the helper filter with the user's selection
    //     $filter = new Filter_Helper($user_selection);
    //     // error_log(print_r($filter->get_user_selection(),true));
    //     error_log("Starting Filtering");

    //     // iterate over the questions 
    //     foreach (  $pro_questions as $question){
    //         $question_id = $question->getQuestionPostId();
    //         $question_terms = wp_get_post_terms($question_id, 'question_category'); //extract the terms
            
    //         error_log("Filter Check - User Selection: " . print_r($filter->get_user_selection(), true) . " | Question ID: " . $question_id . " | Question Terms: " . print_r($question_terms, true));
        
    //         // if this question's terms exist in our user's requiest, add it to filtered questions
    //         if ($filter->found($question_terms)){
    //             $filtered_questions[] = $question;
    //             error_log("\tincluding question $question_id");
    //         } else {
    //             error_log("\tQuestion removed: $question_id");
    //         }
    //     }
    //     error_log("the count is " . count($filtered_questions));        
        
    //     return $filtered_questions;
    // }

    private function find_questions($user_selection, $pro_questions, $target_count = 20, $ignore_seen = False) {
        // the array to return
        $filtered_questions = array();
        
        // load the helper filter with the user's selection
        $filter = new Filter_Helper($user_selection);
        error_log("Starting Filtering");
        
        // Calculate initial batch size (3x target)
        $batch_size = $target_count * 3;
        $total_questions = count($pro_questions);
        $current_position = 0;
        
        while (count($filtered_questions) < $target_count && $current_position < $total_questions) {
            // Calculate end position for current batch
            $end_position = min($current_position + $batch_size, $total_questions);
            error_log("Searching batch from position $current_position to $end_position");
            
            // Get current batch of questions
            $current_batch = array_slice($pro_questions, $current_position, $batch_size);
            
            // Process the current batch
            foreach ($current_batch as $question) {
                $question_id = $question->getQuestionPostId();

                if($this->check_history($question_id)){
                    continue;
                }

                $question_terms = wp_get_post_terms($question_id, 'question_category');
                
                error_log("Filter Check - User Selection: " . print_r($filter->get_user_selection(), true) . 
                         " | Question ID: " . $question_id . 
                         " | Question Terms: " . print_r($question_terms, true));
            
                if ($filter->found($question_terms)) {
                    $filtered_questions[] = $question;
                    error_log("\tincluding question $question_id");
                    
                    // If we've found enough questions, break out
                    if (count($filtered_questions) >= $target_count) {
                        error_log("Found target number of questions. Stopping search.");
                        break;
                    }
                } else {
                    error_log("\tQuestion removed: $question_id");
                }
            }
            
            // Prepare for next batch if needed
            $current_position += $batch_size;
            
            // Log progress
            error_log("Current filtered question count: " . count($filtered_questions));
            if ($current_position < $total_questions && count($filtered_questions) < $target_count) {
                error_log("Not enough questions found. Continuing to next batch...");
            }
        }
        
        error_log("Final count is " . count($filtered_questions) . 
                  " after searching " . min($current_position, $total_questions) . 
                  " out of " . $total_questions . " total questions");
        
        return $filtered_questions;
    }

    /**
     * Checks if a question has been seen by the current user
     * 
     * @param int $question_id The ID of the question to check
     * @param int|null $user_id Optional user ID. Defaults to current user
     * @return bool True if question has been seen, false otherwise
     */
    private function check_history($question_id, $user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        // Get the user's seen questions array
        $seen_questions = get_user_meta($user_id, 'seen_questions', true);
        
        // If no history exists, initialize empty array
        if (!is_array($seen_questions)) {
            $seen_questions = array();
        }
        
        // Check if this question is in the history
        return in_array($question_id, $seen_questions);
    }
    

    // get user's selection from cookies
    public function retrieve_selection() {
        if (isset($_COOKIE['user_selection'])) {
            $selection = json_decode(wp_unslash($_COOKIE['user_selection']), true); // No need for stripslashes here
            // error_log("User selection retrieved: " . print_r($selection, true));
            return $selection;
        } else {
            error_log("No user selection found in cookies.");
            return null;
        }
    }


	/* selection form */
    // todo: make this a class. it crashes bc of some init call order issue

    function run_selection_form(){
        $this->register_shortcodes();
        $this->add_actions();
    }
    /**
     * creates the form page where the user can select their question categories
     */
    public static function create_pages() {
        // todo : prevent this from happening if these pages already exist on activation
        
        // this one contains the form
        $form_page = array(
            'post_title'   => 'Form Page',
            'post_content' => '[unique_form_page]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        );
        wp_insert_post($form_page);


        // // this one displays the results for now. will be turned into a quiz later
        // $display_page = array(
        //     'post_title'   => 'Display Page',
        //     'post_content' => '[unique_display_page]',
        //     'post_status'  => 'publish',
        //     'post_type'    => 'page',
        // );
        // $display_page_id = wp_insert_post($display_page);
        // if (!is_wp_error($display_page_id)) {
        //     // store the page ID for redirection later
        //     update_option('uf_display_page_id', $display_page_id);
        // }
        error_log("created pages");
    }

    public function register_shortcodes() {
        add_shortcode('unique_form_page', array($this, 'render_form'));
        // add_shortcode('unique_display_page', array($this, 'render_display'));
        // error_log("short codes registered");
    }

    /** 
     * the form that will be displayed to the user
     */
    public function render_form() {
        error_log("rendering form");
        ob_start();
        ?>
		<h1> Select your quiz </h1>

        <style>
        .checkbox-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px 20px;
            max-width: 100%;
        }

        .checkbox-item {
            flex: 1 1 200px; /* Each item tries to take at least 200px, grows/shrinks as needed */
        }
        </style>
        <form id="nqb-form">
            <label for="difficulty">Difficulty:</label><br>
            <!-- <select id="difficulty" name="difficulty">
                <option value="easy">Easy</option>
                <option value="medium">Medium</option>
                <option value="hard">Hard</option>
            </select><br><br> -->
            <input type="checkbox" name="difficulty" value="easy"> easy 🥱<br>
            <input type="checkbox" name="difficulty" value="medium"> medium 🤓<br>
            <input type="checkbox" name="difficulty" value="hard"> hard 🤯<br><br>

            <label for="type">Type:</label>
            <select id="type" name="type">
                <option value="clinical">Clinical</option>
                <option value="preclinical">PreClinical</option>
            </select><br><br>

            <label for="systems">Systems: </label><br>
            <div class="checkbox-grid">
            <label class="checkbox-item"><input type="checkbox" name="systems" value="cardiology"> Cardiovascular</label>
            <label class="checkbox-item"><input type="checkbox" name="systems" value="respiratory"> Respiratory</label>
            <label class="checkbox-item"><input type="checkbox" name="systems" value="gastroenterology"> GIT</label>
            <label class="checkbox-item"><input type="checkbox" name="systems" value="endocrinology"> Endocrine</label>
            <label class="checkbox-item"><input type="checkbox" name="systems" value="neurology"> Neurology</label>
            <label class="checkbox-item"><input type="checkbox" name="systems" value="msk rheumatology"> MSK & Rheumatology</label>
            <label class="checkbox-item"><input type="checkbox" name="systems" value="haematology"> Haematology</label>
            <label class="checkbox-item"><input type="checkbox" name="systems" value="nephrology & urology"> Renal & Urology</label>
            <label class="checkbox-item"><input type="checkbox" name="systems" value="infectious diseases"> Infectious Diseases</label>
            <label class="checkbox-item"><input type="checkbox" name="systems" value="dermatology"> Dermatology</label>
            <label class="checkbox-item"><input type="checkbox" name="systems" value="surgery & anatomy"> Surgery & Anatomy</label>
            <label class="checkbox-item"><input type="checkbox" name="systems" value="ophthalmology"> Ophthalmology</label>
            <label class="checkbox-item"><input type="checkbox" name="systems" value="psychiatry"> Psychiatry</label>
            <label class="checkbox-item"><input type="checkbox" name="systems" value="paediatrics"> Paediatrics</label>
            <label class="checkbox-item"><input type="checkbox" name="systems" value="acute care & emergency"> Acute Care & Emergency</label>
            <label class="checkbox-item"><input type="checkbox" name="systems" value="obstetrics & gynaecology"> Obstetrics & Gynaecology</label>
            <label class="checkbox-item"><input type="checkbox" name="systems" value="pharmacology"> Pharmacology</label>
            </div>
            <label for = "size"> number of questions in the quiz: </label>
            <input type="number" id="size" name="size" value = 20> <br><br>

            <input type="button" id="submit-button" value="Submit">
        </form>
        <?php
        return ob_get_clean();
    }

    public  function add_actions() {
        // when plugin is loaded
        // error_log("add actions being called");
        //shortcodes contain page content
        // add_action('init', array($this, 'register_shortcodes'));
        // $this->register_shortcodes();
        // links this script to the ajax js file
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_handle_form', array($this, 'handle_ajax_request'));
        add_action('wp_ajax_nopriv_handle_form', array($this, 'handle_ajax_request'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('form-handler', plugin_dir_url(__FILE__) . 'form-handler.js', array('jquery'), null, true);
        // name url (use plugin url) dependencies
        wp_localize_script('form-handler', 'uf_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
        // handle, name of js object, data array including ajax url and nonce
    }

    public function handle_ajax_request() {
        error_log("request arrives");
        if (isset($_POST['formData'])) {
            error_log("request exists");
            $formData = json_decode(wp_unslash($_POST['formData']), true);
            error_log("decoded");

            $this->update_cookie($formData);

            // decode the json from the page and set uf_form_selection to the data. valid for an hour
            set_transient('uf_form_selection', json_encode($formData), 3600);
            error_log("set transient");
            //get the display page id and redirect the user to that page
            $page_id = get_option('uf_display_page_id');
            error_log($page_id);
            if (!is_wp_error($page_id)) {
                $page_url = get_permalink($page_id);
                error_log($page_url);
                // echo json_encode(array('message' => 'Selection saved.', 'redirect_url' => $page_url));
                
                echo json_encode(array('message' => 'Selection saved.', 'redirect_url' => ''));

            } else {
                error_log("can't find page");
                echo json_encode(array('message' => 'Error creating display page.', 'redirect_url' => ''));
            }
        }
        wp_die();
    }

    public function update_cookie($formData) {
        // Serialize the form data to a JSON string
        $cookie_value = json_encode($formData);
        error_log(print_r($formData,true));
    
        // Set the cookie, valid for 30 days
        setcookie('user_selection', $cookie_value, time() + (86400 * 30), "/");
    
        error_log("Cookie updated with user selection");
    }

}

// todo: move this elsewhere
class Filter_Helper{
    protected $user_selection = null; // a dictionary of the user's selection
    public function __construct($user_selection){
        $this->user_selection = $this->improve_search_keywords( $user_selection); // speeds up searching later
    }

    public function set_user_selection($user_selection){
        $this->user_selection = $this->improve_search_keywords( $user_selection); // speeds up searching later
    }

    public function get_user_selection(){
        return $this->user_selection;
    }
    /**
     * flips the values into the keys
     * @param mixed $search_keywords
     * @return mixed
     */
    private function improve_search_keywords($search_keywords) {
        $improved_keywords = $search_keywords;
        // using fill keys to flip the arrays inside of the user's search selection
        if (isset($search_keywords['difficulty'])) {
            $improved_keywords['difficulty'] = array_fill_keys($search_keywords['difficulty'], null);
        }
    
        if (isset($search_keywords['systems'])) {
            $improved_keywords['systems'] = array_fill_keys($search_keywords['systems'], null);
        }
        return $improved_keywords;
    }

    /**
     * Returns true if terms satisfies the user's search keywords
     * 
     * @param array $terms Array of WP_Term objects
     * @return bool
     */
    function found_old($terms) {
        if ($this->user_selection === null) {
            error_log('[NQB Quiz] Error: user_selection is not set');
            return false;
        }

        $search_keywords = $this->user_selection;
        $type_flag = false;
        $difficulty_flag = false;
        $systems_flag = false;

        error_log('[NQB Quiz] Starting term search with criteria: ' . json_encode([
            'type' => $search_keywords['type'],
            'difficulty_options' => array_keys($search_keywords['difficulty']),
            'system_options' => array_keys($search_keywords['systems'])
        ]));

        error_log('[NQB Quiz] Number of terms to check: ' . count($terms));

        // Iterate through each term
        foreach ($terms as $index => $term) {
            if (!is_a($term, 'WP_Term')) {
                error_log('[NQB Quiz] Warning: Invalid term object at index ' . $index . ': ' . print_r($term, true));
                continue;
            }

            $slug = $term->slug;
            $taxonomy = $term->taxonomy;
            
            error_log(sprintf(
                '[NQB Quiz] Checking term %d: {slug: %s, taxonomy: %s, term_id: %d}',
                $index,
                $slug,
                $taxonomy,
                $term->term_id
            ));

            // Check system match
            if (array_key_exists($slug, $search_keywords['systems'])) {
                error_log('[NQB Quiz] ✓ System match found: ' . $slug);
                $systems_flag = true;
            }

            // Check difficulty match
            if (array_key_exists($slug, $search_keywords['difficulty'])) {
                error_log('[NQB Quiz] ✓ Difficulty match found: ' . $slug);
                $difficulty_flag = true;
            }

            // Check type match
            if ($slug === $search_keywords['type']) {
                error_log('[NQB Quiz] ✓ Type match found: ' . $slug);
                $type_flag = true;
            }

            // Log current state of all flags
            error_log('[NQB Quiz] Current match status: ' . json_encode([
                'type' => $type_flag,
                'difficulty' => $difficulty_flag,
                'systems' => $systems_flag
            ]));

            // Early return if all conditions met
            if ($type_flag && $difficulty_flag && $systems_flag) {
                error_log('[NQB Quiz] ✓ All criteria matched - returning true');
                return true;
            }
        }

        // Log final state if no match found
        error_log('[NQB Quiz] ✗ No complete match found. Final status: ' . json_encode([
            'type' => $type_flag,
            'difficulty' => $difficulty_flag,
            'systems' => $systems_flag
        ]));

        return false;
    }

    /**
     * Returns true if terms satisfies the user's search keywords
     * 
     * @param array $terms Array of WP_Term objects
     * @return bool
     */
    function found($terms) {
        if ($this->user_selection === null) {
            error_log('[NQB Quiz] Error: user_selection is not set');
            return false;
        }

        $search_keywords = $this->user_selection;
        $type_flag = false;
        $difficulty_flag = false;
        $systems_flag = false;

        error_log('[NQB Quiz] Starting term search with criteria: ' . json_encode([
            'type' => $search_keywords['type'],
            'difficulty_options' => array_keys($search_keywords['difficulty']),
            'system_options' => array_keys($search_keywords['systems'])
        ]));

        error_log('[NQB Quiz] Number of terms to check: ' . count($terms));

        // Iterate through each term
        foreach ($terms as $index => $term) {
            if (!is_a($term, 'WP_Term')) {
                error_log('[NQB Quiz] Warning: Invalid term object at index ' . $index . ': ' . print_r($term, true));
                continue;
            }

            $slug = $term->slug;
            $taxonomy = $term->taxonomy;
            
            error_log(sprintf(
                '[NQB Quiz] Checking term %d: {slug: %s, taxonomy: %s, term_id: %d}',
                $index,
                $slug,
                $taxonomy,
                $term->term_id
            ));

            // Check system match
            if (array_key_exists($slug, $search_keywords['systems'])) {
                error_log('[NQB Quiz] ✓ System match found: ' . $slug);
                $systems_flag = true;
            }

            // Check difficulty match
            if (array_key_exists($slug, $search_keywords['difficulty'])) {
                error_log('[NQB Quiz] ✓ Difficulty match found: ' . $slug);
                $difficulty_flag = true;
            }

            // Check type match
            if ($slug === $search_keywords['type']) {
                error_log('[NQB Quiz] ✓ Type match found: ' . $slug);
                $type_flag = true;
            }

            // Log current state of all flags
            error_log('[NQB Quiz] Current match status: ' . json_encode([
                'type' => $type_flag,
                'difficulty' => $difficulty_flag,
                'systems' => $systems_flag
            ]));

            // Early return if all conditions met
            if ($type_flag && $difficulty_flag && $systems_flag) {
                error_log('[NQB Quiz] ✓ All criteria matched - returning true');
                return true;
            }
        }

        // Log final state if no match found
        error_log('[NQB Quiz] ✗ No complete match found. Final status: ' . json_encode([
            'type' => $type_flag,
            'difficulty' => $difficulty_flag,
            'systems' => $systems_flag
        ]));

        return false;
    }
}
