<?php


class Nqb_quiz_Question_Creator {

    // Define constants for quiz shortcode and metadata
    const QUIZ_ID = 6; // Replace with your quiz ID
    const ADV_QUIZ_ID = 1; // Replace with your LDAdvQuiz ID
    const TOP_LIST_ID = 1; // Replace with your LDAdvQuiz_toplist ID

    const QUESTIONTAXONOMY = 'question_category';

    private $term_id_lookup;
    // Define parent categories and their allowed children
    private $hierarchy = array(
        'type' => array('clinical', 'preclinical'),
        'difficulty' => array('easy', 'medium', 'hard'),
        'system' => array() // All other tags will be considered systems
    );

    public function __construct() {
        // Log the initialization of the Question Creator
        error_log("Nqb_quiz_Question_Creator initialized.");

        $term_id_lookup = $this->term_id_lookup;

        
        // Define parent categories and their allowed children
        $hierarchy = $this->hierarchy;
        
        // First ensure parent categories exist
        foreach(array_keys($hierarchy) as $parent) {
            if (!isset($term_id_lookup[$parent])) {
                $result = wp_insert_term($parent, 'question_category');
                if (!is_wp_error($result)) {
                    $term_id_lookup[$parent] = $result['term_id'];
                }
            }
        }

        error_log("term id lookup: " . print_r($term_id_lookup,true));

        $this->term_id_lookup = $this->get_all_question_category_terms();
        error_log("term id lookup" . print_r($term_id_lookup,true)); 
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
    // can probs delete
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

        error_log("verifying ssm " . print_r($system,true));


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
        
        $tags = array_merge([$difficulty, $type], $system);
        $this->add_taxonomy($question_id, $tags);
        
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





    function add_taxonomy($question_id, $all_tags) {
        $term_id_lookup = $this->term_id_lookup;
        $hierarchy = $this->hierarchy;
        // retrieve
        
        error_log("Adding taxonomy for question ID: " . $question_id);
        error_log("Tags to add: " . print_r($all_tags, true));
        error_log("Current term_id_lookup: " . print_r($term_id_lookup, true));
        
        $term_ids = array(); // Collection of term IDs to set
        
        foreach ($all_tags as $tag) {
            $tag = strtolower(trim($tag));
            
            // Skip if tag is empty
            if (empty($tag)) {
                error_log("Skipping empty tag");
                continue;
            }
            
            error_log("Processing tag: " . $tag);
            
            // If tag already exists, just add its ID
            if (isset($term_id_lookup[$tag])) {
                error_log("Tag already exists in lookup with ID: " . $term_id_lookup[$tag]);
                $term_ids[] = $term_id_lookup[$tag];
                continue;
            }
            
            // Determine parent category for tag
            $parent_term_id = 0;
            
            if (in_array($tag, $hierarchy['type'])) {
                error_log("Tag is a type. Setting parent to: " . $term_id_lookup['type']);
                $parent_term_id = $term_id_lookup['type'];
            } elseif (in_array($tag, $hierarchy['difficulty'])) {
                error_log("Tag is a difficulty. Setting parent to: " . $term_id_lookup['difficulty']);
                $parent_term_id = $term_id_lookup['difficulty'];
            } else {
                error_log("Tag is assumed to be a system. Setting parent to: " . $term_id_lookup['system']);
                $parent_term_id = $term_id_lookup['system'];
            }
            
            // Create new term as child of appropriate parent
            error_log("Attempting to create new term: " . $tag . " with parent ID: " . $parent_term_id);
            $result = wp_insert_term(
                $tag,
                'question_category',
                array('parent' => $parent_term_id)
            );
            
            if (!is_wp_error($result)) {
                error_log("Successfully created term. New term ID: " . $result['term_id']);
                $term_id_lookup[$tag] = $result['term_id'];
                $term_ids[] = $result['term_id'];
            } else {
                error_log("Error creating term: " . $result->get_error_message());
            }
        }
        
        error_log("Final term IDs to set: " . print_r($term_ids, true));
        
        // Set all terms at once
        if (!empty($term_ids)) {
            $set_terms_result = wp_set_post_terms($question_id, $term_ids, 'question_category');
            if (is_wp_error($set_terms_result)) {
                error_log("Error setting terms: " . $set_terms_result->get_error_message());
            } else {
                error_log("Successfully set terms for question ID: " . $question_id);
            }
        } else {
            error_log("No terms to set for question ID: " . $question_id);
        }

        $this->term_id_lookup = $term_id_lookup;
    }
    
// 
    


}
