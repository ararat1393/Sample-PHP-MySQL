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

    private static $table;
    private static $selectedColumns;
    private static $query;
    private static $error;
    private static $_where = [];
    private static $limit;
    private static $orderBy;
    private static $groupBy;
    private static $action;
    private static $sets;
    private static $fields;
    private static $values;
    private static $condition;
    private static $join;

    /**
     * BaseModel constructor.
     * @throws Exception
     */
    public function __construct( $table = null )
    {
        $this->connection(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME);
        $this->init( $table );
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
        self::$table = $table;
        self::$selectedColumns = [];
        self::$query = "";
        self::$error = "";
        self::$_where = [];
        self::$limit = "";
        self::$orderBy = "";
        self::$groupBy = "";
        self::$action = "";
        self::$sets = "";
        self::$fields = "";
        self::$values = "";
        self::$condition = "";
        self::$join = "";
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
     * @param $selectedColumns
     * @return $this
     */
    public function select(Array $selectedColumns = [])
    {
        self::$action = self::SQL_ACTION_SELECT;
        if(!empty($selectedColumns)){
            self::$selectedColumns = implode(',',$selectedColumns);
        }else{
            self::$selectedColumns = '*';
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
       if( !empty(self::$condition) ){
           self::$condition .= sprintf(" %s " , self::SQL_AND);
       }
       self::$condition .= sprintf(" (`%s` %s '%s') " ,$key,$operator,$value);
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
        self::_where( $conditions , $separator , self::SQL_OR );
        return $this;
    }

    /**
     * @param array $conditions
     * @return $this
     * @throws \Exception
     */
    public function andWhere( Array $conditions )
    {
        self::_where( $conditions );
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
        self::_between(self::SQL_BETWEEN,$key,$value_1,$value_2);
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
        self::_between(self::SQL_NOT_BETWEEN,$key,$value_1,$value_2);
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
        self::$orderBy = sprintf(" %s `%s` %s ",self::SQL_ORDER_BY,$key,$order);
        return $this;
    }

    /**
     * @param String $key
     * @return $this
     */
    public function groupBy(String $key)
    {
        self::$groupBy = sprintf(" %s `%s` ",self::SQL_GROUP_BY ,$key );
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
        if( !empty(self::$limit) )
            throw new \Exception('Limit already installed');
        self::$limit = sprintf(" %s %u,%u ",self::SQL_LIMIT, $offset , $limit );
        return $this;
    }
    /**
     * @return mixed
     * @throws \Exception
     */
    public function one()
    {
        if ($result = $this->model->query(self::_select())) {
            if ($result->num_rows > 0)
                return $result->fetch_object();
        }
        else
            self::$error = $this->model->error;

        return new \stdClass();
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function all()
    {
        if( $result = $this->model->query(self::_select()) )
            return $result->fetch_all(MYSQLI_ASSOC);
        else
            self::$error = $this->model->error;

        return [];
    }

    /**
     * @param $values
     * @return mixed|stdClass|null
     * @throws Exception
     */
    public function insert($values)
    {
        if( self::$action )
            throw new \Exception("SQL action is already installed");
        if(empty($values))
            throw new \Exception("Value can't be empty");

        self::$action = self::SQL_ACTION_INSERT;

        foreach($values as $column => $value){
            self::$fields .= sprintf("`%s`,", $column);
            self::$values .= sprintf("'%s',", addslashes($value));
        }
        self::$fields = substr(self::$fields, 0, -1);
        self::$values = substr(self::$values, 0, -1);

        if( $this->model->query( self::_insert() )){
            return $this->select()
                ->where('id','=',$this->model->insert_id)
                ->one();
        }else{
            self::$error = $this->model->error;
        }
        return null;
    }

    /**
     * @param array $sets
     * @return bool
     * @throws Exception
     */
    public function update( Array $sets )
    {
        if( self::$action )
            throw new \Exception("SQL action is already installed");

        self::$action = self::SQL_ACTION_UPDATE;

        foreach($sets as $column => $value){
            self::$sets .= sprintf(" `%s`='%s',", $column , addslashes($value) );
        }

        self::$sets = substr(self::$sets, 0, -1);

        if( $this->model->query( self::_update())) return true;

        else self::$error = $this->model->error;

        return false;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function delete()
    {
        if( self::$action )
            throw new \Exception("SQL action is already installed");

        self::$action = self::SQL_ACTION_DELETE;

        if( $this->model->query( self::_delete())) return true;

        else self::$error = $this->model->error;

        return false;
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
        self::_join($join, $table ,$relation_1 ,$relation_2);
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
        self::_join(self::SQL_LEFT_JOIN , $table ,$relation_1 ,$relation_2);
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
        self::_join(self::SQL_RIGHT_JOIN , $table ,$relation_1 ,$relation_2);
        return $this;
    }

    /**
     * @param $join
     * @param $table
     * @param $relation_1
     * @param $relation_2
     * @return string
     */
    public static function _join( $join ,$table , $relation_1 ,$relation_2 )
    {
        if( !empty(self::$join) ){
            self::$join .= PHP_EOL;
        }
        return self::$join .= sprintf(" %s `%s` %s %s=%s ",$join,$table,self::SQL_JOIN_ON ,$relation_1,$relation_2);
    }

    /**
     * @param $between
     * @param $key
     * @param $value_1
     * @param $value_2
     * @return string
     */
    public static function _between($between,$key,$value_1,$value_2)
    {
        if( !empty(self::$condition) ){
            self::$condition .= sprintf(" %s " , self::SQL_AND);
        }
        return self::$condition .= sprintf(" ( `%s` %s '%s' %s '%s') ",$key,$between,$value_1,self::SQL_AND,$value_2);
    }

    /**
     * @param array $conditions
     * @param $separator
     * @param $operator
     * @return string
     * @throws Exception
     */
    private static function _where(Array $conditions, $separator = self::SQL_AND , $operator = self::SQL_AND )
    {

        if( !empty(self::$condition) ){
            self::$condition .=  sprintf(" %s ", $operator);
        }
        self::$_where = [];
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
                    self::$_where[] = sprintf("`%s` %s (%s)",$key,$operator,$value);
                }
            }else{
                if( !is_string($values)){
                    throw new \Exception('value must be an string');
                }else{
                    self::$_where[] = sprintf("`%s` %s '%s'",$key,$operator,$values);
                }
            }
        }
        return self::$condition .= sprintf(" (%s) ",implode($separator,self::$_where));
    }

    /**
     * @return string
     */
    private static function _select()
    {
        self::$query = sprintf(" %s %s %s %s ",self::SQL_ACTION_SELECT,self::$selectedColumns,self::SQL_FROM,self::$table);

        if( self::$join ){
            self::$query .= sprintf("%s",self::$join);
        }
        if( self::$condition ){
            self::$query .= sprintf(" %s %s ",self::SQL_WHERE , self::$condition );
        }
        if( self::$groupBy ) {
            self::$query .= sprintf(" %s ",self::$groupBy );
        }
        if( self::$orderBy ) {
            self::$query .= sprintf(" %s ",self::$orderBy );
        }
        if( self::$limit ) {
            self::$query .= sprintf(" %s ",self::$limit );
        }
        return self::$query;
    }

    /**
     * @return string
     */
    public static function _insert()
    {
        return sprintf(" %s %s (%s) %s (%s) ",self::SQL_ACTION_INSERT , self::$table , self::$fields ,self::SQL_VALUES,self::$values);
    }

    /**
     * @return string
     */
    public static function  _delete()
    {
        self::$query = sprintf(" %s  %s %s ",self::SQL_ACTION_DELETE,self::SQL_FROM,self::$table);
        if( self::$condition ){
            self::$query .= sprintf(" %s %s ",self::SQL_WHERE,self::$condition);
        }
        return self::$query;
    }

    /**
     * @return string
     */
    public static function _update()
    {
        self::$query = sprintf(" %s %s " ,self::SQL_ACTION_UPDATE , self::$table );
        if( self::$sets ){
            self::$query .= sprintf(" %s %s ",self::SQL_SET ,self::$sets );
        }
        if( self::$condition ){
            self::$query .= sprintf(" %s %s ",self::SQL_WHERE,self::$condition );
        }
        return self::$query;
    }

    /**
     * @return string
     */
    public static function query()
    {
        switch (self::$action) {
            case self::SQL_ACTION_SELECT:
                self::$query = self::_select();
                break;
            case self::SQL_ACTION_INSERT:
                self::$query = self::_insert();
                break;
            case self::SQL_ACTION_DELETE:
                self::$query = self::_delete();
                break;
            case self::SQL_ACTION_UPDATE:
                self::$query = self::_update();
                break;
        }
        return self::$query;
    }

    /**
     * @return mixed
     */
    public static function error()
    {
        return  self::$error;
    }
}
$db = new DB('users');
$result1 = $db->select()
    ->leftJoin('social_accounts','users.id','social_accounts.user_id')
    ->where('id','!=',18)
    ->andWhere([
        ['in','name',['Jone','Doe','Admin','Արարատ Մարտիրոսյան']]
    ])
    ->orWhere([
        ['like','email','ararat.martirosyan13@gmail.com']
    ])
    ->limit(2)
    ->orderBy('id','DESC')
    ->all();
//var_dump($db->error());
var_dump($result1);
var_dump(DB::query());
//var_dump($db);
$result2 =DB::table('users')->insert(['name'=>'test','email'=>'test@gmail.com','password'=>'test']);
var_dump(DB::error());
//var_dump($result2);die;

