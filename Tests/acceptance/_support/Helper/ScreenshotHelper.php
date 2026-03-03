<?php

declare(strict_types=1);

namespace MautomicCrmTests\Helper;

use Codeception\Module;
use Codeception\TestInterface;

class ScreenshotHelper extends Module
{
    private int $stepCounter = 0;

    public function _before(TestInterface $test): void
    {
        $this->stepCounter = 0;
    }

    public function takeStepScreenshot(string $label): void
    {
        ++$this->stepCounter;
        $paddedStep = str_pad((string) $this->stepCounter, 2, '0', STR_PAD_LEFT);
        $safeName   = preg_replace('/[^a-zA-Z0-9_-]/', '_', $label);

        /** @var \Codeception\Module\WebDriver $webDriver */
        $webDriver = $this->getModule('WebDriver');
        $webDriver->makeScreenshot("{$paddedStep}_{$safeName}");
    }
}
