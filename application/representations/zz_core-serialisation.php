<?php
/*
 * Note: because the classes defined in the core-serialisation-classes
 *       file exhibit catch-all behaviour (i.e. they attempt to handle
 *       as many requests as possible) they must be registered AFTER
 *       any more specific representations.  Thus this file should
 *       start with the letters 'zz_'
 */

require_once('core-serialisation-classes.php');

// ----- IMPORTANT ------------------------------------------------------
// Note: JSONRepresenter is first; since most web browsers accept */*
//       one of these guys will end up handling most requests if nothing
//       better comes along, and the JSON guy returns his data in text/*
//       so browsers are less likely to barf.
Application::register_representer( new JSONRepresenter() );
Application::register_representer( new YAMLRepresenter() );
//
// ----- IMPORTANT ------------------------------------------------------

