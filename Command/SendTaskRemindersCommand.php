<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Model\NotificationModel;
use MauticPlugin\MautomicCrmBundle\Entity\TaskRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'mautomic:tasks:send-reminders',
    description: 'Send reminder notifications for tasks with due reminders.',
)]
class SendTaskRemindersCommand extends Command
{
    public function __construct(
        private TaskRepository $taskRepository,
        private NotificationModel $notificationModel,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $now   = new \DateTime();
        $tasks = $this->taskRepository->findTasksNeedingReminder($now);

        if (0 === \count($tasks)) {
            $io->info('No task reminders to send.');

            return Command::SUCCESS;
        }

        $sent = 0;

        foreach ($tasks as $task) {
            $owner = $task->getOwner();
            if (null === $owner) {
                $task->setReminderSent(true);
                continue;
            }

            $message = sprintf(
                'Task reminder: "%s" is due %s',
                $task->getTitle(),
                $task->getDueDate()?->format('Y-m-d H:i') ?? 'soon'
            );

            $this->notificationModel->addNotification(
                $message,
                'mautomic_crm',
                false,
                $task->getTitle(),
                'ri-task-line',
                null,
                $owner,
            );

            $task->setReminderSent(true);
            ++$sent;
        }

        $this->entityManager->flush();

        $io->success(sprintf('Sent %d task reminder(s).', $sent));

        return Command::SUCCESS;
    }
}
