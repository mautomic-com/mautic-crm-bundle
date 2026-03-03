<?php

declare(strict_types=1);

namespace MautomicCrmTests;

use Codeception\Actor;

/**
 * Inherited Methods.
 *
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
 */
class AcceptanceTester extends Actor
{
    use _generated\AcceptanceTesterActions;

    public function login(?string $name = null, ?string $password = null): void
    {
        $name ??= getenv('MAUTIC_ADMIN_USER') ?: 'admin';
        $password ??= getenv('MAUTIC_ADMIN_PASSWORD') ?: 'mautic';
        $I = $this;

        if ($I->loadSessionSnapshot('crm_login')) {
            return;
        }

        $I->amOnPage('/s/login');
        $I->waitForElementVisible('#username', 30);
        $I->fillField('#username', $name);
        $I->fillField('#password', $password);
        $I->click('button[type=submit]');
        $I->waitForElement('h1.page-header-title', 30);
        $I->saveSessionSnapshot('crm_login');
    }

    public function hideDebugToolbar(): void
    {
        $this->executeJS(
            "var tb = document.querySelector('.sf-toolbar'); if(tb) tb.style.display='none';"
        );
    }

    public function clickSaveAndClose(string $formName): void
    {
        $selector = "#{$formName}_buttons_save_toolbar";
        $this->scrollTo($selector, 0, -100);
        $this->waitForElementClickable($selector, 15);
        $this->click($selector);
    }

    public function clickCancel(string $formName): void
    {
        $selector = "#{$formName}_buttons_cancel_toolbar";
        $this->scrollTo($selector, 0, -100);
        $this->waitForElementClickable($selector, 15);
        $this->click($selector);
    }

    public function confirmDeletion(): void
    {
        $this->waitForElementVisible('.confirmation-modal button.btn-danger', 10);
        $this->click('.confirmation-modal button.btn-danger');
    }

    /**
     * Select an option in a Chosen.js-enhanced select by setting the value via JS.
     * All <select> elements in Mautic use Chosen.js (native select has display:none).
     * Matches by option text or option value (e.g. 'Select' or 'select' both work).
     */
    public function selectChosenOption(string $selectCssId, string $optionTextOrValue): void
    {
        $this->executeJS(
            'var s = document.querySelector(arguments[0]);'
            ."if(!s) throw new Error('Select not found: '+arguments[0]);"
            .'var t=arguments[1];'
            .'for(var i=0;i<s.options.length;i++){'
            .'  if(s.options[i].text.trim()===t || s.options[i].value===t){'
            .'    s.value=s.options[i].value;'
            ."    jQuery(s).trigger('chosen:updated').trigger('change');"
            .'    return true;'
            .'  }'
            .'}'
            ."throw new Error('Option not found: '+t+' in '+arguments[0]);",
            [$selectCssId, $optionTextOrValue]
        );
    }

    public function waitForPageLoad(int $timeout = 15): void
    {
        $this->waitForElement('h1.page-header-title', $timeout);
        $this->hideDebugToolbar();
    }

    public function seeFlashMessage(string $message): void
    {
        $this->waitForElementVisible('#flashes .alert', 10);
        $this->see($message, '#flashes .alert');
    }

    public function takeNamedScreenshot(string $name): void
    {
        $this->makeScreenshot($name);
    }
}
