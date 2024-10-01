<?php
/**
 * PHDE is a PHP Development Environment utility class.
 * 
 * This class provides methods for managing PHP error reporting, setting HTTP response headers, and handling file downloads.
 * 
 * @author Sakibur Rahman (@sakibweb)
 */
class PHDE {
    // Buffer to store error messages as an array
    private static $errorBuffer = [];

    // Flag to capture errors
    private static $captureErrors = false;

    // Flag to display errors
    private static $displayErrors = false;

    /**
     * Constructor method to initialize error reporting settings.
     *
     * @param bool $state If true, enable error reporting and display errors; if false, disable error reporting and hide errors.
     */
    public function __construct($state = false) {
        if ($state === true) {
            self::enableErrorReporting();
        } elseif ($state === false) {
            self::disableErrorReporting();
        } else {
            self::disableErrorReporting();
        }
    }
    
    /**
     * Enable error reporting and display errors.
     */
    public static function enableErrorReporting() {
        error_reporting(E_ALL);
        set_error_handler([self::class, 'customErrorHandler']);
        register_shutdown_function(function() {
            $error = error_get_last();
            if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_CORE_ERROR || $error['type'] === E_COMPILE_ERROR)) {
                $mainMessage = self::extractMainMessage($error['message']);
                self::$errorBuffer[] = [
                    'errno' => $error['type'],
                    'message' => $mainMessage,
                    'file' => $error['file'],
                    'line' => $error['line'],
                    'type' => self::getErrorType($error['type']),
                    'icon' => self::getErrorIcon(self::getErrorType($error['type'])),
                    'solution' => self::attemptAutoFix($error['type'], $error['message'])
                ];
                self::displayErrors();
            }
        });
        ob_start();
        self::$captureErrors = true;
    }

    /**
     * Disable error reporting and hide errors.
     */
    public static function disableErrorReporting() {
        error_reporting(0);
        ini_set('display_errors', 0);
        set_error_handler(null);
        self::$captureErrors = false;
        ob_end_flush();
    }

    /**
     * Custom error handler to capture errors into a buffer.
     *
     * @param int $errno Error level
     * @param string $errstr Error message
     * @param string $errfile Filename where the error occurred
     * @param int $errline Line number where the error occurred
     * @return bool
     */
    public static function customErrorHandler($errno, $errstr, $errfile, $errline) {
        if (self::$captureErrors) {
            self::$errorBuffer[] = [
                'errno' => $errno,
                'message' => $errstr,
                'file' => $errfile,
                'line' => $errline,
                'type' => self::getErrorType($errno),
                'icon' => self::getErrorIcon(self::getErrorType($errno)),
                'solution' => self::attemptAutoFix($errno, $errstr)
            ];
        }
        return true;
    }

    /**
     * Get the classification of the error type (info-notice, warning, error).
     *
     * @param int $errno Error level
     * @return string
     */
    private static function getErrorType($errno) {
        switch ($errno) {
            case E_NOTICE:
            case E_USER_NOTICE:
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                return "notice";
            case E_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_USER_WARNING:
                return "warning";
            case E_ERROR:
            case E_PARSE:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
            case E_RECOVERABLE_ERROR:
                return "error";
            default:
                return "notice";
        }
    }

    /**
     * Get the corresponding icon for the error type.
     *
     * @param string $type Error type
     * @return string Emoji icon for the error type
     */
    private static function getErrorIcon($type) {
        switch ($type) {
            case "notice":
                return "🟢";
            case "warning":
                return "🟡";
            case "error":
                return "🔴";
            default:
                return "🟢";
        }
    }


    /**
     * Attempt to auto-fix common PHP errors.
     *
     * @param int $errno Error level
     * @param string $errstr Error message
     * @return string Suggested solution or auto-fix result
     */
    private static function attemptAutoFix($errno, $errstr) {
        // Handle undefined variables by suggesting default values
        if (stripos($errstr, 'undefined variable') !== false) {
            return "Define the variable or use isset() to check for its existence.";
        }

        // Handle missing files by providing a file-not-found solution
        if (stripos($errstr, 'failed to open stream: No such file or directory') !== false) {
            return "Check the file path or ensure the file exists.";
        }

        // Handle missing function errors by checking for extensions or typos
        if (stripos($errstr, 'call to undefined function') !== false) {
            return "Ensure the function is defined, check for typos, or verify necessary PHP extensions are enabled.";
        }

        // Handle SQL connection errors
        if (stripos($errstr, 'mysql') !== false || stripos($errstr, 'PDO') !== false) {
            return "Verify your database connection details and ensure the MySQL server is running.";
        }

        // Handle array index errors by checking array bounds
        if (stripos($errstr, 'undefined offset') !== false || stripos($errstr, 'invalid index') !== false) {
            return "Check the array index being accessed or ensure the index exists.";
        }

        // Handle 'Array to string conversion' errors
        if (stripos($errstr, 'array to string conversion') !== false) {
            return "You are likely trying to echo or print an array. Convert it to a string using implode() or print it in a readable format using print_r() or var_dump().";
        }

        // Handle division by zero errors
        if (stripos($errstr, 'division by zero') !== false) {
            return "Check for zero values in the divisor before performing division.";
        }

        // Handle memory limit exhaustion errors
        if (stripos($errstr, 'allowed memory size') !== false) {
            return "Consider increasing the memory limit using ini_set('memory_limit', '256M') or optimize your code to use less memory.";
        }

        // Handle max execution time exceeded errors
        if (stripos($errstr, 'maximum execution time') !== false) {
            return "Consider increasing the execution time limit using ini_set('max_execution_time', '300') or optimize the script to run faster.";
        }

        // Handle deprecated function warnings
        if (stripos($errstr, 'deprecated') !== false) {
            return "You are using a deprecated feature. Update your code to use the recommended alternative.";
        }

        // Handle warning for modifying headers after output
        if (stripos($errstr, 'headers already sent') !== false) {
            return "Ensure that no output is sent before using header() functions, including whitespace or error messages.";
        }

        // Handle function argument warnings
        if (stripos($errstr, 'expects parameter') !== false) {
            return "Ensure the correct argument type and number of arguments are passed to the function.";
        }

        // Handle file permission errors
        if (stripos($errstr, 'permission denied') !== false) {
            return "Check the file or directory permissions and ensure the correct access rights are set.";
        }

        // Handle JSON encoding/decoding errors
        if (stripos($errstr, 'json') !== false) {
            return "Check if the data being encoded/decoded is valid JSON and meets the requirements.";
        }

        // Handle syntax errors
        if (stripos($errstr, 'syntax error') !== false) {
            return "Check for missing or extra brackets, commas, or semicolons, and verify correct syntax.";
        }

        // Handle class not found errors
        if (stripos($errstr, 'class not found') !== false) {
            return "Ensure the class is defined or autoloaded properly, and check for typos in the class name.";
        }

        // Handle undefined property errors
        if (stripos($errstr, 'undefined property') !== false) {
            return "Check if the property exists in the object or class, and ensure it's defined before usage.";
        }

        // Handle invalid argument for foreach loop
        if (stripos($errstr, 'invalid argument for foreach') !== false) {
            return "Ensure the argument is an array or an object that implements Traversable.";
        }

        // Handle file_get_contents() failures
        if (stripos($errstr, 'file_get_contents') !== false) {
            return "Check if the file or URL exists and is accessible, and verify any necessary permissions or configurations.";
        }

        // Handle session start errors
        if (stripos($errstr, 'session_start') !== false) {
            return "Ensure session_start() is called before any output is sent to the browser.";
        }

        // Handle object not found errors
        if (stripos($errstr, 'object not found') !== false) {
            return "Check if the object or instance is created and available in the current context.";
        }

        // Handle Curl errors
        if (stripos($errstr, 'curl') !== false) {
            return "Check the CURL request, verify the URL, and check if the necessary CURL extensions are enabled.";
        }

        // Handle date format errors
        if (stripos($errstr, 'date') !== false) {
            return "Ensure the date string or format is valid and compatible with the expected input.";

        }

        // Handle function not allowed for this object
        if (stripos($errstr, 'method not allowed for this object') !== false) {
            return "Ensure the method is valid for the object type or context in which it's used.";
        }

        // Handle SQL syntax errors
        if (stripos($errstr, 'SQL syntax') !== false) {
            return "Check the SQL query for syntax errors and ensure it follows proper SQL standards.";

        }

        // Handle time limit exceeded
        if (stripos($errstr, 'maximum execution time') !== false) {
            return "Increase the maximum execution time in the php.ini or optimize your script.";
        }

        // Handle exhausted max input variables
        if (stripos($errstr, 'max_input_vars') !== false) {
            return "Increase max_input_vars in the php.ini file.";

        }

        // For all other errors, provide a generic solution message
        return "Review the code and ensure proper error handling and validation.";
    }

    /**
     * Extract the main error message from the full error string.
     * This method intelligently extracts the error message by removing 
     * unnecessary details such as file paths, line numbers, and stack traces.
     *
     * @param string $fullMessage The full error message.
     * @return string The main part of the error message.
     */
    protected static function extractMainMessage($fullMessage) {
        // First, remove the file path and line number by looking for the "in" keyword
        if (preg_match('/^(.*?)( in [^:]+:\d+|$)/', $fullMessage, $matches)) {
            $mainMessage = $matches[1];
        } else {
            $mainMessage = $fullMessage;
        }

        // Further cleanup by removing stack trace or other extra information (e.g., "Stack trace" or "#0")
        $mainMessage = preg_replace('/Stack trace:.*$/s', '', $mainMessage); // Remove stack trace
        $mainMessage = preg_replace('/#\d+.*$/m', '', $mainMessage); // Remove lines starting with #

        // Optionally, remove common patterns like "Uncaught Exception:" to make it more concise
        $patternsToRemove = [
            '/Uncaught\s+[A-Za-z0-9_]+:/',
            '/thrown$/',
        ];

        foreach ($patternsToRemove as $pattern) {
            $mainMessage = preg_replace($pattern, '', $mainMessage);
        }

        return trim($mainMessage);
    }

    /**
     * Initialize error reporting settings.
     *
     * @param bool $state If true, enable error reporting and display errors; if false, disable error reporting and hide errors.
     */
    public static function debug($state = true) {
        if ($state) {
            self::enableErrorReporting();
        } else {
            self::disableErrorReporting();
        }
    }

    /**
     * Get collected error messages as a string.
     * 
     * @param bool $state If true, enable error reporting and display errors; if false, disable error reporting and hide errors.
     * @return string
     */
    public static function errors($state = true) {
        self::$displayErrors = $state;
        if ($state === true) {
            self::disableErrorReporting();
            $errors = self::$errorBuffer;
            self::displayErrors($state);
        } else {
            self::$displayErrors = false;
        }
    }

    /**
     * Get the source code around the error line. If the error is inside a function,
     * return the whole function code.
     *
     * @param string $file The filename where the error occurred.
     * @param int $line The line number of the error.
     * @param int $contextLines Number of context lines to show around the error.
     * @return string The formatted source code.
     */
    private static function getErrorSourceCode($file, $line, $contextLines = 3) {
        if (!file_exists($file)) {
            return "Source code not available: File not found\n";
        }

        $fileObject = new SplFileObject($file);
        $fileObject->seek($line - 1); // PHP lines are 1-based, SplFileObject is 0-based

        $errorLineContent = $fileObject->current();
        $startLine = max($line - $contextLines - 1, 0);
        $endLine = $line + $contextLines;

        // Capture the surrounding code
        $fileObject->rewind();
        $sourceCode = "";
        $inFunction = false;

        foreach (new LimitIterator($fileObject, $startLine, $endLine - $startLine) as $key => $codeLine) {
            $currentLine = $key + 1; // Line number

            // Detect if we're inside a function
            if (strpos($codeLine, 'function') !== false || $inFunction) {
                $inFunction = true;
                $sourceCode .= ($currentLine === $line) ? ">> " : "   ";
                $sourceCode .= "$codeLine"; // Mark the error line
                if (strpos($codeLine, '}') !== false) {
                    $inFunction = false;
                }
            } else {
                // Otherwise just show the context lines
                $sourceCode .= ($currentLine === $line) ? ">> " : "   ";
                $sourceCode .= "$codeLine"; // Mark the error line
            }
        }

        return $sourceCode;
    }

    /**
     * Display captured errors from the error buffer.
     *
     * @return void
     */
    public static function displayErrors($state = true) {
        if (!empty(self::$errorBuffer)) {
            $errors = self::$errorBuffer;
            if ($state && !empty($errors)) {
                PHRQ::api("text/html; charset=UTF-8");
                $html = base64_decode("ICAgIDxzdHlsZT4KICAgICAgICAvKiBib2R5IHsKICAgICAgICAgICAgbWFyZ2luOiAwOwogICAgICAgICAgICBwYWRkaW5nOiAwOwogICAgICAgICAgICBmb250LWZhbWlseTogQXJpYWwsIHNhbnMtc2VyaWY7CiAgICAgICAgfSAqLwoKICAgICAgICAjZGVidWctYmFyIHsKICAgICAgICAgICAgcG9zaXRpb246IGZpeGVkOwogICAgICAgICAgICBib3R0b206IDA7CiAgICAgICAgICAgIGxlZnQ6IDA7CiAgICAgICAgICAgIHdpZHRoOiAxMDAlOwogICAgICAgICAgICBtYXgtaGVpZ2h0OiA4MCU7CiAgICAgICAgICAgIGhlaWdodDogMjAwcHg7CiAgICAgICAgICAgIGJhY2tncm91bmQtY29sb3I6ICMzMzM7CiAgICAgICAgICAgIGNvbG9yOiB3aGl0ZTsKICAgICAgICAgICAgei1pbmRleDogMTAwMDsKICAgICAgICAgICAgYm9yZGVyLXRvcDogMnB4IHNvbGlkICM0NDQ7CiAgICAgICAgICAgIHRyYW5zaXRpb246IGhlaWdodCAwLjNzIGVhc2U7CiAgICAgICAgICAgIGJveC1zaGFkb3c6IDAgLTJweCAxMHB4IHJnYmEoMCwgMCwgMCwgMC41KTsKICAgICAgICAgICAgcmVzaXplOiB2ZXJ0aWNhbDsKICAgICAgICAgICAgLyogb3ZlcmZsb3c6IGhpZGRlbjsgKi8KICAgICAgICB9CgogICAgICAgICNkZWJ1Zy1iYXItaGVhZGVyIHsKICAgICAgICAgICAgcGFkZGluZzogNXB4OwogICAgICAgICAgICBiYWNrZ3JvdW5kLWNvbG9yOiAjNDQ0OwogICAgICAgICAgICBkaXNwbGF5OiBmbGV4OwogICAgICAgICAgICBqdXN0aWZ5LWNvbnRlbnQ6IHNwYWNlLWJldHdlZW47CiAgICAgICAgICAgIGFsaWduLWl0ZW1zOiBjZW50ZXI7CiAgICAgICAgICAgIGN1cnNvcjogcG9pbnRlcjsKICAgICAgICB9CgogICAgICAgICNkZWJ1Zy1iYXItdGl0bGUgewogICAgICAgICAgICB0ZXh0LWFsaWduOiBsZWZ0OwogICAgICAgICAgICBmbGV4LWdyb3c6IDE7CiAgICAgICAgICAgIHBhZGRpbmctbGVmdDogMTBweDsKICAgICAgICB9CgogICAgICAgICNkZWJ1Zy1iYXItdGl0bGUgYSB7CiAgICAgICAgICAgIGNvbG9yOiAjRkZENzAwOwogICAgICAgICAgICB0ZXh0LWRlY29yYXRpb246IG5vbmU7CiAgICAgICAgICAgIGZvbnQtd2VpZ2h0OiBib2xkOwogICAgICAgIH0KCiAgICAgICAgI2RlYnVnLWJhci1jb250cm9scyB7CiAgICAgICAgICAgIGRpc3BsYXk6IGZsZXg7CiAgICAgICAgICAgIGFsaWduLWl0ZW1zOiBjZW50ZXI7CiAgICAgICAgfQoKICAgICAgICAjZGVidWctYmFyLWNvbnRyb2xzIGJ1dHRvbiB7CiAgICAgICAgICAgIGRpc3BsYXk6IGZsZXg7CiAgICAgICAgICAgIGJhY2tncm91bmQ6IG5vbmU7CiAgICAgICAgICAgIGJvcmRlcjogbm9uZTsKICAgICAgICAgICAgY29sb3I6IHdoaXRlOwogICAgICAgICAgICBjdXJzb3I6IHBvaW50ZXI7CiAgICAgICAgICAgIGZvbnQtc2l6ZTogMTZweDsKICAgICAgICAgICAgbWFyZ2luLWxlZnQ6IDEwcHg7CiAgICAgICAgICAgIHBhZGRpbmc6IDVweDsKICAgICAgICAgICAgYWxpZ24taXRlbXM6IGNlbnRlcjsKICAgICAgICAgICAganVzdGlmeS1jb250ZW50OiBzcGFjZS1ldmVubHk7CiAgICAgICAgfQoKICAgICAgICAjZGVidWctYmFyLWNvbnRlbnQgewogICAgICAgICAgICBkaXNwbGF5OiBmbGV4OwogICAgICAgICAgICBoZWlnaHQ6IGNhbGMoMTAwJSAtIDM2cHgpOwogICAgICAgICAgICBwYWRkaW5nOiAxMHB4OwogICAgICAgICAgICBiYWNrZ3JvdW5kLWNvbG9yOiAjMWQxZjIxOwogICAgICAgIH0KCiAgICAgICAgLmRlYnVnLXNlY3Rpb24gewogICAgICAgICAgICBmbGV4LWdyb3c6IDE7CiAgICAgICAgICAgIG1hcmdpbjogMCA1cHg7CiAgICAgICAgICAgIG92ZXJmbG93LXk6IGF1dG87CiAgICAgICAgfQoKICAgICAgICAuZGVidWctc2VjdGlvbiBoMyB7CiAgICAgICAgICAgIG1hcmdpbjogMCAwIDEwcHggMDsKICAgICAgICB9CgogICAgICAgICNkZWJ1Zy1iYXIgdWwgewogICAgICAgICAgICBsaXN0LXN0eWxlOiBub25lOwogICAgICAgICAgICBwYWRkaW5nOiAwOwogICAgICAgIH0KCiAgICAgICAgI2RlYnVnLWJhciB1bCBsaSB7CiAgICAgICAgICAgIHBhZGRpbmc6IDVweDsKICAgICAgICAgICAgYm9yZGVyLWJvdHRvbTogMXB4IHNvbGlkICM0NDQ7CiAgICAgICAgICAgIGJhY2tncm91bmQtY29sb3I6ICMzMzM7CiAgICAgICAgICAgIG1hcmdpbi1ib3R0b206IDVweDsKICAgICAgICAgICAgdHJhbnNpdGlvbjogYmFja2dyb3VuZC1jb2xvciAwLjNzOwogICAgICAgIH0KCiAgICAgICAgI2RlYnVnLWJhciB1bCBsaTpob3ZlciB7CiAgICAgICAgICAgIGJhY2tncm91bmQtY29sb3I6ICM1NTU7CiAgICAgICAgfQoKICAgICAgICAjZXJyb3Itc291cmNlLWNvbnRlbnQgewogICAgICAgICAgICBwYWRkaW5nOiAxMHB4OwogICAgICAgICAgICBib3JkZXItcmFkaXVzOiA0cHg7CiAgICAgICAgICAgIGZvbnQtc2l6ZTogMTRweDsKICAgICAgICAgICAgbGluZS1oZWlnaHQ6IDEuNDsKICAgICAgICAgICAgZm9udC13ZWlnaHQ6IGJvbGQ7CiAgICAgICAgICAgIHdoaXRlLXNwYWNlOiBwcmUtd3JhcDsKICAgICAgICAgICAgb3ZlcmZsb3cteDogYXV0bzsKICAgICAgICB9CgogICAgICAgIC5yZXNpemVyIHsKICAgICAgICAgICAgd2lkdGg6IDVweDsKICAgICAgICAgICAgYmFja2dyb3VuZC1jb2xvcjogIzU1NTsKICAgICAgICAgICAgY3Vyc29yOiBldy1yZXNpemU7CiAgICAgICAgICAgIHBvc2l0aW9uOiByZWxhdGl2ZTsKICAgICAgICB9CgogICAgICAgICNkZWJ1Zy1idXR0b24gewogICAgICAgICAgICBkaXNwbGF5OiBub25lOwogICAgICAgICAgICBwb3NpdGlvbjogZml4ZWQ7CiAgICAgICAgICAgIGJvdHRvbTogMTBweDsKICAgICAgICAgICAgbGVmdDogMTBweDsKICAgICAgICAgICAgd2lkdGg6IDQwcHg7CiAgICAgICAgICAgIGhlaWdodDogNDBweDsKICAgICAgICAgICAgYmFja2dyb3VuZC1jb2xvcjogIzMzMzsKICAgICAgICAgICAgYm9yZGVyLXJhZGl1czogNTAlOwogICAgICAgICAgICBib3JkZXI6IDJweCBzb2xpZCAjNDQ0OwogICAgICAgICAgICBib3gtc2hhZG93OiAwIDAgMTBweCByZ2JhKDAsIDAsIDAsIDAuNSk7CiAgICAgICAgICAgIGN1cnNvcjogcG9pbnRlcjsKICAgICAgICAgICAgei1pbmRleDogMTAwMTsKICAgICAgICB9CgogICAgICAgICNkZWJ1Zy1idXR0b246OmJlZm9yZSB7CiAgICAgICAgICAgIGNvbnRlbnQ6ICLwn5CeIjsKICAgICAgICAgICAgZGlzcGxheTogYmxvY2s7CiAgICAgICAgICAgIGZvbnQtc2l6ZTogMjRweDsKICAgICAgICAgICAgdGV4dC1hbGlnbjogY2VudGVyOwogICAgICAgICAgICBsaW5lLWhlaWdodDogMzZweDsKICAgICAgICB9CgogICAgICAgICN0b3AtcmVzaXplciB7CiAgICAgICAgICAgIGhlaWdodDogNXB4OwogICAgICAgICAgICBiYWNrZ3JvdW5kLWNvbG9yOiAjNTU1OwogICAgICAgICAgICBjdXJzb3I6IG5zLXJlc2l6ZTsKICAgICAgICAgICAgcG9zaXRpb246IGFic29sdXRlOwogICAgICAgICAgICB0b3A6IDA7CiAgICAgICAgICAgIHdpZHRoOiAxMDAlOwogICAgICAgIH0KICAgIDwvc3R5bGU+CiAgICA8c3R5bGU+CiAgICAgICAgLyogUmFpbmJvdyB2Mi4xLjQgcmFpbmJvd2NvLmRlIHwgdGhlbWU6IHRvbW9ycm93LW5pZ2h0ICovQGtleWZyYW1lcyBmYWRlLWluezAle29wYWNpdHk6MH0xMDAle29wYWNpdHk6MX19QGtleWZyYW1lcyBmYWRlezEwJXt0cmFuc2Zvcm06c2NhbGUoMSwgMSl9MzUle3RyYW5zZm9ybTpzY2FsZSgxLCAxLjcpfTQwJXt0cmFuc2Zvcm06c2NhbGUoMSwgMS43KX01MCV7b3BhY2l0eToxfTYwJXt0cmFuc2Zvcm06c2NhbGUoMSwgMSl9MTAwJXt0cmFuc2Zvcm06c2NhbGUoMSwgMSk7b3BhY2l0eTowfX1bZGF0YS1sYW5ndWFnZV0gY29kZSxbY2xhc3NePSJsYW5nIl0gY29kZSxwcmUgW2RhdGEtbGFuZ3VhZ2VdLHByZSBbY2xhc3NePSJsYW5nIl17b3BhY2l0eTowOy1tcy1maWx0ZXI6InByb2dpZDpEWEltYWdlVHJhbnNmb3JtLk1pY3Jvc29mdC5BbHBoYShPcGFjaXR5PTEwMCkiO2FuaW1hdGlvbjpmYWRlLWluIDUwbXMgZWFzZS1pbi1vdXQgMnMgZm9yd2FyZHN9W2RhdGEtbGFuZ3VhZ2VdIGNvZGUucmFpbmJvdyxbY2xhc3NePSJsYW5nIl0gY29kZS5yYWluYm93LHByZSBbZGF0YS1sYW5ndWFnZV0ucmFpbmJvdyxwcmUgW2NsYXNzXj0ibGFuZyJdLnJhaW5ib3d7YW5pbWF0aW9uOm5vbmU7dHJhbnNpdGlvbjpvcGFjaXR5IDUwbXMgZWFzZS1pbi1vdXR9W2RhdGEtbGFuZ3VhZ2VdIGNvZGUubG9hZGluZyxbY2xhc3NePSJsYW5nIl0gY29kZS5sb2FkaW5nLHByZSBbZGF0YS1sYW5ndWFnZV0ubG9hZGluZyxwcmUgW2NsYXNzXj0ibGFuZyJdLmxvYWRpbmd7YW5pbWF0aW9uOm5vbmV9W2RhdGEtbGFuZ3VhZ2VdIGNvZGUucmFpbmJvdy1zaG93LFtjbGFzc149ImxhbmciXSBjb2RlLnJhaW5ib3ctc2hvdyxwcmUgW2RhdGEtbGFuZ3VhZ2VdLnJhaW5ib3ctc2hvdyxwcmUgW2NsYXNzXj0ibGFuZyJdLnJhaW5ib3ctc2hvd3tvcGFjaXR5OjF9cHJle3Bvc2l0aW9uOnJlbGF0aXZlfXByZS5sb2FkaW5nIC5wcmVsb2FkZXIgZGl2e2FuaW1hdGlvbi1wbGF5LXN0YXRlOnJ1bm5pbmd9cHJlLmxvYWRpbmcgLnByZWxvYWRlciBkaXY6bnRoLW9mLXR5cGUoMSl7YmFja2dyb3VuZDojMDA4MWY1O2FuaW1hdGlvbjpmYWRlIDEuNXMgMzAwbXMgbGluZWFyIGluZmluaXRlfXByZS5sb2FkaW5nIC5wcmVsb2FkZXIgZGl2Om50aC1vZi10eXBlKDIpe2JhY2tncm91bmQ6IzUwMDBmNTthbmltYXRpb246ZmFkZSAxLjVzIDQzOG1zIGxpbmVhciBpbmZpbml0ZX1wcmUubG9hZGluZyAucHJlbG9hZGVyIGRpdjpudGgtb2YtdHlwZSgzKXtiYWNrZ3JvdW5kOiM5MDAwZjU7YW5pbWF0aW9uOmZhZGUgMS41cyA1NzdtcyBsaW5lYXIgaW5maW5pdGV9cHJlLmxvYWRpbmcgLnByZWxvYWRlciBkaXY6bnRoLW9mLXR5cGUoNCl7YmFja2dyb3VuZDojZjUwNDE5O2FuaW1hdGlvbjpmYWRlIDEuNXMgNzE1bXMgbGluZWFyIGluZmluaXRlfXByZS5sb2FkaW5nIC5wcmVsb2FkZXIgZGl2Om50aC1vZi10eXBlKDUpe2JhY2tncm91bmQ6I2Y1NzkwMDthbmltYXRpb246ZmFkZSAxLjVzIDg1M21zIGxpbmVhciBpbmZpbml0ZX1wcmUubG9hZGluZyAucHJlbG9hZGVyIGRpdjpudGgtb2YtdHlwZSg2KXtiYWNrZ3JvdW5kOiNmNWU2MDA7YW5pbWF0aW9uOmZhZGUgMS41cyA5OTJtcyBsaW5lYXIgaW5maW5pdGV9cHJlLmxvYWRpbmcgLnByZWxvYWRlciBkaXY6bnRoLW9mLXR5cGUoNyl7YmFja2dyb3VuZDojMDBmNTBjO2FuaW1hdGlvbjpmYWRlIDEuNXMgMTEzMG1zIGxpbmVhciBpbmZpbml0ZX1wcmUgLnByZWxvYWRlcntwb3NpdGlvbjphYnNvbHV0ZTt0b3A6MTJweDtsZWZ0OjEwcHh9cHJlIC5wcmVsb2FkZXIgZGl2e3dpZHRoOjEycHg7aGVpZ2h0OjEycHg7Ym9yZGVyLXJhZGl1czo0cHg7ZGlzcGxheTppbmxpbmUtYmxvY2s7bWFyZ2luLXJpZ2h0OjRweDtvcGFjaXR5OjA7YW5pbWF0aW9uLXBsYXktc3RhdGU6cGF1c2VkO2FuaW1hdGlvbi1maWxsLW1vZGU6Zm9yd2FyZHN9cHJle2JhY2tncm91bmQtY29sb3I6IzAwMDt3b3JkLXdyYXA6YnJlYWstd29yZDttYXJnaW46MHB4O3BhZGRpbmc6MTBweDtjb2xvcjojZmZmO2ZvbnQtc2l6ZToxNHB4O21hcmdpbi1ib3R0b206MjBweH1wcmUsY29kZXtmb250LWZhbWlseTonTW9uYWNvJywgJ01lbmxvJywgY291cmllciwgbW9ub3NwYWNlfXByZXtiYWNrZ3JvdW5kLWNvbG9yOiMxZDFmMjE7Y29sb3I6I2M1YzhjNn1wcmUgLmNvbW1lbnR7Y29sb3I6Izk2OTg5Nn1wcmUgLnZhcmlhYmxlLmdsb2JhbCxwcmUgLnZhcmlhYmxlLmNsYXNzLHByZSAudmFyaWFibGUuaW5zdGFuY2V7Y29sb3I6I2M2Nn1wcmUgLmNvbnN0YW50Lm51bWVyaWMscHJlIC5jb25zdGFudC5sYW5ndWFnZSxwcmUgLmNvbnN0YW50LmhleC1jb2xvcixwcmUgLmtleXdvcmQudW5pdHtjb2xvcjojZGU5MzVmfXByZSAuY29uc3RhbnQscHJlIC5lbnRpdHkscHJlIC5lbnRpdHkuY2xhc3MscHJlIC5zdXBwb3J0e2NvbG9yOiNmMGM2NzR9cHJlIC5jb25zdGFudC5zeW1ib2wscHJlIC5zdHJpbmd7Y29sb3I6I2I1YmQ2OH1wcmUgLmVudGl0eS5mdW5jdGlvbixwcmUgLnN1cHBvcnQuY3NzLXByb3BlcnR5LHByZSAuc2VsZWN0b3J7Y29sb3I6IzgxYTJiZX1wcmUgLmtleXdvcmQscHJlIC5zdG9yYWdle2NvbG9yOiNiMjk0YmJ9CiAgICA8L3N0eWxlPgoKICAgIDxzY3JpcHQ+CiAgICAgICAgLyogUmFpbmJvdyB2Mi4xLjQgcmFpbmJvd2NvLmRlIHwgaW5jbHVkZWQgbGFuZ3VhZ2VzOiBjc3MsIGdlbmVyaWMsIGh0bWwsIGpzb24sIHBocCwgc3FsICovIWZ1bmN0aW9uKGUsdCl7Im9iamVjdCI9PXR5cGVvZiBleHBvcnRzJiYidW5kZWZpbmVkIiE9dHlwZW9mIG1vZHVsZT9tb2R1bGUuZXhwb3J0cz10KCk6ImZ1bmN0aW9uIj09dHlwZW9mIGRlZmluZSYmZGVmaW5lLmFtZD9kZWZpbmUodCk6ZS5SYWluYm93PXQoKX0odGhpcyxmdW5jdGlvbigpeyJ1c2Ugc3RyaWN0IjtmdW5jdGlvbiBlKCl7cmV0dXJuInVuZGVmaW5lZCIhPXR5cGVvZiBtb2R1bGUmJiJvYmplY3QiPT10eXBlb2YgbW9kdWxlLmV4cG9ydHN9ZnVuY3Rpb24gdCgpe3JldHVybiJ1bmRlZmluZWQiPT10eXBlb2YgZG9jdW1lbnQmJiJ1bmRlZmluZWQiIT10eXBlb2Ygc2VsZn1mdW5jdGlvbiBuKGUpe3ZhciB0PWUuZ2V0QXR0cmlidXRlKCJkYXRhLWxhbmd1YWdlIil8fGUucGFyZW50Tm9kZS5nZXRBdHRyaWJ1dGUoImRhdGEtbGFuZ3VhZ2UiKTtpZighdCl7dmFyIG49L1xibGFuZyg/OnVhZ2UpPy0oXHcrKS8scj1lLmNsYXNzTmFtZS5tYXRjaChuKXx8ZS5wYXJlbnROb2RlLmNsYXNzTmFtZS5tYXRjaChuKTtyJiYodD1yWzFdKX1yZXR1cm4gdD90LnRvTG93ZXJDYXNlKCk6bnVsbH1mdW5jdGlvbiByKGUsdCxuLHIpe3JldHVybihuIT09ZXx8ciE9PXQpJiYobjw9ZSYmcj49dCl9ZnVuY3Rpb24gYShlKXtyZXR1cm4gZS5yZXBsYWNlKC88L2csIiZsdDsiKS5yZXBsYWNlKC8+L2csIiZndDsiKS5yZXBsYWNlKC8mKD8hW1x3XCNdKzspL2csIiZhbXA7Iil9ZnVuY3Rpb24gbyhlLHQpe2Zvcih2YXIgbj0wLHI9MTtyPHQ7KytyKWVbcl0mJihuKz1lW3JdLmxlbmd0aCk7cmV0dXJuIG59ZnVuY3Rpb24gaShlLHQsbixyKXtyZXR1cm4gbj49ZSYmbjx0fHxyPmUmJnI8dH1mdW5jdGlvbiBzKGUpe3ZhciB0PVtdO2Zvcih2YXIgbiBpbiBlKWUuaGFzT3duUHJvcGVydHkobikmJnQucHVzaChuKTtyZXR1cm4gdC5zb3J0KGZ1bmN0aW9uKGUsdCl7cmV0dXJuIHQtZX0pfWZ1bmN0aW9uIHUoZSx0LG4scil7dmFyIGE9ci5zdWJzdHIoZSk7cmV0dXJuIHIuc3Vic3RyKDAsZSkrYS5yZXBsYWNlKHQsbil9ZnVuY3Rpb24gYyh0LFByaXNtKXtpZihlKCkpcmV0dXJuIGdsb2JhbC5Xb3JrZXI9cmVxdWlyZSgid2Vid29ya2VyLXRocmVhZHMiKS5Xb3JrZXIsbmV3IFdvcmtlcihfX2ZpbGVuYW1lKTt2YXIgbj1QcmlzbS50b1N0cmluZygpLGM9cy50b1N0cmluZygpO2MrPWEudG9TdHJpbmcoKSxjKz1yLnRvU3RyaW5nKCksYys9aS50b1N0cmluZygpLGMrPXUudG9TdHJpbmcoKSxjKz1vLnRvU3RyaW5nKCksYys9bjt2YXIgbD1jKyJcdHRoaXMub25tZXNzYWdlPSIrdC50b1N0cmluZygpLGY9bmV3IEJsb2IoW2xdLHt0eXBlOiJ0ZXh0L2phdmFzY3JpcHQifSk7cmV0dXJuIG5ldyBXb3JrZXIoKHdpbmRvdy5VUkx8fHdpbmRvdy53ZWJraXRVUkwpLmNyZWF0ZU9iamVjdFVSTChmKSl9ZnVuY3Rpb24gbChlKXtmdW5jdGlvbiB0KCl7c2VsZi5wb3N0TWVzc2FnZSh7aWQ6bi5pZCxsYW5nOm4ubGFuZyxyZXN1bHQ6YX0pfXZhciBuPWUuZGF0YSxyPW5ldyBQcmlzbShuLm9wdGlvbnMpLGE9ci5yZWZyYWN0KG4uY29kZSxuLmxhbmcpO3JldHVybiBuLmlzTm9kZT8odCgpLHZvaWQgc2VsZi5jbG9zZSgpKTp2b2lkIHNldFRpbWVvdXQoZnVuY3Rpb24oKXt0KCl9LDFlMypuLm9wdGlvbnMuZGVsYXkpfWZ1bmN0aW9uIGYoKXtyZXR1cm4oUnx8bnVsbD09PWopJiYoaj1jKGwsUHJpc20pKSxqfWZ1bmN0aW9uIGQoZSx0KXtmdW5jdGlvbiBuKGEpe2EuZGF0YS5pZD09PWUuaWQmJih0KGEuZGF0YSksci5yZW1vdmVFdmVudExpc3RlbmVyKCJtZXNzYWdlIixuKSl9dmFyIHI9ZigpO3IuYWRkRXZlbnRMaXN0ZW5lcigibWVzc2FnZSIsbiksci5wb3N0TWVzc2FnZShlKX1mdW5jdGlvbiBnKGUsdCxuKXtyZXR1cm4gZnVuY3Rpb24ocil7ZS5pbm5lckhUTUw9ci5yZXN1bHQsZS5jbGFzc0xpc3QucmVtb3ZlKCJsb2FkaW5nIiksZS5jbGFzc0xpc3QuYWRkKCJyYWluYm93LXNob3ciKSwiUFJFIj09PWUucGFyZW50Tm9kZS50YWdOYW1lJiYoZS5wYXJlbnROb2RlLmNsYXNzTGlzdC5yZW1vdmUoImxvYWRpbmciKSxlLnBhcmVudE5vZGUuY2xhc3NMaXN0LmFkZCgicmFpbmJvdy1zaG93IikpLE0mJk0oZSxyLmxhbmcpLDA9PT0tLXQuYyYmbigpfX1mdW5jdGlvbiBtKGUpe3JldHVybntwYXR0ZXJuczpDLGluaGVyaXRlbmNlTWFwOlMsYWxpYXNlczpULGdsb2JhbENsYXNzOmUuZ2xvYmFsQ2xhc3MsZGVsYXk6aXNOYU4oZS5kZWxheSk/MDplLmRlbGF5fX1mdW5jdGlvbiB2KGUsdCl7dmFyIG49e307Im9iamVjdCI9PXR5cGVvZiB0JiYobj10LHQ9bi5sYW5ndWFnZSksdD1UW3RdfHx0O3ZhciByPXtpZDpBKyssY29kZTplLGxhbmc6dCxvcHRpb25zOm0obiksaXNOb2RlOlJ9O3JldHVybiByfWZ1bmN0aW9uIHAoZSx0KXtmb3IodmFyIHI9e2M6MH0sYT0wLG89ZTthPG8ubGVuZ3RoO2ErPTEpe3ZhciBpPW9bYV0scz1uKGkpO2lmKCFpLmNsYXNzTGlzdC5jb250YWlucygicmFpbmJvdyIpJiZzKXtpLmNsYXNzTGlzdC5hZGQoImxvYWRpbmciKSxpLmNsYXNzTGlzdC5hZGQoInJhaW5ib3ciKSwiUFJFIj09PWkucGFyZW50Tm9kZS50YWdOYW1lJiZpLnBhcmVudE5vZGUuY2xhc3NMaXN0LmFkZCgibG9hZGluZyIpO3ZhciB1PWkuZ2V0QXR0cmlidXRlKCJkYXRhLWdsb2JhbC1jbGFzcyIpLGM9cGFyc2VJbnQoaS5nZXRBdHRyaWJ1dGUoImRhdGEtZGVsYXkiKSwxMCk7KytyLmMsZCh2KGkuaW5uZXJIVE1MLHtsYW5ndWFnZTpzLGdsb2JhbENsYXNzOnUsZGVsYXk6Y30pLGcoaSxyLHQpKX19MD09PXIuYyYmdCgpfWZ1bmN0aW9uIGgoZSl7dmFyIHQ9ZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgiZGl2Iik7dC5jbGFzc05hbWU9InByZWxvYWRlciI7Zm9yKHZhciBuPTA7bjw3O24rKyl0LmFwcGVuZENoaWxkKGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoImRpdiIpKTtlLmFwcGVuZENoaWxkKHQpfWZ1bmN0aW9uIGIoZSx0KXt0PXR8fGZ1bmN0aW9uKCl7fSxlPWUmJiJmdW5jdGlvbiI9PXR5cGVvZiBlLmdldEVsZW1lbnRzQnlUYWdOYW1lP2U6ZG9jdW1lbnQ7Zm9yKHZhciBuPWUuZ2V0RWxlbWVudHNCeVRhZ05hbWUoInByZSIpLHI9ZS5nZXRFbGVtZW50c0J5VGFnTmFtZSgiY29kZSIpLGE9W10sbz1bXSxpPTAscz1uO2k8cy5sZW5ndGg7aSs9MSl7dmFyIHU9c1tpXTtoKHUpLHUuZ2V0RWxlbWVudHNCeVRhZ05hbWUoImNvZGUiKS5sZW5ndGg/dS5nZXRBdHRyaWJ1dGUoImRhdGEtdHJpbW1lZCIpfHwodS5zZXRBdHRyaWJ1dGUoImRhdGEtdHJpbW1lZCIsITApLHUuaW5uZXJIVE1MPXUuaW5uZXJIVE1MLnRyaW0oKSk6YS5wdXNoKHUpfWZvcih2YXIgYz0wLGw9cjtjPGwubGVuZ3RoO2MrPTEpe3ZhciBmPWxbY107by5wdXNoKGYpfXAoby5jb25jYXQoYSksdCl9ZnVuY3Rpb24gdyhlKXtNPWV9ZnVuY3Rpb24geShlLHQsbil7U1tlXXx8KFNbZV09biksQ1tlXT10LmNvbmNhdChDW2VdfHxbXSl9ZnVuY3Rpb24gTChlKXtkZWxldGUgU1tlXSxkZWxldGUgQ1tlXX1mdW5jdGlvbiBOKCl7Zm9yKHZhciBlPVtdLHQ9YXJndW1lbnRzLmxlbmd0aDt0LS07KWVbdF09YXJndW1lbnRzW3RdO2lmKCJzdHJpbmciPT10eXBlb2YgZVswXSl7dmFyIG49dihlWzBdLGVbMV0pO3JldHVybiB2b2lkIGQobixmdW5jdGlvbihlKXtyZXR1cm4gZnVuY3Rpb24odCl7ZSYmZSh0LnJlc3VsdCx0LmxhbmcpfX0oZVsyXSkpfXJldHVybiJmdW5jdGlvbiI9PXR5cGVvZiBlWzBdP3ZvaWQgYigwLGVbMF0pOnZvaWQgYihlWzBdLGVbMV0pfWZ1bmN0aW9uIEUoZSx0KXtUW2VdPXR9dmFyIE0sUHJpc209ZnVuY3Rpb24gUHJpc20oZSl7ZnVuY3Rpb24gdChlLHQpe2Zvcih2YXIgbiBpbiBoKWlmKG49cGFyc2VJbnQobiwxMCkscihuLGhbbl0sZSx0KSYmKGRlbGV0ZSBoW25dLGRlbGV0ZSBwW25dKSxpKG4saFtuXSxlLHQpKXJldHVybiEwO3JldHVybiExfWZ1bmN0aW9uIG4odCxuKXt2YXIgcj10LnJlcGxhY2UoL1wuL2csIiAiKSxhPWUuZ2xvYmFsQ2xhc3M7cmV0dXJuIGEmJihyKz0iICIrYSksJzxzcGFuIGNsYXNzPSInK3IrJyI+JytuKyI8L3NwYW4+In1mdW5jdGlvbiBjKGUpe2Zvcih2YXIgdD1zKHApLG49MCxyPXQ7bjxyLmxlbmd0aDtuKz0xKXt2YXIgYT1yW25dLG89cFthXTtlPXUoYSxvLnJlcGxhY2Usb1sid2l0aCJdLGUpfXJldHVybiBlfWZ1bmN0aW9uIGwoZSl7dmFyIHQ9IiI7cmV0dXJuIGUuaWdub3JlQ2FzZSYmKHQrPSJpIiksZS5tdWx0aWxpbmUmJih0Kz0ibSIpLG5ldyBSZWdFeHAoZS5zb3VyY2UsdCl9ZnVuY3Rpb24gZihyLGEsaSl7ZnVuY3Rpb24gYyhlKXtyZXR1cm4gci5uYW1lJiYoZT1uKHIubmFtZSxlKSkscFt3XT17cmVwbGFjZTptWzBdLCJ3aXRoIjplfSxoW3ddPXksIWcmJntyZW1haW5pbmc6YS5zdWJzdHIoeS1pKSxvZmZzZXQ6eX19ZnVuY3Rpb24gZih0KXt2YXIgYT1tW3RdO2lmKGEpe3ZhciBpPXIubWF0Y2hlc1t0XSxzPWkubGFuZ3VhZ2UsYz1pLm5hbWUmJmkubWF0Y2hlcz9pLm1hdGNoZXM6aSxsPWZ1bmN0aW9uKGUscixhKXtiPXUobyhtLHQpLGUsYT9uKGEscik6cixiKX07aWYoInN0cmluZyI9PXR5cGVvZiBpKXJldHVybiB2b2lkIGwoYSxhLGkpO3ZhciBmLGQ9bmV3IFByaXNtKGUpO2lmKHMpcmV0dXJuIGY9ZC5yZWZyYWN0KGEscyksdm9pZCBsKGEsZik7Zj1kLnJlZnJhY3QoYSx2LGMubGVuZ3RoP2M6W2NdKSxsKGEsZixpLm1hdGNoZXM/aS5uYW1lOjApfX12b2lkIDA9PT1pJiYoaT0wKTt2YXIgZD1yLnBhdHRlcm47aWYoIWQpcmV0dXJuITE7dmFyIGc9IWQuZ2xvYmFsO2Q9bChkKTt2YXIgbT1kLmV4ZWMoYSk7aWYoIW0pcmV0dXJuITE7IXIubmFtZSYmci5tYXRjaGVzJiYic3RyaW5nIj09dHlwZW9mIHIubWF0Y2hlc1swXSYmKHIubmFtZT1yLm1hdGNoZXNbMF0sZGVsZXRlIHIubWF0Y2hlc1swXSk7dmFyIGI9bVswXSx3PW0uaW5kZXgraSx5PW1bMF0ubGVuZ3RoK3c7aWYodz09PXkpcmV0dXJuITE7aWYodCh3LHkpKXJldHVybntyZW1haW5pbmc6YS5zdWJzdHIoeS1pKSxvZmZzZXQ6eX07Zm9yKHZhciBMPXMoci5tYXRjaGVzKSxOPTAsRT1MO048RS5sZW5ndGg7Tis9MSl7dmFyIE09RVtOXTtmKE0pfXJldHVybiBjKGIpfWZ1bmN0aW9uIGQoZSx0KXtmb3IodmFyIG49MCxyPXQ7bjxyLmxlbmd0aDtuKz0xKWZvcih2YXIgYT1yW25dLG89ZihhLGUpO287KW89ZihhLG8ucmVtYWluaW5nLG8ub2Zmc2V0KTtyZXR1cm4gYyhlKX1mdW5jdGlvbiBnKHQpe2Zvcih2YXIgbj1lLnBhdHRlcm5zW3RdfHxbXTtlLmluaGVyaXRlbmNlTWFwW3RdOyl0PWUuaW5oZXJpdGVuY2VNYXBbdF0sbj1uLmNvbmNhdChlLnBhdHRlcm5zW3RdfHxbXSk7cmV0dXJuIG59ZnVuY3Rpb24gbShlLHQsbil7cmV0dXJuIHY9dCxuPW58fGcodCksZChhKGUpLG4pfXZhciB2LHA9e30saD17fTt0aGlzLnJlZnJhY3Q9bX0sQz17fSxTPXt9LFQ9e30seD17fSxBPTAsUj1lKCksaz10KCksaj1udWxsO3g9e2V4dGVuZDp5LHJlbW92ZTpMLG9uSGlnaGxpZ2h0OncsYWRkQWxpYXM6RSxjb2xvcjpOfSxSJiYoeC5jb2xvclN5bmM9ZnVuY3Rpb24oZSx0KXt2YXIgbj12KGUsdCkscj1uZXcgUHJpc20obi5vcHRpb25zKTtyZXR1cm4gci5yZWZyYWN0KG4uY29kZSxuLmxhbmcpfSksUnx8a3x8ZG9jdW1lbnQuYWRkRXZlbnRMaXN0ZW5lcigiRE9NQ29udGVudExvYWRlZCIsZnVuY3Rpb24oZSl7eC5kZWZlcnx8eC5jb2xvcihlKX0sITEpLGsmJihzZWxmLm9ubWVzc2FnZT1sKTt2YXIgQj14O3JldHVybiBCfSk7CiAgICAgICAgUmFpbmJvdy5leHRlbmQoImNzcyIsW3tuYW1lOiJjb21tZW50IixwYXR0ZXJuOi9cL1wqW1xzXFNdKj9cKlwvL2dtfSx7bmFtZToiY29uc3RhbnQuaGV4LWNvbG9yIixwYXR0ZXJuOi8jKFthLWYwLTldezN9fFthLWYwLTldezZ9KSg/PTt8XHN8LHxcKSkvZ2l9LHttYXRjaGVzOnsxOiJjb25zdGFudC5udW1lcmljIiwyOiJrZXl3b3JkLnVuaXQifSxwYXR0ZXJuOi8oXGQrKShweHxlbXxjbXxzfCUpPy9nfSx7bmFtZToic3RyaW5nIixwYXR0ZXJuOi8oJ3wiKSguKj8pXDEvZ30se25hbWU6InN1cHBvcnQuY3NzLXByb3BlcnR5IixtYXRjaGVzOnsxOiJzdXBwb3J0LnZlbmRvci1wcmVmaXgifSxwYXR0ZXJuOi8oLW8tfC1tb3otfC13ZWJraXQtfC1tcy0pP1tcdy1dKyg/PVxzPzopKD8hLipceykvZ30se21hdGNoZXM6ezE6W3tuYW1lOiJlbnRpdHkubmFtZS5zYXNzIixwYXR0ZXJuOi8mYW1wOy9nfSx7bmFtZToiZGlyZWN0LWRlc2NlbmRhbnQiLHBhdHRlcm46LyZndDsvZ30se25hbWU6ImVudGl0eS5uYW1lLmNsYXNzIixwYXR0ZXJuOi9cLltcd1wtX10rL2d9LHtuYW1lOiJlbnRpdHkubmFtZS5pZCIscGF0dGVybjovXCNbXHdcLV9dKy9nfSx7bmFtZToiZW50aXR5Lm5hbWUucHNldWRvIixwYXR0ZXJuOi86W1x3XC1fXSsvZ30se25hbWU6ImVudGl0eS5uYW1lLnRhZyIscGF0dGVybjovXHcrL2d9XX0scGF0dGVybjovKFtcd1wgLFxuOlwuXCNcJlw7XC1fXSspKD89LipceykvZ30se21hdGNoZXM6ezI6InN1cHBvcnQudmVuZG9yLXByZWZpeCIsMzoic3VwcG9ydC5jc3MtdmFsdWUifSxwYXR0ZXJuOi8oOnwsKVxzKigtby18LW1vei18LXdlYmtpdC18LW1zLSk/KFthLXpBLVotXSopKD89XGIpKD8hLipceykvZ31dKSxSYWluYm93LmFkZEFsaWFzKCJzY3NzIiwiY3NzIiksUmFpbmJvdy5leHRlbmQoImdlbmVyaWMiLFt7bWF0Y2hlczp7MTpbe25hbWU6ImtleXdvcmQub3BlcmF0b3IiLHBhdHRlcm46L1w9fFwrL2d9LHtuYW1lOiJrZXl3b3JkLmRvdCIscGF0dGVybjovXC4vZ31dLDI6e25hbWU6InN0cmluZyIsbWF0Y2hlczp7bmFtZToiY29uc3RhbnQuY2hhcmFjdGVyLmVzY2FwZSIscGF0dGVybjovXFwoJ3wiKXsxfS9nfX19LHBhdHRlcm46LyhcKHxcc3xcW3xcPXw6fFwrfFwufFx7fCwpKCgnfCIpKFteXFxcMV18XFwuKSo/KFwzKSkvZ219LHtuYW1lOiJjb21tZW50IixwYXR0ZXJuOi9cL1wqW1xzXFNdKj9cKlwvfChcL1wvfFwjKSg/IS4qKCd8IikuKj9bXjpdKFwvXC98XCMpKS4qPyQvZ219LHtuYW1lOiJjb25zdGFudC5udW1lcmljIixwYXR0ZXJuOi9cYihcZCsoXC5cZCspPyhlKFwrfFwtKT9cZCspPyhmfGQpP3wweFtcZGEtZl0rKVxiL2dpfSx7bWF0Y2hlczp7MToia2V5d29yZCJ9LHBhdHRlcm46L1xiKGFuZHxhcnJheXxhc3xiKG9vbChlYW4pP3xyZWFrKXxjKGFzZXxhdGNofGhhcnxsYXNzfG9uKHN0fHRpbnVlKSl8ZChlZnxlbGV0ZXxvKHVibGUpPyl8ZShjaG98bHNlKGlmKT98eGl0fHh0ZW5kc3x4Y2VwdCl8ZihpbmFsbHl8bG9hdHxvcihlYWNoKT98dW5jdGlvbil8Z2xvYmFsfGlmfGltcG9ydHxpbnQoZWdlcik/fGxvbmd8bmV3fG9iamVjdHxvcnxwcihpbnR8aXZhdGV8b3RlY3RlZCl8cHVibGljfHJldHVybnxzZWxmfHN0KHJpbmd8cnVjdHxhdGljKXxzd2l0Y2h8dGgoZW58aXN8cm93KXx0cnl8KHVuKT9zaWduZWR8dmFyfHZvaWR8d2hpbGUpKD89XGIpL2dpfSx7bmFtZToiY29uc3RhbnQubGFuZ3VhZ2UiLHBhdHRlcm46L3RydWV8ZmFsc2V8bnVsbC9nfSx7bmFtZToia2V5d29yZC5vcGVyYXRvciIscGF0dGVybjovXCt8XCF8XC18JihndHxsdHxhbXApO3xcfHxcKnxcPS9nfSx7bWF0Y2hlczp7MToiZnVuY3Rpb24uY2FsbCJ9LHBhdHRlcm46Lyhcdys/KSg/PVwoKS9nfSx7bWF0Y2hlczp7MToic3RvcmFnZS5mdW5jdGlvbiIsMjoiZW50aXR5Lm5hbWUuZnVuY3Rpb24ifSxwYXR0ZXJuOi8oZnVuY3Rpb24pXHMoLio/KSg/PVwoKS9nfV0pLFJhaW5ib3cuZXh0ZW5kKCJodG1sIixbe25hbWU6InNvdXJjZS5waHAuZW1iZWRkZWQiLG1hdGNoZXM6ezE6InZhcmlhYmxlLmxhbmd1YWdlLnBocC10YWciLDI6e2xhbmd1YWdlOiJwaHAifSwzOiJ2YXJpYWJsZS5sYW5ndWFnZS5waHAtdGFnIn0scGF0dGVybjovKCZsdDtcP3BocHwmbHQ7XD89Pyg/IXhtbCkpKFtcc1xTXSo/KShcPyZndDspL2dtfSx7bmFtZToic291cmNlLmNzcy5lbWJlZGRlZCIsbWF0Y2hlczp7MTp7bWF0Y2hlczp7MToic3VwcG9ydC50YWcuc3R5bGUiLDI6W3tuYW1lOiJlbnRpdHkudGFnLnN0eWxlIixwYXR0ZXJuOi9ec3R5bGUvZ30se25hbWU6InN0cmluZyIscGF0dGVybjovKCd8IikoLio/KShcMSkvZ30se25hbWU6ImVudGl0eS50YWcuc3R5bGUuYXR0cmlidXRlIixwYXR0ZXJuOi8oXHcrKS9nfV0sMzoic3VwcG9ydC50YWcuc3R5bGUifSxwYXR0ZXJuOi8oJmx0O1wvPykoc3R5bGUuKj8pKCZndDspL2d9LDI6e2xhbmd1YWdlOiJjc3MifSwzOiJzdXBwb3J0LnRhZy5zdHlsZSIsNDoiZW50aXR5LnRhZy5zdHlsZSIsNToic3VwcG9ydC50YWcuc3R5bGUifSxwYXR0ZXJuOi8oJmx0O3N0eWxlLio/Jmd0OykoW1xzXFNdKj8pKCZsdDtcLykoc3R5bGUpKCZndDspL2dtfSx7bmFtZToic291cmNlLmpzLmVtYmVkZGVkIixtYXRjaGVzOnsxOnttYXRjaGVzOnsxOiJzdXBwb3J0LnRhZy5zY3JpcHQiLDI6W3tuYW1lOiJlbnRpdHkudGFnLnNjcmlwdCIscGF0dGVybjovXnNjcmlwdC9nfSx7bmFtZToic3RyaW5nIixwYXR0ZXJuOi8oJ3wiKSguKj8pKFwxKS9nfSx7bmFtZToiZW50aXR5LnRhZy5zY3JpcHQuYXR0cmlidXRlIixwYXR0ZXJuOi8oXHcrKS9nfV0sMzoic3VwcG9ydC50YWcuc2NyaXB0In0scGF0dGVybjovKCZsdDtcLz8pKHNjcmlwdC4qPykoJmd0OykvZ30sMjp7bGFuZ3VhZ2U6ImphdmFzY3JpcHQifSwzOiJzdXBwb3J0LnRhZy5zY3JpcHQiLDQ6ImVudGl0eS50YWcuc2NyaXB0Iiw1OiJzdXBwb3J0LnRhZy5zY3JpcHQifSxwYXR0ZXJuOi8oJmx0O3NjcmlwdCg/ISBzcmMpLio/Jmd0OykoW1xzXFNdKj8pKCZsdDtcLykoc2NyaXB0KSgmZ3Q7KS9nbX0se25hbWU6ImNvbW1lbnQuaHRtbCIscGF0dGVybjovJmx0O1whLS1bXFNcc10qPy0tJmd0Oy9nfSx7bWF0Y2hlczp7MToic3VwcG9ydC50YWcub3BlbiIsMjoic3VwcG9ydC50YWcuY2xvc2UifSxwYXR0ZXJuOi8oJmx0Oyl8KFwvP1w/PyZndDspL2d9LHtuYW1lOiJzdXBwb3J0LnRhZyIsbWF0Y2hlczp7MToic3VwcG9ydC50YWciLDI6InN1cHBvcnQudGFnLnNwZWNpYWwiLDM6InN1cHBvcnQudGFnLW5hbWUifSxwYXR0ZXJuOi8oJmx0O1w/PykoXC98XCE/KShcdyspL2d9LHttYXRjaGVzOnsxOiJzdXBwb3J0LmF0dHJpYnV0ZSJ9LHBhdHRlcm46LyhbYS16LV0rKSg/PVw9KS9naX0se21hdGNoZXM6ezE6InN1cHBvcnQub3BlcmF0b3IiLDI6InN0cmluZy5xdW90ZSIsMzoic3RyaW5nLnZhbHVlIiw0OiJzdHJpbmcucXVvdGUifSxwYXR0ZXJuOi8oPSkoJ3wiKSguKj8pKFwyKS9nfSx7bWF0Y2hlczp7MToic3VwcG9ydC5vcGVyYXRvciIsMjoic3VwcG9ydC52YWx1ZSJ9LHBhdHRlcm46Lyg9KShbYS16QS1aXC0wLTldKilcYi9nfSx7bWF0Y2hlczp7MToic3VwcG9ydC5hdHRyaWJ1dGUifSxwYXR0ZXJuOi9ccyhbXHctXSspKD89XHN8Jmd0OykoPyFbXHNcU10qJmx0OykvZ31dKSxSYWluYm93LmFkZEFsaWFzKCJ4bWwiLCJodG1sIiksUmFpbmJvdy5leHRlbmQoImpzb24iLFt7bWF0Y2hlczp7MDp7bmFtZToic3RyaW5nIixtYXRjaGVzOntuYW1lOiJjb25zdGFudC5jaGFyYWN0ZXIuZXNjYXBlIixwYXR0ZXJuOi9cXCgnfCIpezF9L2d9fX0scGF0dGVybjovKFwifFwnKShcXD8uKSo/XDEvZ30se25hbWU6ImNvbnN0YW50Lm51bWVyaWMiLHBhdHRlcm46L1xiKC0/KDB4KT9cZCpcLj9bXGRhLWZdK3xOYU58LT9JbmZpbml0eSlcYi9naX0se25hbWU6ImNvbnN0YW50Lmxhbmd1YWdlIixwYXR0ZXJuOi9cYih0cnVlfGZhbHNlfG51bGwpXGIvZ31dKSxSYWluYm93LmV4dGVuZCgicGhwIixbe25hbWU6InN1cHBvcnQiLHBhdHRlcm46L1xiZWNob1xiL2dpfSx7bWF0Y2hlczp7MToidmFyaWFibGUuZG9sbGFyLXNpZ24iLDI6InZhcmlhYmxlIn0scGF0dGVybjovKFwkKShcdyspXGIvZ30se25hbWU6ImNvbnN0YW50Lmxhbmd1YWdlIixwYXR0ZXJuOi90cnVlfGZhbHNlfG51bGwvZ2l9LHtuYW1lOiJjb25zdGFudCIscGF0dGVybjovXGJbQS1aMC05X117Mix9XGIvZ30se25hbWU6ImtleXdvcmQuZG90IixwYXR0ZXJuOi9cLi9nfSx7bmFtZToia2V5d29yZCIscGF0dGVybjovXGIoZGllfGVuZChmb3IoZWFjaCk/fHN3aXRjaHxpZil8Y2FzZXxyZXF1aXJlKF9vbmNlKT98aW5jbHVkZShfb25jZSk/KSg/PVxiKS9naX0se21hdGNoZXM6ezE6ImtleXdvcmQiLDI6e25hbWU6InN1cHBvcnQuY2xhc3MiLHBhdHRlcm46L1x3Ky9nfX0scGF0dGVybjovKGluc3RhbmNlb2YpXHMoW15cJF0uKj8pKFwpfDspL2dpfSx7bWF0Y2hlczp7MToic3VwcG9ydC5mdW5jdGlvbiJ9LHBhdHRlcm46L1xiKGFycmF5KF9rZXlfZXhpc3RzfF9tZXJnZXxfa2V5c3xfc2hpZnQpP3xpc3NldHxjb3VudHxlbXB0eXx1bnNldHxwcmludGZ8aXNfKGFycmF5fHN0cmluZ3xudW1lcmljfG9iamVjdCl8c3ByaW50ZnxlYWNofGRhdGV8dGltZXxzdWJzdHJ8cG9zfHN0cihsZW58cG9zfHRvbG93ZXJ8X3JlcGxhY2V8dG90aW1lKT98b3JkfHRyaW18aW5fYXJyYXl8aW1wbG9kZXxlbmR8cHJlZ19tYXRjaHxleHBsb2RlfGZtb2R8ZGVmaW5lfGxpbmt8bGlzdHxnZXRfY2xhc3N8c2VyaWFsaXplfGZpbGV8c29ydHxtYWlsfGRpcnxpZGF0ZXxsb2d8aW50dmFsfGhlYWRlcnxjaHJ8ZnVuY3Rpb25fZXhpc3RzfGRpcm5hbWV8cHJlZ19yZXBsYWNlfGZpbGVfZXhpc3RzKSg/PVwoKS9naX0se25hbWU6InZhcmlhYmxlLmxhbmd1YWdlLnBocC10YWciLHBhdHRlcm46LygmbHQ7XD8ocGhwKT98XD8mZ3Q7KS9naX0se21hdGNoZXM6ezE6ImtleXdvcmQubmFtZXNwYWNlIiwyOntuYW1lOiJzdXBwb3J0Lm5hbWVzcGFjZSIscGF0dGVybjovXHcrL2d9fSxwYXR0ZXJuOi9cYihuYW1lc3BhY2V8dXNlKVxzKC4qPyk7L2dpfSx7bWF0Y2hlczp7MToic3RvcmFnZS5tb2RpZmllciIsMjoic3RvcmFnZS5jbGFzcyIsMzoiZW50aXR5Lm5hbWUuY2xhc3MiLDQ6InN0b3JhZ2UubW9kaWZpZXIuZXh0ZW5kcyIsNToiZW50aXR5Lm90aGVyLmluaGVyaXRlZC1jbGFzcyIsNjoic3RvcmFnZS5tb2RpZmllci5leHRlbmRzIiw3OiJlbnRpdHkub3RoZXIuaW5oZXJpdGVkLWNsYXNzIn0scGF0dGVybjovXGIoYWJzdHJhY3R8ZmluYWwpP1xzPyhjbGFzc3xpbnRlcmZhY2V8dHJhaXQpXHMoXHcrKShcc2V4dGVuZHNccyk/KFtcd1xcXSopPyhcc2ltcGxlbWVudHNccyk/KFtcd1xcXSopP1xzP1x7PyhcbnxcfSkvZ2l9LHtuYW1lOiJrZXl3b3JkLnN0YXRpYyIscGF0dGVybjovc2VsZjo6fHN0YXRpYzo6L2dpfSx7bWF0Y2hlczp7MToic3RvcmFnZS5mdW5jdGlvbiIsMjoiZW50aXR5Lm5hbWUuZnVuY3Rpb24ubWFnaWMifSxwYXR0ZXJuOi8oZnVuY3Rpb24pXHMoX18uKj8pKD89XCgpL2dpfSx7bWF0Y2hlczp7MToic3RvcmFnZS5mdW5jdGlvbiIsMjoiZW50aXR5Lm5hbWUuZnVuY3Rpb24ifSxwYXR0ZXJuOi8oZnVuY3Rpb24pXHMoLio/KSg/PVwoKS9naX0se21hdGNoZXM6ezE6ImtleXdvcmQubmV3IiwyOntuYW1lOiJzdXBwb3J0LmNsYXNzIixwYXR0ZXJuOi9cdysvZ319LHBhdHRlcm46L1xiKG5ldylccyhbXlwkXVthLXowLTlfXFxdKj8pKD89XCl8XCh8OykvZ2l9LHttYXRjaGVzOnsxOntuYW1lOiJzdXBwb3J0LmNsYXNzIixwYXR0ZXJuOi9cdysvZ30sMjoia2V5d29yZC5zdGF0aWMifSxwYXR0ZXJuOi8oW1x3XFxdKj8pKDo6KSg/PVxifFwkKS9nfSx7bWF0Y2hlczp7Mjp7bmFtZToic3VwcG9ydC5jbGFzcyIscGF0dGVybjovXHcrL2d9fSxwYXR0ZXJuOi8oXCh8LFxzPykoW1x3XFxdKj8pKD89XHNcJCkvZ31dLCJnZW5lcmljIiksUmFpbmJvdy5leHRlbmQoInNxbCIsW3ttYXRjaGVzOnsyOntuYW1lOiJzdHJpbmciLG1hdGNoZXM6e25hbWU6ImNvbnN0YW50LmNoYXJhY3Rlci5lc2NhcGUiLHBhdHRlcm46L1xcKCd8InxgKXsxfS9nfX19LHBhdHRlcm46LyhcKHxcc3xcW3xcPXw6fFwrfFwufFx7fCwpKCgnfCJ8YCkoW15cXFwxXXxcXC4pKj8oXDMpKS9nbX0se25hbWU6ImNvbW1lbnQiLHBhdHRlcm46Ly0tLiokfFwvXCpbXHNcU10qP1wqXC98KFwvXC8pW1xzXFNdKj8kL2dtfSx7bmFtZToiY29uc3RhbnQubnVtZXJpYyIscGF0dGVybjovXGIoXGQrKFwuXGQrKT8oZShcK3xcLSk/XGQrKT8oZnxkKT98MHhbXGRhLWZdKylcYi9naX0se25hbWU6ImZ1bmN0aW9uLmNhbGwiLHBhdHRlcm46Lyhcdys/KSg/PVwoKS9nfSx7bmFtZToia2V5d29yZCIscGF0dGVybjovXGIoQUJTT0xVVEV8QUNUSU9OfEFEQXxBRER8QUxMfEFMTE9DQVRFfEFMVEVSfEFORHxBTll8QVJFfEFTfEFTQ3xBU1NFUlRJT058QVR8QVVUSE9SSVpBVElPTnxBVkd8QkVHSU58QkVUV0VFTnxCSVR8QklUX0xFTkdUSHxCT1RIfEJZfENBU0NBREV8Q0FTQ0FERUR8Q0FTRXxDQVNUfENBVEFMT0d8Q0hBUnxDSEFSQUNURVJ8Q0hBUkFDVEVSX0xFTkdUSHxDSEFSX0xFTkdUSHxDSEVDS3xDTE9TRXxDT0FMRVNDRXxDT0xMQVRFfENPTExBVElPTnxDT0xVTU58Q09NTUlUfENPTk5FQ1R8Q09OTkVDVElPTnxDT05TVFJBSU5UfENPTlNUUkFJTlRTfENPTlRJTlVFfENPTlZFUlR8Q09SUkVTUE9ORElOR3xDT1VOVHxDUkVBVEV8Q1JPU1N8Q1VSUkVOVHxDVVJSRU5UX0RBVEV8Q1VSUkVOVF9USU1FfENVUlJFTlRfVElNRVNUQU1QfENVUlJFTlRfVVNFUnxDVVJTT1J8REFURXxEQVl8REVBTExPQ0FURXxERUN8REVDSU1BTHxERUNMQVJFfERFRkFVTFR8REVGRVJSQUJMRXxERUZFUlJFRHxERUxFVEV8REVTQ3xERVNDUklCRXxERVNDUklQVE9SfERJQUdOT1NUSUNTfERJU0NPTk5FQ1R8RElTVElOQ1R8RE9NQUlOfERPVUJMRXxEUk9QfEVMU0V8RU5EfEVORC1FWEVDfEVTQ0FQRXxFWENFUFR8RVhDRVBUSU9OfEVYRUN8RVhFQ1VURXxFWElTVFN8RVhURVJOQUx8RVhUUkFDVHxGQUxTRXxGRVRDSHxGSVJTVHxGTE9BVHxGT1J8Rk9SRUlHTnxGT1JUUkFOfEZPVU5EfEZST018RlVMTHxHRVR8R0xPQkFMfEdPfEdPVE98R1JBTlR8R1JPVVB8SEFWSU5HfEhPVVJ8SURFTlRJVFl8SU1NRURJQVRFfElOfElOQ0xVREV8SU5ERVh8SU5ESUNBVE9SfElOSVRJQUxMWXxJTk5FUnxJTlBVVHxJTlNFTlNJVElWRXxJTlNFUlR8SU5UfElOVEVHRVJ8SU5URVJTRUNUfElOVEVSVkFMfElOVE98SVN8SVNPTEFUSU9OfEpPSU58S0VZfExBTkdVQUdFfExBU1R8TEVBRElOR3xMRUZUfExFVkVMfExJS0V8TElNSVR8TE9DQUx8TE9XRVJ8TUFUQ0h8TUFYfE1JTnxNSU5VVEV8TU9EVUxFfE1PTlRIfE5BTUVTfE5BVElPTkFMfE5BVFVSQUx8TkNIQVJ8TkVYVHxOT3xOT05FfE5PVHxOVUxMfE5VTExJRnxOVU1FUklDfE9DVEVUX0xFTkdUSHxPRnxPTnxPTkxZfE9QRU58T1BUSU9OfE9SfE9SREVSfE9VVEVSfE9VVFBVVHxPVkVSTEFQU3xQQUR8UEFSVElBTHxQQVNDQUx8UE9TSVRJT058UFJFQ0lTSU9OfFBSRVBBUkV8UFJFU0VSVkV8UFJJTUFSWXxQUklPUnxQUklWSUxFR0VTfFBST0NFRFVSRXxQVUJMSUN8UkVBRHxSRUFMfFJFRkVSRU5DRVN8UkVMQVRJVkV8UkVTVFJJQ1R8UkVWT0tFfFJJR0hUfFJPTExCQUNLfFJPV1N8U0NIRU1BfFNDUk9MTHxTRUNPTkR8U0VDVElPTnxTRUxFQ1R8U0VTU0lPTnxTRVNTSU9OX1VTRVJ8U0VUfFNJWkV8U01BTExJTlR8U09NRXxTUEFDRXxTUUx8U1FMQ0F8U1FMQ09ERXxTUUxFUlJPUnxTUUxTVEFURXxTUUxXQVJOSU5HfFNVQlNUUklOR3xTVU18U1lTVEVNX1VTRVJ8VEFCTEV8VEVNUE9SQVJZfFRIRU58VElNRXxUSU1FU1RBTVB8VElNRVpPTkVfSE9VUnxUSU1FWk9ORV9NSU5VVEV8VE98VFJBSUxJTkd8VFJBTlNBQ1RJT058VFJBTlNMQVRFfFRSQU5TTEFUSU9OfFRSSU18VFJVRXxVTklPTnxVTklRVUV8VU5LTk9XTnxVUERBVEV8VVBQRVJ8VVNBR0V8VVNFUnxVU0lOR3xWQUxVRXxWQUxVRVN8VkFSQ0hBUnxWQVJZSU5HfFZJRVd8V0hFTnxXSEVORVZFUnxXSEVSRXxXSVRIfFdPUkt8V1JJVEV8WUVBUnxaT05FfFVTRSkoPz1cYikvZ2l9LHtuYW1lOiJrZXl3b3JkLm9wZXJhdG9yIixwYXR0ZXJuOi9cK3xcIXxcLXwmKGd0fGx0fGFtcCk7fFx8fFwqfD0vZ31dKTsKICAgIDwvc2NyaXB0Pgo8L2hlYWQ+Cgo8Ym9keT4KCiAgICA8ZGl2IGlkPSJkZWJ1Zy1iYXIiPgogICAgICAgIDxkaXYgaWQ9InRvcC1yZXNpemVyIj48L2Rpdj4KICAgICAgICA8IS0tIFJlc2l6ZXIgZm9yIHRvcCAtLT4KICAgICAgICA8ZGl2IGlkPSJkZWJ1Zy1iYXItaGVhZGVyIj4KICAgICAgICAgICAgPGRpdiBpZD0iZGVidWctYmFyLXRpdGxlIj7wn5CeIEVycm9yIERlYnVnIEJhcjwvZGl2PgogICAgICAgICAgICA8ZGl2IGlkPSJkZWJ1Zy1iYXItdGl0bGUiPgogICAgICAgICAgICAgICAgPGEgaHJlZj0iaHR0cHM6Ly9naXRodWIuY29tL3Nha2lid2ViIiB0YXJnZXQ9Il9ibGFuayI+UEhERTwvYT4KICAgICAgICAgICAgPC9kaXY+CiAgICAgICAgICAgIDxkaXYgaWQ9ImRlYnVnLWJhci1jb250cm9scyI+CiAgICAgICAgICAgICAgICA8YnV0dG9uIGlkPSJtaW5pbWl6ZS1idXR0b24iPuKAlDwvYnV0dG9uPgogICAgICAgICAgICAgICAgPGJ1dHRvbiBpZD0ibWF4aW1pemUtYnV0dG9uIj7imJA8L2J1dHRvbj4KICAgICAgICAgICAgICAgIDxidXR0b24gaWQ9ImNsb3NlLWJ1dHRvbiI+4pyWPC9idXR0b24+CiAgICAgICAgICAgIDwvZGl2PgogICAgICAgIDwvZGl2PgogICAgICAgIDxkaXYgaWQ9ImRlYnVnLWJhci1jb250ZW50Ij4KICAgICAgICAgICAgPGRpdiBpZD0iZXJyb3ItbGlzdCIgY2xhc3M9ImRlYnVnLXNlY3Rpb24iPgogICAgICAgICAgICAgICAgPGgzPkVycm9yIExpc3Q8L2gzPgogICAgICAgICAgICAgICAgPHVsIGlkPSJlcnJvci1saXN0LWNvbnRlbnQiPgogICAgICAgICAgICAgICAgICAgIDwhLS0gRXJyb3IgbGlzdCBpdGVtcyB3aWxsIGJlIGluamVjdGVkIGhlcmUgYnkgSmF2YVNjcmlwdCAtLT4KICAgICAgICAgICAgICAgIDwvdWw+CiAgICAgICAgICAgIDwvZGl2PgogICAgICAgICAgICA8ZGl2IGNsYXNzPSJyZXNpemVyIiBpZD0icmVzaXplcjEiPjwvZGl2PgogICAgICAgICAgICA8ZGl2IGlkPSJlcnJvci1zb3VyY2UiIGNsYXNzPSJkZWJ1Zy1zZWN0aW9uIj4KICAgICAgICAgICAgICAgIDxoMz5Tb3VyY2U8L2gzPgogICAgICAgICAgICAgICAgPHByZSBjbGFzcz0ibGFuZ3VhZ2UtcGhwIHJhaW5ib3ctc2hvdyIgZGF0YS10cmltbWVkPSJ0cnVlIj48Y29kZSBpZD0iZXJyb3Itc291cmNlLWNvbnRlbnQiIGNsYXNzPSJsYW5ndWFnZS1waHAgcGhwIiBkYXRhLWxhbmd1YWdlPSJwaHAiPjwvY29kZT48L3ByZT4KICAgICAgICAgICAgPC9kaXY+CiAgICAgICAgICAgIDxkaXYgY2xhc3M9InJlc2l6ZXIiIGlkPSJyZXNpemVyMiI+PC9kaXY+CiAgICAgICAgICAgIDxkaXYgaWQ9ImVycm9yLXNvbHV0aW9uIiBjbGFzcz0iZGVidWctc2VjdGlvbiI+CiAgICAgICAgICAgICAgICA8aDM+U29sdXRpb248L2gzPgogICAgICAgICAgICAgICAgPGRpdiBpZD0iZXJyb3Itc29sdXRpb24tY29udGVudCI+CiAgICAgICAgICAgICAgICAgICAgPCEtLSBFcnJvciBzb2x1dGlvbiB3aWxsIGJlIHByb3ZpZGVkIGhlcmUgYnkgSmF2YVNjcmlwdCAtLT4KICAgICAgICAgICAgICAgIDwvZGl2PgogICAgICAgICAgICA8L2Rpdj4KICAgICAgICA8L2Rpdj4KICAgIDwvZGl2PgoKICAgIDxkaXYgaWQ9ImRlYnVnLWJ1dHRvbiI+PC9kaXY+CgogICAgPHNjcmlwdD4KICAgICAgICBkb2N1bWVudC5hZGRFdmVudExpc3RlbmVyKCdET01Db250ZW50TG9hZGVkJywgZnVuY3Rpb24gKCkgewogICAgICAgICAgICBjb25zdCBkZWJ1Z0JhciA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdkZWJ1Zy1iYXInKTsKICAgICAgICAgICAgY29uc3QgdG9wUmVzaXplciA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCd0b3AtcmVzaXplcicpOwogICAgICAgICAgICBjb25zdCBtaW5pbWl6ZUJ1dHRvbiA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdtaW5pbWl6ZS1idXR0b24nKTsKICAgICAgICAgICAgY29uc3QgbWF4aW1pemVCdXR0b24gPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnbWF4aW1pemUtYnV0dG9uJyk7CiAgICAgICAgICAgIGNvbnN0IGNsb3NlQnV0dG9uID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ2Nsb3NlLWJ1dHRvbicpOwogICAgICAgICAgICBjb25zdCBkZWJ1Z0J1dHRvbiA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdkZWJ1Zy1idXR0b24nKTsKICAgICAgICAgICAgY29uc3QgZXJyb3JMaXN0Q29udGVudCA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdlcnJvci1saXN0LWNvbnRlbnQnKTsKICAgICAgICAgICAgY29uc3QgZXJyb3JTb3VyY2VDb250ZW50ID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ2Vycm9yLXNvdXJjZS1jb250ZW50Jyk7CiAgICAgICAgICAgIGNvbnN0IGVycm9yU29sdXRpb25Db250ZW50ID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ2Vycm9yLXNvbHV0aW9uLWNvbnRlbnQnKTsKICAgICAgICAgICAgY29uc3QgcmVzaXplcjEgPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgncmVzaXplcjEnKTsKICAgICAgICAgICAgY29uc3QgcmVzaXplcjIgPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgncmVzaXplcjInKTsKCiAgICAgICAgICAgIGxldCBpc01pbmltaXplZCA9IGZhbHNlOwogICAgICAgICAgICBsZXQgaXNGdWxsU2NyZWVuID0gZmFsc2U7CiAgICAgICAgICAgIGxldCBwcmV2aW91c0hlaWdodCA9IGRlYnVnQmFyLnN0eWxlLmhlaWdodDsKCiAgICAgICAgICAgIC8vIFRvZ2dsZSBtaW5pbWl6ZQogICAgICAgICAgICBtaW5pbWl6ZUJ1dHRvbi5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsIGZ1bmN0aW9uICgpIHsKICAgICAgICAgICAgICAgIGlzTWluaW1pemVkID0gIWlzTWluaW1pemVkOwogICAgICAgICAgICAgICAgaWYgKGlzTWluaW1pemVkKSB7CiAgICAgICAgICAgICAgICAgICAgZGVidWdCYXIuY2xhc3NMaXN0LmFkZCgnbWluaW1pemVkJyk7CiAgICAgICAgICAgICAgICAgICAgZGVidWdCYXIuc3R5bGUuaGVpZ2h0ID0gJzQwcHgnOwogICAgICAgICAgICAgICAgfSBlbHNlIHsKICAgICAgICAgICAgICAgICAgICBkZWJ1Z0Jhci5jbGFzc0xpc3QucmVtb3ZlKCdtaW5pbWl6ZWQnKTsKICAgICAgICAgICAgICAgICAgICBkZWJ1Z0Jhci5zdHlsZS5oZWlnaHQgPSBwcmV2aW91c0hlaWdodDsKICAgICAgICAgICAgICAgIH0KICAgICAgICAgICAgfSk7CgogICAgICAgICAgICAvLyBUb2dnbGUgbWF4aW1pemUKICAgICAgICAgICAgbWF4aW1pemVCdXR0b24uYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCBmdW5jdGlvbiAoKSB7CiAgICAgICAgICAgICAgICBpc0Z1bGxTY3JlZW4gPSAhaXNGdWxsU2NyZWVuOwogICAgICAgICAgICAgICAgaWYgKGlzRnVsbFNjcmVlbikgewogICAgICAgICAgICAgICAgICAgIHByZXZpb3VzSGVpZ2h0ID0gZGVidWdCYXIuc3R5bGUuaGVpZ2h0OwogICAgICAgICAgICAgICAgICAgIGRlYnVnQmFyLnN0eWxlLmhlaWdodCA9ICcxMDAlJzsKICAgICAgICAgICAgICAgICAgICBkZWJ1Z0Jhci5jbGFzc0xpc3QuYWRkKCdmdWxsc2NyZWVuJyk7CiAgICAgICAgICAgICAgICAgICAgbWF4aW1pemVCdXR0b24udGV4dENvbnRlbnQgPSAn4p2QJzsKICAgICAgICAgICAgICAgIH0gZWxzZSB7CiAgICAgICAgICAgICAgICAgICAgZGVidWdCYXIuc3R5bGUuaGVpZ2h0ID0gcHJldmlvdXNIZWlnaHQ7CiAgICAgICAgICAgICAgICAgICAgZGVidWdCYXIuc3R5bGUuaGVpZ2h0ID0gJzQwJSc7CiAgICAgICAgICAgICAgICAgICAgZGVidWdCYXIuY2xhc3NMaXN0LnJlbW92ZSgnZnVsbHNjcmVlbicpOwogICAgICAgICAgICAgICAgICAgIG1heGltaXplQnV0dG9uLnRleHRDb250ZW50ID0gJ+KYkCc7CiAgICAgICAgICAgICAgICB9CiAgICAgICAgICAgIH0pOwoKICAgICAgICAgICAgLy8gQ2xvc2UgZGVidWcgYmFyCiAgICAgICAgICAgIGNsb3NlQnV0dG9uLmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgZnVuY3Rpb24gKCkgewogICAgICAgICAgICAgICAgZGVidWdCYXIuc3R5bGUuZGlzcGxheSA9ICdub25lJzsKICAgICAgICAgICAgICAgIGRlYnVnQnV0dG9uLnN0eWxlLmRpc3BsYXkgPSAnYmxvY2snOwogICAgICAgICAgICB9KTsKCiAgICAgICAgICAgIC8vIFJlc3RvcmUgZGVidWcgYmFyIGZyb20gYnV0dG9uCiAgICAgICAgICAgIGRlYnVnQnV0dG9uLmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgZnVuY3Rpb24gKCkgewogICAgICAgICAgICAgICAgZGVidWdCYXIuc3R5bGUuZGlzcGxheSA9ICdibG9jayc7CiAgICAgICAgICAgICAgICBkZWJ1Z0J1dHRvbi5zdHlsZS5kaXNwbGF5ID0gJ25vbmUnOwogICAgICAgICAgICB9KTsKCiAgICAgICAgICAgIC8vIEV4YW1wbGUgb2YgcG9wdWxhdGluZyB0aGUgZGVidWcgYmFyIHdpdGggUEhQIGVycm9ycwoK");
                $html .= 'const errors = [';
                foreach ($errors as $error) {
                    $message = str_replace(["\n", "\r", '"'], ['', '', '\"'], $error['message']);
                    $source = self::getErrorSourceCode($error['file'], $error['line']);
                    $html .= '{ errno: "'.$error['errno'].'", errtype: "'.$error['type'].'", erricon: "'.$error['icon'].'", message: "'.$error['icon'].$message.'", file: "'.$error['file'].'", line: '.$error['line'].', source: "'.base64_encode($source).'", solution: "'.$error['solution'].'" },';
                }
                $html .= '];';
                $html .= base64_decode("CgogICAgICAgICAgICBlcnJvcnMuZm9yRWFjaChlcnJvciA9PiB7CiAgICAgICAgICAgICAgICBjb25zdCBsaSA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2xpJyk7CiAgICAgICAgICAgICAgICBsaS50ZXh0Q29udGVudCA9IGAke2Vycm9yLm1lc3NhZ2V9YDsgLy8gaW4gJHtlcnJvci5maWxlfSBvbiBsaW5lICR7ZXJyb3IubGluZX0KICAgICAgICAgICAgICAgIGxpLmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgZnVuY3Rpb24gKCkgewogICAgICAgICAgICAgICAgICAgIGVycm9yU291cmNlQ29udGVudC50ZXh0Q29udGVudCA9IGBTb3VyY2U6ICR7ZXJyb3IuZmlsZX0sIExpbmU6ICR7ZXJyb3IubGluZX1cblxuJHthdG9iKGVycm9yLnNvdXJjZSl9YDsKICAgICAgICAgICAgICAgICAgICBSYWluYm93LmNvbG9yKCk7CiAgICAgICAgICAgICAgICAgICAgZXJyb3JTb2x1dGlvbkNvbnRlbnQudGV4dENvbnRlbnQgPSBgU29sdXRpb246ICR7ZXJyb3Iuc29sdXRpb259YDsKICAgICAgICAgICAgICAgIH0pOwogICAgICAgICAgICAgICAgZXJyb3JMaXN0Q29udGVudC5hcHBlbmRDaGlsZChsaSk7CiAgICAgICAgICAgIH0pOwoKICAgICAgICAgICAgLy8gUmVzaXppbmcgbG9naWMKICAgICAgICAgICAgZnVuY3Rpb24gbWFrZVJlc2l6YWJsZUJhcihiYXIsIGRpcmVjdGlvbikgewogICAgICAgICAgICAgICAgbGV0IHN0YXJ0WCwgc3RhcnRZLCBzdGFydFdpZHRoLCBzdGFydEhlaWdodDsKICAgICAgICAgICAgICAgIGNvbnN0IE1JTl9IRUlHSFQgPSA0MDsKCiAgICAgICAgICAgICAgICBiYXIuYWRkRXZlbnRMaXN0ZW5lcignbW91c2Vkb3duJywgKGUpID0+IHsKICAgICAgICAgICAgICAgICAgICBzdGFydFggPSBlLmNsaWVudFg7CiAgICAgICAgICAgICAgICAgICAgc3RhcnRZID0gZS5jbGllbnRZOwogICAgICAgICAgICAgICAgICAgIHN0YXJ0V2lkdGggPSBwYXJzZUludChkb2N1bWVudC5kZWZhdWx0Vmlldy5nZXRDb21wdXRlZFN0eWxlKGRlYnVnQmFyKS53aWR0aCwgMTApOwogICAgICAgICAgICAgICAgICAgIHN0YXJ0SGVpZ2h0ID0gcGFyc2VJbnQoZG9jdW1lbnQuZGVmYXVsdFZpZXcuZ2V0Q29tcHV0ZWRTdHlsZShkZWJ1Z0JhcikuaGVpZ2h0LCAxMCk7CiAgICAgICAgICAgICAgICAgICAgZG9jdW1lbnQuZG9jdW1lbnRFbGVtZW50LmFkZEV2ZW50TGlzdGVuZXIoJ21vdXNlbW92ZScsIGRvRHJhZywgZmFsc2UpOwogICAgICAgICAgICAgICAgICAgIGRvY3VtZW50LmRvY3VtZW50RWxlbWVudC5hZGRFdmVudExpc3RlbmVyKCdtb3VzZXVwJywgc3RvcERyYWcsIGZhbHNlKTsKICAgICAgICAgICAgICAgIH0pOwoKICAgICAgICAgICAgICAgIGZ1bmN0aW9uIGRvRHJhZyhlKSB7CiAgICAgICAgICAgICAgICAgICAgaWYgKGRpcmVjdGlvbiA9PT0gJ2V3JykgewogICAgICAgICAgICAgICAgICAgICAgICBkZWJ1Z0Jhci5zdHlsZS53aWR0aCA9IChzdGFydFdpZHRoICsgZS5jbGllbnRYIC0gc3RhcnRYKSArICdweCc7CiAgICAgICAgICAgICAgICAgICAgfSBlbHNlIGlmIChkaXJlY3Rpb24gPT09ICducycpIHsKICAgICAgICAgICAgICAgICAgICAgICAgbGV0IG5ld0hlaWdodCA9IHN0YXJ0SGVpZ2h0IC0gZS5jbGllbnRZICsgc3RhcnRZOwogICAgICAgICAgICAgICAgICAgICAgICBpZiAobmV3SGVpZ2h0IDwgTUlOX0hFSUdIVCkgewogICAgICAgICAgICAgICAgICAgICAgICAgICAgbmV3SGVpZ2h0ID0gTUlOX0hFSUdIVDsKICAgICAgICAgICAgICAgICAgICAgICAgfQogICAgICAgICAgICAgICAgICAgICAgICBkZWJ1Z0Jhci5zdHlsZS5oZWlnaHQgPSBuZXdIZWlnaHQgKyAncHgnOwogICAgICAgICAgICAgICAgICAgIH0KICAgICAgICAgICAgICAgIH0KCiAgICAgICAgICAgICAgICBmdW5jdGlvbiBzdG9wRHJhZygpIHsKICAgICAgICAgICAgICAgICAgICBkb2N1bWVudC5kb2N1bWVudEVsZW1lbnQucmVtb3ZlRXZlbnRMaXN0ZW5lcignbW91c2Vtb3ZlJywgZG9EcmFnLCBmYWxzZSk7CiAgICAgICAgICAgICAgICAgICAgZG9jdW1lbnQuZG9jdW1lbnRFbGVtZW50LnJlbW92ZUV2ZW50TGlzdGVuZXIoJ21vdXNldXAnLCBzdG9wRHJhZywgZmFsc2UpOwogICAgICAgICAgICAgICAgfQogICAgICAgICAgICB9CiAgICAgICAgICAgIC8vIFJlc2l6YWJsZSBzZWN0aW9ucwogICAgICAgICAgICBmdW5jdGlvbiBtYWtlUmVzaXphYmxlKHJlc2l6ZXIsIHByZXZpb3VzRWxlbWVudCwgbmV4dEVsZW1lbnQpIHsKICAgICAgICAgICAgICAgIGxldCBzdGFydFgsIHN0YXJ0V2lkdGhQcmV2LCBzdGFydFdpZHRoTmV4dDsKCiAgICAgICAgICAgICAgICByZXNpemVyLmFkZEV2ZW50TGlzdGVuZXIoJ21vdXNlZG93bicsIGZ1bmN0aW9uKGUpIHsKICAgICAgICAgICAgICAgICAgICBzdGFydFggPSBlLmNsaWVudFg7CiAgICAgICAgICAgICAgICAgICAgc3RhcnRXaWR0aFByZXYgPSBwcmV2aW91c0VsZW1lbnQub2Zmc2V0V2lkdGg7CiAgICAgICAgICAgICAgICAgICAgc3RhcnRXaWR0aE5leHQgPSBuZXh0RWxlbWVudC5vZmZzZXRXaWR0aDsKCiAgICAgICAgICAgICAgICAgICAgZG9jdW1lbnQuYWRkRXZlbnRMaXN0ZW5lcignbW91c2Vtb3ZlJywgcmVzaXplKTsKICAgICAgICAgICAgICAgICAgICBkb2N1bWVudC5hZGRFdmVudExpc3RlbmVyKCdtb3VzZXVwJywgc3RvcFJlc2l6ZSk7CiAgICAgICAgICAgICAgICB9KTsKCiAgICAgICAgICAgICAgICBmdW5jdGlvbiByZXNpemUoZSkgewogICAgICAgICAgICAgICAgICAgIGNvbnN0IG9mZnNldCA9IGUuY2xpZW50WCAtIHN0YXJ0WDsKICAgICAgICAgICAgICAgICAgICBwcmV2aW91c0VsZW1lbnQuc3R5bGUuZmxleEJhc2lzID0gYCR7c3RhcnRXaWR0aFByZXYgKyBvZmZzZXR9cHhgOwogICAgICAgICAgICAgICAgICAgIG5leHRFbGVtZW50LnN0eWxlLmZsZXhCYXNpcyA9IGAke3N0YXJ0V2lkdGhOZXh0IC0gb2Zmc2V0fXB4YDsKICAgICAgICAgICAgICAgIH0KCiAgICAgICAgICAgICAgICBmdW5jdGlvbiBzdG9wUmVzaXplKCkgewogICAgICAgICAgICAgICAgICAgIGRvY3VtZW50LnJlbW92ZUV2ZW50TGlzdGVuZXIoJ21vdXNlbW92ZScsIHJlc2l6ZSk7CiAgICAgICAgICAgICAgICAgICAgZG9jdW1lbnQucmVtb3ZlRXZlbnRMaXN0ZW5lcignbW91c2V1cCcsIHN0b3BSZXNpemUpOwogICAgICAgICAgICAgICAgfQogICAgICAgICAgICB9CgogICAgICAgICAgICBtYWtlUmVzaXphYmxlKHJlc2l6ZXIxLCBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnZXJyb3ItbGlzdCcpLCBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnZXJyb3Itc291cmNlJykpOwogICAgICAgICAgICBtYWtlUmVzaXphYmxlKHJlc2l6ZXIyLCBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnZXJyb3Itc291cmNlJyksIGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdlcnJvci1zb2x1dGlvbicpKTsKICAgICAgICAgICAgbWFrZVJlc2l6YWJsZUJhcih0b3BSZXNpemVyLCAnbnMnKTsKICAgICAgICB9KTsKICAgIDwvc2NyaXB0Pgo8L2JvZHk+CjwvaHRtbD4=");
                echo $html;
            } elseif (!$state) {
                return $errors;
            }
        }
    }

    /**
     * Set HTTP response headers for API responses.
     *
     * @param string $method MIME type of the response content.
     */
    public static function api($method = 'application/json') {
        header('Content-Type: '.$method);
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");
    }

    /**
     * Initializes the API testing tool (APIBAR) with a specified URL path.
     *
     * This method sets up a route for an API testing interface at the given URL. 
     * The API testing tool (APIBAR) provides a web-based interface that allows users 
     * to test API requests by selecting HTTP methods, adding headers, body parameters, 
     * and viewing responses including status codes, headers, and content.
     *
     * @param string $url The URL path where the API testing tool will be accessible. 
     *                    Default is '/apibar'. This is the route where the API testing UI is displayed.
     *                    
     * Usage Example:
     * PHDE::apibar('/api-tester'); // Sets the API testing tool at '/api-tester'
     */
    public static function apibar($url = '/apibar') {
        PHRO::get($url, function() {
            PHRQ::api("text/html; charset=UTF-8");
            print <<<EOT
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta http-equiv="X-UA-Compatible" content="IE=edge">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <meta name="icon" content="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAASCAMAAABhEH5lAAAB3VBMVEVHcEydiSEAAAChNxsbCQSQfR9HGwi+RCELBALApykQEAepOh2xPR6wmCVVMw7cTiWGdB14KhUAAABkWBd5aRu+QiDVSSPPtCqUMxrGRSG5QB+GfEaJMBg+NQZ/KhOtn02okiTWUi2YNhvAQiC+QyLAmildURVfEADWh3G9fG1pXBjEqihNRBOELxikORyKbR29pCcuKQ2QPxrrWCjxZirx0ij63R/jUSZ/jKv94B44PWPpaiqircLoVCbuZChgao7vYSnuzymUnbjqei/Iw4OPl5/glCOWp8bzthitv9nsxCn11yXE0t+KmbyOm7OHjaNzf6Lk6vLatyayvNLxbzrByNN3gqakuNjlxyy1rZJbZo+5qDjobjrpyizfwiyanLGWiJWGfFm9jDd6a2rddR/dvx2UbVL340Oqm3y2eCbtzSBue53akx311xZ+h56bizvx1i6/p3fzpS7qqhq5vszshi5OV4GhmaW0oYW2sr7Fj331glGbrc3xbDLKaENpc5jGmIzSn5bbhmXQ3OmqUkKur76Hb32zblc/RWx6em5UWn3jyivpyyvUwU1teJu/dV3JwHXCw7jcdU3rzxuQkorb0Hnx1B6wq3RgZ4DErC5tcoDgtCrLZTmAd0vjXybI0qtxAAAAM3RSTlMAtQJvD50l1wjYFYKX2j7+jVcBb3zc+PpY8KzqRjA188Twh73N11Ea59tk90p1r6zjM6B1d64/AAABMUlEQVQY02MQUhJlBwNVTjBg5WBg4DE3BgFrL1MQiGUECnGbm5g4O1uXBnl7ZVmaxbIyMTAwA4Wsc9w8it0yUy3NIgQYGBgk+B1CqotyvT1SktItzeJEgEIyYiEN5RWFkRlpya4uBUFaQCE5xUY/e/8S/zwLK3t3F3E1oBCbRqBNgFNoqE22VYCnjzwfUEhdM96iJswn0LPMyt3XT1oWKMSi0tIaXt8c7BvVEe6az8sGFOJQNq5rCwu26Yvq6ox25OUCCjFJ2dY2VSY42U+Z2GtmqsACFGLgsY2pau93mhE9dUK3I8g/IB/Z2dlNip8VOX2yWQ/IPyAfOZiYxCRYWCROg/gH6CNJfltjhzmJc2c6xmkLg4U4+HT1RMXmzRY3YBTR4WKAAg42QSN9YUOwbQwA5ztHGpIVCvUAAAAASUVORK5CYII=">
                <meta name="description" content="PHKing Dev | PHP Framework">
                <meta name="author" content="sakibweb">
                <link rel="icon" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAASCAMAAABhEH5lAAAB3VBMVEVHcEydiSEAAAChNxsbCQSQfR9HGwi+RCELBALApykQEAepOh2xPR6wmCVVMw7cTiWGdB14KhUAAABkWBd5aRu+QiDVSSPPtCqUMxrGRSG5QB+GfEaJMBg+NQZ/KhOtn02okiTWUi2YNhvAQiC+QyLAmildURVfEADWh3G9fG1pXBjEqihNRBOELxikORyKbR29pCcuKQ2QPxrrWCjxZirx0ij63R/jUSZ/jKv94B44PWPpaiqircLoVCbuZChgao7vYSnuzymUnbjqei/Iw4OPl5/glCOWp8bzthitv9nsxCn11yXE0t+KmbyOm7OHjaNzf6Lk6vLatyayvNLxbzrByNN3gqakuNjlxyy1rZJbZo+5qDjobjrpyizfwiyanLGWiJWGfFm9jDd6a2rddR/dvx2UbVL340Oqm3y2eCbtzSBue53akx311xZ+h56bizvx1i6/p3fzpS7qqhq5vszshi5OV4GhmaW0oYW2sr7Fj331glGbrc3xbDLKaENpc5jGmIzSn5bbhmXQ3OmqUkKur76Hb32zblc/RWx6em5UWn3jyivpyyvUwU1teJu/dV3JwHXCw7jcdU3rzxuQkorb0Hnx1B6wq3RgZ4DErC5tcoDgtCrLZTmAd0vjXybI0qtxAAAAM3RSTlMAtQJvD50l1wjYFYKX2j7+jVcBb3zc+PpY8KzqRjA188Twh73N11Ea59tk90p1r6zjM6B1d64/AAABMUlEQVQY02MQUhJlBwNVTjBg5WBg4DE3BgFrL1MQiGUECnGbm5g4O1uXBnl7ZVmaxbIyMTAwA4Wsc9w8it0yUy3NIgQYGBgk+B1CqotyvT1SktItzeJEgEIyYiEN5RWFkRlpya4uBUFaQCE5xUY/e/8S/zwLK3t3F3E1oBCbRqBNgFNoqE22VYCnjzwfUEhdM96iJswn0LPMyt3XT1oWKMSi0tIaXt8c7BvVEe6az8sGFOJQNq5rCwu26Yvq6ox25OUCCjFJ2dY2VSY42U+Z2GtmqsACFGLgsY2pau93mhE9dUK3I8g/IB/Z2dlNip8VOX2yWQ/IPyAfOZiYxCRYWCROg/gH6CNJfltjhzmJc2c6xmkLg4U4+HT1RMXmzRY3YBTR4WKAAg42QSN9YUOwbQwA5ztHGpIVCvUAAAAASUVORK5CYII=">
                <meta name="color-scheme" content="light dark">
                <title>♾️API Bar</title>
                <style>
                    body {
                        margin: 0;
                        padding: 0;
                        font-family: Arial, sans-serif;
                    }

                    #api-bar {
                        position: fixed;
                        bottom: 0;
                        left: 0;
                        width: 100%;
                        max-height: 80%;
                        height: 400px;
                        background-color: #333;
                        color: white;
                        z-index: 1000;
                        border-top: 2px solid #444;
                        transition: height 0.3s ease;
                        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.5);
                        resize: vertical;
                        display: flex;
                        flex-direction: column;
                    }

                    #api-bar-header {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        padding: 5px;
                        background-color: #444;
                        cursor: pointer;
                    }

                    #api-bar-title {
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        flex-grow: 1;
                    }

                    #api-bar-title #left-title a {
                        color: #FFD700;
                        text-decoration: none;
                        font-weight: bold;
                        font-size: 18px;
                    }

                    .input-container {
                        display: flex;
                        gap: 5px;
                        flex-grow: 1;
                        justify-content: center;
                        position: relative;
                    }

                    .grouped-button {
                        display: flex;
                        position: relative;
                    }

                    .grouped-button input {
                        padding: 5px;
                        background-color: #333;
                        color: white;
                        border: 1px solid #444;
                        border-radius: 4px 0 0 4px;
                        flex: 1;
                    }

                    .grouped-button button {
                        padding: 5px;
                        background-color: #333333;
                        color: white;
                        border: 1px solid #444;
                        border-radius: 0 4px 4px 0;
                        cursor: pointer;
                    }

                    .dropdown {
                        position: absolute;
                        top: 100%;
                        width: auto;
                        background-color: #333;
                        border: 1px solid #444;
                        border-radius: 4px;
                        display: none;
                        max-height: 150px;
                        overflow-y: auto;
                        z-index: 10;
                    }

                    .dropdown option {
                        padding: 10px;
                        background-color: #333;
                        color: white;
                        border-bottom: 1px solid #444;
                        cursor: pointer;
                    }

                    .dropdown option:hover {
                        background-color: #555;
                    }

                    .remove-event {
                        width: 26px;
                        height: 26px;
                        color: #FFD700;
                        background-color: #444;
                        padding: 5px;
                        border: none;
                        border-radius: 4px;
                        cursor: pointer;
                    }
                    
                    #api-bar-controls {
                        display: flex;
                        align-items: center;
                    }

                    #url-input {
                        width: auto;
                    }

                    #send-button {
                        color: #FFD700;
                    }

                    #open-button {
                        display: none;
                        position: fixed;
                        bottom: 10px;
                        left: 10px;
                        width: 40px;
                        height: 40px;
                        background-color: #333;
                        border-radius: 50%;
                        border: 2px solid #444;
                        box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
                        cursor: pointer;
                        z-index: 1001;
                    }

                    #open-button::before {
                        content: "♾️";
                        display: block;
                        font-size: 24px;
                        text-align: center;
                        line-height: 36px;
                    }

                    #api-bar-controls button {
                        background: none;
                        border: none;
                        color: white;
                        cursor: pointer;
                        font-size: 16px;
                        margin-left: 10px;
                        padding: 5px;
                    }

                    #api-bar-content,
                    #api-result-content {
                        display: flex;
                        flex-grow: 1;
                        padding: 10px;
                        background-color: #1d1f21;
                    }

                    .api-section {
                        flex-grow: 1;
                        margin: 0 10px;
                        overflow-y: auto;
                        background-color: #222;
                        padding: 10px;
                        border-radius: 4px;
                    }

                    .api-section h3 {
                        margin-bottom: 10px;
                        color: #FFD700;
                    }

                    .api-section input,
                    .api-section textarea {
                        width: 100%;
                        padding: 5px;
                        background-color: #333;
                        color: white;
                        border: 1px solid #444;
                        border-radius: 4px;
                        margin-bottom: 10px;
                    }

                    .api-section pre {
                        background-color: #333;
                        color: #FFD700;
                        padding: 10px;
                        border-radius: 4px;
                        overflow: auto;
                    }

                    .key-value-pair,
                    .result-pair {
                        display: flex;
                        gap: 10px;
                        margin-bottom: 5px;
                    }

                    .key-value-pair input {
                        flex: 1;
                        padding: 5px;
                        background-color: #333;
                        color: white;
                        border: 1px solid #444;
                        border-radius: 4px;
                    }

                    .add-button {
                        background-color: #444;
                        color: #FFD700;
                        padding: 5px;
                        border: none;
                        border-radius: 4px;
                        cursor: pointer;
                    }

                    #custom-method-input {
                        padding: 5px;
                        background-color: #333;
                        color: white;
                        border: 1px solid #444;
                        border-radius: 4px;
                        text-transform: uppercase;
                    }

                    .resizer {
                        width: 5px;
                        background-color: #555;
                        cursor: ew-resize;
                    }

                    #top-resizer {
                        height: 2.5px;
                        background-color: #555;
                        cursor: ns-resize;
                        position: absolute;
                        top: 0;
                        width: 100%;
                    }

                    .body-header {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        margin-block-start: 1em;
                        margin-bottom: 10px;
                    }

                    .body-header h3 {
                        margin: 0;
                    }

                    .body-header select {
                        width: auto;
                        padding: 5px;
                    }

                    h1 {
                        text-align: center;
                        display: block;
                        font-size: 2em;
                        margin-block-start: 0.67em;
                        margin-block-end: 0.67em;
                        margin-inline-start: 0px;
                        margin-inline-end: 0px;
                        font-weight: bold;
                        unicode-bidi: isolate;
                    }

                    pre {
                        background-color: #1d1f21;
                        color: #c5c8c6;
                        word-wrap: break-word;
                        margin: 0px;
                        padding: 10px;
                        font-size: 14px;
                        margin-bottom: 20px;
                        position: relative;
                        display: block;
                        unicode-bidi: isolate;
                        white-space: pre;
                        font-family: 'Monaco', 'Menlo', courier, monospace;
                    }
                </style>
            </head>

            <body>
                <div id="result">
                <h1 style="display: flex;justify-content: center;align-content: center;flex-wrap: nowrap;align-items: center;">Response
                    <div style="display: flex;flex-wrap: nowrap;flex-direction: column;align-content: center;justify-content: center;align-items: center;margin-left: 5px;">
                        <ms style="font-size: 14px;">0ms</ms>
                        <br style="display: none;">
                        <dt style="font-size: 14px;">0B</dt>
                    </div>
                    <small style="margin-left: 5px;">()</small>
                </h1>
                    <div id="api-result-content">
                        <div id="headerArea" class="api-section">
                            <h3>Header</h3>
                            <div class="result-pair">
                                <pre id="result-header" class="api-section"></pre>
                            </div>
                        </div>
                        <div class="resizer" id="resizer0"></div>
                        <div id="bodyArea" class="api-section">
                            <h3>Body</h3>
                            <div class="result-pair">
                                <pre id="result-output" class="api-section"></pre>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="api-bar">
                    <div id="top-resizer"></div>
                    <div id="api-bar-header">
                        <div id="api-bar-title">
                            <div id="left-title">
                                <a href="https://github.com/sakibweb" target="_blank">♾️API Bar (PHDE)</a>
                            </div>
                            <div class="input-container">
                                <div class="grouped-button">
                                    <input type="text" id="url-input" placeholder="Enter custom URL" />
                                    <button id="url-select-btn">▼</button>
                                </div>
                                <div class="dropdown" id="url-dropdown"></div>
                                <select id="method-select">
                                    <option value="GET">GET</option>
                                    <option value="POST">POST</option>
                                    <option value="PUT">PUT</option>
                                    <option value="DELETE">DELETE</option>
                                    <option value="OPTIONS">OPTIONS</option>
                                    <option value="PATCH">PATCH</option>
                                    <option value="CUSTOM">CUSTOM</option>
                                </select>
                                <input type="text" id="custom-method-input" style="display: none;" placeholder="Enter custom method" />
                                <button id="send-button">Send</button>
                            </div>
                            <div id="api-bar-controls">
                                <button id="minimize-button">—</button>
                                <button id="maximize-button">☐</button>
                                <button id="close-button">✖</button>
                            </div>
                        </div>
                    </div>
                    <div id="api-bar-content">
                        <div id="params" class="api-section">
                            <h3>Params</h3>
                            <div id="params-container">
                            </div>
                            <button class="add-button" id="add-param" disabled>+ Add Param</button>
                        </div>
                        <div class="resizer" id="resizer1"></div>
                        <div id="headers" class="api-section">
                            <h3>Headers</h3>
                            <div id="headers-container">
                            </div>
                            <button class="add-button" id="add-header">+ Add Header</button>
                        </div>
                        <div class="resizer" id="resizer2"></div>
                        <div id="body" class="api-section">
                            <div class="body-header">
                                <h3>Body</h3>
                                <select id="body-type-select">
                                    <option value="none">None</option>
                                    <option value="form">Form Data</option>
                                    <option value="urlencoded">URL Encoded</option>
                                    <option value="json">JSON</option>
                                    <option value="xml">XML</option>
                                    <option value="raw">Raw</option>
                                    <option value="html">HTML</option>
                                    <option value="javascript">JavaScript</option>
                                    <option value="file">File</option>
                                    <option value="binary">Binary</option>
                                </select>
                            </div>
                            <!-- Dynamic fields based on type selection -->
                            <div id="body-key-value-container" style="display:none;">
                                <div id="body-key-value-pairs">
                                    <div class="key-value-pair">
                                        <input type="text" placeholder="Key" class="param-key">
                                        <input type="text" placeholder="Value" class="param-value">
                                        <button class="remove-event">—</button>
                                    </div>
                                </div>
                                <button class="add-button" id="add-body-key-value">+ Add Body</button>
                                </div>
                            <textarea id="body-text-input" style="display:none;" rows="5" placeholder="Enter raw data here..."></textarea>
                            <input type="file" id="body-file-input" style="display:none;">
                        </div>
                    </div>
                </div>
                <div id="open-button"></div>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        const apiBar = document.getElementById('api-bar');
                        const apiResultContent = document.getElementById('api-result-content');
                        const minimizeButton = document.getElementById('minimize-button');
                        const maximizeButton = document.getElementById('maximize-button');
                        const closeButton = document.getElementById('close-button');
                        const openButton = document.getElementById('open-button');
                        const topResizer = document.getElementById('top-resizer');
                        const resizer0 = document.getElementById('resizer0');
                        const resizer1 = document.getElementById('resizer1');
                        const resizer2 = document.getElementById('resizer2');
                        const headerRes = document.getElementById('headerArea');
                        const bodyRes = document.getElementById('bodyArea');
                        const paramsArea = document.getElementById('params');
                        const headersArea = document.getElementById('headers');
                        const bodyArea = document.getElementById('body');
                        const urlSelect = document.getElementById('url-select');
                        const methodSelect = document.getElementById('method-select');
                        const urlInput = document.getElementById('url-input');
                        const sendButton = document.getElementById('send-button');
                        const resultHeader = document.getElementById('result-header');
                        const resultOutput = document.getElementById('result-output');
                        const paramsContainer = document.getElementById('params-container');
                        const headersContainer = document.getElementById('headers-container');
                        const addParamButton = document.getElementById('add-param');
                        const addHeaderButton = document.getElementById('add-header');
                        const urlSelectBtn = document.getElementById('url-select-btn');
                        const urlDropdown = document.getElementById('url-dropdown');
                        const customMethodInput = document.getElementById('custom-method-input');
                        const resultTime = document.querySelector('#result small');
                        const resultMS = document.querySelector('#result ms');
                        const resultDT = document.querySelector('#result dt');
                        const bodyTypeSelect = document.getElementById('body-type-select');
                        const keyValueContainer = document.getElementById('body-key-value-container');
                        const bodyKeyValuePairs = document.getElementById('body-key-value-pairs');
                        const addBodyKeyValue = document.getElementById('add-body-key-value');
                        const bodyTextInput = document.getElementById('body-text-input');
                        const bodyFileInput = document.getElementById('body-file-input');

                        let isMinimized = false;
                        let isFullScreen = false;
                        let previousHeight = apiBar.style.height;

                        const apiInfo = [
            EOT;
            $routes = PHRO::routes();
            $routeStrings = array_map(function($route) {
                return sprintf(
                    '{ "short": "%s", "method": "%s", "link": "%s" }',
                    $route['short'],
                    $route['method'],
                    $route['link']
                );
            }, $routes);
            echo implode(",\n", $routeStrings);
            print <<<EOT
                        ];

                        // Populate the dropdown with API info
                        apiInfo.forEach(info => {
                            const option = document.createElement('div');
                            option.textContent = `\${info.method} \${info.short}`;
                            option.setAttribute('data-url', info.link);
                            option.setAttribute('data-method', info.method);
                            option.classList.add('dropdown-option');
                            option.style.padding = "10px";
                            urlDropdown.appendChild(option);
                        });

                        // Toggle dropdown visibility
                        urlSelectBtn.addEventListener('click', () => {
                            urlDropdown.style.display = urlDropdown.style.display === 'none' || !urlDropdown.style.display ? 'block' : 'none';
                            adjustDropdownPosition(urlDropdown, urlSelectBtn);
                        });

                        // Handle dropdown selection
                        urlDropdown.addEventListener('click', (e) => {
                            if (e.target.classList.contains('dropdown-option')) {
                                urlInput.value = e.target.getAttribute('data-url');
                                const selectedUrl = e.target.getAttribute('data-url');
                                methodSelect.value = e.target.getAttribute('data-method');
                                urlDropdown.style.display = 'none';

                                // Clear existing parameter inputs
                                paramsContainer.innerHTML = '';

                                // Add parameter inputs based on the URL
                                const urlParts = selectedUrl.split('/');
                                urlParts.forEach(part => {
                                    if (part.startsWith('@')) {
                                        const paramName = part.substring(1);  // Remove '@' to get the parameter key
                                        const paramPair = document.createElement('div');
                                        paramPair.className = 'key-value-pair';
                                        paramPair.innerHTML = `
                                            <input type="text" placeholder="Key" class="param-key" value="\${paramName}" readonly>
                                            <input type="text" placeholder="Value" class="param-value">
                                        `;
                                        paramsContainer.appendChild(paramPair);
                                    }
                                });
                            }
                        });

                        // Show custom method input if CUSTOM is selected
                        methodSelect.addEventListener('change', function () {
                            if (methodSelect.value === 'CUSTOM') {
                                customMethodInput.style.display = 'inline-block';
                                customMethodInput.focus();
                            } else {
                                customMethodInput.style.display = 'none';
                                customMethodInput.value = '';
                            }
                        });

                        // Force custom method input to uppercase
                        customMethodInput.addEventListener('input', function () {
                            this.value = this.value.toUpperCase();
                        });

                        // Add param functionality
                        addParamButton.addEventListener('click', () => {
                            const paramPair = document.createElement('div');
                            paramPair.className = 'key-value-pair';
                            paramPair.innerHTML = `
                                <input type="text" placeholder="Key" class="param-key">
                                <input type="text" placeholder="Value" class="param-value">
                            `;
                            paramsContainer.appendChild(paramPair);
                        });

                        // Add header functionality
                        addHeaderButton.addEventListener('click', () => {
                            const headerPair = document.createElement('div');
                            headerPair.className = 'key-value-pair';
                            headerPair.innerHTML = `
                                <input type="text" placeholder="Key" class="header-key">
                                <input type="text" placeholder="Value" class="header-value">
                                <button class="remove-event">—</button>
                            `;
                            headersContainer.appendChild(headerPair);
                        });

                        function enableRemoveFunctionality(containerId) {
                            const container = document.getElementById(containerId);
                            // remove buttons inside the container
                            container.addEventListener('click', function(event) {
                                if (event.target.classList.contains('remove-event')) {
                                    const keyValuePair = event.target.closest('.key-value-pair');
                                    if (keyValuePair) {
                                        keyValuePair.remove(); // Remove the entire key-value pair
                                    }
                                }
                            });
                        }

                        // Toggle minimize
                        minimizeButton.addEventListener('click', function () {
                            if (isMinimized) {
                                apiBar.style.height = previousHeight;
                                this.textContent = '—';
                            } else {
                                previousHeight = apiBar.style.height;
                                apiBar.style.height = '42px';
                                this.textContent = '+';
                            }
                            isMinimized = !isMinimized;
                        });

                        // Toggle maximize
                        maximizeButton.addEventListener('click', function () {
                            if (isFullScreen) {
                                previousHeight = apiBar.style.height;
                                apiBar.style.height = '100%';
                                apiBar.classList.add('fullscreen');
                                this.textContent = '❐';
                            } else {
                                previousHeight = apiBar.style.height;
                                apiBar.style.height = '40%';
                                apiBar.classList.remove('fullscreen');
                                this.textContent = '☐';
                            }
                            isFullScreen = !isFullScreen;
                        });

                        // Close button functionality
                        closeButton.addEventListener('click', function () {
                            apiBar.style.display = 'none';
                            openButton.style.display = 'block';
                        });

                        // Restore debug bar from button
                        openButton.addEventListener('click', function () {
                            apiBar.style.display = 'block';
                            openButton.style.display = 'none';
                        });


                        // Resizing logic
                        function makeResizableBar(bar, direction) {
                            let startX, startY, startWidth, startHeight;
                            const MIN_HEIGHT = 40;

                            bar.addEventListener('mousedown', (e) => {
                                startX = e.clientX;
                                startY = e.clientY;
                                startWidth = parseInt(document.defaultView.getComputedStyle(apiBar).width, 10);
                                startHeight = parseInt(document.defaultView.getComputedStyle(apiBar).height, 10);
                                document.documentElement.addEventListener('mousemove', doDrag, false);
                                document.documentElement.addEventListener('mouseup', stopDrag, false);
                            });

                            function doDrag(e) {
                                if (direction === 'ew') {
                                    apiBar.style.width = (startWidth + e.clientX - startX) + 'px';
                                } else if (direction === 'ns') {
                                    let newHeight = startHeight - e.clientY + startY;
                                    if (newHeight < MIN_HEIGHT) {
                                        newHeight = MIN_HEIGHT;
                                    }
                                    apiBar.style.height = newHeight + 'px';
                                }
                            }

                            function stopDrag() {
                                document.documentElement.removeEventListener('mousemove', doDrag, false);
                                document.documentElement.removeEventListener('mouseup', stopDrag, false);
                            }
                        }

                        // Resizable sections
                        function makeResizable(resizer, previousElement, nextElement, numberOfElements = 0) {
                            let startX, startWidthPrev, startWidthNext;

                            // Set default flex-basis if not set
                            if (numberOfElements !== 0 && (!previousElement.style.flexBasis || !nextElement.style.flexBasis)) {
                                const totalWidth = previousElement.parentElement.offsetWidth;
                                const defaultFlexBasis = `\${totalWidth / numberOfElements}px`;
                                if (!previousElement.style.flexBasis) {
                                    previousElement.style.flexBasis = defaultFlexBasis;
                                }
                                if (!nextElement.style.flexBasis) {
                                    nextElement.style.flexBasis = defaultFlexBasis;
                                }
                            }

                            resizer.addEventListener('mousedown', function(e) {
                                startX = e.clientX;
                                startWidthPrev = previousElement.offsetWidth;
                                startWidthNext = nextElement.offsetWidth;

                                document.addEventListener('mousemove', resize);
                                document.addEventListener('mouseup', stopResize);
                            });

                            function resize(e) {
                                const offset = e.clientX - startX;
                                previousElement.style.flexBasis = `\${startWidthPrev + offset}px`;
                                nextElement.style.flexBasis = `\${startWidthNext - offset}px`;
                            }

                            function stopResize() {
                                document.removeEventListener('mousemove', resize);
                                document.removeEventListener('mouseup', stopResize);
                            }
                        }

                        function adjustResultContentHeight() {
                            const screenHeight = window.innerHeight;
                            const apiBarHeight = apiBar.getBoundingClientRect().height;
                            apiResultContent.style.height = `\${screenHeight - apiBarHeight - 100}px`;
                            resultHeader.style.minHeight = `\${screenHeight - apiBarHeight - 200}px`;
                            resultOutput.style.minHeight = `\${screenHeight - apiBarHeight - 200}px`;
                        }

                        function adjustDropdownPosition(dropdown, trigger) {
                            const triggerRect = trigger.getBoundingClientRect();
                            const dropdownHeight = dropdown.offsetHeight;
                            const screenHeight = window.innerHeight;

                            const spaceBelow = screenHeight - triggerRect.bottom;
                            const spaceAbove = triggerRect.top;

                            // Check where more space is available
                            if (spaceBelow >= dropdownHeight) {
                                // Enough space below the trigger, position below
                                dropdown.style.top = '100%'; // positioned below the trigger
                                dropdown.style.bottom = 'auto';
                            } else if (spaceAbove >= dropdownHeight) {
                                // Enough space above the trigger, position above
                                dropdown.style.top = 'auto';
                                dropdown.style.bottom = '100%'; // positioned above the trigger
                            } else if (spaceBelow > spaceAbove) {
                                // Not enough space in both directions, but more space below
                                dropdown.style.top = '100%';
                                dropdown.style.bottom = 'auto';
                                dropdown.style.maxHeight = `\${spaceBelow}px`;
                            } else {
                                // Not enough space in both directions, but more space above
                                dropdown.style.top = 'auto';
                                dropdown.style.bottom = '100%';
                                dropdown.style.maxHeight = `\${spaceAbove}px`;
                            }
                        }

                        // Handle Body Type Selection
                        bodyTypeSelect.addEventListener('change', function () {
                            const selectedType = bodyTypeSelect.value;
                            hideAllBodyInputFields();

                            switch (selectedType) {
                                case 'none':
                                    break;
                                case 'form':
                                case 'urlencoded':
                                case 'json':
                                case 'xml':
                                    keyValueContainer.style.display = 'block';
                                    break;
                                case 'raw':
                                case 'html':
                                case 'javascript':
                                    bodyTextInput.style.display = 'block';
                                    break;
                                case 'file':
                                case 'binary':
                                    bodyFileInput.style.display = 'block';
                                    break;
                            }
                        });

                        // Function to hide all body input fields
                        function hideAllBodyInputFields() {
                            keyValueContainer.style.display = 'none';
                            bodyTextInput.style.display = 'none';
                            bodyFileInput.style.display = 'none';
                        }

                        // Function to add key-value pair inputs
                        addBodyKeyValue.addEventListener('click', function () {
                            const keyValuePair = document.createElement('div');
                            keyValuePair.className = 'key-value-pair';
                            keyValuePair.innerHTML = `
                                <input type="text" placeholder="Key" class="param-key">
                                <input type="text" placeholder="Value" class="param-value">
                                <button class="remove-event">—</button>
                            `;
                            bodyKeyValuePairs.appendChild(keyValuePair);
                        });

                        sendButton.addEventListener('click', async () => {
                            sendButton.disabled = true;
                            sendButton.textContent = "Wait";
                            resultMS.textContent = '0ms';
                            resultDT.textContent = '0B';
                            resultTime.textContent = '()';
                            resultOutput.textContent = '';
                            resultHeader.textContent = '';
                            // Gather params
                            const params = Array.from(paramsContainer.querySelectorAll('.key-value-pair')).reduce((acc, pair) => {
                                const keyElem = pair.querySelector('.param-key');
                                const valueElem = pair.querySelector('.param-value');
                                if (keyElem && valueElem) {
                                    const key = keyElem.value;
                                    const value = valueElem.value;
                                    if (key) acc[key] = value;
                                }
                                return acc;
                            }, {});

                            // Original URL
                            let url = urlInput ? urlInput.value : '';

                            // Replace @key in URL with actual values
                            for (const [key, value] of Object.entries(params)) {
                                const regex = new RegExp(`@\${key}`, 'g'); // Create a global regex for @key
                                url = url.replace(regex, value);
                            }

                            const method = methodSelect ? (methodSelect.value === 'CUSTOM' ? customMethodInput.value : methodSelect.value) : 'GET';

                            // Gather headers
                            const headers = {};
                            document.querySelectorAll('.header-key').forEach((keyElem, index) => {
                                const valueElem = document.querySelectorAll('.header-value')[index];
                                if (keyElem && valueElem) {
                                    const key = keyElem.value;
                                    const value = valueElem.value;
                                    if (key) {
                                        headers[key] = value;
                                    }
                                }
                            });

                            // Create request options
                            const options = {
                                method,
                                headers
                            };

                            const bodyType = bodyTypeSelect.value;

                            // Gather params if necessary (for form, urlencoded, json, xml)
                            let body;
                            if (bodyType === 'form' || bodyType === 'urlencoded' || bodyType === 'json' || bodyType === 'xml') {
                                const params = Array.from(bodyKeyValuePairs.querySelectorAll('.key-value-pair')).reduce((acc, pair) => {
                                    const key = pair.querySelector('.param-key').value;
                                    const value = pair.querySelector('.param-value').value;
                                    if (key) acc[key] = value;
                                    return acc;
                                }, {});

                                if (bodyType === 'form') {
                                    body = new FormData();
                                    for (const [key, value] of Object.entries(params)) {
                                        body.append(key, value);
                                    }
                                } else if (bodyType === 'urlencoded') {
                                    body = new URLSearchParams(params).toString();
                                } else if (bodyType === 'json') {
                                    body = JSON.stringify(params);
                                } else if (bodyType === 'xml') {
                                    body = `<root>`;
                                    for (const [key, value] of Object.entries(params)) {
                                        body += `<\${key}>\${value}</\${key}>`;
                                    }
                                    body += `</root>`;
                                }
                            } else if (bodyType === 'raw' || bodyType === 'html' || bodyType === 'javascript') {
                                body = bodyTextInput.value;
                            } else if (bodyType === 'file' || bodyType === 'binary') {
                                body = bodyFileInput.files[0]; // Only handle single file upload for now
                            }

                            if (method === 'POST' || method === 'PUT' || methodSelect.value === 'CUSTOM') {
                                if (body) {
                                    options.body = body;
                                }
                            }

                            try {
                                const startTime = performance.now();
                                const response = await fetch(url, options);
                                const endTime = performance.now();
                                const duration = formatResponseTime(endTime - startTime);

                                let responseData;
                                const contentType = response.headers.get('content-type');
                                if (contentType.includes('application/json')) {
                                    responseData = await response.json();
                                } else if (contentType.includes('text/html')) {
                                    responseData = await response.text();
                                } else if (contentType.includes('application/xml')) {
                                    responseData = await response.text(); // Treat as text for simplicity
                                    responseData = formatXml(responseData);
                                } else {
                                    responseData = await response.text(); // Fallback for other types
                                }

                                // Extract response headers
                                const responseHeaders = {};
                                for (const [key, value] of response.headers.entries()) {
                                    responseHeaders[key] = value;
                                }

                                // Display headers in pretty format
                                resultHeader.textContent = JSON.stringify({
                                    status: response.status,
                                    statusText: response.statusText,
                                    headers: responseHeaders,
                                    cookies: document.cookie // Client-side cookies if available
                                }, null, 2);

                                resultMS.textContent = `\${duration}`;
                                resultDT.textContent = `\${formatResponseSize(responseHeaders['content-length'])}`;
                                resultTime.textContent = `(\${response.status}:\${response.statusText})`;
                                resultOutput.textContent = JSON.stringify(responseData, null, 2);
                            } catch (error) {
                                resultOutput.textContent = `Error: \${error.message}`;
                            }
                            
                            sendButton.disabled = false;
                            sendButton.textContent = "Send";
                        });

                        // Helper function to escape HTML and display it as raw text
                        function escapeHtml(html) {
                            const div = document.createElement('div');
                            div.innerText = html;
                            return div.innerHTML;  // Return escaped HTML code
                        }

                        // Helper function to format XML (optional, for displaying formatted XML in the response)
                        function formatXml(xmlString) {
                            const parser = new DOMParser();
                            const xmlDoc = parser.parseFromString(xmlString, "application/xml");
                            const serializer = new XMLSerializer();
                            return serializer.serializeToString(xmlDoc);
                        }

                        function formatResponseTime(ms) {
                            if (ms < 1000) return `\${ms.toFixed(0)}ms`;
                            else if (ms < 60000) return `\${(ms / 1000).toFixed(2)}sec`;
                            else if (ms < 3600000) return `\${(ms / 60000).toFixed(2)}min`;
                            else if (ms < 86400000) return `\${(ms / 3600000).toFixed(2)}hour`;
                            else if (ms < 604800000) return `\${(ms / 86400000).toFixed(2)}day`;
                            else if (ms < 2419200000) return `\${(ms / 604800000).toFixed(2)}week`;
                            else if (ms < 29030400000) return `\${(ms / 2419200000).toFixed(2)}month`;
                            else return `\${(ms / 29030400000).toFixed(2)}year`;
                        }

                        function formatResponseSize(bytes) {
                            if (bytes < 1024) return `\${bytes}B`;
                            else if (bytes < 1048576) return `\${(bytes / 1024).toFixed(2)}KB`;
                            else if (bytes < 1073741824) return `\${(bytes / 1048576).toFixed(2)}MB`;
                            else if (bytes < 1099511627776) return `\${(bytes / 1073741824).toFixed(2)}GB`;
                            else if (bytes < 1125899906842624) return `\${(bytes / 1099511627776).toFixed(2)}TB`;
                            else return `\${(bytes / 1125899906842624).toFixed(2)}PB`;
                        }

                        makeResizableBar(topResizer, 'ns');
                        makeResizable(resizer0, headerRes, bodyRes, 2);
                        makeResizable(resizer1, paramsArea, headersArea, 3);
                        makeResizable(resizer2, headersArea, bodyArea, 3);

                        window.addEventListener('load', adjustResultContentHeight);
                        window.addEventListener('resize', adjustResultContentHeight);
                        const apiBarObserver = new ResizeObserver(() => {
                            adjustResultContentHeight();
                        });
                        apiBarObserver.observe(apiBar);

                        function setCurrentURL() {
                            const urlInput = document.getElementById('url-input');
                            if (urlInput) {
                                urlInput.value = window.location.href;
                            }
                        }

                        enableRemoveFunctionality('headers-container');
                        enableRemoveFunctionality('params-container');
                        enableRemoveFunctionality('body-key-value-pairs');
                        window.onload = setCurrentURL;
                    });
                </script>
            </body>
            </html>
            EOT;
        });
    }

    /**
     * Set HTTP response headers for file downloads.
     *
     * @param string $name Filename of the file being downloaded.
     * @param int $length Length of the file being downloaded.
     */
    public static function file($name, $length) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.$name.'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: '.$length);
    }
    
    /**
     * Set memory limit for PHP script.
     * 
     * @param string $limit Memory limit in format supported by ini_set(),
     *      examples: 256M, 512K, 1G.
     */
    public static function memory($limit) {
        ini_set('memory_limit', $limit);
        ini_set('upload_max_filesize', $limit);
        ini_set('post_max_size', $limit);
    }
}
?>
