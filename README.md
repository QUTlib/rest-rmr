rest-rmr
========

A modular RESTful RMR framework, designed at the Queensland University of
Technology Library.

Project goal:
------------
Build a framework that provides a simple mechanism to add modules to a RESTful API.
The framework should do the following:
* Make module creation and maintenance as simple as possible
* Promote RESTful design
* Provide a useful API without constraining design
* Provide a uniform interface to external systems

Structure
---------
The general structure supported by the framework is:
 
### Modules and Interfaces ###
The framework is designed to be modular, and to provide a uniform interface to external systems.  As such two concepts are introduced: **modules** and **interfaces**.

A **module** is a collection of expertise related to a single data model, project, or service.  This definition is intentionally vague in order to allow the greatest flexibility in defining the scope of future projects and services.

An **interface** is a standardised mechanism for accessing a resource.  The framework will provide definitions and support for three (or four) interfaces:

* Publicly accessible, human-readable (`pub`)
* Publicly accessible, machine-readable (`api`)
* Authenticated, human-readable (`auth`)
* Authenticated, machine-readable?

By convention, URIs will be of the form:

    /interface/module/resource-path...

However this is not mandatory.  A specific API will be provided to facilitate this pattern.

This allows simple administration of concerns such as defining global Apache rules for authentication (by using a simple Location directive).

Modules
-------
Authored components (modules) fall into one of two categories: **Resources** and **Representations**.  Note that a given service or project may require the creation of both resources and representations.

### Resources ###
Resources are sources of specific information, accessed by a URI.

Resources interact with the framework through _handler methods_ and _URI patterns_.  At program initialisation, the framework scans a strategically-named directory, including all PHP source files it finds.  The intention is that those files will register mappings of _URI patterns_ to _functions/methods_, using a specific framework API.

When a **request** enters the system, the framework compares the request URI to the registered patterns until it finds a match, at which point it invokes the mapped function/method to handle the request.  The result of the handler should be a **data model** object, which is passed back to the framework to be represented.

### Representations ###
Representations are documents that convey resources' information.

Representations interact with the framework through a simple directory structure.  At program initialisation, the framework scans a strategically-named directory, including all PHP source files it finds.  The intention is that those files will register **representer objects** using a specific framework API.

When a **request** is handled and a **data model** is passed to the framework, the representation directory framework uses a simple content-negotiation algorithm to find the set of representers compatible with the request, then it iterates over them until it finds one that is _capable of representing the given data model_.  The representer then creates a representation of the data model and assigns it to a **response** object, which the framework sends back to the waiting client.

API and Interfaces
------------------

### URI Patterns and Handlers ###
To deal with registering URI patterns and handlers, the framework provides two mechanisms:

* A low-level function: `URIMap::register( $http_method, $uri_pattern, $handler )`
* * Any incoming HTTP request using  the given `$http_method`, whose request URI matches the `$uri_pattern`, is handled using the callable `$handler`.
* * If you register a handler for the HTTP GET method, an identical handler is automatically registered for the HEAD method.
* A higher-level class: `URIRegistrar`
* * Provides a simple mechanism to register a set of resource-paths under a single interface and module.
* * Wraps the call to `URIMap::register`, with extra data sanitisation.

Irrespective of _how_ they are registered, URI patterns are tested in the order they are registered.

#### URI Patterns ####
URI patterns may include named parameters:

* `'/hello/:name'` matches `'/hello/foo'` and `'/hello/bar'`
* Parameter “name” is 'foo' or 'bar'

A trailing slash can be made optional by appending a question-mark:
* `'/hello/?'` matches `'/hello'` and `'/hello/'`

#### Handler ####
The handler is invoked with parameter:

* The Request object, which may be queried for example to inspect any HTTP request parameters.

### Representations ###

Representation objects must extend the `Representer` abstract class, implementing the methods:

`can_do_model($m)`
* returns a Boolean, true if it can represent the given model, false if not

`preference_for_type($t)`
* returns a three-decimal-digit-precision floating-point number from 0.000 to 1.000 expressing this representation's preference for representing a given type

`list_types()`
* returns an associative array of `type=>preference` for all types this representer wishes to advertise supporting.  Note that this list does not have to include every type that would return a non-zero value in `#preference_for_type`

`represent($m, $t, $response)`
* represents the given model as the given type, and pokes it into the given response object

Note that wherever a type is given by the framework, it is a complex data structure of the form:

    array(
        'option' => 'foo/bar',
        'raw' => 'foo/bar;q=0.8;baz=quux',
    )

where the “option” is the name of the type, and “raw” is the value supplied by the client, including quality values and other parameters.

Magic
-----
Off the bat, the framework will provide the following features:
* An implementation of a variation of _Transparent Content Negotiation in HTTP_ \[RFC 2295\], a content negotiation protocol.
* Compression, if the client specifies Accept-Encoding: gzip, deflate, or bzip2.
* Proper HTTP HEAD request support.
* Mostly correct HTTP OPTIONS request support.

I would like to provide support for:
* Some data access layer (DAO, ActiveRecord, ORM, ...)
* Response caching and ETag support


