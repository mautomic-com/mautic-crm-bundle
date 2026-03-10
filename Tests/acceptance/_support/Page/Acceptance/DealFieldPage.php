<?php

declare(strict_types=1);

namespace MautomicCrmTests\Page\Acceptance;

class DealFieldPage
{
    // URLs
    public static string $URL    = '/s/mautomic-crm/settings/deal-fields';
    public static string $newURL = '/s/mautomic-crm/settings/deal-fields/new';

    // List view
    public static string $newButton = '#new';
    public static string $listTable = '#dealFieldTable';
    public static string $searchBar = '#list-search';

    // Form fields
    public static string $labelField   = '#deal_field_label';
    public static string $typeField    = '#deal_field_type';
    public static string $aliasField   = '#deal_field_alias';
    public static string $groupField   = '#deal_field_group';
    public static string $orderField   = '#deal_field_order';

    // Form buttons
    public static string $saveButton   = '#deal_field_buttons_save_toolbar';
    public static string $cancelButton = '#deal_field_buttons_cancel_toolbar';

    // Detail view / actions
    public static string $editButton   = '#toolbar a[href*="/edit/"]';
    public static string $dropDown     = 'button#core-options';
    public static string $deleteAction = 'a[href*="/delete/"]';

    // Delete confirmation
    public static string $confirmDelete = 'button.btn.btn-danger';

    public static function editURL(int $id): string
    {
        return "/s/mautomic-crm/settings/deal-fields/edit/{$id}";
    }
}
