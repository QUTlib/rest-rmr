\: [Index](00-index.md) : [Goal](01-goal.md) : Structure : [Modules](03-modules.md) : [APIs and Interfaces](04-apis-and-interfaces.md) : [License & Copyright](05-license-and-copyright.md) : [References](06-references.md) :

Structure
---------

The general structure supported by the framework is:

                                                                     Resources
               Controller               Resource URI                   ┌─────────┐
               (router)                 Mapper                       ┌─┴───────┐ │ (uri_pattern)
    1 request  ┌─────────┐    3 URI     ┌─────────┐                ┌─┴───────┐ │ │ (http-to-model method map)
    ─────────> │---------│ ───────────> │-.       │  2a regsiter   │         │ ├─┘
               │ ,-------│ <─────────── │-'       │ <───────────── │         ├─┘
               └─────────┘  4 resource  └─────────┘                └─────────┘
                │  ^  ^ \     type                                      ^1             ┌─────────┐
                │  │   \ `────.                                         │              │         │
    5 resource/ │  │    `────. \7 model                                 │      ? ────> │         │
      query     │  │6 rep.    \ \          ┌─────────┐                  v1             └─────────┘
                v  │         8 \ \       ┌─┴───────┐ │             ┌─────────┐          DB Layer
               ┌─────────┐ output `──> ┌─┴───────┐ │ │             │         │
               │`--'     │       `──── │-'       │ ├─┘             │         │
               │         │ 2b register │         ├─┘               └─────────┘
               └─────────┘ <────────── └─────────┘                  Model
               Representation          Representations
               Factory                 (can_handle)


### Modules and Interfaces ###

The framework is designed to be modular, and to provide a uniform interface to external systems.  As such two concepts are introduced: **modules** and **interfaces**.

A **module** is a collection of expertise related to a single data model, project, or service.  This definition is intentionally vague in order to allow the greatest flexibility in defining the scope of future projects and services.

An **interface** is a standardised mechanism for accessing a resource.  The framework will provide definitions and support for four interfaces:

* Publicly accessible, human-readable (`www`)
* Publicly accessible, machine-readable (`pub`)
* Authenticated, human-readable (`secure`)
* Authenticated, machine-readable (`auth`)

By convention, URIs will be of the form:

    /interface/module/resource-path...

However this is not mandatory.  A specific API will be provided to facilitate this pattern.

This convention allows simple administration of concerns such as defining global Apache rules for authentication (by using a simple Location directive).

