# Universal Binary Container (WIP)

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

## Token ID (`TID`)

|TID\[3:0\]→<br>↓TID\[7:4\]|0x0|0x1|0x2|0x3|0x4-0xF|Token<br>Shape|
|:--:|:--|:--|:--|:--|:--|:--:|
|0x0|PAD|META||||FIX0|
|0x1|OSTA|OEND|ASTA|AEND||FIX0|
|0x2|DSTA|DEND||||FIX4|
|0x3|COM|||||BSEQ|
|0x4|NULL|||||FIX0|
|0x5||||||FIX0|
|0x6|UVL|IVL||||VLEN|
|0x7|STR|||||BSEQ|
|0x8|U8|I8||BOOL||FIX1|
|0x9|U16|I16||||FIX2|
|0xA|U32|I32|F32|||FIX4|
|0xB|U64|I64|F64|TIME||FIX8|
|0xC|U8A|I8A||BOOLA||BSEQ|
|0xD|U16A|I16A||||BSEQ|
|0xE|U32A|I32A|F32A|||BSEQ|
|0xF|U64A|I64A|F64A|TIMEA||BSEQ|

- empty cell: reserved for future

----

## Syntax

### Document

```
- - - - - - - - - - - - -
DSTA                  A
 |                    |
 V                    |
Meta Data Array       |
 |                    | CRC calculation target
 +--> Variant --,     |
 |              |     |
 |<-------------'     |
 |                    V
-|- - - - - - - - - - - -
 V
DEND
```

- No tokens can be placed before `DSTA`.
- No tokens can be placed after `DEND`.

### Start of Document (`DSTA`)

|Offset|Mnemonic|Description|
|:--:|:--:|:--|
|+0|`DSTA`|Token ID|
|+1|`FMTVER`|Version|
|+2|`FMTFLG`|Flags|
|+3|-|reserved|
|+4|-|reserved|

#### Version (`FMTVER`)

- `0x01`

#### Flags (`FMTFLG`)

|Bit Range|Mnemonic|Description|
|:--:|:--:|:--|
|\[7\]|`CRCEN`|CRC Enable|
|\[6:0\]|-|reserved|

### End of Document (`DEND`)

|Offset|Mnemonic|Description|
|:--:|:--:|:--|
|+0|`DEND`|Token ID|
|+1|`CRC[7:0]`|CRC|
|+2|`CRC[15:8]`|CRC|
|+3|`CRC[23:16]`|CRC|
|+4|`CRC[31:24]`|CRC|

#### CRC (`CRC`)

|Condition|Value of CRC|
|:--:|:--|
|`CRCEN` = 0|`0x00000000`|
|`CRCEN` = 1|CRC Value|

- CRC calculation must be performed on all bytes from the beginning of `DSTA` to before `DEND` (including `PAD` and `COM`).
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
OSTA
 |
 V
Meta Data Array
 |
 |<--------------------,
 |                     |
 +--> Key Value Pair --'  // member of the object
 |
 V
OEND
 |
 V
```

### Array

```
 |
 V
ASTA
 |
 V
Meta Data Array
 |
 |<-------------,
 |              |
 +--> Variant --'  // element of the array
 |
 V
AEND
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
