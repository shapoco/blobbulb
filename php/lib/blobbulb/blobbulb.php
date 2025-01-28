<?php

namespace bllb;

enum Op :int {
  case ARYSTA = 0x2c;
  case OBJSTA = 0x2d;
  case BLKEND = 0x3c;
  case DOCSTA = 0xbc;
  case DOCEND = 0xbd;
  case U6D00 = 0x40;
  case U8 = 0x80;
  case U16 = 0x90;
  case U32 = 0xa0;
  case U64 = 0xb0;
  case I8 = 0x81;
  case I16 = 0x91;
  case I32 = 0xa1;
  case I64 = 0xb1;
  case STR4B = 0xa2;
  case STR1L = 0xc1;
  case STR2L = 0xd1;
  case STR4L = 0xe1;
  case STR8L = 0xf1;
}

function obj2bllb($obj): string {

}

function variant($value): IVariant {
  if (is_array( $value )) {
    if (isObject($value)) {
      return new BObject($value);
    }
    else {
      return new BArray($value);
    }
  }
  else {
    return new BPrimitive($value);
  }
}

class GenerationContext {
  private string $buff;
  
  public function rawOp(Op $op): void {
    $this->rawU8($op->value);
  }
  
  public function rawBlobOp(Op $baseOp, int $size): int {
    if ($size <= 0xff) {
      $this->rawU8($baseOp->value);
      return 0;
    }
    else if ($size <= 0xffff) {
      $this->rawU8($baseOp->value + 0x10);
      return 1;
    }
    else if ($size <= 0xffffffff) {
      $this->rawU8($baseOp->value + 0x20);
      return 2;
    }
    else {
      $this->rawU8($baseOp->value + 0x30);
      return 3;
    }
  }
  
  public function rawU8(int $value): void {
    $this->buff .= pack("C", $value);
  }
  
  public function rawU16(int $value): void {
    $this->buff .= pack("v", $value);
  }
  
  public function rawU32(int $value): void {
    $this->buff .= pack("V", $value);
  }

  public function rawU64(int $value): void {
    $this->buff .= pack("V", $value & 0xffffffff); $value >>= 32;
    $this->buff .= pack("V", $value & 0xffffffff);
  }

  public function intNum(int $value): void {
    if (is_float($value) && floor($value) == $value) {
      $value = (int)floor($value);
    }
    if (is_int($value)) {
      if (0 <= $value) {
        if ($value <= 0x3f) {
          $this->rawU8(Op::U6D00->value + $value);
        }
        else if ($value <= 0xff) {
          $this->rawOp(Op::U8);
          $this->rawU8($value);
        }
        else if ($value <= 0xffff) {
          $this->rawOp(Op::U16);
          $this->rawU16($value);
        }
        else if ($value <= 0xffffffff) {
          $this->rawOp(Op::U32);
          $this->rawU32($value);
        }
        else {
          $this->rawOp(Op::U64);
          $this->rawU64($value);
        }
      }
      else {
        if ($value >= -0x80) {
          $this->rawOp(Op::I8);
          $this->rawU8($value);
        }
        else if ($value >= -0x8000) {
          $this->rawOp(Op::I16);
          $this->rawU16($value);
        }
        else if ($value >= -0x80000000) {
          $this->rawOp(Op::I32);
          $this->rawU32($value);
        }
        else {
          $this->rawOp(Op::I64);
          $this->rawU64($value);
        }
      }
    }
  }

  public function str(string $str): void {
    $size = strlen($str);
    if ($size <= 4) {
      $this->rawOp(Op::STR4B);
      $this->buff .= $str . str_repeat('\0', 4 - $size);
    }
    else {
      $this->rawBlobOp(Op::STR1L, $size);
    }
  }
}

interface IVariant {
  public function generateBllb(GenerationContext $ctx): void;
}

class BArray implements IVariant {
  private array $buff = array();
  public function __construct(array $obj) {
    foreach($obj as $v) {
      array_push($this->buff, variant($v));
    }
  }

  public function generateBllb(GenerationContext $ctx): void {
    $ctx->rawOp(Op::ARYSTA);
    foreach($this->buff as $v) {
      $v->generateBllb($ctx);
    }
    $ctx->rawOp(Op::BLKEND);
  }
}

class BObject implements IVariant {
  private array $buff = array();
  public function __construct(array $obj) {
    foreach($obj as $k => $v) {
      $buff[$k] = variant($v);
    }
  }

  public function generateBllb(GenerationContext $ctx): void {
    $ctx->rawOp(Op::OBJSTA);
    foreach($this->buff as $k => $v) {
      variant($k)->generateBllb($ctx);
      $v->generateBllb($ctx);
    }
    $ctx->rawOp(Op::BLKEND);
  }
}

class BPrimitive implements IVariant {
  private $value = null;
  public function __construct($value) {
    $this->value = $value;
  }

  public function generateBllb(GenerationContext $ctx): void {
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
