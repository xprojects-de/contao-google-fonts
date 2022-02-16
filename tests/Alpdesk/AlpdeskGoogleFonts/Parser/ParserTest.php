<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskGoogleFonts\Parser;

use Alpdesk\AlpdeskGoogleFonts\Library\CssObject;
use Alpdesk\AlpdeskGoogleFonts\Library\GoogleFontsParser;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class ParserTest extends TestCase
{
    /**
     * @return void
     */
    public function testParser(): void
    {
        try {

            $fonts = [
                [
                    'fontFamily' => 'Roboto',
                    'fontTypes' => [],
                    'agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:97.0) Gecko/20100101 Firefox/97.0'
                ],
                [
                    'fontFamily' => 'Roboto',
                    'fontTypes' => ['300italic'],
                    'agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:97.0) Gecko/20100101 Firefox/97.0'
                ],
                [
                    'fontFamily' => 'Open Sans',
                    'fontTypes' => ['300', '300italic', '200', '200italic', '400', '400italic', '500', '500italic', '700'],
                    'agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:97.0) Gecko/20100101 Firefox/97.0'
                ],
                [
                    'fontFamily' => 'Open Sans',
                    'fontTypes' => ['300', '300italic', '400', '400italic', '500', '500italic', '700'],
                    'agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:38.0) Gecko/20100101 Firefox/38.0'
                ],
                [
                    'fontFamily' => 'Ubuntu',
                    'fontTypes' => ['300', '300italic', '400', '400italic', '500', '500italic', '700'],
                    'agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:97.0) Gecko/20100101 Firefox/97.0'
                ],
                [
                    'fontFamily' => 'Ubuntu',
                    'fontTypes' => ['300', '300italic', '400', '400italic', '500', '500italic', '700'],
                    'agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:38.0) Gecko/20100101 Firefox/38.0'
                ]
            ];

            foreach ($fonts as $font) {

                $parser = new GoogleFontsParser($font['fontFamily'], $font['agent'], $font['fontTypes']);
                $result = $parser->parse();

                echo('----------------------' . PHP_EOL);
                echo($font['fontFamily'] . PHP_EOL);
                echo($font['agent'] . PHP_EOL);
                echo($parser->getQueryUrl() . PHP_EOL);
                echo('----------------------' . PHP_EOL);
                echo(PHP_EOL);

                foreach ($result as $item) {

                    if ($item instanceof CssObject) {

                        // echo('Comment: ' . $item->getComment() . PHP_EOL);
                        echo('font-family: ' . $item->getFontFamily() . PHP_EOL);
                        echo('font-style: ' . $item->getFontStyle() . PHP_EOL);
                        echo('font-weight: ' . $item->getFontWeight() . PHP_EOL);
                        echo('font-stretch: ' . $item->getFontStretch() . PHP_EOL);
                        echo('font-display: ' . $item->getFontDisplay() . PHP_EOL);
                        echo('font-url: ' . 'url(' . $item->getFontUrl() . ') format(\'' . $item->getFontFormat() . '\');' . PHP_EOL);
                        echo('unicode-range: ' . \implode(', ', $item->getUnicodeRanges()) . PHP_EOL);

                        if (!$item->isValid()) {
                            throw new \Exception('invalid item');
                        }

                        $filename = \time() . '_' . \uniqid('font_', true) . '.' . $item->getFontFormat();
                        $dest = '/Users/benjaminhummel/Documents/Github/contao-google-fonts/tests/tmp/' . $filename;
                        echo('Download font to ' . $dest . PHP_EOL);
                        $item->downloadFont($dest);

                        if (!\file_exists($dest)) {
                            throw new \Exception('error downloading font');
                        }

                    }

                    echo(PHP_EOL);
                }

            }

            $this->assertTrue(true);

        } catch (\Exception|TransportExceptionInterface $ex) {
            $this->assertSame('', $ex->getMessage());
        }

    }


}
