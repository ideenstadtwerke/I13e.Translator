<?php

namespace I13e\Translator\Xliff;

use DOMDocument;
use Exception;

use const DIRECTORY_SEPARATOR;

class XliffWriter
{
    protected $filePathTemplate = 'Resources/Private/Translations/{locale}/{nodeType}.xlf';
    protected bool $force;

    /**
     * @param string $sourceLanguage
     * @param XliffMessages $messages
     * @param string $packageName
     * @param string $packagePath
     * @param bool $force
     * @return void
     * @throws \DOMException
     */
    public function writeAll(
        string $sourceLanguage,
        XliffMessages $messages,
        string $packageName,
        string $packagePath,
        bool $force = false,
    ) {
        $this->force = $force;
        // Build for source
        foreach ($messages->getNodeTypes() as $nodeType) {
            $xliffContent = $this->toXliff(
                $sourceLanguage,
                $messages->getMessages($nodeType),
                $packageName
            );
            $this->saveXliff($xliffContent, $sourceLanguage, $packagePath, $nodeType);
        }

        // Build other target languages
        foreach ($messages->getTargetLanguages() as $targetLanguage) {
            foreach ($messages->getNodeTypes() as $nodeType) {
                $xliffContent = $this->toXliff(
                    $sourceLanguage,
                    $messages->getMessages($nodeType),
                    $packageName,
                    $targetLanguage
                );

                $this->saveXliff($xliffContent, $targetLanguage, $packagePath, $nodeType);
            }
        }
    }

    /**
     * @param string $xliffContent
     * @param string $language
     * @param string $packagePath
     * @param string $nodeType
     * @return void
     * @throws Exception
     */
    protected function saveXliff(string $xliffContent, string $language, string $packagePath, string $nodeType): void
    {
        $filename = $packagePath . str_replace(
            ['{locale}', '{nodeType}'],
            [$language, str_replace('.', DIRECTORY_SEPARATOR, $nodeType)],
            $this->filePathTemplate
        );
        if (file_exists($filename)) {
            $message = sprintf('File "%s" already exists.', $filename);
            if (!$this->force) {
                throw new Exception($message . ' Skipping.');
            }
        }
        if (!is_dir(dirname($filename))) {
            mkdir(dirname($filename), 0755, true);
        }
        file_put_contents($filename, $xliffContent);
    }

    /**
     * @param string $sourceLanguage
     * @param XliffMessage[]|array $messages
     * @param string $packageName
     * @param string|null $targetLanguage
     * @return string
     * @throws \DOMException
     */
    private function toXliff(
        string $sourceLanguage,
        array $messages,
        string $packageName,
        ?string $targetLanguage = null
    ): string {
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->formatOutput = true;

        $xliff = $dom->appendChild($dom->createElement('xliff'));
        $xliff->setAttribute('version', '1.2');
        $xliff->setAttribute('xmlns', 'urn:oasis:names:tc:xliff:document:1.2');

        $xliffFile = $xliff->appendChild($dom->createElement('file'));
        $xliffFile->setAttribute('source-language', str_replace('_', '-', $sourceLanguage));
        if ($targetLanguage) {
            $xliffFile->setAttribute('target-language', str_replace('_', '-', $targetLanguage));
        }
        $xliffFile->setAttribute('datatype', 'plaintext');
        $xliffFile->setAttribute('original', '');
        $xliffFile->setAttribute('product-name', $packageName);

        $xliffBody = $xliffFile->appendChild($dom->createElement('body'));
        foreach ($messages as $message) {
            $transUnit = $dom->createElement('trans-unit');

            $transUnit->setAttribute('id', $message->getId());
            $transUnit->setAttribute('xml:space', 'preserve');

            $sourceElement = $transUnit->appendChild($dom->createElement('source'));
            $sourceElement->appendChild($dom->createTextNode($message->getSourceText()));

            if ($targetLanguage && $sourceLanguage !== $targetLanguage) {
                $translatedText = $message->getTranslation($targetLanguage)
                    ?? sprintf('%s: %s', strtoupper($targetLanguage), $message->getSourceText());

                $textNode = preg_match('/[&<>]/', $translatedText)
                    ? $dom->createCDATASection($translatedText)
                    : $dom->createTextNode($translatedText);

                $targetElement = $transUnit->appendChild($dom->createElement('target'));
                $targetElement->setAttribute('state', 'final');
                $targetElement->appendChild($textNode);
            }

            $xliffBody->appendChild($transUnit);
        }

        return $dom->saveXML() ?: '';
    }
}
