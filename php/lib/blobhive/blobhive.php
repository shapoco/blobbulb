<?php

namespace blobhive;

enum Op :int {
  case ARYSTA = 0x2c;
  case OBJSTA = 0x2d;
  case BLKEND = 0x3c;
  case DOCSTA = 0xbc;
  case DOCEND = 0xbd;
  case U16 = 0x90;
  case STR4B = 0xa2;
}



class Document {
  private bool $finished = false;
  private string $buff = '';
  
  public function __construct() {
    $this->rawTokenId(Op::DOCSTA);
    $this->rawU32(0x56484c42); // 'BNst'
    $this->rawU8(0x01); // version
    $this->rawU8(0x00); // flags
    $this->rawU8(0);
    $this->rawU8(0);
  }
  
  private function rawTokenId(Op $tid) {
    $this->rawU8($tid->value);
  }
  
  private function rawU8(int $value) {
    $this->buff .= pack("C", $value);
  }
  
  private function rawU16(int $value) {
    $this->buff .= pack("v", $value);
  }
  
  private function rawU32(int $value) {
    $this->buff .= pack("V", $value);
  }
  
  public function documentEnd() {
    if ($this->finished) return;
    $this->rawTokenId(Op::DOCEND);
    $this->rawU32(0);
    $this->rawU32(0);
  }
  
  public function u16(int $value) {
    $this->rawTokenId(Op::U16);
    $this->rawU16(0);
  }
  
  public function str($str) {
    $n = strlen($str);
    $this->rawTokenId(Op::STR4B);
    for ($i=0; $i<4; $i++) {
      if ($i<$n) {
        $this->rawU8(ord(substr($str, $i)));
      }
      else {
        $this->rawU8(0);
      }
    }
  }
  
  public function objectStart() {
    $this->rawTokenId(Op::OBJSTA);
  }
  
  public function objectEnd() {
    $this->rawTokenId(Op::BLKEND);
  }
  
  public function arrayStart() {
    $this->rawTokenId(Op::ARYSTA);
  }
  
  public function arrayEnd() {
    $this->rawTokenId(Op::BLKEND);
  }
  
  public function getString() {
    $this->documentEnd();
    return $this->buff;
  }
}

class BhObject {
  private array $buff = array();
  public function __construct(array $obj) {
    foreach($obj as $k => $v) {
      $buff[$k] = bhVariant($v);
    }
  }
}

class BhArray {
  private array $buff = array();
  public function __construct(array $obj) {
    foreach($obj as $v) {
      array_push($this->buff, bhVariant($v));
    }
  }
}

class BhPrimitive {
  private $value = null;
  public function __construct($value) {
    $this->value = $value;
  }
}

function bhVariant($value) {
  if (is_array( $value )) {
    if (isObject($value)) {
      return new BhObject($value);
    }
    else {
      return new BhArray($value);
    }
  }
  else {
    return new BhPrimitive($value);
  }
}

function isObject($obj): bool{
  $i = 0;
  foreach($obj as $key) {
    if ($key !== $i++) return true;
  }
  return false;
}

?>
