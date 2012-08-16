<?php

require_once('dbconn.inc.php');

class BranchDB {
	/**
	 * @throws Exception if a database connection cannot be established
	 */
	public function __construct()
	{
		$this->db = new DBConn();
	}

	private function _parseFields($fields)
	{
		if ($fields == '*') return "*\n";
		$field_array = array();
		foreach ($fields as $field) {
			switch ($field) {
			case 'branch_id':
			case 'id': // the one and only alias
				$field_array[] = "`hrs_branches`.`branch_id` AS `$field`";
				break;
			case 'name':
			case 'description':
			case 'address':
			case 'phone':
			case 'url':
			case 'notes':
				$field_array[] = "`hrs_branches`.`$field` AS `$field`";
				break;
			case 'display':
				$field_array[] = "CAST(`hrs_branches`.`display` AS UNSIGNED) AS `display`";
				break;
			default:
				throw new Exception("invalid field '$field'");
			}
		}
		return implode(",\n  ", $field_array);
	}

	/**
	 * Fetches the branches from the database.
	 *
	 * @param String[] $fields (optional) fields of the branch records to return (by default just the ids)
	 * @param Boolean (optionalL if FALSE, gets all branches (including deleted)
	 * @throws Exception if an invalid field is supplied (see schema for fields)
	 */
	public function getBranches($fields=NULL, $onlyvisible=TRUE)
	{
		if ($fields) {
			$field_str = $this->_parseFields($fields);
		} else {
			$field_str = "`hrs_branches`.`branch_id` AS `branch_id`";
		}

		// build the basic query
		$query = "
SELECT
  $field_str
FROM `hrs_branches`";

		// maybe limit to current branches
		if ($onlyvisible) {
			$query .= "
WHERE `hrs_branches`.`display` != b'0'";
		}

		// sort the output, for consistency
		$query .= "
ORDER BY `branch_id`";

		// execute it
		return $this->db->select($query);
	}

	/**
	 * Looks up a branch by its name (or alias).
	 *
	 * Returns it as an array, same as #getBranches()
	 *
	 * @param String $name the name/alias of the branch
	 * @param String[] $fields (optional) fields of the branch record to return (by default just the id)
	 * @param Boolean $onlyvisible (optional) if FALSE, gets all branches (including deleted)
	 * @throws Exception if an invalid field is supplied (see schema for fields)
	 */
	public function getBranchByName($name, $fields=NULL, $onlyvisible=TRUE)
	{
		// escape the name/alias
		$alias = $this->db->escape($name);

		// what fields to get from the DB
		if ($fields) {
			$field_str = $this->_parseFields($fields);
		} else {
			$field_str = "`hrs_branches`.`branch_id` AS `branch_id`";
		}

		// build the basic query
		$query = "
SELECT
  $field_str
FROM `hrs_branches`
  JOIN `hrs_branch_aliases` ON `hrs_branches`.`branch_id` = `hrs_branch_aliases`.`branch_id`
  AND `alias` = '$alias' AND `hrs_branch_aliases`.`display` != b'0'";

		// maybe limit to current branches
		if ($onlyvisible) {
			$query .= "
WHERE `hrs_branches`.`display` != b'0'";
		}

		// execute it
		return $this->db->select($query);
	}

	public function getBranchStatus($branch_id, $time=NULL)
	{
		if ($time === NULL) $time = time();
		if (!(is_int($branch_id) || preg_match('/^\d+$/',$branch_id))) throw new Exception("dodgy branch_id '$branch_id'");

		$today = date('Y-m-d',$time);
		$dow = date('w',$time);

		$query = "
SELECT
  CAST(`hrs_days`.`is_tbd` AS UNSIGNED) AS `is_tbd`,
  CAST(`hrs_days`.`is_closed` AS UNSIGNED) AS `is_closed`,
  TIME_TO_SEC(`hrs_days`.`open_time`) AS `open_time`,
  TIME_TO_SEC(`hrs_days`.`close_time`) AS `close_time`,
  DATE_FORMAT(`hrs_days`.`close_time`, '%l:%i %p') AS `nice_close_time`
FROM `hrs_periods`
  JOIN `hrs_period_branches` ON `hrs_periods`.`period_id` = `hrs_period_branches`.`period_id`
  JOIN `hrs_days` ON `hrs_period_branches`.`period_branch_id` = `hrs_days`.`period_branch_id`
    AND `day_of_week` = $dow
WHERE `branch_id` = $branch_id
  AND '$today' BETWEEN `begin_date` AND `end_date`
ORDER BY
  `begin_date` DESC,
  `end_date` ASC
LIMIT 1
";

		return $this->db->select($query);
	}

	public function getBranchAliases($branch_id)
	{
		if (!(is_int($branch_id) || preg_match('/^\d+$/',$branch_id))) throw new Exception("dodgy branch_id '$branch_id'");
		$query = "
SELECT
  `hrs_branch_aliases`.`alias` AS `alias`
FROM `hrs_branch_aliases`
WHERE `branch_id` = $branch_id
ORDER BY
  `prime` DESC,
  `alias` ASC
";
		return $this->db->select($query);
	}
}

