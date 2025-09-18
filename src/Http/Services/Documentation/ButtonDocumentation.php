<?php

namespace Vis\Builder\Services\Documentation;

use Illuminate\Contracts\View\View;
use Vis\Builder\Interfaces\Button;
use Vis\Builder\Services\ButtonBase;
use Vis\Builder\Definitions\Traits\HasDocumentation;

class ButtonDocumentation extends ButtonBase implements Button
{
    public function show(): View
    {
        $button = null;
        $definition = $this->listing->getDefinition();
        if (in_array(HasDocumentation::class, class_uses_recursive($definition))) {
            $url = $definition->getDocumentationUrl();

            if ($url) {
                $button = [
                    'link' => $url,
                    'attributes' => ['target' => '_blank'],
                    'icon' => 'fa fa-question-circle',
                    'caption' => 'Документация',
                ];
            }
        }

        return view('admin::tb.button', compact('button'));
    }
}

