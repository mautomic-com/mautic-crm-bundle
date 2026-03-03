<?php

declare(strict_types=1);

namespace MautomicCrmTests\Page\Acceptance;

class NotePage
{
    // URLs (notes are typically accessed from deal detail)
    public static string $URL    = '/s/mautomic/notes';
    public static string $newURL = '/s/mautomic/notes/new';

    // Form fields
    public static string $textField = '#note_text';
    public static string $typeField = '#note_type';
    public static string $dealField = '#note_deal';

    // Form buttons
    public static string $saveButton   = '#note_buttons_save_toolbar';
    public static string $cancelButton = '#note_buttons_cancel_toolbar';

    // Detail view
    public static string $editButton   = '#toolbar a[href*="/edit/"]';
    public static string $dropDown     = 'button#core-options';
    public static string $deleteAction = 'a[href*="/delete/"]';

    // Delete confirmation
    public static string $confirmDelete = 'button.btn.btn-danger';

    public static function editURL(int $id): string
    {
        return "/s/mautomic/notes/edit/{$id}";
    }
}
