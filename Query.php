<?php

namespace chrismcgahan\query;

use \mysqli;
use \Exception;

class Query {
    private $mysqli;

    /**
     * @param array $params Connection params expects the following keys
     *     host (may contain port localhost:3306)
     *     port (optional, default 3306)
     *     username
     *     password (optional, default blank)
     *     database (optional)
     *
     * @throws Exception
     */
    public function __construct($params) {
        if (strpos($params['host'], ':')) {
            list($host, $port) = explode(':', $params['host']);

            if ($port && $params['port']) {
                throw new \Exception('Port specified in more than one location (host param and port param)');
            }

            $params['host'] = $host;

            if ($port) {
                $params['port'] = $port;
            }
        }

        $params = array_merge([
            'host'     => 'localhost',
            'port'     => '3306',
            'username' => 'root',
            'password' => null,
            'database' => null,
        ], $params);

        $this->mysqli = new mysqli($params['host'], $params['username'], $params['password'], $params['database'], $params['port']);

        if ($this->mysqli->connect_error) {
            throw new Exception('Error connecting to database: ' . $this->mysqli->connect_error);
        }
    }

    /**
     * Runs an SQL query
     * 
     * @param string $query The SQL query to execute (may contain placeholders '?')
     * @param array  $params An array of parameters to put in place of the placeholders.
     *     Values will be escaped. Items in an array will be escaped and joined with a comma.
     * 
     * @return mysqli_result
     * 
     * @throws Exception
     */
    public function query($query, $params = []) {
        $placeholderCount = substr_count($query, '?');

        if ($placeholderCount !== count($params)) {
            throw new Exception('Error performing query: the placeholder count does not match the argument count');
        }

        $preparedQuery = '';

        for ($i = 0; $i < strlen($query); $i++) {
            $char = $query[$i];

            if ($char === '?') {
                $param = array_shift($params);

                if (is_array($param)) {
                    $items = [];

                    foreach ($param as $item) {
                        $items[] = "'" . $this->esc($item) . "'";
                    }

                    $preparedQuery .= implode(',', $items);
                }
                else {
                    $preparedQuery .= "'" . $this->esc($param) . "'";
                }
            }
            else {
                $preparedQuery .= $char;
            }
        }

        // save the query for later access by $this->lastQuery()
        $this->_lastQuery = $preparedQuery;

        $result = $this->mysqli->query($preparedQuery);

        if ($result === false) {
            throw new Exception('Error performing query: ' . $this->mysqli->error);
        }

        return $result;
    }

    /**
     * Returns an array of rows, each represented by an associative array
     * 
     * @param string $query The SQL statement to execute (may contain placeholders)
     * @param array  $params The placeholder values (see query for replacement rules)
     * 
     * @return array rows
     * 
     * @throws Exception
     */
    public function getAll($query, $params = []) {
        $result = $this->query($query, $params);

        $rows = [];

        for ($i = 0; $i < $result->num_rows; $i++) {
            $rows[] = $result->fetch_assoc();
        }

        return $rows;
    }

    /**
     * Returns the first row of the query result, represented by an associative array
     * 
     * @param string $query The SQL statement to execute (may contain placeholders)
     * @param array  $params The placeholder values (see query for replacement rules)
     * 
     * @return array row
     * 
     * @throws Exception
     */
    public function getRow($query, $params = []) {
        $result = $this->query($query, $params);

        return $result->fetch_assoc();
    }

    /**
     * Returns the first field of the first row of the query result
     * 
     * @param string $query The SQL statement to execute (may contain placeholders)
     * @param array  $params The placeholder values (see query for replacement rules)
     * 
     * @return mixed field value
     * 
     * @throws Exception
     */
    public function getOne($query, $params = []) {
        $result = $this->query($query, $params);

        $row = $result->fetch_array();

        return $row[0];
    }

    /**
     * Returns an array of values in the column specified by $columnIndex
     * 
     * @param string $query The SQL statement to execute (may contain placeholders)
     * @param int    $columnIndex The index of the column to be returned for each row of the query result
     * @param array  $params The placeholder values (see query for replacement rules)
     * 
     * @return array columns
     * 
     * @throws Exception
     */
    public function getCol($query, $columnIndex = 0, $params = []) {
        if (is_array($columnIndex)) {
            throw new Exception('Argument 2, columnIndex, must be an integer. An array was specified.');
        }
        else if (! is_int($columnIndex)) {
            throw new Exception('Argument 2, columnIndex, must be an integer');
        }

        $result = $this->query($query, $params);

        if ($columnIndex >= $result->field_count) {
            throw new Exception('Error retreiving columns: columnIndex is out of bounds');
        }

        $columns = [];

        for ($i = 0; $i < $result->num_rows; $i++) {
            $row = $result->fetch_array();

            $columns[] = $row[$columnIndex];
        }

        return $columns;
    }

    /**
     * Inserts a single row into the specified table
     * 
     * @param string $table The name of the table to insert into
     * @param array  $data An associative array containing the field names and values of the row to be inserted
     * 
     * @return int insert id
     * 
     * @throws Exception
     */
    public function insert($table, $data) {
        $fields = [];
        $values = [];

        foreach ($data as $key => $value) {
            $fields[] = '`' . $key . '`';
            $values[] = "'" . $this->esc($value) . "'";
        }

        $query = sprintf('INSERT INTO `%s`(%s) VALUES(%s)',
            $table,
            implode(',', $fields),
            implode(',', $values)
        );

        $this->query($query);

        return $this->mysqli->insert_id;
    }

    /**
     * Updates one or more rows in the specified table using the values in the data param
     * 
     * @param string       $table The name of the table to update
     * @param array        $data An associative array containing the field names and values to be updated
     * @param string|array $where The conditions for the update. If an array is specified the conditions are joined with AND
     * 
     * @return void
     * 
     * @throws Exception
     */
    public function update($table, $data, $where) {
        $assignments = [];

        foreach ($data as $key => $value) {
            $assignments[] = sprintf("`%s`='%s'", $key, $this->esc($value));
        }

        if (is_array($where)) {
            $where = $this->buildWhereStringFromArray($where);
        }

        $query = sprintf('UPDATE %s SET %s WHERE %s',
            $table,
            implode(',', $assignments),
            $where
        );

        $this->query($query);
    }

    /**
     * Escape a string for use in a query
     * 
     * @param string $string The string to be escaped
     * 
     * @return string escaped string
     */
    public function esc($string) {
        return $this->mysqli->real_escape_string($string);
    }

    /**
     * Get the value of the AUTO_INCREMENT field that was updated by the last query
     * 
     * @return int last insert id
     */
    public function insertId() {
        return $this->mysqli->insert_id;
    }

    /**
     * Get the number of rows affected by the last query
     * 
     * @return int rows affected
     */
    public function affectedRows() {
        return $this->mysqli->affected_rows;
    }

    /**
     * Get the last executed query
     * 
     * @return string query
     */
    public function lastQuery() {
        return $this->_lastQuery;
    }

    private function buildWhereStringFromArray($whereArray) {
        $conditions = [];

        foreach ($whereArray as $key => $value) {
            $conditions[] = sprintf("`%s`='%s'", $key, $this->esc($value));
        }

        return implode(' AND ', $conditions);
    }
}