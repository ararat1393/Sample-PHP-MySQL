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

/** @var string $table */
/** @var array $selectedColumns */
/** @var string $query */
/** @var string $error */
/** @var array $_where */
/** @var string $limit */
/** @var string $orderBy */
/** @var string $groupBy */
/** @var string $action */
/** @var string $sets */
/** @var string $fields */
/** @var string $values */
/** @var string $condition */
/** @var string $join */

class DB
{
    const SQL_ACTION_INSERT = 'INSERT INTO';
    const SQL_ACTION_UPDATE = 'UPDATE';
    const SQL_ACTION_DELETE = 'DELETE';
    const SQL_ACTION_SELECT = 'SELECT';
    const SQL_INNER_JOIN = 'INNER JOIN';
    const SQL_LEFT_JOIN = 'LEFT JOIN';
    const SQL_RIGHT_JOIN = 'RIGHT JOIN';
    const SQL_JOIN_ON = 'ON';
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

    private $table;
    private $selectedColumns;
    private $query;
    private $error;
    private $_where = [];
    private $limit;
    private $orderBy;
    private $groupBy;
    private $action;
    private $sets;
    private $fields;
    private $values;
    private $condition;
    private $join;

    /**
     * BaseModel constructor.
     * @throws Exception
     */
    public function __construct( $table = null )
    {
        // TODO: Implement __construct() method.
        $this->connection(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME);
        $this->init( $table );
    }


    public function __destruct()
    {
        // TODO: Implement __destruct() method.
        if( is_resource($this->model) )
            $this->model->close();
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
     * @param null $table
     */
    public function init( $table = null )
    {
        $this->table = $table;
    }

    /**
     * @param $tableName
     * @return $this
     */
    public static function table($tableName)
    {
        return new self($tableName);
    }

    /**
     * @param array $selectedColumns
     * @return $this
     * @throws Exception
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
        $this->_where( $conditions , $separator , self::SQL_OR );
        return $this;
    }

    /**
     * @param array $conditions
     * @return $this
     * @throws \Exception
     */
    public function andWhere( Array $conditions )
    {
        $this->_where( $conditions );
        return $this;
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
        $this->_between(self::SQL_BETWEEN,$key,$value_1,$value_2);
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
        $this->_between(self::SQL_NOT_BETWEEN,$key,$value_1,$value_2);
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
            $this->error = $this->model->error;

        return null;
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
            $this->error = $this->model->error;

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
        }else{
            $this->error = $this->model->error;
        }
        return $this;
    }

    /**
     * @param array $sets
     * @return DB
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

        else $this->error = $this->model->error;

        return $this;
    }

    /**
     * @return DB
     * @throws Exception
     */
    public function delete()
    {
        if( $this->action )
            throw new \Exception("SQL action is already installed");

        $this->action = self::SQL_ACTION_DELETE;

        if( $this->model->query( $this->_delete())) return true;

        else $this->error = $this->model->error;

        return $this;
    }

    /**
     * @param $join | ex "Full"
     * @param String $table | "posts"
     * @param $relation_1 | ex "`users`.id"
     * @param $relation_2   | ex "`posts`.user_id"
     * @return $this
     */
    public function join($join = self::SQL_INNER_JOIN,String $table , String $relation_1,String $relation_2)
    {
        $this->_join($join, $table ,$relation_1 ,$relation_2);
        return $this;
    }

    /**
     * @param String $table | "posts"
     * @param String $relation_1 | ex "`users`.id"
     * @param String $relation_2 | ex "`posts`.user_id"
     * @return $this
     */
    public function leftJoin(String $table , String $relation_1,String $relation_2 )
    {
        $this->_join(self::SQL_LEFT_JOIN , $table ,$relation_1 ,$relation_2);
        return $this;
    }

    /**
     * @param String $table | ex "posts"
     * @param String $relation_1 | ex "`users`.id"
     * @param String $relation_2 | ex "`posts`.user_id"
     * @return $this
     */
    public function rightJoin(String $table , String $relation_1,String $relation_2)
    {
        $this->_join(self::SQL_RIGHT_JOIN , $table ,$relation_1 ,$relation_2);
        return $this;
    }

    /**
     * @param $join
     * @param $table
     * @param $relation_1
     * @param $relation_2
     * @return string
     */
    private function _join( $join ,$table , $relation_1 ,$relation_2 )
    {
        if( !empty($this->join) ){
            $this->join .= PHP_EOL;
        }
        return $this->join .= sprintf(" %s `%s` %s %s=%s ",$join,$table,self::SQL_JOIN_ON ,$relation_1,$relation_2);
    }

    /**
     * @param $between
     * @param $key
     * @param $value_1
     * @param $value_2
     * @return string
     */
    private function _between($between,$key,$value_1,$value_2)
    {
        if( !empty($this->condition) ){
            $this->condition .= sprintf(" %s " , self::SQL_AND);
        }
        return $this->condition .= sprintf(" ( `%s` %s '%s' %s '%s') ",$key,$between,$value_1,self::SQL_AND,$value_2);
    }

    /**
     * @param array $conditions
     * @param $separator
     * @param $operator
     * @return string
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
        return $this->condition .= sprintf(" (%s) ",implode($separator,$this->_where));
    }

    /**
     * @return string
     */
    private function _select()
    {
        $this->query = sprintf(" %s %s %s %s ",self::SQL_ACTION_SELECT,$this->selectedColumns,self::SQL_FROM,$this->table);

        if( $this->join ){
            $this->query .= sprintf("%s",$this->join);
        }
        if( $this->condition ){
            $this->query .= sprintf(" %s %s ",self::SQL_WHERE , $this->condition );
        }
        if( $this->groupBy ) {
            $this->query .= sprintf(" %s ",$this->groupBy );
        }
        if( $this->orderBy ) {
            $this->query .= sprintf(" %s ",$this->orderBy );
        }
        if( $this->limit ) {
            $this->query .= sprintf(" %s ",$this->limit );
        }
        return $this->query;
    }

    /**
     * @return string
     */
    private function _insert()
    {
        return sprintf(" %s %s (%s) %s (%s) ",self::SQL_ACTION_INSERT , $this->table , $this->fields ,self::SQL_VALUES,$this->values);
    }

    /**
     * @return string
     */
    private function  _delete()
    {
        $this->query = sprintf(" %s  %s %s ",self::SQL_ACTION_DELETE,self::SQL_FROM,$this->table);
        if( $this->condition ){
            $this->query .= sprintf(" %s %s ",self::SQL_WHERE,$this->condition);
        }
        return $this->query;
    }

    /**
     * @return string
     */
    private function _update()
    {
        $this->query = sprintf(" %s %s " ,self::SQL_ACTION_UPDATE , $this->table );
        if( $this->sets ){
            $this->query .= sprintf(" %s %s ",self::SQL_SET ,$this->sets );
        }
        if( $this->condition ){
            $this->query .= sprintf(" %s %s ",self::SQL_WHERE,$this->condition );
        }
        return $this->query;
    }

    /**
     * @param String $query
     * @return mixed
     */
    public function execute( String $query )
    {
        if ($result = $this->model->query($query)) {
            if ($result->num_rows && $result->num_rows > 0)
                $result =  $result->fetch_all(MYSQLI_ASSOC);
        }
        else
            $this->error = $this->model->error;

        return $result;
    }

    /**
     * @return string
     */
    public function query()
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
     * @return mixed
     */
    public function error()
    {
        return  $this->error;
    }
}
$db = new DB('users');
//var_dump($db->execute('INSERT INTO users (`name`,`email`,`password`) VALUES (\'test\',\'tes000t@gmail.com\',\'test\')'));
$result1 = $db->select()
    ->leftJoin('social_accounts','users.id','social_accounts.user_id')
    ->where('id','!=',18)
    ->andWhere([
        ['in','name',['Jone','Doe','Admin']]
    ])
    ->orWhere([
        ['like','email','ararat.martirosyan13@gmail.com']
    ])
    ->limit(2)
    ->orderBy('id','DESC');
//    ->all();
//var_dump($db->error());
//var_dump($result1);
//var_dump(DB::query());
//var_dump($db);
$result2 = DB::table('users')->insert(['name'=>'test','email'=>'tes000t@gmail.com','password'=>'test']);
var_dump($result2->error());
var_dump($result2->query());
var_dump($result1->all());
//var_dump(DB::query());
var_dump($result1->query());die;

