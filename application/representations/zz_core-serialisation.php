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

require_once(SYSDIR.'/representations/core-serialisation-classes.php');

// ----- IMPORTANT ------------------------------------------------------
// Note: JSONRepresenter is first; since most web browsers accept */*
//       one of these guys will end up handling most requests if nothing
//       better comes along, and the JSON guy returns his data in text/*
//       so browsers are less likely to barf.
Application::register_representer( new JSONRepresenter() );
Application::register_representer( new YAMLRepresenter() );
//
// ----- IMPORTANT ------------------------------------------------------

