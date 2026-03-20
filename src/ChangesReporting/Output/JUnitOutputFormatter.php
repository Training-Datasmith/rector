<?php

/**
 * JUnit specification:
 * - https://github.com/junit-team/junit5/blob/main/platform-tests/src/test/resources/jenkins-junit.xsda
 */
declare (strict_types=1);
namespace Rector\Changes_Reporting\Output;

use Dom_Document;
use Dom_Element;
use Rector\Changes_Reporting\Contract\Output\Output_Formatter_Interface;
use Rector\Value_Object\Configuration;
use Rector\Value_Object\Process_Result;
use Rector_Prefix202603\Symfony\Component\Console\Style\Symfony_Style;
final class J_Unit_Output_Formatter implements Output_Formatter_Interface
{
    /**
     * @readonly
     */
    private Symfony_Style $symfony_style;
    /**
     * @var string
     */
    private const NAME = 'junit';
    /**
     * @var string
     */
    private const XML_ATTRIBUTE_FILE = 'file';
    /**
     * @var string
     */
    private const XML_ATTRIBUTE_NAME = 'name';
    /**
     * @var string
     */
    private const XML_ATTRIBUTE_TYPE = 'type';
    /**
     * @var string
     */
    private const XML_ELEMENT_TESTSUITES = 'testsuites';
    /**
     * @var string
     */
    private const XML_ELEMENT_TESTSUITE = 'testsuite';
    /**
     * @var string
     */
    private const XML_ELEMENT_TESTCASE = 'testcase';
    /**
     * @var string
     */
    private const XML_ELEMENT_ERROR = 'error';
    public function __construct(Symfony_Style $symfony_style)
    {
        $this->symfony_style = $symfony_style;
    }
    public function get_name(): string
    {
        return self::NAME;
    }
    public function report(Process_Result $process_result, Configuration $configuration): void
    {
        if (!extension_loaded('dom')) {
            $this->symfony_style->warning('The "dom" extension is not loaded. The rector could not generate a response in the JUnit format');
            return;
        }
        $dom_document = new Dom_Document('1.0', 'UTF-8');
        $xml_test_suite = $dom_document->create_element(self::XML_ELEMENT_TESTSUITE);
        $xml_test_suite->set_attribute(self::XML_ATTRIBUTE_NAME, 'rector');
        $xml_test_suites = $dom_document->create_element(self::XML_ELEMENT_TESTSUITES);
        $xml_test_suites->append_child($xml_test_suite);
        $dom_document->append_child($xml_test_suites);
        $this->append_system_errors($process_result, $configuration, $dom_document, $xml_test_suite);
        $this->append_file_diffs($process_result, $configuration, $dom_document, $xml_test_suite);
        echo $dom_document->save_xml() . \PHP_EOL;
    }
    private function append_system_errors(Process_Result $process_result, Configuration $configuration, Dom_Document $dom_document, Dom_Element $dom_element): void
    {
        if ($process_result->get_system_errors() === []) {
            return;
        }
        foreach ($process_result->get_system_errors() as $system_error) {
            $file_path = $configuration->is_reporting_with_real_path() ? $system_error->get_absolute_file_path() ?? '' : $system_error->get_relative_file_path() ?? '';
            $xml_error = $dom_document->create_element(self::XML_ELEMENT_ERROR);
            $xml_error->set_attribute(self::XML_ATTRIBUTE_TYPE, 'Error');
            $xml_error->append_child($dom_document->create_text_node($system_error->get_message()));
            $xml_test_case = $dom_document->create_element(self::XML_ELEMENT_TESTCASE);
            $xml_test_case->set_attribute(self::XML_ATTRIBUTE_FILE, $file_path);
            $xml_test_case->set_attribute(self::XML_ATTRIBUTE_NAME, $file_path . ':' . $system_error->get_line());
            $xml_test_case->append_child($xml_error);
            $dom_element->append_child($xml_test_case);
        }
    }
    private function append_file_diffs(Process_Result $process_result, Configuration $configuration, Dom_Document $dom_document, Dom_Element $dom_element): void
    {
        if ($process_result->get_file_diffs() === []) {
            return;
        }
        $file_diffs = $process_result->get_file_diffs();
        ksort($file_diffs);
        foreach ($file_diffs as $file_diff) {
            $file_path = $configuration->is_reporting_with_real_path() ? $file_diff->get_absolute_file_path() ?? '' : $file_diff->get_relative_file_path() ?? '';
            $rector_classes = implode(' / ', $file_diff->get_rector_short_classes());
            $xml_error = $dom_document->create_element(self::XML_ELEMENT_ERROR);
            $xml_error->set_attribute(self::XML_ATTRIBUTE_TYPE, $rector_classes);
            $xml_error->append_child($dom_document->create_text_node($file_diff->get_diff()));
            $xml_test_case = $dom_document->create_element(self::XML_ELEMENT_TESTCASE);
            $xml_test_case->set_attribute(self::XML_ATTRIBUTE_FILE, $file_path);
            $xml_test_case->set_attribute(self::XML_ATTRIBUTE_NAME, $file_path . ':' . $file_diff->get_first_line_number());
            $xml_test_case->append_child($xml_error);
            $dom_element->append_child($xml_test_case);
        }
    }
}