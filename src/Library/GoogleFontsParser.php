<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskGoogleFonts\Library;

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
    private string $url;
    private string $agent;

    /**
     * @param string $url
     * @param string $agent
     */
    public function __construct(string $url, string $agent)
    {
        $this->url = $url;
        $this->agent = $agent;
    }

    private function getHTTPClient(): HttpClientInterface
    {
        $httpOptions = new HttpOptions();

        $httpOptions->verifyHost(false);
        $httpOptions->verifyPeer(false);

        return HttpClient::create($httpOptions->toArray());
    }

    /**
     * @return array
     * @throws \Exception
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function parse(): array
    {
        $response = $this->getHTTPClient()->request('GET', $this->url, [
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

                $subSet = [];
                foreach ($ruleSet->getRules() as $rule) {

                    if ($rule instanceof Rule) {

                        $ruleValue = $rule->getValue();

                        if ($ruleValue instanceof CSSString) {

                            $subSet[] = [
                                'key' => $rule->getRule(),
                                'value' => $ruleValue->getString()
                            ];

                        } else if ($ruleValue instanceof Size) {

                            $subSet[] = [
                                'key' => $rule->getRule(),
                                'value' => $ruleValue->getSize() . ($ruleValue->getUnit() ?? '')
                            ];

                        } else if ($ruleValue instanceof RuleValueList) {

                            $tmpValue = $this->parseRuleValueList($ruleValue);
                            foreach ($tmpValue as $item) {
                                $subSet[] = $item;
                            }

                        } else {

                            $subSet[] = [
                                'key' => $rule->getRule(),
                                'value' => $rule->getValue()
                            ];

                        }

                    }

                }

                $value[] = $subSet;

            }

        }

        return $value;

    }

    /**
     * @param RuleValueList $list
     * @return array
     */
    private function parseRuleValueList(RuleValueList $list): array
    {
        $value = [];
        foreach ($list->getListComponents() as $rule) {

            if ($rule instanceof Url) {

                $value[] = [
                    'key' => 'URL',
                    'value' => $rule->getURL()->getString()
                ];

            } else if ($rule instanceof CSSFunction) {

                $value[] = [
                    'key' => $rule->getName(),
                    'value' => $rule->getListComponents()[0]->getString()
                ];

            } else if (\is_string($rule)) {

                $value[] = [
                    'key' => 'unicode-range',
                    'value' => $rule
                ];

            }

        }

        return $value;


    }


}