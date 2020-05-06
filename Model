<?php


class BaseModel
{
    const SQL_ACTION_INSERT = 'INSERT INTO';
    const SQL_ACTION_UPDATE = 'UPDATE';
    const SQL_ACTION_DELETE = 'DELETE';
    const SQL_ACTION_SELECT = 'SELECT';
    const SQL_FROM = 'FROM';
    const SQL_WHERE = 'WHERE';
    const SQL_LIMIT = 'LIMIT';
    const SQL_ORDER_BY = 'ORDER BY';
    const SQL_GROUP_BY = 'GROUP BY';
    const SQL_VALUES = 'VALUES';
    const SQL_AND = 'AND';
    const SQL_OR = 'OR';

    private $model;

    protected $table;
    private $selectedColumns;
    private $query;
    private $_where = [];
    private $limit;
    private $orderBy;
    private $groupBy;
    private $action;
    private $sets;
    private $fields;
    private $values;
    private $condition;

    /**
     * create connection
     * @param $host
     * @param $user
     * @param $password
     * @param null $name database name
     */
    public function __construct( $host , $user , $password , $name )
    {
        $this->connection($host,$user,$password,$name);
    }

    /**
     * @param $host
     * @param $user
     * @param $password
     * @param $name
     * @return false|resource
     * @throws \Exception
     */
    private function connection( $host , $user , $password , $name )
    {
        if(!is_resource($this->model) || empty($this->model)){
            if( $this->model = mysqli_connect($host, $user, $password,$name)){
                $this->model->set_charset('utf8');
            }else{
                throw new \Exception('Could not connect to MySQL database.');
            }
        }
        return true;
    }

    /**
     * @param $tableName
     * @return $this
     */
    public function table($tableName)
    {
        $this->table = $tableName;
        return $this;
    }

    /**
     * @param $selectedColumns
     * @return $this
     */
    public function select(Array $selectedColumns = [])
    {
        if( $this->action )
            throw new \Exception("SQL action is already installed");

        $this->action = self::SQL_ACTION_SELECT;
        if(!empty($selectedColumns)){
            $this->selectedColumns = implode(',',$selectedColumns);
        }else{
            $this->selectedColumns = '*';
        }
        return $this;
    }

    /**
     * @param String $key
     * @param String $operator
     * @param String $value
     * @return $this
     */
    public function where(String $key,String $operator, String $value)
    {
       if( !empty($this->condition) ){
           $this->condition .= " AND ";
       }
       $this->condition .= "(`$key` $operator $value)";
       return $this;
    }

    /**
     * @param array $conditions
     * @param string $separator
     * @return $this
     * @throws \Exception
     */
    public function orWhere(Array $conditions, $separator = SQL_AND )
    {
        return $this->_where( $conditions , $separator , SQL_OR );
    }

    /**
     * @param array $conditions
     * @return $this
     * @throws \Exception
     */
    public function andWhere( Array $conditions )
    {
        return $this->_where( $conditions );
    }

    
    private function _where(Array $conditions, $separator = SQL_AND , $operator = SQL_AND )
    {
        if( !empty($this->condition) ){
            $this->condition .=  sprintf(" %s ", $operator);
        }
        foreach ($conditions as $condition ){
            list($operator,$key,$values) = $condition;
            if( in_array(strtolower($operator),['in','not in'])){
                if(!is_array($values) ){
                    throw new \Exception('value must be an array');
                }else{
                    $value = "";
                    foreach ($values as $item){
                        $value .= sprintf("'%s',", $item);
                    }
                    $value = substr($value, 0, -1);
                    $this->_where[] = "`$key` $operator ( $value )";
                }
            }else{
                if( !is_string($values)){
                    throw new \Exception('value must be an string');
                }else{
                    $this->_where[] = "`$key` $operator $value ";
                }
            }
        }
        $this->condition .= " ( ".implode($separator,$this->andWhere) . " ) ";
        return $this;
    }

    /**
     * @param String $key
     * @param String|null $order
     */
    public function orderBy(String $key,String $order = null)
    {
        if( !in_array(strtolower($order),['asc','desc']) ){
            $order = 'asc';
        }
        $this->orderBy = self::SQL_ORDER_BY ." `$key` $order";
        return $this;
    }

    /**
     * @param String $key
     * @return $this
     */
    public function groupBy(String $key)
    {
        $this->groupBy = self::SQL_GROUP_BY ." `$key` ";
        return $this;
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return $this
     * @throws \Exception
     */
    public function limit(int $limit, int $offset = 0)
    {
        if( !empty($this->limit) )
            throw new \Exception('Limit already installed');
        $this->limit = self::SQL_LIMIT ." ". $offset.",".$limit;
        return $this;
    }
    /**
     * @return mixed
     * @throws \Exception
     */
    public function one()
    {
        if ($result = $this->model->query($this->querySQL())) {
            if ($result->num_rows > 0) {
                return $result->fetch_object();
            }
        }else{
            throw new \Exception("Error description: " . $this->model->error);
        }
        return new \stdClass();
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function all()
    {
        if( $result = $this->model->query($this->querySQL()) ){
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        throw new \Exception("Error description: " . $this->model->error);
    }

    public function insert($values)
    {
        if( $this->action )
            throw new \Exception("SQL action is already installed");

        $this->action = self::SQL_ACTION_INSERT;
    }

    public function update($condition = [],$sets = [])
    {
        if( $this->action )
            throw new \Exception("SQL action is already installed");

        $this->action = self::SQL_ACTION_UPDATE;
    }

    public function delete($condition = [])
    {
        if( $this->action )
            throw new \Exception("SQL action is already installed");

        $this->action = self::SQL_ACTION_DELETE;
    }

    /**
     * @return string
     */
    private function querySQL()
    {
        switch ($this->action) {
            case self::SQL_ACTION_SELECT:
                $this->query = $this->_select();
                break;
            case self::SQL_ACTION_INSERT:
                $this->query = $this->_insert();
                break;
            case self::SQL_ACTION_DELETE:
                $this->query = $this->_delete();
                break;
            case self::SQL_ACTION_UPDATE:
                $this->query = $this->_update();
                break;
        }
        return $this->query;
    }

    /**
     * @return string
     */
    private function _select()
    {
        $query = self::SQL_ACTION_SELECT. ' '. $this->selectedColumns .' '.self::SQL_FROM.' '.$this->table.' ';
        if( $this->condition ){
            $query .= self::SQL_WHERE.' '.$this->condition.' ';
        }
        if( $this->groupBy ) {
            $query .= $this->groupBy.' ';
        }
        if( $this->orderBy ) {
            $query .= $this->orderBy.' ';
        }
        if( $this->limit ) {
            $query .= $this->limit.' ';
        }
        return $query;
    }

    /**
     * @return string
     */
    public function _insert()
    {
        $query = self::SQL_ACTION_INSERT .' %s (%s) '.self::SQL_VALUES.' (%s)';
        $query = sprintf($query, $this->table, $this->fields, $this->values);
        return $query;
    }

    /**
     * @return string
     */
    public function  _delete()
    {
        $query = self::SQL_ACTION_DELETE. ' '. $this->selectedColumns .' '.self::SQL_FROM.' '.$this->table.' ';
        if( $this->condition ){
            $query .= self::SQL_WHERE.' '.$this->condition;
        }
        return $query;
    }

    /**
     * @return string
     */
    public function _update()
    {
        $query = self::SQL_ACTION_UPDATE.' '.$this->table.' ';
        if( $this->sets ){
            $query .= self::SQL_SET.' '.$this->sets.' ';
        }
        if( $this->condition ){
            $query .= self::SQL_WHERE.' '.$this->condition.' ';
        }
        return $query;
    }

    public function sql()
    {
        return $this->querySQL();
    }
}
$db = new BaseModel('localhost','root','','db');
$user = $db->table('users')
    ->select(['name','email as E_Mail'])
    ->where('id','=',11)
    ->andWhere([
        ['in','name',['Jone','Doe','Admin']]
    ])
    ->limit(14)
    ->orderBy('id','DESC')
    ->one();
var_dump($user);die;
