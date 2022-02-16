<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskGoogleFonts\Library;

use Contao\Automator;
use Contao\File;
use Contao\Folder;
use Contao\FrontendTemplate;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\HttpOptions;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use ZipArchive;

class GoogleFontsApi
{
    private static string $API = 'https://google-webfonts-helper.herokuapp.com';
    private static string $FONTS_FOLDER = 'files/googlefonts';

    private static array $AGENTS = [
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:97.0) Gecko/20100101 Firefox/97.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:38.0) Gecko/20100101 Firefox/38.0'
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
     * @param bool $isGoogleDownload
     * @return string
     * @throws \Exception
     */
    public static function downloadAndSave(string $fontId, string $fontFamily, array $variants, array $subset, string $version, string $rootDir, bool $isGoogleDownload): string
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

                if ($isGoogleDownload === true) {

                    $googleFontCss = '';

                    $modernGoogleFonts = (new GoogleFontsParser($fontFamily, self::$AGENTS[0], $variants))->parse();
                    $googleFontCss .= self::generateCSSStringFromGoogleFont($modernGoogleFonts, $fontId, $rootDir . '/' . self::$FONTS_FOLDER . '/' . $folderName);
                    $legacyGoogleFonts = (new GoogleFontsParser($fontFamily, self::$AGENTS[1], $variants))->parse();
                    $googleFontCss .= self::generateCSSStringFromGoogleFont($legacyGoogleFonts, $fontId, $rootDir . '/' . self::$FONTS_FOLDER . '/' . $folderName);

                    if ($googleFontCss !== '') {

                        $unicodeCssFile = new File(self::$FONTS_FOLDER . '/' . $folderName . '/font.css');
                        $unicodeCssFile->write($googleFontCss);
                        $unicodeCssFile->close();

                    }

                } else {

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

                    $css = self::generateCss($fontId, $variants, $subset, $version);

                    $legacyCssFile = new File(self::$FONTS_FOLDER . '/' . $folderName . '/font_legacy.css');
                    $legacyCssFile->write($css[0]);
                    $legacyCssFile->close();

                    $modernCssFile = new File(self::$FONTS_FOLDER . '/' . $folderName . '/font.css');
                    $modernCssFile->write($css[1]);
                    $modernCssFile->close();

                }

                return self::$FONTS_FOLDER . '/' . $folderName;

            }

            throw new \Exception('invalid inputs');

        } catch (\Throwable $tr) {
            throw new \Exception($tr->getMessage());
        }

    }

    /**
     * @param string $fontId
     * @param array $variants
     * @param array $subset
     * @param string $version
     * @return string[]
     */
    private static function generateCss(string $fontId, array $variants, array $subset, string $version): array
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
     * @param string $fontId
     * @param string $downloadDir
     * @return string
     * @throws \Exception
     */
    private static function generateCSSStringFromGoogleFont(array $objectCSSData, string $fontId, string $downloadDir): string
    {
        $value = '';

        if (\count($objectCSSData) > 0) {

            foreach ($objectCSSData as $item) {

                if (($item instanceof CssObject) && $item->isValid()) {

                    $filename = \time() . '_' . \uniqid('font_' . $fontId . '_', true) . '.' . $item->getFontFormat();
                    $item->downloadFont($downloadDir . '/' . $filename);

                    $value .= $item->generateOutputString($filename);

                }

            }

        }

        return $value;

    }


}