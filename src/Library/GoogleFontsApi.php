<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskGoogleFonts\Library;

use Contao\Automator;
use Contao\Environment;
use Contao\File;
use Contao\Folder;
use Contao\FrontendTemplate;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\HttpOptions;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use ZipArchive;

class GoogleFontsApi
{
    private static string $API = 'https://gwfh.mranftl.com';
    private static string $FONTS_FOLDER = 'files/googlefonts';

    // The order is important! Always set modern fonts (woff2) at least
    // @TODO don´t know if it´´ necessary to download also for Mac and NT
    private static array $AGENTS = [
        // 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:38.0) Gecko/20100101 Firefox/38.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:38.0) Gecko/20100101 Firefox/38.0',
        // 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:91.0) Gecko/20100101 Firefox/91.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:91.0) Gecko/20100101 Firefox/91.0',
    ];

    private static function getHTTPClient(): HttpClientInterface
    {
        $httpOptions = new HttpOptions();

        $httpOptions->verifyHost(false);
        $httpOptions->verifyPeer(false);

        return HttpClient::create($httpOptions->toArray());
    }

    /**
     * @return array
     * @throws \Exception
     */
    public static function list(): array
    {
        try {

            $response = self::getHTTPClient()->request('GET', self::$API . '/api/fonts', ['timeout' => 5]);
            if ($response->getStatusCode() === 200) {
                return \json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
            }

            throw new \Exception('invalid statusCode');


        } catch (\Throwable $tr) {
            throw new \Exception($tr->getMessage());
        }

    }

    /**
     * @param string $fontId
     * @param string $fontFamily
     * @param array $variants
     * @param array $subset
     * @param string $version
     * @param string $rootDir
     * @return string
     * @throws \Exception
     */
    public static function downloadAndSave(string $fontId, string $fontFamily, array $variants, array $subset, string $version, string $rootDir): string
    {
        try {

            if ($fontId !== '' && \count($variants) > 0 && \count($subset) >= 0) {

                $fontsGlobalFolder = new Folder(self::$FONTS_FOLDER);
                if (!$fontsGlobalFolder->isUnprotected()) {

                    $fontsGlobalFolder->unprotect();
                    (new Automator())->generateSymlinks();

                }

                $folderName = $fontId . '_' . $version . '_' . (new \DateTime())->format('Ymd-His');
                new Folder(self::$FONTS_FOLDER . '/' . $folderName);

                $fileName = $fontId . '.zip';
                $dest = $rootDir . '/' . self::$FONTS_FOLDER . '/' . $folderName . '/' . $fileName;
                $url = self::$API . '/api/fonts/' . $fontId . '?download=zip&subsets=' . \implode(',', $subset) . '&formats=eot,woff,woff2,svg,ttf&variants=' . \implode(',', $variants);

                \file_put_contents($dest, fopen($url, 'rb'));

                $zip = new ZipArchive();

                if ($zip->open($dest) === true) {

                    $zip->extractTo($rootDir . '/' . self::$FONTS_FOLDER . '/' . $folderName);
                    $zip->close();

                    // @TODO downloaded fonts should be synchronized

                } else {
                    throw new \Exception('Unzipped Process failed');
                }

                $css = self::generateCss($fontId, $fontFamily, $variants, $subset, $version);

                $legacyCssFile = new File(self::$FONTS_FOLDER . '/' . $folderName . '/font_legacy.css');
                $legacyCssFile->write($css[0]);
                $legacyCssFile->close();

                $modernCssFile = new File(self::$FONTS_FOLDER . '/' . $folderName . '/font.css');
                $modernCssFile->write($css[1]);
                $modernCssFile->close();


                return self::$FONTS_FOLDER . '/' . $folderName;

            }

            throw new \Exception('invalid inputs');

        } catch (\Throwable $tr) {
            throw new \Exception($tr->getMessage());
        }

    }

    /**
     * @param string $fontId
     * @param string $fontFamily
     * @param array $variants
     * @param array $subset
     * @param string $version
     * @param string $rootDir
     * @return string
     * @throws \Exception
     */
    public static function downloadAndSaveGoogle(string $fontId, string $fontFamily, array $variants, array $subset, string $version, string $rootDir): string
    {
        try {

            if ($fontId !== '' && \count($variants) > 0 && \count($subset) >= 0) {

                $fontsGlobalFolder = new Folder(self::$FONTS_FOLDER);
                if (!$fontsGlobalFolder->isUnprotected()) {

                    $fontsGlobalFolder->unprotect();
                    (new Automator())->generateSymlinks();

                }

                $folderName = $fontId . '_' . $version . '_' . (new \DateTime())->format('Ymd-His');
                $currentFolder = new Folder(self::$FONTS_FOLDER . '/' . $folderName);

                $googleFontCss = '';
                $queryUrl = '';

                foreach (self::$AGENTS as $agent) {

                    $agentFontObject = new GoogleFontsParser($fontFamily, $agent, $variants);
                    $googleFontCss .= self::generateCSSStringFromGoogleFont($agentFontObject->parse(), $currentFolder, $rootDir . '/' . self::$FONTS_FOLDER . '/' . $folderName, $agent);

                    if ($queryUrl === '') {
                        $queryUrl = $agentFontObject->getQueryUrl();
                    }

                }

                if ($googleFontCss !== '') {

                    $unicodeCssFile = new File(self::$FONTS_FOLDER . '/' . $folderName . '/font.css');
                    $unicodeCssFile->write($googleFontCss);
                    $unicodeCssFile->close();

                    $returnValue = self::$FONTS_FOLDER . '/' . $folderName . '<br>';
                    $returnValue .= '<small>';
                    $returnValue .= '<a href="' . $queryUrl . '" target="_blank">' . $queryUrl . '</a>';
                    $returnValue .= ' => ';
                    $returnValue .= '<a href="' . Environment::get('base') . $currentFolder->path . '/font.css' . '" target="_blank">' . Environment::get('base') . $currentFolder->path . '/font.css' . '</a>';
                    $returnValue .= '</small>';

                    return $returnValue;

                }

            }

            throw new \Exception('invalid inputs');

        } catch (\Throwable $tr) {
            throw new \Exception($tr->getMessage());
        }

    }

    /**
     * @param string $fontId
     * @param string $fontFamily
     * @param array $variants
     * @param array $subset
     * @param string $version
     * @return string[]
     */
    private static function generateCss(string $fontId, string $fontFamily, array $variants, array $subset, string $version): array
    {
        $legacyCss = '';
        $modernCss = '';

        foreach ($variants as $variant) {

            $style = 'normal';
            if (\stripos($variant, 'italic') !== false) {
                $style = 'italic';
            }

            if ($variant === 'regular' || $variant === 'italic') {
                $fontWeight = 400;
            } else {
                $fontWeight = (int)$variant;
            }

            $fontName = \ucfirst($fontId);

            if (\count($subset) > 0) {
                // Don´t know why the charset cam in reversed order
                $subset = \array_reverse($subset);
            }
            $subsetItem = \implode('_', $subset);

            $legacyCssTemplateObject = new FrontendTemplate('google_fonts_css_legacy');
            $legacyCssTemplateObject->setDebug(false);
            $legacyCssTemplateObject->fontId = $fontId;
            $legacyCssTemplateObject->fontFamily = $fontFamily;
            $legacyCssTemplateObject->version = $version;
            $legacyCssTemplateObject->variant = $variant;
            $legacyCssTemplateObject->subsetItem = $subsetItem;
            $legacyCssTemplateObject->fontName = $fontName;
            $legacyCssTemplateObject->fontWeight = $fontWeight;
            $legacyCssTemplateObject->fontStyle = $style;

            $legacyCss .= $legacyCssTemplateObject->parse() . PHP_EOL;

            $cssTemplateObject = new FrontendTemplate('google_fonts_css');
            $cssTemplateObject->setDebug(false);
            $cssTemplateObject->fontId = $fontId;
            $cssTemplateObject->fontFamily = $fontFamily;
            $cssTemplateObject->version = $version;
            $cssTemplateObject->variant = $variant;
            $cssTemplateObject->subsetItem = $subsetItem;
            $cssTemplateObject->fontName = $fontName;
            $cssTemplateObject->fontWeight = $fontWeight;
            $cssTemplateObject->fontStyle = $style;

            $modernCss .= $cssTemplateObject->parse() . PHP_EOL;

        }

        return [$legacyCss, $modernCss];

    }

    /**
     * @param array $objectCSSData
     * @param Folder $currentFolder
     * @param string $downloadDir
     * @param string $agent
     * @return string
     * @throws \Exception
     */
    private static function generateCSSStringFromGoogleFont(array $objectCSSData, Folder $currentFolder, string $downloadDir, string $agent): string
    {
        $value = '';

        if (\count($objectCSSData) > 0) {

            foreach ($objectCSSData as $item) {

                if (($item instanceof CssObject) && $item->isValid()) {

                    $pathInfo = $item->pathInfoFromUrl();
                    $filename = $pathInfo['filename'] . '.' . $item->getFontFormat();

                    $currentFontFile = new File($currentFolder->path . '/' . $filename);
                    if (!$currentFontFile->exists()) {
                        $item->downloadFont($downloadDir . '/' . $filename);
                    }

                    $checkFontFile = new File($currentFolder->path . '/' . $filename);
                    if (!$checkFontFile->exists()) {
                        throw new \Exception('error downloading font');
                    }

                    $item->setComment($agent);
                    $value .= $item->generateOutputString($filename);

                }

            }

        }

        return $value;

    }


}
