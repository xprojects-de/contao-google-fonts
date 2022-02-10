<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskGoogleFonts\Library;

use Contao\File;
use Contao\Folder;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\HttpOptions;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use ZipArchive;

class GoogleFontsApi
{
    private static string $API = 'https://google-webfonts-helper.herokuapp.com';
    private static string $FONTS_FOLDER = 'files/googlefonts';

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
     * @param array $variants
     * @param array $subset
     * @param string $version
     * @param string $rootDir
     * @return void
     * @throws \Exception
     */
    public static function downloadAndSave(string $fontId, array $variants, array $subset, string $version, string $rootDir): void
    {
        try {

            if ($fontId !== '' && \count($variants) > 0 && \count($subset) >= 0) {

                $fontsGlobalFolder = new Folder(self::$FONTS_FOLDER);
                if (!$fontsGlobalFolder->isUnprotected()) {
                    $fontsGlobalFolder->unprotect();
                }

                $folderName = $fontId . '_' . \time();
                new Folder(self::$FONTS_FOLDER . '/' . $folderName);

                $fileName = $fontId . '.zip';
                $dest = $rootDir . '/' . self::$FONTS_FOLDER . '/' . $folderName . '/' . $fileName;
                $url = self::$API . '/api/fonts/' . $fontId . '?download=zip&subsets=' . \implode(',', $subset) . '&formats=eot,woff,woff2,svg,ttf&variants=' . \implode(',', $variants);

                \file_put_contents($dest, fopen($url, 'rb'));

                $zip = new ZipArchive();

                if ($zip->open($dest) === true) {

                    $zip->extractTo($rootDir . '/' . self::$FONTS_FOLDER . '/' . $folderName);
                    $zip->close();

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

            foreach ($subset as $subsetItem) {

                $style = 'normal';
                if (empty($variant) || \strpos('italic', $variant) !== false) {
                    $style = 'italic';
                }

                if ($variant === 'regular' || $variant === 'italic') {
                    $fontWeight = 400;
                } else {
                    $fontWeight = (int)$variant;
                }

                $fontName = \ucfirst($fontId);

                $legacyCss .= "
/* $fontId-$version-$variant - $subsetItem */
@font-face {
  font-family: '$fontName';
  font-style: $style;
  font-weight: $fontWeight;
  src: url('$fontId-$version-$subsetItem-$variant.eot'); /* IE9 Compat Modes */
  src: local(''),
       url('$fontId-$version-$subsetItem-$variant.eot?#iefix') format('embedded-opentype'), /* IE6-IE8 */
       url('$fontId-$version-$subsetItem-$variant.woff2') format('woff2'), /* Super Modern Browsers */
       url('$fontId-$version-$subsetItem-$variant.woff') format('woff'), /* Modern Browsers */
       url('$fontId-$version-$subsetItem-$variant.ttf') format('truetype'), /* Safari, Android, iOS */
       url('$fontId-$version-$subsetItem-$variant.svg#$fontName') format('svg'); /* Legacy iOS */
}\n";

                $modernCss .= "
/* $fontId-$version-$variant - $subsetItem */
@font-face {
  font-family: '$fontName';
  font-style: $style;
  font-weight: $fontWeight;
  src: local(''),
       url('$fontId-$version-$subsetItem-$variant.woff2') format('woff2'), /* Chrome 26+, Opera 23+, Firefox 39+ */
       url('$fontId-$version-$subsetItem-$variant.woff') format('woff'); /* Chrome 6+, Firefox 3.6+, IE 9+, Safari 5.1+ */
}\n";
            }

        }

        return [$legacyCss, $modernCss];

    }


}