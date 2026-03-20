<?php

declare (strict_types=1);
namespace Rector\Changes_Reporting\Value_Object_Factory;

use Php_Stan\Analysed_Code_Exception;
use Rector\File_System\File_Path_Helper;
use Rector\Value_Object\Error\System_Error;
final class Error_Factory
{
    /**
     * @readonly
     */
    private File_Path_Helper $file_path_helper;
    public function __construct(File_Path_Helper $file_path_helper)
    {
        $this->file_path_helper = $file_path_helper;
    }
    public function create_autoload_error(Analysed_Code_Exception $analysed_code_exception, string $file_path): System_Error
    {
        $message = $this->create_exception_message($analysed_code_exception);
        $relative_file_path = $this->file_path_helper->relative_path($file_path);
        return new System_Error($message, $relative_file_path);
    }
    private function create_exception_message(Analysed_Code_Exception $analysed_code_exception): string
    {
        return sprintf('Analyze error: "%s". Include your files in "$rectorConfig->autoloadPaths([...]);" or "$rectorConfig->bootstrapFiles([...]);" in "rector.php" config.%sSee https://github.com/rectorphp/rector#configuration', $analysed_code_exception->get_message(), \PHP_EOL);
    }
}