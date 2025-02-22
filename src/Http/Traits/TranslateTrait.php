<?php

namespace Vis\Builder\Helpers\Traits;

use Illuminate\Support\Facades\App;

trait TranslateTrait
{
    public function t($ident)
    {
        $this->$ident = preg_replace("/[\r\n]+/", "\\r\\n", (string)$this->$ident);
        $this->$ident = str_replace("\t", '\t', (string)$this->$ident);

        $fieldArray = json_decode($this->$ident);
        $lang = App::getLocale();

        return $fieldArray->$lang ?? '';
    }

    public function t_htmlfix($ident)
    {
        $content = nl2br($this->t($ident));

        $content = preg_replace_callback('/<table[^>]*>.*?<\/table>/s', function($match) {
            return preg_replace('/<br\s*\/?>/i', '', $match[0]);
        }, $content);

        $content = str_replace( ["\r\n", "\n\r", "\\r\\n", "\\n\\r","\r", "\n"], '', $content );
        $content = str_replace(['</tr><br /><tr>','</tr><br><tr>','</tr><br> <tr>','</tr><br/> <tr>'], '</tr><tr>', $content);
        $content = str_replace(['</li><br /><li>','</li><br><li>'], '</li><li>', $content);

//--
        $content = $fieldArray->$lang ?? '';
        if($content) {
            // Detect the string encoding
            $encoding = mb_detect_encoding($content);
            // pass it to the DOMDocument constructor
            $doc = new \DOMDocument('', $encoding);

            @$doc->loadHTML('<html><head>'
                . '<meta http-equiv="content-type" content="text/html; charset='
                . $encoding . '"></head><body>' . trim($content) . '</body></html>');

            // extract the components we want
            $nodes = $doc->getElementsByTagName('body')->item(0)->childNodes;
            $html = '';
            $len = $nodes->length;
            for ($i = 0; $i < $len; $i++) {
                $html .= $doc->saveHTML($nodes->item($i));
            }
            return $html;
        }
//--


        return $fieldArray->$lang ?? '';
    }
}
