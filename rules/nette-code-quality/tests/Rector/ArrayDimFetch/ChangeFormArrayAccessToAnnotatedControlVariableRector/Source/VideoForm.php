<?php

declare(strict_types=1);

namespace Rector\NetteCodeQuality\Tests\Rector\ArrayDimFetch\ChangeFormArrayAccessToAnnotatedControlVariableRector\Source;

use Nette\Application\UI\Form;

final class VideoForm extends Form
{
    public function __construct()
    {
        $this->addCheckboxList('video');
    }
}
