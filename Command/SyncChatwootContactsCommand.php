<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Command;

use Doctrine\DBAL\Connection;
use MauticPlugin\MautomicCrmBundle\Integration\ContactSyncDryRunAnalyzer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'mautomic:chatwoot:contacts:sync',
    description: 'Dry-run the Mautic to Chatwoot contact sync without changing either system.',
)]
final class SyncChatwootContactsCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ContactSyncDryRunAnalyzer $analyzer,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io      = new SymfonyStyle($input, $output);
        $summary = $this->analyzer->analyze(iterator_to_array($this->contactPhoneRows(), false));

        $io->title('Chatwoot contact sync dry-run');
        $io->table(['Result', 'Count'], [
            ['Mautic contacts with phone data', $summary['contacts_with_phone']],
            ['Valid international-phone contacts', $summary['eligible_contacts']],
            ['Contacts without a valid international phone', $summary['invalid_contacts']],
            ['Duplicate normalized-phone groups', $summary['duplicate_groups']],
            ['Contacts blocked by duplicates', $summary['duplicate_contacts']],
            ['Contacts blocked by multiple valid phones', $summary['multiple_phones']],
            ['Safe outbound candidates', $summary['safe_candidates']],
        ]);
        $io->success('Dry-run complete. No data was changed and no Chatwoot requests were made.');

        return Command::SUCCESS;
    }

    /** @return iterable<array{id: mixed, phone: mixed, mobile: mixed}> */
    private function contactPhoneRows(): iterable
    {
        $query = $this->connection->createQueryBuilder()
            ->select('id', 'phone', 'mobile')
            ->from(MAUTIC_TABLE_PREFIX.'leads')
            ->where("phone IS NOT NULL AND phone <> ''")
            ->orWhere("mobile IS NOT NULL AND mobile <> ''")
            ->orderBy('id', 'ASC');

        return $query->executeQuery()->iterateAssociative();
    }
}
