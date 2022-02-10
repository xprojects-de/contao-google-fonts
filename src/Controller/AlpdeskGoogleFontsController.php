<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskGoogleFonts\Controller;

use Alpdesk\AlpdeskGoogleFonts\Library\GoogleFontsApi;
use Contao\BackendUser;
use Contao\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
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
    private SessionInterface $session;

    public function __construct(TwigEnvironment $twig, CsrfTokenManagerInterface $csrfTokenManager, string $csrfTokenName, RouterInterface $router, Security $security, string $projectDir, SessionInterface $session)
    {
        $this->twig = $twig;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->csrfTokenName = $csrfTokenName;
        $this->router = $router;
        $this->security = $security;
        $this->projectDir = $projectDir;
        $this->session = $session;
    }

    /**
     * @return void
     */
    private function exportFont(): void
    {
        if (Input::post('exportFont') === '1') {

            $fontId = Input::post('fontId');
            $variants = Input::post('fontVariants');
            $subset = Input::post('fontSubsets');
            $version = Input::post('fontVersion');

            $this->session->set('alpdeskGoogleFonts_message', null);

            try {

                if ($variants === null || $subset === null) {
                    throw new \Exception('invalid selection');
                }

                $path = GoogleFontsApi::downloadAndSave($fontId, $variants, $subset, $version, $this->projectDir);
                $this->session->set('alpdeskGoogleFonts_message', 'Erfolgreich heruntergeladen: ' . $path);

            } catch (\Exception $ex) {
                $this->session->set('alpdeskGoogleFonts_message', 'Es ist ein Fehler aufgetreten!');
            }

            Controller::redirect($this->router->generate('alpdesk_googlefonts_backend'));

        }

    }

    /**
     * @throws \Exception
     */
    public function endpoint(): Response
    {
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

        $message = $this->session->get('alpdeskGoogleFonts_message');
        $this->session->set('alpdeskGoogleFonts_message', null);

        if ($message === null) {
            $message = '';
        }

        $outputTwig = $this->twig->render('@AlpdeskGoogleFonts/alpdeskgooglefonts.html.twig', [
            'token' => $this->csrfTokenManager->getToken($this->csrfTokenName)->getValue(),
            'error' => $error,
            'message' => $message,
            'fontItems' => $fontItems
        ]);

        return new Response($outputTwig);
    }

}
