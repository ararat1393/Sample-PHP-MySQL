<?php

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'vue' );
/** MySQL database username */
define( 'DB_USER', 'root' );
/** MySQL database password */
define( 'DB_PASSWORD', '' );
/** MySQL hostname */
define( 'DB_HOST', 'localhost' );
/** Database Charset to use in creating database tables. */

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
    const SQL_ASC = 'ASC';
    const SQL_DESC = 'DESC';
    const SQL_BETWEEN = 'BETWEEN';
    const SQL_NOT_BETWEEN = 'NOT BETWEEN';
    const SQL_SET = 'SET';

    private $model;

    private static $table;
    private $selectedColumns;
    private static $query;
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
     * BaseModel constructor.
     * @throws Exception
     */
    public function __construct( $table = null )
    {
        if( $table ) self::$table = $table;
        $this->connection(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME);
    }

    /**
     * @param $host
     * @param $result
     * @param $password
     * @param $name
     * @return false|resource
     * @throws \Exception
     */
    private function connection( $host , $result , $password , $name )
    {
        if(!is_resource($this->model) || empty($this->model)){
            if( $this->model = mysqli_connect($host, $result, $password,$name)){
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
        return new BaseModel($tableName);
    }

    /**
     * @param $selectedColumns
     * @return $this
     */
    public function select(Array $selectedColumns = [])
    {
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
     * @param $value
     * @return $this
     */
    public function where(String $key,String $operator, $value)
    {
       if( !empty($this->condition) ){
           $this->condition .= sprintf(" %s " , self::SQL_AND);
       }
       $this->condition .= sprintf(" (`%s` %s '%s') " ,$key,$operator,$value);
       return $this;
    }

    /**
     * @param array $conditions
     * @param string $separator
     * @return $this
     * @throws \Exception
     */
    public function orWhere(Array $conditions, $separator = self::SQL_AND )
    {
        return $this->_where( $conditions , $separator , self::SQL_OR );
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

    /**
     * @param String $key
     * @param $value_1
     * @param $value_2
     * @return $this
     * @throws Exception
     */
    public function between(String $key,$value_1,$value_2)
    {
        if( !empty($this->condition) ){
            $this->condition .= sprintf(" %s " , self::SQL_AND);
        }
        $this->condition .= sprintf(" ( `%s` %s '%s' %s '%s') ",$key,self::SQL_BETWEEN,$value_1,self::SQL_AND,$value_2);
        return $this;
    }

    /**
     * @param String $key
     * @param $value_1
     * @param $value_2
     * @return $this
     * @throws Exception
     */
    public function notBetween( String $key ,$value_1,$value_2 )
    {
        if( !empty($this->condition) ){
            $this->condition .= sprintf(" %s " , self::SQL_AND);
        }
        $this->condition .= sprintf(" ( `%s` %s '%s' %s '%s') ",$key,self::SQL_NOT_BETWEEN,$value_1,self::SQL_AND,$value_2);
        return $this;
    }

    /**
     * @param array $conditions
     * @param $separator
     * @param $operator
     * @return $this
     * @throws Exception
     */
    private function _where(Array $conditions, $separator = self::SQL_AND , $operator = self::SQL_AND )
    {

        if( !empty($this->condition) ){
            $this->condition .=  sprintf(" %s ", $operator);
        }
        $this->_where = [];
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
                    $this->_where[] = sprintf("`%s` %s (%s)",$key,$operator,$value);
                }
            }else{
                if( !is_string($values)){
                    throw new \Exception('value must be an string');
                }else{
                    $this->_where[] = sprintf("`%s` %s '%s'",$key,$operator,$values);
                }
            }
        }
        $this->condition .= " ( ".implode($separator,$this->_where) . " ) ";
        return $this;
    }

    /**
     * @param String $key
     * @param String|null $order
     */
    public function orderBy(String $key,String $order = self::SQL_ASC)
    {
        if( !in_array(strtoupper($order),[self::SQL_ASC,self::SQL_DESC]) ){
            $order = self::SQL_ASC;
        }
        $this->orderBy = sprintf(" %s `%s` %s ",self::SQL_ORDER_BY,$key,$order);
        return $this;
    }

    /**
     * @param String $key
     * @return $this
     */
    public function groupBy(String $key)
    {
        $this->groupBy = sprintf(" %s `%s` ",self::SQL_GROUP_BY ,$key );
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
        $this->limit = sprintf(" %s %u,%u ",self::SQL_LIMIT, $offset , $limit );
        return $this;
    }
    /**
     * @return mixed
     * @throws \Exception
     */
    public function one()
    {
        if ($result = $this->model->query($this->_select())) {
            if ($result->num_rows > 0)
                return $result->fetch_object();
        }
        else
            throw new \Exception("Error description: " . $this->model->error);

        return new \stdClass();
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function all()
    {
        if( $result = $this->model->query($this->_select()) )
            return $result->fetch_all(MYSQLI_ASSOC);
        else
            throw new \Exception("Error description: " . $this->model->error);
        return [];
    }

    /**
     * @param $values
     * @return mixed|stdClass|null
     * @throws Exception
     */
    public function insert($values)
    {
        if( $this->action )
            throw new \Exception("SQL action is already installed");
        if(empty($values))
            throw new \Exception("Value can't be empty");

        $this->action = self::SQL_ACTION_INSERT;

        foreach($values as $column => $value){
            $this->fields .= sprintf("`%s`,", $column);
            $this->values .= sprintf("'%s',", addslashes($value));
        }
        $this->fields = substr($this->fields, 0, -1);
        $this->values = substr($this->values, 0, -1);

        if( $this->model->query( $this->_insert() )){
            return $this->select()
                ->where('id','=',$this->model->insert_id)
                ->one();
        }
        return true;
    }

    /**
     * @param array $sets
     * @return bool
     * @throws Exception
     */
    public function update( Array $sets )
    {
        if( $this->action )
            throw new \Exception("SQL action is already installed");
        $this->action = self::SQL_ACTION_UPDATE;

        foreach($sets as $column => $value){
            $this->sets .= sprintf(" `%s`='%s',", $column , addslashes($value) );
        }
        $this->sets = substr($this->sets, 0, -1);

        if( $this->model->query( $this->_update())) return true;

        return false;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function delete()
    {
        if( $this->action )
            throw new \Exception("SQL action is already installed");
        $this->action = self::SQL_ACTION_DELETE;

        if( $this->model->query( $this->_delete())) return true;

        return false;
    }

    /**
     * @return string
     */
    private function _select()
    {
        self::$query = sprintf(" %s %s %s %s ",self::SQL_ACTION_SELECT,$this->selectedColumns,self::SQL_FROM,self::$table);

        if( $this->condition ){
            self::$query .= sprintf(" %s %s ",self::SQL_WHERE , $this->condition );
        }
        if( $this->groupBy ) {
            self::$query .= sprintf(" %s ",$this->groupBy );
        }
        if( $this->orderBy ) {
            self::$query .= sprintf(" %s ",$this->orderBy );
        }
        if( $this->limit ) {
            self::$query .= sprintf(" %s ",$this->limit );
        }
        return self::$query;
    }

    /**
     * @return string
     */
    public function _insert()
    {
        return sprintf(" %s %s (%s) %s (%s) ",self::SQL_ACTION_INSERT , self::$table , $this->fields ,self::SQL_VALUES,$this->values);
    }

    /**
     * @return string
     */
    public function  _delete()
    {
        self::$query = sprintf(" %s  %s %s ",self::SQL_ACTION_DELETE,self::SQL_FROM,self::$table);
        if( $this->condition ){
            self::$query .= sprintf(" %s %s ",self::SQL_WHERE,$this->condition);
        }
        return self::$query;
    }

    /**
     * @return string
     */
    public function _update()
    {
        self::$query = sprintf(" %s %s " ,self::SQL_ACTION_UPDATE , self::$table );
        if( $this->sets ){
            self::$query .= sprintf(" %s %s ",self::SQL_SET ,$this->sets );
        }
        if( $this->condition ){
            self::$query .= sprintf(" %s %s ",self::SQL_WHERE,$this->condition );
        }
        return self::$query;
    }

    /**
     * @return string
     */
    public function query()
    {
        switch ($this->action) {
            case self::SQL_ACTION_SELECT:
                self::$query = $this->_select();
                break;
            case self::SQL_ACTION_INSERT:
                self::$query = $this->_insert();
                break;
            case self::SQL_ACTION_DELETE:
                self::$query = $this->_delete();
                break;
            case self::SQL_ACTION_UPDATE:
                self::$query = $this->_update();
                break;
        }
        return self::$query;
    }

    /**
     * @return mixed
     */
    public function error()
    {
        return  $this->model->error;
    }
}
$db = new BaseModel();
$result1 = $db->table('users')
    ->select(['name','email as E_Mail'])
    ->where('id','!=',18)
    ->andWhere([
        ['in','name',['Jone','Doe','Admin']]
    ])
    ->orWhere([
        ['like','email','ararat.martirosyan13@gmail.com']
    ])
    ->between('created_at','2020-05-01 14:58:51','2020-05-02 14:58:51')
    ->notBetween('created_at','2020-05-01 14:58:51','2020-05-02 14:58:51')
    ->limit(14)
    ->orderBy('id','DESC')
    ->one();
var_dump($db->error());
var_dump($result1);
$result2 = $db->table('users')->insert(['name'=>'test','email'=>'test@gmail.com','password'=>'test']);
var_dump($db->error());
var_dump($result2);
$result3 = $db->table('users')->where('name','=','test8')->delete();
var_dump($db->error());
var_dump($result3);
$result4 = $db->table('users')->where('name','=','test8')->update(['name' => 'test9']);
var_dump($db->error());
var_dump($result4);die;

