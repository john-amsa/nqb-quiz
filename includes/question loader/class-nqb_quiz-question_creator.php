<?php


class Nqb_quiz_Question_Creator {

    // Define constants for quiz shortcode and metadata
    const QUIZ_ID = 609; // Replace with your quiz ID
    const ADV_QUIZ_ID = 17; // Replace with your LDAdvQuiz ID
    const TOP_LIST_ID = 17; // Replace with your LDAdvQuiz_toplist ID

    public function __construct() {
        // Log the initialization of the Question Creator
        error_log("Nqb_quiz_Question_Creator initialized.");
    }

    /**
     * Creates a LearnDash question from the first question in the list
     *
     * @param array $questions - Array of Question objects
     * @param int $quiz_id - The ID of the LearnDash quiz
     */
    public function create_questions(array $questions, $quiz_id = self::QUIZ_ID) {
        if (empty($questions)) {
            error_log("No questions provided.");
            return;
        }

        // Get the first question from the list
        $first_question = $questions[0];
        error_log(" first question!!!!");
        $first_question->pretty_print();

        error_log("Start creating questions");

        // Create the question in LearnDash
        $this->create_question($first_question, $quiz_id);
    }


    /**
     * Creates a LearnDash question using the provided Question object
     *
     * @param Question $question - The Question object containing details for the LearnDash question
     * @param int $quiz_id - The ID of the LearnDash quiz to associate the question with
     * @return int|WP_Error - The post ID on success, WP_Error on failure
     */
    public function create_question(Question $question, $quiz_id = self::QUIZ_ID) {
        // Truncate the stem to the first 20 characters for the title
        $post_title = substr(wp_strip_all_tags($question->stem), 0, 22);

        // Set up the question post data with the truncated stem as the title and the full stem as the content
        $question_post = array(
            'post_title'   => "taxonomy time",// $post_title, // Truncated title
            'post_content' => wp_strip_all_tags($question->stem), // Full stem as the content
            'post_status'  => 'publish',
            'post_type'    => 'sfwd-question',
        );

        // Insert the question post into WordPress
        $question_id = wp_insert_post($question_post);

        if (is_wp_error($question_id)) {
            error_log("Error creating LearnDash question: " . $question_id->get_error_message());
            return $question_id;
        }

        // Log success
        error_log("LearnDash Question created with ID: " . $question_id);
        
         // Properly associate the question with the quiz
         $this->associate_question_with_quiz($question_id, $quiz_id);

         // Call the add_taxonomy function to add system, category (difficulty), and type taxonomy terms
        $system = $question->system; // Assuming system is provided in the Question object
        $category = $question->difficulty; // Using difficulty as the category
        $type = $question->type; // Using type as another taxonomy

        // Add taxonomy terms to the question
        $this->add_taxonomy($question_id, $system, $category, $type);


        return $question_id;
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
    
        // Set the terms in the 'question_category' taxonomy
        if (!empty($terms)) {
            wp_set_post_terms($question_id, $terms, 'question_category', true);
        }
    
        
    }
    

    /**
     * Associates the question with the quiz using LearnDash's internal settings
     *
     * @param int $question_id - The ID of the LearnDash question
     * @param int $quiz_id - The ID of the LearnDash quiz
     */
    private function associate_question_with_quiz($question_id, $quiz_id) {
       // Step 1: Associate the question with the quiz using post meta
       update_post_meta($question_id, 'quiz_id', $quiz_id);
       error_log("Question ID " . $question_id . " associated with Quiz ID: " . $quiz_id);

       // Step 2: Update the quiz's list of question IDs
       $quiz_questions = get_post_meta($quiz_id, 'ld_quiz_questions', true);
        error_log(print_r($quiz_questions,true));
       if (!is_array($quiz_questions)) {
           $quiz_questions = [];
       }

       // Add the new question ID to the quiz
       $quiz_questions[] = $question_id;

       // Save the updated question IDs back to the quiz meta
       update_post_meta($quiz_id, 'ld_quiz_questions', $quiz_questions);
       $quiz_questions = get_post_meta($quiz_id, 'ld_quiz_questions', true);
        error_log(print_r($quiz_questions,true));

       // Log the update
       error_log("Question ID " . $question_id . " added to Quiz ID: " . $quiz_id);
   }
}


// class Nqb_quiz_Question_Creator {

    
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