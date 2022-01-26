<?php

namespace I13e\Translator\Xliff;

class XliffMessage
{
    /**
     * @param string $id
     * @param string|null $sourceText
     * @param array $translations
     */
    public function __construct(
        protected string $id,
        protected ?string $sourceText = null,
        protected array $translations = []
    ) {
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return XliffMessage
     */
    public function setId($id): static
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSourceText()
    {
        return $this->sourceText;
    }

    /**
     * @param mixed $sourceText
     * @return XliffMessage
     */
    public function setSourceText($sourceText): static
    {
        $this->sourceText = $sourceText;
        return $this;
    }

    public function addTranslation(string $language, $text): static
    {
        $this->translations[$language] = $text;

        return $this;
    }

    /**
     * @param string $language
     * @return string|null
     */
    public function getTranslation(string $language): ?string
    {
        return $this->translations[$language] ?? null;
    }
}
