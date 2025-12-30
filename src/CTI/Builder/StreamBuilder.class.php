<?php
require_once('Builder.class.php');
require_once(dirname(__FILE__).'/../CTIP2.php');

/**
 * 標準出力、ストリーム、変数のいずれかに出力します。
 *
 * @param $out mixed 出力先
 * @param $buff string データ
 * @access private
 */
function _cti_output(&$out, &$buff) {
  if ($out === null || $out === FALSE) {
    //標準出力
    echo $buff;
    $out = FALSE;
  }
  elseif (is_resource($out)) {
    //ストリーム
    if (fwrite($out, $buff) === false) {
      throw new Exception("I/O Error");
    }
  }
  else {
    //変数
    $out .= $buff;
  }
}

class Fragment {
  public $id;
  public $prev = null;
  public $next = null;
  private $length = 0;
  private $buffer = '';
  private $segments;
  private $segLen;
    
  public function __construct($id) {
    $this->id = $id;
  }

/**
 * フラグメントにデータを書き込みます。
 * 
 * @param $tempFile resource 一時ファイル
 * @param $onMemory int メモリ上に置かれたデータの合計サイズ
 * @param $segment int セグメント番号シーケンス
 * @param $frg array フラグメント
 * @param $bytes string データ
 * @return int 書き込んだバイト数
 */
  public function write(&$tempFile, &$onMemory, &$segment, &$bytes) {
    $len = strlen($bytes);
    if (!isset($this->segments) &&
        ($this->length + $len) <= CTI_CTIP_FRG_MEM_SIZE &&
        ($onMemory + $len) <= CTI_CTIP_ON_MEMORY) {
      $this->buffer .= $bytes;
      $onMemory += $len;
    }
    else {
      if (isset($this->buffer)) {
        $wlen = $this->raf_write($tempFile, $segment, $this->buffer);
        $onMemory -= $wlen;
        unset($this->buffer);
      }
      $len = $this->raf_write($tempFile, $segment, $bytes);
    }
    $this->length += $len;
    return $len;
  }

/**
 * 一時ファイルに書き込みます。
 *
 * @param $tempFile resource 一時ファイル
 * @param $segment int セグメント番号シーケンス
 * @param $frg array フラグメント
 * @param $bytes string データ
 * @return int 書き込んだバイト数
 */
  private function raf_write(&$tempFile, &$segment, $bytes) {
  	if (!isset($this->segments)) {
      $this->segments = array($segment++);
      $this->segLen = 0;
    }
    $written = 0;
    while (($len = strlen($bytes)) > 0) {
	  if ($this->segLen == CTI_CTIP_SEGMENT_SIZE) {
		$this->segments[] = $segment++;
		$this->segLen = 0;
	  }
	  $seg = $this->segments[count($this->segments) - 1];
	  $wlen = min($len, CTI_CTIP_SEGMENT_SIZE - $this->segLen);
	  $wpos = $seg * CTI_CTIP_SEGMENT_SIZE + $this->segLen;
	  if (fseek($tempFile, $wpos) === -1) {
	    throw new Exception("I/O Error");
	  }
	  if (($wlen = fwrite($tempFile, $bytes, $wlen)) === false) {
	    throw new Exception("I/O Error");
	  }
	  $this->segLen += $wlen;
	  $written += $wlen;
	  $bytes = substr($bytes, $wlen);
    }
    return $written;
  }

/**
 * フラグメントの内容を吐き出して、フラグメントを破棄します。
 * 
 * @param $tempFile resource 一時ファイル
 * @param $frg array フラグメント
 * @param $out mixed 出力先ストリーム(resource),出力先変数(string),または標準出力であればnull。
 */
  public function flush(&$tempFile, &$out) {
    if (!isset($this->segments)) {
      _cti_output($out, $this->buffer);
      unset($this->buffer);
    }
    else {
      $segcount = count($this->segments);
      for ($i = 0; $i < $segcount - 1; ++$i) {
        $seg = $this->segments[$i];
        $rpos = $seg * CTI_CTIP_SEGMENT_SIZE;
        if (fseek($tempFile, $rpos) == -1) {
          throw new Exception("I/O Error");
        }
        $buff = _cti_read($tempFile, CTI_CTIP_SEGMENT_SIZE);
        _cti_output($out, $buff);
      }
      $seg = $this->segments[$segcount - 1];
      $rpos = $seg * CTI_CTIP_SEGMENT_SIZE;
      if (fseek($tempFile, $rpos) == -1) {
        throw new Exception("I/O Error");
      }
      $buff = _cti_read($tempFile, $this->segLen);
      _cti_output($out, $buff);
    }
  }
}

class StreamBuilder implements Builder {
  protected $out;
  protected $tempFile;
  protected $frgs = array();
  protected $first = null;
  protected $last = null;
  protected $onMemory = 0;
  protected $length = 0;
  protected $segment = 0;
  
  public function __construct(&$out = null) {
    $this->tempFile = tmpfile();
  	$this->out =& $out;
  }

  public function add_block () {
    $id = count($this->frgs);
    $frg = new Fragment($id);
    $this->frgs[$id] =& $frg;
    if ($this->first === null) {
      $this->first =& $frg;
    }
    else {
      $this->last->next =& $frg;
      $frg->prev =& $this->last;
    }
    $this->last =& $frg;
  }
  
  public function insert_block_before ($anchor_id) {
    $id = count($this->frgs);
    $anchor =& $this->frgs[$anchor_id];
    $frg = new Fragment($id);
    $this->frgs[$id] =& $frg;
    $frg->prev =& $anchor->prev;
    $frg->next =& $anchor;
    $anchor->prev->next =& $frg;
    $anchor->prev =& $frg;
    if ($this->first->id === $anchor->id) {
      $this->first =& $frg;
    }
  }
  
  public function write($id, &$data) {
  	$frg =& $this->frgs[$id];
    $this->length += $frg->write($this->tempFile,
    	$this->onMemory, $this->segment, $data);
  }
  
  public function close_block($id) {
  	// NOP
  }
  
  public function serial_write (&$data) {
  	_cti_output($this->out, $data);
  }
  
  public function finish () {
    if ($this->out === null) {
      header("Content-Length: ".$this->length);
    }
    $frg =& $this->first;
    while ($frg !== null) {
      $frg->flush($this->tempFile, $this->out);
      $frg =& $frg->next;
    }
    fclose($this->tempFile);
  }
  
  public function dispose () {
  	// NOP
  }
}
?>