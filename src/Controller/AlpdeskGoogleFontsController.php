<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskGoogleFonts\Controller;

use Alpdesk\AlpdeskGoogleFonts\Library\GoogleFontsApi;
use Contao\BackendUser;
use Contao\Controller;
use Contao\UserModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment as TwigEnvironment;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Contao\Input;

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

    private function exportFont(): void
    {
        if (Input::post('exportFont') === '1') {

            $fontId = Input::post('fontId');
            $variants = Input::post('fontVariants');
            $subset = Input::post('fontSubsets');
            $version = Input::post('fontVersion');

            GoogleFontsApi::downloadAndSave($fontId, $variants, $subset, $version, $this->projectDir);

            Controller::redirect($this->router->generate('alpdesk_googlefonts_backend'));

        }

    }

    /**
     * @throws \Exception
     */
    public function endpoint(): Response
    {
        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/alpdeskgooglefonts/js/alpdeskgooglefonts.js';
        $GLOBALS['TL_CSS'][] = 'bundles/alpdeskgooglefonts/css/alpdeskgooglefonts.css';

        $backendUser = $this->security->getUser();

        if (!$backendUser instanceof BackendUser || !$backendUser->isAdmin) {

            $outputTwig = $this->twig->render('@AlpdeskGoogleFonts/alpdeskgooglefonts_error.html.twig', [
                'errorMessage' => 'invalid access'
            ]);

            return new Response($outputTwig);

        }

        $this->exportFont();

        $error = '';

        try {

            $fontItems = GoogleFontsApi::list();
            if (\count($fontItems) <= 0) {
                throw new \Exception('invalid font data');
            }

        } catch (\Exception $ex) {

            $error = $ex->getMessage();
            $fontItems = [];

        }

        $outputTwig = $this->twig->render('@AlpdeskGoogleFonts/alpdeskgooglefonts.html.twig', [
            'token' => $this->csrfTokenManager->getToken($this->csrfTokenName)->getValue(),
            'error' => $error,
            'fontItems' => $fontItems
        ]);

        return new Response($outputTwig);
    }

}
