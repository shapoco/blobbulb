# universal-binary-format (WIP)

## Basic Element Syntax

### Fixed-length Element

|Offset|Size|Mnemonic|Description|
|:--:|:--:|:--:|:--|
|0|1|`CC`|Control Code|
|1|defined by `CC`<br>(see table below)|`DATA`|Data|

### Variable-length Element 

#### Except Keyword

|Offset|Size|Mnemonic|Description|
|:--:|:--:|:--:|:--|
|0|1|`CC` != `KWD`|Control Code|
|1|1-8|`DSZ_VLQ`|Data Size in Bytes|
|1+sizeof(`DSZ_VLQ`)|`DSZ`|`DATA`|Data|

#### Keyword

|Offset|Size|Mnemonic|Description|
|:--:|:--:|:--:|:--|
|0|1|`CC` == `KWD`|Control Code|
|1|1-2|`KID_VLQ`|Keyword ID|

## CC: Control Code vs Mnemonic

- empty cell: reserved for future

|Category|Data Size<br>(Bytes)|＼CC\[3:0\]<br>CC\[7:4\]＼|0|1|2|3|4|...|F|
|:--|:--|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
|Primitive|Fixed (1)|0|U8|I8|||BOOL|||
|Primitive|Fixed (2)|1|U16|I16||||||
|Primitive|Fixed (4)|2|U32|I32|F32|||||
|Primitive|Fixed (8)|3|U64|I64|F64||TIME|||
|Primitive Array|Variable (1*N)|4|U8A|I8A|||BOOLA|||
|Primitive Array|Variable (2*N)|5|U16A|I16A||||||
|Primitive Array|Variable (4*N)|6|U32A|I32A|F32A|||||
|Primitive Array|Variable (8*N)|7|U64A|I64A|F64A||TIMEA|||
|Special|Fixed (0)|8|NULL|||||||
||Fixed (0)|9||||||||
|String|Variable|A|STR|||||||
||Variable|B||||||||
|Control|Fixed (0)|C|SOO|EOO|SOA|EOA||||
|Control|Fixed (0)|D|META|BODY|||||PAD|
|Control|Variable|E|SOD|EOD||||||
|Control|Variable|F|||||||KWD|

## DSZ: Data Size

- TODO: Variable-Length Quantity

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

### SOD: Start of Document

### EOD: End of Document


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
SOA
 |
 V
Meta Array
 |
 |<-------------,
 |              |
 +--> Variant --'
 |
 V
EOA
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

### KWD: Keyword

|`KID`|`KID_VLQ`|Raw String|
|:--|:--|:--|
||||

----

### Integer

### Floating Point

### Boolean

### Date Time

### String

### Null

----
