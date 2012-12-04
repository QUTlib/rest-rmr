Specific Representers
=====================
The representers defined in the `raw-FOO-representer.php` files are tightly coupled with
the models in `{SYSDIR}/models`.

See: `{APPDIR}/representations/aa_raw-doc-representations.php` for more info.

Generic / Catch-All Representers
================================
The representers defined in `core-serialisation-classes.php` are mostly catch-alls
that will attempt to represent as many objects as possible.  Because of their greedy
nature, they _must_ be added to the representation directory after all more-specific
representations.

See: `{APPDIR}/representations/zz_core-serialisation.php` for more info.

