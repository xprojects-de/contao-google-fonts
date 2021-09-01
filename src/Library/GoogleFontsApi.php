<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskGoogleFonts\Library;

use Contao\Folder;
use Contao\StringUtil;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\HttpOptions;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoogleFontsApi
{
    private static string $API = 'https://www.googleapis.com/webfonts/v1/webfonts?&sort=alpha&key=';
    private static string $FONTS_FOLDER = 'files/googlefonts';

    private static function getHTTPClient(): HttpClientInterface
    {
        $httpOptions = new HttpOptions();

        $httpOptions->verifyHost(false);
        $httpOptions->verifyPeer(false);

        return HttpClient::create($httpOptions->toArray());
    }

    /**
     * @param string $apiKey
     * @return array
     * @throws \Exception
     */
    public static function list(string $apiKey): array
    {
        try {
            $httpClient = self::getHTTPClient();

            $response = $httpClient->request('GET', self::$API . $apiKey, ['timeout' => 2.5]);
            if ($response->getStatusCode() === 200) {

                $headers = $response->getHeaders();
                $content = $response->getContent();

                if ($headers !== null && $content !== null && \array_key_exists('content-type', $headers) && \count($headers['content-type']) > 0 && $content !== '') {

                    if (\strtolower($headers['content-type'][0]) === 'application/json; charset=utf-8') {

                        $data = \json_decode($content, true);
                        if (\is_array($data) && \count($data) > 0) {

                            if (\array_key_exists('items', $data) && \is_array($data['items']) && \count($data['items']) > 0) {

                                $items = [];

                                foreach ($data['items'] as $item) {

                                    if (\array_key_exists('subsets', $item) && \is_array($item['subsets'])) {

                                        if (\in_array('latin', $item['subsets'])) {
                                            $items[] = $item;
                                        }

                                    }

                                }

                                return $items;

                            } else {
                                throw new \Exception('invalid data items');
                            }

                        } else {
                            throw new \Exception('invalid data');
                        }

                    } else {
                        throw new \Exception('invalid contentType');
                    }

                } else {
                    throw new \Exception('invalid response');
                }

            } else {
                throw new \Exception('invalid statusCode');
            }

        } catch (\Throwable $tr) {
            throw new \Exception($tr->getMessage());
        }

    }

    public static function downloadAndSave(string $family, string $version, string $charset, array $selectedFiles, array $fileKeys, array $fileValues)
    {
        try {

            if (\count($selectedFiles) > 0 && \count($fileKeys) >= \count($selectedFiles) && \count($fileValues) >= \count($selectedFiles) && \count($fileKeys) === \count($fileValues)) {

                $filesToDownload = [];

                foreach ($selectedFiles as $selectedFile) {

                    $counter = 0;
                    foreach ($fileKeys as $fileKey) {

                        if ($selectedFile === $fileKey) {

                            $filesToDownload[] = [
                                'type' => $fileKey,
                                'url' => $fileValues[$counter]
                            ];

                            break;

                        }

                        $counter++;

                    }

                }

                if (\count($filesToDownload) > 0) {

                    $fontsFolder = new Folder(self::$FONTS_FOLDER);
                    if (!$fontsFolder->isUnprotected()) {
                        $fontsFolder->unprotect();
                    }

                    $fontName = StringUtil::generateAlias($family . '-' . $version);
                    $fontFolder = new Folder(self::$FONTS_FOLDER . '/' . $fontName);
                    if (!$fontFolder->isUnprotected()) {
                        $fontFolder->unprotect();
                    }

                }


            }

        } catch (\Throwable $tr) {

        }

    }



}