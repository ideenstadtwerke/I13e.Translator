<?php

namespace I13e\Translator\Command;

use I13e\Translator\Service\NodeTypeConfigurationService;
use I13e\Translator\Service\NodeTypeTranslationService;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Configuration\Exception\InvalidConfigurationTypeException;
use Neos\Flow\Package\Exception\UnknownPackageException;
use Neos\Flow\Package\PackageManager;
use const STR_PAD_LEFT;

/**
 * @Flow\Scope("singleton")
 */
class TranslatorCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * @Flow\Inject
     * @var NodeTypeConfigurationService
     */
    protected $nodeTypeConfigurationService;

    /**
     * @Flow\Inject
     * @var NodeTypeTranslationService
     */
    protected $nodeTypeTranslationService;

    protected string $sourceLanguage = 'en';
    protected array $targetLanguages = [];
    protected bool $silent;
    protected bool $force;

    /**
     * @param string $packageKeyOrNodeType The package key or full node type
     * @param string $sourceLanguage Source language for translations.
     * @param string $targetLanguages Comma separated list of target languages. E.g. de,es
     * @param bool $silent Do not ask for translation values interactively
     * @param bool $force Overwrite existing files
     * @return bool
     * @throws InvalidConfigurationTypeException
     */
    public function generateCommand(
        string $packageKeyOrNodeType,
        string $sourceLanguage = 'en',
        string $targetLanguages = '',
        bool $silent = false,
        bool $force = false,
    ): bool {
        $this->sourceLanguage = $sourceLanguage;
        $this->setTargetLanguagesFromInput($targetLanguages, $sourceLanguage);
        $this->silent = $silent;
        $this->force = $force;

        $argumentParts = explode(':', $packageKeyOrNodeType);
        $packageKey = $argumentParts[0];
        $nodeType = $argumentParts[1] ?? null;

        if (!$nodeType) {
            if (!$this->packageManager->isPackageKeyValid($packageKey) || !$this->packageManager->isPackageAvailable($packageKey)) {
                $this->outputLine('<error>Package "%s" is not available.</error>', [$packageKey]);
                exit(2);
            }

            $nodeTypesToTranslate = $this->nodeTypeConfigurationService->getNodeTypes($packageKey);
        } else {
            if (!$nodeTypeConfiguration = $this->nodeTypeConfigurationService->getNodeType($packageKeyOrNodeType)) {
                $this->outputLine('<error>Node type "%s" is not available.</error>', [$packageKeyOrNodeType]);
                exit(2);
            }
            $nodeTypesToTranslate = $nodeTypeConfiguration;
        }

        $this->translate($nodeTypesToTranslate, $packageKey);

        return true;
    }

    /**
     * @param array $nodeTypes
     * @param string $packageKey
     * @return void
     * @throws UnknownPackageException
     */
    protected function translate(array $nodeTypes, string $packageKey): void
    {
        $translations = $this->nodeTypeTranslationService->findAllTranslationFields($nodeTypes);

        $output = $this->output;

        $current = 0;
        $count = 0;
        array_walk($translations,
            static function ($entries) use (&$count) {
                $count += count($entries);
            }
        );
        $count = $count * (count($this->targetLanguages) + 1);

        $translator = !$this->silent
            ? static function (
                string $nodeTypeName,
                string $field,
                string $targetLanguage,
                ?string $sourceText = null
            ) use ($packageKey, $output, &$current, $count) {
                return $output->ask(
                        sprintf('<comment>[%5$s/%6$s] Please translate "<info>%2$s</info>" for "<info>%3$s</info>" to "<question>%1$s</question>":</comment> ',
                            $targetLanguage, $field, $nodeTypeName, $packageKey,
                            str_pad(++$current, strlen($count), ' ', STR_PAD_LEFT),
                            $count)
                    ) ?? NodeTypeTranslationService::defaultMessageTranslator($nodeTypeName, $field, $targetLanguage,
                        $sourceText);
            }
            : null;


        $messages = $this->nodeTypeTranslationService->buildMessages(
            $translations,
            $this->sourceLanguage,
            $this->targetLanguages,
            $translator
        );

        $this->nodeTypeTranslationService->writeAll($packageKey, $messages, $this->force);
    }

    /**
     * @param string $targetLanguages
     * @param string $sourceLanguage
     * @return void
     */
    protected function setTargetLanguagesFromInput(string $targetLanguages, string $sourceLanguage): void
    {
        $this->targetLanguages = array_filter(
            array_map('trim', explode(',', $targetLanguages)),
            static fn($entry) => $entry !== $sourceLanguage
        );
    }
}
