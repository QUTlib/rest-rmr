rest-rmr
========

A modular RESTful [Resource-Method-Representation][RMR] \[RMR\] framework, designed at the Queensland University of
Technology Library.

[RMR]: http://www.peej.co.uk/articles/rmr-architecture.html

Documentation
-------------

See attached [documentation](documentation/00-index.md).

Magic
-----
Off the bat, the framework will provide the following features:
* An implementation of a variation of _Transparent Content Negotiation in HTTP_ \[RFC 2295\], a content negotiation protocol.
* Compression, if the client specifies Accept-Encoding and/or TE: gzip, deflate, or bzip2.
* Support for If-Modified-Since and If-None-Match headers in GET requests.
* Proper HTTP HEAD request support.
* Mostly correct HTTP OPTIONS request support.
* An implementation of [I-D.ietf-appsawg-http-problem](https://tools.ietf.org/html/draft-ietf-appsawg-http-problem-00)
* An implementation of [I-D.nottingham-http-browser-hints](https://datatracker.ietf.org/doc/draft-nottingham-http-browser-hints/)

I would like to provide support for:
* ~~Some data access layer (DAO, ActiveRecord, ORM, ...)~~ (done, 2012-08-29)
* Response caching ~~and ETag support~~ (response ETags are supported)

License & Copyright
-------------------

See the NOTICE file distributed with this work for information
regarding copyright ownership.  QUT licenses this file to you
under the Apache License, Version 2.0 (the "License"); you may
not use this file except in compliance with the License.
You may obtain a copy of the License at

   http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing,
software distributed under the License is distributed on an
"AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
KIND, either express or implied.  See the License for the
specific language governing permissions and limitations
under the License.

