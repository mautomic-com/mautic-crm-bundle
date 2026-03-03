<?php

declare(strict_types=1);

namespace MautomicCrmTests\Helper;

use Codeception\Module;

class CrmDataHelper extends Module
{
    public function uniqueName(string $prefix = 'E2E'): string
    {
        return sprintf('%s_%s_%s', $prefix, date('His'), substr(uniqid(), -4));
    }
}
