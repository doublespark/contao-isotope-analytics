<?php

declare(strict_types=1);

namespace Doublespark\IsotopeAnalyticsBundle\ContaoManager;

use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Config\ConfigPluginInterface;
use Doublespark\IsotopeAnalyticsBundle\DependencyInjection\Compiler\RegisterHookListenersPass;
use Doublespark\IsotopeAnalyticsBundle\IsotopeAnalyticsBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class Plugin implements BundlePluginInterface, ConfigPluginInterface
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

    public function registerContainerConfiguration(LoaderInterface $loader, array $managerConfig): void
    {
        $loader->load(function (ContainerBuilder $container) {
            $container->addCompilerPass(new RegisterHookListenersPass());
        });
    }
}