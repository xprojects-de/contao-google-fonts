<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskGoogleFonts\ContaoManager;

use Alpdesk\AlpdeskGoogleFonts\AlpdeskGoogleFontsBundle;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouteCollection;

class Plugin implements BundlePluginInterface, RoutingPluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [BundleConfig::create(AlpdeskGoogleFontsBundle::class)->setLoadAfter([ContaoCoreBundle::class])];
    }

    /**
     * @param LoaderResolverInterface $resolver
     * @param KernelInterface $kernel
     * @return RouteCollection|null
     * @throws \Exception
     */
    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel): ?RouteCollection
    {
        $file = __DIR__ . '/../Resources/config/routes.yml';
        return $resolver->resolve($file)->load($file);
    }

}
