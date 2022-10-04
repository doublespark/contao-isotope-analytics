<?php

declare(strict_types=1);

namespace Doublespark\IsotopeAnalyticsBundle\ContaoManager;

use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Doublespark\IsotopeAnalyticsBundle\IsotopeAnalyticsBundle;

class Plugin implements BundlePluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(IsotopeAnalyticsBundle::class)->setLoadAfter(['isotope'])
        ];
    }
}