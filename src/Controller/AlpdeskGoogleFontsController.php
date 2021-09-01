<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskGoogleFonts\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment as TwigEnvironment;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;

class AlpdeskGoogleFontsController extends AbstractController
{
    private TwigEnvironment $twig;
    private CsrfTokenManagerInterface $csrfTokenManager;
    private string $csrfTokenName;
    protected RouterInterface $router;
    private Security $security;
    private string $projectDir;

    public function __construct(TwigEnvironment $twig, CsrfTokenManagerInterface $csrfTokenManager, string $csrfTokenName, RouterInterface $router, Security $security, string $projectDir)
    {
        $this->twig = $twig;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->csrfTokenName = $csrfTokenName;
        $this->router = $router;
        $this->security = $security;
        $this->projectDir = $projectDir;
    }

    /**
     * @throws \Exception
     */
    public function endpoint(): Response
    {
        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/alpdeskgooglefonts/js/alpdeskgooglefonts.js';
        $GLOBALS['TL_CSS'][] = 'bundles/alpdeskgooglefonts/css/alpdeskgooglefonts.css';

        $outputTwig = $this->twig->render('@AlpdeskGoogleFonts/alpdeskgooglefonts.html.twig', [
            'token' => $this->csrfTokenManager->getToken($this->csrfTokenName)->getValue(),
            'hello' => 'hello World'
        ]);

        return new Response($outputTwig);
    }

}
