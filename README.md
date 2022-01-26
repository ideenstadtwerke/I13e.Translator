# I13e.Translator
## CLI Tool to generate XLIFF files for neos node types

Small CLI command to generate all node type related translations.

This package allows developers to generate multiple XLIFF files for any package (or just one node type) at once.
Also it asks for the translation values to use and will fallback to some default value.

### Attention

This is the first early release of this package. 
Please be aware of that when running the command in an non destructible environment.

## Installation

Run `composer require --dev i13e/translator`.

## Usage

After installing you can just run the new command.

```shell
./flow translator:generate <PACKAGE-KEY-OR-NODE-TYPE>
```

See `./flow help translator:generate` for more options.

## Roadmap

In feature versions we'll maybe implement more features like:
* add translations based on existing xliff files
* add new fields to existing translation fields
* table of missing translations

## Contribution

If you'd like to contribute simply create a pull-request.

---

Proudly developed in the Hanover Region
