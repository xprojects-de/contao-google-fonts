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

    private function saveCheckApiKey(BackendUser $backendUser): void
    {
        if (Input::post('saveAPIKey') === '1') {

            $apiKey = Input::post('apiKey');
            if ($apiKey !== null) {

                $user = UserModel::findById((int)$backendUser->id);
                if ($user !== null) {

                    $user->alpdeskgooglefonts_apikey = (string)$apiKey;
                    $user->save();

                }

            }

            Controller::redirect($this->router->generate('alpdesk_googlefonts_backend'));

        }

    }

    private function exportFont(): void
    {
        if (Input::post('saveFontStyle') === '1') {

            $family = Input::post('family');
            $version = Input::post('version');
            $charset = 'latin';
            $selectedFiles = Input::post('selectedFiles');
            $fileKeys = Input::post('fileKeys');
            $fileValues = Input::post('fileValues');

            GoogleFontsApi::downloadAndSave($family, $version, $charset, $selectedFiles, $fileKeys, $fileValues);

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

        $this->saveCheckApiKey($backendUser);
        $this->exportFont();

        $apiKey = ($backendUser->alpdeskgooglefonts_apikey === null ? '' : $backendUser->alpdeskgooglefonts_apikey);

        $error = '';

        try {

            if ($apiKey !== null && $apiKey !== '') {
                $fontItems = GoogleFontsApi::list($apiKey);
            } else {
                $error = 'invalid API-Key';
            }

        } catch (\Exception $ex) {

            $error = $ex->getMessage();
            $fontItems = [];

        }

        $outputTwig = $this->twig->render('@AlpdeskGoogleFonts/alpdeskgooglefonts.html.twig', [
            'token' => $this->csrfTokenManager->getToken($this->csrfTokenName)->getValue(),
            'apiKey' => $apiKey,
            'error' => $error,
            'fontItems' => $fontItems
        ]);

        return new Response($outputTwig);
    }

}
