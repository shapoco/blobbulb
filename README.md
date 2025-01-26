# universal-binary-format (WIP)

## Basic Element Syntax

### Fixed-length Element

|Offset|Size|Mnemonic|Description|
|:--:|:--:|:--:|:--|
|0|1|`CC`|Control Code|
|1|defined by `CC`<br>(see table below)|`DATA`|Data|

### Variable-length Element

|Offset|Size|Mnemonic|Description|
|:--:|:--:|:--:|:--|
|0|1|`CC` (Except `KWD`)|Control Code|
|1|1-8|`DSIZEVL`|Data Size in Bytes|
|1+sizeof(`DSIZEVL`)|`DSIZE`|`DATA`|Data|

### Keyword Element

|Offset|Size|Mnemonic|Description|
|:--:|:--:|:--:|:--|
|0|1|`KWD`|Control Code|
|1|1-2|`KWDIDVL`|Keyword ID|

## CC: Control Code vs Mnemonic

- empty cell: reserved for future

|Category|Data Size<br>(Bytes)|＼CC\[3:0\]<br>CC\[7:4\]＼|0|1|2|3|4-E|F|
|:--|:--|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
|Primitive|Fixed (1)|0|U8|I8||BOOL|||
|Primitive|Fixed (2)|1|U16|I16|||||
|Primitive|Fixed (4)|2|U32|I32|F32||||
|Primitive|Fixed (8)|3|U64|I64|F64|TIME|||
|Primitive Array|Variable (1*N)|4|U8A|I8A||BOOLA|||
|Primitive Array|Variable (2*N)|5|U16A|I16A|||||
|Primitive Array|Variable (4*N)|6|U32A|I32A|F32A||||
|Primitive Array|Variable (8*N)|7|U64A|I64A|F64A|TIMEA|||
|Special|Fixed (0)|8|NULL||||||
||Fixed (0)|9|||||||
|String|Variable|A|STR||||||
||Variable|B|||||||
|Control|Fixed (0)|C|META|BODY|||||
|Control|Fixed (0)|D|SOA|EOA|SOO|EOO||PAD|
|Control|Variable|E|SOD|EOD|||||
|Control|Variable|F||||||KWD|

## DSIZE: Data Size

- TODO: Variable-Length Quantity

## KWDID: Keyword ID

|`KWDID`|`KWDIDVL`|Description|
|:--|:--|:--|
||||

## Syntax

### Document

```
SOD
 |
 V
Meta Array
 |
 +--> BODY --> Variant --,
 |                       |
 |<----------------------'
 |
 V
EOD
```

### Variant

```
 |
 +---------+---------,
 |         |         |
 V         V         V
Object   Array   Primitive
 |         |         |
 |<--------'         |
 |<------------------'
 |
 V
```

### Object

```
 |
 V
SOO
 |
 V
Meta Array
 |
 |<----------------------------,
 |                             |
 +--> Identifier --> Variant --'
 |
 V
EOO
 |
 V
```

### Array

```
 |
 V
SOAR
 |
 V
Meta Array
 |
 |<-------------,
 |              |
 +--> Variant --'
 |
 V
EOAR
 |
 V
```

### Meta Array

```
 |
 |<-------------------------------------,
 |                                      |
 +--> META --> Identifier --> Variant --'
 |
 V
```

### Identifier

```
 |
 +----,
 |    |
 V    V
STR  KWD
 |    |
 |<---'
 |
 V
```
