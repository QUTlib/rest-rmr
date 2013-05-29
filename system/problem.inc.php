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

/**
 * Allows an API developer to specify problems with finer
 * granularity, or more terseness, than is afforded by basic
 * HTTP response status codes.
 *
 * See: http://tools.ietf.org/html/draft-nottingham-http-problem-03
 */
class Problem {
	private $problemType;
	private $title;
	private $httpStatus;
	private $detail;
	private $problemInstance;

	/**
	 * Creates a new Problem, with the minimum set of required attributes.
	 */
	public function __construct($problemType, $title, $httpStatus, $detail=null) {
		$this->problemType = "$problemType";
		$this->title = "$title";
		$this->httpStatus = (int)$httpStatus;
		if (func_num_args() > 3) {
			$this->detail = "$detail";
		}
	}

	/** Get the immutable problemType URI of this problem. */
	public function problemType() { return $this->problemType; }
	/** Get the immutable short, human-readable summary of this problem. */
	public function title() { return $this->title; }
	/** Get the immutable HTTP status code set by the server for this occurrence of the problem. */
	public function httpStatus() { return $this->httpStatus; }

	/** Get or set the optional human readable explanation specific to this occurrence of the problem. */
	public function detail($val=null) {
		if (func_num_args() == 0) return $this->detail;
		$this->detail = "$val";
	}

	/** Get or set the optional URI that identifies the specific occurrence of the problem. */
	public function problemInstance($val=null) {
		if (func_num_args() == 0) return $this->problemInstance;
		$this->problemInstance = "$val";
	}

	/** Get an associative-array representation of this problem object. */
	public function to_array() {
		$array = array(
			'problemType' => $this->problemType,
			'title' => $this->title,
			'httpStatus' => $this->httpStatus,
		);
		if (isset($this->detail)) $array['detail'] = $this->detail;
		if (isset($this->problemInstance)) $array['problemInstance'] = $this->problemInstance;
		return $array;
	}
}
