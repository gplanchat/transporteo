<?php

declare(strict_types=1);

namespace Akeneo\PimMigration\Domain\Command;

use Akeneo\PimMigration\Domain\Pim\Pim;
use Akeneo\PimMigration\Domain\Pim\PimConnection;

/**
 * Console helper which known where are located the pims to execute command on them.
 *
 * @author    Anael Chardan <anael.chardan@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 */
class ChainedConsole implements Console
{
    /** @var Console[] */
    private $consoles = [];

    public function execute(Command $command, Pim $pim): CommandResult
    {
        return $this->get($pim->getConnection())->execute($command, $pim);
    }

    public function addConsole(Console $console): void
    {
        $this->consoles[] = $console;
    }

    /**
     * @throws \InvalidArgumentException when th
     */
    protected function get(PimConnection $connection): Console
    {
        foreach ($this->consoles as $console) {
            if ($console->supports($connection)) {
                return $console;
            }
        }

        throw new \InvalidArgumentException('The connection is not supported by any consoles');
    }

    public function supports(PimConnection $connection): bool
    {
        return false;
    }
}
