<?php
/**
 * @filesource Kotchasan/Database/Sql.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 * @author Goragod Wiriya <admin@goragod.com>
 * @package Kotchasan
 */

namespace Kotchasan\Database;

/**
 * SQL Function
 *
 * @see https://www.kotchasan.com/
 */
class Sql
{
    /**
     * SQL statement stored here
     *
     * @var string
     */
    protected $sql;

    /**
     * Array to store parameters for binding
     *
     * @var array
     */
    protected $values;

    /**
     * Calculate the average of the selected column
     *
     * This function generates a SQL expression to calculate the average of a specified column.
     *
     * @assert ('id')->text() [==] 'AVG(`id`)'
     *
     * @demo
     * ```php
     * $result = \Kotchasan\Database\Sql::AVG('id')->text();
     * echo $result; // Outputs: AVG(`id`)
     * ```
     *
     * @param string      $column_name The name of the column to calculate the average for
     * @param string|null $alias       The alias for the resulting column, optional
     * @param bool        $distinct    If true, calculates the average of distinct values only; default is false
     *
     * @return static
     */
    public static function AVG($column_name, $alias = null, $distinct = false)
    {
        // Build the SQL expression for calculating the average
        // Include 'DISTINCT' if $distinct is true
        $expression = 'AVG('.($distinct ? 'DISTINCT ' : '').self::fieldName($column_name).')';

        // Add an alias if provided
        if ($alias) {
            $expression .= " AS `$alias`";
        }

        // Create and return the SQL expression
        return self::create($expression);
    }

    /**
     * Generate a SQL BETWEEN ... AND ... clause
     *
     * This function creates a SQL BETWEEN clause for a specified column and range.
     *
     * @assert ('create_date', 'create_date', 'U.create_date')->text() [==] "`create_date` BETWEEN `create_date` AND U.`create_date`"
     * @assert ('create_date', 'table_name.field_name', 'U.`create_date`')->text() [==] "`create_date` BETWEEN `table_name`.`field_name` AND U.`create_date`"
     * @assert ('create_date', '`database`.`table`', '12-1-1')->text() [==] "`create_date` BETWEEN `database`.`table` AND '12-1-1'"
     * @assert ('create_date', 0, 1)->text() [==] "`create_date` BETWEEN 0 AND 1"
     *
     * @demo
     * ```php
     * $result = \Kotchasan\Database\Sql::BETWEEN('create_date', 'create_date', 'U.create_date')->text();
     * echo $result; // Outputs: `create_date` BETWEEN `create_date` AND U.`create_date`
     * ```
     *
     * @param string $column_name The name of the column for the BETWEEN clause
     * @param string $min The minimum value for the range
     * @param string $max The maximum value for the range
     *
     * @return static
     */
    public static function BETWEEN($column_name, $min, $max)
    {
        // Generate the SQL BETWEEN clause
        $expression = self::fieldName($column_name).' BETWEEN '.self::fieldName($min).' AND '.self::fieldName($max);

        // Create and return the SQL expression
        return self::create($expression);
    }

    /**
     * Generate a SQL CONCAT or CONCAT_WS clause
     *
     * This function creates a SQL CONCAT or CONCAT_WS clause with optional alias and separator.
     *
     * @assert (array('fname', 'lname'))->text() [==] "CONCAT(`fname`, `lname`)"
     * @assert (array('U.fname', 'U.`lname`'), 'displayname')->text() [==] "CONCAT(U.`fname`, U.`lname`) AS `displayname`"
     * @assert (array('fname', 'lname'), 'displayname', ' ')->text() [==] "CONCAT_WS(' ', `fname`, `lname`) AS `displayname`"
     *
     * @demo
     * ```php
     * $result = \Kotchasan\Database\Sql::CONCAT(['fname', 'lname'])->text();
     * echo $result; // Outputs: CONCAT(`fname`, `lname`)
     * ```
     *
     * @param array       $fields    List of fields to concatenate
     * @param string|null $alias     The alias for the resulting concatenation, optional
     * @param string|null $separator Null (default) to use CONCAT, specify a separator to use CONCAT_WS
     *
     * @throws \InvalidArgumentException If $fields is not an array
     *
     * @return static
     */
    public static function CONCAT($fields, $alias = null, $separator = null)
    {
        // Check if $fields is an array
        if (!is_array($fields)) {
            throw new \InvalidArgumentException('$fields must be an array');
        }

        // Initialize an array to hold the field names
        $fs = [];

        // Loop through each field and prepare it for the SQL clause
        foreach ($fields as $item) {
            $fs[] = self::fieldName($item);
        }

        // Create the SQL CONCAT or CONCAT_WS clause
        $expression = ($separator === null ? 'CONCAT(' : "CONCAT_WS('$separator', ").implode(', ', $fs).')';

        // Add an alias if provided
        if ($alias) {
            $expression .= " AS `$alias`";
        }

        // Create and return the SQL expression
        return self::create($expression);
    }

    /**
     * Count the number of records for the selected column
     *
     * This function generates a SQL COUNT expression for a specified column.
     *
     * @assert ('id')->text() [==] 'COUNT(`id`)'
     *
     * @demo
     * ```php
     * $result = \Kotchasan\Database\Sql::COUNT('id')->text();
     * echo $result; // Outputs: COUNT(`id`)
     * ```
     *
     * @param string      $column_name The name of the column to count, defaults to '*'
     * @param string|null $alias       The alias for the resulting count, optional
     * @param bool        $distinct    If true, counts only distinct values; default is false
     *
     * @return static
     */
    public static function COUNT($column_name = '*', $alias = null, $distinct = false)
    {
        // Determine the column name to use in the SQL expression
        $column_name = $column_name == '*' ? '*' : self::fieldName($column_name);

        // Build the SQL COUNT expression
        $expression = 'COUNT('.($distinct ? 'DISTINCT ' : '').$column_name.')';

        // Add an alias if provided
        if ($alias) {
            $expression .= " AS `$alias`";
        }

        // Create and return the SQL expression
        return self::create($expression);
    }

    /**
     * Extract date from a DATETIME column
     *
     * This function generates a SQL DATE expression to extract the date part from a DATETIME column.
     *
     * @assert ('create_date')->text() [==] 'DATE(`create_date`)'
     * @assert ('create_date', 'date')->text() [==] 'DATE(`create_date`) AS `date`'
     *
     * @demo
     * ```php
     * $result = \Kotchasan\Database\Sql::DATE('create_date')->text();
     * echo $result; // Outputs: DATE(`create_date`)
     * ```
     *
     * @param string      $column_name The name of the DATETIME column
     * @param string|null $alias       The alias for the resulting date, optional
     *
     * @return static
     */
    public static function DATE($column_name, $alias = null)
    {
        // Build the SQL DATE expression
        $expression = 'DATE('.self::fieldName($column_name).')';

        // Add an alias if provided
        if ($alias) {
            $expression .= " AS `$alias`";
        }

        // Create and return the SQL expression
        return self::create($expression);
    }

    /**
     * Calculate the difference in days between two dates or between a date and NOW()
     *
     * This function generates a SQL DATEDIFF expression to calculate the difference in days between two dates.
     *
     * @assert ('create_date', Sql::NOW())->text() [==] "DATEDIFF(`create_date`, NOW())"
     * @assert ('2017-04-04', 'create_date')->text() [==] "DATEDIFF('2017-04-04', `create_date`)"
     *
     * @demo
     * ```php
     * $result = \Kotchasan\Database\Sql::DATEDIFF('create_date', Sql::NOW())->text();
     * echo $result; // Outputs: DATEDIFF(`create_date`, NOW())
     * ```
     *
     * @param string $column_name1 The first date column or a specific date string
     * @param string $column_name2 The second date column or a specific date string
     * @param string $alias        The alias for the resulting difference, optional
     *
     * @return static
     */
    public static function DATEDIFF($column_name1, $column_name2, $alias = null)
    {
        // Build the SQL DATEDIFF expression
        $expression = 'DATEDIFF('.self::fieldName($column_name1).', '.self::fieldName($column_name2).')';

        // Add an alias if provided
        if ($alias) {
            $expression .= " AS `$alias`";
        }

        // Create and return the SQL expression
        return self::create($expression);
    }

    /**
     * Format a date column for display
     *
     * This function generates a SQL DATE_FORMAT expression to format a date column for display.
     *
     * @assert (Sql::NOW(), '%h:%i')->text() [==] "DATE_FORMAT(NOW(), '%h:%i')"
     * @assert ('create_date', '%Y-%m-%d', 'today')->text() [==] "DATE_FORMAT(`create_date`, '%Y-%m-%d') AS `today`"
     *
     * @demo
     * ```php
     * $result = \Kotchasan\Database\Sql::DATE_FORMAT(Sql::NOW(), '%h:%i')->text();
     * echo $result; // Outputs: DATE_FORMAT(NOW(), '%h:%i')
     * ```
     *
     * @param string      $column_name The name of the date column
     * @param string      $format      The format string for date formatting
     * @param string|null $alias       The alias for the resulting formatted date, optional
     *
     * @return static
     */
    public static function DATE_FORMAT($column_name, $format, $alias = null)
    {
        // Build the SQL DATE_FORMAT expression
        $expression = 'DATE_FORMAT('.self::fieldName($column_name).", '$format')";

        // Add an alias if provided
        if ($alias) {
            $expression .= " AS `$alias`";
        }

        // Create and return the SQL expression
        return self::create($expression);
    }

    /**
     * Extract the day from a DATE or DATETIME column
     *
     * This function generates a SQL DAY expression to extract the day from a DATE or DATETIME column.
     *
     * @assert ('date')->text() [==] 'DAY(`date`)'
     * @assert ('date', 'd')->text() [==] 'DAY(`date`) AS `d`'
     *
     * @param string      $column_name The name of the DATE or DATETIME column
     * @param string|null $alias       The alias for the resulting day, optional
     *
     * @return static
     */
    public static function DAY($column_name, $alias = null)
    {
        // Build the SQL DAY expression
        $expression = 'DAY('.self::fieldName($column_name).')';

        // Add an alias if provided
        if ($alias) {
            $expression .= " AS `$alias`";
        }

        // Create and return the SQL expression
        return self::create($expression);
    }

    /**
     * Return distinct values of a column
     *
     * This function generates a SQL DISTINCT expression to return unique values of a column.
     *
     * @assert ('id')->text() [==] 'DISTINCT `id`'
     *
     * @param string      $column_name The name of the column to retrieve distinct values from
     * @param string|null $alias       The alias for the resulting distinct values, optional
     *
     * @return static
     */
    public static function DISTINCT($column_name, $alias = null)
    {
        // Build the SQL DISTINCT expression
        $expression = 'DISTINCT '.self::fieldName($column_name);

        // Add an alias if provided
        if ($alias) {
            $expression .= " AS `$alias`";
        }

        // Create and return the SQL expression
        return self::create($expression);
    }

    /**
     * Extract sorting information and return as an array.
     * This function processes sorting parameters and returns an array of valid sorting instructions.
     *
     * @assert (['order_date', 'order_no', 'project_type', 'company', 'delivery'], 'order_date desc,order_no ASC,project_type none') [==] array('order_date desc', 'order_no ASC')
     * @assert (['order_date', 'order_no', 'project_type', 'company', 'delivery'], 'order_date desc,order_no ASC,project_type') [==] array('order_date desc', 'order_no ASC', 'project_type')
     * @assert (['order_date', 'order_no', 'project_type', 'company', 'delivery'], '', ['order_date']) [==] array('order_date')
     * @assert (['order_date', 'order_no', 'project_type', 'company', 'delivery'], '', 'order_date') [==] 'order_date'
     *
     * @param array  $columns List of columns that are valid for sorting.
     * @param string $sort    Sorting instructions in the format 'column_name direction'.
     * @param array  $default Default sorting array if no valid sort instructions are provided.
     *
     * @return array|string
     */
    public static function extractSort($columns, $sort, $default = [])
    {
        // Combine the column names into a regex pattern to match against
        $all_fields = implode('|', $columns);

        // Split the sort string by commas to get individual sort instructions
        $sorts = explode(',', $sort);

        // Initialize an array to hold the valid sort instructions
        $result = [];

        // Loop through each sort instruction
        foreach ($sorts as $item) {
            // Use regex to match the sort instruction against the valid columns and sort directions
            if (preg_match('/('.$all_fields.')([\s]+(asc|desc))?$/i', trim($item), $match)) {
                // If there's a match, add it to the result array
                $result[] = $match[0];
            }
        }

        // If no valid sort instructions were found, return the default array
        // Otherwise, return the array of valid sort instructions
        if (empty($result)) {
            return $default;
        } elseif (count($result) === 1) {
            // If only one valid sort instruction is found, return it as a string
            return $result[0];
        } else {
            // Otherwise, return the array of valid sort instructions
            return $result;
        }
    }

    /**
     * Format a column for display
     *
     * This function generates a SQL FORMAT expression to format a column for display.
     *
     * @assert (Sql::NOW(), 'Y-m-d')->text() [==] "FORMAT(NOW(), 'Y-m-d')"
     * @assert ('create_date', 'Y-m-d', 'today')->text() [==] "FORMAT(`create_date`, 'Y-m-d') AS `today`"
     *
     * @param string      $column_name The name of the column to format
     * @param string      $format      The format string for formatting
     * @param string|null $alias       The alias for the resulting formatted column, optional
     *
     * @return static
     */
    public static function FORMAT($column_name, $format, $alias = null)
    {
        // Build the SQL FORMAT expression
        $expression = 'FORMAT('.self::fieldName($column_name).", '$format')";

        // Add an alias if provided
        if ($alias) {
            $expression .= " AS `$alias`";
        }

        // Create and return the SQL expression
        return self::create($expression);
    }

    /**
     * Create a GROUP_CONCAT SQL statement
     *
     * This function generates a SQL GROUP_CONCAT expression to concatenate values of a column within a group.
     *
     * @assert ('C.topic', 'topic', ', ')->text() [==] "GROUP_CONCAT(C.`topic` SEPARATOR ', ') AS `topic`"
     *
     * @param string       $column_name The name of the column to concatenate
     * @param string|null  $alias       The alias for the resulting concatenated column, optional
     * @param string       $separator   The separator to use between concatenated values, default is ','
     * @param bool         $distinct    If true, returns only distinct values; default is false
     * @param string|array $order       The order in which concatenated values should appear
     *
     * @return static
     */
    public static function GROUP_CONCAT($column_name, $alias = null, $separator = ',', $distinct = false, $order = null)
    {
        // Handle ordering if specified
        if (!empty($order)) {
            $orders = [];
            if (is_array($order)) {
                foreach ($order as $item) {
                    $orders[] = self::fieldName($item);
                }
            } else {
                $orders[] = self::fieldName($order);
            }
            // Construct the ORDER BY clause
            $order = empty($orders) ? '' : ' ORDER BY '.implode(',', $orders);
        }

        // Build the SQL GROUP_CONCAT expression
        $expression = 'GROUP_CONCAT('.($distinct ? 'DISTINCT ' : '').self::fieldName($column_name).$order." SEPARATOR '$separator')";

        // Add an alias if provided
        if ($alias) {
            $expression .= " AS `$alias`";
        }

        // Create and return the SQL expression
        return self::create($expression);
    }

    /**
     * Generates and returns an SQL expression that creates a JSON object from the specified columns.
     *
     * @assert (['level' => 'R.level', 'position' => Sql::IFNULL('G.topic', "")], 'R.level DESC', 'approver')->text() [==] "CONCAT('[', GROUP_CONCAT(JSON_OBJECT('level', R.`level`, 'position', IFNULL(G.`topic`, '')) ORDER BY R.`level` DESC), ']') AS `approver`"
     * @assert (['level' => 'R.level', 'position' => ''], ['R.level DESC'])->text() [==] "CONCAT('[', GROUP_CONCAT(JSON_OBJECT('level', R.`level`, 'position', '') ORDER BY R.`level` DESC), ']')"
     *
     * @param array $columns The columns to be converted into a JSON object.
     * @param array|string|null $order The order in which to sort the results (if any).
     * @param string|null $alias The alias name for the expression (if any).
     *
     * @return string The SQL expression that creates the JSON object.
     */
    public static function toJSON($columns, $order = null, $alias = null)
    {
        // Handle ordering if specified
        if (!empty($order)) {
            $orders = [];
            if (is_array($order)) {
                // If $order is an array, loop through it to create field names
                foreach ($order as $item) {
                    $orders[] = self::fieldName($item);
                }
            } else {
                // If $order is not an array, add the field name directly
                $orders[] = self::fieldName($order);
            }
            // Construct the ORDER BY clause
            $order = empty($orders) ? '' : ' ORDER BY '.implode(',', $orders);
        } else {
            $order = '';
        }

        $fields = [];
        // Loop through the columns to create column names and fields
        foreach ($columns as $column_name => $field) {
            $fields[] = "'$column_name'";
            $fields[] = self::fieldName($field);
        }

        // Build the SQL CONCAT expression
        $expression = "CONCAT('[', GROUP_CONCAT(JSON_OBJECT(".implode(', ', $fields).')'.$order."), ']')";
        // Add an alias if provided
        if ($alias) {
            $expression .= " AS `$alias`";
        }

        // Create and return the SQL expression
        return self::create($expression);
    }

    /**
     * Extract the hour from a DATETIME column
     *
     * This function generates a SQL HOUR expression to extract the hour from a DATETIME column.
     *
     * @assert ('create_date')->text() [==] 'HOUR(`create_date`)'
     * @assert ('create_date', 'date')->text() [==] 'HOUR(`create_date`) AS `date`'
     *
     * @param string      $column_name The name of the DATETIME column
     * @param string|null $alias       The alias for the resulting hour, optional
     *
     * @return static
     */
    public static function HOUR($column_name, $alias = null)
    {
        // Build the SQL HOUR expression
        $expression = 'HOUR('.self::fieldName($column_name).')';

        // Add an alias if provided
        if ($alias) {
            $expression .= " AS `$alias`";
        }

        // Create and return the SQL expression
        return self::create($expression);
    }

    /**
     * Create an IFNULL SQL statement
     *
     * This function generates a SQL IFNULL expression to return the first non-null value from two columns.
     *
     * @assert ('create_date', 'U.create_date')->text() [==] "IFNULL(`create_date`, U.`create_date`)"
     * @assert ('create_date', 'U.create_date', 'test')->text() [==] "IFNULL(`create_date`, U.`create_date`) AS `test`"
     *
     * @param string      $column_name1 The first column name
     * @param string      $column_name2 The second column name
     * @param string|null $alias        The alias for the resulting expression, optional
     *
     * @return static
     */
    public static function IFNULL($column_name1, $column_name2, $alias = null)
    {
        // Build the SQL IFNULL expression
        $expression = 'IFNULL('.self::fieldName($column_name1).', '.self::fieldName($column_name2).')';

        // Add an alias if provided
        if ($alias) {
            $expression .= " AS `$alias`";
        }

        // Create and return the SQL expression
        return self::create($expression);
    }

    /**
     * Create an IS NOT NULL SQL statement
     *
     * This function generates a SQL IS NOT NULL expression to check if a column is not null.
     *
     * @assert ('U.id')->text() [==] "U.`id` IS NOT NULL"
     *
     * @param string $column_name The column name to check for not null
     *
     * @return static
     */
    public static function ISNOTNULL($column_name)
    {
        // Build the SQL IS NOT NULL expression
        $expression = self::fieldName($column_name).' IS NOT NULL';

        // Create and return the SQL expression
        return self::create($expression);
    }

    /**
     * Create an IS NULL SQL statement
     *
     * This function generates a SQL IS NULL expression to check if a column is null.
     *
     * @assert ('U.id')->text() [==] "U.`id` IS NULL"
     *
     * @param string $column_name The column name to check for null
     *
     * @return static
     */
    public static function ISNULL($column_name)
    {
        // Build the SQL IS NULL expression
        $expression = self::fieldName($column_name).' IS NULL';

        // Create and return the SQL expression
        return self::create($expression);
    }

    /**
     * Find the maximum value of a column
     *
     * This function generates a SQL MAX expression to find the maximum value of a column.
     *
     * @assert ('id')->text() [==] 'MAX(`id`)'
     *
     * @param string      $column_name The column name to find the maximum value
     * @param string|null $alias       The alias for the resulting maximum value, optional
     *
     * @return static
     */
    public static function MAX($column_name, $alias = null)
    {
        // Build the SQL MAX expression
        $expression = 'MAX('.self::fieldName($column_name).')';

        // Add an alias if provided
        if ($alias) {
            $expression .= " AS `$alias`";
        }

        // Create and return the SQL expression
        return self::create($expression);
    }

    /**
     * Find the minimum value of a column
     *
     * This function generates a SQL MIN expression to find the minimum value of a column.
     *
     * @assert ('id')->text() [==] 'MIN(`id`)'
     *
     * @param string      $column_name The column name to find the minimum value
     * @param string|null $alias       The alias for the resulting minimum value, optional
     *
     * @return static
     */
    public static function MIN($column_name, $alias = null)
    {
        // Build the SQL MIN expression
        $expression = 'MIN('.self::fieldName($column_name).')';

        // Add an alias if provided
        if ($alias) {
            $expression .= " AS `$alias`";
        }

        // Create and return the SQL expression
        return self::create($expression);
    }

    /**
     * Extract minutes from a DATETIME column
     *
     * This function generates a SQL MINUTE expression to extract minutes from a DATETIME column.
     *
     * @assert ('create_date')->text() [==] 'MINUTE(`create_date`)'
     * @assert ('create_date', 'date')->text() [==] 'MINUTE(`create_date`) AS `date`'
     *
     * @param string      $column_name The column name to extract minutes from
     * @param string|null $alias       The alias for the resulting minutes, optional
     *
     * @return static
     */
    public static function MINUTE($column_name, $alias = null)
    {
        // Build the SQL MINUTE expression
        $expression = 'MINUTE('.self::fieldName($column_name).')';

        // Add an alias if provided
        if ($alias) {
            $expression .= " AS `$alias`";
        }

        // Create and return the SQL expression
        return self::create($expression);
    }

    /**
     * Extract month from a DATE or DATETIME column
     *
     * This function generates a SQL MONTH expression to extract the month from a DATE or DATETIME column.
     *
     * @assert ('date')->text() [==] 'MONTH(`date`)'
     * @assert ('date', 'm')->text() [==] 'MONTH(`date`) AS `m`'
     *
     * @param string      $column_name The column name to extract the month from
     * @param string|null $alias       The alias for the resulting month, optional
     *
     * @return static
     */
    public static function MONTH($column_name, $alias = null)
    {
        // Build the SQL MONTH expression
        $expression = 'MONTH('.self::fieldName($column_name).')';

        // Add an alias if provided
        if ($alias) {
            $expression .= " AS `$alias`";
        }

        // Create and return the SQL expression
        return self::create($expression);
    }

    /**
     * Generate SQL to find the next value in a sequence (MAX + 1)
     *
     * Used to find the next ID in a table.
     *
     * @assert ('id', '`world`')->text() [==] '(1 + IFNULL((SELECT MAX(`id`) FROM `world` AS X), 0))'
     * @assert ('id', '`world`', array(array('module_id', 'D.`id`')), 'next_id')->text() [==] '(1 + IFNULL((SELECT MAX(`id`) FROM `world` AS X WHERE `module_id` = D.`id`), 0)) AS `next_id`'
     * @assert ('id', '`world`', array(array('module_id', 'D.`id`')), null)->text() [==] '(1 + IFNULL((SELECT MAX(`id`) FROM `world` AS X WHERE `module_id` = D.`id`), 0))'
     *
     * @param string $field      The field name to find the maximum value
     * @param string $table_name The table name
     * @param mixed  $condition  (optional) WHERE condition for the query
     * @param string $alias      (optional) Alias for the resulting field, null means no alias
     * @param string $operator   (optional) Logical operator like AND or OR
     * @param string $id         (optional) Key field name
     *
     * @return static
     */
    public static function NEXT($field, $table_name, $condition = null, $alias = null, $operator = 'AND', $id = 'id')
    {
        $obj = new static;

        // Build the WHERE clause if condition is provided
        if (!empty($condition)) {
            $condition = ' WHERE '.$obj->buildWhere($condition, $obj->values, $operator, $id);
        } else {
            $condition = '';
        }

        // Build the SQL expression to find next ID
        $obj->sql = '(1 + IFNULL((SELECT MAX(`'.$field.'`) FROM '.$table_name.' AS X'.$condition.'), 0))';

        // Add an alias if provided
        if (isset($alias)) {
            $obj->sql .= " AS `$alias`";
        }

        // Return the SQL object
        return $obj;
    }

    /**
     * Returns the current date and time as a SQL function NOW().
     *
     * @param string|null $alias Optional alias for the NOW() function result in SQL.
     *                           If provided, formats the SQL as NOW() AS `$alias`.
     *
     * @return static
     */
    public static function NOW($alias = null)
    {
        // Build the SQL expression for NOW() with optional alias
        $sql = 'NOW()'.($alias ? " AS `$alias`" : '');

        // Assuming self::create() constructs or modifies a query or model object
        return self::create($sql);
    }

    /**
     * Searches for a substring in a string and returns its position. If not found, returns 0; indexing starts from 1.
     *
     * @assert ('find', 'C.`topic`')->text() [==] "LOCATE('find', C.`topic`)"
     *
     * @param string      $substr The substring to search for. If it's a field name, it should be enclosed in ``.
     * @param string      $str    The original string to search within. If it's a field name, it should be enclosed in ``.
     * @param string|null $alias  Optional alias for the result of the LOCATE() function in SQL.
     *                            If provided, formats the SQL as LOCATE(...) AS `$alias`.
     * @param int         $pos    Optional starting position for the search. Defaults to 0 (search from the beginning).
     *
     * @return static
     */
    public static function POSITION($substr, $str, $alias = null, $pos = 0)
    {
        // Adjust substrings if they are not field names to be SQL-compatible
        $substr = strpos($substr, '`') === false ? "'$substr'" : $substr;
        $str = strpos($str, '`') === false ? "'$str'" : $str;

        // Build the SQL expression for LOCATE() with optional alias and position
        $sql = "LOCATE($substr, $str".(empty($pos) ? ')' : ", $pos)").($alias ? " AS `$alias`" : '');

        // Assuming self::create() constructs or modifies a query or model object
        return self::create($sql);
    }

    /**
     * Generates a random number.
     *
     * @assert ()->text() [==] 'RAND()'
     * @assert ('id')->text() [==] 'RAND() AS `id`'
     *
     * @param string|null $alias       Optional alias for the RAND() function result in SQL.
     *                                 If provided, formats the SQL as RAND() AS `$alias`.
     *
     * @return static
     */
    public static function RAND($alias = null)
    {
        // Build the SQL expression for RAND() with optional alias
        $sql = 'RAND()'.($alias ? " AS `$alias`" : '');

        // Assuming self::create() constructs or modifies a query or model object
        return self::create($sql);
    }

    /**
     * Extracts the seconds from a DATETIME column.
     *
     * @assert ('create_date')->text() [==] 'SECOND(`create_date`)'
     * @assert ('create_date', 'date')->text() [==] 'SECOND(`create_date`) AS `date`'
     *
     * @param string      $column_name The name of the DATETIME column to extract seconds from.
     * @param string|null $alias       Optional alias for the SECOND() function result in SQL.
     *                                 If provided, formats the SQL as SECOND(...) AS `$alias`.
     *
     * @return static
     */
    public static function SECOND($column_name, $alias = null)
    {
        // Build the SQL expression for SECOND() with optional alias
        $sql = 'SECOND('.self::fieldName($column_name).')'.($alias ? " AS `$alias`" : '');

        // Assuming self::create() constructs or modifies a query or model object
        return self::create($sql);
    }

    /**
     * Calculates the sum of values in a selected column.
     *
     * @assert ('id')->text() [==] 'SUM(`id`)'
     * @assert ('table_name.`id`', 'id')->text() [==] 'SUM(`table_name`.`id`) AS `id`'
     * @assert ('U.id', 'id', true)->text() [==] 'SUM(DISTINCT U.`id`) AS `id`'
     * @assert ('U1.id', 'id', true)->text() [==] 'SUM(DISTINCT U1.`id`) AS `id`'
     *
     * @param string      $column_name The name of the column to sum. If it's a field name, it should be enclosed in ``.
     * @param string|null $alias       Optional alias for the SUM() function result in SQL.
     *                                 If provided, formats the SQL as SUM(...) AS `$alias`.
     * @param bool        $distinct    Optional. If true, sums only distinct values in the column.
     *                                 Defaults to false, summing all values in the column.
     *
     * @return static
     */
    public static function SUM($column_name, $alias = null, $distinct = false)
    {
        // Build the SQL expression for SUM() with optional DISTINCT and alias
        $sql = 'SUM('.($distinct ? 'DISTINCT ' : '').self::fieldName($column_name).')'.($alias ? " AS `$alias`" : '');

        // Assuming self::create() constructs or modifies a query or model object
        return self::create($sql);
    }

    /**
     * Calculates the time difference between two datetime columns or values.
     *
     * @assert ('create_date', Sql::NOW())->text() [==] "TIMEDIFF(`create_date`, NOW())"
     * @assert ('2017-04-04', 'create_date')->text() [==] "TIMEDIFF('2017-04-04', `create_date`)"
     *
     * @param string $column_name1 The first datetime column or value. If it's a column name, it should be enclosed in ``.
     * @param string $column_name2 The second datetime column or value. If it's a column name, it should be enclosed in ``.
     * @param string $alias        Optional alias for the TIMEDIFF() function result in SQL.
     *                             If provided, formats the SQL as TIMEDIFF(...) AS `$alias`.
     *
     * @return static
     */
    public static function TIMEDIFF($column_name1, $column_name2, $alias = null)
    {
        // Build the SQL expression for TIMEDIFF() with optional alias
        $sql = 'TIMEDIFF('.self::fieldName($column_name1).', '.self::fieldName($column_name2).')'.($alias ? " AS `$alias`" : '');

        // Assuming self::create() constructs or modifies a query or model object
        return self::create($sql);
    }

    /**
     * Calculates the difference between two datetime columns or values in specified units.
     *
     * @assert ('HOUR', 'create_date', Sql::NOW())->text() [==] "TIMESTAMPDIFF(HOUR, `create_date`, NOW())"
     * @assert ('MONTH', '2017-04-04', 'create_date')->text() [==] "TIMESTAMPDIFF(MONTH, '2017-04-04', `create_date`)"
     *
     * @param string $unit        The unit of time difference to calculate:
     *                            FRAC_SECOND (microseconds), SECOND, MINUTE, HOUR, DAY, WEEK, MONTH, QUARTER, or YEAR.
     * @param string $column_name1 The first datetime column or value. If it's a column name, it should be enclosed in ``.
     * @param string $column_name2 The second datetime column or value. If it's a column name, it should be enclosed in ``.
     * @param string $alias       Optional alias for the TIMESTAMPDIFF() function result in SQL.
     *                            If provided, formats the SQL as TIMESTAMPDIFF(...) AS `$alias`.
     *
     * @return static
     */
    public static function TIMESTAMPDIFF($unit, $column_name1, $column_name2, $alias = null)
    {
        // Build the SQL expression for TIMESTAMPDIFF() with optional alias
        $sql = 'TIMESTAMPDIFF('.$unit.', '.self::fieldName($column_name1).', '.self::fieldName($column_name2).')'.($alias ? " AS `$alias`" : '');

        // Assuming self::create() constructs or modifies a query or model object
        return self::create($sql);
    }

    /**
     * Constructs a WHERE clause based on the provided conditions.
     *
     * @assert (1)->text() [==] "`id` = 1"
     * @assert ('1')->text() [==] "`id` = '1'"
     * @assert (0.1)->text() [==] "`id` = 0.1"
     * @assert ('ทดสอบ')->text() [==] "`id` = 'ทดสอบ'"
     * @assert (null)->text() [==] "`id` = NULL"
     * @assert (0x64656)->text() [==] "`id` = 411222"
     * @assert ('SELECT * FROM')->text() [==] "`id` = :id0"
     * @assert (Sql::create('EXISTS SELECT FROM WHERE'))->text() [==] "EXISTS SELECT FROM WHERE"
     * @assert (array('id', '=', 1))->text() [==] "`id` = 1"
     * @assert (array('U.id', '2017-01-01 00:00:00'))->text() [==] "U.`id` = '2017-01-01 00:00:00'"
     * @assert (array('id', 'IN', array(1, '2', null)))->text() [==] "`id` IN (1, '2', NULL)"
     * @assert (array('id', 'SELECT * FROM'))->text() [==] "`id` = :id0"
     * @assert (array('U.`id`', 'NOT IN', Sql::create('SELECT * FROM')))->text() [==] "U.`id` NOT IN SELECT * FROM"
     * @assert (array(array('id', 'IN', array(1, '2', null))))->text() [==] "`id` IN (1, '2', NULL)"
     * @assert (array(array('U.id', 1), array('U.id', '!=', '1')))->text() [==] "(U.`id` = 1 AND U.`id` != '1')"
     * @assert (array(array(Sql::MONTH('create_date'), 1), array(Sql::YEAR('create_date'), 1)))->text() [==] "(MONTH(`create_date`) = 1 AND YEAR(`create_date`) = 1)"
     * @assert (array(array('id', array(1, 'a')), array('id', array('G.id', 'G.`id2`'))))->text() [==] "(`id` IN (1, 'a') AND `id` IN (G.`id`, G.`id2`))"
     * @assert (array(array('id', array('', 'th'))))->text() [==] "`id` IN ('', 'th')"
     * @assert (array(Sql::YEAR('create_date'), Sql::YEAR('`create_date`')))->text() [==] "YEAR(`create_date`) = YEAR(`create_date`)"
     * @assert (array('ip', 'NOT IN', array('', '192.168.1.2')))->text() [==] "`ip` NOT IN ('', '192.168.1.2')"
     * @assert (array(1, 1))->text() [==] "1 = 1"
     * @assert (array(array('username', NULL), array('username', '=', NULL), array('username', '!=', NULL)))->text() [==] "(`username` IS NULL AND `username` IS NULL AND `username` IS NOT NULL)"
     *
     * @param mixed  $condition The condition(s) to build the WHERE clause. Can be:
     *                          - A scalar value for simple comparisons.
     *                          - An array for more complex conditions:
     *                            - [column, operator, value]
     *                            - [column, 'IN', array(values)]
     *                            - [column, 'NOT IN', Sql::create(subquery)]
     *                            - Nested arrays for complex logical conditions.
     * @param string $operator  (optional) The logical operator to combine multiple conditions ('AND' or 'OR'). Defaults to 'AND'.
     * @param string $id        (optional) The key field name. Defaults to 'id' if not specified.
     *
     * @return static
     */
    public static function WHERE($condition, $operator = 'AND', $id = 'id')
    {
        $obj = new static;
        // Call buildWhere method to construct the WHERE clause and set it to $obj->sql
        $obj->sql = $obj->buildWhere($condition, $obj->values, $operator, $id);
        return $obj;
    }

    /**
     * Extracts the year from a DATE or DATETIME column.
     *
     * @assert ('date')->text() [==] 'YEAR(`date`)'
     * @assert ('date', 'y')->text() [==] 'YEAR(`date`) AS `y`'
     *
     * @param string      $column_name The name of the DATE or DATETIME column. Should be enclosed in `` if it's a column name.
     * @param string|null $alias       Optional alias for the YEAR() function result in SQL.
     *                                 If provided, formats the SQL as YEAR(...) AS `$alias`.
     *
     * @return static
     */
    public static function YEAR($column_name, $alias = null)
    {
        // Build the SQL expression for YEAR() with optional alias
        $sql = 'YEAR('.self::fieldName($column_name).')'.($alias ? " AS `$alias`" : '');

        // Assuming self::create() constructs or modifies a query or model object
        return self::create($sql);
    }

    /**
     * class constructer
     *
     * @param string $sql
     */
    public function __construct($sql = null)
    {
        $this->sql = $sql;
        $this->values = [];
    }

    /**
     * สร้าง Object Sql
     *
     * @param string $sql
     */
    public static function create($sql)
    {
        return new static($sql);
    }

    /**
     * Wraps a column name with backticks (`) for SQL identifiers.
     * Column names should consist of English letters, numbers, and underscores only.
     * If the column name contains any other characters, returns it wrapped in single quotes ('').
     *
     * @assert ('C') [==] "'C'"
     * @assert ('c') [==] "'c'"
     * @assert ('UU') [==] "'UU'"
     * @assert ('U9') [==] "'U9'"
     * @assert ('id') [==] '`id`'
     * @assert ('field_name') [==] '`field_name`'
     * @assert ('U.id') [==] 'U.`id`'
     * @assert ('U1.id') [==] 'U1.`id`'
     * @assert ('U99.member_id') [==] 'U99.`member_id`'
     * @assert ('U99.provinceId1') [==] 'U99.`provinceId1`'
     * @assert ('U999.provinceId1') [==] "`U999`.`provinceId1`"
     * @assert ('U999.`provinceId1`') [==] "`U999`.`provinceId1`"
     * @assert ('U1.id DESC') [==] 'U1.`id` DESC'
     * @assert ('table_name.field_name') [==] '`table_name`.`field_name`'
     * @assert ('`table_name`.`field_name`') [==] '`table_name`.`field_name`'
     * @assert ('table_name.`field_name`') [==] '`table_name`.`field_name`'
     * @assert ('`table_name`.field_name') [==] '`table_name`.`field_name`'
     * @assert ('`table_name`.field_name ASC') [==] '`table_name`.`field_name` ASC'
     * @assert ('0x64656') [==] "`0x64656`"
     * @assert (0x64656) [==] 411222
     * @assert ('DATE(day)') [==] "'DATE(day)'"
     * @assert ('DROP table') [==] "'DROP table'"
     * @assert ('SQL(DATE(day))') [==] 'DATE(day)'
     * @assert (Sql::DATE('day')) [==] 'DATE(`day`)'
     * @assert ([]) [throws] InvalidArgumentException
     *
     * @param string|int $column_name The column name or value to be formatted for SQL.
     *
     * @throws \InvalidArgumentException If the column name format is invalid.
     *
     * @return string|int
     */
    public static function fieldName($column_name)
    {
        if ($column_name instanceof self || $column_name instanceof QueryBuilder) {
            // If $column_name is an instance of Sql or QueryBuilder, return its text representation
            return $column_name->text();
        } elseif (is_string($column_name)) {
            // Check and format SQL command wrapped in SQL(...) if present
            if (preg_match('/^SQL\((.+)\)$/', $column_name, $match)) {
                return $match[1];
            } elseif (preg_match('/^`?([a-z0-9_]{2,})`?(\s(ASC|DESC|asc|desc))?$/', $column_name, $match)) {
                // Match for simple column names or names with ASC/DESC appended
                return '`'.$match[1].'`'.(empty($match[3]) ? '' : $match[2]);
            } elseif (preg_match('/^([A-Z][0-9]{0,2}\.)`?([a-zA-Z0-9_]+)`?(\s(ASC|DESC|asc|desc))?$/', $column_name, $match)) {
                // Match for prefixed column names (e.g., U1.id) or names with ASC/DESC appended
                return $match[1].'`'.$match[2].'`'.(empty($match[4]) ? '' : $match[3]);
            } elseif (preg_match('/^`?([a-zA-Z0-9_]+)`?\.`?([a-zA-Z0-9_]+)`?(\s(ASC|DESC|asc|desc))?$/', $column_name, $match)) {
                // Match for table_name.field_name or similar combinations with ASC/DESC appended
                return ("`$match[1]`.`$match[2]`").(empty($match[4]) ? '' : $match[3]);
            } else {
                // If none of the above matches, return the column name wrapped in single quotes ('')
                return "'$column_name'";
            }
        } elseif (is_numeric($column_name)) {
            // If $column_name is numeric, return it as is
            return $column_name;
        }

        // Throw exception if $column_name format is invalid
        throw new \InvalidArgumentException('Invalid arguments in fieldName');
    }

    /**
     * Retrieves or merges bind parameters ($values) used for prepared statements in SQL queries.
     *
     * @param array $values Optional. An array of bind parameters to merge or retrieve.
     *
     * @return array
     */
    public function getValues($values = [])
    {
        if (empty($values)) {
            // If $values array is empty, return the internal $this->values array
            return $this->values;
        }

        // If $values array is provided, merge it with the internal $this->values array
        foreach ($this->values as $key => $value) {
            $values[$key] = $value;
        }

        return $values;
    }

    /**
     * Quotes and prepares a value for use in SQL queries, handling various data types and formats.
     * Updates the $values array with bind parameters for prepared statements.
     *
     * @assert ('id', 'ทดสอบ', $array) [==] "'ทดสอบ'"
     * @assert ('id', 'test', $array) [==] "'test'"
     * @assert ('id', 'abcde012345', $array) [==] "'abcde012345'"
     * @assert ('id', 123456, $array) [==] 123456
     * @assert ('id', 0.1, $array) [==] 0.1
     * @assert ('id', null, $array) [==] 'NULL'
     * @assert ('id', 'U.id', $array) [==] "U.`id`"
     * @assert ('id', 'U.`id`', $array) [==] 'U.`id`'
     * @assert ('id', 'domain.tld', $array) [==] "'domain.tld'"
     * @assert ('id', 'table_name.`id`', $array) [==] '`table_name`.`id`'
     * @assert ('id', '`table_name`.id', $array) [==] '`table_name`.`id`'
     * @assert ('id', '`table_name`.`id`', $array) [==] '`table_name`.`id`'
     * @assert ('id', 'INSERT INTO', $array) [==] ':id0'
     * @assert ('id', array(1, '2', null), $array) [==] "(1, '2', NULL)"
     * @assert ('id', '0x64656', $array) [==] ':id0'
     * @assert ('id', 0x64656, $array) [==] 411222
     * @assert ('`table_name`.`id`', '0x64656', $array) [==] ':tablenameid0'
     * @assert ('U1.`id`', '0x64656', $array) [==] ':u1id0'
     * @assert ('U.id', '0x64656', $array) [==] ':uid0'
     *
     * @param string $column_name The column name or identifier to associate with the value.
     * @param mixed  $value       The value to quote and prepare for the query.
     * @param array  $values      Reference to an array to store bind parameters for prepared statements.
     *
     * @throws \InvalidArgumentException If the value format is invalid or not handled.
     *
     * @return string|int
     */
    public static function quoteValue($column_name, $value, &$values)
    {
        if (is_array($value)) {
            // If $value is an array, recursively quote each element
            $qs = [];
            foreach ($value as $v) {
                $qs[] = self::quoteValue($column_name, $v, $values);
            }
            $sql = '('.implode(', ', $qs).')';
        } elseif ($value === null) {
            // If $value is null, return 'NULL'
            $sql = 'NULL';
        } elseif ($value === '') {
            // If $value is an empty string, return "''"
            $sql = "''";
        } elseif (is_string($value)) {
            // Handle different types of strings
            if (preg_match('/^([0-9\s\r\n\t\.\_\-:]+)$/', $value)) {
                // If $value is numeric-like, date, or time format, wrap in single quotes
                $sql = "'$value'";
            } elseif (preg_match('/0x[0-9]+/is', $value)) {
                // If $value is hexadecimal, prepare a bind parameter
                $sql = ':'.strtolower(preg_replace('/[`\.\s\-_]+/', '', $column_name));
                if (empty($values) || !is_array($values)) {
                    $sql .= 0;
                } else {
                    $sql .= count($values);
                }
                // Store the bind parameter in $values array
                $values[$sql] = $value;
            } else {
                // Handle complex or potentially unsafe string values
                if (preg_match('/^(([A-Z][0-9]{0,2})|`([a-zA-Z0-9_]+)`)\.`?([a-zA-Z0-9_]+)`?$/', $value, $match)) {
                    // If $value matches a prefixed column name format, format accordingly
                    $sql = $match[3] == '' ? "$match[2].`$match[4]`" : "`$match[3]`.`$match[4]`";
                } elseif (preg_match('/^([a-zA-Z0-9_]+)\.`([a-zA-Z0-9_]+)`$/', $value, $match)) {
                    // If $value matches a table and column name format, format accordingly
                    $sql = "`$match[1]`.`$match[2]`";
                } elseif (!preg_match('/[\s\r\n\t`;\(\)\*\=<>\/\'"]+/s', $value) && !preg_match('/(UNION|INSERT|DELETE|TRUNCATE|DROP|0x[0-9]+)/is', $value)) {
                    // If $value is a safe plain string, wrap in single quotes
                    $sql = "'$value'";
                } else {
                    // If $value contains potential SQL keywords or unsafe characters, prepare a bind parameter
                    $sql = ':'.strtolower(preg_replace('/[`\.\s\-_]+/', '', $column_name));
                    if (empty($values) || !is_array($values)) {
                        $sql .= 0;
                    } else {
                        $sql .= count($values);
                    }
                    // Store the bind parameter in $values array
                    $values[$sql] = $value;
                }
            }
        } elseif (is_numeric($value)) {
            // If $value is numeric, return it as is
            $sql = $value;
        } elseif ($value instanceof self) {
            // If $value is an instance of Sql, get its text representation and update $values
            $sql = $value->text($column_name);
            $values = $value->getValues($values);
        } elseif ($value instanceof QueryBuilder) {
            // If $value is an instance of QueryBuilder, get its text representation and update $values
            $sql = '('.$value->text().')';
            $values = $value->getValues($values);
        } else {
            // Throw exception if $value format is invalid or not handled
            throw new \InvalidArgumentException('Invalid arguments in quoteValue');
        }

        return $sql;
    }

    /**
     * Creates a SQL string literal by wrapping the given value in single quotes ('').
     *
     * @param string $value The string value to be wrapped in single quotes.
     *
     * @return static
     */
    public static function strValue($value)
    {
        return self::create("'$value'");
    }

    /**
     * Returns the SQL command as a string.
     * If $sql is null, returns :$key for binding purposes.
     *
     * @param string|null $key The key used for binding (optional).
     *
     * @return string
     *
     * @throws \InvalidArgumentException When $key is provided but empty.
     */
    public function text($key = null)
    {
        if ($this->sql === null) {
            if (is_string($key) && $key != '') {
                return ':'.preg_replace('/[\.`]/', '', strtolower($key));
            } else {
                throw new \InvalidArgumentException('$key must be a non-empty string');
            }
        } else {
            return $this->sql;
        }
    }

    /**
     * Constructs SQL WHERE command based on given conditions.
     *
     * @param mixed  $condition The condition(s) to build into WHERE clause.
     * @param array  $values    Array to collect values for parameter binding.
     * @param string $operator  Logical operator (e.g., AND, OR) to combine multiple conditions.
     * @param string $id        Field name used as key in conditions.
     *
     * @return string
     */
    private function buildWhere($condition, &$values, $operator, $id)
    {
        // If $condition is an array, handle it recursively
        if (is_array($condition)) {
            $qs = [];

            // If $condition is a nested array of conditions
            if (is_array($condition[0])) {
                foreach ($condition as $item) {
                    // Handle QueryBuilder and self instances
                    if ($item instanceof QueryBuilder) {
                        $qs[] = '('.$item->text().')';
                        $values = $item->getValues($values);
                    } elseif ($item instanceof self) {
                        $qs[] = $item->text();
                        $values = $item->getValues($values);
                    } else {
                        // Recursively build each nested condition
                        $qs[] = $this->buildWhere($item, $values, $operator, $id);
                    }
                }
                // Combine nested conditions with $operator (AND/OR)
                $sql = count($qs) > 1 ? '('.implode(' '.$operator.' ', $qs).')' : implode(' '.$operator.' ', $qs);
            } else {
                // Handle simple array conditions
                if ($condition[0] instanceof QueryBuilder) {
                    $key = $condition[0]->text();
                    $values = $condition[0]->getValues($values);
                } elseif ($condition[0] instanceof self) {
                    $key = $condition[0]->text();
                    $values = $condition[0]->getValues($values);
                } elseif (preg_match('/^SQL(\(.*\))$/', $condition[0], $match)) {
                    $key = $match[1];
                } else {
                    // Convert field name using self::fieldName() method
                    $key = self::fieldName($condition[0]);
                }

                // Determine condition count
                $c = count($condition);

                // Handle conditions with two elements
                if ($c == 2) {
                    if ($condition[1] instanceof QueryBuilder) {
                        $operator = 'IN';
                        $value = '('.$condition[1]->text().')';
                        $values = $condition[1]->getValues($values);
                    } elseif ($condition[1] instanceof self) {
                        $operator = '=';
                        $value = $condition[1]->text();
                        $values = $condition[1]->getValues($values);
                    } elseif ($condition[1] === null) {
                        $operator = 'IS';
                        $value = 'NULL';
                    } else {
                        $operator = '=';
                        if (is_array($condition[1]) && $operator == '=') {
                            $operator = 'IN';
                        }
                        $value = self::quoteValue($key, $condition[1], $values);
                    }
                } elseif ($c == 3) {
                    // Handle conditions with three elements
                    if ($condition[2] instanceof QueryBuilder) {
                        $operator = trim($condition[1]);
                        $value = '('.$condition[2]->text().')';
                        $values = $condition[2]->getValues($values);
                    } elseif ($condition[2] instanceof self) {
                        $operator = trim($condition[1]);
                        $value = $condition[2]->text();
                        $values = $condition[2]->getValues($values);
                    } elseif ($condition[2] === null) {
                        $operator = trim($condition[1]);
                        if ($operator == '=') {
                            $operator = 'IS';
                        } elseif ($operator == '!=') {
                            $operator = 'IS NOT';
                        }
                        $value = 'NULL';
                    } else {
                        $operator = trim($condition[1]);
                        if (is_array($condition[2]) && $operator == '=') {
                            $operator = 'IN';
                        }
                        $value = self::quoteValue($key, $condition[2], $values);
                    }
                }

                // Construct final SQL statement based on condition type
                if (isset($value)) {
                    $sql = $key.' '.$operator.' '.$value;
                } else {
                    $sql = $key;
                }
            }
        } elseif ($condition instanceof QueryBuilder) {
            // Handle QueryBuilder instance
            $sql = '('.$condition->text().')';
            $values = $condition->getValues($values);
        } elseif ($condition instanceof self) {
            // Handle self instance
            $sql = $condition->text();
            $values = $condition->getValues($values);
        } elseif (preg_match('/^SQL\((.+)\)$/', $condition, $match)) {
            // Handle SQL command
            $sql = $match[1];
        } else {
            // Use $id as column_name to construct simple equality comparison
            $sql = self::fieldName($id).' = '.self::quoteValue($id, $condition, $values);
        }

        // Return the constructed SQL WHERE clause
        return $sql;
    }
}
