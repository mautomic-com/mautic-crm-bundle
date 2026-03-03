<?php

declare(strict_types=1);

namespace MautomicCrmTests\Page\Acceptance;

class DealPage
{
    // URLs
    public static string $URL    = '/s/mautomic/deals';
    public static string $newURL = '/s/mautomic/deals/new';

    // List view
    public static string $newButton = '#new';
    public static string $listTable = '#dealTable';
    public static string $searchBar = '#list-search';

    // Form fields
    public static string $nameField     = '#deal_name';
    public static string $pipelineField = '#deal_pipeline';
    public static string $stageField    = '#deal_stage';
    public static string $amountField   = '#deal_amount';
    public static string $ownerField    = '#deal_owner';

    // Form buttons
    public static string $saveButton   = '#deal_buttons_save_toolbar';
    public static string $cancelButton = '#deal_buttons_cancel_toolbar';

    // Detail view
    public static string $editButton    = '#toolbar a[href*="/edit/"]';
    public static string $dropDown      = '#toolbar .dropdown-toggle';
    public static string $deleteAction  = '.std-toolbar .dropdown-menu a[href*="/delete/"]';

    // Delete confirmation
    public static string $confirmDelete = 'button.btn.btn-danger';

    public static function editURL(int $id): string
    {
        return "/s/mautomic/deals/edit/{$id}";
    }

    public static function viewURL(int $id): string
    {
        return "/s/mautomic/deals/view/{$id}";
    }
}
