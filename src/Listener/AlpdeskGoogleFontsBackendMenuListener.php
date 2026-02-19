<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskGoogleFonts\Listener;

use Contao\CoreBundle\Event\MenuEvent;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Contao\BackendUser;

class AlpdeskGoogleFontsBackendMenuListener
{
    protected RouterInterface $router;
    protected RequestStack $requestStack;
    private Security $security;

    public function __construct(Security $security, RouterInterface $router, RequestStack $requestStack)
    {
        $this->router = $router;
        $this->requestStack = $requestStack;
        $this->security = $security;
    }

    public function __invoke(MenuEvent $event): void
    {
        $backendUser = $this->security->getUser();

        if (!$backendUser instanceof BackendUser || !$backendUser->isAdmin) {
            return;
        }

        $factory = $event->getFactory();
        $tree = $event->getTree();

        if ('mainMenu' === $tree->getName()) {

            $contentNode = $tree->getChild('content');

            $node = $factory
                ->createItem('alpdesk_googlefonts_backend')
                ->setUri($this->router->generate('alpdesk_googlefonts_backend'))
                ->setLabel('Google Fonts')
                ->setLinkAttribute('title', 'Google Fonts')
                ->setLinkAttribute('class', 'alpdesk_googlefonts_backend')
                ->setCurrent($this->requestStack->getCurrentRequest()?->attributes->get('_route') === 'alpdesk_googlefonts_backend');

            $contentNode?->addChild($node);

        }

    }

}
