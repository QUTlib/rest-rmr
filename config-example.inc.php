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

#date_default_timezone_set('Australia/Brisbane');

// Names of special directories, relative to the document root
define('SYSTEM_DIR', 'system');
define('APPLICATION_DIR', 'application');

// Site-specific config settings
// The simplest method is to define SITEHOST and SITENAME directly:
define('SITEHOST', 'www.example.com');
define('SITENAME', 'My Site');
// To support multiple environments, you can use the DEV, TST, and
// PRD prefixes (for development, testing, and production,
// respectively).
// If you specify the appropriate *_SITEHOST values, the framework
// will be able to determine the environment one which it is
// currently deployed, and automatically set the ENVIRONMENT
// value appropriately.
// (Note: it uses $_SERVER['SERVER_ADDR'] to accomplish this)
// If not otherwise known or determinable, the ENVIRONMENT
// default to production ('PRD').
#define('DEV_SITEHOST', 'dev.example.com');
#define('DEV_SITENAME', 'My {dev} Site');
#define('PRD_SITEHOST', 'www,example.com');
#define('PRD_SITENAME', 'My Site');
// To override the self-detection algorithm above, you can
// explicitly define the ENVIRONMENT.  "Valid" values are
// 'DEV', 'TST', or 'PRD'.
#define('ENVIRONMENT', 'DEV');

// Limit number of requests per client address per minute.
// To leave it unlimited, leave undefined.
#define('RATELIMIT', 60);

// If rate limiting is enabled, you can define IP
// addresses that are not limited.
#$RATELIMIT_WHITELIST[] = '1.2.3.4';
#$RATELIMIT_WHITELIST[] = '34.56.78.90';

// If commented, won't write Splunk-friendly logs.
// If set to a true non-string value, won't write to files.
#define('SPLUNK_LOG', "/var/log/splunk/my_site.log");

// Enables debugging features.
#define('DEBUG', 1);

// Uncomment to bring the site offline for maintenance.
#define('MAINTENANCE', 1);

