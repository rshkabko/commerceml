@php /** @var \Laravel\Boost\Install\GuidelineAssist $assist */ @endphp
# Flamix CommerceML

Plain-PHP (not Laravel) CommerceML exchange between a CMS site and Bitrix24, plus `Flamix\CommerceML\OneC\CommerceML`, the XML builder/translator the Inventory app uses directly. It owns the request protocol and the Russian↔English tag dictionary; it does not store products or talk to Bitrix24 itself.

- Do not hand-roll CommerceML XML or the Russian tag names: `CommerceML::init('ru')->setArray([...])->getXML()` to build, `CommerceML::init()->translate($xml, 'en')` before parsing.
- `setArray()` writes array keys as given; only the root is translated. A `code` key names a repeated tag, a list item without it becomes `item_<n>`.
- `translate()` renames only tags listed in `src/translate.php`; `Остатки`, `Склад` and any unlisted tag stay Russian after `translate($xml, 'en')`.
- `getData(true)` goes through `json_encode()`: one child is an object, several are a list. Normalise before looping.
- Site side: extend `Flamix\CommerceML\Init`, override `actionCheck()` and the handlers. Every `commerceml_response*()` ends the request with `die()`, so nothing after it runs.
- The `commerceml_*()` helpers exist only after `Init::init($dir)`; they are not in the Composer autoload.
- Full docs: `vendor/flamix/commerceml/docs/*.md` (setup, export, import, xml).

Detail lives in the skill: `commerceml-exchange`.
