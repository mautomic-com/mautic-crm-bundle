<?php

declare(strict_types=1);

namespace MautomicCrmTests\Page\Acceptance;

class PipelinePage
{
    // URLs
    public static string $URL      = '/s/mautomic/pipelines';
    public static string $newURL   = '/s/mautomic/pipelines/new';

    // List view
    public static string $newButton    = '#new';
    public static string $listTable    = '#pipelineTable';
    public static string $searchBar    = '#list-search';

    // Form fields
    public static string $nameField        = '#pipeline_name';
    public static string $descriptionField = '#pipeline_description';

    // Stage fields (dynamic, use stageFieldSelector())
    public static string $stageNamePrefix  = '#pipeline_stages_';
    public static string $addStageButton   = 'a[data-prototype]';

    // Form buttons
    public static string $saveButton   = '#pipeline_buttons_save_toolbar';
    public static string $cancelButton = '#pipeline_buttons_cancel_toolbar';

    // Detail view
    public static string $editButton   = '#toolbar a[href*="/edit/"]';
    public static string $dropDown     = '#toolbar .dropdown-toggle';
    public static string $deleteAction = '.std-toolbar .dropdown-menu a[href*="/delete/"]';

    // Delete confirmation
    public static string $confirmDelete = 'button.btn.btn-danger';

    public static function route(string $param): string
    {
        return static::$URL.$param;
    }

    public static function editURL(int $id): string
    {
        return "/s/mautomic/pipelines/edit/{$id}";
    }

    public static function viewURL(int $id): string
    {
        return "/s/mautomic/pipelines/view/{$id}";
    }
}
