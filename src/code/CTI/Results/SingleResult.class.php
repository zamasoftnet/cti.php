<?php
require_once('Results.class.php');
require_once(dirname(__FILE__).'/../Builder/NullBuilder.class.php');

class SingleResult implements Results {
  private $builder;
  
  public function __construct($builder) {
    $this->builder = $builder;
  }

  public function next_builder($opts = null) {
    if (!isset($this->builder)) {
      return new NullBuilder();
    }
    $builder = $this->builder;
    unset($this->builder);
    return $builder;
  }
}
?>