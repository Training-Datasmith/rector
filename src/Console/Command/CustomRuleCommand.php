<?php

declare (strict_types=1);
namespace Rector\Console\Command;

use Php_Stan\Reflection\Reflection_Provider;
use Rector\Enum\Class_Name;
use Rector\Exception\Should_Not_Happen_Exception;
use Rector\File_System\Json_File_System;
use Rector_Prefix202603\Nette\Utils\File_System;
use Rector_Prefix202603\Nette\Utils\Strings;
use Rector_Prefix202603\Symfony\Component\Console\Command\Command;
use Rector_Prefix202603\Symfony\Component\Console\Input\Input_Interface;
use Rector_Prefix202603\Symfony\Component\Console\Output\Output_Interface;
use Rector_Prefix202603\Symfony\Component\Console\Style\Symfony_Style;
use Rector_Prefix202603\Symfony\Component\Finder\Finder;
use Rector_Prefix202603\Symfony\Component\Finder\Spl_File_Info;
final class Custom_Rule_Command extends Command
{
    /**
     * @readonly
     */
    private Symfony_Style $symfony_style;
    /**
     * @readonly
     */
    private Reflection_Provider $reflection_provider;
    public function __construct(Symfony_Style $symfony_style, Reflection_Provider $reflection_provider)
    {
        $this->symfony_style = $symfony_style;
        $this->reflection_provider = $reflection_provider;
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->set_name('custom-rule');
        $this->set_description('Create base of local custom rule with tests');
    }
    protected function execute(Input_Interface $input, Output_Interface $output): int
    {
        // ask for rule name
        $rector_name = $this->symfony_style->ask('What is the rule class name? (e.g. "LegacyCallToDbalMethodCall")?', null, static function (?string $answer): string {
            if ($answer === '' || $answer === null) {
                throw new Should_Not_Happen_Exception('Rector name cannot be empty');
            }
            return $answer;
        });
        // suffix with Rector by convention
        if (substr_compare((string) $rector_name, 'Rector', -strlen('Rector')) !== 0) {
            $rector_name .= 'Rector';
        }
        $rector_name = ucfirst((string) $rector_name);
        // find all files in templates directory
        $finder = Finder::create()->files()->in(__DIR__ . '/../../../templates/custom-rule')->not_name('__Name__Test.php.phtml');
        // 0. resolve if local phpunit is at least PHPUnit 10 (which supports #[DataProvider])
        // to provide annotation if not
        if ($this->is_php_unit_attribute_supported()) {
            $finder->append([new Spl_File_Info(__DIR__ . '/../../../templates/custom-rule/utils/rector/tests/Rector/__Name__/__Name__Test.php.phtml', 'utils/rector/tests/Rector/__Name__', 'utils/rector/tests/Rector/__Name__/__Name__Test.php.phtml')]);
        } else {
            // use @annotations for PHPUnit 9 and bellow
            $finder->append([new Spl_File_Info(__DIR__ . '/../../../templates/custom-rules-annotations/utils/rector/tests/Rector/__Name__/__Name__Test.php.phtml', 'utils/rector/tests/Rector/__Name__', 'utils/rector/tests/Rector/__Name__/__Name__Test.php.phtml')]);
        }
        $current_directory = getcwd();
        $generated_file_paths = [];
        $file_infos = iterator_to_array($finder->getIterator());
        foreach ($file_infos as $file_info) {
            // replace "__Name__" with $rectorName
            $new_content = $this->replace_name_variable($rector_name, $file_info->get_contents());
            $new_file_path = $this->replace_name_variable($rector_name, $file_info->get_relative_pathname());
            // remove "phtml" suffix
            $new_file_path = Strings::substring($new_file_path, 0, -strlen('.phtml'));
            File_System::write($current_directory . '/' . $new_file_path, $new_content, null);
            $generated_file_paths[] = $new_file_path;
        }
        $title = sprintf('Skeleton for "%s" rule was created. Now write rule logic to solve your problem', $rector_name);
        $this->symfony_style->title($title);
        $this->symfony_style->listing($generated_file_paths);
        // 2. update autoload-dev in composer.json
        $composer_json_file_path = $current_directory . '/composer.json';
        if (file_exists($composer_json_file_path)) {
            $has_changed = \false;
            $composer_json = Json_File_System::read_file_path($composer_json_file_path);
            if (!isset($composer_json['autoload-dev']['psr-4']['Utils\Rector\\'])) {
                $composer_json['autoload-dev']['psr-4']['Utils\Rector\\'] = 'utils/rector/src';
                $composer_json['autoload-dev']['psr-4']['Utils\Rector\Tests\\'] = 'utils/rector/tests';
                $has_changed = \true;
            }
            if ($has_changed) {
                $this->symfony_style->writeln('We updated "composer.json" autoload-dev to load Rector rules.');
                $this->symfony_style->writeln('Now run "composer dump-autoload" to update paths');
                Json_File_System::write_file($composer_json_file_path, $composer_json);
            }
        }
        $this->symfony_style->new_line(1);
        // 3. update phpunit.xml(.dist) to include rector test suite
        $this->symfony_style->writeln('<fg=green>Run Rector tests via PHPUnit:</>');
        $this->symfony_style->new_line(1);
        $this->symfony_style->writeln('  vendor/bin/phpunit utils/rector/tests');
        $this->symfony_style->new_line(1);
        return Command::SUCCESS;
    }
    private function replace_name_variable(string $rector_name, string $contents): string
    {
        return str_replace('__Name__', $rector_name, $contents);
    }
    private function is_php_unit_attribute_supported(): bool
    {
        return $this->reflection_provider->has_class(Class_Name::DATA_PROVIDER);
    }
}