rest-rmr
========

A modular RESTful RMR framework, designed at the Queensland University of
Technology Library.

Resource-Model-Representation
-----------------------------

RMR (Resource-Method-Representation) is an alternative design concept to
the usual MVC (Model-View-Controller), proposed by Paul James on his
[blog][peej].  The basic premise is that in our projects we define the
Resources, the Methods by which they are accessed, and the
Representations in which they are made available to the client.

  [peej]: http://www.peej.co.uk/articles/rmr-architecture.html

This framework attempts to provide a simple mechanism to build RESTful
applications inspired by the RMR paradigm.

Resources
---------

Resources are identified by their URI.  That's what URI _stands for_.
As such, the way you teach the framework about your resources is by
registering the URIs used to access them.

In order to streamline the process, and to slightly subvert the RMR
paradigm, we've used a _handler_ pattern rather than "pure" resources;
so what is registered is actually a combination of the URI, the HTTP
method used to access it, and the method or function that handles the
request.

We've also designed the framework to support modules (i.e. projects) and
interfaces.  This allows us at the QUT Library to use a single instance
of the framework to support multiple projects, and lets us configure
access to separate sections of each project globally (e.g. by adding a
simple Apache directive we can require authentication for all /auth/\*
locations.)

The following example shows the typical pattern we'd use:

    $registrar = new URIRegistrar('my-module');
    $registrar->set_interface(Application::IF_PUBLIC);
    $registrar->register_handler('GET',  '/?',          'MyModule->index');
    $registrar->register_handler('GET',  '/user/:name', 'MyModule->show_user');
    $registrar->register_handler('POST', '/user/:name', 'MyModule->post_user');

This example sets up two URI patterns:

    /pub/my-module/
    /pub/my-module/user/foo

1. If a user views `/pub/my-module/` the framework will instantiate a new
   `MyModule` object, and invoke its `index()` method, passing in a Request
   object that represents the current HTTP Request.

2. If a user views `/pub/my-module/user/fred` the framework will instantiate
   a new `MyModule` object, and invoke its `show_user()` method.

The question mark \(?\) on the index URI pattern makes the final slash
optional, so a request to `/pub/my-module` will also be handled.

The `:name` part in the other two URI patterns represents a named parameter.
For example, the `post_user()` method can see which user it being modified
by accessing the "name" param, thus:

    $request->param('name')

The framework expects the handler methods to return resources in the form
of data model objects.  There is no restriction on the form these may take,
except that they shouldn't be Response objects.

When a handler returns its data model object to the framework, it attempts
to _represent_ that data model in a way that satisfies the request.

Representation
--------------

The representation subsystem is an ordered list of Representer objects.
When a request arrives and a handler generates a data model object, the
framework first checks all the listed Representers to see which are most
capable of representing data in a form that satisfies the request.  Then
it checks through those, in order of capability, to see which is willing to
represent the data model object.

The first representer is the one that is used.  If none are found, a variation
of the Transparent Content Negotiation protocol [RFC 2295] is invoked.

