# BlobNest (WIP)

universal binary data container.

## Basic Token Shape

### Fixed-Length (`FIXn`)

- `n` = 0, 1, 2, 4, or 8

|Offset|Size \[Bytes\]|Mnemonic|Description|
|:--:|:--:|:--:|:--|
|+0|1|`TID`|Token ID|
|+1|`n` defined by `TID`|`DATA`|Data|

### Variable Length Number (`VLEN`)

|Offset|Size \[Bytes\]|Mnemonic|Description|
|:--:|:--:|:--:|:--|
|+0|1|`TID`|Token ID|
|+1|1-8|`VALUE_VLQ`|Data expressed in VLQ|

### Byte Sequence (`BSEQ`)

|Offset|Size \[Bytes\]|Mnemonic|Description|
|:--:|:--:|:--:|:--|
|+0|1|`TID`|Token ID|
|+1|1-8|`SIZE_VLQ`|Length of `DATA` in bytes, expressed in VLQ|
|+(1 + sizeof(`SIZE_VLQ`))|`SIZE`|`DATA`|Byte sequence|

----

## Token Mnemonic and Shapes

- empty cell: reserved for future

### C-Stlye Number Primitive

|TID|0x00|0x01|0x02|0x03|0x04|0x05|0x06|0x07|
|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
|0x80|U8|U16|U32|U64|BOOL|||TIME|
|0x90|I8|I16|I32|I64|||||
|0xA0|||F32|F64|||||
|0xB0|||||||||
|Shape|FIX1|FIX2|FIX4|FIX8|FIX1|FIX2|FIX4|FIX8|

### C-Stlye Number Primitive Array

|TID|0x00|0x01|0x02|0x03|0x04|0x05|0x06|0x07|
|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
|0xC0|U8A|U16A|U32A|U64A|BOOLA|||TIMEA|
|0xD0|I8A|I16A|I32A|I64A|||||
|0xE0|||F32A|F64A|||||
|0xF0|||||||||
|Shape|BSEQ|BSEQ|BSEQ|BSEQ|BSEQ|BSEQ|BSEQ|BSEQ|

### Non-C-Stlye Number Primitive

|TID|0x08|0x09|0x0A|0x0B|0x0C|0x0D|0x0E|0x0F|
|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
|0x80|NULL||||UVL|IVL|STR||
|0x90|||||||||
|0xA0|||||||||
|0xB0|||||||||
|Shape|FIX0|FIX0|FIX0|FIX0|VLEN|VLEN|BSEQ|BSEQ|

### Control Token

|TID|0x08|0x09|0x0A|0x0B|0x0C|0x0D|0x0E|0x0F|
|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
|0xC0|META||OBJST|OBJED|||DOCST|DOCED|
|0xD0|||ARYST|ARYED|||||
|0xE0|||||||||
|0xF0|PAD||||||COM||
|Shape|FIX0|FIX0|FIX0|FIX0|VLEN|VLEN|BSEQ|BSEQ|

----

## Syntax

### Document

```
DOCST
 |
 V
Meta Data Array
 |
 +--> Variant --,
 |              |
 |<-------------'
 |
 |
 V
DOCED
```

- No tokens can be placed before `DOCST`.
- No tokens can be placed after `DOCED`.

### Document Start (`DOCST`)

|Offset|Mnemonic|Description|
|:--:|:--:|:--|
|+0|`DOCST`|Token ID|
|+1|`SIZE_VLQ`|Size = 0x06|
|+2|-|0x42 (`'B'`)|
|+3|-|0x4e (`'N'`)|
|+4|`FMTVER`|Version|
|+5|`FMTFLG`|Flags|
|+6|-|reserved|
|+7|-|reserved|

#### Version (`FMTVER`)

- `0x01`

#### Flags (`FMTFLG`)

|Bit Range|Mnemonic|Description|
|:--:|:--:|:--|
|\[7\]|`CRCEN`|CRC Enable|
|\[6:0\]|-|reserved|

### Document End (`DOCED`)

|Offset|Mnemonic|Description|
|:--:|:--:|:--|
|+0|`DOCED`|Token ID|
|+1|`SIZE_VLQ`|Size = 0x06|
|+2|-|reserved|
|+3|-|reserved|
|+4|`CRC[7:0]`|CRC|
|+5|`CRC[15:8]`|CRC|
|+6|`CRC[23:16]`|CRC|
|+7|`CRC[31:24]`|CRC|

#### CRC (`CRC`)

|Condition|Value of CRC|
|:--:|:--|
|`CRCEN` = 0|`0x00000000`|
|`CRCEN` = 1|CRC Value|

- CRC calculation must be performed on all bytes from the beginning of `DOCST` to before `CRC` (including `PAD` and `COM`).
- Polynomial for CRC: `0x04c11db7`.

### Variant

```
 |
 +---------+---------,
 |         |         |
 V         V         V
Object   Array   Primitive
 |         |         |
 |         V         |
 |<------------------'
 |
 V
```

### Object

```
 |
 V
OBJST
 |
 V
Meta Data Array
 |
 |<--------------------,
 |                     |
 +--> Key Value Pair --'  // member of the object
 |
 V
OBJED
 |
 V
```

### Array

```
 |
 V
ARYST
 |
 V
Meta Data Array
 |
 |<-------------,
 |              |
 +--> Variant --'  // element of the array
 |
 V
ARYED
 |
 V
```

### Meta Data Array

```
 |
 |<-----------------------------,
 |                              |
 +--> META --> Key Value Pair --'
 |
 V
```

### Key Value Pair

```
 |
 +----,
 |    |
 V    V
STR  UVL  // Key
 |    |
 |<---'
 |
 V
Variant  // Value
 |
 V
```

- for object member of JSON, only STR can be used for Key.

----

## Primitive Value

### Integer (`Un`, `In`)

- `n` = 8, 16, 32, or 64

|Offset|U8, I8|U16, I16|U32, I32|U64, I64|Description|
|:--:|:--:|:--:|:--:|:--:|:--|
|+0|`TID`|`TID`|`TID`|`TID`|Token ID|
|+1|`VALUE[7:0]`|`VALUE[7:0]`|`VALUE[7:0]`|`VALUE[7:0]`|Value|
|+2||`VALUE[15:8]`|`VALUE[15:8]`|`VALUE[15:8]`|Value|
|+3|||`VALUE[23:16]`|`VALUE[23:16]`|Value|
|+4|||`VALUE[31:24]`|`VALUE[31:24]`|Value|
|+5||||`VALUE[39:32]`|Value|
|+6||||`VALUE[47:40]`|Value|
|+7||||`VALUE[55:48]`|Value|
|+8||||`VALUE[63:56]`|Value|

- `VALUE`: unsigned/signed integer value (two's complement)

### Floating Point (`Fn`)

- `n` = 32 or 64

|Offset|F32|F64|Description|
|:--:|:--:|:--:|:--|
|+0|`TID`|`TID`|Token ID|
|+1|`VALUE[7:0]`|`VALUE[7:0]`|Value|
|+2|`VALUE[15:8]`|`VALUE[15:8]`|Value|
|+3|`VALUE[23:16]`|`VALUE[23:16]`|Value|
|+4|`VALUE[31:24]`|`VALUE[31:24]`|Value|
|+5||`VALUE[39:32]`|Value|
|+6||`VALUE[47:40]`|Value|
|+7||`VALUE[55:48]`|Value|
|+8||`VALUE[63:56]`|Value|

- `VALUE`: floating point value (IEEE754)

### Boolean (`BOOL`)

|Offset|Mnemonic|Description|
|:--:|:--:|:--|
|+0|`BOOL`|Token ID|
|+1|`VALUE`|Value|

|`VALUE`|Mnemonic|
|:--:|:---|
|0x00|`false`|
|0x01-0xff|`true`|

### Date Time (`TIME`)

|Offset|Mnemonic|Description|
|:--:|:--:|:--|
|+0|`TIME`|Token ID|
|+1|`UNIX_TIME[7:0]`|UNIX time|
|+2|`UNIX_TIME[15:8]`|UNIX time|
|+3|`UNIX_TIME[23:16]`|UNIX time|
|+4|`UNIX_TIME[31:24]`|UNIX time|
|+5|`UNIX_TIME[39:32]`|UNIX time|
|+6|`UNIX_TIME[47:40]`|UNIX time|
|+7|`UNIX_TIME[55:48]`|UNIX time|
|+8|-|reserved|

- `UNIX_TIME`:
    - UNIX time in milliseconds
    - 56 bit signed integer

----

## Primitive Array (`xxxA`)

- Packed array of primitive values.
- Number of elements in array = `floor(number of bytes of DATA / element size)`.

----

## Non-Primitive Value

### String (`STR`)

- Byte array of UTF-8.

### Null (`NULL`)

- Same as `null` of JavaScript.

----

## Padding and Comments

- Paddings and Comments should be ignored at parsing.

### Padding (`PAD`)

- Can be used for data alignment.

### Comment (`COM`)

- Byte array of UTF-8.

----

## Pre-defined Meta Data IDs

(no IDs defined yet)

----
