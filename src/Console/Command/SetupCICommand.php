<?php

declare (strict_types=1);
namespace Rector\Console\Command;

use Rector\Git\Repository_Helper;
use Rector_Prefix202603\Nette\Utils\File_System;
use Rector_Prefix202603\Ondra_M\Ci_Detector\Ci_Detector;
use Rector_Prefix202603\Symfony\Component\Console\Command\Command;
use Rector_Prefix202603\Symfony\Component\Console\Input\Input_Interface;
use Rector_Prefix202603\Symfony\Component\Console\Output\Output_Interface;
use Rector_Prefix202603\Symfony\Component\Console\Style\Symfony_Style;
use function sprintf;
final class Setup_Ci_Command extends Command
{
    /**
     * @readonly
     */
    private Symfony_Style $symfony_style;
    public function __construct(Symfony_Style $symfony_style)
    {
        $this->symfony_style = $symfony_style;
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->set_name('setup-ci');
        $this->set_description('Add CI workflow to let Rector work for you');
    }
    protected function execute(Input_Interface $input, Output_Interface $output): int
    {
        // detect current CI
        $ci = $this->resolve_current_ci();
        if ($ci === Ci_Detector::CI_GITLAB) {
            return $this->handle_gitlab_ci();
        }
        if ($ci === Ci_Detector::CI_GITHUB_ACTIONS) {
            return $this->handle_github_actions();
        }
        $note_message = sprintf('Only GitHub and GitLab are currently supported.%s Contribute your CI template to Rector to make this work: %s', "\n", 'https://github.com/rectorphp/rector-src/');
        $this->symfony_style->note($note_message);
        return self::SUCCESS;
    }
    /**
     * @return CiDetector::CI_*|null
     */
    private function resolve_current_ci(): ?string
    {
        if (file_exists(getcwd() . '/.github')) {
            return Ci_Detector::CI_GITHUB_ACTIONS;
        }
        if (file_exists(getcwd() . '/.gitlab-ci.yml')) {
            return Ci_Detector::CI_GITLAB;
        }
        return null;
    }
    private function add_github_actions_workflow(string $current_repository, string $target_workflow_file_path): void
    {
        $workflow_template = File_System::read(__DIR__ . '/../../../templates/rector-github-action-check.yaml');
        $workflow_contents = strtr($workflow_template, ['__CURRENT_REPOSITORY__' => $current_repository]);
        File_System::write($target_workflow_file_path, $workflow_contents, null);
        $this->symfony_style->new_line();
        $this->symfony_style->success('The ".github/workflows/rector.yaml" file was added');
        $this->symfony_style->writeln('<comment>2 more steps to run Rector in CI:</comment>');
        $this->symfony_style->new_line();
        $this->symfony_style->writeln('1) Generate token with "repo" scope:' . \PHP_EOL . 'https://github.com/settings/tokens/new');
        $this->symfony_style->new_line();
        $repository_new_secrets_link = sprintf('https://github.com/%s/settings/secrets/actions/new', $current_repository);
        $this->symfony_style->writeln('2) Add the token to Action secrets as "ACCESS_TOKEN":' . \PHP_EOL . $repository_new_secrets_link);
    }
    private function add_gitlab_file(string $target_gitlab_file_path): void
    {
        $gitlab_template = File_System::read(__DIR__ . '/../../../templates/rector-gitlab-check.yaml');
        File_System::write($target_gitlab_file_path, $gitlab_template, null);
        $this->symfony_style->new_line();
        $this->symfony_style->success('The "gitlab/rector.yaml" file was added');
        $this->symfony_style->new_line();
        $this->symfony_style->writeln('1) Register it in your ".gitlab-ci.yml" file:' . \PHP_EOL . 'include:' . \PHP_EOL . '    - local: gitlab/rector.yaml');
    }
    /**
     * @return self::SUCCESS
     */
    private function handle_gitlab_ci(): int
    {
        // add snippet in the end of file or include it?
        $ci_rector_file_path = getcwd() . '/gitlab/rector.yaml';
        if (file_exists($ci_rector_file_path)) {
            $response = $this->symfony_style->ask('The "gitlab/rector.yaml" workflow already exists. Overwrite it?', 'Yes');
            if (!in_array($response, ['y', 'yes', 'Yes'], \true)) {
                $this->symfony_style->note('Nothing changed');
                return self::SUCCESS;
            }
        }
        $this->add_gitlab_file($ci_rector_file_path);
        return self::SUCCESS;
    }
    /**
     * @return self::SUCCESS|self::FAILURE
     */
    private function handle_github_actions(): int
    {
        $rector_workflow_file_path = getcwd() . '/.github/workflows/rector.yaml';
        if (file_exists($rector_workflow_file_path)) {
            $response = $this->symfony_style->ask('The "rector.yaml" workflow already exists. Overwrite it?', 'Yes');
            if (!in_array($response, ['y', 'yes', 'Yes'], \true)) {
                $this->symfony_style->note('Nothing changed');
                return self::SUCCESS;
            }
        }
        $current_repository = Repository_Helper::resolve_github_repository_name(getcwd());
        if ($current_repository === null) {
            $this->symfony_style->error('Current repository name could not be resolved');
            return self::FAILURE;
        }
        $this->add_github_actions_workflow($current_repository, $rector_workflow_file_path);
        return self::SUCCESS;
    }
}