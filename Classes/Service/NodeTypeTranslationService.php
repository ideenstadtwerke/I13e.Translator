<?php

namespace I13e\Translator\Service;

use Closure;
use I13e\Translator\Xliff\XliffMessages;
use I13e\Translator\Xliff\XliffWriter;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Package\Exception\UnknownPackageException;
use Neos\Flow\Package\PackageManager;

class NodeTypeTranslationService
{
    /**
     * @Flow\Inject
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * @Flow\Inject
     * @var XliffWriter
     */
    protected $xliffWriter;

    /**
     * @param array $nodeTypeConfiguration
     * @param string $localNodeTypeName
     * @param string|null $path
     * @return array
     */
    public function findTranslationFields(
        array $nodeTypeConfiguration,
        string $localNodeTypeName,
        ?string $path = null
    ): array {
        $results = [];
        foreach ($nodeTypeConfiguration as $key => $value) {
            $currentPath = $path ? $path . '.' . $key : $key;
            if ($value === 'i18n') {
                $results[$localNodeTypeName] = $results[$localNodeTypeName] ?? [];
                $results[$localNodeTypeName][] = $currentPath;
                continue;
            }
            if (is_array($value)) {
                $results = array_merge_recursive(
                    $results,
                    $this->findTranslationFields($value, $localNodeTypeName, $currentPath)
                );
            }
        }

        return $results;
    }

    /**
     * @param array $nodeTypes
     * @return array
     */
    public function findAllTranslationFields(array $nodeTypes): array
    {
        $result = [];
        foreach ($nodeTypes as $nodeTypeName => $nodeTypeConfig) {
            [, $shortNodeType] = explode(':', $nodeTypeName);
            $result += $this->findTranslationFields($nodeTypeConfig, $shortNodeType);
        }

        return $result;
    }

    /**
     * @param array $translationFields
     * @param string $sourceLanguage
     * @param array $targetLanguages
     * @param Closure|null $translator
     * @return XliffMessages
     */
    public function buildMessages(
        array $translationFields,
        string $sourceLanguage,
        array $targetLanguages = [],
        Closure $translator = null
    ): XliffMessages {
        $messages = new XliffMessages($sourceLanguage, $targetLanguages);

        if (!$translator) {
            $translator = [$this, 'defaultMessageTranslator'];
        }

        foreach ($translationFields as $nodeTypeName => $fields) {
            foreach ($fields as $field) {
                $translatedText = $translator($nodeTypeName, $field, strtoupper($sourceLanguage));
                $message = $messages->addMessage($nodeTypeName, $field, $translatedText);
                foreach ($targetLanguages as $targetLanguage) {
                    $translatedText = $translator(
                        $nodeTypeName,
                        $field,
                        strtoupper($targetLanguage),
                        $message->getSourceText()
                    );
                    $message->addTranslation($targetLanguage, $translatedText);
                }
            }
        }

        return $messages;
    }

    /**
     * @param string $packageName
     * @param XliffMessages $messages
     * @param bool $force
     * @return void
     * @throws UnknownPackageException
     */
    public function writeAll(string $packageName, XliffMessages $messages, bool $force): void
    {
        $this->xliffWriter->writeAll(
            $messages->getSourceLanguage(),
            $messages,
            $packageName,
            $this->packageManager->getPackage($packageName)->getPackagePath(),
            $force
        );
    }

    /**
     * @param string $nodeTypeName
     * @param string $field
     * @param string $targetLanguage
     * @param string|null $sourceText
     * @return string
     */
    public static function defaultMessageTranslator(
        string $nodeTypeName,
        string $field,
        string $targetLanguage,
        ?string $sourceText = null
    ): string {
        if (!$sourceText) {
            return sprintf('%s: %s:%s', $targetLanguage, $nodeTypeName, $field);
        }

        return sprintf('%s: %s', $targetLanguage, preg_replace('/^[A-Z]+:\s?/', '', $sourceText));
    }
}
