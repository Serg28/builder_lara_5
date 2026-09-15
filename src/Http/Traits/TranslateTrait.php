<?php

namespace Vis\Builder\Helpers\Traits;

use Illuminate\Support\Facades\App;

trait TranslateTrait
{
    protected $translatedCache = [];

    public function t($ident)
    {
        $lang = App::getLocale();
        $key = "{$ident}_{$lang}";

        return $this->translatedCache[$key] ??= $this->translateField($ident, $lang);
    }

    protected function translateField(string $ident, string $lang): string
    {
        $value = $this->getAttributes()[$ident] ?? $this->{$ident} ?? null;
        $value = preg_replace("/[\r\n]+/", "\\r\\n", (string) $value);
        $value = str_replace("\t", '\t', $value);

        return json_decode($value)->{$lang} ?? '';
    }

    public function t_htmlfix($ident)
    {
        $content = nl2br($this->t($ident));

        $content = preg_replace_callback('/<table[^>]*>.*?<\/table>/s', function($match) {
            return preg_replace('/<br\s*\/?>/i', '', $match[0]);
        }, $content);

        $content = str_replace(["\r\n", "\n\r", "\\r\\n", "\\n\\r", "\r", "\n"], '', $content);
        $content = str_replace(['</tr><br /><tr>', '</tr><br><tr>', '</tr><br> <tr>', '</tr><br/> <tr>'], '</tr><tr>', $content);
        $content = str_replace(['</li><br /><li>', '</li><br><li>'], '</li><li>', $content);

        if ($content) {
            $encoding = mb_detect_encoding($content);
            $doc = new \DOMDocument('', $encoding);

            @$doc->loadHTML('<html><head>'
                . '<meta http-equiv="content-type" content="text/html; charset='
                . $encoding . '"></head><body>' . trim($content) . '</body></html>');

            $nodes = $doc->getElementsByTagName('body')->item(0)->childNodes;
            $html = '';
            $len = $nodes->length;
            for ($i = 0; $i < $len; $i++) {
                $html .= $doc->saveHTML($nodes->item($i));
            }
            return $html;
        }

        return $content;
    }
}
