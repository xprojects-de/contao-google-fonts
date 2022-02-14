# contao-google-fonts

[![Contao Version](https://img.shields.io/badge/Contao-%5E4.9-orange)](https://contao.org)

## Description

With this extension, Google-Webfonts can be easily downloaded and integrated locally into Contao CMS with just a few
clicks.

see also https://docs.contao.org/manual/de/guides/webfont/

## Manual

1. open menu item "Google fonts" in backend
2. select the font and click "Download"
3. the font is then stored in the file manager with appropriate CSS
4. there are two CSS files. One for "best practice and old browsers" (font_legacy.css) and one for "modern browsers" (
   font.css)
5. select the appropriate CSS from the file manager in the layout
6. last put the font-family to your css
7. ready!! Now you are compliant to DSGVO :-)

## Contao DebugMode
If DebugMode is enabled there are INVALID additional lines inside the CSS like e.g.

```css
<!-- TEMPLATE START: vendor/alpdesk/contao-google-fonts/src/Resources/contao/templates/google_fonts_css.html5 -->
/* ubuntu-v19-300 - latin */
@font-face {
  font-family: 'Ubuntu';
  font-style: normal;
  font-weight: 300;
  font-display: swap;
  src: local(''),
       url('ubuntu-v19-latin-300.woff2') format('woff2'),
       url('ubuntu-v19-latin-300.woff') format('woff');
}
<!-- TEMPLATE END: vendor/alpdesk/contao-google-fonts/src/Resources/contao/templates/google_fonts_css.html5 -->
```

Currently this cannot be disabled. So to download the font you have to be in ProductionMode!

## Other

The feature is only available for administrators.

## Technically

Uses API from https://google-webfonts-helper.herokuapp.com/fonts (https://github.com/majodev/google-webfonts-helper).
Big thanks to majodev (https://github.com/majodev).

## Images

<p><img src="https://x-projects.de/files/alpdesk/contao-google-fonts/1.png" alt=""></p>
<p><img src="https://x-projects.de/files/alpdesk/contao-google-fonts/2.png" alt=""></p>
<p><img src="https://x-projects.de/files/alpdesk/contao-google-fonts/3.png" alt=""></p>