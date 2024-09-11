<?php

class Nqb_quiz_Csv_Loader {

    private $csv_files = [];

    public function __construct() {
        error_log("\n\n\n starting csv loader");
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
                    error_log("CSV file found: " . $file);
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

            // Read each line from the CSV
            while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                error_log("CSV Row: " . implode(", ", $data));  // Log each line of the CSV file
            }

            fclose($handle);
        } else {
            error_log("Unable to open CSV file: " . $csv_file);
        }
    }

    // Other functional methods will be added later
}
?>