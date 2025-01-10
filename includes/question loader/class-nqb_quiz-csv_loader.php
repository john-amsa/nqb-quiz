<?php

class Nqb_quiz_Csv_Loader {

    private $csv_files = [];

    public function __construct() {
        // error_log("\n\n\n starting csv loader");
        // Call the find_csvs method to search for CSV files
        require_once 'class-nqb_quiz-question_loader.php';
        
        
        $this->find_csvs();
    }

    /**
     * Function to find all CSV files in the "question resources" folder
     */
    public function find_csvs() {
        // Define the folder path relative to this script
        $folder_path = __DIR__ . '/question resources';

        // Check if the folder exists
        if (is_dir($folder_path)) {
            // Scan the folder for files
            $files = scandir($folder_path);

            // Loop through the files in the folder
            foreach ($files as $file) {
                // Check if the file has a .csv extension
                if (pathinfo($file, PATHINFO_EXTENSION) === 'csv') {
                    // Log the CSV file name
                    // error_log("CSV file found: " . $file);
                    $full_path = $folder_path . '/' . $file;
                    $this->csv_files[] = $full_path; // Store full path to the CSV file
                    
                    // Read the CSV file
                    $this->read_csv($full_path);
                }
            }
        } else {
            // Log an error if the folder doesn't exist
            error_log("Folder 'question resources' not found.");
        }
    }

    /**
     * Reads the content of a CSV file and logs each line
     * 
     * @param string $csv_file - Path to the CSV file
     */
    public function read_csv($csv_file) {
        if (($handle = fopen($csv_file, 'r')) !== FALSE) {
            // Log the start of reading the CSV
            error_log("Reading CSV file: " . basename($csv_file));

            // Extract the base filename without the extension (this will be used for the system name)
            $filename = basename($csv_file);
            
            // Remove numbers and full stops from the filename to create the system name
            $system = preg_replace('/[0-9.]/', '', pathinfo($filename, PATHINFO_FILENAME));

            // Trim any remaining whitespace in the system name
            $system = trim($system);

            // Skip the first row if it's the header
            $header = fgetcsv($handle, 1000, ',');
            
            // Read each row from the CSV
            while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                // Create a Question object from the CSV row
                $this->create_question_from_row($data, $system);
            }

            // error_log($this->pretty_print_questions());


            fclose($handle);
        } else {
            error_log("Unable to open CSV file: " . $csv_file);
        }
    }

    /**
     * Creates a Question object from a CSV row and saves it in the questions array
     * 
     * @param array $row - A single row of data from the CSV file
     */
    private function create_question_from_row($row, $system) {
        // Columns: Question No.,Preclinical / Clinical,Difficulty Level,Question Stem,Answer Options,Explanation
        $type = $row[0];  // Clinical/Preclinical
        $difficulty = $row[1];  // Difficulty Level
        $system = json_decode($row[2], true);  // Parse JSON array of topics
        $stem = $row[3];  // Question Stem
        $answer_options = json_decode($row[4], true);  // Parse JSON array of answer options
        $correct_index = $row[5];  // Index of correct answer (A-E)
        $correct_text = $row[6];  // Text of correct answer
        $explanation = $row[7];  // Explanation

        // Check if the stem is empty, skip the question if it is
        if (empty(trim($stem))) {
            // error_log("Skipped question with empty stem.");
            return;
        }
        // Create the Question object
        $question = new Question($type, $difficulty, $stem, $explanation, $system);

        // Add answer options if they were successfully parsed
        if ($answer_options !== null) {
            $correct_answer_found = false;
            
            foreach ($answer_options as $index => $option_text) {
                // Convert numeric index to letter (0 -> A, 1 -> B, etc.)
                $option_letter = chr(65 + $index);  // 65 is ASCII for 'A'
                
                // Check if this option matches both the correct index and text
                $matches_index = ($option_letter === $correct_index);
                $matches_text = (trim($option_text) === trim($correct_text));
                
                // Log an error if there's a mismatch between index and text
                if ($matches_index !== $matches_text) {
                    error_log("Warning: Mismatch in correct answer for question '{$stem}'. " .
                            "Index indicates option {$correct_index} but correct text matches option " .
                            ($matches_text ? $option_letter : "none"));
                }
                
                // Only mark as correct if both index and text match
                $isCorrect = $matches_index && $matches_text;
                
                if ($isCorrect) {
                    $correct_answer_found = true;
                }
                
                $question->addAnswerOption($option_text, $isCorrect);
            }
            
            // Log an error if no answer option matched the correct text
            if (!$correct_answer_found) {
                error_log("Error: No matching correct answer found for question '{$stem}'. " .
                        "Correct text: '{$correct_text}', Index: '{$correct_index}'");
            }
        }
      

        // Log the question creation
        // error_log("Created Question: " . $question->stem);

        // Save the question in the questions array
        $this->questions[] = $question;

    }

    /**
     * Parses the answer options string and returns an array of options
     * 
     * @param string $answer_options_str - The string containing answer options
     * @param string $correct_answer - The correct answer as extracted from the explanation
     * @return array - An array of options with 'optionText' and 'isCorrect'
     */
    private function parse_answers($answer_options_str, $correct_answer) {
        $parsed_options = [];
    
        // Normalize the correct answer by trimming and converting to lowercase
        $correct_answer = strtolower(trim($correct_answer));
        // error_log("Normalized correct answer: " . $correct_answer);
    
        // Extract the options using the new function
        $options = $this->extract_option_text($answer_options_str);
    
        // Loop through each option and determine if it's the correct answer
        foreach ($options as $option_letter => $option_text) {
            // Normalize the option text by trimming and converting to lowercase
            $normalized_option_text = strtolower(trim($option_text));
    
            // Compare the normalized option text with the correct answer
            $isCorrect = $normalized_option_text == $correct_answer;
    
            // Log for debugging purposes
            // error_log("Option letter: " . $option_letter);
            // error_log("Option text: " . $normalized_option_text);
            // error_log("Is correct: " . print_r($isCorrect, true));
    
            // Add the parsed option to the array
            $parsed_options[] = [
                'optionText' => $option_text,
                'isCorrect' => $isCorrect
            ];
        }
    
        return $parsed_options;
    }
    

/**
 * Extracts the text for each answer option (a-e) from the answer options string
 *
 * @param string $answer_options_str - The string containing answer options
 * @return array - An array with each answer option text indexed by the letter (a-e)
 */
private function extract_option_text($answer_options_str) {
    $options = [];
    
    // Regular expression to match each option (e.g., "a. AnswerText")
    $regex = '/([a-e])\.\s*(.*?)(?=(?:[a-e]\.|$))/is';

    // Run the regular expression to find all matches
    if (preg_match_all($regex, $answer_options_str, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $option_letter = $match[1];  // The option label (e.g., a, b, c)
            $option_text = trim($match[2]);  // The option text
            
            // Store the option text in the array with the option letter as the key
            $options[$option_letter] = $option_text;
        }
    }
    // error_log("options" . print_r($options, true));

    return $options;
}


    /**
 * Extracts the correct answer from the last line of the explanation
 * 
 * @param string $explanation - The explanation text
 * @return string - The correct answer as extracted from the explanation
 */
private function extract_correct_answer_from_explanation($explanation) {
    $correct_answer = '';

    // Split the explanation into lines
    $lines = preg_split("/\r\n|\n|\r/", $explanation);

    // Check if there are lines in the explanation
    if (!empty($lines)) {
        // Get the last non-empty line
        $last_line = '';
        for ($i = count($lines) - 1; $i >= 0; $i--) {
            $last_line = trim($lines[$i]);
            if (!empty($last_line)) {
                break;
            }
        }

        // Check if the last line contains "The correct answer is" and extract the answer
        if (stripos($last_line, 'The correct answer is') !== false) {
            // Extract the answer after "The correct answer is"
            $correct_answer = trim(preg_replace('/The correct answer is:?/i', '', $last_line));
        }
    }
    // error_log( "correct answer" . $correct_answer);
    return $correct_answer;
}

    /**
     * Pretty print the questions stored in $this->questions and return as a string
     */
    public function pretty_print_questions() {
        $output = '';

        foreach ($this->questions as $question) {
            $truncated_stem = strlen($question->stem) > 20 ? substr($question->stem, 0, 20) . '...' : $question->stem;
            $truncated_explanation = strlen($question->explanation) > 20 ? substr($question->explanation, 0, 20) . '...' : $question->explanation;
            
            $output .= "Question: {$truncated_stem}\n";
            $output .= "Type: {$question->type}\n";
            $output .= "Difficulty: {$question->difficulty}\n";
            $output .= "Explanation: {$truncated_explanation}\n";
            $output .= "Number of Answer Options: " . count($question->answerOptions) . "\n";
            $output .= "Correct Answer: " . $question->getCorrectAnswer() . "\n";
            $output .= str_repeat("-", 40) . "\n"; // Divider for better readability
        }

        return $output;
    }

    /**
     * Returns the array of questions
     *
     * @return array - The array of Question objects
     */
    public function get_questions() {
        return $this->questions;
    }


    // Other functional methods will be added later
}
?>