<?php

  // test::order('id', 'desc')
  // test::get(), select *
  // test::get($limit, $offset), select  limit 5, 1
  // test::get_where($where, $limit, $offset)
  // test::get_by($field, $value)
  // test::find(1), select *, where pk=1

  // test::update($data, $where)
  // test::save($data), insert into table values $data
  // test::delete(1), delete from table where pk=1
  // test::delete_where($where)
  // test::num_rows(), num rows after select
  // test::count_all(), count rows in db
  // test::empty_table()
  // test::num_queries() / return queries count

  // keep last result
  // keep last count
  // keep insert_id
  // keep num_rows

  class ORM
  {
    static $instance;
    private $_vars = array();

    public $db;

    public $_table;
    public $_pk;

    private $timer;

    private $queries = array();
    private $num_rows = NULL;
    private $insert_id = NULL;

    private $select_param = array();
    private $where_param = array();
    private $order_param = array();

    function __construct()
    {
      self::$instance =& $this;

      $this->db =& $GLOBALS['wpdb'];
    }

    static function select($fields)
    {
      self::$instance->select_param[] = $fields;

      return self::$instance;
    }

    static function order($field, $type = NULL)
    {
      self::$instance->order_param[$field] = $type;

      return self::$instance;
    }

    static function get($limit = NULL, $offset = NULL)
    {
      $self = self::$instance;

      $query = "SELECT %s FROM " . self::table();

      if (!empty($self->select_param)) {
        $query = sprintf($query, implode(', ', $self->select_param));
      }
      else
        $query = sprintf($query, "*");

      if (!empty($self->where_param)) {
        $query .= " WHERE " . $self->where_param;
      }

      if (!empty($self->order_param)) {
        $query .= " ORDER BY " . implode(", ",
                                         array_map(function($field, $type)
                                           {
                                             return $field . " " . $type;
                                           }, array_keys($self->order_param), array_values($self->order_param))
        );
      }

      if (!is_null($limit)) {
        $query .= " LIMIT " . $limit;
      }

      if (!is_null($offset)) {
        $query .= ", " . $offset;
      }

      $result = $self->db->get_results($query);

      $self->add_query();
      $self->num_rows = $self->db->num_rows;

      return $result;
    }

    static function get_where($where = array(), $limit = NULL, $offset = NULL)
    {
      if (!empty($where)) {

        $where[0] = "`" . $where[0] . "`";

        if (!is_numeric($where[2]))
          $where[2] = "'" . $where[2] . "'";

        self::$instance->where_param = implode(" ", $where);
      }

      return self::get($limit, $offset);
    }

    static function get_by($field = NULL, $value = NULL, $limit = NULL, $offset = NULL)
    {
      if (!empty($field) && !empty($value)) {

        if (!is_numeric($value))
          $value = "'" . $value . "'";

        $where = array("`" . $field . "`", '=', $value);

        self::$instance->where_param = implode(" ", $where);
      }

      return self::get($limit, $offset);
    }

    static function find($value = NULL)
    {
      if (!is_null($value)) {
        if (!is_numeric($value))
          $value = "'" . $value . "'";

        $where = array("`" . self::pk() . "`", '=', $value);

        self::$instance->where_param = implode(" ", $where);

        return self::get();
      }

      return FALSE;
    }

    static function count_all()
    {
      $self = self::$instance;

      $result = $self->db->get_var("SELECT COUNT(`" . $self::pk() . "`) FROM " . $self->table());

      $self->add_query();
      $self->num_rows = $self->db->num_rows;

      return $result;
    }

    static function update($data = array(), $where)
    {
      $self = self::$instance;

      $result = $self->db->update($self::table(), $data, $where);

      $self->add_query();
      $self->num_rows = $result;

      return $result;
    }


    static function last_query()
    {
      return end(self::$instance->queries);
    }

    static function queries()
    {
      return self::$instance->queries;
    }

    static function pk()
    {
      return self::$instance->_pk;
    }

    static function table()
    {
      return self::$instance->_table;
    }

    static function num_rows()
    {
      return self::$instance->num_rows;
    }

    private function add_query()
    {
      $this->queries[] = $this->db->last_query;
    }
  }