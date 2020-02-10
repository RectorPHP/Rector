<?php

declare(strict_types=1);

namespace Rector\Utils\PHPStanStaticTypeMapperChecker\Command;

use PHPStan\Type\NonexistentParentClassType;
use Rector\Console\Command\AbstractCommand;
use Rector\Console\Shell;
use Rector\PHPStanStaticTypeMapper\Contract\TypeMapperInterface;
use Rector\Utils\PHPStanStaticTypeMapperChecker\Finder\PHPStanTypeClassFinder;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symplify\PackageBuilder\Console\Command\CommandNaming;

final class CheckStaticTypeMappersCommand extends AbstractCommand
{
    /**
     * @var SymfonyStyle
     */
    private $symfonyStyle;

    /**
     * @var TypeMapperInterface[]
     */
    private $typeMappers = [];

    /**
     * @var PHPStanTypeClassFinder
     */
    private $phpStanTypeClassFinder;

    /**
     * @param TypeMapperInterface[] $typeMappers
     */
    public function __construct(
        array $typeMappers,
        SymfonyStyle $symfonyStyle,
        PHPStanTypeClassFinder $phpStanTypeClassFinder
    ) {
        $this->typeMappers = $typeMappers;
        $this->symfonyStyle = $symfonyStyle;
        $this->phpStanTypeClassFinder = $phpStanTypeClassFinder;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(CommandNaming::classToName(self::class));
        $this->setDescription('[Dev] check PHPStan types to TypeMappers');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $missingNodeClasses = $this->getMissingNodeClasses();
        if ($missingNodeClasses === []) {
            $this->symfonyStyle->success('All PHPStan Types are covered by TypeMapper');

            return Shell::CODE_SUCCESS;
        }

        $this->symfonyStyle->error('Some classes are missing nodes');
        $this->symfonyStyle->listing($missingNodeClasses);

        return Shell::CODE_ERROR;
    }

    /**
     * @return string[]
     */
    private function getMissingNodeClasses(): array
    {
        $phpStanTypeClasses = $this->phpStanTypeClassFinder->find();
        $supportedPHPStanTypeClasses = $this->getSupportedTypeClasses();

        $unsupportedTypeClasses = [];
        foreach ($phpStanTypeClasses as $phpStanTypeClass) {
            foreach ($supportedPHPStanTypeClasses as $supportedPHPStanTypeClass) {
                if (is_a($phpStanTypeClass, $supportedPHPStanTypeClass, true)) {
                    continue 2;
                }
            }

            $unsupportedTypeClasses[] = $phpStanTypeClass;
        }

        $typesToRemove = [NonexistentParentClassType::class];

        return array_diff($unsupportedTypeClasses, $typesToRemove);
    }

    /**
     * @return string[]
     */
    private function getSupportedTypeClasses(): array
    {
        $supportedPHPStanTypeClasses = [];
        foreach ($this->typeMappers as $typeMappers) {
            $supportedPHPStanTypeClasses[] = $typeMappers->getNodeClass();
        }

        return $supportedPHPStanTypeClasses;
    }
}
