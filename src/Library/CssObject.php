<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskGoogleFonts\Library;

use Contao\FrontendTemplate;

class CssObject
{
    private ?string $comment = null;
    private ?string $fontFamily = null;
    private ?string $fontStyle = null;
    private ?float $fontWeight = null;
    private ?string $fontStretch = null;
    private ?string $fontDisplay = null;
    private ?string $fontUrl = null;
    private ?string $fontFormat = null;
    private array $unicodeRanges = [];

    /**
     * @return string|null
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * @param string|null $comment
     */
    public function setComment(?string $comment): void
    {
        $this->comment = $comment;
    }

    /**
     * @return string|null
     */
    public function getFontFamily(): ?string
    {
        return $this->fontFamily;
    }

    /**
     * @param string|null $fontFamily
     */
    public function setFontFamily(?string $fontFamily): void
    {
        $this->fontFamily = $fontFamily;
    }

    /**
     * @return string|null
     */
    public function getFontStyle(): ?string
    {
        return $this->fontStyle;
    }

    /**
     * @param string|null $fontStyle
     */
    public function setFontStyle(?string $fontStyle): void
    {
        $this->fontStyle = $fontStyle;
    }

    /**
     * @return float|null
     */
    public function getFontWeight(): ?float
    {
        return $this->fontWeight;
    }

    /**
     * @param float|null $fontWeight
     */
    public function setFontWeight(?float $fontWeight): void
    {
        $this->fontWeight = $fontWeight;
    }

    /**
     * @return string|null
     */
    public function getFontStretch(): ?string
    {
        return $this->fontStretch;
    }

    /**
     * @param string|null $fontStretch
     */
    public function setFontStretch(?string $fontStretch): void
    {
        $this->fontStretch = $fontStretch;
    }


    /**
     * @return string|null
     */
    public function getFontDisplay(): ?string
    {
        return $this->fontDisplay;
    }

    /**
     * @param string|null $fontDisplay
     */
    public function setFontDisplay(?string $fontDisplay): void
    {
        $this->fontDisplay = $fontDisplay;
    }

    /**
     * @return string|null
     */
    public function getFontUrl(): ?string
    {
        return $this->fontUrl;
    }

    /**
     * @param string|null $fontUrl
     */
    public function setFontUrl(?string $fontUrl): void
    {
        $this->fontUrl = $fontUrl;
    }

    /**
     * @return string|null
     */
    public function getFontFormat(): ?string
    {
        return $this->fontFormat;
    }

    /**
     * @param string|null $fontFormat
     */
    public function setFontFormat(?string $fontFormat): void
    {
        $this->fontFormat = $fontFormat;
    }

    /**
     * @return array
     */
    public function getUnicodeRanges(): array
    {
        return $this->unicodeRanges;
    }

    /**
     * @param array $unicodeRanges
     */
    public function setUnicodeRanges(array $unicodeRanges): void
    {
        $this->unicodeRanges = $unicodeRanges;
    }

    /**
     * @param string $value
     * @return void
     */
    public function addUnicodeRange(string $value): void
    {
        $this->unicodeRanges[] = $value;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->getFontFamily() !== null && $this->getFontFamily() !== '' &&
            $this->getFontUrl() !== null && $this->getFontUrl() !== '' &&
            $this->getFontFormat() !== null && $this->getFontFormat() !== '' &&
            $this->getFontWeight() !== null &&
            $this->getFontStyle() !== null && $this->getFontStyle() !== '' &&
            $this->getFontDisplay() !== null && $this->getFontDisplay() !== '';

    }

    /**
     * @param string $path
     * @return void
     * @throws \Exception
     */
    public function downloadFont(string $path): void
    {
        if ($this->isValid() === false) {
            throw new \Exception('invalid font-settings');
        }

        \file_put_contents($path, fopen($this->getFontUrl(), 'rb'));

    }

    /**
     * @param string $localFontPath
     * @return string
     */
    public function generateOutputString(string $localFontPath): string
    {
        $cssTemplateObject = new FrontendTemplate('google_fonts_css_unicode');
        $cssTemplateObject->setDebug(false);
        $cssTemplateObject->fontFamily = $this->getFontFamily();
        $cssTemplateObject->fontStyle = $this->getFontStyle();
        $cssTemplateObject->fontWeight = $this->getFontWeight();
        $cssTemplateObject->fontStretch = $this->getFontStretch();
        $cssTemplateObject->fontUrl = $localFontPath;
        $cssTemplateObject->fontFormat = $this->getFontFormat();
        $cssTemplateObject->fontUnicode = (\count($this->getUnicodeRanges()) > 0 ? \implode(', ', $this->getUnicodeRanges()) : null);

        return $cssTemplateObject->parse() . PHP_EOL;

    }

}