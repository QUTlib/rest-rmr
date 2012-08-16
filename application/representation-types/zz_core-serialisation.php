<?php

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

