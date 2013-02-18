Specific Representers
=====================

Raw Documents
-------------
The representers defined in the `raw-FOO-representer.php` files are tightly coupled with
the models in `{SYSDIR}/models`.

See: `{APPDIR}/representations/aa_raw-doc-representations.php` for more info.

HTML/XHTML Representers
-----------------------
The `XHTMLRepresenter` and `HTMLRepresenter` objects defined in `core-serialisation-classes.php`
represent various HTML-specific object types as HTML.

`XHTMLRepresenter` (application/xhtml+xml):
* `HTMLDocument` -- defined via `{SYSDIR}/utils/html.inc.php`
* `SimpleXMLElement` -- if the root element's name is `'html'`
* `DOMDocument` -- if it looks like a HTML DOM (note: current heuristic is a bit dodgy)

`HTMLRepresenter` (text/html):
* `HTMLDocument` -- defined via `{SYSDIR}/utils/html.inc.php`

TemplateEngine Templates
------------------------
The `TemplateRepresenter` defined in `template-representer.php` is tightly coupled with
the `TemplateEngine` class in `{SYSDIR}/utils/template-engine.inc.php`.  It automatically
represents `TemplateEngine` objects as `text/html`.

Smarty Templates
----------------
The `SmartyRepresenter` defined in `smarty-representer.php` is tightly coupled with the
`SmartyTemplate` class in `{SYSDIR}/utils/smarty.inc.php`.  It automatically represents
`SmartyTemplate` objects as `text/html`.

Generic / Catch-All Representers
================================
The representers defined in `core-serialisation-classes.php` are mostly catch-alls
that will attempt to represent as many objects as possible.  Because of their greedy
nature, they _must_ be added to the representation directory after all more-specific
representations.

See: `{APPDIR}/representations/zz_core-serialisation.php` for more info.

