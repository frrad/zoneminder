<?php
namespace ZM;
require_once('Object.php');

class Filter extends ZM_Object {
  protected static $table = 'Filters';

  protected $defaults = array(
    'Id'              =>  null,
    'Name'            =>  '',
    'AutoExecute'     =>  0,
    'AutoExecuteCmd'  =>  '',
    'AutoEmail'       =>  0,
		'EmailTo'					=>	'',
		'EmailSubject'		=>	'',
		'EmailBody'				=>	'',
    'AutoDelete'      =>  0,
    'AutoArchive'     =>  0,
    'AutoVideo'       =>  0,
    'AutoUpload'      =>  0,
    'AutoMessage'     =>  0,
    'AutoMove'        =>  0,
    'AutoMoveTo'      =>  0,
    'AutoCopy'        =>  0,
    'AutoCopyTo'      =>  0,
    'UpdateDiskSpace' =>  0,
    'UserId'          =>  0,
    'Background'      =>  0,
    'Concurrent'      =>  0,
    'Query_json'      =>  '',
  );

  protected $_querystring;
  protected $_sql;
  protected $_hidden_fields;
  public $_pre_sql_conditions;
  public $_post_sql_conditions;
  protected $_Terms;

  public function sql() {
    if ( ! isset($this->_sql) ) {
      foreach ( $this->FilterTerms() as $term ) {
        #if ( ! ($term->is_pre_sql() or $term->is_post_sql()) ) {
          $this->_sql .= $term->sql();
        #} else {
          #$this->_sql .= '1';
        #}
      } # end foreach term
    }
    return $this->_sql;
  }

  public function querystring() {
    if ( ! isset($this->_querystring) ) {
      foreach ( $this->FilterTerms() as $term ) {
        $this->_querystring .= $term->querystring();
      } # end foreach term
    }
    return $this->_querystring;
  }

  public function hidden_fields() {
    if ( ! isset($this->_hidden_fields) ) {
      foreach ( $this->FilterTerms() as $term ) {
        $this->_hidden_fields .= $term->hidden_fields();
      } # end foreach term
    }
    return $this->_hidden_fields;
  }

  public function pre_sql_conditions() {
    if ( ! isset($this->_pre_sql_conditions) ) {
      $this->_pre_sql_conditions = array();
      foreach ( $this->FilterTerms() as $term ) {
        if ( $term->is_pre_sql() )
          $this->_pre_sql_conditions[] = $term;
      } # end foreach term
    }
    return $this->_pre_sql_conditions;
  }

  public function post_sql_conditions() {

    if ( ! isset($this->_post_sql_conditions) ) {
      $this->_post_sql_conditions = array();
      foreach ( $this->FilterTerms() as $term ) {
        if ( $term->is_post_sql() )
          $this->_post_sql_conditions[] = $term;
      } # end foreach term
    }
    return $this->_post_sql_conditions;
  }

  public function FilterTerms() { 
    if ( ! isset($this->Terms) ) {
      $this->Terms = array();
      $_terms = $this->terms();
      for ( $i = 0; $i < count($_terms); $i++ ) {
        $term = new FilterTerm($this, $_terms[$i], $i);
        $this->Terms[] = $term;
      } # end foreach term
    }
    return $this->Terms;
  }

  public static function parse($new_filter, $querySep='&amp;') {
    $filter = new Filter();
    $filter->Query($new_filter['Query']);
    return $filter;
  }

  # If no storage areas are specified in the terms, then return all
  public function get_StorageAreas() {
    $storage_ids = array();
    foreach ( $this->Terms as $term ) {
      if ( $term->attr == 'StorageId' ) {
        # TODO handle other operators like !=
        $storage_ids[] = $term->value;
      }
    }
    if ( count($storage_ids) ) {
      return Storage::find(array('Id'=>$storage_ids));
    } else {
      return Storage::find();
    }
  } # end function get_StorageAreas

  public function Query_json() {
    if ( func_num_args( ) ) {
      $this->{'Query_json'} = func_get_arg(0);
      $this->{'Query'} = jsonDecode($this->{'Query_json'});
    }
    return $this->{'Query_json'};
  }

  public function Query() {
    if ( func_num_args( ) ) {
      $this->{'Query'} = func_get_arg(0);
      $this->{'Query_json'} = jsonEncode($this->{'Query'});
    }
    if ( !property_exists($this, 'Query') ) {
      if ( property_exists($this, 'Query_json') and $this->{'Query_json'} ) {
        $this->{'Query'} = jsonDecode($this->{'Query_json'});
      } else {
        $this->{'Query'} = array();
      }
    } else {
      if ( !is_array($this->{'Query'}) ) {
        # Handle existence of both Query_json and Query in the row
        $this->{'Query'} = jsonDecode($this->{'Query_json'});
      }
    }
    return $this->{'Query'};
  }

  public static function find( $parameters = array(), $options = array() ) {
    return ZM_Object::_find(get_class(), $parameters, $options);
  }

  public static function find_one( $parameters = array(), $options = array() ) {
    return ZM_Object::_find_one(get_class(), $parameters, $options);
  }

  public function terms( ) {
    if ( func_num_args() ) {
      $Query = $this->Query();
      $Query['terms'] = func_get_arg(0);
      $this->Query($Query);
    }
    if ( isset( $this->Query()['terms'] ) ) {
      return $this->Query()['terms'];
    }
    return array();
  }

  // The following three fields are actually stored in the Query
  public function sort_field( ) {
    if ( func_num_args( ) ) {
      $Query = $this->Query();
      $Query['sort_field'] = func_get_arg(0);
      $this->Query($Query);
    }
    if ( isset( $this->Query()['sort_field'] ) ) {
      return $this->{'Query'}['sort_field'];
    }
    return ZM_WEB_EVENT_SORT_FIELD;
    #return $this->defaults{'sort_field'};
  }

  public function sort_asc( ) {
    if ( func_num_args( ) ) {
      $Query = $this->Query();
      $Query['sort_asc'] = func_get_arg(0);
      $this->Query($Query);
    }
    if ( isset( $this->Query()['sort_asc'] ) ) {
      return $this->{'Query'}['sort_asc'];
    }
    return ZM_WEB_EVENT_SORT_ORDER == 'asc' ? 1 : 0;
    #return $this->defaults{'sort_asc'};
  }

  public function limit( ) {
    if ( func_num_args( ) ) {
      $Query = $this->Query();
      $Query['limit'] = func_get_arg(0);
      $this->Query($Query);
    }
    if ( isset( $this->Query()['limit'] ) )
      return $this->{'Query'}['limit'];
    return 100;
    #return $this->defaults{'limit'};
  }

  public function control($command, $server_id=null) {
    $Servers = $server_id ? Server::find(array('Id'=>$server_id)) : Server::find(array('Status'=>'Running'));
    if ( !count($Servers) ) {
      if ( !$server_id ) {
        # This will be the non-multi-server case
        $Servers = array(new Server());
      } else {
        Warning("Server not found for id $server_id");
      }
    }
    foreach ( $Servers as $Server ) {

      if ( (!defined('ZM_SERVER_ID')) or (!$Server->Id()) or (ZM_SERVER_ID==$Server->Id()) ) {
        # Local
        Logger::Debug("Controlling filter locally $command for server ".$Server->Id());
        daemonControl($command, 'zmfilter.pl', '--filter_id='.$this->{'Id'}.' --daemon');
      } else {
        # Remote case

        $url = $Server->UrlToIndex();
        if ( ZM_OPT_USE_AUTH ) {
          if ( ZM_AUTH_RELAY == 'hashed' ) {
            $url .= '?auth='.generateAuthHash(ZM_AUTH_HASH_IPS);
          } else if ( ZM_AUTH_RELAY == 'plain' ) {
            $url = '?user='.$_SESSION['username'];
            $url = '?pass='.$_SESSION['password'];
          } else if ( ZM_AUTH_RELAY == 'none' ) {
            $url = '?user='.$_SESSION['username'];
          }
        }
        $url .= '&view=filter&object=filter&action=control&command='.$command.'&Id='.$this->Id().'&ServerId='.$Server->Id();
        Logger::Debug("sending command to $url");
        $data = array();
        if ( defined('ZM_ENABLE_CSRF_MAGIC') ) {
          require_once( 'includes/csrf/csrf-magic.php' );
          $data['__csrf_magic'] = csrf_get_tokens();
        }

        // use key 'http' even if you send the request to https://...
        $options = array(
          'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
          )
        );
        $context  = stream_context_create($options);
        try {
          $result = file_get_contents($url, false, $context);
          if ( $result === FALSE ) { /* Handle error */
            Error("Error restarting zmfilter.pl using $url");
          }
        } catch ( Exception $e ) {
          Error("Except $e thrown trying to restart zmfilter");
        }
      } # end if local or remote
    } # end foreach erver
  } # end function control

  public function execute() {
    $command = ZM_PATH_BIN.'/zmfilter.pl --filter_id='.escapeshellarg($this->Id());
    $result = exec($command, $output, $status);
    Logger::Debug("$command status:$status output:".implode("\n", $output));
    return $status;
  }

  public function test_pre_sql_conditions() {
    if ( !count($this->pre_sql_conditions()) ) {
      return true;
    } # end if pre_sql_conditions

    $failed = false;
    foreach ( $this->pre_sql_conditions() as $term ) {
      if ( !$term->test() ) {
        $failed = true;
        break;
      }
    }
    return $failed;
  }

  public function test_post_sql_conditions($event) {
    if ( !count($this->post_sql_conditions()) ) {
      return true;
    } # end if pre_sql_conditions

    $failed = true;
    foreach ( $this->post_sql_conditions() as $term ) {
      if ( !$term->test($event) ) {
        $failed = false;
        break;
      }
    }
    return $failed;
  }


} # end class Filter

?>
