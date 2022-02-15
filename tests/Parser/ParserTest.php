<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskGoogleFonts\Parser;

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

            // 1,400 => 400italic
            // 0,400 => 400normal
            $fonts = [
                [
                    'url' => 'https://fonts.googleapis.com/css2?family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500&display=swap',
                    'agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:97.0) Gecko/20100101 Firefox/97.0'
                ],
                [
                    'url' => 'https://fonts.googleapis.com/css2?family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500&display=swap',
                    'agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:38.0) Gecko/20100101 Firefox/38.0'
                ]
            ];

            foreach ($fonts as $font) {

                echo('----------------------' . PHP_EOL);
                echo($font['url'] . PHP_EOL);
                echo($font['agent'] . PHP_EOL);
                echo('----------------------' . PHP_EOL);
                echo(PHP_EOL);

                $parser = new GoogleFontsParser($font['url'], $font['agent']);
                $result = $parser->parse();

                foreach ($result as $item) {

                    if (\is_array($item)) {

                        foreach ($item as $subItem) {
                            echo($subItem['key'] . ' : ' . $subItem['value']);
                            echo(PHP_EOL);
                        }

                    }

                    echo(PHP_EOL);
                }

                $this->assertSame(0.0, 0.0);

            }

        } catch (\Exception|TransportExceptionInterface $ex) {
            $this->assertSame('', $ex->getMessage());
        }

    }


}
