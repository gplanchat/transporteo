<?php

declare(strict_types=1);

namespace Akeneo\PimMigration\Domain\DestinationPimDownload;

use Akeneo\PimMigration\Domain\SourcePimDetection\SourcePim;

/**
 * Interface to define contract about downloading the pim.
 *
 * @author    Anael Chardan <anael.chardan@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 */
interface DestinationPimDownloader
{
    public function download(SourcePim $pim, string $projectName): string;
}
