<?php

/**
 * performance trace tool
 * @author liujianchun
 */
class TCPerformanceTracer{
  public static $trace_items = array();
  private static $unclosed_trace_items = array();
  
  /**
   * add a trace item directly
   * @param string $tag
   * @param float $time millisecond cost of this trace event
   */
  public static function addTraceItem($tag, $time){
    $item = new TCPerformanceTracerItem();
    $item->time = $time;
    $item->tag = $tag;
    self::$trace_items[] = $item;
  }
  
  /**
   * get a summary array of the current trace items
   * @return array
   */
  public static function summary($items=null){
    if(!Yaf_Application::app()->getConfig()->get('performance.trace.enable')) return;
    $execute_time_of_tags = array();
    if($items===null) $items = self::$trace_items;
    if(!empty($items)){
      foreach($items as $item){
        if(!isset($execute_time_of_tags[$item->tag])) $execute_time_of_tags[$item->tag] = 0;
        $execute_time_of_tags[$item->tag] += $item->time;
        if(!empty($item->children)){
          $children_execute_times = self::summary($item->children);
          foreach($children_execute_times as $tag=>$time){
            if($tag==$item->tag) continue;
            if(!isset($execute_time_of_tags[$tag])) $execute_time_of_tags[$tag] = 0;
            $execute_time_of_tags[$tag] += $time;
          }
        }
      }
    }
    arsort($execute_time_of_tags);
    return $execute_time_of_tags;
  }
  
  public static function convertTraceItemsToJsonObject(){
    $json = array();
    foreach(self::$trace_items as $item){
      $item->sortChildren();
    }
    usort(self::$trace_items, array(__CLASS__, '_compareItem'));
    foreach(self::$trace_items as $item){
      $json[] = $item->toJsonObject();
    }
    return $json;
  }
  
  private static function _compareItem($a, $b){
    return $a->time < $b->time;
  }
  
  
  /**
   * start a performance trace with a tag
   * @param string $tag
   */
  public static function start($tag){
    if(!Yaf_Application::app()->getConfig()->get('performance.trace.enable')) return;
    $item = new TCPerformanceTracerItem();
    $item->tag = $tag;
    $item->start_time = microtime(true);
    self::$unclosed_trace_items[] = $item;
  }

  
  /**
   * end a performance trace with a tag
   * @param string $tag
   */
  public static function end($method, $file, $line){
    if(!Yaf_Application::app()->getConfig()->get('performance.trace.enable')) return;
    $item = self::$unclosed_trace_items[count(self::$unclosed_trace_items)-1];
    $item->method = $method;
    $item->file = $file;
    $item->line = $line;
    $item->time = (microtime(true) - $item->start_time) * 1000;
    array_pop(self::$unclosed_trace_items);
    if(!empty(self::$unclosed_trace_items)){
      self::$unclosed_trace_items[count(self::$unclosed_trace_items)-1]->children[] = $item;
    }else{
      self::$trace_items[] = $item;
    }
  }
  
}

class TCPerformanceTracerItem{
  /**
   * the tag of this trace item
   */
  public $tag;
  /**
   * the method of this trace item happened
   */
  public $method;
  /**
   * the file path of this trace item happened
   */
  public $file;
  /**
   * the line number of this trace item happened
   */
  public $line;
  /**
   * execute time of this trace item at millisecond
   */
  public $time;
  /**
   * time when this trace item start
   */
  public $start_time;
  /**
   * child trace items
   */
  public $children = array();
  
  public function toJsonObject(){
    $json = new stdClass();
    $json->time = $this->time;
    $json->tag = $this->tag;
    $json->method = $this->method;
    $json->file = $this->file;
    $json->line = $this->line;
    if(!empty($this->children)){
      $json->children = array();
      foreach($this->children as $c){
        $json->children[] = $c->toJsonObject();
      }
    }
    return $json;
  }
  
  public function sortChildren(){
    if(empty($this->children)) return;
    usort($this->children, array(__CLASS__, '_compareItem'));
    foreach($this->children as $child){
      $child->sortChildren();
    }
  }
  
  private static function _compareItem($a, $b){
    return $a->time < $b->time;
  }
}

