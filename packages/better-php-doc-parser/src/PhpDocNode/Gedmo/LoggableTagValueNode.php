<?php

declare(strict_types=1);

namespace Rector\BetterPhpDocParser\PhpDocNode\Gedmo;

use Gedmo\Mapping\Annotation\Loggable;
use Rector\BetterPhpDocParser\PhpDocNode\AbstractTagValueNode;

final class LoggableTagValueNode extends AbstractTagValueNode
{
    /**
     * @var string
     */
    public const SHORT_NAME = '@Gedmo\Loggable';

    /**
     * @var string
     */
    public const CLASS_NAME = Loggable::class;

    /**
     * @var string|null
     */
    private $logEntryClass;

    public function __construct(?string $logEntryClass)
    {
        $this->logEntryClass = $logEntryClass;
    }

    public function __toString(): string
    {
        $contentItems = [];

        if ($this->logEntryClass !== null) {
            $contentItems['logEntryClass'] = $this->logEntryClass;
        }

        return $this->printContentItems($contentItems);
    }
}
