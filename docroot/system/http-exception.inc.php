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

/** Generic exception class for errors that result in specific HTTP responses. */
abstract class HttpException extends Exception {
	/**
	 * The HTTP status code.
	 * @return integer
	 */
	abstract public function status();
	/**
	 * The HTTP status message.
	 * @return string
	 */
	abstract public function status_message();
}

/** Generic exception class for errors that result in HTTP 4xx responses. */
abstract class ClientError extends HttpException {
}

/** Error that results in HTTP 400 response. */
class BadRequestException extends ClientError {
	/** @ignore */ public function status() { return 400; }
	/** @ignore */ public function status_message() { return 'Bad Request'; }
}

/** Error that results in HTTP 401 response. */
class UnauthorizedException extends ClientError {
	/** @ignore */ public function status() { return 401; }
	/** @ignore */ public function status_message() { return 'Unauthorized'; }
}

/** Error that results in HTTP 403 response. */
class ForbiddenException extends ClientError {
	/** @ignore */ public function status() { return 403; }
	/** @ignore */ public function status_message() { return 'Forbidden'; }
}

/** Error that results in HTTP 404 response. */
class NotFoundException extends ClientError {
	/** @ignore */ public function status() { return 404; }
	/** @ignore */ public function status_message() { return 'Not Found'; }
}

/** Error that results in HTTP 405 response. */
class MethodNotAllowedException extends ClientError {
	/** @ignore */ public function status() { return 405; }
	/** @ignore */ public function status_message() { return 'Method Not Allowed'; }
}

/** Error that results in HTTP 406 response. */
class NotAcceptableException extends ClientError {
	/** @ignore */ public function status() { return 406; }
	/** @ignore */ public function status_message() { return 'Not Acceptable'; }
}

/** Error that results in HTTP 415 response. */
class UnsupportedMediaTypeException extends ClientError {
	/** @ignore */ public function status() { return 415; }
	/** @ignore */ public function status_message() { return 'Unsupported Media Type'; }
}

/** Generic exception class for errors that result in HTTP 5xx responses. */
abstract class ServerError extends HttpException {
}

/** Error that results in HTTP 500 response. */
class InternalServerErrorException extends ServerError {
	/** @ignore */ public function status() { return 500; }
	/** @ignore */ public function status_message() { return 'Internal Server Error'; }
}

/** Error that results in HTTP 501 response. */
class NotImplementedException extends ServerError {
	/** @ignore */ public function status() { return 501; }
	/** @ignore */ public function status_message() { return 'Not Implemented'; }
}

/** Error that results in HTTP 503 response. */
class ServiceUnavailableException extends ServerError {
	/** @ignore */ public function status() { return 503; }
	/** @ignore */ public function status_message() { return 'Service Unavailable'; }
}

