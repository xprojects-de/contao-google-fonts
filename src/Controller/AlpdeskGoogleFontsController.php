<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskGoogleFonts\Controller;

use Alpdesk\AlpdeskGoogleFonts\Library\GoogleFontsApi;
use Contao\BackendUser;
use Contao\Controller;
use Contao\CoreBundle\Controller\AbstractBackendController;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\System;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Contao\Input;

class AlpdeskGoogleFontsController extends AbstractBackendController
{
    private CsrfTokenManagerInterface $csrfTokenManager;
    private string $csrfTokenName;
    protected RouterInterface $router;
    private Security $security;
    private string $projectDir;
    protected ContaoFramework $framework;
    private RequestStack $requestStack;

    public function __construct(
        CsrfTokenManagerInterface $csrfTokenManager,
        string                    $csrfTokenName,
        RouterInterface           $router,
        Security                  $security,
        string                    $projectDir,
        ContaoFramework           $framework,
        RequestStack              $requestStack
    )
    {
        $this->csrfTokenManager = $csrfTokenManager;
        $this->csrfTokenName = $csrfTokenName;
        $this->router = $router;
        $this->security = $security;
        $this->projectDir = $projectDir;
        $this->framework = $framework;
        $this->requestStack = $requestStack;
    }

    private function getCurrentSession(): SessionInterface
    {
        return $this->requestStack->getCurrentRequest()->getSession();
    }

    /**
     * @return void
     */
    private function checkFilter(): void
    {
        if (Input::post('setFilter')) {

            $filterValue = Input::postRaw('filterValue');

            if ($filterValue !== null) {
                $this->getCurrentSession()->set('alpdeskGoogleFonts_filter', $filterValue);
            } else {
                $this->getCurrentSession()->set('alpdeskGoogleFonts_filter', null);
            }

            Controller::redirect($this->router->generate('alpdesk_googlefonts_backend'));

        }

    }

    /**
     * @return void
     */
    private function exportFont(): void
    {
        if (Input::post('exportFont') === '1') {

            $fontId = Input::post('fontId');
            $fontFamily = Input::post('fontFamily');
            $variants = Input::post('fontVariants');
            $subset = Input::post('fontSubsets');
            $version = Input::post('fontVersion');

            $bt_export_unicode = Input::post('export_unicode');

            $this->getCurrentSession()->set('alpdeskGoogleFonts_message', null);

            try {

                if ($variants === null || $subset === null) {
                    throw new \Exception('invalid selection');
                }

                if ($bt_export_unicode !== null) {
                    $response = GoogleFontsApi::downloadAndSaveGoogle($fontId, $fontFamily, $variants, $subset, $version, $this->projectDir);
                } else {
                    $response = GoogleFontsApi::downloadAndSave($fontId, $fontFamily, $variants, $subset, $version, $this->projectDir);
                }

                $this->getCurrentSession()->set('alpdeskGoogleFonts_message', 'Erfolgreich heruntergeladen: ' . $response);

            } catch (\Exception) {
                $this->getCurrentSession()->set('alpdeskGoogleFonts_message', 'Es ist ein Fehler aufgetreten!');
            }

            Controller::redirect($this->router->generate('alpdesk_googlefonts_backend'));

        }

    }

    /**
     * @throws \Exception
     */
    public function endpoint(): Response
    {
        $this->framework->initialize();

        $backendUser = $this->security->getUser();

        $GLOBALS['TL_CSS'][] = 'bundles/alpdeskgooglefonts/css/alpdeskgooglefonts.css';

        if (!$backendUser instanceof BackendUser || !$backendUser->isAdmin) {

            return $this->render('@AlpdeskGoogleFonts/alpdeskgooglefonts_error.html.twig', [
                'error' => 'invalid access',
                'headline' => 'Error'
            ]);

        }

        System::loadLanguageFile('default');

        $this->checkFilter();
        $this->exportFont();

        $error = '';
        $filterValue = '';

        try {

            $fontItems = GoogleFontsApi::list();

            $filterValue = $this->getCurrentSession()->get('alpdeskGoogleFonts_filter');
            if ($filterValue !== null && $filterValue !== '') {

                $filteredFontItems = [];
                foreach ($fontItems as $fontItem) {

                    if (\stripos((string)$fontItem['family'], $filterValue) !== false) {
                        $filteredFontItems[] = $fontItem;
                    }

                }

                $fontItems = $filteredFontItems;

            }

            if (\count($fontItems) <= 0) {
                throw new \Exception($GLOBALS['TL_LANG']['MOD']['alpdeskgooglefonts_invalid_font_data']);
            }

        } catch (\Exception $ex) {

            $error = $ex->getMessage();
            $fontItems = [];

        }

        $message = $this->getCurrentSession()->get('alpdeskGoogleFonts_message');
        $this->getCurrentSession()->set('alpdeskGoogleFonts_message', null);

        if ($message === null) {
            $message = '';
        }

        return $this->render('@AlpdeskGoogleFonts/alpdeskgooglefonts.html.twig', [
            'token' => $this->csrfTokenManager->getToken($this->csrfTokenName)->getValue(),
            'error' => $error,
            'message' => $message,
            'filterValue' => $filterValue,
            'fontItems' => $fontItems,
            'headline' => 'Google Fonts'
        ]);

    }

}
