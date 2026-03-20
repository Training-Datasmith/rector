<?php

declare (strict_types=1);
namespace Rector\Console\Command;

use Rector\Changes_Reporting\Output\Console_Output_Formatter;
use Rector\Configuration\Option;
use Rector\Contract\Rector\Rector_Interface;
use Rector\Post_Rector\Contract\Rector\Post_Rector_Interface;
use Rector\Skipper\Skip_Criteria_Resolver\Skipped_Class_Resolver;
use Rector_Prefix202603\Nette\Utils\Json;
use Rector_Prefix202603\Symfony\Component\Console\Command\Command;
use Rector_Prefix202603\Symfony\Component\Console\Input\Input_Interface;
use Rector_Prefix202603\Symfony\Component\Console\Input\Input_Option;
use Rector_Prefix202603\Symfony\Component\Console\Output\Output_Interface;
use Rector_Prefix202603\Symfony\Component\Console\Style\Symfony_Style;
final class List_Rules_Command extends Command
{
    /**
     * @readonly
     */
    private Symfony_Style $symfony_style;
    /**
     * @readonly
     */
    private Skipped_Class_Resolver $skipped_class_resolver;
    /**
     * @var RectorInterface[]
     * @readonly
     */
    private array $rectors;
    /**
     * @param RectorInterface[] $rectors
     */
    public function __construct(Symfony_Style $symfony_style, Skipped_Class_Resolver $skipped_class_resolver, array $rectors)
    {
        $this->symfony_style = $symfony_style;
        $this->skipped_class_resolver = $skipped_class_resolver;
        $this->rectors = $rectors;
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->set_name('list-rules');
        $this->set_description('Show loaded Rectors');
        $this->set_aliases(['show-rules']);
        $this->add_option(Option::OUTPUT_FORMAT, null, Input_Option::VALUE_REQUIRED, 'Select output format', Console_Output_Formatter::NAME);
        $this->add_option(Option::ONLY, null, Input_Option::VALUE_REQUIRED, 'Fully qualified rule class name');
    }
    protected function execute(Input_Interface $input, Output_Interface $output): int
    {
        $rector_classes = $this->resolve_rector_classes();
        $skipped_classes = $this->get_skipped_rector_classes();
        $output_format = $input->get_option(Option::OUTPUT_FORMAT);
        if ($output_format === 'json') {
            $data = ['rectors' => $rector_classes, 'skipped-rectors' => $skipped_classes];
            echo Json::encode($data, \true) . \PHP_EOL;
            return Command::SUCCESS;
        }
        $this->symfony_style->title('Loaded Rector rules');
        $this->symfony_style->listing($rector_classes);
        if ($skipped_classes !== []) {
            $this->symfony_style->title('Skipped Rector rules');
            $this->symfony_style->listing($skipped_classes);
        }
        $this->symfony_style->new_line();
        $this->symfony_style->note(sprintf('Loaded %d rules', count($rector_classes)));
        return Command::SUCCESS;
    }
    /**
     * @return array<class-string<RectorInterface>>
     */
    private function resolve_rector_classes(): array
    {
        $custom_rectors = array_filter($this->rectors, static fn(Rector_Interface $rector): bool => !$rector instanceof Post_Rector_Interface);
        $rector_classes = array_map(static fn(Rector_Interface $rector): string => get_class($rector), $custom_rectors);
        sort($rector_classes);
        return array_unique($rector_classes);
    }
    /**
     * @return array<class-string>
     */
    private function get_skipped_rector_classes(): array
    {
        $skipped_rector_classes = [];
        foreach ($this->skipped_class_resolver->resolve() as $rector_class => $file_list) {
            // ignore specific skips
            if ($file_list !== null) {
                continue;
            }
            $skipped_rector_classes[] = $rector_class;
        }
        return $skipped_rector_classes;
    }
}