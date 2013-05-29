<?php
/*
 * See the NOTICE file distributed with this work for information
 * regarding copyright ownership.  QUT licenses this file to you
 * under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 */

/*
 * Note: because the classes defined in the core-serialisation-classes
 *       file exhibit catch-all behaviour (i.e. they attempt to handle
 *       as many requests as possible) they must be registered AFTER
 *       any more specific representations.  Thus this file should
 *       start with the letters 'zz_'
 */

require_once(SYSDIR.'/std-reps/core-serialisation-classes.php');

// ----- IMPORTANT ------------------------------------------------------
// Note: the order is HTML > XHTML > XML > JSON > YAML.
//
//       HTML first because that's the defacto standard content-type for
//       resource delivery on the web.
//        * Note that the HTMLRepresenter only represents HTMLDocument
//          objects.
//
//       XHTML and XML second and third because most web browsers accept
//       application/xhtml+xml and application/xml with similar preference,
//       and XHTML is more "web friendly" than XML.
//        * Note that the XHTMLRepresenter only represents HTMLDocument
//          objects, or XML DOM-type objects with a <html> root element.
//
//       JSON fourth because he delivers data in a 'text/*' format, so
//       browsers are less likely to barf at it (some browsers are happy
//       to render any text/*, while they all force the user to download
//       application/* -- and I hate that.)
//
//       Finally YAML because it's an awesome format and everyone should
//       support it.
//
Application::register_representer( new HTMLRepresenter() );
Application::register_representer( new XHTMLRepresenter() );
#Application::register_representer( new XMLRepresenter() );
Application::register_representer( new JSONRepresenter() );
Application::register_representer( new YAMLRepresenter() );
//
// ----- IMPORTANT ------------------------------------------------------

