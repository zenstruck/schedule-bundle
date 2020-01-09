<?php

namespace Zenstruck\ScheduleBundle\Command;

use Lorisleiva\CronTranslator\CronTranslator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\Extension;
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
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $schedule = $this->scheduleRunner->buildSchedule();

        if (0 === \count($schedule->all())) {
            throw new \RuntimeException('No scheduled tasks configured.');
        }

        $io = new SymfonyStyle($input, $output);

        $io->title(\sprintf('<info>%d</info> Scheduled Tasks Configured', \count($schedule->all())));

        $exit = $input->getOption('detail') ? $this->renderDetail($schedule, $io) : $this->renderTable($schedule, $io);

        $this->renderExtenstions($io, 'Schedule', $schedule->getExtensions());

        $scheduleIssues = \iterator_to_array($this->getScheduleIssues($schedule));

        if ($issueCount = \count($scheduleIssues)) {
            $io->warning(\sprintf('%d issue%s with schedule:', $issueCount, $issueCount > 1 ? 's' : ''));

            $exit = 1;
        }

        $this->renderIssues($io, ...$scheduleIssues);

        return $exit;
    }

    private function renderDetail(Schedule $schedule, SymfonyStyle $io): int
    {
        $exit = 0;

        foreach ($schedule->all() as $i => $task) {
            $io->section(\sprintf('(%d/%d) %s: %s', $i + 1, \count($schedule->all()), $task->getType(), $task));

            if ($task instanceof CommandTask && $arguments = $task->getArguments()) {
                $io->comment("Arguments: <comment>{$task->getArguments()}</comment>");
            }

            $io->definitionList(
                ['Class' => \get_class($task)],
                ['Frequency' => $this->renderFrequency($task)],
                ['Next Run' => $task->getNextRun()->format('D, M d, Y @ g:i (e O)')]
            );

            $this->renderExtenstions($io, 'Task', $task->getExtensions());

            $issues = \iterator_to_array($this->getTaskIssues($task));

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

    private function renderTable(Schedule $schedule, SymfonyStyle $io): int
    {
        /** @var \Throwable[] $taskIssues */
        $taskIssues = [];
        $rows = [];

        foreach ($schedule->all() as $task) {
            $issues = \iterator_to_array($this->getTaskIssues($task));
            $taskIssues[] = $issues;

            $rows[] = [
                \count($issues) ? "<error>[!] {$task->getType()}</error>" : $task->getType(),
                $this->getHelper('formatter')->truncate($task, 50),
                \count($task->getExtensions()),
                $this->renderFrequency($task),
                $task->getNextRun()->format(DATE_ATOM),
            ];
        }

        $taskIssues = \array_merge([], ...$taskIssues);

        $io->table(['Type', 'Task', 'Extensions', 'Frequency', 'Next Run'], $rows);

        if ($issueCount = \count($taskIssues)) {
            $io->warning(\sprintf('%d task issue%s:', $issueCount, $issueCount > 1 ? 's' : ''));
        }

        $this->renderIssues($io, ...$taskIssues);

        return \count($taskIssues) ? 1 : 0;
    }

    /**
     * @param Extension[] $extensions
     */
    private function renderExtenstions(SymfonyStyle $io, string $type, array $extensions): void
    {
        if (0 === $count = \count($extensions)) {
            return;
        }

        $io->comment(\sprintf('<info>%d</info> %s Extension%s:', $count, $type, $count > 1 ? 's' : ''));
        $io->listing(\array_map(
            function (Extension $extension) {
                return \sprintf('%s <comment>(%s)</comment>',
                    \strtr($extension, self::extensionHighlightMap()),
                    \get_class($extension)
                );
            },
            $extensions
        ));
    }

    /**
     * @return \Throwable[]
     */
    private function getScheduleIssues(Schedule $schedule): \Generator
    {
        foreach ($schedule->getExtensions() as $extension) {
            try {
                $this->handlerRegistry->handlerFor($extension);
            } catch (\Throwable $e) {
                yield $e;
            }
        }
    }

    private static function extensionHighlightMap(): array
    {
        return [
            Extension::TASK_SUCCESS => \sprintf('<info>%s</info>', Extension::TASK_SUCCESS),
            Extension::SCHEDULE_SUCCESS => \sprintf('<info>%s</info>', Extension::SCHEDULE_SUCCESS),
            Extension::TASK_FAILURE => \sprintf('<error>%s</error>', Extension::TASK_FAILURE),
            Extension::SCHEDULE_FAILURE => \sprintf('<error>%s</error>', Extension::SCHEDULE_FAILURE),
        ];
    }

    /**
     * @return \Throwable[]
     */
    private function getTaskIssues(Task $task): \Generator
    {
        try {
            $this->scheduleRunner->runnerFor($task);
        } catch (\Throwable $e) {
            yield $e;
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

            $this->getApplication()->renderThrowable($issue, $io);
        }
    }

    private function renderFrequency(Task $task): string
    {
        if (!\class_exists(CronTranslator::class)) {
            return $task->getExpression();
        }

        return CronTranslator::translate($task->getExpression())." ({$task->getExpression()})";
    }
}
