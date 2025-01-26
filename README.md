# Universal Binary Container (WIP)

## Basic Token Shape

### FIXn: Fixed-Length

|Offset|Size \[Bytes\]|Mnemonic|Description|
|:--:|:--:|:--:|:--|
|+0|1|`TID`|Token ID|
|+1|`n` defined by `TID`|`DATA`|Data|

### VLQ: Variable Length Quantity

|Offset|Size \[Bytes\]|Mnemonic|Description|
|:--:|:--:|:--:|:--|
|+0|1|`TID`|Token ID|
|+1|1-8|`VALUE_VLQ`|Data expressed in VLQ|

### BARY: Byte Array

|Offset|Size \[Bytes\]|Mnemonic|Description|
|:--:|:--:|:--:|:--|
|+0|1|`TID`|Token ID|
|+1|1-8|`SIZE_VLQ`|Length of `DATA` in bytes, expressed in VLQ|
|+(1 + sizeof(`SIZE_VLQ`))|`SIZE_VLQ`|`DATA`|Byte array|

----

## Token Definition

|TID\[3:0\]→<br>↓TID\[7:4\]|0x0|0x1|0x2|0x3|0x4-0xF|Token<br>Shape|
|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
|0x0|PAD🟢|BODY🟢|META|||FIX0|
|0x1|OSTA🟢|OEND🟢|ASTA🟢|AEND🟢||FIX0|
|0x2||||||VLQ|
|0x3|COM|DSTA🟢|DEND🟢|||BARY|
|0x4|NULL🟢|||||FIX0|
|0x5||||||FIX0|
|0x6|UVLQ|IVLQ||||VLQ|
|0x7|STR🟢|||||BARY|
|0x8|U8|I8||BOOL🟢||FIX1|
|0x9|U16|I16||||FIX2|
|0xA|U32|I32|F32|||FIX4|
|0xB|U64|I64|F64🟢|TIME||FIX8|
|0xC|U8A|I8A||BOOLA||BARY|
|0xD|U16A|I16A||||BARY|
|0xE|U32A|I32A|F32A|||BARY|
|0xF|U64A|I64A|F64A|TIMEA||BARY|

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
STR  UVLQ  // Key
 |    |
 |<---'
 |
 V
Variant  // Value
 |
 V
```

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

### Integer (Un, In)

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

### Floating Point (Fn)

|Offset|U32, I32|U64, I64|
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

### Boolean (BOOL)

|Value|Mnemonic|
|:-:|:---:|
|0x00|`false`|
|0x01-0xff|`true`|

### Date Time (TIME)

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

### String (STR)

- byte array
- UTF-8

### Primitive Array (xxxA)

- packed array of primitive values

### Null

- same as `null` of JavaScript

----

## Pre-defined Meta Data IDs

(no IDs defined yet)

----
