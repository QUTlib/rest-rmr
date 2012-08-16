<?php

abstract class HttpException extends Exception {
	abstract public function status();
	abstract public function status_message();
}

abstract class ClientError extends HttpException {
}

class BadRequestException extends ClientError {
	public function status() { return 400; }
	public function status_message() { return 'Bad Request'; }
}

class UnauthorizedException extends ClientError {
	public function status() { return 401; }
	public function status_message() { return 'Unauthorized'; }
}

class ForbiddenException extends ClientError {
	public function status() { return 403; }
	public function status_message() { return 'Forbidden'; }
}

class NotFoundException extends ClientError {
	public function status() { return 404; }
	public function status_message() { return 'Not Found'; }
}

class MethodNotAllowedException extends ClientError {
	public function status() { return 405; }
	public function status_message() { return 'Method Not Allowed'; }
}

class NotAcceptableException extends ClientError {
	public function status() { return 406; }
	public function status_message() { return 'Not Acceptable'; }
}

class UnsupportedMediaTypeException extends ClientError {
	public function status() { return 415; }
	public function status_message() { return 'Unsupported Media Type'; }
}

abstract class ServerError extends HttpException {
}

class InternalServerErrorException extends ServerError {
	public function status() { return 500; }
	public function status_message() { return 'Internal Server Error'; }
}

class ServiceUnavailableException extends ServerError {
	public function status() { return 503; }
	public function status_message() { return 'Service Unavailable'; }
}

