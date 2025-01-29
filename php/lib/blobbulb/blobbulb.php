<?php

namespace bllb;

use Error;

enum Op :int {
  case ARYSTA = 0x2c;
  case OBJSTA = 0x2d;
  case BLKEND = 0x3c;
  case DOCSTA = 0xbc;
  case DOCEND = 0xbd;
  
  case BOOL = 0x82;
  case FLASE = 0x30;
  case TRUE = 0x31;

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
  case STR1X = 0xc1;
  case STR2X = 0xd1;
  case STR4X = 0xe1;
  case STR8X = 0xf1;

  case NULL = 0x20;
}

function object2bllb($obj): string {
  $ctx = new BlobBuilder();
  variant($obj)->generateBlob($ctx);
  return $ctx->flush();
}

function isLinearArray($obj): bool{
  $i = 0;
  foreach($obj as $key) {
    if ($key !== $i++) return false;
  }
  return true;
}

function variant($value): IVariant {
  if ($value instanceof IVariant) {
    return $value;
  }
  else if (is_array( $value )) {
    if (isLinearArray($value)) {
      return new BArray($value);
    }
    else {
      return new BObject($value);
    }
  }
  else {
    return new BPrimitive($value);
  }
}

class BlobBuilder {
  private string $buff = '';

  public function __construct() {
    $this->rawOp(Op::DOCSTA);
    $this->rawU32(0x624c6c42);
    $this->rawU8(0x10);
    $this->rawU8(0x00);
    $this->rawU8(0x00);
    $this->rawU8(0x00);
  }

  public function flush(): string {
    $this->rawOp(Op::DOCEND);
    $this->rawU32(0);
    $this->rawU32(0);
    return $this->buff;
  }
  
  public function pushPrimitive($value): void {
    if (is_int($value) || is_float($value)) {
      $this->pushNumber($value);
    }
    else if (is_string($value)) {
      $this->pushStr($value);
    }
    else if (is_bool($value)) {
      $this->pushBool($value);
    }
    else if (is_null($value)) {
      $this->pushNull();
    }
    else {
      throw new Error('Unsupported type.');
    }
  }

  public function pushNumber(int $value): void {
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
    else {
      // todo impl FP32, FP64.
      throw new Error("Not implemented yet.");
    }
  }

  public function pushStr(string $value): void {
    $size = strlen($value);
    if ($size <= 4) {
      $this->rawOp(Op::STR4B);
      $this->buff .= $value . str_repeat('\0', 4 - $size);
    }
    else {
      $this->rawBlobOp(Op::STR1X, $size);
      $this->buff .= $value;
    }
  }

  public function pushBool(bool $val): void {
    $this->rawOp($val ? Op::TRUE : Op::FLASE);
  }

  public function pushNull(): void {
    $this->rawOp(Op::NULL);
  }
  
  public function rawOp(Op $op): void {
    $this->rawU8($op->value);
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
  
  public function rawBlobOp(Op $baseOp, int $size): int {
    if ($size <= 0xff) {
      $this->rawU8($baseOp->value);
      return 0;
    }
    else if ($size <= 0xffff) {
      $this->rawU16($baseOp->value + 0x10);
      return 1;
    }
    else if ($size <= 0xffffffff) {
      $this->rawU32($baseOp->value + 0x20);
      return 2;
    }
    else {
      $this->rawU64($baseOp->value + 0x30);
      return 3;
    }
  }
}

interface IVariant {
  public function generateBlob(BlobBuilder $ctx): void;
}

class BArray implements IVariant {
  private array $buff = array();
  public function __construct(array $obj) {
    foreach($obj as $v) {
      array_push($this->buff, variant($v));
    }
  }

  public function generateBlob(BlobBuilder $ctx): void {
    $ctx->rawOp(Op::ARYSTA);
    foreach($this->buff as $v) {
      $v->generateBlob($ctx);
    }
    $ctx->rawOp(Op::BLKEND);
  }
}

class BObject implements IVariant {
  private array $buff = array();
  public function __construct(array $obj) {
    foreach($obj as $k => $v) {
      $this->buff[$k] = variant($v);
    }
  }

  public function generateBlob(BlobBuilder $ctx): void {
    $ctx->rawOp(Op::OBJSTA);
    foreach($this->buff as $k => $v) {
      variant($k)->generateBlob($ctx);
      $v->generateBlob($ctx);
    }
    $ctx->rawOp(Op::BLKEND);
  }
}

class BPrimitive implements IVariant {
  private $value = null;
  public function __construct($value) {
    $this->value = $value;
  }

  public function generateBlob(BlobBuilder $ctx): void {
    $ctx->pushPrimitive($this->value);
  }
}

?>
