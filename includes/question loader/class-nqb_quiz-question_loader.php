<?php


class Nqb_quiz_Question_Loader {

    private $csv_loader;
    private $question_creator;

    public function __construct() {
        require_once 'class-nqb_quiz-question_creator.php';
        require_once 'class-nqb_quiz-csv_loader.php';

        // // Log the start of the constructor
        // error_log("Nqb_quiz_Question_Loader initialized.");

        // // Initialize the CSV loader
        // $this->csv_loader = new Nqb_quiz_Csv_Loader();
        // error_log("Nqb_quiz_Csv_Loader initialized.");

        // // Initialize the Question Creator
        // $this->question_creator = new Nqb_quiz_Question_Creator();
        // error_log("Nqb_quiz_Question_Creator initialized.");

        // $questions = $this->get_loaded_questions();
        // $this->question_creator->create_questions($questions);

        // // $this->create_questions_once();
    }

    public function run_question_loader(){
        // Initialize the CSV loader
        $this->csv_loader = new Nqb_quiz_Csv_Loader();
        error_log("Nqb_quiz_Csv_Loader initialized.");

        // Initialize the Question Creator
        $this->question_creator = new Nqb_quiz_Question_Creator();
        error_log("Nqb_quiz_Question_Creator initialized.");

        $questions = $this->get_loaded_questions();
        $this->question_creator->create_questions($questions);

        return "yay";
    }

    /**
      * Function to run only once during plugin activation
      */
    // public function create_questions_once() {
    //     // Check if the function has already been run in this session
    //     if (get_option('nqb_questions_created') === 'yes') {
    //         error_log("Questions already created, skipping.");
    //         return;
    //     }

    //     $questions = $this->get_loaded_questions();
    //     if (!empty($questions)) {
    //         $this->question_creator->create_questions($questions);
    //         error_log("Questions created during plugin activation.");

    //         // Mark that the questions have been created to avoid re-running
    //         update_option('nqb_questions_created', 'yes');
    //     } else {
    //         error_log("No questions found during plugin activation.");
    //     }
    // }

    


    /**
     * Retrieves questions from the CSV loader
     * 
     * @return array - Array of Question objects
     */
    public function get_loaded_questions() {
        // Get the questions from the CSV loader
        error_log(print_r($this->csv_loader->get_questions(), true));
        return $this->csv_loader->get_questions();
    }

    
    
    

}

class AnswerOption { //protected
    public $optionText;
    public $isCorrect;

    public function __construct($optionText, $isCorrect) {
        $this->optionText = $optionText;
        $this->isCorrect = $isCorrect;
    }
}

class Question {
    public $type;
    public $difficulty;
    public $stem;
    public $answerOptions = [];
    public $explanation;
    public $system = [];

    public function __construct($type, $difficulty, $stem, $explanation, $system) {
        $this->type = $type;
        $this->difficulty = $difficulty;
        $this->stem = $stem;
        $this->explanation = $explanation;
        $this->system = $system;
    }

    public function addAnswerOption($optionText, $isCorrect) {
        $this->answerOptions[] = new AnswerOption($optionText, $isCorrect);
    }

    public function getCorrectAnswer() {
        foreach ($this->answerOptions as $option) {
            if ($option->isCorrect) {
                return $option->optionText;
            }
        }
        return null;
    }
    /**
     * Truncate a string if it exceeds the specified length
     *
     * @param string $text - The text to truncate
     * @param int $maxLength - The maximum length of the string
     * @return string - The truncated string with "..." if needed
     */
    private function truncate($text, $maxLength = 50) {
        if (strlen($text) > $maxLength) {
            return substr($text, 0, $maxLength) . '...';
        }
        return $text;
    }

    /**
     * Pretty prints the Question details to error_log, truncating long lines
     */
    public function pretty_print() {
        error_log("Question:");
        error_log("Type: " . $this->type);
        error_log("Difficulty: " . $this->difficulty);
        error_log("System: " .  print_r($this->system,true));
        error_log("Stem: " . $this->truncate($this->stem));
        error_log("Explanation: " . $this->truncate($this->explanation));

        if (!empty($this->answerOptions)) {
            error_log("Answer Options:");
            foreach ($this->answerOptions as $index => $option) {
                $answerText = $this->truncate($option->optionText);
                $isCorrect = $option->isCorrect ? 'Correct' : 'Incorrect';
                error_log(($index + 1) . ". " . $answerText . " (" . $isCorrect . ")");
            }
        } else {
            error_log("No answer options available.");
        }

        $correctAnswer = $this->getCorrectAnswer();
        if ($correctAnswer) {
            error_log("Correct Answer: " . $this->truncate($correctAnswer));
        } else {
            error_log("No correct answer set.");
        }

        error_log("System: " . print_r($this->system,true));
        // error_log("System: " . $this->truncate($this->system));
        error_log("-------------------------------------------");
    }
}