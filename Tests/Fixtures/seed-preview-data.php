<?php

declare(strict_types=1);
$loader = require __DIR__.'/../../../../autoload.php';

// Mautic 7 boot
$_SERVER['APP_ENV']             = 'prod';
$_SERVER['APP_DEBUG']           = '0';
$_SERVER['MAUTIC_TABLE_PREFIX'] = $_SERVER['MAUTIC_TABLE_PREFIX'] ?? '';

$kernel = new AppKernel('prod', false);
$kernel->boot();

$container = $kernel->getContainer();
$em        = $container->get('doctrine.orm.entity_manager');

echo "Seeding preview data...\n";

// ─── Helper ──────────────────────────────────────────────────────────

$adminUser = $em->getRepository(Mautic\UserBundle\Entity\User::class)->findOneBy(['username' => 'admin']);
if (!$adminUser) {
    echo "ERROR: Admin user not found. Run mautic:install first.\n";
    exit(1);
}

$now = new DateTime();

function randomDate(string $from, string $to): DateTime
{
    $start = strtotime($from);
    $end   = strtotime($to);

    return (new DateTime())->setTimestamp(mt_rand($start, $end));
}

// ─── Companies ───────────────────────────────────────────────────────

echo "  Creating companies...\n";

$companies   = [];
$companyData = [
    ['companyname' => 'Acme Corp',      'companyemail' => 'info@acme.example.com',     'companycity' => 'Warsaw',   'companycountry' => 'Poland'],
    ['companyname' => 'TechStart GmbH', 'companyemail' => 'hello@techstart.example.de', 'companycity' => 'Berlin',   'companycountry' => 'Germany'],
    ['companyname' => 'Nordic Solutions', 'companyemail' => 'contact@nordic.example.se', 'companycity' => 'Stockholm', 'companycountry' => 'Sweden'],
];

$companyModel = $container->get('mautic.lead.model.company');
foreach ($companyData as $cd) {
    $company = new Mautic\LeadBundle\Entity\Company();
    foreach ($cd as $field => $value) {
        $company->addUpdatedField($field, $value);
    }
    $companyModel->saveEntity($company);
    $companies[] = $company;
}

// ─── Contacts ────────────────────────────────────────────────────────

echo "  Creating contacts...\n";

$contacts    = [];
$contactData = [
    ['firstname' => 'Jan',     'lastname' => 'Kowalski',   'email' => 'jan.kowalski@acme.example.com'],
    ['firstname' => 'Anna',    'lastname' => 'Nowak',      'email' => 'anna.nowak@acme.example.com'],
    ['firstname' => 'Piotr',   'lastname' => 'Wiśniewski', 'email' => 'piotr.w@techstart.example.de'],
    ['firstname' => 'Maria',   'lastname' => 'Zielińska',  'email' => 'maria.z@techstart.example.de'],
    ['firstname' => 'Tomasz',  'lastname' => 'Lewandowski', 'email' => 'tomasz.l@nordic.example.se'],
    ['firstname' => 'Katarzyna', 'lastname' => 'Kamińska', 'email' => 'katarzyna.k@nordic.example.se'],
    ['firstname' => 'Michał',  'lastname' => 'Szymański',  'email' => 'michal.sz@acme.example.com'],
    ['firstname' => 'Agnieszka', 'lastname' => 'Woźniak',  'email' => 'agnieszka.w@techstart.example.de'],
    ['firstname' => 'Robert',  'lastname' => 'Dąbrowski',  'email' => 'robert.d@nordic.example.se'],
    ['firstname' => 'Monika',  'lastname' => 'Kozłowska',  'email' => 'monika.k@acme.example.com'],
];

$leadModel = $container->get('mautic.lead.model.lead');
foreach ($contactData as $i => $cd) {
    $lead = new Mautic\LeadBundle\Entity\Lead();
    $lead->setOwner($adminUser);
    foreach ($cd as $field => $value) {
        $lead->addUpdatedField($field, $value);
    }
    $leadModel->saveEntity($lead);

    // Link to company (round-robin)
    $companyModel->addLeadToCompany($companies[$i % count($companies)], $lead);

    $contacts[] = $lead;
}

// ─── Pipelines & Stages ─────────────────────────────────────────────

echo "  Creating pipelines and stages...\n";

$pipelineModel = $container->get('mautic.mautomic_crm.model.pipeline');

// Pipeline 1: Enterprise Sales
$p1 = new MauticPlugin\MautomicCrmBundle\Entity\Pipeline();
$p1->setName('Enterprise Sales');
$p1->setDescription('High-value B2B deals');
$p1->setIsPublished(true);
$p1->setIsDefault(true);

$p1Stages = [
    ['name' => 'Qualification',  'order' => 1, 'probability' => 10, 'type' => 'open'],
    ['name' => 'Discovery',      'order' => 2, 'probability' => 25, 'type' => 'open'],
    ['name' => 'Proposal',       'order' => 3, 'probability' => 50, 'type' => 'open'],
    ['name' => 'Negotiation',    'order' => 4, 'probability' => 75, 'type' => 'open'],
    ['name' => 'Won',            'order' => 5, 'probability' => 100, 'type' => 'won'],
    ['name' => 'Lost',           'order' => 6, 'probability' => 0,  'type' => 'lost'],
];

foreach ($p1Stages as $sd) {
    $stage = new MauticPlugin\MautomicCrmBundle\Entity\Stage();
    $stage->setName($sd['name']);
    $stage->setOrder($sd['order']);
    $stage->setProbability($sd['probability']);
    $stage->setType($sd['type']);
    $stage->setPipeline($p1);
    $p1->addStage($stage);
}

$pipelineModel->saveEntity($p1);

// Pipeline 2: SMB Quick Close
$p2 = new MauticPlugin\MautomicCrmBundle\Entity\Pipeline();
$p2->setName('SMB Quick Close');
$p2->setDescription('Small and medium business — fast cycle');
$p2->setIsPublished(true);
$p2->setIsDefault(false);

$p2Stages = [
    ['name' => 'Lead In',      'order' => 1, 'probability' => 20, 'type' => 'open'],
    ['name' => 'Demo Booked',  'order' => 2, 'probability' => 50, 'type' => 'open'],
    ['name' => 'Proposal Sent', 'order' => 3, 'probability' => 70, 'type' => 'open'],
    ['name' => 'Closed Won',   'order' => 4, 'probability' => 100, 'type' => 'won'],
    ['name' => 'Closed Lost',  'order' => 5, 'probability' => 0,  'type' => 'lost'],
];

foreach ($p2Stages as $sd) {
    $stage = new MauticPlugin\MautomicCrmBundle\Entity\Stage();
    $stage->setName($sd['name']);
    $stage->setOrder($sd['order']);
    $stage->setProbability($sd['probability']);
    $stage->setType($sd['type']);
    $stage->setPipeline($p2);
    $p2->addStage($stage);
}

$pipelineModel->saveEntity($p2);

// Refresh to get IDs
$em->refresh($p1);
$em->refresh($p2);

$p1StageEntities = $p1->getStages()->toArray();
$p2StageEntities = $p2->getStages()->toArray();

// Sort by order
usort($p1StageEntities, fn ($a, $b) => $a->getOrder() <=> $b->getOrder());
usort($p2StageEntities, fn ($a, $b) => $a->getOrder() <=> $b->getOrder());

// ─── Deals ───────────────────────────────────────────────────────────

echo "  Creating deals...\n";

$dealModel = $container->get('mautic.mautomic_crm.model.deal');

$dealData = [
    // Enterprise pipeline deals
    ['name' => 'Acme Annual License',     'amount' => '45000.00', 'pipeline' => $p1, 'stageIdx' => 3, 'contactIdx' => 0, 'companyIdx' => 0, 'closeDate' => '+30 days'],
    ['name' => 'TechStart Platform Deal', 'amount' => '28000.00', 'pipeline' => $p1, 'stageIdx' => 2, 'contactIdx' => 2, 'companyIdx' => 1, 'closeDate' => '+45 days'],
    ['name' => 'Nordic Expansion',        'amount' => '62000.00', 'pipeline' => $p1, 'stageIdx' => 1, 'contactIdx' => 4, 'companyIdx' => 2, 'closeDate' => '-10 days'],
    ['name' => 'Acme Renewal 2026',       'amount' => '38000.00', 'pipeline' => $p1, 'stageIdx' => 4, 'contactIdx' => 1, 'companyIdx' => 0, 'closeDate' => '-5 days'],
    ['name' => 'TechStart Add-On',        'amount' => '12000.00', 'pipeline' => $p1, 'stageIdx' => 0, 'contactIdx' => 3, 'companyIdx' => 1, 'closeDate' => '+90 days'],
    ['name' => 'Nordic Data Migration',   'amount' => '8500.00',  'pipeline' => $p1, 'stageIdx' => 5, 'contactIdx' => 5, 'companyIdx' => 2, 'closeDate' => '-15 days'],
    ['name' => 'Acme Consulting Bundle',  'amount' => '15000.00', 'pipeline' => $p1, 'stageIdx' => 2, 'contactIdx' => 6, 'companyIdx' => 0, 'closeDate' => '-2 days'],

    // SMB pipeline deals
    ['name' => 'Kowalski Starter Pack',   'amount' => '2500.00',  'pipeline' => $p2, 'stageIdx' => 1, 'contactIdx' => 0, 'companyIdx' => 0, 'closeDate' => '+14 days'],
    ['name' => 'Wiśniewski Pro License',  'amount' => '4800.00',  'pipeline' => $p2, 'stageIdx' => 2, 'contactIdx' => 2, 'companyIdx' => 1, 'closeDate' => '+7 days'],
    ['name' => 'Dąbrowski Quick Start',   'amount' => '1900.00',  'pipeline' => $p2, 'stageIdx' => 0, 'contactIdx' => 8, 'companyIdx' => 2, 'closeDate' => '+21 days'],
    ['name' => 'Kozłowska Team Plan',     'amount' => '3200.00',  'pipeline' => $p2, 'stageIdx' => 3, 'contactIdx' => 9, 'companyIdx' => 0, 'closeDate' => '-3 days'],
    ['name' => 'Szymański Upgrade',       'amount' => '5500.00',  'pipeline' => $p2, 'stageIdx' => 4, 'contactIdx' => 6, 'companyIdx' => 0, 'closeDate' => '-10 days'],
];

$deals = [];
$conn  = $em->getConnection();
foreach ($dealData as $dd) {
    $stages = $dd['pipeline'] === $p1 ? $p1StageEntities : $p2StageEntities;

    $deal = new MauticPlugin\MautomicCrmBundle\Entity\Deal();
    $deal->setName($dd['name']);
    $deal->setAmount($dd['amount']);
    $deal->setCurrency('PLN');
    $deal->setPipeline($dd['pipeline']);
    $deal->setStage($stages[$dd['stageIdx']]);
    $deal->setContact($contacts[$dd['contactIdx']]);
    $deal->setCompany($companies[$dd['companyIdx']]);
    $deal->setOwner($adminUser);
    $deal->setCloseDate(new DateTime($dd['closeDate']));
    $deal->setIsPublished(true);
    $dealModel->saveEntity($deal);

    // DealModel::saveEntity() resets stage to first-in-pipeline on new deals — fix via SQL
    $targetStage = $stages[$dd['stageIdx']];
    $conn->executeStatement(
        'UPDATE mautomic_deals SET stage_id = :stageId WHERE id = :dealId',
        ['stageId' => $targetStage->getId(), 'dealId' => $deal->getId()]
    );

    $deals[] = $deal;
}

// ─── Tasks ───────────────────────────────────────────────────────────

echo "  Creating tasks...\n";

$taskModel = $container->get('mautic.mautomic_crm.model.task');

$taskData = [
    ['title' => 'Send proposal to Acme',           'deal' => 0, 'contact' => 0, 'status' => 'open',      'priority' => 'high',   'due' => '+2 days'],
    ['title' => 'Schedule demo with TechStart',     'deal' => 1, 'contact' => 2, 'status' => 'open',      'priority' => 'normal', 'due' => '+5 days'],
    ['title' => 'Follow up on Nordic proposal',     'deal' => 2, 'contact' => 4, 'status' => 'open',      'priority' => 'urgent', 'due' => '-1 days'],  // overdue
    ['title' => 'Prepare renewal contract',         'deal' => 3, 'contact' => 1, 'status' => 'open',      'priority' => 'high',   'due' => '-3 days'],  // overdue
    ['title' => 'Qualify TechStart lead',           'deal' => 4, 'contact' => 3, 'status' => 'completed', 'priority' => 'normal', 'due' => '-7 days'],
    ['title' => 'Update pricing for Wiśniewski',    'deal' => 8, 'contact' => 2, 'status' => 'open',      'priority' => 'normal', 'due' => '+3 days'],
    ['title' => 'Send welcome pack to Kozłowska',   'deal' => 10, 'contact' => 9, 'status' => 'open',      'priority' => 'low',    'due' => '+10 days'],
    ['title' => 'Quarterly pipeline review',        'deal' => null, 'contact' => null, 'status' => 'open', 'priority' => 'normal', 'due' => '+14 days'],
];

foreach ($taskData as $td) {
    $task = new MauticPlugin\MautomicCrmBundle\Entity\Task();
    $task->setTitle($td['title']);
    $task->setStatus($td['status']);
    $task->setPriority($td['priority']);
    $task->setDueDate(new DateTime($td['due']));
    $task->setOwner($adminUser);
    $task->setIsPublished(true);

    if (null !== $td['deal']) {
        $task->setDeal($deals[$td['deal']]);
    }
    if (null !== $td['contact']) {
        $task->setContact($contacts[$td['contact']]);
    }

    $taskModel->saveEntity($task);
}

// ─── Notes ───────────────────────────────────────────────────────────

echo "  Creating notes...\n";

$noteModel = $container->get('mautic.mautomic_crm.model.note');

$noteData = [
    ['text' => 'Initial call went well. Decision maker is the CTO. Budget confirmed for Q2.',    'type' => 'call',    'deal' => 0, 'contact' => 0],
    ['text' => 'Sent product comparison document and case study from similar industry.',           'type' => 'email',   'deal' => 0, 'contact' => 0],
    ['text' => 'Met with technical team. They need API integration with their ERP system.',        'type' => 'meeting', 'deal' => 1, 'contact' => 2],
    ['text' => 'Pricing negotiation ongoing. They want 15% volume discount.',                      'type' => 'general', 'deal' => 3, 'contact' => 1],
    ['text' => 'Called to discuss renewal terms. Happy with service, wants multi-year option.',     'type' => 'call',    'deal' => 3, 'contact' => 1],
    ['text' => 'Demo scheduled for Friday. Prepare custom dashboard for their use case.',          'type' => 'general', 'deal' => 7, 'contact' => 0],
    ['text' => 'Sent proposal with three tiers. Waiting for feedback by end of week.',             'type' => 'email',   'deal' => 8, 'contact' => 2],
    ['text' => 'Quick intro call. They found us via a conference talk. Very interested.',           'type' => 'call',    'deal' => 9, 'contact' => 8],
    ['text' => 'Closed! Signed annual contract. Onboarding starts next Monday.',                   'type' => 'general', 'deal' => 10, 'contact' => 9],
    ['text' => 'Lost — went with competitor on price. Keep relationship warm for next year.',       'type' => 'general', 'deal' => 5, 'contact' => 5],
];

foreach ($noteData as $nd) {
    $note = new MauticPlugin\MautomicCrmBundle\Entity\Note();
    $note->setText($nd['text']);
    $note->setType($nd['type']);
    $note->setDeal($deals[$nd['deal']]);
    $note->setContact($contacts[$nd['contact']]);
    $note->setIsPublished(true);
    $noteModel->saveEntity($note);
}

// ─── Summary ─────────────────────────────────────────────────────────

echo "\n";
echo "Seed data created:\n";
echo '  Companies:  '.count($companyData)."\n";
echo '  Contacts:   '.count($contactData)."\n";
echo "  Pipelines:  2\n";
echo '  Stages:     '.(count($p1Stages) + count($p2Stages))."\n";
echo '  Deals:      '.count($dealData)."\n";
echo '  Tasks:      '.count($taskData)."\n";
echo '  Notes:      '.count($noteData)."\n";
echo "\nDone!\n";
