<?php

namespace App\Support;

use HTMLPurifier;
use HTMLPurifier_Config;

class HtmlSanitizer
{
    private HTMLPurifier $purifier;

    public function __construct()
    {
        $config = HTMLPurifier_Config::createDefault();

        $config->set('Cache.SerializerPath', storage_path('app/htmlpurifier'));
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
        $config->set('HTML.Allowed',
            'p,br,strong,em,u,s,'
            .'a[href|title|target|rel],'
            .'ul,ol,li,'
            .'h1,h2,h3,'
            .'img[src|alt],'
            .'blockquote,'
            .'span[style]'
        );
        $config->set('CSS.AllowedProperties', ['color', 'background-color', 'text-align']);
        $config->set('HTML.TargetBlank', true);
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        // Force rel="noopener" on target=_blank links (tabnabbing defense).
        $config->set('HTML.TargetNoopener', true);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);

        $this->purifier = new HTMLPurifier($config);
    }

    public function clean(string $html): string
    {
        return $this->purifier->purify($html);
    }
}
