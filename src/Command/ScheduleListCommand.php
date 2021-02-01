<?php

namespace Zenstruck\ScheduleBundle\Command;

use Lorisleiva\CronTranslator\CronParsingException;
use Lorisleiva\CronTranslator\CronTranslator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\Extension\ExtensionHandlerRegistry;
use Zenstruck\ScheduleBundle\Schedule\ScheduleRunner;
use Zenstruck\ScheduleBundle\Schedule\Task;
use Zenstruck\ScheduleBundle\Schedule\Task\CommandTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ScheduleListCommand extends Command
{
    protected static $defaultName = 'schedule:list';

    private $scheduleRunner;
    private $handlerRegistry;

    public function __construct(ScheduleRunner $scheduleRunner, ExtensionHandlerRegistry $handlerRegistry)
    {
        $this->scheduleRunner = $scheduleRunner;
        $this->handlerRegistry = $handlerRegistry;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('List configured scheduled tasks')
            ->addOption('detail', null, null, 'Show detailed task list')
            ->setHelp(<<<EOF
Exit code 0: no issues.
Exit code 1: some issues.
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $schedule = $this->scheduleRunner->buildSchedule();

        if (0 === \count($schedule->all())) {
            throw new \RuntimeException('No scheduled tasks configured.');
        }

        $io = new SymfonyStyle($input, $output);

        $io->title(\sprintf('<info>%d</info> Scheduled Task%s Configured', \count($schedule->all()), \count($schedule->all()) > 1 ? 's' : ''));

        $exit = $input->getOption('detail') ? $this->renderDetail($schedule, $io) : $this->renderTable($schedule, $io);

        $this->renderExtenstions($io, 'Schedule', $schedule->getExtensions());

        $scheduleIssues = \iterator_to_array($this->getScheduleIssues($schedule), false);

        if ($issueCount = \count($scheduleIssues)) {
            $io->warning(\sprintf('%d issue%s with schedule:', $issueCount, $issueCount > 1 ? 's' : ''));

            $exit = 1;
        }

        $this->renderIssues($io, ...$scheduleIssues);

        if (0 === $exit) {
            $io->success('No schedule or task issues.');
        }

        return $exit;
    }

    private function renderDetail(Schedule $schedule, SymfonyStyle $io): int
    {
        $exit = 0;

        foreach ($schedule->all() as $i => $task) {
            $io->section(\sprintf('(%d/%d) %s', $i + 1, \count($schedule->all()), $task));

            $details = [];

            foreach ($task->getContext() as $key => $value) {
                $details[] = [$key => $value];
            }

            $details[] = ['Task ID' => $task->getId()];
            $details[] = ['Task Class' => \get_class($task)];

            $details[] = [$task->getExpression()->isHashed() ? 'Calculated Frequency' : 'Frequency' => $this->renderFrequency($task)];

            if ($task->getExpression()->isHashed()) {
                $details[] = ['Raw Frequency' => $task->getExpression()->getRawValue()];
            }

            $details[] = ['Next Run' => $task->getNextRun()->format('D, M d, Y @ g:i (e O)')];

            $this->renderDefinitionList($io, $details);
            $this->renderExtenstions($io, 'Task', $task->getExtensions());

            $issues = \iterator_to_array($this->getTaskIssues($task), false);

            if ($issueCount = \count($issues)) {
                $io->warning(\sprintf('%d issue%s with this task:', $issueCount, $issueCount > 1 ? 's' : ''));
            }

            $this->renderIssues($io, ...$issues);

            if ($issueCount > 0) {
                $exit = 1;
            }
        }

        return $exit;
    }

    /**
     * BC - Symfony 4.4 added SymfonyStyle::definitionList().
     */
    private function renderDefinitionList(SymfonyStyle $io, array $list): void
    {
        if (\method_exists($io, 'definitionList')) {
            $io->definitionList(...$list);

            return;
        }

        $io->listing(\array_map(
            function(array $line) {
                return \sprintf('<info>%s:</info> %s', \array_keys($line)[0], \array_values($line)[0]);
            },
            $list
        ));
    }

    private function renderTable(Schedule $schedule, SymfonyStyle $io): int
    {
        /** @var \Throwable[] $taskIssues */
        $taskIssues = [];
        $rows = [];

        foreach ($schedule->all() as $task) {
            $issues = \iterator_to_array($this->getTaskIssues($task), false);
            $taskIssues[] = $issues;

            $rows[] = [
                \count($issues) ? "<error>[!] {$task->getType()}</error>" : $task->getType(),
                $this->getHelper('formatter')->truncate($task->getDescription(), 50),
                \count($task->getExtensions()),
                $this->renderFrequency($task),
                $task->getNextRun()->format(\DATE_ATOM),
            ];
        }

        $taskIssues = \array_merge([], ...$taskIssues);

        $io->table(['Type', 'Description', 'Extensions', 'Frequency', 'Next Run'], $rows);

        if ($issueCount = \count($taskIssues)) {
            $io->warning(\sprintf('%d task issue%s:', $issueCount, $issueCount > 1 ? 's' : ''));
        }

        $this->renderIssues($io, ...$taskIssues);

        $io->note('For more details, run php bin/console schedule:list --detail');

        return \count($taskIssues) ? 1 : 0;
    }

    /**
     * @param object[] $extensions
     */
    private function renderExtenstions(SymfonyStyle $io, string $type, array $extensions): void
    {
        if (0 === $count = \count($extensions)) {
            return;
        }

        $io->comment(\sprintf('<info>%d</info> %s Extension%s:', $count, $type, $count > 1 ? 's' : ''));
        $io->listing(\array_map(
            function(object $extension) {
                if (\method_exists($extension, '__toString')) {
                    return \sprintf('%s <comment>(%s)</comment>',
                        \strtr($extension, self::extensionHighlightMap()),
                        \get_class($extension)
                    );
                }

                return \get_class($extension);
            },
            $extensions
        ));
    }

    /**
     * @return \Throwable[]
     */
    private function getScheduleIssues(Schedule $schedule): iterable
    {
        foreach ($schedule->getExtensions() as $extension) {
            try {
                $this->handlerRegistry->handlerFor($extension);
            } catch (\Throwable $e) {
                yield $e;
            }
        }

        // check for duplicated task ids
        $tasks = [];

        foreach ($schedule->all() as $task) {
            $tasks[$task->getId()][] = $task;
        }

        foreach ($tasks as $taskGroup) {
            $count = \count($taskGroup);

            if (1 === $count) {
                continue;
            }

            $task = $taskGroup[0];

            yield new \LogicException(\sprintf('Task "%s" (%s) is duplicated %d times. Make their descriptions unique to fix.', $task, $task->getExpression(), $count));
        }
    }

    private static function extensionHighlightMap(): array
    {
        return [
            Task::SUCCESS => \sprintf('<info>%s</info>', Task::SUCCESS),
            Schedule::SUCCESS => \sprintf('<info>%s</info>', Schedule::SUCCESS),
            Task::FAILURE => \sprintf('<error>%s</error>', Task::FAILURE),
            Schedule::FAILURE => \sprintf('<error>%s</error>', Schedule::FAILURE),
        ];
    }

    /**
     * @return \Throwable[]
     */
    private function getTaskIssues(Task $task): iterable
    {
        try {
            $this->scheduleRunner->runnerFor($task);
        } catch (\Throwable $e) {
            yield $e;
        }

        if ($task instanceof CommandTask && $application = $this->getApplication()) {
            try {
                $definition = $task->createCommand($application)->getDefinition();
                $definition->addOptions($application->getDefinition()->getOptions());
                $input = new StringInput($task->getArguments());

                $input->bind($definition);
            } catch (\Throwable $e) {
                yield $e;
            }
        }

        foreach ($task->getExtensions() as $extension) {
            try {
                $this->handlerRegistry->handlerFor($extension);
            } catch (\Throwable $e) {
                yield $e;
            }
        }
    }

    private function renderIssues(SymfonyStyle $io, \Throwable ...$issues): void
    {
        foreach ($issues as $issue) {
            if (OutputInterface::VERBOSITY_NORMAL === $io->getVerbosity()) {
                $io->error($issue->getMessage());

                continue;
            }

            // BC - Symfony 4.4 deprecated Application::renderException()
            if (\method_exists($this->getApplication(), 'renderThrowable')) {
                $this->getApplication()->renderThrowable($issue, $io);
            } else {
                $this->getApplication()->renderException($issue, $io);
            }
        }
    }

    private function renderFrequency(Task $task): string
    {
        $expression = (string) $task->getExpression();

        if (!\class_exists(CronTranslator::class)) {
            return $expression;
        }

        try {
            return \sprintf('%s (%s)', $expression, CronTranslator::translate($expression));
        } catch (CronParsingException $e) {
            return $expression;
        }
    }
}
