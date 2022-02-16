<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskGoogleFonts\Library;

// use Sabberworm\CSS\Comment\Comment;
use Sabberworm\CSS\CSSList\Document;
use Sabberworm\CSS\Parser;
use Sabberworm\CSS\Rule\Rule;
use Sabberworm\CSS\RuleSet\AtRuleSet;
use Sabberworm\CSS\Value\CSSFunction;
use Sabberworm\CSS\Value\CSSString;
use Sabberworm\CSS\Value\RuleValueList;
use Sabberworm\CSS\Value\Size;
use Sabberworm\CSS\Value\URL;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\HttpOptions;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoogleFontsParser
{
    private string $fontFamily;
    private array $fontTypes;

    // e.g. Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:38.0) Gecko/20100101 Firefox/38.0
    // e.g. Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:97.0) Gecko/20100101 Firefox/97.0
    private string $agent;
    private bool $displaySwap = true;

    // e.g. https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500&display=swap
    // e.g. https://fonts.googleapis.com/css2?family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500&display=swap
    private static string $BASE_URL = 'https://fonts.googleapis.com/css2';
    private static string $PREFIX_ITALIC = 'ital';
    private static string $PREFIX_WEIGHT = 'wght';

    private string $queryUrl = '';

    /**
     * @param string $fontFamily
     * @param string $agent
     * @param array $fontTypes
     * @throws \Exception
     */
    public function __construct(string $fontFamily, string $agent, array $fontTypes)
    {
        $this->fontFamily = $fontFamily;
        $this->fontTypes = $fontTypes;
        $this->agent = $agent;

        $this->generateQueryUrl();
    }

    private function getHTTPClient(): HttpClientInterface
    {
        $httpOptions = new HttpOptions();

        $httpOptions->verifyHost(false);
        $httpOptions->verifyPeer(false);

        return HttpClient::create($httpOptions->toArray());
    }

    /**
     * @return void
     * @throws \Exception
     */
    private function generateQueryUrl(): void
    {
        if ($this->fontFamily === '') {
            throw new \Exception('invalid fontFamily');
        }

        $url = self::$BASE_URL . '?family=' . \str_replace(' ', '+', $this->fontFamily);

        $tmpFontTypesNormal = [];
        $tmpFontTypesItalic = [];

        if (\count($this->fontTypes) > 0) {

            foreach ($this->fontTypes as $fontType) {

                if (\stripos($fontType, 'italic') !== false) {

                    if ($fontType === 'italic') {
                        $tmpFontTypesItalic[] = 400;
                    } else {
                        $tmpFontTypesItalic[] = (int)\str_replace('italic', '', $fontType);
                    }


                } else {

                    if ($fontType === 'regular') {
                        $fontType = 400;
                    }

                    $tmpFontTypesNormal[] = (int)$fontType;
                }

            }

        }

        if (\count($tmpFontTypesNormal) > 0 || \count($tmpFontTypesItalic) > 0) {
            $url .= ':' . self::$PREFIX_ITALIC . ',' . self::$PREFIX_WEIGHT . '@';
        }

        if (\count($tmpFontTypesNormal) > 0) {

            \sort($tmpFontTypesNormal, SORT_ASC);
            $url .= '0,' . \implode(';0,', $tmpFontTypesNormal);

        }

        if (\count($tmpFontTypesItalic) > 0) {

            \sort($tmpFontTypesItalic, SORT_ASC);

            if (\count($tmpFontTypesNormal) > 0) {
                $url .= ';';
            }

            $url .= '1,' . \implode(';1,', $tmpFontTypesItalic);

        }

        if ($this->displaySwap) {
            $url .= '&display=swap';
        }

        $this->queryUrl = $url;

    }

    /**
     * @return array
     * @throws \Exception
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function parse(): array
    {
        $response = $this->getHTTPClient()->request('GET', $this->queryUrl, [
            'timeout' => 5,
            'headers' => [
                'Content-Type' => 'text/css; charset=utf-8',
                'User-Agent' => $this->agent
            ]
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception('invalid response code');
        }

        $parser = new Parser($response->getContent());
        $cssDocument = $parser->parse();

        $value = $this->parseCSS($cssDocument);


        if (\count($value) <= 0) {
            throw new \Exception('error at parsing');
        }

        return $value;

    }

    /**
     * @param Document $cssDocument
     * @return array
     */
    private function parseCSS(Document $cssDocument): array
    {
        $value = [];

        foreach ($cssDocument->getAllRuleSets() as $ruleSet) {

            if ($ruleSet instanceof AtRuleSet) {

                $cssObject = new CssObject();

                foreach ($ruleSet->getRules() as $rule) {

                    if ($rule instanceof Rule) {

                        $ruleValue = $rule->getValue();

                        if (
                            $ruleValue instanceof CSSString &&
                            $rule->getRule() === 'font-family'
                        ) {
                            $cssObject->setFontFamily($ruleValue->getString());
                        } else if (
                            \is_string($ruleValue) &&
                            $rule->getRule() === 'font-style'
                        ) {
                            $cssObject->setFontStyle($ruleValue);
                        } else if (
                            $ruleValue instanceof Size &&
                            $rule->getRule() === 'font-weight'
                        ) {
                            $cssObject->setFontWeight($ruleValue->getSize());
                        } else if (
                            $ruleValue instanceof Size &&
                            $rule->getRule() === 'font-stretch'
                        ) {
                            $cssObject->setFontStretch($ruleValue->getSize() . ($ruleValue->getUnit() ?? ''));
                        } else if (
                            \is_string($ruleValue) &&
                            $rule->getRule() === 'font-display'
                        ) {
                            $cssObject->setFontDisplay($ruleValue);
                        } else if (
                            $ruleValue instanceof RuleValueList &&
                            $rule->getRule() === 'unicode-range'
                        ) {

                            foreach ($ruleValue->getListComponents() as $unicodeComponent) {
                                if (\is_string($unicodeComponent)) {
                                    $cssObject->addUnicodeRange($unicodeComponent);
                                }
                            }

                        } else if (
                            $ruleValue instanceof RuleValueList &&
                            $rule->getRule() === 'src'
                        ) {

                            foreach ($ruleValue->getListComponents() as $srcComponent) {

                                if (
                                    $srcComponent instanceof URL
                                ) {
                                    $cssObject->setFontUrl($srcComponent->getURL()->getString());
                                } else if (
                                    $srcComponent instanceof CSSFunction &&
                                    $srcComponent->getName() === 'format'
                                ) {
                                    $cssObject->setFontFormat($srcComponent->getListComponents()[0]->getString());
                                }

                            }

                        }

                    }

                }

                /*foreach ($ruleSet->getComments() as $comment) {

                    if ($comment instanceof Comment && $comment->getLineNo() === 1) {
                        $cssObject->setComment($comment->getComment());
                    }

                }*/

                $value[] = $cssObject;

            }


        }

        return $value;

    }

    /**
     * @return string
     */
    public function getQueryUrl(): string
    {
        return $this->queryUrl;
    }

}