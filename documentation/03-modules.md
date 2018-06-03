\: [Index](00-index.md) : [Goal](01-goal.md) : [Structure](02-structure.md) : Modules : [APIs and Interfaces](04-apis-and-interfaces.md) : [License & Copyright](05-license-and-copyright.md) : [References](06-references.md) :

Modules
-------
Authored components (modules) fall into one of two categories: **Resources** and **Representations**.  Note that a given service or project may require the creation of both resources and representations.

### Resources ###

Resources are sources of specific information, accessed by a URI.

Resources interact with the framework through _handler methods_ and _URI patterns_.  At program initialisation the framework scans a strategically-named directory in the filesystem, `include`ing all PHP source files it finds.  The intention is that those files will register mappings of _URI patterns_ to _functions/methods_, using a specific framework API.

When a **request** enters the system, the framework compares the request's URI to the registered patterns until it finds a match, at which point it invokes the mapped function/method to handle the request.  The result of the handler should be a **data model** object, which is passed back to the framework to be represented.

### Representations ###

Representations are documents that convey resources' information.

Representations interact with the framework through a simple directory structure.  At program initialisation, the framework scans a strategically-named directory in the filesystem, `include`ing all PHP source files it finds.  The intention is that those files will register **representer objects** using a specific framework API.

When a **request** is handled and a resulting **data model** is passed to the framework, the representation directory framework uses a simple content-negotiation algorithm to find the set of representers _compatible with the request_, then it iterates over them until it finds one that is _capable of representing the given data model_.  The representer then creates a **representation** of the data model and assigns it to a **response** object, which the framework sends back to the waiting client.

