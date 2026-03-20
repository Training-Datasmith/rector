<?php

declare (strict_types=1);
namespace Rector\Console\Output;

use Rector\Changes_Reporting\Contract\Output\Output_Formatter_Interface;
use Rector\Exception\Configuration\Invalid_Configuration_Exception;
final class Output_Formatter_Collector
{
    /**
     * @var array<string, OutputFormatterInterface>
     */
    private array $output_formatters = [];
    /**
     * @param OutputFormatterInterface[] $outputFormatters
     */
    public function __construct(iterable $output_formatters)
    {
        foreach ($output_formatters as $output_formatter) {
            $this->output_formatters[$output_formatter->get_name()] = $output_formatter;
        }
    }
    public function get_by_name(string $name): Output_Formatter_Interface
    {
        $this->ensure_output_format_exists($name);
        return $this->output_formatters[$name];
    }
    private function ensure_output_format_exists(string $name): void
    {
        if (isset($this->output_formatters[$name])) {
            return;
        }
        $output_formatter_names = array_keys($this->output_formatters);
        throw new Invalid_Configuration_Exception(sprintf('Output formatter "%s" was not found. Pick one of "%s".', $name, implode('", "', $output_formatter_names)));
    }
}