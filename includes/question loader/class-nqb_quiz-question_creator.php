<?php


class Nqb_quiz_Question_Creator {

    // Define constants for quiz shortcode and metadata
    const QUIZ_ID = 609; // Replace with your quiz ID
    const ADV_QUIZ_ID = 17; // Replace with your LDAdvQuiz ID
    const TOP_LIST_ID = 17; // Replace with your LDAdvQuiz_toplist ID

    const QUESTIONTAXONOMY = 'question_category';

    private $term_id_lookup;

    public function __construct() {
        // Log the initialization of the Question Creator
        error_log("Nqb_quiz_Question_Creator initialized.");

        $this->term_id_lookup = $this->get_all_question_category_terms();
        error_log(print_r($term_id_lookup,true)); 
    }

    /**
     * Returns the currently defined terms for nqb quiz
     * as an dictionary with system name as the key and id as the value
     */
    function get_all_question_category_terms() {
        // Fetch all terms for the 'question_category' taxonomy
        $terms = get_terms(array(
            'taxonomy' => 'question_category',
            'hide_empty' => false, // Set to true if you only want terms that have posts assigned to them
        ));

        // Initialize an empty array to store the terms
        $term_list = array();

        // Check if terms exist
        if (!is_wp_error($terms) && !empty($terms)) {
            foreach ($terms as $term) {
                $term_list[$term->name] = $term->term_id;
            }
        }

        return $term_list;
    }

    
    /*
    should be easy from here on out
    check if term is real
    if its real then use wp set post terms with term ID
    if not create a new term in the heirachy
    put it under the right parent
    then on the other side
    shuffle
    write to databse the seen IDs and remove/screen

    */

    /**
     * Assigns terms to a post based on the provided list of terms if they exist in the class attribute term_id_lookup.
     *
     * @param int $post_id The ID of the post.
     * @param array $taxonomy_terms A list of term system names to assign.
     */
    //this function is a mess todo rm dupes
    function question_add_taxonomy($post_id, $taxonomy_terms) {
        // Ensure $taxonomy_terms is an array
        if (!is_array($taxonomy_terms)) {
            error_log('Invalid input: taxonomy_terms should be an array.');
            return;
        }
        //todo do this earlier in csv loader
        $taxonomy_terms = array_map('strtolower', $taxonomy_terms);

        // Check if term_id_lookup exists and is an array
        if (isset($this->term_id_lookup) && is_array($this->term_id_lookup)) {
            $term_ids = array(); // To collect valid term IDs
            $new_terms = array(); // array of strings

            // Iterate through the list of terms to assign
            foreach ($taxonomy_terms as $nqb_tag) {
                // Check if the term exists in term_id_lookup or in the taxonomy itself
                if (!array_key_exists($nqb_tag, $this->term_id_lookup)) {
                    // Log that the term does not exist and needs to be created
                    error_log('Term with nqb tag: ' . $nqb_tag . ' does not exist in term_id_lookup.');

                    // Fetch the parent term 'system'
                    $parent_term = get_term_by('name', 'system', 'question_category');

                    if ($parent_term && !is_wp_error($parent_term)) {
                        // Create the term under the 'system' parent
                        $new_term = wp_insert_term($nqb_tag, 'question_category', array('parent' => $parent_term->term_id));

                        // Check for errors during term creation
                        if (is_wp_error($new_term)) {
                            $error_message = $new_term->get_error_message();
                            error_log('Failed to insert new term: ' . $nqb_tag . '. Error: ' . $error_message);
                            continue; // Skip to the next term if creation failed
                        } else {
                            // If successful, update term_id_lookup with the new term ID
                            $this->term_id_lookup[$nqb_tag] = $new_term['term_id'];
                            error_log('Successfully created new term: ' . $nqb_tag . ' under parent term "system".');
                        }
                        // // If successful, update term_id_lookup with the new term ID
                        // $this->term_id_lookup[$nqb_tag] = $new_term['term_id'];
                        $this->term_id_lookup = $this->get_all_question_category_terms();

                    } else {
                        error_log('Parent term "system" does not exist or could not be retrieved.');
                        continue; // Skip to the next term if parent term doesn't exist
                    }
                }

                $term_ids[] = $this->term_id_lookup[$nqb_tag];
                // collect all the terms
                     
                 
            }

            if (!empty($term_ids)) {
                // set post terms needs to be done outside of the foreach
                // or else it only updates the call made to wp set post terms
                $result = wp_set_post_terms($post_id, $term_ids, 'question_category');
                error_log("Setting post terms: " . implode(', ', $term_ids) . " for post ID: " . $post_id);
    
                if (is_wp_error($result)) {
                    $error_message = $result->get_error_message();
                    error_log('Failed to set the terms for post ID: ' . $post_id . '. Error: ' . $error_message);
                }
            }

            
        } else {
            // Log an error if the term_id_lookup is not set or is not an array
            error_log('term_id_lookup is not set or is not an array.');
        }
    }

    /**
     * Creates all the questions.
     *
     * @param array $questions - Array of Question objects
     * @param int $quiz_id - The ID of the LearnDash quiz
     */
    public function create_questions(array $questions, $quiz_id = self::QUIZ_ID) {
        if (empty($questions)) {
            error_log("No questions provided.");
            return;
        }

        error_log("Start creating questions");

        // Iterate over all questions
        foreach ($questions as $question) {
            // Check if the stem is not null
            if (!is_null($question->stem)) {
                // Log the question details
                error_log("Creating question with stem: " . substr($question->stem, 0, 50)); // Log the first 50 chars of the stem
                $question->pretty_print();

                // Create the question in LearnDash
                $this->create_question($question, $quiz_id);
            } else {
                error_log("Skipped question due to null stem.");
            }
        }
    }


    /**
     * Creates a LearnDash question using the provided Question object
     *
     * @param Question $question - The Question object containing details for the LearnDash question
     * @param int $quiz_id - The ID of the LearnDash quiz to associate the question with
     * @return int|WP_Error - The post ID on success, WP_Error on failure
     */
    public function create_question(Question $question, $quiz_id = self::QUIZ_ID) {

        // extract question content
        $post_title = substr(wp_strip_all_tags($question->stem), 0, 22);
        // Assuming $question is an instance of the Question class
        $type = $question->type;
        $difficulty = $question->difficulty;
        $stem = $question->stem;
        $answerOptions = $question->answerOptions; // This will be an array of AnswerOption objects
        $explanation = $question->explanation;
        $system = $question->system;       
        $correctAnswerText = $question->getCorrectAnswer();

        error_log("verifying ssm " . $system);


        // prepare the post
        $post_args = array(
            'action'       => 'new_step', // thats what a new question is called in class-learndash-admin-quiz-builder-metabox
            'post_type'    => learndash_get_post_type_slug( LDLMS_Post_Types::QUESTION ), // sfwd-question should be the slug
            'post_status'  => 'publish',
            'post_title'   => $post_title,  
            'post_content' => $stem,
        );
        
        $question_id = wp_insert_post( $post_args, true );

        if ( is_wp_error( $question_id ) || $question_id===0) {
            error_log("Error creating question at wpinsertpost"); 
        }

        // update in database
        $this->question_update_guid($question_id);
        
        // associate with pro_quiz settings
        $question_pro_id = learndash_update_pro_question( 0, $post_args );
        if ( empty( $question_pro_id ) ) {
            error_log("error at proquestion settings"); 
        }

        // attach the question to the quiz
        $this->question_associate_to_quiz($question_pro_id, $question_id);

        // attach pro_quiz settings back on question (?)
        update_post_meta( $question_id, 'question_pro_id', absint( $question_pro_id ) );
        learndash_proquiz_sync_question_fields( $question_id, $question_pro_id );
        
        // attach the quiz to the question 
        learndash_update_setting( $question_id, 'quiz', $quiz_id );
        update_post_meta( $question_id, 'quiz_id', $quiz_id );

        $this->question_add_answers($question_pro_id, $question_id, $answerOptions, $stem);
        error_log("created one question at qid ". $question_id . "and proid " . $question_pro_id); 
        
        $tags = array($system, $difficulty, $type);
        $this->question_add_taxonomy($question_id,  $tags);
        
        
        return $question_id;
    }


    private function question_update_guid($question_id){
        global $wpdb; // the database

        $wpdb->update(
            $wpdb->posts,
            array(
                'guid' => add_query_arg(
                    array(
                        'post_type' => learndash_get_post_type_slug( LDLMS_Post_Types::QUESTION ),
                        'p'         => $question_id,
                    ),
                    home_url()
                ),
            ),
            array( 'ID' => $question_id )
        );
    }

    /**
     * Attaches a question to a quiz
     * @param int $question_pro_id pro_id (not the wordpress post id)
     * @param int $quiz_id this one is the wordpress id
     */
    private function question_associate_to_quiz( $question_pro_id, $question_id){
        $quiz_id = Nqb_quiz_Question_Creator::QUIZ_ID; // quiz id is hardcoded

        // get questions inside the quiz
        $questions = get_post_meta( $quiz_id, 'ld_quiz_questions', true );
        $questions = is_array( $questions ) ? $questions : []; // safety

        // attach question to array of questions
        $questions[ $question_id ] = $question_pro_id;

        // update quiz with new question inside
        update_post_meta( $quiz_id, 'ld_quiz_questions', $questions );
        

    }

    /**
     * adds answers to a question
     * @param int $question_pro_id pro_id (not the wordpress post id)
     * @param int $quiz_id this one is the wordpress id
     */
    private function question_add_answers($question_pro_id, $question_id, $answerOptionsList, $stem){
        $question_mapper     = new \WpProQuiz_Model_QuestionMapper();
        $question_model      = $question_mapper->fetch( $question_pro_id );
        $question_pro_params = [
            '_answerData' => [],
            '_answerType' => 'single',  // Single-answer type question
        ];
        // Define answers (hardcoded)
        // $answers = [
        //     ['answer' => 'Lung', 'correct' => true],  // Correct answer
        //     ['answer' => 'Heart', 'correct' => false],
        //     ['answer' => 'Spleen', 'correct' => false],
        // ];

        $answers = [];

        foreach ($answerOptionsList as $i => $answerOption) {
            $answers[] = ['answer'=> $answerOption->optionText, 'correct'=> $answerOption->isCorrect];
        }

        // Populate the answer data
        foreach ( $answers as $answer ) {
            $answer_data = [
                '_answer'             => $answer['answer'],
                '_correct'            => $answer['correct'],
                '_graded'             => '1',
                '_gradedType'         => 'text',
                '_gradingProgression' => 'not-graded-none',
                '_html'               => false,
                '_points'             => 1,
                '_sortString'         => '',
                '_sortStringHtml'     => false,
                '_type'               => 'answer',
            ];

            $question_pro_params['_answerData'][] = $answer_data;
        }

        $question_pro_params['_question'] = $stem;

        wp_update_post(
            [
                'ID'           => $question_id,
                'post_content' => wp_slash( $question_pro_params['_question'] ),
            ]
        );

        // Save the question model with the updated parameters
        $question_model->set_array_to_object( $question_pro_params );
        $question_mapper->save( $question_model );

      

    }







    private function add_taxonomy($question_id, $system, $category, $type) {
        // Ensure the question is valid
        if (get_post_type($question_id) !== 'sfwd-question') {
            return 'Invalid question ID';
        }
    
        // Prepare the taxonomy terms array
        $terms = array();
    
        // Check and assign terms for system, category, and type
        if (!empty($system)) {
            $terms[] = $system;
        }
        if (!empty($category)) {
            $terms[] = $category;
        }
        if (!empty($type)) {
            $terms[] = $type;
        }

        $terms[] = "cardiovascular";

        error_log(print_r($terms)); 
    
        wp_set_post_terms($question_id, $terms, 'question_category', true);
        
        // Log the assigned terms to verify
        $assigned_terms = wp_get_post_terms($question_id, 'question_category');
        error_log('Assigned terms: ' . print_r($assigned_terms, true));
    
        
    }
    


}

//     /**
//      * Associates the question with the quiz using LearnDash's internal settings
//      *
//      * @param int $question_id - The ID of the LearnDash question
//      * @param int $quiz_id - The ID of the LearnDash quiz
//      */
//     private function associate_question_with_quiz($question_id, $quiz_id) {
        
//             // Log the start of the process
//             error_log("Starting to associate question ID $question_id with quiz ID $quiz_id.");
        
//             // Step 1: Retrieve the current list of questions tied to the quiz
//             $quiz_questions = get_post_meta($quiz_id, 'ld_quiz_questions', true);
        
//             // Initialize the quiz question array if it's empty
//             if (!$quiz_questions) {
//                 $quiz_questions = array();
//                 error_log("No existing questions found for quiz ID $quiz_id. Initializing a new list.");
//             }
        
//             // Step 2: Check if the question is already associated with the quiz
//             if (!empty($quiz_questions['sfwd-question'])) {
//                 foreach ($quiz_questions['sfwd-question'] as $existing_question) {
//                     if ($existing_question['question_id'] == $question_id) {
//                         $message = "Question ID $question_id is already associated with quiz ID $quiz_id.";
//                         error_log($message);
//                         return $message;
//                     }
//                 }
//             }
        
//             // Step 3: Add the new question to the quiz question list
//             $quiz_questions['sfwd-question'][] = array(
//                 'question_id' => $question_id//,
//                 // 'points' => 1, // Assign points (default: 1, you can adjust this as needed)
//                 // 'answer_points' => array(1), // Points for correct answers (default: 1)
//             );
        
//             // Log the addition of the new question
//             error_log("Adding question ID $question_id to quiz ID $quiz_id.");
        
//             // Step 4: Update the quiz with the new list of questions
//             $update_result = update_post_meta($quiz_id, 'ld_quiz_questions', $quiz_questions);
        
//             // Log whether the update was successful
//             if ($update_result) {
//                 $success_message = "Successfully associated question ID $question_id with quiz ID $quiz_id.";
//                 error_log($success_message);
//                 return $success_message;
//             } else {
//                 $error_message = "Failed to associate question ID $question_id with quiz ID $quiz_id.";
//                 error_log($error_message);
//                 return $error_message;
//             }
//         }
        
//    }



// class Nqb_quiz_Question_Creator { // tried to do too much at once and cant figure out whats broken

    
//         // Define constants for quiz shortcode and metadata
//         const QUIZ_ID = 609; // Replace with your quiz ID
//         const ADV_QUIZ_ID = 17; // Replace with your LDAdvQuiz ID
//         const TOP_LIST_ID = 17; // Replace with your LDAdvQuiz_toplist ID
    
//         public function __construct() {
//             // Log the initialization of the Question Creator
//             error_log("Nqb_quiz_Question_Creator initialized.");
//         }

//         /**
//          * Creates a LearnDash question from the first question in the list
//          *
//          * @param array $questions - Array of Question objects
//          * @param int $quiz_id - The ID of the LearnDash quiz
//          */
//         public function create_questions(array $questions, $quiz_id = self::QUIZ_ID) {
//             if (empty($questions)) {
//                 error_log("No questions provided.");
//                 return;
//             }

//             // Get the first question from the list
//             $first_question = $questions[0];

//             error_log("start creating questions");

//             // Create the question in LearnDash
//             $this->create_question($first_question, $quiz_id);
//         }
    
    
//         /**
//          * Creates a LearnDash question using the provided Question object
//          *
//          * @param Question $question - The Question object containing details for the LearnDash question
//          * @param int $quiz_id - The ID of the LearnDash quiz to associate the question with
//          * @return int|WP_Error - The post ID on success, WP_Error on failure
//          */
//         public function create_question(Question $question, $quiz_id = self::QUIZ_ID) {
//             // Set up the question post data with the stem as content
//             $question_post = array(
//                 'post_title'   => wp_strip_all_tags( $question->stem ),
//                 'post_content' => wp_strip_all_tags( $question->stem ), // Set the question stem as the content
//                 'post_status'  => 'publish',
//                 'post_type'    => 'sfwd-question',
//             );
    
//             // Insert the question post into WordPress
//             $question_id = wp_insert_post($question_post);
    
//             if (is_wp_error($question_id)) {
//                 error_log("Error creating LearnDash question: " . $question_id->get_error_message());
//                 return $question_id;
//             }
    
//             // Update LearnDash meta for the question
//             update_post_meta($question_id, 'quiz_id', $quiz_id); // Associate the question with the quiz
//             update_post_meta($question_id, 'question_type', 'single'); // Assuming multiple choice/single answer
    
//             // Prepare the answer options in the format LearnDash expects
//             $answers = [];
//             $correct_answer = 1;
//             foreach ($question->answerOptions as $index => $option) {
//                 $answers[] = [
//                     'value' => wp_strip_all_tags( $option->optionText ),
//                     'isCorrect' => $option->isCorrect ? $correct_answer : 0,
//                 ];
//             }
    
//             // Update LearnDash answer metadata
//             update_post_meta($question_id, 'answers', $answers);
    
//             // Add explanation to correct and incorrect answer messages
//             $correct_message = 'Correct! ' . wp_strip_all_tags( $question->explanation );
//             $incorrect_message = 'Incorrect! ' . wp_strip_all_tags( $question->explanation );
    
//             // Set the feedback for correct and incorrect answers
//             update_post_meta($question_id, 'question_pro', [
//                 'correctMsg' => $correct_message,
//                 'incorrectMsg' => $incorrect_message
//             ]);
    
//             // Log success
//             error_log("LearnDash Question created with ID: " . $question_id);
    
//             // Associate the question with the quiz using the constants for shortcode and meta
//             $this->associate_question_with_quiz($question_id, $quiz_id);
    
//             return $question_id;
//         }
    
//         /**
//          * Associates the question with the quiz using the predefined quiz metadata and constants
//          *
//          * @param int $question_id - The ID of the LearnDash question
//          * @param int $quiz_id - The ID of the LearnDash quiz
//          */
//         private function associate_question_with_quiz($question_id, $quiz_id) {
//             // Associate the question with the quiz in the LearnDash settings
//             learndash_set_setting($question_id, 'quiz', $quiz_id);
    
//             // Log the association
//             error_log("Question ID " . $question_id . " associated with Quiz ID " . $quiz_id);
//         }
    
    

//     // Add functional methods later
// }