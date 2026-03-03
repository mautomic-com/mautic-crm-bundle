<?php

declare(strict_types=1);

namespace MautomicCrmTests\Page\Acceptance;

class TaskPage
{
    // URLs
    public static string $URL    = '/s/mautomic/tasks';
    public static string $newURL = '/s/mautomic/tasks/new';

    // List view
    public static string $newButton = '#new';
    public static string $listTable = '#taskTable';
    public static string $searchBar = '#list-search';

    // Form fields
    public static string $titleField       = '#task_title';
    public static string $descriptionField = '#task_description';
    public static string $dealField        = '#task_deal';
    public static string $dueDateField     = '#task_dueDate';
    public static string $statusField      = '#task_status';
    public static string $assignedToField  = '#task_assignedTo';

    // Form buttons
    public static string $saveButton   = '#task_buttons_save_toolbar';
    public static string $cancelButton = '#task_buttons_cancel_toolbar';

    // Detail view
    public static string $editButton   = '#toolbar a[href*="/edit/"]';
    public static string $dropDown     = '#toolbar .dropdown-toggle';
    public static string $deleteAction = '.std-toolbar .dropdown-menu a[href*="/delete/"]';

    // Delete confirmation
    public static string $confirmDelete = 'button.btn.btn-danger';

    public static function editURL(int $id): string
    {
        return "/s/mautomic/tasks/edit/{$id}";
    }

    public static function viewURL(int $id): string
    {
        return "/s/mautomic/tasks/view/{$id}";
    }
}
