\: [Index](00-index.md) : [Goal](01-goal.md) : [Structure](02-structure.md) : [Modules](03-modules.md) : APIs and Interfaces : [License & Copyright](05-license-and-copyright.md) : [References](06-references.md) :

API and Interfaces
------------------

### URI Patterns and Handlers ###
To deal with registering URI patterns and handlers, the framework provides three mechanisms:

* A low-level function: `URIMap::register( $http_method, $uri_pattern, $handler )`
    * Any incoming HTTP request using  the given `$http_method`, whose request URI matches the `$uri_pattern`, is handled using the callable `$handler`.
    * If you register a handler for the HTTP GET method, an identical handler is automatically registered for the HEAD method.
* A high-level class: `URIRegistrar`
    * Provides a simple mechanism to register a set of resource-paths under a single module.
    * Wraps the call to `URIMap::register`, with extra data sanitisation.
* A higher-level class: `InterfacedURIRegistrar`
    * An extension of `URIRegistrar` that includes an _interface_ as well as a module.

Irrespective of _how_ they are registered, URI patterns are tested in the order they are registered.

#### URI Patterns ####
URI patterns may include named parameters:

* `'/hello/:name'` matches `'/hello/foo'` and `'/hello/bar'`, but not `'/hello/foo/bar'`
* Parameter “name” is set to 'foo' or 'bar'

A trailing slash can be made optional by appending a question-mark:
* `'/hello/?'` matches `'/hello'` and `'/hello/'`

#### Handler ####
The handler is invoked without parameters; however the framework provides global access to the Request object, which may be queried for example to inspect any HTTP request parameters.

### Representations ###

Representation objects must extend the abstract `Representer` class, implementing the methods:

`can_do_model($m)`
* returns a Boolean, true if it can represent the given model, false if not

`preference_for_type($type, $all)`
* returns a three-decimal-digit-precision floating-point number from 0.000 to 1.000 expressing this representation's preference for representing a given type
* the response may be in the form: `array(<preference>, <actual-type>)` if the preference value corresponds with a different type than `$type`
  * for example, the client may specify `Accept: */*` and the representer may respond with preference `array(1.0, "text/html")`

`preference_for_charset($charset, $all)`
* returns a three-decimal-digit-precision floating-point number from 0.000 to 1.000 expressing this representation's preference for representing a given character set
* the response may be in the form: `array(<preference>, <actual-charset>)` if the preference value corresponds with a different character set than `$charset`
  * for example, the client may specify `Accept-Charset: *` and the representer may respond with preference `array(1.0, "UTF-8")`

`preference_for_language($language, $all)`
* returns a three-decimal-digit-precision floating-point number from 0.000 to 1.000 expressing this representation's preference for representing a given language
* the response may be in the form: `array(<preference>, <actual-language>)` if the preference value corresponds with a language than `$language`
  * for example, the client may specify `Accept: *` and the representer may respond with preference `array(1.0, "en-AU")`

`list_types()`
* returns an associative array of `type=>preference` for all types this representer wishes to advertise supporting.  Note that this list does not have to include every type that would return a non-zero value in `#preference_for_type`

`list_charsets()`
* returns an associative array of `charset=>preference` for all character sets this representer wishes to advertise supporting.  Note that this list does not have to include every character set that would return a non-zero value in `#preference_for_charset`

`list_languages()`
* returns an associative array of `language=>preference` for all languages this representer wishes to advertise supporting.  Note that this list does not have to include every language that would return a non-zero value in `#preference_for_language`

`represent($model, $type, $charset, $language, $response)`
* represents the given model as the given type, charset, and language, and pokes it into the given response object

#### BasicRepresenter ####

A useful partial implementation of `Representer` is the abstract `BasicRepresenter` class.  To use `BasicRepresenter`:

1. Create an extending class.
2. Override the constructor, calling `super::__construct` with appropriate lists of `InternetMediaType`, `ContentLanguage`, and `CharacterSet` objects, and acceptable-model specifications.
3. Implement `rep($model, $metadata, $type, $charset, $language, $response)`, which is the same as `Representer#represent` with an added metadata parameter.

All built-in representation objects extend `BasicRepresenter`.

