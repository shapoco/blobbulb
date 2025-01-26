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
|+(1 + sizeof(`SIZE_VLQ`))|`SIZE_VLQ`|`DATA`|Byte sequence|

----

## Token ID (`TID`)

|TID\[3:0\]→<br>↓TID\[7:4\]|0x0|0x1|0x2|0x3|0x4-0xF|Token<br>Shape|
|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
|0x0|PAD🟢|BODY🟢|META|||FIX0|
|0x1|OSTA🟢|OEND🟢|ASTA🟢|AEND🟢||FIX0|
|0x2||||||VLEN|
|0x3|COM|DSTA🟢|DEND🟢|||BSEQ|
|0x4|NULL🟢|||||FIX0|
|0x5||||||FIX0|
|0x6|UVL|IVL||||VLEN|
|0x7|STR🟢|||||BSEQ|
|0x8|U8|I8||BOOL🟢||FIX1|
|0x9|U16|I16||||FIX2|
|0xA|U32|I32|F32|||FIX4|
|0xB|U64|I64|F64🟢|TIME||FIX8|
|0xC|U8A|I8A||BOOLA||BSEQ|
|0xD|U16A|I16A||||BSEQ|
|0xE|U32A|I32A|F32A|||BSEQ|
|0xF|U64A|I64A|F64A|TIMEA||BSEQ|

- 🟢: compatible with JSON
- empty cell: reserved for future

----

## Syntax

### Document

```
DSTA
 |
 V
Meta Data Array
 |
 +--> BODY --> Variant --,
 |                       |
 |<----------------------'
 |
 V
DEND
```

### DSTA: Start of Document

### DEND: End of Document

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

- for object member JSON, only STR can be used for Key.

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

----

## Primitive Value Token

### Integer (`Un`, `In`)

- `n` = 8, 16, 32, or 64

|Offset|U8, I8|U16, I16|U32, I32|U64, I64|
|:--:|:--:|:--:|:--:|:--:|
|+0|`VALUE[7:0]`|`VALUE[7:0]`|`VALUE[7:0]`|`VALUE[7:0]`|
|+1||`VALUE[15:8]`|`VALUE[15:8]`|`VALUE[15:8]`|
|+2|||`VALUE[23:16]`|`VALUE[23:16]`|
|+3|||`VALUE[31:24]`|`VALUE[31:24]`|
|+4||||`VALUE[39:32]`|
|+5||||`VALUE[47:40]`|
|+6||||`VALUE[55:48]`|
|+7||||`VALUE[63:56]`|

- `VALUE`: unsigned/signed integer value (two's complement)

### Floating Point (`Fn`)

- `n` = 32 or 64

|Offset|F32|F64|
|:--:|:--:|:--:|
|+0|`VALUE[7:0]`|`VALUE[7:0]`|
|+1|`VALUE[15:8]`|`VALUE[15:8]`|
|+2|`VALUE[23:16]`|`VALUE[23:16]`|
|+3|`VALUE[31:24]`|`VALUE[31:24]`|
|+4||`VALUE[39:32]`|
|+5||`VALUE[47:40]`|
|+6||`VALUE[55:48]`|
|+7||`VALUE[63:56]`|

- `VALUE`: floating point value (IEEE754)

### Boolean (`BOOL`)

|Value|Mnemonic|
|:-:|:---:|
|0x00|`false`|
|0x01-0xff|`true`|

### Date Time (`TIME`)

|Offset|Description|
|:--:|:--|
|+0|`UNIX_TIME[7:0]`|
|+1|`UNIX_TIME[15:8]`|
|+2|`UNIX_TIME[23:16]`|
|+3|`UNIX_TIME[31:24]`|
|+4|`UNIX_TIME[39:32]`|
|+5|`UNIX_TIME[47:40]`|
|+6|`UNIX_TIME[55:48]`|
|+7|reserved|

- `UNIX_TIME`:
    - UNIX time in milliseconds
    - 56 bit signed integer

----

## Primitive Array (`xxxA`)

- packed array of primitive values

----

## Non-Primitive Value Token

### String (`STR`)

- byte array
- UTF-8

### Null (`NULL`)

- same as `null` of JavaScript

----

## Pre-defined Meta Data IDs

(no IDs defined yet)

----
