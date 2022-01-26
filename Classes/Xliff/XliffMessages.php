<?php

namespace I13e\Translator\Xliff;

class XliffMessages
{

    /**
     * @var XliffMessage[]|array
     */
    protected array $messages = [];

    /**
     * @param string $sourceLanguage
     * @param array $targetLanguages
     */
    public function __construct(protected string $sourceLanguage, protected array $targetLanguages = [])
    {
    }

    /**
     * @param string $nodeType
     * @param string $id
     * @param string|null $sourceText
     * @return XliffMessage
     */
    public function addMessage(string $nodeType, string $id, ?string $sourceText = null): XliffMessage
    {
        $message = new XliffMessage($id, $sourceText);
        $this->messages[$nodeType] = $this->messages[$nodeType] ?? [];
        $this->messages[$nodeType][] = $message;

        return $message;
    }

    /**
     * @param string $nodeType
     * @return XliffMessage[]
     */
    public function getMessages(string $nodeType): array
    {
        return $this->messages[$nodeType] ?? [];
    }

    /**
     * @return array
     */
    public function getNodeTypes(): array
    {
        return array_keys($this->messages);
    }

    /**
     * @return string
     */
    public function getSourceLanguage(): string
    {
        return $this->sourceLanguage;
    }

    /**
     * @return array
     */
    public function getTargetLanguages(): array
    {
        return $this->targetLanguages;
    }

    /**
     * @param array $targetLanguages
     * @return XliffMessages
     */
    public function setTargetLanguages(array $targetLanguages): static
    {
        $this->targetLanguages = $targetLanguages;
        return $this;
    }
}
