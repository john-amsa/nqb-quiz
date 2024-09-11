<?php


class Nqb_quiz_Question_Loader {

    private $csv_loader;
    private $question_creator;

    public function __construct() {
        require_once 'class-nqb_quiz-question_creator.php';
        require_once 'class-nqb_quiz-csv_loader.php';

        // Log the start of the constructor
        error_log("Nqb_quiz_Question_Loader initialized.");

        // Initialize the CSV loader
        $this->csv_loader = new Nqb_quiz_Csv_Loader();
        error_log("Nqb_quiz_Csv_Loader initialized.");

        // Initialize the Question Creator
        $this->question_creator = new Nqb_quiz_Question_Creator();
        error_log("Nqb_quiz_Question_Creator initialized.");
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
    public $system;

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
}